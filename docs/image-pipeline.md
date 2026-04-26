# Image Pipeline

**Scope:** This document describes the image processing subsystem — how uploads become responsive `<picture>`/`srcset` markup, how focal points are persisted and edited, and how operators regenerate variants when the schema or quality settings change.

**Audience:** A new collaborator who has read [CLAUDE.md](../CLAUDE.md) and is touching anything under `src/Core/image_*.php`, `src/Core/gd_backend.php`, `src/Core/magick_backend.php`, `public/assets/js/image-crop.js`, or `public/uploads/`.

---

## Table of contents

1. [Architecture at a glance](#architecture-at-a-glance)
2. [Backend abstraction](#backend-abstraction)
3. [Asset types](#asset-types)
4. [Variant generation](#variant-generation)
5. [Quality tiering](#quality-tiering)
6. [Filename convention](#filename-convention)
7. [Focal-point editor (JS + DB)](#focal-point-editor-js--db)
8. [`srcset` rendering](#srcset-rendering)
9. [Upload validation](#upload-validation)
10. [Operator scripts](#operator-scripts)

---

## Architecture at a glance

```text
┌──────────────────────────┐
│ Controller upload action │  validates MIME via finfo, moves file to
│ (tool / profile /        │  public/uploads/{type}/{name}.{ext},
│  incident)               │  then calls ImageProcessor.
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│ ImageProcessor (facade)  │  detects backend once per request,
│ src/Core/image_processor │  dispatches resize / cropResize /
│                          │  generateVariants / generateResizedVariants.
└────────────┬─────────────┘
             │
   ┌─────────┴─────────┐
   ▼                   ▼
┌────────────┐   ┌────────────┐
│ Magick     │   │ Gd         │   GD has no AVIF — that branch
│ Backend    │   │ Backend    │   silently no-ops on AVIF requests.
│ (CLI)      │   │ (ext-gd)   │
└────────────┘   └────────────┘
             │
             ▼
┌──────────────────────────┐
│ public/uploads/{tools,   │  source + width-suffixed siblings
│ profiles, incidents}/    │  for each variant (and .webp / .avif
│                          │  format copies for non-webp sources).
└──────────────────────────┘
```

Three things are deliberately **not** image-pipeline concerns:

- **`src/Core/file_cache.php`** — generic JSON TTL cache for query results in `storage/cache/`. Unrelated to image processing despite the name proximity. Documented in passing only.
- **CDN / cache headers** — handled at the web-server layer (`.htaccess` / nginx vhost), not in PHP.
- **CSP enforcement** — image markup is governed by the `img-src` directive, not by anything this subsystem does. See `src/Controllers/csp_controller.php` for violation reporting.

---

## Backend abstraction

The pipeline runs against a small interface so the backend can swap based on what's available on the host.

| File | Role |
| --- | --- |
| [src/Core/image_backend.php](../src/Core/image_backend.php) | `ImageBackend` interface — `resize`, `cropResize`, `createFormatVariant`, `getIntrinsicWidth`, `autoOrient`. |
| [src/Core/image_processor.php](../src/Core/image_processor.php) | `ImageProcessor` facade — static methods only. Memoizes the backend in a static field, so detection runs once per request. |
| [src/Core/magick_backend.php](../src/Core/magick_backend.php) | Shells out to the `magick` CLI. Preferred when present (better quality, AVIF support, EXIF handling). |
| [src/Core/gd_backend.php](../src/Core/gd_backend.php) | Pure-PHP fallback via ext-gd. **Returns `null` from `createFormatVariant()` for AVIF** — no AVIF without ImageMagick. |

### Selection logic

`ImageProcessor::detectBackend()` ([image_processor.php:25](../src/Core/image_processor.php#L25)):

1. If `exec` is in `disable_functions`, return `GdBackend` immediately — no shelling out.
2. Try `$_ENV['MAGICK_BINARY']`, then `/bin/magick`, `/usr/bin/magick`, `/usr/local/bin/magick`, then bare `magick` on `$PATH`. The first one whose `magick -version` exits 0 wins.
3. Fall back to `GdBackend`.

Set `MAGICK_BINARY` in `.env` to pin a specific path on hosts where the binary is in an unusual location.

### Magick vs GD differences

| Feature | Magick | GD |
| --- | --- | --- |
| AVIF output | yes | **no** (`createFormatVariant` returns `null`) |
| Resize filter | Lanczos | bicubic via `imagecopyresampled` |
| EXIF auto-orient | `-auto-orient` flag | manual `imagerotate` / `imageflip` per orientation byte |
| Strip metadata | `-strip` on every operation | implicit (re-encode drops it) |
| Failure logging | `error_log()` from `MagickBackend::exec()` on non-zero exit | `error_log()` from individual methods on decode failure |

EXIF auto-orient runs implicitly via the source re-encode that `cropResize` performs, but only matters for JPEGs — PNG and WebP carry no `Orientation` tag.

---

## Asset types

Three distinct surfaces use the pipeline. Each has its own table, upload route, storage directory, aspect-ratio policy, variant width set, and focal-point support.

| Asset | Table (cols of interest) | Upload route | Storage | Aspect | Variant widths | Focal point |
| --- | --- | --- | --- | --- | --- | --- |
| Tool images | `tool_image_tim` (`file_name_tim`, `width_tim`, `focal_x_tim`, `focal_y_tim`) | `POST /tools/{id}/images` (multi-upload) | `public/uploads/tools/` | 3:2 (cropped) | `ImageProcessor::VARIANT_WIDTHS` = `[1200, 900, 600, 400]` | yes |
| Avatars | `account_image_aim` (`file_name_aim`, `width_aim`, `focal_x_aim`, `focal_y_aim`) | profile edit form | `public/uploads/profiles/` | 1:1 (square crop) | `[360, 150, 80]` | yes |
| Incident photos | `incident_photo_iph` (`file_name_iph`, `width_iph`, `height_iph`) | `POST /incidents` (multi-upload) | `public/uploads/incidents/` | preserve native | varies (resize-only, see below) | **no** — evidence photos preserve original framing |

The reason for three different aspect-ratio policies is product, not technical:

- **Tools** are browsed in a 3:2 card grid — uniform aspect makes the grid scan cleanly.
- **Avatars** appear in circles and small squares — a 1:1 crop removes the "tall avatar in a circle" failure mode.
- **Incident photos** are evidence — cropping would discard the very thing the photo proves.

### Why height is stored for incidents but not avatars

`account_image_aim` carries `width_aim` only — no height column — because the upload pipeline crops avatars to a square, so height = width. `incident_photo_iph` carries both `width_iph` and `height_iph` because incident photos preserve native aspect, so the rendered `<img>` needs per-photo `width` / `height` attrs to avoid CLS. Tool images carry `width_tim` for the same srcset reason and live under a 3:2 contract enforced by the crop pipeline.

The canonical schema for all four columns (and every view / SP that selects them) is [dumps/warren-jeremy-dump-phase6.sql](../dumps/warren-jeremy-dump-phase6.sql) — treat the dump as the single source of truth.

---

## Variant generation

Two entry points, both in [src/Core/image_processor.php](../src/Core/image_processor.php). Both write `{base}-{w}w.{ext}` siblings next to the source for every width strictly less than the source width, then (for non-WebP sources) write `.webp` and `.avif` format copies for each variant *and* the source.

### `generateVariants()` — used by tools and avatars

[image_processor.php:114](../src/Core/image_processor.php#L114). Crops to a target aspect ratio around the focal point, then resizes. Used when product wants uniform aspect.

Signature:

```php
ImageProcessor::generateVariants(
    string $sourcePath,
    array  $widths       = ImageProcessor::VARIANT_WIDTHS, // tool defaults; profiles pass [360, 150, 80]
    int    $focalX       = 50,                              // 0–100, percent from left
    int    $focalY       = 50,                              // 0–100, percent from top
    ?float $aspectRatio  = null,                            // null = 3:2 default; profiles pass 1.0
    ?string $outputDir   = null,                            // null = source's directory
    bool    $preserveSource = false,                        // true during regen to skip re-encoding the source
): array; // returns paths of every file written; [] on failure (with cleanup)
```

### `generateResizedVariants()` — used by incidents

[image_processor.php:196](../src/Core/image_processor.php#L196). Preserves source aspect — no crop. The function is otherwise structurally similar.

```php
ImageProcessor::generateResizedVariants(
    string $sourcePath,
    array  $widths,
    ?string $outputDir   = null,
    bool    $preserveSource = false,
): array;
```

### Failure semantics

Either method catches `RuntimeException` mid-loop, calls `cleanupFiles()` on everything written so far, and returns `[]`. Callers should treat an empty return as "nothing was created" and not assume partial state.

### `preserveSource` flag

The default behavior re-encodes the source in place (so the source matches the new variant set). The regen flow passes `preserveSource: true` because it has already deleted the old variants and wants the source untouched.

---

## Quality tiering

`ImageProcessor::qualityForWidth()` ([image_processor.php:277](../src/Core/image_processor.php#L277)) tiers AVIF/WebP quality by output width — smaller variants get lower quality because the artifacts are imperceptible at thumbnail sizes:

| Width range | AVIF quality | WebP quality | JPEG (source) quality |
| --- | --- | --- | --- |
| ≤ 240 px | 38 | 65 | — |
| 240–820 px | 45 | 72 | 82 |
| ≥ 820 px | 55 | 82 | 90 |

### GD JPEG/PNG/WebP defaults

[gd_backend.php:12-14](../src/Core/gd_backend.php#L12-L14) — used when GD is the backend and it re-encodes a source through `saveImage()` (resize / cropResize):

```php
private const int JPEG_QUALITY  = 90;
private const int PNG_COMPRESSION = 4;
private const int WEBP_QUALITY  = 85;
```

The `qualityForWidth()` tier above governs *format-variant* output (the `.webp` / `.avif` siblings), not the source re-encode.

---

## Filename convention

For a source named `tool_xyz.jpg`, `generateVariants()` writes:

```text
public/uploads/tools/
├── tool_xyz.jpg         ← source (re-encoded unless preserveSource=true)
├── tool_xyz.webp        ← format variant of source
├── tool_xyz.avif        ← format variant of source (only if Magick is the backend)
├── tool_xyz-1200w.jpg   ← width variant
├── tool_xyz-1200w.webp
├── tool_xyz-1200w.avif
├── tool_xyz-900w.jpg
├── tool_xyz-900w.webp
├── tool_xyz-900w.avif
├── tool_xyz-600w.jpg
├── tool_xyz-600w.webp
├── tool_xyz-600w.avif
├── tool_xyz-400w.jpg
├── tool_xyz-400w.webp
└── tool_xyz-400w.avif
```

Variants whose width is `>=` the source width are skipped — `array_filter($widths, fn($w) => $w < $sourceWidth)`. So a 700-pixel-wide upload will only produce 600w and 400w variants.

If the source is itself `.webp`, no `.webp`/`.avif` siblings are written (the source already is one), and the matching branches in `getAvailableVariants()` / `deleteVariants()` skip them.

---

## Focal-point editor (JS + DB)

Focal point is stored as two `TINYINT UNSIGNED` columns (0–100) representing a percentage offset from top-left. Defaults to 50/50 (centered).

### Server side

The relevant DB columns:

- `tool_image_tim.focal_x_tim` / `focal_y_tim`
- `account_image_aim.focal_x_aim` / `focal_y_aim`

Used at two points:

1. **Variant generation.** The values flow into `ImageProcessor::generateVariants(focalX:, focalY:)` so each crop is anchored on the same point.
2. **Render time.** Views select the values into render data (`primary_focal_x` etc.) and emit them as the CSS custom properties consumed by the `object-position` rules in `tools.css` / `dashboard.css`.

Any new view or stored procedure that joins `tool_image_tim` or `account_image_aim` must select the focal columns through to the render layer; if a routine omits them, the rendered `<img>` silently falls back to the 50/50 default and crops shift off-center. The dump's view and SP definitions are authoritative — when adding a column to either image table, grep the dump for routines that join the table and propagate the column there.

### Client side

[public/assets/js/image-crop.js](../public/assets/js/image-crop.js) is a single `ImageCrop` class that manages a `<dialog>` element. Two open modes:

- **Upload mode** — `NT.crop.openUpload(file, opts)` — show a draggable preview of an unsaved file, capture the focal point on confirm, and write hidden `focal_x` / `focal_y` form fields before submit.
- **Reposition mode** — `NT.crop.openReposition(src, fx, fy, id, opts)` — load an existing image with its current focal point, capture the new value, and `PATCH /tools/{id}/images/{img}` (or `PATCH /profile/image`) with the updated `{focal_x, focal_y}` JSON.

Both modes flow through the same `<dialog>` markup, the same drag/touch handlers, and the same confirm/cancel buttons. The instance is a singleton (`ImageCrop.#instance`).

### Reposition endpoints

| Route | Controller method | Behavior |
| --- | --- | --- |
| `PATCH /tools/{id}/images/{img}` | `ToolController::updateImage` | Validates `{focal_x, focal_y}` ∈ 0..100, deletes existing variants for that image, regenerates with the new focal point, updates the row. |
| `PATCH /profile/image` | `ProfileController::repositionImage` | Same flow, but for the user's primary avatar image. |

Both endpoints call `ImageProcessor::deleteVariantsOnly()` (keeps the source) before `generateVariants()` so the source-focal alignment stays consistent.

---

## `srcset` rendering

Two helpers in [image_processor.php](../src/Core/image_processor.php) build `<picture>`-ready data:

### `getAvailableVariants()` — discover what exists

[image_processor.php:314](../src/Core/image_processor.php#L314). Probes the upload directory for the variant files, returns a width-keyed array of `['file' => ..., 'webp' => ..., 'avif' => ...]`. Falls back to `getimagesize()` if the source's full width wasn't passed (the `width_tim` / `width_aim` / `width_iph` DB column should always be passed when known).

### `buildSrcset()` — turn variants into HTML attributes

[image_processor.php:378](../src/Core/image_processor.php#L378). Returns:

```php
[
    'srcset'     => '/uploads/tools/tool_xxx-400w.jpg?v=1234 400w, …',
    'webpSrcset' => '/uploads/tools/tool_xxx-400w.webp?v=1234 400w, …',
    'avifSrcset' => '/uploads/tools/tool_xxx-400w.avif?v=1234 400w, …',
]
```

The `?v={mtime}` cache-buster is per-file, not per-deploy — so a regen flips just the affected files' query strings.

Views then render a `<picture>` with three `<source>` tags (AVIF first, then WebP, then the original-format `<img>` fallback). Browsers pick the best supported format.

---

## Upload validation

Every upload action validates with `finfo(FILEINFO_MIME_TYPE)` against the file contents — never against `$_FILES['type']`, which is client-supplied and trivially spoofable.

| Controller | Method | Allowed MIMEs | Max size |
| --- | --- | --- | --- |
| `ToolController` | `validateImage()` / `moveImage()` ([tool_controller.php:944](../src/Controllers/tool_controller.php#L944)) | `image/jpeg`, `image/png`, `image/webp` | 5 MB |
| `ProfileController` | `validateAvatar()` / `moveAvatar()` ([profile_controller.php:605](../src/Controllers/profile_controller.php#L605)) | same | same |
| `IncidentController` | inline finfo check in `storePhotos()` ([incident_controller.php:485](../src/Controllers/incident_controller.php#L485)) | same | same |

Filenames are generated via `uniqid('{prefix}_', true) . '.' . $ext`. The original client-supplied filename is never used on disk.

On any post-move failure (variant generation throws, DB insert fails), the controller calls `ImageProcessor::deleteVariants()` to clean up before redirecting with errors. There is no orphan-file reaper for half-failed uploads — failure cleanup happens in the controller path.

---

## Operator scripts

Two scripts live under [scripts/](../scripts/). Run them from the repo root: `php scripts/{name}.php [flags]`.

### `regenerate-variants.php`

[scripts/regenerate-variants.php](../scripts/regenerate-variants.php). Re-runs `generateVariants()` against every row in `tool_image_tim` and `account_image_aim`, deleting the old variant set first. Run when:

- `ImageProcessor::VARIANT_WIDTHS` changes.
- `qualityForWidth()` is retuned.
- The aspect ratio for tools or avatars is changed.
- The Magick binary becomes available on a host that previously fell back to GD (so AVIF can finally land).

Flags:

| Flag | Effect |
| --- | --- |
| `--dry-run` | Lists what would change without touching disk. |
| `--tools-only` | Skips the avatar pass. |
| `--profiles-only` | Skips the tool pass. |

The script also writes the post-regen `width_tim` back to the DB if it changed (e.g., after the `[820,750,540,360]` → `[1200,900,600,400]` widening). It does not currently update `width_aim` — that column was added later and avatars are square so the value is recoverable from the file.

The regen flow deletes the **old** variant widths (`[820, 750, 540, 360]` for tools) before generating the **new** ones — both lists are hardcoded at the top of the script. If you bump widths again, update both lists in the same commit.

### `purge-deleted-images.php`

[scripts/purge-deleted-images.php](../scripts/purge-deleted-images.php). Reaps image files for tools that were soft-deleted ≥ 90 days ago (configurable via `--days=N`). Calls `Tool::deleteAllImages()` (which clears the DB rows) then `ImageProcessor::deleteVariants()` per filename.

Soft-deletes don't auto-purge — the grace window lets admins recover accidental deletes. This script is the one that finally frees disk.

Flags: `--dry-run`, `--days=N` (default 90).
