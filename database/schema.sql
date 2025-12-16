-- Slippy Road Conditions Database Schema
-- Table prefix: slippy_
-- To change the prefix, update all table names in this file and set DB_TABLE_PREFIX in config/database.php

CREATE DATABASE IF NOT EXISTS slippy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE slippy_db;

-- Table for storing road condition reports
CREATE TABLE IF NOT EXISTS slippy_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    condition_type ENUM('ice', 'slush', 'snow', 'water') NOT NULL,
    location_type ENUM('road', 'sidewalk') NOT NULL DEFAULT 'road',
    submitter_name VARCHAR(25) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location (latitude, longitude),
    INDEX idx_created_at (created_at DESC),
    INDEX idx_location_type (location_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing votes on reports
CREATE TABLE IF NOT EXISTS slippy_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    vote_type ENUM('up', 'down') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL, -- IPv4 or IPv6
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_vote_type (vote_type),
    UNIQUE KEY unique_vote (report_id, ip_address),
    FOREIGN KEY (report_id) REFERENCES slippy_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing comments on reports
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

