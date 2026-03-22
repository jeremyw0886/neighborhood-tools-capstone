# NeighborhoodTools

Community tool-sharing platform for neighbors in the Asheville & Hendersonville area.

**WEB-289 Capstone Project** &mdash; Jeremy Warren
**Live:** <https://neighborhoodtools.com>

## Overview

NeighborhoodTools lets neighbors share and borrow tools from each other. Members can list tools, browse what's available nearby, request to borrow, and rate each other after transactions. Admins manage users, disputes, and platform content through a dedicated dashboard.

## Tech Stack

| Layer        | Technology                                                                      |
| ------------ | ------------------------------------------------------------------------------- |
| Backend      | PHP 8.4+ (no framework)                                                         |
| Database     | MySQL 8 &mdash; views for reads, stored procedures for writes                   |
| Frontend     | Vanilla HTML5, CSS, JavaScript (ES6+)                                           |
| Icons        | Font Awesome 6.5.0 (self-hosted subset)                                         |
| Server       | Apache with mod_rewrite, SiteGround hosting                                     |
| Payments     | Stripe (`stripe/stripe-php`)                                                    |
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
├── src/
│   ├── Core/                  # BaseController, Database, Role enum, ImageProcessor, RateLimiter, Environment, ViewHelper
│   ├── Controllers/           # Route handlers
│   ├── Models/                # Data access (static methods, PDO)
│   └── Views/
│       ├── layouts/main.php   # Shared HTML shell
│       ├── partials/          # Nav, dashboard nav, tool cards, sort-filter, overdue/pickup lists, modals, content blocks
│       └── {feature}/         # Page templates by feature
├── usability_testing/          # Usability test plans, reports, and support files
└── dumps/                     # SQL schema dump
```

## Implemented Features

- **Home** &mdash; Hero section, featured tools, top members, location-based member carousel
- **Authentication** &mdash; Login, registration, logout with CSRF protection, honeypot, bcrypt hashing, password reset via email
- **Tools** &mdash; Browse with search/filter/pagination, detail view, create, edit, delete, multi-image upload with reorder and primary selection, bookmarks, availability management, listing toggle
- **Dashboard** &mdash; Unified shell with partial content swaps (XHR for fast navigation), overview, lender view (listed tools + incoming requests), borrower view (active borrows), transaction history, loan status tracking, integrated profile/bookmarks/events via shared navigation
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

## Coding Standards

- **PHP:** PSR-12, strict types, prepared statements, `htmlspecialchars()` on all output
- **CSS:** Design tokens, CSS nesting, `@layer` cascade, Grid/Flexbox, `clamp()`, no `!important`
- **JS:** `'use strict'`, progressive enhancement (everything 'works' without JS)
- **HTML:** Semantic HTML5, WCAG AA, ARIA landmarks, 44px touch targets, visible focus rings
- **Security:** CSRF tokens, CSP/HSTS/X-Frame-Options headers, HttpOnly/Secure/SameSite cookies

## AI-Assisted Development

[Claude](https://claude.ai) (Anthropic) was used throughout development for code audits, accessibility reviews, and usability testing support. Placeholder images were created using [Artlist AI](https://artlist.io).

## Local Development

Requires a local Apache/PHP/MySQL stack such as MAMP PRO (macOS) or Laragon (Windows) with PHP 8.4+ and MySQL 8.

1. Clone the repo
2. `composer install`
3. Copy `.env.example` to `.env` and configure database credentials
4. Import `dumps/warren-jeremy-dump-phase3.sql`
5. Point Apache document root to `public/`
6. Visit `http://localhost:8888`

After adding new PHP classes (controllers, models), regenerate the autoloader:

```bash
composer dump-autoload
```

After changing bundled CSS files, rebuild the production bundle:

```bash
php build-assets.php
```
