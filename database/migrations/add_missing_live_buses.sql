-- ============================================================
-- Missing / unregistered bus entries
-- Generated: 2026-02-25
--
-- HOW TO USE:
-- 1. Run this file to add buses known to be missing from the DB.
-- 2. For buses appearing LIVE (from the external API) but not in DB,
--    visit /api/buses/missing-sql in your browser while the API is live —
--    it will auto-generate INSERT statements and save them to
--    database/migrations/missing_live_buses_<timestamp>.sql
-- ============================================================

USE `nexbus`;

-- ════════════════════════════════════════════════════════════════
-- STEP 1 – Register buses in sltb_buses
-- All NB-31xx & NB-5667 operate out of Colombo Depot (depot_id=1)
-- They already appear in the `timetables` table; this just adds
-- the sltb_buses row that the analytics depot-filter JOINs require.
-- ════════════════════════════════════════════════════════════════

INSERT IGNORE INTO `sltb_buses` (`reg_no`, `sltb_depot_id`, `chassis_no`, `capacity`, `status`)
VALUES
  ('NB-5667',  1, NULL, 54, 'Active'),  -- timetable_id 2  (route 1, Sunday)
  ('NB-3109',  1, NULL, 54, 'Active'),  -- timetable_ids 69,81 (route 1, Wed)
  ('NB-3110',  1, NULL, 54, 'Active'),  -- timetable_ids 70,82 (route 2, Wed)
  ('NB-3111',  1, NULL, 54, 'Active'),  -- timetable_ids 71,83 (route 3, Wed)
  ('NB-3112',  1, NULL, 54, 'Active');  -- timetable_ids 72,84 (route 4, Wed)

-- ════════════════════════════════════════════════════════════════
-- STEP 2 – Link buses to existing timetables via sltb_trips
-- These timetable_ids already exist in the `timetables` table.
-- Using sltb_trip_ids 9001–9010 to avoid collisions with live data.
-- sltb_depot_id=1 (Colombo), drivers & conductors from depot 1.
-- Route → route_id mapping: route '1'=1, '2'=2, '3'=3, '4'=4
-- ════════════════════════════════════════════════════════════════

INSERT IGNORE INTO `sltb_trips`
  (`sltb_trip_id`, `timetable_id`, `bus_reg_no`, `trip_date`,
   `scheduled_departure_time`, `scheduled_arrival_time`,
   `route_id`, `sltb_driver_id`, `sltb_conductor_id`,
   `sltb_depot_id`, `turn_no`, `status`)
VALUES
  -- NB-3109 – morning (timetable 69) & mid-morning (timetable 81) on route 1
  (9001, 69, 'NB-3109', CURDATE(), '07:20:00', '08:05:00', 1, 1, 1,  1, 1, 'Planned'),
  (9002, 81, 'NB-3109', CURDATE(), '09:20:00', '10:05:00', 1, 2, 2,  1, 2, 'Planned'),

  -- NB-3110 – route 2 (timetable 70 & 82)
  (9003, 70, 'NB-3110', CURDATE(), '07:30:00', '08:20:00', 2, 8, 8,  1, 1, 'Planned'),
  (9004, 82, 'NB-3110', CURDATE(), '09:30:00', '10:20:00', 2, 11,11, 1, 2, 'Planned'),

  -- NB-3111 – route 3 (timetable 71 & 83)
  (9005, 71, 'NB-3111', CURDATE(), '07:40:00', '08:25:00', 3, 1, 1,  1, 1, 'Planned'),
  (9006, 83, 'NB-3111', CURDATE(), '09:40:00', '10:25:00', 3, 2, 2,  1, 2, 'Planned'),

  -- NB-3112 – route 4 (timetable 72 & 84)
  (9007, 72, 'NB-3112', CURDATE(), '07:50:00', '08:40:00', 4, 8, 8,  1, 1, 'Planned'),
  (9008, 84, 'NB-3112', CURDATE(), '09:50:00', '10:40:00', 4, 11,11, 1, 2, 'Planned'),

  -- NB-5667 – route 1, Sunday evening (timetable 2)
  (9009,  2, 'NB-5667', CURDATE(), '17:04:00', '21:46:00', 1, 1, 1,  1, 1, 'Planned');

-- ════════════════════════════════════════════════════════════════
-- STEP 3 – Seed tracking data so analytics charts have data for
--           these buses (30-day rolling window).  Uses today's date.
-- ════════════════════════════════════════════════════════════════

INSERT IGNORE INTO `tracking_monitoring`
  (`operator_type`, `bus_reg_no`, `snapshot_at`,
   `lat`, `lng`, `speed`, `route_id`, `timetable_id`,
   `operational_status`, `avg_delay_min`, `speed_violations`)
