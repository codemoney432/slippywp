-- Complete Update Script for Existing Databases
-- Run this if you have an existing database and need to update it
-- This script is safe to run multiple times (uses IF EXISTS/IF NOT EXISTS where possible)
-- Table prefix: slippy_
-- To change the prefix, update all table names in this file and set DB_TABLE_PREFIX in config/database.php

USE slippy_db;

-- Step 1: Add location_type column if it doesn't exist
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'slippy_db' 
    AND TABLE_NAME = 'slippy_reports' 
    AND COLUMN_NAME = 'location_type'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE slippy_reports ADD COLUMN location_type ENUM(''road'', ''sidewalk'') NOT NULL DEFAULT ''road'' AFTER condition_type, ADD INDEX idx_location_type (location_type)',
    'SELECT ''location_type column already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Update condition_type from ponding_flooding to water if needed
UPDATE slippy_reports SET condition_type = 'water' WHERE condition_type = 'ponding_flooding';

-- Step 3: Modify condition_type ENUM to include 'water' and remove 'ponding_flooding' if it exists
ALTER TABLE slippy_reports MODIFY COLUMN condition_type ENUM('ice', 'slush', 'snow', 'water') NOT NULL;

-- Step 4: Create votes table if it doesn't exist
CREATE TABLE IF NOT EXISTS slippy_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    vote_type ENUM('up', 'down') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_vote_type (vote_type),
    UNIQUE KEY unique_vote (report_id, ip_address),
    FOREIGN KEY (report_id) REFERENCES slippy_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 5: Create comments table if it doesn't exist
CREATE TABLE IF NOT EXISTS slippy_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_created_at (created_at DESC),
    FOREIGN KEY (report_id) REFERENCES slippy_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 6: Update submitter_name length to 25 characters
ALTER TABLE slippy_reports MODIFY COLUMN submitter_name VARCHAR(25) DEFAULT NULL;

SELECT 'Database update complete!' AS status;

