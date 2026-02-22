-- Migration: Add arrival_depot_id and completed_by to sltb_trips
-- Purpose: Enforce Option B business rule (only the timekeeper at the
-- route's designated ending depot may complete a trip). This migration
-- only adds storage columns; the enforcement logic is implemented in
-- the PHP model (TurnModel::complete).


-- Add columns to record which depot closed the trip and which user completed it
ALTER TABLE `sltb_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`,
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

-- Optional: add indexes for faster lookups
ALTER TABLE `sltb_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`),
  ADD INDEX `idx_cancelled_by` (`cancelled_by`);

-- Optional foreign keys (uncomment if you want referential integrity enforced):
-- ALTER TABLE `sltb_trips`
--   ADD CONSTRAINT `fk_sltbtrips_arrival_depot` FOREIGN KEY (`arrival_depot_id`) REFERENCES `sltb_depots`(`sltb_depot_id`),
--   ADD CONSTRAINT `fk_sltbtrips_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users`(`user_id`);

-- Notes:
-- The application enforces that the depot completing the trip must match
-- the route's last stop mapped to an SLTB depot (matched by depot `code` or name).
-- If your deployment cannot map route stops to depot codes, adjust the
-- application logic or populate `sltb_depots.code` accordingly.
