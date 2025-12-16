-- Verification Script for Slippy Database
-- Run this to check if your database schema is correct
-- Table prefix: slippy_

USE slippy_db;

-- Check if all tables exist
SELECT 'Checking tables...' AS status;
SELECT 
    TABLE_NAME,
    CASE 
        WHEN TABLE_NAME IN ('slippy_reports', 'slippy_votes', 'slippy_comments') THEN '✓ Exists'
        ELSE '✗ Missing'
    END AS status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND TABLE_NAME IN ('slippy_reports', 'slippy_votes', 'slippy_comments');

-- Check reports table structure
SELECT 'Checking reports table structure...' AS status;
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND TABLE_NAME = 'slippy_reports'
ORDER BY ORDINAL_POSITION;

-- Check if condition_type ENUM is correct
SELECT 'Checking condition_type ENUM...' AS status;
SELECT 
    COLUMN_TYPE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND TABLE_NAME = 'slippy_reports' 
AND COLUMN_NAME = 'condition_type';

-- Check if location_type ENUM is correct
SELECT 'Checking location_type ENUM...' AS status;
SELECT 
    COLUMN_TYPE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND TABLE_NAME = 'slippy_reports' 
AND COLUMN_NAME = 'location_type';

-- Check indexes
SELECT 'Checking indexes...' AS status;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND TABLE_NAME IN ('slippy_reports', 'slippy_votes', 'slippy_comments')
ORDER BY TABLE_NAME, INDEX_NAME;

-- Check foreign keys
SELECT 'Checking foreign keys...' AS status;
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'slippy_db' 
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- Count records in each table
SELECT 'Record counts...' AS status;
SELECT 'slippy_reports' AS table_name, COUNT(*) AS record_count FROM slippy_reports
UNION ALL
SELECT 'slippy_votes', COUNT(*) FROM slippy_votes
UNION ALL
SELECT 'slippy_comments', COUNT(*) FROM slippy_comments;






