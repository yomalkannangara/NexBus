-- Migration: Create sltb_assignment_overrides audit table

CREATE TABLE `sltb_assignment_overrides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `shift` enum('Morning','Evening','Night') DEFAULT 'Morning',
  `bus_reg_no` varchar(20) NOT NULL,
  `previous_bus_reg_no` varchar(20) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `conductor_id` int(11) DEFAULT NULL,
  `override_remark` text DEFAULT NULL,
  `overridden_by` int(11) DEFAULT NULL,
  `override_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sltb_assignment_overrides`
  ADD INDEX `idx_overridden_by` (`overridden_by`),
  ADD INDEX `idx_assignment` (`assignment_id`);

-- Optional FK constraints (uncomment to enable):
-- ALTER TABLE `sltb_assignment_overrides`
--   ADD CONSTRAINT `fk_aov_overridden_by` FOREIGN KEY (`overridden_by`) REFERENCES `users`(`user_id`),
--   ADD CONSTRAINT `fk_aov_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `sltb_assignments`(`assignment_id`);
