-- Create zip codes table for fast local lookups
-- This table stores US zip codes with their geographic coordinates

CREATE TABLE IF NOT EXISTS `{TABLE_PREFIX}zip_codes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `zip_code` VARCHAR(10) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(2) DEFAULT NULL,
    `state_name` VARCHAR(50) DEFAULT NULL,
    `county` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `zip_code` (`zip_code`),
    KEY `idx_zip_code` (`zip_code`),
    KEY `idx_lat_lng` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

