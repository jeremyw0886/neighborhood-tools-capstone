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
    message_text_ntf TEXT NOT NULL,
    id_bor_ntf INT,
    is_read_ntf BOOLEAN NOT NULL DEFAULT FALSE,
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

-- available_tool_v: Tools truly available for borrowing

-- active_borrow_v: Currently checked-out items

-- overdue_borrow_v: Past-due items requiring attention

-- pending_request_v: Borrow requests awaiting approval

-- ============================================================
-- Profile/Detail Views
-- ============================================================

-- account_profile_v: Complete member profile with all related data

-- tool_detail_v: Full tool listing information

-- ============================================================
-- Analytics/Reporting Views
-- ============================================================

-- user_reputation_v: Aggregated user ratings by role

-- tool_statistics_v: Tool ratings and borrow counts

-- neighborhood_summary_v: Community statistics

-- ============================================================
-- Admin Views
-- ============================================================

-- open_dispute_v: Unresolved disputes requiring attention

-- pending_deposit_v: Deposits held in escrow requiring action

-- ============================================================
-- Legal & Compliance Views
-- ============================================================

-- current_tos_v: Currently active Terms of Service version

-- tos_acceptance_required_v: Active users who need to accept the current ToS

-- pending_waiver_v: Approved borrows missing signed waivers (compliance gate)

-- open_incident_v: Unresolved incident reports requiring admin action

-- pending_handover_v: Verification codes generated but not yet confirmed

-- ============================================================
-- User Interaction Views
-- ============================================================

-- unread_notification_v: User notification feed (unread items)

-- user_bookmarks_v: User's saved tools with current availability status

-- ============================================================
-- Analytics/Categorization Views
-- ============================================================

-- category_summary_v: Tool counts and statistics by category

-- ============================================================
-- Future Expansion Views
-- ============================================================

-- upcoming_event_v: Community events for discovery and engagement

-- ============================================================
-- Convenience Views for Summary Tables (Fast Views)
-- ============================================================
-- These views provide a simple interface to the materialized summary tables.
-- Used for dashboard queries when near-real-time data is acceptable.

-- ================================================================
-- ================================================================
--                          END OF VIEWS
-- ================================================================
-- ================================================================
