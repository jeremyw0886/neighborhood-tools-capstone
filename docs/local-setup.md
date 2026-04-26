# Local Setup

**Scope:** end-to-end procedure for cloning NeighborhoodTools and running it locally. Covers prerequisites, required PHP extensions, database import, optional services, verification, and the common failure modes.

**Audience:** anyone setting up a local development environment for the first time, or recovering from a broken local install.

---

## Table of contents

1. [Prerequisites](#prerequisites)
2. [Required PHP extensions](#required-php-extensions)
3. [Setup procedure](#setup-procedure)
4. [Storage directory writability](#storage-directory-writability)
5. [Local hostname and HTTPS](#local-hostname-and-https)
6. [Test accounts](#test-accounts)
7. [Verification](#verification)
8. [Working without optional services](#working-without-optional-services)
9. [Common dev tasks](#common-dev-tasks)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

| Component | Requirement |
| --- | --- |
| PHP | 8.4 or later. Production runs 8.5.2; the codebase uses 8.4+ features (typed constants, `json_validate()`, `#[\Override]`, `array_find()` / `array_any()` / `array_all()`, property hooks). PHP 8.2/8.3 will not run. |
| MySQL | 8.0+ (any minor version that supports `utf8mb4_0900_ai_ci`, which is the default since 8.0.1). MariaDB is not tested. |
| Web server | Apache with `mod_rewrite` enabled, **or** nginx using `config/nginx/neighborhoodtools.local.conf.example` as the vhost. |
| Composer | 2.x. Used once at install time; not needed at runtime. |
| `magick` CLI binary | **Optional.** Image processing prefers ImageMagick when present (better quality, AVIF support); falls back to ext-gd otherwise. See [docs/image-pipeline.md](image-pipeline.md). |

---

## Required PHP extensions

The following extensions must be enabled in the PHP build serving the application:

| Extension | Used by |
| --- | --- |
| `pdo_mysql` | All database access (`Database::connection()`, every model). |
| `mbstring` | String handling across controllers and the auth layer. |
| `fileinfo` | MIME-type validation on every image upload (tools, avatars, incidents). |
| `exif` | Image EXIF auto-orient in the GD backend. |
| `gd` | Pure-PHP image processing fallback when no `magick` binary is available. |
| `json`, `session` | Universal — bundled with every PHP build. |

Composer's `composer.json` declares `"php": ">=8.2"` for tooling compatibility, but the application requires 8.4+ as noted above.

---

## Setup procedure

Five-step happy path. Each step has expanded notes below the list.

```bash
# 1. Clone and install dependencies
git clone https://github.com/jeremyw0886/neighborhood-tools-capstone.git
cd neighborhood-tools-capstone
composer install

# 2. Create the database
mysql -u root -p -e "CREATE DATABASE neighborhoodtools;"

# 3. Import the schema dump
mysql -u root -p neighborhoodtools < dumps/warren-jeremy-dump-phase6.sql

# 4. Configure .env
#    The repo provides .env.example; composer install copies it to .env
#    automatically via the post-install-cmd script. If .env was not created
#    (e.g., composer install was skipped), run: cp .env.example .env
#    Then set DB_USERNAME, DB_PASSWORD, and any optional-service keys.
$EDITOR .env

# 5. Point the web server at public/ and visit the site
#    Apache: virtual host with DocumentRoot pointing at public/
#    nginx:  see config/nginx/neighborhoodtools.local.conf.example
```

### Notes per step

**1. Clone and install** — `composer install` runs the `post-install-cmd` script that auto-copies `.env.example` to `.env` if no `.env` exists. The vendor directory is gitignored; `composer.lock` is committed.

**2. Create the database** — A bare `CREATE DATABASE` is sufficient. The dump configures `utf8mb4_0900_ai_ci` collation explicitly, which is also MySQL 8's default, so no explicit `COLLATE` clause is required.

**3. Import the dump** — The dump is the canonical schema. It includes table structure, views, stored procedures, triggers, helper functions, and seeded data (categories, neighborhoods, ZIP codes, vector icons, test accounts, sample tools, sample borrows, sample disputes/events/incidents).

**4. Configure `.env`** — At minimum, set the four `DB_*` values. The rest of the file documents which env vars are required vs optional; see [Working without optional services](#working-without-optional-services) below.

**5. Web server** — Document root must point at `public/`, not the repo root. Front-controller routing through `public/index.php` requires URL rewriting; Apache uses the checked-in `public/.htaccess`, nginx uses the example vhost as a starting point.

---

## Storage directory writability

Three directories under `storage/` must be writable by the web-server process:

| Directory | Purpose |
| --- | --- |
| `storage/cache/` | File-based TTL cache for expensive query results (see [src/Core/file_cache.php](../src/Core/file_cache.php)). |
| `storage/sessions/` | PHP session files. |
| `storage/rate-limits/` | Rate-limiter state for auth endpoints. |

Each directory ships with a `.gitkeep` (committed) and an `.htaccess` denying direct web access; runtime files inside are gitignored.

A typical fresh-clone permission fix:

```bash
chmod -R 775 storage/
```

The web-server user (`www-data`, `_www`, etc., depending on platform) must own or share write access. On macOS with Apache running as the local user, the default permissions are usually sufficient.

Permission failures manifest as one of:

- `Failed to open stream: Permission denied` in the PHP error log when `FileCache` tries to write.
- Sessions silently failing — login appears to succeed but the next request acts as if logged out.
- Rate-limit logic erroring on auth attempts.

---

## Local hostname and HTTPS

Several features assume an HTTPS context:

- **Cloudflare Turnstile** widgets refuse to render over plain HTTP for non-localhost hostnames.
- **`Secure` session cookies** (`config/app.php` sets `cookie_secure: true`) are dropped by browsers over HTTP, breaking session persistence.
- **CSP** declares `upgrade-insecure-requests`, which Chrome will silently rewrite mixed-content requests for.

The recommended local setup uses a hostfile entry plus a self-signed certificate:

```bash
# /etc/hosts
127.0.0.1   neighborhoodtools.local
```

Then configure the local web server to serve `https://neighborhoodtools.local` (port 8890 or any other available port). `.env` is set to match:

```ini
APP_URL=https://neighborhoodtools.local:8890
TURNSTILE_ALLOWED_HOSTNAMES=neighborhoodtools.local
```

Plain `localhost` over HTTP will work for browsing but will produce non-obvious failures on auth and any page that depends on session state.

---

## Test accounts

The dump seeds seven accounts. All share the password `password123` — these are demo credentials, intended for local dev and exploratory testing only.

| ID | Name | Role | Notes |
| --- | --- | --- | --- |
| 1 | Allyson | Member | Standard borrower/lender. |
| 2 | Jeremiah | Member | Standard borrower/lender. |
| 3 | Chantelle | Member | Standard borrower/lender. |
| 4 | Alec | Member | Standard borrower/lender. |
| 5 | Admin | Admin | Admin dashboard access. |
| 6 | Jeremy | Super Admin | Full admin + super-admin-only actions (role assignment, account purge). |
| 7 | Pending | Member (pending) | Account in `pending` status — exercises the "awaiting approval" auth path. |

The dump's seeded data also covers a realistic mix of active borrows, completed transactions, ratings, disputes, events, and incidents so the dashboard and admin views are populated on first load.

For production deployments, every seeded password must be reset before exposing the site. The dump is intended as a development artifact, not a production seed.

---

## Verification

After setup, the following loads confirm each layer of the stack:

| URL | Confirms |
| --- | --- |
| `https://<host>/styleguide` | Asset pipeline (CSS, fonts, vector icons) is intact. |
| `https://<host>/login` (load + log in as Admin / `password123`) | DB connection, session storage, CSRF, Turnstile dev bypass. |
| `https://<host>/dashboard` (post-login) | Session persistence, dashboard XHR partial swaps. |
| `https://<host>/tools` | Tool grid, image variant delivery (Network panel shows AVIF/WebP being served), search/filter/pagination. |
| `https://<host>/admin` (logged in as Admin) | Admin role gate, platform-stats query, admin XHR endpoints. |

A full page load with the browser console open is recommended for the first verification — the console catches missing-asset 404s, CSP violations, and JS errors that would not show up in the rendered HTML.

---

## Working without optional services

The application runs without Stripe, Cloudflare Turnstile, and Google Geocoding configured. Each is exercised only when the related flow is touched.

| Service | Required for | When `.env` keys can be blank |
| --- | --- | --- |
| **Cloudflare Turnstile** | Production auth-form bot protection. | Always when `APP_DEBUG=true` (verification is bypassed). |
| **Stripe** | Security-deposit payments on borrow handovers. | When the deposit flow is not being tested. The rest of the app — browsing, borrowing, ratings, admin, events, disputes, incidents — works without Stripe. |
| **Google Geocoding** | New ZIP code lookups (latitude/longitude). | When working with the seeded ZIP codes only. The dump includes coordinates for every seeded ZIP; the geocoder is only called when a new ZIP is added. |

Setting `APP_DEBUG=true` in `.env` enables the Turnstile bypass and switches the asset URLs from minified bundles to source files. It must never be set in production — it disables bot protection and exposes detailed errors.

---

## Common dev tasks

### Adding a new PHP class

```bash
composer dump-autoload
```

Required after creating any new file under `src/` (controllers, models, core classes). The classmap is committed; production does not regenerate it on deploy.

### Editing CSS or JS

Local dev (`APP_DEBUG=true`) serves source files directly. Rebuilds are not required during development.

Before commit and deploy, the bundle must be regenerated:

```bash
php build-assets.php             # CSS + JS
php build-assets.php --css-only  # CSS only
php build-assets.php --js-only   # JS only
```

`build-assets.php` writes to `public/assets/css/style.min.css`, the per-file `*.min.css` / `*.min.js` siblings, and a content-hash to `config/asset-version.php`. See [docs/deployment.md](deployment.md) for the full asset pipeline.

### Resetting the local database

To start over with a clean schema and seed data:

```bash
mysql -u root -p -e "DROP DATABASE neighborhoodtools; CREATE DATABASE neighborhoodtools;"
mysql -u root -p neighborhoodtools < dumps/warren-jeremy-dump-phase6.sql
```

The dump is idempotent at the `DROP DATABASE → CREATE DATABASE → import` level. Partial re-imports of the dump into an existing schema are not supported (the dump's teardown step expects to recreate everything).

---

## Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| `404 Not Found` on every URL except `/` | Web server is not rewriting requests through `public/index.php`. | Apache: enable `mod_rewrite` and confirm `AllowOverride All` for the document root. nginx: confirm the vhost matches `config/nginx/neighborhoodtools.local.conf.example`. |
| `500 Internal Server Error` with no detail in the browser | `APP_DEBUG=false` is suppressing the error message. | Set `APP_DEBUG=true` in `.env`, reload, and read the actual error. Check the PHP error log for the underlying cause. |
| `Failed to open stream: Permission denied` in PHP error log, paths under `storage/` | The web-server process can't write to the storage subdirectories. | `chmod -R 775 storage/`; confirm ownership matches the web-server user. |
| Login appears to succeed but the next request acts as if logged out | Session files aren't being written, or `Secure` cookies are being dropped over HTTP. | Verify `storage/sessions/` is writable. Switch to HTTPS for any non-localhost hostname. |
| `Class App\Foo\Bar not found` | Autoloader is stale. | `composer dump-autoload`. |
| Turnstile widget renders but every login fails with "verification failed" | `TURNSTILE_ALLOWED_HOSTNAMES` does not include the local hostname. | Add the local hostname to the comma-separated list in `.env`, or set `APP_DEBUG=true` to bypass verification entirely. |
| `Stripe::createIntent` errors during borrow handover | `STRIPE_*` keys are blank or invalid. | Provide test-mode keys from Stripe Dashboard, or skip the deposit flow during testing. |
| Image variants 404 (only the source loads) | Variants haven't been generated yet. | Run `php scripts/regenerate-variants.php` to backfill. See [docs/image-pipeline.md](image-pipeline.md). |
| `Illegal mix of collations` errors on JOINs | (Should not occur after the collation standardization.) Local DB was created before the standardization and has tables in `utf8mb4_unicode_ci`. | Drop and re-import: `DROP DATABASE neighborhoodtools; CREATE DATABASE neighborhoodtools;` then re-import the dump. |
| Browser console shows CSP violations on every page | A recent edit introduced an inline style (`style="…"` attribute or `element.style.…` JS assignment). | The CSP is `style-src 'self'` with no `'unsafe-inline'`. Move the style to a stylesheet or use `NT.style.setRule()`/constructable stylesheets. |

If the symptom isn't covered above, the PHP error log (`logs/error.log` or wherever the local PHP is configured to write) usually carries the actual stack trace — `APP_DEBUG=true` surfaces it in the browser response as well.
