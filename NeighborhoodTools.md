# NeighborhoodTools.com Database Design

**Author:** Jeremy Warren
**Course:** WEB-289 Capstone

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
- **Dispute resolution** for handling conflicts
- **User interactions** including bookmarks, notifications, and search logging
- **Future expansion** for community events

---

## Table Groups

The database is organized into logical groups for easier management and
visualization:

| Group                  | Tables                                                                                                            |
|------------------------|-------------------------------------------------------------------------------------------------------------------|
| **Accounts**           | `role_rol`, `account_status_ats`, `contact_preference_ctp`, `account_act`, `account_image_actimg`, `zip_code_zpc` |
| **Tools**              | `category_cat`, `tool_condition_tcn`, `tool_tol`, `tool_category_tolcat`, `tool_image_tolimg`, `vector_image_vec` |
| **Borrowing**          | `borrow_status_bst`, `block_type_btp`, `borrow_brw`, `availability_block_abl`                                     |
| **Ratings & Disputes** | `rating_role_rtr`, `user_rating_urt`, `tool_rating_trt`, `dispute_dsp`, `dispute_status_dst`                      |
| **User Interactions**  | `bookmark_acttol`, `notification_ntf`, `notification_type_ntt`, `search_log_slg`                                  |
| **Future Expansion**   | `event_evt`                                                                                                       |

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

### account_status_ats

Tracks account lifecycle states.

| Column            | Type        | Constraints        | Notes                                         |
|-------------------|-------------|--------------------|-----------------------------------------------|
| `id_ats`          | int         | PK, auto-increment | -                                             |
| `status_name_ats` | varchar(30) | unique, not null   | Values: pending, active, suspended, deleted   |

---

### contact_preference_ctp

User communication preferences.

| Column                | Type        | Constraints        | Notes                             |
|-----------------------|-------------|--------------------|-----------------------------------|
| `id_ctp`              | int         | PK, auto-increment | -                                 |
| `preference_name_ctp` | varchar(30) | unique, not null   | Values: email, phone, both, app   |

---

### category_cat

Tool categories for classification.

| Column              | Type         | Constraints        | Notes |
|---------------------|--------------|--------------------|-------|
| `id_cat`            | int          | PK, auto-increment | -     |
| `category_name_cat` | varchar(100) | unique, not null   | -     |

---

### tool_condition_tcn

Describes physical condition of tools.

| Column               | Type        | Constraints        | Notes                          |
|----------------------|-------------|--------------------|--------------------------------|
| `id_tcn`             | int         | PK, auto-increment | -                              |
| `condition_name_tcn` | varchar(30) | unique, not null   | Values: new, good, fair, poor  |

---

### borrow_status_bst

Tracks borrow request lifecycle.

| Column            | Type        | Constraints        | Notes                                                              |
|-------------------|-------------|--------------------|--------------------------------------------------------------------|
| `id_bst`          | int         | PK, auto-increment | -                                                                  |
| `status_name_bst` | varchar(30) | unique, not null   | Values: requested, approved, borrowed, returned, denied, cancelled |

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

### notification_type_ntt

Categories of system notifications.

| Column          | Type        | Constraints        | Notes                                          |
|-----------------|-------------|--------------------|------------------------------------------------|
| `id_ntt`        | int         | PK, auto-increment | -                                              |
| `type_name_ntt` | varchar(30) | unique, not null   | Values: request, approval, due, return, rating |

---

## Core Tables

### Accounts

#### account_act

Main user account table containing all user information.

