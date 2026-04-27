# Deployment Runbook

**Scope:** Procedures for deploying NeighborhoodTools to production, configuring production-only services (Stripe webhook, Cloudflare Turnstile, cron), and recovering from failed deploys.

**Audience:** anyone with SSH access to the SiteGround host.

---

## Table of contents

1. [Quick reference](#quick-reference)
2. [Walk-through: deploy a one-line CSS fix](#walk-through-deploy-a-one-line-css-fix)
3. [Hosts](#hosts)
4. [Secrets management](#secrets-management)
5. [Pull / deploy procedure](#pull--deploy-procedure)
6. [Database backup before any DDL](#database-backup-before-any-ddl)
7. [Asset rebuild](#asset-rebuild)
8. [Composer](#composer)
9. [PHP version](#php-version)
10. [Cron install](#cron-install)
11. [Stripe webhook wiring](#stripe-webhook-wiring)
12. [Cloudflare Turnstile wiring](#cloudflare-turnstile-wiring)
13. [MySQL `sql_mode` baked into routines](#mysql-sql_mode-baked-into-routines)
14. [Post-deploy smoke checks](#post-deploy-smoke-checks)
15. [Rollback](#rollback)

---

## Quick reference

| What | Value |
| --- | --- |
| Production domain | `https://neighborhoodtools.org` |
| Parked redirect | `neighborhoodtools.com` → `.org` |
| Host | SiteGround |
| SSH port | **18765** (not 22) |
| SiteGround path | `/home/<siteground-user>/public_html/` |
| `php` binary on prod | `/usr/local/bin/php` |
| PHP version on prod | 8.5.2 |
| Coding target | PHP 8.4+ features |
| Live `.env` location | `/home/<siteground-user>/public_html/.env` (never committed) |
| DB collation | `utf8mb4_0900_ai_ci` (MySQL 8 default — matches the dump) |

`<siteground-user>` is a placeholder for the actual SiteGround account identifier (visible in Site Tools → Dashboard → "Server Information"). The real value is intentionally not committed — the substitution happens at command-execution time on the host.

`scp` uses **capital** `-P` for the port, not lowercase `-p`:

```bash
scp -P 18765 file.txt user@host:/path/
```

---

## Walk-through: deploy a one-line CSS fix

End-to-end flow for a one-line edit to a bundled CSS file.

```bash
# 1. Make the edit locally
$EDITOR public/assets/css/components.css

# 2. Rebuild the bundle and bump the version hash
php build-assets.php
git add public/assets/css/components.css public/assets/css/style.min.css \
        public/assets/css/components.min.css config/asset-version.php
git commit -m "fix(css): correct button hover contrast"
git push

# 3. SSH into SiteGround
ssh -p 18765 user@host
cd ~/public_html

# 4. Pull. See "Pull / deploy procedure" if untracked / modified file
#    warnings appear in public/uploads/.
git pull origin main

# 5. (Optional) Verify the new asset-version landed
cat config/asset-version.php

# 6. Smoke-check (see "Post-deploy smoke checks")
curl -I https://neighborhoodtools.org/
curl -I https://neighborhoodtools.org/login
```

CSS-only changes do not require `composer install`, database work, or cron updates.

---

## Hosts

NeighborhoodTools runs on a single SiteGround shared-hosting account. All requests resolve to `/home/<siteground-user>/public_html/`, with Apache serving `public/` as the document root via `.htaccess`.

The `.com` domain is registered defensively and parked at the registrar level — a 301 redirect points it to `.org`. DNS changes on `.org` propagate normally; the `.com` redirect remains independent of those changes.

### SSH access

SiteGround uses port `18765` rather than the default `22`. Two consequences:

- **`ssh`** — pass `-p 18765` (lowercase).
- **`scp`** — pass `-P 18765` (uppercase). Mixing the case fails with a non-obvious error message.

An `~/.ssh/config` entry removes the need to repeat the port and key on every connection:

```ssh-config
Host neighborhoodtools-prod
    HostName <your-siteground-host>
    User <your-siteground-user>
    Port 18765
    IdentityFile ~/.ssh/<your-key>
```

Subsequent connections become `ssh neighborhoodtools-prod` and `scp file neighborhoodtools-prod:/path/`.

---

## Secrets management

The live `.env` lives at `/home/<siteground-user>/public_html/.env` and is never committed. `.gitignore` blocks `.env` and any `.env.*.local`; `composer install` does not ship it.

Rotation procedures, by credential:

| Secret | Rotation procedure |
| --- | --- |
| `DB_PASSWORD` | SiteGround Site Tools → MySQL → Manage → reset password. The new value takes effect immediately and SiteGround offers no grace window during which both passwords are valid. The prod `.env` must be updated to the new value as part of the same operation. |
| `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY` | Stripe Dashboard → Developers → API keys → Roll. Updates take effect immediately. The prod `.env` must be updated first, or in-flight payments fail during the window between rotation and update. |
| `STRIPE_WEBHOOK_SECRET` | Stripe Dashboard → Developers → Webhooks → endpoint → Roll signing secret. Webhooks fail signature verification until both sides match, so the prod `.env` must be updated immediately. |
| `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | Cloudflare Dashboard → Turnstile → site → Rotate. Auth forms break for a few seconds during rotation. |
| `GOOGLE_GEOCODING_API_KEY` | Google Cloud Console → APIs & Services → Credentials → restrict + regenerate. Geocoding is non-blocking (existing zip codes are seeded with coordinates), making this a low-urgency rotation. |

The order of operations matters: the new value lands in the prod `.env` first, then the old credential is invalidated. Reversing that order produces downtime for whichever feature relies on the key.

**Audit after rotation** — confirm the new value is in `.env` and not committed:

```bash
git status .env
# .env is gitignored; output should be empty.
```

---

## Pull / deploy procedure

Production `git pull` is the standard deploy. Two operational details apply: the tracked `public/uploads/` directory and the host-specific cron file.

### Tracked uploads in `public/uploads/`

Every uploaded image (sources + every variant in every format) is committed; the directory contains 600+ files.

The repo ships with seeded images committed so a fresh clone + database import + asset rebuild produces a fully populated demo site. Untracking uploads would leave the demo without the seeded tool photos, avatars, and incident evidence images.

Variant regeneration on production (after retuning AVIF/WebP quality, for example) writes new files that diverge from origin. The next `git pull` then reports either:

- **Untracked** — variants new to the working tree are not in the repo. The pull succeeds but leaves the working tree out of sync with origin.
- **Modified** — same path, different bytes. The pull aborts: "Your local changes to the following files would be overwritten by merge."

The clean-pull procedure on prod, in `~/public_html`:

```bash
# Step 1: stash regenerated variants and any other working-tree drift
git stash push -u -m "prod: regen variants, pre-pull stash"

# Step 2: pull cleanly
git pull origin main

# Step 3: drop the stash. Regenerated files are derivable from the source
# images. Restoring the stash also conflicts when the pull brings new
# variants in.
git stash drop
```

When the pull does not bring forward variants tuned by a recent local regen (e.g. an uncommitted quality retune), re-running `regenerate-variants.php` after the pull restores them.

### Host-specific cron schedule

[cron/siteground-cron-commands.txt](../cron/siteground-cron-commands.txt) is in `.gitignore` because the absolute paths differ per host. The file is a reference document, not a deployment artifact. Production's actual schedule lives in SiteGround's cron panel; changes to the .txt file must be mirrored there. See [Cron install](#cron-install).

---

## Database backup before any DDL

A snapshot is taken before:

- Restoring [dumps/warren-jeremy-dump-phase6.sql](../dumps/warren-jeremy-dump-phase6.sql).
- Running any loose `.sql` from `sql/`.
- Recreating routines manually.
- Any operation that modifies schema or stored procedures.

The canonical mysqldump invocation:

```bash
mysqldump \
    --routines \
    --triggers \
    --single-transaction \
    --set-gtid-purged=OFF \
    -h "$DB_HOST" \
    -u "$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    "$DB_DATABASE" \
    > "/home/<siteground-user>/db-backups/$(date +%Y%m%d-%H%M%S).sql"
```

Flag rationale:

- `--routines` — includes stored procedures and functions. Without this, an SP-only schema change rolls back to a stripped DB.
- `--triggers` — defaults to true on modern MySQL, passed explicitly so the runbook is self-documenting.
- `--single-transaction` — consistent snapshot without table locks. Safe for InnoDB.
- `--set-gtid-purged=OFF` — SiteGround MySQL does not use GTID; the default emits a warning that this flag silences.

Reading `DB_*` from the prod `.env`:

```bash
set -a; . ./.env; set +a
mysqldump --routines --triggers --single-transaction \
    --set-gtid-purged=OFF \
    -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
    > "$HOME/db-backups/$(date +%Y%m%d-%H%M%S).sql"
```

### Retention

Recommended retention: 7 daily snapshots and 4 weekly snapshots. Beyond that, retention is judgment-based. A monthly cron job to prune old snapshots is appropriate but is not currently wired up.

[dumps/warren-jeremy-dump-phase6.sql](../dumps/warren-jeremy-dump-phase6.sql) is the canonical schema; the loose `dumps/migration-*.sql` files are operator-only and are not part of public docs.

---

## Asset rebuild

`build-assets.php` at the repo root regenerates production assets. It is run when bundled CSS, page-specific CSS, or any JS file changes.

```bash
php build-assets.php             # Build + minify everything
php build-assets.php --no-minify # Concatenate without minifying (debug only)
php build-assets.php --css-only  # CSS only (faster when only CSS changed)
php build-assets.php --js-only   # JS only
```

The build performs four operations:

1. Concatenates the CSS manifest in [config/css.php](../config/css.php) into `public/assets/css/style.min.css`.
2. Minifies each non-bundled `.css` file into `{name}.min.css`.
3. Minifies each `.js` file into `{name}.min.js`.
4. Writes a content-hash to [config/asset-version.php](../config/asset-version.php).

The asset-version hash is appended to all asset URLs as `?v={hash}`, allowing production to serve assets with `Cache-Control: max-age=31536000, immutable` without staleness on deploy. The hash is recomputed every build and changes only when content changes; it is never edited manually.

### Source vs generated files

Source files are at the unsuffixed names (`base.css`, `components.css`, `responsive.css`); `style.min.css` is generated and overwritten on every build. Edits made directly to the minified file are lost on the next build.

### Production serves minified, dev serves source

[public/index.php](../public/index.php) checks `APP_DEBUG` and switches asset URLs accordingly. On prod (`APP_DEBUG=false`), `<link>` tags point at `style.min.css` and `{name}.min.css`. Locally (`APP_DEBUG=true`), they point at the unminified sources. Rebuilds are required only before commit/deploy, not during local dev.

---

## Composer

After a new PHP class is added under `src/`, the autoloader must be regenerated:

```bash
composer dump-autoload
```

This regenerates `vendor/composer/autoload_classmap.php` so the new class is autoloadable. The classmap is committed — production does not run `composer install` automatically, and the project favors zero-config deploys.

A commit that adds a class without regenerating the autoloader produces `Class App\Foo\Bar not found` errors on prod after pull. The remediation is `composer dump-autoload` on prod; the durable fix is a pre-commit hook that runs the dumper automatically.

`composer install` on prod is required only when `composer.lock` changes (i.e., dependency updates).

### Vulnerability scan before any dependency-bumping deploy

Run `composer audit` locally before pushing a `composer.lock` change. It queries the GitHub Advisory Database (and the FriendsOfPHP advisories DB as fallback) for known CVEs in the locked versions:

```bash
composer audit
```

A clean run prints `No security vulnerability advisories found` and exits `0`. A non-empty result lists each affected package with its CVE link — review and decide whether to upgrade the package, pin around it, or accept the risk before the deploy proceeds. Dependency-bump commits that pass `composer audit` should reference the run in the PR / commit body so the gate is auditable.

---

## PHP version

Production runs **PHP 8.5.2** on SiteGround. The codebase targets PHP 8.4+ features (typed constants, `json_validate()`, `#[\Override]`, property hooks, `array_find()` / `array_any()` / `array_all()`, etc.). Both 8.4 and 8.5 support these.

8.5-specific syntax is avoided. SiteGround can downgrade hosts to 8.4 during stability rollouts; a syntax-error fatal in production would result.

The `php` CLI on prod is at `/usr/local/bin/php`. Cron jobs and operator scripts use the absolute path; bare `php` resolves to a different binary in some shell environments.

---

## Cron install

Repository reference: [cron/siteground-cron-commands.txt](../cron/siteground-cron-commands.txt) (gitignored — server-specific paths). The file is the source of truth for what should be scheduled; the actual schedule lives in SiteGround's panel.

To add or modify a cron entry:

1. Edit the .txt file locally so the reference stays current.
2. Log into SiteGround Site Tools → Devs → Cron Jobs.
3. Add the command exactly as written (including the absolute `/usr/local/bin/php` and the absolute path to the script).
4. Set the schedule to match the .txt file's annotation (e.g., "Every 1 hour").
5. Save.

### Current jobs

| Job | Schedule | Purpose |
| --- | --- | --- |
| `expire-stale-borrows.php` | Every hour | 48-hour warning notification + auto-cancel approved-but-not-picked-up borrows after 72 hours. |
| `cleanup-expired-handovers.php` | Every hour | Clears expired pickup/return codes. |
| `send-overdue-notifications.php` | Daily at 8:00 AM | Notifies borrowers whose return date has passed. |
| `remind-pickup.php` | Daily at 9:00 AM | Tiered pickup reminders at days 3/5/6 of the 7-day approval window. |
| `refresh-summaries.php` | Every hour | Refreshes platform aggregate summary tables. |
| `refresh-tool-statistics.php` | Every 2 hours | Refreshes the materialized tool stats. |
| `refresh-user-reputation.php` | Every 4 hours | Refreshes the materialized reputation table. |
| `daily-platform-stats.php` | Daily at midnight | Writes a daily row into platform stats. |
| `archive-old-notifications.php` | Weekly Sun 3:00 AM | Moves old read notifications to archive table. |
| `cleanup-search-logs.php` | Weekly Sun 3:00 AM | Trims the search log retention window. |

New crons are also added to [CLAUDE.md](../CLAUDE.md)'s cron list to keep that inventory current.

---

## Stripe webhook wiring

Stripe sends payment events to a webhook endpoint, which `PaymentController::stripeWebhook` ([payment_controller.php:441](../src/Controllers/payment_controller.php#L441)) consumes.

### Endpoint configuration in Stripe Dashboard

- **URL:** `https://neighborhoodtools.org/webhook/stripe`
- **API version:** matches what `stripe/stripe-php` in `composer.json` is built against (currently the latest stable Stripe API version).
- **Events to send:** at minimum
  - `payment_intent.amount_capturable_updated`
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`

Unhandled events log to `error_log("stripeWebhook — unhandled event: …")` and return 200. Over-subscribing is non-fatal but adds log noise.

### Signature verification

Every webhook is verified via `\Stripe\Webhook::constructEvent($payload, $sigHeader, $_ENV['STRIPE_WEBHOOK_SECRET'])`. Failures return 400 — Stripe retries automatically.

`STRIPE_WEBHOOK_SECRET` is the **endpoint signing secret** (starts with `whsec_`), not the API key. It is available in Stripe Dashboard → Developers → Webhooks → endpoint → "Signing secret".

### Verifying the wiring

After a redeploy of `payment_controller.php` or rotation of `STRIPE_WEBHOOK_SECRET`:

```bash
# In Stripe Dashboard → Developers → Webhooks → endpoint:
# click "Send test event" → payment_intent.succeeded.
# Then on prod:
tail -f logs/error.log
```

A successful run produces no `stripeWebhook — verification failed` line. The presence of that line indicates a secret mismatch.

---

## Cloudflare Turnstile wiring

Auth forms (`/login`, `/register`, `/forgot-password`) carry a Turnstile widget that gates submission. Production verifies the token server-side; development bypasses verification entirely when `APP_DEBUG=true`.

### Required `.env` values

| Var | Purpose |
| --- | --- |
| `TURNSTILE_SITE_KEY` | Public key embedded in the widget HTML. |
| `TURNSTILE_SECRET_KEY` | Server-side verification key. Never exposed to the browser. |
| `TURNSTILE_ALLOWED_HOSTNAMES` | Comma-separated list of hostnames the verification accepts. The token's `hostname` claim must be in this list. |

### `TURNSTILE_ALLOWED_HOSTNAMES` semantics

The Turnstile token includes the hostname where the widget rendered. Verification fails when that hostname is absent from the allowlist.

- **Production:** `neighborhoodtools.org`
- **Local dev (with hostfile entry):** `neighborhoodtools.local`
- **Both, comma-separated:** `neighborhoodtools.org,neighborhoodtools.local`

Pointing a new domain at the site without adding it to the allowlist causes every login on that domain to fail with a Turnstile verification error.

### Dev bypass

When `APP_DEBUG=true`, the auth controllers skip Turnstile verification entirely. Local dev can run with `TURNSTILE_*` keys blank or stale without being blocked. `APP_DEBUG=true` must not be set in production — it disables bot protection.

---

## MySQL `sql_mode` baked into routines

MySQL stores the `sql_mode` that was active **at routine creation time** with each procedure / view. Session-level `SET sql_mode = …` changes do not apply retroactively.

Local MySQL 8's default `sql_mode` includes `ONLY_FULL_GROUP_BY`. The dump's stored procedures contain non-aggregated columns in `GROUP BY` queries (intentional, semantically correct), and they break under `ONLY_FULL_GROUP_BY`.

The dump has `sql_mode` guards around the routines section (lines ~3804–5786) that disable `ONLY_FULL_GROUP_BY` before creating each routine and restore it after. As long as the unedited dump is imported, this is invisible.

Routines recreated outside the dump (manual `CREATE OR REPLACE PROCEDURE`, etc.) must run under a session that excludes `ONLY_FULL_GROUP_BY`:

```sql
SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));
-- now create the routine
```

The views section (lines ~2775–3784) carries no `sql_mode` guard but is written to be `ONLY_FULL_GROUP_BY`-compliant — no fix needed there.

---

## Post-deploy smoke checks

Every deploy is verified by loading the URLs below. A 5xx response or PHP error output indicates a broken deploy and triggers rollback.

| URL | Verification |
| --- | --- |
| `https://neighborhoodtools.org/` | Home renders, no PHP errors, hero image loads, "Browse tools" CTA works. |
| `https://neighborhoodtools.org/tools` | Tool grid renders → DB read works, image variants serve (Network tab confirms AVIF/WebP delivery, not just JPEG). |
| `https://neighborhoodtools.org/login` | Form renders, Turnstile widget loads (Cloudflare iframe present), no CSP violations in the browser console. |
| `https://neighborhoodtools.org/styleguide` | Asset bundle loaded, custom fonts resolve, no missing icons. Confirms `style.min.css` and the FA self-hosted bundle are intact. |
| `https://neighborhoodtools.org/dashboard` (logged in) | Session works, dashboard XHR partials swap, member-only views render. |

### Failure signatures

- **5xx:** broken application code; rollback required.
- **Asset 404 with old version hash in URL:** `build-assets.php` ran but [config/asset-version.php](../config/asset-version.php) was not deployed; the file must be redeployed.
- **CSP violations in console:** typically an inline-style regression. Recent commits should be inspected for `style="…"` attributes or `element.style.…` assignments. The CSP policy is `style-src 'self'` with no `'unsafe-inline'`.
- **Turnstile widget missing:** `TURNSTILE_SITE_KEY` is not set in production `.env`; the page source will show an empty `data-sitekey` attribute.
- **DB connection refused:** `DB_*` values in production `.env` do not match SiteGround Site Tools → MySQL credentials.

### Browser console pass

A full page load with the browser console open is performed after every deploy. The console catches silent CSP violations, missing-asset 404s, and JS errors that pass server-side rendering but break interactivity.

---

## Rollback

Three rollback paths, ordered by reach.

### 1. Code-only rollback (most common)

Code-only deploys (no schema changes, no `.env` changes) roll back via:

```bash
ssh neighborhoodtools-prod
cd ~/public_html
git log --oneline -5             # find the last-known-good commit
git reset --hard <good-sha>      # back the working tree to that commit
```

Asset rebuild is typically unnecessary when reverting to a recent commit — the pinned asset-version hashes still resolve to existing files. Cases where old variants have been purged require `php build-assets.php` after the reset.

### 2. Asset-only rollback

When a bad rebuild produces broken assets (e.g., a corrupt minify):

```bash
git checkout HEAD~1 -- config/asset-version.php public/assets/css public/assets/js
```

This restores just the asset-version pin and the prebuilt files. Application code stays at HEAD.

### 3. Schema rollback

When a DDL change has broken prod, restore from the most recent pre-change snapshot:

```bash
# Identify the most recent good snapshot
ls -lh ~/db-backups/

# Restore (DESTRUCTIVE — drops and recreates everything)
set -a; . ./.env; set +a
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
    < ~/db-backups/<timestamp>.sql
```

This is destructive — rows written between snapshot and rollback are lost. Long-running schema changes are often better repaired forward than rolled back.

Schema rollback is followed by deploying code that matches the restored schema; otherwise the rollback only delays the failure.
