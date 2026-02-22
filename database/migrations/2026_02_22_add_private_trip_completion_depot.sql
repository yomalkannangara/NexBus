-- Migration: Add arrival_depot_id and completed_by to private_trips
-- Purpose: Enforce Option B business rule (when possible use route end depot to validate completion).

ALTER TABLE `private_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`,
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

ALTER TABLE `private_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`),
  ADD INDEX `idx_cancelled_by` (`cancelled_by`);

-- Optional foreign keys (uncomment to enable referential integrity):
-- ALTER TABLE `private_trips`
--   ADD CONSTRAINT `fk_ptrips_arrival_depot` FOREIGN KEY (`arrival_depot_id`) REFERENCES `sltb_depots`(`sltb_depot_id`),
--   ADD CONSTRAINT `fk_ptrips_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users`(`user_id`);

-- Notes:
-- - The application will attempt to map a route's last stop token to `sltb_depots.code` or `sltb_depots.name`.
-- - If mapping succeeds, completion is restricted to users at that depot; otherwise the operator's timekeeper may complete.
