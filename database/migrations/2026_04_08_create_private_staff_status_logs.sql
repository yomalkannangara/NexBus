-- ============================================================
-- Migration: 2026_04_08_create_private_staff_status_logs
-- Tracks the full suspension / reactivation history for
-- private drivers and conductors.
-- ============================================================

CREATE TABLE IF NOT EXISTS `private_staff_status_logs` (
  `log_id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `staff_type`       ENUM('driver','conductor') NOT NULL,
  `staff_id`         INT(11)      NOT NULL,
  `old_status`       VARCHAR(30)  NOT NULL DEFAULT 'Active',
  `new_status`       VARCHAR(30)  NOT NULL,
  `reason`           TEXT         NULL,
  `changed_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_staff` (`staff_type`, `staff_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
