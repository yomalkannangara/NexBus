-- Replace old point-based assignment with a shared stop location field
-- for both SLTB and Private timekeepers.

ALTER TABLE `users`
  DROP COLUMN IF EXISTS `timekeeper_point`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `timekeeper_location`
    varchar(120) DEFAULT NULL
    COMMENT 'Route stop name used to filter timekeeper schedules';

UPDATE `users`
SET `timekeeper_location` = 'Common'
WHERE `role` IN ('SLTBTimekeeper','PrivateTimekeeper')
  AND (`timekeeper_location` IS NULL OR TRIM(`timekeeper_location`) = '');