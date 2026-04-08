-- Add comprehensive bus details to sltb_buses table for Fleet Management enhancement
-- Date: 2026-04-08

ALTER TABLE `sltb_buses` 
ADD COLUMN `bus_model` VARCHAR(100) DEFAULT NULL COMMENT 'e.g., Ashok Leyland Viking, Tata LPO 1623' AFTER `chassis_no`,
ADD COLUMN `year_manufacture` YEAR DEFAULT NULL COMMENT 'Year the bus was manufactured' AFTER `bus_model`,
ADD COLUMN `manufacture_date` DATE DEFAULT NULL COMMENT 'Exact date of manufacture' AFTER `year_manufacture`,
ADD COLUMN `bus_class` ENUM('Normal', 'Semi Luxury', 'Luxury') DEFAULT 'Normal' COMMENT 'Bus service class' AFTER `manufacture_date`;

-- Create index for filtering by bus_class and year_manufacture for performance
CREATE INDEX `idx_bus_class` ON `sltb_buses` (`bus_class`);
CREATE INDEX `idx_year_manufacture` ON `sltb_buses` (`year_manufacture`);
