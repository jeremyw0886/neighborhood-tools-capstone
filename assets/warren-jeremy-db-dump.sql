-- ============================================================
-- NeighborhoodTools Database Dump File
-- ============================================================
-- Author:      Jeremy Warren
-- Updated:     April 26, 2026
-- Database:    MySQL 8.0.16 or later
-- Description: Full schema + seed data for the NeighborhoodTools
--              peer-to-peer tool-lending platform.
--
-- Navigate by section divider (`-- === ... ===`):
--   1. Session Setup            — charset, SQL mode, DB create
--   2. Teardown                 — DROP VIEW / FUNCTION / PROCEDURE / TRIGGER / EVENT / TABLE
--   3. Tables                   — lookups, geography, accounts, tools,
--                                 borrows, ratings, disputes, notifications,
--                                 events, audit, TOS, waivers, handovers,
--                                 incidents, deposits, payments, materialized
--   4. Triggers                 — per-table BEFORE/AFTER guards + lookup locks
--   5. Helper Functions         — fn_get_*_id lookup resolvers, fn_is_tool_available
--   6. Views                    — read-only query surfaces (+ _fast_v variants)
--   7. Stored Procedures        — all writes and bulk refreshes
--   8. Events                   — scheduled maintenance (DISABLED; run via cron)
--   9. Geography Seed           — ZIP codes + neighborhoods (NC service area)
--  10. Sample Data              — lookups, accounts, tools, borrows, and activity
--  11. Summary Refresh + COMMIT — populate materialized tables
--  12. Session Restore          — revert OLD_* session variables
-- ============================================================

-- ============================================================
-- 1. SESSION SETUP
-- ============================================================
-- Snapshot client session state into @OLD_* variables, lock the
-- charset/collation to utf8mb4_0900_ai_ci, force a strict SQL
-- mode, and anchor the session to UTC. Restored at file end.

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES';
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';

CREATE DATABASE IF NOT EXISTS neighborhoodtools
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE neighborhoodtools;

-- ============================================================
-- 2. TEARDOWN — DROP EXISTING OBJECTS
-- ============================================================
-- FK checks disabled so objects can be dropped in any order.
-- Order: views → functions → procedures → triggers → events →
-- tables (child-before-parent). FK checks are re-enabled just
-- before the CREATE TABLE block.

SET FOREIGN_KEY_CHECKS = 0;

-- ---- Drop views ----
DROP VIEW IF EXISTS category_summary_fast_v;
DROP VIEW IF EXISTS tool_statistics_fast_v;
DROP VIEW IF EXISTS user_reputation_fast_v;
DROP VIEW IF EXISTS neighborhood_summary_fast_v;
DROP VIEW IF EXISTS upcoming_event_v;
DROP VIEW IF EXISTS category_summary_v;
DROP VIEW IF EXISTS user_bookmarks_v;
DROP VIEW IF EXISTS unread_notification_v;
DROP VIEW IF EXISTS pending_handover_v;
DROP VIEW IF EXISTS open_incident_v;
DROP VIEW IF EXISTS pending_waiver_v;
DROP VIEW IF EXISTS tos_acceptance_required_v;
DROP VIEW IF EXISTS current_tos_v;
DROP VIEW IF EXISTS pending_deposit_v;
DROP VIEW IF EXISTS open_dispute_v;
DROP VIEW IF EXISTS payment_history_v;
DROP VIEW IF EXISTS neighborhood_summary_v;
DROP VIEW IF EXISTS tool_statistics_v;
DROP VIEW IF EXISTS user_reputation_v;
DROP VIEW IF EXISTS tool_detail_v;
DROP VIEW IF EXISTS tool_availability_v;
DROP VIEW IF EXISTS account_profile_v;
DROP VIEW IF EXISTS pending_request_v;
DROP VIEW IF EXISTS overdue_borrow_v;
DROP VIEW IF EXISTS active_borrow_v;
DROP VIEW IF EXISTS available_tool_v;
DROP VIEW IF EXISTS active_account_v;

-- ---- Drop triggers ----
DROP TRIGGER IF EXISTS trg_incident_report_before_insert;
DROP TRIGGER IF EXISTS trg_handover_verification_before_insert;
DROP TRIGGER IF EXISTS trg_borrow_waiver_before_insert;
DROP TRIGGER IF EXISTS trg_dispute_message_before_insert;
DROP TRIGGER IF EXISTS trg_dispute_before_insert;
DROP TRIGGER IF EXISTS trg_tool_rating_before_insert;
DROP TRIGGER IF EXISTS trg_user_rating_after_delete;
DROP TRIGGER IF EXISTS trg_user_rating_after_update;
DROP TRIGGER IF EXISTS trg_user_rating_after_insert;
DROP TRIGGER IF EXISTS trg_user_rating_before_update;
DROP TRIGGER IF EXISTS trg_user_rating_before_insert;
DROP TRIGGER IF EXISTS trg_availability_block_before_update;
DROP TRIGGER IF EXISTS trg_availability_block_before_insert;
DROP TRIGGER IF EXISTS trg_borrow_before_update;
DROP TRIGGER IF EXISTS trg_borrow_before_insert;
DROP TRIGGER IF EXISTS trg_bookmark_before_insert;
DROP TRIGGER IF EXISTS trg_tool_before_update;
DROP TRIGGER IF EXISTS trg_tool_before_insert;
DROP TRIGGER IF EXISTS trg_account_before_update;
DROP TRIGGER IF EXISTS trg_nbhzpc_before_update;
DROP TRIGGER IF EXISTS trg_nbhzpc_before_insert;
DROP TRIGGER IF EXISTS trg_tos_before_insert;

DROP TRIGGER IF EXISTS trg_payment_provider_before_update;
DROP TRIGGER IF EXISTS trg_payment_provider_before_delete;
DROP TRIGGER IF EXISTS trg_incident_type_before_update;
DROP TRIGGER IF EXISTS trg_incident_type_before_delete;
DROP TRIGGER IF EXISTS trg_waiver_type_before_update;
DROP TRIGGER IF EXISTS trg_waiver_type_before_delete;
DROP TRIGGER IF EXISTS trg_dispute_message_type_before_update;
DROP TRIGGER IF EXISTS trg_dispute_message_type_before_delete;
DROP TRIGGER IF EXISTS trg_dispute_status_before_update;
DROP TRIGGER IF EXISTS trg_dispute_status_before_delete;
DROP TRIGGER IF EXISTS trg_notification_type_before_update;
DROP TRIGGER IF EXISTS trg_notification_type_before_delete;
DROP TRIGGER IF EXISTS trg_tool_condition_before_update;
DROP TRIGGER IF EXISTS trg_tool_condition_before_delete;
DROP TRIGGER IF EXISTS trg_role_before_update;
DROP TRIGGER IF EXISTS trg_role_before_delete;
DROP TRIGGER IF EXISTS trg_deposit_status_before_update;
DROP TRIGGER IF EXISTS trg_deposit_status_before_delete;
DROP TRIGGER IF EXISTS trg_handover_type_before_update;
DROP TRIGGER IF EXISTS trg_handover_type_before_delete;
DROP TRIGGER IF EXISTS trg_rating_role_before_update;
DROP TRIGGER IF EXISTS trg_rating_role_before_delete;
DROP TRIGGER IF EXISTS trg_block_type_before_update;
DROP TRIGGER IF EXISTS trg_block_type_before_delete;
DROP TRIGGER IF EXISTS trg_borrow_status_before_update;
DROP TRIGGER IF EXISTS trg_borrow_status_before_delete;
DROP TRIGGER IF EXISTS trg_account_status_before_update;
DROP TRIGGER IF EXISTS trg_account_status_before_delete;

-- ---- Drop scheduled events ----
DROP EVENT IF EXISTS evt_daily_stat_midnight;
DROP EVENT IF EXISTS evt_refresh_tool_statistics_every_2h;
DROP EVENT IF EXISTS evt_refresh_user_reputation_every_4h;
DROP EVENT IF EXISTS evt_refresh_summaries_hourly;
DROP EVENT IF EXISTS evt_send_overdue_notifications;
DROP EVENT IF EXISTS evt_cleanup_expired_handovers;
DROP EVENT IF EXISTS evt_archive_old_notifications;
DROP EVENT IF EXISTS evt_cleanup_search_logs;
DROP EVENT IF EXISTS evt_expire_stale_approved_borrows;

-- ---- Drop stored procedures ----
DROP PROCEDURE IF EXISTS sp_refresh_all_summaries;
DROP PROCEDURE IF EXISTS sp_refresh_platform_daily_stat;
DROP PROCEDURE IF EXISTS sp_refresh_category_summary;
DROP PROCEDURE IF EXISTS sp_refresh_tool_statistics;
DROP PROCEDURE IF EXISTS sp_refresh_user_reputation_for;
DROP PROCEDURE IF EXISTS sp_refresh_user_reputation;
DROP PROCEDURE IF EXISTS sp_refresh_neighborhood_summary;
DROP PROCEDURE IF EXISTS sp_create_tos_version;
DROP PROCEDURE IF EXISTS sp_extend_loan;
DROP PROCEDURE IF EXISTS sp_create_borrow_request;
DROP PROCEDURE IF EXISTS sp_approve_borrow_request;
DROP PROCEDURE IF EXISTS sp_deny_borrow_request;
DROP PROCEDURE IF EXISTS sp_complete_pickup;
DROP PROCEDURE IF EXISTS sp_complete_return;
DROP PROCEDURE IF EXISTS sp_cancel_borrow_request;
DROP PROCEDURE IF EXISTS sp_rate_user;
DROP PROCEDURE IF EXISTS sp_rate_tool;
DROP PROCEDURE IF EXISTS sp_send_notification;
DROP PROCEDURE IF EXISTS sp_mark_notifications_read;
DROP PROCEDURE IF EXISTS sp_send_overdue_notifications;
DROP PROCEDURE IF EXISTS sp_cleanup_expired_handover_codes;
DROP PROCEDURE IF EXISTS sp_archive_old_notifications;
DROP PROCEDURE IF EXISTS sp_cleanup_old_search_logs;
DROP PROCEDURE IF EXISTS sp_release_deposit_on_return;
DROP PROCEDURE IF EXISTS sp_search_available_tools;
DROP PROCEDURE IF EXISTS sp_get_user_borrow_history;
DROP PROCEDURE IF EXISTS sp_clear_read_notifications;
DROP PROCEDURE IF EXISTS sp_expire_stale_approved_borrows;
DROP PROCEDURE IF EXISTS sp_process_stale_approved_borrows;
DROP PROCEDURE IF EXISTS sp_forfeit_deposit;
DROP PROCEDURE IF EXISTS sp_create_bookmark;
DROP PROCEDURE IF EXISTS sp_delete_bookmark;

-- ---- Drop helper functions ----
DROP FUNCTION IF EXISTS fn_get_account_status_id;
DROP FUNCTION IF EXISTS fn_get_borrow_status_id;
DROP FUNCTION IF EXISTS fn_get_block_type_id;
DROP FUNCTION IF EXISTS fn_get_rating_role_id;
DROP FUNCTION IF EXISTS fn_get_notification_type_id;
DROP FUNCTION IF EXISTS fn_get_deposit_status_id;
DROP FUNCTION IF EXISTS fn_get_dispute_status_id;
DROP FUNCTION IF EXISTS fn_get_handover_type_id;
DROP FUNCTION IF EXISTS fn_is_tool_available;

-- ---- Drop tables (child-before-parent to satisfy FKs when checks are re-enabled) ----
DROP TABLE IF EXISTS platform_daily_stat_pds;
DROP TABLE IF EXISTS category_summary_mat;
DROP TABLE IF EXISTS tool_statistics_mat;
DROP TABLE IF EXISTS user_reputation_mat;
DROP TABLE IF EXISTS neighborhood_summary_mat;
DROP TABLE IF EXISTS payment_transaction_meta_ptm;
DROP TABLE IF EXISTS payment_transaction_ptx;
DROP TABLE IF EXISTS payment_transaction_type_ptt;
DROP TABLE IF EXISTS security_deposit_sdp;
DROP TABLE IF EXISTS loan_extension_lex;
DROP TABLE IF EXISTS incident_photo_iph;
DROP TABLE IF EXISTS incident_report_irt;
DROP TABLE IF EXISTS handover_verification_hov;
DROP TABLE IF EXISTS borrow_waiver_bwv;
DROP TABLE IF EXISTS tos_acceptance_tac;
DROP TABLE IF EXISTS terms_of_service_tos;
DROP TABLE IF EXISTS payment_provider_ppv;
DROP TABLE IF EXISTS deposit_status_dps;
DROP TABLE IF EXISTS incident_type_ity;
DROP TABLE IF EXISTS handover_type_hot;
DROP TABLE IF EXISTS waiver_type_wtp;
DROP TABLE IF EXISTS audit_log_detail_ald;
DROP TABLE IF EXISTS audit_log_aud;
DROP TABLE IF EXISTS phpbb_integration_php;
DROP TABLE IF EXISTS event_attendee_eya;
DROP TABLE IF EXISTS event_meta_evm;
DROP TABLE IF EXISTS event_evt;
DROP TABLE IF EXISTS search_log_slg;
DROP TABLE IF EXISTS notification_preference_ntp;
DROP TABLE IF EXISTS notification_ntf;
DROP TABLE IF EXISTS dispute_message_dsm;
DROP TABLE IF EXISTS dispute_dsp;
DROP TABLE IF EXISTS tool_rating_trt;
DROP TABLE IF EXISTS user_rating_urt;
DROP TABLE IF EXISTS availability_block_avb;
DROP TABLE IF EXISTS borrow_bor;
DROP TABLE IF EXISTS tool_bookmark_acctol;
DROP TABLE IF EXISTS tool_category_tolcat;
DROP TABLE IF EXISTS tool_image_tim;
DROP TABLE IF EXISTS tool_meta_tlm;
DROP TABLE IF EXISTS tool_tol;
DROP TABLE IF EXISTS fuel_type_ftp;
DROP TABLE IF EXISTS category_cat;
DROP TABLE IF EXISTS vector_image_vec;
DROP TABLE IF EXISTS avatar_vector_avv;
DROP TABLE IF EXISTS password_reset_pwr;
DROP TABLE IF EXISTS account_bio_abi;
DROP TABLE IF EXISTS account_image_aim;
DROP TABLE IF EXISTS account_meta_acm;
DROP TABLE IF EXISTS account_acc;
DROP TABLE IF EXISTS neighborhood_zip_nbhzpc;
DROP TABLE IF EXISTS neighborhood_meta_nbm;
DROP TABLE IF EXISTS neighborhood_nbh;
DROP TABLE IF EXISTS zip_code_zpc;
DROP TABLE IF EXISTS notification_type_ntt;
DROP TABLE IF EXISTS dispute_message_type_dmt;
DROP TABLE IF EXISTS dispute_status_dst;
DROP TABLE IF EXISTS rating_role_rtr;
DROP TABLE IF EXISTS block_type_btp;
DROP TABLE IF EXISTS borrow_status_bst;
DROP TABLE IF EXISTS tool_condition_tcd;
DROP TABLE IF EXISTS state_sta;
DROP TABLE IF EXISTS contact_preference_cpr;
DROP TABLE IF EXISTS account_status_ast;
DROP TABLE IF EXISTS role_rol;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 3. TABLES
-- ============================================================
-- Naming: tables use 3-letter suffixes (tool_tol), columns carry
-- the same suffix (tool_name_tol), PKs are id_{suffix}, FKs are
-- id_{foreign}_{local}. Booleans use is_ prefix; timestamps use
-- created_at_{suffix}. All tables are InnoDB.
--
-- Creation order follows FK dependencies (parent-before-child):
--   3a. Lookup tables       — enum-style reference data
--   3b. Geography           — zip_code_zpc, neighborhood_nbh, neighborhood_zip_nbhzpc
--   3c. Accounts & profile  — account_acc + meta/image/bio/avatar/password-reset
--   3d. Categories & icons  — vector_image_vec, category_cat, fuel_type_ftp
--   3e. Tools & linkage     — tool_tol + meta/image/category/bookmark
--   3f. Borrow activity     — borrow_bor, availability_block_avb
--   3g. Ratings             — user_rating_urt, tool_rating_trt
--   3h. Disputes            — dispute_dsp, dispute_message_dsm
--   3i. Notifications       — notification_ntf, notification_preference_ntp
--   3j. Search & events     — search_log_slg, event_evt, event_meta_evm, event_attendee_eya
--   3k. phpBB integration   — phpbb_integration_php (reserved for forum linkage)
--   3l. Audit log           — audit_log_aud, audit_log_detail_ald
--   3m. Terms of service    — terms_of_service_tos, tos_acceptance_tac
--   3n. Borrow waivers      — borrow_waiver_bwv
--   3o. Handover codes      — handover_verification_hov
--   3p. Incidents           — incident_report_irt, incident_photo_iph
--   3q. Loan extensions     — loan_extension_lex
--   3r. Deposits & payments — security_deposit_sdp, payment_transaction_*
--   3s. Materialized tables — *_mat summaries + platform_daily_stat_pds

-- ---- 3a. Lookup tables ----
CREATE TABLE role_rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    role_name_rol VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'member, admin, super_admin'
) ENGINE=InnoDB;

CREATE TABLE account_status_ast (
    id_ast INT AUTO_INCREMENT PRIMARY KEY,
    status_name_ast VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pending, active, suspended, deleted'
) ENGINE=InnoDB;

CREATE TABLE contact_preference_cpr (
    id_cpr INT AUTO_INCREMENT PRIMARY KEY,
    preference_name_cpr VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'email, phone, both, app'
) ENGINE=InnoDB;

CREATE TABLE state_sta (
    id_sta INT AUTO_INCREMENT PRIMARY KEY,
    state_code_sta VARCHAR(2) NOT NULL UNIQUE
        COMMENT 'Two-letter US state code',
    state_name_sta VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'Full US state name'
) ENGINE=InnoDB
    COMMENT='US state lookup table for address normalization';

CREATE TABLE tool_condition_tcd (
    id_tcd INT AUTO_INCREMENT PRIMARY KEY,
    condition_name_tcd VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'new, good, fair, poor'
) ENGINE=InnoDB;

CREATE TABLE borrow_status_bst (
    id_bst INT AUTO_INCREMENT PRIMARY KEY,
    status_name_bst VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'requested, approved, borrowed, returned, denied, cancelled'
) ENGINE=InnoDB
    COMMENT='Use name lookups or views at runtime. Enforce with BEFORE UPDATE/DELETE triggers and explicit seeding if IDs must be locked.';

CREATE TABLE block_type_btp (
    id_btp INT AUTO_INCREMENT PRIMARY KEY,
    type_name_btp VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'admin, borrow'
) ENGINE=InnoDB;

CREATE TABLE rating_role_rtr (
    id_rtr INT AUTO_INCREMENT PRIMARY KEY,
    role_name_rtr VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'lender, borrower'
) ENGINE=InnoDB;

CREATE TABLE dispute_status_dst (
    id_dst INT AUTO_INCREMENT PRIMARY KEY,
    status_name_dst VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'open, resolved, dismissed'
) ENGINE=InnoDB;

CREATE TABLE dispute_message_type_dmt (
    id_dmt INT AUTO_INCREMENT PRIMARY KEY,
    type_name_dmt VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'initial_report, response, admin_note, resolution'
) ENGINE=InnoDB;

CREATE TABLE notification_type_ntt (
    id_ntt INT AUTO_INCREMENT PRIMARY KEY,
    type_name_ntt VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'request, approval, due, return, rating, denial, role_change'
) ENGINE=InnoDB;

CREATE TABLE waiver_type_wtp (
    id_wtp INT AUTO_INCREMENT PRIMARY KEY,
    type_name_wtp VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'borrow_waiver, condition_acknowledgment, liability_release'
) ENGINE=InnoDB;

CREATE TABLE handover_type_hot (
    id_hot INT AUTO_INCREMENT PRIMARY KEY,
    type_name_hot VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pickup, return'
) ENGINE=InnoDB;

CREATE TABLE incident_type_ity (
    id_ity INT AUTO_INCREMENT PRIMARY KEY,
    type_name_ity VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'damage, theft, loss, injury, late_return, condition_dispute, other'
) ENGINE=InnoDB;

CREATE TABLE deposit_status_dps (
    id_dps INT AUTO_INCREMENT PRIMARY KEY,
    status_name_dps VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pending, held, released, forfeited, partial_release'
) ENGINE=InnoDB;

CREATE TABLE payment_provider_ppv (
    id_ppv INT AUTO_INCREMENT PRIMARY KEY,
    provider_name_ppv VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'stripe, paypal, manual',
    is_active_ppv TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---- 3b. Geography: ZIP codes + neighborhoods ----
CREATE TABLE zip_code_zpc (
    zip_code_zpc VARCHAR(10) PRIMARY KEY,
    latitude_zpc DECIMAL(9,6) NOT NULL,
    longitude_zpc DECIMAL(9,6) NOT NULL,
    location_point_zpc POINT NOT NULL SRID 4326
        COMMENT 'Spatial POINT for indexed proximity queries (SRID 4326 = WGS84)',
    SPATIAL INDEX idx_location_zpc (location_point_zpc)
) ENGINE=InnoDB
    COMMENT='ZIP code table with spatial indexing. Proximity queries use ST_Distance_Sphere (meters). 1 mile = 1609.344 meters. SRID 4326 = WGS84 standard GPS coordinates.';

CREATE TABLE neighborhood_nbh (
    id_nbh INT AUTO_INCREMENT PRIMARY KEY,
    neighborhood_name_nbh VARCHAR(100) NOT NULL UNIQUE
        COMMENT 'Name of local community/service area',
    city_name_nbh VARCHAR(100)
        COMMENT 'Primary city for this neighborhood',
    id_sta_nbh INT NOT NULL
        COMMENT 'State the neighborhood is primarily in',
    latitude_nbh DECIMAL(9,6) NOT NULL,
    longitude_nbh DECIMAL(9,6) NOT NULL,
    location_point_nbh POINT NOT NULL SRID 4326
        COMMENT 'Spatial POINT for indexed proximity queries (SRID 4326 = WGS84)',
    created_at_nbh TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_nbh TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_state_nbh (id_sta_nbh),
    INDEX idx_city_nbh (city_name_nbh),
    SPATIAL INDEX idx_location_nbh (location_point_nbh),
    CONSTRAINT fk_neighborhood_state FOREIGN KEY (id_sta_nbh)
        REFERENCES state_sta (id_sta) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Neighborhood entity with spatial indexing. Proximity queries use ST_Distance_Sphere (meters). 1 mile = 1609.344 meters. SRID 4326 = WGS84 standard GPS coordinates.';

CREATE TABLE neighborhood_meta_nbm (
    id_nbm INT AUTO_INCREMENT PRIMARY KEY,
    id_nbh_nbm INT NOT NULL,
    meta_key_nbm VARCHAR(100) NOT NULL,
    meta_value_nbm VARCHAR(255) NOT NULL,
    created_at_nbm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_neighborhood_meta_nbm (id_nbh_nbm, meta_key_nbm),
    INDEX idx_meta_key_nbm (meta_key_nbm),
    CONSTRAINT fk_nbm_neighborhood FOREIGN KEY (id_nbh_nbm)
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional neighborhood metadata in key/value rows (strict 1NF/3NF).';

CREATE TABLE neighborhood_zip_nbhzpc (
    id_nbhzpc INT AUTO_INCREMENT PRIMARY KEY,
    id_nbh_nbhzpc INT NOT NULL,
    zip_code_nbhzpc VARCHAR(10) NOT NULL,
    is_primary_nbhzpc TINYINT UNSIGNED DEFAULT 0
        COMMENT 'True = primary neighborhood for this ZIP; only one allowed per ZIP',
    created_at_nbhzpc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_neighborhood_zip_nbhzpc (id_nbh_nbhzpc, zip_code_nbhzpc),
    INDEX idx_zip_primary_nbhzpc (zip_code_nbhzpc, is_primary_nbhzpc),
    CONSTRAINT fk_nbhzpc_neighborhood FOREIGN KEY (id_nbh_nbhzpc)
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_nbhzpc_zip FOREIGN KEY (zip_code_nbhzpc)
        REFERENCES zip_code_zpc (zip_code_zpc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Junction table: neighborhoods can contain multiple ZIPs, ZIPs can belong to multiple neighborhoods. Handles edge cases where ZIP codes cross neighborhood/community boundaries.';

-- ---- 3c. Accounts & profile ----
CREATE TABLE avatar_vector_avv (
    id_avv INT AUTO_INCREMENT PRIMARY KEY,
    file_name_avv VARCHAR(255) NOT NULL,
    description_text_avv VARCHAR(255),
    is_active_avv TINYINT UNSIGNED NOT NULL DEFAULT 1,
    id_acc_avv INT NOT NULL
        COMMENT 'Account that uploaded this vector',
    uploaded_at_avv TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_avv (is_active_avv),
    INDEX idx_uploader_avv (id_acc_avv)
) ENGINE=InnoDB
    COMMENT='SVG avatar vectors selectable by users for their profile picture';
-- Note: FK fk_avatar_vector_account added via ALTER TABLE after account_acc exists

CREATE TABLE account_acc (
    id_acc INT AUTO_INCREMENT PRIMARY KEY,
    first_name_acc VARCHAR(100) NOT NULL,
    last_name_acc VARCHAR(100) NOT NULL,
    username_acc VARCHAR(30) NOT NULL
        COMMENT 'Public display name — unique, 3-30 chars, alphanumeric + underscores',
    phone_number_acc VARCHAR(20),
    email_address_acc VARCHAR(255) NOT NULL UNIQUE
        COMMENT 'Primary login credential - used for authentication',
    street_address_acc VARCHAR(255)
        COMMENT 'Optional for privacy - ZIP required',
    zip_code_acc VARCHAR(10) NOT NULL,
    id_nbh_acc INT
        COMMENT 'Optional neighborhood membership; state derivable via neighborhood_nbh.id_sta_nbh',
    password_hash_acc VARCHAR(255) NOT NULL
        COMMENT 'bcrypt or argon2 hash only',
    id_rol_acc INT NOT NULL,
    id_ast_acc INT NOT NULL,
    id_cpr_acc INT NOT NULL,
    is_verified_acc TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_purged_acc TINYINT UNSIGNED NOT NULL DEFAULT 0,
    has_consent_acc TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_login_at_acc TIMESTAMP NULL,
    created_at_acc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_acc TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at_acc TIMESTAMP NULL
        COMMENT 'Set via trigger when id_ast_acc changes to deleted status; NULL = active',
    id_avv_acc INT
        COMMENT 'Selected avatar vector; NULL = no vector chosen',
    UNIQUE INDEX idx_username_acc (username_acc),
    INDEX idx_zip_acc (zip_code_acc),
    INDEX idx_role_acc (id_rol_acc),
    INDEX idx_status_verified_acc (id_ast_acc, is_verified_acc),
    INDEX idx_contact_preference_acc (id_cpr_acc),
    INDEX idx_neighborhood_acc (id_nbh_acc),
    INDEX idx_status_neighborhood_verified_acc (id_ast_acc, id_nbh_acc, is_verified_acc),
    INDEX idx_last_login_acc (last_login_at_acc),
    INDEX idx_created_at_acc (created_at_acc),
    INDEX idx_avatar_vector_acc (id_avv_acc),
    CONSTRAINT fk_account_role FOREIGN KEY (id_rol_acc)
        REFERENCES role_rol (id_rol) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_account_status FOREIGN KEY (id_ast_acc)
        REFERENCES account_status_ast (id_ast) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_account_contact_pref FOREIGN KEY (id_cpr_acc)
        REFERENCES contact_preference_cpr (id_cpr) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_account_zip FOREIGN KEY (zip_code_acc)
        REFERENCES zip_code_zpc (zip_code_zpc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_account_neighborhood FOREIGN KEY (id_nbh_acc)
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_account_avatar_vector FOREIGN KEY (id_avv_acc)
        REFERENCES avatar_vector_avv (id_avv) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_email_format CHECK (email_address_acc REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
) ENGINE=InnoDB
    COMMENT='Main account table. Soft-delete: id_ast_acc = deleted status is the single source of truth.';

-- Deferred FK: avatar_vector_avv.id_acc_avv -> account_acc (circular dependency)
ALTER TABLE avatar_vector_avv
    ADD CONSTRAINT fk_avatar_vector_account FOREIGN KEY (id_acc_avv)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT;

CREATE TABLE account_meta_acm (
    id_acm INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_acm INT NOT NULL,
    meta_key_acm VARCHAR(100) NOT NULL,
    meta_value_acm VARCHAR(255) NOT NULL,
    created_at_acm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_account_meta_acm (id_acc_acm, meta_key_acm),
    INDEX idx_meta_key_acm (meta_key_acm),
    CONSTRAINT fk_acm_account FOREIGN KEY (id_acc_acm)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional account metadata in key/value rows (strict 1NF/3NF).';

CREATE TABLE account_image_aim (
    id_aim INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_aim INT NOT NULL,
    file_name_aim VARCHAR(255) NOT NULL,
    width_aim SMALLINT UNSIGNED NULL
        COMMENT 'Intrinsic width in pixels after EXIF auto-orient (square crop, so height matches)',
    alt_text_aim VARCHAR(255),
    is_primary_aim TINYINT UNSIGNED NOT NULL DEFAULT 0,
    focal_x_aim TINYINT UNSIGNED NOT NULL DEFAULT 50
        COMMENT 'Horizontal focal point 0-100 for object-position',
    focal_y_aim TINYINT UNSIGNED NOT NULL DEFAULT 50
        COMMENT 'Vertical focal point 0-100 for object-position',
    uploaded_at_aim TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    primary_flag_aim TINYINT GENERATED ALWAYS AS (
        IF(is_primary_aim, 1, NULL)
    ) STORED,
    INDEX idx_account_primary_aim (id_acc_aim, is_primary_aim),
    UNIQUE INDEX uq_one_primary_per_account_aim (id_acc_aim, primary_flag_aim),
    CONSTRAINT fk_account_image_account FOREIGN KEY (id_acc_aim)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Profile images for accounts. Single-primary enforced via generated column + composite unique index.';

CREATE TABLE account_bio_abi (
    id_abi INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_abi INT NOT NULL UNIQUE
        COMMENT 'One bio per account; row exists only if bio provided',
    bio_text_abi TEXT NOT NULL,
    created_at_abi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_abi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_account_bio_account FOREIGN KEY (id_acc_abi)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional bio text stored separately to save space - only populated when user provides a bio';

CREATE TABLE password_reset_pwr (
    id_pwr INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_pwr INT NOT NULL,
    token_hash_pwr VARCHAR(64) NOT NULL,
    expires_at_pwr TIMESTAMP NOT NULL,
    created_at_pwr TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at_pwr TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_token_hash_pwr (token_hash_pwr),
    INDEX idx_acc_expires_pwr (id_acc_pwr, expires_at_pwr),
    CONSTRAINT fk_pwr_acc FOREIGN KEY (id_acc_pwr)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- 3d. Categories & icons ----
CREATE TABLE vector_image_vec (
    id_vec INT AUTO_INCREMENT PRIMARY KEY,
    file_name_vec VARCHAR(255) NOT NULL,
    description_text_vec TEXT,
    id_acc_vec INT NOT NULL
        COMMENT 'uploaded_by account (admin)',
    uploaded_at_vec TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploader_vec (id_acc_vec),
    CONSTRAINT fk_vector_image_account FOREIGN KEY (id_acc_vec)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE category_cat (
    id_cat INT AUTO_INCREMENT PRIMARY KEY,
    category_name_cat VARCHAR(100) NOT NULL UNIQUE,
    id_vec_cat INT
        COMMENT 'Optional category icon from vector_image_vec',
    INDEX idx_category_vector_icon_cat (id_vec_cat),
    CONSTRAINT fk_category_vector FOREIGN KEY (id_vec_cat)
        REFERENCES vector_image_vec (id_vec) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fuel_type_ftp (
    id_ftp INT AUTO_INCREMENT PRIMARY KEY,
    fuel_name_ftp VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'gasoline, diesel, propane, two-stroke mix, electric/battery, kerosene, natural gas'
) ENGINE=InnoDB;

-- ---- 3e. Tools & linkage ----
CREATE TABLE tool_tol (
    id_tol INT AUTO_INCREMENT PRIMARY KEY,
    tool_name_tol VARCHAR(255) NOT NULL,
    tool_description_tol TEXT,
    id_tcd_tol INT NOT NULL,
    id_acc_tol INT NOT NULL
        COMMENT 'owner account',
    serial_number_tol VARCHAR(50),
    rental_fee_tol DECIMAL(6,2) DEFAULT 0.00
        COMMENT '0 = free sharing',
    default_loan_duration_hours_tol INT DEFAULT 168
        COMMENT 'Owner default in hours; UI converts days/weeks',
    is_available_tol TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Owner listing toggle - see Note for true availability logic',
    is_deleted_tol TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Soft-delete flag -- excluded from all views',
    deleted_at_tol TIMESTAMP NULL
        COMMENT 'When soft-delete occurred -- used for 90-day deferred image cleanup',
    is_deposit_required_tol TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Lender requires refundable security deposit',
    default_deposit_amount_tol DECIMAL(8,2) DEFAULT 0.00
        COMMENT 'Default deposit amount; 0 = no deposit required',
    estimated_value_tol DECIMAL(8,2)
        COMMENT 'Estimated tool value for insurance/deposit reference',
    preexisting_conditions_tol TEXT
        COMMENT 'Lender disclosure of any pre-existing damage, wear, or conditions - ToS requirement',
    is_insurance_recommended_tol TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Flag for high-value tools ($1000+) where insurance is recommended',
    id_ftp_tol INT
        COMMENT 'FK to fuel_type_ftp -- NULL means tool does not use fuel',
    created_at_tol TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_tol TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner_available_tol (id_acc_tol, is_available_tol),
    INDEX idx_condition_tol (id_tcd_tol),
    INDEX idx_available_owner_created_tol (is_available_tol, id_acc_tol, created_at_tol),
    INDEX idx_available_created_fee_tol (is_available_tol, is_deleted_tol, created_at_tol, rental_fee_tol),
    INDEX idx_created_at_tol (created_at_tol),
    INDEX idx_rental_fee_tol (rental_fee_tol),
    INDEX idx_is_deposit_required_tol (is_deposit_required_tol),
    INDEX idx_fuel_type_tol (id_ftp_tol),
    INDEX idx_deleted_tol (is_deleted_tol),
    INDEX idx_deleted_at_tol (deleted_at_tol),
    FULLTEXT INDEX fulltext_tool_search_tol (tool_name_tol, tool_description_tol),
    CONSTRAINT fk_tool_condition FOREIGN KEY (id_tcd_tol)
        REFERENCES tool_condition_tcd (id_tcd) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tool_owner FOREIGN KEY (id_acc_tol)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tool_fuel_type FOREIGN KEY (id_ftp_tol)
        REFERENCES fuel_type_ftp (id_ftp) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_rental_fee_non_negative CHECK (rental_fee_tol >= 0),
    CONSTRAINT chk_deposit_amount_non_negative CHECK (default_deposit_amount_tol >= 0),
    CONSTRAINT chk_estimated_value_non_negative CHECK (estimated_value_tol IS NULL OR estimated_value_tol >= 0)
) ENGINE=InnoDB
    COMMENT='is_available_tol = owner intent only. True availability requires: is_available_tol = true AND no overlapping availability_block_avb AND no active borrow_bor. Legal fields: is_deposit_required_tol, preexisting_conditions_tol, is_insurance_recommended_tol.';

CREATE TABLE tool_meta_tlm (
    id_tlm INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_tlm INT NOT NULL,
    meta_key_tlm VARCHAR(100) NOT NULL,
    meta_value_tlm VARCHAR(255) NOT NULL,
    created_at_tlm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_tool_meta_tlm (id_tol_tlm, meta_key_tlm),
    INDEX idx_meta_key_tlm (meta_key_tlm),
    CONSTRAINT fk_tlm_tool FOREIGN KEY (id_tol_tlm)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional tool metadata in key/value rows (strict 1NF/3NF).';

CREATE TABLE tool_image_tim (
    id_tim INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_tim INT NOT NULL,
    file_name_tim VARCHAR(255) NOT NULL,
    alt_text_tim VARCHAR(255),
    is_primary_tim TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sort_order_tim INT DEFAULT 0
        COMMENT 'Display order for gallery',
    focal_x_tim TINYINT UNSIGNED NOT NULL DEFAULT 50
        COMMENT 'Horizontal focal point 0-100 for object-position',
    focal_y_tim TINYINT UNSIGNED NOT NULL DEFAULT 50
        COMMENT 'Vertical focal point 0-100 for object-position',
    width_tim SMALLINT UNSIGNED
        COMMENT 'Intrinsic pixel width stored at upload time',
    uploaded_at_tim TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    primary_flag_tim TINYINT GENERATED ALWAYS AS (
        IF(is_primary_tim, 1, NULL)
    ) STORED,
    INDEX idx_tool_primary_tim (id_tol_tim, is_primary_tim),
    INDEX idx_tool_sort_tim (id_tol_tim, sort_order_tim),
    UNIQUE INDEX uq_one_primary_per_tool_tim (id_tol_tim, primary_flag_tim),
    CONSTRAINT fk_tool_image_tool FOREIGN KEY (id_tol_tim)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Tool images with gallery ordering. Single-primary enforced via generated column + composite unique index.';

CREATE TABLE tool_category_tolcat (
    id_tolcat INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_tolcat INT NOT NULL,
    id_cat_tolcat INT NOT NULL,
    created_at_tolcat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_tool_category_tolcat (id_tol_tolcat, id_cat_tolcat),
    INDEX idx_category_tolcat (id_cat_tolcat),
    CONSTRAINT fk_tolcat_tool FOREIGN KEY (id_tol_tolcat)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tolcat_category FOREIGN KEY (id_cat_tolcat)
        REFERENCES category_cat (id_cat) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Junction table: tools can belong to multiple categories';

CREATE TABLE tool_bookmark_acctol (
    id_acctol INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_acctol INT NOT NULL,
    id_tol_acctol INT NOT NULL,
    created_at_acctol TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_account_tool_acctol (id_acc_acctol, id_tol_acctol),
    INDEX idx_tool_acctol (id_tol_acctol),
    CONSTRAINT fk_bookmark_account FOREIGN KEY (id_acc_acctol)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_bookmark_tool FOREIGN KEY (id_tol_acctol)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Junction table: accounts can bookmark multiple tools';

-- ---- 3f. Borrow activity ----
CREATE TABLE borrow_bor (
    id_bor INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_bor INT NOT NULL,
    id_acc_bor INT NOT NULL
        COMMENT 'borrower account',
    id_bst_bor INT NOT NULL,
    loan_duration_hours_bor INT NOT NULL
        COMMENT 'Agreed period in hours; UI converts',
    requested_at_bor TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at_bor TIMESTAMP NULL,
    borrowed_at_bor TIMESTAMP NULL,
    due_at_bor TIMESTAMP NULL
        COMMENT 'Set via trigger on status -> borrowed',
    returned_at_bor TIMESTAMP NULL,
    cancelled_at_bor TIMESTAMP NULL,
    notes_text_bor TEXT,
    is_contact_shared_bor TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at_bor TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_due_tool_bor (id_bst_bor, due_at_bor, id_tol_bor),
    INDEX idx_tool_status_bor (id_tol_bor, id_bst_bor),
    INDEX idx_tool_borrower_bor (id_tol_bor, id_acc_bor),
    INDEX idx_borrower_bor (id_acc_bor),
    INDEX idx_returned_bor (returned_at_bor),
    INDEX idx_requested_at_bor (requested_at_bor),
    INDEX idx_borrower_status_bor (id_acc_bor, id_bst_bor, requested_at_bor),
    CONSTRAINT fk_borrow_tool FOREIGN KEY (id_tol_bor)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_borrow_borrower FOREIGN KEY (id_acc_bor)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_borrow_status FOREIGN KEY (id_bst_bor)
        REFERENCES borrow_status_bst (id_bst) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_returned_cancelled_exclusive CHECK (
        returned_at_bor IS NULL OR cancelled_at_bor IS NULL
    ),
    CONSTRAINT chk_borrow_loan_duration_bor CHECK (loan_duration_hours_bor > 0)
) ENGINE=InnoDB
    COMMENT='Borrow transaction table. CHECK constraints enforce timestamp ordering and mutual exclusivity.';

CREATE TABLE availability_block_avb (
    id_avb INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_avb INT NOT NULL,
    id_btp_avb INT NOT NULL,
    start_at_avb TIMESTAMP NOT NULL,
    end_at_avb TIMESTAMP NOT NULL,
    id_bor_avb INT
        COMMENT 'Required for borrow blocks; NULL for admin blocks',
    notes_text_avb TEXT,
    created_at_avb TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_avb TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tool_range_type_avb (id_tol_avb, start_at_avb, end_at_avb, id_btp_avb),
    UNIQUE INDEX uq_borrow_avb (id_bor_avb),
    INDEX idx_block_type_avb (id_btp_avb),
    CONSTRAINT fk_avb_tool FOREIGN KEY (id_tol_avb)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_avb_block_type FOREIGN KEY (id_btp_avb)
        REFERENCES block_type_btp (id_btp) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_avb_borrow FOREIGN KEY (id_bor_avb)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_end_after_start CHECK (end_at_avb > start_at_avb)
) ENGINE=InnoDB
    COMMENT='Availability blocks for tools. Borrow-type blocks require id_bor_avb; admin blocks must have NULL.';

-- ---- 3g. Ratings ----
CREATE TABLE user_rating_urt (
    id_urt INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_urt INT NOT NULL
        COMMENT 'rater account',
    id_acc_target_urt INT NOT NULL
        COMMENT 'ratee account',
    id_bor_urt INT NOT NULL,
    id_rtr_urt INT NOT NULL
        COMMENT 'lender or borrower context',
    score_urt INT NOT NULL
        COMMENT '1-5; enforce via CHECK',
    comment_text_urt TEXT,
    created_at_urt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target_role_score_urt (id_acc_target_urt, id_rtr_urt, score_urt),
    UNIQUE INDEX uq_one_user_rating_per_borrow_urt (id_bor_urt, id_acc_urt, id_rtr_urt),
    INDEX idx_rater_urt (id_acc_urt),
    CONSTRAINT fk_user_rating_rater FOREIGN KEY (id_acc_urt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_rating_target FOREIGN KEY (id_acc_target_urt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_rating_borrow FOREIGN KEY (id_bor_urt)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_rating_role FOREIGN KEY (id_rtr_urt)
        REFERENCES rating_role_rtr (id_rtr) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_score_range_urt CHECK (score_urt BETWEEN 1 AND 5)
) ENGINE=InnoDB
    COMMENT='No self-rating enforced via BEFORE INSERT trigger (CHECK constraint incompatible with FK referential actions in MySQL 8)';

CREATE TABLE tool_rating_trt (
    id_trt INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_trt INT NOT NULL
        COMMENT 'rater account',
    id_tol_trt INT NOT NULL,
    id_bor_trt INT NOT NULL,
    score_trt INT NOT NULL
        COMMENT '1-5; enforce via CHECK',
    comment_text_trt TEXT,
    created_at_trt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tool_score_trt (id_tol_trt, score_trt),
    UNIQUE INDEX uq_one_tool_rating_per_borrow_trt (id_bor_trt, id_tol_trt),
    INDEX idx_rater_trt (id_acc_trt),
    CONSTRAINT fk_tool_rating_rater FOREIGN KEY (id_acc_trt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tool_rating_tool FOREIGN KEY (id_tol_trt)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tool_rating_borrow FOREIGN KEY (id_bor_trt)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_score_range_trt CHECK (score_trt BETWEEN 1 AND 5)
) ENGINE=InnoDB
    COMMENT='UNIQUE per borrow/tool. Covering index on (id_tol_trt, score_trt) enables AVG aggregation without table lookup.';

-- ---- 3h. Disputes ----
CREATE TABLE dispute_dsp (
    id_dsp INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_dsp INT NOT NULL,
    id_acc_dsp INT NOT NULL
        COMMENT 'reporter account',
    subject_text_dsp VARCHAR(255) NOT NULL,
    id_dst_dsp INT NOT NULL,
    id_acc_resolver_dsp INT
        COMMENT 'admin who resolved',
    resolved_at_dsp TIMESTAMP NULL,
    created_at_dsp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_dsp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_acc_updated_by_dsp INT
        COMMENT 'admin who last modified; NULL if system',
    INDEX idx_status_created_dsp (id_dst_dsp, created_at_dsp),
    INDEX idx_borrow_dsp (id_bor_dsp),
    INDEX idx_reporter_dsp (id_acc_dsp),
    INDEX idx_resolver_dsp (id_acc_resolver_dsp),
    INDEX idx_updated_by_dsp (id_acc_updated_by_dsp),
    CONSTRAINT fk_dispute_borrow FOREIGN KEY (id_bor_dsp)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_dispute_reporter FOREIGN KEY (id_acc_dsp)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_dispute_status FOREIGN KEY (id_dst_dsp)
        REFERENCES dispute_status_dst (id_dst) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_dispute_resolver FOREIGN KEY (id_acc_resolver_dsp)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dispute_updated_by FOREIGN KEY (id_acc_updated_by_dsp)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
    COMMENT='Dispute header; messages in dispute_message_dsm.';

CREATE TABLE dispute_message_dsm (
    id_dsm INT AUTO_INCREMENT PRIMARY KEY,
    id_dsp_dsm INT NOT NULL,
    id_acc_dsm INT NOT NULL,
    id_dmt_dsm INT NOT NULL,
    message_text_dsm TEXT NOT NULL,
    is_internal_dsm TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Admin-only if true',
    created_at_dsm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dispute_timeline_dsm (id_dsp_dsm, created_at_dsm),
    INDEX idx_author_dsm (id_acc_dsm),
    INDEX idx_message_type_dsm (id_dmt_dsm),
    CONSTRAINT fk_dsm_dispute FOREIGN KEY (id_dsp_dsm)
        REFERENCES dispute_dsp (id_dsp) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_dsm_author FOREIGN KEY (id_acc_dsm)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_dsm_message_type FOREIGN KEY (id_dmt_dsm)
        REFERENCES dispute_message_type_dmt (id_dmt) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---- 3i. Notifications ----
CREATE TABLE notification_ntf (
    id_ntf INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_ntf INT NOT NULL,
    id_ntt_ntf INT NOT NULL,
    title_ntf VARCHAR(255) NOT NULL,
    body_ntf TEXT,
    id_bor_ntf INT,
    is_read_ntf TINYINT UNSIGNED NOT NULL DEFAULT 0,
    read_at_ntf TIMESTAMP NULL DEFAULT NULL,
    created_at_ntf TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unread_timeline_type_ntf (id_acc_ntf, is_read_ntf, created_at_ntf, id_ntt_ntf),
    INDEX idx_borrow_ntf (id_bor_ntf),
    INDEX idx_type_ntf (id_ntt_ntf),
    INDEX idx_borrow_type_created_ntf (id_bor_ntf, id_ntt_ntf, created_at_ntf),
    CONSTRAINT fk_notification_account FOREIGN KEY (id_acc_ntf)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notification_type FOREIGN KEY (id_ntt_ntf)
        REFERENCES notification_type_ntt (id_ntt) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_notification_borrow FOREIGN KEY (id_bor_ntf)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
    COMMENT='Archival: Delete or move records older than 12 months via scheduled job.';

CREATE TABLE notification_preference_ntp (
    id_ntp INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_ntp INT NOT NULL,
    id_ntt_ntp INT NOT NULL,
    is_enabled_ntp TINYINT UNSIGNED NOT NULL DEFAULT 1,
    updated_at_ntp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_account_type_ntp (id_acc_ntp, id_ntt_ntp),
    INDEX fk_ntp_notification_type (id_ntt_ntp),
    CONSTRAINT fk_ntp_account FOREIGN KEY (id_acc_ntp)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ntp_notification_type FOREIGN KEY (id_ntt_ntp)
        REFERENCES notification_type_ntt (id_ntt) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Opt-out model: no row = enabled. Rows only exist for disabled types.';

-- ---- 3j. Search & events ----
CREATE TABLE search_log_slg (
    id_slg INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_slg INT,
    id_tol_slg INT,
    search_text_slg VARCHAR(255),
    ip_address_slg VARCHAR(45),
    session_id_slg VARCHAR(64),
    created_at_slg TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT INDEX fulltext_search_slg (search_text_slg),
    INDEX idx_created_at_slg (created_at_slg),
    INDEX idx_account_slg (id_acc_slg),
    INDEX idx_tool_slg (id_tol_slg),
    CONSTRAINT fk_search_log_account FOREIGN KEY (id_acc_slg)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_search_log_tool FOREIGN KEY (id_tol_slg)
        REFERENCES tool_tol (id_tol) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
    COMMENT='Search logs for analytics. Archival: Delete or move records older than 12 months via scheduled job.';

CREATE TABLE event_evt (
    id_evt INT AUTO_INCREMENT PRIMARY KEY,
    event_name_evt VARCHAR(255) NOT NULL,
    event_description_evt TEXT,
    start_at_evt TIMESTAMP NOT NULL,
    end_at_evt TIMESTAMP NULL,
    event_address_evt VARCHAR(255)
        COMMENT 'NULL for virtual events',
    id_nbh_evt INT
        COMMENT 'Neighborhood where event takes place',
    id_acc_evt INT NOT NULL
        COMMENT 'created_by account (admin)',
    created_at_evt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_evt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_acc_updated_by_evt INT
        COMMENT 'admin who last modified; NULL if unchanged',
    INDEX idx_date_neighborhood_evt (start_at_evt, id_nbh_evt),
    INDEX idx_creator_evt (id_acc_evt),
    INDEX idx_updated_by_evt (id_acc_updated_by_evt),
    INDEX idx_neighborhood_evt (id_nbh_evt),
    CONSTRAINT fk_event_creator FOREIGN KEY (id_acc_evt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_event_updated_by FOREIGN KEY (id_acc_updated_by_evt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_event_neighborhood FOREIGN KEY (id_nbh_evt)
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_event_time_order_evt
        CHECK (end_at_evt IS NULL OR end_at_evt >= start_at_evt)
) ENGINE=InnoDB;

CREATE TABLE event_meta_evm (
    id_evm INT AUTO_INCREMENT PRIMARY KEY,
    id_evt_evm INT NOT NULL,
    meta_key_evm VARCHAR(100) NOT NULL,
    meta_value_evm VARCHAR(255) NOT NULL,
    created_at_evm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_event_meta_evm (id_evt_evm, meta_key_evm),
    INDEX idx_meta_key_evm (meta_key_evm),
    CONSTRAINT fk_evm_event FOREIGN KEY (id_evt_evm)
        REFERENCES event_evt (id_evt) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional event metadata in key/value rows (strict 1NF/3NF).';

CREATE TABLE event_attendee_eya (
    id_eya INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_eya INT NOT NULL
        COMMENT 'Account who RSVPd',
    id_evt_eya INT NOT NULL
        COMMENT 'Event being attended',
    created_at_eya TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_attendee_event (id_acc_eya, id_evt_eya),
    INDEX idx_event_attendee (id_evt_eya),
    CONSTRAINT fk_eya_account FOREIGN KEY (id_acc_eya)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_eya_event FOREIGN KEY (id_evt_eya)
        REFERENCES event_evt (id_evt) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- 3k. phpBB forum integration (reserved) ----
CREATE TABLE phpbb_integration_php (
    id_php INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_php INT NOT NULL
        COMMENT 'Maps to phpBB user',
    phpbb_user_id_php INT
        COMMENT 'phpBB user ID for SSO linking',
    created_at_php TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_account_php (id_acc_php),
    CONSTRAINT fk_phpbb_account FOREIGN KEY (id_acc_php)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Placeholder for phpBB forum SSO integration';

-- ---- 3l. Audit log ----
CREATE TABLE audit_log_aud (
    id_aud INT AUTO_INCREMENT PRIMARY KEY,
    table_name_aud VARCHAR(64) NOT NULL,
    row_id_aud INT NOT NULL,
    action_aud VARCHAR(10) NOT NULL
        COMMENT 'INSERT, UPDATE, DELETE',
    id_acc_aud INT
        COMMENT 'Account who made the change; NULL if system',
    created_at_aud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table_row_aud (table_name_aud, row_id_aud),
    INDEX idx_account_aud (id_acc_aud),
    INDEX idx_created_at_aud (created_at_aud),
    CONSTRAINT fk_audit_account FOREIGN KEY (id_acc_aud)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
    COMMENT='Future: Audit log for tracking changes. Implement via AFTER INSERT/UPDATE/DELETE triggers. Archival: Delete or move records older than 24 months via scheduled job.';

CREATE TABLE audit_log_detail_ald (
    id_ald INT AUTO_INCREMENT PRIMARY KEY,
    id_aud_ald INT NOT NULL,
    column_name_ald VARCHAR(64) NOT NULL,
    old_value_ald TEXT,
    new_value_ald TEXT,
    created_at_ald TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_detail_column_ald (column_name_ald),
    CONSTRAINT fk_ald_audit FOREIGN KEY (id_aud_ald)
        REFERENCES audit_log_aud (id_aud) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Normalized audit detail rows (strict 1NF/3NF).';

-- ---- 3m. Terms of service ----
CREATE TABLE terms_of_service_tos (
    id_tos INT AUTO_INCREMENT PRIMARY KEY,
    version_tos VARCHAR(20) NOT NULL UNIQUE
        COMMENT 'Version identifier e.g. 1.0, 2.0, 2.1',
    title_tos VARCHAR(255) NOT NULL
        COMMENT 'ToS document title',
    content_tos TEXT NOT NULL
        COMMENT 'Full Terms of Service text',
    summary_tos TEXT
        COMMENT 'Plain-language summary of key terms',
    effective_at_tos TIMESTAMP NOT NULL
        COMMENT 'When this version becomes active',
    superseded_at_tos TIMESTAMP NULL
        COMMENT 'When this version was replaced; NULL = current',
    is_active_tos TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Only one version should be active at a time',
    id_acc_created_by_tos INT NOT NULL
        COMMENT 'Admin who created this version',
    created_at_tos TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_tos (is_active_tos),
    INDEX idx_effective_tos (effective_at_tos),
    INDEX idx_creator_tos (id_acc_created_by_tos),
    CONSTRAINT fk_tos_creator FOREIGN KEY (id_acc_created_by_tos)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Versioned Terms of Service. Key clauses: Platform is matchmaking service, not party to transactions; not liable for damage/theft/loss; users resolve disputes directly; borrowers assume responsibility; lenders must disclose pre-existing conditions; mandatory 24-48hr incident reporting.';

CREATE TABLE tos_acceptance_tac (
    id_tac INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_tac INT NOT NULL,
    id_tos_tac INT NOT NULL,
    ip_address_tac VARCHAR(45)
        COMMENT 'IP address at time of acceptance',
    user_agent_tac VARCHAR(1000)
        COMMENT 'Browser/device info for audit trail',
    accepted_at_tac TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_account_tos_tac (id_acc_tac, id_tos_tac),
    INDEX idx_tos_version_tac (id_tos_tac),
    INDEX idx_accepted_at_tac (accepted_at_tac),
    CONSTRAINT fk_tac_account FOREIGN KEY (id_acc_tac)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tac_tos FOREIGN KEY (id_tos_tac)
        REFERENCES terms_of_service_tos (id_tos) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Records user acceptance of each ToS version. Required during registration and when new ToS versions are published.';

-- ---- 3n. Borrow waivers ----
CREATE TABLE borrow_waiver_bwv (
    id_bwv INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_bwv INT NOT NULL UNIQUE
        COMMENT 'One waiver per borrow; FK to borrow_bor',
    id_wtp_bwv INT NOT NULL
        COMMENT 'FK to waiver_type_wtp',
    id_acc_bwv INT NOT NULL
        COMMENT 'Borrower who signed the waiver',
    is_tool_condition_acknowledged_bwv TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Borrower confirms current tool condition',
    preexisting_conditions_noted_bwv TEXT
        COMMENT 'Snapshot of tool preexisting conditions at time of waiver',
    is_responsibility_accepted_bwv TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Borrower accepts responsibility for tool during borrow',
    is_liability_waiver_accepted_bwv TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Borrower acknowledges platform liability limitations',
    is_insurance_reminder_shown_bwv TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Reminder about personal insurance was displayed',
    ip_address_bwv VARCHAR(45),
    user_agent_bwv VARCHAR(1000),
    signed_at_bwv TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_borrower_bwv (id_acc_bwv),
    INDEX idx_signed_at_bwv (signed_at_bwv),
    INDEX idx_waiver_type_bwv (id_wtp_bwv),
    CONSTRAINT fk_bwv_borrow FOREIGN KEY (id_bor_bwv)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bwv_waiver_type FOREIGN KEY (id_wtp_bwv)
        REFERENCES waiver_type_wtp (id_wtp) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bwv_borrower FOREIGN KEY (id_acc_bwv)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Digital waiver required for each borrow transaction. All acknowledgment booleans must be true for waiver to be valid.';

-- ---- 3o. Handover verification codes ----
CREATE TABLE handover_verification_hov (
    id_hov INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_hov INT NOT NULL
        COMMENT 'FK to borrow_bor',
    id_hot_hov INT NOT NULL
        COMMENT 'FK to handover_type_hot (pickup or return)',
    verification_code_hov CHAR(6) NOT NULL
        COMMENT 'Unique 6-character code for digital handshake (generator: trg_handover_before_insert)',
    id_acc_generator_hov INT NOT NULL
        COMMENT 'Account that generated the code (lender for pickup, borrower for return)',
    id_acc_verifier_hov INT
        COMMENT 'Account that verified the code; NULL until verified',
    condition_notes_hov TEXT
        COMMENT 'Condition notes at handover (photos referenced separately)',
    generated_at_hov TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at_hov TIMESTAMP NOT NULL
        COMMENT 'Code expires after 24 hours',
    verified_at_hov TIMESTAMP NULL
        COMMENT 'When verification completed; NULL = pending',
    UNIQUE INDEX uq_borrow_handover_type_hov (id_bor_hov, id_hot_hov),
    UNIQUE INDEX uq_verification_code_hov (verification_code_hov),
    INDEX idx_generator_hov (id_acc_generator_hov),
    INDEX idx_verifier_hov (id_acc_verifier_hov),
    INDEX idx_expires_at_hov (expires_at_hov),
    INDEX idx_unverified_expires_hov (verified_at_hov, expires_at_hov),
    CONSTRAINT fk_hov_borrow FOREIGN KEY (id_bor_hov)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_hov_handover_type FOREIGN KEY (id_hot_hov)
        REFERENCES handover_type_hot (id_hot) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_hov_generator FOREIGN KEY (id_acc_generator_hov)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_hov_verifier FOREIGN KEY (id_acc_verifier_hov)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
    COMMENT='Digital handshake system. Pickup: Lender generates code, borrower verifies. Return: Borrower generates code, lender verifies. Code expires after 24 hours.';

-- ---- 3p. Incidents ----
CREATE TABLE incident_report_irt (
    id_irt INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_irt INT NOT NULL
        COMMENT 'FK to borrow_bor',
    id_acc_irt INT NOT NULL
        COMMENT 'Account reporting the incident',
    id_ity_irt INT NOT NULL
        COMMENT 'FK to incident_type_ity',
    subject_irt VARCHAR(255) NOT NULL,
    description_irt TEXT NOT NULL,
    incident_occurred_at_irt TIMESTAMP NOT NULL
        COMMENT 'When the incident occurred',
    is_reported_within_deadline_irt TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'True if reported within 48 hours of incident',
    estimated_damage_amount_irt DECIMAL(8,2)
        COMMENT 'Estimated cost of damage/loss',
    resolution_notes_irt TEXT
        COMMENT 'Admin resolution notes',
    resolved_at_irt TIMESTAMP NULL
        COMMENT 'When incident was resolved',
    id_acc_resolved_by_irt INT
        COMMENT 'Admin who resolved',
    created_at_irt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_irt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_borrow_irt (id_bor_irt),
    INDEX idx_reporter_irt (id_acc_irt),
    INDEX idx_incident_type_irt (id_ity_irt),
    INDEX idx_deadline_compliance_irt (is_reported_within_deadline_irt),
    INDEX idx_created_at_irt (created_at_irt),
    INDEX idx_unresolved_irt (resolved_at_irt, created_at_irt),
    CONSTRAINT fk_irt_borrow FOREIGN KEY (id_bor_irt)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_reporter FOREIGN KEY (id_acc_irt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_incident_type FOREIGN KEY (id_ity_irt)
        REFERENCES incident_type_ity (id_ity) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_resolver FOREIGN KEY (id_acc_resolved_by_irt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_damage_amount_non_negative CHECK (estimated_damage_amount_irt IS NULL OR estimated_damage_amount_irt >= 0),
    CONSTRAINT chk_incident_time_order_irt CHECK (incident_occurred_at_irt <= created_at_irt)
) ENGINE=InnoDB
    COMMENT='Mandatory incident reporting for damage, theft, loss, or disputes. ToS requires reporting within 24-48 hours of incident.';

CREATE TABLE incident_photo_iph (
    id_iph INT AUTO_INCREMENT PRIMARY KEY,
    id_irt_iph INT NOT NULL,
    file_name_iph VARCHAR(255) NOT NULL,
    caption_iph VARCHAR(255),
    sort_order_iph INT DEFAULT 0,
    width_iph SMALLINT UNSIGNED NULL
        COMMENT 'Intrinsic width in pixels after EXIF auto-orient',
    height_iph SMALLINT UNSIGNED NULL
        COMMENT 'Intrinsic height in pixels after EXIF auto-orient',
    created_at_iph TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incident_photo_order_iph (id_irt_iph, sort_order_iph),
    CONSTRAINT fk_incident_photo_incident FOREIGN KEY (id_irt_iph)
        REFERENCES incident_report_irt (id_irt) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Incident photos normalized into separate rows (strict 1NF/3NF).';

-- ---- 3q. Loan extensions ----
CREATE TABLE loan_extension_lex (
    id_lex INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_lex INT NOT NULL
        COMMENT 'FK to borrow_bor',
    original_due_at_lex TIMESTAMP NOT NULL
        COMMENT 'Snapshot of due_at_bor before extension',
    extended_hours_lex INT NOT NULL
        COMMENT 'Additional hours granted',
    new_due_at_lex TIMESTAMP NOT NULL
        COMMENT 'New due date after extension',
    reason_lex TEXT
        COMMENT 'Reason for extension',
    id_acc_approved_by_lex INT NOT NULL
        COMMENT 'Lender or admin who approved the extension',
    created_at_lex TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_borrow_lex (id_bor_lex),
    INDEX idx_approver_lex (id_acc_approved_by_lex),
    CONSTRAINT fk_lex_borrow FOREIGN KEY (id_bor_lex)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lex_approver FOREIGN KEY (id_acc_approved_by_lex)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_extended_hours_positive CHECK (extended_hours_lex > 0)
) ENGINE=InnoDB
    COMMENT='Tracks loan extensions with full audit trail.';

-- ---- 3r. Deposits & payments ----
CREATE TABLE security_deposit_sdp (
    id_sdp INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_sdp INT NOT NULL UNIQUE
        COMMENT 'One deposit record per borrow; FK to borrow_bor',
    id_dps_sdp INT NOT NULL
        COMMENT 'FK to deposit_status_dps',
    amount_sdp DECIMAL(8,2) NOT NULL
        COMMENT 'Deposit amount in USD',
    id_ppv_sdp INT NOT NULL
        COMMENT 'FK to payment_provider_ppv (e.g., Stripe)',
    external_payment_id_sdp VARCHAR(255)
        COMMENT 'Stripe PaymentIntent ID or similar',
    held_at_sdp TIMESTAMP NULL
        COMMENT 'When deposit was captured/held',
    released_at_sdp TIMESTAMP NULL
        COMMENT 'When deposit was released back to borrower',
    forfeited_at_sdp TIMESTAMP NULL
        COMMENT 'When deposit was forfeited to lender (damage/loss)',
    forfeited_amount_sdp DECIMAL(8,2)
        COMMENT 'Amount forfeited (may be partial)',
    forfeiture_reason_sdp TEXT
        COMMENT 'Reason for forfeiture',
    id_irt_sdp INT
        COMMENT 'FK to incident_report_irt if forfeited due to incident',
    created_at_sdp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_sdp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_sdp (id_dps_sdp),
    INDEX idx_provider_sdp (id_ppv_sdp),
    INDEX idx_external_id_sdp (external_payment_id_sdp),
    INDEX idx_held_at_sdp (held_at_sdp),
    CONSTRAINT fk_sdp_borrow FOREIGN KEY (id_bor_sdp)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sdp_status FOREIGN KEY (id_dps_sdp)
        REFERENCES deposit_status_dps (id_dps) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sdp_provider FOREIGN KEY (id_ppv_sdp)
        REFERENCES payment_provider_ppv (id_ppv) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sdp_incident FOREIGN KEY (id_irt_sdp)
        REFERENCES incident_report_irt (id_irt) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_deposit_non_negative CHECK (amount_sdp >= 0),
    CONSTRAINT chk_forfeited_non_negative CHECK (forfeited_amount_sdp IS NULL OR forfeited_amount_sdp >= 0)
) ENGINE=InnoDB
    COMMENT='Refundable security deposit tracking. Workflow: pending -> held (escrow via Stripe) -> released (on return) OR forfeited (on incident).';

CREATE TABLE payment_transaction_type_ptt (
    id_ptt        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type_name_ptt VARCHAR(30)      NOT NULL,
    UNIQUE KEY uq_type_name_ptt (type_name_ptt)
) ENGINE=InnoDB
    COMMENT='Lookup for payment transaction types. Replaces free-form transaction_type_ptx VARCHAR.';

CREATE TABLE payment_transaction_ptx (
    id_ptx INT AUTO_INCREMENT PRIMARY KEY,
    id_sdp_ptx INT
        COMMENT 'FK to security_deposit_sdp; NULL for rental fees',
    id_bor_ptx INT NOT NULL
        COMMENT 'FK to borrow_bor',
    id_ppv_ptx INT NOT NULL
        COMMENT 'FK to payment_provider_ppv',
    id_ptt_ptx TINYINT UNSIGNED NOT NULL
        COMMENT 'FK to payment_transaction_type_ptt',
    amount_ptx DECIMAL(8,2) NOT NULL,
    external_transaction_id_ptx VARCHAR(255) NOT NULL
        COMMENT 'Stripe Charge/Transfer ID',
    external_status_ptx VARCHAR(50)
        COMMENT 'Status from payment provider',
    id_acc_from_ptx INT
        COMMENT 'Payer account (borrower for deposits/fees)',
    id_acc_to_ptx INT
        COMMENT 'Payee account (lender for forfeits/fees); NULL for platform-held escrow',
    processed_at_ptx TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_deposit_ptx (id_sdp_ptx),
    INDEX idx_borrow_ptx (id_bor_ptx),
    INDEX idx_external_txn_ptx (external_transaction_id_ptx),
    INDEX idx_txn_type_ptx (id_ptt_ptx),
    INDEX idx_processed_at_ptx (processed_at_ptx),
    CONSTRAINT fk_ptx_deposit FOREIGN KEY (id_sdp_ptx)
        REFERENCES security_deposit_sdp (id_sdp) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ptx_borrow FOREIGN KEY (id_bor_ptx)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptx_provider FOREIGN KEY (id_ppv_ptx)
        REFERENCES payment_provider_ppv (id_ppv) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptx_type FOREIGN KEY (id_ptt_ptx)
        REFERENCES payment_transaction_type_ptt (id_ptt) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptx_from_account FOREIGN KEY (id_acc_from_ptx)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ptx_to_account FOREIGN KEY (id_acc_to_ptx)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_transaction_amount_non_negative CHECK (amount_ptx >= 0)
) ENGINE=InnoDB
    COMMENT='Detailed transaction log for all payment activities. Tracks Stripe integration for deposits and rental fees. Future: insurance provider API integration.';

CREATE TABLE payment_transaction_meta_ptm (
    id_ptm INT AUTO_INCREMENT PRIMARY KEY,
    id_ptx_ptm INT NOT NULL,
    meta_key_ptm VARCHAR(100) NOT NULL,
    meta_value_ptm VARCHAR(255) NOT NULL,
    created_at_ptm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_ptx_meta_ptm (id_ptx_ptm, meta_key_ptm),
    INDEX idx_meta_key_ptm (meta_key_ptm),
    CONSTRAINT fk_ptm_ptx FOREIGN KEY (id_ptx_ptm)
        REFERENCES payment_transaction_ptx (id_ptx) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Optional transaction metadata in key/value rows (strict 1NF/3NF).';

-- ---- 3s. Materialized summary tables + daily platform stats ----
-- Refreshed by sp_refresh_* procedures and fronted by *_fast_v views.
CREATE TABLE neighborhood_summary_mat (
    id_nbh INT NOT NULL PRIMARY KEY,
    neighborhood_name_nbh VARCHAR(100) NOT NULL,
    city_name_nbh VARCHAR(100) NOT NULL,
    state_code_sta CHAR(2) NOT NULL,
    state_name_sta VARCHAR(50) NOT NULL,
    latitude_nbh DECIMAL(9, 6),
    longitude_nbh DECIMAL(9, 6),
    location_point_nbh POINT SRID 4326,
    created_at_nbh TIMESTAMP,
    total_members INT UNSIGNED NOT NULL DEFAULT 0,
    active_members INT UNSIGNED NOT NULL DEFAULT 0,
    verified_members INT UNSIGNED NOT NULL DEFAULT 0,
    total_tools INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools INT UNSIGNED NOT NULL DEFAULT 0,
    active_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows_30d INT UNSIGNED NOT NULL DEFAULT 0,
    upcoming_events INT UNSIGNED NOT NULL DEFAULT 0,
    zip_codes TEXT
        COMMENT 'Associated ZIP codes (comma-separated for display)',
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_state_mat (state_code_sta),
    INDEX idx_city_mat (city_name_nbh),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB
COMMENT='Materialized view: pre-computed neighborhood statistics';

CREATE TABLE user_reputation_mat (
    id_acc INT NOT NULL PRIMARY KEY,
    full_name VARCHAR(101) NOT NULL,
    email_address_acc VARCHAR(255) NOT NULL,
    account_status VARCHAR(30) NOT NULL,
    member_since TIMESTAMP,
    lender_avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    lender_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    borrower_avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    borrower_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    overall_avg_rating DECIMAL(3, 1) DEFAULT NULL,
    total_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    tools_owned INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_lender_rating_mat (lender_avg_rating DESC),
    INDEX idx_borrower_rating_mat (borrower_avg_rating DESC),
    INDEX idx_overall_rating_mat (overall_avg_rating DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB
COMMENT='Materialized view: pre-computed user reputation scores';

CREATE TABLE tool_statistics_mat (
    id_tol INT NOT NULL PRIMARY KEY,
    tool_name_tol VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL,
    owner_name VARCHAR(101) NOT NULL,
    tool_condition VARCHAR(30) NOT NULL,
    rental_fee_tol DECIMAL(6, 2),
    estimated_value_tol DECIMAL(8, 2),
    created_at_tol TIMESTAMP,
    avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    five_star_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    denied_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    total_hours_borrowed INT UNSIGNED NOT NULL DEFAULT 0,
    last_borrowed_at TIMESTAMP NULL,
    incident_count INT UNSIGNED NOT NULL DEFAULT 0,
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_owner_mat (owner_id),
    INDEX idx_avg_rating_mat (avg_rating DESC),
    INDEX idx_total_borrows_mat (total_borrows DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB
COMMENT='Materialized view: pre-computed tool statistics';

CREATE TABLE category_summary_mat (
    id_cat INT NOT NULL PRIMARY KEY,
    category_name_cat VARCHAR(100) NOT NULL,
    category_icon VARCHAR(255),
    total_tools INT UNSIGNED NOT NULL DEFAULT 0,
    listed_tools INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools INT UNSIGNED NOT NULL DEFAULT 0,
    category_avg_rating DECIMAL(3, 1) DEFAULT NULL,
    total_completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    min_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    max_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    avg_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_total_tools_mat (total_tools DESC),
    INDEX idx_available_mat (available_tools DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB
COMMENT='Materialized view: pre-computed category statistics';

CREATE TABLE platform_daily_stat_pds (
    stat_date_pds DATE NOT NULL PRIMARY KEY,
    total_accounts_pds INT UNSIGNED NOT NULL DEFAULT 0,
    active_accounts_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_accounts_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    total_tools_pds INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_tools_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    active_borrows_pds INT UNSIGNED NOT NULL DEFAULT 0,
    completed_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_requests_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    open_disputes_pds INT UNSIGNED NOT NULL DEFAULT 0,
    open_incidents_pds INT UNSIGNED NOT NULL DEFAULT 0,
    overdue_borrows_pds INT UNSIGNED NOT NULL DEFAULT 0,
    deposits_held_total_pds DECIMAL(12, 2) NOT NULL DEFAULT 0,
    platform_avg_rating_pds DECIMAL(3, 2) NULL
        COMMENT 'Platform-wide avg tool rating on this date; NULL when no ratings exist',
    refreshed_at_pds TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_stat_month_pds (stat_date_pds)
) ENGINE=InnoDB
COMMENT='Daily platform statistics for admin dashboard and reporting';

-- ============================================================
-- 4. TRIGGERS
-- ============================================================
-- Two groups:
--   (a) Per-table BEFORE/AFTER triggers that enforce business
--       rules (borrow status transitions, rating constraints,
--       availability windows, waiver truthiness, etc.) and keep
--       denormalized data in sync (e.g. user_reputation_mat).
--   (b) Lookup protection triggers (trg_*_before_delete /
--       _before_update) that prevent deletion or renaming of
--       system-required enum values referenced by other triggers.

-- ---- 4a. Per-table business-rule triggers ----

DELIMITER $$
CREATE TRIGGER trg_nbhzpc_before_insert
BEFORE INSERT ON neighborhood_zip_nbhzpc
FOR EACH ROW
BEGIN
    IF NEW.is_primary_nbhzpc = TRUE THEN
        IF EXISTS (
            SELECT 1 FROM neighborhood_zip_nbhzpc
            WHERE zip_code_nbhzpc = NEW.zip_code_nbhzpc
              AND is_primary_nbhzpc = TRUE
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Only one primary neighborhood per ZIP allowed';
        END IF;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_nbhzpc_before_update
BEFORE UPDATE ON neighborhood_zip_nbhzpc
FOR EACH ROW
BEGIN
    IF NEW.is_primary_nbhzpc = TRUE THEN
        IF EXISTS (
            SELECT 1 FROM neighborhood_zip_nbhzpc
            WHERE zip_code_nbhzpc = NEW.zip_code_nbhzpc
              AND is_primary_nbhzpc = TRUE
              AND id_nbhzpc != NEW.id_nbhzpc
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Only one primary neighborhood per ZIP allowed';
        END IF;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_account_before_update
BEFORE UPDATE ON account_acc
FOR EACH ROW
main_block: BEGIN
    DECLARE deleted_status_id INT;

    IF NEW.id_ast_acc = OLD.id_ast_acc THEN
        LEAVE main_block;
    END IF;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    IF deleted_status_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'System error: required lookup value "deleted" not found in account_status_ast';
    END IF;

    IF NEW.id_ast_acc = deleted_status_id THEN
        SET NEW.deleted_at_acc = NOW();
    ELSEIF OLD.id_ast_acc = deleted_status_id THEN
        SET NEW.deleted_at_acc = NULL;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_tool_before_insert
BEFORE INSERT ON tool_tol
FOR EACH ROW
BEGIN
    DECLARE owner_status_id INT;
    DECLARE deleted_status_id INT;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO owner_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_tol;

    IF owner_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create tool: owner account is deleted';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_tool_before_update
BEFORE UPDATE ON tool_tol
FOR EACH ROW
BEGIN
    DECLARE owner_status_name VARCHAR(30);

    IF NEW.id_acc_tol != OLD.id_acc_tol THEN
        SELECT ast.status_name_ast INTO owner_status_name
        FROM account_acc acc
        JOIN account_status_ast ast ON acc.id_ast_acc = ast.id_ast
        WHERE acc.id_acc = NEW.id_acc_tol;

        IF owner_status_name = 'deleted' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cannot transfer tool: new owner account is deleted';
        END IF;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_bookmark_before_insert
BEFORE INSERT ON tool_bookmark_acctol
FOR EACH ROW
BEGIN
    DECLARE account_status_id INT;
    DECLARE deleted_status_id INT;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO account_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_acctol;

    IF account_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create bookmark: account is deleted';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_borrow_before_insert
BEFORE INSERT ON borrow_bor
FOR EACH ROW
BEGIN
    DECLARE borrower_status_id INT;
    DECLARE owner_status_id INT;
    DECLARE tool_owner_id INT;
    DECLARE deleted_status_id INT;
    DECLARE requested_status_id INT;

    SET requested_status_id = fn_get_borrow_status_id('requested');
    SET deleted_status_id   = fn_get_account_status_id('deleted');

    IF @seeding IS NULL AND NEW.id_bst_bor != requested_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Borrow rows must be inserted with status=requested';
    END IF;

    SELECT id_acc_tol INTO tool_owner_id
    FROM tool_tol
    WHERE id_tol = NEW.id_tol_bor;

    IF tool_owner_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'System error: tool not found for borrow request';
    END IF;

    IF tool_owner_id = NEW.id_acc_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot borrow own tool';
    END IF;

    SELECT id_ast_acc INTO borrower_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_bor;

    IF borrower_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create borrow request: borrower account is deleted';
    END IF;

    SELECT id_ast_acc INTO owner_status_id
    FROM account_acc
    WHERE id_acc = tool_owner_id;

    IF owner_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create borrow request: tool owner account is deleted';
    END IF;

    IF NEW.approved_at_bor IS NOT NULL AND NEW.requested_at_bor IS NOT NULL
       AND NEW.approved_at_bor < NEW.requested_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'approved_at must be after requested_at';
    END IF;

    IF NEW.borrowed_at_bor IS NOT NULL AND NEW.approved_at_bor IS NOT NULL
       AND NEW.borrowed_at_bor < NEW.approved_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'borrowed_at must be after approved_at';
    END IF;

    IF NEW.returned_at_bor IS NOT NULL AND NEW.borrowed_at_bor IS NOT NULL
       AND NEW.returned_at_bor < NEW.borrowed_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'returned_at must be after borrowed_at';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_borrow_before_update
BEFORE UPDATE ON borrow_bor
FOR EACH ROW
BEGIN
    DECLARE requested_status_id INT;
    DECLARE approved_status_id INT;
    DECLARE borrowed_status_id INT;
    DECLARE returned_status_id INT;
    DECLARE denied_status_id INT;
    DECLARE cancelled_status_id INT;
    DECLARE tool_owner_id INT;

    IF NEW.id_acc_bor != OLD.id_acc_bor OR NEW.id_tol_bor != OLD.id_tol_bor THEN
        SELECT id_acc_tol INTO tool_owner_id
        FROM tool_tol
        WHERE id_tol = NEW.id_tol_bor;

        IF tool_owner_id IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'System error: tool not found for borrow request';
        END IF;

        IF tool_owner_id = NEW.id_acc_bor THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cannot borrow own tool';
        END IF;
    END IF;

    IF OLD.id_bst_bor != NEW.id_bst_bor OR
       (OLD.due_at_bor IS NOT NULL AND NEW.due_at_bor != OLD.due_at_bor) THEN

        SET requested_status_id = fn_get_borrow_status_id('requested');
        SET approved_status_id  = fn_get_borrow_status_id('approved');
        SET borrowed_status_id  = fn_get_borrow_status_id('borrowed');
        SET returned_status_id  = fn_get_borrow_status_id('returned');
        SET denied_status_id    = fn_get_borrow_status_id('denied');
        SET cancelled_status_id = fn_get_borrow_status_id('cancelled');

        -- ============================================================
        -- STATUS TRANSITION VALIDATION (only when status changing)
        -- ============================================================
        IF OLD.id_bst_bor != NEW.id_bst_bor THEN
            IF OLD.id_bst_bor IN (returned_status_id, denied_status_id, cancelled_status_id) THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot change status: borrow is in terminal state';
            END IF;

            IF OLD.id_bst_bor = borrowed_status_id
               AND NEW.id_bst_bor IN (requested_status_id, approved_status_id) THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot regress status from borrowed to earlier state';
            END IF;

            IF OLD.id_bst_bor = approved_status_id
               AND NEW.id_bst_bor = requested_status_id THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot regress status from approved to requested';
            END IF;

            IF OLD.id_bst_bor = requested_status_id
               AND NEW.id_bst_bor IN (borrowed_status_id, returned_status_id) THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot skip approval step: must transition through approved status';
            END IF;

            IF OLD.id_bst_bor = approved_status_id
               AND NEW.id_bst_bor = returned_status_id THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot skip borrowed step: must transition through borrowed status';
            END IF;
        END IF;

        -- ============================================================
        -- STRICT TIMESTAMP COHERENCE
        -- ============================================================
        IF NEW.id_bst_bor = approved_status_id THEN
            IF NEW.requested_at_bor IS NULL THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot set approved status: requested_at timestamp missing';
            END IF;
            IF NEW.approved_at_bor IS NULL THEN
                SET NEW.approved_at_bor = NOW();
            END IF;
        END IF;

        IF NEW.id_bst_bor = borrowed_status_id THEN
            IF NEW.approved_at_bor IS NULL THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot set borrowed status: approved_at timestamp missing';
            END IF;
            IF NEW.borrowed_at_bor IS NULL THEN
                SET NEW.borrowed_at_bor = NOW();
            END IF;
            IF NEW.due_at_bor IS NULL THEN
                SET NEW.due_at_bor = DATE_ADD(NEW.borrowed_at_bor, INTERVAL NEW.loan_duration_hours_bor HOUR);
            END IF;
        END IF;

        IF NEW.id_bst_bor = returned_status_id THEN
            IF NEW.borrowed_at_bor IS NULL THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot set returned status: borrowed_at timestamp missing';
            END IF;
            IF NEW.returned_at_bor IS NULL THEN
                SET NEW.returned_at_bor = NOW();
            END IF;
        END IF;

        IF NEW.id_bst_bor = cancelled_status_id THEN
            IF NEW.cancelled_at_bor IS NULL THEN
                SET NEW.cancelled_at_bor = NOW();
            END IF;
        END IF;

        -- ============================================================
        -- DUE DATE PROTECTION
        -- ============================================================
        IF OLD.due_at_bor IS NOT NULL AND NEW.due_at_bor != OLD.due_at_bor THEN
            IF NOT EXISTS (
                SELECT 1 FROM loan_extension_lex
                WHERE id_bor_lex = NEW.id_bor
                  AND new_due_at_lex = NEW.due_at_bor
            ) THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot modify due date without approved extension record';
            END IF;
        END IF;
    END IF;

    -- ============================================================
    -- TIMESTAMP ORDERING VALIDATION (always check)
    -- ============================================================
    IF NEW.approved_at_bor IS NOT NULL AND NEW.requested_at_bor IS NOT NULL
       AND NEW.approved_at_bor < NEW.requested_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'approved_at must be after requested_at';
    END IF;

    IF NEW.borrowed_at_bor IS NOT NULL AND NEW.approved_at_bor IS NOT NULL
       AND NEW.borrowed_at_bor < NEW.approved_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'borrowed_at must be after approved_at';
    END IF;

    IF NEW.returned_at_bor IS NOT NULL AND NEW.borrowed_at_bor IS NOT NULL
       AND NEW.returned_at_bor < NEW.borrowed_at_bor THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'returned_at must be after borrowed_at';
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_availability_block_before_insert
BEFORE INSERT ON availability_block_avb
FOR EACH ROW
BEGIN
    DECLARE block_type_name VARCHAR(30);

    SELECT type_name_btp INTO block_type_name
    FROM block_type_btp
    WHERE id_btp = NEW.id_btp_avb;

    IF block_type_name IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'System error: block type not found in block_type_btp';
    END IF;

    IF block_type_name = 'borrow' AND NEW.id_bor_avb IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Borrow-type blocks require id_bor_avb';
    END IF;

    IF block_type_name = 'admin' AND NEW.id_bor_avb IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Admin-type blocks must have NULL id_bor_avb';
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_availability_block_before_update
BEFORE UPDATE ON availability_block_avb
FOR EACH ROW
BEGIN
    DECLARE block_type_name VARCHAR(30);

    IF NEW.id_btp_avb != OLD.id_btp_avb OR
       NOT (NEW.id_bor_avb <=> OLD.id_bor_avb) THEN

        SELECT type_name_btp INTO block_type_name
        FROM block_type_btp
        WHERE id_btp = NEW.id_btp_avb;

        IF block_type_name IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'System error: block type not found in block_type_btp';
        END IF;

        IF block_type_name = 'borrow' AND NEW.id_bor_avb IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Borrow-type blocks require id_bor_avb';
        END IF;

        IF block_type_name = 'admin' AND NEW.id_bor_avb IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Admin-type blocks must have NULL id_bor_avb';
        END IF;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_user_rating_before_insert
BEFORE INSERT ON user_rating_urt
FOR EACH ROW
BEGIN
    DECLARE rater_status_id INT;
    DECLARE target_status_id INT;
    DECLARE deleted_status_id INT;
    DECLARE borrow_borrower_id INT;
    DECLARE borrow_tool_owner_id INT;

    IF NEW.id_acc_urt = NEW.id_acc_target_urt THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rate yourself';
    END IF;

    SELECT bor.id_acc_bor, tol.id_acc_tol
    INTO borrow_borrower_id, borrow_tool_owner_id
    FROM borrow_bor bor
    JOIN tool_tol tol ON bor.id_tol_bor = tol.id_tol
    WHERE bor.id_bor = NEW.id_bor_urt;

    IF borrow_borrower_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'System error: borrow transaction not found for rating';
    END IF;

    IF NEW.id_acc_urt != borrow_borrower_id AND NEW.id_acc_urt != borrow_tool_owner_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create rating: rater was not a participant in this borrow';
    END IF;

    IF NEW.id_acc_target_urt != borrow_borrower_id AND NEW.id_acc_target_urt != borrow_tool_owner_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create rating: target was not a participant in this borrow';
    END IF;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO rater_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_urt;

    IF rater_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create rating: rater account is deleted';
    END IF;

    SELECT id_ast_acc INTO target_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_target_urt;

    IF target_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create rating: target account is deleted';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_user_rating_before_update
BEFORE UPDATE ON user_rating_urt
FOR EACH ROW
BEGIN
    IF NEW.id_acc_urt = NEW.id_acc_target_urt THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rate yourself';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_user_rating_after_insert
AFTER INSERT ON user_rating_urt
FOR EACH ROW
BEGIN
    CALL sp_refresh_user_reputation_for(NEW.id_acc_target_urt);
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_user_rating_after_update
AFTER UPDATE ON user_rating_urt
FOR EACH ROW
BEGIN
    CALL sp_refresh_user_reputation_for(NEW.id_acc_target_urt);
    IF NEW.id_acc_target_urt != OLD.id_acc_target_urt THEN
        CALL sp_refresh_user_reputation_for(OLD.id_acc_target_urt);
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_user_rating_after_delete
AFTER DELETE ON user_rating_urt
FOR EACH ROW
BEGIN
    CALL sp_refresh_user_reputation_for(OLD.id_acc_target_urt);
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_tool_rating_before_insert
BEFORE INSERT ON tool_rating_trt
FOR EACH ROW
BEGIN
    DECLARE rater_status_id INT;
    DECLARE deleted_status_id INT;
    DECLARE borrow_borrower_id INT;
    DECLARE borrow_tool_id INT;

    SELECT id_acc_bor, id_tol_bor
    INTO borrow_borrower_id, borrow_tool_id
    FROM borrow_bor
    WHERE id_bor = NEW.id_bor_trt;

    IF NEW.id_acc_trt != borrow_borrower_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create tool rating: only the borrower can rate the tool';
    END IF;

    IF NEW.id_tol_trt != borrow_tool_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create tool rating: tool does not match borrow transaction';
    END IF;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO rater_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_trt;

    IF rater_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create rating: rater account is deleted';
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_dispute_before_insert
BEFORE INSERT ON dispute_dsp
FOR EACH ROW
BEGIN
    DECLARE reporter_status_id INT;
    DECLARE deleted_status_id INT;
    DECLARE borrow_borrower_id INT;
    DECLARE borrow_tool_owner_id INT;

    SELECT bor.id_acc_bor, tol.id_acc_tol
    INTO borrow_borrower_id, borrow_tool_owner_id
    FROM borrow_bor bor
    JOIN tool_tol tol ON bor.id_tol_bor = tol.id_tol
    WHERE bor.id_bor = NEW.id_bor_dsp;

    IF NEW.id_acc_dsp != borrow_borrower_id AND NEW.id_acc_dsp != borrow_tool_owner_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create dispute: reporter was not a participant in this borrow';
    END IF;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO reporter_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_dsp;

    IF reporter_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create dispute: reporter account is deleted';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_dispute_message_before_insert
BEFORE INSERT ON dispute_message_dsm
FOR EACH ROW
BEGIN
    DECLARE author_status_id INT;
    DECLARE deleted_status_id INT;

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    SELECT id_ast_acc INTO author_status_id
    FROM account_acc
    WHERE id_acc = NEW.id_acc_dsm;

    IF author_status_id = deleted_status_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot create dispute message: author account is deleted';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_borrow_waiver_before_insert
BEFORE INSERT ON borrow_waiver_bwv
FOR EACH ROW
BEGIN
    IF NEW.is_tool_condition_acknowledged_bwv = FALSE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Waiver requires tool condition acknowledgment';
    END IF;

    IF NEW.is_responsibility_accepted_bwv = FALSE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Waiver requires responsibility acceptance';
    END IF;

    IF NEW.is_liability_waiver_accepted_bwv = FALSE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Waiver requires liability waiver acceptance';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_handover_verification_before_insert
BEFORE INSERT ON handover_verification_hov
FOR EACH ROW
BEGIN
    SET NEW.verification_code_hov = UPPER(SUBSTRING(
        MD5(CONCAT(
            RAND(),
            MICROSECOND(NOW(6)),
            CONNECTION_ID(),
            UUID()
        )), 1, 6));

    SET NEW.expires_at_hov = DATE_ADD(NOW(), INTERVAL 24 HOUR);
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_incident_report_before_insert
BEFORE INSERT ON incident_report_irt
FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(HOUR, NEW.incident_occurred_at_irt, NOW()) > 48 THEN
        SET NEW.is_reported_within_deadline_irt = FALSE;
    ELSE
        SET NEW.is_reported_within_deadline_irt = TRUE;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_tos_before_insert
BEFORE INSERT ON terms_of_service_tos
FOR EACH ROW
BEGIN
    SET NEW.created_at_tos = COALESCE(NEW.created_at_tos, NOW());
END$$
DELIMITER ;

-- ---- 4b. Lookup protection triggers ----
-- Prevent deletion or renaming of system-required lookup values that triggers depend on for enforcement logic.

DELIMITER $$
CREATE TRIGGER trg_account_status_before_delete
BEFORE DELETE ON account_status_ast
FOR EACH ROW
BEGIN
    IF OLD.status_name_ast IN ('pending', 'active', 'suspended', 'deleted') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required status values';
    END IF;
END$$

CREATE TRIGGER trg_account_status_before_update
BEFORE UPDATE ON account_status_ast
FOR EACH ROW
BEGIN
    IF OLD.status_name_ast IN ('pending', 'active', 'suspended', 'deleted')
       AND NEW.status_name_ast != OLD.status_name_ast THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required status values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_borrow_status_before_delete
BEFORE DELETE ON borrow_status_bst
FOR EACH ROW
BEGIN
    IF OLD.status_name_bst IN ('requested', 'approved', 'borrowed', 'returned', 'denied', 'cancelled') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required borrow status values';
    END IF;
END$$

CREATE TRIGGER trg_borrow_status_before_update
BEFORE UPDATE ON borrow_status_bst
FOR EACH ROW
BEGIN
    IF OLD.status_name_bst IN ('requested', 'approved', 'borrowed', 'returned', 'denied', 'cancelled')
       AND NEW.status_name_bst != OLD.status_name_bst THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required borrow status values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_block_type_before_delete
BEFORE DELETE ON block_type_btp
FOR EACH ROW
BEGIN
    IF OLD.type_name_btp IN ('admin', 'borrow') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required block type values';
    END IF;
END$$

CREATE TRIGGER trg_block_type_before_update
BEFORE UPDATE ON block_type_btp
FOR EACH ROW
BEGIN
    IF OLD.type_name_btp IN ('admin', 'borrow')
       AND NEW.type_name_btp != OLD.type_name_btp THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required block type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_rating_role_before_delete
BEFORE DELETE ON rating_role_rtr
FOR EACH ROW
BEGIN
    IF OLD.role_name_rtr IN ('lender', 'borrower') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required rating role values';
    END IF;
END$$

CREATE TRIGGER trg_rating_role_before_update
BEFORE UPDATE ON rating_role_rtr
FOR EACH ROW
BEGIN
    IF OLD.role_name_rtr IN ('lender', 'borrower')
       AND NEW.role_name_rtr != OLD.role_name_rtr THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required rating role values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_handover_type_before_delete
BEFORE DELETE ON handover_type_hot
FOR EACH ROW
BEGIN
    IF OLD.type_name_hot IN ('pickup', 'return') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required handover type values';
    END IF;
END$$

CREATE TRIGGER trg_handover_type_before_update
BEFORE UPDATE ON handover_type_hot
FOR EACH ROW
BEGIN
    IF OLD.type_name_hot IN ('pickup', 'return')
       AND NEW.type_name_hot != OLD.type_name_hot THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required handover type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_deposit_status_before_delete
BEFORE DELETE ON deposit_status_dps
FOR EACH ROW
BEGIN
    IF OLD.status_name_dps IN ('pending', 'held', 'released', 'forfeited', 'partial_release') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required deposit status values';
    END IF;
END$$

CREATE TRIGGER trg_deposit_status_before_update
BEFORE UPDATE ON deposit_status_dps
FOR EACH ROW
BEGIN
    IF OLD.status_name_dps IN ('pending', 'held', 'released', 'forfeited', 'partial_release')
       AND NEW.status_name_dps != OLD.status_name_dps THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required deposit status values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_role_before_delete
BEFORE DELETE ON role_rol
FOR EACH ROW
BEGIN
    IF OLD.role_name_rol IN ('member', 'admin', 'super_admin') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required role values';
    END IF;
END$$

CREATE TRIGGER trg_role_before_update
BEFORE UPDATE ON role_rol
FOR EACH ROW
BEGIN
    IF OLD.role_name_rol IN ('member', 'admin', 'super_admin')
       AND NEW.role_name_rol != OLD.role_name_rol THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required role values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_tool_condition_before_delete
BEFORE DELETE ON tool_condition_tcd
FOR EACH ROW
BEGIN
    IF OLD.condition_name_tcd IN ('new', 'good', 'fair', 'poor') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required tool condition values';
    END IF;
END$$

CREATE TRIGGER trg_tool_condition_before_update
BEFORE UPDATE ON tool_condition_tcd
FOR EACH ROW
BEGIN
    IF OLD.condition_name_tcd IN ('new', 'good', 'fair', 'poor')
       AND NEW.condition_name_tcd != OLD.condition_name_tcd THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required tool condition values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_notification_type_before_delete
BEFORE DELETE ON notification_type_ntt
FOR EACH ROW
BEGIN
    IF OLD.type_name_ntt IN ('request', 'approval', 'due', 'return', 'rating', 'denial', 'role_change') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required notification type values';
    END IF;
END$$

CREATE TRIGGER trg_notification_type_before_update
BEFORE UPDATE ON notification_type_ntt
FOR EACH ROW
BEGIN
    IF OLD.type_name_ntt IN ('request', 'approval', 'due', 'return', 'rating', 'denial', 'role_change')
       AND NEW.type_name_ntt != OLD.type_name_ntt THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required notification type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_dispute_status_before_delete
BEFORE DELETE ON dispute_status_dst
FOR EACH ROW
BEGIN
    IF OLD.status_name_dst IN ('open', 'resolved', 'dismissed') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required dispute status values';
    END IF;
END$$

CREATE TRIGGER trg_dispute_status_before_update
BEFORE UPDATE ON dispute_status_dst
FOR EACH ROW
BEGIN
    IF OLD.status_name_dst IN ('open', 'resolved', 'dismissed')
       AND NEW.status_name_dst != OLD.status_name_dst THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required dispute status values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_dispute_message_type_before_delete
BEFORE DELETE ON dispute_message_type_dmt
FOR EACH ROW
BEGIN
    IF OLD.type_name_dmt IN ('initial_report', 'response', 'admin_note', 'resolution') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required dispute message type values';
    END IF;
END$$

CREATE TRIGGER trg_dispute_message_type_before_update
BEFORE UPDATE ON dispute_message_type_dmt
FOR EACH ROW
BEGIN
    IF OLD.type_name_dmt IN ('initial_report', 'response', 'admin_note', 'resolution')
       AND NEW.type_name_dmt != OLD.type_name_dmt THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required dispute message type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_waiver_type_before_delete
BEFORE DELETE ON waiver_type_wtp
FOR EACH ROW
BEGIN
    IF OLD.type_name_wtp IN ('borrow_waiver', 'condition_acknowledgment', 'liability_release') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required waiver type values';
    END IF;
END$$

CREATE TRIGGER trg_waiver_type_before_update
BEFORE UPDATE ON waiver_type_wtp
FOR EACH ROW
BEGIN
    IF OLD.type_name_wtp IN ('borrow_waiver', 'condition_acknowledgment', 'liability_release')
       AND NEW.type_name_wtp != OLD.type_name_wtp THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required waiver type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_incident_type_before_delete
BEFORE DELETE ON incident_type_ity
FOR EACH ROW
BEGIN
    IF OLD.type_name_ity IN ('damage', 'theft', 'loss', 'injury', 'late_return', 'condition_dispute', 'other') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required incident type values';
    END IF;
END$$

CREATE TRIGGER trg_incident_type_before_update
BEFORE UPDATE ON incident_type_ity
FOR EACH ROW
BEGIN
    IF OLD.type_name_ity IN ('damage', 'theft', 'loss', 'injury', 'late_return', 'condition_dispute', 'other')
       AND NEW.type_name_ity != OLD.type_name_ity THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required incident type values';
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_payment_provider_before_delete
BEFORE DELETE ON payment_provider_ppv
FOR EACH ROW
BEGIN
    IF OLD.provider_name_ppv IN ('stripe', 'paypal', 'manual') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete system-required payment provider values';
    END IF;
END$$

CREATE TRIGGER trg_payment_provider_before_update
BEFORE UPDATE ON payment_provider_ppv
FOR EACH ROW
BEGIN
    IF OLD.provider_name_ppv IN ('stripe', 'paypal', 'manual')
       AND NEW.provider_name_ppv != OLD.provider_name_ppv THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot rename system-required payment provider values';
    END IF;
END$$
DELIMITER ;


-- ============================================================
-- 5. HELPER FUNCTIONS
-- ============================================================
-- fn_get_*_id(name) resolvers return the PK for a lookup value
-- by name; they SIGNAL on unknown names so callers fail loudly.
-- fn_is_tool_available(tool_id) encapsulates availability logic
-- used by views and procedures.
--
-- Helper functions must be defined before views: MySQL resolves stored-function
-- references at CREATE VIEW time (ALGORITHM=MERGE inlines the call). Routines
-- are baked with sql_mode at CREATE time — keep the same mode across the block.

SET @ROUTINE_OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

DELIMITER $$
CREATE FUNCTION fn_get_account_status_id(p_status_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_ast INTO v_id
    FROM account_status_ast
    WHERE status_name_ast = p_status_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_borrow_status_id(p_status_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_bst INTO v_id
    FROM borrow_status_bst
    WHERE status_name_bst = p_status_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_block_type_id(p_type_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_btp INTO v_id
    FROM block_type_btp
    WHERE type_name_btp = p_type_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_rating_role_id(p_role_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_rtr INTO v_id
    FROM rating_role_rtr
    WHERE role_name_rtr = p_role_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_notification_type_id(p_type_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_ntt INTO v_id
    FROM notification_type_ntt
    WHERE type_name_ntt = p_type_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_deposit_status_id(p_status_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_dps INTO v_id
    FROM deposit_status_dps
    WHERE status_name_dps = p_status_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_dispute_status_id(p_status_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_dst INTO v_id
    FROM dispute_status_dst
    WHERE status_name_dst = p_status_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_get_handover_type_id(p_type_name VARCHAR(30))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id INT;

    SELECT id_hot INTO v_id
    FROM handover_type_hot
    WHERE type_name_hot = p_type_name
    LIMIT 1;

    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fn_get_*_id: unknown lookup name';
    END IF;

    RETURN v_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION fn_is_tool_available(p_tool_id INT)
RETURNS BOOLEAN
READS SQL DATA
BEGIN
    DECLARE v_is_listed BOOLEAN;
    DECLARE v_has_active_borrow BOOLEAN DEFAULT FALSE;
    DECLARE v_has_active_block BOOLEAN DEFAULT FALSE;

    SELECT is_available_tol INTO v_is_listed
    FROM tool_tol
    WHERE id_tol = p_tool_id;

    IF v_is_listed IS NULL OR v_is_listed = FALSE THEN
        RETURN FALSE;
    END IF;

    SELECT EXISTS(
        SELECT 1 FROM borrow_bor
        WHERE id_tol_bor = p_tool_id
          AND id_bst_bor IN (
              fn_get_borrow_status_id('requested'),
              fn_get_borrow_status_id('approved'),
              fn_get_borrow_status_id('borrowed')
          )
    ) INTO v_has_active_borrow;

    IF v_has_active_borrow THEN
        RETURN FALSE;
    END IF;

    SELECT EXISTS(
        SELECT 1 FROM availability_block_avb
        WHERE id_tol_avb = p_tool_id
          AND NOW() BETWEEN start_at_avb AND end_at_avb
    ) INTO v_has_active_block;

    RETURN NOT v_has_active_block;
END$$
DELIMITER ;

SET SQL_MODE=@ROUTINE_OLD_SQL_MODE;

-- ============================================================
-- 6. VIEWS
-- ============================================================
-- Read-only query surfaces used by the application (models always
-- select FROM these views, never directly from base tables).
-- Grouped by domain:
--   - active_account_v, account_profile_v
--   - available_tool_v, tool_availability_v, tool_detail_v
--   - active_borrow_v, overdue_borrow_v, pending_request_v
--   - user_reputation_v, tool_statistics_v, neighborhood_summary_v, category_summary_v
--   - open_dispute_v, pending_deposit_v, payment_history_v
--   - current_tos_v, tos_acceptance_required_v
--   - pending_waiver_v, open_incident_v, pending_handover_v
--   - unread_notification_v, user_bookmarks_v, upcoming_event_v
-- *_fast_v variants read from the _mat summary tables for dashboard
-- queries where near-real-time data is acceptable.

-- Force session collation so CASE-expression string literals
-- inherit utf8mb4_0900_ai_ci instead of the server default.
SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Columns enumerated explicitly so future account_acc columns do not leak
-- into the view implicitly; consumers must opt in.
CREATE VIEW active_account_v AS
SELECT
    id_acc,
    first_name_acc,
    last_name_acc,
    username_acc,
    phone_number_acc,
    email_address_acc,
    street_address_acc,
    zip_code_acc,
    id_nbh_acc,
    password_hash_acc,
    id_rol_acc,
    id_ast_acc,
    id_cpr_acc,
    is_verified_acc,
    is_purged_acc,
    has_consent_acc,
    last_login_at_acc,
    created_at_acc,
    updated_at_acc,
    deleted_at_acc,
    id_avv_acc
FROM account_acc
WHERE id_ast_acc != fn_get_account_status_id('deleted');

CREATE VIEW available_tool_v AS
SELECT
    t.id_tol,
    t.tool_name_tol,
    t.tool_description_tol,
    t.rental_fee_tol,
    t.default_loan_duration_hours_tol,
    t.is_deposit_required_tol,
    t.default_deposit_amount_tol,
    t.estimated_value_tol,
    t.preexisting_conditions_tol,
    t.is_insurance_recommended_tol,
    t.created_at_tol,
    t.id_acc_tol AS owner_id,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
    a.zip_code_acc AS owner_zip,
    tcd.condition_name_tcd AS tool_condition,
    tim.file_name_tim AS primary_image,
    tim.focal_x_tim AS primary_focal_x,
    tim.focal_y_tim AS primary_focal_y,
    tim.width_tim AS primary_width,
    nbh.neighborhood_name_nbh AS owner_neighborhood,
    ftp.fuel_name_ftp AS fuel_type
FROM tool_tol t
JOIN account_acc a ON t.id_acc_tol = a.id_acc
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
LEFT JOIN neighborhood_nbh nbh ON a.id_nbh_acc = nbh.id_nbh
LEFT JOIN fuel_type_ftp ftp ON t.id_ftp_tol = ftp.id_ftp
WHERE t.is_available_tol = TRUE
  AND t.is_deleted_tol = FALSE
  AND a.id_ast_acc != fn_get_account_status_id('deleted')
  AND NOT EXISTS (
      SELECT 1 FROM borrow_bor b
      WHERE b.id_tol_bor = t.id_tol
        AND b.id_bst_bor IN (
            fn_get_borrow_status_id('requested'),
            fn_get_borrow_status_id('approved'),
            fn_get_borrow_status_id('borrowed')
        )
  )
  AND NOT EXISTS (
      SELECT 1 FROM availability_block_avb avb
      WHERE avb.id_tol_avb = t.id_tol
        AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
  );

CREATE VIEW active_borrow_v AS
SELECT
    b.id_bor,
    b.id_tol_bor,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    borrower.email_address_acc AS borrower_email,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    lender.email_address_acc AS lender_email,
    b.loan_duration_hours_bor,
    b.borrowed_at_bor,
    b.due_at_bor,
    TIMESTAMPDIFF(HOUR, NOW(), b.due_at_bor) AS hours_until_due,
    CASE
        WHEN b.due_at_bor < NOW() THEN 'OVERDUE'
        WHEN TIMESTAMPDIFF(HOUR, NOW(), b.due_at_bor) <= 24 THEN 'DUE SOON'
        ELSE 'ON TIME'
    END AS due_status,
    b.notes_text_bor,
    b.is_contact_shared_bor
FROM borrow_bor b
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
WHERE b.id_bst_bor = fn_get_borrow_status_id('borrowed');

CREATE VIEW overdue_borrow_v AS
SELECT
    b.id_bor,
    b.id_tol_bor,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    borrower.email_address_acc AS borrower_email,
    borrower.phone_number_acc AS borrower_phone,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    lender.email_address_acc AS lender_email,
    b.borrowed_at_bor,
    b.due_at_bor,
    TIMESTAMPDIFF(HOUR, b.due_at_bor, NOW()) AS hours_overdue,
    TIMESTAMPDIFF(DAY, b.due_at_bor, NOW()) AS days_overdue,
    t.estimated_value_tol,
    sdp.amount_sdp AS deposit_held,
    sdp.id_sdp AS deposit_id
FROM borrow_bor b
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN security_deposit_sdp sdp ON b.id_bor = sdp.id_bor_sdp
WHERE b.id_bst_bor = fn_get_borrow_status_id('borrowed')
  AND b.due_at_bor < NOW();

CREATE VIEW pending_request_v AS
SELECT
    b.id_bor,
    b.id_tol_bor,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    borrower.email_address_acc AS borrower_email,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    lender.email_address_acc AS lender_email,
    b.loan_duration_hours_bor,
    b.requested_at_bor,
    TIMESTAMPDIFF(HOUR, b.requested_at_bor, NOW()) AS hours_pending,
    b.notes_text_bor,
    t.is_deposit_required_tol,
    t.default_deposit_amount_tol,
    COALESCE(borrower_rep.borrower_avg_rating, 0) AS borrower_avg_rating,
    COALESCE(borrower_rep.borrower_rating_count, 0) AS borrower_rating_count
FROM borrow_bor b
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN user_reputation_mat borrower_rep ON b.id_acc_bor = borrower_rep.id_acc
WHERE b.id_bst_bor = fn_get_borrow_status_id('requested');


CREATE VIEW account_profile_v AS
SELECT
    a.id_acc,
    a.first_name_acc,
    a.last_name_acc,
    a.username_acc,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
    a.email_address_acc,
    a.phone_number_acc,
    a.street_address_acc,
    a.zip_code_acc,
    zpc.latitude_zpc,
    zpc.longitude_zpc,
    nbh.id_nbh AS neighborhood_id,
    nbh.neighborhood_name_nbh,
    nbh.city_name_nbh,
    COALESCE(sta.state_code_sta, sta_fallback.state_code_sta) AS state_code_sta,
    COALESCE(sta.state_name_sta, sta_fallback.state_name_sta) AS state_name_sta,
    rol.role_name_rol,
    ast.status_name_ast AS account_status,
    cpr.preference_name_cpr AS contact_preference,
    a.is_verified_acc,
    a.has_consent_acc,
    a.last_login_at_acc,
    a.created_at_acc,
    aim.file_name_aim AS primary_image,
    aim.alt_text_aim AS image_alt_text,
    aim.focal_x_aim AS focal_x,
    aim.focal_y_aim AS focal_y,
    avv.file_name_avv AS vector_avatar,
    avv.description_text_avv AS vector_avatar_alt,
    abi.bio_text_abi,
    COALESCE(tool_counts.active_tool_count, 0) AS active_tool_count,
    COALESCE(reputation.lender_avg_rating, 0) AS lender_rating,
    COALESCE(reputation.borrower_avg_rating, 0) AS borrower_rating
FROM account_acc a
JOIN role_rol rol ON a.id_rol_acc = rol.id_rol
JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
JOIN contact_preference_cpr cpr ON a.id_cpr_acc = cpr.id_cpr
JOIN zip_code_zpc zpc ON a.zip_code_acc = zpc.zip_code_zpc
LEFT JOIN neighborhood_nbh nbh ON a.id_nbh_acc = nbh.id_nbh
LEFT JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
LEFT JOIN neighborhood_zip_nbhzpc nz
    ON a.zip_code_acc = nz.zip_code_nbhzpc
    AND nz.is_primary_nbhzpc = TRUE
LEFT JOIN neighborhood_nbh nbh_fallback ON nz.id_nbh_nbhzpc = nbh_fallback.id_nbh
LEFT JOIN state_sta sta_fallback ON nbh_fallback.id_sta_nbh = sta_fallback.id_sta
LEFT JOIN account_image_aim aim ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
LEFT JOIN avatar_vector_avv avv ON a.id_avv_acc = avv.id_avv
LEFT JOIN account_bio_abi abi ON a.id_acc = abi.id_acc_abi
LEFT JOIN (
    SELECT id_acc_tol, COUNT(*) AS active_tool_count
    FROM tool_tol
    WHERE is_available_tol = TRUE
      AND is_deleted_tol = FALSE
    GROUP BY id_acc_tol
) tool_counts ON a.id_acc = tool_counts.id_acc_tol
LEFT JOIN user_reputation_mat reputation ON a.id_acc = reputation.id_acc
WHERE a.id_ast_acc != fn_get_account_status_id('deleted');

-- Returns one row per tool with the same UPPERCASE contract that tool_detail_v
-- exposes (UNLISTED | BORROWED | PENDING | BLOCKED | AVAILABLE). Defined as
-- its own view so the CASE block isn't duplicated across callers.
CREATE VIEW tool_availability_v AS
SELECT
    t.id_tol,
    CASE
        WHEN t.is_available_tol = 0 THEN 'UNLISTED'
        WHEN EXISTS (
            SELECT 1 FROM borrow_bor b
            WHERE b.id_tol_bor = t.id_tol
              AND b.id_bst_bor = fn_get_borrow_status_id('borrowed')
        ) THEN 'BORROWED'
        WHEN EXISTS (
            SELECT 1 FROM borrow_bor b
            WHERE b.id_tol_bor = t.id_tol
              AND b.id_bst_bor IN (
                  fn_get_borrow_status_id('requested'),
                  fn_get_borrow_status_id('approved')
              )
        ) THEN 'PENDING'
        WHEN EXISTS (
            SELECT 1 FROM availability_block_avb avb
            WHERE avb.id_tol_avb = t.id_tol
              AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
        ) THEN 'BLOCKED'
        ELSE 'AVAILABLE'
    END AS availability_status
FROM tool_tol t
WHERE t.is_deleted_tol = 0;

CREATE VIEW tool_detail_v AS
SELECT
    t.id_tol,
    t.tool_name_tol,
    t.tool_description_tol,
    t.serial_number_tol,
    t.rental_fee_tol,
    t.default_loan_duration_hours_tol,
    t.is_available_tol,
    t.is_deposit_required_tol,
    t.default_deposit_amount_tol,
    t.estimated_value_tol,
    t.preexisting_conditions_tol,
    t.is_insurance_recommended_tol,
    t.created_at_tol,
    t.updated_at_tol,
    tcd.condition_name_tcd AS tool_condition,
    t.id_acc_tol AS owner_id,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
    a.email_address_acc AS owner_email,
    a.zip_code_acc AS owner_zip,
    nbh.neighborhood_name_nbh AS owner_neighborhood,
    sta.state_code_sta AS owner_state,
    tim.file_name_tim AS primary_image,
    tim.alt_text_tim AS primary_image_alt,
    tim.focal_x_tim AS primary_focal_x,
    tim.focal_y_tim AS primary_focal_y,
    tim.width_tim AS primary_width,
    COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
    COALESCE(rating_stats.rating_count, 0) AS rating_count,
    COALESCE(borrow_stats.completed_borrow_count, 0) AS completed_borrow_count,
    cat_list.categories,
    ftp.fuel_name_ftp AS fuel_type,
    tav.availability_status
FROM tool_tol t
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
JOIN account_acc a ON t.id_acc_tol = a.id_acc
LEFT JOIN neighborhood_nbh nbh ON a.id_nbh_acc = nbh.id_nbh
LEFT JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
LEFT JOIN fuel_type_ftp ftp ON t.id_ftp_tol = ftp.id_ftp
LEFT JOIN tool_availability_v tav ON t.id_tol = tav.id_tol
LEFT JOIN (
    SELECT id_tol_trt,
           ROUND(AVG(score_trt), 1) AS avg_rating,
           COUNT(*) AS rating_count
    FROM tool_rating_trt
    GROUP BY id_tol_trt
) rating_stats ON t.id_tol = rating_stats.id_tol_trt
LEFT JOIN (
    SELECT id_tol_bor, COUNT(*) AS completed_borrow_count
    FROM borrow_bor
    WHERE id_bst_bor = fn_get_borrow_status_id('returned')
    GROUP BY id_tol_bor
) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
LEFT JOIN (
    SELECT tc.id_tol_tolcat,
           GROUP_CONCAT(c.category_name_cat ORDER BY c.category_name_cat SEPARATOR ', ') AS categories
    FROM tool_category_tolcat tc
    JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
    GROUP BY tc.id_tol_tolcat
) cat_list ON t.id_tol = cat_list.id_tol_tolcat
WHERE t.is_deleted_tol = FALSE;


CREATE VIEW user_reputation_v AS
SELECT
    a.id_acc,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
    a.email_address_acc,
    ast.status_name_ast AS account_status,
    a.created_at_acc AS member_since,
    COALESCE(lender_stats.avg_score, 0) AS lender_avg_rating,
    COALESCE(lender_stats.rating_count, 0) AS lender_rating_count,
    COALESCE(borrower_stats.avg_score, 0) AS borrower_avg_rating,
    COALESCE(borrower_stats.rating_count, 0) AS borrower_rating_count,
    ROUND((COALESCE(lender_stats.avg_score, 0) + COALESCE(borrower_stats.avg_score, 0)) /
          NULLIF((CASE WHEN lender_stats.avg_score IS NOT NULL THEN 1 ELSE 0 END +
                  CASE WHEN borrower_stats.avg_score IS NOT NULL THEN 1 ELSE 0 END), 0), 1) AS overall_avg_rating,
    COALESCE(lender_stats.rating_count, 0) + COALESCE(borrower_stats.rating_count, 0) AS total_rating_count,
    COALESCE(tool_counts.tools_owned, 0) AS tools_owned,
    COALESCE(borrow_counts.completed_borrows, 0) AS completed_borrows
FROM account_acc a
JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
LEFT JOIN (
    SELECT id_acc_target_urt,
           ROUND(AVG(score_urt), 1) AS avg_score,
           COUNT(*) AS rating_count
    FROM user_rating_urt
    WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender')
    GROUP BY id_acc_target_urt
) lender_stats ON a.id_acc = lender_stats.id_acc_target_urt
LEFT JOIN (
    SELECT id_acc_target_urt,
           ROUND(AVG(score_urt), 1) AS avg_score,
           COUNT(*) AS rating_count
    FROM user_rating_urt
    WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower')
    GROUP BY id_acc_target_urt
) borrower_stats ON a.id_acc = borrower_stats.id_acc_target_urt
LEFT JOIN (
    SELECT id_acc_tol, COUNT(*) AS tools_owned
    FROM tool_tol
    GROUP BY id_acc_tol
) tool_counts ON a.id_acc = tool_counts.id_acc_tol
LEFT JOIN (
    SELECT id_acc_bor, COUNT(*) AS completed_borrows
    FROM borrow_bor
    WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
    GROUP BY id_acc_bor
) borrow_counts ON a.id_acc = borrow_counts.id_acc_bor
WHERE a.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted');

CREATE VIEW tool_statistics_v AS
SELECT
    t.id_tol,
    t.tool_name_tol,
    t.id_acc_tol AS owner_id,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
    tcd.condition_name_tcd AS tool_condition,
    t.rental_fee_tol,
    t.estimated_value_tol,
    t.created_at_tol,
    COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
    COALESCE(rating_stats.rating_count, 0) AS rating_count,
    COALESCE(rating_stats.five_star_count, 0) AS five_star_count,
    COALESCE(borrow_stats.total_borrows, 0) AS total_borrows,
    COALESCE(borrow_stats.completed_borrows, 0) AS completed_borrows,
    COALESCE(borrow_stats.cancelled_borrows, 0) AS cancelled_borrows,
    COALESCE(borrow_stats.denied_borrows, 0) AS denied_borrows,
    COALESCE(borrow_stats.total_hours_borrowed, 0) AS total_hours_borrowed,
    borrow_stats.last_borrowed_at,
    COALESCE(incident_stats.incident_count, 0) AS incident_count
FROM tool_tol t
JOIN account_acc a ON t.id_acc_tol = a.id_acc
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
LEFT JOIN (
    SELECT id_tol_trt,
           ROUND(AVG(score_trt), 1) AS avg_rating,
           COUNT(*) AS rating_count,
           SUM(CASE WHEN score_trt = 5 THEN 1 ELSE 0 END) AS five_star_count
    FROM tool_rating_trt
    GROUP BY id_tol_trt
) rating_stats ON t.id_tol = rating_stats.id_tol_trt
LEFT JOIN (
    SELECT id_tol_bor,
           COUNT(*) AS total_borrows,
           SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned') THEN 1 ELSE 0 END) AS completed_borrows,
           SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'cancelled') THEN 1 ELSE 0 END) AS cancelled_borrows,
           SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'denied') THEN 1 ELSE 0 END) AS denied_borrows,
           SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned') THEN loan_duration_hours_bor ELSE 0 END) AS total_hours_borrowed,
           MAX(borrowed_at_bor) AS last_borrowed_at
    FROM borrow_bor
    GROUP BY id_tol_bor
) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
LEFT JOIN (
    SELECT b.id_tol_bor, COUNT(*) AS incident_count
    FROM incident_report_irt irt
    JOIN borrow_bor b ON irt.id_bor_irt = b.id_bor
    GROUP BY b.id_tol_bor
) incident_stats ON t.id_tol = incident_stats.id_tol_bor;

CREATE VIEW neighborhood_summary_v AS
SELECT
    nbh.id_nbh,
    nbh.neighborhood_name_nbh,
    nbh.city_name_nbh,
    sta.state_code_sta,
    sta.state_name_sta,
    nbh.latitude_nbh,
    nbh.longitude_nbh,
    nbh.location_point_nbh,
    nbh.created_at_nbh,
    COALESCE(member_stats.total_members, 0) AS total_members,
    COALESCE(member_stats.active_members, 0) AS active_members,
    COALESCE(member_stats.verified_members, 0) AS verified_members,
    COALESCE(tool_stats.total_tools, 0) AS total_tools,
    COALESCE(tool_stats.available_tools, 0) AS available_tools,
    COALESCE(borrow_stats.active_borrows, 0) AS active_borrows,
    COALESCE(borrow_stats.completed_borrows_30d, 0) AS completed_borrows_30d,
    COALESCE(event_stats.upcoming_events, 0) AS upcoming_events,
    zip_list.zip_codes
FROM neighborhood_nbh nbh
JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
LEFT JOIN (
    SELECT id_nbh_acc,
           COUNT(*) AS total_members,
           SUM(CASE WHEN id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active') THEN 1 ELSE 0 END) AS active_members,
           SUM(CASE WHEN is_verified_acc = TRUE THEN 1 ELSE 0 END) AS verified_members
    FROM account_acc
    WHERE id_nbh_acc IS NOT NULL AND id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')
    GROUP BY id_nbh_acc
) member_stats ON nbh.id_nbh = member_stats.id_nbh_acc
LEFT JOIN (
    SELECT a.id_nbh_acc,
           COUNT(*) AS total_tools,
           SUM(CASE WHEN t.is_available_tol = TRUE THEN 1 ELSE 0 END) AS available_tools
    FROM tool_tol t
    JOIN account_acc a ON t.id_acc_tol = a.id_acc
    WHERE a.id_nbh_acc IS NOT NULL
    GROUP BY a.id_nbh_acc
) tool_stats ON nbh.id_nbh = tool_stats.id_nbh_acc
LEFT JOIN (
    SELECT a.id_nbh_acc,
           SUM(CASE WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed') THEN 1 ELSE 0 END) AS active_borrows,
           SUM(CASE WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
                     AND b.returned_at_bor >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS completed_borrows_30d
    FROM borrow_bor b
    JOIN account_acc a ON b.id_acc_bor = a.id_acc
    WHERE a.id_nbh_acc IS NOT NULL
    GROUP BY a.id_nbh_acc
) borrow_stats ON nbh.id_nbh = borrow_stats.id_nbh_acc
LEFT JOIN (
    SELECT id_nbh_evt, COUNT(*) AS upcoming_events
    FROM event_evt
    WHERE start_at_evt >= NOW()
    GROUP BY id_nbh_evt
) event_stats ON nbh.id_nbh = event_stats.id_nbh_evt
LEFT JOIN (
    SELECT id_nbh_nbhzpc,
           GROUP_CONCAT(zip_code_nbhzpc ORDER BY zip_code_nbhzpc SEPARATOR ', ') AS zip_codes
    FROM neighborhood_zip_nbhzpc
    GROUP BY id_nbh_nbhzpc
) zip_list ON nbh.id_nbh = zip_list.id_nbh_nbhzpc;


CREATE VIEW open_dispute_v AS
SELECT
    d.id_dsp,
    d.subject_text_dsp,
    d.created_at_dsp,
    TIMESTAMPDIFF(DAY, d.created_at_dsp, NOW()) AS days_open,
    d.id_acc_dsp AS reporter_id,
    CONCAT(reporter.first_name_acc, ' ', reporter.last_name_acc) AS reporter_name,
    reporter.email_address_acc AS reporter_email,
    d.id_bor_dsp,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    COALESCE(msg_stats.message_count, 0) AS message_count,
    msg_stats.last_message_at,
    COALESCE(incident_stats.related_incidents, 0) AS related_incidents,
    sdp.amount_sdp AS deposit_amount,
    dps.status_name_dps AS deposit_status
FROM dispute_dsp d
JOIN account_acc reporter ON d.id_acc_dsp = reporter.id_acc
JOIN borrow_bor b ON d.id_bor_dsp = b.id_bor
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN security_deposit_sdp sdp ON b.id_bor = sdp.id_bor_sdp
LEFT JOIN deposit_status_dps dps ON sdp.id_dps_sdp = dps.id_dps
LEFT JOIN (
    SELECT id_dsp_dsm,
           COUNT(*) AS message_count,
           MAX(created_at_dsm) AS last_message_at
    FROM dispute_message_dsm
    GROUP BY id_dsp_dsm
) msg_stats ON d.id_dsp = msg_stats.id_dsp_dsm
LEFT JOIN (
    SELECT id_bor_irt, COUNT(*) AS related_incidents
    FROM incident_report_irt
    GROUP BY id_bor_irt
) incident_stats ON d.id_bor_dsp = incident_stats.id_bor_irt
WHERE d.id_dst_dsp = fn_get_dispute_status_id('open');

CREATE VIEW pending_deposit_v AS
SELECT
    sdp.id_sdp,
    sdp.amount_sdp,
    dps.status_name_dps AS deposit_status,
    ppv.provider_name_ppv AS payment_provider,
    sdp.external_payment_id_sdp,
    sdp.held_at_sdp,
    TIMESTAMPDIFF(DAY, sdp.held_at_sdp, NOW()) AS days_held,
    sdp.id_bor_sdp,
    bst.status_name_bst AS borrow_status,
    b.due_at_bor,
    CASE
        WHEN b.id_bst_bor = fn_get_borrow_status_id('returned') THEN 'READY FOR RELEASE'
        WHEN b.id_bst_bor = fn_get_borrow_status_id('borrowed') AND b.due_at_bor < NOW() THEN 'OVERDUE - REVIEW NEEDED'
        WHEN b.id_bst_bor = fn_get_borrow_status_id('borrowed') THEN 'ACTIVE BORROW'
        ELSE 'REVIEW NEEDED'
    END AS action_required,
    t.id_tol,
    t.tool_name_tol,
    t.estimated_value_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    borrower.email_address_acc AS borrower_email,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    lender.email_address_acc AS lender_email,
    COALESCE(incident_stats.incident_count, 0) AS incident_count,
    sdp.id_irt_sdp AS linked_incident_id
FROM security_deposit_sdp sdp
JOIN deposit_status_dps dps ON sdp.id_dps_sdp = dps.id_dps
JOIN payment_provider_ppv ppv ON sdp.id_ppv_sdp = ppv.id_ppv
JOIN borrow_bor b ON sdp.id_bor_sdp = b.id_bor
JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN (
    SELECT id_bor_irt, COUNT(*) AS incident_count
    FROM incident_report_irt
    GROUP BY id_bor_irt
) incident_stats ON sdp.id_bor_sdp = incident_stats.id_bor_irt
WHERE sdp.id_dps_sdp = fn_get_deposit_status_id('held');

CREATE VIEW payment_history_v AS
SELECT
    ptx.id_ptx,
    ptt.type_name_ptt AS transaction_type_ptx,
    ptx.amount_ptx,
    ptx.external_transaction_id_ptx,
    ptx.external_status_ptx,
    ptx.processed_at_ptx,
    ptx.id_acc_from_ptx,
    ptx.id_acc_to_ptx,
    ppv.provider_name_ppv AS payment_provider,
    t.tool_name_tol,
    CONCAT(afrom.first_name_acc, ' ', afrom.last_name_acc) AS from_name,
    CONCAT(ato.first_name_acc, ' ', ato.last_name_acc) AS to_name
FROM payment_transaction_ptx ptx
    INNER JOIN payment_transaction_type_ptt ptt ON ptt.id_ptt = ptx.id_ptt_ptx
    JOIN payment_provider_ppv ppv ON ptx.id_ppv_ptx = ppv.id_ppv
    JOIN borrow_bor b ON ptx.id_bor_ptx = b.id_bor
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    LEFT JOIN account_acc afrom ON ptx.id_acc_from_ptx = afrom.id_acc
    LEFT JOIN account_acc ato ON ptx.id_acc_to_ptx = ato.id_acc;

CREATE VIEW current_tos_v AS
SELECT
    tos.id_tos,
    tos.version_tos,
    tos.title_tos,
    tos.content_tos,
    tos.summary_tos,
    tos.effective_at_tos,
    tos.created_at_tos,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS created_by_name,
    COALESCE(acceptance_stats.total_acceptances, 0) AS total_acceptances
FROM terms_of_service_tos tos
JOIN account_acc a ON tos.id_acc_created_by_tos = a.id_acc
LEFT JOIN (
    SELECT id_tos_tac, COUNT(DISTINCT id_acc_tac) AS total_acceptances
    FROM tos_acceptance_tac
    GROUP BY id_tos_tac
) acceptance_stats ON tos.id_tos = acceptance_stats.id_tos_tac
WHERE tos.is_active_tos = TRUE
  AND tos.superseded_at_tos IS NULL;

CREATE VIEW tos_acceptance_required_v AS
SELECT
    a.id_acc,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
    a.email_address_acc,
    ast.status_name_ast AS account_status,
    a.last_login_at_acc,
    a.created_at_acc,
    tos_history.last_tos_accepted_at,
    tos_history.last_accepted_version
FROM account_acc a
JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
LEFT JOIN (
    SELECT DISTINCT tac.id_acc_tac
    FROM tos_acceptance_tac tac
    JOIN terms_of_service_tos tos ON tac.id_tos_tac = tos.id_tos
    WHERE tos.is_active_tos = TRUE
      AND tos.superseded_at_tos IS NULL
) current_tos_accepted ON a.id_acc = current_tos_accepted.id_acc_tac
LEFT JOIN (
    SELECT tac.id_acc_tac,
           MAX(tac.accepted_at_tac) AS last_tos_accepted_at,
           (SELECT tos2.version_tos
            FROM tos_acceptance_tac tac2
            JOIN terms_of_service_tos tos2 ON tac2.id_tos_tac = tos2.id_tos
            WHERE tac2.id_acc_tac = tac.id_acc_tac
            ORDER BY tac2.accepted_at_tac DESC
            LIMIT 1) AS last_accepted_version
    FROM tos_acceptance_tac tac
    GROUP BY tac.id_acc_tac
) tos_history ON a.id_acc = tos_history.id_acc_tac
WHERE a.id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active')
  AND current_tos_accepted.id_acc_tac IS NULL;

CREATE VIEW pending_waiver_v AS
SELECT
    b.id_bor,
    b.id_tol_bor,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    borrower.email_address_acc AS borrower_email,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    b.approved_at_bor,
    TIMESTAMPDIFF(HOUR, b.approved_at_bor, NOW()) AS hours_since_approval,
    t.preexisting_conditions_tol,
    t.is_deposit_required_tol,
    t.default_deposit_amount_tol
FROM borrow_bor b
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN borrow_waiver_bwv bwv ON b.id_bor = bwv.id_bor_bwv
WHERE b.id_bst_bor = fn_get_borrow_status_id('approved')
  AND bwv.id_bwv IS NULL;

CREATE VIEW open_incident_v AS
SELECT
    irt.id_irt,
    irt.subject_irt,
    irt.description_irt,
    ity.type_name_ity AS incident_type,
    irt.incident_occurred_at_irt,
    irt.created_at_irt,
    TIMESTAMPDIFF(DAY, irt.created_at_irt, NOW()) AS days_open,
    irt.is_reported_within_deadline_irt,
    irt.estimated_damage_amount_irt,
    irt.id_acc_irt AS reporter_id,
    CONCAT(reporter.first_name_acc, ' ', reporter.last_name_acc) AS reporter_name,
    reporter.email_address_acc AS reporter_email,
    irt.id_bor_irt,
    t.id_tol AS tool_id,
    t.tool_name_tol,
    t.estimated_value_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    COALESCE(dispute_stats.related_disputes, 0) AS related_disputes,
    sdp.id_sdp AS deposit_id,
    sdp.amount_sdp AS deposit_amount,
    dps.status_name_dps AS deposit_status
FROM incident_report_irt irt
JOIN incident_type_ity ity ON irt.id_ity_irt = ity.id_ity
JOIN account_acc reporter ON irt.id_acc_irt = reporter.id_acc
JOIN borrow_bor b ON irt.id_bor_irt = b.id_bor
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN security_deposit_sdp sdp ON b.id_bor = sdp.id_bor_sdp
LEFT JOIN deposit_status_dps dps ON sdp.id_dps_sdp = dps.id_dps
LEFT JOIN (
    SELECT id_bor_dsp, COUNT(*) AS related_disputes
    FROM dispute_dsp
    GROUP BY id_bor_dsp
) dispute_stats ON irt.id_bor_irt = dispute_stats.id_bor_dsp
WHERE irt.resolved_at_irt IS NULL;

CREATE VIEW pending_handover_v AS
SELECT
    hov.id_hov,
    hov.verification_code_hov,
    hot.type_name_hot AS handover_type,
    hov.generated_at_hov,
    hov.expires_at_hov,
    TIMESTAMPDIFF(HOUR, NOW(), hov.expires_at_hov) AS hours_until_expiry,
    CASE
        WHEN hov.expires_at_hov < NOW() THEN 'EXPIRED'
        WHEN TIMESTAMPDIFF(HOUR, NOW(), hov.expires_at_hov) <= 2 THEN 'EXPIRING SOON'
        ELSE 'ACTIVE'
    END AS code_status,
    hov.condition_notes_hov,
    hov.id_acc_generator_hov AS generator_id,
    CONCAT(generator.first_name_acc, ' ', generator.last_name_acc) AS generator_name,
    generator.email_address_acc AS generator_email,
    hov.id_bor_hov,
    t.id_tol AS tool_id,
    t.tool_name_tol,
    b.id_acc_bor AS borrower_id,
    CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
    t.id_acc_tol AS lender_id,
    CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
    bst.status_name_bst AS borrow_status
FROM handover_verification_hov hov
JOIN handover_type_hot hot ON hov.id_hot_hov = hot.id_hot
JOIN account_acc generator ON hov.id_acc_generator_hov = generator.id_acc
JOIN borrow_bor b ON hov.id_bor_hov = b.id_bor
JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
WHERE hov.verified_at_hov IS NULL;


CREATE VIEW unread_notification_v AS
SELECT
    ntf.id_ntf,
    ntf.id_acc_ntf AS user_id,
    CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS user_name,
    a.email_address_acc AS user_email,
    ntt.type_name_ntt AS notification_type,
    ntf.title_ntf,
    ntf.body_ntf,
    ntf.created_at_ntf,
    TIMESTAMPDIFF(HOUR, ntf.created_at_ntf, NOW()) AS hours_ago,
    ntf.id_bor_ntf,
    t.tool_name_tol AS related_tool_name,
    bst.status_name_bst AS related_borrow_status
FROM notification_ntf ntf
JOIN account_acc a ON ntf.id_acc_ntf = a.id_acc
JOIN notification_type_ntt ntt ON ntf.id_ntt_ntf = ntt.id_ntt
LEFT JOIN borrow_bor b ON ntf.id_bor_ntf = b.id_bor
LEFT JOIN tool_tol t ON b.id_tol_bor = t.id_tol
LEFT JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
WHERE ntf.is_read_ntf = FALSE;

CREATE VIEW user_bookmarks_v AS
SELECT
    acctol.id_acctol AS bookmark_id,
    acctol.id_acc_acctol AS user_id,
    CONCAT(bookmarker.first_name_acc, ' ', bookmarker.last_name_acc) AS user_name,
    acctol.created_at_acctol AS bookmarked_at,
    t.id_tol AS tool_id,
    t.tool_name_tol,
    t.tool_description_tol,
    t.rental_fee_tol,
    tcd.condition_name_tcd AS tool_condition,
    tim.file_name_tim AS primary_image,
    tim.focal_x_tim AS primary_focal_x,
    tim.focal_y_tim AS primary_focal_y,
    tim.width_tim AS primary_width,
    t.id_acc_tol AS owner_id,
    CONCAT(owner.first_name_acc, ' ', owner.last_name_acc) AS owner_name,
    nbh.neighborhood_name_nbh AS owner_neighborhood,
    CASE
        WHEN owner.id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted') THEN 'OWNER DELETED'
        WHEN t.is_available_tol = FALSE THEN 'UNLISTED'
        WHEN EXISTS (
            SELECT 1 FROM borrow_bor b
            WHERE b.id_tol_bor = t.id_tol
              AND b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
        ) THEN 'UNAVAILABLE'
        WHEN EXISTS (
            SELECT 1 FROM borrow_bor b
            WHERE b.id_tol_bor = t.id_tol
              AND b.id_bst_bor IN (
                  (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'),
                  (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved')
              )
        ) THEN 'PENDING'
        WHEN EXISTS (
            SELECT 1 FROM availability_block_avb avb
            WHERE avb.id_tol_avb = t.id_tol
              AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
        ) THEN 'BLOCKED'
        ELSE 'AVAILABLE'
    END AS availability_status,
    COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
    COALESCE(rating_stats.rating_count, 0) AS rating_count
FROM tool_bookmark_acctol acctol
JOIN account_acc bookmarker ON acctol.id_acc_acctol = bookmarker.id_acc
JOIN tool_tol t ON acctol.id_tol_acctol = t.id_tol
    AND t.is_deleted_tol = FALSE
JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
LEFT JOIN neighborhood_nbh nbh ON owner.id_nbh_acc = nbh.id_nbh
LEFT JOIN (
    SELECT id_tol_trt,
           ROUND(AVG(score_trt), 1) AS avg_rating,
           COUNT(*) AS rating_count
    FROM tool_rating_trt
    GROUP BY id_tol_trt
) rating_stats ON t.id_tol = rating_stats.id_tol_trt;


CREATE VIEW category_summary_v AS
SELECT
    c.id_cat,
    c.category_name_cat,
    vec.file_name_vec AS category_icon,
    COUNT(DISTINCT tc.id_tol_tolcat) AS total_tools,
    SUM(CASE WHEN t.is_available_tol = TRUE THEN 1 ELSE 0 END) AS listed_tools,
    SUM(CASE
        WHEN t.is_available_tol = TRUE
         AND owner.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')
         AND NOT EXISTS (
             SELECT 1 FROM borrow_bor b
             WHERE b.id_tol_bor = t.id_tol
               AND b.id_bst_bor IN (
                   (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'),
                   (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'),
                   (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
               )
         )
         AND NOT EXISTS (
             SELECT 1 FROM availability_block_avb avb
             WHERE avb.id_tol_avb = t.id_tol
               AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
         )
        THEN 1 ELSE 0
    END) AS available_tools,
    ROUND(AVG(rating_stats.avg_rating), 1) AS category_avg_rating,
    COALESCE(SUM(borrow_stats.completed_borrows), 0) AS total_completed_borrows,
    MIN(t.rental_fee_tol) AS min_rental_fee,
    MAX(t.rental_fee_tol) AS max_rental_fee,
    ROUND(AVG(t.rental_fee_tol), 2) AS avg_rental_fee
FROM category_cat c
LEFT JOIN vector_image_vec vec ON c.id_vec_cat = vec.id_vec
LEFT JOIN tool_category_tolcat tc ON c.id_cat = tc.id_cat_tolcat
LEFT JOIN tool_tol t ON tc.id_tol_tolcat = t.id_tol
LEFT JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
LEFT JOIN (
    SELECT id_tol_trt, ROUND(AVG(score_trt), 1) AS avg_rating
    FROM tool_rating_trt
    GROUP BY id_tol_trt
) rating_stats ON t.id_tol = rating_stats.id_tol_trt
LEFT JOIN (
    SELECT id_tol_bor, COUNT(*) AS completed_borrows
    FROM borrow_bor
    WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
    GROUP BY id_tol_bor
) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
GROUP BY c.id_cat, c.category_name_cat, vec.file_name_vec;


CREATE VIEW upcoming_event_v AS
SELECT
    e.id_evt,
    e.event_name_evt,
    e.event_description_evt,
    e.start_at_evt,
    e.end_at_evt,
    TIMESTAMPDIFF(DAY, NOW(), e.start_at_evt) AS days_until_event,
    CASE
        WHEN e.start_at_evt <= NOW() AND (e.end_at_evt IS NULL OR e.end_at_evt >= NOW()) THEN 'HAPPENING NOW'
        WHEN TIMESTAMPDIFF(DAY, NOW(), e.start_at_evt) <= 7 THEN 'THIS WEEK'
        WHEN TIMESTAMPDIFF(DAY, NOW(), e.start_at_evt) <= 30 THEN 'THIS MONTH'
        ELSE 'UPCOMING'
    END AS event_timing,
    e.id_nbh_evt AS neighborhood_id,
    nbh.neighborhood_name_nbh,
    nbh.city_name_nbh,
    sta.state_code_sta,
    e.id_acc_evt AS creator_id,
    CONCAT(creator.first_name_acc, ' ', creator.last_name_acc) AS creator_name,
    e.created_at_evt,
    e.updated_at_evt,
    CONCAT(updater.first_name_acc, ' ', updater.last_name_acc) AS last_updated_by
FROM event_evt e
LEFT JOIN neighborhood_nbh nbh ON e.id_nbh_evt = nbh.id_nbh
LEFT JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
JOIN account_acc creator ON e.id_acc_evt = creator.id_acc
LEFT JOIN account_acc updater ON e.id_acc_updated_by_evt = updater.id_acc
WHERE e.start_at_evt >= NOW()
   OR (e.end_at_evt IS NOT NULL AND e.end_at_evt >= NOW());

-- Fast views: simple interface to the materialized summary tables for
-- dashboard queries where near-real-time data is acceptable.

CREATE VIEW neighborhood_summary_fast_v AS
SELECT
    id_nbh,
    neighborhood_name_nbh,
    city_name_nbh,
    state_code_sta,
    state_name_sta,
    latitude_nbh,
    longitude_nbh,
    location_point_nbh,
    created_at_nbh,
    total_members,
    active_members,
    verified_members,
    total_tools,
    available_tools,
    active_borrows,
    completed_borrows_30d,
    upcoming_events,
    zip_codes,
    refreshed_at
FROM neighborhood_summary_mat;

CREATE VIEW user_reputation_fast_v AS
SELECT
    id_acc,
    full_name,
    email_address_acc,
    account_status,
    member_since,
    lender_avg_rating,
    lender_rating_count,
    borrower_avg_rating,
    borrower_rating_count,
    overall_avg_rating,
    total_rating_count,
    tools_owned,
    completed_borrows,
    refreshed_at
FROM user_reputation_mat;

CREATE VIEW tool_statistics_fast_v AS
SELECT
    id_tol,
    tool_name_tol,
    owner_id,
    owner_name,
    tool_condition,
    rental_fee_tol,
    estimated_value_tol,
    created_at_tol,
    avg_rating,
    rating_count,
    five_star_count,
    total_borrows,
    completed_borrows,
    cancelled_borrows,
    denied_borrows,
    total_hours_borrowed,
    last_borrowed_at,
    incident_count,
    refreshed_at
FROM tool_statistics_mat;

CREATE VIEW category_summary_fast_v AS
SELECT
    id_cat,
    category_name_cat,
    category_icon,
    total_tools,
    listed_tools,
    available_tools,
    category_avg_rating,
    total_completed_borrows,
    min_rental_fee,
    max_rental_fee,
    avg_rental_fee,
    refreshed_at
FROM category_summary_mat;


-- ============================================================
-- 7. STORED PROCEDURES
-- ============================================================
-- All writes go through these procedures. Groups:
--   - TOS & loans:        sp_create_tos_version, sp_extend_loan
--   - Summary refresh:    sp_refresh_neighborhood_summary,
--                         sp_refresh_user_reputation(+_for),
--                         sp_refresh_tool_statistics,
--                         sp_refresh_category_summary,
--                         sp_refresh_platform_daily_stat,
--                         sp_refresh_all_summaries
--   - Borrow lifecycle:   sp_create_borrow_request, sp_approve_borrow_request,
--                         sp_deny_borrow_request, sp_complete_pickup,
--                         sp_complete_return, sp_cancel_borrow_request
--   - Ratings:            sp_rate_user, sp_rate_tool
--   - Notifications:      sp_send_notification, sp_mark_notifications_read,
--                         sp_send_overdue_notifications, sp_clear_read_notifications,
--                         sp_archive_old_notifications
--   - Cleanup / cron:     sp_cleanup_expired_handover_codes,
--                         sp_cleanup_old_search_logs,
--                         sp_process_stale_approved_borrows
--   - Deposits:           sp_release_deposit_on_return, sp_forfeit_deposit
--   - Search & history:   sp_search_available_tools, sp_get_user_borrow_history
--   - Bookmarks:          sp_create_bookmark, sp_delete_bookmark

SET @ROUTINE_OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

DELIMITER $$
CREATE PROCEDURE sp_create_tos_version(
    IN p_version VARCHAR(20),
    IN p_title VARCHAR(255),
    IN p_content TEXT,
    IN p_summary TEXT,
    IN p_effective_at TIMESTAMP,
    IN p_created_by INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    UPDATE terms_of_service_tos
    SET is_active_tos = FALSE,
        superseded_at_tos = NOW()
    WHERE is_active_tos = TRUE;

    INSERT INTO terms_of_service_tos (
        version_tos, title_tos, content_tos, summary_tos,
        effective_at_tos, is_active_tos, id_acc_created_by_tos
    ) VALUES (
        p_version, p_title, p_content, p_summary,
        p_effective_at, TRUE, p_created_by
    );

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_extend_loan(
    IN p_bor_id INT,
    IN p_extra_hours INT,
    IN p_reason TEXT,
    IN p_approved_by INT
)
BEGIN
    DECLARE v_current_due TIMESTAMP;
    DECLARE v_new_due TIMESTAMP;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT due_at_bor INTO v_current_due
    FROM borrow_bor
    WHERE id_bor = p_bor_id
    FOR UPDATE;

    IF v_current_due IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot extend: borrow has no due date set';
    END IF;

    SET v_new_due = DATE_ADD(v_current_due, INTERVAL p_extra_hours HOUR);

    INSERT INTO loan_extension_lex (
        id_bor_lex, original_due_at_lex, extended_hours_lex,
        new_due_at_lex, reason_lex, id_acc_approved_by_lex
    ) VALUES (
        p_bor_id, v_current_due, p_extra_hours,
        v_new_due, p_reason, p_approved_by
    );

    UPDATE borrow_bor
    SET due_at_bor = v_new_due
    WHERE id_bor = p_bor_id;

    COMMIT;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_refresh_neighborhood_summary()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        DROP TABLE IF EXISTS neighborhood_summary_mat_new;
        RESIGNAL;
    END;

    DROP TABLE IF EXISTS neighborhood_summary_mat_new;
    CREATE TABLE neighborhood_summary_mat_new LIKE neighborhood_summary_mat;

    INSERT INTO neighborhood_summary_mat_new (
        id_nbh, neighborhood_name_nbh, city_name_nbh,
        state_code_sta, state_name_sta,
        latitude_nbh, longitude_nbh, location_point_nbh, created_at_nbh,
        total_members, active_members, verified_members,
        total_tools, available_tools,
        active_borrows, completed_borrows_30d,
        upcoming_events, zip_codes, refreshed_at
    )
    SELECT
        nbh.id_nbh,
        nbh.neighborhood_name_nbh,
        nbh.city_name_nbh,
        sta.state_code_sta,
        sta.state_name_sta,
        nbh.latitude_nbh,
        nbh.longitude_nbh,
        nbh.location_point_nbh,
        nbh.created_at_nbh,
        COALESCE(member_stats.total_members, 0),
        COALESCE(member_stats.active_members, 0),
        COALESCE(member_stats.verified_members, 0),
        COALESCE(tool_stats.total_tools, 0),
        COALESCE(tool_stats.available_tools, 0),
        COALESCE(borrow_stats.active_borrows, 0),
        COALESCE(borrow_stats.completed_borrows_30d, 0),
        COALESCE(event_stats.upcoming_events, 0),
        zip_list.zip_codes,
        NOW()
    FROM neighborhood_nbh nbh
    JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
    LEFT JOIN (
        SELECT id_nbh_acc,
               COUNT(*) AS total_members,
               SUM(CASE WHEN id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active') THEN 1 ELSE 0 END) AS active_members,
               SUM(CASE WHEN is_verified_acc = TRUE THEN 1 ELSE 0 END) AS verified_members
        FROM account_acc
        WHERE id_nbh_acc IS NOT NULL
          AND id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')
        GROUP BY id_nbh_acc
    ) member_stats ON nbh.id_nbh = member_stats.id_nbh_acc
    LEFT JOIN (
        SELECT a.id_nbh_acc,
               COUNT(*) AS total_tools,
               SUM(CASE WHEN t.is_available_tol = TRUE THEN 1 ELSE 0 END) AS available_tools
        FROM tool_tol t
        JOIN account_acc a ON t.id_acc_tol = a.id_acc
        WHERE a.id_nbh_acc IS NOT NULL
        GROUP BY a.id_nbh_acc
    ) tool_stats ON nbh.id_nbh = tool_stats.id_nbh_acc
    LEFT JOIN (
        SELECT a.id_nbh_acc,
               SUM(CASE WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed') THEN 1 ELSE 0 END) AS active_borrows,
               SUM(CASE WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
                        AND b.returned_at_bor >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS completed_borrows_30d
        FROM borrow_bor b
        JOIN account_acc a ON b.id_acc_bor = a.id_acc
        WHERE a.id_nbh_acc IS NOT NULL
        GROUP BY a.id_nbh_acc
    ) borrow_stats ON nbh.id_nbh = borrow_stats.id_nbh_acc
    LEFT JOIN (
        SELECT id_nbh_evt, COUNT(*) AS upcoming_events
        FROM event_evt
        WHERE start_at_evt > NOW()
        GROUP BY id_nbh_evt
    ) event_stats ON nbh.id_nbh = event_stats.id_nbh_evt
    LEFT JOIN (
        SELECT id_nbh_nbhzpc,
               GROUP_CONCAT(zip_code_nbhzpc ORDER BY is_primary_nbhzpc DESC SEPARATOR ', ') AS zip_codes
        FROM neighborhood_zip_nbhzpc
        GROUP BY id_nbh_nbhzpc
    ) zip_list ON nbh.id_nbh = zip_list.id_nbh_nbhzpc;

    RENAME TABLE
        neighborhood_summary_mat     TO neighborhood_summary_mat_old,
        neighborhood_summary_mat_new TO neighborhood_summary_mat,
        neighborhood_summary_mat_old TO neighborhood_summary_mat_new;
    DROP TABLE neighborhood_summary_mat_new;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_refresh_user_reputation()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        DROP TABLE IF EXISTS user_reputation_mat_new;
        RESIGNAL;
    END;

    DROP TABLE IF EXISTS user_reputation_mat_new;
    CREATE TABLE user_reputation_mat_new LIKE user_reputation_mat;

    INSERT INTO user_reputation_mat_new (
        id_acc, full_name, email_address_acc, account_status, member_since,
        lender_avg_rating, lender_rating_count,
        borrower_avg_rating, borrower_rating_count,
        overall_avg_rating, total_rating_count,
        tools_owned, completed_borrows, refreshed_at
    )
    SELECT
        a.id_acc,
        CONCAT(a.first_name_acc, ' ', a.last_name_acc),
        a.email_address_acc,
        ast.status_name_ast,
        a.created_at_acc,
        COALESCE(lender_stats.avg_score, 0),
        COALESCE(lender_stats.rating_count, 0),
        COALESCE(borrower_stats.avg_score, 0),
        COALESCE(borrower_stats.rating_count, 0),
        ROUND((COALESCE(lender_stats.avg_score, 0) + COALESCE(borrower_stats.avg_score, 0)) /
              NULLIF((CASE WHEN lender_stats.avg_score IS NOT NULL THEN 1 ELSE 0 END +
                      CASE WHEN borrower_stats.avg_score IS NOT NULL THEN 1 ELSE 0 END), 0), 1),
        COALESCE(lender_stats.rating_count, 0) + COALESCE(borrower_stats.rating_count, 0),
        COALESCE(tool_counts.tools_owned, 0),
        COALESCE(borrow_counts.completed_borrows, 0),
        NOW()
    FROM account_acc a
    JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
    LEFT JOIN (
        SELECT id_acc_target_urt,
               ROUND(AVG(score_urt), 1) AS avg_score,
               COUNT(*) AS rating_count
        FROM user_rating_urt
        WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender')
        GROUP BY id_acc_target_urt
    ) lender_stats ON a.id_acc = lender_stats.id_acc_target_urt
    LEFT JOIN (
        SELECT id_acc_target_urt,
               ROUND(AVG(score_urt), 1) AS avg_score,
               COUNT(*) AS rating_count
        FROM user_rating_urt
        WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower')
        GROUP BY id_acc_target_urt
    ) borrower_stats ON a.id_acc = borrower_stats.id_acc_target_urt
    LEFT JOIN (
        SELECT id_acc_tol, COUNT(*) AS tools_owned
        FROM tool_tol
        GROUP BY id_acc_tol
    ) tool_counts ON a.id_acc = tool_counts.id_acc_tol
    LEFT JOIN (
        SELECT id_acc_bor, COUNT(*) AS completed_borrows
        FROM borrow_bor
        WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
        GROUP BY id_acc_bor
    ) borrow_counts ON a.id_acc = borrow_counts.id_acc_bor
    WHERE a.id_ast_acc != fn_get_account_status_id('deleted');

    RENAME TABLE
        user_reputation_mat     TO user_reputation_mat_old,
        user_reputation_mat_new TO user_reputation_mat,
        user_reputation_mat_old TO user_reputation_mat_new;
    DROP TABLE user_reputation_mat_new;
END$$
DELIMITER ;

-- Per-account UPSERT variant of sp_refresh_user_reputation; safe to call from
-- triggers (no DDL, no transaction boundaries) and used by the AFTER
-- INSERT/UPDATE/DELETE triggers on user_rating_urt so profile pages reflect
-- rating changes immediately instead of waiting for the 4-hour rebuild.
DELIMITER $$
CREATE PROCEDURE sp_refresh_user_reputation_for(IN p_account_id INT)
proc_end: BEGIN
    DECLARE v_deleted_status_id INT;
    DECLARE v_account_status_id INT;
    DECLARE v_full_name         VARCHAR(101);
    DECLARE v_email             VARCHAR(255);
    DECLARE v_status_name       VARCHAR(30);
    DECLARE v_member_since      TIMESTAMP;
    DECLARE v_lender_avg        DECIMAL(3, 1) DEFAULT 0;
    DECLARE v_lender_count      INT UNSIGNED  DEFAULT 0;
    DECLARE v_borrower_avg      DECIMAL(3, 1) DEFAULT 0;
    DECLARE v_borrower_count    INT UNSIGNED  DEFAULT 0;
    DECLARE v_overall_avg       DECIMAL(3, 1) DEFAULT NULL;
    DECLARE v_total_count       INT UNSIGNED  DEFAULT 0;
    DECLARE v_tools_owned       INT UNSIGNED  DEFAULT 0;
    DECLARE v_completed_borrows INT UNSIGNED  DEFAULT 0;

    SET v_deleted_status_id = fn_get_account_status_id('deleted');

    SELECT a.id_ast_acc,
           CONCAT(a.first_name_acc, ' ', a.last_name_acc),
           a.email_address_acc,
           ast.status_name_ast,
           a.created_at_acc
      INTO v_account_status_id, v_full_name, v_email, v_status_name, v_member_since
      FROM account_acc a
      JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
     WHERE a.id_acc = p_account_id
     LIMIT 1;

    IF v_full_name IS NULL THEN
        LEAVE proc_end;
    END IF;

    IF v_account_status_id = v_deleted_status_id THEN
        DELETE FROM user_reputation_mat WHERE id_acc = p_account_id;
        LEAVE proc_end;
    END IF;

    SELECT COALESCE(ROUND(AVG(score_urt), 1), 0), COUNT(*)
      INTO v_lender_avg, v_lender_count
      FROM user_rating_urt
     WHERE id_acc_target_urt = p_account_id
       AND id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender');

    SELECT COALESCE(ROUND(AVG(score_urt), 1), 0), COUNT(*)
      INTO v_borrower_avg, v_borrower_count
      FROM user_rating_urt
     WHERE id_acc_target_urt = p_account_id
       AND id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower');

    SET v_overall_avg = ROUND(
        (v_lender_avg + v_borrower_avg) /
        NULLIF((CASE WHEN v_lender_count > 0 THEN 1 ELSE 0 END +
                CASE WHEN v_borrower_count > 0 THEN 1 ELSE 0 END), 0), 1);

    SET v_total_count = v_lender_count + v_borrower_count;

    SELECT COUNT(*) INTO v_tools_owned
      FROM tool_tol
     WHERE id_acc_tol = p_account_id;

    SELECT COUNT(*) INTO v_completed_borrows
      FROM borrow_bor
     WHERE id_acc_bor = p_account_id
       AND id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned');

    INSERT INTO user_reputation_mat (
        id_acc, full_name, email_address_acc, account_status, member_since,
        lender_avg_rating, lender_rating_count,
        borrower_avg_rating, borrower_rating_count,
        overall_avg_rating, total_rating_count,
        tools_owned, completed_borrows, refreshed_at
    ) VALUES (
        p_account_id, v_full_name, v_email, v_status_name, v_member_since,
        v_lender_avg, v_lender_count,
        v_borrower_avg, v_borrower_count,
        v_overall_avg, v_total_count,
        v_tools_owned, v_completed_borrows, NOW()
    )
    ON DUPLICATE KEY UPDATE
        full_name             = v_full_name,
        email_address_acc     = v_email,
        account_status        = v_status_name,
        member_since          = v_member_since,
        lender_avg_rating     = v_lender_avg,
        lender_rating_count   = v_lender_count,
        borrower_avg_rating   = v_borrower_avg,
        borrower_rating_count = v_borrower_count,
        overall_avg_rating    = v_overall_avg,
        total_rating_count    = v_total_count,
        tools_owned           = v_tools_owned,
        completed_borrows     = v_completed_borrows,
        refreshed_at          = NOW();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_refresh_tool_statistics()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        DROP TABLE IF EXISTS tool_statistics_mat_new;
        RESIGNAL;
    END;

    DROP TABLE IF EXISTS tool_statistics_mat_new;
    CREATE TABLE tool_statistics_mat_new LIKE tool_statistics_mat;

    INSERT INTO tool_statistics_mat_new (
        id_tol, tool_name_tol, owner_id, owner_name, tool_condition,
        rental_fee_tol, estimated_value_tol, created_at_tol,
        avg_rating, rating_count, five_star_count,
        total_borrows, completed_borrows, cancelled_borrows, denied_borrows,
        total_hours_borrowed, last_borrowed_at, incident_count, refreshed_at
    )
    SELECT
        t.id_tol,
        t.tool_name_tol,
        t.id_acc_tol,
        CONCAT(a.first_name_acc, ' ', a.last_name_acc),
        tcd.condition_name_tcd,
        t.rental_fee_tol,
        t.estimated_value_tol,
        t.created_at_tol,
        COALESCE(rating_stats.avg_rating, 0),
        COALESCE(rating_stats.rating_count, 0),
        COALESCE(rating_stats.five_star_count, 0),
        COALESCE(borrow_stats.total_borrows, 0),
        COALESCE(borrow_stats.completed_borrows, 0),
        COALESCE(borrow_stats.cancelled_borrows, 0),
        COALESCE(borrow_stats.denied_borrows, 0),
        COALESCE(borrow_stats.total_hours_borrowed, 0),
        borrow_stats.last_borrowed_at,
        COALESCE(incident_stats.incident_count, 0),
        NOW()
    FROM tool_tol t
    JOIN account_acc a ON t.id_acc_tol = a.id_acc
    JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
    LEFT JOIN (
        SELECT id_tol_trt,
               ROUND(AVG(score_trt), 1) AS avg_rating,
               COUNT(*) AS rating_count,
               SUM(CASE WHEN score_trt = 5 THEN 1 ELSE 0 END) AS five_star_count
        FROM tool_rating_trt
        GROUP BY id_tol_trt
    ) rating_stats ON t.id_tol = rating_stats.id_tol_trt
    LEFT JOIN (
        SELECT id_tol_bor,
               COUNT(*) AS total_borrows,
               SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned') THEN 1 ELSE 0 END) AS completed_borrows,
               SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'cancelled') THEN 1 ELSE 0 END) AS cancelled_borrows,
               SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'denied') THEN 1 ELSE 0 END) AS denied_borrows,
               SUM(CASE WHEN id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned') THEN loan_duration_hours_bor ELSE 0 END) AS total_hours_borrowed,
               MAX(borrowed_at_bor) AS last_borrowed_at
        FROM borrow_bor
        GROUP BY id_tol_bor
    ) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
    LEFT JOIN (
        SELECT b.id_tol_bor, COUNT(*) AS incident_count
        FROM incident_report_irt irt
        JOIN borrow_bor b ON irt.id_bor_irt = b.id_bor
        GROUP BY b.id_tol_bor
    ) incident_stats ON t.id_tol = incident_stats.id_tol_bor;

    RENAME TABLE
        tool_statistics_mat     TO tool_statistics_mat_old,
        tool_statistics_mat_new TO tool_statistics_mat,
        tool_statistics_mat_old TO tool_statistics_mat_new;
    DROP TABLE tool_statistics_mat_new;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_refresh_category_summary()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        DROP TABLE IF EXISTS category_summary_mat_new;
        RESIGNAL;
    END;

    DROP TABLE IF EXISTS category_summary_mat_new;
    CREATE TABLE category_summary_mat_new LIKE category_summary_mat;

    INSERT INTO category_summary_mat_new (
        id_cat, category_name_cat, category_icon,
        total_tools, listed_tools, available_tools,
        category_avg_rating, total_completed_borrows,
        min_rental_fee, max_rental_fee, avg_rental_fee, refreshed_at
    )
    SELECT
        c.id_cat,
        c.category_name_cat,
        vec.file_name_vec,
        COUNT(DISTINCT tc.id_tol_tolcat),
        SUM(CASE WHEN t.is_available_tol = TRUE THEN 1 ELSE 0 END),
        SUM(CASE
            WHEN t.is_available_tol = TRUE
             AND owner.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')
             AND NOT EXISTS (
                 SELECT 1 FROM borrow_bor b
                 WHERE b.id_tol_bor = t.id_tol
                   AND b.id_bst_bor IN (
                       (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'),
                       (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'),
                       (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
                   )
             )
             AND NOT EXISTS (
                 SELECT 1 FROM availability_block_avb avb
                 WHERE avb.id_tol_avb = t.id_tol
                   AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
             )
            THEN 1 ELSE 0
        END),
        ROUND(AVG(rating_stats.avg_rating), 1),
        COALESCE(SUM(borrow_stats.completed_borrows), 0),
        MIN(t.rental_fee_tol),
        MAX(t.rental_fee_tol),
        ROUND(AVG(t.rental_fee_tol), 2),
        NOW()
    FROM category_cat c
    LEFT JOIN vector_image_vec vec ON c.id_vec_cat = vec.id_vec
    LEFT JOIN tool_category_tolcat tc ON c.id_cat = tc.id_cat_tolcat
    LEFT JOIN tool_tol t ON tc.id_tol_tolcat = t.id_tol
    LEFT JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
    LEFT JOIN (
        SELECT id_tol_trt, ROUND(AVG(score_trt), 1) AS avg_rating
        FROM tool_rating_trt
        GROUP BY id_tol_trt
    ) rating_stats ON t.id_tol = rating_stats.id_tol_trt
    LEFT JOIN (
        SELECT id_tol_bor, COUNT(*) AS completed_borrows
        FROM borrow_bor
        WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
        GROUP BY id_tol_bor
    ) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
    GROUP BY c.id_cat, c.category_name_cat, vec.file_name_vec;

    RENAME TABLE
        category_summary_mat     TO category_summary_mat_old,
        category_summary_mat_new TO category_summary_mat,
        category_summary_mat_old TO category_summary_mat_new;
    DROP TABLE category_summary_mat_new;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_refresh_platform_daily_stat()
BEGIN
    DECLARE v_today DATE DEFAULT CURDATE();
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM platform_daily_stat_pds WHERE stat_date_pds = v_today;

    INSERT INTO platform_daily_stat_pds (
        stat_date_pds,
        total_accounts_pds, active_accounts_pds, new_accounts_today_pds,
        total_tools_pds, available_tools_pds, new_tools_today_pds,
        active_borrows_pds, completed_today_pds, new_requests_today_pds,
        open_disputes_pds, open_incidents_pds, overdue_borrows_pds,
        deposits_held_total_pds,
        platform_avg_rating_pds,
        refreshed_at_pds
    )
    SELECT
        v_today,
        (SELECT COUNT(*) FROM account_acc
         WHERE id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')),
        (SELECT COUNT(*) FROM account_acc
         WHERE id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active')),
        (SELECT COUNT(*) FROM account_acc
         WHERE DATE(created_at_acc) = v_today
           AND id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')),
        (SELECT COUNT(*) FROM tool_tol),
        (SELECT COUNT(*) FROM tool_tol WHERE is_available_tol = TRUE),
        (SELECT COUNT(*) FROM tool_tol WHERE DATE(created_at_tol) = v_today),
        (SELECT COUNT(*) FROM borrow_bor
         WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')),
        (SELECT COUNT(*) FROM borrow_bor
         WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
           AND DATE(returned_at_bor) = v_today),
        (SELECT COUNT(*) FROM borrow_bor
         WHERE DATE(requested_at_bor) = v_today),
        (SELECT COUNT(*) FROM dispute_dsp
         WHERE id_dst_dsp = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'open')),
        (SELECT COUNT(*) FROM incident_report_irt WHERE resolved_at_irt IS NULL),
        (SELECT COUNT(*) FROM borrow_bor
         WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
           AND due_at_bor < NOW()),
        (SELECT COALESCE(SUM(amount_sdp), 0) FROM security_deposit_sdp
         WHERE id_dps_sdp = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'held')),
        (SELECT AVG(score_trt) FROM tool_rating_trt),
        NOW();

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_refresh_all_summaries()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET SESSION sql_safe_updates = @saved_safe_updates;
        RESIGNAL;
    END;

    SET @saved_safe_updates = @@SESSION.sql_safe_updates;
    SET SESSION sql_safe_updates = 0;

    CALL sp_refresh_neighborhood_summary();
    CALL sp_refresh_user_reputation();
    CALL sp_refresh_tool_statistics();
    CALL sp_refresh_category_summary();
    CALL sp_refresh_platform_daily_stat();

    SET SESSION sql_safe_updates = @saved_safe_updates;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_create_borrow_request(
    IN p_tool_id INT,
    IN p_borrower_id INT,
    IN p_loan_duration_hours INT,
    IN p_notes TEXT,
    OUT p_borrow_id INT,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_tool_owner_id INT;
    DECLARE v_borrower_status_id INT;
    DECLARE v_owner_status_id INT;
    DECLARE v_deleted_status_id INT;
    DECLARE v_requested_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_borrow_id = NULL;
        ROLLBACK;
    END;

    SET p_error_message = NULL;
    SET p_borrow_id = NULL;

    SET v_deleted_status_id = fn_get_account_status_id('deleted');
    SET v_requested_status_id = fn_get_borrow_status_id('requested');

    START TRANSACTION;

    SELECT id_acc_tol INTO v_tool_owner_id
    FROM tool_tol
    WHERE id_tol = p_tool_id
    FOR UPDATE;

    IF v_tool_owner_id IS NULL THEN
        SET p_error_message = 'Tool not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tool not found';
    END IF;

    IF v_tool_owner_id = p_borrower_id THEN
        SET p_error_message = 'Cannot borrow your own tool';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot borrow your own tool';
    END IF;

    IF NOT fn_is_tool_available(p_tool_id) THEN
        SET p_error_message = 'Tool is not available for borrowing';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tool is not available for borrowing';
    END IF;

    SELECT id_ast_acc INTO v_borrower_status_id
    FROM account_acc
    WHERE id_acc = p_borrower_id;

    IF v_borrower_status_id IS NULL THEN
        SET p_error_message = 'Borrower account not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrower account not found';
    END IF;

    IF v_borrower_status_id = v_deleted_status_id THEN
        SET p_error_message = 'Borrower account is deleted';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrower account is deleted';
    END IF;

    SELECT id_ast_acc INTO v_owner_status_id
    FROM account_acc
    WHERE id_acc = v_tool_owner_id;

    IF v_owner_status_id = v_deleted_status_id THEN
        SET p_error_message = 'Tool owner account is deleted';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tool owner account is deleted';
    END IF;

    INSERT INTO borrow_bor (
        id_tol_bor,
        id_acc_bor,
        id_bst_bor,
        loan_duration_hours_bor,
        notes_text_bor,
        requested_at_bor
    ) VALUES (
        p_tool_id,
        p_borrower_id,
        v_requested_status_id,
        p_loan_duration_hours,
        p_notes,
        NOW()
    );

    SET p_borrow_id = LAST_INSERT_ID();

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_approve_borrow_request(
    IN p_borrow_id INT,
    IN p_approver_id INT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_tool_owner_id INT;
    DECLARE v_tool_id INT;
    DECLARE v_requested_status_id INT;
    DECLARE v_approved_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_requested_status_id = fn_get_borrow_status_id('requested');
    SET v_approved_status_id = fn_get_borrow_status_id('approved');

    START TRANSACTION;

    SELECT b.id_bst_bor, b.id_tol_bor, t.id_acc_tol
    INTO v_current_status_id, v_tool_id, v_tool_owner_id
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bor = p_borrow_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Borrow request not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow request not found';
    END IF;

    IF v_tool_owner_id != p_approver_id THEN
        SET p_error_message = 'Only the tool owner can approve requests';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only the tool owner can approve requests';
    END IF;

    IF v_current_status_id != v_requested_status_id THEN
        SET p_error_message = 'Can only approve requests in "requested" status';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only approve requests in "requested" status';
    END IF;

    UPDATE borrow_bor
    SET id_bst_bor = v_approved_status_id,
        approved_at_bor = NOW()
    WHERE id_bor = p_borrow_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_deny_borrow_request(
    IN p_borrow_id INT,
    IN p_denier_id INT,
    IN p_reason TEXT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_tool_owner_id INT;
    DECLARE v_requested_status_id INT;
    DECLARE v_denied_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_requested_status_id = fn_get_borrow_status_id('requested');
    SET v_denied_status_id = fn_get_borrow_status_id('denied');

    START TRANSACTION;

    SELECT b.id_bst_bor, t.id_acc_tol
    INTO v_current_status_id, v_tool_owner_id
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bor = p_borrow_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Borrow request not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow request not found';
    END IF;

    IF v_tool_owner_id != p_denier_id THEN
        SET p_error_message = 'Only the tool owner can deny requests';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only the tool owner can deny requests';
    END IF;

    IF v_current_status_id != v_requested_status_id THEN
        SET p_error_message = 'Can only deny requests in "requested" status';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only deny requests in "requested" status';
    END IF;

    UPDATE borrow_bor
    SET id_bst_bor = v_denied_status_id,
        notes_text_bor = CONCAT(COALESCE(notes_text_bor, ''), '\n[DENIED] ', p_reason)
    WHERE id_bor = p_borrow_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_complete_pickup(
    IN p_borrow_id INT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_tool_id INT;
    DECLARE v_loan_hours INT;
    DECLARE v_approved_status_id INT;
    DECLARE v_borrowed_status_id INT;
    DECLARE v_borrow_block_type_id INT;
    DECLARE v_due_at TIMESTAMP;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_approved_status_id = fn_get_borrow_status_id('approved');
    SET v_borrowed_status_id = fn_get_borrow_status_id('borrowed');
    SET v_borrow_block_type_id = fn_get_block_type_id('borrow');

    START TRANSACTION;

    SELECT id_bst_bor, id_tol_bor, loan_duration_hours_bor
    INTO v_current_status_id, v_tool_id, v_loan_hours
    FROM borrow_bor
    WHERE id_bor = p_borrow_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Borrow request not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow request not found';
    END IF;

    IF v_current_status_id != v_approved_status_id THEN
        SET p_error_message = 'Can only complete pickup for approved requests';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only complete pickup for approved requests';
    END IF;

    SET v_due_at = DATE_ADD(NOW(), INTERVAL v_loan_hours HOUR);

    UPDATE borrow_bor
    SET id_bst_bor = v_borrowed_status_id,
        borrowed_at_bor = NOW(),
        due_at_bor = v_due_at
    WHERE id_bor = p_borrow_id;

    INSERT INTO availability_block_avb (
        id_tol_avb,
        id_btp_avb,
        start_at_avb,
        end_at_avb,
        id_bor_avb,
        notes_text_avb
    ) VALUES (
        v_tool_id,
        v_borrow_block_type_id,
        NOW(),
        v_due_at,
        p_borrow_id,
        'Active borrow period'
    );

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_complete_return(
    IN p_borrow_id INT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_borrowed_status_id INT;
    DECLARE v_returned_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_borrowed_status_id = fn_get_borrow_status_id('borrowed');
    SET v_returned_status_id = fn_get_borrow_status_id('returned');

    START TRANSACTION;

    SELECT id_bst_bor INTO v_current_status_id
    FROM borrow_bor
    WHERE id_bor = p_borrow_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Borrow request not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow request not found';
    END IF;

    IF v_current_status_id != v_borrowed_status_id THEN
        SET p_error_message = 'Can only complete return for borrowed items';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only complete return for borrowed items';
    END IF;

    UPDATE borrow_bor
    SET id_bst_bor = v_returned_status_id,
        returned_at_bor = NOW()
    WHERE id_bor = p_borrow_id;

    DELETE FROM availability_block_avb
    WHERE id_bor_avb = p_borrow_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_cancel_borrow_request(
    IN p_borrow_id INT,
    IN p_canceller_id INT,
    IN p_reason TEXT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_borrower_id INT;
    DECLARE v_tool_owner_id INT;
    DECLARE v_requested_status_id INT;
    DECLARE v_approved_status_id INT;
    DECLARE v_cancelled_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_requested_status_id = fn_get_borrow_status_id('requested');
    SET v_approved_status_id = fn_get_borrow_status_id('approved');
    SET v_cancelled_status_id = fn_get_borrow_status_id('cancelled');

    START TRANSACTION;

    SELECT b.id_bst_bor, b.id_acc_bor, t.id_acc_tol
    INTO v_current_status_id, v_borrower_id, v_tool_owner_id
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bor = p_borrow_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Borrow request not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow request not found';
    END IF;

    IF p_canceller_id != v_borrower_id AND p_canceller_id != v_tool_owner_id THEN
        SET p_error_message = 'Only the borrower or tool owner can cancel';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only the borrower or tool owner can cancel';
    END IF;

    IF v_current_status_id NOT IN (v_requested_status_id, v_approved_status_id) THEN
        SET p_error_message = 'Can only cancel requests in "requested" or "approved" status';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only cancel requests in "requested" or "approved" status';
    END IF;

    UPDATE borrow_bor
    SET id_bst_bor = v_cancelled_status_id,
        cancelled_at_bor = NOW(),
        notes_text_bor = CONCAT(COALESCE(notes_text_bor, ''), '\n[CANCELLED] ', p_reason)
    WHERE id_bor = p_borrow_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_rate_user(
    IN p_borrow_id INT,
    IN p_rater_id INT,
    IN p_target_id INT,
    IN p_role VARCHAR(30),
    IN p_score INT,
    IN p_review_text TEXT,
    OUT p_rating_id INT,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_borrow_status_id INT;
    DECLARE v_borrower_id INT;
    DECLARE v_lender_id INT;
    DECLARE v_returned_status_id INT;
    DECLARE v_role_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_rating_id = NULL;
        ROLLBACK;
    END;

    SET p_rating_id = NULL;
    SET p_error_message = NULL;

    IF p_score < 1 OR p_score > 5 THEN
        SET p_error_message = 'Score must be between 1 and 5';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Score must be between 1 and 5';
    END IF;

    SET v_returned_status_id = fn_get_borrow_status_id('returned');
    SET v_role_id = fn_get_rating_role_id(p_role);

    IF v_role_id IS NULL THEN
        SET p_error_message = 'Invalid rating role';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid rating role';
    END IF;

    START TRANSACTION;

    SELECT b.id_bst_bor, b.id_acc_bor, t.id_acc_tol
    INTO v_borrow_status_id, v_borrower_id, v_lender_id
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bor = p_borrow_id;

    IF v_borrow_status_id IS NULL THEN
        SET p_error_message = 'Borrow not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow not found';
    END IF;

    IF v_borrow_status_id != v_returned_status_id THEN
        SET p_error_message = 'Can only rate completed borrows';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only rate completed borrows';
    END IF;

    IF p_rater_id != v_borrower_id AND p_rater_id != v_lender_id THEN
        SET p_error_message = 'Rater must be the borrower or lender';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rater must be the borrower or lender';
    END IF;

    IF p_target_id != v_borrower_id AND p_target_id != v_lender_id THEN
        SET p_error_message = 'Target must be the borrower or lender';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target must be the borrower or lender';
    END IF;

    IF p_rater_id = p_target_id THEN
        SET p_error_message = 'Cannot rate yourself';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot rate yourself';
    END IF;

    INSERT INTO user_rating_urt (
        id_acc_urt,
        id_acc_target_urt,
        id_bor_urt,
        id_rtr_urt,
        score_urt,
        comment_text_urt
    ) VALUES (
        p_rater_id,
        p_target_id,
        p_borrow_id,
        v_role_id,
        p_score,
        p_review_text
    );

    SET p_rating_id = LAST_INSERT_ID();

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_rate_tool(
    IN p_borrow_id INT,
    IN p_rater_id INT,
    IN p_score INT,
    IN p_review_text TEXT,
    OUT p_rating_id INT,
    OUT p_error_message VARCHAR(255)
)
BEGIN
    DECLARE v_borrow_status_id INT;
    DECLARE v_borrower_id INT;
    DECLARE v_tool_id INT;
    DECLARE v_returned_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_rating_id = NULL;
        ROLLBACK;
    END;

    SET p_rating_id = NULL;
    SET p_error_message = NULL;

    IF p_score < 1 OR p_score > 5 THEN
        SET p_error_message = 'Score must be between 1 and 5';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Score must be between 1 and 5';
    END IF;

    SET v_returned_status_id = fn_get_borrow_status_id('returned');

    START TRANSACTION;

    SELECT id_bst_bor, id_acc_bor, id_tol_bor
    INTO v_borrow_status_id, v_borrower_id, v_tool_id
    FROM borrow_bor
    WHERE id_bor = p_borrow_id;

    IF v_borrow_status_id IS NULL THEN
        SET p_error_message = 'Borrow not found';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Borrow not found';
    END IF;

    IF v_borrow_status_id != v_returned_status_id THEN
        SET p_error_message = 'Can only rate tools after borrow is completed';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can only rate tools after borrow is completed';
    END IF;

    IF p_rater_id != v_borrower_id THEN
        SET p_error_message = 'Only the borrower can rate the tool';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only the borrower can rate the tool';
    END IF;

    INSERT INTO tool_rating_trt (
        id_acc_trt,
        id_tol_trt,
        id_bor_trt,
        score_trt,
        comment_text_trt
    ) VALUES (
        p_rater_id,
        v_tool_id,
        p_borrow_id,
        p_score,
        p_review_text
    );

    SET p_rating_id = LAST_INSERT_ID();

    COMMIT;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_send_notification(
    IN p_account_id INT,
    IN p_notification_type VARCHAR(30),
    IN p_title VARCHAR(255),
    IN p_body TEXT,
    IN p_related_borrow_id INT,
    OUT p_notification_id INT
)
BEGIN
    DECLARE v_type_id INT;

    SET v_type_id = fn_get_notification_type_id(p_notification_type);

    IF v_type_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Unknown notification type';
    END IF;

    INSERT INTO notification_ntf (
        id_acc_ntf,
        id_ntt_ntf,
        title_ntf,
        body_ntf,
        id_bor_ntf,
        is_read_ntf,
        created_at_ntf
    ) VALUES (
        p_account_id,
        v_type_id,
        p_title,
        p_body,
        p_related_borrow_id,
        FALSE,
        NOW()
    );

    SET p_notification_id = LAST_INSERT_ID();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_mark_notifications_read(
    IN p_account_id INT,
    IN p_notification_ids TEXT,
    OUT p_count INT
)
proc_body: BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION RESIGNAL;

    IF p_notification_ids IS NULL OR p_notification_ids = '' THEN
        UPDATE notification_ntf
        SET is_read_ntf = TRUE,
            read_at_ntf = NOW()
        WHERE id_acc_ntf = p_account_id
          AND is_read_ntf = FALSE;
    ELSE
        UPDATE notification_ntf
        SET is_read_ntf = TRUE,
            read_at_ntf = NOW()
        WHERE id_acc_ntf = p_account_id
          AND is_read_ntf = FALSE
          AND FIND_IN_SET(id_ntf, p_notification_ids) > 0;
    END IF;

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_send_overdue_notifications(
    OUT p_count INT
)
BEGIN
    DECLARE v_borrowed_status_id INT;
    DECLARE v_due_type_id INT;

    SET v_borrowed_status_id = fn_get_borrow_status_id('borrowed');
    SET v_due_type_id = fn_get_notification_type_id('due');

    INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf)
    SELECT
        b.id_acc_bor,
        v_due_type_id,
        CONCAT('Overdue: ', t.tool_name_tol),
        CONCAT('Your borrow of "', t.tool_name_tol, '" was due on ',
               DATE_FORMAT(b.due_at_bor, '%M %d, %Y at %h:%i %p'),
               '. Please return it as soon as possible.'),
        b.id_bor,
        FALSE
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bst_bor = v_borrowed_status_id
      AND b.due_at_bor < NOW()
      AND NOT EXISTS (
          SELECT 1 FROM notification_ntf n
          WHERE n.id_bor_ntf = b.id_bor
            AND n.id_ntt_ntf = v_due_type_id
            AND n.created_at_ntf >= CURDATE()
            AND n.created_at_ntf <  CURDATE() + INTERVAL 1 DAY
      );

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_cleanup_expired_handover_codes(
    OUT p_count INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION RESIGNAL;

    DELETE FROM handover_verification_hov
    WHERE expires_at_hov < NOW()
      AND verified_at_hov IS NULL;

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_archive_old_notifications(
    IN p_days_old INT,
    OUT p_count INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION RESIGNAL;

    IF p_days_old IS NULL OR p_days_old < 30 THEN
        SET p_days_old = 90;
    END IF;

    DELETE FROM notification_ntf
    WHERE is_read_ntf = TRUE
      AND created_at_ntf < DATE_SUB(NOW(), INTERVAL p_days_old DAY);

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_cleanup_old_search_logs(
    IN p_days_old INT,
    OUT p_count INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION RESIGNAL;

    IF p_days_old IS NULL OR p_days_old < 7 THEN
        SET p_days_old = 30;
    END IF;

    DELETE FROM search_log_slg
    WHERE created_at_slg < DATE_SUB(NOW(), INTERVAL p_days_old DAY);

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_release_deposit_on_return(
    IN p_borrow_id INT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_deposit_id INT;
    DECLARE v_current_status_id INT;
    DECLARE v_held_status_id INT;
    DECLARE v_released_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_held_status_id = fn_get_deposit_status_id('held');
    SET v_released_status_id = fn_get_deposit_status_id('released');

    START TRANSACTION;

    SELECT id_sdp, id_dps_sdp
    INTO v_deposit_id, v_current_status_id
    FROM security_deposit_sdp
    WHERE id_bor_sdp = p_borrow_id
    FOR UPDATE;

    IF v_deposit_id IS NULL THEN
        SET p_success = TRUE;
        COMMIT;
        LEAVE proc_block;
    END IF;

    IF v_current_status_id != v_held_status_id THEN
        SET p_error_message = 'Deposit is not in held status';
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Deposit is not in held status';
    END IF;

    UPDATE security_deposit_sdp
    SET id_dps_sdp = v_released_status_id,
        released_at_sdp = NOW()
    WHERE id_sdp = v_deposit_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_search_available_tools(
    IN p_search_term VARCHAR(255),
    IN p_zip_code VARCHAR(10),
    IN p_category_id INT,
    IN p_max_rental_fee DECIMAL(6,2),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_active_status_id INT;
    DECLARE v_deleted_status_id INT;

    SET v_active_status_id = fn_get_account_status_id('active');
    SET v_deleted_status_id = fn_get_account_status_id('deleted');

    IF p_limit IS NULL OR p_limit < 1 THEN SET p_limit = 20; END IF;
    IF p_limit > 100 THEN SET p_limit = 100; END IF;
    IF p_offset IS NULL OR p_offset < 0 THEN SET p_offset = 0; END IF;

    SELECT
        t.id_tol,
        t.tool_name_tol,
        t.tool_description_tol,
        t.rental_fee_tol,
        t.default_loan_duration_hours_tol,
        t.is_deposit_required_tol,
        t.default_deposit_amount_tol,
        tcd.condition_name_tcd AS tool_condition,
        CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
        a.zip_code_acc AS owner_zip,
        tim.file_name_tim AS primary_image,
        COALESCE(rs.avg_rating, 0) AS avg_rating,
        COALESCE(rs.rating_count, 0) AS rating_count
    FROM tool_tol t
    JOIN account_acc a ON t.id_acc_tol = a.id_acc
    JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
    LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
    LEFT JOIN (
        SELECT id_tol_trt, ROUND(AVG(score_trt), 1) AS avg_rating, COUNT(*) AS rating_count
        FROM tool_rating_trt
        GROUP BY id_tol_trt
    ) rs ON t.id_tol = rs.id_tol_trt
    LEFT JOIN tool_category_tolcat tc ON t.id_tol = tc.id_tol_tolcat
    WHERE t.is_available_tol = TRUE
      AND a.id_ast_acc NOT IN (v_deleted_status_id)
      AND (p_search_term IS NULL OR MATCH(t.tool_name_tol, t.tool_description_tol) AGAINST(p_search_term IN NATURAL LANGUAGE MODE))
      AND (p_zip_code IS NULL OR a.zip_code_acc = p_zip_code)
      AND (p_category_id IS NULL OR tc.id_cat_tolcat = p_category_id)
      AND (p_max_rental_fee IS NULL OR t.rental_fee_tol <= p_max_rental_fee)
      AND NOT EXISTS (
          SELECT 1 FROM borrow_bor b
          WHERE b.id_tol_bor = t.id_tol
            AND b.id_bst_bor IN (
                fn_get_borrow_status_id('requested'),
                fn_get_borrow_status_id('approved'),
                fn_get_borrow_status_id('borrowed')
            )
      )
      AND NOT EXISTS (
          SELECT 1 FROM availability_block_avb avb
          WHERE avb.id_tol_avb = t.id_tol
            AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
      )
    GROUP BY t.id_tol
    ORDER BY rs.avg_rating DESC, t.created_at_tol DESC
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_get_user_borrow_history(
    IN p_account_id INT,
    IN p_role VARCHAR(10),
    IN p_status VARCHAR(30),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN

    DECLARE v_status_id INT DEFAULT NULL;

    IF p_limit IS NULL OR p_limit < 1 THEN SET p_limit = 20; END IF;
    IF p_limit > 100 THEN SET p_limit = 100; END IF;
    IF p_offset IS NULL OR p_offset < 0 THEN SET p_offset = 0; END IF;

    IF p_status IS NOT NULL THEN
        SET v_status_id = fn_get_borrow_status_id(p_status);
    END IF;

    -- Split role=NULL into a UNION ALL of two index-friendly queries (one
    -- hits idx on b.id_acc_bor, the other hits idx on t.id_acc_tol) instead
    -- of a single OR that defeats both indexes. The borrower and lender
    -- predicates are disjoint for a given row because a borrower cannot
    -- borrow their own tool (trigger-enforced).
    IF p_role = 'borrower' THEN
        SELECT
            b.id_bor,
            t.id_tol,
            t.tool_name_tol,
            tim.file_name_tim AS tool_image,
            bst.status_name_bst AS status,
            b.loan_duration_hours_bor,
            b.requested_at_bor,
            b.approved_at_bor,
            b.borrowed_at_bor,
            b.due_at_bor,
            b.returned_at_bor,
            'borrower' AS user_role,
            CONCAT(owner.first_name_acc, ' ', owner.last_name_acc) AS other_party_name
        FROM borrow_bor b
        JOIN tool_tol t ON b.id_tol_bor = t.id_tol
        JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
        JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
        LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
        WHERE b.id_acc_bor = p_account_id
          AND (v_status_id IS NULL OR b.id_bst_bor = v_status_id)
        ORDER BY b.requested_at_bor DESC
        LIMIT p_limit OFFSET p_offset;

    ELSEIF p_role = 'lender' THEN
        SELECT
            b.id_bor,
            t.id_tol,
            t.tool_name_tol,
            tim.file_name_tim AS tool_image,
            bst.status_name_bst AS status,
            b.loan_duration_hours_bor,
            b.requested_at_bor,
            b.approved_at_bor,
            b.borrowed_at_bor,
            b.due_at_bor,
            b.returned_at_bor,
            'lender' AS user_role,
            CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS other_party_name
        FROM borrow_bor b
        JOIN tool_tol t ON b.id_tol_bor = t.id_tol
        JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
        JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
        LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
        WHERE t.id_acc_tol = p_account_id
          AND (v_status_id IS NULL OR b.id_bst_bor = v_status_id)
        ORDER BY b.requested_at_bor DESC
        LIMIT p_limit OFFSET p_offset;

    ELSE
        SELECT * FROM (
            SELECT
                b.id_bor,
                t.id_tol,
                t.tool_name_tol,
                tim.file_name_tim AS tool_image,
                bst.status_name_bst AS status,
                b.loan_duration_hours_bor,
                b.requested_at_bor,
                b.approved_at_bor,
                b.borrowed_at_bor,
                b.due_at_bor,
                b.returned_at_bor,
                'borrower' AS user_role,
                CONCAT(owner.first_name_acc, ' ', owner.last_name_acc) AS other_party_name
            FROM borrow_bor b
            JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
            LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
            WHERE b.id_acc_bor = p_account_id
              AND (v_status_id IS NULL OR b.id_bst_bor = v_status_id)

            UNION ALL

            SELECT
                b.id_bor,
                t.id_tol,
                t.tool_name_tol,
                tim.file_name_tim AS tool_image,
                bst.status_name_bst AS status,
                b.loan_duration_hours_bor,
                b.requested_at_bor,
                b.approved_at_bor,
                b.borrowed_at_bor,
                b.due_at_bor,
                b.returned_at_bor,
                'lender' AS user_role,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS other_party_name
            FROM borrow_bor b
            JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
            LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
            WHERE t.id_acc_tol = p_account_id
              AND (v_status_id IS NULL OR b.id_bst_bor = v_status_id)
        ) AS combined
        ORDER BY requested_at_bor DESC
        LIMIT p_limit OFFSET p_offset;
    END IF;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_clear_read_notifications(
    IN p_account_id INT UNSIGNED,
    OUT p_deleted_count INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION RESIGNAL;

    DELETE FROM notification_ntf
    WHERE id_acc_ntf = p_account_id
      AND is_read_ntf = TRUE;

    SET p_deleted_count = ROW_COUNT();
END$$
DELIMITER ;


-- Warns at 48h and auto-cancels approved borrows after 72h without pickup.
DELIMITER $$
CREATE PROCEDURE sp_process_stale_approved_borrows(
    OUT p_warned INT,
    OUT p_expired INT
)
BEGIN
    DECLARE v_approved_status_id INT;
    DECLARE v_cancelled_status_id INT;
    DECLARE v_request_type_id INT;
    DECLARE v_pending_deposit_status_id INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS tmp_expired_borrows;
        RESIGNAL;
    END;

    SET v_approved_status_id = fn_get_borrow_status_id('approved');
    SET v_cancelled_status_id = fn_get_borrow_status_id('cancelled');
    SET v_request_type_id = fn_get_notification_type_id('request');
    SET v_pending_deposit_status_id = fn_get_deposit_status_id('pending');

    SET p_warned = 0;
    SET p_expired = 0;

    START TRANSACTION;

    -- Send warning notifications for borrows approved 48-72 hours ago
    INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf)
    SELECT
        b.id_acc_bor,
        v_request_type_id,
        CONCAT('Pickup Expiring: ', t.tool_name_tol),
        CONCAT('Your approved borrow of "', t.tool_name_tol,
               '" will expire in 24 hours if pickup is not completed. ',
               'Please complete all pickup steps or the request will be automatically cancelled.'),
        b.id_bor,
        FALSE
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bst_bor = v_approved_status_id
      AND b.approved_at_bor <= NOW() - INTERVAL 48 HOUR
      AND b.approved_at_bor > NOW() - INTERVAL 72 HOUR
      AND NOT EXISTS (
          SELECT 1 FROM notification_ntf n
          WHERE n.id_bor_ntf = b.id_bor
            AND n.title_ntf LIKE 'Pickup Expiring:%'
      );

    SET p_warned = ROW_COUNT();

    -- Collect borrows approved more than 72 hours ago into temp table
    CREATE TEMPORARY TABLE IF NOT EXISTS tmp_expired_borrows (
        borrow_id INT,
        borrower_id INT,
        lender_id INT,
        tool_name VARCHAR(255)
    );

    TRUNCATE TABLE tmp_expired_borrows;

    INSERT INTO tmp_expired_borrows (borrow_id, borrower_id, lender_id, tool_name)
    SELECT b.id_bor, b.id_acc_bor, t.id_acc_tol, t.tool_name_tol
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    WHERE b.id_bst_bor = v_approved_status_id
      AND b.approved_at_bor <= NOW() - INTERVAL 72 HOUR;

    -- Cancel the stale borrows
    UPDATE borrow_bor b
    JOIN tmp_expired_borrows te ON b.id_bor = te.borrow_id
    SET b.id_bst_bor = v_cancelled_status_id,
        b.cancelled_at_bor = NOW(),
        b.notes_text_bor = CONCAT(
            COALESCE(b.notes_text_bor, ''),
            '\n[AUTO-CANCELLED] Pickup not completed within 72 hours of approval.'
        );

    -- Delete pending deposits for cancelled borrows
    DELETE sdp FROM security_deposit_sdp sdp
    JOIN tmp_expired_borrows te ON sdp.id_bor_sdp = te.borrow_id
    WHERE sdp.id_dps_sdp = v_pending_deposit_status_id;

    -- Notify borrowers
    INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf)
    SELECT
        te.borrower_id,
        v_request_type_id,
        'Borrow Request Expired',
        CONCAT('Your approved borrow of "', te.tool_name,
               '" has been automatically cancelled because pickup was not completed within 72 hours.'),
        te.borrow_id,
        FALSE
    FROM tmp_expired_borrows te;

    -- Notify lenders
    INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf)
    SELECT
        te.lender_id,
        v_request_type_id,
        'Borrow Request Expired',
        CONCAT('The approved borrow of "', te.tool_name,
               '" has been automatically cancelled because the borrower did not complete pickup within 72 hours. ',
               'Your tool is now available again.'),
        te.borrow_id,
        FALSE
    FROM tmp_expired_borrows te;

    SET p_expired = (SELECT COUNT(*) FROM tmp_expired_borrows);

    DROP TEMPORARY TABLE IF EXISTS tmp_expired_borrows;

    COMMIT;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_forfeit_deposit(
    IN p_deposit_id INT,
    IN p_amount DECIMAL(8,2),
    IN p_reason TEXT,
    IN p_incident_id INT,
    OUT p_success BOOLEAN,
    OUT p_error_message VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_current_status_id INT;
    DECLARE v_held_status_id INT;
    DECLARE v_forfeited_status_id INT;
    DECLARE v_deposit_amount DECIMAL(8,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 p_error_message = MESSAGE_TEXT;
        SET p_success = FALSE;
        ROLLBACK;
    END;

    SET p_success = FALSE;
    SET p_error_message = NULL;

    SET v_held_status_id = fn_get_deposit_status_id('held');
    SET v_forfeited_status_id = fn_get_deposit_status_id('forfeited');

    START TRANSACTION;

    SELECT id_dps_sdp, amount_sdp
    INTO v_current_status_id, v_deposit_amount
    FROM security_deposit_sdp
    WHERE id_sdp = p_deposit_id
    FOR UPDATE;

    IF v_current_status_id IS NULL THEN
        SET p_error_message = 'Deposit not found';
        ROLLBACK;
        LEAVE proc_block;
    END IF;

    IF v_current_status_id != v_held_status_id THEN
        SET p_error_message = 'Deposit is not in held status';
        ROLLBACK;
        LEAVE proc_block;
    END IF;

    IF p_amount <= 0 OR p_amount > v_deposit_amount THEN
        SET p_error_message = 'Forfeiture amount must be between 0.01 and the deposit amount';
        ROLLBACK;
        LEAVE proc_block;
    END IF;

    UPDATE security_deposit_sdp
    SET id_dps_sdp            = v_forfeited_status_id,
        forfeited_at_sdp      = NOW(),
        forfeited_amount_sdp  = p_amount,
        forfeiture_reason_sdp = p_reason,
        id_irt_sdp            = p_incident_id
    WHERE id_sdp = p_deposit_id;

    SET p_success = TRUE;

    COMMIT;
END$$
DELIMITER ;

-- sp_create_bookmark: idempotent via ON DUPLICATE KEY UPDATE so that
-- trg_bookmark_before_insert's SIGNAL for deleted accounts is not swallowed.
DELIMITER $$
CREATE PROCEDURE sp_create_bookmark(
    IN p_account_id INT UNSIGNED,
    IN p_tool_id    INT UNSIGNED
)
MODIFIES SQL DATA
BEGIN
    INSERT INTO tool_bookmark_acctol (id_acc_acctol, id_tol_acctol)
    VALUES (p_account_id, p_tool_id)
    ON DUPLICATE KEY UPDATE id_acc_acctol = id_acc_acctol;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE sp_delete_bookmark(
    IN p_account_id INT UNSIGNED,
    IN p_tool_id    INT UNSIGNED
)
MODIFIES SQL DATA
BEGIN
    DELETE FROM tool_bookmark_acctol
    WHERE id_acc_acctol = p_account_id
      AND id_tol_acctol = p_tool_id;
END$$
DELIMITER ;

-- ============================================================
-- 8. SCHEDULED EVENTS (DISABLED)
-- ============================================================
-- Defines the recurring-maintenance schedule as documentation.
-- All events are DISABLEd — the underlying sp_* procedures are
-- invoked externally by cron instead of MySQL's event scheduler.
--
-- SET GLOBAL event_scheduler = ON;
-- Requires SUPER/SYSTEM_VARIABLES_ADMIN; not available on every host.
-- All events below are created with DISABLE and scheduled work is driven
-- externally by cron. Re-enable the line above only on environments where
-- the MySQL user has the required privilege.

DELIMITER $$
CREATE EVENT evt_refresh_summaries_hourly
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DISABLE
COMMENT 'Refresh summary tables hourly for dashboard performance'
DO
BEGIN
    CALL sp_refresh_neighborhood_summary();
    CALL sp_refresh_category_summary();
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_refresh_user_reputation_every_4h
ON SCHEDULE EVERY 4 HOUR
STARTS CURRENT_TIMESTAMP
DISABLE
COMMENT 'Refresh user reputation every 4 hours'
DO
BEGIN
    CALL sp_refresh_user_reputation();
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_refresh_tool_statistics_every_2h
ON SCHEDULE EVERY 2 HOUR
STARTS CURRENT_TIMESTAMP
DISABLE
COMMENT 'Refresh tool statistics every 2 hours'
DO
BEGIN
    CALL sp_refresh_tool_statistics();
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_daily_stat_midnight
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURDATE()) + INTERVAL 1 DAY)
DISABLE
COMMENT 'Capture daily platform statistics at midnight'
DO
BEGIN
    CALL sp_refresh_platform_daily_stat();
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_send_overdue_notifications
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURDATE()) + INTERVAL 8 HOUR)
DISABLE
COMMENT 'Send daily overdue notifications to borrowers'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_send_overdue_notifications(v_count);
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_cleanup_expired_handovers
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DISABLE
COMMENT 'Clean up expired handover verification codes'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_cleanup_expired_handover_codes(v_count);
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_archive_old_notifications
ON SCHEDULE EVERY 1 WEEK
STARTS (TIMESTAMP(CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY) + INTERVAL 2 HOUR)
DISABLE
COMMENT 'Archive old read notifications weekly'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_archive_old_notifications(90, v_count);
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_cleanup_search_logs
ON SCHEDULE EVERY 1 WEEK
STARTS (TIMESTAMP(CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY) + INTERVAL 3 HOUR)
DISABLE
COMMENT 'Clean up old search logs weekly'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_cleanup_old_search_logs(30, v_count);
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT evt_expire_stale_approved_borrows
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
ON COMPLETION PRESERVE
DISABLE
COMMENT 'Warn at 48h and auto-cancel approved borrows after 72h without pickup'
DO
BEGIN
    DECLARE v_warned INT;
    DECLARE v_expired INT;
    CALL sp_process_stale_approved_borrows(v_warned, v_expired);
END$$
DELIMITER ;

SET SQL_MODE=@ROUTINE_OLD_SQL_MODE;

-- ============================================================
-- 9. REQUIRED SEED DATA — LOOKUP VALUES + GEOGRAPHY
-- ============================================================
-- Populates enum-style lookup tables (roles, statuses, types,
-- conditions, providers) and the Asheville/Hendersonville ZIP +
-- neighborhood service area. Everything from here through the
-- final COMMIT is wrapped in one transaction so either the whole
-- seed succeeds or nothing is committed.

START TRANSACTION;

INSERT INTO role_rol (role_name_rol) VALUES
    ('member'),
    ('admin'),
    ('super_admin');

INSERT INTO account_status_ast (status_name_ast) VALUES
    ('pending'),
    ('active'),
    ('suspended'),
    ('deleted');

INSERT INTO contact_preference_cpr (preference_name_cpr) VALUES
    ('email'),
    ('phone'),
    ('both'),
    ('app');

INSERT INTO state_sta (state_code_sta, state_name_sta) VALUES
    ('AL', 'Alabama'),
    ('AK', 'Alaska'),
    ('AZ', 'Arizona'),
    ('AR', 'Arkansas'),
    ('CA', 'California'),
    ('CO', 'Colorado'),
    ('CT', 'Connecticut'),
    ('DE', 'Delaware'),
    ('FL', 'Florida'),
    ('GA', 'Georgia'),
    ('HI', 'Hawaii'),
    ('ID', 'Idaho'),
    ('IL', 'Illinois'),
    ('IN', 'Indiana'),
    ('IA', 'Iowa'),
    ('KS', 'Kansas'),
    ('KY', 'Kentucky'),
    ('LA', 'Louisiana'),
    ('ME', 'Maine'),
    ('MD', 'Maryland'),
    ('MA', 'Massachusetts'),
    ('MI', 'Michigan'),
    ('MN', 'Minnesota'),
    ('MS', 'Mississippi'),
    ('MO', 'Missouri'),
    ('MT', 'Montana'),
    ('NE', 'Nebraska'),
    ('NV', 'Nevada'),
    ('NH', 'New Hampshire'),
    ('NJ', 'New Jersey'),
    ('NM', 'New Mexico'),
    ('NY', 'New York'),
    ('NC', 'North Carolina'),
    ('ND', 'North Dakota'),
    ('OH', 'Ohio'),
    ('OK', 'Oklahoma'),
    ('OR', 'Oregon'),
    ('PA', 'Pennsylvania'),
    ('RI', 'Rhode Island'),
    ('SC', 'South Carolina'),
    ('SD', 'South Dakota'),
    ('TN', 'Tennessee'),
    ('TX', 'Texas'),
    ('UT', 'Utah'),
    ('VT', 'Vermont'),
    ('VA', 'Virginia'),
    ('WA', 'Washington'),
    ('WV', 'West Virginia'),
    ('WI', 'Wisconsin'),
    ('WY', 'Wyoming');

INSERT INTO tool_condition_tcd (condition_name_tcd) VALUES
    ('new'),
    ('good'),
    ('fair'),
    ('poor');

INSERT INTO borrow_status_bst (status_name_bst) VALUES
    ('requested'),
    ('approved'),
    ('borrowed'),
    ('returned'),
    ('denied'),
    ('cancelled');

INSERT INTO block_type_btp (type_name_btp) VALUES
    ('admin'),
    ('borrow');

INSERT INTO rating_role_rtr (role_name_rtr) VALUES
    ('lender'),
    ('borrower');

INSERT INTO dispute_status_dst (status_name_dst) VALUES
    ('open'),
    ('resolved'),
    ('dismissed');

INSERT INTO dispute_message_type_dmt (type_name_dmt) VALUES
    ('initial_report'),
    ('response'),
    ('admin_note'),
    ('resolution');

INSERT INTO notification_type_ntt (type_name_ntt) VALUES
    ('request'),
    ('approval'),
    ('due'),
    ('return'),
    ('rating'),
    ('denial'),
    ('role_change'),
    ('welcome');

INSERT INTO waiver_type_wtp (type_name_wtp) VALUES
    ('borrow_waiver'),
    ('condition_acknowledgment'),
    ('liability_release');

INSERT INTO handover_type_hot (type_name_hot) VALUES
    ('pickup'),
    ('return');

INSERT INTO incident_type_ity (type_name_ity) VALUES
    ('damage'),
    ('theft'),
    ('loss'),
    ('injury'),
    ('late_return'),
    ('condition_dispute'),
    ('other');

INSERT INTO deposit_status_dps (status_name_dps) VALUES
    ('pending'),
    ('held'),
    ('released'),
    ('forfeited'),
    ('partial_release');

INSERT INTO payment_provider_ppv (provider_name_ppv, is_active_ppv) VALUES
    ('stripe', 1),
    ('paypal', 0),
    ('manual', 1);

INSERT INTO payment_transaction_type_ptt (type_name_ptt) VALUES
    ('deposit_hold'),
    ('rental_fee'),
    ('deposit_release'),
    ('deposit_forfeit'),
    ('refund');

INSERT INTO fuel_type_ftp (fuel_name_ftp) VALUES
    ('gasoline'),
    ('diesel'),
    ('propane'),
    ('two-stroke mix'),
    ('electric/battery'),
    ('kerosene'),
    ('natural gas');

-- ZIP codes / neighborhoods for the Asheville & Hendersonville service area.
-- Source: USPS / zip-codes.com.

INSERT INTO zip_code_zpc (zip_code_zpc, latitude_zpc, longitude_zpc, location_point_zpc) VALUES
('28704', 35.4623, -82.5758, ST_GeomFromText('POINT(-82.5758 35.4623)', 4326, 'axis-order=long-lat')),
('28711', 35.6179, -82.3212, ST_GeomFromText('POINT(-82.3212 35.6179)', 4326, 'axis-order=long-lat')),
('28715', 35.5126, -82.7142, ST_GeomFromText('POINT(-82.7142 35.5126)', 4326, 'axis-order=long-lat')),
('28728', 35.5498, -82.6501, ST_GeomFromText('POINT(-82.6501 35.5498)', 4326, 'axis-order=long-lat')),
('28778', 35.6281, -82.4063, ST_GeomFromText('POINT(-82.4063 35.6281)', 4326, 'axis-order=long-lat')),
('28787', 35.7304, -82.5145, ST_GeomFromText('POINT(-82.5145 35.7304)', 4326, 'axis-order=long-lat')),
('28801', 35.5901, -82.5582, ST_GeomFromText('POINT(-82.5582 35.5901)', 4326, 'axis-order=long-lat')),
('28803', 35.5320, -82.5227, ST_GeomFromText('POINT(-82.5227 35.5320)', 4326, 'axis-order=long-lat')),
('28804', 35.6482, -82.5543, ST_GeomFromText('POINT(-82.5543 35.6482)', 4326, 'axis-order=long-lat')),
('28805', 35.6213, -82.4792, ST_GeomFromText('POINT(-82.4792 35.6213)', 4326, 'axis-order=long-lat')),
('28806', 35.5716, -82.6216, ST_GeomFromText('POINT(-82.6216 35.5716)', 4326, 'axis-order=long-lat')),
('28724', 35.3340, -82.3854, ST_GeomFromText('POINT(-82.3854 35.3340)', 4326, 'axis-order=long-lat')),
('28726', 35.2810, -82.4193, ST_GeomFromText('POINT(-82.4193 35.2810)', 4326, 'axis-order=long-lat')),
('28727', 35.3940, -82.3409, ST_GeomFromText('POINT(-82.3409 35.3940)', 4326, 'axis-order=long-lat')),
('28729', 35.3200, -82.5900, ST_GeomFromText('POINT(-82.5900 35.3200)', 4326, 'axis-order=long-lat')),
('28731', 35.2876, -82.3887, ST_GeomFromText('POINT(-82.3887 35.2876)', 4326, 'axis-order=long-lat')),
('28732', 35.4460, -82.4659, ST_GeomFromText('POINT(-82.4659 35.4460)', 4326, 'axis-order=long-lat')),
('28735', 35.4793, -82.3482, ST_GeomFromText('POINT(-82.3482 35.4793)', 4326, 'axis-order=long-lat')),
('28739', 35.2526, -82.5333, ST_GeomFromText('POINT(-82.5333 35.2526)', 4326, 'axis-order=long-lat')),
('28742', 35.3824, -82.6410, ST_GeomFromText('POINT(-82.6410 35.3824)', 4326, 'axis-order=long-lat')),
('28758', 35.3693, -82.4924, ST_GeomFromText('POINT(-82.4924 35.3693)', 4326, 'axis-order=long-lat')),
('28759', 35.3740, -82.5979, ST_GeomFromText('POINT(-82.5979 35.3740)', 4326, 'axis-order=long-lat')),
('28784', 35.2254, -82.4296, ST_GeomFromText('POINT(-82.4296 35.2254)', 4326, 'axis-order=long-lat')),
('28790', 35.2600, -82.3925, ST_GeomFromText('POINT(-82.3925 35.2600)', 4326, 'axis-order=long-lat')),
('28791', 35.3630, -82.5111, ST_GeomFromText('POINT(-82.5111 35.3630)', 4326, 'axis-order=long-lat')),
('28792', 35.3895, -82.3809, ST_GeomFromText('POINT(-82.3809 35.3895)', 4326, 'axis-order=long-lat'));

SET @nc_state_id = (SELECT id_sta FROM state_sta WHERE state_code_sta = 'NC');

INSERT INTO neighborhood_nbh (neighborhood_name_nbh, city_name_nbh, id_sta_nbh, latitude_nbh, longitude_nbh, location_point_nbh) VALUES
    ('Albemarle Park', 'Asheville', @nc_state_id, 35.6047, -82.5441, ST_GeomFromText('POINT(-82.5441 35.6047)', 4326, 'axis-order=long-lat')),
    ('Arden', 'Arden', @nc_state_id, 35.4662, -82.5165, ST_GeomFromText('POINT(-82.5165 35.4662)', 4326, 'axis-order=long-lat')),
    ('Beaver Lake', 'Asheville', @nc_state_id, 35.6343, -82.5629, ST_GeomFromText('POINT(-82.5629 35.6343)', 4326, 'axis-order=long-lat')),
    ('Beaverdam Valley', 'Asheville', @nc_state_id, 35.6380, -82.5280, ST_GeomFromText('POINT(-82.5280 35.6380)', 4326, 'axis-order=long-lat')),
    ('Beverly Hills', 'Asheville', @nc_state_id, 35.5843, -82.5034, ST_GeomFromText('POINT(-82.5034 35.5843)', 4326, 'axis-order=long-lat')),
    ('Biltmore Forest', 'Biltmore Forest', @nc_state_id, 35.5350, -82.5300, ST_GeomFromText('POINT(-82.5300 35.5350)', 4326, 'axis-order=long-lat')),
    ('Biltmore Park', 'Asheville', @nc_state_id, 35.4746, -82.5350, ST_GeomFromText('POINT(-82.5350 35.4746)', 4326, 'axis-order=long-lat')),
    ('Biltmore Village', 'Asheville', @nc_state_id, 35.5684, -82.5434, ST_GeomFromText('POINT(-82.5434 35.5684)', 4326, 'axis-order=long-lat')),
    ('Black Mountain', 'Black Mountain', @nc_state_id, 35.6179, -82.3212, ST_GeomFromText('POINT(-82.3212 35.6179)', 4326, 'axis-order=long-lat')),
    ('Candler', 'Candler', @nc_state_id, 35.5365, -82.6929, ST_GeomFromText('POINT(-82.6929 35.5365)', 4326, 'axis-order=long-lat')),
    ('Chestnut Hills', 'Asheville', @nc_state_id, 35.6036, -82.5486, ST_GeomFromText('POINT(-82.5486 35.6036)', 4326, 'axis-order=long-lat')),
    ('Chunn''s Cove', 'Asheville', @nc_state_id, 35.6026, -82.5151, ST_GeomFromText('POINT(-82.5151 35.6026)', 4326, 'axis-order=long-lat')),
    ('Downtown Asheville', 'Asheville', @nc_state_id, 35.5951, -82.5515, ST_GeomFromText('POINT(-82.5515 35.5951)', 4326, 'axis-order=long-lat')),
    ('East Asheville', 'Asheville', @nc_state_id, 35.5873, -82.4920, ST_GeomFromText('POINT(-82.4920 35.5873)', 4326, 'axis-order=long-lat')),
    ('Enka', 'Candler', @nc_state_id, 35.5498, -82.6501, ST_GeomFromText('POINT(-82.6501 35.5498)', 4326, 'axis-order=long-lat')),
    ('Falconhurst', 'Asheville', @nc_state_id, 35.5800, -82.6000, ST_GeomFromText('POINT(-82.6000 35.5800)', 4326, 'axis-order=long-lat')),
    ('Five Points', 'Asheville', @nc_state_id, 35.6030, -82.5530, ST_GeomFromText('POINT(-82.5530 35.6030)', 4326, 'axis-order=long-lat')),
    ('Grace', 'Asheville', @nc_state_id, 35.6240, -82.5540, ST_GeomFromText('POINT(-82.5540 35.6240)', 4326, 'axis-order=long-lat')),
    ('Grove Park', 'Asheville', @nc_state_id, 35.6174, -82.5414, ST_GeomFromText('POINT(-82.5414 35.6174)', 4326, 'axis-order=long-lat')),
    ('Haw Creek', 'Asheville', @nc_state_id, 35.5937, -82.4962, ST_GeomFromText('POINT(-82.4962 35.5937)', 4326, 'axis-order=long-lat')),
    ('Kenilworth', 'Asheville', @nc_state_id, 35.5750, -82.5362, ST_GeomFromText('POINT(-82.5362 35.5750)', 4326, 'axis-order=long-lat')),
    ('Lakeview Park', 'Asheville', @nc_state_id, 35.6396, -82.5621, ST_GeomFromText('POINT(-82.5621 35.6396)', 4326, 'axis-order=long-lat')),
    ('Malvern Hills', 'Asheville', @nc_state_id, 35.5673, -82.6096, ST_GeomFromText('POINT(-82.6096 35.5673)', 4326, 'axis-order=long-lat')),
    ('Montford', 'Asheville', @nc_state_id, 35.6025, -82.5604, ST_GeomFromText('POINT(-82.5604 35.6025)', 4326, 'axis-order=long-lat')),
    ('North Asheville', 'Asheville', @nc_state_id, 35.6150, -82.5571, ST_GeomFromText('POINT(-82.5571 35.6150)', 4326, 'axis-order=long-lat')),
    ('Norwood Park', 'Asheville', @nc_state_id, 35.6156, -82.5525, ST_GeomFromText('POINT(-82.5525 35.6156)', 4326, 'axis-order=long-lat')),
    ('Oakley', 'Asheville', @nc_state_id, 35.5615, -82.4985, ST_GeomFromText('POINT(-82.4985 35.5615)', 4326, 'axis-order=long-lat')),
    ('River Arts District', 'Asheville', @nc_state_id, 35.5685, -82.5650, ST_GeomFromText('POINT(-82.5650 35.5685)', 4326, 'axis-order=long-lat')),
    ('Royal Pines', 'Arden', @nc_state_id, 35.4781, -82.5036, ST_GeomFromText('POINT(-82.5036 35.4781)', 4326, 'axis-order=long-lat')),
    ('Shiloh', 'Asheville', @nc_state_id, 35.5450, -82.5350, ST_GeomFromText('POINT(-82.5350 35.5450)', 4326, 'axis-order=long-lat')),
    ('South Asheville', 'Asheville', @nc_state_id, 35.5578, -82.5210, ST_GeomFromText('POINT(-82.5210 35.5578)', 4326, 'axis-order=long-lat')),
    ('South Slope', 'Asheville', @nc_state_id, 35.5912, -82.5540, ST_GeomFromText('POINT(-82.5540 35.5912)', 4326, 'axis-order=long-lat')),
    ('Sulphur Springs', 'Asheville', @nc_state_id, 35.5704, -82.6223, ST_GeomFromText('POINT(-82.6223 35.5704)', 4326, 'axis-order=long-lat')),
    ('Swannanoa', 'Swannanoa', @nc_state_id, 35.5979, -82.3998, ST_GeomFromText('POINT(-82.3998 35.5979)', 4326, 'axis-order=long-lat')),
    ('Town Mountain', 'Asheville', @nc_state_id, 35.6253, -82.5167, ST_GeomFromText('POINT(-82.5167 35.6253)', 4326, 'axis-order=long-lat')),
    ('Weaverville', 'Weaverville', @nc_state_id, 35.6973, -82.5607, ST_GeomFromText('POINT(-82.5607 35.6973)', 4326, 'axis-order=long-lat')),
    ('West Asheville', 'Asheville', @nc_state_id, 35.5784, -82.5855, ST_GeomFromText('POINT(-82.5855 35.5784)', 4326, 'axis-order=long-lat')),
    ('Woodfin', 'Woodfin', @nc_state_id, 35.6350, -82.5800, ST_GeomFromText('POINT(-82.5800 35.6350)', 4326, 'axis-order=long-lat'));

-- Town Mountain appears in both counties in source data but shares
-- coordinates/ZIP (28804), so it's only included with Buncombe above.

INSERT INTO neighborhood_nbh (neighborhood_name_nbh, city_name_nbh, id_sta_nbh, latitude_nbh, longitude_nbh, location_point_nbh) VALUES
    ('Balfour', 'Hendersonville', @nc_state_id, 35.3484, -82.4715, ST_GeomFromText('POINT(-82.4715 35.3484)', 4326, 'axis-order=long-lat')),
    ('Barker Heights', 'Hendersonville', @nc_state_id, 35.3115, -82.4432, ST_GeomFromText('POINT(-82.4432 35.3115)', 4326, 'axis-order=long-lat')),
    ('Carriage Park', 'Hendersonville', @nc_state_id, 35.3400, -82.4930, ST_GeomFromText('POINT(-82.4930 35.3400)', 4326, 'axis-order=long-lat')),
    ('Champion Hills', 'Hendersonville', @nc_state_id, 35.3014, -82.5204, ST_GeomFromText('POINT(-82.5204 35.3014)', 4326, 'axis-order=long-lat')),
    ('Cummings Cove', 'Hendersonville', @nc_state_id, 35.3086, -82.5661, ST_GeomFromText('POINT(-82.5661 35.3086)', 4326, 'axis-order=long-lat')),
    ('Dana', 'Dana', @nc_state_id, 35.3340, -82.3854, ST_GeomFromText('POINT(-82.3854 35.3340)', 4326, 'axis-order=long-lat')),
    ('Downtown Hendersonville', 'Hendersonville', @nc_state_id, 35.3157, -82.4607, ST_GeomFromText('POINT(-82.4607 35.3157)', 4326, 'axis-order=long-lat')),
    ('Druid Hills', 'Hendersonville', @nc_state_id, 35.3321, -82.4729, ST_GeomFromText('POINT(-82.4729 35.3321)', 4326, 'axis-order=long-lat')),
    ('East Flat Rock', 'East Flat Rock', @nc_state_id, 35.2801, -82.4219, ST_GeomFromText('POINT(-82.4219 35.2801)', 4326, 'axis-order=long-lat')),
    ('Edneyville', 'Edneyville', @nc_state_id, 35.3940, -82.3409, ST_GeomFromText('POINT(-82.3409 35.3940)', 4326, 'axis-order=long-lat')),
    ('Etowah', 'Etowah', @nc_state_id, 35.3200, -82.5900, ST_GeomFromText('POINT(-82.5900 35.3200)', 4326, 'axis-order=long-lat')),
    ('Fifth Avenue West', 'Hendersonville', @nc_state_id, 35.3195, -82.4680, ST_GeomFromText('POINT(-82.4680 35.3195)', 4326, 'axis-order=long-lat')),
    ('Flat Rock', 'Flat Rock', @nc_state_id, 35.2730, -82.4420, ST_GeomFromText('POINT(-82.4420 35.2730)', 4326, 'axis-order=long-lat')),
    ('Fletcher', 'Fletcher', @nc_state_id, 35.4300, -82.5000, ST_GeomFromText('POINT(-82.5000 35.4300)', 4326, 'axis-order=long-lat')),
    ('Fruitland', 'Hendersonville', @nc_state_id, 35.3965, -82.3932, ST_GeomFromText('POINT(-82.3932 35.3965)', 4326, 'axis-order=long-lat')),
    ('Gerton', 'Gerton', @nc_state_id, 35.4793, -82.3482, ST_GeomFromText('POINT(-82.3482 35.4793)', 4326, 'axis-order=long-lat')),
    ('Green River', 'Zirconia', @nc_state_id, 35.2787, -82.3687, ST_GeomFromText('POINT(-82.3687 35.2787)', 4326, 'axis-order=long-lat')),
    ('Haywood Knolls', 'Hendersonville', @nc_state_id, 35.3526, -82.5234, ST_GeomFromText('POINT(-82.5234 35.3526)', 4326, 'axis-order=long-lat')),
    ('Hoopers Creek', 'Fletcher', @nc_state_id, 35.4273, -82.4918, ST_GeomFromText('POINT(-82.4918 35.4273)', 4326, 'axis-order=long-lat')),
    ('Horse Shoe', 'Horse Shoe', @nc_state_id, 35.3432, -82.5565, ST_GeomFromText('POINT(-82.5565 35.3432)', 4326, 'axis-order=long-lat')),
    ('Hyman Heights', 'Hendersonville', @nc_state_id, 35.3235, -82.4632, ST_GeomFromText('POINT(-82.4632 35.3235)', 4326, 'axis-order=long-lat')),
    ('Kenmure', 'Flat Rock', @nc_state_id, 35.2547, -82.4447, ST_GeomFromText('POINT(-82.4447 35.2547)', 4326, 'axis-order=long-lat')),
    ('Laurel Park', 'Laurel Park', @nc_state_id, 35.3150, -82.4950, ST_GeomFromText('POINT(-82.4950 35.3150)', 4326, 'axis-order=long-lat')),
    ('Mills River', 'Mills River', @nc_state_id, 35.3884, -82.5668, ST_GeomFromText('POINT(-82.5668 35.3884)', 4326, 'axis-order=long-lat')),
    ('Mountain Home', 'Hendersonville', @nc_state_id, 35.3693, -82.4924, ST_GeomFromText('POINT(-82.4924 35.3693)', 4326, 'axis-order=long-lat')),
    ('Osceola Lake', 'Hendersonville', @nc_state_id, 35.2954, -82.4765, ST_GeomFromText('POINT(-82.4765 35.2954)', 4326, 'axis-order=long-lat')),
    ('Tuxedo', 'Tuxedo', @nc_state_id, 35.2254, -82.4296, ST_GeomFromText('POINT(-82.4296 35.2254)', 4326, 'axis-order=long-lat')),
    ('Valley Hill', 'Hendersonville', @nc_state_id, 35.2985, -82.4832, ST_GeomFromText('POINT(-82.4832 35.2985)', 4326, 'axis-order=long-lat')),
    ('Zirconia', 'Zirconia', @nc_state_id, 35.2418, -82.4162, ST_GeomFromText('POINT(-82.4162 35.2418)', 4326, 'axis-order=long-lat'));

INSERT INTO neighborhood_zip_nbhzpc (id_nbh_nbhzpc, zip_code_nbhzpc, is_primary_nbhzpc) VALUES
    (2, '28704', 1),      -- Arden (primary)
    (29, '28704', 0),    -- Royal Pines

    (9, '28711', 1),      -- Black Mountain (primary)

    (10, '28715', 1),     -- Candler (primary)

    (15, '28728', 1),     -- Enka (primary)

    (34, '28778', 1),     -- Swannanoa (primary)

    (36, '28787', 1),     -- Weaverville (primary)

    -- 28801 - Downtown Asheville (Buncombe)
    (13, '28801', 1),     -- Downtown Asheville (primary)
    (1, '28801', 0),     -- Albemarle Park
    (11, '28801', 0),    -- Chestnut Hills
    (17, '28801', 0),    -- Five Points
    (24, '28801', 0),    -- Montford
    (28, '28801', 0),    -- River Arts District
    (32, '28801', 0),    -- South Slope

    -- 28803 - South Asheville (Buncombe)
    (31, '28803', 1),     -- South Asheville (primary)
    (6, '28803', 0),     -- Biltmore Forest
    (7, '28803', 0),     -- Biltmore Park
    (8, '28803', 0),     -- Biltmore Village
    (21, '28803', 0),    -- Kenilworth
    (27, '28803', 0),    -- Oakley
    (30, '28803', 0),    -- Shiloh

    -- 28804 - North Asheville (Buncombe)
    (25, '28804', 1),     -- North Asheville (primary)
    (3, '28804', 0),     -- Beaver Lake
    (4, '28804', 0),     -- Beaverdam Valley
    (18, '28804', 0),    -- Grace
    (19, '28804', 0),    -- Grove Park
    (22, '28804', 0),    -- Lakeview Park
    (26, '28804', 0),    -- Norwood Park
    (35, '28804', 0),    -- Town Mountain
    (38, '28804', 0),    -- Woodfin

    -- 28805 - East Asheville (Buncombe)
    (14, '28805', 1),     -- East Asheville (primary)
    (5, '28805', 0),     -- Beverly Hills
    (12, '28805', 0),    -- Chunn's Cove
    (20, '28805', 0),    -- Haw Creek

    -- 28806 - West Asheville (Buncombe)
    (37, '28806', 1),     -- West Asheville (primary)
    (16, '28806', 0),    -- Falconhurst
    (23, '28806', 0),    -- Malvern Hills
    (33, '28806', 0),    -- Sulphur Springs

    (44, '28724', 1),     -- Dana (primary)

    (47, '28726', 1),     -- East Flat Rock (primary)

    (48, '28727', 1),     -- Edneyville (primary)

    (49, '28729', 1),     -- Etowah (primary)

    (51, '28731', 1),     -- Flat Rock (primary)
    (60, '28731', 0),    -- Kenmure

    (52, '28732', 1),     -- Fletcher (primary)
    (57, '28732', 0),    -- Hoopers Creek

    (54, '28735', 1),     -- Gerton (primary)

    (61, '28739', 1),     -- Laurel Park (primary)
    (42, '28739', 0),    -- Champion Hills
    (43, '28739', 0),    -- Cummings Cove
    (50, '28739', 0),    -- Fifth Avenue West
    (64, '28739', 0),    -- Osceola Lake
    (66, '28739', 0),    -- Valley Hill

    (58, '28742', 1),     -- Horse Shoe (primary)

    (63, '28758', 1),     -- Mountain Home (primary)

    (62, '28759', 1),     -- Mills River (primary)

    (65, '28784', 1),     -- Tuxedo (primary)

    (67, '28790', 1),     -- Zirconia (primary)
    (55, '28790', 0),    -- Green River

    (46, '28791', 1),     -- Druid Hills (primary)
    (39, '28791', 0),    -- Balfour
    (41, '28791', 0),    -- Carriage Park
    (56, '28791', 0),    -- Haywood Knolls

    (45, '28792', 1),     -- Downtown Hendersonville (primary)
    (40, '28792', 0),    -- Barker Heights
    (53, '28792', 0),    -- Fruitland
    (59, '28792', 0);    -- Hyman Heights

-- ============================================================
-- 10. SAMPLE DATA (OPTIONAL — COMMENT OUT FOR CLEAN INSTALL)
-- ============================================================
-- Test accounts, tools, borrow scenarios, disputes, events, and
-- activity traces used for development and portfolio demos. Safe
-- to strip without affecting schema correctness.

-- ---- 10a. Lookup-ID session variables (reused by inserts below) ----
SET @member_role         = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'member');
SET @admin_role          = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'admin');
SET @super_admin_role    = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'super_admin');
SET @active_status       = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active');
SET @suspended_status    = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'suspended');
SET @pending_status      = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'pending');
SET @email_pref          = (SELECT id_cpr FROM contact_preference_cpr WHERE preference_name_cpr = 'email');

SET @new_condition       = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'new');
SET @good_condition      = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'good');
SET @fair_condition      = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'fair');
SET @poor_condition      = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'poor');

SET @requested_bst       = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested');
SET @approved_bst        = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved');
SET @borrowed_bst        = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed');
SET @returned_bst        = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned');
SET @denied_bst          = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'denied');
SET @cancelled_bst       = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'cancelled');

SET @admin_block         = (SELECT id_btp FROM block_type_btp WHERE type_name_btp = 'admin');
SET @borrow_block        = (SELECT id_btp FROM block_type_btp WHERE type_name_btp = 'borrow');

SET @lender_rtr          = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender');
SET @borrower_rtr        = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower');

SET @open_dst            = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'open');
SET @resolved_dst        = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'resolved');
SET @dismissed_dst       = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'dismissed');

SET @initial_report_dmt  = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'initial_report');
SET @response_dmt        = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'response');
SET @admin_note_dmt      = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'admin_note');
SET @resolution_dmt      = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'resolution');

SET @request_ntt         = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'request');
SET @approval_ntt        = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'approval');
SET @due_ntt             = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'due');
SET @return_ntt          = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'return');
SET @rating_ntt          = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'rating');
SET @denial_ntt          = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'denial');
SET @role_change_ntt     = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'role_change');
SET @welcome_ntt         = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'welcome');

SET @borrow_waiver_wtp   = (SELECT id_wtp FROM waiver_type_wtp WHERE type_name_wtp = 'borrow_waiver');
SET @condition_ack_wtp   = (SELECT id_wtp FROM waiver_type_wtp WHERE type_name_wtp = 'condition_acknowledgment');
SET @liability_rel_wtp   = (SELECT id_wtp FROM waiver_type_wtp WHERE type_name_wtp = 'liability_release');

SET @pickup_hot          = (SELECT id_hot FROM handover_type_hot WHERE type_name_hot = 'pickup');
SET @return_hot          = (SELECT id_hot FROM handover_type_hot WHERE type_name_hot = 'return');

SET @damage_ity          = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'damage');
SET @theft_ity           = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'theft');
SET @late_return_ity     = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'late_return');
SET @condition_disp_ity  = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'condition_dispute');
SET @loss_ity            = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'loss');
SET @injury_ity          = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'injury');
SET @other_ity           = (SELECT id_ity FROM incident_type_ity WHERE type_name_ity = 'other');

SET @held_dps            = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'held');
SET @released_dps        = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'released');
SET @forfeited_dps       = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'forfeited');
SET @partial_release_dps = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'partial_release');
SET @pending_dps         = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'pending');

SET @stripe_ppv          = (SELECT id_ppv FROM payment_provider_ppv WHERE provider_name_ppv = 'stripe');

SET @deposit_hold_ptt    = (SELECT id_ptt FROM payment_transaction_type_ptt WHERE type_name_ptt = 'deposit_hold');
SET @rental_fee_ptt      = (SELECT id_ptt FROM payment_transaction_type_ptt WHERE type_name_ptt = 'rental_fee');
SET @deposit_release_ptt = (SELECT id_ptt FROM payment_transaction_type_ptt WHERE type_name_ptt = 'deposit_release');
SET @deposit_forfeit_ptt = (SELECT id_ptt FROM payment_transaction_type_ptt WHERE type_name_ptt = 'deposit_forfeit');
SET @refund_ptt          = (SELECT id_ptt FROM payment_transaction_type_ptt WHERE type_name_ptt = 'refund');

SET @pw_hash = '$2y$12$EqSpCwhxEfLgDS6GhoZUAOG.Ohx.hqtCE78NMG/IF744hsSr9BlkG';

-- ============================================================
-- ACCOUNTS #1-8
-- ============================================================

INSERT INTO account_acc (
    first_name_acc, last_name_acc, username_acc, phone_number_acc, email_address_acc,
    street_address_acc, zip_code_acc, id_nbh_acc, password_hash_acc,
    id_rol_acc, id_ast_acc, id_cpr_acc, is_verified_acc, has_consent_acc
) VALUES
    ('Allyson', 'Warren', 'allyson_w', '828-555-0101', 'allyson.warren@example.com',
     '123 Haywood St', '28801', 13, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Jeremiah', 'Lutz', 'jeremiah_l', '828-555-0102', 'jeremiah.lutz@example.com',
     '456 Patton Ave', '28806', 37, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Chantelle', 'Turcotte', 'chantelle_t', '828-555-0103', 'chantelle.turcotte@example.com',
     '789 Carriage Park Dr', '28791', 41, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Alec', 'Fehl', 'alec_f', '828-555-0104', 'alec.fehl@example.com',
     '321 Merrimon Ave', '28804', 25, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Admin', 'User', 'admin', '828-555-9999', 'admin@neighborhoodtools.org',
     NULL, '28801', 13, @pw_hash,
     @admin_role, @active_status, @email_pref, TRUE, TRUE),

    ('Jeremy', 'Warren', 'jeremy_w', '828-555-0001', 'jeremywarren@neighborhoodtools.org',
     '100 Charlotte St', '28804', 19, @pw_hash,
     @super_admin_role, @active_status, @email_pref, TRUE, TRUE),

    ('Pending', 'User', 'pending_u', '828-555-0105', 'pending.user@example.com',
     '47 Hendersonville Rd', '28704', 2, @pw_hash,
     @member_role, @pending_status, @email_pref, FALSE, TRUE),

    ('Marcus', 'Blackwell', 'marcus_b', '828-555-0106', 'marcus.blackwell@example.com',
     '55 Banks Ave', '28801', 32, @pw_hash,
     @member_role, @suspended_status, @email_pref, TRUE, TRUE);

-- ============================================================
-- ACCOUNTS #9-14 — diverse neighborhoods
-- ============================================================

INSERT INTO account_acc (
    first_name_acc, last_name_acc, username_acc, phone_number_acc, email_address_acc,
    street_address_acc, zip_code_acc, id_nbh_acc, password_hash_acc,
    id_rol_acc, id_ast_acc, id_cpr_acc, is_verified_acc, has_consent_acc
) VALUES
    ('Sofia', 'Reyes', 'sofia_r', '828-555-0201', 'sofia.reyes@example.com',
     '42 Mills River Rd', '28759', 62, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('David', 'Chen', 'david_c', '828-555-0202', 'david.chen@example.com',
     '118 Black Mountain Ave', '28711', 9, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Tamara', 'Brooks', 'tamara_b', '828-555-0203', 'tamara.brooks@example.com',
     '305 Swannanoa River Rd', '28778', 34, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Raj', 'Patel', 'raj_p', '828-555-0204', 'raj.patel@example.com',
     '77 Weaverville Hwy', '28787', 36, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Grace', 'Kim', 'grace_k', '828-555-0205', 'grace.kim@example.com',
     '210 Tunnel Rd', '28805', 14, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Leon', 'Baptiste', 'leon_b', '828-555-0206', 'leon.baptiste@example.com',
     '88 Brevard Rd', '28806', 37, @pw_hash,
     @member_role, @active_status, @email_pref, TRUE, TRUE);

-- ============================================================
-- ACCOUNT BIOS
-- ============================================================

INSERT INTO account_bio_abi (id_acc_abi, bio_text_abi) VALUES
    (1, 'Downtown Asheville gardener and DIY enthusiast. Five-star lender with a dozen successful loans — loan extensions welcome with notice. Keep an eye on the events calendar for my spring tool-swap meetups.'),
    (2, 'West Asheville home renovator and mountain biker. Most gear lends free; security deposit only on the ladder and saws. Please return ratings after each loan — it helps the whole neighborhood.'),
    (3, 'Carriage Park resident and apple orchard volunteer. Loppers, shears, and apple picker are always free to borrow during harvest season. Drop by an orchard event — I''ll bring the pole.'),
    (4, 'North Asheville woodworking hobbyist. Pressure washer and router come with a refundable deposit paid through the platform. Waiver required before pickup; handover code on arrival.'),
    (6, 'Platform founder and super admin. Reach me through the dispute center for escalations, and watch the events calendar for quarterly community tool-swap meetups.'),
    (8, 'South Slope resident. Account currently suspended pending review of an open incident report — appeals are routed through support.'),
    (9, 'New to Mills River and still building my reputation. Looking to borrow a few tools before investing. Planning to attend the next community repair café.'),
    (10, 'Licensed plumber in Black Mountain. Five-star lender, zero open disputes. Specialty plumbing tools available with a small refundable deposit and a quick condition-acknowledgment waiver.'),
    (11, 'Swannanoa electrician. Live-circuit tools require a safety waiver and I''ll happily walk new borrowers through proper use at pickup.'),
    (12, 'Weaverville shade-tree mechanic. Jacks and torque wrenches come with refundable deposits released on safe return. Loan extensions fine with 24 hours'' notice.'),
    (13, 'East Asheville transplant, new to the platform. Still browsing tools and events, getting a feel for the neighborhood before lending my own gear.'),
    (14, 'West Asheville woodworker and furniture maker. Belt sanders, router table, and jigs available. Please rate after return — it helps both of us and keeps the community strong.');

-- ============================================================
-- AVATAR VECTORS
-- ============================================================

INSERT INTO avatar_vector_avv (file_name_avv, description_text_avv, is_active_avv, id_acc_avv) VALUES
    ('avt_69a5aa1dc87e72.43974475.svg', 'avatar-1',  TRUE, 6),
    ('avt_69a5aa2890b587.48500241.svg', 'avatar-2',  TRUE, 6),
    ('avt_69a5aa3b71f714.91649271.svg', 'avatar-3',  TRUE, 6),
    ('avt_69a5aa4b413f40.24535579.svg', 'avatar-4',  TRUE, 6),
    ('avt_69a5aa57580628.01118389.svg', 'avatar-5',  TRUE, 6),
    ('avt_69a5aa64b808a2.33386837.svg', 'avatar-6',  TRUE, 6),
    ('avt_69a5aa71241c32.53060215.svg', 'avatar-7',  TRUE, 6),
    ('avt_69a5aaa11a24d4.63259667.svg', 'avatar-8',  TRUE, 6),
    ('avt_69a5aaaf79cf48.16166160.svg', 'avatar-9',  TRUE, 6),
    ('avt_69a5aabb11d825.14185374.svg', 'avatar-10', TRUE, 6),
    ('avt_69a5aac8f2ea43.65112652.svg', 'avatar-11', TRUE, 6),
    ('avt_69a5aad3b632a3.67004938.svg', 'avatar-12', TRUE, 6),
    ('avt_69a5aae6d71878.06811300.svg', 'avatar-13', TRUE, 6),
    ('avt_69a5aaf1882513.09259868.svg', 'avatar-14', TRUE, 6),
    ('avt_69a5aafed5bbf0.78255479.svg', 'avatar-15', TRUE, 6),
    ('avt_69a5ab0b3867b2.21771731.svg', 'avatar-16', TRUE, 6);

UPDATE account_acc SET id_avv_acc = 3  WHERE id_acc = 1;
UPDATE account_acc SET id_avv_acc = 11 WHERE id_acc = 2;
UPDATE account_acc SET id_avv_acc = 1  WHERE id_acc = 3;
UPDATE account_acc SET id_avv_acc = 16 WHERE id_acc = 4;
UPDATE account_acc SET id_avv_acc = 4  WHERE id_acc = 5;
UPDATE account_acc SET id_avv_acc = 12 WHERE id_acc = 6;
UPDATE account_acc SET id_avv_acc = 10 WHERE id_acc = 9;
UPDATE account_acc SET id_avv_acc = 8  WHERE id_acc = 10;
UPDATE account_acc SET id_avv_acc = 5  WHERE id_acc = 11;
UPDATE account_acc SET id_avv_acc = 14 WHERE id_acc = 12;
UPDATE account_acc SET id_avv_acc = 7  WHERE id_acc = 13;
UPDATE account_acc SET id_avv_acc = 2  WHERE id_acc = 14;

-- ============================================================
-- VECTOR IMAGES (Category Icons)
-- ============================================================

INSERT INTO vector_image_vec (file_name_vec, description_text_vec, id_acc_vec) VALUES
    ('vec_69a5a8e9b983b5.40099131.svg', 'Woodworking',          6),
    ('vec_69a5a8f222cd44.04412313.svg', 'Power tools',          6),
    ('vec_69a5a8fd942530.77798160.svg', 'Plumbing',             6),
    ('vec_69a5a9129dda31.67768932.svg', 'Outdoor/Landscaping',  6),
    ('vec_69a5a91a4bdec2.76135786.svg', 'Hand tools',           6),
    ('vec_69a5a921b296e2.24521923.svg', 'Gardening',            6),
    ('vec_69a5a92b9a77b9.09013646.svg', 'Electrical',           6),
    ('vec_69a5a93d7dcce7.58612372.svg', 'Automotive',           6);

-- ============================================================
-- CATEGORIES
-- ============================================================

INSERT INTO category_cat (category_name_cat, id_vec_cat) VALUES
    ('Hand Tools', 5),
    ('Power Tools', 2),
    ('Gardening', 6),
    ('Woodworking', 1),
    ('Automotive', 8),
    ('Plumbing', 3),
    ('Electrical', 7),
    ('Outdoor/Landscaping', 4),
    ('Other', NULL);

-- ============================================================
-- TOOLS #1-3 — Allyson
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol,
    preexisting_conditions_tol, is_insurance_recommended_tol
) VALUES
    ('DeWalt 20V Cordless Drill', 'Powerful cordless drill with two batteries and charger. Great for all drilling and driving tasks.',
     @new_condition, 1, 0.00, 168, TRUE, FALSE, 0.00, 150.00, NULL, FALSE),

    ('Craftsman 16oz Claw Hammer', 'Classic claw hammer, perfect for general use around the house.',
     @good_condition, 1, 0.00, 336, TRUE, FALSE, 0.00, 25.00, NULL, FALSE),

    ('Stihl MS 170 Chainsaw', '16-inch gas chainsaw. Perfect for mountain property cleanup after storms.',
     @good_condition, 1, 5.00, 48, TRUE, TRUE, 150.00, 250.00, 'Minor chain wear - recently sharpened', TRUE);

-- ============================================================
-- TOOLS #4-6 — Jeremiah
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol,
    preexisting_conditions_tol, is_insurance_recommended_tol
) VALUES
    ('Makita Circular Saw', '7-1/4 inch circular saw with laser guide. Very accurate cuts.',
     @good_condition, 2, 2.00, 72, TRUE, TRUE, 50.00, 200.00, NULL, FALSE),

    ('Milwaukee Reciprocating Saw', 'Powerful reciprocating saw for demolition and remodeling work.',
     @fair_condition, 2, 1.50, 72, TRUE, TRUE, 40.00, 180.00, 'Blade guard has minor wear', FALSE),

    ('Werner 24ft Extension Ladder', 'Aluminum extension ladder - perfect for two-story homes and tree trimming.',
     @good_condition, 2, 3.00, 48, TRUE, TRUE, 75.00, 350.00, 'Some scuff marks on feet', TRUE);

-- ============================================================
-- TOOLS #7-9 — Chantelle
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES
    ('Fiskars Loppers', 'Heavy-duty bypass loppers for pruning branches up to 2 inches.',
     @new_condition, 3, 0.00, 168, TRUE),

    ('Corona Hedge Shears', 'Manual hedge shears with cushioned grip. Great for shaping bushes.',
     @good_condition, 3, 0.00, 168, TRUE),

    ('Apple Picking Pole', 'Telescoping apple picker - great for Henderson County orchards!',
     @good_condition, 3, 0.00, 336, TRUE);

-- ============================================================
-- TOOLS #10-11 — Alec
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol
) VALUES
    ('Ryobi Pressure Washer', '2000 PSI electric pressure washer. Great for decks, driveways, and siding.',
     @good_condition, 4, 3.00, 24, TRUE, TRUE, 50.00, 200.00),

    ('Black & Decker Leaf Blower', 'Cordless leaf blower with 2 batteries. Quiet enough for city use.',
     @good_condition, 4, 0.00, 72, TRUE, FALSE, 0.00, 100.00);

-- ============================================================
-- TOOLS #12-13 — poor condition + unavailable
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES (
    'Stanley No. 4 Hand Plane',
    'Vintage Stanley bench plane. Well-worn but still functional for rough work. Blade needs frequent resharpening.',
    @poor_condition, 2, 0.00, 168, TRUE
);

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES (
    'Craftsman 230-Piece Mechanics Tool Set',
    'Complete mechanics tool set with ratchets, sockets, and wrenches in carry case.',
    @good_condition, 1, 0.00, 168, FALSE
);

UPDATE tool_tol SET id_ftp_tol = (SELECT id_ftp FROM fuel_type_ftp WHERE fuel_name_ftp = 'two-stroke mix') WHERE id_tol = 3;

-- ============================================================
-- TOOLS #14-17 — fill Plumbing + Electrical categories
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES
    ('Ridgid Pipe Wrench Set (3-Piece)',
     'Heavy-duty 10/14/18-inch pipe wrenches. Essential for any plumbing job.',
     @good_condition, 10, 0.00, 72, TRUE),

    ('General Wire Toilet Auger',
     '6-foot closet auger with vinyl guard to protect porcelain. Clears tough clogs.',
     @good_condition, 10, 0.00, 48, TRUE);

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES
    ('Klein Tools Non-Contact Voltage Tester',
     'Dual-range voltage detector for AC 12-1000V. Auto-off and pocket clip.',
     @new_condition, 11, 0.00, 168, TRUE),

    ('Irwin Wire Stripping Kit',
     'Self-adjusting wire stripper with built-in cutter. Handles 10-24 AWG.',
     @good_condition, 11, 0.00, 168, TRUE);

-- ============================================================
-- TOOLS #18-19 — Super Admin Jeremy (tests admin-as-lender)
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol
) VALUES
    ('Bosch 12V Impact Driver',
     'Compact impact driver for tight spaces. Comes with two batteries and a bit set.',
     @new_condition, 6, 0.00, 72, TRUE),

    ('Stanley FatMax Tape Measure (25ft)',
     'Heavy-duty 25-foot tape measure with blade armor coating. Magnetic tip.',
     @good_condition, 6, 0.00, 336, TRUE);

-- ============================================================
-- TOOLS #20-21 — 3rd Plumbing + 3rd Electrical
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol
) VALUES
    ('SharkBite PEX Crimping Tool',
     'Professional PEX crimp ring tool for 3/8 to 1-inch fittings. Includes gauge set.',
     @new_condition, 10, 1.00, 48, TRUE, TRUE, 25.00, 75.00),

    ('Klein Fish Tape (100ft)',
     'Steel fish tape for pulling wire through conduit and walls. 100-foot reel with case.',
     @fair_condition, 11, 1.00, 72, TRUE, TRUE, 20.00, 60.00);

-- ============================================================
-- TOOLS #22-24 — Automotive (Raj)
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol
) VALUES
    ('Torin Floor Jack (3-Ton)',
     'Heavy-duty hydraulic floor jack with 3-ton capacity. Quick-lift pump and saddle pad.',
     @good_condition, 12, 2.00, 48, TRUE, TRUE, 40.00, 120.00),

    ('Tekton Torque Wrench Set',
     'Two-piece 1/2-inch and 3/8-inch click torque wrench set with protective cases.',
     @new_condition, 12, 1.00, 72, TRUE, FALSE, 0.00, 90.00),

    ('BlueDriver OBD2 Scanner',
     'Bluetooth OBD2 diagnostic scanner. Works with free app on iOS and Android.',
     @good_condition, 12, 0.00, 168, TRUE, FALSE, 0.00, 100.00);

-- ============================================================
-- TOOLS #25-27 — Woodworking (Leon)
-- ============================================================

INSERT INTO tool_tol (
    tool_name_tol, tool_description_tol, id_tcd_tol, id_acc_tol,
    rental_fee_tol, default_loan_duration_hours_tol, is_available_tol,
    is_deposit_required_tol, default_deposit_amount_tol, estimated_value_tol,
    preexisting_conditions_tol
) VALUES
    ('Makita Belt Sander',
     '3x21-inch variable speed belt sander. Dust bag and 3 grit belts included.',
     @good_condition, 14, 2.00, 72, TRUE, TRUE, 50.00, 150.00, NULL),

    ('Bosch Router Table RA1171',
     'Benchtop router table with aluminum fence and dust port. Router not included.',
     @fair_condition, 14, 3.00, 72, TRUE, TRUE, 75.00, 200.00,
     'Fence micro-adjust knob stiff — works but needs extra force'),

    ('Porter-Cable Dovetail Jig',
     'Half-blind and through dovetail jig for drawers and boxes. Up to 24-inch boards.',
     @new_condition, 14, 0.00, 168, TRUE, FALSE, 0.00, 120.00, NULL);

-- ============================================================
-- TOOL-CATEGORY ASSOCIATIONS
-- ============================================================

INSERT INTO tool_category_tolcat (id_tol_tolcat, id_cat_tolcat) VALUES
    (1, 2),
    (2, 1),
    (3, 8),
    (4, 2),
    (5, 2),
    (6, 1),
    (7, 3),
    (8, 3),
    (9, 3),
    (10, 8),
    (11, 8),
    (12, 4),
    (13, 5),
    (14, 6),
    (15, 6),
    (16, 7),
    (17, 7),
    (18, 2),
    (19, 1),
    (20, 6),
    (21, 7),
    (22, 5),
    (23, 5),
    (24, 5),
    (25, 2),
    (26, 4),
    (27, 4);

-- ============================================================
-- TOOL IMAGES
-- ============================================================

INSERT INTO tool_image_tim (id_tol_tim, file_name_tim, alt_text_tim, is_primary_tim, sort_order_tim) VALUES
    ( 1, 'tool_69ae1c80cd1bc2.67867757.jpg', 'DeWalt 20V Cordless Drill',                    TRUE, 1),
    ( 2, 'tool_69ae1ca0c4c152.21848106.jpg', 'Craftsman 16oz Claw Hammer',                   TRUE, 1),
    ( 3, 'tool_69ae1cbf539b12.86789512.jpg', 'Stihl MS 170 Chainsaw',                        TRUE, 1),
    ( 4, 'tool_69ae1cf1bb8152.75002348.jpg', 'Makita Circular Saw',                          TRUE, 1),
    ( 5, 'tool_69ae1d0a98da25.72564674.jpg', 'Milwaukee Reciprocating Saw',                  TRUE, 1),
    ( 6, 'tool_69ae1d242c92c1.94779517.jpg', 'Werner 24ft Extension Ladder',                 TRUE, 1),
    ( 7, 'tool_69ae1d701b4448.60477916.jpg', 'Fiskars Loppers',                              TRUE, 1),
    ( 8, 'tool_69ae1d95660904.34907040.jpg', 'Corona Hedge Shears',                          TRUE, 1),
    ( 9, 'tool_69ae1dab47a529.31421894.jpg', 'Apple Picking Pole',                           TRUE, 1),
    (10, 'tool_69ae1e0fac0bc6.64556294.jpg', 'Ryobi Pressure Washer',                       TRUE, 1),
    (11, 'tool_69ae1e308b1f06.49602047.jpg', 'Black & Decker Leaf Blower',                   TRUE, 1),
    (12, 'tool_69ae1d408491b0.70639371.jpg', 'Stanley No. 4 Hand Plane',                     TRUE, 1),
    (13, 'tool_69ae1c2fc26af4.13689426.jpg', 'Craftsman 230-Piece Mechanics Tool Set',       TRUE, 1),
    (14, 'tool_69ae1eff3e6bf1.29895774.jpg', 'Ridgid Pipe Wrench Set (3-Piece)',             TRUE, 1),
    (15, 'tool_69ae1f1922aeb7.40584875.jpg', 'General Wire Toilet Auger',                    TRUE, 1),
    (16, 'tool_69ae1f685ae664.27768502.jpg', 'Klein Tools Non-Contact Voltage Tester',       TRUE, 1),
    (17, 'tool_69ae1f81a87d51.00093715.jpg', 'Irwin Wire Stripping Kit',                     TRUE, 1),
    (18, 'tool_69ae1e737b5d51.99451175.jpg', 'Bosch 12V Impact Driver',                      TRUE, 1),
    (19, 'tool_69ae1e9ca87910.73773019.jpg', 'Stanley FatMax Tape Measure (25ft)',            TRUE, 1),
    (20, 'tool_69ae1f35653f19.16159085.jpg', 'SharkBite PEX Crimping Tool',                  TRUE, 1),
    (21, 'tool_69ae1f9a0301b0.54996568.jpg', 'Klein Fish Tape (100ft)',                       TRUE, 1),
    (22, 'tool_69ae1fcdcaf8b5.59106055.jpg', 'Torin Floor Jack (3-Ton)',                     TRUE, 1),
    (23, 'tool_69ae1ff2214d24.70507756.jpg', 'Tekton Torque Wrench Set',                     TRUE, 1),
    (24, 'tool_69ae200e012475.09422724.jpg', 'BlueDriver OBD2 Scanner',                      TRUE, 1),
    (25, 'tool_69ae207ee069a5.58319759.jpg', 'Makita Belt Sander',                           TRUE, 1),
    (26, 'tool_69ae20b9931836.58871132.jpg', 'Bosch Router Table RA1171',                    TRUE, 1),
    (27, 'tool_69ae20f12479a4.00888629.jpg', 'Porter-Cable Dovetail Jig',                    TRUE, 1);

-- ============================================================
-- TERMS OF SERVICE — v1.0 active + v0.9 superseded
-- ============================================================

INSERT INTO terms_of_service_tos (
    version_tos, title_tos, content_tos, summary_tos,
    effective_at_tos, is_active_tos, id_acc_created_by_tos
) VALUES (
    '1.0',
    'NeighborhoodTools Terms of Service',
    'Full terms of service text would go here...\n\n1. Acceptance of Terms\n2. Platform Role\n3. User Responsibilities\n4. Liability Limitations\n5. Dispute Resolution\n6. Privacy Policy\n7. Modifications\n8. Termination',
    'By using NeighborhoodTools, you agree to: (1) be responsible for borrowed tools, (2) report incidents within 48 hours, (3) resolve disputes directly with other users, (4) accept that the platform is a matchmaking service only.',
    '2026-01-01 00:00:00',
    TRUE,
    5
);

INSERT INTO terms_of_service_tos (
    version_tos, title_tos, content_tos, summary_tos,
    effective_at_tos, superseded_at_tos, is_active_tos, id_acc_created_by_tos
) VALUES (
    '0.9',
    'NeighborhoodTools Terms of Service (Beta)',
    'Beta terms of service...\n\n1. Acceptance of Terms\n2. Platform Role\n3. User Responsibilities\n4. Liability Limitations\n5. Privacy Policy',
    'Beta terms: Users agree to responsible tool sharing, incident reporting within 48 hours, and platform liability limitations.',
    '2025-06-01 00:00:00',
    '2025-12-31 23:59:59',
    FALSE,
    5
);

-- ============================================================
-- TOS ACCEPTANCE
-- ============================================================

-- Accounts #4 (Alec) and #13 (Grace) deliberately omitted to test TOS acceptance gate
INSERT INTO tos_acceptance_tac (id_acc_tac, id_tos_tac, ip_address_tac, user_agent_tac) VALUES
    (1,  1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
    (2,  1, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
    (3,  1, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)'),
    (5,  1, '192.168.1.1',   'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
    (6,  1, '192.168.1.1',   'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
    (9,  1, '192.168.1.201', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
    (10, 1, '192.168.1.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
    (11, 1, '192.168.1.203', 'Mozilla/5.0 (Linux; Android 13)'),
    (12, 1, '192.168.1.204', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0)'),
    (14, 1, '192.168.1.206', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0)');

-- ============================================================
-- BORROWS #1-11
-- ============================================================

-- Enable the borrow-seed bypass so historical borrows can be inserted
-- directly in terminal/non-requested states. trg_borrow_before_insert
-- only permits this when @seeding IS NOT NULL.
SET @seeding = 1;

-- #1: RETURNED — Jeremiah borrows Allyson's Drill
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    1, 2, @returned_bst, 72,
    '2026-01-15 10:00:00', '2026-01-15 12:00:00', '2026-01-15 14:00:00', '2026-01-18 14:00:00',
    'Building shelves in my West Asheville workshop', TRUE
);

-- #2: BORROWED — Chantelle borrows Allyson's Hammer
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    2, 3, @borrowed_bst, 168,
    '2026-01-28 09:00:00', '2026-01-28 10:00:00', '2026-01-28 11:00:00',
    'Hanging pictures in new Hendersonville home', TRUE
);

-- #3: REQUESTED — Alec requests Chantelle's Loppers
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    notes_text_bor
) VALUES (
    7, 4, @requested_bst, 48,
    'Spring cleanup on my mountain property'
);

-- #4: APPROVED — Allyson borrows Jeremiah's Ladder
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    6, 1, @approved_bst, 48,
    '2026-02-01 08:00:00', '2026-02-01 09:00:00',
    'Need to clean gutters on my downtown building', TRUE
);

-- #5: DENIED — Alec requests Allyson's Chainsaw during maintenance
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, notes_text_bor
) VALUES (
    3, 4, @denied_bst, 48,
    '2026-02-02 14:00:00',
    'Need to clear storm damage on my North Asheville property'
);

-- #6: CANCELLED — Chantelle requests then cancels Jeremiah's Recip Saw
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, cancelled_at_bor, notes_text_bor
) VALUES (
    5, 3, @cancelled_bst, 72,
    '2026-02-03 09:00:00', '2026-02-03 11:00:00',
    'Remodeling the kitchen — schedule changed, no longer need it'
);

-- #7: OVERDUE — Jeremiah borrows Alec's Pressure Washer
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    10, 2, @borrowed_bst, 24,
    '2026-01-18 08:00:00', '2026-01-18 09:00:00', '2026-01-19 10:00:00',
    'Cleaning the deck and driveway at my West Asheville place', TRUE
);

-- #8: RETURNED — Alec borrows Allyson's Drill
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    1, 4, @returned_bst, 72,
    '2026-01-25 10:00:00', '2026-01-25 11:00:00', '2026-01-26 09:00:00', '2026-01-28 09:00:00',
    'Installing a fence in my North Asheville backyard', TRUE
);

-- #9: RETURNED — Chantelle borrows Jeremiah's Circular Saw
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    4, 3, @returned_bst, 48,
    '2026-01-22 08:00:00', '2026-01-22 10:00:00', '2026-01-23 09:00:00', '2026-01-25 09:00:00',
    'Building garden bed frames for the Hendersonville community garden', TRUE
);

-- #10: RETURNED — Alec borrows Allyson's Chainsaw
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    3, 4, @returned_bst, 48,
    '2026-02-16 08:00:00', '2026-02-16 09:00:00', '2026-02-16 10:00:00', '2026-02-18 10:00:00',
    'Clearing fallen trees after the ice storm on Merrimon Ave', TRUE
);

-- #11: RETURNED — Marcus borrows Jeremiah's Ladder
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    6, 8, @returned_bst, 48,
    '2026-01-10 08:00:00', '2026-01-10 09:00:00', '2026-01-11 10:00:00', '2026-01-13 10:00:00',
    'Cleaning gutters on my South Slope rental property', TRUE
);

-- ============================================================
-- BORROWS #12-20 — expanded scenarios
-- ============================================================

-- #12: REQUESTED — Alec requests David's Pipe Wrench
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, notes_text_bor
) VALUES (
    14, 4, @requested_bst, 72,
    NOW() - INTERVAL 6 HOUR,
    'Replacing shut-off valves under the kitchen sink'
);

-- #13: APPROVED — Jeremiah borrows Raj's Floor Jack (awaiting pickup)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    22, 2, @approved_bst, 48,
    NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 10 HOUR,
    'Need to rotate tires and check brakes on my truck', TRUE
);

-- #14: BORROWED — Allyson borrows Leon's Belt Sander (due soon)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    due_at_bor, notes_text_bor, is_contact_shared_bor
) VALUES (
    25, 1, @borrowed_bst, 72,
    NOW() - INTERVAL 60 HOUR, NOW() - INTERVAL 58 HOUR, NOW() - INTERVAL 48 HOUR,
    NOW() + INTERVAL 24 HOUR,
    'Refinishing a dresser I found at the West Asheville flea market', TRUE
);

-- #15: BORROWED — David borrows Leon's Router Table (overdue)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    due_at_bor, notes_text_bor, is_contact_shared_bor
) VALUES (
    26, 10, @borrowed_bst, 72,
    NOW() - INTERVAL 120 HOUR, NOW() - INTERVAL 118 HOUR, NOW() - INTERVAL 96 HOUR,
    NOW() - INTERVAL 24 HOUR,
    'Building cabinet doors for a kitchen reno in Black Mountain', TRUE
);

-- #16: RETURNED — Leon borrows David's PEX Crimper (unrated — test rating flow)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    20, 14, @returned_bst, 48,
    '2026-02-10 09:00:00', '2026-02-10 10:00:00', '2026-02-10 14:00:00', '2026-02-12 14:00:00',
    'Replumbing the upstairs bathroom in my West Asheville house', TRUE
);

-- #17: RETURNED — Chantelle borrows Raj's Torque Wrench (rated)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    23, 3, @returned_bst, 72,
    '2026-02-08 08:00:00', '2026-02-08 09:00:00', '2026-02-08 14:00:00', '2026-02-10 14:00:00',
    'Torquing lug nuts after changing winter tires on my Honda', TRUE
);

-- #18: CANCELLED — Allyson cancels request for Raj's OBD2 Scanner
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, cancelled_at_bor, notes_text_bor
) VALUES (
    24, 1, @cancelled_bst, 168,
    '2026-02-15 10:00:00', '2026-02-15 14:00:00',
    'Check engine light came on — turns out it was just a loose gas cap'
);

-- #19: DENIED — Raj requests Tamara's Wire Stripping Kit
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, notes_text_bor
) VALUES (
    17, 12, @denied_bst, 168,
    '2026-02-14 09:00:00',
    'Rewiring the detached garage for 240V outlet'
);

-- #20: BORROWED — Alec borrows Tamara's Fish Tape (with active deposit)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    due_at_bor, notes_text_bor, is_contact_shared_bor
) VALUES (
    21, 4, @borrowed_bst, 72,
    NOW() - INTERVAL 36 HOUR, NOW() - INTERVAL 34 HOUR, NOW() - INTERVAL 30 HOUR,
    NOW() + INTERVAL 42 HOUR,
    'Running Ethernet cable through the walls in my North Asheville house', TRUE
);

-- #21: RETURNED — Sofia borrows Chantelle's Hedge Shears (this-month completion)
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    8, 9, @returned_bst, 96,
    NOW() - INTERVAL 168 HOUR, NOW() - INTERVAL 166 HOUR,
    NOW() - INTERVAL 164 HOUR, NOW() - INTERVAL 72 HOUR,
    'Shaping the boxwoods at my new Mills River place — first loan through the platform!', TRUE
);

-- ============================================================
-- AVAILABILITY BLOCKS
-- ============================================================

INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, notes_text_avb
) VALUES (
    3, @admin_block, '2026-02-01 00:00:00', '2026-02-15 23:59:59',
    'Chain replacement and engine tune-up at Asheville Outdoor Supply'
);

INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, id_bor_avb
) VALUES (
    2, @borrow_block, '2026-01-28 11:00:00', '2026-02-04 11:00:00', 2
);

INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, notes_text_avb
) VALUES (
    9, @admin_block, '2026-03-01 00:00:00', '2026-03-31 23:59:59',
    'Owner on vacation through March'
);

INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, id_bor_avb
) VALUES (
    10, @borrow_block, '2026-01-19 10:00:00', '2026-01-20 10:00:00', 7
);

INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, id_bor_avb
) VALUES
    (25, @borrow_block, NOW() - INTERVAL 48 HOUR, NOW() + INTERVAL 24 HOUR, 14),
    (26, @borrow_block, NOW() - INTERVAL 96 HOUR, NOW() - INTERVAL 24 HOUR, 15),
    (21, @borrow_block, NOW() - INTERVAL 30 HOUR, NOW() + INTERVAL 42 HOUR, 20);

-- ============================================================
-- BORROW WAIVERS
-- ============================================================

-- Borrows #1-2
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES
    (1, @borrow_waiver_wtp, 2,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '2026-01-15 13:45:00'),

    (2, @borrow_waiver_wtp, 3,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)', '2026-01-28 10:30:00');

-- Borrow #7: condition_acknowledgment
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES (
    7, @condition_ack_wtp, 2,
    TRUE, NULL,
    TRUE, TRUE,
    TRUE, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '2026-01-19 09:45:00'
);

-- Borrow #8: borrow_waiver (Alec for Drill)
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES (
    8, @borrow_waiver_wtp, 4,
    TRUE,
    TRUE, TRUE,
    TRUE, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-01-26 08:45:00'
);

-- Borrow #9: liability_release (Chantelle for Circular Saw)
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES (
    9, @liability_rel_wtp, 3,
    TRUE,
    TRUE, TRUE,
    TRUE, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)', '2026-01-23 08:45:00'
);

-- Borrow #10: borrow_waiver (Alec for Chainsaw)
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES (
    10, @borrow_waiver_wtp, 4,
    TRUE, 'Minor chain wear - recently sharpened',
    TRUE, TRUE,
    TRUE, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-02-16 09:45:00'
);

-- Borrow #11: borrow_waiver (Marcus for Ladder)
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES (
    11, @borrow_waiver_wtp, 8,
    TRUE, 'Some scuff marks on feet',
    TRUE, TRUE,
    TRUE, '192.168.1.108', 'Mozilla/5.0 (Linux; Android 13)', '2026-01-11 09:45:00'
);

-- Borrows #14, #15, #16, #17, #20 — progressed past approved
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES
    (14, @borrow_waiver_wtp, 1,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
     NOW() - INTERVAL 48 HOUR),

    (15, @borrow_waiver_wtp, 10,
     TRUE, 'Fence micro-adjust knob stiff — works but needs extra force',
     TRUE, TRUE,
     TRUE, '192.168.1.202', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
     NOW() - INTERVAL 96 HOUR),

    (16, @borrow_waiver_wtp, 14,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.206', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0)', '2026-02-10 13:45:00'),

    (17, @liability_rel_wtp, 3,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)', '2026-02-08 13:45:00'),

    (20, @condition_ack_wtp, 4,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
     NOW() - INTERVAL 30 HOUR),

    (21, @borrow_waiver_wtp, 9,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.201', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
     NOW() - INTERVAL 165 HOUR);

-- ============================================================
-- HANDOVER VERIFICATIONS
-- ============================================================

-- Borrows #1-2
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, expires_at_hov, verified_at_hov
) VALUES
    (1, @pickup_hot, 'SEED01',
     1, 2,
     'Drill in excellent condition. Both batteries fully charged. No visible damage.',
     DATE_ADD(NOW(), INTERVAL 24 HOUR), '2026-01-15 14:00:00'),

    (1, @return_hot, 'SEED02',
     2, 1,
     'Drill returned. Minor scratches noted on chuck area. Batteries at ~40% charge.',
     DATE_ADD(NOW(), INTERVAL 24 HOUR), '2026-01-18 14:00:00'),

    (2, @pickup_hot, 'SEED03',
     1, 3,
     'Hammer in good condition. Handle grip intact. No chips or cracks on head.',
     DATE_ADD(NOW(), INTERVAL 24 HOUR), '2026-01-28 11:00:00');

-- Borrow #7: pickup VERIFIED + return UNVERIFIED/EXPIRED
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    7, @pickup_hot, 'SEED07',
    4, 2,
    'Pressure washer in good condition. Hose and nozzles included. Full soap reservoir.',
    '2026-01-19 09:30:00', '2026-01-20 09:30:00', '2026-01-19 10:00:00'
);

INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    7, @return_hot, 'SEED08',
    2, NULL,
    NULL,
    '2026-01-22 08:00:00', '2026-01-23 08:00:00', NULL
);

-- Borrow #4: pickup PENDING (approved, awaiting pickup verification)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, expires_at_hov, verified_at_hov
) VALUES (
    4, @pickup_hot, 'SEED04',
    2, NULL,
    NULL,
    NOW() + INTERVAL 24 HOUR, NULL
);

-- Borrow #8
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    8, @pickup_hot, 'SEED09',
    1, 4,
    'Drill in good condition. Two batteries and charger included.',
    '2026-01-26 08:30:00', '2026-01-27 08:30:00', '2026-01-26 09:00:00'
);

INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    8, @return_hot, 'SEED10',
    4, 1,
    'Drill returned in same condition. Both batteries returned.',
    '2026-01-28 08:30:00', '2026-01-29 08:30:00', '2026-01-28 09:00:00'
);

-- Borrow #9
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    9, @pickup_hot, 'SEED11',
    2, 3,
    'Circular saw with laser guide working. Blade in fair condition.',
    '2026-01-23 08:30:00', '2026-01-24 08:30:00', '2026-01-23 09:00:00'
);

INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    9, @return_hot, 'SEED12',
    3, 2,
    'Saw returned. Blade shows additional wear from garden bed framing cuts.',
    '2026-01-25 08:30:00', '2026-01-26 08:30:00', '2026-01-25 09:00:00'
);

-- Borrow #10
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    10, @pickup_hot, 'SEED13',
    1, 4,
    'Chainsaw serviced — new chain, fresh fuel mix. Pull-start cord intact.',
    '2026-02-16 09:30:00', '2026-02-17 09:30:00', '2026-02-16 10:00:00'
);

INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    10, @return_hot, 'SEED14',
    4, 1,
    'Chainsaw returned. Pull-start cord frayed. Guide bar scored from ice-bound wood.',
    '2026-02-18 09:30:00', '2026-02-19 09:30:00', '2026-02-18 10:00:00'
);

-- Borrow #11
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    11, @pickup_hot, 'SEED15',
    2, 8,
    'Ladder in good condition. Scuff marks on feet as noted. All rungs intact.',
    '2026-01-11 09:30:00', '2026-01-12 09:30:00', '2026-01-11 10:00:00'
);

INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    11, @return_hot, 'SEED16',
    8, 2,
    'Ladder returned. Second rung bent slightly inward.',
    '2026-01-13 09:30:00', '2026-01-14 09:30:00', '2026-01-13 10:00:00'
);

-- Borrow #13: pickup PENDING — awaiting pickup verification (key test scenario)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, expires_at_hov, verified_at_hov
) VALUES (
    13, @pickup_hot, 'SEED17',
    12, NULL,
    NULL,
    NOW() + INTERVAL 24 HOUR, NULL
);

-- Borrow #14: pickup verified (Belt Sander, Allyson from Leon)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    14, @pickup_hot, 'SEED18',
    14, 1,
    'Belt sander in good condition. Dust bag clean. Belts have moderate wear.',
    NOW() - INTERVAL 49 HOUR, NOW() - INTERVAL 25 HOUR, NOW() - INTERVAL 48 HOUR
);

-- Borrow #15: pickup verified (Router Table, David from Leon)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    15, @pickup_hot, 'SEED19',
    14, 10,
    'Router table fence micro-adjust is stiff as noted. Table surface clean, dust port clear.',
    NOW() - INTERVAL 97 HOUR, NOW() - INTERVAL 73 HOUR, NOW() - INTERVAL 96 HOUR
);

-- Borrow #16: pickup + return verified (PEX Crimper, Leon from David)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES
    (16, @pickup_hot, 'SEED20',
     10, 14,
     'PEX crimper and gauge set in new condition. All fittings included.',
     '2026-02-10 13:30:00', '2026-02-11 13:30:00', '2026-02-10 14:00:00'),

    (16, @return_hot, 'SEED21',
     14, 10,
     'Crimper returned in same condition. Gauge set complete.',
     '2026-02-12 13:30:00', '2026-02-13 13:30:00', '2026-02-12 14:00:00');

-- Borrow #17: pickup + return verified (Torque Wrench, Chantelle from Raj)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES
    (17, @pickup_hot, 'SEED22',
     12, 3,
     'Both torque wrenches in cases. Calibration stickers current.',
     '2026-02-08 13:30:00', '2026-02-09 13:30:00', '2026-02-08 14:00:00'),

    (17, @return_hot, 'SEED23',
     3, 12,
     'Torque wrenches returned in cases. No issues.',
     '2026-02-10 13:30:00', '2026-02-11 13:30:00', '2026-02-10 14:00:00');

-- Borrow #20: pickup verified (Fish Tape, Alec from Tamara)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES (
    20, @pickup_hot, 'SEED24',
    11, 4,
    'Fish tape reel complete. Steel tape has some surface rust near the tip — fair condition as listed.',
    NOW() - INTERVAL 31 HOUR, NOW() - INTERVAL 7 HOUR, NOW() - INTERVAL 30 HOUR
);

-- Borrow #21: pickup + return verified (Hedge Shears, Sofia from Chantelle)
INSERT INTO handover_verification_hov (
    id_bor_hov, id_hot_hov, verification_code_hov,
    id_acc_generator_hov, id_acc_verifier_hov,
    condition_notes_hov, generated_at_hov, expires_at_hov, verified_at_hov
) VALUES
    (21, @pickup_hot, 'SEED25',
     3, 9,
     'Hedge shears sharp and clean. Cushioned grip intact.',
     NOW() - INTERVAL 165 HOUR, NOW() - INTERVAL 141 HOUR, NOW() - INTERVAL 164 HOUR),

    (21, @return_hot, 'SEED26',
     9, 3,
     'Shears returned lightly used — borrower cleaned sap off blades and oiled the pivot.',
     NOW() - INTERVAL 73 HOUR, NOW() - INTERVAL 49 HOUR, NOW() - INTERVAL 72 HOUR);

-- ============================================================
-- USER RATINGS
-- ============================================================

-- Borrow #1: Jeremiah ↔ Allyson (Drill) — 5-star
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (2, 1, 1, @lender_rtr, 5, 'Allyson was great! Easy pickup downtown and drill worked perfectly.'),
    (1, 2, 1, @borrower_rtr, 5, 'Jeremiah returned the drill on time and in great shape. Great neighbor!');

-- Borrow #8: Alec ↔ Allyson (Drill) — medium
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (4, 1, 8, @lender_rtr, 4, 'Allyson was friendly and the pickup went smoothly downtown.'),
    (1, 4, 8, @borrower_rtr, 3, 'Alec returned the drill on time but one battery was completely dead.');

-- Borrow #9: Chantelle ↔ Jeremiah (Circular Saw) — medium
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (3, 2, 9, @lender_rtr, 3, 'Jeremiah was helpful but the blade was already dull at pickup.'),
    (2, 3, 9, @borrower_rtr, 4, 'Chantelle was communicative and returned the saw promptly.');

-- Borrow #10: Alec ↔ Allyson (Chainsaw) — low
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (4, 1, 10, @lender_rtr, 2, 'Deposit forfeiture felt excessive for normal wear on a chainsaw.'),
    (1, 4, 10, @borrower_rtr, 1, 'Returned chainsaw with a frayed pull cord and scored guide bar.');

-- Borrow #11: Marcus ↔ Jeremiah (Ladder) — mixed
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (8, 2, 11, @lender_rtr, 4, 'Jeremiah was easy to coordinate with for pickup in West Asheville.'),
    (2, 8, 11, @borrower_rtr, 2, 'Returned ladder with a bent rung. Disappointing.');

-- Borrow #17: Chantelle ↔ Raj (Torque Wrench) — high
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (3, 12, 17, @lender_rtr, 5, 'Raj was super responsive and the wrenches were perfectly calibrated.'),
    (12, 3, 17, @borrower_rtr, 4, 'Chantelle returned the set promptly and in great condition.');

-- Borrow #21: Sofia ↔ Chantelle (Hedge Shears) — Sofia's first-loan warm welcome
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES
    (9, 3, 21, @lender_rtr, 5, 'Chantelle walked me through the waiver step patiently — made my first loan feel easy.'),
    (3, 9, 21, @borrower_rtr, 5, 'Sofia returned the shears cleaner than they went out. Textbook neighbor.');

-- ============================================================
-- TOOL RATINGS
-- ============================================================

INSERT INTO tool_rating_trt (
    id_acc_trt, id_tol_trt, id_bor_trt, score_trt, comment_text_trt
) VALUES
    (2, 1,  1,  5, 'Excellent drill! Made my workshop shelving project a breeze.'),
    (4, 1,  8,  4, 'Great drill, but one of the two batteries barely holds a charge.'),
    (3, 4,  9,  3, 'Laser guide is accurate but the blade was dull out of the box.'),
    (4, 3,  10, 2, 'Chainsaw ran fine but pull cord felt weak the whole time.'),
    (8, 6,  11, 4, 'Solid extension ladder. Reaches the second story easily.'),
    (3, 23, 17, 5, 'Both wrenches click precisely. Great for DIY brake and tire work.'),
    (9, 8,  21, 5, 'Perfect for shaping small boxwoods. Cushioned grip saved my hands on a two-hour trim.');

-- ============================================================
-- INCIDENT REPORTS
-- ============================================================

-- #1: Damage — Allyson reports drill scratches (borrow #1, resolved)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, estimated_damage_amount_irt,
    resolution_notes_irt, resolved_at_irt, id_acc_resolved_by_irt,
    created_at_irt
) VALUES (
    1, 1, @damage_ity,
    'Scratches on DeWalt Drill Chuck',
    'After Jeremiah returned the drill, I noticed several scratches on the drill chuck that were not present before the loan. The scratches don''t affect functionality but reduce the tool''s resale value. See attached photos comparing pickup and return condition.',
    '2026-01-18 15:00:00', 25.00,
    'Both parties agreed the scratches are cosmetic and do not affect drill function. No financial penalty applied. Recommended both users document condition more thoroughly with photos during future handovers.',
    '2026-01-22 11:00:00', 5,
    '2026-01-19 10:30:00'
);

-- #2: Theft — Jeremiah reports Pressure Washer stolen (borrow #7)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    estimated_damage_amount_irt, created_at_irt
) VALUES (
    7, 2, @theft_ity,
    'Pressure Washer Stolen from Property',
    'The Ryobi Pressure Washer was stolen from my back porch in West Asheville overnight. I filed a police report (#AVL-2026-0412). The washer, hose, and all nozzle attachments are missing.',
    '2026-02-03 06:00:00', TRUE,
    200.00, '2026-02-04 08:00:00'
);
SET @incident_theft = LAST_INSERT_ID();

-- #3: Late Return — Alec reports overdue loan (borrow #7)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    created_at_irt
) VALUES (
    7, 4, @late_return_ity,
    'Pressure Washer Not Returned by Due Date',
    'Jeremiah has not returned my Ryobi Pressure Washer. The loan was due January 20th and it is now January 22nd with no communication about a return date.',
    '2026-01-20 10:00:00', TRUE,
    '2026-01-22 09:00:00'
);
SET @incident_late = LAST_INSERT_ID();

-- #4: Condition Dispute — Allyson reports chainsaw damage (borrow #10, resolved)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    estimated_damage_amount_irt,
    resolution_notes_irt, resolved_at_irt, id_acc_resolved_by_irt,
    created_at_irt
) VALUES (
    10, 1, @condition_disp_ity,
    'Chainsaw Pull Cord Frayed and Guide Bar Scored',
    'After Alec returned the chainsaw, the pull-start cord is visibly frayed and the guide bar has deep scoring from cutting ice-bound hardwood. The chainsaw had just been serviced before this loan — new chain and fresh fuel mix. These issues were not present at pickup.',
    '2026-02-18 11:00:00', TRUE,
    175.00,
    'Handover photos confirm cord and bar were in excellent post-service condition at pickup (SEED13). Return photos (SEED14) show clear fraying and scoring. Damage exceeds normal wear for a 48-hour loan. Full deposit forfeiture upheld.',
    '2026-02-18 15:00:00', 5,
    '2026-02-18 12:00:00'
);
SET @incident_condition = LAST_INSERT_ID();

-- #5: Loss — Jeremiah reports ladder accessories missing (borrow #11, open)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    created_at_irt
) VALUES (
    11, 2, @loss_ity,
    'Ladder Leveler Attachment Missing After Return',
    'Marcus returned the Werner Extension Ladder but the adjustable leveler attachment that clips to the base is missing. He says he never saw it but it was definitely attached at pickup — the handover photos show it clearly.',
    '2026-01-13 11:00:00', TRUE,
    '2026-01-13 14:00:00'
);
SET @incident_loss = LAST_INSERT_ID();

-- #6: Injury — Chantelle reports minor cut from circular saw (borrow #9, resolved)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    estimated_damage_amount_irt,
    resolution_notes_irt, resolved_at_irt, id_acc_resolved_by_irt,
    created_at_irt
) VALUES (
    9, 3, @injury_ity,
    'Minor Cut While Using Circular Saw',
    'Got a small cut on my left index finger when a piece of scrap kicked back while cutting garden bed frames. Treated at home with first aid — no stitches needed. Wanted to report for the record.',
    '2026-01-24 14:00:00', TRUE,
    0.00,
    'Borrower confirmed injury was minor and self-treated. No medical bills. Reminded both parties about the safety class on April 5th. No further action required.',
    '2026-01-25 10:00:00', 5,
    '2026-01-24 15:00:00'
);
SET @incident_injury = LAST_INSERT_ID();

-- #7: Other — Alec reports wrong charger returned with drill (borrow #8, resolved)
INSERT INTO incident_report_irt (
    id_bor_irt, id_acc_irt, id_ity_irt, subject_irt, description_irt,
    incident_occurred_at_irt, is_reported_within_deadline_irt,
    resolution_notes_irt, resolved_at_irt, id_acc_resolved_by_irt,
    created_at_irt
) VALUES (
    8, 4, @other_ity,
    'Wrong Battery Charger Returned in Drill Case',
    'I returned the DeWalt drill to Allyson but she messaged me that the charger in the case is a Black & Decker, not the DeWalt one. I must have accidentally swapped them — I have a similar drill at home. I still have the correct charger.',
    '2026-01-28 16:00:00', TRUE,
    'Alec dropped off the correct DeWalt charger and picked up his B&D charger the next morning. Simple mix-up resolved. No damage to either charger.',
    '2026-01-29 12:00:00', 5,
    '2026-01-28 17:00:00'
);
SET @incident_other = LAST_INSERT_ID();

-- ============================================================
-- INCIDENT PHOTOS
-- ============================================================

-- width_iph / height_iph reflect post-autoOrient dimensions. Seed rows
-- whose files are tracked under public/uploads/incidents/ carry real
-- values so a fresh import renders with srcset + per-photo dims out of
-- the box; rows referencing stub filenames (not committed) stay NULL
-- and the backfill scripts skip them as "missing on disk".
INSERT INTO incident_photo_iph (id_irt_iph, file_name_iph, caption_iph, sort_order_iph, width_iph, height_iph) VALUES
    (1, 'drill-scratch-closeup-01.jpg', 'Close-up of scratches on drill chuck', 1, NULL, NULL),
    (1, 'drill-overview-02.jpg', 'Overview of drill showing affected area', 2, NULL, NULL),
    (@incident_theft, 'porch-empty-01.jpg', 'Empty back porch where pressure washer was stored', 1, 2528, 1696),
    (@incident_theft, 'porch-security-cam-02.jpg', 'Security camera still from night of theft', 2, 2528, 1696),
    (@incident_theft, 'police-report-03.jpg', 'Asheville PD report number confirmation', 3, 2528, 1696),
    (@incident_late,  'overdue-screenshot-01.jpg', 'Screenshot of overdue borrow on dashboard', 1, 927, 483),
    (@incident_condition, 'chainsaw-cord-frayed-01.jpg', 'Close-up of frayed pull-start cord', 1, NULL, NULL),
    (@incident_loss, 'ladder-leveler-pickup-01.jpg', 'Handover photo showing leveler attached at pickup', 1, 2528, 1696),
    (@incident_loss, 'ladder-base-return-02.jpg', 'Ladder base at return with leveler missing', 2, 2528, 1696),
    (@incident_injury, 'finger-cut-bandaged-01.jpg', 'Bandaged index finger after first aid', 1, NULL, NULL),
    (@incident_other, 'wrong-charger-01.jpg', 'Black and Decker charger found in DeWalt case', 1, NULL, NULL);

-- ============================================================
-- LOAN EXTENSIONS — Chantelle extends Hammer borrow (#2) twice
-- ============================================================

INSERT INTO loan_extension_lex (
    id_bor_lex, original_due_at_lex, extended_hours_lex, new_due_at_lex,
    reason_lex, id_acc_approved_by_lex
) VALUES (
    2, '2026-02-04 11:00:00', 72, '2026-02-07 11:00:00',
    'Need a few more days to finish hanging pictures and shelves in the new Hendersonville house.',
    1
);

INSERT INTO loan_extension_lex (
    id_bor_lex, original_due_at_lex, extended_hours_lex, new_due_at_lex,
    reason_lex, id_acc_approved_by_lex
) VALUES (
    2, '2026-02-07 11:00:00', 72, '2026-02-10 11:00:00',
    'Contractor pushed the drywall repair to next week — need the hammer through Tuesday.',
    1
);

-- Apply extensions sequentially (trigger requires matching loan_extension_lex record each time)
UPDATE borrow_bor SET due_at_bor = '2026-02-07 11:00:00' WHERE id_bor = 2;
UPDATE borrow_bor SET due_at_bor = '2026-02-10 11:00:00' WHERE id_bor = 2;

-- Borrow #7 overdue: borrowed_at (2026-01-19 10:00:00) + 24h loan_duration
UPDATE borrow_bor SET due_at_bor = '2026-01-20 10:00:00' WHERE id_bor = 7;

-- ============================================================
-- SECURITY DEPOSITS
-- ============================================================

-- Borrow #4: held ($75 for Ladder)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp
) VALUES (
    4, @held_dps, 75.00, @stripe_ppv,
    'pi_test_ladder_deposit_001', '2026-02-01 09:15:00'
);

-- Borrow #7: held ($50 for Pressure Washer)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp
) VALUES (
    7, @held_dps, 50.00, @stripe_ppv,
    'pi_test_washer_deposit_001', '2026-01-18 09:15:00'
);
SET @dep7 = LAST_INSERT_ID();

-- Borrow #9: released ($50 for Circular Saw)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp, released_at_sdp
) VALUES (
    9, @released_dps, 50.00, @stripe_ppv,
    'pi_test_circsaw_deposit_001', '2026-01-22 10:15:00', '2026-01-25 12:00:00'
);
SET @dep9 = LAST_INSERT_ID();

-- Borrow #10: forfeited ($150 for Chainsaw)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp,
    forfeited_at_sdp, forfeited_amount_sdp, forfeiture_reason_sdp, id_irt_sdp
) VALUES (
    10, @forfeited_dps, 150.00, @stripe_ppv,
    'pi_test_chainsaw_deposit_001', '2026-02-16 09:15:00',
    '2026-02-18 16:00:00', 150.00,
    'Broken pull-start cord and scored guide bar confirmed via handover photos',
    @incident_condition
);
SET @dep10 = LAST_INSERT_ID();

-- Borrow #11: partial_release ($75 for Ladder, $25 forfeited)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp,
    released_at_sdp, forfeited_at_sdp, forfeited_amount_sdp, forfeiture_reason_sdp
) VALUES (
    11, @partial_release_dps, 75.00, @stripe_ppv,
    'pi_test_ladder_deposit_002', '2026-01-10 09:15:00',
    '2026-01-14 12:00:00', '2026-01-14 12:00:00', 25.00,
    'Bent second rung on ladder — both parties agreed to $25 deduction for repair'
);
SET @dep11 = LAST_INSERT_ID();

-- Borrow #13: pending ($40 for Floor Jack, awaiting pickup)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp
) VALUES (
    13, @pending_dps, 40.00, @stripe_ppv,
    'pi_test_floorjack_deposit_001', NOW() - INTERVAL 10 HOUR
);
SET @dep13 = LAST_INSERT_ID();

-- Borrow #15: held ($75 for Router Table, overdue)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp
) VALUES (
    15, @held_dps, 75.00, @stripe_ppv,
    'pi_test_router_deposit_001', NOW() - INTERVAL 96 HOUR
);
SET @dep15 = LAST_INSERT_ID();

-- Borrow #16: released ($25 for PEX Crimper, returned)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp, released_at_sdp
) VALUES (
    16, @released_dps, 25.00, @stripe_ppv,
    'pi_test_pex_deposit_001', '2026-02-10 10:15:00', '2026-02-12 16:00:00'
);
SET @dep16 = LAST_INSERT_ID();

-- Borrow #20: held ($20 for Fish Tape, active)
INSERT INTO security_deposit_sdp (
    id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp,
    external_payment_id_sdp, held_at_sdp
) VALUES (
    20, @held_dps, 20.00, @stripe_ppv,
    'pi_test_fishtape_deposit_001', NOW() - INTERVAL 30 HOUR
);
SET @dep20 = LAST_INSERT_ID();

-- ============================================================
-- PAYMENT TRANSACTIONS
-- ============================================================

-- Borrow #4: deposit hold + rental fee
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (1, 4, @stripe_ppv, @deposit_hold_ptt,
     75.00, 'ch_test_ladder_hold_001', 'succeeded',
     1, NULL, '2026-02-01 09:15:00'),
    (NULL, 4, @stripe_ppv, @rental_fee_ptt,
     3.00, 'ch_test_ladder_fee_001', 'succeeded',
     1, 2, '2026-02-01 09:16:00');

-- Borrow #7: deposit hold + rental fee
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep7, 7, @stripe_ppv, @deposit_hold_ptt,
     50.00, 'ch_test_washer_hold_001', 'succeeded',
     2, NULL, '2026-01-18 09:15:00'),
    (NULL, 7, @stripe_ppv, @rental_fee_ptt,
     3.00, 'ch_test_washer_fee_001', 'succeeded',
     2, 4, '2026-01-18 09:16:00');

-- Borrow #9: deposit hold + rental fee + deposit release + refund
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep9, 9, @stripe_ppv, @deposit_hold_ptt,
     50.00, 'ch_test_circsaw_hold_001', 'succeeded',
     3, NULL, '2026-01-22 10:15:00'),
    (NULL, 9, @stripe_ppv, @rental_fee_ptt,
     2.00, 'ch_test_circsaw_fee_001', 'succeeded',
     3, 2, '2026-01-22 10:16:00'),
    (@dep9, 9, @stripe_ppv, @deposit_release_ptt,
     50.00, 'ch_test_circsaw_release_001', 'succeeded',
     NULL, 3, '2026-01-25 12:00:00'),
    (NULL, 9, @stripe_ppv, @refund_ptt,
     2.00, 'ch_test_circsaw_refund_001', 'succeeded',
     2, 3, '2026-01-27 10:00:00');

-- Borrow #10: deposit hold + rental fee + deposit forfeit
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep10, 10, @stripe_ppv, @deposit_hold_ptt,
     150.00, 'ch_test_chainsaw_hold_001', 'succeeded',
     4, NULL, '2026-02-16 09:15:00'),
    (NULL, 10, @stripe_ppv, @rental_fee_ptt,
     5.00, 'ch_test_chainsaw_fee_001', 'succeeded',
     4, 1, '2026-02-16 09:16:00'),
    (@dep10, 10, @stripe_ppv, @deposit_forfeit_ptt,
     150.00, 'ch_test_chainsaw_forfeit_001', 'succeeded',
     NULL, 1, '2026-02-18 16:00:00');

-- Borrow #11: deposit hold + rental fee + partial forfeit + partial release
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep11, 11, @stripe_ppv, @deposit_hold_ptt,
     75.00, 'ch_test_ladder2_hold_001', 'succeeded',
     8, NULL, '2026-01-10 09:15:00'),
    (NULL, 11, @stripe_ppv, @rental_fee_ptt,
     3.00, 'ch_test_ladder2_fee_001', 'succeeded',
     8, 2, '2026-01-10 09:16:00'),
    (@dep11, 11, @stripe_ppv, @deposit_forfeit_ptt,
     25.00, 'ch_test_ladder2_forfeit_001', 'succeeded',
     NULL, 2, '2026-01-14 12:00:00'),
    (@dep11, 11, @stripe_ppv, @deposit_release_ptt,
     50.00, 'ch_test_ladder2_release_001', 'succeeded',
     NULL, 8, '2026-01-14 12:01:00');

-- Borrow #13: deposit hold + rental fee
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep13, 13, @stripe_ppv, @deposit_hold_ptt,
     40.00, 'ch_test_floorjack_hold_001', 'succeeded',
     2, NULL, NOW() - INTERVAL 10 HOUR),
    (NULL, 13, @stripe_ppv, @rental_fee_ptt,
     2.00, 'ch_test_floorjack_fee_001', 'succeeded',
     2, 12, NOW() - INTERVAL 10 HOUR);

-- Borrow #15: deposit hold + rental fee
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep15, 15, @stripe_ppv, @deposit_hold_ptt,
     75.00, 'ch_test_router_hold_001', 'succeeded',
     10, NULL, NOW() - INTERVAL 96 HOUR),
    (NULL, 15, @stripe_ppv, @rental_fee_ptt,
     3.00, 'ch_test_router_fee_001', 'succeeded',
     10, 14, NOW() - INTERVAL 96 HOUR);

-- Borrow #16: deposit hold + rental fee + deposit release
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep16, 16, @stripe_ppv, @deposit_hold_ptt,
     25.00, 'ch_test_pex_hold_001', 'succeeded',
     14, NULL, '2026-02-10 10:15:00'),
    (NULL, 16, @stripe_ppv, @rental_fee_ptt,
     1.00, 'ch_test_pex_fee_001', 'succeeded',
     14, 10, '2026-02-10 10:16:00'),
    (@dep16, 16, @stripe_ppv, @deposit_release_ptt,
     25.00, 'ch_test_pex_release_001', 'succeeded',
     NULL, 14, '2026-02-12 16:00:00');

-- Borrow #20: deposit hold + rental fee
INSERT INTO payment_transaction_ptx (
    id_sdp_ptx, id_bor_ptx, id_ppv_ptx, id_ptt_ptx,
    amount_ptx, external_transaction_id_ptx, external_status_ptx,
    id_acc_from_ptx, id_acc_to_ptx, processed_at_ptx
) VALUES
    (@dep20, 20, @stripe_ppv, @deposit_hold_ptt,
     20.00, 'ch_test_fishtape_hold_001', 'succeeded',
     4, NULL, NOW() - INTERVAL 30 HOUR),
    (NULL, 20, @stripe_ppv, @rental_fee_ptt,
     1.00, 'ch_test_fishtape_fee_001', 'succeeded',
     4, 11, NOW() - INTERVAL 30 HOUR);

-- ============================================================
-- PAYMENT TRANSACTION METADATA
-- ============================================================

INSERT INTO payment_transaction_meta_ptm (id_ptx_ptm, meta_key_ptm, meta_value_ptm) VALUES
    (1, 'stripe_payment_method', 'pm_card_visa'),
    (1, 'stripe_receipt_url', 'https://pay.stripe.com/receipts/test_hold_001'),
    (2, 'stripe_payment_method', 'pm_card_visa'),
    (2, 'stripe_receipt_url', 'https://pay.stripe.com/receipts/test_fee_001');

-- ============================================================
-- DISPUTES
-- ============================================================

-- #1: Open — Drill scratches (borrow #1)
INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp, created_at_dsp
) VALUES (
    1, 1, 'Minor scratches found on DeWalt Drill after return',
    @open_dst, '2026-01-19 10:00:00'
);

-- #2: Resolved — Circular Saw blade wear (borrow #9)
INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp,
    id_acc_resolver_dsp, resolved_at_dsp, created_at_dsp
) VALUES (
    9, 2, 'Circular saw blade damaged during loan',
    @resolved_dst, 5, '2026-01-28 14:00:00', '2026-01-26 10:00:00'
);
SET @dispute2 = LAST_INSERT_ID();

-- #3: Dismissed — Chainsaw deposit forfeiture contested (borrow #10)
INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp,
    id_acc_resolver_dsp, resolved_at_dsp, created_at_dsp
) VALUES (
    10, 4, 'Contesting full deposit forfeiture for chainsaw',
    @dismissed_dst, 5, '2026-02-19 14:00:00', '2026-02-19 08:00:00'
);
SET @dispute3 = LAST_INSERT_ID();

-- ============================================================
-- DISPUTE MESSAGES
-- ============================================================

-- Dispute #1 (3 messages)
INSERT INTO dispute_message_dsm (id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm) VALUES
    (1, 1, @initial_report_dmt,
     'I noticed some scratches on the drill chuck after Jeremiah returned it. These weren''t there before the loan. I have photos from the handover that show the condition before and after.',
     FALSE, '2026-01-19 10:00:00'),

    (1, 2, @response_dmt,
     'The scratches were already there when I picked it up. I was very careful with the drill and only used it for shelving work. Happy to discuss in person at the West Asheville farmer''s market.',
     FALSE, '2026-01-19 14:30:00'),

    (1, 5, @admin_note_dmt,
     'Reviewed handover condition notes from both pickup and return. Pickup notes say "excellent condition" but no photo evidence was uploaded at pickup. Suggesting mediation between both parties.',
     TRUE, '2026-01-20 09:00:00');

-- Dispute #2 (6 messages)
INSERT INTO dispute_message_dsm (
    id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm
) VALUES
    (@dispute2, 2, @initial_report_dmt,
     'The circular saw blade was noticeably more worn when Chantelle returned it. The laser guide alignment also seems off. I noted fair blade condition at pickup but the wear now is beyond what 48 hours of garden bed framing should cause.',
     FALSE, '2026-01-26 10:00:00'),

    (@dispute2, 3, @response_dmt,
     'The blade was already dull when I picked it up — I noted this in my rating. I only made straight cuts in pressure-treated 2x6 lumber for raised bed frames. The laser guide was already misaligned; I assumed that was normal for this model.',
     FALSE, '2026-01-26 15:00:00'),

    (@dispute2, 5, @admin_note_dmt,
     'Reviewed handover condition notes. Pickup note (SEED11) states "blade in fair condition." Return note (SEED12) states "blade shows additional wear." Both parties acknowledge the blade was not new at pickup.',
     TRUE, '2026-01-27 09:00:00'),

    (@dispute2, 2, @response_dmt,
     'Fair condition means usable, not ready for replacement. The wear from two days of cutting is excessive. However, I admit the blade was due for replacement soon regardless.',
     FALSE, '2026-01-27 14:00:00'),

    (@dispute2, 5, @admin_note_dmt,
     'Both parties agree the blade was aging. The borrower used the saw for its intended purpose (cutting lumber). Recommending resolution: no financial penalty. Lender should replace blade as part of normal tool maintenance.',
     TRUE, '2026-01-28 10:00:00'),

    (@dispute2, 5, @resolution_dmt,
     'Resolved: Blade wear is consistent with normal use on an already-fair-condition blade. No deposit forfeiture. Jeremiah will replace the blade as routine maintenance. Both users reminded to document tool condition with photos at each handover.',
     FALSE, '2026-01-28 14:00:00');

-- Dispute #3 (4 messages)
INSERT INTO dispute_message_dsm (
    id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm
) VALUES
    (@dispute3, 4, @initial_report_dmt,
     'I am contesting the full $150 deposit forfeiture on the chainsaw loan. The guide bar scoring happened while cutting ice-bound hardwood — that is normal chainsaw use. The pull cord fraying could have been pre-existing and worsened during normal operation. The pre-existing conditions note only mentioned chain wear, not cord condition.',
     FALSE, '2026-02-19 08:00:00'),

    (@dispute3, 1, @response_dmt,
     'The chainsaw was professionally serviced the week before this loan. The pull cord was brand new and the guide bar was polished. The handover photo at pickup (SEED13) clearly shows pristine condition. Two days later, both components are damaged.',
     FALSE, '2026-02-19 10:00:00'),

    (@dispute3, 5, @admin_note_dmt,
     'Pickup handover (SEED13): "new chain, fresh fuel mix, pull-start cord intact." Return handover (SEED14): "pull-start cord frayed, guide bar scored." Pre-existing conditions disclosure: "Minor chain wear - recently sharpened" — no mention of cord or bar issues. Damage clearly occurred during the loan.',
     TRUE, '2026-02-19 12:00:00'),

    (@dispute3, 5, @resolution_dmt,
     'Dismissed: Handover documentation conclusively shows damage occurred during the 48-hour loan period. The pull cord and guide bar were in excellent post-service condition at pickup. Full deposit forfeiture of $150 upheld per incident resolution.',
     FALSE, '2026-02-19 14:00:00');

-- #4: Open — Late-return dispute (borrow #15, David's router table overdue from Leon)
INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp, created_at_dsp
) VALUES (
    15, 14, 'Router table overdue — borrower unresponsive',
    @open_dst, NOW() - INTERVAL 22 HOUR
);
SET @dispute4 = LAST_INSERT_ID();

INSERT INTO dispute_message_dsm (
    id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm
) VALUES
    (@dispute4, 14, @initial_report_dmt,
     'David has had my Bosch router table for five days now — original due date was 24 hours ago. I sent a reminder two days ago and have not heard back. I need it this weekend for a commission.',
     FALSE, NOW() - INTERVAL 22 HOUR),

    (@dispute4, 10, @response_dmt,
     'Apologies Leon — the kitchen reno ran long and I had the table buried under cabinetry. I will drop it back tonight with a generator rental credit as a goodwill gesture.',
     FALSE, NOW() - INTERVAL 18 HOUR),

    (@dispute4, 5, @admin_note_dmt,
     'Borrower has acknowledged lateness and offered goodwill. Monitoring for actual return before closing the dispute.',
     TRUE, NOW() - INTERVAL 16 HOUR);

-- #5: Resolved — Condition dispute (borrow #16, PEX crimper ratchet)
INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp,
    id_acc_resolver_dsp, resolved_at_dsp, created_at_dsp
) VALUES (
    16, 10, 'PEX crimper ratchet slipping after return',
    @resolved_dst, 5, '2026-02-14 10:00:00', '2026-02-13 09:00:00'
);
SET @dispute5 = LAST_INSERT_ID();

INSERT INTO dispute_message_dsm (
    id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm
) VALUES
    (@dispute5, 10, @initial_report_dmt,
     'The SharkBite crimper ratchet is slipping on 1/2" crimps after Leon returned it. It was calibrated perfectly before the loan. The tool is not safe to lend out again until recalibrated.',
     FALSE, '2026-02-13 09:00:00'),

    (@dispute5, 14, @response_dmt,
     'Only did eight crimps on 1/2" PEX for a bathroom rough-in — nothing abnormal. Happy to split the calibration cost if a shop confirms it needs service.',
     FALSE, '2026-02-13 13:00:00'),

    (@dispute5, 5, @admin_note_dmt,
     'Both parties agree on the scope of use. Proposing a split calibration fee — fair given eight crimps is well inside normal use but calibration drift can happen.',
     TRUE, '2026-02-14 08:00:00'),

    (@dispute5, 5, @resolution_dmt,
     'Resolved: Borrower (Leon) voluntarily covers 50% of the $40 calibration fee ($20). No deposit adjustment. Lender reminded to document calibration state at handover going forward.',
     FALSE, '2026-02-14 10:00:00');

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

-- Welcome notifications — one per account, dated to match registration.
-- Long-standing members have read them; #7 Pending and #13 Grace have not.
INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (6,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Jeremy! Thanks for standing up the platform. Browse tools, host events, and keep an eye on the dispute and incident queues.',
     NULL, TRUE, '2026-01-01 06:10:00', '2026-01-01 06:05:00'),

    (5,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Admin! Your account is provisioned for moderation — review the admin dashboard for pending disputes, incidents, and deposit actions.',
     NULL, TRUE, '2026-01-01 07:15:00', '2026-01-01 07:05:00'),

    (1,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Allyson! Start by listing a tool or browsing your neighbors'' offerings. Rate every loan after return — reputation is what keeps the community running.',
     NULL, TRUE, '2026-01-01 09:30:00', '2026-01-01 08:05:00'),

    (2,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Jeremiah! You can now request loans, list tools, and join community events. Remember: a waiver is required before any power-tool pickup.',
     NULL, TRUE, '2026-01-02 12:00:00', '2026-01-02 09:05:00'),

    (3,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Chantelle! Browse events near you in Henderson County and list any tools you are willing to share. Deposits are optional but recommended for higher-value items.',
     NULL, TRUE, '2026-01-02 16:30:00', '2026-01-02 14:05:00'),

    (4,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Alec! Please accept the Terms of Service from your dashboard so you can start borrowing. Safety waivers are one-click on each request.',
     NULL, TRUE, '2026-01-03 11:00:00', '2026-01-03 08:35:00'),

    (8,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Marcus! Browse tools, meet your South Slope neighbors, and bookmark items you borrow often. Questions? Hit the FAQ.',
     NULL, TRUE, '2026-01-08 13:00:00', '2026-01-08 10:05:00'),

    (9,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Sofia! Mills River is a new neighborhood on the platform — your early ratings will help build reputation for everyone here. Borrow first, list when ready.',
     NULL, TRUE, '2026-02-05 12:45:00', '2026-02-05 09:05:00'),

    (10, @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, David! Your Black Mountain account is verified. Remember to mark professional-grade tools with a deposit so borrowers take care of them.',
     NULL, TRUE, '2026-02-06 14:30:00', '2026-02-06 10:05:00'),

    (11, @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Tamara! Electrical tools can require a liability-release waiver — you can set that per tool from your listing page.',
     NULL, TRUE, '2026-02-07 13:00:00', '2026-02-07 11:05:00'),

    (12, @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Raj! Your Weaverville listings are live. Deposit holds are processed through Stripe and released automatically on return.',
     NULL, TRUE, '2026-02-08 10:00:00', '2026-02-08 08:05:00'),

    (14, @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Leon! Your woodworking gear is a great fit for the West Asheville maker community. Consider hosting an event once you have a few ratings.',
     NULL, TRUE, '2026-02-10 12:15:00', '2026-02-10 10:05:00'),

    (13, @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome, Grace! Please accept the current Terms of Service from your dashboard — you will not be able to borrow or list until you do.',
     NULL, FALSE, NULL, '2026-04-08 14:25:00'),

    (7,  @welcome_ntt, 'Welcome to NeighborhoodTools!',
     'Welcome! Your account is pending email verification. Watch your inbox for a confirmation link from support@neighborhoodtools.org.',
     NULL, FALSE, NULL, '2026-04-12 18:50:00');

INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf) VALUES
    (3, @request_ntt, 'New Borrow Request', 'Alec from North Asheville has requested to borrow your Fiskars Loppers.', 3, FALSE),
    (2, @approval_ntt, 'Request Approved', 'Allyson has approved your request for the DeWalt Drill.', 1, TRUE),
    (3, @approval_ntt, 'Request Approved', 'Allyson has approved your request for the Craftsman Hammer.', 2, TRUE),
    (1, @approval_ntt, 'Request Approved', 'Jeremiah has approved your request for the Werner Extension Ladder.', 4, FALSE);

INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (2, @due_ntt, 'Tool Due Reminder',
     'Your loan of the Ryobi Pressure Washer from Alec is due on January 20th.',
     7, FALSE, NULL, '2026-01-19 18:00:00'),

    (1, @return_ntt, 'Tool Returned',
     'Alec has returned your DeWalt 20V Cordless Drill.',
     8, TRUE, '2026-01-28 09:30:00', '2026-01-28 09:05:00'),

    (2, @return_ntt, 'Tool Returned',
     'Chantelle has returned your Makita Circular Saw.',
     9, FALSE, NULL, '2026-01-25 09:05:00'),

    (1, @rating_ntt, 'You''ve Been Rated',
     'Alec left you a rating for your DeWalt Drill loan.',
     8, FALSE, NULL, '2026-01-29 10:00:00'),

    (2, @rating_ntt, 'You''ve Been Rated',
     'Chantelle left you a rating for your Circular Saw loan.',
     9, TRUE, '2026-01-26 12:00:00', '2026-01-26 10:00:00'),

    (2, @request_ntt, 'New Borrow Request',
     'Marcus from South Slope has requested to borrow your Werner Extension Ladder.',
     11, TRUE, '2026-01-10 09:00:00', '2026-01-10 08:05:00');

-- Allyson (#1) — bring to 15+ total for pagination testing
INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (1, @request_ntt, 'New Borrow Request',
     'Jeremiah from West Asheville has requested to borrow your DeWalt Drill.',
     1, TRUE, '2026-01-15 10:30:00', '2026-01-15 10:05:00'),

    (1, @request_ntt, 'New Borrow Request',
     'Chantelle from Hendersonville has requested to borrow your Craftsman Hammer.',
     2, TRUE, '2026-01-28 09:30:00', '2026-01-28 09:05:00'),

    (1, @request_ntt, 'New Borrow Request',
     'Alec from North Asheville has requested to borrow your Stihl Chainsaw.',
     5, TRUE, '2026-02-02 14:30:00', '2026-02-02 14:05:00'),

    (1, @request_ntt, 'New Borrow Request',
     'Alec from North Asheville has requested to borrow your DeWalt Drill.',
     8, TRUE, '2026-01-25 10:30:00', '2026-01-25 10:05:00'),

    (1, @request_ntt, 'New Borrow Request',
     'Alec from North Asheville has requested to borrow your Stihl Chainsaw.',
     10, TRUE, '2026-02-16 08:30:00', '2026-02-16 08:05:00'),

    (1, @return_ntt, 'Tool Returned',
     'Jeremiah has returned your DeWalt 20V Cordless Drill.',
     1, TRUE, '2026-01-18 14:30:00', '2026-01-18 14:05:00'),

    (1, @return_ntt, 'Tool Returned',
     'Alec has returned your Stihl MS 170 Chainsaw.',
     10, TRUE, '2026-02-18 10:30:00', '2026-02-18 10:05:00'),

    (1, @due_ntt, 'Tool Due Reminder',
     'Chantelle''s loan of your Craftsman Hammer is due on February 7th.',
     2, FALSE, NULL, '2026-02-06 18:00:00'),

    (1, @rating_ntt, 'You''ve Been Rated',
     'Jeremiah left you a rating for your DeWalt Drill loan.',
     1, TRUE, '2026-01-20 11:00:00', '2026-01-19 10:00:00'),

    (1, @rating_ntt, 'You''ve Been Rated',
     'Alec left you a rating for your Stihl Chainsaw loan.',
     10, FALSE, NULL, '2026-02-19 10:00:00'),

    (1, @rating_ntt, 'Rate Your Experience',
     'How was your experience borrowing Jeremiah''s Werner Extension Ladder?',
     4, FALSE, NULL, '2026-02-02 09:00:00'),

    (1, @due_ntt, 'Tool Due Reminder',
     'Your loan of the Werner Extension Ladder from Jeremiah is due soon.',
     4, FALSE, NULL, '2026-02-02 18:00:00');

-- Chantelle (#3) + Alec (#4) — fill gaps in notification types
INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (3, @due_ntt, 'Tool Due Reminder',
     'Your loan of the Craftsman Hammer from Allyson is due on February 7th.',
     2, FALSE, NULL, '2026-02-06 18:00:00'),

    (3, @return_ntt, 'Tool Returned',
     'You returned the Makita Circular Saw to Jeremiah. Thanks for borrowing!',
     9, TRUE, '2026-01-25 10:00:00', '2026-01-25 09:10:00'),

    (4, @request_ntt, 'New Borrow Request',
     'Jeremiah from West Asheville has requested to borrow your Ryobi Pressure Washer.',
     7, TRUE, '2026-01-18 08:30:00', '2026-01-18 08:05:00'),

    (4, @due_ntt, 'Tool Due Reminder',
     'Jeremiah''s loan of your Ryobi Pressure Washer is overdue since January 20th.',
     7, FALSE, NULL, '2026-01-21 08:00:00'),

    (4, @rating_ntt, 'Rate Your Experience',
     'How was your experience borrowing Allyson''s DeWalt Drill?',
     8, TRUE, '2026-01-29 10:00:00', '2026-01-28 10:00:00');

INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (10, @request_ntt, 'New Borrow Request',
     'Alec from North Asheville has requested to borrow your Ridgid Pipe Wrench Set.',
     12, FALSE, NULL, NOW() - INTERVAL 6 HOUR),

    (12, @request_ntt, 'New Borrow Request',
     'Jeremiah from West Asheville has requested to borrow your Torin Floor Jack.',
     13, TRUE, NOW() - INTERVAL 9 HOUR, NOW() - INTERVAL 12 HOUR),

    (2, @approval_ntt, 'Request Approved',
     'Raj has approved your request for the Torin Floor Jack.',
     13, TRUE, NOW() - INTERVAL 9 HOUR, NOW() - INTERVAL 10 HOUR),

    (14, @request_ntt, 'New Borrow Request',
     'Allyson from Downtown Asheville has requested to borrow your Makita Belt Sander.',
     14, TRUE, NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 60 HOUR),

    (14, @request_ntt, 'New Borrow Request',
     'David from Black Mountain has requested to borrow your Bosch Router Table.',
     15, TRUE, NOW() - INTERVAL 100 HOUR, NOW() - INTERVAL 120 HOUR),

    (10, @return_ntt, 'Tool Returned',
     'Leon has returned your SharkBite PEX Crimping Tool.',
     16, TRUE, '2026-02-12 15:00:00', '2026-02-12 14:05:00'),

    (14, @rating_ntt, 'Rate Your Experience',
     'How was your experience borrowing David''s SharkBite PEX Crimping Tool?',
     16, FALSE, NULL, '2026-02-13 10:00:00'),

    (12, @return_ntt, 'Tool Returned',
     'Chantelle has returned your Tekton Torque Wrench Set.',
     17, TRUE, '2026-02-10 15:00:00', '2026-02-10 14:05:00'),

    (11, @request_ntt, 'New Borrow Request',
     'Raj from Weaverville has requested to borrow your Irwin Wire Stripping Kit.',
     19, TRUE, '2026-02-14 10:00:00', '2026-02-14 09:05:00'),

    (11, @request_ntt, 'New Borrow Request',
     'Alec from North Asheville has requested to borrow your Klein Fish Tape.',
     20, TRUE, NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 36 HOUR),

    (4, @approval_ntt, 'Request Approved',
     'Tamara has approved your request for the Klein Fish Tape.',
     20, TRUE, NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 34 HOUR);

-- Denial notifications — emitted to the requester when a lender denies.
INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (4,  @denial_ntt, 'Request Denied',
     'Allyson has denied your request to borrow the Stihl MS 170 Chainsaw. Reason: tool is in for scheduled maintenance.',
     5,  TRUE, '2026-02-02 16:20:00', '2026-02-02 15:05:00'),

    (12, @denial_ntt, 'Request Denied',
     'Tamara has denied your request to borrow the Irwin Wire Stripping Kit. Reason: requested window conflicts with an already-approved loan.',
     19, TRUE, '2026-02-14 13:00:00', '2026-02-14 12:05:00');

-- Role-change notification — Admin (#5) was promoted from member to admin.
INSERT INTO notification_ntf (
    id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf,
    is_read_ntf, read_at_ntf, created_at_ntf
) VALUES
    (5,  @role_change_ntt, 'Your role has been updated',
     'You have been promoted to Admin. You were signed out automatically — please sign back in to pick up your new permissions.',
     NULL, TRUE, '2026-01-15 09:30:00', '2026-01-15 09:00:00');

-- ============================================================
-- BOOKMARKS
-- ============================================================

-- Bookmarks reflect each user's interests from their bio and past borrow history.
-- New users (#7 Pending, #13 Grace) have none or minimal; #8 Marcus (suspended) has none.
INSERT INTO tool_bookmark_acctol (id_acc_acctol, id_tol_acctol) VALUES
    -- Allyson (#1) — gardener/DIY, eyes West Asheville woodworking gear
    (1, 7), (1, 8), (1, 25),

    -- Jeremiah (#2) — renovator/mountain biker, heavy machinery + auto
    (2, 3), (2, 10), (2, 22), (2, 25),

    -- Chantelle (#3) — apple orchard volunteer + Hendersonville home improvement
    (3, 4), (3, 10), (3, 22),

    -- Alec (#4) — North Asheville woodworker
    (4, 4), (4, 5), (4, 25), (4, 26), (4, 27),

    -- Sofia (#9) — new Mills River member, small starter list
    (9, 1), (9, 7), (9, 9),

    -- David (#10) — plumber, interested in pro woodworking gear
    (10, 1), (10, 25),

    -- Tamara (#11) — electrician, borrowing-curious beyond her trade
    (11, 1), (11, 3), (11, 22),

    -- Raj (#12) — mechanic, also woodworking/shop curious
    (12, 1), (12, 4), (12, 25),

    -- Grace (#13) — brand-new transplant, two light bookmarks
    (13, 7), (13, 8),

    -- Leon (#14) — West Asheville woodworker, short focused list
    (14, 1), (14, 3), (14, 6), (14, 10), (14, 22);

-- ============================================================
-- EVENTS
-- ============================================================

-- Event timing (today is 2026-04-15):
--   THIS MONTH bucket: May 5, May 8, May 12, May 14 (within 30 days)
--   UPCOMING bucket:   May 22, June 6 (beyond 30 days)
-- Hosts span admin (#5), super admin (#6), and admin-in-training scenarios.

INSERT INTO event_evt (event_name_evt, event_description_evt, event_address_evt, start_at_evt, end_at_evt, id_nbh_evt, id_acc_evt) VALUES
    ('Apple Orchard Pruning Workshop',
     'Hands-on pruning day at the Carriage Park community orchard. Loppers and apple pickers provided through the platform. Beginners welcome — we will cover cut placement, tool care, and safety waivers before any blades come out.',
     'Carriage Park Community Orchard, 789 Carriage Park Dr, Hendersonville',
     '2026-05-05 09:00:00', '2026-05-05 12:00:00', 41, 5),

    ('Weaverville Auto Repair Clinic',
     'Bring your ride, borrow a torque wrench and floor jack, and learn basic brake and fluid service. Licensed mechanic on site. Refundable deposits required on hydraulic tools.',
     'Weaverville Town Garage, 77 Weaverville Hwy, Weaverville',
     '2026-05-08 13:00:00', '2026-05-08 17:00:00', 36, 5),

    ('West Asheville Woodworking Hour',
     'Drop in with a project and work alongside neighbors. Router table, belt sander, and jigs set up on the patio. Liability waiver required before power-tool use.',
     'Haywood Rd Maker Space, 88 Brevard Rd, Asheville',
     '2026-05-12 18:00:00', '2026-05-12 20:30:00', 37, 5),

    ('Downtown Tool Library Open House',
     'Tour the downtown tool library, meet platform admins, and learn how lending, deposits, and handover codes work. Coffee and demos. Great for new members.',
     'Pack Square Park Pavilion, Downtown Asheville',
     '2026-05-14 10:00:00', '2026-05-14 14:00:00', 13, 6),

    ('Black Mountain Garden Tools Swap',
     'Spring swap-meet for gardening, pruning, and small landscaping tools. Bring what you no longer use and leave with what you need. All swaps logged for rating and bookmarking.',
     'Lake Tomahawk Park, Black Mountain',
     '2026-05-22 10:00:00', '2026-05-22 14:00:00', 9, 5),

    ('Mills River Spring Plant Sale & Tool Share',
     'Seedlings, cuttings, and shared garden tools for the planting season. Borrow a wheelbarrow, tiller, or loppers on the spot via the platform — condition acknowledgment required.',
     'Mills River Town Hall, 42 Mills River Rd, Mills River',
     '2026-06-06 09:00:00', '2026-06-06 13:00:00', 62, 6);

-- ============================================================
-- EVENT METADATA
-- ============================================================

INSERT INTO event_meta_evm (id_evt_evm, meta_key_evm, meta_value_evm) VALUES
    (1, 'max_capacity',  '20'),
    (1, 'contact_email', 'orchard@neighborhoodtools.org'),
    (2, 'max_capacity',  '15'),
    (2, 'contact_email', 'auto@neighborhoodtools.org'),
    (3, 'max_capacity',  '25'),
    (4, 'max_capacity',  '60'),
    (4, 'contact_email', 'events@neighborhoodtools.org'),
    (5, 'max_capacity',  '40'),
    (6, 'max_capacity',  '80'),
    (6, 'contact_email', 'millsriver@neighborhoodtools.org');

-- ============================================================
-- EVENT ATTENDEES
-- ============================================================

-- Attendees: Chantelle/Allyson/Sofia (gardeners) pile into event 1 & 6.
--            Jeremiah/Raj/Alec follow the auto/woodworking events.
--            Event 4 (platform open house) pulls the broadest crowd including new users.
INSERT INTO event_attendee_eya (id_acc_eya, id_evt_eya) VALUES
    (1, 1), (3, 1), (9, 1), (13, 1),
    (2, 2), (4, 2), (12, 2),
    (1, 3), (4, 3), (14, 3),
    (1, 4), (2, 4), (3, 4), (9, 4), (10, 4), (12, 4), (13, 4),
    (1, 5), (3, 5), (9, 5), (10, 5), (11, 5),
    (1, 6), (3, 6), (9, 6), (13, 6);

-- ============================================================
-- NEIGHBORHOOD META
-- ============================================================

INSERT INTO neighborhood_meta_nbm (id_nbh_nbm, meta_key_nbm, meta_value_nbm) VALUES
    (1,  'description',   'Heart of Asheville''s tool-sharing community'),
    (1,  'founded_year',  '2025'),
    (1,  'website',       'https://downtown.neighborhoodtools.org'),
    (4,  'description',   'West AVL makers and DIY community'),
    (4,  'founded_year',  '2025'),
    (8,  'description',   'North Asheville neighborhood tool library'),
    (23, 'description',   'Henderson County tool-sharing network'),
    (23, 'contact_email', 'hendersonville@neighborhoodtools.org');

-- ============================================================
-- ACCOUNT META
-- ============================================================

INSERT INTO account_meta_acm (id_acc_acm, meta_key_acm, meta_value_acm) VALUES
    (1,  'preferred_contact_time', 'weekday evenings'),
    (2,  'skill_level',            'advanced'),
    (4,  'preferred_contact_time', 'weekends only'),
    (10, 'skill_level',            'professional'),
    (10, 'trade_license',          'NC Licensed Plumber #P-28711'),
    (11, 'skill_level',            'professional'),
    (11, 'trade_license',          'NC Licensed Electrician #E-28778'),
    (14, 'skill_level',            'intermediate');

-- ============================================================
-- TOOL META
-- ============================================================

INSERT INTO tool_meta_tlm (id_tol_tlm, meta_key_tlm, meta_value_tlm) VALUES
    (1,  'manual_url',     'https://www.dewalt.com/product/dcd771c2'),
    (3,  'purchase_date',  '2024-06-15'),
    (3,  'manual_url',     'https://www.stihlusa.com/products/chain-saws/homeowner-saws/ms170/'),
    (10, 'serial_number',  'RY142300-A1234'),
    (10, 'purchase_date',  '2025-03-20'),
    (14, 'purchase_date',  '2023-11-01'),
    (18, 'serial_number',  'BOSCH-PS41-2A-09876');

-- ============================================================
-- SEARCH LOGS
-- ============================================================

INSERT INTO search_log_slg (id_acc_slg, id_tol_slg, search_text_slg, ip_address_slg, session_id_slg) VALUES
    (2, 1,    'cordless drill',               '192.168.1.101', 'sess_test_001'),
    (3, 4,    'circular saw woodworking',      '192.168.1.102', 'sess_test_002'),
    (4, 7,    'garden loppers pruning',        '192.168.1.103', 'sess_test_003'),
    (1, NULL, 'pressure washer deck cleaning', '192.168.1.100', 'sess_test_004'),
    (NULL, NULL, 'chainsaw rental asheville',  '192.168.1.200', 'sess_test_005'),
    (2, NULL, 'extension ladder',              '192.168.1.101', 'sess_test_006'),
    (3, NULL, 'hedge trimmer hendersonville',  '192.168.1.102', 'sess_test_007'),
    (NULL, 10, 'power washer rental',          '192.168.1.210', 'sess_test_008'),
    (4, 12,    'hand plane woodworking',       '192.168.1.103', 'sess_test_009'),
    (8, 6,     'extension ladder asheville',   '192.168.1.108', 'sess_test_010'),
    (1, NULL,  'gardening tools hendersonville', '192.168.1.100', 'sess_test_011'),
    (NULL, NULL, 'automotive repair tools',    '192.168.1.215', 'sess_test_012'),
    (10, 14,     'pipe wrench plumbing',      '192.168.1.202', 'sess_test_013'),
    (11, 16,     'voltage tester electrical',  '192.168.1.203', 'sess_test_014'),
    (12, NULL,   'floor jack 3 ton',           '192.168.1.204', 'sess_test_015'),
    (14, 25,     'belt sander woodworking',    '192.168.1.206', 'sess_test_016'),
    (NULL, 22,   'floor jack car repair',      '192.168.1.220', 'sess_test_017'),
    (9,  NULL,   'drill set beginner',         '192.168.1.201', 'sess_test_018'),
    (10, 20,     'pex crimping tool plumbing', '192.168.1.202', 'sess_test_019'),
    (NULL, NULL, 'router table woodworking',   '192.168.1.225', 'sess_test_020');

-- ============================================================
-- AUDIT LOGS
-- ============================================================

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('account_acc', 1, 'INSERT', NULL, '2026-01-01 08:00:00'),
    ('account_acc', 2, 'INSERT', NULL, '2026-01-02 09:00:00'),
    ('tool_tol',    1, 'INSERT', 1,    '2026-01-05 10:00:00'),
    ('borrow_bor',  1, 'INSERT', 2,    '2026-01-15 10:00:00'),
    ('borrow_bor',  1, 'UPDATE', 1,    '2026-01-15 12:00:00');

INSERT INTO audit_log_detail_ald (id_aud_ald, column_name_ald, old_value_ald, new_value_ald) VALUES
    (4, 'id_bst_bor',    NULL,        'requested'),
    (5, 'id_bst_bor',    'requested', 'approved'),
    (5, 'approved_at_bor', NULL,      '2026-01-15 12:00:00');

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud)
VALUES ('tool_tol', 12, 'UPDATE', 2, '2026-02-10 14:00:00');
SET @audit_tool = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud)
VALUES ('account_acc', 5, 'UPDATE', 6, '2026-01-15 08:55:00');
SET @audit_promote = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud)
VALUES ('account_acc', 8, 'UPDATE', 5, '2026-01-15 09:00:00');
SET @audit_suspend = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud)
VALUES ('borrow_bor', 10, 'UPDATE', 4, '2026-02-18 10:00:00');
SET @audit_borrow = LAST_INSERT_ID();

INSERT INTO audit_log_detail_ald (id_aud_ald, column_name_ald, old_value_ald, new_value_ald) VALUES
    (@audit_tool,    'id_tcd_tol',      'fair',      'poor'),
    (@audit_promote, 'id_rol_acc',      'member',    'admin'),
    (@audit_suspend, 'id_ast_acc',      'active',    'suspended'),
    (@audit_borrow,  'id_bst_bor',      'borrowed',  'returned'),
    (@audit_borrow,  'returned_at_bor', NULL,        '2026-02-18 10:00:00');

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('account_acc',  3,  'INSERT', NULL, '2026-01-02 14:00:00'),
    ('account_acc',  4,  'INSERT', NULL, '2026-01-03 08:30:00'),
    ('account_acc',  9,  'INSERT', NULL, '2026-02-05 09:00:00'),
    ('account_acc',  10, 'INSERT', NULL, '2026-02-06 10:00:00'),
    ('account_acc',  11, 'INSERT', NULL, '2026-02-07 11:00:00'),
    ('account_acc',  12, 'INSERT', NULL, '2026-02-08 08:00:00'),
    ('account_acc',  13, 'INSERT', NULL, '2026-04-08 14:20:00'),
    ('account_acc',  14, 'INSERT', NULL, '2026-02-10 10:00:00'),
    ('account_acc',  7,  'INSERT', NULL, '2026-04-12 18:45:00'),
    ('tool_tol',     2,  'INSERT', 1,    '2026-01-05 10:30:00'),
    ('tool_tol',     3,  'INSERT', 1,    '2026-01-05 11:00:00'),
    ('tool_tol',     14, 'INSERT', 10,   '2026-02-11 09:00:00'),
    ('tool_tol',     16, 'INSERT', 11,   '2026-02-12 10:00:00'),
    ('tool_tol',     18, 'INSERT', 6,    '2026-02-13 14:00:00'),
    ('tool_tol',     20, 'INSERT', 10,   '2026-02-11 09:30:00'),
    ('tool_tol',     21, 'INSERT', 11,   '2026-02-12 10:30:00'),
    ('tool_tol',     22, 'INSERT', 12,   '2026-02-13 09:00:00'),
    ('tool_tol',     23, 'INSERT', 12,   '2026-02-13 09:30:00'),
    ('tool_tol',     24, 'INSERT', 12,   '2026-02-13 10:00:00'),
    ('tool_tol',     25, 'INSERT', 14,   '2026-02-14 09:00:00'),
    ('tool_tol',     26, 'INSERT', 14,   '2026-02-14 09:30:00'),
    ('tool_tol',     27, 'INSERT', 14,   '2026-02-14 10:00:00');

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',   4,  'UPDATE', 2,    '2026-02-01 09:00:00');
SET @audit_approve4 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',   5,  'UPDATE', 1,    '2026-02-02 15:00:00');
SET @audit_deny5 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',   6,  'UPDATE', 3,    '2026-02-03 11:00:00');
SET @audit_cancel6 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('tos_acceptance_tac', 9,  'INSERT', 9,  '2026-02-05 09:05:00'),
    ('tos_acceptance_tac', 10, 'INSERT', 10, '2026-02-06 10:05:00');

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('dispute_dsp',        1,  'UPDATE', 5,  '2026-02-15 11:00:00');
SET @audit_dispute_resolve = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('incident_report_irt', 1, 'UPDATE', 5,  '2026-01-22 11:00:00');
SET @audit_incident_resolve = LAST_INSERT_ID();

INSERT INTO audit_log_detail_ald (id_aud_ald, column_name_ald, old_value_ald, new_value_ald) VALUES
    (@audit_approve4,         'id_bst_bor',       'requested',  'approved'),
    (@audit_approve4,         'approved_at_bor',   NULL,         '2026-02-01 09:00:00'),
    (@audit_deny5,            'id_bst_bor',       'requested',  'denied'),
    (@audit_cancel6,          'id_bst_bor',       'requested',  'cancelled'),
    (@audit_cancel6,          'cancelled_at_bor',  NULL,         '2026-02-03 11:00:00'),
    (@audit_dispute_resolve,  'id_dst_dsp',       'open',       'resolved'),
    (@audit_dispute_resolve,  'resolved_at_dsp',   NULL,         '2026-02-15 11:00:00'),
    (@audit_incident_resolve, 'resolved_at_irt',   NULL,         '2026-01-22 11:00:00'),
    (@audit_incident_resolve, 'id_acc_resolved_by_irt', NULL,    '5');

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',  13, 'UPDATE', 12,   '2026-02-20 10:00:00');
SET @audit_approve13 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',  16, 'UPDATE', 10,   '2026-02-12 14:00:00');
SET @audit_return16 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',  17, 'UPDATE', 12,   '2026-02-10 14:00:00');
SET @audit_return17 = LAST_INSERT_ID();

INSERT INTO audit_log_aud (table_name_aud, row_id_aud, action_aud, id_acc_aud, created_at_aud) VALUES
    ('borrow_bor',  19, 'UPDATE', 11,   '2026-02-14 12:00:00');
SET @audit_deny19 = LAST_INSERT_ID();

INSERT INTO audit_log_detail_ald (id_aud_ald, column_name_ald, old_value_ald, new_value_ald) VALUES
    (@audit_approve13, 'id_bst_bor',    'requested', 'approved'),
    (@audit_return16,  'id_bst_bor',    'borrowed',  'returned'),
    (@audit_return16,  'returned_at_bor', NULL,       '2026-02-12 14:00:00'),
    (@audit_return17,  'id_bst_bor',    'borrowed',  'returned'),
    (@audit_return17,  'returned_at_bor', NULL,       '2026-02-10 14:00:00'),
    (@audit_deny19,    'id_bst_bor',    'requested', 'denied');

-- ============================================================
-- PASSWORD RESET TOKENS
-- ============================================================

INSERT INTO password_reset_pwr (id_acc_pwr, token_hash_pwr, expires_at_pwr, used_at_pwr) VALUES
    (9,  SHA2('test_reset_token_valid',   256), NOW() + INTERVAL 1 HOUR, NULL),
    (10, SHA2('test_reset_token_expired', 256), NOW() - INTERVAL 2 HOUR, NULL),
    (1,  SHA2('test_reset_token_used',    256), NOW() + INTERVAL 1 HOUR, NOW() - INTERVAL 30 MINUTE);

-- ============================================================
-- PLATFORM DAILY STATS — 30 days of history
-- ============================================================
-- Today's row is populated by sp_refresh_all_summaries() below.
-- Dates use CURDATE() offsets so the data stays relative.
--
-- Columns: stat_date, total_accounts, active_accounts, new_accounts_today,
--          total_tools, available_tools, new_tools_today,
--          active_borrows, completed_today, new_requests_today,
--          open_disputes, open_incidents, overdue_borrows,
--          deposits_held_total

INSERT INTO platform_daily_stat_pds (
    stat_date_pds,
    total_accounts_pds, active_accounts_pds, new_accounts_today_pds,
    total_tools_pds, available_tools_pds, new_tools_today_pds,
    active_borrows_pds, completed_today_pds, new_requests_today_pds,
    open_disputes_pds, open_incidents_pds, overdue_borrows_pds,
    deposits_held_total_pds
) VALUES
    (CURDATE() - INTERVAL 30 DAY,  8,  6, 0,  13, 11, 0,  2, 0, 0,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 29 DAY,  8,  6, 0,  13, 11, 0,  2, 0, 1,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 28 DAY,  8,  6, 0,  13, 11, 0,  2, 1, 1,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 27 DAY,  8,  6, 0,  13, 11, 0,  2, 0, 0,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 26 DAY,  9,  7, 1,  13, 11, 0,  2, 0, 0,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 25 DAY, 10,  8, 1,  15, 13, 2,  2, 0, 1,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 24 DAY, 10,  8, 0,  15, 13, 0,  3, 0, 1,  0, 0, 0, 175.00),
    (CURDATE() - INTERVAL 23 DAY, 11,  9, 1,  18, 16, 3,  3, 0, 0,  0, 0, 0, 175.00),
    (CURDATE() - INTERVAL 22 DAY, 11,  9, 0,  19, 17, 1,  3, 1, 0,  0, 0, 0, 175.00),
    (CURDATE() - INTERVAL 21 DAY, 12, 10, 1,  19, 17, 0,  2, 0, 2,  0, 0, 0, 125.00),
    (CURDATE() - INTERVAL 20 DAY, 12, 10, 0,  22, 20, 3,  3, 0, 1,  0, 1, 0, 175.00),
    (CURDATE() - INTERVAL 19 DAY, 12, 10, 0,  22, 20, 0,  3, 1, 0,  0, 1, 0, 175.00),
    (CURDATE() - INTERVAL 18 DAY, 13, 11, 1,  22, 20, 0,  2, 0, 0,  1, 1, 0, 125.00),
    (CURDATE() - INTERVAL 17 DAY, 13, 11, 0,  22, 20, 0,  3, 0, 1,  1, 2, 1, 175.00),
    (CURDATE() - INTERVAL 16 DAY, 14, 12, 1,  25, 22, 3,  3, 0, 1,  1, 2, 1, 220.00),
    (CURDATE() - INTERVAL 15 DAY, 14, 12, 0,  25, 22, 0,  4, 1, 0,  1, 2, 1, 220.00),
    (CURDATE() - INTERVAL 14 DAY, 14, 12, 0,  25, 22, 0,  3, 0, 0,  1, 3, 1, 175.00),
    (CURDATE() - INTERVAL 13 DAY, 14, 12, 0,  25, 22, 0,  3, 0, 1,  2, 3, 1, 175.00),
    (CURDATE() - INTERVAL 12 DAY, 14, 12, 0,  27, 24, 2,  4, 0, 1,  2, 3, 1, 220.00),
    (CURDATE() - INTERVAL 11 DAY, 14, 12, 0,  27, 24, 0,  4, 1, 0,  2, 4, 1, 220.00),
    (CURDATE() - INTERVAL 10 DAY, 14, 12, 0,  27, 24, 0,  3, 0, 1,  3, 4, 1, 175.00),
    (CURDATE() - INTERVAL  9 DAY, 14, 12, 0,  27, 24, 0,  3, 0, 0,  3, 4, 2, 175.00),
    (CURDATE() - INTERVAL  8 DAY, 14, 12, 0,  27, 23, 0,  3, 1, 1,  2, 4, 2, 175.00),
    (CURDATE() - INTERVAL  7 DAY, 14, 12, 0,  27, 23, 0,  4, 0, 1,  2, 3, 2, 220.00),
    (CURDATE() - INTERVAL  6 DAY, 14, 12, 0,  27, 23, 0,  4, 0, 1,  1, 3, 2, 220.00),
    (CURDATE() - INTERVAL  5 DAY, 14, 12, 0,  27, 23, 0,  5, 0, 1,  1, 3, 2, 260.00),
    (CURDATE() - INTERVAL  4 DAY, 14, 12, 0,  27, 23, 0,  5, 1, 0,  1, 2, 2, 260.00),
    (CURDATE() - INTERVAL  3 DAY, 14, 12, 0,  27, 23, 0,  4, 1, 0,  1, 2, 2, 220.00),
    (CURDATE() - INTERVAL  2 DAY, 14, 12, 0,  27, 23, 0,  4, 0, 1,  1, 2, 2, 220.00),
    (CURDATE() - INTERVAL  1 DAY, 14, 12, 0,  27, 23, 0,  4, 0, 0,  1, 2, 2, 220.00);

-- Clear the borrow-seed bypass now that all historical borrows are loaded.
SET @seeding = NULL;

-- ============================================================
-- 11. POPULATE SUMMARY TABLES + COMMIT
-- ============================================================
-- Refresh all materialized summary tables so _fast_v views
-- return populated data on first read, then commit the seed
-- transaction.

CALL sp_refresh_all_summaries();

COMMIT;


-- Smoke-test query: list seeded accounts grouped by role so the
-- dump output confirms the install succeeded at a glance.
SELECT
    id_acc AS 'ID',
    first_name_acc AS 'First Name',
    last_name_acc AS 'Last Name',
    email_address_acc AS 'Email',
    r.role_name_rol AS 'Role',
    s.status_name_ast AS 'Status'
FROM account_acc a
JOIN role_rol r ON a.id_rol_acc = r.id_rol
JOIN account_status_ast s ON a.id_ast_acc = s.id_ast
WHERE a.deleted_at_acc IS NULL
ORDER BY r.id_rol DESC, a.id_acc;


-- ============================================================
-- 12. SESSION RESTORE
-- ============================================================
-- Restore the OLD_* session variables captured in section 1 so
-- the calling session exits with its original state intact.

SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;

