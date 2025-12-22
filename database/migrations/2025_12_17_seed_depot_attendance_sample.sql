-- Seed sample attendance rows for drivers and conductors (safe to run)
-- Inserts up to 2 active drivers and 2 active conductors for each depot as seeded rows for today.

INSERT INTO depot_attendance (sltb_depot_id, attendance_key, work_date, mark_absent, notes)
SELECT sltb_depot_id, CONCAT('driver:', sltb_driver_id), CURDATE(), 0, 'seeded'
FROM sltb_drivers
WHERE status = 'Active'
GROUP BY sltb_depot_id, sltb_driver_id
LIMIT 4;

INSERT INTO depot_attendance (sltb_depot_id, attendance_key, work_date, mark_absent, notes)
SELECT sltb_depot_id, CONCAT('conductor:', sltb_conductor_id), CURDATE(), 0, 'seeded'
FROM sltb_conductors
WHERE status = 'Active'
GROUP BY sltb_depot_id, sltb_conductor_id
LIMIT 4;
