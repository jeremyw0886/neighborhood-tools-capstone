# HTTP Endpoint Reference

This document catalogues the JSON and machine-to-machine HTTP endpoints exposed by NeighborhoodTools. The full route table lives in [config/routes.php](../config/routes.php); this reference covers only the subset that returns JSON, accepts a JSON body, or has machine-readable contract semantics (signed webhooks, CSP reports). Page-rendering routes (HTML for browsers) are intentionally omitted — read the routes file for those.

> All paths are relative to the application origin (`https://neighborhoodtools.org` in production).

---

## Conventions

### Authentication

| Posture | Meaning |
| --- | --- |
| **Public** | No session required. |
| **Auth required** | Endpoint calls `$this->requireAuth()`; non-authenticated callers receive `401 {"error":"Authentication required."}` for XHR requests or a redirect to `/login` for HTML. |
| **Owner-only** | Auth + ownership check. The current user must own the resource (e.g. tool listing). Failure returns `403 {"error":"Unauthorized"}`. |
| **Admin / SuperAdmin** | Auth + `Role::Admin` or `Role::SuperAdmin`. Failure renders `errors/403.php`. |
| **Webhook (signed)** | No session. Request body is verified against a shared secret using a provider-specific signature header. Failure returns `400`. |
| **Machine-to-machine** | No session. Browser-originated only (CSP/Trusted-Types reporters). Rate-limited by IP. |

### CSRF

State-changing endpoints called from authenticated browser sessions (POST / PATCH / DELETE) require a CSRF token. The token comes from `$_SESSION['csrf_token']` and is delivered to the client as a hidden form field or a `meta[name="csrf-token"]` value. JSON endpoints accept it via either the `X-CSRF-Token` request header or a `csrf_token` field in the JSON body. Webhooks and CSP reports are exempt — they have no session.

### Content negotiation

Some endpoints serve both browser form posts (HTML redirect) and XHR callers (JSON). The controller uses `BaseController::isXhr()` (checks `X-Requested-With: XMLHttpRequest`) and `BaseController::wantsJson()` (checks the `Accept` header) to pick a response shape. Endpoints that behave this way are flagged **dual-mode** below.

### Standard error envelope

JSON errors follow `{ "error": "<human-readable message>" }`. Notification mutation endpoints additionally return `success: false` to keep the success-shape parser happy on the client side.

---

## Registration helpers (public)

### GET `/api/neighborhoods/{zip}`

Lookup neighborhoods by ZIP code. Used by the registration form to populate the neighborhood `<select>` after a ZIP is entered.

- **Auth:** Public.
- **Params:** `zip` (path) — five-digit ZIP code.
- **Success — `200`:** Array of neighborhoods covering the ZIP.
  ```json
  [{ "id_nbh": 12, "neighborhood_name_nbh": "Cherokee Triangle" }]
  ```
- **Errors:** `400 { "error": "Invalid ZIP code format." }` if `zip` is not exactly five digits.

### GET `/api/check-username`

Live-check whether a username is available during registration.

- **Auth:** Public, rate-limited (30 requests/min per IP).
- **Params:** `u` (query) — candidate username, lowercased server-side.
- **Success — `200`:** `{ "status": "available" | "taken" | "invalid" }`. Format/length failures return `invalid` rather than a `400` so the client can render the same UI for any rejected input.
- **Errors:** `429 { "status": "rate_limited" }` once the per-IP cap trips.

---

## Search & autocomplete

### GET `/tools/suggest`

Tool-name autocomplete for the public browse page.

- **Auth:** Public.
- **Params:** `q` (query, ≥ 2 chars; shorter queries return `[]`); `available` (query, optional, `1` to restrict to currently available tools).
- **Success — `200`:** Array of tool names — `["Cordless Drill", "Cordless Sander"]`.

### GET `/admin/suggest`

Cross-entity autocomplete for the admin search bar.

