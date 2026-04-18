-- =============================================================
-- NexBus Demo Preparation SQL
-- Run on: April 21, 2026 (Tuesday, DOW=2)
-- Covers:
--   1.  Ensure notifications columns exist (idempotent)
--   2.  Tuesday SLTB timetables for depot 1 (cloned from Wednesday)
--   3.  SLTB assignments for April 21 — Morning & Evening shifts
--   4.  SLTB trips for April 21 — mix of Completed / Cancelled / Delayed /
--       InProgress / Planned to show every status in the dashboard
--   5.  Private assignments for April 21
--   6.  Private trips for April 21
--   7.  Demo notifications — all message types for all depot-1 users
-- =============================================================

USE nexbus;

-- ---------------------------------------------------------------
-- 1. Ensure extended notification columns exist (idempotent)
-- ---------------------------------------------------------------
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS priority ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal' AFTER message,
  ADD COLUMN IF NOT EXISTS metadata LONGTEXT DEFAULT NULL AFTER priority,
  ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT NULL AFTER metadata;


-- ---------------------------------------------------------------
-- 2. Tuesday (DOW=2) SLTB timetables for depot 1
--    Clone Wednesday (DOW=3) entries — skip any already present
-- ---------------------------------------------------------------
INSERT INTO timetables
  (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, effective_from)
SELECT 'SLTB', t.route_id, t.bus_reg_no, 2, t.departure_time, t.arrival_time, t.effective_from
FROM timetables t
JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
WHERE t.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND t.day_of_week = 3
  AND NOT EXISTS (
      SELECT 1 FROM timetables x
      WHERE x.operator_type = 'SLTB'
        AND x.day_of_week   = 2
        AND x.bus_reg_no    = t.bus_reg_no
        AND x.departure_time = t.departure_time
        AND x.route_id      = t.route_id
  );

-- Also clone the Monday NB-1002 Route-1 entry as Tuesday
INSERT INTO timetables
  (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, effective_from)
SELECT 'SLTB', t.route_id, t.bus_reg_no, 2, t.departure_time, t.arrival_time, t.effective_from
FROM timetables t
JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
WHERE t.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND t.day_of_week   = 1
  AND NOT EXISTS (
      SELECT 1 FROM timetables x
      WHERE x.operator_type  = 'SLTB'
        AND x.day_of_week    = 2
        AND x.bus_reg_no     = t.bus_reg_no
        AND x.departure_time = t.departure_time
        AND x.route_id       = t.route_id
  );

-- Clone NB-1001 Friday (DOW=5) entries as Tuesday as well
INSERT INTO timetables
  (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, effective_from)
SELECT 'SLTB', t.route_id, t.bus_reg_no, 2, t.departure_time, t.arrival_time, t.effective_from
FROM timetables t
JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
WHERE t.operator_type = 'SLTB'
  AND b.sltb_depot_id = 1
  AND t.day_of_week   = 5
  AND NOT EXISTS (
      SELECT 1 FROM timetables x
      WHERE x.operator_type  = 'SLTB'
        AND x.day_of_week    = 2
        AND x.bus_reg_no     = t.bus_reg_no
        AND x.departure_time = t.departure_time
        AND x.route_id       = t.route_id
  );


-- ---------------------------------------------------------------
-- 3. SLTB assignments for April 21 (depot 1)
--    Active drivers:   1 (Sunimal), 1001 (Kamal), 1002 (gayashan), 8 (Manjula)
--    Active conductors: 1 (S. Dissanayake), 2 (R. Jayalath), 11 (Pradeep), 2001 (Sunil)
-- ---------------------------------------------------------------
INSERT INTO sltb_assignments
  (assigned_date, shift, bus_reg_no, sltb_driver_id, sltb_conductor_id, sltb_depot_id)
VALUES
  ('2026-04-21', 'Morning', 'NB-3101',  1,    1,    1),
  ('2026-04-21', 'Morning', 'NB-3102',  8,    2,    1),
  ('2026-04-21', 'Morning', 'NB-3103', 1001,  11,   1),
  ('2026-04-21', 'Morning', 'NB-3104', 1002, 2001,  1),
  ('2026-04-21', 'Morning', 'NB-1002',  1,    1,    1),
  ('2026-04-21', 'Evening', 'NB-3105',  8,    2,    1),
  ('2026-04-21', 'Evening', 'NB-3106', 1001,  11,   1),
  ('2026-04-21', 'Evening', 'NB-3107', 1002, 2001,  1),
  ('2026-04-21', 'Evening', 'NB-1001',  1,    1,    1)
