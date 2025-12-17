-- Create table to store attendance for drivers, conductors, and user-based staff
CREATE TABLE IF NOT EXISTS depot_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sltb_depot_id INT NOT NULL,
  attendance_key VARCHAR(64) NOT NULL,
  work_date DATE NOT NULL,
  mark_absent TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_attendance (sltb_depot_id, attendance_key, work_date)
);
