-- Migration script to add status column to comments table
-- Run this SQL script on your database to add the status column
-- Replace {TABLE_PREFIX} with your actual table prefix (e.g., 'slippy_')

-- Add status column (pending, approved, rejected)
ALTER TABLE `slippy_comments` 
ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `comment_text`;

-- Add index for faster lookups of pending comments
CREATE INDEX `idx_status` ON `slippy_comments` (`status`);

-- Note: Existing comments will be set to 'pending' status
-- You may want to manually approve existing comments:
-- UPDATE `slippy_comments` SET status = 'approved' WHERE status = 'pending';

