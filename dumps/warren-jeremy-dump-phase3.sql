-- ============================================================
-- NeigborhoodTools Database Dump File
-- ============================================================
-- Author: Jeremy Warren
-- Course: WEB-289 Capstone Project
-- Database: MySQL 8.0.16 or later
-- File: warren-jeremy-dump-phase3.sql
-- Description: Database Creation
-- ============================================================

-- ================================================================
-- ================================================================
--                            SCHEMA
-- ================================================================
-- ================================================================

-- ============================================================
-- SESSION SETTINGS
-- ============================================================

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES';
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';

-- ============================================================
-- DATABASE CREATION
-- ============================================================

CREATE DATABASE IF NOT EXISTS neighborhoodtools
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE neighborhoodtools;

-- ============================================================
-- CLEANUP: Drop existing objects (for re-import)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all views (including fast/materialized convenience views)
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
DROP VIEW IF EXISTS neighborhood_summary_v;
DROP VIEW IF EXISTS tool_statistics_v;
DROP VIEW IF EXISTS user_reputation_v;
DROP VIEW IF EXISTS tool_detail_v;
DROP VIEW IF EXISTS account_profile_v;
DROP VIEW IF EXISTS pending_request_v;
DROP VIEW IF EXISTS overdue_borrow_v;
DROP VIEW IF EXISTS active_borrow_v;
DROP VIEW IF EXISTS available_tool_v;
DROP VIEW IF EXISTS active_account_v;

-- Drop all triggers
DROP TRIGGER IF EXISTS trg_incident_report_before_insert;
DROP TRIGGER IF EXISTS trg_handover_verification_before_insert;
DROP TRIGGER IF EXISTS trg_borrow_waiver_before_insert;
DROP TRIGGER IF EXISTS trg_dispute_message_before_insert;
DROP TRIGGER IF EXISTS trg_dispute_before_insert;
DROP TRIGGER IF EXISTS trg_tool_rating_before_insert;
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

-- Drop lookup table protection triggers
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

-- Drop scheduled events
DROP EVENT IF EXISTS evt_daily_stat_midnight;
DROP EVENT IF EXISTS evt_refresh_tool_statistics_every_2h;
DROP EVENT IF EXISTS evt_refresh_user_reputation_every_4h;
DROP EVENT IF EXISTS evt_refresh_summaries_hourly;
DROP EVENT IF EXISTS evt_send_overdue_notifications;
DROP EVENT IF EXISTS evt_cleanup_expired_handovers;
DROP EVENT IF EXISTS evt_archive_old_notifications;
DROP EVENT IF EXISTS evt_cleanup_search_logs;

-- Drop stored procedures
DROP PROCEDURE IF EXISTS sp_refresh_all_summaries;
DROP PROCEDURE IF EXISTS sp_refresh_platform_daily_stat;
DROP PROCEDURE IF EXISTS sp_refresh_category_summary;
DROP PROCEDURE IF EXISTS sp_refresh_tool_statistics;
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

-- Drop helper functions
DROP FUNCTION IF EXISTS fn_get_account_status_id;
DROP FUNCTION IF EXISTS fn_get_borrow_status_id;
DROP FUNCTION IF EXISTS fn_get_block_type_id;
DROP FUNCTION IF EXISTS fn_get_rating_role_id;
DROP FUNCTION IF EXISTS fn_get_notification_type_id;
DROP FUNCTION IF EXISTS fn_get_deposit_status_id;
DROP FUNCTION IF EXISTS fn_get_dispute_status_id;
DROP FUNCTION IF EXISTS fn_get_handover_type_id;
DROP FUNCTION IF EXISTS fn_is_tool_available;

-- Drop tables in reverse dependency order
-- Summary/materialized tables (no FK dependencies)
DROP TABLE IF EXISTS platform_daily_stat_pds;
DROP TABLE IF EXISTS category_summary_mat;
DROP TABLE IF EXISTS tool_statistics_mat;
DROP TABLE IF EXISTS user_reputation_mat;
DROP TABLE IF EXISTS neighborhood_summary_mat;
-- Transactional tables
DROP TABLE IF EXISTS payment_transaction_meta_ptm;
DROP TABLE IF EXISTS payment_transaction_ptx;
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
DROP TABLE IF EXISTS event_meta_evm;
DROP TABLE IF EXISTS event_evt;
DROP TABLE IF EXISTS search_log_slg;
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
DROP TABLE IF EXISTS category_cat;
DROP TABLE IF EXISTS vector_image_vec;
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
-- LOOKUP/REFERENCE TABLES
-- ============================================================

-- role_rol
CREATE TABLE role_rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    role_name_rol VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'member, admin, super_admin'
) ENGINE=InnoDB;

-- account_tatus_ast
CREATE TABLE account_status_ast (
    id_ast INT AUTO_INCREMENT PRIMARY KEY,
    status_name_ast VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pending, active, suspended, deleted'
) ENGINE=InnoDB;

-- contact_preference_cpr
CREATE TABLE contact_preference_cpr (
    id_cpr INT AUTO_INCREMENT PRIMARY KEY,
    preference_name_cpr VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'email, phone, both, app'
) ENGINE=InnoDB;

-- state_sta
CREATE TABLE state_sta (
    id_sta INT AUTO_INCREMENT PRIMARY KEY,
    state_code_sta VARCHAR(2) NOT NULL UNIQUE
        COMMENT 'Two-letter US state code',
    state_name_sta VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'Full US state name'
) ENGINE=InnoDB
    COMMENT='US state lookup table for address normalization';

-- tool_condition_tcd
CREATE TABLE tool_condition_tcd (
    id_tcd INT AUTO_INCREMENT PRIMARY KEY,
    condition_name_tcd VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'new, good, fair, poor'
) ENGINE=InnoDB;

-- borrow_status_bst
CREATE TABLE borrow_status_bst (
    id_bst INT AUTO_INCREMENT PRIMARY KEY,
    status_name_bst VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'requested, approved, borrowed, returned, denied, cancelled'
) ENGINE=InnoDB
    COMMENT='Use name lookups or views at runtime. Enforce with BEFORE UPDATE/DELETE triggers and explicit seeding if IDs must be locked.';

-- block_type_btp
CREATE TABLE block_type_btp (
    id_btp INT AUTO_INCREMENT PRIMARY KEY,
    type_name_btp VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'admin, borrow'
) ENGINE=InnoDB;

-- rating_role_rtr
CREATE TABLE rating_role_rtr (
    id_rtr INT AUTO_INCREMENT PRIMARY KEY,
    role_name_rtr VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'lender, borrower'
) ENGINE=InnoDB;

-- dispute_status_dst
CREATE TABLE dispute_status_dst (
    id_dst INT AUTO_INCREMENT PRIMARY KEY,
    status_name_dst VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'open, resolved, dismissed'
) ENGINE=InnoDB;

-- dispute_message_type_dmt
CREATE TABLE dispute_message_type_dmt (
    id_dmt INT AUTO_INCREMENT PRIMARY KEY,
    type_name_dmt VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'initial_report, response, admin_note, resolution'
) ENGINE=InnoDB;

-- notification_type_ntt
CREATE TABLE notification_type_ntt (
    id_ntt INT AUTO_INCREMENT PRIMARY KEY,
    type_name_ntt VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'request, approval, due, return, rating'
) ENGINE=InnoDB;

-- waiver_type_wtp
CREATE TABLE waiver_type_wtp (
    id_wtp INT AUTO_INCREMENT PRIMARY KEY,
    type_name_wtp VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'borrow_waiver, condition_acknowledgment, liability_release'
) ENGINE=InnoDB;

-- handover_type_hot
CREATE TABLE handover_type_hot (
    id_hot INT AUTO_INCREMENT PRIMARY KEY,
    type_name_hot VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pickup, return'
) ENGINE=InnoDB;

-- incident_type_ity
CREATE TABLE incident_type_ity (
    id_ity INT AUTO_INCREMENT PRIMARY KEY,
    type_name_ity VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'damage, theft, loss, injury, late_return, condition_dispute, other'
) ENGINE=InnoDB;

-- deposit_status_dps
CREATE TABLE deposit_status_dps (
    id_dps INT AUTO_INCREMENT PRIMARY KEY,
    status_name_dps VARCHAR(30) NOT NULL UNIQUE
        COMMENT 'pending, held, released, forfeited, partial_release'
) ENGINE=InnoDB;

