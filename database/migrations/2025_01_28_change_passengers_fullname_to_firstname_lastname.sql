-- Migration: Change passengers table from full_name to first_name and last_name
-- Date: 2025-01-28
-- Description: Align passengers table schema with users table to have first_name and last_name instead of full_name

-- Step 1: Add new columns
ALTER TABLE `passengers` 
ADD COLUMN `first_name` VARCHAR(255) NULL DEFAULT NULL AFTER `passenger_id`,
ADD COLUMN `last_name` VARCHAR(255) NULL DEFAULT NULL AFTER `first_name`;

-- Step 2: Backfill data from full_name (split at space or use full_name as first_name)
UPDATE `passengers` 
SET `first_name` = SUBSTRING_INDEX(`full_name`, ' ', 1),
    `last_name` = IF(LOCATE(' ', `full_name`) > 0, SUBSTRING(`full_name`, LOCATE(' ', `full_name`) + 1), '');

-- Step 3: Drop the old full_name column
ALTER TABLE `passengers` 
DROP COLUMN `full_name`;

-- Optional: Create an index on first_name for better query performance
ALTER TABLE `passengers` 
ADD INDEX `idx_first_name` (`first_name`);