- **Auth:** Admin / SuperAdmin.
- **Params:** `q` (query, ≥ 2 chars); `type` (query) — one of `users`, `tools`, `deposits`, `categories`, `icons`, `avatars`, or `all`.
- **Success — `200`:**
  - For a specific `type`: array of display strings.
  - For `type=all`: array of `{ name, type, url }` objects (max 7 results across entities, two per entity), suitable for jump-to navigation.

---

## Notifications

### GET `/notifications/unread-count`

Lightweight polling for the nav-bar bell badge.

- **Auth:** Auth required.
- **Success — `200`:** `{ "success": true, "unread": <int> }`.
- **Errors:** `500 { "success": false, "message": "Something went wrong." }`.

### GET `/notifications/preview`

Five most recent unread notifications for the bell dropdown.

- **Auth:** Auth required.
- **Success — `200`:**
  ```json
  {
    "success": true,
    "unread": 3,
    "items": [
      {
        "id": 412,
        "type": "approval",
        "title": "Borrow approved",
        "body": "Your request for Cordless Drill was approved.",
        "timestamp": "2026-04-26 14:02:11",
        "hoursAgo": 2,
        "toolName": "Cordless Drill",
        "link": "/notifications/412/go"
      }
    ]
  }
  ```

### POST `/notifications/read` *(dual-mode)*

Mark notifications as read.

- **Auth:** Auth required + CSRF.
- **Body:** `notification_ids` — comma-separated positive integers (optional). Omit to mark all as read.
- **XHR success — `200`:** `{ "success": true, "unread": 0 }`.
- **HTML success:** `302` redirect back to `/notifications` (preserving `filter` and `page`).
- **Errors:** `500 { "success": false, "unread": <int> }` for XHR; redirect with no flash for HTML.

### POST `/notifications/clear-read` *(dual-mode)*

Delete every notification the user has already marked as read.

- **Auth:** Auth required + CSRF.
- **XHR success — `200`:** `{ "success": true, "cleared": <int>, "unread": <int> }`.
- **HTML success:** `302` redirect to `/notifications`.

---

## Tool image management *(auth + owner-only)*

All five tool-image endpoints require the caller to own the tool. They share the same error envelope: `403 { "error": "Unauthorized" }` for non-owners and `404 { "error": "Image not found" }` for image IDs that do not belong to the tool. They are called from [public/assets/js/image-crop.js](../public/assets/js/image-crop.js) and the tool-edit dashboard view.

### POST `/tools/{id}/images` *(dual-mode)*

Upload a new image. Returns JSON when the `Accept` header requests JSON, otherwise redirects with flash.

- **Body (multipart):** `photo` (file, required); `alt_text` (string, ≤ 255 chars, optional); `focal_x`, `focal_y` (integers 0–100, default 50).
- **Constraints:** ≤ 6 images per tool; image MIME validated via `finfo`; max 5 MB; allowed types `image/jpeg`, `image/png`, `image/webp`.
- **Success — `200`:**
  ```json
  {
    "id": 87,
    "filename": "tool_67e05a0c01c2e7.21934401.jpg",
    "alt_text": null,
    "sort_order": 3,
    "is_primary": false,
    "focal_x": 50,
    "focal_y": 50,
    "width": 1920
  }
  ```
- **Errors:** `404` (tool missing), `403` (not owner), `422` (validation: limit hit, no file, bad MIME, too large), `500` (move/variant generation failure).

### PATCH `/tools/{id}/images/order`

Reorder images via drag-and-drop.

- **Body (JSON):** `{ "order": [<image_id>, ...] }` — must contain every existing image ID for the tool.
- **Success — `200`:** `{ "success": true }`.
- **Errors:** `422 { "error": "Order must contain all image IDs for this tool" }`.

### PATCH `/tools/{id}/images/{img}/primary`

Promote an image to be the tool's primary thumbnail.

- **Success — `200`:** `{ "success": true }`.

### PATCH `/tools/{id}/images/{img}`

Update alt text and/or focal point. Updating the focal point regenerates AVIF/WebP variants synchronously.

- **Body (JSON):** `alt_text` (string, optional, ≤ 255 chars); `focal_x` and `focal_y` (integers 0–100, must be sent together).
- **Success — `200`:** `{ "success": true }`.

