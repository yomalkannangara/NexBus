-- =============================================================
-- NexBus Demo Preparation SQL
-- Run: April 16 2026 (Thursday, DOW=4)
-- Fixes:
--   1. Re-apply notifications migration (priority, metadata, category)
--   2. Add SLTB Thursday timetables for depot 1
--   3. Add SLTB assignments for today
--   4. Add SLTB trips for today (mix of Completed/InProgress/Planned)
--   5. Add private assignments for today
--   6. Add private trips for today
--   7. Add demo notifications for users 54 and 10002
-- =============================================================

USE nexbus;

-- ---------------------------------------------------------------
-- 1. Re-apply notifications migration (idempotent)
-- ---------------------------------------------------------------
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS priority ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal' AFTER message,
  ADD COLUMN IF NOT EXISTS metadata LONGTEXT DEFAULT NULL AFTER priority,
  ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT NULL AFTER metadata;

-- ---------------------------------------------------------------
-- 2. SLTB Thursday timetables for depot 1
--    Clone Wednesday (DOW=3) entries and set day_of_week=4
-- ---------------------------------------------------------------
INSERT INTO timetables (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, effective_from)
SELECT 'SLTB', t.route_id, t.bus_reg_no, 4, t.departure_time, t.arrival_time, t.effective_from
FROM timetables t
JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
WHERE t.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND t.day_of_week = 3;

-- Also add the Monday entry (NB-1002 Route 1 DOW=1) as Thursday
INSERT INTO timetables (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, effective_from)
SELECT 'SLTB', t.route_id, t.bus_reg_no, 4, t.departure_time, t.arrival_time, t.effective_from
FROM timetables t
JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
WHERE t.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND t.day_of_week = 1;

-- ---------------------------------------------------------------
-- 3. SLTB assignments for today (Colombo Depot - depot 1)
-- ---------------------------------------------------------------
INSERT INTO sltb_assignments (assigned_date, shift, bus_reg_no, sltb_driver_id, sltb_conductor_id, sltb_depot_id)
VALUES
  (CURDATE(), 'Morning', 'NB-1001',  1,    1,    1),
  (CURDATE(), 'Morning', 'NB-1002',  2,    2,    1),
  (CURDATE(), 'Morning', 'NB-3102',  8,    8,    1),
  (CURDATE(), 'Morning', 'NB-3103', 11,   11,    1),
  (CURDATE(), 'Evening', 'NB-3104', 1001, 2001,  1),
  (CURDATE(), 'Evening', 'NB-5667',  2,    1,    1)
ON DUPLICATE KEY UPDATE
  sltb_driver_id    = VALUES(sltb_driver_id),
  sltb_conductor_id = VALUES(sltb_conductor_id),
  sltb_depot_id     = VALUES(sltb_depot_id);

-- ---------------------------------------------------------------
-- 4. SLTB trips for today (depot 1) - reference the new Thursday timetables
-- ---------------------------------------------------------------
-- Completed morning trips (08:xx departed)
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date, scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id, turn_no,
   departure_time, start_delay_seconds, arrival_time, end_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, CURDATE(), tt.departure_time, tt.arrival_time,
  tt.route_id, 1, 1, 1, 1,
  tt.departure_time, 0, tt.arrival_time, 0, 'Completed'
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND tt.day_of_week = 4
  AND tt.departure_time < '09:30:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = CURDATE()
  );

-- InProgress / Delayed mid-morning trips
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date, scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id, turn_no,
   departure_time, start_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, CURDATE(), tt.departure_time, tt.arrival_time,
  tt.route_id, 2, 2, 1, 1,
  tt.departure_time,
  CASE WHEN tt.departure_time BETWEEN '09:30:00' AND '10:30:00' THEN 300 ELSE 0 END,
  CASE WHEN tt.departure_time BETWEEN '09:30:00' AND '10:30:00' THEN 'Delayed' ELSE 'InProgress' END
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND tt.day_of_week = 4
  AND tt.departure_time >= '09:30:00'
  AND tt.departure_time < '12:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = CURDATE()
  );