ON DUPLICATE KEY UPDATE
  sltb_driver_id    = VALUES(sltb_driver_id),
  sltb_conductor_id = VALUES(sltb_conductor_id),
  sltb_depot_id     = VALUES(sltb_depot_id);


-- ---------------------------------------------------------------
-- 4. SLTB trips for April 21 — varied statuses for demo
-- ---------------------------------------------------------------

-- 4a. Completed early-morning trips (departed before 08:30, arrived on time)
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
   turn_no, departure_time, start_delay_seconds,
   arrival_time, end_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id, a.sltb_driver_id, a.sltb_conductor_id, 1,
  1,
  tt.departure_time, 0,
  tt.arrival_time, 0, 'Completed'
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
LEFT JOIN sltb_assignments a
       ON a.assigned_date = '2026-04-21'
      AND a.bus_reg_no    = tt.bus_reg_no
      AND a.sltb_depot_id = 1
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id  = 1
  AND tt.day_of_week   = 2
  AND tt.departure_time < '08:30:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = '2026-04-21'
  )
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- 4b. One delayed completed trip (departed 08:30-09:00, 7-min late start)
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
   turn_no, departure_time, start_delay_seconds,
   arrival_time, end_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id, a.sltb_driver_id, a.sltb_conductor_id, 1,
  2,
  ADDTIME(tt.departure_time, '00:07:00'), 420,
  ADDTIME(tt.arrival_time,   '00:07:00'), 420, 'Delayed'
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
LEFT JOIN sltb_assignments a
       ON a.assigned_date = '2026-04-21'
      AND a.bus_reg_no    = tt.bus_reg_no
      AND a.sltb_depot_id = 1
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id  = 1
  AND tt.day_of_week   = 2
  AND tt.departure_time >= '08:30:00'
  AND tt.departure_time <  '09:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = '2026-04-21'
  )
ON DUPLICATE KEY UPDATE status = VALUES(status), end_delay_seconds = VALUES(end_delay_seconds);

-- 4c. One cancelled trip — engine failure (09:00-09:15)
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
   turn_no, departure_time, start_delay_seconds,
   status, cancelled_by, cancel_reason, cancelled_at)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id, a.sltb_driver_id, a.sltb_conductor_id, 1,
  1,
  tt.departure_time, 0,
  'Cancelled', 54, 'Engine mechanical failure — bus taken off route.',
  '2026-04-21 09:22:00'
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
LEFT JOIN sltb_assignments a
       ON a.assigned_date = '2026-04-21'
      AND a.bus_reg_no    = tt.bus_reg_no
      AND a.sltb_depot_id = 1
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id  = 1
  AND tt.day_of_week   = 2
  AND tt.departure_time >= '09:00:00'
  AND tt.departure_time <  '09:15:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = '2026-04-21'
  )
LIMIT 1
ON DUPLICATE KEY UPDATE status = VALUES(status), cancel_reason = VALUES(cancel_reason);

-- 4d. InProgress trips (09:15-10:30 — currently running during demo)
INSERT INTO sltb_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
   turn_no, departure_time, start_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id, a.sltb_driver_id, a.sltb_conductor_id, 1,
  1,
  tt.departure_time, 0, 'InProgress'
FROM timetables tt
JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
LEFT JOIN sltb_assignments a
       ON a.assigned_date = '2026-04-21'
      AND a.bus_reg_no    = tt.bus_reg_no
      AND a.sltb_depot_id = 1
WHERE tt.operator_type = 'SLTB'
  AND b.sltb_depot_id  = 1
  AND tt.day_of_week   = 2
  AND tt.departure_time >= '09:15:00'
  AND tt.departure_time <  '10:30:00'
  AND NOT EXISTS (
      SELECT 1 FROM sltb_trips st2
      WHERE st2.timetable_id = tt.timetable_id AND st2.trip_date = '2026-04-21'
  )
ON DUPLICATE KEY UPDATE status = VALUES(status);


-- ---------------------------------------------------------------
-- 5. Private assignments for April 21 (operator 1)
--    Active drivers:   2 (Saman), 3 (Anura), 5108 (Gayashan), 5109 (yomal)
--    Active conductors: 1 (Indunil), 2 (Roshan), 6104 (Pasindu), 7001 (Nuwan)
-- ---------------------------------------------------------------
INSERT INTO private_assignments
  (assigned_date, shift, bus_reg_no, private_driver_id, private_conductor_id, private_operator_id)
VALUES
  ('2026-04-21', 'Morning', 'PB-1001',  2,    1,    1),
  ('2026-04-21', 'Morning', 'PA-1002',  3,    2,    1),
  ('2026-04-21', 'Morning', 'PB-4001', 5108, 6104,  1),
  ('2026-04-21', 'Morning', 'PB-4002', 5109, 7001,  1),
  ('2026-04-21', 'Evening', 'PB-1001',  2,    1,    1),
  ('2026-04-21', 'Evening', 'PA-1002',  3,    2,    1)