| Column               | Type         | Constraints        | Notes                                          |
|----------------------|--------------|--------------------|------------------------------------------------|
| `id_act`             | int          | PK, auto-increment | -                                              |
| `full_name_act`      | varchar(255) | not null           | -                                              |
| `phone_number_act`   | varchar(20)  | -                  | -                                              |
| `email_address_act`  | varchar(255) | unique, not null   | -                                              |
| `street_address_act` | varchar(255) | -                  | Optional for privacy – ZIP required            |
| `zip_code_zpc_act`   | varchar(10)  | not null           | FK to zip_code_zpc                             |
| `password_hash_act`  | varchar(255) | not null           | bcrypt or argon2 hash only                     |
| `bio_text_act`       | text         | -                  | -                                              |
| `id_rol_act`         | int          | not null           | FK to role_rol                                 |
| `id_ats_act`         | int          | not null           | FK to account_status_ats                       |
| `id_ctp_act`         | int          | not null           | FK to contact_preference_ctp                   |
| `is_verified_act`    | boolean      | default: false     | -                                              |
| `has_consent_act`    | boolean      | default: false     | -                                              |
| `is_deleted_act`     | boolean      | default: false     | Soft delete – belt-and-suspenders with status  |
| `last_login_at_act`  | timestamp    | -                  | -                                              |
| `created_at_act`     | timestamp    | default: now()     | -                                              |
| `updated_at_act`     | timestamp    | default: now()     | -                                              |
| `metadata_json_act`  | json         | -                  | Future: preferences, settings, etc.            |

**Indexes:**

- `idx_email_act` on `email_address_act`
- `idx_zip_act` on `zip_code_zpc_act`
- `idx_status_verified_act` on `(id_ats_act, is_verified_act)`

---

#### account_image_actimg

Profile images for user accounts. One account can have multiple images.

| Column               | Type         | Constraints        | Notes             |
|----------------------|--------------|--------------------|-------------------|
| `id_actimg`          | int          | PK, auto-increment | -                 |
| `id_act_actimg`      | int          | not null           | FK to account_act |
| `file_name_actimg`   | varchar(255) | not null           | -                 |
| `alt_text_actimg`    | varchar(255) | -                  | -                 |
| `is_primary_actimg`  | boolean      | default: false     | -                 |
| `uploaded_at_actimg` | timestamp    | default: now()     | -                 |

**Indexes:**

- `idx_account_primary_actimg` on `(id_act_actimg, is_primary_actimg)`

---

#### zip_code_zpc

Geographic data for location-based features. Pre-populated with NC focus.

| Column                  | Type         | Constraints             | Notes |
|-------------------------|--------------|-------------------------|-------|
| `zip_code_zpc`          | varchar(10)  | PK                      | -     |
| `city_name_zpc`         | varchar(100) | not null                | -     |
| `neighborhood_name_zpc` | varchar(100) | -                       | -     |
| `state_code_zpc`        | varchar(2)   | not null, default: "nc" | -     |
| `latitude_zpc`          | decimal(9,6) | -                       | -     |
| `longitude_zpc`         | decimal(9,6) | -                       | -     |

**Indexes:**

- `idx_state_city_zpc` on `(state_code_zpc, city_name_zpc)`

> **Note:** Enables Haversine proximity queries for finding nearby tools.

---

### Tools

#### tool_tol

Main tool listing table.

| Column                | Type          | Constraints        | Notes                            |
|-----------------------|---------------|--------------------|----------------------------------|
| `id_tol`              | int           | PK, auto-increment | -                                |
| `tool_name_tol`       | varchar(255)  | not null           | -                                |
| `tool_description_tol`| text          | -                  | -                                |
| `id_tcn_tol`          | int           | not null           | FK to tool_condition_tcn         |
| `id_act_tol`          | int           | not null           | Owner account FK                 |
| `serial_number_tol`   | varchar(50)   | -                  | -                                |
| `rental_fee_tol`      | decimal(10,2) | default: 0.00      | 0 = free sharing                 |
| `is_available_tol`    | boolean       | default: true      | -                                |
| `created_at_tol`      | timestamp     | default: now()     | -                                |
| `updated_at_tol`      | timestamp     | default: now()     | -                                |
| `metadata_json_tol`   | json          | -                  | Future: custom attributes, tags  |

**Indexes:**

- `idx_owner_tol` on `id_act_tol`
- `idx_availability_tol` on `is_available_tol`
- `fulltext_tool_search_tol` (FULLTEXT) on `(tool_name_tol, tool_description_tol)`

---

#### tool_category_tolcat

Junction table enabling many-to-many relationship between tools and categories.

