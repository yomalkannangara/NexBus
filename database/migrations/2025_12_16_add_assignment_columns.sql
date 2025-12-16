-- ============================================
-- Fleet Assignment Migration
-- ============================================
-- Description: Add driver_id and conductor_id columns to private_buses table
-- Date: 2025-12-16
-- Instructions: Run this SQL in phpMyAdmin or MySQL command line
-- ============================================

-- Add driver_id column
ALTER TABLE `private_buses` 
ADD COLUMN `driver_id` INT(11) NULL DEFAULT NULL 
COMMENT 'Foreign key to private_drivers.private_driver_id' 
AFTER `status`;

-- Add conductor_id column
ALTER TABLE `private_buses` 
ADD COLUMN `conductor_id` INT(11) NULL DEFAULT NULL 
COMMENT 'Foreign key to private_conductors.private_conductor_id' 
AFTER `driver_id`;

-- Create indexes for better query performance
CREATE INDEX `idx_driver_id` ON `private_buses` (`driver_id`);
CREATE INDEX `idx_conductor_id` ON `private_buses` (`conductor_id`);

-- Optional: Add foreign key constraints for referential integrity
-- Uncomment these if you want database-level enforcement
/*
ALTER TABLE `private_buses` 
ADD CONSTRAINT `fk_private_bus_driver` 
    FOREIGN KEY (`driver_id`) 
    REFERENCES `private_drivers`(`private_driver_id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

ALTER TABLE `private_buses` 
ADD CONSTRAINT `fk_private_bus_conductor` 
    FOREIGN KEY (`conductor_id`) 
    REFERENCES `private_conductors`(`private_conductor_id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;
*/
