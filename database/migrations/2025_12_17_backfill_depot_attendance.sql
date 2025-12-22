-- Backfill existing staff_attendance into depot_attendance
-- This will create user-based attendance entries (attendance_key = 'user:<user_id>')
-- Run once after creating depot_attendance table.

INSERT INTO depot_attendance (sltb_depot_id, attendance_key, work_date, mark_absent, notes, created_at, updated_at)
SELECT
  COALESCE(u.sltb_depot_id, 0) AS sltb_depot_id,
  CONCAT('user:', sa.user_id) AS attendance_key,
  sa.work_date,
  sa.mark_absent,
  sa.notes,
  sa.created_at,
  sa.updated_at
FROM staff_attendance sa
JOIN users u ON u.user_id = sa.user_id
WHERE COALESCE(u.sltb_depot_id, 0) > 0
ON DUPLICATE KEY UPDATE
  mark_absent = VALUES(mark_absent),
  notes = VALUES(notes),
  updated_at = VALUES(updated_at);