### DELETE `/tools/{id}/images/{img}`

Soft-delete an image and return the new primary (since deleting the primary promotes the next image).

- **Success — `200`:** `{ "deleted": true, "new_primary_id": <int|null> }`.

---

## Profile image

### PATCH `/profile/image`

Reposition the focal point on the authenticated user's profile photo. Regenerates 1:1 variants synchronously.

- **Auth:** Auth required + CSRF (header or body).
- **Body (JSON):** `{ "focal_x": <0–100>, "focal_y": <0–100>, "csrf_token": "..." }`. Token may also be supplied via `X-CSRF-Token` header.
- **Rate limit:** Per-IP, governed by [config/rate-limit.php](../config/rate-limit.php) (`profile_image` key).
- **Success — `200`:** `{ "success": true, "focal_x": <int>, "focal_y": <int> }`.
- **Errors:** `400` (invalid body), `403` (CSRF mismatch), `404` (no profile image), `429` (rate limited), `500` (variant regeneration failed).

---

## Payments

### POST `/api/stripe/create-intent`

Create or retrieve a Stripe `PaymentIntent` for a pending security deposit. Stripe keys live in `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY`. Intents use `capture_method: manual` so funds are authorised but not captured until the borrow returns.

- **Auth:** Auth required. Borrower must own the deposit.
- **Body (JSON):** `{ "deposit_id": <int>, "csrf_token": "..." }`.
- **Success — `200`:** `{ "clientSecret": "pi_..._secret_...", "publishableKey": "pk_..." }`.
- **Errors:** `400` (bad body, deposit not Stripe), `403` (CSRF mismatch or wrong borrower), `404` (deposit missing or already processed), `500` (Stripe API error).

### POST `/webhook/stripe`

Stripe-to-server webhook for deposit lifecycle events.

- **Auth:** Webhook (signed). The body is verified with `\Stripe\Webhook::constructEvent()` against `STRIPE_WEBHOOK_SECRET` and the `Stripe-Signature` header. **Verification is fail-closed** — bad or missing signatures get `400` and the handler exits before any side effect.
- **Handled events:**
  - `payment_intent.amount_capturable_updated` → mark deposit as held.
  - `payment_intent.succeeded` → mark deposit captured.
  - `payment_intent.payment_failed` → flag deposit as failed and notify borrower.
- **Success — `200`:** `{ "status": "ok" }` (always returned after a successful signature check, even for unhandled event types — Stripe retries `non-2xx` responses indefinitely).
- **Errors:** `400 { "error": "Signature verification failed." }`.

---

## Reporting (machine-to-machine)

### POST `/csp-report`

Receives Content Security Policy and Trusted Types violation reports from browsers. Wired up via the `report-uri` and `report-to` directives on the application's CSP header.

- **Auth:** Machine-to-machine. No session, no CSRF.
- **Content-Type:** Must start with `application/csp-report` or `application/reports+json`. Other types are rejected with `400`.
- **Rate limit:** 30 reports/min per IP.
- **Body size:** ≤ 10 KiB (over-size requests get `413`).
- **Accepted shapes:** legacy `report-uri` (`{ "csp-report": {...} }`); modern `report-to` array (`[{ "type": "csp-violation", "body": {...} }]`); Trusted-Types fallback (`{ "type": "tt-default-fallback", ... }`); DOMPurify strip events (`{ "type": "dompurify-strip", ... }`).
- **Success — `204`:** No body. The violation is written to the PHP error log with sanitised fields.
- **Errors:** `400` (bad content type, malformed JSON, unrecognised shape), `413` (body too large), `429` (rate limited).

---

## Not yet documented

- **Search controller** — the route `GET /search` is reserved at [config/routes.php:108](../config/routes.php#L108) but not implemented. When the controller lands, document any JSON endpoints here.
- **Dashboard partial-swap** — the dashboard currently re-renders full pages on each navigation. If XHR partial-swapping is added later, the affected dashboard routes belong in this file.

---

*Last verified against the codebase on 2026-04-26 (commit `34dbd1d`).*
