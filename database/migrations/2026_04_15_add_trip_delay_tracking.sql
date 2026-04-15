-- Add delayed trip lifecycle support for SLTB and private trips.
-- This migration:
-- 1) Ensures status enum includes 'Delayed'
-- 2) Adds exact delay storage for start/end in seconds
-- 3) Backfills delay values/status for existing rows

ALTER TABLE `sltb_trips`
  MODIFY COLUMN `status` enum('Planned','InProgress','Completed','Cancelled','Delayed') DEFAULT 'Planned',
  ADD COLUMN `start_delay_seconds` int(10) unsigned NOT NULL DEFAULT 0 AFTER `departure_time`,
  ADD COLUMN `end_delay_seconds` int(10) unsigned NOT NULL DEFAULT 0 AFTER `arrival_time`;

ALTER TABLE `private_trips`
  MODIFY COLUMN `status` enum('Planned','InProgress','Completed','Cancelled','Delayed') DEFAULT 'Planned',
  ADD COLUMN `start_delay_seconds` int(10) unsigned NOT NULL DEFAULT 0 AFTER `departure_time`,
  ADD COLUMN `end_delay_seconds` int(10) unsigned NOT NULL DEFAULT 0 AFTER `arrival_time`;

UPDATE `sltb_trips`
SET
  `start_delay_seconds` = CASE
    WHEN `scheduled_departure_time` IS NULL OR `departure_time` IS NULL THEN 0
    WHEN `departure_time` > `scheduled_departure_time`
      THEN TIME_TO_SEC(TIMEDIFF(`departure_time`, `scheduled_departure_time`))
    ELSE 0
  END,
  `end_delay_seconds` = CASE
    WHEN `scheduled_arrival_time` IS NULL OR `arrival_time` IS NULL THEN 0
    WHEN `arrival_time` > `scheduled_arrival_time`
      THEN TIME_TO_SEC(TIMEDIFF(`arrival_time`, `scheduled_arrival_time`))
    ELSE 0
  END;

UPDATE `private_trips`
SET
  `start_delay_seconds` = CASE
    WHEN `scheduled_departure_time` IS NULL OR `departure_time` IS NULL THEN 0
    WHEN `departure_time` > `scheduled_departure_time`
      THEN TIME_TO_SEC(TIMEDIFF(`departure_time`, `scheduled_departure_time`))
    ELSE 0
  END,
  `end_delay_seconds` = CASE
    WHEN `scheduled_arrival_time` IS NULL OR `arrival_time` IS NULL THEN 0
    WHEN `arrival_time` > `scheduled_arrival_time`
      THEN TIME_TO_SEC(TIMEDIFF(`arrival_time`, `scheduled_arrival_time`))
    ELSE 0
  END;

UPDATE `sltb_trips`
SET `status` = 'Delayed'
WHERE `status` IN ('InProgress', 'Completed')
  AND (`start_delay_seconds` > 0 OR `end_delay_seconds` > 0);

UPDATE `private_trips`
SET `status` = 'Delayed'
WHERE `status` IN ('InProgress', 'Completed')
  AND (`start_delay_seconds` > 0 OR `end_delay_seconds` > 0);
