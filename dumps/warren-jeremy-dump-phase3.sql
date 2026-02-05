-- ============================================================
-- NeigborhoodTools Database Dump File
-- ============================================================
-- Author: Jeremy Warren
-- Course: WEB-289 Capstone Project
-- Database: MySQL 8.0.16 or later
-- File: warren-jeremy-dump-phase3.sql
-- Description: Database Creation
-- ============================================================

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

-- Materialized version of user_reputation_v

-- Materialized version of tool_statistics_view

-- Materialized version of category_summary_v

-- Daily platform-wide statistics for admin dashboard

-- ============================================================
-- RESTORE SESSION SETTINGS
-- ============================================================

SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
