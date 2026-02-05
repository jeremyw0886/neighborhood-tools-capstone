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