| Column              | Type      | Constraints        | Notes              |
|---------------------|-----------|--------------------|--------------------|
| `id_tolcat`         | int       | PK, auto-increment | -                  |
| `id_tol_tolcat`     | int       | not null           | FK to tool_tol     |
| `id_cat_tolcat`     | int       | not null           | FK to category_cat |
| `created_at_tolcat` | timestamp | default: now()     | -                  |

**Indexes:**

- `uq_tool_category_tolcat` (UNIQUE) on `(id_tol_tolcat, id_cat_tolcat)`
- `idx_category_tolcat` on `id_cat_tolcat`

---

#### tool_image_tolimg

Images for tools. One tool can have multiple images with display ordering.

| Column               | Type         | Constraints        | Notes                     |
|----------------------|--------------|--------------------|---------------------------|
| `id_tolimg`          | int          | PK, auto-increment | -                         |
| `id_tol_tolimg`      | int          | not null           | FK to tool_tol            |
| `file_name_tolimg`   | varchar(255) | not null           | -                         |
| `alt_text_tolimg`    | varchar(255) | -                  | -                         |
| `is_primary_tolimg`  | boolean      | default: false     | -                         |
| `sort_order_tolimg`  | int          | default: 0         | Display order for gallery |
| `uploaded_at_tolimg` | timestamp    | default: now()     | -                         |

**Indexes:**

- `idx_tool_primary_tolimg` on `(id_tol_tolimg, is_primary_tolimg)`
- `idx_tool_sort_tolimg` on `(id_tol_tolimg, sort_order_tolimg)`

---

#### vector_image_vec

Vector/SVG images uploaded by admins for site use.

| Column                | Type         | Constraints        | Notes                        |
|-----------------------|--------------|--------------------|------------------------------|
| `id_vec`              | int          | PK, auto-increment | -                            |
| `file_name_vec`       | varchar(255) | not null           | -                            |
| `description_text_vec`| text         | -                  | -                            |
| `id_act_vec`          | int          | not null           | Uploaded by account (admin)  |
| `uploaded_at_vec`     | timestamp    | default: now()     | -                            |

---

### Borrowing

#### borrow_brw

Tracks tool borrow requests and their lifecycle.

| Column                 | Type      | Constraints        | Notes                   |
|------------------------|-----------|--------------------|-------------------------|
| `id_brw`               | int       | PK, auto-increment | -                       |
| `id_tol_brw`           | int       | not null           | FK to tool_tol          |
| `id_act_brw`           | int       | not null           | Borrower account FK     |
| `id_bst_brw`           | int       | not null           | FK to borrow_status_bst |
| `requested_at_brw`     | timestamp | default: now()     | -                       |
| `approved_at_brw`      | timestamp | -                  | -                       |
| `borrowed_at_brw`      | timestamp | -                  | -                       |
| `due_at_brw`           | timestamp | -                  | -                       |
| `returned_at_brw`      | timestamp | -                  | -                       |
| `cancelled_at_brw`     | timestamp | -                  | -                       |
| `notes_text_brw`       | text      | -                  | -                       |
| `is_contact_shared_brw`| boolean   | default: false     | -                       |
| `created_at_brw`       | timestamp | default: now()     | -                       |

**Indexes:**

- `idx_status_due_brw` on `(id_bst_brw, due_at_brw)`
- `idx_tool_borrower_brw` on `(id_tol_brw, id_act_brw)`

---

#### availability_block_abl

Manages tool availability, supporting both admin manual blocks and automatic
borrow unavailability.

| Column           | Type      | Constraints        | Notes                                             |
|------------------|-----------|--------------------| --------------------------------------------------|
| `id_abl`         | int       | PK, auto-increment | -                                                 |
| `id_tol_abl`     | int       | not null           | FK to tool_tol                                    |
| `id_btp_abl`     | int       | not null           | FK to block_type_btp                              |
| `start_at_abl`   | timestamp | not null           | -                                                 |
| `end_at_abl`     | timestamp | not null           | -                                                 |
| `id_brw_abl`     | int       | -                  | Required for borrow blocks; null for admin blocks |
| `notes_text_abl` | text      | -                  | -                                                 |
| `created_at_abl` | timestamp | default: now()     | -                                                 |

**Indexes:**

- `idx_tool_range_abl` on `(id_tol_abl, start_at_abl, end_at_abl)`
- `idx_borrow_abl` on `id_brw_abl`