-- ---------------------------------------------------------------
-- 5. Private assignments for today (operator 1)
-- ---------------------------------------------------------------
INSERT INTO private_assignments (assigned_date, shift, bus_reg_no, private_driver_id, private_conductor_id, private_operator_id)
VALUES
  (CURDATE(), 'Morning', 'PB-1001', 1, 1, 1),
  (CURDATE(), 'Morning', 'PA-1002', 2, 2, 1),
  (CURDATE(), 'Morning', 'PB-4001', 3, 9, 1),
  (CURDATE(), 'Evening', 'PB-1001', 1, 1, 1),
  (CURDATE(), 'Evening', 'PA-1002', 2, 2, 1);

-- ---------------------------------------------------------------
-- 6. Private trips for today (operator 1)
-- ---------------------------------------------------------------
-- Completed morning trips
INSERT INTO private_trips
  (timetable_id, bus_reg_no, trip_date, scheduled_departure_time, scheduled_arrival_time,
   route_id, private_driver_id, private_conductor_id, private_operator_id, turn_no,
   departure_time, start_delay_seconds, arrival_time, end_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, CURDATE(), tt.departure_time, tt.arrival_time,
  tt.route_id, 1, 1, 1, 1,
  tt.departure_time, 0, tt.arrival_time, 0, 'Completed'
FROM timetables tt
JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
WHERE tt.operator_type = 'Private'
  AND pb.private_operator_id = 1
  AND tt.day_of_week = 4
  AND tt.departure_time < '09:30:00'
  AND tt.departure_time > '05:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM private_trips pt2
      WHERE pt2.timetable_id = tt.timetable_id AND pt2.trip_date = CURDATE()
  );

-- InProgress afternoon trips
INSERT INTO private_trips
  (timetable_id, bus_reg_no, trip_date, scheduled_departure_time, scheduled_arrival_time,
   route_id, private_driver_id, private_conductor_id, private_operator_id, turn_no,
   departure_time, start_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, CURDATE(), tt.departure_time, tt.arrival_time,
  tt.route_id, 2, 2, 1, 1,
  tt.departure_time, 0, 'InProgress'
FROM timetables tt
JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
WHERE tt.operator_type = 'Private'
  AND pb.private_operator_id = 1
  AND tt.day_of_week = 4
  AND tt.departure_time >= '09:30:00'
  AND tt.departure_time < '13:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM private_trips pt2
      WHERE pt2.timetable_id = tt.timetable_id AND pt2.trip_date = CURDATE()
  );

-- ---------------------------------------------------------------
-- 7. Demo notifications
--    user 54  = SLTB Timekeeper (sltbtimekeeper@gmail.com, depot 1)
--    user 10002 = Private Timekeeper (privatetimekeeper@gmail.com, op 1)
-- ---------------------------------------------------------------
INSERT INTO notifications (user_id, type, message, priority, is_seen, created_at) VALUES
-- For SLTB Timekeeper
(54, 'Message', 'Schedule change notice: All Route 1 morning services should depart 10 minutes earlier today due to special event traffic near Colombo Fort. Please inform drivers and update logs accordingly.', 'normal', 0, NOW() - INTERVAL 2 HOUR),
(54, 'Alert',   'URGENT: Bus NB-1002 has been reported with engine overheating. Arrange immediate coverage for afternoon Route 1 slots. Contact workshop: 011-2345678.', 'urgent', 0, NOW() - INTERVAL 45 MINUTE),
(54, 'Message', 'Poya day schedule next week: All routes will operate on Sunday timetable on Monday. Please update trip records on the day.', 'normal', 1, NOW() - INTERVAL 5 HOUR),
-- For Private Timekeeper
(10002, 'Message', 'Driver notice: Mr. Kasun Perera (Driver ID 1) will be operating an additional evening shift today on Route PB-12002. Ensure trip logs are completed by 20:00.', 'normal', 0, NOW() - INTERVAL 1 HOUR),
(10002, 'Alert',   'Passenger complaint received for Bus PA-1002 on morning route. Please verify trip log and contact depot manager for resolution.', 'urgent', 0, NOW() - INTERVAL 20 MINUTE);

-- ---------------------------------------------------------------
-- 8. Demo messaging notifications (covers all message system types)
--    user 53 = DepotOfficer, user 54 = SLTBTimekeeper, user 56 = DepotManager
--    All at sltb_depot_id = 1 (Colombo)
-- ---------------------------------------------------------------

-- 8a. One cancelled afternoon trip to power the emergency notification below
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date, scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id, turn_no,
   departure_time, start_delay_seconds, status, cancelled_by, cancel_reason, cancelled_at)
