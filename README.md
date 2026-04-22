# NeighborhoodTools

Community tool-sharing platform for neighbors in the Asheville & Hendersonville area.

**WEB-289 Capstone Project** &mdash; Jeremy Warren
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
├── sql/                       # Schema migrations and seed scripts
├── src/
│   ├── Core/                  # BaseController, Database, Role enum, ImageProcessor, RateLimiter, Environment, ViewHelper
│   ├── Controllers/           # 18 route handlers
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
- **Info Pages** &mdash; How-To, FAQ (available as standalone pages and modals)
- **Scheduled jobs** &mdash; Cron scripts in `cron/` handle overdue notifications, expired handovers, stale borrow expiry, search-log cleanup, daily platform stats, and refreshes for tool/user/neighborhood summary tables

## Coding Standards

- **PHP:** PSR-12, strict types, prepared statements with explicit `bindValue()`, `htmlspecialchars()` on all output
- **CSS:** Design tokens, CSS nesting, Grid/Flexbox, `clamp()`, container queries, no `!important`, no inline styles (strict CSP)
- **JS:** ES2025 class syntax with private fields and arrow-field handlers, static `init()` factories, progressive enhancement (everything works without JS)
- **HTML:** Semantic HTML5, WCAG AA, ARIA landmarks, 44px touch targets, visible focus rings, skip-to-content link
- **Security:** CSRF tokens, honeypot fields, Cloudflare Turnstile, bcrypt cost-12 hashes, CSP/HSTS/X-Frame-Options/Referrer-Policy headers, HttpOnly/Secure/SameSite cookies, 30-minute idle session timeout

## AI-Assisted Development

[Claude](https://claude.ai) (Anthropic) was used throughout development for code audits, accessibility reviews, usability testing support, and used to flatten css from nesting (css could not be validated using W3C using nesting, nesting will be restored after completion of this course). Placeholder images were created using [Artlist AI](https://artlist.io).

## Local Development

Requires a local PHP/MySQL stack with PHP 8.4+ and MySQL 8. Apache works out of the box with the checked-in `.htaccess`; for nginx, use `public/` as the document root and mirror the rewrite/security headers in `config/nginx/neighborhoodtools.local.conf.example`.

1. Clone the repo
2. `composer install`
3. Copy `.env.example` to `.env` and configure database credentials
4. Import `dumps/warren-jeremy-dump-phase6.sql`
5. Point your web server document root to `public/`
6. Set `APP_URL`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, and `TURNSTILE_ALLOWED_HOSTNAMES` in `.env` for your local hostname
7. Visit your local host, for example `https://neighborhoodtools.local:8890`

After adding new PHP classes (controllers, models), regenerate the autoloader:

```bash
composer dump-autoload
```

After changing bundled CSS files, rebuild the production bundle:

```bash
php build-assets.php
```

## W3C CSS Validator Notes

The stylesheets ship with four modern CSS features that [jigsaw.w3.org/css-validator](https://jigsaw.w3.org/css-validator) reports as errors. Each one is spec-valid CSS in a published W3C specification and shipping in current Chrome, Edge, Safari, and Firefox &mdash; the validator simply implements an older level of the CSS spec and has not caught up yet. The labels below match the validator's exact output, verified by submitting minimal test snippets under the CSS level 3 + SVG profile.

| Feature              | Spec                 | Baseline | Count in source | Validator emits                                                  |
| -------------------- | -------------------- | :------: | :-------------: | ---------------------------------------------------------------- |
| `@starting-style`    | CSS Transitions L2   | 2024     | 2               | Error: `Unrecognized at-rule "@starting-style"`                  |
| `@container` queries | CSS Containment L3   | 2023     | 4               | Error: `Unrecognized at-rule "@container"`                       |
| `container-type`     | CSS Containment L3   | 2023     | 4               | Error: `Property "container-type" doesn't exist`                 |
| `container-name`     | CSS Containment L3   | 2023     | 3               | Error: `Property "container-name" doesn't exist`                 |

The following modern features ALSO appear in the source but currently validate cleanly on this version of the W3C CSS Validator &mdash; listed here for completeness because earlier revisions of this document noted them as caveats:

- `@layer` &mdash; 12 occurrences across 10 files.
- `color-mix()` &mdash; 21 occurrences across 5 files.
- `text-wrap: balance` &mdash; 1 occurrence in `home.css`.
- `dvh` / `svh` units &mdash; 7 occurrences across `base.css` and `components.css`.