> **Note:** Used for both admin manual blocks (maintenance, personal use) and
> automatic borrow unavailability. `block_type_btp` distinguishes the two.

---

### Ratings & Disputes

#### user_rating_urt

Ratings between users (lender rating borrower or vice versa).

| Column              | Type      | Constraints        | Notes                                       |
|---------------------|-----------|--------------------| --------------------------------------------|
| `id_urt`            | int       | PK, auto-increment | -                                           |
| `id_act_urt`        | int       | not null           | Rater account FK                            |
| `id_act_target_urt` | int       | not null           | Ratee account FK                            |
| `id_brw_urt`        | int       | not null           | FK to borrow_brw                            |
| `id_rtr_urt`        | int       | not null           | FK to rating_role_rtr (lender or borrower)  |
| `score_urt`         | int       | not null           | 1-5 scale                                   |
| `comment_text_urt`  | text      | -                  | -                                           |
| `created_at_urt`    | timestamp | default: now()     | -                                           |

**Indexes:**

- `idx_target_role_urt` on `(id_act_target_urt, id_rtr_urt)`
- `uq_one_user_rating_per_borrow_urt` (UNIQUE) on `(id_brw_urt, id_act_urt, id_rtr_urt)`

> **Note:** `CHECK (score_urt BETWEEN 1 AND 5)` added in SQL script.

---

#### tool_rating_trt

Ratings for tools after borrowing.

| Column             | Type      | Constraints        | Notes            |
|--------------------|-----------|--------------------| -----------------|
| `id_trt`           | int       | PK, auto-increment | -                |
| `id_act_trt`       | int       | not null           | Rater account FK |
| `id_tol_trt`       | int       | not null           | FK to tool_tol   |
| `id_brw_trt`       | int       | not null           | FK to borrow_brw |
| `score_trt`        | int       | not null           | 1-5 scale        |
| `comment_text_trt` | text      | -                  | -                |
| `created_at_trt`   | timestamp | default: now()     | -                |

**Indexes:**

- `idx_tool_trt` on `id_tol_trt`
- `uq_one_tool_rating_per_borrow_trt` (UNIQUE) on `(id_brw_trt, id_act_trt, id_tol_trt)`

> **Note:** `CHECK (score_trt BETWEEN 1 AND 5)` added in SQL script.

---

#### dispute_dsp

Handles conflicts and issues related to borrow transactions.

| Column                | Type      | Constraints        | Notes                     |
|-----------------------|-----------|--------------------| --------------------------|
| `id_dsp`              | int       | PK, auto-increment | -                         |
| `id_brw_dsp`          | int       | not null           | FK to borrow_brw          |
| `id_act_dsp`          | int       | not null           | Reporter account FK       |
| `description_text_dsp`| text      | not null           | -                         |
| `id_dst_dsp`          | int       | not null           | FK to dispute_status_dst  |
| `id_act_resolver_dsp` | int       | -                  | Admin who resolved FK     |
| `created_at_dsp`      | timestamp | default: now()     | -                         |

**Indexes:**

- `idx_status_dsp` on `id_dst_dsp`
- `idx_borrow_dsp` on `id_brw_dsp`

---

### User Interactions

#### bookmark_acttol

Junction table for user-saved/favorited tools.

| Column             | Type      | Constraints        | Notes             |
|--------------------|-----------|--------------------| ------------------|
| `id_acttol`        | int       | PK, auto-increment | -                 |
| `id_act_acttol`    | int       | not null           | FK to account_act |
| `id_tol_acttol`    | int       | not null           | FK to tool_tol    |
| `created_at_acttol`| timestamp | default: now()     | -                 |

**Indexes:**

- `uq_account_tool_acttol` (UNIQUE) on `(id_act_acttol, id_tol_acttol)`
- `idx_tool_acttol` on `id_tol_acttol`

---

#### notification_ntf

System notifications sent to users.