SELECT
  tt.timetable_id, tt.bus_reg_no, CURDATE(), tt.departure_time, tt.arrival_time,
  tt.route_id, 1, 1, 1, 2,
  tt.departure_time, 0, 'Cancelled', 54, 'Engine failure on route.', NOW() - INTERVAL 35 MINUTE
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND tt.day_of_week = 4
  AND tt.departure_time >= '12:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = CURDATE()
  )
ORDER BY tt.departure_time
LIMIT 1;

SET @cancelled_trip_id = LAST_INSERT_ID();
SET @cancelled_bus     = COALESCE((SELECT bus_reg_no FROM sltb_trips WHERE sltb_trip_id = @cancelled_trip_id LIMIT 1), 'NB-1001');
SET @cancelled_route   = COALESCE((SELECT route_id   FROM sltb_trips WHERE sltb_trip_id = @cancelled_trip_id LIMIT 1), 1);

-- 8b. Automated: assignment lifecycle → SLTBTimekeeper (user 54)
--     Source: DepotOfficer (user 53) via sendAssignmentAutomation()
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at) VALUES
-- Assignment Updated (normal)
(54, 'Message',
 'OPERATION UPDATE: Assignment updated for bus NB-1001 on 2026-04-16 (Morning shift).',
 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL),
 0, NOW() - INTERVAL 90 MINUTE),
-- Staff Reassigned (normal)
(54, 'Message',
 'OPERATION UPDATE: Staff reassigned for bus NB-1002 on 2026-04-16 (Morning shift).',
 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL),
 0, NOW() - INTERVAL 60 MINUTE),
-- Assignment Deleted (urgent)
(54, 'Message',
 'OPERATION UPDATE: Assignment deleted for bus NB-3104 on 2026-04-16 (Evening shift).',
 'urgent',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL),
 0, NOW() - INTERVAL 15 MINUTE);

-- 8c. Automated: trip cancelled → DepotOfficer + DepotManager (users 53, 56)
--     Source: SLTBTimekeeper (user 54) via TripEntryModel::notifyDepotEmergency()
--     Type=Breakdown + priority=critical because reason contains "engine"
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
SELECT
  u.user_id,
  'Breakdown',
  CONCAT('EMERGENCY UPDATE: Trip #', IFNULL(@cancelled_trip_id, 0),
         ' was cancelled by sltbtimekeeper. Bus: ', @cancelled_bus,
         ', Route ID: ', @cancelled_route, '. Reason: Engine failure on route.'),
  'critical',
  JSON_OBJECT(
    'source',         'sltb_timekeeper_emergency',
    'source_role',    'SLTBTimekeeper',
    'source_user_id', 54,
    'source_name',    'sltbtimekeeper',
    'event_kind',     'trip_cancelled',
    'trip_id',        @cancelled_trip_id,
    'bus_reg_no',     @cancelled_bus,
    'route_id',       @cancelled_route,
    'depot_id',       1,
    'reason',         'Engine failure on route.'
  ),
  0,
  NOW() - INTERVAL 30 MINUTE
FROM users u
WHERE u.sltb_depot_id = 1 AND u.role IN ('DepotOfficer', 'DepotManager');

-- 8d. Manual: DepotOfficer (53) → SLTBTimekeeper (54)
--     Sent via Messages compose form (scope=individual)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at) VALUES
(54, 'Message',
 'Reminder: Afternoon shift handover should be completed by 14:30. Ensure all InProgress trips from the morning are logged before the long weekend.',
 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','individual','category',NULL),
 0, NOW() - INTERVAL 25 MINUTE);

-- 8e. Manual: SLTBTimekeeper (54) → DepotOfficer + DepotManager (users 53, 56)
--     Sent via timekeeper compose card via TimekeeperMessageModel::sendToDepotOfficers()
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
SELECT
  u.user_id,
  'Message',
  'Bus NB-1001 returned with a minor windshield crack. Parked and logged. Please arrange inspection before the next scheduled run.',
  'urgent',
  JSON_OBJECT(
    'source',         'timekeeper_message',
    'source_user_id', 54,
    'source_role',    'SLTBTimekeeper',
    'source_name',    'sltbtimekeeper',
    'scope',          'individual',
    'category',       NULL
  ),
  0,
  NOW() - INTERVAL 10 MINUTE
FROM users u
WHERE u.sltb_depot_id = 1 AND u.role IN ('DepotOfficer', 'DepotManager');
