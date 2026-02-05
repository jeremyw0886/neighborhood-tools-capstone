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

-- Neighborhoods Table

-- Neighborhood Metadata Table

-- Neighborhood-ZIP junction Table

-- Main Account Table

-- Account Meta Table

-- Account Image Table

-- Account Bio Table

-- Vector Images Table

-- Categories Table

-- Main Tool Table

-- Tool Metadata Table

-- Tool Images Table

-- Tool-Category Junction Table

-- Tool Bookmarks Junction Table

-- Borrow Transactions Table

-- Availability Blocks Table

-- User Ratings Table

-- Tool Ratings Table

-- Disputes Header Table

-- Dispute Messages Table

-- Notifications Table

-- Search Analytics Table

-- Events Table

-- phpBB Integration Table

-- Audit Logging Table

-- Terms of Service Table

-- ToS Acceptance Table

-- Borrow Waivers Table

-- Handover Verification Table

-- Incident Reporting Table

-- Incident Photos Table

-- Loan Extensions Table

-- Security Deposit Table

-- Payment Transactions Table

-- Payment Transaction Metadata Table

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
