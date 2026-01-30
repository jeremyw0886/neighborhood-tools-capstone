# NeighborhoodTools.com Database Design

**Author:** Jeremy Warren

**Course:** WEB-289 Capstone

**Target Database:** MySQL 8.0.16 or later

---

## Table of Contents

1. [Overview](#overview)
2. [Table Groups](#table-groups)
3. [Lookup Tables](#lookup-tables)
4. [Core Tables](#core-tables)
   - [Accounts](#accounts)
   - [Tools](#tools)
   - [Borrowing](#borrowing)
   - [Ratings & Disputes](#ratings--disputes)
   - [User Interactions](#user-interactions)
   - [Shared Assets](#shared-assets)
   - [Junction Tables](#junction-tables)
   - [Future Expansion](#future-expansion)
5. [Relationships](#relationships)
6. [Entity Relationship Diagram](#entity-relationship-diagram)

---

## Overview

NeighborhoodTools.com is a community-based tool lending platform that enables
neighbors to share tools with each other. This database design supports:

- **User account management** with roles, statuses, and contact preferences
- **Tool listings** with categories, conditions, and images
- **Borrowing workflow** with status tracking and availability management
- **Rating system** for both users and tools
- **Dispute resolution** with message threading for handling conflicts
- **User interactions** including bookmarks, notifications, and search logging
- **Future expansion** for community events and phpBB forum integration

---

## Table Groups

The database is organized into logical groups for easier management and
visualization:

| Group                  | Tables                                                                                                                                                            |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Accounts**           | `role_rol`, `account_status_ast`, `contact_preference_cpr`, `state_sta`, `neighborhood_nbh`, `zip_code_zpc`, `account_acc`, `account_image_aim`, `account_bio_abi`|
| **Tools**              | `category_cat`, `tool_condition_tcd`, `tool_tol`, `tool_image_tim`                                                                                                |
| **Borrowing**          | `borrow_status_bst`, `block_type_btp`, `borrow_bor`, `availability_block_avb`                                                                                     |
| **Ratings & Disputes** | `rating_role_rtr`, `user_rating_urt`, `tool_rating_trt`, `dispute_dsp`, `dispute_status_dst`, `dispute_message_type_dmt`, `dispute_message_dsm`                   |
| **User Interactions**  | `notification_ntf`, `notification_type_ntt`, `search_log_slg`                                                                                                     |
| **Shared Assets**      | `vector_image_vec`                                                                                                                                                |
| **Junction Tables**    | `tool_category_tolcat`, `tool_bookmark_acctol`, `neighborhood_zip_nbhzpc`                                                                                         |
| **Future Expansion**   | `event_evt`, `phpbb_integration_php`, `audit_log_aud`                                                                                                             |

---

## Lookup Tables

Lookup tables store predefined values used throughout the system.

### role_rol

Defines user roles for access control.

| Column          | Type        | Constraints        | Notes                                |
|-----------------|-------------|--------------------|--------------------------------------|
| `id_rol`        | int         | PK, auto-increment | -                                    |
| `role_name_rol` | varchar(50) | unique, not null   | Values: member, admin, super_admin   |

---

### account_status_ast

Tracks account lifecycle states.

| Column            | Type        | Constraints        | Notes                                         |
|-------------------|-------------|--------------------|-----------------------------------------------|
| `id_ast`          | int         | PK, auto-increment | -                                             |
| `status_name_ast` | varchar(30) | unique, not null   | Values: pending, active, suspended, deleted   |

---

### contact_preference_cpr

User communication preferences.

| Column                | Type        | Constraints        | Notes                             |
|-----------------------|-------------|--------------------|-----------------------------------|
| `id_cpr`              | int         | PK, auto-increment | -                                 |
| `preference_name_cpr` | varchar(30) | unique, not null   | Values: email, phone, both, app   |

---

### state_sta

US state lookup table for address normalization.

| Column          | Type        | Constraints        | Notes                          |
|-----------------|-------------|--------------------|--------------------------------|
| `id_sta`        | int         | PK, auto-increment | -                              |
| `state_code_sta`| varchar(2)  | unique, not null   | Two-letter US state code       |
| `state_name_sta`| varchar(50) | unique, not null   | Full US state name             |

---

### neighborhood_nbh

Dedicated neighborhood entity for local communities/service areas.

| Column                  | Type         | Constraints        | Notes                                                             |
|-------------------------|--------------|--------------------|-------------------------------------------------------------------|
| `id_nbh`                | int          | PK, auto-increment | -                                                                 |
| `neighborhood_name_nbh` | varchar(100) | unique, not null   | Name of local community/service area                              |
| `city_name_nbh`         | varchar(100) | -                  | Primary city for this neighborhood                                |
| `id_sta_nbh`            | int          | not null           | State the neighborhood is primarily in (FK to state_sta)          |
| `latitude_nbh`          | decimal(9,6) | -                  | -                                                                 |
| `longitude_nbh`         | decimal(9,6) | -                  | -                                                                 |
| `location_point_nbh`    | point        | -                  | MySQL 8 POINT type with SRID 4326 (WGS84) for optimized proximity |
| `created_at_nbh`        | timestamp    | default: now()     | -                                                                 |
| `updated_at_nbh`        | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                                       |
| `metadata_json_nbh`     | json         | -                  | Future: community features, settings, etc.                        |

**Indexes:**

- `idx_state_nbh` on `id_sta_nbh`
- `idx_city_nbh` on `city_name_nbh`
- `idx_location_spatial_nbh` (SPATIAL) on `location_point_nbh`

> **Note:**
>
> - Spatial trigger: BEFORE INSERT/UPDATE – auto-populate `location_point_nbh` from lat/long:
>   `SET NEW.location_point_nbh = ST_PointFromText(CONCAT('POINT(', NEW.longitude_nbh, ' ', NEW.latitude_nbh, ')'), 4326)`

---

### category_cat

Tool categories for classification.

| Column              | Type         | Constraints        | Notes                                        |
|---------------------|--------------|--------------------|----------------------------------------------|
| `id_cat`            | int          | PK, auto-increment | -                                            |
| `category_name_cat` | varchar(100) | unique, not null   | -                                            |
| `id_vec_cat`        | int          | -                  | Optional category icon from vector_image_vec |

**Indexes:**

- `idx_category_vector_icon_cat` on `id_vec_cat`

---

### tool_condition_tcd

Describes physical condition of tools.

| Column               | Type        | Constraints        | Notes                          |
|----------------------|-------------|--------------------|--------------------------------|
| `id_tcd`             | int         | PK, auto-increment | -                              |
| `condition_name_tcd` | varchar(30) | unique, not null   | Values: new, good, fair, poor  |

---

### borrow_status_bst

Tracks borrow request lifecycle.

| Column            | Type        | Constraints        | Notes                                                              |
|-------------------|-------------|--------------------|--------------------------------------------------------------------|
| `id_bst`          | int         | PK, auto-increment | -                                                                  |
| `status_name_bst` | varchar(30) | unique, not null   | Values: requested, approved, borrowed, returned, denied, cancelled |

> **Note:**
>
> - Avoid hard-coded status IDs - use name lookups or views at runtime.

---

### block_type_btp

Distinguishes types of availability blocks.

| Column          | Type        | Constraints        | Notes                   |
|-----------------|-------------|--------------------|-------------------------|
| `id_btp`        | int         | PK, auto-increment | -                       |
| `type_name_btp` | varchar(30) | unique, not null   | Values: admin, borrow   |

---

### rating_role_rtr

Context for user ratings.

| Column          | Type        | Constraints        | Notes                      |
|-----------------|-------------|--------------------|----------------------------|
| `id_rtr`        | int         | PK, auto-increment | -                          |
| `role_name_rtr` | varchar(30) | unique, not null   | Values: lender, borrower   |

---

### dispute_status_dst

Tracks dispute resolution status.

| Column            | Type        | Constraints        | Notes                               |
|-------------------|-------------|--------------------|-------------------------------------|
| `id_dst`          | int         | PK, auto-increment | -                                   |
| `status_name_dst` | varchar(30) | unique, not null   | Values: open, resolved, dismissed   |

---

### dispute_message_type_dmt

Types of messages in dispute threads.

| Column          | Type        | Constraints        | Notes                                                    |
|-----------------|-------------|--------------------|----------------------------------------------------------|
| `id_dmt`        | int         | PK, auto-increment | -                                                        |
| `type_name_dmt` | varchar(30) | unique, not null   | Values: initial_report, response, admin_note, resolution |

---

### notification_type_ntt

Categories of system notifications.

| Column          | Type        | Constraints        | Notes                                          |
|-----------------|-------------|--------------------|------------------------------------------------|
| `id_ntt`        | int         | PK, auto-increment | -                                              |
| `type_name_ntt` | varchar(30) | unique, not null   | Values: request, approval, due, return, rating |

---

## Core Tables

### Accounts

#### zip_code_zpc

Simplified ZIP code table – pure geographic identifiers only.

| Column               | Type         | Constraints | Notes                                |
|----------------------|--------------|-------------|--------------------------------------|
| `zip_code_zpc`       | varchar(10)  | PK          | -                                    |
| `latitude_zpc`       | decimal(9,6) | -           | -                                    |
| `longitude_zpc`      | decimal(9,6) | -           | -                                    |
| `location_point_zpc` | point        | -           | MySQL 8 POINT with SRID 4326 (WGS84) |

**Indexes:**

- `idx_location_spatial_zpc` (SPATIAL) on `location_point_zpc`

> **Note:**
>
> - Spatial trigger: BEFORE INSERT/UPDATE – auto-populate `location_point_zpc` from lat/long:
>   `SET NEW.location_point_zpc = ST_PointFromText(CONCAT('POINT(', NEW.longitude_zpc, ' ', NEW.latitude_zpc, ')'), 4326)`
> - Proximity queries (`ST_Distance_Sphere` returns meters):
>   - Find within 10 miles: `WHERE ST_Distance_Sphere(location_point_zpc, point) <= 10 * 1609.344`
>   - Return distance in miles: `ST_Distance_Sphere(...) / 1609.344 AS distance_miles`
> - **Conversion:** 1 mile = 1609.344 meters.

---

#### account_acc

Main user account table containing all user information.

| Column                      | Type         | Constraints        | Notes                                                                    |
|-----------------------------|--------------|--------------------|--------------------------------------------------------------------------|
| `id_acc`                    | int          | PK, auto-increment | -                                                                        |
| `first_name_acc`            | varchar(100) | not null           | -                                                                        |
| `last_name_acc`             | varchar(100) | not null           | -                                                                        |
| `phone_number_acc`          | varchar(20)  | -                  | -                                                                        |
| `email_address_acc`         | varchar(255) | unique, not null   | Primary login credential - used for authentication                       |
| `street_address_acc`        | varchar(255) | -                  | Optional for privacy - ZIP required                                      |
| `zip_code_acc`              | varchar(10)  | not null           | FK to zip_code_zpc                                                       |
| `id_nbh_acc`                | int          | -                  | Optional neighborhood membership; state derivable via neighborhood_nbh   |
| `password_hash_acc`         | varchar(255) | not null           | bcrypt or argon2 hash only                                               |
| `id_rol_acc`                | int          | not null           | FK to role_rol                                                           |
| `id_ast_acc`                | int          | not null           | FK to account_status_ast                                                 |
| `id_cpr_acc`                | int          | not null           | FK to contact_preference_cpr                                             |
| `is_verified_acc`           | boolean      | default: false     | -                                                                        |
| `has_consent_acc`           | boolean      | default: false     | -                                                                        |
| `last_login_at_acc`         | timestamp    | -                  | -                                                                        |
| `created_at_acc`            | timestamp    | default: now()     | -                                                                        |
| `updated_at_acc`            | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                                              |
| `deleted_at_acc`            | timestamp    | -                  | Set via trigger when status changes to deleted; NULL = active            |
| `metadata_json_acc`         | json         | -                  | Future: preferences, settings, etc.                                      |

**Indexes:**

- `idx_email_acc` on `email_address_acc`
- `idx_zip_acc` on `zip_code_acc`
- `idx_role_acc` on `id_rol_acc`
- `idx_status_verified_acc` on `(id_ast_acc, is_verified_acc)`
- `idx_contact_preference_acc` on `id_cpr_acc`
- `idx_neighborhood_acc` on `id_nbh_acc`
- `idx_last_login_acc` on `last_login_at_acc`
- `idx_created_at_acc` on `created_at_acc`

**SQL Constraints Required:**

```sql
CHECK (email_address_acc REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
```

> **Note:**
>
> **Derived aggregates** (compute on-demand; cache later if needed):
>
> - Lender rating: `SELECT AVG(score_urt), COUNT(*) FROM user_rating_urt WHERE id_acc_target_urt = ? AND id_rtr_urt = <lender_id>`
> - Borrower rating: `SELECT AVG(score_urt), COUNT(*) FROM user_rating_urt WHERE id_acc_target_urt = ? AND id_rtr_urt = <borrower_id>`
> - Tool count: `SELECT COUNT(*) FROM tool_tol WHERE id_acc_tol = ? AND is_available_tol = true`

**Soft-Delete Strategy:**

- `id_ast_acc = deleted` status is the single source of truth for soft-delete
- `deleted_at_acc` records WHEN deletion occurred (for retention policies, GDPR compliance)
- CHECK: `(id_ast_acc = <deleted_id> AND deleted_at_acc IS NOT NULL) OR (id_ast_acc != <deleted_id> AND deleted_at_acc IS NULL)`
- Trigger: BEFORE UPDATE – set `deleted_at_acc = NOW()` when `id_ast_acc` changes to deleted
- View: `CREATE VIEW active_account_v AS SELECT * FROM account_acc WHERE id_ast_acc != <deleted_id>`
- Use `active_account_v` for all application reads; use base table for admin/audit queries
- Referencing tables enforce via BEFORE INSERT/UPDATE triggers (see tool_tol, borrow_bor, etc.)

---

#### account_image_aim

Profile images for user accounts. One account can have multiple images.

| Column            | Type         | Constraints        | Notes             |
|-------------------|--------------|--------------------|-------------------|
| `id_aim`          | int          | PK, auto-increment | -                 |
| `id_acc_aim`      | int          | not null           | FK to account_acc |
| `file_name_aim`   | varchar(255) | not null           | -                 |
| `alt_text_aim`    | varchar(255) | -                  | -                 |
| `is_primary_aim`  | boolean      | default: false     | -                 |
| `uploaded_at_aim` | timestamp    | default: now()     | -                 |

**Indexes:**

- `idx_account_primary_aim` on `(id_acc_aim, is_primary_aim)`

> **Note:**
>
> - Single-primary constraint enforced via BEFORE INSERT/UPDATE trigger.

---

#### account_bio_abi

Optional bio text stored separately to save space. Only populated when user
provides a bio - application displays placeholder text when no row exists.

| Column           | Type      | Constraints           | Notes                                            |
|------------------|-----------|-----------------------|--------------------------------------------------|
| `id_abi`         | int       | PK, auto-increment    | -                                                |
| `id_acc_abi`     | int       | unique, not null      | FK to account_acc; one bio per account           |
| `bio_text_abi`   | text      | not null              | -                                                |
| `created_at_abi` | timestamp | default: now()        | -                                                |
| `updated_at_abi` | timestamp | default: now()        | -                                                |

> **Note:**
>
> - Row exists only when user provides a bio.
> - Check for existence and display placeholder text if no row found.

---

### Shared Assets

#### vector_image_vec

Vector/SVG images uploaded by admins for site use.

| Column                | Type         | Constraints        | Notes                        |
|-----------------------|--------------|--------------------|------------------------------|
| `id_vec`              | int          | PK, auto-increment | -                            |
| `file_name_vec`       | varchar(255) | not null           | -                            |
| `description_text_vec`| text         | -                  | -                            |
| `id_acc_vec`          | int          | not null           | Uploaded by account (admin)  |
| `uploaded_at_vec`     | timestamp    | default: now()     | -                            |

**Indexes:**

- `idx_uploader_vec` on `id_acc_vec`

---

### Tools

#### tool_tol

Main tool listing table.

| Column                            | Type          | Constraints        | Notes                                                       |
|-----------------------------------|---------------|--------------------|-------------------------------------------------------------|
| `id_tol`                          | int           | PK, auto-increment | -                                                           |
| `tool_name_tol`                   | varchar(255)  | not null           | -                                                           |
| `tool_description_tol`            | text          | -                  | -                                                           |
| `id_tcd_tol`                      | int           | not null           | FK to tool_condition_tcd                                    |
| `id_acc_tol`                      | int           | not null           | Owner account FK                                            |
| `serial_number_tol`               | varchar(50)   | -                  | -                                                           |
| `rental_fee_tol`                  | decimal(6,2)  | default: 0.00      | 0 = free sharing                                            |
| `default_loan_duration_hours_tol` | int           | default: 168       | Owner default in hours; UI converts days/weeks              |
| `is_available_tol`                | boolean       | default: true      | Owner listing toggle - see Note for true availability logic |
| `created_at_tol`                  | timestamp     | default: now()     | -                                                           |
| `updated_at_tol`                  | timestamp     | default: now()     | -                                                           |
| `metadata_json_tol`               | json          | -                  | Future: custom attributes, tags                             |

**Indexes:**

- `idx_owner_available_tol` on `(id_acc_tol, is_available_tol)`
- `idx_condition_tol` on `id_tcd_tol`
- `idx_available_created_tol` on `(is_available_tol, created_at_tol)`
- `idx_created_at_tol` on `created_at_tol`
- `idx_rental_fee_tol` on `rental_fee_tol`
- `fulltext_tool_search_tol` (FULLTEXT) on `(tool_name_tol, tool_description_tol)`

> **Note:**
>
> - `is_available_tol` = owner intent only.
> - True availability requires: `is_available_tol = true` AND no overlapping `availability_block_avb` AND no active `borrow_bor`.
> - Compute at query time (JOIN/NOT EXISTS) for accuracy.
> - Trigger: BEFORE INSERT/UPDATE – reject if `id_acc_tol` references deleted account.
>
> **Derived aggregates** (compute on-demand; cache later if needed):
>
> - Avg rating: `SELECT AVG(score_trt), COUNT(*) FROM tool_rating_trt WHERE id_tol_trt = ?`
> - Borrow count: `SELECT COUNT(*) FROM borrow_bor WHERE id_tol_bor = ? AND id_bst_bor = <returned_id>`

---

#### tool_image_tim

Images for tools. One tool can have multiple images with display ordering.

| Column            | Type         | Constraints        | Notes                     |
|-------------------|--------------|--------------------|---------------------------|
| `id_tim`          | int          | PK, auto-increment | -                         |
| `id_tol_tim`      | int          | not null           | FK to tool_tol            |
| `file_name_tim`   | varchar(255) | not null           | -                         |
| `alt_text_tim`    | varchar(255) | -                  | -                         |
| `is_primary_tim`  | boolean      | default: false     | -                         |
| `sort_order_tim`  | int          | default: 0         | Display order for gallery |
| `uploaded_at_tim` | timestamp    | default: now()     | -                         |

**Indexes:**

- `idx_tool_primary_tim` on `(id_tol_tim, is_primary_tim)`
- `idx_tool_sort_tim` on `(id_tol_tim, sort_order_tim)`

> **Note:**
>
> - Single-primary constraint via BEFORE INSERT/UPDATE trigger.

---

### Borrowing

#### borrow_bor

Tracks tool borrow requests and their lifecycle.

| Column                 | Type      | Constraints        | Notes                                     |
|------------------------|-----------|--------------------|-------------------------------------------|
| `id_bor`               | int       | PK, auto-increment | -                                         |
| `id_tol_bor`           | int       | not null           | FK to tool_tol                            |
| `id_acc_bor`           | int       | not null           | Borrower account FK                       |
| `id_bst_bor`           | int       | not null           | FK to borrow_status_bst                   |
| `loan_duration_hours_bor` | int    | not null           | Agreed period in hours; UI converts       |
| `requested_at_bor`     | timestamp | default: now()     | -                                         |
| `approved_at_bor`      | timestamp | -                  | -                                         |
| `borrowed_at_bor`      | timestamp | -                  | -                                         |
| `due_at_bor`           | timestamp | -                  | Set via trigger on status -> borrowed     |
| `returned_at_bor`      | timestamp | -                  | -                                         |
| `cancelled_at_bor`     | timestamp | -                  | -                                         |
| `notes_text_bor`       | text      | -                  | -                                         |
| `is_contact_shared_bor`| boolean   | default: false     | -                                         |
| `created_at_bor`       | timestamp | default: now()     | -                                         |

**Indexes:**

- `idx_status_due_bor` on `(id_bst_bor, due_at_bor)`
- `idx_tool_status_bor` on `(id_tol_bor, id_bst_bor)`
- `idx_tool_borrower_bor` on `(id_tol_bor, id_acc_bor)`
- `idx_borrower_bor` on `id_acc_bor`
- `idx_returned_bor` on `returned_at_bor`
- `idx_requested_at_bor` on `requested_at_bor`

> **Note:**
>
> - CHECK constraints required for timestamp order & mutual exclusivity (returned vs cancelled).
> - Trigger: validate status-timestamp consistency + set `due_at_bor` when status changes to borrowed.
> - Prevent `due_at_bor` modification once set.
> - Trigger: prevent borrowing own tool (tool_tol.id_acc_tol != borrow_bor.id_acc_bor).

---

#### availability_block_avb

Manages tool availability, supporting both admin manual blocks and automatic
borrow unavailability.

| Column           | Type      | Constraints        | Notes                                             |
|------------------|-----------|--------------------| --------------------------------------------------|
| `id_avb`         | int       | PK, auto-increment | -                                                 |
| `id_tol_avb`     | int       | not null           | FK to tool_tol                                    |
| `id_btp_avb`     | int       | not null           | FK to block_type_btp                              |
| `start_at_avb`   | timestamp | not null           | -                                                 |
| `end_at_avb`     | timestamp | not null           | -                                                 |
| `id_bor_avb`     | int       | -                  | Required for borrow blocks; null for admin blocks |
| `notes_text_avb` | text      | -                  | -                                                 |
| `created_at_avb` | timestamp | default: now()     | -                                                 |
| `updated_at_avb` | timestamp | default: now()     | ON UPDATE CURRENT_TIMESTAMP                       |

**Indexes:**

- `idx_tool_range_avb` on `(id_tol_avb, start_at_avb, end_at_avb)`
- `uq_borrow_avb` (UNIQUE) on `id_bor_avb`
- `idx_block_type_avb` on `id_btp_avb`

> **Note:**
>
> - `CHECK (end_at_avb > start_at_avb)`.
> - Trigger: validate id_bor_avb presence based on block type (borrow -> required, admin -> NULL).
> - 1-to-1 with borrow for borrow-type blocks; UPDATE existing block on extensions.
> - **Overlap Prevention Trigger:** BEFORE INSERT/UPDATE prevents overlapping blocks for the same tool using `NEW.start_at_avb < end_at_avb AND NEW.end_at_avb > start_at_avb` check.
> - MySQL lacks PostgreSQL EXCLUDE constraints; trigger-based enforcement required.

---

### Ratings & Disputes

#### user_rating_urt

Ratings between users (lender rating borrower or vice versa).

| Column              | Type      | Constraints        | Notes                                       |
|---------------------|-----------|--------------------| --------------------------------------------|
| `id_urt`            | int       | PK, auto-increment | -                                           |
| `id_acc_urt`        | int       | not null           | Rater account FK                            |
| `id_acc_target_urt` | int       | not null           | Ratee account FK                            |
| `id_bor_urt`        | int       | not null           | FK to borrow_bor                            |
| `id_rtr_urt`        | int       | not null           | FK to rating_role_rtr (lender or borrower)  |
| `score_urt`         | int       | not null           | 1-5 scale                                   |
| `comment_text_urt`  | text      | -                  | -                                           |
| `created_at_urt`    | timestamp | default: now()     | -                                           |

**Indexes:**

- `idx_target_role_urt` on `(id_acc_target_urt, id_rtr_urt)`
- `uq_one_user_rating_per_borrow_urt` (UNIQUE) on `(id_bor_urt, id_acc_urt, id_rtr_urt)`
- `idx_rater_urt` on `id_acc_urt`

> **Note:**
>
> - `CHECK (score_urt BETWEEN 1 AND 5)`.
> - `CHECK (id_acc_urt != id_acc_target_urt)` - prevents self-rating.

---

#### tool_rating_trt

Ratings for tools after borrowing.

| Column             | Type      | Constraints        | Notes            |
|--------------------|-----------|--------------------| -----------------|
| `id_trt`           | int       | PK, auto-increment | -                |
| `id_acc_trt`       | int       | not null           | Rater account FK |
| `id_tol_trt`       | int       | not null           | FK to tool_tol   |
| `id_bor_trt`       | int       | not null           | FK to borrow_bor |
| `score_trt`        | int       | not null           | 1-5 scale        |
| `comment_text_trt` | text      | -                  | -                |
| `created_at_trt`   | timestamp | default: now()     | -                |

**Indexes:**

- `idx_tool_score_trt` on `(id_tol_trt, score_trt)` - covering index for AVG aggregation
- `uq_one_tool_rating_per_borrow_trt` (UNIQUE) on `(id_bor_trt, id_tol_trt)`
- `idx_rater_trt` on `id_acc_trt`

> **Note:**
>
> - `CHECK (score_trt BETWEEN 1 AND 5)`; UNIQUE per borrow/tool.
> - Covering index on (id_tol_trt, score_trt) enables AVG aggregation without table lookup.

---

#### dispute_dsp

Handles conflicts and issues related to borrow transactions. Dispute header; messages in dispute_message_dsm.

| Column                  | Type         | Constraints        | Notes                                   |
|-------------------------|--------------|--------------------|-----------------------------------------|
| `id_dsp`                | int          | PK, auto-increment | -                                       |
| `id_bor_dsp`            | int          | not null           | FK to borrow_bor                        |
| `id_acc_dsp`            | int          | not null           | Reporter account FK                     |
| `subject_text_dsp`      | varchar(255) | not null           | -                                       |
| `id_dst_dsp`            | int          | not null           | FK to dispute_status_dst                |
| `id_acc_resolver_dsp`   | int          | -                  | Admin who resolved FK                   |
| `resolved_at_dsp`       | timestamp    | -                  | -                                       |
| `created_at_dsp`        | timestamp    | default: now()     | -                                       |
| `updated_at_dsp`        | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP             |
| `id_acc_updated_by_dsp` | int          | -                  | Admin who last modified; NULL if system |

**Indexes:**

- `idx_status_dsp` on `id_dst_dsp`
- `idx_borrow_dsp` on `id_bor_dsp`
- `idx_reporter_dsp` on `id_acc_dsp`
- `idx_resolver_dsp` on `id_acc_resolver_dsp`
- `idx_updated_by_dsp` on `id_acc_updated_by_dsp`

---

#### dispute_message_dsm

Messages within a dispute thread.

| Column             | Type      | Constraints        | Notes                          |
|--------------------|-----------|--------------------|--------------------------------|
| `id_dsm`           | int       | PK, auto-increment | -                              |
| `id_dsp_dsm`       | int       | not null           | FK to dispute_dsp              |
| `id_acc_dsm`       | int       | not null           | Author account FK              |
| `id_dmt_dsm`       | int       | not null           | FK to dispute_message_type_dmt |
| `message_text_dsm` | text      | not null           | -                              |
| `is_internal_dsm`  | boolean   | default: false     | Admin-only if true             |
| `created_at_dsm`   | timestamp | default: now()     | -                              |

**Indexes:**

- `idx_dispute_timeline_dsm` on `(id_dsp_dsm, created_at_dsm)`
- `idx_author_dsm` on `id_acc_dsm`
- `idx_message_type_dsm` on `id_dmt_dsm`

---

### User Interactions

#### notification_ntf

System notifications sent to users.

| Column             | Type      | Constraints        | Notes                         |
|--------------------|-----------|--------------------| ------------------------------|
| `id_ntf`           | int       | PK, auto-increment | -                             |
| `id_acc_ntf`       | int       | not null           | FK to account_acc             |
| `id_ntt_ntf`       | int       | not null           | FK to notification_type_ntt   |
| `message_text_ntf` | text      | not null           | -                             |
| `id_bor_ntf`       | int       | -                  | FK to borrow_bor (optional)   |
| `is_read_ntf`      | boolean   | default: false     | -                             |
| `created_at_ntf`   | timestamp | default: now()     | -                             |

**Indexes:**

- `idx_unread_timeline_ntf` on `(id_acc_ntf, is_read_ntf, created_at_ntf)` - covering index for notification feed
- `idx_borrow_ntf` on `id_bor_ntf`
- `idx_type_ntf` on `id_ntt_ntf`

> **Note:**
>
> - Archival: Delete or move records older than 12 months via scheduled job.
> - At small scale (< 100K rows/year), no partitioning needed.

---

#### search_log_slg

Analytics table for tracking user searches.

| Column            | Type         | Constraints        | Notes                         |
|-------------------|--------------|--------------------| ------------------------------|
| `id_slg`          | int          | PK, auto-increment | -                             |
| `id_acc_slg`      | int          | -                  | FK to account_acc (optional)  |
| `id_tol_slg`      | int          | -                  | FK to tool_tol (if clicked)   |
| `search_text_slg` | varchar(255) | -                  | -                             |
| `ip_address_slg`  | varchar(45)  | -                  | Supports IPv6                 |
| `session_id_slg`  | varchar(64)  | -                  | -                             |
| `created_at_slg`  | timestamp    | default: now()     | -                             |

**Indexes:**

- `fulltext_search_slg` (FULLTEXT) on `search_text_slg`
- `idx_created_at_slg` on `created_at_slg`
- `idx_account_slg` on `id_acc_slg`
- `idx_tool_slg` on `id_tol_slg`

> **Note:**
>
> - Search logs for analytics.
> - Archival: Delete or move records older than 12 months via scheduled job.
> - At small scale (< 500K rows/year), no partitioning needed.

---

### Junction Tables

#### tool_category_tolcat

Junction table enabling many-to-many relationship between tools and categories.

| Column             | Type      | Constraints        | Notes              |
|--------------------|-----------|--------------------|--------------------|
| `id_tolcat`        | int       | PK, auto-increment | -                  |
| `id_tol_tolcat`    | int       | not null           | FK to tool_tol     |
| `id_cat_tolcat`    | int       | not null           | FK to category_cat |
| `created_at_tolcat`| timestamp | default: now()     | -                  |

**Indexes:**

- `uq_tool_category_tolcat` (UNIQUE) on `(id_tol_tolcat, id_cat_tolcat)`
- `idx_category_tolcat` on `id_cat_tolcat`

> **Note:**
>
> - Junction table: tools can belong to multiple categories.

---

#### tool_bookmark_acctol

Junction table for user-saved/favorited tools.

| Column              | Type      | Constraints        | Notes             |
|---------------------|-----------|--------------------|-------------------|
| `id_acctol`         | int       | PK, auto-increment | -                 |
| `id_acc_acctol`     | int       | not null           | FK to account_acc |
| `id_tol_acctol`     | int       | not null           | FK to tool_tol    |
| `created_at_acctol` | timestamp | default: now()     | -                 |

**Indexes:**

- `uq_account_tool_acctol` (UNIQUE) on `(id_acc_acctol, id_tol_acctol)`
- `idx_tool_acctol` on `id_tol_acctol`

---

#### neighborhood_zip_nbhzpc

Junction table: neighborhoods can contain multiple ZIPs, ZIPs can belong to multiple neighborhoods.

| Column              | Type        | Constraints        | Notes                                                        |
|---------------------|-------------|--------------------|--------------------------------------------------------------|
| `id_nbhzpc`         | int         | PK, auto-increment | -                                                            |
| `id_nbh_nbhzpc`     | int         | not null           | FK to neighborhood_nbh                                       |
| `zip_code_nbhzpc`   | varchar(10) | not null           | FK to zip_code_zpc                                           |
| `is_primary_nbhzpc` | boolean     | default: false     | True = primary neighborhood for this ZIP; one allowed per ZIP|
| `created_at_nbhzpc` | timestamp   | default: now()     | -                                                            |

**Indexes:**

- `uq_neighborhood_zip_nbhzpc` (UNIQUE) on `(id_nbh_nbhzpc, zip_code_nbhzpc)`
- `idx_zip_primary_nbhzpc` on `(zip_code_nbhzpc, is_primary_nbhzpc)`
- `idx_neighborhood_nbhzpc` on `id_nbh_nbhzpc`

> **Note:**
>
> - Handles edge cases where ZIP codes cross neighborhood/community boundaries.
> - Constraint: Only one `is_primary_nbhzpc = true` per `zip_code_nbhzpc`.
> - Trigger: BEFORE INSERT/UPDATE – enforce single primary per ZIP.

```sql
IF NEW.is_primary_nbhzpc = true AND EXISTS (
  SELECT 1 FROM neighborhood_zip_nbhzpc
  WHERE zip_code_nbhzpc = NEW.zip_code_nbhzpc
    AND is_primary_nbhzpc = true
    AND id_nbhzpc != COALESCE(NEW.id_nbhzpc, 0)
) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one primary neighborhood per ZIP allowed';
```

---

### Future Expansion

#### event_evt

Community events table for future functionality.

| Column                  | Type         | Constraints        | Notes                                      |
|-------------------------|--------------|--------------------|--------------------------------------------|
| `id_evt`                | int          | PK, auto-increment | -                                          |
| `event_name_evt`        | varchar(255) | not null           | -                                          |
| `event_description_evt` | text         | -                  | -                                          |
| `start_at_evt`          | timestamp    | not null           | -                                          |
| `end_at_evt`            | timestamp    | -                  | -                                          |
| `id_nbh_evt`            | int          | -                  | Neighborhood where event takes place       |
| `id_acc_evt`            | int          | not null           | Created by account (admin) FK              |
| `created_at_evt`        | timestamp    | default: now()     | -                                          |
| `updated_at_evt`        | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                |
| `id_acc_updated_by_evt` | int          | -                  | Admin who last modified; NULL if unchanged |
| `metadata_json_evt`     | json         | -                  | Future: tags, RSVPs, etc.                  |

**Indexes:**

- `idx_date_neighborhood_evt` on `(start_at_evt, id_nbh_evt)`
- `idx_creator_evt` on `id_acc_evt`
- `idx_updated_by_evt` on `id_acc_updated_by_evt`
- `idx_neighborhood_evt` on `id_nbh_evt`

---

#### phpbb_integration_php

Placeholder for phpBB forum SSO integration.

| Column             | Type      | Constraints        | Notes                          |
|--------------------|-----------|--------------------|--------------------------------|
| `id_php`           | int       | PK, auto-increment | -                              |
| `id_acc_php`       | int       | not null           | Maps to phpBB user             |
| `phpbb_user_id_php`| int       | -                  | phpBB user ID for SSO linking  |
| `created_at_php`   | timestamp | default: now()     | -                              |

**Indexes:**

- `uq_account_php` (UNIQUE) on `id_acc_php`

> **Note:**
>
> - Placeholder for phpBB forum SSO integration.

---

#### audit_log_aud

Generic audit log for tracking changes across all tables. Implement when detailed change history is needed.

| Column                | Type        | Constraints        | Notes                                         |
|-----------------------|-------------|--------------------|-----------------------------------------------|
| `id_aud`              | int         | PK, auto-increment | -                                             |
| `table_name_aud`      | varchar(64) | not null           | Name of the table that was modified           |
| `row_id_aud`          | int         | not null           | PK of the modified row                        |
| `action_aud`          | varchar(10) | not null           | INSERT, UPDATE, DELETE                        |
| `id_acc_aud`          | int         | -                  | Account who made the change; NULL if system   |
| `old_values_json_aud` | json        | -                  | Previous row state (UPDATE/DELETE only)       |
| `new_values_json_aud` | json        | -                  | New row state (INSERT/UPDATE only)            |
| `created_at_aud`      | timestamp   | default: now()     | -                                             |

**Indexes:**

- `idx_table_row_aud` on `(table_name_aud, row_id_aud)`
- `idx_account_aud` on `id_acc_aud`
- `idx_created_at_aud` on `created_at_aud`

> **Note:**
>
> - Future: Implement via AFTER INSERT/UPDATE/DELETE triggers on tables requiring audit trails.
> - Archival: Delete or move records older than 24 months via scheduled job.

---

## Relationships

### Many-to-Many Relationships (M:M)

Junction tables create the following M:M relationships:

| Relationship               | Parent A           | Parent B       | Junction Table           |
|----------------------------|--------------------|----------------|--------------------------|
| Tools have Categories      | `tool_tol`         | `category_cat` | `tool_category_tolcat`   |
| Accounts bookmark Tools    | `account_acc`      | `tool_tol`     | `tool_bookmark_acctol`   |
| Neighborhoods have ZIPs    | `neighborhood_nbh` | `zip_code_zpc` | `neighborhood_zip_nbhzpc`|

### One-to-Many Relationships (1:M)

#### Account Domain

| Parent (One)             | Child (Many)        | Foreign Key        | Description                       |
|--------------------------|---------------------|--------------------|-----------------------------------|
| `role_rol`               | `account_acc`       | `id_rol_acc`       | Role assigned to accounts         |
| `account_status_ast`     | `account_acc`       | `id_ast_acc`       | Status of accounts                |
| `contact_preference_cpr` | `account_acc`       | `id_cpr_acc`       | Contact preference for accounts   |
| `zip_code_zpc`           | `account_acc`       | `zip_code_acc`     | Location of accounts              |
| `neighborhood_nbh`       | `account_acc`       | `id_nbh_acc`       | Optional neighborhood membership  |
| `account_acc`            | `account_image_aim` | `id_acc_aim`       | Account has profile images        |
| `account_acc`            | `account_bio_abi`   | `id_acc_abi`       | Account has optional bio (0 or 1) |
| `account_acc`            | `vector_image_vec`  | `id_acc_vec`       | Admin uploads vector images       |
| `vector_image_vec`       | `category_cat`      | `id_vec_cat`       | Category has optional icon        |

#### Neighborhood Domain

| Parent (One)       | Child (Many)             | Foreign Key       | Description                        |
|--------------------|--------------------------|-------------------|------------------------------------|
| `state_sta`        | `neighborhood_nbh`       | `id_sta_nbh`      | State for neighborhood             |
| `neighborhood_nbh` | `neighborhood_zip_nbhzpc`| `id_nbh_nbhzpc`   | Neighborhood contains ZIP codes    |
| `zip_code_zpc`     | `neighborhood_zip_nbhzpc`| `zip_code_nbhzpc` | ZIP code belongs to neighborhoods  |

#### Tool Domain

| Parent (One)         | Child (Many)      | Foreign Key    | Description                    |
|----------------------|-------------------|----------------|--------------------------------|
| `tool_condition_tcd` | `tool_tol`        | `id_tcd_tol`   | Condition of tools             |
| `account_acc`        | `tool_tol`        | `id_acc_tol`   | Account owns tools             |
| `tool_tol`           | `tool_image_tim`  | `id_tol_tim`   | Tool has images                |

#### Borrowing Domain

| Parent (One)        | Child (Many)            | Foreign Key    | Description                       |
|---------------------|-------------------------|----------------|-----------------------------------|
| `tool_tol`          | `borrow_bor`            | `id_tol_bor`   | Tool is borrowed multiple times   |
| `account_acc`       | `borrow_bor`            | `id_acc_bor`   | Account borrows tools             |
| `borrow_status_bst` | `borrow_bor`            | `id_bst_bor`   | Status of borrow requests         |
| `tool_tol`          | `availability_block_avb`| `id_tol_avb`   | Tool has availability blocks      |
| `block_type_btp`    | `availability_block_avb`| `id_btp_avb`   | Type of availability block        |
| `borrow_bor`        | `availability_block_avb`| `id_bor_avb`   | Borrow creates availability block |

#### Rating Domain

| Parent (One)      | Child (Many)      | Foreign Key        | Description                        |
|-------------------|-------------------|--------------------|------------------------------------|
| `account_acc`     | `user_rating_urt` | `id_acc_urt`       | Account gives user ratings         |
| `account_acc`     | `user_rating_urt` | `id_acc_target_urt`| Account receives user ratings      |
| `borrow_bor`      | `user_rating_urt` | `id_bor_urt`       | Borrow transaction has ratings     |
| `rating_role_rtr` | `user_rating_urt` | `id_rtr_urt`       | Rating context (lender/borrower)   |
| `account_acc`     | `tool_rating_trt` | `id_acc_trt`       | Account rates tools                |
| `tool_tol`        | `tool_rating_trt` | `id_tol_trt`       | Tool receives ratings              |
| `borrow_bor`      | `tool_rating_trt` | `id_bor_trt`       | Borrow transaction has tool rating |

#### Dispute Domain

| Parent (One)              | Child (Many)         | Foreign Key            | Description                  |
|---------------------------|----------------------|------------------------|------------------------------|
| `borrow_bor`              | `dispute_dsp`        | `id_bor_dsp`           | Borrow has disputes          |
| `account_acc`             | `dispute_dsp`        | `id_acc_dsp`           | Account reports disputes     |
| `account_acc`             | `dispute_dsp`        | `id_acc_resolver_dsp`  | Admin resolves disputes      |
| `account_acc`             | `dispute_dsp`        | `id_acc_updated_by_dsp`| Admin last modified dispute  |
| `dispute_status_dst`      | `dispute_dsp`        | `id_dst_dsp`           | Status of disputes           |
| `dispute_dsp`             | `dispute_message_dsm`| `id_dsp_dsm`           | Dispute has messages         |
| `account_acc`             | `dispute_message_dsm`| `id_acc_dsm`           | Account authors messages     |
| `dispute_message_type_dmt`| `dispute_message_dsm`| `id_dmt_dsm`           | Type of dispute message      |

#### Notification Domain

| Parent (One)           | Child (Many)       | Foreign Key  | Description                     |
|------------------------|--------------------|--------------|---------------------------------|
| `account_acc`          | `notification_ntf` | `id_acc_ntf` | Account receives notifications  |
| `notification_type_ntt`| `notification_ntf` | `id_ntt_ntf` | Type of notification            |
| `borrow_bor`           | `notification_ntf` | `id_bor_ntf` | Borrow triggers notifications   |

#### Search & Analytics Domain

| Parent (One)  | Child (Many)    | Foreign Key  | Description                    |
|---------------|-----------------|--------------|--------------------------------|
| `account_acc` | `search_log_slg`| `id_acc_slg` | Account performs searches      |
| `tool_tol`    | `search_log_slg`| `id_tol_slg` | Tool appears in search results |

#### Event Domain

| Parent (One)       | Child (Many) | Foreign Key             | Description               |
|--------------------|--------------|-------------------------|---------------------------|
| `neighborhood_nbh` | `event_evt`  | `id_nbh_evt`            | Location of events        |
| `account_acc`      | `event_evt`  | `id_acc_evt`            | Admin creates events      |
| `account_acc`      | `event_evt`  | `id_acc_updated_by_evt` | Admin last modified event |

#### phpBB Integration Domain

| Parent (One)  | Child (Many)           | Foreign Key  | Description              |
|---------------|------------------------|--------------|--------------------------|
| `account_acc` | `phpbb_integration_php`| `id_acc_php` | Account links to phpBB   |

#### Audit Log Domain

| Parent (One)  | Child (Many)    | Foreign Key  | Description                  |
|---------------|-----------------|--------------|------------------------------|
| `account_acc` | `audit_log_aud` | `id_acc_aud` | Account makes audited change |

---

## Entity Relationship Diagram

```text
+------------------------------------------------------------------------------+
|                              ACCOUNTS GROUP                                  |
+------------------------------------------------------------------------------+
|                                                                              |
|  +------------+    +--------------------+    +-------------------------+     |
|  |  role_rol  |    | account_status_ast |    | contact_preference_cpr  |     |
|  +------+-----+    +---------+----------+    +------------+------------+     |
|         |                    |                            |                  |
|         +--------------------+----------------------------+                  |
|                              |                                               |
|                              v                                               |
|  +-----------+      +----------------+                                       |
|  | state_sta |      |  account_acc   |<---------+                            |
|  +-----+-----+      +-------+--------+          |                            |
|        |                    |                   |                            |
|        v                    v                   |                            |
|  +------------------+  +------------------+     |                            |
|  | neighborhood_nbh |  | account_image_aim|     |                            |
|  +--------+---------+  +------------------+     |                            |
|           |                   |                 |                            |
|           v                   v                 |                            |
|    +-------------+     +----------------+       |                            |
|    | zip_code_zpc|     | account_bio_abi|       |                            |
|    +-------------+     +----------------+       |                            |
|                                                 |                            |
+------------------------------------------------------------------------------+
                                                  |
+------------------------------------------------------------------------------+
|                              TOOLS GROUP        |                            |
+------------------------------------------------------------------------------+
|                                                 |                            |
|  +-------------------+    +---------------+     |                            |
|  | tool_condition_tcd|    | category_cat  |     |                            |
|  +--------+----------+    +------+--------+     |                            |
|           |                      |              |                            |
|           +----------+----------+               |                            |
|                      |                          |                            |
|                      v                          |                            |
|             +-----------------+<----------------+                            |
|             |    tool_tol     |                                              |
|             +--------+--------+                                              |
|                      |                                                       |
|                      v                                                       |
|              +----------------+                                              |
|              | tool_image_tim |                                              |
|              +----------------+                                              |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                            BORROWING GROUP                                   |
+------------------------------------------------------------------------------+
|                                                                              |
|  +------------------+                                                        |
|  | borrow_status_bst|                                                        |
|  +--------+---------+                                                        |
|           |                                                                  |
|           v                                                                  |
|  +-----------------+       +------------------------+                        |
|  |   borrow_bor    |<------| availability_block_avb |                        |
|  +--------+--------+       +-----------+------------+                        |
|           |                            |                                     |
|           |                            v                                     |
|           |                   +----------------+                             |
|           |                   | block_type_btp |                             |
|           |                   +----------------+                             |
|           |                                                                  |
+-----------+--------------------------------------------------------------+---+
            |
+-----------+------------------------------------------------------------------+
|           |                RATINGS & DISPUTES GROUP                          |
+-----------+------------------------------------------------------------------+
|           |                                                                  |
|           v                                                                  |
|  +-----------------+    +-----------------+    +-----------------+           |
|  | user_rating_urt |    | tool_rating_trt |    |   dispute_dsp   |           |
|  +--------+--------+    +-----------------+    +--------+--------+           |
|           |                                             |                    |
|           v                                             v                    |
|  +-----------------+                          +------------------+           |
|  | rating_role_rtr |                          |dispute_status_dst|           |
|  +-----------------+                          +------------------+           |
|                                                         |                    |
|                                                         v                    |
|                                               +--------------------+         |
|                                               | dispute_message_dsm|         |
|                                               +---------+----------+         |
|                                                         |                    |
|                                                         v                    |
|                                              +-------------------------+     |
|                                              | dispute_message_type_dmt|     |
|                                              +-------------------------+     |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                          USER INTERACTIONS GROUP                             |
+------------------------------------------------------------------------------+
|                                                                              |
|            +-----------------+                +-----------------+            |
|            | notification_ntf|                |  search_log_slg |            |
|            +--------+--------+                +-----------------+            |
|                     |                                                        |
|                     v                                                        |
|           +----------------------+                                           |
|           | notification_type_ntt|                                           |
|           +----------------------+                                           |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                          SHARED ASSETS GROUP                                 |
+------------------------------------------------------------------------------+
|                                                                              |
|                         +-----------------+                                  |
|                         | vector_image_vec|                                  |
|                         +-----------------+                                  |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                          JUNCTION TABLES GROUP                               |
+------------------------------------------------------------------------------+
|                                                                              |
| +----------------------+  +---------------------+  +-----------------------+ |
| | tool_category_tolcat |  | tool_bookmark_acctol|  |neighborhood_zip_nbhzpc| |
| +----------------------+  +---------------------+  +-----------------------+ |
|                                                                              |
|   tool_tol <-> category_cat   account_acc <-> tool_tol   nbh <-> zip_code    |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                          FUTURE EXPANSION GROUP                              |
+------------------------------------------------------------------------------+
|                                                                              |
|    +-----------------+  +----------------------+  +-----------------+        |
|    |    event_evt    |  | phpbb_integration_php|  |  audit_log_aud  |        |
|    +-----------------+  +----------------------+  +-----------------+        |
|                                                                              |
+------------------------------------------------------------------------------+
```

---

## Naming Conventions

This database uses a consistent naming convention:

- **Table names:** `entity_suffix` (e.g., `account_acc`, `tool_tol`)
- **Column names:** `column_name_suffix` where suffix matches the table suffix
- **Primary keys:** `id_suffix` (e.g., `id_acc`, `id_tol`)
- **Foreign keys:** `id_referenced_table_current_table` (e.g., `id_acc_bor` for
  account FK in borrow table)
- **Indexes:** Descriptive names prefixed with `idx_`, `uq_`, or `fulltext_`

### Junction Table Naming

Junction tables model many-to-many relationships and use a special naming convention:

- **Table names:** `descriptive_name_suffix1suffix2` where suffix1 and suffix2 are
  the 3-character suffixes from both parent tables (e.g., `tool_category_tolcat`
  combines `tol` + `cat`)
- **Primary keys:** `id_suffix1suffix2` (e.g., `id_tolcat`, `id_acctol`)
- **Foreign keys:** `id_parentsuffix_junctionsuffix` (e.g., `id_tol_tolcat`,
  `id_cat_tolcat`)
- **Uniqueness:** Enforced via composite unique constraint on both foreign keys

This convention ensures clarity about which table each column belongs to and
makes SQL queries more readable.
