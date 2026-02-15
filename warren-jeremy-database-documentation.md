# [NeighborhoodTools.com](https://neighborhoodtools.com) Complete Documentation

**Author:** Jeremy Warren

**Course:** WEB-289 Capstone Project

**Target Database:** MySQL 8.0.16 or later

**Source File:** `warren-jeremy-dump-phase3.sql` - Updated February 15, 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Database Configuration](#database-configuration)
3. [Database Object Summary](#database-object-summary)
4. [Table Groups](#table-groups)
5. [Lookup Tables](#lookup-tables)
6. [Core Tables](#core-tables)
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
   - [Materialized Cache & Analytics](#materialized-cache--analytics)
7. [Relationships](#relationships)
8. [Entity Relationship Diagram](#entity-relationship-diagram)
9. [Triggers](#triggers)
   - [Business Logic Triggers](#business-logic-triggers)
   - [Lookup Table Protection Triggers](#lookup-table-protection-triggers)
10. [Views](#views)
    - [Status & Availability Views](#status--availability-views)
    - [Profile & Detail Views](#profile--detail-views)
    - [Reputation & Statistics Views](#reputation--statistics-views)
    - [Neighborhood & Dispute Views](#neighborhood--dispute-views)
    - [Financial & Legal Views](#financial--legal-views)
    - [Operational Views](#operational-views)
    - [Aggregate & Reporting Views](#aggregate--reporting-views)
    - [Fast Materialized Views](#fast-materialized-views)
11. [Stored Procedures](#stored-procedures)
    - [ToS & Loan Extension Procedures](#111-tos--loan-extension-procedures)
    - [Materialized View Refresh Procedures](#112-materialized-view-refresh-procedures)
    - [Borrow Workflow Procedures](#113-borrow-workflow-procedures)
    - [Rating Procedures](#114-rating-procedures)
    - [Notification Procedures](#115-notification-procedures)
    - [Maintenance Procedures](#116-maintenance-procedures)
    - [Deposit Procedures](#117-deposit-procedures)
    - [Search & Query Procedures](#118-search--query-procedures)
12. [Stored Functions](#stored-functions)
    - [Lookup ID Functions](#121-lookup-id-functions-8-functions)
    - [Business Logic Functions](#122-business-logic-functions)
13. [Scheduled Events](#scheduled-events)
    - [Materialized View Refresh Events](#131-materialized-view-refresh-events)
    - [Notification Events](#132-notification-events)
    - [Maintenance Events](#133-maintenance-events)
14. [Seed Data Reference](#seed-data-reference)
    - [Required Lookup Data](#141-required-lookup-data)
    - [Geographic Data](#142-geographic-data)
    - [Sample Accounts](#143-sample-accounts)
    - [Sample Tools & Categories](#144-sample-tools--categories)
    - [Sample Transactions](#145-sample-transactions)
    - [Post-Seed Initialization](#146-post-seed-initialization)
15. [Naming Conventions](#naming-conventions)
16. [Development Tools Used](#development-tools-used)

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

## Database Configuration

| Setting | Value |
|---------|-------|
| **Database Name** | `neighborhoodtools` |
| **Character Set** | `utf8mb4` |
| **Collation** | `utf8mb4_0900_ai_ci` |
| **SQL Mode** | `NO_AUTO_VALUE_ON_ZERO, STRICT_TRANS_TABLES` |
| **Time Zone** | `+00:00` (UTC) |
| **Spatial SRID** | 4326 (WGS84) |
| **Minimum MySQL Version** | 8.0.16 |

> **Note:**
>
> - All timestamps are stored in UTC. Time zone conversion belongs in the application layer.
> - Spatial columns use the `POINT` type with SRID 4326 for WGS84 coordinate system.
> - `STRICT_TRANS_TABLES` ensures invalid data is rejected rather than silently truncated.

---

## Database Object Summary

| Object Type | Count |
|-------------|-------|
| Tables | 60 |
| Triggers | 31 |
| Views | 25 |
| Stored Procedures | 25 |
| Stored Functions | 9 |
| Scheduled Events | 8 |
| Foreign Keys | 83 |
| Indexes | ~200 |

---

## Table Groups

The database is organized into logical groups for easier management and
visualization:

| Group                            | Tables                                                                                                                                                                                                                                |
|----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Accounts**                     | `role_rol`, `account_status_ast`, `contact_preference_cpr`, `state_sta`, `neighborhood_nbh`, `neighborhood_meta_nbm`, `zip_code_zpc`, `account_acc`, `account_meta_acm`, `account_image_aim`, `account_bio_abi`, `password_reset_pwr` |
| **Tools**                        | `category_cat`, `tool_condition_tcd`, `tool_tol`, `tool_image_tim`, `tool_meta_tlm`                                                                                                                                                   |
| **Borrowing**                    | `borrow_status_bst`, `block_type_btp`, `borrow_bor`, `availability_block_avb`, `loan_extension_lex`                                                                                                                                   |
| **Ratings & Disputes**           | `rating_role_rtr`, `user_rating_urt`, `tool_rating_trt`, `dispute_dsp`, `dispute_status_dst`, `dispute_message_type_dmt`, `dispute_message_dsm`                                                                                       |
| **User Interactions**            | `notification_ntf`, `notification_type_ntt`, `search_log_slg`                                                                                                                                                                         |
| **Shared Assets**                | `vector_image_vec`                                                                                                                                                                                                                    |
| **Junction Tables**              | `tool_category_tolcat`, `tool_bookmark_acctol`, `neighborhood_zip_nbhzpc`                                                                                                                                                             |
| **Future Expansion**             | `event_evt`, `event_meta_evm`, `phpbb_integration_php`, `audit_log_aud`, `audit_log_detail_ald`                                                                                                                                       |
| **Legal & Compliance**           | `terms_of_service_tos`, `tos_acceptance_tac`, `waiver_type_wtp`, `borrow_waiver_bwv`, `handover_type_hot`, `handover_verification_hov`, `incident_type_ity`, `incident_report_irt`, `incident_photo_iph`                              |
| **Payments & Deposits**          | `deposit_status_dps`, `security_deposit_sdp`, `payment_provider_ppv`, `payment_transaction_ptx`, `payment_transaction_meta_ptm`                                                                                                       |
| **Materialized Cache & Analytics** | `neighborhood_summary_mat`, `user_reputation_mat`, `tool_statistics_mat`, `category_summary_mat`, `platform_daily_stat_pds`                                                                                                         |

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

| Column              | Type        | Constraints             | Notes                           |
|---------------------|-------------|-------------------------|---------------------------------|
| `id_ppv`            | int         | PK, auto-increment      | -                               |
| `provider_name_ppv` | varchar(50) | unique, not null        | Values: stripe, paypal, manual  |
| `is_active_ppv`     | boolean     | not null, default: true | Whether provider is available   |

---

## Core Tables

### Accounts

#### neighborhood_nbh

Dedicated neighborhood entity for local communities/service areas.

| Column                  | Type         | Constraints        | Notes                                                              |
|-------------------------|--------------|--------------------|--------------------------------------------------------------------|
| `id_nbh`                | int          | PK, auto-increment | -                                                                  |
| `neighborhood_name_nbh` | varchar(100) | unique, not null   | Name of local community/service area                               |
| `city_name_nbh`         | varchar(100) | -                  | Primary city for this neighborhood                                 |
| `id_sta_nbh`            | int          | not null           | State the neighborhood is primarily in (FK to state_sta)           |
| `latitude_nbh`          | decimal(9,6) | not null           | -                                                                  |
| `longitude_nbh`         | decimal(9,6) | not null           | -                                                                  |
| `location_point_nbh`    | point        | not null           | MySQL 8 POINT type with SRID 4326 (WGS84) for optimized proximity  |
| `created_at_nbh`        | timestamp    | default: now()     | -                                                                  |
| `updated_at_nbh`        | timestamp    | default: now()     | ON UPDATE CURRENT_TIMESTAMP                                        |

**Indexes:**

- `idx_state_nbh` on `id_sta_nbh`
- `idx_city_nbh` on `city_name_nbh`
- `idx_location_nbh` (SPATIAL) on `location_point_nbh`

> **Note:**
>
> - Spatial trigger: BEFORE INSERT/UPDATE – auto-populate `location_point_nbh` from lat/long:
>   `SET NEW.location_point_nbh = ST_PointFromText(CONCAT('POINT(', NEW.longitude_nbh, ' ', NEW.latitude_nbh, ')'), 4326)`
> - Extended attributes stored in `neighborhood_meta_nbm` (EAV pattern) — replaces former JSON column.

---

#### neighborhood_meta_nbm

Optional neighborhood metadata stored as key/value rows (EAV pattern, strict 1NF/3NF).

| Column             | Type         | Constraints        | Notes                    |
|--------------------|--------------|--------------------|--------------------------|
| `id_nbm`           | int          | PK, auto-increment | -                        |
| `id_nbh_nbm`       | int          | not null           | FK to neighborhood_nbh   |
| `meta_key_nbm`     | varchar(100) | not null           | -                        |
| `meta_value_nbm`   | varchar(255) | not null           | -                        |
| `created_at_nbm`   | timestamp    | default: now()     | -                        |

**Indexes:**

- `uq_neighborhood_meta_nbm` (UNIQUE) on `(id_nbh_nbm, meta_key_nbm)`
- `idx_meta_key_nbm` on `meta_key_nbm`

> **Note:**
>
> - FK cascades on delete.

---

#### zip_code_zpc

ZIP code table – pure geographic identifiers only.

| Column               | Type         | Constraints | Notes                                 |
|----------------------|--------------|-------------|---------------------------------------|
| `zip_code_zpc`       | varchar(10)  | PK          | -                                     |
| `latitude_zpc`       | decimal(9,6) | not null    | -                                     |
| `longitude_zpc`      | decimal(9,6) | not null    | -                                     |
| `location_point_zpc` | point        | not null    | MySQL 8 POINT with SRID 4326 (WGS84)  |

**Indexes:**

- `idx_location_zpc` (SPATIAL) on `location_point_zpc`

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

| Column                      | Type         | Constraints              | Notes                                                                  |
|-----------------------------|--------------|--------------------------|------------------------------------------------------------------------|
| `id_acc`                    | int          | PK, auto-increment       | -                                                                      |
| `first_name_acc`            | varchar(100) | not null                 | -                                                                      |
| `last_name_acc`             | varchar(100) | not null                 | -                                                                      |
| `username_acc`              | varchar(30)  | unique, not null         | Public display name — 3-30 chars, alphanumeric + underscores           |
| `phone_number_acc`          | varchar(20)  | -                        | -                                                                      |
| `email_address_acc`         | varchar(255) | unique, not null         | Primary login credential - used for authentication                     |
| `street_address_acc`        | varchar(255) | -                        | Optional for privacy - ZIP required                                    |
| `zip_code_acc`              | varchar(10)  | not null                 | FK to zip_code_zpc                                                     |
| `id_nbh_acc`                | int          | -                        | Optional neighborhood membership; state derivable via neighborhood_nbh |
| `password_hash_acc`         | varchar(255) | not null                 | bcrypt or argon2 hash only                                             |
| `id_rol_acc`                | int          | not null                 | FK to role_rol                                                         |
| `id_ast_acc`                | int          | not null                 | FK to account_status_ast                                               |
| `id_cpr_acc`                | int          | not null                 | FK to contact_preference_cpr                                           |
| `is_verified_acc`           | boolean      | not null, default: false | -                                                                      |
| `has_consent_acc`           | boolean      | not null, default: false | -                                                                      |
| `last_login_at_acc`         | timestamp    | -                        | -                                                                      |
| `created_at_acc`            | timestamp    | default: now()           | -                                                                      |
| `updated_at_acc`            | timestamp    | default: now()           | ON UPDATE CURRENT_TIMESTAMP                                            |
| `deleted_at_acc`            | timestamp    | -                        | Set via trigger when status changes to deleted; NULL = active          |

**Indexes:**

- `idx_email_acc` on `email_address_acc`
- `idx_username_acc` (UNIQUE) on `username_acc`
- `idx_zip_acc` on `zip_code_acc`
- `idx_role_acc` on `id_rol_acc`
- `idx_status_verified_acc` on `(id_ast_acc, is_verified_acc)`
- `idx_status_neighborhood_verified_acc` on `(id_ast_acc, id_nbh_acc, is_verified_acc)`
- `idx_contact_preference_acc` on `id_cpr_acc`
- `idx_neighborhood_acc` on `id_nbh_acc`
- `idx_last_login_acc` on `last_login_at_acc`
- `idx_created_at_acc` on `created_at_acc`

**SQL Constraints:**

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
>
> **Index notes:**
>
> - `idx_status_neighborhood_verified_acc` covers neighborhood-scoped queries for active, verified users (e.g., dashboard member lists).
> - Extended attributes stored in `account_meta_acm` (EAV pattern) — replaces former JSON column.

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

Optional account metadata stored as key/value rows (EAV pattern, strict 1NF/3NF).

| Column             | Type         | Constraints        | Notes             |
|--------------------|--------------|--------------------|--------------------|
| `id_acm`           | int          | PK, auto-increment | -                 |
| `id_acc_acm`       | int          | not null           | FK to account_acc |
| `meta_key_acm`     | varchar(100) | not null           | -                 |
| `meta_value_acm`   | varchar(255) | not null           | -                 |
| `created_at_acm`   | timestamp    | default: now()     | -                 |

**Indexes:**

- `uq_account_meta_acm` (UNIQUE) on `(id_acc_acm, meta_key_acm)`
- `idx_meta_key_acm` on `meta_key_acm`

> **Note:**
>
> - FK cascades on delete.

---

#### account_image_aim

Profile images for user accounts. One account can have multiple images.

| Column              | Type         | Constraints              | Notes                                                    |
|---------------------|--------------|--------------------------|----------------------------------------------------------|
| `id_aim`            | int          | PK, auto-increment       | -                                                        |
| `id_acc_aim`        | int          | not null                 | FK to account_acc                                        |
| `file_name_aim`     | varchar(255) | not null                 | -                                                        |
| `alt_text_aim`      | varchar(255) | -                        | -                                                        |
| `is_primary_aim`    | boolean      | not null, default: false | -                                                        |
| `primary_flag_aim`  | tinyint      | -                        | GENERATED ALWAYS AS (IF(is_primary_aim, 1, NULL)) STORED |
| `uploaded_at_aim`   | timestamp    | default: now()           | -                                                        |

**Indexes:**

- `idx_account_primary_aim` on `(id_acc_aim, is_primary_aim)`
- `uq_one_primary_per_account_aim` (UNIQUE) on `(id_acc_aim, primary_flag_aim)`

> **Note:**
>
> - **Single-primary constraint:** `primary_flag_aim` is `GENERATED ALWAYS AS (IF(is_primary_aim, 1, NULL)) STORED`. The unique index `uq_one_primary_per_account_aim` on `(id_acc_aim, primary_flag_aim)` permits multiple NULLs but only one `1` per account — enforcing at most one primary image at the database level.

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

#### password_reset_pwr

Password reset tokens tied to user accounts. Tokens are hashed, have an
expiration, and track whether they have been used.

| Column             | Type        | Constraints        | Notes                                                |
|--------------------|-------------|--------------------|------------------------------------------------------|
| `id_pwr`           | int         | PK, auto-increment | -                                                    |
| `id_acc_pwr`       | int         | not null           | FK to account_acc                                    |
| `token_hash_pwr`   | varchar(64) | not null           | Hashed reset token                                   |
| `expires_at_pwr`   | timestamp   | not null           | Token expiry time                                    |
| `created_at_pwr`   | timestamp   | default: now()     | -                                                    |
| `used_at_pwr`      | timestamp   | -                  | When token was used; NULL = unused                   |

**Indexes:**

- `idx_token_hash_pwr` on `token_hash_pwr`
- `idx_acc_expires_pwr` on `(id_acc_pwr, expires_at_pwr)`

> **Note:**
>
> - FK cascades on delete (account deletion removes tokens).

---

### Tools

#### tool_tol

Main tool listing table.

| Column                            | Type          | Constraints              | Notes                                                                   |
|-----------------------------------|---------------|--------------------------|-------------------------------------------------------------------------|
| `id_tol`                          | int           | PK, auto-increment       | -                                                                       |
| `tool_name_tol`                   | varchar(255)  | not null                 | -                                                                       |
| `tool_description_tol`            | text          | -                        | -                                                                       |
| `id_tcd_tol`                      | int           | not null                 | FK to tool_condition_tcd                                                |
| `id_acc_tol`                      | int           | not null                 | Owner account FK                                                        |
| `serial_number_tol`               | varchar(50)   | -                        | -                                                                       |
| `rental_fee_tol`                  | decimal(6,2)  | default: 0.00            | 0 = free sharing                                                        |
| `default_loan_duration_hours_tol` | int           | default: 168             | Owner default in hours; UI converts days/weeks                          |
| `is_available_tol`                | boolean       | not null, default: true  | Owner listing toggle - see Note for true availability logic             |
| `is_deposit_required_tol`         | boolean       | not null, default: false | Lender requires refundable security deposit                             |
| `default_deposit_amount_tol`      | decimal(8,2)  | default: 0.00            | Default deposit amount; 0 = no deposit required                         |
| `estimated_value_tol`             | decimal(8,2)  | -                        | Estimated tool value for insurance/deposit reference                    |
| `preexisting_conditions_tol`      | text          | -                        | Lender disclosure of pre-existing damage, wear, conditions - ToS req.   |
| `is_insurance_recommended_tol`    | boolean       | not null, default: false | Flag for high-value tools ($1000+) where insurance is recommended       |
| `created_at_tol`                  | timestamp     | default: now()           | -                                                                       |
| `updated_at_tol`                  | timestamp     | default: now()           | ON UPDATE CURRENT_TIMESTAMP                                             |

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
>
> **Index notes:**
>
> - `idx_available_owner_created_tol` is a covering index for listing pages filtered by availability and owner with newest-first sort.
>
> Extended attributes stored in `tool_meta_tlm` (EAV pattern) — replaces former JSON column.

---

#### tool_image_tim

Images for tools. One tool can have multiple images with display ordering.

| Column              | Type         | Constraints              | Notes                                                    |
|---------------------|--------------|--------------------------|----------------------------------------------------------|
| `id_tim`            | int          | PK, auto-increment       | -                                                        |
| `id_tol_tim`        | int          | not null                 | FK to tool_tol                                           |
| `file_name_tim`     | varchar(255) | not null                 | -                                                        |
| `alt_text_tim`      | varchar(255) | -                        | -                                                        |
| `is_primary_tim`    | boolean      | not null, default: false | -                                                        |
| `primary_flag_tim`  | tinyint      | -                        | GENERATED ALWAYS AS (IF(is_primary_tim, 1, NULL)) STORED |
| `sort_order_tim`    | int          | default: 0               | Display order for gallery                                |
| `uploaded_at_tim`   | timestamp    | default: now()           | -                                                        |

**Indexes:**

- `idx_tool_primary_tim` on `(id_tol_tim, is_primary_tim)`
- `uq_one_primary_per_tool_tim` (UNIQUE) on `(id_tol_tim, primary_flag_tim)`
- `idx_tool_sort_tim` on `(id_tol_tim, sort_order_tim)`

> **Note:**
>
> - **Single-primary constraint:** `primary_flag_tim` is `GENERATED ALWAYS AS (IF(is_primary_tim, 1, NULL)) STORED`. The unique index `uq_one_primary_per_tool_tim` on `(id_tol_tim, primary_flag_tim)` permits multiple NULLs but only one `1` per tool — enforcing at most one primary image at the database level.

---

#### tool_meta_tlm

Optional tool metadata stored as key/value rows (EAV pattern, strict 1NF/3NF).

| Column             | Type         | Constraints        | Notes           |
|--------------------|--------------|--------------------|-----------------|
| `id_tlm`           | int          | PK, auto-increment | -               |
| `id_tol_tlm`       | int          | not null           | FK to tool_tol  |
| `meta_key_tlm`     | varchar(100) | not null           | -               |
| `meta_value_tlm`   | varchar(255) | not null           | -               |
| `created_at_tlm`   | timestamp    | default: now()     | -               |

**Indexes:**

- `uq_tool_meta_tlm` (UNIQUE) on `(id_tol_tlm, meta_key_tlm)`
- `idx_meta_key_tlm` on `meta_key_tlm`

> **Note:**
>
> - FK cascades on delete.

---

### Borrowing

#### borrow_bor

Tracks tool borrow requests and their lifecycle.

| Column                    | Type      | Constraints              | Notes                                 |
|---------------------------|-----------|--------------------------|---------------------------------------|
| `id_bor`                  | int       | PK, auto-increment       | -                                     |
| `id_tol_bor`              | int       | not null                 | FK to tool_tol                        |
| `id_acc_bor`              | int       | not null                 | Borrower account FK                   |
| `id_bst_bor`              | int       | not null                 | FK to borrow_status_bst               |
| `loan_duration_hours_bor` | int       | not null                 | Agreed period in hours; UI converts   |
| `requested_at_bor`        | timestamp | default: now()           | -                                     |
| `approved_at_bor`         | timestamp | -                        | -                                     |
| `borrowed_at_bor`         | timestamp | -                        | -                                     |
| `due_at_bor`              | timestamp | -                        | Set via trigger on status -> borrowed |
| `returned_at_bor`         | timestamp | -                        | -                                     |
| `cancelled_at_bor`        | timestamp | -                        | -                                     |
| `notes_text_bor`          | text      | -                        | -                                     |
| `is_contact_shared_bor`   | boolean   | not null, default: false | -                                     |
| `created_at_bor`          | timestamp | default: now()           | -                                     |

**Indexes:**

- `idx_status_due_tool_bor` on `(id_bst_bor, due_at_bor, id_tol_bor)`
- `idx_tool_status_bor` on `(id_tol_bor, id_bst_bor)`
- `idx_tool_borrower_bor` on `(id_tol_bor, id_acc_bor)`
- `idx_borrower_bor` on `id_acc_bor`
- `idx_borrower_status_bor` on `(id_acc_bor, id_bst_bor, requested_at_bor)`
- `idx_returned_bor` on `returned_at_bor`
- `idx_requested_at_bor` on `requested_at_bor`

> **Note:**
>
> - CHECK constraints required for timestamp order & mutual exclusivity (returned vs cancelled).
> - Trigger: validate status-timestamp consistency + set `due_at_bor` when status changes to borrowed.
> - Prevent `due_at_bor` modification once set.
> - Trigger: prevent borrowing own tool (tool_tol.id_acc_tol != borrow_bor.id_acc_bor).
>
> **Index notes:**
>
> - `idx_status_due_tool_bor` covers overdue-tracking queries (status + due date + tool).
> - `idx_borrower_status_bor` supports "my borrows" queries filtered by status with newest-first sort.

---

#### availability_block_avb

Manages tool availability, supporting both admin manual blocks and automatic
borrow unavailability.

| Column           | Type      | Constraints        | Notes                                             |
|------------------|-----------|--------------------|---------------------------------------------------|
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

- `idx_tool_range_type_avb` on `(id_tol_avb, start_at_avb, end_at_avb, id_btp_avb)`
- `uq_borrow_avb` (UNIQUE) on `id_bor_avb`
- `idx_block_type_avb` on `id_btp_avb`

> **Note:**
>
> - `CHECK (end_at_avb > start_at_avb)`.
> - Trigger: validate id_bor_avb presence based on block type (borrow -> required, admin -> NULL).
> - 1-to-1 with borrow for borrow-type blocks; UPDATE existing block on extensions.
> - **Overlap Prevention Trigger:** BEFORE INSERT/UPDATE prevents overlapping blocks for the same tool using `NEW.start_at_avb < end_at_avb AND NEW.end_at_avb > start_at_avb` check.
> - MySQL lacks PostgreSQL EXCLUDE constraints; trigger-based enforcement required.
> - `idx_tool_range_type_avb` includes `id_btp_avb` to support type-filtered range queries without table lookups.

---

#### loan_extension_lex

Tracks loan extensions with full audit trail. Multiple extensions per borrow are allowed.

| Column                   | Type      | Constraints        | Notes                                         |
|--------------------------|-----------|--------------------|-----------------------------------------------|
| `id_lex`                 | int       | PK, auto-increment | -                                             |
| `id_bor_lex`             | int       | not null           | FK to borrow_bor                              |
| `original_due_at_lex`    | timestamp | not null           | Snapshot of due_at_bor before extension       |
| `extended_hours_lex`     | int       | not null           | Additional hours granted                      |
| `new_due_at_lex`         | timestamp | not null           | New due date after extension                  |
| `reason_lex`             | text      | -                  | Reason for extension                          |
| `id_acc_approved_by_lex` | int       | not null           | Lender or admin who approved the extension    |
| `created_at_lex`         | timestamp | default: now()     | -                                             |

**Indexes:**

- `idx_borrow_lex` on `id_bor_lex`
- `idx_approver_lex` on `id_acc_approved_by_lex`

**SQL Constraints:**

```sql
CHECK (extended_hours_lex > 0)
```

> **Note:**
>
> - FK to borrow_bor ON DELETE RESTRICT; FK to account_acc ON DELETE RESTRICT.

---

### Ratings & Disputes

#### user_rating_urt

Ratings between users (lender rating borrower or vice versa).

| Column              | Type      | Constraints        | Notes                                       |
|---------------------|-----------|--------------------|---------------------------------------------|
| `id_urt`            | int       | PK, auto-increment | -                                           |
| `id_acc_urt`        | int       | not null           | Rater account FK                            |
| `id_acc_target_urt` | int       | not null           | Ratee account FK                            |
| `id_bor_urt`        | int       | not null           | FK to borrow_bor                            |
| `id_rtr_urt`        | int       | not null           | FK to rating_role_rtr (lender or borrower)  |
| `score_urt`         | int       | not null           | 1-5 scale                                   |
| `comment_text_urt`  | text      | -                  | -                                           |
| `created_at_urt`    | timestamp | default: now()     | -                                           |

**Indexes:**

- `idx_target_role_score_urt` on `(id_acc_target_urt, id_rtr_urt, score_urt)`
- `uq_one_user_rating_per_borrow_urt` (UNIQUE) on `(id_bor_urt, id_acc_urt, id_rtr_urt)`
- `idx_rater_urt` on `id_acc_urt`

> **Note:**
>
> - `CHECK (score_urt BETWEEN 1 AND 5)`.
> - Trigger: BEFORE INSERT – prevent self-rating (`id_acc_urt != id_acc_target_urt`). Uses a trigger instead of CHECK because CHECK constraints are incompatible with foreign key actions in MySQL 8.
> - `idx_target_role_score_urt` includes `score_urt` as a covering column for aggregate rating queries (AVG/COUNT) without table lookups.

---

#### tool_rating_trt

Ratings for tools after borrowing.

| Column             | Type      | Constraints        | Notes            |
|--------------------|-----------|--------------------|------------------|
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

| Column             | Type      | Constraints              | Notes                          |
|--------------------|-----------|--------------------------|--------------------------------|
| `id_dsm`           | int       | PK, auto-increment       | -                              |
| `id_dsp_dsm`       | int       | not null                 | FK to dispute_dsp              |
| `id_acc_dsm`       | int       | not null                 | Author account FK              |
| `id_dmt_dsm`       | int       | not null                 | FK to dispute_message_type_dmt |
| `message_text_dsm` | text      | not null                 | -                              |
| `is_internal_dsm`  | boolean   | not null, default: false | Admin-only if true             |
| `created_at_dsm`   | timestamp | default: now()           | -                              |

**Indexes:**

- `idx_dispute_timeline_dsm` on `(id_dsp_dsm, created_at_dsm)`
- `idx_author_dsm` on `id_acc_dsm`
- `idx_message_type_dsm` on `id_dmt_dsm`

---

### User Interactions

#### notification_ntf

System notifications sent to users.

| Column           | Type         | Constraints              | Notes                                     |
|------------------|--------------|--------------------------|-------------------------------------------|
| `id_ntf`         | int          | PK, auto-increment       | -                                         |
| `id_acc_ntf`     | int          | not null                 | FK to account_acc                         |
| `id_ntt_ntf`     | int          | not null                 | FK to notification_type_ntt               |
| `title_ntf`      | varchar(255) | not null                 | -                                         |
| `body_ntf`       | text         | -                        | -                                         |
| `id_bor_ntf`     | int          | -                        | FK to borrow_bor (optional)               |
| `is_read_ntf`    | boolean      | not null, default: false | -                                         |
| `read_at_ntf`    | timestamp    | -                        | When notification was read; NULL = unread |
| `created_at_ntf` | timestamp    | default: now()           | -                                         |

**Indexes:**

- `idx_unread_timeline_type_ntf` on `(id_acc_ntf, is_read_ntf, created_at_ntf, id_ntt_ntf)` - covering index for notification feed
- `idx_borrow_ntf` on `id_bor_ntf`
- `idx_type_ntf` on `id_ntt_ntf`

> **Note:**
>
> - Archival: Delete or move records older than 12 months via scheduled job.
> - At small scale (< 100K rows/year), no partitioning needed.
> - `idx_unread_timeline_type_ntf` is a covering index for the notification feed query (user + unread filter + chronological sort + type filter).

---

#### search_log_slg

Analytics table for tracking user searches.

| Column            | Type         | Constraints        | Notes                         |
|-------------------|--------------|--------------------|-------------------------------|
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

> **Note:**
>
> - Extended attributes stored in `event_meta_evm` (EAV pattern) — replaces former JSON column.

---

#### event_meta_evm

Optional event metadata stored as key/value rows (EAV pattern, strict 1NF/3NF).

| Column            | Type         | Constraints        | Notes            |
|-------------------|--------------|--------------------|------------------|
| `id_evm`          | int          | PK, auto-increment | -                |
| `id_evt_evm`      | int          | not null           | FK to event_evt  |
| `meta_key_evm`    | varchar(100) | not null           | -                |
| `meta_value_evm`  | varchar(255) | not null           | -                |
| `created_at_evm`  | timestamp    | default: now()     | -                |

**Indexes:**

- `uq_event_meta_evm` (UNIQUE) on `(id_evt_evm, meta_key_evm)`
- `idx_meta_key_evm` on `meta_key_evm`

> **Note:**
>
> - FK cascades on delete.

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
> - Per-column change details stored in `audit_log_detail_ald` (normalized rows) — replaces former JSON columns.

---

#### audit_log_detail_ald

Normalized audit detail rows (strict 1NF/3NF). One row per changed column per audit entry.

| Column            | Type        | Constraints        | Notes                |
|-------------------|-------------|--------------------|----------------------|
| `id_ald`          | int         | PK, auto-increment | -                    |
| `id_aud_ald`      | int         | not null           | FK to audit_log_aud  |
| `column_name_ald` | varchar(64) | not null           | -                    |
| `old_value_ald`   | text        | -                  | -                    |
| `new_value_ald`   | text        | -                  | -                    |
| `created_at_ald`  | timestamp   | default: now()     | -                    |

**Indexes:**

- `idx_audit_detail_column_ald` on `column_name_ald`

> **Note:**
>
> - FK cascades on delete.

---

### Legal & Compliance

#### terms_of_service_tos

Stores versioned Terms of Service documents. Emphasizes platform's matchmaking role.

| Column                  | Type         | Constraints             | Notes                                    |
|-------------------------|--------------|-------------------------|------------------------------------------|
| `id_tos`                | int          | PK, auto-increment      | -                                        |
| `version_tos`           | varchar(20)  | unique, not null        | Version identifier (e.g., 1.0, 2.0)      |
| `title_tos`             | varchar(255) | not null                | ToS document title                       |
| `content_tos`           | text         | not null                | Full Terms of Service text               |
| `summary_tos`           | text         | -                       | Plain-language summary of key terms      |
| `effective_at_tos`      | timestamp    | not null                | When this version becomes active         |
| `superseded_at_tos`     | timestamp    | -                       | When replaced; NULL = current            |
| `is_active_tos`         | boolean      | not null, default: true | Only one version should be active        |
| `id_acc_created_by_tos` | int          | not null                | Admin who created this version           |
| `created_at_tos`        | timestamp    | default: now()          | -                                        |

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

| Column           | Type         | Constraints              | Notes                               |
|------------------|--------------|--------------------------|-------------------------------------|
| `id_tac`         | int          | PK, auto-increment       | -                                   |
| `id_acc_tac`     | int          | not null                 | FK to account_acc                   |
| `id_tos_tac`     | int          | not null                 | FK to terms_of_service_tos          |
| `ip_address_tac` | varchar(45)  | -                        | IP address at time of acceptance    |
| `user_agent_tac` | varchar(512) | -                        | Browser/device info for audit trail |
| `accepted_at_tac`| timestamp    | not null, default: now() | When user accepted                  |

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

| Column                                | Type         | Constraints              | Notes                                           |
|---------------------------------------|--------------|--------------------------|-------------------------------------------------|
| `id_bwv`                              | int          | PK, auto-increment       | -                                               |
| `id_bor_bwv`                          | int          | unique, not null         | One waiver per borrow; FK to borrow_bor         |
| `id_wtp_bwv`                          | int          | not null                 | FK to waiver_type_wtp                           |
| `id_acc_bwv`                          | int          | not null                 | Borrower who signed the waiver                  |
| `is_tool_condition_acknowledged_bwv`  | boolean      | not null, default: false | Borrower confirms current tool condition        |
| `preexisting_conditions_noted_bwv`    | text         | -                        | Snapshot of tool conditions at waiver time      |
| `is_responsibility_accepted_bwv`      | boolean      | not null, default: false | Borrower accepts responsibility for tool        |
| `is_liability_waiver_accepted_bwv`    | boolean      | not null, default: false | Borrower acknowledges platform liability limits |
| `is_insurance_reminder_shown_bwv`     | boolean      | not null, default: false | Insurance recommendation was displayed          |
| `ip_address_bwv`                      | varchar(45)  | -                        | -                                               |
| `user_agent_bwv`                      | varchar(512) | -                        | -                                               |
| `signed_at_bwv`                       | timestamp    | not null, default: now() | -                                               |

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

| Column                 | Type        | Constraints              | Notes                                              |
|------------------------|-------------|--------------------------|----------------------------------------------------|
| `id_hov`               | int         | PK, auto-increment       | -                                                  |
| `id_bor_hov`           | int         | not null                 | FK to borrow_bor                                   |
| `id_hot_hov`           | int         | not null                 | FK to handover_type_hot (pickup or return)         |
| `verification_code_hov`| varchar(8)  | not null                 | Unique 6-8 character code for digital handshake    |
| `id_acc_generator_hov` | int         | not null                 | Account that generated the code                    |
| `id_acc_verifier_hov`  | int         | -                        | Account that verified; NULL until verified         |
| `condition_notes_hov`  | text        | -                        | Condition notes at handover                        |
| `generated_at_hov`     | timestamp   | not null, default: now() | -                                                  |
| `expires_at_hov`       | timestamp   | not null                 | Code expires after 24 hours                        |
| `verified_at_hov`      | timestamp   | -                        | When verification completed; NULL = pending        |

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
> - `uq_verification_code_hov` enforces code uniqueness at the database level (upgraded from non-unique index).

---

#### incident_report_irt

Mandatory incident reporting for damage, theft, loss, or disputes.

| Column                              | Type         | Constraints             | Notes                                              |
|-------------------------------------|--------------|-------------------------|----------------------------------------------------|
| `id_irt`                            | int          | PK, auto-increment      | -                                                  |
| `id_bor_irt`                        | int          | not null                | FK to borrow_bor                                   |
| `id_acc_irt`                        | int          | not null                | Account reporting the incident                     |
| `id_ity_irt`                        | int          | not null                | FK to incident_type_ity                            |
| `subject_irt`                       | varchar(255) | not null                | -                                                  |
| `description_irt`                   | text         | not null                | -                                                  |
| `incident_occurred_at_irt`          | timestamp    | not null                | When the incident occurred                         |
| `is_reported_within_deadline_irt`   | boolean      | not null, default: true | True if reported within 48 hours of incident       |
| `estimated_damage_amount_irt`       | decimal(8,2) | -                       | Estimated cost of damage/loss                      |
| `resolution_notes_irt`              | text         | -                       | Admin resolution notes                             |
| `resolved_at_irt`                   | timestamp    | -                       | When incident was resolved                         |
| `id_acc_resolved_by_irt`            | int          | -                       | Admin who resolved                                 |
| `created_at_irt`                    | timestamp    | default: now()          | -                                                  |
| `updated_at_irt`                    | timestamp    | default: now()          | ON UPDATE CURRENT_TIMESTAMP                        |

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
> - Incident photos stored in `incident_photo_iph` (normalized rows) — replaces former JSON column.

---

#### incident_photo_iph

Incident photos normalized into separate rows (strict 1NF/3NF). Replaces former `photos_json_irt` column.

| Column            | Type         | Constraints        | Notes                      |
|-------------------|--------------|--------------------|----------------------------|
| `id_iph`          | int          | PK, auto-increment | -                          |
| `id_irt_iph`      | int          | not null           | FK to incident_report_irt  |
| `file_name_iph`   | varchar(255) | not null           | -                          |
| `caption_iph`     | varchar(255) | -                  | -                          |
| `sort_order_iph`  | int          | default: 0         | -                          |
| `created_at_iph`  | timestamp    | default: now()     | -                          |

**Indexes:**

- `idx_incident_photo_order_iph` on `(id_irt_iph, sort_order_iph)`

> **Note:**
>
> - FK cascades on delete.

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
> - Extended attributes stored in `payment_transaction_meta_ptm` (EAV pattern) — replaces former JSON column.

---

#### payment_transaction_meta_ptm

Optional transaction metadata stored as key/value rows (EAV pattern, strict 1NF/3NF).

| Column            | Type         | Constraints        | Notes                          |
|-------------------|--------------|--------------------|--------------------------------|
| `id_ptm`          | int          | PK, auto-increment | -                              |
| `id_ptx_ptm`      | int          | not null           | FK to payment_transaction_ptx  |
| `meta_key_ptm`    | varchar(100) | not null           | -                              |
| `meta_value_ptm`  | varchar(255) | not null           | -                              |
| `created_at_ptm`  | timestamp    | default: now()     | -                              |

**Indexes:**

- `uq_ptx_meta_ptm` (UNIQUE) on `(id_ptx_ptm, meta_key_ptm)`
- `idx_meta_key_ptm` on `meta_key_ptm`

> **Note:**
>
> - FK cascades on delete.

---

### Materialized Cache & Analytics

Materialized cache tables store pre-computed statistics for dashboard and reporting performance. These tables have **no foreign keys** — data integrity is maintained by refresh stored procedures. Primary keys mirror source table PKs (not auto-increment). Column names mirror source tables and are not suffixed to these cache tables.

#### neighborhood_summary_mat

Pre-computed neighborhood statistics for dashboard display.

| Column                   | Type          | Constraints              | Notes                                                 |
|--------------------------|---------------|--------------------------|-------------------------------------------------------|
| `id_nbh`                 | int           | PK                       | Mirrors neighborhood_nbh.id_nbh; not auto-increment   |
| `neighborhood_name_nbh`  | varchar(100)  | not null                 | -                                                     |
| `city_name_nbh`          | varchar(100)  | not null                 | -                                                     |
| `state_code_sta`         | char(2)       | not null                 | -                                                     |
| `state_name_sta`         | varchar(50)   | not null                 | -                                                     |
| `latitude_nbh`           | decimal(10,8) | -                        | -                                                     |
| `longitude_nbh`          | decimal(11,8) | -                        | -                                                     |
| `location_point_nbh`     | point         | -                        | SRID 4326                                             |
| `created_at_nbh`         | timestamp     | -                        | -                                                     |
| `total_members`          | int           | not null, default: 0     | -                                                     |
| `active_members`         | int           | not null, default: 0     | -                                                     |
| `verified_members`       | int           | not null, default: 0     | -                                                     |
| `total_tools`            | int           | not null, default: 0     | -                                                     |
| `available_tools`        | int           | not null, default: 0     | -                                                     |
| `active_borrows`         | int           | not null, default: 0     | -                                                     |
| `completed_borrows_30d`  | int           | not null, default: 0     | -                                                     |
| `upcoming_events`        | int           | not null, default: 0     | -                                                     |
| `zip_codes`              | text          | -                        | Comma-separated ZIP codes for display                 |
| `refreshed_at`           | timestamp     | not null, default: now() | -                                                     |

**Indexes:**

- `idx_state_mat` on `state_code_sta`
- `idx_city_mat` on `city_name_nbh`
- `idx_refreshed_mat` on `refreshed_at`

> **Note:**
>
> - Refreshed periodically via scheduled event (`CALL refresh_neighborhood_summary_mat`).
> - No foreign keys; data integrity maintained by refresh procedure.

---

#### user_reputation_mat

Pre-computed user reputation scores for profile display and ranking.

| Column                  | Type          | Constraints              | Notes                                          |
|-------------------------|---------------|--------------------------|------------------------------------------------|
| `id_acc`                | int           | PK                       | Mirrors account_acc.id_acc; not auto-increment |
| `full_name`             | varchar(101)  | not null                 | -                                              |
| `email_address_acc`     | varchar(255)  | not null                 | -                                              |
| `account_status`        | varchar(30)   | not null                 | -                                              |
| `member_since`          | timestamp     | -                        | -                                              |
| `lender_avg_rating`     | decimal(3,1)  | not null, default: 0     | -                                              |
| `lender_rating_count`   | int           | not null, default: 0     | -                                              |
| `borrower_avg_rating`   | decimal(3,1)  | not null, default: 0     | -                                              |
| `borrower_rating_count` | int           | not null, default: 0     | -                                              |
| `overall_avg_rating`    | decimal(3,1)  | -                        | -                                              |
| `total_rating_count`    | int           | not null, default: 0     | -                                              |
| `tools_owned`           | int           | not null, default: 0     | -                                              |
| `completed_borrows`     | int           | not null, default: 0     | -                                              |
| `refreshed_at`          | timestamp     | not null, default: now() | -                                              |

**Indexes:**

- `idx_lender_rating_mat` on `lender_avg_rating`
- `idx_borrower_rating_mat` on `borrower_avg_rating`
- `idx_overall_rating_mat` on `overall_avg_rating`
- `idx_refreshed_mat` on `refreshed_at`

> **Note:**
>
> - Refreshed periodically via scheduled event (`CALL refresh_user_reputation_mat`).
> - No foreign keys; data integrity maintained by refresh procedure.

---

#### tool_statistics_mat

Pre-computed tool usage statistics for analytics and search ranking.

| Column                 | Type          | Constraints              | Notes                                        |
|------------------------|---------------|--------------------------|----------------------------------------------|
| `id_tol`               | int           | PK                       | Mirrors tool_tol.id_tol; not auto-increment  |
| `tool_name_tol`        | varchar(100)  | not null                 | -                                            |
| `owner_id`             | int           | not null                 | -                                            |
| `owner_name`           | varchar(101)  | not null                 | -                                            |
| `tool_condition`       | varchar(30)   | not null                 | -                                            |
| `rental_fee_tol`       | decimal(10,2) | -                        | -                                            |
| `estimated_value_tol`  | decimal(10,2) | -                        | -                                            |
| `created_at_tol`       | timestamp     | -                        | -                                            |
| `avg_rating`           | decimal(3,1)  | not null, default: 0     | -                                            |
| `rating_count`         | int           | not null, default: 0     | -                                            |
| `five_star_count`      | int           | not null, default: 0     | -                                            |
| `total_borrows`        | int           | not null, default: 0     | -                                            |
| `completed_borrows`    | int           | not null, default: 0     | -                                            |
| `cancelled_borrows`    | int           | not null, default: 0     | -                                            |
| `denied_borrows`       | int           | not null, default: 0     | -                                            |
| `total_hours_borrowed` | int           | not null, default: 0     | -                                            |
| `last_borrowed_at`     | timestamp     | -                        | -                                            |
| `incident_count`       | int           | not null, default: 0     | -                                            |
| `refreshed_at`         | timestamp     | not null, default: now() | -                                            |

**Indexes:**

- `idx_owner_mat` on `owner_id`
- `idx_avg_rating_mat` on `avg_rating`
- `idx_total_borrows_mat` on `total_borrows`
- `idx_refreshed_mat` on `refreshed_at`

> **Note:**
>
> - Refreshed periodically via scheduled event (`CALL refresh_tool_statistics_mat`).
> - No foreign keys; data integrity maintained by refresh procedure.

---

#### category_summary_mat

Pre-computed category-level summaries for browse/filter pages.

| Column                    | Type          | Constraints              | Notes                                          |
|---------------------------|---------------|--------------------------|------------------------------------------------|
| `id_cat`                  | int           | PK                       | Mirrors category_cat.id_cat; not auto-increment|
| `category_name_cat`       | varchar(100)  | not null                 | -                                              |
| `category_icon`           | varchar(255)  | -                        | -                                              |
| `total_tools`             | int           | not null, default: 0     | -                                              |
| `listed_tools`            | int           | not null, default: 0     | -                                              |
| `available_tools`         | int           | not null, default: 0     | -                                              |
| `category_avg_rating`     | decimal(3,1)  | -                        | -                                              |
| `total_completed_borrows` | int           | not null, default: 0     | -                                              |
| `min_rental_fee`          | decimal(10,2) | -                        | -                                              |
| `max_rental_fee`          | decimal(10,2) | -                        | -                                              |
| `avg_rental_fee`          | decimal(10,2) | -                        | -                                              |
| `refreshed_at`            | timestamp     | not null, default: now() | -                                              |

**Indexes:**

- `idx_total_tools_mat` on `total_tools`
- `idx_available_mat` on `available_tools`
- `idx_refreshed_mat` on `refreshed_at`

> **Note:**
>
> - Refreshed periodically via scheduled event (`CALL refresh_category_summary_mat`).
> - No foreign keys; data integrity maintained by refresh procedure.

---

#### platform_daily_stat_pds

Daily platform-wide analytics for admin dashboard and reporting.

| Column                    | Type          | Constraints              | Notes           |
|---------------------------|---------------|--------------------------|-----------------|
| `stat_date_pds`           | date          | PK                       | One row per day |
| `total_accounts_pds`      | int           | not null, default: 0     | -               |
| `active_accounts_pds`     | int           | not null, default: 0     | -               |
| `new_accounts_today_pds`  | int           | not null, default: 0     | -               |
| `total_tools_pds`         | int           | not null, default: 0     | -               |
| `available_tools_pds`     | int           | not null, default: 0     | -               |
| `new_tools_today_pds`     | int           | not null, default: 0     | -               |
| `active_borrows_pds`      | int           | not null, default: 0     | -               |
| `completed_today_pds`     | int           | not null, default: 0     | -               |
| `new_requests_today_pds`  | int           | not null, default: 0     | -               |
| `open_disputes_pds`       | int           | not null, default: 0     | -               |
| `open_incidents_pds`      | int           | not null, default: 0     | -               |
| `overdue_borrows_pds`     | int           | not null, default: 0     | -               |
| `deposits_held_total_pds` | decimal(12,2) | not null, default: 0     | -               |
| `refreshed_at_pds`        | timestamp     | not null, default: now() | -               |

**Indexes:**

- `idx_stat_month_pds` on `stat_date_pds`

> **Note:**
>
> - Refreshed via scheduled event (`CALL refresh_platform_daily_stats`).
> - No foreign keys; standalone analytics table.

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

| Parent (One)             | Child (Many)         | Foreign Key        | Description                       |
|--------------------------|----------------------|--------------------|-----------------------------------|
| `role_rol`               | `account_acc`        | `id_rol_acc`       | Role assigned to accounts         |
| `account_status_ast`     | `account_acc`        | `id_ast_acc`       | Status of accounts                |
| `contact_preference_cpr` | `account_acc`        | `id_cpr_acc`       | Contact preference for accounts   |
| `zip_code_zpc`           | `account_acc`        | `zip_code_acc`     | Location of accounts              |
| `neighborhood_nbh`       | `account_acc`        | `id_nbh_acc`       | Optional neighborhood membership  |
| `account_acc`            | `account_image_aim`  | `id_acc_aim`       | Account has profile images        |
| `account_acc`            | `account_meta_acm`   | `id_acc_acm`       | Account has metadata (EAV)        |
| `account_acc`            | `account_bio_abi`    | `id_acc_abi`       | Account has optional bio (0 or 1) |
| `account_acc`            | `password_reset_pwr` | `id_acc_pwr`       | Account has password reset tokens |
| `account_acc`            | `vector_image_vec`   | `id_acc_vec`       | Admin uploads vector images       |
| `vector_image_vec`       | `category_cat`       | `id_vec_cat`       | Category has optional icon        |

#### Neighborhood Domain

| Parent (One)        | Child (Many)               | Foreign Key        | Description                       |
|---------------------|----------------------------|--------------------|-----------------------------------|
| `state_sta`         | `neighborhood_nbh`         | `id_sta_nbh`       | State for neighborhood            |
| `neighborhood_nbh`  | `neighborhood_meta_nbm`    | `id_nbh_nbm`       | Neighborhood has metadata (EAV)   |
| `neighborhood_nbh`  | `neighborhood_zip_nbhzpc`  | `id_nbh_nbhzpc`    | Neighborhood contains ZIP codes   |
| `zip_code_zpc`      | `neighborhood_zip_nbhzpc`  | `zip_code_nbhzpc`  | ZIP code belongs to neighborhoods |

#### Tool Domain

| Parent (One)         | Child (Many)      | Foreign Key    | Description                    |
|----------------------|-------------------|----------------|--------------------------------|
| `tool_condition_tcd` | `tool_tol`        | `id_tcd_tol`   | Condition of tools             |
| `account_acc`        | `tool_tol`        | `id_acc_tol`   | Account owns tools             |
| `tool_tol`           | `tool_meta_tlm`   | `id_tol_tlm`   | Tool has metadata (EAV)        |
| `tool_tol`           | `tool_image_tim`  | `id_tol_tim`   | Tool has images                |

#### Borrowing Domain

| Parent (One)        | Child (Many)             | Foreign Key              | Description                       |
|---------------------|--------------------------|--------------------------|-----------------------------------|
| `tool_tol`          | `borrow_bor`             | `id_tol_bor`             | Tool is borrowed multiple times   |
| `account_acc`       | `borrow_bor`             | `id_acc_bor`             | Account borrows tools             |
| `borrow_status_bst` | `borrow_bor`             | `id_bst_bor`             | Status of borrow requests         |
| `tool_tol`          | `availability_block_avb` | `id_tol_avb`             | Tool has availability blocks      |
| `block_type_btp`    | `availability_block_avb` | `id_btp_avb`             | Type of availability block        |
| `borrow_bor`        | `availability_block_avb` | `id_bor_avb`             | Borrow creates availability block |
| `borrow_bor`        | `loan_extension_lex`     | `id_bor_lex`             | Borrow has extensions             |
| `account_acc`       | `loan_extension_lex`     | `id_acc_approved_by_lex` | Approver of extension             |

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

| Parent (One)        | Child (Many)     | Foreign Key              | Description               |
|---------------------|------------------|--------------------------|---------------------------|
| `neighborhood_nbh`  | `event_evt`      | `id_nbh_evt`            | Location of events        |
| `account_acc`       | `event_evt`      | `id_acc_evt`            | Admin creates events      |
| `account_acc`       | `event_evt`      | `id_acc_updated_by_evt` | Admin last modified event |
| `event_evt`         | `event_meta_evm` | `id_evt_evm`            | Event has metadata (EAV)  |

#### phpBB Integration Domain

| Parent (One)  | Child (Many)           | Foreign Key  | Description              |
|---------------|------------------------|--------------|--------------------------|
| `account_acc` | `phpbb_integration_php`| `id_acc_php` | Account links to phpBB   |

#### Audit Log Domain

| Parent (One)    | Child (Many)           | Foreign Key  | Description                       |
|-----------------|------------------------|--------------|-----------------------------------|
| `account_acc`   | `audit_log_aud`        | `id_acc_aud` | Account makes audited change      |
| `audit_log_aud` | `audit_log_detail_ald` | `id_aud_ald` | Audit entry has detail rows (EAV) |

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

| Parent (One)              | Child (Many)                   | Foreign Key       | Description                      |
|---------------------------|--------------------------------|-------------------|----------------------------------|
| `borrow_bor`              | `security_deposit_sdp`         | `id_bor_sdp`      | Borrow has deposit (1:1)         |
| `deposit_status_dps`      | `security_deposit_sdp`         | `id_dps_sdp`      | Deposit status                   |
| `payment_provider_ppv`    | `security_deposit_sdp`         | `id_ppv_sdp`      | Payment provider for deposit     |
| `incident_report_irt`     | `security_deposit_sdp`         | `id_irt_sdp`      | Incident causing forfeiture      |
| `security_deposit_sdp`    | `payment_transaction_ptx`      | `id_sdp_ptx`      | Deposit has transactions         |
| `borrow_bor`              | `payment_transaction_ptx`      | `id_bor_ptx`      | Borrow has payment transactions  |
| `payment_provider_ppv`    | `payment_transaction_ptx`      | `id_ppv_ptx`      | Payment provider for transaction |
| `account_acc`             | `payment_transaction_ptx`      | `id_acc_from_ptx` | Payer account                    |
| `account_acc`             | `payment_transaction_ptx`      | `id_acc_to_ptx`   | Payee account                    |
| `payment_transaction_ptx` | `payment_transaction_meta_ptm` | `id_ptx_ptm`      | Transaction has metadata (EAV)   |

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
|  +-----+-----+      +---+--------+--+           |                            |
|        |                 |        |             |                            |
|        v                 |        v             |                            |
|  +------------------+    |   +------------------+                            |
|  | neighborhood_nbh |    |   | account_image_aim|                            |
|  +--------+---------+    |   +------------------+                            |
|           |              |        |                                          |
|           v              |        v                                          |
|  +-----------------------+   +----------------+                              |
|  | neighborhood_meta_nbm|   | account_bio_abi |                              |
|  +-----------------------+   +----------------+                              |
|           |              |                                                   |
|           v              v                                                   |
|    +-------------+  +-------------------+  +----------------------+          |
|    | zip_code_zpc|  | account_meta_acm  |  | password_reset_pwr   |          |
|    +-------------+  +-------------------+  +----------------------+          |
|                                                                              |
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
|             +---+--------+---+                                               |
|                 |        |                                                   |
|                 v        v                                                   |
|     +----------------+  +----------------+                                   |
|     | tool_image_tim |  | tool_meta_tlm  |                                   |
|     +----------------+  +----------------+                                   |
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
|  +---+--------+----+       +-----------+------------+                        |
|      |        |                        |                                     |
|      |        v                        v                                     |
|      |   +--------------------+  +----------------+                          |
|      |   | loan_extension_lex |  | block_type_btp |                          |
|      |   +--------------------+  +----------------+                          |
|      |                                                                       |
+------+--------------------------------------------------------------+--------+
       |
+------+-------------------------------------------------------------------+---+
|      |                   RATINGS & DISPUTES GROUP                        |   |
+------+-------------------------------------------------------------------+---+
|      |                                                                       |
|      v                                                                       |
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
|  +-----------------+   +----------------------+   +-----------------+        |
|  |    event_evt    |   | phpbb_integration_php|   |  audit_log_aud  |        |
|  +--------+--------+   +----------------------+   +--------+--------+        |
|           |                                                 |                |
|           v                                                 v                |
|  +-----------------+                              +-----------------------+  |
|  | event_meta_evm  |                              | audit_log_detail_ald  |  |
|  +-----------------+                              +-----------------------+  |
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
|  +--------------------------+  +---------+----------+                        |
|            |                             |                                   |
|            +-------------+---------------+                                   |
|                          |               |                                   |
|                          |               v                                   |
|                          |     +--------------------+                        |
|                          |     | incident_photo_iph |                        |
|                          |     +--------------------+                        |
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
|             +------------+------------+                                      |
|                          |                                                   |
|                          v                                                   |
|           +-------------------------------+                                  |
|           | payment_transaction_meta_ptm  |                                  |
|           +-------------------------------+                                  |
|                                                                              |
+------------------------------------------------------------------------------+

+------------------------------------------------------------------------------+
|                    MATERIALIZED CACHE & ANALYTICS GROUP                      |
+------------------------------------------------------------------------------+
|                                                                              |
|  +---------------------------+  +------------------------+                   |
|  | neighborhood_summary_mat  |  | user_reputation_mat    |                   |
|  +---------------------------+  +------------------------+                   |
|                                                                              |
|  +------------------------+  +------------------------+                      |
|  | tool_statistics_mat    |  | category_summary_mat   |                      |
|  +------------------------+  +------------------------+                      |
|                                                                              |
|                  +-------------------------+                                 |
|                  | platform_daily_stat_pds |                                 |
|                  +-------------------------+                                 |
|                                                                              |
|  Note: No foreign keys. Refreshed via stored procedures.                     |
|                                                                              |
+------------------------------------------------------------------------------+
```

---

## Triggers

> Triggers enforce business rules, data integrity, and referential constraints at the database level. This section documents all 31 triggers in the schema.

### Trigger Summary

| # | Trigger | Timing | Table | Purpose |
|---|---------|--------|-------|---------|
| 1 | `trg_nbhzpc_before_insert` | BEFORE INSERT | `neighborhood_zip_nbhzpc` | Enforce single primary neighborhood per ZIP |
| 2 | `trg_nbhzpc_before_update` | BEFORE UPDATE | `neighborhood_zip_nbhzpc` | Enforce single primary neighborhood per ZIP |
| 3 | `trg_account_before_update` | BEFORE UPDATE | `account_acc` | Set/clear `deleted_at_acc` on status change |
| 4 | `trg_tool_before_insert` | BEFORE INSERT | `tool_tol` | Reject tool creation by deleted accounts |
| 5 | `trg_tool_before_update` | BEFORE UPDATE | `tool_tol` | Reject tool transfer to deleted accounts |
| 6 | `trg_bookmark_before_insert` | BEFORE INSERT | `tool_bookmark_acctol` | Reject bookmarks by deleted accounts |
| 7 | `trg_borrow_before_insert` | BEFORE INSERT | `borrow_bor` | Validate new borrow requests (permissive) |
| 8 | `trg_borrow_before_update` | BEFORE UPDATE | `borrow_bor` | Enforce status transitions and timestamps (strict) |
| 9 | `trg_availability_block_before_insert` | BEFORE INSERT | `availability_block_avb` | Validate block type rules and overlap prevention |
| 10 | `trg_availability_block_before_update` | BEFORE UPDATE | `availability_block_avb` | Validate block type rules and overlap prevention |
| 11 | `trg_user_rating_before_insert` | BEFORE INSERT | `user_rating_urt` | Prevent self-rating; validate score range |
| 12 | `trg_user_rating_before_update` | BEFORE UPDATE | `user_rating_urt` | Validate score range on update |
| 13 | `trg_tool_rating_before_insert` | BEFORE INSERT | `tool_rating_trt` | Validate score range |
| 14 | `trg_dispute_before_insert` | BEFORE INSERT | `dispute_dsp` | Set default status to open |
| 15 | `trg_dispute_message_before_insert` | BEFORE INSERT | `dispute_message_dsm` | Set default message type to initial_report |
| 16 | `trg_borrow_waiver_before_insert` | BEFORE INSERT | `borrow_waiver_bwv` | Enforce required acknowledgments |
| 17 | `trg_handover_verification_before_insert` | BEFORE INSERT | `handover_verification_hov` | Auto-generate verification code and expiry |
| 18 | `trg_incident_report_before_insert` | BEFORE INSERT | `incident_report_irt` | Auto-calculate deadline compliance |
| 19 | `trg_tos_before_insert` | BEFORE INSERT | `terms_of_service_tos` | Deactivate previous ToS version |
| 20-31 | Lookup protection triggers | BEFORE UPDATE/DELETE | 6 lookup tables | Prevent modification of seeded lookup rows |

### Business Logic Triggers

#### trg_nbhzpc_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `neighborhood_zip_nbhzpc` |
| **Purpose** | Enforces that only one neighborhood can be marked as primary for any given ZIP code |

**Business Rules:**

- If `is_primary_nbhzpc` is TRUE, checks for existing primary assignment on the same ZIP
- Signals SQLSTATE `45000` with message: `'Only one primary neighborhood per ZIP allowed'`

---

#### trg_nbhzpc_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `neighborhood_zip_nbhzpc` |
| **Purpose** | Same constraint as INSERT but excludes the current row from the duplicate check |

**Business Rules:**

- If `is_primary_nbhzpc` is TRUE, checks for existing primary on same ZIP (excluding self via `id_nbhzpc != NEW.id_nbhzpc`)
- Signals SQLSTATE `45000` with message: `'Only one primary neighborhood per ZIP allowed'`

---

#### trg_account_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `account_acc` |
| **Purpose** | Manages the `deleted_at_acc` timestamp for soft-delete lifecycle |

**Business Rules:**

- Skips processing if `id_ast_acc` has not changed (early exit via labeled block)
- Looks up the `deleted` status ID from `account_status_ast`
- If `deleted` status value is missing from lookup table: signals SQLSTATE `45000` with system error
- If new status is `deleted`: sets `deleted_at_acc = NOW()`
- If old status was `deleted` and new status is not: clears `deleted_at_acc` to NULL (reactivation)

---

#### trg_tool_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `tool_tol` |
| **Purpose** | Prevents deleted accounts from creating new tool listings |

**Business Rules:**

- Looks up the `deleted` status ID from `account_status_ast`
- Looks up the owner's current status from `account_acc`
- If owner status matches `deleted`: signals SQLSTATE `45000` with message: `'Cannot create tool: owner account is deleted'`

---

#### trg_tool_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `tool_tol` |
| **Purpose** | Prevents tool ownership transfer to deleted accounts |

**Business Rules:**

- Only fires when `id_acc_tol` (owner) is changing
- Joins `account_acc` with `account_status_ast` to resolve the new owner's status name
- If new owner status is `'deleted'`: signals SQLSTATE `45000` with message: `'Cannot transfer tool: new owner account is deleted'`

---

#### trg_bookmark_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `tool_bookmark_acctol` |
| **Purpose** | Prevents deleted accounts from creating bookmarks |

**Business Rules:**

- Looks up the `deleted` status ID from `account_status_ast`
- Looks up the bookmarking account's current status from `account_acc`
- If account status matches `deleted`: signals SQLSTATE `45000` with message: `'Cannot create bookmark: account is deleted'`

---

#### trg_borrow_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `borrow_bor` |
| **Purpose** | Validates new borrow requests and auto-fills timestamps (permissive mode for seed data) |

**Business Rules:**

- Looks up `approved`, `borrowed`, and `returned` status IDs from `borrow_status_bst`; signals system error if any are missing
- Looks up tool owner from `tool_tol`; signals error if tool not found
- **Self-borrow prevention:** rejects if `tool_owner_id = NEW.id_acc_bor`
- **Deleted account checks:** rejects if borrower or tool owner has `deleted` status
- **Timestamp ordering validation:**
  - `approved_at` must be after `requested_at`
  - `borrowed_at` must be after `approved_at`
  - `returned_at` must be after `borrowed_at`
- **Auto-fill logic (permissive):** fills missing timestamps based on status:
  - Status = `approved`: auto-fills `approved_at`
  - Status = `borrowed`: auto-fills `approved_at`, `borrowed_at`, and calculates `due_at` from `borrowed_at + loan_duration_hours`
  - Status = `returned`: auto-fills all intermediate timestamps including `returned_at`

> **Note:** INSERT trigger is permissive to support seed data loading with various states. The UPDATE trigger enforces strict transition rules.

---

#### trg_borrow_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `borrow_bor` |
| **Purpose** | Enforces strict status transitions, timestamp coherence, and due-date protection |

**Status Transition Matrix:**

```text
requested --> approved --> borrowed --> returned
    |             |           |
    +--> denied   +--> denied +--> (no regression)
    |             |
    +--> cancelled+--> cancelled

Terminal states (no outgoing transitions): returned, denied, cancelled
```

**Business Rules:**

- **Self-borrow prevention:** re-validates if borrower or tool changes
- **Terminal state protection:** cannot change status from `returned`, `denied`, or `cancelled`
- **No regression:** cannot move backward in the lifecycle (e.g., `borrowed` -> `approved`)
- **No skipping:** cannot skip `approved` (requested -> borrowed) or `borrowed` (approved -> returned)
- **Strict timestamp coherence (UPDATE only):**
  - `approved` status requires `requested_at` to exist; auto-fills `approved_at`
  - `borrowed` status requires `approved_at` to exist; auto-fills `borrowed_at` and calculates `due_at`
  - `returned` status requires `borrowed_at` to exist; auto-fills `returned_at`
  - `cancelled` status auto-fills `cancelled_at`
- **Due date protection:** once `due_at_bor` is set, it can only be changed if a matching `loan_extension_lex` record exists with the new due date
- **Timestamp ordering:** always validates chronological order of all timestamps

---

#### trg_availability_block_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `availability_block_avb` |
| **Purpose** | Validates block type rules — borrow blocks require a borrow ID, admin blocks must not have one |

**Business Rules:**

- Looks up block type name from `block_type_btp`; signals error if not found
- If type = `'borrow'`: `id_bor_avb` is required (signals error if NULL)
- If type = `'admin'`: `id_bor_avb` must be NULL (signals error if present)

---

#### trg_availability_block_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `availability_block_avb` |
| **Purpose** | Same block type validation as INSERT, but only fires when type or borrow ID changes |

**Business Rules:**

- Only fires when `id_btp_avb` or `id_bor_avb` changes (uses NULL-safe `<=>` comparison)
- Same validation rules as the INSERT trigger

---

#### trg_user_rating_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `user_rating_urt` |
| **Purpose** | Prevents self-rating, validates borrow participation, and rejects deleted accounts |

**Business Rules:**

- **Self-rating prevention:** rejects if `id_acc_urt = id_acc_target_urt`
- **Participation validation:** joins `borrow_bor` with `tool_tol` to find both the borrower and tool owner:
  - Rater must be either the borrower or the tool owner
  - Target must be either the borrower or the tool owner
- **Deleted account checks:** rejects if rater or target account has `deleted` status

---

#### trg_user_rating_before_update

| Property | Value |
|----------|-------|
| **Timing** | BEFORE UPDATE |
| **Table** | `user_rating_urt` |
| **Purpose** | Defense-in-depth self-rating prevention on update |

**Business Rules:**

- Rejects if `id_acc_urt = id_acc_target_urt` (prevents self-rating via update)

---

#### trg_tool_rating_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `tool_rating_trt` |
| **Purpose** | Validates that only the borrower can rate a tool, and only for the correct borrow transaction |

**Business Rules:**

- Looks up borrower and tool from `borrow_bor` using `id_bor_trt`
- **Borrower-only rating:** rejects if `id_acc_trt` does not match the borrow's borrower
- **Tool match validation:** rejects if `id_tol_trt` does not match the borrow's tool
- **Deleted account check:** rejects if rater account has `deleted` status

---

#### trg_dispute_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `dispute_dsp` |
| **Purpose** | Validates that the dispute reporter was a participant in the borrow transaction |

**Business Rules:**

- Joins `borrow_bor` with `tool_tol` to identify both the borrower and the tool owner
- Rejects if `id_acc_dsp` (reporter) is neither the borrower nor the tool owner
- Rejects if reporter account has `deleted` status

---

#### trg_dispute_message_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `dispute_message_dsm` |
| **Purpose** | Prevents deleted accounts from posting dispute messages |

**Business Rules:**

- Looks up the `deleted` status ID from `account_status_ast`
- Rejects if `id_acc_dsm` (message author) has `deleted` status

---

#### trg_borrow_waiver_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `borrow_waiver_bwv` |
| **Purpose** | Enforces that all three required acknowledgment booleans are TRUE before a waiver can be saved |

**Business Rules:**

- Rejects if `is_tool_condition_acknowledged_bwv = FALSE` — borrower must acknowledge tool condition
- Rejects if `is_responsibility_accepted_bwv = FALSE` — borrower must accept responsibility
- Rejects if `is_liability_waiver_accepted_bwv = FALSE` — borrower must accept liability waiver

---

#### trg_handover_verification_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `handover_verification_hov` |
| **Purpose** | Auto-generates a unique 6-character verification code and sets 24-hour expiry |

**Business Rules:**

- Generates code using `UPPER(SUBSTRING(MD5(CONCAT(RAND(), MICROSECOND(NOW(6)), CONNECTION_ID(), UUID())), 1, 6))`
- Sets `expires_at_hov = DATE_ADD(NOW(), INTERVAL 24 HOUR)`
- Code uniqueness enforced at database level by `uq_verification_code_hov` index

---

#### trg_incident_report_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `incident_report_irt` |
| **Purpose** | Auto-calculates whether the incident was reported within the 48-hour deadline |

**Business Rules:**

- Compares `incident_occurred_at_irt` with current time using `TIMESTAMPDIFF(HOUR, ...)`
- Sets `is_reported_within_deadline_irt = FALSE` if more than 48 hours have elapsed
- Sets `is_reported_within_deadline_irt = TRUE` otherwise

---

#### trg_tos_before_insert

| Property | Value |
|----------|-------|
| **Timing** | BEFORE INSERT |
| **Table** | `terms_of_service_tos` |
| **Purpose** | Sets `created_at_tos` to current time if not provided |

**Business Rules:**

- Uses `COALESCE(NEW.created_at_tos, NOW())` to set timestamp only when missing

> **Note:** For full ToS version management (deactivating previous versions, setting superseded timestamps), use the `sp_create_tos_version` stored procedure.

---

### Lookup Table Protection Triggers

Lookup table protection triggers prevent deletion or renaming of system-required values that business logic triggers depend on. All 12 triggers follow the same pattern: a BEFORE DELETE trigger that rejects deletion of protected values, and a BEFORE UPDATE trigger that rejects renaming of protected values.

| Table | Protected Values | DELETE Trigger | UPDATE Trigger |
|-------|-----------------|----------------|----------------|
| `account_status_ast` | pending, active, suspended, deleted | `trg_account_status_before_delete` | `trg_account_status_before_update` |
| `borrow_status_bst` | requested, approved, borrowed, returned, denied, cancelled | `trg_borrow_status_before_delete` | `trg_borrow_status_before_update` |
| `block_type_btp` | admin, borrow | `trg_block_type_before_delete` | `trg_block_type_before_update` |
| `rating_role_rtr` | lender, borrower | `trg_rating_role_before_delete` | `trg_rating_role_before_update` |
| `handover_type_hot` | pickup, return | `trg_handover_type_before_delete` | `trg_handover_type_before_update` |
| `deposit_status_dps` | pending, held, released, forfeited, partial_release | `trg_deposit_status_before_delete` | `trg_deposit_status_before_update` |

**Pattern for DELETE triggers:**

```sql
IF OLD.value_column IN ('protected_value_1', 'protected_value_2') THEN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete system-required values';
END IF;
```

**Pattern for UPDATE triggers:**

```sql
IF OLD.value_column IN ('protected_value_1', 'protected_value_2')
   AND NEW.value_column != OLD.value_column THEN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot rename system-required values';
END IF;
```

> **Note:** UPDATE triggers only block renaming — other column changes (e.g., adding a description) are permitted. New values can be freely added to any lookup table.

---

## Views

> Views provide pre-built queries for common application needs. This section documents all 25 views in the schema.

### View Summary

| # | View | Purpose | Key Tables |
|---|------|---------|------------|
| 1 | `active_account_v` | Non-deleted accounts | `account_acc`, `account_status_ast` |
| 2 | `available_tool_v` | Tools truly available for borrowing | `tool_tol`, `account_acc`, `borrow_bor`, `availability_block_avb` |
| 3 | `active_borrow_v` | Currently checked-out items with due status | `borrow_bor`, `tool_tol`, `account_acc` |
| 4 | `overdue_borrow_v` | Past-due items requiring attention | `borrow_bor`, `tool_tol`, `account_acc`, `security_deposit_sdp` |
| 5 | `pending_request_v` | Borrow requests awaiting approval | `borrow_bor`, `tool_tol`, `account_acc`, `user_rating_urt` |
| 6 | `account_profile_v` | Complete member profile with aggregates | `account_acc`, `role_rol`, `account_status_ast`, `zip_code_zpc`, `neighborhood_nbh`, `user_rating_urt` |
| 7 | `tool_detail_v` | Full tool listing with availability status | `tool_tol`, `account_acc`, `tool_rating_trt`, `borrow_bor`, `category_cat` |
| 8 | `user_reputation_v` | Live reputation scores (computed) | `account_acc`, `user_rating_urt`, `tool_tol`, `borrow_bor` |
| 9 | `tool_statistics_v` | Live tool usage statistics (computed) | `tool_tol`, `tool_rating_trt`, `borrow_bor`, `incident_report_irt` |
| 10 | `neighborhood_summary_v` | Live neighborhood statistics (computed) | `neighborhood_nbh`, `account_acc`, `tool_tol`, `borrow_bor`, `event_evt` |
| 11 | `open_dispute_v` | Active disputes requiring attention | `dispute_dsp`, `borrow_bor`, `account_acc`, `dispute_message_dsm` |
| 12 | `pending_deposit_v` | Deposits in pending/held status | `security_deposit_sdp`, `borrow_bor`, `account_acc`, `payment_provider_ppv` |
| 13 | `current_tos_v` | Currently active Terms of Service | `terms_of_service_tos` |
| 14 | `tos_acceptance_required_v` | Users who need to accept current ToS | `account_acc`, `terms_of_service_tos`, `tos_acceptance_tac` |
| 15 | `pending_waiver_v` | Approved borrows missing waivers | `borrow_bor`, `tool_tol`, `account_acc`, `borrow_waiver_bwv` |
| 16 | `open_incident_v` | Unresolved incident reports | `incident_report_irt`, `borrow_bor`, `tool_tol`, `account_acc`, `incident_photo_iph` |
| 17 | `pending_handover_v` | Handover verifications awaiting completion | `handover_verification_hov`, `borrow_bor`, `tool_tol`, `account_acc` |
| 18 | `unread_notification_v` | Unread notifications per user | `notification_ntf`, `notification_type_ntt` |
| 19 | `user_bookmarks_v` | User bookmark list with tool details | `tool_bookmark_acctol`, `tool_tol`, `account_acc`, `tool_image_tim` |
| 20 | `category_summary_v` | Live category statistics (computed) | `category_cat`, `tool_category_tolcat`, `tool_tol`, `tool_rating_trt`, `borrow_bor` |
| 21 | `upcoming_event_v` | Future community events | `event_evt`, `neighborhood_nbh`, `account_acc` |
| 22 | `neighborhood_summary_fast_v` | Fast dashboard read from materialized table | `neighborhood_summary_mat` |
| 23 | `user_reputation_fast_v` | Fast profile read from materialized table | `user_reputation_mat` |
| 24 | `tool_statistics_fast_v` | Fast analytics read from materialized table | `tool_statistics_mat` |
| 25 | `category_summary_fast_v` | Fast browse page read from materialized table | `category_summary_mat` |

### Status & Availability Views

#### active_account_v

| Property | Value |
|----------|-------|
| **Purpose** | Excludes soft-deleted accounts from all application reads |
| **Tables** | `account_acc`, `account_status_ast` |
| **Filter** | `id_ast_acc != (SELECT id_ast WHERE status_name_ast = 'deleted')` |
| **Use Case** | Default view for all user-facing queries; admin queries use the base table directly |

**Key Columns:** All columns from `account_acc`

---

#### available_tool_v

| Property | Value |
|----------|-------|
| **Purpose** | Shows only tools truly available for borrowing (owner intent + no active borrows + no active blocks) |
| **Tables** | `tool_tol`, `account_acc`, `tool_condition_tcd`, `tool_image_tim`, `neighborhood_nbh`, `borrow_bor`, `availability_block_avb` |
| **Filter** | `is_available_tol = TRUE` AND owner not deleted AND no active borrow AND no active availability block |
| **Use Case** | Browse/search page for borrowers looking for tools |

**Key Columns:**

| Column | Source | Notes |
|--------|--------|-------|
| `owner_name` | Computed | `CONCAT(first_name, ' ', last_name)` |
| `owner_zip` | `account_acc` | For proximity filtering |
| `tool_condition` | `tool_condition_tcd` | Resolved condition name |
| `primary_image` | `tool_image_tim` | Primary image file name |
| `owner_neighborhood` | `neighborhood_nbh` | For neighborhood filtering |

---

#### active_borrow_v

| Property | Value |
|----------|-------|
| **Purpose** | Shows currently checked-out items with computed due-date status |
| **Tables** | `borrow_bor`, `tool_tol`, `account_acc` (x2 for borrower and lender) |
| **Filter** | `id_bst_bor = 'borrowed'` status only |
| **Use Case** | Dashboard for lenders and borrowers to track active loans |

**Key Computed Columns:**

| Column | Computation | Notes |
|--------|-------------|-------|
| `hours_until_due` | `TIMESTAMPDIFF(HOUR, NOW(), due_at_bor)` | Negative when overdue |
| `due_status` | CASE expression | `'OVERDUE'`, `'DUE SOON'` (<=24h), or `'ON TIME'` |

---

#### overdue_borrow_v

| Property | Value |
|----------|-------|
| **Purpose** | Past-due items requiring attention, with deposit information |
| **Tables** | `borrow_bor`, `tool_tol`, `account_acc` (x2), `security_deposit_sdp` |
| **Filter** | Status = `'borrowed'` AND `due_at_bor < NOW()` |
| **Use Case** | Admin dashboard for overdue item management; input for overdue notification procedures |

**Key Computed Columns:**

| Column | Computation | Notes |
|--------|-------------|-------|
| `hours_overdue` | `TIMESTAMPDIFF(HOUR, due_at_bor, NOW())` | Hours past due |
| `days_overdue` | `TIMESTAMPDIFF(DAY, due_at_bor, NOW())` | Days past due |
| `deposit_held` | `security_deposit_sdp.amount_sdp` | Deposit amount at risk |

---

#### pending_request_v

| Property | Value |
|----------|-------|
| **Purpose** | Borrow requests awaiting lender approval, with borrower reputation data |
| **Tables** | `borrow_bor`, `tool_tol`, `account_acc` (x2), `user_rating_urt` (subquery) |
| **Filter** | Status = `'requested'` |
| **Use Case** | Lender inbox for reviewing and approving/denying borrow requests |

**Key Computed Columns:**

| Column | Computation | Notes |
|--------|-------------|-------|
| `hours_pending` | `TIMESTAMPDIFF(HOUR, requested_at_bor, NOW())` | Time since request |
| `borrower_avg_rating` | Subquery AVG on `user_rating_urt` | Borrower's overall rating |
| `borrower_rating_count` | Subquery COUNT on `user_rating_urt` | Number of ratings received |

---

### Profile & Detail Views

#### account_profile_v

| Property | Value |
|----------|-------|
| **Purpose** | Complete member profile with resolved lookups, location, images, bio, and aggregate statistics |
| **Tables** | `account_acc`, `role_rol`, `account_status_ast`, `contact_preference_cpr`, `zip_code_zpc`, `neighborhood_nbh`, `state_sta`, `account_image_aim`, `account_bio_abi`, `tool_tol`, `user_rating_urt`, `rating_role_rtr` |
| **Filter** | Excludes deleted accounts |
| **Use Case** | User profile pages; member directory |

**Key Computed Columns:**

| Column | Computation | Notes |
|--------|-------------|-------|
| `full_name` | `CONCAT(first_name, ' ', last_name)` | Display name |
| `state_code_sta` | `COALESCE(neighborhood state, ZIP fallback state)` | Resolves state via neighborhood or ZIP |
| `active_tool_count` | Subquery COUNT on `tool_tol` | Tools listed by this user |
| `lender_rating` | Subquery AVG filtered by `'lender'` role | Average lender rating |
| `borrower_rating` | Subquery AVG filtered by `'borrower'` role | Average borrower rating |

> **Note:** Uses a fallback chain for state resolution: first tries the user's assigned neighborhood, then falls back to the primary neighborhood for their ZIP code.

---

#### tool_detail_v

| Property | Value |
|----------|-------|
| **Purpose** | Complete tool listing with owner info, ratings, borrow history, categories, and real-time availability status |
| **Tables** | `tool_tol`, `tool_condition_tcd`, `account_acc`, `neighborhood_nbh`, `state_sta`, `tool_image_tim`, `tool_rating_trt`, `borrow_bor`, `tool_category_tolcat`, `category_cat`, `availability_block_avb` |
| **Filter** | None (shows all tools including unlisted) |
| **Use Case** | Tool detail pages; admin tool management |

**Key Computed Columns:**

| Column | Computation | Notes |
|--------|-------------|-------|
| `categories` | `GROUP_CONCAT(category_name_cat)` | Comma-separated category names |
| `availability_status` | CASE expression | `'UNLISTED'`, `'BORROWED'`, `'BLOCKED'`, or `'AVAILABLE'` |
| `avg_rating` | Subquery AVG on `tool_rating_trt` | Tool's average rating |
| `completed_borrow_count` | Subquery COUNT on `borrow_bor` | Number of completed borrows |

---

### Reputation & Statistics Views

#### user_reputation_v

| Property | Value |
|----------|-------|
| **Purpose** | Aggregated user reputation with separate lender/borrower ratings, overall average, tools owned, and completed borrows |
| **Tables** | `account_acc`, `account_status_ast`, `user_rating_urt`, `rating_role_rtr`, `tool_tol`, `borrow_bor` |
| **Filter** | Excludes deleted accounts |
| **Use Case** | Community leaderboards; trust scores; member directory ranking |

**Key Computed Columns:** `lender_avg_rating`, `borrower_avg_rating`, `overall_avg_rating` (weighted average of both roles), `total_rating_count`, `tools_owned`, `completed_borrows`

---

#### tool_statistics_v

| Property | Value |
|----------|-------|
| **Purpose** | Comprehensive tool usage statistics including ratings, borrow breakdowns by status, total hours borrowed, and incident count |
| **Tables** | `tool_tol`, `account_acc`, `tool_condition_tcd`, `tool_rating_trt`, `borrow_bor`, `borrow_status_bst`, `incident_report_irt` |
| **Filter** | None (shows all tools) |
| **Use Case** | Admin analytics; tool performance dashboards; search ranking |

**Key Computed Columns:** `avg_rating`, `five_star_count`, `total_borrows`, `completed_borrows`, `cancelled_borrows`, `denied_borrows`, `total_hours_borrowed`, `last_borrowed_at`, `incident_count`

---

### Neighborhood & Dispute Views

#### neighborhood_summary_v

| Property | Value |
|----------|-------|
| **Purpose** | Live community statistics: members, tools, active borrows, upcoming events, and associated ZIP codes |
| **Tables** | `neighborhood_nbh`, `state_sta`, `account_acc`, `tool_tol`, `borrow_bor`, `event_evt`, `neighborhood_zip_nbhzpc` |
| **Filter** | None (shows all neighborhoods) |
| **Use Case** | Community landing pages; neighborhood comparison; geographic analytics |

**Key Computed Columns:** `total_members`, `active_members`, `verified_members`, `total_tools`, `available_tools`, `active_borrows`, `completed_borrows_30d`, `upcoming_events`, `zip_codes` (comma-separated list)

---

#### open_dispute_v

| Property | Value |
|----------|-------|
| **Purpose** | Unresolved disputes with full context: participants, message count, related incidents, and deposit info |
| **Tables** | `dispute_dsp`, `account_acc` (x3), `borrow_bor`, `tool_tol`, `dispute_message_dsm`, `incident_report_irt`, `security_deposit_sdp`, `deposit_status_dps` |
| **Filter** | `status = 'open'` only |
| **Use Case** | Admin dispute resolution dashboard |

**Key Computed Columns:** `days_open`, `message_count`, `last_message_at`, `related_incidents`, `deposit_amount`, `deposit_status`

---

### Financial & Legal Views

#### pending_deposit_v

| Property | Value |
|----------|-------|
| **Purpose** | Deposits currently held in escrow with action-required classification |
| **Tables** | `security_deposit_sdp`, `deposit_status_dps`, `payment_provider_ppv`, `borrow_bor`, `borrow_status_bst`, `tool_tol`, `account_acc`, `incident_report_irt` |
| **Filter** | Deposit status = `'held'` only |
| **Use Case** | Admin financial dashboard; escrow management |

**Key Computed Columns:**

| Column | Values |
|--------|--------|
| `days_held` | Days since deposit was captured |
| `action_required` | `'READY FOR RELEASE'`, `'OVERDUE - REVIEW NEEDED'`, `'ACTIVE BORROW'`, or `'REVIEW NEEDED'` |

---

#### current_tos_v

| Property | Value |
|----------|-------|
| **Purpose** | Currently active Terms of Service version with acceptance count |
| **Tables** | `terms_of_service_tos`, `account_acc`, `tos_acceptance_tac` |
| **Filter** | `is_active_tos = TRUE` AND `superseded_at_tos IS NULL` |
| **Use Case** | ToS display page; admin compliance dashboard |

**Key Computed Columns:** `created_by_name`, `total_acceptances`

---

#### tos_acceptance_required_v

| Property | Value |
|----------|-------|
| **Purpose** | Active users who have not yet accepted the current ToS version |
| **Tables** | `account_acc`, `account_status_ast`, `terms_of_service_tos`, `tos_acceptance_tac` |
| **Filter** | Active accounts only; excludes those who have already accepted current ToS |
| **Use Case** | Compliance gate enforcement; admin notification targeting |

**Key Computed Columns:** `last_tos_accepted_at`, `last_accepted_version`

---

#### pending_waiver_v

| Property | Value |
|----------|-------|
| **Purpose** | Approved borrows that are missing a signed waiver — compliance gate before pickup |
| **Tables** | `borrow_bor`, `tool_tol`, `account_acc`, `borrow_waiver_bwv` |
| **Filter** | Borrow status = `'approved'` AND no matching waiver exists |
| **Use Case** | Compliance workflow; prevents pickup without signed waiver |

**Key Computed Columns:** `hours_since_approval`, `preexisting_conditions_tol`, `is_deposit_required_tol`

---

### Operational Views

#### open_incident_v

| Property | Value |
|----------|-------|
| **Purpose** | Unresolved incident reports with full borrow/tool context, deposit info, and related disputes |
| **Tables** | `incident_report_irt`, `incident_type_ity`, `account_acc` (x3), `borrow_bor`, `tool_tol`, `security_deposit_sdp`, `deposit_status_dps`, `dispute_dsp` |
| **Filter** | `resolved_at_irt IS NULL` |
| **Use Case** | Admin incident management dashboard |

**Key Computed Columns:** `days_open`, `incident_type`, `is_reported_within_deadline_irt`, `related_disputes`, `deposit_amount`, `deposit_status`

---

#### pending_handover_v

| Property | Value |
|----------|-------|
| **Purpose** | Handover verification codes that have been generated but not yet confirmed |
| **Tables** | `handover_verification_hov`, `handover_type_hot`, `account_acc` (x3), `borrow_bor`, `borrow_status_bst`, `tool_tol` |
| **Filter** | `verified_at_hov IS NULL` |
| **Use Case** | Operational status tracking; expired code cleanup |

**Key Computed Columns:**

| Column | Values |
|--------|--------|
| `hours_until_expiry` | Hours until code expires |
| `code_status` | `'EXPIRED'`, `'EXPIRING SOON'` (<=2h), or `'ACTIVE'` |

---

#### unread_notification_v

| Property | Value |
|----------|-------|
| **Purpose** | Unread notifications with resolved notification type and optional borrow context |
| **Tables** | `notification_ntf`, `account_acc`, `notification_type_ntt`, `borrow_bor`, `tool_tol`, `borrow_status_bst` |
| **Filter** | `is_read_ntf = FALSE` |
| **Use Case** | User notification feed; notification badge count |

**Key Computed Columns:** `hours_ago`, `notification_type`, `related_tool_name`, `related_borrow_status`

---

#### user_bookmarks_v

| Property | Value |
|----------|-------|
| **Purpose** | User's saved/favorited tools with current availability status and ratings |
| **Tables** | `tool_bookmark_acctol`, `account_acc` (x2), `tool_tol`, `tool_condition_tcd`, `tool_image_tim`, `neighborhood_nbh`, `borrow_bor`, `availability_block_avb`, `tool_rating_trt` |
| **Filter** | None (shows all bookmarks) |
| **Use Case** | User's "My Saved Tools" page |

**Key Computed Columns:**

| Column | Values |
|--------|--------|
| `availability_status` | `'OWNER DELETED'`, `'UNLISTED'`, `'UNAVAILABLE'`, `'BLOCKED'`, or `'AVAILABLE'` |

---

### Aggregate & Reporting Views

#### category_summary_v

| Property | Value |
|----------|-------|
| **Purpose** | Tool counts and statistics aggregated by category, including real-time availability |
| **Tables** | `category_cat`, `vector_image_vec`, `tool_category_tolcat`, `tool_tol`, `account_acc`, `borrow_bor`, `availability_block_avb`, `tool_rating_trt` |
| **Filter** | None (shows all categories) |
| **Use Case** | Browse/filter pages; category navigation with tool counts |

**Key Computed Columns:** `total_tools`, `listed_tools`, `available_tools`, `category_avg_rating`, `total_completed_borrows`, `min_rental_fee`, `max_rental_fee`, `avg_rental_fee`

---

#### upcoming_event_v

| Property | Value |
|----------|-------|
| **Purpose** | Future community events with timing classification and neighborhood context |
| **Tables** | `event_evt`, `neighborhood_nbh`, `state_sta`, `account_acc` (x2 for creator and updater) |
| **Filter** | Events where `start_at >= NOW()` or `end_at >= NOW()` (includes currently happening events) |
| **Use Case** | Community events calendar; neighborhood engagement features |

**Key Computed Columns:**

| Column | Values |
|--------|--------|
| `days_until_event` | Days from now until event start |
| `event_timing` | `'HAPPENING NOW'`, `'THIS WEEK'`, `'THIS MONTH'`, or `'UPCOMING'` |

---

### Fast Materialized Views

These four views are simple `SELECT *` wrappers around the materialized cache tables. They provide a consistent view interface for dashboard queries when near-real-time data is acceptable (data freshness depends on scheduled refresh intervals).

| View | Source Table | Refresh Interval | Use Case |
|------|-------------|-------------------|----------|
| `neighborhood_summary_fast_v` | `neighborhood_summary_mat` | Every 1 hour | Neighborhood dashboards |
| `user_reputation_fast_v` | `user_reputation_mat` | Every 4 hours | User profiles, leaderboards |
| `tool_statistics_fast_v` | `tool_statistics_mat` | Every 2 hours | Tool analytics, search ranking |
| `category_summary_fast_v` | `category_summary_mat` | Every 1 hour | Category browse pages |

> **Note:** Use `*_fast_v` views for high-traffic dashboard pages. Use the live `*_v` views (e.g., `user_reputation_v`) when real-time accuracy is required. Each fast view includes a `refreshed_at` timestamp so the application can display data freshness to users.

---

## Stored Procedures

> Stored procedures encapsulate complex business logic and multi-step operations. This section documents all 25 stored procedures in the schema.

### Procedure Summary

| # | Procedure | Category | Purpose |
|---|-----------|----------|---------|
| 1 | `sp_create_tos_version` | ToS Management | Atomically deactivate previous ToS and insert new version |
| 2 | `sp_extend_loan` | Loan Extension | Extend borrow due date with audit trail |
| 3 | `sp_refresh_neighborhood_summary` | Materialized Refresh | Rebuild `neighborhood_summary_mat` cache |
| 4 | `sp_refresh_user_reputation` | Materialized Refresh | Rebuild `user_reputation_mat` cache |
| 5 | `sp_refresh_tool_statistics` | Materialized Refresh | Rebuild `tool_statistics_mat` cache |
| 6 | `sp_refresh_category_summary` | Materialized Refresh | Rebuild `category_summary_mat` cache |
| 7 | `sp_refresh_platform_daily_stat` | Materialized Refresh | Rebuild `platform_daily_stat_pds` for current day |
| 8 | `sp_refresh_all_summaries` | Materialized Refresh | Master procedure — calls all 5 refresh procedures |
| 9 | `sp_create_borrow_request` | Borrow Workflow | Validate availability and create borrow request |
| 10 | `sp_approve_borrow_request` | Borrow Workflow | Approve pending request, generate handover code |
| 11 | `sp_deny_borrow_request` | Borrow Workflow | Deny pending request with reason |
| 12 | `sp_complete_pickup` | Borrow Workflow | Record tool pickup via handover verification |
| 13 | `sp_complete_return` | Borrow Workflow | Record tool return via handover verification |
| 14 | `sp_cancel_borrow_request` | Borrow Workflow | Cancel request (borrower-initiated) |
| 15 | `sp_rate_user` | Rating | Submit a user-to-user rating after borrow |
| 16 | `sp_rate_tool` | Rating | Submit a tool rating after borrow |
| 17 | `sp_send_notification` | Notification | Create a notification for a specific account |
| 18 | `sp_mark_notifications_read` | Notification | Bulk-mark notifications as read for an account |
| 19 | `sp_send_overdue_notifications` | Notification | Batch-send overdue alerts to borrowers and lenders |
| 20 | `sp_cleanup_expired_handover_codes` | Maintenance | Expire handover codes past their expiry window |
| 21 | `sp_archive_old_notifications` | Maintenance | Archive read notifications older than threshold |
| 22 | `sp_cleanup_old_search_logs` | Maintenance | Purge search logs older than retention period |
| 23 | `sp_release_deposit_on_return` | Deposit | Release or forfeit security deposit on borrow return |
| 24 | `sp_search_available_tools` | Search & Query | Full-text search of available tools with filters |
| 25 | `sp_get_user_borrow_history` | Search & Query | Paginated borrow history for a specific account |

### 11.1 ToS & Loan Extension Procedures

#### `sp_create_tos_version`

Creates a new Terms of Service version atomically. Deactivates the current active version and inserts the new one within a single transaction.

| Property | Value |
|----------|-------|
| **Category** | ToS Management |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Error Handling** | `EXIT HANDLER FOR SQLEXCEPTION` → `ROLLBACK` + `RESIGNAL` |
| **Tables Modified** | `terms_of_service_tos` (UPDATE existing, INSERT new) |

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `p_version` | `VARCHAR(20)` | Version identifier (e.g., `'2.0'`) |
| `p_title` | `VARCHAR(255)` | Title of the new ToS |
| `p_content` | `TEXT` | Full legal content |
| `p_summary` | `TEXT` | Human-readable summary of changes |
| `p_effective_at` | `TIMESTAMP` | When the new version takes effect |
| `p_created_by` | `INT` | `id_acc` of the admin creating the version |

**Logic Steps:**

1. Deactivate all currently active ToS records (`is_active_tos = FALSE`, set `superseded_at_tos`)
2. Insert new ToS record with `is_active_tos = TRUE`
3. Commit transaction

**Usage:**

```sql
CALL sp_create_tos_version('2.0', 'Updated Terms', 'Full text...', 'Summary...', NOW(), 5);
```

#### `sp_extend_loan`

Extends a borrow's due date by a specified number of hours. Creates an audit record in `loan_extension_lex` before updating the borrow record. Uses `FOR UPDATE` row locking to prevent race conditions.

| Property | Value |
|----------|-------|
| **Category** | Loan Extension |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Error Handling** | `EXIT HANDLER FOR SQLEXCEPTION` → `ROLLBACK` + `RESIGNAL` |
| **Tables Modified** | `loan_extension_lex` (INSERT), `borrow_bor` (UPDATE) |
| **Row Locking** | `SELECT ... FOR UPDATE` on the borrow record |

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `p_bor_id` | `INT` | `id_bor` of the borrow to extend |
| `p_extra_hours` | `INT` | Number of hours to add to the due date |
| `p_reason` | `TEXT` | Reason for the extension |
| `p_approved_by` | `INT` | `id_acc` of the approving admin/lender |

**Logic Steps:**

1. Lock the borrow row and read the current `due_at_bor`
2. **Validate:** If `due_at_bor` is `NULL`, signal error `45000` — `'Cannot extend: borrow has no due date set'`
3. Calculate `v_new_due = due_at_bor + p_extra_hours HOUR`
4. Insert audit record into `loan_extension_lex` (original due date, new due date, hours, reason, approver)
5. Update `borrow_bor.due_at_bor` to the new due date
6. Commit transaction

**Usage:**

```sql
CALL sp_extend_loan(2, 48, 'Borrower needs extra weekend for project', 1);
```

### 11.2 Materialized View Refresh Procedures

These procedures implement a "truncate-and-reload" pattern for materialized cache tables. Each procedure:

- Wraps operations in a transaction with `EXIT HANDLER FOR SQLEXCEPTION` → `ROLLBACK` + `RESIGNAL`
- Deletes all existing rows from the target table (`DELETE ... LIMIT 999999999`)
- Rebuilds the table from live source data via a complex aggregation query
- Sets `refreshed_at` to `NOW()`

> **Why `DELETE ... LIMIT` instead of `TRUNCATE`?** MySQL's `TRUNCATE TABLE` performs an implicit commit and cannot be rolled back. Using `DELETE` with a high limit keeps the operation within the transaction, so on failure the old data is preserved.

#### `sp_refresh_neighborhood_summary`

Rebuilds the `neighborhood_summary_mat` table with current neighborhood metrics.

| Property | Value |
|----------|-------|
| **Target Table** | `neighborhood_summary_mat` |
| **Source Tables** | `neighborhood_nbh`, `state_sta`, `account_acc`, `account_status_ast`, `tool_tol`, `borrow_bor`, `borrow_status_bst`, `event_evt`, `neighborhood_zip_nbhzpc` |
| **Refresh Strategy** | Full rebuild (delete all + insert) |
| **Called By** | `sp_refresh_all_summaries`, `evt_refresh_neighborhood_summary` |

**Computed Columns:**

| Column | Aggregation |
|--------|-------------|
| `total_members` | Count of non-deleted accounts in neighborhood |
| `active_members` | Count where `status_name_ast = 'active'` |
| `verified_members` | Count where `is_verified_acc = TRUE` |
| `total_tools` | Count of tools owned by neighborhood members |
| `available_tools` | Count where `is_available_tol = TRUE` |
| `active_borrows` | Count where `status_name_bst = 'borrowed'` |
| `completed_borrows_30d` | Count of returns within last 30 days |
| `upcoming_events` | Count where `start_at_evt > NOW()` |
| `zip_codes` | `GROUP_CONCAT` of all ZIP codes (primary first) |

#### `sp_refresh_user_reputation`

Rebuilds the `user_reputation_mat` table with current user reputation metrics.

| Property | Value |
|----------|-------|
| **Target Table** | `user_reputation_mat` |
| **Source Tables** | `account_acc`, `account_status_ast`, `user_rating_urt`, `rating_role_rtr`, `tool_tol`, `borrow_bor`, `borrow_status_bst` |
| **Refresh Strategy** | Full rebuild (delete all + insert) |
| **Filter** | Excludes deleted accounts |
| **Called By** | `sp_refresh_all_summaries`, `evt_refresh_user_reputation` |

**Computed Columns:**

| Column | Aggregation |
|--------|-------------|
| `full_name` | `CONCAT(first_name_acc, ' ', last_name_acc)` |
| `lender_avg_rating` | `AVG(score_urt)` where `role_name_rtr = 'lender'` |
| `lender_rating_count` | Count of lender ratings received |
| `borrower_avg_rating` | `AVG(score_urt)` where `role_name_rtr = 'borrower'` |
| `borrower_rating_count` | Count of borrower ratings received |
| `overall_avg_rating` | Average of non-null role averages (handles one-role users) |
| `total_rating_count` | Sum of lender + borrower rating counts |
| `tools_owned` | Count of tools in `tool_tol` |
| `completed_borrows` | Count where `status_name_bst = 'returned'` |

#### `sp_refresh_tool_statistics`

Rebuilds the `tool_statistics_mat` table with current tool performance metrics.

| Property | Value |
|----------|-------|
| **Target Table** | `tool_statistics_mat` |
| **Source Tables** | `tool_tol`, `account_acc`, `tool_condition_tcd`, `tool_rating_trt`, `borrow_bor`, `borrow_status_bst`, `incident_report_irt` |
| **Refresh Strategy** | Full rebuild (delete all + insert) |
| **Called By** | `sp_refresh_all_summaries`, `evt_refresh_tool_statistics` |

**Computed Columns:**

| Column | Aggregation |
|--------|-------------|
| `owner_name` | `CONCAT(first_name_acc, ' ', last_name_acc)` |
| `tool_condition` | Resolved via `tool_condition_tcd` lookup |
| `avg_rating` | `AVG(score_trt)` from `tool_rating_trt` |
| `rating_count` | Count of tool ratings |
| `five_star_count` | Count where `score_trt = 5` |
| `total_borrows` | Count of all borrows for the tool |
| `completed_borrows` | Count where `status_name_bst = 'returned'` |
| `cancelled_borrows` | Count where `status_name_bst = 'cancelled'` |
| `denied_borrows` | Count where `status_name_bst = 'denied'` |
| `total_hours_borrowed` | `SUM(loan_duration_hours_bor)` for completed borrows |
| `last_borrowed_at` | `MAX(borrowed_at_bor)` |
| `incident_count` | Count of related incident reports |

#### `sp_refresh_category_summary`

Rebuilds the `category_summary_mat` table with current category-level metrics.

| Property | Value |
|----------|-------|
| **Target Table** | `category_summary_mat` |
| **Source Tables** | `category_cat`, `vector_image_vec`, `tool_category_tolcat`, `tool_tol`, `account_acc`, `account_status_ast`, `borrow_bor`, `borrow_status_bst`, `availability_block_avb`, `tool_rating_trt` |
| **Refresh Strategy** | Full rebuild (delete all + insert) |
| **Called By** | `sp_refresh_all_summaries`, `evt_refresh_category_summary` |

**Computed Columns:**

| Column | Aggregation |
|--------|-------------|
| `category_icon` | `file_name_vec` from `vector_image_vec` |
| `total_tools` | `COUNT(DISTINCT id_tol_tolcat)` |
| `listed_tools` | Count where `is_available_tol = TRUE` |
| `available_tools` | Count where available, owner not deleted, no active borrow, no active availability block |
| `category_avg_rating` | `AVG` of per-tool average ratings |
| `total_completed_borrows` | Sum of completed borrows across tools |
| `min_rental_fee` / `max_rental_fee` / `avg_rental_fee` | `MIN` / `MAX` / `AVG` of `rental_fee_tol` |

> **Availability Logic:** A tool is counted as "available" only when `is_available_tol = TRUE` AND the owner is not deleted AND no active borrow exists (status in `requested`, `approved`, `borrowed`) AND no active availability block covers `NOW()`.

#### `sp_refresh_platform_daily_stat`

Rebuilds the `platform_daily_stat_pds` row for the current day with platform-wide KPIs.

| Property | Value |
|----------|-------|
| **Target Table** | `platform_daily_stat_pds` |
| **Source Tables** | `account_acc`, `account_status_ast`, `tool_tol`, `borrow_bor`, `borrow_status_bst`, `dispute_dsp`, `dispute_status_dst`, `incident_report_irt`, `security_deposit_sdp`, `deposit_status_dps` |
| **Refresh Strategy** | Delete today's row only + insert replacement |
| **Called By** | `sp_refresh_all_summaries`, `evt_refresh_platform_daily_stat` |

**Computed Columns:**

| Column | Source |
|--------|--------|
| `total_accounts_pds` | Count of non-deleted accounts |
| `active_accounts_pds` | Count where `status_name_ast = 'active'` |
| `new_accounts_today_pds` | Count where `created_at_acc = today` |
| `total_tools_pds` | Total tool count |
| `available_tools_pds` | Count where `is_available_tol = TRUE` |
| `new_tools_today_pds` | Count where `created_at_tol = today` |
| `active_borrows_pds` | Count where `status_name_bst = 'borrowed'` |
| `completed_today_pds` | Count where returned today |
| `new_requests_today_pds` | Count where `requested_at_bor = today` |
| `open_disputes_pds` | Count where `status_name_dst = 'open'` |
| `open_incidents_pds` | Count where `resolved_at_irt IS NULL` |
| `overdue_borrows_pds` | Count where borrowed and `due_at_bor < NOW()` |
| `deposits_held_total_pds` | `SUM(amount_sdp)` where `status_name_dps = 'held'` |

> **Note:** Unlike other refresh procedures that delete all rows, this procedure only replaces the row for `CURDATE()`, preserving historical daily statistics.

#### `sp_refresh_all_summaries`

Master orchestration procedure that calls all five individual refresh procedures in sequence.

| Property | Value |
|----------|-------|
| **Category** | Materialized Refresh — Master |
| **Transaction** | No — each child procedure manages its own transaction |
| **Called By** | `evt_refresh_all_summaries` (weekly) |

**Execution Order:**

1. `CALL sp_refresh_neighborhood_summary()`
2. `CALL sp_refresh_user_reputation()`
3. `CALL sp_refresh_tool_statistics()`
4. `CALL sp_refresh_category_summary()`
5. `CALL sp_refresh_platform_daily_stat()`

> **Design Note:** Each child procedure has its own `START TRANSACTION` / `COMMIT` block, so a failure in one does not roll back previously completed refreshes. The master procedure does not wrap the calls in an additional transaction.

### 11.3 Borrow Workflow Procedures

These six procedures implement the complete borrow lifecycle as a state machine. Each procedure validates the current status before transitioning, uses `FOR UPDATE` row locking for concurrency safety, and returns both a success flag and a descriptive error message via `OUT` parameters.

**Borrow Lifecycle State Machine:**

```
 ┌──────────┐     approve      ┌──────────┐     pickup      ┌──────────┐     return      ┌──────────┐
 │ requested├────────────────►│ approved ├───────────────►│ borrowed ├──────────────►│ returned │
 └────┬─────┘                 └────┬─────┘                └──────────┘               └──────────┘
      │ deny                       │ cancel
      ▼                            ▼
 ┌──────────┐               ┌───────────┐
 │  denied  │               │ cancelled │
 └──────────┘               └───────────┘
```

> **Note:** Cancel is allowed from both `requested` and `approved` states (by either party). Deny is only from `requested` (by the owner).

#### `sp_create_borrow_request`

Creates a new borrow request with comprehensive validation.

| Property | Value |
|----------|-------|
| **Category** | Borrow Workflow |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Error Handling** | `EXIT HANDLER` → captures `MESSAGE_TEXT` into `p_error_message` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `tool_tol` row |
| **Tables Modified** | `borrow_bor` (INSERT) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_tool_id` | `IN` | `INT` | Tool to borrow |
| `p_borrower_id` | `IN` | `INT` | Borrower's `id_acc` |
| `p_loan_duration_hours` | `IN` | `INT` | Requested loan duration in hours |
| `p_notes` | `IN` | `TEXT` | Optional notes from borrower |
| `p_borrow_id` | `OUT` | `INT` | Newly created `id_bor` (or `NULL` on failure) |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description (or `NULL` on success) |

**Validation Rules:**

1. Tool must exist
2. Borrower cannot borrow their own tool
3. Tool must pass `fn_is_tool_available()` — listed, no active borrow, no active block
4. Borrower account must exist and not be deleted
5. Tool owner account must not be deleted

**Usage:**

```sql
CALL sp_create_borrow_request(5, 2, 168, 'Need for weekend project', @borrow_id, @error);
```

#### `sp_approve_borrow_request`

Approves a pending borrow request. Only the tool owner can approve.

| Property | Value |
|----------|-------|
| **Required Status** | `requested` → `approved` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `borrow_bor` joined with `tool_tol` |
| **Tables Modified** | `borrow_bor` (UPDATE — status + `approved_at_bor`) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow request to approve |
| `p_approver_id` | `IN` | `INT` | Must match the tool owner's `id_acc` |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if approved |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Validation Rules:**

1. Borrow request must exist
2. Approver must be the tool owner
3. Current status must be `requested`

**Usage:**

```sql
CALL sp_approve_borrow_request(1, 3, @success, @error);
```

#### `sp_deny_borrow_request`

Denies a pending borrow request with a reason. Only the tool owner can deny.

| Property | Value |
|----------|-------|
| **Required Status** | `requested` → `denied` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `borrow_bor` joined with `tool_tol` |
| **Tables Modified** | `borrow_bor` (UPDATE — status + append reason to `notes_text_bor`) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow request to deny |
| `p_denier_id` | `IN` | `INT` | Must match the tool owner's `id_acc` |
| `p_reason` | `IN` | `TEXT` | Reason for denial |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if denied |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Note:** The denial reason is appended to `notes_text_bor` prefixed with `[DENIED]`.

**Usage:**

```sql
CALL sp_deny_borrow_request(1, 3, 'Tool needed for personal use', @success, @error);
```

#### `sp_complete_pickup`

Records tool pickup, transitioning from `approved` to `borrowed`. Calculates the due date and creates an availability block for the borrow period.

| Property | Value |
|----------|-------|
| **Required Status** | `approved` → `borrowed` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `borrow_bor` |
| **Tables Modified** | `borrow_bor` (UPDATE — status, `borrowed_at_bor`, `due_at_bor`), `availability_block_avb` (INSERT) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow request to mark as picked up |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if pickup recorded |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Logic Steps:**

1. Verify status is `approved`
2. Calculate `due_at = NOW() + loan_duration_hours HOUR`
3. Update borrow: set `borrowed_at_bor`, `due_at_bor`, change status to `borrowed`
4. Insert availability block (type: `borrow`) spanning `NOW()` to `due_at`

**Usage:**

```sql
CALL sp_complete_pickup(1, @success, @error);
```

#### `sp_complete_return`

Records tool return, transitioning from `borrowed` to `returned`. Removes the availability block.

| Property | Value |
|----------|-------|
| **Required Status** | `borrowed` → `returned` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `borrow_bor` |
| **Tables Modified** | `borrow_bor` (UPDATE — status, `returned_at_bor`), `availability_block_avb` (DELETE) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow to mark as returned |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if return recorded |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Logic Steps:**

1. Verify status is `borrowed`
2. Update borrow: set `returned_at_bor = NOW()`, change status to `returned`
3. Delete the availability block for this borrow (`id_bor_avb = p_borrow_id`)

**Usage:**

```sql
CALL sp_complete_return(1, @success, @error);
```

#### `sp_cancel_borrow_request`

Cancels a borrow request. Allowed from `requested` or `approved` status by either the borrower or the tool owner.

| Property | Value |
|----------|-------|
| **Required Status** | `requested` or `approved` → `cancelled` |
| **Row Locking** | `SELECT ... FOR UPDATE` on `borrow_bor` joined with `tool_tol` |
| **Tables Modified** | `borrow_bor` (UPDATE — status, `cancelled_at_bor`, append reason to `notes_text_bor`) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow request to cancel |
| `p_canceller_id` | `IN` | `INT` | Must be either borrower or tool owner |
| `p_reason` | `IN` | `TEXT` | Reason for cancellation |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if cancelled |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Validation Rules:**

1. Borrow request must exist
2. Canceller must be either the borrower or the tool owner
3. Current status must be `requested` or `approved`

**Note:** The cancellation reason is appended to `notes_text_bor` prefixed with `[CANCELLED]`.

**Usage:**

```sql
CALL sp_cancel_borrow_request(1, 2, 'Change of plans', @success, @error);
```

### 11.4 Rating Procedures

#### `sp_rate_user`

Submits a user-to-user rating after a completed borrow transaction. Validates that both rater and target were participants in the borrow.

| Property | Value |
|----------|-------|
| **Category** | Rating |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Tables Modified** | `user_rating_urt` (INSERT) |
| **Prerequisite** | Borrow must be in `returned` status |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Completed borrow transaction |
| `p_rater_id` | `IN` | `INT` | `id_acc` of the person giving the rating |
| `p_target_id` | `IN` | `INT` | `id_acc` of the person being rated |
| `p_role` | `IN` | `VARCHAR(30)` | Rating role: `'lender'` or `'borrower'` |
| `p_score` | `IN` | `INT` | Score 1-5 |
| `p_review_text` | `IN` | `TEXT` | Optional review comment |
| `p_rating_id` | `OUT` | `INT` | Newly created `id_urt` |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Validation Rules:**

1. Score must be between 1 and 5
2. Rating role must be valid (resolved via `fn_get_rating_role_id`)
3. Borrow must exist and be in `returned` status
4. Rater must be the borrower or lender of the transaction
5. Target must be the borrower or lender of the transaction
6. Rater cannot rate themselves

**Usage:**

```sql
CALL sp_rate_user(1, 2, 3, 'lender', 5, 'Great lender!', @rating_id, @error);
```

#### `sp_rate_tool`

Submits a tool rating after a completed borrow. Only the borrower can rate the tool.

| Property | Value |
|----------|-------|
| **Category** | Rating |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Tables Modified** | `tool_rating_trt` (INSERT) |
| **Prerequisite** | Borrow must be in `returned` status |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Completed borrow transaction |
| `p_rater_id` | `IN` | `INT` | `id_acc` of the borrower |
| `p_score` | `IN` | `INT` | Score 1-5 |
| `p_review_text` | `IN` | `TEXT` | Optional review comment |
| `p_rating_id` | `OUT` | `INT` | Newly created `id_trt` |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Validation Rules:**

1. Score must be between 1 and 5
2. Borrow must exist and be in `returned` status
3. Rater must be the borrower (not the lender)

**Usage:**

```sql
CALL sp_rate_tool(1, 2, 4, 'Tool worked well but was a bit worn', @rating_id, @error);
```

### 11.5 Notification Procedures

#### `sp_send_notification`

Creates a notification for a specific account. Falls back to `'request'` type if the specified notification type is not found.

| Property | Value |
|----------|-------|
| **Category** | Notification |
| **Transaction** | No (single INSERT) |
| **Tables Modified** | `notification_ntf` (INSERT) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_account_id` | `IN` | `INT` | Target account `id_acc` |
| `p_notification_type` | `IN` | `VARCHAR(30)` | Type name (e.g., `'request'`, `'approval'`, `'due'`) |
| `p_title` | `IN` | `VARCHAR(255)` | Notification title |
| `p_body` | `IN` | `TEXT` | Notification body |
| `p_related_borrow_id` | `IN` | `INT` | Optional `id_bor` for context linking |
| `p_notification_id` | `OUT` | `INT` | Newly created `id_ntf` |

**Usage:**

```sql
CALL sp_send_notification(2, 'request', 'New Borrow Request', 'User X wants to borrow your drill', 5, @ntf_id);
```

#### `sp_mark_notifications_read`

Batch-marks notifications as read for an account. If `p_notification_ids` is `NULL` or empty, marks **all** unread notifications as read.

| Property | Value |
|----------|-------|
| **Category** | Notification |
| **Transaction** | No (single UPDATE) |
| **Tables Modified** | `notification_ntf` (UPDATE — `is_read_ntf`, `read_at_ntf`) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_account_id` | `IN` | `INT` | Target account `id_acc` |
| `p_notification_ids` | `IN` | `TEXT` | Comma-separated list of `id_ntf` values, or `NULL` for all |
| `p_count` | `OUT` | `INT` | Number of notifications marked read |

**Usage:**

```sql
CALL sp_mark_notifications_read(2, '1,5,7,12', @count);  -- specific IDs
CALL sp_mark_notifications_read(2, NULL, @count);          -- mark all as read
```

#### `sp_send_overdue_notifications`

Batch-sends overdue notifications to borrowers with tools past their due date. Prevents duplicate notifications by checking if one was already sent today for the same borrow.

| Property | Value |
|----------|-------|
| **Category** | Notification — Batch |
| **Transaction** | No (single INSERT...SELECT) |
| **Tables Modified** | `notification_ntf` (INSERT) |
| **Called By** | `evt_send_overdue_notifications` (daily event) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_count` | `OUT` | `INT` | Number of notifications sent |

**Logic:**

1. Find all borrows where `status = 'borrowed'` AND `due_at_bor < NOW()`
2. Exclude borrows where an overdue notification was already sent today
3. Insert a notification for each with type `'due'`
4. Notification body includes the tool name and formatted due date

### 11.6 Maintenance Procedures

#### `sp_cleanup_expired_handover_codes`

Deletes expired and unverified handover verification codes.

| Property | Value |
|----------|-------|
| **Category** | Maintenance |
| **Tables Modified** | `handover_verification_hov` (DELETE) |
| **Called By** | `evt_cleanup_expired_handovers` (daily event) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_count` | `OUT` | `INT` | Number of records deleted |

**Filter:** Deletes where `expires_at_hov < NOW()` AND `verified_at_hov IS NULL`

#### `sp_archive_old_notifications`

Deletes read notifications older than a specified number of days.

| Property | Value |
|----------|-------|
| **Category** | Maintenance |
| **Tables Modified** | `notification_ntf` (DELETE) |
| **Called By** | `evt_archive_notifications` (weekly event) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_days_old` | `IN` | `INT` | Retention period in days (minimum 30, default 90) |
| `p_count` | `OUT` | `INT` | Number of records deleted |

**Safety:** If `p_days_old` is `NULL` or less than 30, defaults to 90 days.

#### `sp_cleanup_old_search_logs`

Purges search log entries older than a specified number of days.

| Property | Value |
|----------|-------|
| **Category** | Maintenance |
| **Tables Modified** | `search_log_slg` (DELETE) |
| **Called By** | `evt_cleanup_search_logs` (weekly event) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_days_old` | `IN` | `INT` | Retention period in days (minimum 7, default 30) |
| `p_count` | `OUT` | `INT` | Number of records deleted |

**Safety:** If `p_days_old` is `NULL` or less than 7, defaults to 30 days.

### 11.7 Deposit Procedures

#### `sp_release_deposit_on_return`

Releases a security deposit when a tool is returned successfully. Should be called after `sp_complete_return`. Handles the case where no deposit exists (succeeds silently).

| Property | Value |
|----------|-------|
| **Category** | Deposit |
| **Transaction** | Yes — `START TRANSACTION` / `COMMIT` with `ROLLBACK` on error |
| **Row Locking** | `SELECT ... FOR UPDATE` on `security_deposit_sdp` |
| **Tables Modified** | `security_deposit_sdp` (UPDATE — status, `released_at_sdp`) |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_borrow_id` | `IN` | `INT` | Borrow that was just returned |
| `p_success` | `OUT` | `BOOLEAN` | `TRUE` if released or no deposit exists |
| `p_error_message` | `OUT` | `VARCHAR(255)` | Error description on failure |

**Logic Steps:**

1. Look up the deposit for the borrow (`FOR UPDATE`)
2. If no deposit exists → commit and return success (no action needed)
3. If deposit is not in `held` status → signal error
4. Update deposit status from `held` to `released`, set `released_at_sdp = NOW()`

**Usage:**

```sql
CALL sp_release_deposit_on_return(1, @success, @error);
```

### 11.8 Search & Query Procedures

#### `sp_search_available_tools`

Full-text search for available tools with optional filters. Uses MySQL `MATCH ... AGAINST` in natural language mode and covering indexes for performance.

| Property | Value |
|----------|-------|
| **Category** | Search & Query |
| **Transaction** | No (read-only SELECT) |
| **Tables Read** | `tool_tol`, `account_acc`, `tool_condition_tcd`, `tool_image_tim`, `tool_rating_trt`, `tool_category_tolcat`, `borrow_bor`, `availability_block_avb` |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_search_term` | `IN` | `VARCHAR(255)` | Full-text search term (or `NULL` for all) |
| `p_zip_code` | `IN` | `VARCHAR(10)` | Filter by owner's ZIP code |
| `p_category_id` | `IN` | `INT` | Filter by category |
| `p_max_rental_fee` | `IN` | `DECIMAL(6,2)` | Maximum rental fee filter |
| `p_limit` | `IN` | `INT` | Results per page (default 20, max 100) |
| `p_offset` | `IN` | `INT` | Pagination offset (default 0) |

**Availability Checks:**

- `is_available_tol = TRUE`
- Owner not in deleted status
- No active borrow (status in `requested`, `approved`, `borrowed`)
- No active availability block covering `NOW()`

**Result Set Columns:** `id_tol`, `tool_name_tol`, `tool_description_tol`, `rental_fee_tol`, `default_loan_duration_hours_tol`, `is_deposit_required_tol`, `default_deposit_amount_tol`, `tool_condition`, `owner_name`, `owner_zip`, `primary_image`, `avg_rating`, `rating_count`

**Sort Order:** `avg_rating DESC`, `created_at_tol DESC`

**Usage:**

```sql
CALL sp_search_available_tools('drill', '28801', NULL, 50.00, 20, 0);
```

#### `sp_get_user_borrow_history`

Returns paginated borrow history for a specific account, filterable by role and status.

| Property | Value |
|----------|-------|
| **Category** | Search & Query |
| **Transaction** | No (read-only SELECT) |
| **Tables Read** | `borrow_bor`, `tool_tol`, `borrow_status_bst`, `account_acc` (owner + borrower), `tool_image_tim` |

**Parameters:**

| Parameter | Direction | Type | Description |
|-----------|-----------|------|-------------|
| `p_account_id` | `IN` | `INT` | Account to get history for |
| `p_role` | `IN` | `VARCHAR(10)` | `'borrower'`, `'lender'`, or `NULL` for both |
| `p_status` | `IN` | `VARCHAR(30)` | Filter by status name (or `NULL` for all) |
| `p_limit` | `IN` | `INT` | Results per page (default 20, max 100) |
| `p_offset` | `IN` | `INT` | Pagination offset (default 0) |

**Result Set Columns:** `id_bor`, `id_tol`, `tool_name_tol`, `tool_image`, `status`, `loan_duration_hours_bor`, `requested_at_bor`, `approved_at_bor`, `borrowed_at_bor`, `due_at_bor`, `returned_at_bor`, `user_role`, `other_party_name`

**Sort Order:** `requested_at_bor DESC`

**Usage:**

```sql
CALL sp_get_user_borrow_history(2, 'borrower', NULL, 20, 0);
CALL sp_get_user_borrow_history(3, 'lender', 'borrowed', 10, 0);
```

---

## Stored Functions

> Stored functions provide reusable lookup and computation helpers. This section documents all 9 stored functions in the schema.

### Function Summary

| # | Function | Returns | Lookup Table | Example |
|---|----------|---------|--------------|---------|
| 1 | `fn_get_account_status_id` | `INT` | `account_status_ast` | `fn_get_account_status_id('active')` → `2` |
| 2 | `fn_get_borrow_status_id` | `INT` | `borrow_status_bst` | `fn_get_borrow_status_id('borrowed')` → `3` |
| 3 | `fn_get_block_type_id` | `INT` | `block_type_btp` | `fn_get_block_type_id('borrow')` → `2` |
| 4 | `fn_get_rating_role_id` | `INT` | `rating_role_rtr` | `fn_get_rating_role_id('lender')` → `1` |
| 5 | `fn_get_notification_type_id` | `INT` | `notification_type_ntt` | `fn_get_notification_type_id('request')` → `1` |
| 6 | `fn_get_deposit_status_id` | `INT` | `deposit_status_dps` | `fn_get_deposit_status_id('held')` → `2` |
| 7 | `fn_get_dispute_status_id` | `INT` | `dispute_status_dst` | `fn_get_dispute_status_id('open')` → `1` |
| 8 | `fn_get_handover_type_id` | `INT` | `handover_type_hot` | `fn_get_handover_type_id('pickup')` → `1` |
| 9 | `fn_is_tool_available` | `BOOLEAN` | *(multiple)* | `fn_is_tool_available(5)` → `TRUE` |

### 12.1 Lookup ID Functions (8 functions)

All eight lookup functions follow an identical pattern. They accept a name string, query the corresponding lookup table, and return the integer primary key. This eliminates repeated subqueries like `(SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested')` throughout triggers and stored procedures.

**Common Pattern:**

```sql
CREATE FUNCTION fn_get_<entity>_id(p_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;
    SELECT <pk_column> INTO v_id
    FROM <lookup_table>
    WHERE <name_column> = p_name
    LIMIT 1;
    RETURN v_id;
END
```

**Characteristics:**

| Property | Value |
|----------|-------|
| **Returns** | `INT` — the primary key of the matching row, or `NULL` if not found |
| **Determinism** | `DETERMINISTIC` — same input always returns same output (lookup data is static) |
| **Data Access** | `READS SQL DATA` — read-only, no side effects |
| **Caching** | MySQL caches function results within a single query execution |
| **Parameter** | `VARCHAR(30)` — the lookup name (e.g., `'active'`, `'borrowed'`, `'open'`) |

**Individual Functions:**

| Function | Parameter | Lookup Table | PK Column | Name Column |
|----------|-----------|--------------|-----------|-------------|
| `fn_get_account_status_id` | `p_status_name` | `account_status_ast` | `id_ast` | `status_name_ast` |
| `fn_get_borrow_status_id` | `p_status_name` | `borrow_status_bst` | `id_bst` | `status_name_bst` |
| `fn_get_block_type_id` | `p_type_name` | `block_type_btp` | `id_btp` | `type_name_btp` |
| `fn_get_rating_role_id` | `p_role_name` | `rating_role_rtr` | `id_rtr` | `role_name_rtr` |
| `fn_get_notification_type_id` | `p_type_name` | `notification_type_ntt` | `id_ntt` | `type_name_ntt` |
| `fn_get_deposit_status_id` | `p_status_name` | `deposit_status_dps` | `id_dps` | `status_name_dps` |
| `fn_get_dispute_status_id` | `p_status_name` | `dispute_status_dst` | `id_dst` | `status_name_dst` |
| `fn_get_handover_type_id` | `p_type_name` | `handover_type_hot` | `id_hot` | `type_name_hot` |

> **Usage Note:** These functions are used extensively by stored procedures (`sp_create_borrow_request`, `sp_complete_pickup`, etc.) and by `fn_is_tool_available`. They replace inline subqueries for cleaner, more maintainable code.

### 12.2 Business Logic Functions

#### `fn_is_tool_available`

Determines whether a tool is currently available for borrowing by checking three conditions.

| Property | Value |
|----------|-------|
| **Returns** | `BOOLEAN` — `TRUE` if the tool can be borrowed |
| **Determinism** | Not deterministic (depends on current borrow/block state) |
| **Data Access** | `READS SQL DATA` |
| **Parameter** | `p_tool_id INT` — the `id_tol` to check |

**Availability Checks (all must pass):**

| # | Check | Source | Fail Condition |
|---|-------|--------|----------------|
| 1 | Tool is listed | `tool_tol.is_available_tol` | `NULL` or `FALSE` → return `FALSE` |
| 2 | No active borrow | `borrow_bor` via `fn_get_borrow_status_id` | Any borrow in `requested`, `approved`, or `borrowed` status → return `FALSE` |
| 3 | No active block | `availability_block_avb` | Any block where `NOW() BETWEEN start_at_avb AND end_at_avb` → return `FALSE` |

**Logic Flow:**

1. Look up `is_available_tol` from `tool_tol` — if `NULL` (tool not found) or `FALSE`, return `FALSE`
2. Check `borrow_bor` for any active borrows (status in `requested`, `approved`, `borrowed`) — if found, return `FALSE`
3. Check `availability_block_avb` for any block covering `NOW()` — if found, return `FALSE`
4. Return `TRUE`

**Used By:** `sp_create_borrow_request` (validates tool before creating borrow request)

**Usage:**

```sql
SELECT fn_is_tool_available(5);  -- Returns TRUE or FALSE
```

---

## Scheduled Events

> Scheduled events automate recurring maintenance and data refresh operations. This section documents all 8 scheduled events in the schema.

> **Prerequisite:** The MySQL event scheduler must be enabled: `SET GLOBAL event_scheduler = ON;`

### Event Schedule Matrix

| Event | Frequency | Start Time | Calls | Purpose |
|-------|-----------|------------|-------|---------|
| `evt_refresh_summaries_hourly` | Every 1 hour | `CURRENT_TIMESTAMP` | `sp_refresh_neighborhood_summary()`, `sp_refresh_category_summary()` | Dashboard performance |
| `evt_refresh_user_reputation_every_4h` | Every 4 hours | `CURRENT_TIMESTAMP` | `sp_refresh_user_reputation()` | User reputation cache |
| `evt_refresh_tool_statistics_every_2h` | Every 2 hours | `CURRENT_TIMESTAMP` | `sp_refresh_tool_statistics()` | Tool analytics, search ranking |
| `evt_daily_stat_midnight` | Every 1 day | Midnight (next day) | `sp_refresh_platform_daily_stat()` | Daily KPI snapshot |
| `evt_send_overdue_notifications` | Every 1 day | 8:00 AM | `sp_send_overdue_notifications()` | Borrower overdue alerts |
| `evt_cleanup_expired_handovers` | Every 1 hour | `CURRENT_TIMESTAMP` | `sp_cleanup_expired_handover_codes()` | Remove expired verification codes |
| `evt_archive_old_notifications` | Every 1 week | Sunday 2:00 AM | `sp_archive_old_notifications(90)` | Delete read notifications > 90 days |
| `evt_cleanup_search_logs` | Every 1 week | Sunday 3:00 AM | `sp_cleanup_old_search_logs(30)` | Delete search logs > 30 days |

### 13.1 Materialized View Refresh Events

#### `evt_refresh_summaries_hourly`

Refreshes the neighborhood and category summary caches every hour for fast dashboard rendering.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 HOUR` |
| **Starts** | `CURRENT_TIMESTAMP` (immediately on creation) |
| **Status** | `ENABLE` |
| **Procedures Called** | `sp_refresh_neighborhood_summary()`, `sp_refresh_category_summary()` |
| **Target Tables** | `neighborhood_summary_mat`, `category_summary_mat` |

#### `evt_refresh_user_reputation_every_4h`

Refreshes the user reputation cache every 4 hours. Less frequent than neighborhood/category since reputation changes less often.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 4 HOUR` |
| **Starts** | `CURRENT_TIMESTAMP` |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_refresh_user_reputation()` |
| **Target Table** | `user_reputation_mat` |

#### `evt_refresh_tool_statistics_every_2h`

Refreshes tool statistics every 2 hours to support trending tools and search ranking.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 2 HOUR` |
| **Starts** | `CURRENT_TIMESTAMP` |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_refresh_tool_statistics()` |
| **Target Table** | `tool_statistics_mat` |

#### `evt_daily_stat_midnight`

Captures daily platform statistics at midnight for reporting and monitoring dashboards.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 DAY` |
| **Starts** | `TIMESTAMP(CURDATE()) + INTERVAL 1 DAY` (next midnight) |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_refresh_platform_daily_stat()` |
| **Target Table** | `platform_daily_stat_pds` |

### 13.2 Notification Events

#### `evt_send_overdue_notifications`

Sends daily overdue notifications to borrowers with tools past their due date. Runs at 8 AM to reach users during business hours.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 DAY` |
| **Starts** | `TIMESTAMP(CURDATE()) + INTERVAL 8 HOUR` (8:00 AM) |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_send_overdue_notifications()` |
| **Target Table** | `notification_ntf` (INSERT) |

> **Deduplication:** The procedure ensures only one overdue notification per borrow per day by checking `DATE(created_at_ntf) = CURDATE()`.

### 13.3 Maintenance Events

#### `evt_cleanup_expired_handovers`

Removes expired and unverified handover codes every hour to prevent table bloat.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 HOUR` |
| **Starts** | `CURRENT_TIMESTAMP` |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_cleanup_expired_handover_codes()` |
| **Target Table** | `handover_verification_hov` (DELETE) |

#### `evt_archive_old_notifications`

Archives (deletes) read notifications older than 90 days. Runs weekly on Sunday at 2 AM to minimize impact.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 WEEK` |
| **Starts** | Next Sunday at 2:00 AM |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_archive_old_notifications(90)` |
| **Target Table** | `notification_ntf` (DELETE) |
| **Retention** | 90 days for read notifications |

#### `evt_cleanup_search_logs`

Purges search log entries older than 30 days. Runs weekly on Sunday at 3 AM.

| Property | Value |
|----------|-------|
| **Schedule** | `EVERY 1 WEEK` |
| **Starts** | Next Sunday at 3:00 AM |
| **Status** | `ENABLE` |
| **Procedure Called** | `sp_cleanup_old_search_logs(30)` |
| **Target Table** | `search_log_slg` (DELETE) |
| **Retention** | 30 days |

> **Scheduling Strategy:** Weekly maintenance events are staggered (2 AM and 3 AM) to avoid concurrent heavy DELETE operations. Hourly events run on creation and continuously thereafter.

---

## Seed Data Reference

> The dump file includes seed data for all lookup tables and sample test data. This section documents the seeded values and test data.

The dump file includes seed data for all lookup tables plus sample test data for the Asheville/Hendersonville, NC service area. All seed data is wrapped in a single `START TRANSACTION` / `COMMIT` block for atomic insertion.

### 14.1 Required Lookup Data

These 16 lookup tables **must** be seeded before any other data can be inserted (foreign key dependencies). Lookup table protection triggers prevent modification of seeded rows.

| Lookup Table | Values |
| --- | --- |
| `role_rol` | `member`, `admin`, `super_admin` |
| `account_status_ast` | `pending`, `active`, `suspended`, `deleted` |
| `contact_preference_cpr` | `email`, `phone`, `both`, `app` |
| `state_sta` | All 50 US states (`AL` through `WY` with full state names) |
| `tool_condition_tcd` | `new`, `good`, `fair`, `poor` |
| `borrow_status_bst` | `requested`, `approved`, `borrowed`, `returned`, `denied`, `cancelled` |
| `block_type_btp` | `admin`, `borrow` |
| `rating_role_rtr` | `lender`, `borrower` |
| `dispute_status_dst` | `open`, `resolved`, `dismissed` |
| `dispute_message_type_dmt` | `initial_report`, `response`, `admin_note`, `resolution` |
| `notification_type_ntt` | `request`, `approval`, `due`, `return`, `rating` |
| `waiver_type_wtp` | `borrow_waiver`, `condition_acknowledgment`, `liability_release` |
| `handover_type_hot` | `pickup`, `return` |
| `incident_type_ity` | `damage`, `theft`, `loss`, `injury`, `late_return`, `condition_dispute`, `other` |
| `deposit_status_dps` | `pending`, `held`, `released`, `forfeited`, `partial_release` |
| `payment_provider_ppv` | `stripe` (active), `paypal` (inactive), `manual` (active) |

> **Insertion order matters.** Lookup tables have no inter-dependencies and can be inserted in any order, but they must all be populated before core entity data.

### 14.2 Geographic Data

The sample data covers the **Asheville and Hendersonville, NC** metropolitan area.

#### ZIP Codes (17 entries)

| Area | ZIP Codes |
| --- | --- |
| Asheville (Buncombe County) | `28801` (Downtown), `28803` (South), `28804` (North), `28805` (East), `28806` (West) |
| Greater Asheville | `28704` (Arden), `28715` (Candler), `28732` (Fletcher), `28778` (Black Mountain/Swannanoa), `28787` (Weaverville) |
| Hendersonville (Henderson County) | `28739` (Hendersonville/Laurel Park), `28791` (Hendersonville North), `28792` (Hendersonville East/South) |
| Henderson County Area | `28726` (East Flat Rock), `28731` (Flat Rock), `28742` (Horse Shoe), `28759` (Mills River) |

Each ZIP code includes latitude, longitude, and a spatial `POINT` column using `ST_GeomFromText()` with SRID 4326 (WGS84).

#### Neighborhoods (31 entries)

| Region | Neighborhoods |
| --- | --- |
| Downtown/Central Asheville (28801) | Downtown Asheville, South Slope, Montford |
| West Asheville (28806) | West Asheville |
| Candler (28715) | Candler |
| North Asheville (28804) | North Asheville, Grove Park, Beaver Lake, Woodfin |
| Weaverville (28787) | Weaverville |
| South Asheville (28803) | South Asheville, Biltmore Village, Biltmore Forest, Biltmore Park |
| Arden/Fletcher (28704, 28732) | Arden, Fletcher |
| East Asheville (28805) | East Asheville, Kenilworth, Haw Creek, Oakley |
| East of Asheville (28778) | Swannanoa, Black Mountain |
| Hendersonville (28739, 28791, 28792) | Downtown Hendersonville, Laurel Park, Druid Hills, Fifth Avenue West |
| Henderson County | Flat Rock, East Flat Rock, Mills River, Horse Shoe, Etowah |

#### Neighborhood-ZIP Associations (31 entries)

Each neighborhood is linked to one or more ZIP codes via `neighborhood_zip_nbhzpc`. The `is_primary_nbhzpc` flag designates the main neighborhood for each ZIP code. Examples:

- Downtown Asheville is **primary** for 28801; South Slope and Montford are secondary
- Downtown Hendersonville serves both 28791 (primary) and 28792 (secondary)
- Etowah is secondary under Horse Shoe's ZIP code (28742)

### 14.3 Sample Accounts

#### Main Accounts (5 entries)

| ID | Name | Role | Neighborhood | Email |
| --- | --- | --- | --- | --- |
| 1 | Allyson Warren | member | Downtown Asheville (28801) | allyson.warren@example.com |
| 2 | Jeremiah Lutz | member | West Asheville (28806) | jeremiah.lutz@example.com |
| 3 | Chantelle Turcotte | member | Downtown Hendersonville (28791) | chantelle.turcotte@example.com |
| 4 | Alec Fehl | member | North Asheville (28804) | alec.fehl@example.com |
| 5 | Admin User | admin | Downtown Asheville (28801) | admin@neighborhoodtools.com |

All main accounts are **active**, **verified**, and have **consent** granted.

#### Test Accounts (2 entries)

| ID | Name | Role | Purpose |
| --- | --- | --- | --- |
| 6 | Jeremy Warren | super_admin | Platform super-administrator for testing |
| 7 | Pending User | member | Testing pending approval flow (unverified, pending status) |

> **Test password for all accounts:** `password123` (bcrypt hash applied via `UPDATE` statement)

#### Account Bios (4 entries)

Bios are stored in `account_bio_abi` (one per account) for accounts 1-4, reflecting local Asheville/Hendersonville interests (gardening, mountain biking, woodworking, orchard volunteering).

#### Vector Images (6 entries)

Six SVG icon files uploaded by the Admin account (ID 5): `hammer-icon.svg`, `saw-icon.svg`, `drill-icon.svg`, `wrench-icon.svg`, `gardening-icon.svg`, `chainsaw-icon.svg`.

### 14.4 Sample Tools & Categories

#### Categories (8 entries)

| ID | Category | Icon |
| --- | --- | --- |
| 1 | Hand Tools | hammer-icon.svg |
| 2 | Power Tools | drill-icon.svg |
| 3 | Gardening | gardening-icon.svg |
| 4 | Woodworking | saw-icon.svg |
| 5 | Automotive | wrench-icon.svg |
| 6 | Plumbing | *(none)* |
| 7 | Electrical | *(none)* |
| 8 | Outdoor/Landscaping | chainsaw-icon.svg |

#### Tools (11 entries)

| ID | Tool | Owner | Condition | Fee | Deposit | Duration |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | DeWalt 20V Cordless Drill | Allyson (1) | new | $0.00 | — | 168h (7d) |
| 2 | Craftsman 16oz Claw Hammer | Allyson (1) | good | $0.00 | — | 336h (14d) |
| 3 | Stihl MS 170 Chainsaw | Allyson (1) | good | $5.00 | $150.00 | 48h (2d) |
| 4 | Makita Circular Saw | Jeremiah (2) | good | $2.00 | $50.00 | 72h (3d) |
| 5 | Milwaukee Reciprocating Saw | Jeremiah (2) | fair | $1.50 | $40.00 | 72h (3d) |
| 6 | Werner 24ft Extension Ladder | Jeremiah (2) | good | $3.00 | $75.00 | 48h (2d) |
| 7 | Fiskars Loppers | Chantelle (3) | new | $0.00 | — | 168h (7d) |
| 8 | Corona Hedge Shears | Chantelle (3) | good | $0.00 | — | 168h (7d) |
| 9 | Apple Picking Pole | Chantelle (3) | good | $0.00 | — | 336h (14d) |
| 10 | Ryobi Pressure Washer | Alec (4) | good | $3.00 | $50.00 | 24h (1d) |
| 11 | Black & Decker Leaf Blower | Alec (4) | good | $0.00 | — | 72h (3d) |

Tools 3 and 5 include `preexisting_conditions_tol` notes. Tools 3 and 6 have `is_insurance_recommended_tol = TRUE`.

#### Tool-Category Associations (15 entries)

Several tools belong to multiple categories (many-to-many via `tool_category_tolcat`):

- **Stihl Chainsaw** → Power Tools + Outdoor/Landscaping
- **Makita Circular Saw** → Power Tools + Woodworking
- **Ryobi Pressure Washer** → Power Tools + Outdoor/Landscaping
- **Leaf Blower** → Gardening + Outdoor/Landscaping

### 14.5 Sample Transactions

#### Borrow Records (4 entries)

| ID | Tool | Borrower | Owner | Status | Timeline |
| --- | --- | --- | --- | --- | --- |
| 1 | DeWalt Drill (1) | Jeremiah (2) | Allyson (1) | **returned** | Requested 01/15 → Approved → Borrowed → Returned 01/18 |
| 2 | Craftsman Hammer (2) | Chantelle (3) | Allyson (1) | **borrowed** | Requested 01/28 → Approved → Borrowed 01/28 (active) |
| 3 | Fiskars Loppers (7) | Alec (4) | Chantelle (3) | **requested** | Requested (pending approval) |
| 4 | Werner Ladder (6) | Allyson (1) | Jeremiah (2) | **approved** | Requested 02/01 → Approved 02/01 (awaiting pickup) |

> These 4 borrows test every active state in the borrow lifecycle: `returned`, `borrowed`, `requested`, and `approved`.

#### Availability Blocks (2 entries)

| Tool | Block Type | Reason | Period |
| --- | --- | --- | --- |
| Stihl Chainsaw (3) | `admin` | Chain replacement and engine tune-up | 02/01 – 02/15 |
| Craftsman Hammer (2) | `borrow` | Linked to borrow 2 (Chantelle) | 01/28 – 02/04 |

#### Ratings (3 entries)

| Type | Rater | Target | Borrow | Score |
| --- | --- | --- | --- | --- |
| User rating | Jeremiah (2) | Allyson (1) as lender | Borrow 1 | 5/5 |
| User rating | Allyson (1) | Jeremiah (2) as borrower | Borrow 1 | 5/5 |
| Tool rating | Jeremiah (2) | DeWalt Drill (1) | Borrow 1 | 5/5 |

#### Terms of Service (1 version + 5 acceptances)

- **Version 1.0** — "NeighborhoodTools Terms of Service" created by Admin (5), effective 2026-01-01
- All 5 main accounts accepted ToS v1.0 with IP address and user agent recorded

#### Notifications (4 entries)

| Recipient | Type | Borrow | Read? |
| --- | --- | --- | --- |
| Chantelle (3) | request | Borrow 3 (Alec requests loppers) | No |
| Jeremiah (2) | approval | Borrow 1 (drill approved) | Yes |
| Chantelle (3) | approval | Borrow 2 (hammer approved) | Yes |
| Allyson (1) | approval | Borrow 4 (ladder approved) | No |

#### Bookmarks (5 entries)

| User | Bookmarked Tool |
| --- | --- |
| Jeremiah (2) | Allyson's Stihl Chainsaw (3) |
| Chantelle (3) | Jeremiah's Circular Saw (4), Jeremiah's Reciprocating Saw (5) |
| Alec (4) | His own Pressure Washer (10) |
| Allyson (1) | Chantelle's Apple Picking Pole (9) |

#### Events (5 entries + 10 metadata)

| Event | Neighborhood | Date | Metadata |
| --- | --- | --- | --- |
| Spring Tool Swap Meet | Downtown Asheville | 03/15/2026 | Pack Square Park, capacity 50 |
| Community Garden Workday | West Asheville | 03/08/2026 | Haywood Rd garden, capacity 30 |
| DIY Home Repair Workshop | Downtown Hendersonville | 03/22/2026 | Henderson County Library, capacity 40 |
| Mountain Trail Cleanup Day | Beaver Lake | 01/25/2026 | Beaver Lake Trail, capacity 25 |
| Tool Safety & Maintenance Class | Downtown Hendersonville | 04/05/2026 | Fire Station #2, capacity 20 |

All events created by Admin (5). Metadata stored as key-value pairs in `event_meta_evm` (location, max_capacity, contact_email).

#### Disputes (1 dispute + 3 messages)

- **Dispute on Borrow 1** — Allyson reports minor scratches on DeWalt Drill after Jeremiah's return
- **Messages:**
  1. Allyson (initial_report) — describes scratches, references handover photos
  2. Jeremiah (response) — claims scratches were pre-existing, offers to discuss in person
  3. Admin (admin_note, internal) — reviews handover notes, suggests mediation

#### Borrow Waivers (2 entries)

| Borrow | Signer | Type | All Acknowledgments |
| --- | --- | --- | --- |
| Borrow 1 (drill) | Jeremiah (2) | borrow_waiver | All TRUE |
| Borrow 2 (hammer) | Chantelle (3) | borrow_waiver | All TRUE |

Both waivers include IP address, user agent, and `signed_at` timestamp. The trigger `trg_borrow_waiver_before_insert` enforces that all three acknowledgment booleans are TRUE.

#### Handover Verifications (3 entries)

| Borrow | Type | Generator | Verifier | Condition Notes |
| --- | --- | --- | --- | --- |
| Borrow 1 | pickup | Allyson (1) | Jeremiah (2) | "Drill in excellent condition. Both batteries fully charged." |
| Borrow 1 | return | Jeremiah (2) | Allyson (1) | "Minor scratches noted on chuck area. Batteries at ~40% charge." |
| Borrow 2 | pickup | Allyson (1) | Chantelle (3) | "Hammer in good condition. Handle grip intact." |

> Verification codes (`SEED01`, `SEED02`, `SEED03`) are overwritten by the `trg_handover_verification_before_insert` trigger which auto-generates codes and expiration timestamps.

#### Incident Report (1 report + 2 photos)

- **Subject:** Scratches on DeWalt Drill Chuck (Borrow 1)
- **Reporter:** Allyson (1), Type: `damage`, Estimated damage: $25.00
- **Resolution:** Both parties agreed scratches are cosmetic; no financial penalty. Resolved by Admin (5) on 01/22/2026
- **Photos:** `drill-scratch-closeup-01.jpg`, `drill-overview-02.jpg`

#### Loan Extension (1 entry)

- **Borrow 2** (Chantelle's hammer borrow) extended by **72 hours** (3 days)
- Original due: 2026-02-04 11:00:00 → New due: 2026-02-07 11:00:00
- Approved by Allyson (1)

#### Financial Records

**Security Deposit (1 entry):**

| Borrow | Amount | Status | Provider | External ID |
| --- | --- | --- | --- | --- |
| Borrow 4 (ladder) | $75.00 | held | Stripe | `pi_test_ladder_deposit_001` |

**Payment Transactions (2 entries + 4 metadata):**

| Transaction | Type | Amount | From → To | Status |
| --- | --- | --- | --- | --- |
| 1 | deposit_hold | $75.00 | Allyson (1) → escrow | succeeded |
| 2 | rental_fee | $3.00 | Allyson (1) → Jeremiah (2) | succeeded |

Metadata includes Stripe payment method (`pm_card_visa`) and receipt URLs for both transactions.

#### Search Logs (7 entries)

| User | Query | Clicked Tool |
| --- | --- | --- |
| Jeremiah (2) | "cordless drill" | DeWalt Drill (1) |
| Chantelle (3) | "circular saw woodworking" | Makita Circular Saw (4) |
| Alec (4) | "garden loppers pruning" | Fiskars Loppers (7) |
| Allyson (1) | "pressure washer deck cleaning" | *(none)* |
| Anonymous | "chainsaw rental asheville" | *(none)* |
| Jeremiah (2) | "extension ladder" | *(none)* |
| Chantelle (3) | "hedge trimmer hendersonville" | *(none)* |

Includes both authenticated and anonymous searches. Each entry records IP address and session ID.

#### Audit Logs (5 entries + 3 details)

| Table | Row | Action | Actor | Timestamp |
| --- | --- | --- | --- | --- |
| `account_acc` | 1 | INSERT | system | 2026-01-01 08:00 |
| `account_acc` | 2 | INSERT | system | 2026-01-02 09:00 |
| `tool_tol` | 1 | INSERT | Allyson (1) | 2026-01-05 10:00 |
| `borrow_bor` | 1 | INSERT | Jeremiah (2) | 2026-01-15 10:00 |
| `borrow_bor` | 1 | UPDATE | Allyson (1) | 2026-01-15 12:00 |

**Audit Detail** records for borrow status transitions:

- Borrow 1 created: `id_bst_bor` NULL → `requested`
- Borrow 1 approved: `id_bst_bor` `requested` → `approved`, `approved_at_bor` set

### 14.6 Post-Seed Initialization

After all seed data is inserted, the dump executes:

```sql
CALL sp_refresh_all_summaries();
```

This populates the four materialized summary tables (`neighborhood_summary_mat`, `user_reputation_mat`, `tool_statistics_mat`, `category_summary_mat`) with aggregated data from the sample records.

A **verification query** at the end of the dump lists all non-deleted accounts with their roles and statuses to confirm successful seeding.

---

## Naming Conventions

This database uses a consistent naming convention:

- **Table names:** `entity_suffix` (e.g., `account_acc`, `tool_tol`)
- **Column names:** `column_name_suffix` where suffix matches the table suffix
- **Primary keys:** `id_suffix` (e.g., `id_acc`, `id_tol`)
- **Foreign keys:** `id_referenced_table_current_table` (e.g., `id_acc_bor` for
  account FK in borrow table)
- **Indexes:** Descriptive names prefixed with `idx_`, `uq_`, or `fulltext_`
- **Triggers:** `trg_<table_or_entity>_before_<insert|update|delete>` (e.g., `trg_borrow_before_update`)
- **Views:** `<descriptive_name>_v` (e.g., `available_tool_v`, `user_reputation_fast_v`)
- **Stored Procedures:** `sp_<verb>_<entity>` (e.g., `sp_create_borrow_request`, `sp_refresh_all_summaries`)
- **Stored Functions:** `fn_<verb>_<entity>` (e.g., `fn_get_role_id`, `fn_is_tool_available`)
- **Scheduled Events:** `evt_<frequency>_<action>` (e.g., `evt_hourly_refresh_tool_statistics`, `evt_daily_archive_notifications`)

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

---

## Development Tools Used

Tools used in development of the database design

- **VS Code** Code Editor
- **Grok** Analytics from X.com
- **Codex** Document Analysis
- **Claude Code** Document Refinement
- **[dbdiagram.io](https://dbdiagram.io/d/neighborhoodtools-com-ERD-69711419bd82f5fce231c284)** ERD Creation
