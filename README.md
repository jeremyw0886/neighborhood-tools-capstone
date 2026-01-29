# NeighborhoodTools

Community Tool Sharing Web Application
WEB-289 Capstone Project – Jeremy Warren
Live: https://neighborhoodtools.com
GitHub: this repo

## Overview

NeighborhoodTools is a community-based platform where neighbors can share and borrow tools from each other. The application includes member registration, tool listings, borrowing workflows, ratings, and admin management.

## Current Status

### Implemented

- Home page with navigation to project resources
- Database design documentation viewer with markdown rendering
- Responsive CSS with WCAG AA accessibility compliance
- ERD (Entity Relationship Diagram) via dbdiagram.io

### In Progress

- Database schema (fully documented in [NeighborhoodTools.md](NeighborhoodTools.md))
- PHP backend structure

## Planned Features

- Public tool search (JS filter/sort + PHP fallback)
- Member lending/borrowing with requests, loans, ratings
- Location-based tool discovery (proximity search)
- Admin approval workflow + vector image library
- Secure PHP image uploads
- Role-based access (member, admin, super admin)
- Dispute resolution system
- Notification system

## Tech Stack

- **Backend:** PHP 8.x + PDO (secure prepared statements)
- **Database:** MySQL 8.0.16+ (normalized 3NF, spatial indexing)
- **Frontend:** Semantic HTML5 + CSS (responsive, WCAG AA)
- **JavaScript:** Vanilla JS, marked.js for markdown rendering
- **Hosting:** SiteGround GoGeek

## Project Structure

```text
neighborhoodtools/
├── index.php                    # Home page
├── database-design.php          # Documentation viewer
├── database-design-content.php  # Markdown content handler
├── NeighborhoodTools.md         # Database design documentation
├── css/
│   ├── style.css
│   ├── database-design.css
│   └── database-design-content.css
├── js/
│   └── database-design-content.js
├── admin/                       # Admin features (planned)
├── includes/                    # PHP includes (db_connect.php)
└── assets/images/               # Image assets
```

## Documentation

- [Database Design](NeighborhoodTools.md) - Complete schema documentation
- [ERD Diagram](https://dbdiagram.io/d/neighborhoodtools-com-ERD-69711419bd82f5fce231c284) - Visual database diagram (link on home page)
