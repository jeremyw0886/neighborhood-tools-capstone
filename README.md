# NeighborhoodTools

Community tool-sharing platform for neighbors in the Asheville & Hendersonville area.

**Author:** Jeremy Warren
**Live:** <https://neighborhoodtools.org>

## Overview

NeighborhoodTools lets neighbors share and borrow tools from each other. Members can list tools, browse what's available nearby, request to borrow, and rate each other after transactions. Admins manage users, disputes, and platform content through a dedicated dashboard.

## Tech Stack

| Layer        | Technology                                                                      |
| ------------ | ------------------------------------------------------------------------------- |
| Backend      | PHP 8.4+ (no framework, production runs PHP 8.5)                                |
| Database     | MySQL 8 &mdash; views for reads, stored procedures for writes                   |
| Frontend     | Vanilla HTML5, CSS, JavaScript (ES2025 class syntax with private fields)        |
| Icons        | Font Awesome 6.5.0 (self-hosted subset)                                         |
| Server       | Apache or nginx, SiteGround hosting                                             |
| Payments     | Stripe (`stripe/stripe-php`) for security deposits                              |
| Bot defense  | Cloudflare Turnstile on auth forms                                              |
| Dependencies | Composer &mdash; `vlucas/phpdotenv`, `stripe/stripe-php`, classmap autoloading  |

## Architecture

Custom MVC with a front controller at `public/index.php`. All requests route through `config/routes.php` to controller actions. Views use PHP templating with output buffering into a shared layout.

```text
neighborhoodtools/
├── config/
│   ├── app.php               # App settings (timezone, debug)
│   ├── asset-version.php      # Cache-busting version string
│   ├── css.php                # CSS bundle manifest
│   ├── database.php           # DB config (reads from .env)
│   ├── rate-limit.php         # Rate limiting thresholds
│   └── routes.php             # Route definitions
├── public/
│   ├── index.php              # Front controller
│   ├── .htaccess              # Rewrite rules, caching, security headers
│   └── assets/
│       ├── css/               # Stylesheets + style.min.css bundle
│       ├── js/                # Client-side scripts
│       ├── images/            # SVGs and static images
│       └── vendor/fontawesome/ # Self-hosted FA subset
├── cron/                      # Scheduled jobs (notifications, stats, cleanup, summary refreshes)
├── scripts/                   # Ad-hoc operator scripts (variant regen, image purge, manual reminders)
├── sql/                       # Schema migrations and seed scripts
├── docs/                      # Architecture, runbook, and reference docs
├── src/
│   ├── Core/                  # BaseController, Database, Role enum, ImageProcessor (GD/Imagick backends + file cache), RateLimiter, Environment, ViewHelper
│   ├── Controllers/           # 20 route handlers
│   ├── Models/                # 23 data-access classes (static methods, PDO)
│   └── Views/
│       ├── layouts/main.php   # Shared HTML shell
│       ├── partials/          # Nav, dashboard nav, tool cards, sort-filter, overdue/pickup lists, modals, content blocks
│       └── {feature}/         # Page templates by feature
├── storage/                   # Runtime-writable storage (uploads, caches)
├── usability_testing/         # Usability test plans, reports, and support files
└── dumps/                     # SQL schema dump
```

## Implemented Features