| Column             | Type      | Constraints        | Notes                         |
|--------------------|-----------|--------------------| ------------------------------|
| `id_ntf`           | int       | PK, auto-increment | -                             |
| `id_act_ntf`       | int       | not null           | FK to account_act             |
| `id_ntt_ntf`       | int       | not null           | FK to notification_type_ntt   |
| `message_text_ntf` | text      | not null           | -                             |
| `id_brw_ntf`       | int       | -                  | FK to borrow_brw (optional)   |
| `is_read_ntf`      | boolean   | default: false     | -                             |
| `created_at_ntf`   | timestamp | default: now()     | -                             |

**Indexes:**

- `idx_unread_ntf` on `(id_act_ntf, is_read_ntf)`
- `idx_borrow_ntf` on `id_brw_ntf`

---

#### search_log_slg

Analytics table for tracking user searches.

| Column            | Type         | Constraints        | Notes                         |
|-------------------|--------------|--------------------| ------------------------------|
| `id_slg`          | int          | PK, auto-increment | -                             |
| `id_act_slg`      | int          | -                  | FK to account_act (optional)  |
| `id_tol_slg`      | int          | -                  | FK to tool_tol (if clicked)   |
| `search_text_slg` | varchar(255) | -                  | -                             |
| `ip_address_slg`  | varchar(45)  | -                  | Supports IPv6                 |
| `session_id_slg`  | varchar(64)  | -                  | -                             |
| `created_at_slg`  | timestamp    | default: now()     | -                             |

**Indexes:**

- `fulltext_search_slg` (FULLTEXT) on `search_text_slg`
- `idx_created_at_slg` on `created_at_slg`

---

### Future Expansion

#### event_evt

Community events table for future functionality.

| Column                 | Type         | Constraints        | Notes                          |
|------------------------|--------------|--------------------|--------------------------------|
| `id_evt`               | int          | PK, auto-increment | -                              |
| `event_name_evt`       | varchar(255) | not null           | -                              |
| `event_description_evt`| text         | -                  | -                              |
| `start_at_evt`         | timestamp    | not null           | -                              |
| `end_at_evt`           | timestamp    | -                  | -                              |
| `zip_code_zpc_evt`     | varchar(10)  | -                  | FK to zip_code_zpc             |
| `id_act_evt`           | int          | not null           | Created by account (admin) FK  |
| `created_at_evt`       | timestamp    | default: now()     | -                              |
| `metadata_json_evt`    | json         | -                  | Future: tags, RSVPs, etc.      |

**Indexes:**

- `idx_date_zip_evt` on `(start_at_evt, zip_code_zpc_evt)`

---

## Relationships

### Many-to-Many Relationships (M:M)

Junction tables create the following M:M relationships:

| Relationship              | Parent A       | Parent B       | Junction Table        |
|---------------------------|----------------|----------------|-----------------------|
| Tools have Categories     | `tool_tol`     | `category_cat` | `tool_category_tolcat`|
| Accounts bookmark Tools   | `account_act`  | `tool_tol`     | `bookmark_acttol`     |

### One-to-Many Relationships (1:M)

#### Account Domain

| Parent (One)             | Child (Many)            | Foreign Key                | Description                      |
|--------------------------|-------------------------|----------------------------|----------------------------------|
| `role_rol`               | `account_act`           | `id_rol_act`               | Role assigned to accounts        |
| `account_status_ats`     | `account_act`           | `id_ats_act`               | Status of accounts               |
| `contact_preference_ctp` | `account_act`           | `id_ctp_act`               | Contact preference for accounts  |
| `zip_code_zpc`           | `account_act`           | `zip_code_zpc_act`         | Location of accounts             |
| `account_act`            | `account_image_actimg`  | `id_act_actimg`            | Account has profile images       |
| `account_act`            | `vector_image_vec`      | `id_act_vec`               | Admin uploads vector images      |

#### Tool Domain

| Parent (One)         | Child (Many)         | Foreign Key      | Description                    |
|----------------------|----------------------|------------------|--------------------------------|
| `tool_condition_tcn` | `tool_tol`           | `id_tcn_tol`     | Condition of tools             |
| `account_act`        | `tool_tol`           | `id_act_tol`     | Account owns tools             |
| `tool_tol`           | `tool_image_tolimg`  | `id_tol_tolimg`  | Tool has images                |

#### Borrowing Domain

