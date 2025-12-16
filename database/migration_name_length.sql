-- Migration: Change submitter_name from VARCHAR(255) to VARCHAR(25)
-- Run this if you already have a database with reports
-- Table prefix: slippy_

USE slippy_db;

ALTER TABLE slippy_reports 
MODIFY COLUMN submitter_name VARCHAR(25) DEFAULT NULL;






