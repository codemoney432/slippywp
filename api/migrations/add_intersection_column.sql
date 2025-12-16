-- Migration script to add intersection column to reports table
-- Run this SQL script on your database to add the intersection column

-- Add intersection column (nullable, VARCHAR(255) should be enough for most intersections)
ALTER TABLE `{TABLE_PREFIX}reports` 
ADD COLUMN `intersection` VARCHAR(255) NULL DEFAULT NULL AFTER `submitter_name`;

-- Optional: Add index for faster lookups (if you plan to search by intersection)
-- CREATE INDEX `idx_intersection` ON `{TABLE_PREFIX}reports` (`intersection`);

-- Note: Existing reports will have NULL intersection values
-- You can optionally backfill them by running a script that calls fetchIntersection()
-- for each report with NULL intersection, but this is not required for new reports