ON DUPLICATE KEY UPDATE
  private_driver_id    = VALUES(private_driver_id),
  private_conductor_id = VALUES(private_conductor_id),
  private_operator_id  = VALUES(private_operator_id);


-- ---------------------------------------------------------------
-- 6. Private trips for April 21 (operator 1)
-- ---------------------------------------------------------------

-- 6a. Completed morning trips (departed before 09:30)
INSERT INTO private_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, private_driver_id, private_conductor_id, private_operator_id,
   turn_no, departure_time, start_delay_seconds,
   arrival_time, end_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id,
  COALESCE(a.private_driver_id, 2),
  COALESCE(a.private_conductor_id, 1),
  1, 1,
  tt.departure_time, 0, tt.arrival_time, 0, 'Completed'
FROM timetables tt
JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
LEFT JOIN private_assignments a
       ON a.assigned_date        = '2026-04-21'
      AND a.bus_reg_no           = tt.bus_reg_no
      AND a.private_operator_id  = 1
WHERE tt.operator_type          = 'Private'
  AND pb.private_operator_id    = 1
  AND tt.day_of_week            = 2
  AND tt.departure_time         < '09:30:00'
  AND tt.departure_time         > '05:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM private_trips pt2
      WHERE pt2.timetable_id = tt.timetable_id AND pt2.trip_date = '2026-04-21'
  )
ON DUPLICATE KEY UPDATE status = VALUES(status), arrival_time = VALUES(arrival_time);

-- 6b. InProgress afternoon trips (09:30-13:00)
INSERT INTO private_trips
  (timetable_id, bus_reg_no, trip_date,
   scheduled_departure_time, scheduled_arrival_time,
   route_id, private_driver_id, private_conductor_id, private_operator_id,
   turn_no, departure_time, start_delay_seconds, status)
SELECT
  tt.timetable_id, tt.bus_reg_no, '2026-04-21',
  tt.departure_time, tt.arrival_time,
  tt.route_id,
  COALESCE(a.private_driver_id, 3),
  COALESCE(a.private_conductor_id, 2),
  1, 2,
  tt.departure_time, 0, 'InProgress'
FROM timetables tt
JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
LEFT JOIN private_assignments a
       ON a.assigned_date        = '2026-04-21'
      AND a.bus_reg_no           = tt.bus_reg_no
      AND a.private_operator_id  = 1
WHERE tt.operator_type          = 'Private'
  AND pb.private_operator_id    = 1
  AND tt.day_of_week            = 2
  AND tt.departure_time         >= '09:30:00'
  AND tt.departure_time         <  '13:00:00'
  AND NOT EXISTS (
      SELECT 1 FROM private_trips pt2
      WHERE pt2.timetable_id = tt.timetable_id AND pt2.trip_date = '2026-04-21'
  )
ON DUPLICATE KEY UPDATE status = VALUES(status);


-- ---------------------------------------------------------------
-- 7. Demo notifications for all message types
--    Depot 1 users:
--      54    = SLTBTimekeeper (sltbtimekeeper)
--      10001 = SLTBTimekeeper (Test TK)
--      53    = DepotOfficer (depotofficer)
--      32    = DepotOfficer (Chamara Fernando)
--      10008 = DepotOfficer (yomal)
--      19    = DepotManager (pasidu Perera)
--      31    = DepotManager (Sunil Silva)
--      56    = DepotManager (DepotManager)
-- ---------------------------------------------------------------

-- Clear any stale demo notifications from previous dates so inbox
-- is clean for the presentation (keeps real operational records intact)
DELETE FROM notifications
WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.source')) IN
      ('depot_message','timekeeper_message','sltb_timekeeper_emergency')
  AND user_id IN (54, 10001, 53, 32, 10008, 19, 31, 56)
  AND DATE(created_at) < '2026-04-21';

