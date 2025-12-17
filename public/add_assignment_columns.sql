-- Migration: Add driver_id and conductor_id columns to private_buses table
-- Run this SQL in your database to enable driver/conductor assignment functionality

ALTER TABLE `private_buses` 
ADD COLUMN `driver_id` INT NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `conductor_id` INT NULL DEFAULT NULL AFTER `driver_id`;

-- Optional: Add foreign key constraints for data integrity
-- ALTER TABLE `private_buses` 
-- ADD CONSTRAINT `fk_bus_driver` 
--     FOREIGN KEY (`driver_id`) REFERENCES `private_drivers`(`private_driver_id`) 
--     ON DELETE SET NULL ON UPDATE CASCADE;

-- ALTER TABLE `private_buses` 
-- ADD CONSTRAINT `fk_bus_conductor` 
--     FOREIGN KEY (`conductor_id`) REFERENCES `private_conductors`(`private_conductor_id`) 
--     ON DELETE SET NULL ON UPDATE CASCADE;