- **Home** &mdash; Hero section, featured tools, top members, location-based member carousel
- **Authentication** &mdash; Login, registration, logout with CSRF protection, honeypot, bcrypt hashing, password reset via email
- **Tools** &mdash; Public browse with search/filter/pagination, detail view, multi-image upload with drag-reorder, primary-image selection, focal-point repositioning, availability blocks, listing toggle (create/edit/delete flows live under the dashboard shell)
- **Dashboard** &mdash; Unified shell with partial content swaps (XHR for fast navigation): overview, lender view (listed tools + incoming requests), borrower view (active borrows), loans, loan-status tracking, transaction history, list-tool / edit-tool, profile + profile-edit, bookmarks, and events all integrated under one shared navigation
- **Borrowing** &mdash; Request, approve, deny, cancel, extend, reminders
- **Handover verification** &mdash; Pickup/return code confirmation
- **Ratings** &mdash; Rate borrowers and lenders after transactions, rate tools
- **Profiles** &mdash; Public member profiles with ratings, listed tools, bio; own-profile and editing render through the dashboard shell, other users' profiles render standalone
- **Payments/deposits** &mdash; Security deposit handling with Stripe integration, payment history
- **Disputes** &mdash; Member-facing dispute filing, detail view, messaging
- **Events** &mdash; Community events with detail view, creation, RSVP
- **Incidents** &mdash; Member-facing damage/loss/injury reporting, detail view
- **Waivers** &mdash; Borrow waiver, condition acknowledgment, liability release
- **Categories** &mdash; Category browsing page
- **Notifications** &mdash; Paginated notification feed with mark-all-read, notification preferences
- **Admin** &mdash; Dashboard with platform stats, global search, user management (approve/deny/status), tool management, category CRUD with icon assignment, vector image library, avatar vector management, deposit management, dispute/event/incident oversight, reports, audit log, TOS versioning
- **Terms of Service** &mdash; Versioned TOS with acceptance tracking
- **Privacy Policy** &mdash; Standalone privacy page with data-handling disclosures
- **Info Pages** &mdash; How-To, FAQ (available as standalone pages and modals)
- **Scheduled jobs** &mdash; Cron scripts in `cron/` handle overdue notifications, expired handovers, stale borrow expiry, search-log cleanup, daily platform stats, and refreshes for tool/user/neighborhood summary tables

## Coding Standards

- **PHP:** PSR-12, strict types, prepared statements with explicit `bindValue()`, `htmlspecialchars()` on all output
- **CSS:** Design tokens, CSS nesting, Grid/Flexbox, `clamp()`, container queries, no `!important`, no inline styles (strict CSP)
- **JS:** ES2025 class syntax with private fields and arrow-field handlers, static `init()` factories, progressive enhancement (everything works without JS)
- **HTML:** Semantic HTML5, WCAG AA, ARIA landmarks, 44px touch targets, visible focus rings, skip-to-content link
- **Security:** CSRF tokens, honeypot fields, Cloudflare Turnstile, bcrypt cost-12 hashes, nonce-based CSP with `'strict-dynamic'`, Trusted Types enforcement, HSTS/X-Frame-Options/Referrer-Policy headers, HttpOnly/Secure/SameSite cookies, 30-minute idle session timeout, per-IP rate limiting &mdash; full posture in [docs/security.md](docs/security.md)

## Documentation

- [Local setup](docs/local-setup.md) &mdash; cloner guide: prerequisites, PHP extensions, database import, test accounts, verification, troubleshooting.
- [Database schema reference](docs/database.md) &mdash; tables, views, stored procedures, triggers, business rules, and ER diagram for the MySQL schema.
- [Image pipeline](docs/image-pipeline.md) &mdash; backend abstraction, variant generation, focal-point editor, operator scripts.
- [Deployment runbook](docs/deployment.md) &mdash; SiteGround procedures: pull/deploy, secrets, DB backup, cron, Stripe/Turnstile wiring, smoke checks, rollback.
- [HTTP endpoint reference](docs/endpoints.md) &mdash; JSON, XHR, and webhook endpoints: auth posture, request/response shapes, and error envelopes.
- [Security posture](docs/security.md) &mdash; threat model, authentication, CSP/Trusted Types directive breakdown, file uploads, rate limits, accepted trade-offs, and future hardening.

## AI-Assisted Development