VALUES
  ('SLTB','NB-3109', NOW(), 6.9271, 79.8612, 42.0, 1, 69,  'OnTime',   2, 0),
  ('SLTB','NB-3110', NOW(), 7.2906, 80.6337, 55.0, 2, 70,  'Delayed',  8, 0),
  ('SLTB','NB-3111', NOW(), 7.4818, 80.3609, 38.0, 3, 71,  'OnTime',   1, 0),
  ('SLTB','NB-3112', NOW(), 6.0329, 80.2168, 61.5, 4, 72,  'OnTime',   3, 1),
  ('SLTB','NB-5667', NOW(), 6.9271, 79.8612, 48.0, 1,  2,  'OnTime',   0, 0);

-- ════════════════════════════════════════════════════════════════
-- STEP 4 – Seed earnings rows so the Revenue Trends chart
--           includes the new buses.
-- ════════════════════════════════════════════════════════════════

INSERT IGNORE INTO `earnings` (`bus_reg_no`, `operator_type`, `date`, `amount`)
VALUES
  ('NB-3109','SLTB', CURDATE(), 18500.00),
  ('NB-3110','SLTB', CURDATE(), 21000.00),
  ('NB-3111','SLTB', CURDATE(), 17800.00),
  ('NB-3112','SLTB', CURDATE(), 22400.00),
  ('NB-5667','SLTB', CURDATE(), 19200.00);

-- ════════════════════════════════════════════════════════════════
-- BUSES FROM THE LIVE-TRACKING API (not yet in DB)
-- For real-time discovery:  visit /api/buses/missing-sql while the
-- live API is running – it auto-generates & saves a timestamped
-- SQL file to  database/migrations/missing_live_buses_<ts>.sql
-- ════════════════════════════════════════════════════════════════

-- Example – edit reg_no / depot_id before running:
-- INSERT IGNORE INTO `sltb_buses` (`reg_no`, `sltb_depot_id`, `status`)
--   VALUES ('NB-XXXX', 1, 'Active');
--
-- For private buses:
-- private_operator_id: 1=Prime Transport, 2=CityExpress, 3=Sunrise Travels
-- INSERT IGNORE INTO `private_buses` (`reg_no`, `private_operator_id`, `status`)
--   VALUES ('PB-XXXX', 1, 'Active');
/* === JUDE-ONLY MIGRATION (safe if rerun) === */

/* -------------------------------
   1) Upgrade `notifications`
--------------------------------- */

/* message -> TEXT */
ALTER TABLE `notifications`
  MODIFY COLUMN `message` TEXT NOT NULL;

/* type enum -> add Alert, Breakdown (keep old values too) */
ALTER TABLE `notifications`
  MODIFY COLUMN `type` ENUM('System','Delay','Complaint','Timetable','Message','Alert','Breakdown')
  NOT NULL DEFAULT 'System';

/* Add priority if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='notifications'
             AND COLUMN_NAME='priority');
SET @sql := IF(@c=0,
  "ALTER TABLE `notifications` ADD COLUMN `priority` ENUM('normal','urgent','critical') DEFAULT 'normal' AFTER `is_seen`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* Add metadata if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='notifications'
             AND COLUMN_NAME='metadata');
SET @sql := IF(@c=0,
  "ALTER TABLE `notifications` ADD COLUMN `metadata` JSON DEFAULT NULL AFTER `priority`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* Add updated_at if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='notifications'
             AND COLUMN_NAME='updated_at');
SET @sql := IF(@c=0,
  "ALTER TABLE `notifications` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* -------------------------------
   2) Trips: completion + cancellation
--------------------------------- */

