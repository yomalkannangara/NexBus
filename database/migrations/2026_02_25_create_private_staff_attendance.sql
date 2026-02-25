-- ============================================================
-- Migration: private_staff_attendance
-- Tracks daily attendance for private bus owners' drivers & conductors
-- ============================================================

CREATE TABLE IF NOT EXISTS private_staff_attendance (
    id              INT(11)         NOT NULL AUTO_INCREMENT,
    operator_id     INT(11)         NOT NULL,           -- FK private_bus_owners
    staff_type      ENUM('Driver','Conductor') NOT NULL,
    staff_id        INT(11)         NOT NULL,            -- FK private_drivers or private_conductors
    work_date       DATE            NOT NULL,
    status          ENUM('Present','Absent','Late','Half_Day') NOT NULL DEFAULT 'Present',
    notes           TEXT            NULL,
    marked_by       INT(11)         NULL,                -- FK users.user_id
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_staff_date (staff_type, staff_id, work_date),
    KEY idx_operator_date (operator_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