| Parent (One)        | Child (Many)            | Foreign Key    | Description                       |
|---------------------|-------------------------|----------------|-----------------------------------|
| `tool_tol`          | `borrow_brw`            | `id_tol_brw`   | Tool is borrowed multiple times   |
| `account_act`       | `borrow_brw`            | `id_act_brw`   | Account borrows tools             |
| `borrow_status_bst` | `borrow_brw`            | `id_bst_brw`   | Status of borrow requests         |
| `tool_tol`          | `availability_block_abl`| `id_tol_abl`   | Tool has availability blocks      |
| `block_type_btp`    | `availability_block_abl`| `id_btp_abl`   | Type of availability block        |
| `borrow_brw`        | `availability_block_abl`| `id_brw_abl`   | Borrow creates availability block |

#### Rating Domain

| Parent (One)      | Child (Many)      | Foreign Key        | Description                        |
|-------------------|-------------------|--------------------|------------------------------------|
| `account_act`     | `user_rating_urt` | `id_act_urt`       | Account gives user ratings         |
| `account_act`     | `user_rating_urt` | `id_act_target_urt`| Account receives user ratings      |
| `borrow_brw`      | `user_rating_urt` | `id_brw_urt`       | Borrow transaction has ratings     |
| `rating_role_rtr` | `user_rating_urt` | `id_rtr_urt`       | Rating context (lender/borrower)   |
| `account_act`     | `tool_rating_trt` | `id_act_trt`       | Account rates tools                |
| `tool_tol`        | `tool_rating_trt` | `id_tol_trt`       | Tool receives ratings              |
| `borrow_brw`      | `tool_rating_trt` | `id_brw_trt`       | Borrow transaction has tool rating |

#### Dispute Domain

| Parent (One)         | Child (Many)  | Foreign Key          | Description                  |
|----------------------|---------------|----------------------|------------------------------|
| `borrow_brw`         | `dispute_dsp` | `id_brw_dsp`         | Borrow has disputes          |
| `account_act`        | `dispute_dsp` | `id_act_dsp`         | Account reports disputes     |
| `account_act`        | `dispute_dsp` | `id_act_resolver_dsp`| Admin resolves disputes      |
| `dispute_status_dst` | `dispute_dsp` | `id_dst_dsp`         | Status of disputes           |

#### Notification Domain

| Parent (One)           | Child (Many)       | Foreign Key  | Description                     |
|------------------------|--------------------|--------------|---------------------------------|
| `account_act`          | `notification_ntf` | `id_act_ntf` | Account receives notifications  |
| `notification_type_ntt`| `notification_ntf` | `id_ntt_ntf` | Type of notification            |
| `borrow_brw`           | `notification_ntf` | `id_brw_ntf` | Borrow triggers notifications   |

#### Search & Analytics Domain

| Parent (One)  | Child (Many)    | Foreign Key  | Description                    |
|---------------|-----------------|--------------|--------------------------------|
| `account_act` | `search_log_slg`| `id_act_slg` | Account performs searches      |
| `tool_tol`    | `search_log_slg`| `id_tol_slg` | Tool appears in search results |

#### Event Domain

| Parent (One)   | Child (Many) | Foreign Key        | Description          |
|----------------|--------------|--------------------| ---------------------|
| `zip_code_zpc` | `event_evt`  | `zip_code_zpc_evt` | Location of events   |
| `account_act`  | `event_evt`  | `id_act_evt`       | Admin creates events |

---

## Entity Relationship Diagram

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│                              ACCOUNTS GROUP                                  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐    ┌────────────────────┐    ┌─────────────────────────┐   │
│  │   role_rol   │    │ account_status_ats │    │ contact_preference_ctp  │   │
│  └──────┬───────┘    └─────────┬──────────┘    └────────────┬────────────┘   │
│         │                      │                             │               │
│         └──────────────────────┼─────────────────────────────┘               │
│                                │                                             │
│                                ▼                                             │
│                      ┌─────────────────┐                                     │
│                      │   account_act   │◄─────────┐                          │
│                      └────────┬────────┘          │                          │
│                               │                   │                          │
│         ┌─────────────────────┼───────────────────┤                          │
│         │                     │                   │                          │
│         ▼                     ▼                   │                          │
│  ┌────────────────────┐  ┌──────────────┐         │                          │
│  │account_image_actimg│  │ zip_code_zpc │         │                          │
│  └────────────────────┘  └──────────────┘         │                          │
│                                                   │                          │
└───────────────────────────────────────────────────┼──────────────────────────┘
                                                    │
