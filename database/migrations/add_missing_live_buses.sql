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
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('System','Delay','Complaint','Timetable','Message','Alert','Breakdown') DEFAULT 'System',
  `message` text NOT NULL,
  `is_seen` tinyint(1) DEFAULT 0,
  `priority` enum('normal','urgent','critical') DEFAULT 'normal',
  `metadata` json DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sltb_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`;

ALTER TABLE `sltb_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`);

ALTER TABLE `private_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`;

ALTER TABLE `private_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`);

ALTER TABLE `sltb_trips`
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

ALTER TABLE `private_trips`
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

ALTER TABLE `sltb_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`);
ALTER TABLE `private_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`);

ALTER TABLE `sltb_assignments`
  ADD COLUMN `override_remark` TEXT DEFAULT NULL AFTER `sltb_depot_id`,
  ADD COLUMN `overridden_by` int(11) DEFAULT NULL AFTER `override_remark`,
  ADD COLUMN `override_at` datetime DEFAULT NULL AFTER `overridden_by`;

ALTER TABLE `sltb_assignments`
  ADD INDEX `idx_overridden_by` (`overridden_by`);

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

ALTER TABLE notifications
    MODIFY COLUMN `type` ENUM(
        'Message',
        'Delay',
        'Timetable',
        'Alert',
        'Urgent',
        'Breakdown',
        'System'
    ) NOT NULL DEFAULT 'Message';

CREATE INDEX IF NOT EXISTS idx_notif_depot_type_time
    ON notifications (user_id, type, created_at);