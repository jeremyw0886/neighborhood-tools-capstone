# NeighborhoodTools

Community tool-sharing platform for neighbors in the Asheville & Hendersonville area.

**WEB-289 Capstone Project** &mdash; Jeremy Warren
**Live:** <https://neighborhoodtools.com>

## Overview

NeighborhoodTools lets neighbors share and borrow tools from each other. Members can list tools, browse what's available nearby, request to borrow, and rate each other after transactions. Admins manage users, disputes, and platform content through a dedicated dashboard.

## Tech Stack

| Layer        | Technology                                                    |
| ------------ | ------------------------------------------------------------- |
| Backend      | PHP 8.4+ (no framework)                                       |
| Database     | MySQL 8 &mdash; views for reads, stored procedures for writes |
| Frontend     | Vanilla HTML5, CSS, JavaScript (ES6+)                         |
| Icons        | Font Awesome 6.5.0 (self-hosted subset)                       |
| Server       | Apache with mod_rewrite, SiteGround hosting                   |
| Dependencies | Composer &mdash; `vlucas/phpdotenv`, classmap autoloading     |

## Architecture

Custom MVC with a front controller at `public/index.php`. All requests route through `config/routes.php` to controller actions. Views use PHP templating with output buffering into a shared layout.

```text
neighborhoodtools/
├── config/
│   ├── app.php               # App settings (timezone, debug)
│   ├── css.php                # CSS bundle manifest
│   ├── database.php           # DB config (auto-detects environment)
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
│   ├── Core/                  # BaseController, Database, Role enum
│   ├── Controllers/           # Route handlers
│   ├── Models/                # Data access (static methods, PDO)
│   └── Views/
│       ├── layouts/main.php   # Shared HTML shell
│       ├── partials/          # Nav, tool cards, modals, content blocks
│       └── {feature}/         # Page templates by feature
└── dumps/                     # SQL schema dump
```

## Implemented Features

- **Home** &mdash; Hero section, featured tools, top members, location-based member sidebar
- **Authentication** &mdash; Login, registration, logout with CSRF protection, honeypot, bcrypt hashing
- **Tools** &mdash; Browse with search/filter/pagination, detail view, create, edit, delete, bookmarks
- **Dashboard** &mdash; Overview, lender view (listed tools + incoming requests), borrower view (active borrows), transaction history
- **Borrowing** &mdash; Request submission from tool detail page
- **Profiles** &mdash; Public member profiles with ratings, listed tools, bio
- **Notifications** &mdash; Paginated notification feed with mark-all-read
- **Admin** &mdash; Dashboard with platform stats, user/tool/dispute/event/incident management, reports, audit log, TOS versioning (none have content yet)
- **Terms of Service** &mdash; Versioned TOS with acceptance tracking
- **Info Pages** &mdash; How-To, FAQ (available as standalone pages and modals)

## Not Yet Implemented

- **Borrow workflow** &mdash; Approve/deny requests, checkout, check-in, return, extend (lender actions)
- **Loan tracking** &mdash; Real-time status tracking of active loans through the borrow lifecycle (requested, approved, borrowed, due soon, overdue, returned)
- **Dashboard sort/filter** &mdash; Filter/sort lender and borrower views by date, name, status
- **Tool search sort** &mdash; Sort results asc/desc, JS sort/filter enhancement with PHP fallback
- **Ratings** &mdash; Rate borrowers and lenders after transactions
- **Admin actions** &mdash; Approve account requests, activate/deactivate members, vector image library
- **Payments/deposits** &mdash; Security deposit handling
- **Disputes** &mdash; Member-facing dispute filing and messaging
- **Events** &mdash; Community events
- **Handover verification** &mdash; Pickup/return code confirmation
- **Incidents** &mdash; Member-facing damage/loss/injury reporting
- **Waivers** &mdash; Borrow waiver, condition acknowledgment, liability release
- **Categories API** &mdash; Category browsing endpoint

## Coding Standards

- **PHP:** PSR-12, strict types, prepared statements, `htmlspecialchars()` on all output
- **CSS:** Design tokens, CSS nesting, `@layer` cascade, Grid/Flexbox, `clamp()`, no `!important`
- **JS:** `'use strict'`, progressive enhancement (everything 'works' without JS)
- **HTML:** Semantic HTML5, WCAG AA, ARIA landmarks, 44px touch targets, visible focus rings (color pallet to be updated for contrast)
- **Security:** CSRF tokens, CSP/HSTS/X-Frame-Options headers, HttpOnly/Secure/SameSite cookies

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
php build-css.php
```