┌───────────────────────────────────────────────────┼──────────────────────────┐
│                              TOOLS GROUP          │                          │
├───────────────────────────────────────────────────┼──────────────────────────┤
│                                                   │                          │
│  ┌──────────────────┐                             │                          │
│  │ tool_condition_tcn│                            │                          │
│  └────────┬─────────┘                             │                          │
│           │                                       │                          │
│           ▼                                       │                          │
│  ┌─────────────────┐◄─────────────────────────────┘                          │
│  │    tool_tol     │                                                         │
│  └────────┬────────┘                                                         │
│           │                                                                  │
│     ┌───────────────────────────┼─────────────────────────┐                  │
│     │                           │                         │                  │
│     ▼                           ▼                         ▼                  │
│ ┌─────────────────────┐  ┌──────────────────┐ ┌────────────────────┐         │
│ │tool_category_tolcat │  │tool_image_tolimg │ │ vector_image_vec   │         │
│ └────────┬────────────┘  └──────────────────┘ └────────────────────┘         │
│          │                                                                   │
│          ▼                                                                   │
│   ┌──────────────┐                                                           │
│   │ category_cat │                                                           │
│   └──────────────┘                                                           │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                            BORROWING GROUP                                   │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────┐                                                        │
│  │ borrow_status_bst│                                                        │
│  └────────┬─────────┘                                                        │
│           │                                                                  │
│           ▼                                                                  │
│  ┌─────────────────┐       ┌────────────────────────┐                        │
│  │   borrow_brw    │◄──────│  availability_block_abl│                        │
│  └────────┬────────┘       └───────────┬────────────┘                        │
│           │                            │                                     │
│           │                            ▼                                     │
│           │                   ┌────────────────┐                             │
│           │                   │  block_type_btp│                             │
│           │                   └────────────────┘                             │
│           │                                                                  │
└───────────┼──────────────────────────────────────────────────────────────────┘
            │
┌───────────┼──────────────────────────────────────────────────────────────────┐
│           │                RATINGS & DISPUTES GROUP                          │
├───────────┼──────────────────────────────────────────────────────────────────┤
│           │                                                                  │
│           ▼                                                                  │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐           │
│  │ user_rating_urt │    │ tool_rating_trt │    │   dispute_dsp   │           │
│  └────────┬────────┘    └─────────────────┘    └────────┬────────┘           │
│           │                                             │                    │
│           ▼                                             ▼                    │
│  ┌─────────────────┐                          ┌──────────────────┐           │
│  │ rating_role_rtr │                          │dispute_status_dst│           │
│  └─────────────────┘                          └──────────────────┘           │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                          USER INTERACTIONS GROUP                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐               │
│  │ bookmark_acttol │  │ notification_ntf│  │  search_log_slg │               │
│  └─────────────────┘  └────────┬────────┘  └─────────────────┘               │
│                                │                                             │
│                                ▼                                             │
│                      ┌──────────────────────┐                                │
│                      │ notification_type_ntt│                                │
│                      └──────────────────────┘                                │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                          FUTURE EXPANSION GROUP                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│                         ┌─────────────────┐                                  │
│                         │    event_evt    │                                  │
│                         └─────────────────┘                                  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Naming Conventions

This database uses a consistent naming convention:

- **Table names:** `entity_suffix` (e.g., `account_act`, `tool_tol`)
- **Column names:** `column_name_suffix` where suffix matches the table suffix
- **Primary keys:** `id_suffix` (e.g., `id_act`, `id_tol`)
- **Foreign keys:** `id_referenced_table_current_table` (e.g., `id_act_brw` for
  account FK in borrow table)
- **Indexes:** Descriptive names prefixed with `idx_`, `uq_`, or `fulltext_`

This convention ensures clarity about which table each column belongs to and
makes SQL queries more readable.