[Claude](https://claude.ai) (Anthropic) was used throughout development for code audits, accessibility reviews, usability testing support, and to flatten CSS nesting (the W3C validator does not yet recognize the modern nesting syntax — nesting will be restored once the validator catches up). Placeholder images were created using [Artlist AI](https://artlist.io).

## Local Development

Requires PHP 8.4+, MySQL 8, and Apache (with `mod_rewrite`) or nginx. Happy path:

1. `composer install`
2. `mysql -u root -p -e "CREATE DATABASE neighborhoodtools;"`
3. `mysql -u root -p neighborhoodtools < dumps/warren-jeremy-dump-phase6.sql`
4. Copy `.env.example` to `.env` (handled automatically by `composer install`) and set `DB_USERNAME` and `DB_PASSWORD`.
5. Point the web server document root at `public/` and visit the site.

For full setup including required PHP extensions, storage permissions, HTTPS configuration, test accounts, verification, and troubleshooting, see [docs/local-setup.md](docs/local-setup.md).

Common dev tasks:

```bash
composer dump-autoload   # after adding a new PHP class under src/
php build-assets.php     # after changing bundled CSS or any JS
```

## W3C Validator Notes

### CSS Validator (jigsaw.w3.org/css-validator)

The stylesheets ship with four modern CSS features that [jigsaw.w3.org/css-validator](https://jigsaw.w3.org/css-validator) reports as errors. Each one is spec-valid CSS in a published W3C specification and shipping in current Chrome, Edge, Safari, and Firefox &mdash; the validator simply implements an older level of the CSS spec and has not caught up yet. The labels below match the validator's exact output, verified by submitting minimal test snippets under the CSS level 3 + SVG profile.

| Feature              | Spec                 | Baseline | Count in source | Validator emits                                                  |
| -------------------- | -------------------- | :------: | :-------------: | ---------------------------------------------------------------- |
| `@starting-style`    | CSS Transitions L2   | 2024     | 2               | Error: `Unrecognized at-rule "@starting-style"`                  |
| `@container` queries | CSS Containment L3   | 2023     | 4               | Error: `Unrecognized at-rule "@container"`                       |
| `container-type`     | CSS Containment L3   | 2023     | 4               | Property: `Property "container-type" doesn't exist`              |
| `container-name`     | CSS Containment L3   | 2023     | 3               | Property: `Property "container-name" doesn't exist`              |

### HTML Validator (validator.w3.org/nu) &mdash; `@layer` on the home page

When the **home page** (or any page that opts into the critical-CSS path) is submitted to [validator.w3.org/nu](https://validator.w3.org/nu), the validator runs an embedded CSS sub-check on every inline `<style>` block and reports:

> Error: `CSS: Unrecognized at-rule "@layer"`

The error fires once for **every** `@layer` declaration in the inlined critical CSS, so the home page typically surfaces three `@layer` errors (the at-rule list `@layer base, components, utilities;` plus the three `@layer base { … }` / `@layer utilities { … }` / `@layer components { … }` blocks).

The error originates from this block in [src/Views/layouts/main.php](src/Views/layouts/main.php), which inlines the per-page critical CSS so the first paint is unblocked:

```php
<style id="nt-dynamic-styles" nonce="<?= CSP_NONCE ?>">
  <?php if ($asyncCss) { readfile($criticalFile); } ?>
</style>
```

The `nonce` attribute is intentional and required &mdash; the project's Content Security Policy is `style-src 'self' 'nonce-…'` (no `'unsafe-inline'`), so every inline `<style>` block must carry a fresh per-request nonce that matches the CSP header to be allowed by the browser. The nonce is generated once per request in [public/index.php](public/index.php) as `bin2hex(random_bytes(...))` and exposed to views as the `CSP_NONCE` constant. This is the **only** inline style block in the entire codebase &mdash; everything else loads from external CSS files &mdash; and it exists only to inline the small critical-CSS payload for the first paint.

[`@layer`](https://www.w3.org/TR/css-cascade-5/#layering) is part of CSS Cascading and Inheritance Level 5 (Baseline-supported in all modern browsers since 2022). The standalone CSS validator at jigsaw.w3.org **accepts the same input cleanly** &mdash; the rejection only happens inside the Nu HTML validator's older embedded CSS profile. Submitting the at-rule directly to jigsaw.w3.org returns "Congratulations! No Error Found".

### Progressive-enhancement fallbacks

Container queries (`@container`, `container-type`, `container-name`) are treated as progressive enhancement. Pre-2023 engines that do not understand `container-type: inline-size` receive viewport-matched `@media` fallbacks wrapped in `@supports not (container-type: inline-size)`, so the narrow-dialog type scale and footer-stack ([components.css](public/assets/css/components.css)) and the tablet-range neighbor-grid 2-up layout ([home.css](public/assets/css/home.css)) both degrade cleanly rather than leaving oversized headers or stretched cards. Modern engines ignore the fallback block and use the real `@container` rules above.
