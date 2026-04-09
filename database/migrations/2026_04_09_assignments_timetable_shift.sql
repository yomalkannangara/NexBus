-- Migrate sltb_assignments.shift from fixed enum to actual departure time (varchar)
-- and add timetable_id FK so each assignment links to a specific timetable trip.

ALTER TABLE `sltb_assignments`
  MODIFY COLUMN `shift` varchar(20) NOT NULL DEFAULT 'Morning',
  ADD COLUMN `timetable_id` int(11) NULL DEFAULT NULL AFTER `shift`;

ALTER TABLE `sltb_assignments`
  ADD INDEX `idx_sla_timetable` (`timetable_id`);
