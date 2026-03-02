-- Add suspend_reason column to sltb_drivers table
ALTER TABLE `sltb_drivers` ADD COLUMN `suspend_reason` TEXT NULL AFTER `status`;

-- Add suspend_reason column to sltb_conductors table
ALTER TABLE `sltb_conductors` ADD COLUMN `suspend_reason` TEXT NULL AFTER `status`;
