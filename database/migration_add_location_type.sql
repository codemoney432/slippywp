-- Migration: Add location_type column to existing reports table
-- Run this if you already have a database with reports
-- Table prefix: slippy_

USE slippy_db;

ALTER TABLE slippy_reports 
ADD COLUMN location_type ENUM('road', 'sidewalk') NOT NULL DEFAULT 'road' 
AFTER condition_type;

ALTER TABLE slippy_reports 
ADD INDEX idx_location_type (location_type);