/* sltb_trips: add columns if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND COLUMN_NAME='arrival_depot_id');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_trips` ADD COLUMN `arrival_depot_id` INT(11) DEFAULT NULL AFTER `arrival_time`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND COLUMN_NAME='completed_by');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_trips` ADD COLUMN `completed_by` INT(11) DEFAULT NULL AFTER `arrival_depot_id`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND COLUMN_NAME='cancelled_by');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_trips` ADD COLUMN `cancelled_by` INT(11) DEFAULT NULL AFTER `completed_by`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND COLUMN_NAME='cancel_reason');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_trips` ADD COLUMN `cancel_reason` TEXT DEFAULT NULL AFTER `cancelled_by`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND COLUMN_NAME='cancelled_at');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_trips` ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancel_reason`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* sltb_trips indexes if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND INDEX_NAME='idx_arrival_depot');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_trips` ADD INDEX `idx_arrival_depot` (`arrival_depot_id`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND INDEX_NAME='idx_completed_by');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_trips` ADD INDEX `idx_completed_by` (`completed_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_trips' AND INDEX_NAME='idx_cancelled_by');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* private_trips: add columns if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND COLUMN_NAME='arrival_depot_id');
SET @sql := IF(@c=0,
  "ALTER TABLE `private_trips` ADD COLUMN `arrival_depot_id` INT(11) DEFAULT NULL AFTER `arrival_time`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND COLUMN_NAME='completed_by');
SET @sql := IF(@c=0,
  "ALTER TABLE `private_trips` ADD COLUMN `completed_by` INT(11) DEFAULT NULL AFTER `arrival_depot_id`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND COLUMN_NAME='cancelled_by');
SET @sql := IF(@c=0,
  "ALTER TABLE `private_trips` ADD COLUMN `cancelled_by` INT(11) DEFAULT NULL AFTER `completed_by`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND COLUMN_NAME='cancel_reason');
SET @sql := IF(@c=0,
  "ALTER TABLE `private_trips` ADD COLUMN `cancel_reason` TEXT DEFAULT NULL AFTER `cancelled_by`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND COLUMN_NAME='cancelled_at');
SET @sql := IF(@c=0,
  "ALTER TABLE `private_trips` ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancel_reason`",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* private_trips indexes if missing */
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND INDEX_NAME='idx_arrival_depot');
SET @sql := IF(@c=0, "ALTER TABLE `private_trips` ADD INDEX `idx_arrival_depot` (`arrival_depot_id`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND INDEX_NAME='idx_completed_by');
SET @sql := IF(@c=0, "ALTER TABLE `private_trips` ADD INDEX `idx_completed_by` (`completed_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='private_trips' AND INDEX_NAME='idx_cancelled_by');
SET @sql := IF(@c=0, "ALTER TABLE `private_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* -------------------------------
   3) SLTB assignments: override tracking
--------------------------------- */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignments' AND COLUMN_NAME='override_remark');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_assignments` ADD COLUMN `override_remark` TEXT DEFAULT NULL",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignments' AND COLUMN_NAME='overridden_by');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_assignments` ADD COLUMN `overridden_by` INT(11) DEFAULT NULL",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignments' AND COLUMN_NAME='override_at');
SET @sql := IF(@c=0,
  "ALTER TABLE `sltb_assignments` ADD COLUMN `override_at` DATETIME DEFAULT NULL",
  "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignments' AND INDEX_NAME='idx_overridden_by');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_assignments` ADD INDEX `idx_overridden_by` (`overridden_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* -------------------------------
   4) New table: sltb_assignment_overrides
--------------------------------- */

CREATE TABLE IF NOT EXISTS `sltb_assignment_overrides` (
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

/* indexes for overrides table (safe) */
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignment_overrides' AND INDEX_NAME='idx_overridden_by');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_assignment_overrides` ADD INDEX `idx_overridden_by` (`overridden_by`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sltb_assignment_overrides' AND INDEX_NAME='idx_assignment');
SET @sql := IF(@c=0, "ALTER TABLE `sltb_assignment_overrides` ADD INDEX `idx_assignment` (`assignment_id`)", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* =========================================================
   finalDB.sql  -> missing changes on 127_0_0_1 (10).sql
   Safe / idempotent migration (MariaDB/MySQL)
   ========================================================= */

START TRANSACTION;

-- ---------------------------------------------------------
-- 1) NEW TABLE: private_staff_attendance  (only if missing)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `private_staff_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_id` int(11) NOT NULL,
  `staff_type` enum('Driver','Conductor') NOT NULL,
  `staff_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Leave') NOT NULL DEFAULT 'Present',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for private_staff_attendance if missing (safe)
SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_staff_attendance');

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_staff_attendance'
             AND INDEX_NAME = 'idx_attendance_operator');
SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `private_staff_attendance` ADD KEY `idx_attendance_operator` (`operator_id`)", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_staff_attendance'
             AND INDEX_NAME = 'idx_attendance_staff');
SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `private_staff_attendance` ADD KEY `idx_attendance_staff` (`staff_type`,`staff_id`)", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_staff_attendance'
             AND INDEX_NAME = 'idx_attendance_date');
SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `private_staff_attendance` ADD KEY `idx_attendance_date` (`work_date`)", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------
-- 2) Add suspend_reason columns (only if missing)
-- ---------------------------------------------------------
/* private_conductors.suspend_reason */
SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_conductors');

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='private_conductors'
             AND COLUMN_NAME='suspend_reason');

SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `private_conductors` ADD COLUMN `suspend_reason` varchar(255) DEFAULT NULL", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* private_drivers.suspend_reason */
SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'private_drivers');

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='private_drivers'
             AND COLUMN_NAME='suspend_reason');

SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `private_drivers` ADD COLUMN `suspend_reason` varchar(255) DEFAULT NULL", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------
-- 3) Add index on notifications (only if missing)
-- ---------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'notifications');

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME='notifications'
             AND INDEX_NAME='idx_notif_depot_type_time');

SET @sql := IF(@t=0, 'SELECT 1',
           IF(@c=0, "ALTER TABLE `notifications` ADD KEY `idx_notif_depot_type_time` (`user_id`,`type`,`created_at`)", 'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;