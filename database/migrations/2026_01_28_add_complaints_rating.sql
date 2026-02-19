-- Add optional rating to complaints (1-5)
-- Safe to run once.

ALTER TABLE `complaints`
  ADD COLUMN `rating` TINYINT UNSIGNED NULL DEFAULT NULL
  AFTER `description`;

-- Optional (MariaDB/MySQL 8+ enforces CHECK; older versions may ignore it)
-- ALTER TABLE `complaints`
--   ADD CONSTRAINT `chk_complaints_rating_range` CHECK (`rating` IS NULL OR (`rating` BETWEEN 1 AND 5));
