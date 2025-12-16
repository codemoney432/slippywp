-- Migration: Add votes and comments tables
-- Table prefix: slippy_

USE slippy_db;

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
    -- Prevent duplicate votes from same IP (optional - can be removed if you want multiple votes)
    UNIQUE KEY unique_vote (report_id, ip_address)
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






