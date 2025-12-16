-- Migration: Change ponding_flooding to water
-- Run this if you already have a database with reports
-- Table prefix: slippy_

USE slippy_db;

-- Update existing records
UPDATE slippy_reports SET condition_type = 'water' WHERE condition_type = 'ponding_flooding';

-- Modify the ENUM to remove ponding_flooding and add water
ALTER TABLE slippy_reports MODIFY COLUMN condition_type ENUM('ice', 'slush', 'snow', 'water') NOT NULL;






