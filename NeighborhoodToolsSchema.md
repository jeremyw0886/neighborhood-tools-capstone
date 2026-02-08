# NeighborhoodTools.com Database Design

**Author:** Jeremy Warren

**Course:** WEB-289 Capstone Project

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
   - [Legal & Compliance](#legal--compliance)
   - [Payments & Deposits](#payments--deposits)
   - [Materialized Summary Tables](#materialized-summary-tables)
5. [Relationships](#relationships)
6. [Entity Relationship Diagram](#entity-relationship-diagram)
7. [Procedural Layer](#procedural-layer)
8. [Naming Conventions](#naming-conventions)
9. [Development Tools Used](#development-tools-used)

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
- **Legal & compliance** with Terms of Service versioning, digital waivers, handover verification, and incident reporting
- **Security deposits** with payment provider integration and transaction tracking
- **Future expansion** for community events and phpBB forum integration

---

## Table Groups

The database is organized into logical groups for easier management and
visualization:

| Group                  | Tables                                                                                                                                                            |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Accounts**           | `role_rol`, `account_status_ast`, `contact_preference_cpr`, `state_sta`, `neighborhood_nbh`, `neighborhood_meta_nbm`, `zip_code_zpc`, `account_acc`, `account_meta_acm`, `account_image_aim`, `account_bio_abi`|
| **Tools**              | `category_cat`, `tool_condition_tcd`, `tool_tol`, `tool_meta_tlm`, `tool_image_tim`                                                                               |
| **Borrowing**          | `borrow_status_bst`, `block_type_btp`, `borrow_bor`, `availability_block_avb`, `loan_extension_lex`                                                               |
| **Ratings & Disputes** | `rating_role_rtr`, `user_rating_urt`, `tool_rating_trt`, `dispute_dsp`, `dispute_status_dst`, `dispute_message_type_dmt`, `dispute_message_dsm`                   |
| **User Interactions**  | `notification_ntf`, `notification_type_ntt`, `search_log_slg`                                                                                                     |
| **Shared Assets**      | `vector_image_vec`                                                                                                                                                |
| **Junction Tables**    | `tool_category_tolcat`, `tool_bookmark_acctol`, `neighborhood_zip_nbhzpc`                                                                                         |
| **Future Expansion**   | `event_evt`, `event_meta_evm`, `phpbb_integration_php`, `audit_log_aud`, `audit_log_detail_ald`                                                                   |
| **Legal & Compliance** | `terms_of_service_tos`, `tos_acceptance_tac`, `waiver_type_wtp`, `borrow_waiver_bwv`, `handover_type_hot`, `handover_verification_hov`, `incident_type_ity`, `incident_report_irt`, `incident_photo_iph`|
| **Payments & Deposits**| `deposit_status_dps`, `security_deposit_sdp`, `payment_provider_ppv`, `payment_transaction_ptx`, `payment_transaction_meta_ptm`                                                   |
| **Materialized Summaries** | `neighborhood_summary_mat`, `user_reputation_mat`, `tool_statistics_mat`, `category_summary_mat`, `platform_daily_stat_pds`                                |

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

### waiver_type_wtp

Types of digital waivers for legal compliance.

| Column          | Type        | Constraints        | Notes                                                       |
|-----------------|-------------|--------------------|-------------------------------------------------------------|
| `id_wtp`        | int         | PK, auto-increment | -                                                           |
| `type_name_wtp` | varchar(50) | unique, not null   | Values: borrow_waiver, condition_acknowledgment, liability_release |

---

### handover_type_hot

Types of tool handover events.

| Column          | Type        | Constraints        | Notes                  |
|-----------------|-------------|--------------------|------------------------|
| `id_hot`        | int         | PK, auto-increment | -                      |
| `type_name_hot` | varchar(30) | unique, not null   | Values: pickup, return |

---

### incident_type_ity

Categories of incidents for mandatory reporting.

| Column          | Type        | Constraints        | Notes                                                              |
|-----------------|-------------|--------------------|-------------------------------------------------------------------|
| `id_ity`        | int         | PK, auto-increment | -                                                                  |
| `type_name_ity` | varchar(50) | unique, not null   | Values: damage, theft, loss, injury, late_return, condition_dispute, other |

---

### deposit_status_dps

States for security deposit lifecycle.

| Column            | Type        | Constraints        | Notes                                                  |
|-------------------|-------------|--------------------|--------------------------------------------------------|
| `id_dps`          | int         | PK, auto-increment | -                                                      |
| `status_name_dps` | varchar(30) | unique, not null   | Values: pending, held, released, forfeited, partial_release |

---

### payment_provider_ppv

Supported payment providers for deposits and fees.

| Column              | Type        | Constraints        | Notes                           |
|---------------------|-------------|--------------------|--------------------------------|
| `id_ppv`            | int         | PK, auto-increment | -                               |
| `provider_name_ppv` | varchar(50) | unique, not null   | Values: stripe, paypal, manual  |
| `is_active_ppv`     | boolean     | default: true      | Whether provider is available   |

---

## Core Tables

### Accounts

#### neighborhood_nbh

Dedicated neighborhood entity for local communities/service areas.

| Column                  | Type         | Constraints        | Notes                                                             |
|-------------------------|--------------|--------------------|-------------------------------------------------------------------|
| `id_nbh`                | int          | PK, auto-increment | -                                                                 |
| `neighborhood_name_nbh` | varchar(100) | unique, not null   | Name of local community/service area                              |
| `city_name_nbh`         | varchar(100) | -                  | Primary city for this neighborhood                                |
| `id_sta_nbh`            | int          | not null           | State the neighborhood is primarily in (FK to state_sta)          |
| `latitude_nbh`          | decimal(9,6) | not null           | -                                                                 |
| `longitude_nbh`         | decimal(9,6) | not null           | -                                                                 |
| `location_point_nbh`    | point        | not null, SRID 4326| MySQL 8 POINT type with SRID 4326 (WGS84) for optimized proximity |
| `created_at_nbh`        | timestamp    | default: now()     | -                                                                 |
| `updated_at_nbh`        | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                                       |

**Indexes:**

- `idx_state_nbh` on `id_sta_nbh`
- `idx_city_nbh` on `city_name_nbh`
- `idx_location_nbh` (SPATIAL) on `location_point_nbh`

> **Note:**
>
> - Spatial trigger: BEFORE INSERT/UPDATE – auto-populate `location_point_nbh` from lat/long:
>   `SET NEW.location_point_nbh = ST_PointFromText(CONCAT('POINT(', NEW.longitude_nbh, ' ', NEW.latitude_nbh, ')'), 4326)`

---

#### neighborhood_meta_nbm

Optional neighborhood metadata in key/value rows (strict 1NF/3NF). Replaces JSON column for future community features and settings.

| Column           | Type         | Constraints        | Notes                   |
|------------------|--------------|--------------------|-------------------------|
| `id_nbm`         | int          | PK, auto-increment | -                       |
| `id_nbh_nbm`    | int          | not null           | FK to neighborhood_nbh  |
| `meta_key_nbm`  | varchar(100) | not null           | -                       |
| `meta_value_nbm`| varchar(255) | not null           | -                       |
| `created_at_nbm`| timestamp    | default: now()     | -                       |

**Indexes:**

- `uq_neighborhood_meta_nbm` (UNIQUE) on `(id_nbh_nbm, meta_key_nbm)`
- `idx_meta_key_nbm` on `meta_key_nbm`

---

#### zip_code_zpc

ZIP code table – pure geographic identifiers only.

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

**Indexes:**

- `idx_email_acc` on `email_address_acc`
- `idx_zip_acc` on `zip_code_acc`
- `idx_role_acc` on `id_rol_acc`
- `idx_status_verified_acc` on `(id_ast_acc, is_verified_acc)`
- `idx_contact_preference_acc` on `id_cpr_acc`
- `idx_neighborhood_acc` on `id_nbh_acc`
- `idx_status_neighborhood_verified_acc` on `(id_ast_acc, id_nbh_acc, is_verified_acc)`
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

#### account_meta_acm

Optional account metadata in key/value rows (strict 1NF/3NF). Replaces JSON column for future preferences and settings.

| Column           | Type         | Constraints        | Notes             |
|------------------|--------------|--------------------|-------------------|
| `id_acm`         | int          | PK, auto-increment | -                 |
| `id_acc_acm`     | int          | not null           | FK to account_acc |
| `meta_key_acm`   | varchar(100) | not null           | -                 |
| `meta_value_acm` | varchar(255) | not null           | -                 |
| `created_at_acm` | timestamp    | default: now()     | -                 |

**Indexes:**

- `uq_account_meta_acm` (UNIQUE) on `(id_acc_acm, meta_key_acm)`
- `idx_meta_key_acm` on `meta_key_acm`

---

#### account_image_aim

Profile images for user accounts. One account can have multiple images.

| Column            | Type         | Constraints        | Notes             |
|-------------------|--------------|--------------------|-------------------|
| `id_aim`          | int          | PK, auto-increment | -                 |
| `id_acc_aim`      | int          | not null           | FK to account_acc |
| `file_name_aim`   | varchar(255) | not null           | -                 |
| `alt_text_aim`    | varchar(255) | -                  | -                 |
| `is_primary_aim`  | boolean      | default: false     | -                                                       |
| `uploaded_at_aim` | timestamp    | default: now()     | -                                                       |
| `primary_flag_aim`| tinyint      | generated (stored) | `IF(is_primary_aim, 1, NULL)` — for unique enforcement  |

**Indexes:**

- `idx_account_primary_aim` on `(id_acc_aim, is_primary_aim)`
- `uq_one_primary_per_account_aim` (UNIQUE) on `(id_acc_aim, primary_flag_aim)`

> **Note:**
>
> - Single-primary constraint enforced via generated column + composite unique index.

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
| `is_deposit_required_tol`         | boolean       | default: false     | Lender requires refundable security deposit                 |
| `default_deposit_amount_tol`      | decimal(8,2)  | default: 0.00      | Default deposit amount; 0 = no deposit required             |
| `estimated_value_tol`             | decimal(8,2)  | -                  | Estimated tool value for insurance/deposit reference        |
| `preexisting_conditions_tol`      | text          | -                  | Lender disclosure of pre-existing damage, wear, conditions  |
| `is_insurance_recommended_tol`    | boolean       | default: false     | Flag for high-value tools where insurance is recommended    |
| `created_at_tol`                  | timestamp     | default: now()     | -                                                           |
| `updated_at_tol`                  | timestamp     | default: now()     | -                                                           |

**Indexes:**

- `idx_owner_available_tol` on `(id_acc_tol, is_available_tol)`
- `idx_condition_tol` on `id_tcd_tol`
- `idx_available_owner_created_tol` on `(is_available_tol, id_acc_tol, created_at_tol)`
- `idx_created_at_tol` on `created_at_tol`
- `idx_rental_fee_tol` on `rental_fee_tol`
- `idx_is_deposit_required_tol` on `is_deposit_required_tol`
- `fulltext_tool_search_tol` (FULLTEXT) on `(tool_name_tol, tool_description_tol)`

> **Note:**
>
> - `is_available_tol` = owner intent only.
> - True availability requires: `is_available_tol = true` AND no overlapping `availability_block_avb` AND no active `borrow_bor`.
> - Compute at query time (JOIN/NOT EXISTS) for accuracy.
> - Trigger: BEFORE INSERT/UPDATE – reject if `id_acc_tol` references deleted account.
>
> **Legal/Liability Fields:**
>
> - `is_deposit_required_tol`: Lender can require refundable security deposit
> - `preexisting_conditions_tol`: Required disclosure of tool condition before lending (ToS requirement)
> - `is_insurance_recommended_tol`: Flags high-value tools ($1000+) for insurance recommendation
>
> **Derived aggregates** (compute on-demand; cache later if needed):
>
> - Avg rating: `SELECT AVG(score_trt), COUNT(*) FROM tool_rating_trt WHERE id_tol_trt = ?`
> - Borrow count: `SELECT COUNT(*) FROM borrow_bor WHERE id_tol_bor = ? AND id_bst_bor = <returned_id>`

---

#### tool_meta_tlm

Optional tool metadata in key/value rows (strict 1NF/3NF). Replaces JSON column for custom attributes and tags.

| Column           | Type         | Constraints        | Notes          |
|------------------|--------------|--------------------|----------------|
| `id_tlm`         | int          | PK, auto-increment | -              |
| `id_tol_tlm`     | int          | not null           | FK to tool_tol |
| `meta_key_tlm`   | varchar(100) | not null           | -              |
| `meta_value_tlm` | varchar(255) | not null           | -              |
| `created_at_tlm` | timestamp    | default: now()     | -              |

**Indexes:**

- `uq_tool_meta_tlm` (UNIQUE) on `(id_tol_tlm, meta_key_tlm)`
- `idx_meta_key_tlm` on `meta_key_tlm`

---

#### tool_image_tim

Images for tools. One tool can have multiple images with display ordering.

| Column            | Type         | Constraints        | Notes                     |
|-------------------|--------------|--------------------|---------------------------|
| `id_tim`          | int          | PK, auto-increment | -                         |
| `id_tol_tim`      | int          | not null           | FK to tool_tol            |
| `file_name_tim`   | varchar(255) | not null           | -                         |
| `alt_text_tim`    | varchar(255) | -                  | -                         |
| `is_primary_tim`  | boolean      | default: false     | -                                                      |
| `sort_order_tim`  | int          | default: 0         | Display order for gallery                              |
| `uploaded_at_tim` | timestamp    | default: now()     | -                                                      |
| `primary_flag_tim`| tinyint      | generated (stored) | `IF(is_primary_tim, 1, NULL)` — for unique enforcement |

**Indexes:**

- `idx_tool_primary_tim` on `(id_tol_tim, is_primary_tim)`
- `idx_tool_sort_tim` on `(id_tol_tim, sort_order_tim)`
- `uq_one_primary_per_tool_tim` (UNIQUE) on `(id_tol_tim, primary_flag_tim)`

> **Note:**
>
> - Single-primary constraint enforced via generated column + composite unique index.

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

#### loan_extension_lex

Tracks loan extensions with full audit trail.

| Column                   | Type      | Constraints        | Notes                                       |
|--------------------------|-----------|--------------------|---------------------------------------------|
| `id_lex`                 | int       | PK, auto-increment | -                                           |
| `id_bor_lex`             | int       | not null           | FK to borrow_bor                            |
| `original_due_at_lex`    | timestamp | not null           | Snapshot of due_at_bor before extension     |
| `extended_hours_lex`     | int       | not null           | Additional hours granted                    |
| `new_due_at_lex`         | timestamp | not null           | New due date after extension                |
| `reason_lex`             | text      | -                  | Reason for extension                        |
| `id_acc_approved_by_lex` | int       | not null           | Lender or admin who approved the extension  |
| `created_at_lex`         | timestamp | default: now()     | -                                           |

**Indexes:**

- `idx_borrow_lex` on `id_bor_lex`
- `idx_approver_lex` on `id_acc_approved_by_lex`

> **Note:**
>
> - `CHECK (extended_hours_lex > 0)`

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
| `id_ntf`           | int          | PK, auto-increment | -                             |
| `id_acc_ntf`       | int          | not null           | FK to account_acc             |
| `id_ntt_ntf`       | int          | not null           | FK to notification_type_ntt   |
| `title_ntf`        | varchar(255) | not null           | Notification title            |
| `body_ntf`         | text         | -                  | Notification body text        |
| `id_bor_ntf`       | int          | -                  | FK to borrow_bor (optional)   |
| `is_read_ntf`      | boolean      | default: false     | -                             |
| `read_at_ntf`      | timestamp    | -                  | When notification was read    |
| `created_at_ntf`   | timestamp    | default: now()     | -                             |

**Indexes:**

- `idx_unread_timeline_type_ntf` on `(id_acc_ntf, is_read_ntf, created_at_ntf, id_ntt_ntf)` - covering index for notification feed
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

**Indexes:**

- `idx_date_neighborhood_evt` on `(start_at_evt, id_nbh_evt)`
- `idx_creator_evt` on `id_acc_evt`
- `idx_updated_by_evt` on `id_acc_updated_by_evt`
- `idx_neighborhood_evt` on `id_nbh_evt`

---

#### event_meta_evm

Optional event metadata in key/value rows (strict 1NF/3NF). Replaces JSON column for tags, RSVPs, etc.

| Column           | Type         | Constraints        | Notes            |
|------------------|--------------|--------------------|------------------|
| `id_evm`         | int          | PK, auto-increment | -                |
| `id_evt_evm`     | int          | not null           | FK to event_evt  |
| `meta_key_evm`   | varchar(100) | not null           | -                |
| `meta_value_evm` | varchar(255) | not null           | -                |
| `created_at_evm` | timestamp    | default: now()     | -                |

**Indexes:**

- `uq_event_meta_evm` (UNIQUE) on `(id_evt_evm, meta_key_evm)`
- `idx_meta_key_evm` on `meta_key_evm`

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

Audit log for tracking changes across all tables. Implement when detailed change history is needed.

| Column                | Type        | Constraints        | Notes                                         |
|-----------------------|-------------|--------------------|-----------------------------------------------|
| `id_aud`              | int         | PK, auto-increment | -                                             |
| `table_name_aud`      | varchar(64) | not null           | Name of the table that was modified           |
| `row_id_aud`          | int         | not null           | PK of the modified row                        |
| `action_aud`          | varchar(10) | not null           | INSERT, UPDATE, DELETE                        |
| `id_acc_aud`          | int         | -                  | Account who made the change; NULL if system   |
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

#### audit_log_detail_ald

Normalized audit detail rows (strict 1NF/3NF). Stores per-column change details for audit log entries.

| Column            | Type        | Constraints        | Notes                |
|-------------------|-------------|--------------------|----------------------|
| `id_ald`          | int         | PK, auto-increment | -                    |
| `id_aud_ald`      | int         | not null           | FK to audit_log_aud  |
| `column_name_ald` | varchar(64) | not null           | -                    |
| `old_value_ald`   | text        | -                  | Previous value       |
| `new_value_ald`   | text        | -                  | New value            |
| `created_at_ald`  | timestamp   | default: now()     | -                    |

**Indexes:**

- `idx_audit_detail_column_ald` on `column_name_ald`

---

### Legal & Compliance

#### terms_of_service_tos

Stores versioned Terms of Service documents. Emphasizes platform's matchmaking role.

| Column                  | Type         | Constraints        | Notes                                         |
|-------------------------|--------------|--------------------|-----------------------------------------------|
| `id_tos`                | int          | PK, auto-increment | -                                             |
| `version_tos`           | varchar(20)  | unique, not null   | Version identifier (e.g., 1.0, 2.0)           |
| `title_tos`             | varchar(255) | not null           | ToS document title                            |
| `content_tos`           | text         | not null           | Full Terms of Service text                    |
| `summary_tos`           | text         | -                  | Plain-language summary of key terms           |
| `effective_at_tos`      | timestamp    | not null           | When this version becomes active              |
| `superseded_at_tos`     | timestamp    | -                  | When replaced; NULL = current                 |
| `is_active_tos`         | boolean      | default: true      | Only one version should be active             |
| `id_acc_created_by_tos` | int          | not null           | Admin who created this version                |
| `created_at_tos`        | timestamp    | default: now()     | -                                             |

**Indexes:**

- `idx_active_tos` on `is_active_tos`
- `idx_effective_tos` on `effective_at_tos`
- `idx_creator_tos` on `id_acc_created_by_tos`

> **Note:**
>
> Key ToS clauses for platform liability protection:
> - Platform is a matchmaking service, not party to transactions
> - Platform not liable for damage, theft, or loss of tools
> - Users resolve disputes directly (e.g., small claims court)
> - Borrowers assume responsibility during borrow period
> - Lenders must disclose pre-existing conditions
> - Mandatory incident reporting within 24-48 hours
> - Users should verify their own insurance coverage

---

#### tos_acceptance_tac

Records user acceptance of each Terms of Service version.

| Column           | Type         | Constraints        | Notes                                    |
|------------------|--------------|--------------------|------------------------------------------|
| `id_tac`         | int          | PK, auto-increment | -                                        |
| `id_acc_tac`     | int          | not null           | FK to account_acc                        |
| `id_tos_tac`     | int          | not null           | FK to terms_of_service_tos               |
| `ip_address_tac` | varchar(45)  | -                  | IP address at time of acceptance         |
| `user_agent_tac` | varchar(512) | -                  | Browser/device info for audit trail      |
| `accepted_at_tac`| timestamp    | not null           | When user accepted                       |

**Indexes:**

- `uq_account_tos_tac` (UNIQUE) on `(id_acc_tac, id_tos_tac)`
- `idx_tos_version_tac` on `id_tos_tac`
- `idx_accepted_at_tac` on `accepted_at_tac`

> **Note:**
>
> - Required during registration and when new ToS versions are published.
> - One acceptance record per user per ToS version.

---

#### borrow_waiver_bwv

Digital waiver required for each borrow transaction.

| Column                           | Type         | Constraints        | Notes                                           |
|----------------------------------|--------------|--------------------|------------------------------------------------|
| `id_bwv`                         | int          | PK, auto-increment | -                                               |
| `id_bor_bwv`                     | int          | unique, not null   | One waiver per borrow; FK to borrow_bor         |
| `id_wtp_bwv`                     | int          | not null           | FK to waiver_type_wtp                           |
| `id_acc_bwv`                     | int          | not null           | Borrower who signed the waiver                  |
| `is_tool_condition_acknowledged_bwv`| boolean   | not null           | Borrower confirms current tool condition        |
| `preexisting_conditions_noted_bwv`| text        | -                  | Snapshot of tool conditions at waiver time      |
| `is_responsibility_accepted_bwv` | boolean      | not null           | Borrower accepts responsibility for tool        |
| `is_liability_waiver_accepted_bwv`| boolean     | not null           | Borrower acknowledges platform liability limits |
| `is_insurance_reminder_shown_bwv`| boolean      | default: false     | Insurance recommendation was displayed          |
| `ip_address_bwv`                 | varchar(45)  | -                  | -                                               |
| `user_agent_bwv`                 | varchar(512) | -                  | -                                               |
| `signed_at_bwv`                  | timestamp    | not null           | -                                               |

**Indexes:**

- `idx_borrower_bwv` on `id_acc_bwv`
- `idx_signed_at_bwv` on `signed_at_bwv`
- `idx_waiver_type_bwv` on `id_wtp_bwv`

> **Note:**
>
> - All acknowledgment booleans must be true for waiver to be valid.
> - Trigger enforces required acknowledgments on INSERT.

---

#### handover_verification_hov

Digital handshake system for tool pickup and return confirmation.

| Column                 | Type        | Constraints        | Notes                                              |
|------------------------|-------------|--------------------|---------------------------------------------------|
| `id_hov`               | int         | PK, auto-increment | -                                                  |
| `id_bor_hov`           | int         | not null           | FK to borrow_bor                                   |
| `id_hot_hov`           | int         | not null           | FK to handover_type_hot (pickup or return)         |
| `verification_code_hov`| varchar(8)  | not null           | Unique 6-8 character code for digital handshake    |
| `id_acc_generator_hov` | int         | not null           | Account that generated the code                    |
| `id_acc_verifier_hov`  | int         | -                  | Account that verified; NULL until verified         |
| `condition_notes_hov`  | text        | -                  | Condition notes at handover                        |
| `generated_at_hov`     | timestamp   | not null           | -                                                  |
| `expires_at_hov`       | timestamp   | not null           | Code expires after 24 hours                        |
| `verified_at_hov`      | timestamp   | -                  | When verification completed; NULL = pending        |

**Indexes:**

- `uq_borrow_handover_type_hov` (UNIQUE) on `(id_bor_hov, id_hot_hov)`
- `uq_verification_code_hov` (UNIQUE) on `verification_code_hov`
- `idx_generator_hov` on `id_acc_generator_hov`
- `idx_verifier_hov` on `id_acc_verifier_hov`
- `idx_expires_at_hov` on `expires_at_hov`

> **Note:**
>
> Similar to ShareHub's "digital handshake" system:
>
> - **Pickup:** Lender generates code, borrower verifies at pickup
> - **Return:** Borrower generates code, lender verifies at return
> - Code expires after 24 hours; new code required if expired
> - Trigger auto-generates unique verification code on INSERT

---

#### incident_report_irt

Mandatory incident reporting for damage, theft, loss, or disputes.

| Column                       | Type         | Constraints        | Notes                                           |
|------------------------------|--------------|--------------------|------------------------------------------------|
| `id_irt`                     | int          | PK, auto-increment | -                                               |
| `id_bor_irt`                 | int          | not null           | FK to borrow_bor                                |
| `id_acc_irt`                 | int          | not null           | Account reporting the incident                  |
| `id_ity_irt`                 | int          | not null           | FK to incident_type_ity                         |
| `subject_irt`                | varchar(255) | not null           | -                                               |
| `description_irt`            | text         | not null           | -                                               |
| `incident_occurred_at_irt`   | timestamp    | not null           | When the incident occurred                      |
| `is_reported_within_deadline_irt`| boolean  | default: true      | True if reported within 48 hours                |
| `estimated_damage_amount_irt`| decimal(8,2) | -                  | Estimated cost of damage/loss                   |
| `resolution_notes_irt`       | text         | -                  | Admin resolution notes                          |
| `resolved_at_irt`            | timestamp    | -                  | When incident was resolved                      |
| `id_acc_resolved_by_irt`     | int          | -                  | Admin who resolved                              |
| `created_at_irt`             | timestamp    | default: now()     | -                                               |
| `updated_at_irt`             | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                     |

**Indexes:**

- `idx_borrow_irt` on `id_bor_irt`
- `idx_reporter_irt` on `id_acc_irt`
- `idx_incident_type_irt` on `id_ity_irt`
- `idx_deadline_compliance_irt` on `is_reported_within_deadline_irt`
- `idx_created_at_irt` on `created_at_irt`

> **Note:**
>
> - ToS requires incident reporting within 24-48 hours.
> - `is_reported_within_deadline_irt` auto-calculated by trigger: `(created_at - incident_occurred_at <= 48 hours)`

---

#### incident_photo_iph

Photos attached to incident reports, normalized into separate rows (strict 1NF/3NF).

| Column            | Type         | Constraints        | Notes                       |
|-------------------|--------------|--------------------|-----------------------------|
| `id_iph`          | int          | PK, auto-increment | -                           |
| `id_irt_iph`      | int          | not null           | FK to incident_report_irt   |
| `file_name_iph`   | varchar(255) | not null           | -                           |
| `caption_iph`     | varchar(255) | -                  | -                           |
| `sort_order_iph`  | int          | default: 0         | -                           |
| `created_at_iph`  | timestamp    | default: now()     | -                           |

**Indexes:**

- `idx_incident_photo_order_iph` on `(id_irt_iph, sort_order_iph)`

---

### Payments & Deposits

#### security_deposit_sdp

Refundable security deposit tracking for borrow transactions.

| Column                   | Type         | Constraints        | Notes                                          |
|--------------------------|--------------|--------------------|------------------------------------------------|
| `id_sdp`                 | int          | PK, auto-increment | -                                              |
| `id_bor_sdp`             | int          | unique, not null   | One deposit per borrow; FK to borrow_bor       |
| `id_dps_sdp`             | int          | not null           | FK to deposit_status_dps                       |
| `amount_sdp`             | decimal(8,2) | not null           | Deposit amount in USD                          |
| `id_ppv_sdp`             | int          | not null           | FK to payment_provider_ppv                     |
| `external_payment_id_sdp`| varchar(255) | -                  | Stripe PaymentIntent ID or similar             |
| `held_at_sdp`            | timestamp    | -                  | When deposit was captured/held                 |
| `released_at_sdp`        | timestamp    | -                  | When deposit was released back to borrower     |
| `forfeited_at_sdp`       | timestamp    | -                  | When deposit was forfeited to lender           |
| `forfeited_amount_sdp`   | decimal(8,2) | -                  | Amount forfeited (may be partial)              |
| `forfeiture_reason_sdp`  | text         | -                  | Reason for forfeiture                          |
| `id_irt_sdp`             | int          | -                  | FK to incident_report_irt if forfeited         |
| `created_at_sdp`         | timestamp    | default: now()     | -                                              |
| `updated_at_sdp`         | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                    |

**Indexes:**

- `idx_status_sdp` on `id_dps_sdp`
- `idx_provider_sdp` on `id_ppv_sdp`
- `idx_external_id_sdp` on `external_payment_id_sdp`
- `idx_held_at_sdp` on `held_at_sdp`

> **Note:**
>
> **Deposit Workflow:**
>
> 1. Deposit created with status = pending when borrow approved
> 2. Funds captured via Stripe, status = held (escrow)
> 3. On successful return (verified via `handover_verification_hov`): status = released
> 4. On incident: status = forfeited (full or partial), funds transferred to lender

---

#### payment_transaction_ptx

Detailed transaction log for all payment activities.

| Column                      | Type         | Constraints        | Notes                                         |
|-----------------------------|--------------|--------------------|-----------------------------------------------|
| `id_ptx`                    | int          | PK, auto-increment | -                                             |
| `id_sdp_ptx`                | int          | -                  | FK to security_deposit_sdp; NULL for fees     |
| `id_bor_ptx`                | int          | not null           | FK to borrow_bor                              |
| `id_ppv_ptx`                | int          | not null           | FK to payment_provider_ppv                    |
| `transaction_type_ptx`      | varchar(30)  | not null           | deposit_hold, deposit_release, deposit_forfeit, rental_fee |
| `amount_ptx`                | decimal(8,2) | not null           | -                                             |
| `external_transaction_id_ptx`| varchar(255)| not null           | Stripe Charge/Transfer ID                     |
| `external_status_ptx`       | varchar(50)  | -                  | Status from payment provider                  |
| `id_acc_from_ptx`           | int          | -                  | Payer account (borrower)                      |
| `id_acc_to_ptx`             | int          | -                  | Payee account; NULL for platform escrow       |
| `processed_at_ptx`          | timestamp    | default: now()     | -                                             |

**Indexes:**

- `idx_deposit_ptx` on `id_sdp_ptx`
- `idx_borrow_ptx` on `id_bor_ptx`
- `idx_external_txn_ptx` on `external_transaction_id_ptx`
- `idx_txn_type_ptx` on `transaction_type_ptx`
- `idx_processed_at_ptx` on `processed_at_ptx`

> **Note:**
>
> - Tracks Stripe integration for deposits and rental fees.
> - Future expansion: Integrate insurance provider APIs (Lemonade, etc.) for quotes.

---

#### payment_transaction_meta_ptm

Optional transaction metadata in key/value rows (strict 1NF/3NF). Replaces JSON column for additional payment provider details.

| Column           | Type         | Constraints        | Notes                         |
|------------------|--------------|--------------------|-------------------------------|
| `id_ptm`         | int          | PK, auto-increment | -                             |
| `id_ptx_ptm`     | int          | not null           | FK to payment_transaction_ptx |
| `meta_key_ptm`   | varchar(100) | not null           | -                             |
| `meta_value_ptm` | varchar(255) | not null           | -                             |
| `created_at_ptm` | timestamp    | default: now()     | -                             |

**Indexes:**

- `uq_ptx_meta_ptm` (UNIQUE) on `(id_ptx_ptm, meta_key_ptm)`
- `idx_meta_key_ptm` on `meta_key_ptm`

---

### Materialized Summary Tables

Pre-computed summary tables refreshed periodically by stored procedures and scheduled events. Provides fast reads for dashboards and reporting without expensive JOINs at query time.

#### neighborhood_summary_mat

Materialized view of neighborhood statistics.

| Column                  | Type              | Constraints        | Notes                                  |
|-------------------------|-------------------|--------------------|----------------------------------------|
| `id_nbh`                | int unsigned      | PK                 | Matches neighborhood_nbh.id_nbh       |
| `neighborhood_name_nbh` | varchar(100)     | not null           | -                                      |
| `city_name_nbh`         | varchar(100)     | not null           | -                                      |
| `state_code_sta`        | char(2)          | not null           | -                                      |
| `state_name_sta`        | varchar(50)      | not null           | -                                      |
| `latitude_nbh`          | decimal(10,8)    | -                  | -                                      |
| `longitude_nbh`         | decimal(11,8)    | -                  | -                                      |
| `location_point_nbh`    | point            | -                  | SRID 4326                              |
| `created_at_nbh`        | timestamp        | -                  | -                                      |
| `total_members`          | int unsigned     | default: 0         | -                                      |
| `active_members`         | int unsigned     | default: 0         | -                                      |
| `verified_members`       | int unsigned     | default: 0         | -                                      |
| `total_tools`            | int unsigned     | default: 0         | -                                      |
| `available_tools`        | int unsigned     | default: 0         | -                                      |
| `active_borrows`         | int unsigned     | default: 0         | -                                      |
| `completed_borrows_30d`  | int unsigned     | default: 0         | Completed borrows in last 30 days      |
| `upcoming_events`        | int unsigned     | default: 0         | -                                      |
| `zip_codes`              | text             | -                  | Comma-separated associated ZIP codes   |
| `refreshed_at`           | timestamp        | default: now()     | Last refresh timestamp                 |

**Indexes:**

- `idx_state_mat` on `state_code_sta`
- `idx_city_mat` on `city_name_nbh`
- `idx_refreshed_mat` on `refreshed_at`

---

#### user_reputation_mat

Materialized view of user reputation scores.

| Column                | Type          | Constraints        | Notes                      |
|-----------------------|---------------|--------------------|----------------------------|
| `id_acc`              | int unsigned  | PK                 | Matches account_acc.id_acc |
| `full_name`           | varchar(101)  | not null           | -                          |
| `email_address_acc`   | varchar(255)  | not null           | -                          |
| `account_status`      | varchar(30)   | not null           | -                          |
| `member_since`        | timestamp     | -                  | -                          |
| `lender_avg_rating`   | decimal(3,1)  | default: 0         | -                          |
| `lender_rating_count` | int unsigned  | default: 0         | -                          |
| `borrower_avg_rating` | decimal(3,1)  | default: 0         | -                          |
| `borrower_rating_count`| int unsigned | default: 0         | -                          |
| `overall_avg_rating`  | decimal(3,1)  | -                  | -                          |
| `total_rating_count`  | int unsigned  | default: 0         | -                          |
| `tools_owned`         | int unsigned  | default: 0         | -                          |
| `completed_borrows`   | int unsigned  | default: 0         | -                          |
| `refreshed_at`        | timestamp     | default: now()     | Last refresh timestamp     |

**Indexes:**

- `idx_lender_rating_mat` on `lender_avg_rating` (DESC)
- `idx_borrower_rating_mat` on `borrower_avg_rating` (DESC)
- `idx_overall_rating_mat` on `overall_avg_rating` (DESC)
- `idx_refreshed_mat` on `refreshed_at`

---

#### tool_statistics_mat

Materialized view of tool statistics.

| Column              | Type          | Constraints        | Notes                  |
|---------------------|---------------|--------------------|------------------------|
| `id_tol`            | int unsigned  | PK                 | Matches tool_tol.id_tol|
| `tool_name_tol`     | varchar(100)  | not null           | -                      |
| `owner_id`          | int unsigned  | not null           | -                      |
| `owner_name`        | varchar(101)  | not null           | -                      |
| `tool_condition`    | varchar(30)   | not null           | -                      |
| `rental_fee_tol`    | decimal(10,2) | -                  | -                      |
| `estimated_value_tol`| decimal(10,2)| -                  | -                      |
| `created_at_tol`    | timestamp     | -                  | -                      |
| `avg_rating`        | decimal(3,1)  | default: 0         | -                      |
| `rating_count`      | int unsigned  | default: 0         | -                      |
| `five_star_count`   | int unsigned  | default: 0         | -                      |
| `total_borrows`     | int unsigned  | default: 0         | -                      |
| `completed_borrows` | int unsigned  | default: 0         | -                      |
| `cancelled_borrows` | int unsigned  | default: 0         | -                      |
| `denied_borrows`    | int unsigned  | default: 0         | -                      |
| `total_hours_borrowed`| int unsigned| default: 0         | -                      |
| `last_borrowed_at`  | timestamp     | -                  | -                      |
| `incident_count`    | int unsigned  | default: 0         | -                      |
| `refreshed_at`      | timestamp     | default: now()     | Last refresh timestamp |

**Indexes:**

- `idx_owner_mat` on `owner_id`
- `idx_avg_rating_mat` on `avg_rating` (DESC)
- `idx_total_borrows_mat` on `total_borrows` (DESC)
- `idx_refreshed_mat` on `refreshed_at`

---

#### category_summary_mat

Materialized view of category statistics.

| Column                  | Type          | Constraints        | Notes                  |
|-------------------------|---------------|--------------------|------------------------|
| `id_cat`                | int unsigned  | PK                 | Matches category_cat   |
| `category_name_cat`     | varchar(100)  | not null           | -                      |
| `category_icon`         | varchar(255)  | -                  | -                      |
| `total_tools`           | int unsigned  | default: 0         | -                      |
| `listed_tools`          | int unsigned  | default: 0         | -                      |
| `available_tools`       | int unsigned  | default: 0         | -                      |
| `category_avg_rating`   | decimal(3,1)  | -                  | -                      |
| `total_completed_borrows`| int unsigned | default: 0         | -                      |
| `min_rental_fee`        | decimal(10,2) | -                  | -                      |
| `max_rental_fee`        | decimal(10,2) | -                  | -                      |
| `avg_rental_fee`        | decimal(10,2) | -                  | -                      |
| `refreshed_at`          | timestamp     | default: now()     | Last refresh timestamp |

**Indexes:**

- `idx_total_tools_mat` on `total_tools` (DESC)
- `idx_available_mat` on `available_tools` (DESC)
- `idx_refreshed_mat` on `refreshed_at`

---

#### platform_daily_stat_pds

Daily platform-wide statistics for admin dashboard and reporting.

| Column                   | Type          | Constraints    | Notes                             |
|--------------------------|---------------|----------------|-----------------------------------|
| `stat_date_pds`          | date          | PK             | -                                 |
| `total_accounts_pds`     | int unsigned  | default: 0     | -                                 |
| `active_accounts_pds`    | int unsigned  | default: 0     | -                                 |
| `new_accounts_today_pds` | int unsigned  | default: 0     | -                                 |
| `total_tools_pds`        | int unsigned  | default: 0     | -                                 |
| `available_tools_pds`    | int unsigned  | default: 0     | -                                 |
| `new_tools_today_pds`    | int unsigned  | default: 0     | -                                 |
| `active_borrows_pds`     | int unsigned  | default: 0     | -                                 |
| `completed_today_pds`    | int unsigned  | default: 0     | -                                 |
| `new_requests_today_pds` | int unsigned  | default: 0     | -                                 |
| `open_disputes_pds`      | int unsigned  | default: 0     | -                                 |
| `open_incidents_pds`     | int unsigned  | default: 0     | -                                 |
| `overdue_borrows_pds`    | int unsigned  | default: 0     | -                                 |
| `deposits_held_total_pds`| decimal(12,2) | default: 0     | Total deposits currently in escrow|
| `refreshed_at_pds`       | timestamp     | default: now() | Last refresh timestamp            |

**Indexes:**

- `idx_stat_month_pds` on `stat_date_pds`

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
| `account_acc`            | `account_meta_acm`  | `id_acc_acm`       | Account has optional metadata     |
| `account_acc`            | `vector_image_vec`  | `id_acc_vec`       | Admin uploads vector images       |
| `vector_image_vec`       | `category_cat`      | `id_vec_cat`       | Category has optional icon        |

#### Neighborhood Domain

| Parent (One)       | Child (Many)             | Foreign Key       | Description                        |
|--------------------|--------------------------|-------------------|------------------------------------|
| `state_sta`        | `neighborhood_nbh`       | `id_sta_nbh`      | State for neighborhood             |
| `neighborhood_nbh` | `neighborhood_meta_nbm`  | `id_nbh_nbm`      | Neighborhood has optional metadata |
| `neighborhood_nbh` | `neighborhood_zip_nbhzpc`| `id_nbh_nbhzpc`   | Neighborhood contains ZIP codes    |
| `zip_code_zpc`     | `neighborhood_zip_nbhzpc`| `zip_code_nbhzpc` | ZIP code belongs to neighborhoods  |

#### Tool Domain

| Parent (One)         | Child (Many)      | Foreign Key    | Description                    |
|----------------------|-------------------|----------------|--------------------------------|
| `tool_condition_tcd` | `tool_tol`        | `id_tcd_tol`   | Condition of tools             |
| `account_acc`        | `tool_tol`        | `id_acc_tol`   | Account owns tools             |
| `tool_tol`           | `tool_meta_tlm`   | `id_tol_tlm`   | Tool has optional metadata     |
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
| `borrow_bor`        | `loan_extension_lex`    | `id_bor_lex`   | Borrow has loan extensions        |
| `account_acc`       | `loan_extension_lex`    | `id_acc_approved_by_lex` | Approver of extension  |

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
| `event_evt`        | `event_meta_evm` | `id_evt_evm`        | Event has optional metadata |

#### phpBB Integration Domain

| Parent (One)  | Child (Many)           | Foreign Key  | Description              |
|---------------|------------------------|--------------|--------------------------|
| `account_acc` | `phpbb_integration_php`| `id_acc_php` | Account links to phpBB   |

#### Audit Log Domain

| Parent (One)  | Child (Many)    | Foreign Key  | Description                  |
|---------------|-----------------|--------------|------------------------------|
| `account_acc` | `audit_log_aud` | `id_acc_aud` | Account makes audited change |
| `audit_log_aud` | `audit_log_detail_ald` | `id_aud_ald` | Audit entry has detail rows |

#### Legal & Compliance Domain

| Parent (One)             | Child (Many)              | Foreign Key             | Description                        |
|--------------------------|---------------------------|-------------------------|------------------------------------|
| `account_acc`            | `terms_of_service_tos`    | `id_acc_created_by_tos` | Admin creates ToS versions         |
| `account_acc`            | `tos_acceptance_tac`      | `id_acc_tac`            | Account accepts ToS                |
| `terms_of_service_tos`   | `tos_acceptance_tac`      | `id_tos_tac`            | ToS version accepted               |
| `borrow_bor`             | `borrow_waiver_bwv`       | `id_bor_bwv`            | Borrow has waiver (1:1)            |
| `waiver_type_wtp`        | `borrow_waiver_bwv`       | `id_wtp_bwv`            | Waiver type                        |
| `account_acc`            | `borrow_waiver_bwv`       | `id_acc_bwv`            | Borrower signs waiver              |
| `borrow_bor`             | `handover_verification_hov`| `id_bor_hov`           | Borrow has handover verifications  |
| `handover_type_hot`      | `handover_verification_hov`| `id_hot_hov`           | Handover type (pickup/return)      |
| `account_acc`            | `handover_verification_hov`| `id_acc_generator_hov` | Account generates verification     |
| `account_acc`            | `handover_verification_hov`| `id_acc_verifier_hov`  | Account verifies handover          |
| `borrow_bor`             | `incident_report_irt`     | `id_bor_irt`            | Borrow has incidents               |
| `account_acc`            | `incident_report_irt`     | `id_acc_irt`            | Account reports incident           |
| `incident_type_ity`      | `incident_report_irt`     | `id_ity_irt`            | Incident type                      |
| `account_acc`            | `incident_report_irt`     | `id_acc_resolved_by_irt`| Admin resolves incident            |
| `incident_report_irt`    | `incident_photo_iph`      | `id_irt_iph`            | Incident has photos                |

#### Payments & Deposits Domain

| Parent (One)             | Child (Many)              | Foreign Key             | Description                        |
|--------------------------|---------------------------|-------------------------|------------------------------------|
| `borrow_bor`             | `security_deposit_sdp`    | `id_bor_sdp`            | Borrow has deposit (1:1)           |
| `deposit_status_dps`     | `security_deposit_sdp`    | `id_dps_sdp`            | Deposit status                     |
| `payment_provider_ppv`   | `security_deposit_sdp`    | `id_ppv_sdp`            | Payment provider for deposit       |
| `incident_report_irt`    | `security_deposit_sdp`    | `id_irt_sdp`            | Incident causing forfeiture        |
| `security_deposit_sdp`   | `payment_transaction_ptx` | `id_sdp_ptx`            | Deposit has transactions           |
| `borrow_bor`             | `payment_transaction_ptx` | `id_bor_ptx`            | Borrow has payment transactions    |
| `payment_provider_ppv`   | `payment_transaction_ptx` | `id_ppv_ptx`            | Payment provider for transaction   |
| `account_acc`            | `payment_transaction_ptx` | `id_acc_from_ptx`       | Payer account                      |
| `account_acc`            | `payment_transaction_ptx` | `id_acc_to_ptx`         | Payee account                      |
| `payment_transaction_ptx`| `payment_transaction_meta_ptm` | `id_ptx_ptm`      | Transaction has optional metadata  |

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

+------------------------------------------------------------------------------+
|                        LEGAL & COMPLIANCE GROUP                              |
+------------------------------------------------------------------------------+
|                                                                              |
|  +---------------------+       +-------------------+                         |
|  | terms_of_service_tos|       |  waiver_type_wtp  |                         |
|  +---------+-----------+       +---------+---------+                         |
|            |                             |                                   |
|            v                             v                                   |
|  +-------------------+         +-------------------+                         |
|  | tos_acceptance_tac|         |  borrow_waiver_bwv|---> borrow_bor          |
|  +-------------------+         +-------------------+                         |
|                                                                              |
|  +------------------+          +-------------------+                         |
|  | handover_type_hot|          |  incident_type_ity|                         |
|  +--------+---------+          +---------+---------+                         |
|           |                              |                                   |
|           v                              v                                   |
|  +--------------------------+  +--------------------+                        |
|  | handover_verification_hov|  | incident_report_irt|                        |
|  +--------------------------+  +--------------------+                        |
|            |                             |                                   |
|            +-------------+---------------+                                   |
|                          |                                                   |
|                          +---> borrow_bor                                    |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                        PAYMENTS & DEPOSITS GROUP                             |
+------------------------------------------------------------------------------+
|                                                                              |
|  +-------------------+         +---------------------+                       |
|  | deposit_status_dps|         | payment_provider_ppv|                       |
|  +---------+---------+         +----------+----------+                       |
|            |                              |                                  |
|            +-------------+----------------+                                  |
|                          |                                                   |
|                          v                                                   |
|              +-----------------------+                                       |
|              |  security_deposit_sdp |---> borrow_bor                        |
|              +----------+------------+                                       |
|                         |                                                    |
|                         v                                                    |
|             +-------------------------+                                      |
|             | payment_transaction_ptx |---> borrow_bor                       |
|             +-------------------------+                                      |
|                                                                              |
+------------------------------------------------------------------------------+
```

---

## Procedural Layer

### Views

Summary views provide pre-joined, filtered, or aggregated data for common queries.

#### Status / Availability Views

| View | Purpose |
|------|---------|
| `active_account_v` | All accounts excluding those with "deleted" status |
| `available_tool_v` | Tools truly available for borrowing — listed, owner not deleted, no active borrow or availability block |
| `active_borrow_v` | Currently checked-out items in "borrowed" status with borrower/lender details and computed due-status (OVERDUE / DUE SOON / ON TIME) |
| `overdue_borrow_v` | Past-due borrowed items with hours/days overdue, borrower contact info, and linked deposit data |
| `pending_request_v` | Borrow requests awaiting owner approval, including borrower reputation scores and hours pending |

#### Profile / Detail Views

| View | Purpose |
|------|---------|
| `account_profile_v` | Complete member profile joining account, role, status, contact preference, neighborhood, ZIP coordinates, bio, image, tool count, and lender/borrower ratings |
| `tool_detail_v` | Full tool listing with condition, owner info, primary image, avg rating, borrow count, categories, and computed availability status (AVAILABLE / BORROWED / BLOCKED / UNLISTED) |

#### Analytics / Reporting Views

| View | Purpose |
|------|---------|
| `user_reputation_v` | Aggregated user reputation with separate lender and borrower average ratings, total ratings, tools owned, and completed borrows |
| `tool_statistics_v` | Per-tool analytics: avg rating, five-star count, total/completed/cancelled/denied borrows, total hours borrowed, last borrow date, and incident count |
| `neighborhood_summary_v` | Community-level statistics: member counts (total/active/verified), tool counts, active/recent borrows, upcoming events, and associated ZIP codes |
| `category_summary_v` | Per-category tool counts, available tools, avg rating, completed borrows, and min/max/avg rental fee |

#### Admin Views

| View | Purpose |
|------|---------|
| `open_dispute_v` | Unresolved disputes with reporter, borrower, lender details, message count, related incidents, and deposit info |
| `pending_deposit_v` | Security deposits currently held in escrow with computed action-required status |

#### Legal and Compliance Views

| View | Purpose |
|------|---------|
| `current_tos_v` | Currently active Terms of Service version with creator name and total acceptance count |
| `tos_acceptance_required_v` | Active users who have not yet accepted the current ToS, with their last accepted version and date |
| `pending_waiver_v` | Approved borrows that are missing a signed waiver, acting as a compliance gate before pickup |
| `open_incident_v` | Unresolved incident reports with incident type, estimated damage, reporter/borrower/lender details, related disputes, and deposit info |
| `pending_handover_v` | Handover verification codes generated but not yet confirmed, with computed code status (ACTIVE / EXPIRING SOON / EXPIRED) |

#### User Interaction Views

| View | Purpose |
|------|---------|
| `unread_notification_v` | Unread notifications per user with notification type, related tool/borrow info, and hours since creation |
| `user_bookmarks_v` | User's saved/bookmarked tools with current availability status and tool ratings |

#### Future Expansion Views

| View | Purpose |
|------|---------|
| `upcoming_event_v` | Community events starting in the future or currently happening, with computed timing label (HAPPENING NOW / THIS WEEK / THIS MONTH / UPCOMING) |

#### Materialized Summary Fast Views

Thin views that read from the materialized summary tables for fast dashboard queries.

| View | Source Table | Purpose |
|------|-------------|---------|
| `neighborhood_summary_fast_v` | `neighborhood_summary_mat` | Fast alternative to `neighborhood_summary_v` |
| `user_reputation_fast_v` | `user_reputation_mat` | Fast alternative to `user_reputation_v` |
| `tool_statistics_fast_v` | `tool_statistics_mat` | Fast alternative to `tool_statistics_v` |
| `category_summary_fast_v` | `category_summary_mat` | Fast alternative to `category_summary_v` |

---

### Triggers

Triggers enforce business rules and data integrity at the database level.

#### Neighborhood ZIP Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_nbhzpc_before_insert` | `neighborhood_zip_nbhzpc` | BEFORE INSERT | Enforce only one primary neighborhood per ZIP code |
| `trg_nbhzpc_before_update` | `neighborhood_zip_nbhzpc` | BEFORE UPDATE | Enforce only one primary neighborhood per ZIP code (excluding self) |

#### Account Status Automation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_account_before_update` | `account_acc` | BEFORE UPDATE | Auto-set `deleted_at_acc` when status changes to "deleted"; clear it when un-deleted |

#### Tool Ownership Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_tool_before_insert` | `tool_tol` | BEFORE INSERT | Reject tool creation if the owner account is deleted |
| `trg_tool_before_update` | `tool_tol` | BEFORE UPDATE | Reject tool ownership transfer to a deleted account |

#### Bookmark Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_bookmark_before_insert` | `tool_bookmark_acctol` | BEFORE INSERT | Reject bookmark creation by a deleted account |

#### Borrow Lifecycle Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_borrow_before_insert` | `borrow_bor` | BEFORE INSERT | Validate borrower/owner not deleted, prevent self-borrow, enforce timestamp ordering, auto-populate timestamps based on status |
| `trg_borrow_before_update` | `borrow_bor` | BEFORE UPDATE | Enforce strict status-transition rules, require timestamps for each state, protect due date from unauthorized changes |

#### Availability Block Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_availability_block_before_insert` | `availability_block_avb` | BEFORE INSERT | Require `id_bor_avb` for "borrow" blocks; forbid it for "admin" blocks |
| `trg_availability_block_before_update` | `availability_block_avb` | BEFORE UPDATE | Same borrow/admin block-type validation on update |

#### Rating Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_user_rating_before_insert` | `user_rating_urt` | BEFORE INSERT | Prevent self-rating, require both rater and target to be borrow participants, reject deleted accounts |
| `trg_user_rating_before_update` | `user_rating_urt` | BEFORE UPDATE | Prevent self-rating (defense in depth) |
| `trg_tool_rating_before_insert` | `tool_rating_trt` | BEFORE INSERT | Only the borrower can rate the tool, tool must match borrow, reject deleted rater |

#### Dispute Validation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_dispute_before_insert` | `dispute_dsp` | BEFORE INSERT | Reject disputes from non-participants or deleted accounts |
| `trg_dispute_message_before_insert` | `dispute_message_dsm` | BEFORE INSERT | Reject dispute messages from deleted accounts |

#### Waiver and Handover Automation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_borrow_waiver_before_insert` | `borrow_waiver_bwv` | BEFORE INSERT | Require all three waiver acknowledgments (tool condition, responsibility, liability) |
| `trg_handover_verification_before_insert` | `handover_verification_hov` | BEFORE INSERT | Auto-generate a 6-character uppercase verification code and set 24-hour expiry |

#### Incident Automation

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_incident_report_before_insert` | `incident_report_irt` | BEFORE INSERT | Auto-calculate `is_reported_within_deadline_irt` based on 48-hour window |

#### Terms of Service

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_tos_before_insert` | `terms_of_service_tos` | BEFORE INSERT | Auto-set `created_at_tos` to NOW() if not provided |

#### Lookup Table Protection

These triggers prevent deletion or renaming of system-required lookup values that other triggers and procedures depend on.

| Trigger | Table | Event | Purpose |
|---------|-------|-------|---------|
| `trg_account_status_before_delete` | `account_status_ast` | BEFORE DELETE | Block deletion of required statuses |
| `trg_account_status_before_update` | `account_status_ast` | BEFORE UPDATE | Block renaming of required statuses |
| `trg_borrow_status_before_delete` | `borrow_status_bst` | BEFORE DELETE | Block deletion of required statuses |
| `trg_borrow_status_before_update` | `borrow_status_bst` | BEFORE UPDATE | Block renaming of required statuses |
| `trg_block_type_before_delete` | `block_type_btp` | BEFORE DELETE | Block deletion of required types |
| `trg_block_type_before_update` | `block_type_btp` | BEFORE UPDATE | Block renaming of required types |
| `trg_rating_role_before_delete` | `rating_role_rtr` | BEFORE DELETE | Block deletion of required roles |
| `trg_rating_role_before_update` | `rating_role_rtr` | BEFORE UPDATE | Block renaming of required roles |
| `trg_handover_type_before_delete` | `handover_type_hot` | BEFORE DELETE | Block deletion of required types |
| `trg_handover_type_before_update` | `handover_type_hot` | BEFORE UPDATE | Block renaming of required types |
| `trg_deposit_status_before_delete` | `deposit_status_dps` | BEFORE DELETE | Block deletion of required statuses |
| `trg_deposit_status_before_update` | `deposit_status_dps` | BEFORE UPDATE | Block renaming of required statuses |

---

### Stored Procedures

#### Legal / ToS Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_create_tos_version` | `p_version, p_title, p_content, p_summary, p_effective_at, p_created_by` | Atomically deactivate the current ToS and insert a new active version |

#### Loan Extension Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_extend_loan` | `p_bor_id, p_extra_hours, p_reason, p_approved_by` | Extend a borrow's due date atomically via `loan_extension_lex` audit record |

#### Materialized Summary Refresh Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_refresh_neighborhood_summary` | *(none)* | Truncate and repopulate `neighborhood_summary_mat` |
| `sp_refresh_user_reputation` | *(none)* | Truncate and repopulate `user_reputation_mat` |
| `sp_refresh_tool_statistics` | *(none)* | Truncate and repopulate `tool_statistics_mat` |
| `sp_refresh_category_summary` | *(none)* | Truncate and repopulate `category_summary_mat` |
| `sp_refresh_platform_daily_stat` | *(none)* | Insert or replace today's row in `platform_daily_stat_pds` |
| `sp_refresh_all_summaries` | *(none)* | Master procedure that calls all five refresh procedures in sequence |

#### Borrow Workflow Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_create_borrow_request` | `p_tool_id, p_borrower_id, p_loan_duration_hours, p_notes` | Create a new borrow request with full validation |
| `sp_approve_borrow_request` | `p_borrow_id, p_approver_id` | Approve a pending request; verifies approver is the tool owner |
| `sp_deny_borrow_request` | `p_borrow_id, p_denier_id, p_reason` | Deny a pending request; appends denial reason to notes |
| `sp_complete_pickup` | `p_borrow_id` | Transition approved borrow to "borrowed"; sets due_at and creates availability block |
| `sp_complete_return` | `p_borrow_id` | Transition to "returned"; sets returned_at and removes availability block |
| `sp_cancel_borrow_request` | `p_borrow_id, p_canceller_id, p_reason` | Cancel a "requested" or "approved" borrow |

#### Rating Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_rate_user` | `p_borrow_id, p_rater_id, p_target_id, p_role, p_score, p_review_text` | Rate a user after a completed borrow |
| `sp_rate_tool` | `p_borrow_id, p_rater_id, p_score, p_review_text` | Rate a tool after a completed borrow |

#### Notification Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_send_notification` | `p_account_id, p_notification_type, p_title, p_body, p_related_borrow_id` | Create a typed notification for a user |
| `sp_mark_notifications_read` | `p_account_id, p_notification_ids` | Batch-mark notifications as read |

#### Maintenance / Batch Processing Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_send_overdue_notifications` | *(none)* | Send one "overdue" notification per day per borrower (avoids duplicates) |
| `sp_cleanup_expired_handover_codes` | *(none)* | Delete expired, unverified handover verification codes |
| `sp_archive_old_notifications` | `p_days_old` | Delete read notifications older than given days (min 30, default 90) |
| `sp_cleanup_old_search_logs` | `p_days_old` | Delete search log entries older than given days (min 7, default 30) |

#### Deposit Management Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_release_deposit_on_return` | `p_borrow_id` | Release a held security deposit on successful return |

#### Search and Reporting Procedures

| Procedure | Parameters (IN) | Purpose |
|-----------|-----------------|---------|
| `sp_search_available_tools` | `p_search_term, p_zip_code, p_category_id, p_max_rental_fee, p_limit, p_offset` | Full-text search for available tools with optional filters; paginated |
| `sp_get_user_borrow_history` | `p_account_id, p_role, p_status, p_limit, p_offset` | Paginated borrow history filterable by role and status |

---

### Helper Functions

Lookup helper functions cache IDs to avoid repeated subqueries in triggers and application code.

| Function | Parameters | Returns | Purpose |
|----------|-----------|---------|---------|
| `fn_get_account_status_id` | `p_status_name VARCHAR(30)` | `INT` | Return PK for a given account status name |
| `fn_get_borrow_status_id` | `p_status_name VARCHAR(30)` | `INT` | Return PK for a given borrow status name |
| `fn_get_block_type_id` | `p_type_name VARCHAR(30)` | `INT` | Return PK for a given block type name |
| `fn_get_rating_role_id` | `p_role_name VARCHAR(30)` | `INT` | Return PK for a given rating role name |
| `fn_get_notification_type_id` | `p_type_name VARCHAR(30)` | `INT` | Return PK for a given notification type name |
| `fn_get_deposit_status_id` | `p_status_name VARCHAR(30)` | `INT` | Return PK for a given deposit status name |
| `fn_get_dispute_status_id` | `p_status_name VARCHAR(30)` | `INT` | Return PK for a given dispute status name |
| `fn_get_handover_type_id` | `p_type_name VARCHAR(30)` | `INT` | Return PK for a given handover type name |
| `fn_is_tool_available` | `p_tool_id INT` | `BOOLEAN` | Return TRUE if tool is listed, has no active borrow, and no current availability block |

---

### Scheduled Events

All events require `SET GLOBAL event_scheduler = ON` to run automatically.

| Event | Schedule | Calls | Purpose |
|-------|----------|-------|---------|
| `evt_refresh_summaries_hourly` | Every 1 hour | `sp_refresh_neighborhood_summary()`, `sp_refresh_category_summary()` | Keep neighborhood and category dashboard data fresh |
| `evt_refresh_user_reputation_every_4h` | Every 4 hours | `sp_refresh_user_reputation()` | Refresh user reputation scores periodically |
| `evt_refresh_tool_statistics_every_2h` | Every 2 hours | `sp_refresh_tool_statistics()` | Refresh tool statistics for trending and recommendations |
| `evt_daily_stat_midnight` | Every 1 day at midnight | `sp_refresh_platform_daily_stat()` | Capture daily platform KPI snapshot |
| `evt_send_overdue_notifications` | Every 1 day at 8:00 AM | `sp_send_overdue_notifications()` | Send daily overdue-tool reminders |
| `evt_cleanup_expired_handovers` | Every 1 hour | `sp_cleanup_expired_handover_codes()` | Remove expired, unverified handover codes |
| `evt_archive_old_notifications` | Every 1 week (Sunday 2:00 AM) | `sp_archive_old_notifications(90)` | Delete read notifications older than 90 days |
| `evt_cleanup_search_logs` | Every 1 week (Sunday 3:00 AM) | `sp_cleanup_old_search_logs(30)` | Delete search log entries older than 30 days |

---

### Naming Conventions

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

### Development Tools Used

Tools used in development of the database design

- **VS Code** Code Editor
- **Grok** Analytics from X.com
- **Codex** Document Analysis
- **Claude Code** Document Refinement
- **[dbdiagram.io](https://dbdiagram.io/d/neighborhoodtools-com-ERD-69711419bd82f5fce231c284)** ERD Creation
