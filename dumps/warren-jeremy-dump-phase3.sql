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

-- account_tatus_ast

-- contact_preference_cpr

-- state_sta

-- tool_condition_tcd

-- borrow_status_bst

-- block_type_btp

-- rating_role_rtr

-- dispute_status_dst

-- dispute_message_type_dmt

-- notification_type_ntt

-- waiver_type_wtp

-- handover_type_hot

-- incident_type_ity

-- deposit_status_dps

-- payment_provider_ppv

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
