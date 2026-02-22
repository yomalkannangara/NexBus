-- Migration: Add override fields to sltb_assignments
-- Adds a remark and metadata when an assignment is overridden

ALTER TABLE `sltb_assignments`
  ADD COLUMN `override_remark` TEXT DEFAULT NULL AFTER `sltb_depot_id`,
  ADD COLUMN `overridden_by` int(11) DEFAULT NULL AFTER `override_remark`,
  ADD COLUMN `override_at` datetime DEFAULT NULL AFTER `overridden_by`;

ALTER TABLE `sltb_assignments`
  ADD INDEX `idx_overridden_by` (`overridden_by`);

-- Note: Run this migration on your DB (e.g., via mysql cli) to apply schema changes.
