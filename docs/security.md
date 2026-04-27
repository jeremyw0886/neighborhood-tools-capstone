# Security Posture

**Scope:** Defenses, headers, and hardening choices implemented across the NeighborhoodTools application. Each section documents *what* protects which surface, *why* the choice was made, and (where applicable) *what is intentionally not addressed*.

**Audience:** anyone reviewing or contributing to the project — code reviewers, future maintainers, security-curious visitors who open DevTools.

**Last reviewed:** 2026-04-27.

---

## Table of contents

1. [Threat model](#threat-model)
2. [Authentication & sessions](#authentication--sessions)
3. [Authorization](#authorization)
4. [Input handling, output escaping, and SQL](#input-handling-output-escaping-and-sql)
5. [Content Security Policy & Trusted Types](#content-security-policy--trusted-types)
6. [Other HTTP security headers](#other-http-security-headers)
7. [File uploads](#file-uploads)
8. [Bot protection & rate limiting](#bot-protection--rate-limiting)
9. [Payments & deposits](#payments--deposits)
10. [Logging & violation reporting](#logging--violation-reporting)
11. [Known accepted trade-offs](#known-accepted-trade-offs)
12. [Future hardening considerations](#future-hardening-considerations)

---

## Threat model

NeighborhoodTools defends against:

- **Cross-site scripting (XSS)** — both reflected and DOM-based
- **Cross-site request forgery (CSRF)** on every state-changing request
- **Session hijacking and fixation**
- **Credential stuffing and brute-force login**
- **SQL injection**
- **File-upload abuse** (oversize files, MIME spoofing, path traversal)
- **Clickjacking**
- **Mixed-content / downgrade attacks**
- **DOM clobbering and prototype pollution** (defense-in-depth)
- **Bot-driven account creation and abuse**
- **Privilege escalation and IDOR** (insecure direct object reference)
- **Deposit fraud** (unauthorized release / forfeit)

Out of scope (intentionally):

- **Application-layer DoS** — deferred to upstream (Cloudflare, SiteGround). The app's per-IP rate limits exist to slow targeted abuse, not to absorb volumetric attacks.
- **Supply-chain compromise of vendored libraries** — DOMPurify and Font Awesome are vendored at fixed versions; integrity is reviewed at update time, not enforced at runtime via SRI for dynamically-loaded code.
- **Phishing / social engineering** outside the application surface.
- **Multi-factor authentication** — not implemented (see [Future hardening](#future-hardening-considerations)).

---

## Authentication & sessions

### Password storage

- **Algorithm:** bcrypt via `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`. Cost 12 is the project standard; raise it at next deploy if hardware allows ≤ 250 ms verification.
- **Length policy:** 8–72 characters. The 72-byte ceiling is a bcrypt limitation; longer passwords are silently truncated by the algorithm, so the form rejects them outright to avoid the foot-gun.
- **Verification:** `password_verify()` (constant-time) wrapped in `Account::verifyPassword()`.
- **Timing-attack mitigation on login:** the controller calls `password_verify('dummy', '$2y$12$…')` against a static dummy hash when the email isn't found, so unknown-account responses take roughly the same time as wrong-password responses ([auth_controller.php:90](../src/Controllers/auth_controller.php#L90)).
- **Reset tokens:** one-shot, expire after 60 minutes, stored as a hash (never the raw token) in `password_reset_pwr`.

### Session management

Configured in [public/index.php:54–83](../public/index.php#L54-L83):

| Setting | Value | Reason |
| --- | --- | --- |
| `cookie_httponly` | `true` | JS cannot read `PHPSESSID` |
| `cookie_samesite` | `Lax` | Defeats most CSRF without breaking top-level GETs from email links |
| `cookie_secure` | auto-detected from HTTPS | Cookie not transmitted over plain HTTP |
| `cookie_lifetime` | `0` | Browser-session cookie; closing the browser ends the session |
| `gc_maxlifetime` | `1800` (30 min) | Server-side garbage collection horizon |
| Custom save path | `storage/sessions` | Files outside the web root, owner-only permissions |

**Idle timeout:** the front controller compares `time() - $_SESSION['last_activity']` to `$sessionLifetime` on every request. Exceeding 30 minutes destroys the session and starts a fresh one ([index.php:76–83](../public/index.php#L76-L83)).

**Session ID rotation:** `session_regenerate_id(delete_old_session: true)` runs after every successful login, registration, and password reset ([auth_controller.php:128, 354](../src/Controllers/auth_controller.php)). Defeats session fixation.

**Live role/status revalidation:** every write request and every read request older than 10 seconds re-queries `account_acc` for the user's current role and account status. A flipped role or a `suspended`/`deleted` status destroys the session and forces re-authentication ([index.php:86–125](../public/index.php#L86-L125)). This means an admin who's just been demoted cannot continue performing admin actions for the remainder of their session.

### TOS enforcement

Logged-in users are redirected to `/tos` until they accept the current TOS version. A short-lived per-version session cache avoids hitting the database on every request ([index.php:127–152](../public/index.php#L127-L152)).

---

## Authorization

### Role model

Three roles, modeled as a PHP enum (`App\Core\Role`): `member`, `admin`, `super_admin`. Stored in DB lookup table `role_rol` and protected by triggers from being renamed or deleted.

### Guards

- `BaseController::requireAuth()` — redirects unauthenticated users to `/login`; returns `401 {"error":"Authentication required."}` for XHR/JSON.
- `BaseController::requireRole(Role::Admin, ...)` — aborts 403 if the session role isn't in the allowed set.
- `BaseController::abort(403|404|500)` — renders the matching error view and exits.

### Owner-only resources

Resource-ownership checks (tool edit/update/delete, profile edit, etc.) follow a strict pattern: `requireAuth()` first, then `if ((int) $resource['owner_id'] !== (int) $_SESSION['user_id']) { $this->abort(403); }`. Both sides cast to `int` to defeat string/integer comparison quirks. Applies uniformly to both browser POSTs and AJAX endpoints.

### Cross-cutting business rules

Role changes, account deletion, and ownership transitions are also enforced by **MySQL triggers** as a second wall — see [database.md](database.md). A controller bug that bypasses an owner check would still be caught at the SP/trigger layer.

---

## Input handling, output escaping, and SQL

### Output escaping

Every dynamic value rendered into HTML passes through `htmlspecialchars($value)` at the template boundary. JS files never use `innerHTML = userInput` — DOM mutations either build elements via `document.createElement()` + `textContent`, or route untrusted strings through `NT.sanitizeHtml()` (DOMPurify-backed, Trusted-Types-aware).

### SQL

- **All queries via PDO prepared statements** with explicit `bindValue()` on each parameter. Casting + `PARAM_INT`/`PARAM_NULL`/`PARAM_STR` is set at bind time, not relied on from PHP type juggling.
- **Reads use views, writes use stored procedures.** Direct table reads/writes from controllers are forbidden by project convention; the SP layer wraps business logic and centralizes input validation. Full reference in [database.md](database.md).
- **Lookup tables** (roles, statuses, condition tiers, etc.) are protected by triggers from rename or delete, so referential integrity stays consistent even under direct-DB tampering.

### CSRF protection

A 64-character hex token is generated on first session use and stored in `$_SESSION['csrf_token']` ([index.php:154–157](../public/index.php#L154-L157)). State-changing requests must include it via:

- A hidden `csrf_token` form field, **or**
- An `X-CSRF-Token` request header (XHR), **or**
- A `csrf_token` field in JSON bodies.

Validation uses `hash_equals()` (constant-time compare). Failure aborts 403 before any business logic runs.

### Honeypot

Auth forms include a hidden `<input type="text" name="website" tabindex="-1" autocomplete="off">`. If a bot fills it, the controller silently redirects without processing — see `auth_controller.php` lines 65, 183, etc. This catches naïve crawlers before Turnstile is even consulted.

### Email links

Verification, reset, and notification emails use single-use, time-bounded tokens. Reset tokens hash on disk (raw token never stored), and accepting one rotates the session ID.

---

## Content Security Policy & Trusted Types

The CSP is built per-request in [public/index.php:162–165](../public/index.php#L162-L165) and emitted alongside a `Reporting-Endpoints` header pointing at `/csp-report`. Each directive below is intentional, not boilerplate.

### Per-directive rationale

| Directive | Value | Why |
| --- | --- | --- |
| `default-src` | `'self'` | Deny everything unless an explicit directive permits it |
| `script-src` | `'nonce-{nonce}' 'strict-dynamic'` | Per-request base64 nonce on every `<script>` tag the server emits; `'strict-dynamic'` lets vetted scripts load further scripts but blocks injected `<script>` tags without the nonce |
| `style-src` | `'self' 'nonce-{nonce}'` | Same nonce model for `<style>` blocks; no `'unsafe-inline'` — no `style=""` attributes anywhere in the codebase |
| `font-src` | `'self'` | Self-hosted Font Awesome only; no Google Fonts |
| `img-src` | `'self' data: blob:` | `data:` for inline SVG icons; `blob:` for client-side image cropping previews |
| `connect-src` | `'self' https://challenges.cloudflare.com https://api.stripe.com https://r.stripe.com https://m.stripe.com` | Restricts `fetch()`/XHR to this app and the Stripe/Turnstile origins |
| `frame-src` | `https://challenges.cloudflare.com https://js.stripe.com https://hooks.stripe.com` | The only iframes we embed are Turnstile and Stripe's secured payment frames |
| `frame-ancestors` | `'none'` | Defeats clickjacking — no other site may iframe us |
| `object-src` | `'none'` | No Flash, no `<object>` plugins |
| `manifest-src` | `'self'` | PWA manifest from this origin only |
| `base-uri` | `'self'` | Prevents `<base>`-tag-based URL hijacking |
| `form-action` | `'self'` | Forms cannot post to other origins |
| `upgrade-insecure-requests` | (flag) | Browser auto-upgrades any `http://` subresource |
| `trusted-types` | `dompurify nt-html nt-script-url nt-dom-parser default` | Whitelist of policy names that may exist; arbitrary `trustedTypes.createPolicy('attacker', …)` calls fail |
| `require-trusted-types-for` | `'script'` | All TT-protected sinks (`innerHTML`, `eval`, `setTimeout(string)`, `script.src`, …) must receive `TrustedHTML`/`TrustedScript`/`TrustedScriptURL` from a registered policy, not raw strings |
| `report-uri` / `report-to` | `/csp-report` / `csp-endpoint` | Both legacy and modern reporting endpoints; backed by [CspController](../src/Controllers/csp_controller.php) |

### Trusted Types policies

Three named policies are created in [trusted-types.js](../public/assets/js/trusted-types.js); a fourth, `default`, catches everything else:

- **`nt-html`** — wraps DOMPurify with an explicit allowlist of tags and attributes. Fed every `innerHTML` assignment used by the SPA routers.
- **`nt-script-url`** — origin allowlist: same-origin `/assets/js/*`, `js.stripe.com`, `challenges.cloudflare.com`. Anything else throws.
- **`nt-dom-parser`** — passthrough policy that exists solely to satisfy `DOMParser.parseFromString('text/html')` under TT enforcement. The result is an inert document, never assigned to a sink.
- **`default`** — fallback that beacons to `/csp-report` so we see anything missed. The createScriptURL fast-paths the same origins as `nt-script-url` to avoid log noise from third-party SDKs (Turnstile, Stripe) that load secondary scripts.

### Where TT cannot reach

Trusted Types policies are **per-document**. The parent document's policies do not apply inside iframes loaded from other origins (e.g. Cloudflare Turnstile's `challenges.cloudflare.com` frame), nor inside `srcdoc` iframes those frames create. See [Known accepted trade-offs](#known-accepted-trade-offs) for the resulting console noise on auth pages.

---

## Other HTTP security headers

Set in [public/.htaccess:38–49](../public/.htaccess#L38-L49):

| Header | Value | Purpose |
| --- | --- | --- |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | One year of forced HTTPS, including subdomains; preload-list eligible |
| `X-Content-Type-Options` | `nosniff` | Browser will not MIME-sniff responses, defeating polyglot attacks |
| `X-Frame-Options` | `DENY` | Belt-and-suspenders alongside CSP `frame-ancestors 'none'` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Send full referrer same-origin; just origin cross-origin; nothing on HTTPS→HTTP |
| `X-XSS-Protection` | `0` | Explicitly disable the legacy XSS auditor (it caused vulnerabilities in some browsers); CSP is the modern protection |
| `Cross-Origin-Opener-Policy` | `same-origin-allow-popups` | Isolates browsing context; popups (e.g. Stripe checkout) still work |
| `Cross-Origin-Resource-Policy` | `same-origin` | Other origins cannot embed our subresources |
| `X-Permitted-Cross-Domain-Policies` | `none` | Disables Adobe-era cross-domain policies entirely |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=(self "https://js.stripe.com"), xr-spatial-tracking=(self "https://challenges.cloudflare.com")` | Deny all sensors by default; explicitly grant `payment` to Stripe and `xr-spatial-tracking` to the Turnstile iframe origin |

HTTPS redirect (308) is enforced in `.htaccess` for all non-localhost hosts.

---

## File uploads

Image uploads are accepted for tool listings, profile avatars, and incident photos. The pipeline in `ToolController` and the analogous profile/incident controllers applies:

1. **Size limit:** 5 MB per file at the application layer; `.htaccess` raises `upload_max_filesize` to 6 MB to give a clear error before PHP truncates.
2. **MIME validation:** `finfo(FILEINFO_MIME_TYPE)` reads the magic bytes — `$_FILES['type']` (browser-supplied) is **never** trusted. Only `image/jpeg`, `image/png`, and `image/webp` pass.
3. **Filename generation:** `uniqid('tool_', true) . '.' . $ext` — never derived from user input. Defeats path traversal and prevents overwriting other uploads.
4. **Move via `move_uploaded_file()`:** rejects anything that wasn't uploaded by the current request.
5. **Variant generation:** `ImageProcessor::generateVariants()` re-encodes via the chosen backend (ImageMagick CLI or ext-gd) into AVIF/WebP at multiple widths. Re-encoding strips EXIF and any embedded scripts/payloads.
6. **Cleanup on failure:** if any later step fails (DB insert, variant generation), the original upload and any partial variants are deleted before the error response.

Uploads land under `public/uploads/{feature}/`, served as static files with the security headers above. See [docs/image-pipeline.md](image-pipeline.md) for the full pipeline.

---

## Bot protection & rate limiting

### Cloudflare Turnstile

All four authentication forms (`/login`, `/register`, `/forgot-password`, `/reset-password`) embed a Turnstile widget. The token is verified server-side against Cloudflare's siteverify endpoint before any account lookup runs. See [docs/deployment.md](deployment.md) for the production wiring.

### Per-IP rate limits

Configured in [config/rate-limit.php](../config/rate-limit.php) and enforced by `App\Core\RateLimiter` (file-backed token bucket per key):

| Action | Cap | Window |
| --- | --- | --- |
| Login attempts | 5 | 15 min |
| Registrations | 3 | 1 hr |
| Borrow requests | 10 | 1 hr |
| Forgot-password requests | 3 | 15 min |
| Password resets | 5 | 15 min |
| Profile updates | 10 | 15 min |
| Profile image uploads | 10 | 15 min |
| `/csp-report` beacons | 30 | 1 min (per-IP, hard-coded in CspController) |
| `/api/check-username` | 30 | 1 min (per-IP) |

A successful login resets the IP's login counter ([auth_controller.php:121](../src/Controllers/auth_controller.php#L121)) so legitimate users aren't penalized after typos.

### Why per-IP, not per-account

Per-account locks are vulnerable to a spray attack that locks every account's owner out as a side-effect. Per-IP slows credential stuffing without enabling targeted lockouts. Combined with Turnstile, the realistic attack rate against a single account is dominated by the user's own ability to remember their password.

---

## Payments & deposits

Stripe integration handles security deposits via SetupIntents and PaymentIntents. The full lifecycle is in `PaymentController` and `Deposit` model.

- Stripe API keys are loaded as environment variables at startup; never committed.
- The webhook endpoint (`POST /stripe/webhook`) verifies the request signature against `STRIPE_WEBHOOK_SECRET` before doing anything; failure returns `400` and logs nothing actionable to an attacker.
- Webhook handlers are idempotent — repeat deliveries (Stripe retries on 5xx) won't double-credit a deposit.
- Deposit state transitions (`pending → held → released | forfeited | partial_release`) are constrained at the SP layer; controllers cannot skip a state.
- Forfeit and release actions are admin-gated and logged.

---

## Logging & violation reporting

### CSP / Trusted Types violation reports

`/csp-report` ([CspController](../src/Controllers/csp_controller.php)) accepts both legacy `application/csp-report` payloads and modern `application/reports+json`. It validates content type, body size (10 KB ceiling), JSON well-formedness, and per-IP rate. Each accepted report is written via `error_log()` with the offending directive, blocked URI, source file, and document URI — all run through a control-character stripper to prevent log injection.

### DOMPurify strip beacons

When the SPA-router-fed sanitize layer strips an unknown tag or attribute, the trusted-types module aggregates all strips from a single sanitize call into one beacon (deduped, parser-wrapper noise like `body`/`html`/`head` filtered out) and POSTs to `/csp-report` with `type: "dompurify-strip"`. These show up as `DOMPurify strip: ip=… tags=… page=…` in the PHP error log.

### What gets logged where

- **Application errors:** `error_log()` calls inside `try { } catch (\Throwable $e)` blocks throughout controllers and models, routed to whatever destination the `error_log` ini directive set in [public/index.php](../public/index.php) points at (a runtime file, not a committed artifact).
- **CSP / TT / DOMPurify violations:** routed to the same destination, prefixed with `CSP violation:` / `TT-default fallback:` / `DOMPurify strip:`.
- **Audit log:** schema defined, controller stub exists at `/admin/audit-log` — see [Future hardening](#future-hardening-considerations).

---

## Known accepted trade-offs

These are documented choices, not gaps. Each was considered and ruled in.

### Cloudflare Turnstile produces console noise on auth pages

Turnstile's widget loads from `challenges.cloudflare.com` into an iframe and creates further `srcdoc` iframes inside its own iframe. Those run under Cloudflare's CSP (which they appear to violate — `Trusted Types` blocks, `xr-spatial-tracking` permissions probes, `document.write` warnings, etc.) and surface in DevTools console because the iframe is a child of our document.

**This noise originates entirely inside Cloudflare's iframe, against Cloudflare's CSP, and cannot be silenced from our code.** The alternatives — dropping the widget, switching to a self-hosted captcha, or relaxing our CSP enough to allow inline-script execution everywhere — each cost more than the cosmetic console cleanup is worth. The widget functions correctly and bot tokens are issued; the warnings are informational.

### `DOMPurify SANITIZE_DOM` is disabled

The `nt-html` policy sets `SANITIZE_DOM: false`. Default DOMPurify strips `name="action"`, `name="dir"`, etc. on form inputs to defeat DOM-clobbering attacks — but it does so silently, breaking real form submissions (notably `<select name="action">` on the admin deposits page). Server output is already escaped via `htmlspecialchars()`, so DOM-clobbering protection at the sanitize layer is double-coverage at the cost of breaking forms. The disable is intentional. See [trusted-types.js:69](../public/assets/js/trusted-types.js#L69).

### `public/uploads/` is tracked in git

Tool / profile / category seed assets live in git so a fresh clone reproduces the demo site. The downside is that re-running variant regeneration produces noisy untracked files. Operator workaround documented in [docs/deployment.md](deployment.md) and [docs/image-pipeline.md](image-pipeline.md).

### No multi-factor authentication

Out of scope for the current build. Users with strong unique passwords plus Turnstile-gated login are the working bar. See [Future hardening](#future-hardening-considerations).

### Per-request CSP nonce blocks third-party CSS-in-JS

`style-src 'nonce-…' 'self'` rejects any `<style>` element added by JS that doesn't carry the nonce. The project uses constructable stylesheets (`NT.style.setRule`) to set dynamic styles instead. New third-party widgets that inject inline styles will be blocked by design.

### Permissions-Policy delegation to Cloudflare's iframe is best-effort

The header lists `xr-spatial-tracking=(self "https://challenges.cloudflare.com")`, but Permissions-Policy delegation also requires the iframe element to carry an `allow="…"` attribute. Cloudflare creates the iframe and we cannot modify its element, so the permission is granted at the parent header level but not propagated into the iframe. The browser still emits a violation when Cloudflare's widget probes the feature. Documented here so it isn't mistaken for a typo.

---

## Future hardening considerations

Tracked here so they aren't forgotten in code review:

- **Multi-factor authentication.** TOTP via authenticator apps would close credential-stuffing as an attack vector even when Turnstile is bypassed.
- **Audit log table + writes.** The `/admin/audit-log` route renders a placeholder; the schema for `audit_log_aud` is straightforward (actor, action verb, target type, target id, payload JSON, timestamp) but write hooks at the controller layer are not yet wired.
- **Per-IP escalating lockouts.** Current rate limits are flat windows; a logarithmic backoff after each failed login window (5 min → 15 min → 60 min → 24 hr) would slow patient attackers further.
- **Subresource Integrity (SRI) on Turnstile / Stripe loaders.** Currently we trust the CDN response. SRI hashes would catch CDN compromise, but Cloudflare's widget URL changes frequently enough that maintaining the hash is operationally painful — deferred until a stable public hash is published.
- **Webhook IP allowlist.** Stripe publishes a list of source IPs; restricting `/stripe/webhook` to those at the firewall would stop replay/probe traffic before signature verification runs.
- **Email verification on registration.** Today, accounts go from `pending` to `active` via admin approval. Adding a token-confirmed email step would catch typo'd addresses and prove control of the address.
- **Session binding.** Tying a session to the user-agent string (or a partial fingerprint) would invalidate stolen cookies replayed from a different device. Has UX cost — re-auth on browser updates — and would need careful tuning.
- **CSP `report-only` parallel header.** Running a stricter CSP in report-only alongside the enforcing header would surface candidate tightenings without breaking pages.