-- payment_provider_ppv
CREATE TABLE payment_provider_ppv (
    id_ppv INT AUTO_INCREMENT PRIMARY KEY,
    provider_name_ppv VARCHAR(50) NOT NULL UNIQUE
        COMMENT 'stripe, paypal, manual',
    is_active_ppv BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- ============================================================
-- CORE SCHEMA TABLES
-- ============================================================

-- ZIP codes Table
CREATE TABLE zip_code_zpc (
    zip_code_zpc VARCHAR(10) PRIMARY KEY,
    latitude_zpc DECIMAL(9,6) NOT NULL,
    longitude_zpc DECIMAL(9,6) NOT NULL,
    location_point_zpc POINT NOT NULL SRID 4326
        COMMENT 'Spatial POINT for indexed proximity queries (SRID 4326 = WGS84)',
    SPATIAL INDEX idx_location_zpc (location_point_zpc)
) ENGINE=InnoDB
    COMMENT='ZIP code table with spatial indexing. Proximity queries use ST_Distance_Sphere (meters). 1 mile = 1609.344 meters. SRID 4326 = WGS84 standard GPS coordinates.';

-- Neighborhoods Table
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

-- Neighborhood Metadata Table
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

-- Neighborhood-ZIP junction Table
CREATE TABLE neighborhood_zip_nbhzpc (
    id_nbhzpc INT AUTO_INCREMENT PRIMARY KEY,
    id_nbh_nbhzpc INT NOT NULL,
    zip_code_nbhzpc VARCHAR(10) NOT NULL,
    is_primary_nbhzpc BOOLEAN DEFAULT FALSE
        COMMENT 'True = primary neighborhood for this ZIP; only one allowed per ZIP',
    created_at_nbhzpc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_neighborhood_zip_nbhzpc (id_nbh_nbhzpc, zip_code_nbhzpc),
    INDEX idx_zip_primary_nbhzpc (zip_code_nbhzpc, is_primary_nbhzpc),
    INDEX idx_neighborhood_nbhzpc (id_nbh_nbhzpc),
    CONSTRAINT fk_nbhzpc_neighborhood FOREIGN KEY (id_nbh_nbhzpc)
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_nbhzpc_zip FOREIGN KEY (zip_code_nbhzpc)
        REFERENCES zip_code_zpc (zip_code_zpc) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Junction table: neighborhoods can contain multiple ZIPs, ZIPs can belong to multiple neighborhoods. Handles edge cases where ZIP codes cross neighborhood/community boundaries.';

-- Main Account Table
CREATE TABLE account_acc (
    id_acc INT AUTO_INCREMENT PRIMARY KEY,
    first_name_acc VARCHAR(100) NOT NULL,
    last_name_acc VARCHAR(100) NOT NULL,
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
    is_verified_acc BOOLEAN NOT NULL DEFAULT FALSE,
    has_consent_acc BOOLEAN NOT NULL DEFAULT FALSE,
    last_login_at_acc TIMESTAMP NULL,
    created_at_acc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_acc TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at_acc TIMESTAMP NULL
        COMMENT 'Set via trigger when id_ast_acc changes to deleted status; NULL = active',
    INDEX idx_email_acc (email_address_acc),
    INDEX idx_zip_acc (zip_code_acc),
    INDEX idx_role_acc (id_rol_acc),
    INDEX idx_status_verified_acc (id_ast_acc, is_verified_acc),
    INDEX idx_contact_preference_acc (id_cpr_acc),
    INDEX idx_neighborhood_acc (id_nbh_acc),
    INDEX idx_status_neighborhood_verified_acc (id_ast_acc, id_nbh_acc, is_verified_acc),
    INDEX idx_last_login_acc (last_login_at_acc),
    INDEX idx_created_at_acc (created_at_acc),
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
    CONSTRAINT chk_email_format CHECK (email_address_acc REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
) ENGINE=InnoDB
    COMMENT='Main account table. Soft-delete: id_ast_acc = deleted status is the single source of truth.';

-- Account Meta Table
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

-- Account Image Table
CREATE TABLE account_image_aim (
    id_aim INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_aim INT NOT NULL,
    file_name_aim VARCHAR(255) NOT NULL,
    alt_text_aim VARCHAR(255),
    is_primary_aim BOOLEAN NOT NULL DEFAULT FALSE,
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

-- Account Bio Table
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

-- Vector Images Table
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

-- Categories Table
CREATE TABLE category_cat (
    id_cat INT AUTO_INCREMENT PRIMARY KEY,
    category_name_cat VARCHAR(100) NOT NULL UNIQUE,
    id_vec_cat INT
        COMMENT 'Optional category icon from vector_image_vec',
    INDEX idx_category_vector_icon_cat (id_vec_cat),
    CONSTRAINT fk_category_vector FOREIGN KEY (id_vec_cat)
        REFERENCES vector_image_vec (id_vec) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Main Tool Table
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
    is_available_tol BOOLEAN NOT NULL DEFAULT TRUE
        COMMENT 'Owner listing toggle - see Note for true availability logic',
    is_deposit_required_tol BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Lender requires refundable security deposit',
    default_deposit_amount_tol DECIMAL(8,2) DEFAULT 0.00
        COMMENT 'Default deposit amount; 0 = no deposit required',
    estimated_value_tol DECIMAL(8,2)
        COMMENT 'Estimated tool value for insurance/deposit reference',
    preexisting_conditions_tol TEXT
        COMMENT 'Lender disclosure of any pre-existing damage, wear, or conditions - ToS requirement',
    is_insurance_recommended_tol BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Flag for high-value tools ($1000+) where insurance is recommended',
    created_at_tol TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_tol TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner_available_tol (id_acc_tol, is_available_tol),
    INDEX idx_condition_tol (id_tcd_tol),
    INDEX idx_available_owner_created_tol (is_available_tol, id_acc_tol, created_at_tol),
    INDEX idx_created_at_tol (created_at_tol),
    INDEX idx_rental_fee_tol (rental_fee_tol),
    INDEX idx_is_deposit_required_tol (is_deposit_required_tol),
    FULLTEXT INDEX fulltext_tool_search_tol (tool_name_tol, tool_description_tol),
    CONSTRAINT fk_tool_condition FOREIGN KEY (id_tcd_tol)
        REFERENCES tool_condition_tcd (id_tcd) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tool_owner FOREIGN KEY (id_acc_tol)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_rental_fee_non_negative CHECK (rental_fee_tol >= 0),
    CONSTRAINT chk_deposit_amount_non_negative CHECK (default_deposit_amount_tol >= 0),
    CONSTRAINT chk_estimated_value_non_negative CHECK (estimated_value_tol IS NULL OR estimated_value_tol >= 0)
) ENGINE=InnoDB
    COMMENT='is_available_tol = owner intent only. True availability requires: is_available_tol = true AND no overlapping availability_block_avb AND no active borrow_bor. Legal fields: is_deposit_required_tol, preexisting_conditions_tol, is_insurance_recommended_tol.';

-- Tool Metadata Table
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

-- Tool Images Table
CREATE TABLE tool_image_tim (
    id_tim INT AUTO_INCREMENT PRIMARY KEY,
    id_tol_tim INT NOT NULL,
    file_name_tim VARCHAR(255) NOT NULL,
    alt_text_tim VARCHAR(255),
    is_primary_tim BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order_tim INT DEFAULT 0
        COMMENT 'Display order for gallery',
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

-- Tool-Category Junction Table
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

-- Tool Bookmarks Junction Table
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

-- Borrow Transactions Table
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
    is_contact_shared_bor BOOLEAN NOT NULL DEFAULT FALSE,
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
    )
) ENGINE=InnoDB
    COMMENT='Borrow transaction table. CHECK constraints enforce timestamp ordering and mutual exclusivity.';

-- Availability Blocks Table
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

-- User Ratings Table
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

-- Tool Ratings Table
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

-- Disputes Header Table
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
    INDEX idx_status_dsp (id_dst_dsp),
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

-- Dispute Messages Table
CREATE TABLE dispute_message_dsm (
    id_dsm INT AUTO_INCREMENT PRIMARY KEY,
    id_dsp_dsm INT NOT NULL,
    id_acc_dsm INT NOT NULL,
    id_dmt_dsm INT NOT NULL,
    message_text_dsm TEXT NOT NULL,
    is_internal_dsm BOOLEAN NOT NULL DEFAULT FALSE
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

-- Notifications Table
CREATE TABLE notification_ntf (
    id_ntf INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_ntf INT NOT NULL,
    id_ntt_ntf INT NOT NULL,
    title_ntf VARCHAR(255) NOT NULL,
    body_ntf TEXT,
    id_bor_ntf INT,
    is_read_ntf BOOLEAN NOT NULL DEFAULT FALSE,
    read_at_ntf TIMESTAMP NULL DEFAULT NULL,
    created_at_ntf TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unread_timeline_type_ntf (id_acc_ntf, is_read_ntf, created_at_ntf, id_ntt_ntf),
    INDEX idx_borrow_ntf (id_bor_ntf),
    INDEX idx_type_ntf (id_ntt_ntf),
    CONSTRAINT fk_notification_account FOREIGN KEY (id_acc_ntf)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notification_type FOREIGN KEY (id_ntt_ntf)
        REFERENCES notification_type_ntt (id_ntt) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_notification_borrow FOREIGN KEY (id_bor_ntf)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB
    COMMENT='Archival: Delete or move records older than 12 months via scheduled job.';

-- Search Analytics Table
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

-- Events Table
CREATE TABLE event_evt (
    id_evt INT AUTO_INCREMENT PRIMARY KEY,
    event_name_evt VARCHAR(255) NOT NULL,
    event_description_evt TEXT,
    start_at_evt TIMESTAMP NOT NULL,
    end_at_evt TIMESTAMP NULL,
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
        REFERENCES neighborhood_nbh (id_nbh) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Events Metadata Table
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

-- phpBB Integration Table
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

-- Audit Logging Table
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

-- Audit Log Details Table
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

-- Terms of Service Table
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
    is_active_tos BOOLEAN NOT NULL DEFAULT TRUE
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

-- ToS Acceptance Table
CREATE TABLE tos_acceptance_tac (
    id_tac INT AUTO_INCREMENT PRIMARY KEY,
    id_acc_tac INT NOT NULL,
    id_tos_tac INT NOT NULL,
    ip_address_tac VARCHAR(45)
        COMMENT 'IP address at time of acceptance',
    user_agent_tac VARCHAR(512)
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

-- Borrow Waivers Table
CREATE TABLE borrow_waiver_bwv (
    id_bwv INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_bwv INT NOT NULL UNIQUE
        COMMENT 'One waiver per borrow; FK to borrow_bor',
    id_wtp_bwv INT NOT NULL
        COMMENT 'FK to waiver_type_wtp',
    id_acc_bwv INT NOT NULL
        COMMENT 'Borrower who signed the waiver',
    is_tool_condition_acknowledged_bwv BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Borrower confirms current tool condition',
    preexisting_conditions_noted_bwv TEXT
        COMMENT 'Snapshot of tool preexisting conditions at time of waiver',
    is_responsibility_accepted_bwv BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Borrower accepts responsibility for tool during borrow',
    is_liability_waiver_accepted_bwv BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Borrower acknowledges platform liability limitations',
    is_insurance_reminder_shown_bwv BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'Reminder about personal insurance was displayed',
    ip_address_bwv VARCHAR(45),
    user_agent_bwv VARCHAR(512),
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

-- Handover Verification Table
CREATE TABLE handover_verification_hov (
    id_hov INT AUTO_INCREMENT PRIMARY KEY,
    id_bor_hov INT NOT NULL
        COMMENT 'FK to borrow_bor',
    id_hot_hov INT NOT NULL
        COMMENT 'FK to handover_type_hot (pickup or return)',
    verification_code_hov VARCHAR(8) NOT NULL
        COMMENT 'Unique 6-8 character code for digital handshake',
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

-- Incident Reporting Table
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
    is_reported_within_deadline_irt BOOLEAN NOT NULL DEFAULT TRUE
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
    CONSTRAINT fk_irt_borrow FOREIGN KEY (id_bor_irt)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_reporter FOREIGN KEY (id_acc_irt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_incident_type FOREIGN KEY (id_ity_irt)
        REFERENCES incident_type_ity (id_ity) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_irt_resolver FOREIGN KEY (id_acc_resolved_by_irt)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_damage_amount_non_negative CHECK (estimated_damage_amount_irt IS NULL OR estimated_damage_amount_irt >= 0)
) ENGINE=InnoDB
    COMMENT='Mandatory incident reporting for damage, theft, loss, or disputes. ToS requires reporting within 24-48 hours of incident.';

-- Incident Photos Table
CREATE TABLE incident_photo_iph (
    id_iph INT AUTO_INCREMENT PRIMARY KEY,
    id_irt_iph INT NOT NULL,
    file_name_iph VARCHAR(255) NOT NULL,
    caption_iph VARCHAR(255),
    sort_order_iph INT DEFAULT 0,
    created_at_iph TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incident_photo_order_iph (id_irt_iph, sort_order_iph),
    CONSTRAINT fk_incident_photo_incident FOREIGN KEY (id_irt_iph)
        REFERENCES incident_report_irt (id_irt) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
    COMMENT='Incident photos normalized into separate rows (strict 1NF/3NF).';

-- Loan Extensions Table
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

-- Security Deposit Table
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

-- Payment Transactions Table
CREATE TABLE payment_transaction_ptx (
    id_ptx INT AUTO_INCREMENT PRIMARY KEY,
    id_sdp_ptx INT
        COMMENT 'FK to security_deposit_sdp; NULL for rental fees',
    id_bor_ptx INT NOT NULL
        COMMENT 'FK to borrow_bor',
    id_ppv_ptx INT NOT NULL
        COMMENT 'FK to payment_provider_ppv',
    transaction_type_ptx VARCHAR(30) NOT NULL
        COMMENT 'deposit_hold, deposit_release, deposit_forfeit, rental_fee',
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
    INDEX idx_txn_type_ptx (transaction_type_ptx),
    INDEX idx_processed_at_ptx (processed_at_ptx),
    CONSTRAINT fk_ptx_deposit FOREIGN KEY (id_sdp_ptx)
        REFERENCES security_deposit_sdp (id_sdp) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ptx_borrow FOREIGN KEY (id_bor_ptx)
        REFERENCES borrow_bor (id_bor) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptx_provider FOREIGN KEY (id_ppv_ptx)
        REFERENCES payment_provider_ppv (id_ppv) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptx_from_account FOREIGN KEY (id_acc_from_ptx)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ptx_to_account FOREIGN KEY (id_acc_to_ptx)
        REFERENCES account_acc (id_acc) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_transaction_amount_non_negative CHECK (amount_ptx >= 0)
) ENGINE=InnoDB
    COMMENT='Detailed transaction log for all payment activities. Tracks Stripe integration for deposits and rental fees. Future: insurance provider API integration.';

-- Payment Transaction Metadata Table
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

-- ============================================================
-- MATERIALIZED SUMMARY TABLES
-- ============================================================

-- Matterialized version of neighborhood_summary_view
CREATE TABLE neighborhood_summary_mat (
    id_nbh INT UNSIGNED NOT NULL PRIMARY KEY,
    neighborhood_name_nbh VARCHAR(100) NOT NULL,
    city_name_nbh VARCHAR(100) NOT NULL,
    state_code_sta CHAR(2) NOT NULL,
    state_name_sta VARCHAR(50) NOT NULL,
    latitude_nbh DECIMAL(10, 8),
    longitude_nbh DECIMAL(11, 8),
    location_point_nbh POINT SRID 4326,
    created_at_nbh TIMESTAMP,
    -- Member statistics
    total_members INT UNSIGNED NOT NULL DEFAULT 0,
    active_members INT UNSIGNED NOT NULL DEFAULT 0,
    verified_members INT UNSIGNED NOT NULL DEFAULT 0,
    -- Tool statistics
    total_tools INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools INT UNSIGNED NOT NULL DEFAULT 0,
    -- Borrow statistics
    active_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows_30d INT UNSIGNED NOT NULL DEFAULT 0,
    -- Event statistics
    upcoming_events INT UNSIGNED NOT NULL DEFAULT 0,
    -- Associated ZIP codes (comma-separated for display)
    zip_codes TEXT,
    -- Refresh metadata
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_state_mat (state_code_sta),
    INDEX idx_city_mat (city_name_nbh),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Materialized view: pre-computed neighborhood statistics';

-- Materialized version of user_reputation_v
CREATE TABLE user_reputation_mat (
    id_acc INT UNSIGNED NOT NULL PRIMARY KEY,
    full_name VARCHAR(101) NOT NULL,
    email_address_acc VARCHAR(255) NOT NULL,
    account_status VARCHAR(30) NOT NULL,
    member_since TIMESTAMP,
    -- Lender metrics
    lender_avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    lender_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    -- Borrower metrics
    borrower_avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    borrower_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    -- Overall metrics
    overall_avg_rating DECIMAL(3, 1) DEFAULT NULL,
    total_rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    -- Activity metrics
    tools_owned INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    -- Refresh metadata
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_lender_rating_mat (lender_avg_rating DESC),
    INDEX idx_borrower_rating_mat (borrower_avg_rating DESC),
    INDEX idx_overall_rating_mat (overall_avg_rating DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Materialized view: pre-computed user reputation scores';

-- Materialized version of tool_statistics_view
CREATE TABLE tool_statistics_mat (
    id_tol INT UNSIGNED NOT NULL PRIMARY KEY,
    tool_name_tol VARCHAR(100) NOT NULL,
    owner_id INT UNSIGNED NOT NULL,
    owner_name VARCHAR(101) NOT NULL,
    tool_condition VARCHAR(30) NOT NULL,
    rental_fee_tol DECIMAL(10, 2),
    estimated_value_tol DECIMAL(10, 2),
    created_at_tol TIMESTAMP,
    -- Rating statistics
    avg_rating DECIMAL(3, 1) NOT NULL DEFAULT 0,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    five_star_count INT UNSIGNED NOT NULL DEFAULT 0,
    -- Borrow statistics
    total_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    denied_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    -- Usage metrics
    total_hours_borrowed INT UNSIGNED NOT NULL DEFAULT 0,
    last_borrowed_at TIMESTAMP NULL,
    -- Incident history
    incident_count INT UNSIGNED NOT NULL DEFAULT 0,
    -- Refresh metadata
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_owner_mat (owner_id),
    INDEX idx_avg_rating_mat (avg_rating DESC),
    INDEX idx_total_borrows_mat (total_borrows DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Materialized view: pre-computed tool statistics';

-- Materialized version of category_summary_v
CREATE TABLE category_summary_mat (
    id_cat INT UNSIGNED NOT NULL PRIMARY KEY,
    category_name_cat VARCHAR(100) NOT NULL,
    category_icon VARCHAR(255),
    -- Tool counts
    total_tools INT UNSIGNED NOT NULL DEFAULT 0,
    listed_tools INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools INT UNSIGNED NOT NULL DEFAULT 0,
    -- Rating statistics
    category_avg_rating DECIMAL(3, 1) DEFAULT NULL,
    -- Borrow activity
    total_completed_borrows INT UNSIGNED NOT NULL DEFAULT 0,
    -- Price range
    min_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    max_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    avg_rental_fee DECIMAL(10, 2) DEFAULT NULL,
    -- Refresh metadata
    refreshed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_total_tools_mat (total_tools DESC),
    INDEX idx_available_mat (available_tools DESC),
    INDEX idx_refreshed_mat (refreshed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Materialized view: pre-computed category statistics';

-- Daily platform-wide statistics for admin dashboard
CREATE TABLE platform_daily_stat_pds (
    stat_date_pds DATE NOT NULL PRIMARY KEY,
    -- User metrics
    total_accounts_pds INT UNSIGNED NOT NULL DEFAULT 0,
    active_accounts_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_accounts_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    -- Tool metrics
    total_tools_pds INT UNSIGNED NOT NULL DEFAULT 0,
    available_tools_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_tools_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    -- Borrow metrics
    active_borrows_pds INT UNSIGNED NOT NULL DEFAULT 0,
    completed_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    new_requests_today_pds INT UNSIGNED NOT NULL DEFAULT 0,
    -- Issue metrics
    open_disputes_pds INT UNSIGNED NOT NULL DEFAULT 0,
    open_incidents_pds INT UNSIGNED NOT NULL DEFAULT 0,
    overdue_borrows_pds INT UNSIGNED NOT NULL DEFAULT 0,
    -- Financial metrics (if deposits enabled)
    deposits_held_total_pds DECIMAL(12, 2) NOT NULL DEFAULT 0,
    -- Refresh metadata
    refreshed_at_pds TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_stat_month_pds (stat_date_pds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Daily platform statistics for admin dashboard and reporting';

-- ============================================================
-- RESTORE SESSION SETTINGS
-- ============================================================

SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;

-- ================================================================
-- ================================================================
--                         End of Schema
-- ================================================================
-- ================================================================


-- ================================================================
-- ================================================================
--                           Triggers
-- ================================================================
-- ================================================================

-- ============================================================
-- NEIGHBORHOOD TRIGGERS
-- ============================================================

-- Trigger: enforce single primary neighborhood per ZIP on INSERT
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

-- Trigger: enforce single primary neighborhood per ZIP on UPDATE
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

-- ============================================================
-- ACCOUNT TRIGGERS
-- ============================================================

-- Trigger: set deleted_at_acc when status changes to deleted
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

-- ============================================================
-- TOOL TRIGGERS
-- ============================================================

-- Trigger: reject if owner is deleted account on INSERT
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

-- Trigger: reject if owner is deleted account on UPDATE
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

-- ============================================================
-- BOOKMARK TRIGGERS
-- ============================================================

-- Trigger: reject deleted accounts on bookmark INSERT
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

-- ============================================================
-- BORROW TRIGGERS
-- ============================================================

-- Trigger: borrow validations on INSERT (PERMISSIVE)
DELIMITER $$
CREATE TRIGGER trg_borrow_before_insert
BEFORE INSERT ON borrow_bor
FOR EACH ROW
BEGIN
    DECLARE borrower_status_id INT;
    DECLARE owner_status_id INT;
    DECLARE tool_owner_id INT;
    DECLARE deleted_status_id INT;
    DECLARE approved_status_id INT;
    DECLARE borrowed_status_id INT;
    DECLARE returned_status_id INT;

    SELECT
        MAX(CASE WHEN status_name_bst = 'approved' THEN id_bst END),
        MAX(CASE WHEN status_name_bst = 'borrowed' THEN id_bst END),
        MAX(CASE WHEN status_name_bst = 'returned' THEN id_bst END)
    INTO approved_status_id, borrowed_status_id, returned_status_id
    FROM borrow_status_bst
    WHERE status_name_bst IN ('approved', 'borrowed', 'returned');

    SELECT id_ast INTO deleted_status_id
    FROM account_status_ast
    WHERE status_name_ast = 'deleted'
    LIMIT 1;

    IF approved_status_id IS NULL OR borrowed_status_id IS NULL OR returned_status_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'System error: required borrow status values not found in borrow_status_bst';
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

    IF NEW.id_bst_bor = approved_status_id AND NEW.approved_at_bor IS NULL THEN
        SET NEW.approved_at_bor = NOW();
    END IF;

    IF NEW.id_bst_bor = borrowed_status_id THEN
        IF NEW.approved_at_bor IS NULL THEN
            SET NEW.approved_at_bor = NOW();
        END IF;
        IF NEW.borrowed_at_bor IS NULL THEN
            SET NEW.borrowed_at_bor = NOW();
        END IF;
        IF NEW.due_at_bor IS NULL THEN
            SET NEW.due_at_bor = DATE_ADD(NEW.borrowed_at_bor, INTERVAL NEW.loan_duration_hours_bor HOUR);
        END IF;
    END IF;

    IF NEW.id_bst_bor = returned_status_id THEN
        IF NEW.approved_at_bor IS NULL THEN
            SET NEW.approved_at_bor = NOW();
        END IF;
        IF NEW.borrowed_at_bor IS NULL THEN
            SET NEW.borrowed_at_bor = NOW();
        END IF;
        IF NEW.due_at_bor IS NULL THEN
            SET NEW.due_at_bor = DATE_ADD(NEW.borrowed_at_bor, INTERVAL NEW.loan_duration_hours_bor HOUR);
        END IF;
        IF NEW.returned_at_bor IS NULL THEN
            SET NEW.returned_at_bor = NOW();
        END IF;
    END IF;
END$$
DELIMITER ;

-- Trigger: borrow validations on UPDATE (STRICT)
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

        SELECT
            MAX(CASE WHEN status_name_bst = 'requested' THEN id_bst END),
            MAX(CASE WHEN status_name_bst = 'approved' THEN id_bst END),
            MAX(CASE WHEN status_name_bst = 'borrowed' THEN id_bst END),
            MAX(CASE WHEN status_name_bst = 'returned' THEN id_bst END),
            MAX(CASE WHEN status_name_bst = 'denied' THEN id_bst END),
            MAX(CASE WHEN status_name_bst = 'cancelled' THEN id_bst END)
        INTO requested_status_id, approved_status_id, borrowed_status_id,
             returned_status_id, denied_status_id, cancelled_status_id
        FROM borrow_status_bst
        WHERE status_name_bst IN ('requested', 'approved', 'borrowed', 'returned', 'denied', 'cancelled');

        IF requested_status_id IS NULL OR approved_status_id IS NULL
           OR borrowed_status_id IS NULL OR returned_status_id IS NULL
           OR denied_status_id IS NULL OR cancelled_status_id IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'System error: required borrow status values not found in borrow_status_bst';
        END IF;

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

-- ============================================================
-- AVAILABILITY BLOCK TRIGGERS
-- ============================================================

-- Trigger: validate availability block on INSERT
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

-- Trigger: validate availability block on UPDATE
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

-- ============================================================
-- RATING TRIGGERS
-- ============================================================

-- Trigger: reject deleted accounts, self-ratings, and non-participants on user rating INSERT
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

-- Trigger: prevent self-rating on UPDATE (defense in depth)
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

-- Trigger: reject deleted accounts and non-borrowers on tool rating INSERT
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

-- ============================================================
-- DISPUTE TRIGGERS
-- ============================================================

-- Trigger: reject deleted reporter and non-participants on dispute INSERT
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

-- Trigger: reject deleted author on dispute message INSERT
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

-- ============================================================
-- WAIVER & HANDOVER TRIGGERS
-- ============================================================

-- Trigger: enforce required acknowledgments
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

-- Trigger: auto-generate unique verification code and set expiry
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

-- ============================================================
-- INCIDENT TRIGGERS
-- ============================================================

-- Trigger: auto-calculate is_reported_within_deadline_irt
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

-- ============================================================
-- TOS TRIGGERS
-- ============================================================

-- Trigger: Prevent direct INSERT of active ToS (forces use of sp_create_tos_version)
DELIMITER $$
CREATE TRIGGER trg_tos_before_insert
BEFORE INSERT ON terms_of_service_tos
FOR EACH ROW
BEGIN
    -- Set created_at if not provided
    SET NEW.created_at_tos = COALESCE(NEW.created_at_tos, NOW());
END$$
DELIMITER ;

-- ============================================================
-- LOOKUP TABLE PROTECTION TRIGGERS
-- ============================================================
-- Prevent deletion or renaming of system-required lookup values that triggers depend on for enforcement logic.

-- Protect account_status_ast required values
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

-- Protect borrow_status_bst required values
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

-- Protect block_type_btp required values
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

-- Protect rating_role_rtr required values
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

-- Protect handover_type_hot required values
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

-- Protect deposit_status_dps required values
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

-- ================================================================
-- ================================================================
--                         END OF TRIGGERS
-- ================================================================
-- ================================================================

-- ================================================================
-- ================================================================
--                              VIEWS
-- ================================================================
-- ================================================================

-- ============================================================
-- Status/Availability Views
-- ============================================================

-- active_account_v: All accounts except deleted ones
CREATE VIEW active_account_v AS
SELECT *
FROM account_acc
WHERE id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted');

-- available_tool_v: Tools truly available for borrowing
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
    nbh.neighborhood_name_nbh AS owner_neighborhood
FROM tool_tol t
JOIN account_acc a ON t.id_acc_tol = a.id_acc
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
LEFT JOIN neighborhood_nbh nbh ON a.id_nbh_acc = nbh.id_nbh
LEFT JOIN borrow_bor active_borrow ON t.id_tol = active_borrow.id_tol_bor
    AND active_borrow.id_bst_bor IN ((SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed'))
LEFT JOIN availability_block_avb active_block ON t.id_tol = active_block.id_tol_avb
    AND NOW() BETWEEN active_block.start_at_avb AND active_block.end_at_avb
WHERE t.is_available_tol = TRUE
  AND a.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted')
  AND active_borrow.id_bor IS NULL
  AND active_block.id_avb IS NULL;

-- active_borrow_v: Currently checked-out items
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
WHERE b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed');

-- overdue_borrow_v: Past-due items requiring attention
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
WHERE b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
  AND b.due_at_bor < NOW();

-- pending_request_v: Borrow requests awaiting approval
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
    COALESCE(borrower_ratings.avg_rating, 0) AS borrower_avg_rating,
    COALESCE(borrower_ratings.rating_count, 0) AS borrower_rating_count
FROM borrow_bor b
JOIN tool_tol t ON b.id_tol_bor = t.id_tol
JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
LEFT JOIN (
    SELECT id_acc_target_urt,
           ROUND(AVG(score_urt), 1) AS avg_rating,
           COUNT(*) AS rating_count
    FROM user_rating_urt
    GROUP BY id_acc_target_urt
) borrower_ratings ON b.id_acc_bor = borrower_ratings.id_acc_target_urt
WHERE b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested');

-- ============================================================
-- Profile/Detail Views
-- ============================================================

-- account_profile_v: Complete member profile with all related data
CREATE VIEW account_profile_v AS
SELECT
    a.id_acc,
    a.first_name_acc,
    a.last_name_acc,
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
    abi.bio_text_abi,
    COALESCE(tool_counts.active_tool_count, 0) AS active_tool_count,
    COALESCE(lender_ratings.avg_rating, 0) AS lender_rating,
    COALESCE(borrower_ratings.avg_rating, 0) AS borrower_rating
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
LEFT JOIN account_bio_abi abi ON a.id_acc = abi.id_acc_abi
LEFT JOIN (
    SELECT id_acc_tol, COUNT(*) AS active_tool_count
    FROM tool_tol
    WHERE is_available_tol = TRUE
    GROUP BY id_acc_tol
) tool_counts ON a.id_acc = tool_counts.id_acc_tol
LEFT JOIN (
    SELECT id_acc_target_urt, ROUND(AVG(score_urt), 1) AS avg_rating
    FROM user_rating_urt
    WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender')
    GROUP BY id_acc_target_urt
) lender_ratings ON a.id_acc = lender_ratings.id_acc_target_urt
LEFT JOIN (
    SELECT id_acc_target_urt, ROUND(AVG(score_urt), 1) AS avg_rating
    FROM user_rating_urt
    WHERE id_rtr_urt = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower')
    GROUP BY id_acc_target_urt
) borrower_ratings ON a.id_acc = borrower_ratings.id_acc_target_urt
WHERE a.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted');

-- tool_detail_v: Full tool listing information
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
    COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
    COALESCE(rating_stats.rating_count, 0) AS rating_count,
    COALESCE(borrow_stats.completed_borrow_count, 0) AS completed_borrow_count,
    cat_list.categories,
    CASE
        WHEN t.is_available_tol = FALSE THEN 'UNLISTED'
        WHEN active_borrow.id_bor IS NOT NULL THEN 'BORROWED'
        WHEN active_block.id_avb IS NOT NULL THEN 'BLOCKED'
        ELSE 'AVAILABLE'
    END AS availability_status
FROM tool_tol t
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
JOIN account_acc a ON t.id_acc_tol = a.id_acc
LEFT JOIN neighborhood_nbh nbh ON a.id_nbh_acc = nbh.id_nbh
LEFT JOIN state_sta sta ON nbh.id_sta_nbh = sta.id_sta
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
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
    WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned')
    GROUP BY id_tol_bor
) borrow_stats ON t.id_tol = borrow_stats.id_tol_bor
LEFT JOIN (
    SELECT tc.id_tol_tolcat,
           GROUP_CONCAT(c.category_name_cat ORDER BY c.category_name_cat SEPARATOR ', ') AS categories
    FROM tool_category_tolcat tc
    JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
    GROUP BY tc.id_tol_tolcat
) cat_list ON t.id_tol = cat_list.id_tol_tolcat
LEFT JOIN borrow_bor active_borrow ON t.id_tol = active_borrow.id_tol_bor
    AND active_borrow.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
LEFT JOIN availability_block_avb active_block ON t.id_tol = active_block.id_tol_avb
    AND NOW() BETWEEN active_block.start_at_avb AND active_block.end_at_avb;

-- ============================================================
-- Analytics/Reporting Views
-- ============================================================

-- user_reputation_v: Aggregated user ratings by role
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

-- tool_statistics_v: Tool ratings and borrow counts
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

-- neighborhood_summary_v: Community statistics
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

-- ============================================================
-- Admin Views
-- ============================================================

-- open_dispute_v: Unresolved disputes requiring attention
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
WHERE d.id_dst_dsp = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'open');

-- pending_deposit_v: Deposits held in escrow requiring action
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
        WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned') THEN 'READY FOR RELEASE'
        WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed') AND b.due_at_bor < NOW() THEN 'OVERDUE - REVIEW NEEDED'
        WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed') THEN 'ACTIVE BORROW'
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
WHERE sdp.id_dps_sdp = (SELECT id_dps FROM deposit_status_dps WHERE status_name_dps = 'held');

-- ============================================================
-- Legal & Compliance Views
-- ============================================================

-- current_tos_v: Currently active Terms of Service version
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

-- tos_acceptance_required_v: Active users who need to accept the current ToS
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

-- pending_waiver_v: Approved borrows missing signed waivers (compliance gate)
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
WHERE b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved')
  AND bwv.id_bwv IS NULL;

-- open_incident_v: Unresolved incident reports requiring admin action
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

-- pending_handover_v: Verification codes generated but not yet confirmed
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

-- ============================================================
-- User Interaction Views
-- ============================================================

-- unread_notification_v: User notification feed (unread items)
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

-- user_bookmarks_v: User's saved tools with current availability status
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
    t.id_acc_tol AS owner_id,
    CONCAT(owner.first_name_acc, ' ', owner.last_name_acc) AS owner_name,
    nbh.neighborhood_name_nbh AS owner_neighborhood,
    CASE
        WHEN owner.id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted') THEN 'OWNER DELETED'
        WHEN t.is_available_tol = FALSE THEN 'UNLISTED'
        WHEN active_borrow.id_bor IS NOT NULL THEN 'UNAVAILABLE'
        WHEN active_block.id_avb IS NOT NULL THEN 'BLOCKED'
        ELSE 'AVAILABLE'
    END AS availability_status,
    COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
    COALESCE(rating_stats.rating_count, 0) AS rating_count
FROM tool_bookmark_acctol acctol
JOIN account_acc bookmarker ON acctol.id_acc_acctol = bookmarker.id_acc
JOIN tool_tol t ON acctol.id_tol_acctol = t.id_tol
JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
LEFT JOIN neighborhood_nbh nbh ON owner.id_nbh_acc = nbh.id_nbh
LEFT JOIN borrow_bor active_borrow ON t.id_tol = active_borrow.id_tol_bor
    AND active_borrow.id_bst_bor IN ((SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed'))
LEFT JOIN availability_block_avb active_block ON t.id_tol = active_block.id_tol_avb
    AND NOW() BETWEEN active_block.start_at_avb AND active_block.end_at_avb
LEFT JOIN (
    SELECT id_tol_trt,
           ROUND(AVG(score_trt), 1) AS avg_rating,
           COUNT(*) AS rating_count
    FROM tool_rating_trt
    GROUP BY id_tol_trt
) rating_stats ON t.id_tol = rating_stats.id_tol_trt;

-- ============================================================
-- Analytics/Categorization Views
-- ============================================================

-- category_summary_v: Tool counts and statistics by category
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
         AND active_borrow.id_bor IS NULL
         AND active_block.id_avb IS NULL
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
LEFT JOIN borrow_bor active_borrow ON t.id_tol = active_borrow.id_tol_bor
    AND active_borrow.id_bst_bor IN ((SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'), (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed'))
LEFT JOIN availability_block_avb active_block ON t.id_tol = active_block.id_tol_avb
    AND NOW() BETWEEN active_block.start_at_avb AND active_block.end_at_avb
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

-- ============================================================
-- Future Expansion Views
-- ============================================================

-- upcoming_event_v: Community events for discovery and engagement
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

-- ============================================================
-- Convenience Views for Summary Tables (Fast Views)
-- ============================================================
-- These views provide a simple interface to the materialized summary tables.
-- Used for dashboard queries when near-real-time data is acceptable.

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

-- ================================================================
-- ================================================================
--                          END OF VIEWS
-- ================================================================
-- ================================================================

-- ================================================================
-- ================================================================
--                     PROCEDURES & FUNCTIONS
-- ================================================================
-- ================================================================

-- ============================================================
-- ToS & Loan Extension Procedures
-- ============================================================

-- Stored Procedure: Create new ToS version atomically (deactivates previous, inserts new)
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
-- Usage: CALL sp_create_tos_version('2.0', 'Updated Terms', 'Full text...', 'Summary...', NOW(), 5);

-- Stored Procedure: Extend loan due date atomically (creates audit record, then updates borrow)
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
-- Usage: CALL sp_extend_loan(2, 48, 'Borrower needs extra weekend for project', 1);

-- ============================================================
-- Refresh Stored Procedures
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_refresh_neighborhood_summary
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_refresh_neighborhood_summary()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM neighborhood_summary_mat LIMIT 999999999;

    INSERT INTO neighborhood_summary_mat (
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
        JOIN tool_tol t ON b.id_tol_bor = t.id_tol
        JOIN account_acc a ON t.id_acc_tol = a.id_acc
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

    COMMIT;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Procedure: sp_refresh_user_reputation
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_refresh_user_reputation()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM user_reputation_mat LIMIT 999999999;

    INSERT INTO user_reputation_mat (
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
    WHERE a.id_ast_acc != (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'deleted');

    COMMIT;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Procedure: sp_refresh_tool_statistics
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_refresh_tool_statistics()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM tool_statistics_mat LIMIT 999999999;

    INSERT INTO tool_statistics_mat (
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

    COMMIT;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Procedure: sp_refresh_category_summary
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_refresh_category_summary()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    DELETE FROM category_summary_mat LIMIT 999999999;

    INSERT INTO category_summary_mat (
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
             AND active_borrow.id_bor IS NULL
             AND active_block.id_avb IS NULL
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
    LEFT JOIN borrow_bor active_borrow ON t.id_tol = active_borrow.id_tol_bor
        AND active_borrow.id_bst_bor IN (
            (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested'),
            (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved'),
            (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed')
        )
    LEFT JOIN availability_block_avb active_block ON t.id_tol = active_block.id_tol_avb
        AND NOW() BETWEEN active_block.start_at_avb AND active_block.end_at_avb
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

    COMMIT;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Procedure: sp_refresh_platform_daily_stat
-- -------------------------------------------------------------

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
        deposits_held_total_pds, refreshed_at_pds
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
        NOW();

    COMMIT;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Procedure: sp_refresh_all_summaries
-- Master procedure to refresh all summary tables
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_refresh_all_summaries()
BEGIN
    CALL sp_refresh_neighborhood_summary();
    CALL sp_refresh_user_reputation();
    CALL sp_refresh_tool_statistics();
    CALL sp_refresh_category_summary();
    CALL sp_refresh_platform_daily_stat();
END$$
DELIMITER ;

-- ============================================================
-- Helper Functions for Lookup IDs
-- ============================================================
-- These functions cache lookup table IDs to avoid repeated queries in triggers and application code. MySQL caches function results within a query, reducing lookup overhead.
-- ============================================================

-- -------------------------------------------------------------
-- Function: fn_get_account_status_id
-- Returns the ID for a given account status name
-- Usage: fn_get_account_status_id('active') returns 2
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_borrow_status_id
-- Returns the ID for a given borrow status name
-- Usage: fn_get_borrow_status_id('borrowed') returns 3
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_block_type_id
-- Returns the ID for a given block type name
-- Usage: fn_get_block_type_id('borrow') returns 2
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_rating_role_id
-- Returns the ID for a given rating role name
-- Usage: fn_get_rating_role_id('lender') returns 1
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_notification_type_id
-- Returns the ID for a given notification type name
-- Usage: fn_get_notification_type_id('request') returns 1
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_deposit_status_id
-- Returns the ID for a given deposit status name
-- Usage: fn_get_deposit_status_id('held') returns 2
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_dispute_status_id
-- Returns the ID for a given dispute status name
-- Usage: fn_get_dispute_status_id('open') returns 1
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_get_handover_type_id
-- Returns the ID for a given handover type name
-- Usage: fn_get_handover_type_id('pickup') returns 1
-- -------------------------------------------------------------

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

    RETURN v_id;
END$$
DELIMITER ;

-- -------------------------------------------------------------
-- Function: fn_is_tool_available
-- Returns TRUE if tool is available for borrowing
-- Checks: is_available_tol, no active borrow, no current block
-- -------------------------------------------------------------

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

-- ============================================================
-- Borrow Workflow Procedures
-- ============================================================
-- These procedures encapsulate the complete borrow lifecycle, ensuring consistent validation and state management.
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_create_borrow_request
-- Creates a new borrow request with full validation
-- Validates: tool availability, account status, not own tool
-- -------------------------------------------------------------

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
-- Usage: CALL sp_create_borrow_request(5, 2, 168, 'Need for weekend project', @borrow_id, @error);

-- -------------------------------------------------------------
-- Procedure: sp_approve_borrow_request
-- Approves a pending borrow request
-- Updates status and sets approved_at timestamp
-- -------------------------------------------------------------

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
-- Usage: CALL sp_approve_borrow_request(1, 3, @success, @error);

-- -------------------------------------------------------------
-- Procedure: sp_deny_borrow_request
-- Denies a pending borrow request
-- -------------------------------------------------------------

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
-- Usage: CALL sp_deny_borrow_request(1, 3, 'Tool needed for personal use', @success, @error);

-- -------------------------------------------------------------
-- Procedure: sp_complete_pickup
-- Marks a borrow as picked up (status: borrowed)
-- Creates availability block for the borrow period
-- -------------------------------------------------------------

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
-- Usage: CALL sp_complete_pickup(1, @success, @error);

-- -------------------------------------------------------------
-- Procedure: sp_complete_return
-- Marks a borrow as returned
-- Removes the availability block
-- -------------------------------------------------------------

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
-- Usage: CALL sp_complete_return(1, @success, @error);

-- -------------------------------------------------------------
-- Procedure: sp_cancel_borrow_request
-- Cancels a borrow request (by borrower or lender)
-- Only allowed for 'requested' or 'approved' status
-- -------------------------------------------------------------

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
-- Usage: CALL sp_cancel_borrow_request(1, 2, 'Change of plans', @success, @error);

-- ============================================================
-- Rating Procedures
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_rate_user
-- Adds a rating for a user (lender or borrower) after transaction
-- Validates: borrow must be completed, rater was part of transaction
-- -------------------------------------------------------------

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
        review_text_urt
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
-- Usage: CALL sp_rate_user(1, 2, 3, 'lender', 5, 'Great lender, tool was in perfect condition!', @rating_id, @error);

-- -------------------------------------------------------------
-- Procedure: sp_rate_tool
-- Adds a rating for a tool after borrowing
-- Validates: borrow must be completed, rater was the borrower
-- -------------------------------------------------------------

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
        review_text_trt
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
-- Usage: CALL sp_rate_tool(1, 2, 4, 'Tool worked well but was a bit worn', @rating_id, @error);

-- ============================================================
-- Notification Procedures
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_send_notification
-- Creates a notification for a user
-- -------------------------------------------------------------

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
        SET v_type_id = fn_get_notification_type_id('request'); -- fallback
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
-- Usage: CALL sp_send_notification(2, 'request', 'New Borrow Request', 'User X wants to borrow your drill', 5, @ntf_id);

-- -------------------------------------------------------------
-- Procedure: sp_mark_notifications_read
-- Batch marks multiple notifications as read for a user
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_mark_notifications_read(
    IN p_account_id INT,
    IN p_notification_ids TEXT,
    OUT p_count INT
)
proc_body: BEGIN

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
-- Usage: CALL sp_mark_notifications_read(2, '1,5,7,12', @count);
-- Usage: CALL sp_mark_notifications_read(2, NULL, @count);  -- marks all as read

-- ============================================================
-- Batch Processing Procedures
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_send_overdue_notifications
-- Sends notifications to borrowers with overdue tools
-- Should be called by a scheduled event
-- -------------------------------------------------------------

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
            AND DATE(n.created_at_ntf) = CURDATE()
      );

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;
-- Usage: CALL sp_send_overdue_notifications(@count);

-- -------------------------------------------------------------
-- Procedure: sp_cleanup_expired_handover_codes
-- Deletes expired unverified handover verification codes
-- Should be called by a scheduled event
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_cleanup_expired_handover_codes(
    OUT p_count INT
)
BEGIN
    DELETE FROM handover_verification_hov
    WHERE expires_at_hov < NOW()
      AND verified_at_hov IS NULL;

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;
-- Usage: CALL sp_cleanup_expired_handover_codes(@count);

-- -------------------------------------------------------------
-- Procedure: sp_archive_old_notifications
-- Deletes read notifications older than specified days
-- Should be called by a scheduled event
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_archive_old_notifications(
    IN p_days_old INT,
    OUT p_count INT
)
BEGIN
    IF p_days_old IS NULL OR p_days_old < 30 THEN
        SET p_days_old = 90;
    END IF;

    DELETE FROM notification_ntf
    WHERE is_read_ntf = TRUE
      AND created_at_ntf < DATE_SUB(NOW(), INTERVAL p_days_old DAY);

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;
-- Usage: CALL sp_archive_old_notifications(90, @count);

-- -------------------------------------------------------------
-- Procedure: sp_cleanup_old_search_logs
-- Deletes search log entries older than specified days
-- Should be called by a scheduled event
-- -------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE sp_cleanup_old_search_logs(
    IN p_days_old INT,
    OUT p_count INT
)
BEGIN
    IF p_days_old IS NULL OR p_days_old < 7 THEN
        SET p_days_old = 30;
    END IF;

    DELETE FROM search_log_slg
    WHERE created_at_slg < DATE_SUB(NOW(), INTERVAL p_days_old DAY);

    SET p_count = ROW_COUNT();
END$$
DELIMITER ;
-- Usage: CALL sp_cleanup_old_search_logs(30, @count);

-- -------------------------------------------------------------
-- Procedure: sp_release_deposit_on_return
-- Releases security deposit when tool is returned successfully
-- Should be called after sp_complete_return
-- -------------------------------------------------------------

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
-- Usage: CALL sp_release_deposit_on_return(1, @success, @error);

-- ============================================================
-- Search and Query Procedures
-- ============================================================

-- -------------------------------------------------------------
-- Procedure: sp_search_available_tools
-- Efficiently searches for available tools with filters
-- Uses covering indexes for optimal performance
-- -------------------------------------------------------------

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
-- Usage: CALL sp_search_available_tools('drill', '28801', NULL, 50.00, 20, 0);

-- -------------------------------------------------------------
-- Procedure: sp_get_user_borrow_history
-- Gets borrow history for a user with pagination
-- -------------------------------------------------------------

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
        CASE WHEN b.id_acc_bor = p_account_id THEN 'borrower' ELSE 'lender' END AS user_role,
        CASE
            WHEN b.id_acc_bor = p_account_id THEN CONCAT(owner.first_name_acc, ' ', owner.last_name_acc)
            ELSE CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc)
        END AS other_party_name
    FROM borrow_bor b
    JOIN tool_tol t ON b.id_tol_bor = t.id_tol
    JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
    JOIN account_acc owner ON t.id_acc_tol = owner.id_acc
    JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
    LEFT JOIN tool_image_tim tim ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
    WHERE (
        (p_role = 'borrower' AND b.id_acc_bor = p_account_id)
        OR (p_role = 'lender' AND t.id_acc_tol = p_account_id)
        OR (p_role IS NULL AND (b.id_acc_bor = p_account_id OR t.id_acc_tol = p_account_id))
    )
    AND (v_status_id IS NULL OR b.id_bst_bor = v_status_id)
    ORDER BY b.requested_at_bor DESC
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;
-- Usage: CALL sp_get_user_borrow_history(2, 'borrower', NULL, 20, 0);
-- Usage: CALL sp_get_user_borrow_history(3, 'lender', 'borrowed', 10, 0);

-- ============================================================
-- Event Scheduler Jobs
-- ============================================================
-- SET GLOBAL event_scheduler = ON;
-- ============================================================

-- Refresh summary tables for dashboard performance every hour
DELIMITER $$
CREATE EVENT evt_refresh_summaries_hourly
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
ENABLE
COMMENT 'Refresh summary tables hourly for dashboard performance'
DO
BEGIN
    CALL sp_refresh_neighborhood_summary();
    CALL sp_refresh_category_summary();
END$$
DELIMITER ;

-- Refresh user reputation scores every 4 hours
DELIMITER $$
CREATE EVENT evt_refresh_user_reputation_every_4h
ON SCHEDULE EVERY 4 HOUR
STARTS CURRENT_TIMESTAMP
ENABLE
COMMENT 'Refresh user reputation every 4 hours'
DO
BEGIN
    CALL sp_refresh_user_reputation();
END$$
DELIMITER ;

-- Refresh tool statistics every 2 hours for trending and recommendations
DELIMITER $$
CREATE EVENT evt_refresh_tool_statistics_every_2h
ON SCHEDULE EVERY 2 HOUR
STARTS CURRENT_TIMESTAMP
ENABLE
COMMENT 'Refresh tool statistics every 2 hours'
DO
BEGIN
    CALL sp_refresh_tool_statistics();
END$$
DELIMITER ;

-- Capture daily platform statistics at midnight for reporting and monitoring
DELIMITER $$
CREATE EVENT evt_daily_stat_midnight
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURDATE()) + INTERVAL 1 DAY)
ENABLE
COMMENT 'Capture daily platform statistics at midnight'
DO
BEGIN
    CALL sp_refresh_platform_daily_stat();
END$$
DELIMITER ;

-- Daily overdue notifications (run at 8 AM)
DELIMITER $$
CREATE EVENT evt_send_overdue_notifications
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURDATE()) + INTERVAL 8 HOUR)
ENABLE
COMMENT 'Send daily overdue notifications to borrowers'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_send_overdue_notifications(v_count);
END$$
DELIMITER ;

-- Hourly cleanup of expired handover codes
DELIMITER $$
CREATE EVENT evt_cleanup_expired_handovers
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
ENABLE
COMMENT 'Clean up expired handover verification codes'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_cleanup_expired_handover_codes(v_count);
END$$
DELIMITER ;

-- Weekly notification cleanup (run Sunday at 2 AM)
DELIMITER $$
CREATE EVENT evt_archive_old_notifications
ON SCHEDULE EVERY 1 WEEK
STARTS (TIMESTAMP(CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY) + INTERVAL 2 HOUR)
ENABLE
COMMENT 'Archive old read notifications weekly'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_archive_old_notifications(90, v_count);
END$$
DELIMITER ;

-- Weekly search log cleanup (run Sunday at 3 AM)
DELIMITER $$
CREATE EVENT evt_cleanup_search_logs
ON SCHEDULE EVERY 1 WEEK
STARTS (TIMESTAMP(CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY) + INTERVAL 3 HOUR)
ENABLE
COMMENT 'Clean up old search logs weekly'
DO
BEGIN
    DECLARE v_count INT;
    CALL sp_cleanup_old_search_logs(30, v_count);
END$$
DELIMITER ;

-- ================================================================
-- ================================================================
--                  END PROCEDURES & FUNCTIONS
-- ================================================================
-- ================================================================

-- ================================================================
-- ================================================================
--                    SEED DATA FOR TESTING
-- ================================================================
-- ================================================================

-- ============================================================
-- Begin transaction for atomic seed data insertion
-- All INSERTs succeed or none do - allows clean rollback on failure
-- ============================================================
START TRANSACTION;

-- ============================================================
-- LOOKUP TABLE SEED DATA (required for foreign key constraints and application logic)
-- ============================================================

-- role_rol seed data
INSERT INTO role_rol (role_name_rol) VALUES
    ('member'),
    ('admin'),
    ('super_admin');

-- account_status_ast seed data
INSERT INTO account_status_ast (status_name_ast) VALUES
    ('pending'),
    ('active'),
    ('suspended'),
    ('deleted');

-- contact_preference_cpr seed data
INSERT INTO contact_preference_cpr (preference_name_cpr) VALUES
    ('email'),
    ('phone'),
    ('both'),
    ('app');

-- state_sta seed data (all 50 US states)
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

-- tool_condition_tcd seed data
INSERT INTO tool_condition_tcd (condition_name_tcd) VALUES
    ('new'),
    ('good'),
    ('fair'),
    ('poor');

-- borrow_status_bst seed data
INSERT INTO borrow_status_bst (status_name_bst) VALUES
    ('requested'),
    ('approved'),
    ('borrowed'),
    ('returned'),
    ('denied'),
    ('cancelled');

-- block_type_btp seed data
INSERT INTO block_type_btp (type_name_btp) VALUES
    ('admin'),
    ('borrow');

-- rating_role_rtr seed data
INSERT INTO rating_role_rtr (role_name_rtr) VALUES
    ('lender'),
    ('borrower');

-- dispute_status_dst seed data
INSERT INTO dispute_status_dst (status_name_dst) VALUES
    ('open'),
    ('resolved'),
    ('dismissed');

-- dispute_message_type_dmt seed data
INSERT INTO dispute_message_type_dmt (type_name_dmt) VALUES
    ('initial_report'),
    ('response'),
    ('admin_note'),
    ('resolution');

-- notification_type_ntt seed data
INSERT INTO notification_type_ntt (type_name_ntt) VALUES
    ('request'),
    ('approval'),
    ('due'),
    ('return'),
    ('rating');

-- waiver_type_wtp seed data
INSERT INTO waiver_type_wtp (type_name_wtp) VALUES
    ('borrow_waiver'),
    ('condition_acknowledgment'),
    ('liability_release');

-- handover_type_hot seed data
INSERT INTO handover_type_hot (type_name_hot) VALUES
    ('pickup'),
    ('return');

-- incident_type_ity seed data
INSERT INTO incident_type_ity (type_name_ity) VALUES
    ('damage'),
    ('theft'),
    ('loss'),
    ('injury'),
    ('late_return'),
    ('condition_dispute'),
    ('other');

-- deposit_status_dps seed data
INSERT INTO deposit_status_dps (status_name_dps) VALUES
    ('pending'),
    ('held'),
    ('released'),
    ('forfeited'),
    ('partial_release');

-- payment_provider_ppv seed data
INSERT INTO payment_provider_ppv (provider_name_ppv, is_active_ppv) VALUES
    ('stripe', TRUE),
    ('paypal', FALSE),
    ('manual', TRUE);

-- ============================================================
-- SAMPLE DATA (Optional - for testing comment out if not needed)
-- ============================================================

-- ============================================================
-- ZIP CODES (Asheville & Hendersonville Service Area)
-- ============================================================
-- Source: USPS / zip-codes.com
-- Standard ZIP codes only
-- ============================================================

-- Asheville ZIP Codes (Buncombe County)
-- 28801 - Downtown Asheville
-- 28803 - South Asheville / Biltmore Forest
-- 28804 - North Asheville / Woodfin
-- 28805 - East Asheville
-- 28806 - West Asheville

-- Greater Asheville Area
-- 28704 - Arden
-- 28715 - Candler
-- 28778 - Black Mountain / Swannanoa
-- 28787 - Weaverville
-- 28732 - Fletcher

-- Hendersonville ZIP Codes (Henderson County)
-- 28739 - Hendersonville / Laurel Park
-- 28791 - Hendersonville (North)
-- 28792 - Hendersonville (East/South)

-- Henderson County Area
-- 28726 - East Flat Rock
-- 28731 - Flat Rock
-- 28742 - Horse Shoe
-- 28759 - Mills River

-- ============================================================
-- NEIGHBORHOODS (Asheville/Hendersonville Area)
-- ============================================================

-- Get NC state ID
SET @nc_state_id = (SELECT id_sta FROM state_sta WHERE state_code_sta = 'NC');

-- ============================================================
-- ASHEVILLE NEIGHBORHOODS
-- ============================================================

INSERT INTO neighborhood_nbh (neighborhood_name_nbh, city_name_nbh, id_sta_nbh, latitude_nbh, longitude_nbh, location_point_nbh) VALUES
    -- Downtown & Central Asheville (28801)
    ('Downtown Asheville', 'Asheville', @nc_state_id, 35.5951, -82.5515, ST_GeomFromText('POINT(-82.5515 35.5951)', 4326)),
    ('South Slope', 'Asheville', @nc_state_id, 35.5912, -82.5540, ST_GeomFromText('POINT(-82.5540 35.5912)', 4326)),
    ('Montford', 'Asheville', @nc_state_id, 35.6050, -82.5580, ST_GeomFromText('POINT(-82.5580 35.6050)', 4326)),

    -- West Asheville (28806)
    ('West Asheville', 'Asheville', @nc_state_id, 35.5851, -82.6146, ST_GeomFromText('POINT(-82.6146 35.5851)', 4326)),
    ('Candler', 'Candler', @nc_state_id, 35.5379, -82.6985, ST_GeomFromText('POINT(-82.6985 35.5379)', 4326)),

    -- North Asheville (28804)
    ('North Asheville', 'Asheville', @nc_state_id, 35.6295, -82.5571, ST_GeomFromText('POINT(-82.5571 35.6295)', 4326)),
    ('Grove Park', 'Asheville', @nc_state_id, 35.6120, -82.5650, ST_GeomFromText('POINT(-82.5650 35.6120)', 4326)),
    ('Beaver Lake', 'Asheville', @nc_state_id, 35.6400, -82.5500, ST_GeomFromText('POINT(-82.5500 35.6400)', 4326)),
    ('Woodfin', 'Woodfin', @nc_state_id, 35.6350, -82.5800, ST_GeomFromText('POINT(-82.5800 35.6350)', 4326)),
    ('Weaverville', 'Weaverville', @nc_state_id, 35.6973, -82.5607, ST_GeomFromText('POINT(-82.5607 35.6973)', 4326)),

    -- South Asheville (28803)
    ('South Asheville', 'Asheville', @nc_state_id, 35.5578, -82.5210, ST_GeomFromText('POINT(-82.5210 35.5578)', 4326)),
    ('Biltmore Village', 'Asheville', @nc_state_id, 35.5700, -82.5450, ST_GeomFromText('POINT(-82.5450 35.5700)', 4326)),
    ('Biltmore Forest', 'Biltmore Forest', @nc_state_id, 35.5350, -82.5300, ST_GeomFromText('POINT(-82.5300 35.5350)', 4326)),
    ('Biltmore Park', 'Asheville', @nc_state_id, 35.5100, -82.5280, ST_GeomFromText('POINT(-82.5280 35.5100)', 4326)),
    ('Arden', 'Arden', @nc_state_id, 35.4661, -82.5345, ST_GeomFromText('POINT(-82.5345 35.4661)', 4326)),
    ('Fletcher', 'Fletcher', @nc_state_id, 35.4300, -82.5000, ST_GeomFromText('POINT(-82.5000 35.4300)', 4326)),

    -- East Asheville (28805)
    ('East Asheville', 'Asheville', @nc_state_id, 35.5708, -82.4865, ST_GeomFromText('POINT(-82.4865 35.5708)', 4326)),
    ('Kenilworth', 'Asheville', @nc_state_id, 35.5750, -82.5200, ST_GeomFromText('POINT(-82.5200 35.5750)', 4326)),
    ('Haw Creek', 'Asheville', @nc_state_id, 35.5850, -82.4700, ST_GeomFromText('POINT(-82.4700 35.5850)', 4326)),
    ('Oakley', 'Asheville', @nc_state_id, 35.5600, -82.4800, ST_GeomFromText('POINT(-82.4800 35.5600)', 4326)),

    -- East of Asheville (28778)
    ('Swannanoa', 'Swannanoa', @nc_state_id, 35.6169, -82.3987, ST_GeomFromText('POINT(-82.3987 35.6169)', 4326)),
    ('Black Mountain', 'Black Mountain', @nc_state_id, 35.6179, -82.3212, ST_GeomFromText('POINT(-82.3212 35.6179)', 4326)),

    -- ============================================================
    -- HENDERSONVILLE / HENDERSON COUNTY NEIGHBORHOODS
    -- ============================================================

    -- Hendersonville (28739, 28791, 28792)
    ('Downtown Hendersonville', 'Hendersonville', @nc_state_id, 35.3185, -82.4612, ST_GeomFromText('POINT(-82.4612 35.3185)', 4326)),
    ('Laurel Park', 'Laurel Park', @nc_state_id, 35.3150, -82.4950, ST_GeomFromText('POINT(-82.4950 35.3150)', 4326)),
    ('Druid Hills', 'Hendersonville', @nc_state_id, 35.3280, -82.4550, ST_GeomFromText('POINT(-82.4550 35.3280)', 4326)),
    ('Fifth Avenue West', 'Hendersonville', @nc_state_id, 35.3200, -82.4700, ST_GeomFromText('POINT(-82.4700 35.3200)', 4326)),

    -- Henderson County Communities
    ('Flat Rock', 'Flat Rock', @nc_state_id, 35.2730, -82.4420, ST_GeomFromText('POINT(-82.4420 35.2730)', 4326)),
    ('East Flat Rock', 'East Flat Rock', @nc_state_id, 35.2850, -82.4100, ST_GeomFromText('POINT(-82.4100 35.2850)', 4326)),
    ('Mills River', 'Mills River', @nc_state_id, 35.3813, -82.5889, ST_GeomFromText('POINT(-82.5889 35.3813)', 4326)),
    ('Horse Shoe', 'Horse Shoe', @nc_state_id, 35.3500, -82.5600, ST_GeomFromText('POINT(-82.5600 35.3500)', 4326)),
    ('Etowah', 'Etowah', @nc_state_id, 35.3200, -82.5900, ST_GeomFromText('POINT(-82.5900 35.3200)', 4326));

-- ============================================================
-- NEIGHBORHOOD-ZIP ASSOCIATIONS
-- ============================================================
-- Maps neighborhoods to their zip codes
-- is_primary indicates the main neighborhood for that zip code

-- Seed zip codes for Asheville and Hendersonville service areas
INSERT INTO zip_code_zpc (zip_code_zpc, latitude_zpc, longitude_zpc, location_point_zpc) VALUES
('28801', 35.595000, -82.556000, ST_GeomFromText('POINT(-82.556000 35.595000)', 4326)),
('28803', 35.549000, -82.522000, ST_GeomFromText('POINT(-82.522000 35.549000)', 4326)),
('28804', 35.637000, -82.558000, ST_GeomFromText('POINT(-82.558000 35.637000)', 4326)),
('28805', 35.595000, -82.502000, ST_GeomFromText('POINT(-82.502000 35.595000)', 4326)),
('28806', 35.584000, -82.608000, ST_GeomFromText('POINT(-82.608000 35.584000)', 4326)),
('28715', 35.537000, -82.681000, ST_GeomFromText('POINT(-82.681000 35.537000)', 4326)),
('28787', 35.719000, -82.547000, ST_GeomFromText('POINT(-82.547000 35.719000)', 4326)),
('28704', 35.473000, -82.519000, ST_GeomFromText('POINT(-82.519000 35.473000)', 4326)),
('28732', 35.422000, -82.500000, ST_GeomFromText('POINT(-82.500000 35.422000)', 4326)),
('28778', 35.603000, -82.409000, ST_GeomFromText('POINT(-82.409000 35.603000)', 4326)),
('28791', 35.350000, -82.494000, ST_GeomFromText('POINT(-82.494000 35.350000)', 4326)),
('28739', 35.319000, -82.488000, ST_GeomFromText('POINT(-82.488000 35.319000)', 4326)),
('28792', 35.337000, -82.449000, ST_GeomFromText('POINT(-82.449000 35.337000)', 4326)),
('28731', 35.272000, -82.420000, ST_GeomFromText('POINT(-82.420000 35.272000)', 4326)),
('28726', 35.282000, -82.418000, ST_GeomFromText('POINT(-82.418000 35.282000)', 4326)),
('28759', 35.302000, -82.581000, ST_GeomFromText('POINT(-82.581000 35.302000)', 4326)),
('28742', 35.361000, -82.574000, ST_GeomFromText('POINT(-82.574000 35.361000)', 4326));

INSERT INTO neighborhood_zip_nbhzpc (id_nbh_nbhzpc, zip_code_nbhzpc, is_primary_nbhzpc) VALUES
    -- Downtown & Central Asheville (28801)
    (1, '28801', TRUE),     -- Downtown Asheville (primary)
    (2, '28801', FALSE),    -- South Slope
    (3, '28801', FALSE),    -- Montford

    -- West Asheville (28806)
    (4, '28806', TRUE),     -- West Asheville (primary)

    -- Candler (28715)
    (5, '28715', TRUE),     -- Candler (primary)

    -- North Asheville (28804)
    (6, '28804', TRUE),     -- North Asheville (primary)
    (7, '28804', FALSE),    -- Grove Park
    (8, '28804', FALSE),    -- Beaver Lake
    (9, '28804', FALSE),    -- Woodfin

    -- Weaverville (28787)
    (10, '28787', TRUE),    -- Weaverville (primary)

    -- South Asheville (28803)
    (11, '28803', TRUE),    -- South Asheville (primary)
    (12, '28803', FALSE),   -- Biltmore Village
    (13, '28803', FALSE),   -- Biltmore Forest
    (14, '28803', FALSE),   -- Biltmore Park

    -- Arden (28704)
    (15, '28704', TRUE),    -- Arden (primary)

    -- Fletcher (28732)
    (16, '28732', TRUE),    -- Fletcher (primary)

    -- East Asheville (28805)
    (17, '28805', TRUE),    -- East Asheville (primary)
    (18, '28805', FALSE),   -- Kenilworth
    (19, '28805', FALSE),   -- Haw Creek
    (20, '28805', FALSE),   -- Oakley

    -- Swannanoa / Black Mountain (28778)
    (21, '28778', TRUE),    -- Swannanoa (primary)
    (22, '28778', FALSE),   -- Black Mountain

    -- Hendersonville (28791 - north/downtown area)
    (23, '28791', TRUE),    -- Downtown Hendersonville (primary)
    (25, '28791', FALSE),   -- Druid Hills

    -- Hendersonville (28739 - west/Laurel Park area)
    (24, '28739', TRUE),    -- Laurel Park (primary)
    (26, '28739', FALSE),   -- Fifth Avenue West

    -- Hendersonville (28792 - east/south area)
    (23, '28792', FALSE),    -- Downtown Hendersonville (also serves 28792)

    -- Flat Rock (28731)
    (27, '28731', TRUE),    -- Flat Rock (primary)

    -- East Flat Rock (28726)
    (28, '28726', TRUE),    -- East Flat Rock (primary)

    -- Mills River (28759)
    (29, '28759', TRUE),    -- Mills River (primary)

    -- Horse Shoe (28742)
    (30, '28742', TRUE),    -- Horse Shoe (primary)
    (31, '28742', FALSE);   -- Etowah

-- ============================================================
-- SAMPLE ACCOUNTS (Asheville/Hendersonville residents)
-- ============================================================

-- Get lookup IDs
SET @member_role = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'member');
SET @admin_role = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'admin');
SET @active_status = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active');
SET @email_pref = (SELECT id_cpr FROM contact_preference_cpr WHERE preference_name_cpr = 'email');

-- Sample users (placeholder hash is replaced by TEST ACCOUNTS SETUP below)
-- Neighborhood IDs: 1=Downtown AVL, 4=West AVL, 6=North AVL, 23=Downtown Hendersonville
INSERT INTO account_acc (
    first_name_acc, last_name_acc, phone_number_acc, email_address_acc,
    street_address_acc, zip_code_acc, id_nbh_acc, password_hash_acc,
    id_rol_acc, id_ast_acc, id_cpr_acc, is_verified_acc, has_consent_acc
) VALUES
    ('Allyson', 'Warren', '828-555-0101', 'allyson.warren@example.com',
     '123 Haywood St', '28801', 1, '$2y$10$abcdefghijklmnopqrstuv',
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Jeremiah', 'Lutz', '828-555-0102', 'jeremiah.lutz@example.com',
     '456 Patton Ave', '28806', 4, '$2y$10$abcdefghijklmnopqrstuv',
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Chantelle', 'Turcotte', '828-555-0103', 'chantelle.turcotte@example.com',
     '789 Main St', '28791', 23, '$2y$10$abcdefghijklmnopqrstuv',
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Alec', 'Fehl', '828-555-0104', 'alec.fehl@example.com',
     '321 Merrimon Ave', '28804', 6, '$2y$10$abcdefghijklmnopqrstuv',
     @member_role, @active_status, @email_pref, TRUE, TRUE),

    ('Admin', 'User', '828-555-9999', 'admin@neighborhoodtools.com',
     NULL, '28801', 1, '$2y$10$abcdefghijklmnopqrstuv',
     @admin_role, @active_status, @email_pref, TRUE, TRUE);

-- ============================================================
-- SAMPLE ACCOUNT BIOS
-- ============================================================

INSERT INTO account_bio_abi (id_acc_abi, bio_text_abi) VALUES
    (1, 'Avid gardener and DIY enthusiast in Downtown Asheville. Love sharing my tools with neighbors!'),
    (2, 'Mountain biker and home renovator in North Asheville. My tools are your tools!'),
    (3, 'Longtime Hendersonville resident and apple orchard volunteer. Happy to lend a hand (or tool)!'),
    (4, 'Woodworking hobbyist in West Asheville. Always working on my next project in the garage.');

-- Account mapping:
-- 1 = Allyson Warren (Downtown Asheville)
-- 2 = Jeremiah Lutz (West Asheville)
-- 3 = Chantelle Turcotte (Hendersonville)
-- 4 = Alec Fehl (North Asheville)
-- 5 = Admin User

-- ============================================================
-- SAMPLE VECTOR IMAGES (Admin uploads)
-- ============================================================

INSERT INTO vector_image_vec (file_name_vec, description_text_vec, id_acc_vec) VALUES
    ('hammer-icon.svg', 'Hammer tool icon', 5),
    ('saw-icon.svg', 'Saw tool icon', 5),
    ('drill-icon.svg', 'Power drill icon', 5),
    ('wrench-icon.svg', 'Wrench tool icon', 5),
    ('gardening-icon.svg', 'Gardening tools icon', 5),
    ('chainsaw-icon.svg', 'Chainsaw icon', 5);

-- ============================================================
-- SAMPLE CATEGORIES
-- ============================================================

INSERT INTO category_cat (category_name_cat, id_vec_cat) VALUES
    ('Hand Tools', 1),
    ('Power Tools', 3),
    ('Gardening', 5),
    ('Woodworking', 2),
    ('Automotive', 4),
    ('Plumbing', NULL),
    ('Electrical', NULL),
    ('Outdoor/Landscaping', 6);

-- ============================================================
-- SAMPLE TOOLS (Local Asheville/Hendersonville inventory)
-- ============================================================

-- Get condition IDs
SET @new_condition = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'new');
SET @good_condition = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'good');
SET @fair_condition = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'fair');

-- Allyson's tools (Downtown Asheville)
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

-- Jeremiah's tools (West Asheville)
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

-- Chantelle's tools (Hendersonville)
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

-- Alec's tools (North Asheville)
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
-- TOOL-CATEGORY ASSOCIATIONS
-- ============================================================

INSERT INTO tool_category_tolcat (id_tol_tolcat, id_cat_tolcat) VALUES
    (1, 2),   -- DeWalt Drill -> Power Tools
    (2, 1),   -- Craftsman Hammer -> Hand Tools
    (3, 2),   -- Stihl Chainsaw -> Power Tools
    (3, 8),   -- Stihl Chainsaw -> Outdoor/Landscaping
    (4, 2),   -- Makita Circular Saw -> Power Tools
    (4, 4),   -- Makita Circular Saw -> Woodworking
    (5, 2),   -- Milwaukee Recip Saw -> Power Tools
    (6, 1),   -- Werner Ladder -> Hand Tools
    (7, 3),   -- Fiskars Loppers -> Gardening
    (8, 3),   -- Corona Hedge Shears -> Gardening
    (9, 3),   -- Apple Picking Pole -> Gardening
    (10, 2),  -- Ryobi Pressure Washer -> Power Tools
    (10, 8),  -- Ryobi Pressure Washer -> Outdoor/Landscaping
    (11, 3),  -- Leaf Blower -> Gardening
    (11, 8);  -- Leaf Blower -> Outdoor/Landscaping

-- ============================================================
-- SAMPLE BORROW TRANSACTION (Tests workflow)
-- ============================================================

-- Get status IDs
SET @requested_status = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'requested');
SET @approved_status = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'approved');
SET @borrowed_status = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'borrowed');
SET @returned_status = (SELECT id_bst FROM borrow_status_bst WHERE status_name_bst = 'returned');

-- Jeremiah (West AVL) borrows Allyson's drill (Downtown AVL) - completed transaction
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor, returned_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    1, 2, @returned_status, 72,
    '2026-01-15 10:00:00', '2026-01-15 12:00:00', '2026-01-15 14:00:00', '2026-01-18 14:00:00',
    'Building shelves in my West Asheville workshop', TRUE
);

-- Chantelle (Hendersonville) borrows Allyson's hammer (Downtown AVL) - currently borrowed
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor, borrowed_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    2, 3, @borrowed_status, 168,
    '2026-01-28 09:00:00', '2026-01-28 10:00:00', '2026-01-28 11:00:00',
    'Hanging pictures in new Hendersonville home', TRUE
);

-- Alec (North AVL) requests to borrow Chantelle's loppers - pending approval
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    notes_text_bor
) VALUES (
    7, 4, @requested_status, 48,
    'Spring cleanup on my mountain property'
);

-- Allyson borrows Jeremiah's ladder - approved, waiting for pickup
INSERT INTO borrow_bor (
    id_tol_bor, id_acc_bor, id_bst_bor, loan_duration_hours_bor,
    requested_at_bor, approved_at_bor,
    notes_text_bor, is_contact_shared_bor
) VALUES (
    6, 1, @approved_status, 48,
    '2026-02-01 08:00:00', '2026-02-01 09:00:00',
    'Need to clean gutters on my downtown building', TRUE
);

-- ============================================================
-- SAMPLE AVAILABILITY BLOCKS
-- ============================================================

SET @admin_block_type = (SELECT id_btp FROM block_type_btp WHERE type_name_btp = 'admin');
SET @borrow_block_type = (SELECT id_btp FROM block_type_btp WHERE type_name_btp = 'borrow');

-- Admin block: Allyson's chainsaw unavailable for maintenance
INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, notes_text_avb
) VALUES (
    3, @admin_block_type, '2026-02-01 00:00:00', '2026-02-15 23:59:59',
    'Chain replacement and engine tune-up at Asheville Outdoor Supply'
);

-- Borrow block: Chantelle's hammer borrow (linked to borrow record)
INSERT INTO availability_block_avb (
    id_tol_avb, id_btp_avb, start_at_avb, end_at_avb, id_bor_avb
) VALUES (
    2, @borrow_block_type, '2026-01-28 11:00:00', '2026-02-04 11:00:00', 2
);

-- ============================================================
-- SAMPLE RATINGS (Tests self-rating prevention, participant validation)
-- ============================================================

SET @lender_role = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'lender');
SET @borrower_role = (SELECT id_rtr FROM rating_role_rtr WHERE role_name_rtr = 'borrower');

-- Jeremiah rates Allyson as lender (for drill borrow)
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES (
    2, 1, 1, @lender_role, 5, 'Allyson was great! Easy pickup downtown and drill worked perfectly.'
);

-- Allyson rates Jeremiah as borrower (for drill borrow)
INSERT INTO user_rating_urt (
    id_acc_urt, id_acc_target_urt, id_bor_urt, id_rtr_urt, score_urt, comment_text_urt
) VALUES (
    1, 2, 1, @borrower_role, 5, 'Jeremiah returned the drill on time and in great shape. Great neighbor!'
);

-- Jeremiah rates the drill
INSERT INTO tool_rating_trt (
    id_acc_trt, id_tol_trt, id_bor_trt, score_trt, comment_text_trt
) VALUES (
    2, 1, 1, 5, 'Excellent drill! Made my workshop shelving project a breeze.'
);

-- ============================================================
-- SAMPLE TERMS OF SERVICE
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

-- ============================================================
-- SAMPLE TOS ACCEPTANCE
-- ============================================================

INSERT INTO tos_acceptance_tac (id_acc_tac, id_tos_tac, ip_address_tac, user_agent_tac) VALUES
    (1, 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
    (2, 1, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
    (3, 1, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)'),
    (4, 1, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
    (5, 1, '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

-- ============================================================
-- SAMPLE NOTIFICATIONS
-- ============================================================

SET @request_type = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'request');
SET @approval_type = (SELECT id_ntt FROM notification_type_ntt WHERE type_name_ntt = 'approval');

INSERT INTO notification_ntf (id_acc_ntf, id_ntt_ntf, title_ntf, body_ntf, id_bor_ntf, is_read_ntf) VALUES
    (3, @request_type, 'New Borrow Request', 'Alec from North Asheville has requested to borrow your Fiskars Loppers.', 3, FALSE),
    (2, @approval_type, 'Request Approved', 'Allyson has approved your request for the DeWalt Drill.', 1, TRUE),
    (3, @approval_type, 'Request Approved', 'Allyson has approved your request for the Craftsman Hammer.', 2, TRUE),
    (1, @approval_type, 'Request Approved', 'Jeremiah has approved your request for the Werner Extension Ladder.', 4, FALSE);

-- ============================================================
-- SAMPLE BOOKMARKS
-- ============================================================

INSERT INTO tool_bookmark_acctol (id_acc_acctol, id_tol_acctol) VALUES
    (2, 3),   -- Jeremiah bookmarks Allyson's chainsaw
    (3, 4),   -- Chantelle bookmarks Jeremiah's circular saw
    (3, 5),   -- Chantelle bookmarks Jeremiah's reciprocating saw
    (4, 10),  -- Alec bookmarks his own pressure washer (for easy access)
    (1, 9);   -- Allyson bookmarks Chantelle's apple picker

-- ============================================================
-- SAMPLE EVENTS (Asheville / Hendersonville area)
-- ============================================================
-- Neighborhood IDs: 1=Downtown AVL, 4=West AVL, 6=North AVL,
--   8=Beaver Lake, 11=South AVL, 23=Downtown Hendersonville
-- Account 5 = Admin User (event creator)

INSERT INTO event_evt (event_name_evt, event_description_evt, start_at_evt, end_at_evt, id_nbh_evt, id_acc_evt) VALUES
    ('Spring Tool Swap Meet',
     'Bring tools you no longer need and swap with your neighbors! Tables provided in Pack Square Park. All hand tools, power tools, and gardening equipment welcome.',
     '2026-03-15 10:00:00', '2026-03-15 14:00:00', 1, 5),

    ('Community Garden Workday',
     'Help build raised beds at the West Asheville Community Garden on Haywood Rd. Tools and materials provided. All skill levels welcome!',
     '2026-03-08 09:00:00', '2026-03-08 13:00:00', 4, 5),

    ('DIY Home Repair Workshop',
     'Free workshop covering basic plumbing, electrical, and drywall repair. Hosted at the Henderson County Library. Bring a notepad!',
     '2026-03-22 13:00:00', '2026-03-22 16:00:00', 23, 5),

    ('Mountain Trail Cleanup Day',
     'Join neighbors to clear storm debris from trails around Beaver Lake. Bring loppers, work gloves, and sturdy boots. Coffee and donuts provided.',
     '2026-01-25 08:00:00', '2026-01-25 12:00:00', 8, 5),

    ('Tool Safety & Maintenance Class',
     'Learn proper chainsaw, circular saw, and power tool safety. Certification cards available for participants who complete the hands-on module. Hendersonville Fire Station #2.',
     '2026-04-05 10:00:00', '2026-04-05 15:00:00', 23, 5);

-- ============================================================
-- SAMPLE EVENT METADATA
-- ============================================================

INSERT INTO event_meta_evm (id_evt_evm, meta_key_evm, meta_value_evm) VALUES
    (1, 'location', 'Pack Square Park, Downtown Asheville'),
    (1, 'max_capacity', '50'),
    (1, 'contact_email', 'events@neighborhoodtools.com'),
    (2, 'location', 'West Asheville Community Garden, Haywood Rd'),
    (2, 'max_capacity', '30'),
    (3, 'location', 'Henderson County Public Library, Main St'),
    (3, 'max_capacity', '40'),
    (4, 'location', 'Beaver Lake Trail Parking Lot, North Asheville'),
    (4, 'max_capacity', '25'),
    (5, 'location', 'Hendersonville Fire Station #2, Spartanburg Hwy'),
    (5, 'max_capacity', '20'),
    (5, 'contact_email', 'safety@neighborhoodtools.com');

-- ============================================================
-- SAMPLE DISPUTES & MESSAGES
-- ============================================================
-- Dispute on borrow 1 (Jeremiah returned Allyson's drill)
-- Allyson reports minor scratches found after return

SET @open_dispute_status = (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'open');
SET @initial_report_msg = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'initial_report');
SET @response_msg = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'response');
SET @admin_note_msg = (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'admin_note');

INSERT INTO dispute_dsp (
    id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp, created_at_dsp
) VALUES (
    1, 1, 'Minor scratches found on DeWalt Drill after return',
    @open_dispute_status, '2026-01-19 10:00:00'
);

INSERT INTO dispute_message_dsm (id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm, created_at_dsm) VALUES
    (1, 1, @initial_report_msg,
     'I noticed some scratches on the drill chuck after Jeremiah returned it. These weren''t there before the loan. I have photos from the handover that show the condition before and after.',
     FALSE, '2026-01-19 10:00:00'),

    (1, 2, @response_msg,
     'The scratches were already there when I picked it up. I was very careful with the drill and only used it for shelving work. Happy to discuss in person at the West Asheville farmer''s market.',
     FALSE, '2026-01-19 14:30:00'),

    (1, 5, @admin_note_msg,
     'Reviewed handover condition notes from both pickup and return. Pickup notes say "excellent condition" but no photo evidence was uploaded at pickup. Suggesting mediation between both parties.',
     TRUE, '2026-01-20 09:00:00');

-- ============================================================
-- SAMPLE BORROW WAIVERS
-- ============================================================
-- All 3 acknowledgment booleans must be TRUE (enforced by trigger)

SET @borrow_waiver_type = (SELECT id_wtp FROM waiver_type_wtp WHERE type_name_wtp = 'borrow_waiver');

-- Borrow 1: Jeremiah signed waiver before picking up Allyson's drill
-- Borrow 2: Chantelle signed waiver before picking up Allyson's hammer
INSERT INTO borrow_waiver_bwv (
    id_bor_bwv, id_wtp_bwv, id_acc_bwv,
    is_tool_condition_acknowledged_bwv, preexisting_conditions_noted_bwv,
    is_responsibility_accepted_bwv, is_liability_waiver_accepted_bwv,
    is_insurance_reminder_shown_bwv, ip_address_bwv, user_agent_bwv, signed_at_bwv
) VALUES
    (1, @borrow_waiver_type, 2,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '2026-01-15 13:45:00'),

    (2, @borrow_waiver_type, 3,
     TRUE, NULL,
     TRUE, TRUE,
     TRUE, '192.168.1.102', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)', '2026-01-28 10:30:00');

-- ============================================================
-- POPULATE SUMMARY TABLES
-- ============================================================
-- Refresh all materialized summary tables with the sample data

CALL sp_refresh_all_summaries();

-- ============================================================
-- TEST ACCOUNTS SETUP
-- ============================================================
-- Password for ALL test accounts: password123

SET @test_password_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';

-- Get additional lookup IDs for test accounts
SET @super_admin_role = (SELECT id_rol FROM role_rol WHERE role_name_rol = 'super_admin');
SET @pending_status = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'pending');

-- Update all existing accounts to use test password
UPDATE account_acc
SET password_hash_acc = @test_password_hash
WHERE deleted_at_acc IS NULL AND id_acc > 0;

-- Create Super Admin (if not exists)
INSERT INTO account_acc (
    first_name_acc, last_name_acc, phone_number_acc, email_address_acc,
    street_address_acc, zip_code_acc, id_nbh_acc, password_hash_acc,
    id_rol_acc, id_ast_acc, id_cpr_acc, is_verified_acc, has_consent_acc
)
SELECT
    'Jeremy', 'Warren', '828-555-0001', 'jeremywarren@neighborhoodtools.com',
    '100 Admin Way', '28791', 1, @test_password_hash,
    @super_admin_role, @active_status, @email_pref, TRUE, TRUE
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM account_acc WHERE email_address_acc = 'jeremywarren@neighborhoodtools.com'
);

-- Create Pending User (for testing pending approval flow)
INSERT INTO account_acc (
    first_name_acc, last_name_acc, phone_number_acc, email_address_acc,
    street_address_acc, zip_code_acc, id_nbh_acc, password_hash_acc,
    id_rol_acc, id_ast_acc, id_cpr_acc, is_verified_acc, has_consent_acc
)
SELECT
    'Pending', 'User', '828-555-0105', 'pending@test.com',
    '999 Waiting Lane', '28801', 1, @test_password_hash,
    @member_role, @pending_status, @email_pref, FALSE, TRUE
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM account_acc WHERE email_address_acc = 'pending@test.com'
);

-- ============================================================
-- COMMIT TRANSACTION
-- ============================================================

COMMIT;

-- ============================================================
-- VERIFICATION QUERY
-- ============================================================

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

-- ================================================================
-- ================================================================
--                  END SEED DATA FOR TESTING
-- ================================================================
-- ================================================================