-- 7a. Automated: assignment lifecycle messages → SLTBTimekeepers at depot 1
--     Source: DepotOfficer (user 53)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
VALUES
(54,    'Message', 'OPERATION UPDATE: Assignment created for bus NB-3101 on 2026-04-21 (Morning shift).', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 06:55:00'),
(10001, 'Message', 'OPERATION UPDATE: Assignment created for bus NB-3101 on 2026-04-21 (Morning shift).', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 06:55:00'),

(54,    'Message', 'OPERATION UPDATE: Staff reassigned for bus NB-3104 on 2026-04-21 (Morning shift).', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 07:10:00'),
(10001, 'Message', 'OPERATION UPDATE: Staff reassigned for bus NB-3104 on 2026-04-21 (Morning shift).', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 07:10:00'),

(54,    'Message', 'OPERATION UPDATE: Assignment deleted for bus NB-3108 on 2026-04-21 (Evening shift).', 'urgent',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 08:45:00'),
(10001, 'Message', 'OPERATION UPDATE: Assignment deleted for bus NB-3108 on 2026-04-21 (Evening shift).', 'urgent',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 08:45:00');

-- 7b. Automated: EMERGENCY — trip cancelled (engine failure) → all DepotOfficer+DepotManager at depot 1
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
SELECT
  u.user_id,
  'Breakdown',
  'EMERGENCY UPDATE: Trip #1 was cancelled by sltbtimekeeper. Bus: NB-3103, Route ID: 3. Reason: Engine mechanical failure — bus taken off route.',
  'critical',
  JSON_OBJECT(
    'source',         'sltb_timekeeper_emergency',
    'source_role',    'SLTBTimekeeper',
    'source_user_id', 54,
    'source_name',    'sltbtimekeeper',
    'event_kind',     'trip_cancelled',
    'bus_reg_no',     'NB-3103',
    'route_id',       3,
    'depot_id',       1,
    'reason',         'Engine mechanical failure — bus taken off route.'
  ),
  0, '2026-04-21 09:22:00'
FROM users u
WHERE u.sltb_depot_id = 1 AND u.role IN ('DepotOfficer','DepotManager');

-- 7c. Manual: DepotOfficer → all Timekeepers (general announcement)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
VALUES
(54,    'Message', 'All timekeepers: please ensure trip logs are submitted within 15 minutes of trip completion. Audit is scheduled for this week.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 08:00:00'),
(10001, 'Message', 'All timekeepers: please ensure trip logs are submitted within 15 minutes of trip completion. Audit is scheduled for this week.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 0, '2026-04-21 08:00:00');

-- 7d. Manual: DepotOfficer → individual Timekeeper (direct message)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
VALUES
(54, 'Message', 'Kasun — NB-3101 afternoon run is confirmed. Driver Sunimal Perera will be at the depot by 13:30, please log the departure accurately.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','individual','category',NULL), 0, '2026-04-21 09:05:00');

-- 7e. Manual: SLTBTimekeeper → DepotOfficers + DepotManagers (reply / report)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
SELECT
  u.user_id,
  'Message',
  'Bus NB-3102 arrived with a cracked windshield. Turn 2 completed but vehicle needs workshop inspection before next departure.',
  'urgent',
  JSON_OBJECT(
    'source',         'timekeeper_message',
    'source_user_id', 54,
    'source_role',    'SLTBTimekeeper',
    'source_name',    'sltbtimekeeper',
    'scope',          'individual',
    'category',       NULL
  ),
  0, '2026-04-21 09:40:00'
FROM users u
WHERE u.sltb_depot_id = 1 AND u.role IN ('DepotOfficer','DepotManager');

-- 7f. Inbox messages for SLTBTimekeeper — past history (already read)
INSERT INTO notifications (user_id, type, message, priority, metadata, is_seen, created_at)
VALUES
(54, 'Message', 'Reminder: Poya day schedule applies this Friday. All Route 1 services operate on Sunday timetable. Update your logs accordingly.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',53,'source_role','DepotOfficer','source_name','depotofficer','scope','role','category',NULL), 1, '2026-04-20 17:30:00'),
(54, 'Alert', 'URGENT: Heavy traffic on Kandy Road this morning. All Route 1 departures should be flagged as potentially Delayed until 09:00.', 'urgent',
 JSON_OBJECT('source','depot_message','source_user_id',56,'source_role','DepotManager','source_name','DepotManager','scope','role','category',NULL), 1, '2026-04-20 07:15:00'),
-- Inbox for DepotOfficer 53 — from DepotManager
(53, 'Message', 'Weekly fleet health check due: please ensure all buses NB-31xx series are logged with odometer readings by end of business today.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',56,'source_role','DepotManager','source_name','DepotManager','scope','individual','category',NULL), 1, '2026-04-20 08:00:00'),
-- Unread alert for DepotManager 56 — from system
(56, 'Alert', 'Monthly on-time performance report is ready. Depot 1 achieved 87% on-time rate for April 1–20. View full report in the dashboard.', 'normal',
 JSON_OBJECT('source','depot_message','source_user_id',31,'source_role','DepotManager','source_name','Sunil Silva','scope','individual','category',NULL), 0, '2026-04-21 07:00:00');
