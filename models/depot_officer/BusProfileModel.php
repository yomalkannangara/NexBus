<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class BusProfileModel extends BaseModel
{
    /**
     * Get bus information by registration number
     */
    public function getBusByReg(string $busReg): array
    {
        try {
            $st = $this->pdo->prepare(
                "SELECT
                    b.reg_no AS bus_reg_no,
                    b.capacity,
                    b.status,
                    b.chassis_no,
                    CONCAT('SLTB Bus ', b.reg_no) AS make_model,
                    NULL AS license_expiry
                 FROM sltb_buses b
                 WHERE b.reg_no = ?
                 LIMIT 1"
            );
            $st->execute([trim($busReg)]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get current tracking status for bus
     */
    public function getTracking(string $busReg): array
    {
        try {
            $st = $this->pdo->prepare(
                "SELECT
                    tm.snapshot_at,
                    tm.speed,
                    tm.avg_delay_min,
                    tm.operational_status,
                    COALESCE(r.route_no, '-') AS route_no
                 FROM tracking_monitoring tm
                 LEFT JOIN routes r ON r.route_id = tm.route_id
                 WHERE tm.operator_type='SLTB' AND tm.bus_reg_no=?
                 ORDER BY tm.snapshot_at DESC
                 LIMIT 1"
            );
            $st->execute([trim($busReg)]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get assignment history for bus
     */
    public function getAssignments(string $busReg, int $limit = 10): array
    {
        try {
            $limit = max(1, min(100, (int)$limit));
            $sql =
                "SELECT
                    a.assigned_date,
                    a.shift,
                    COALESCE(r.route_no, '-') AS route_no,
                    COALESCE(d.full_name, TRIM(CONCAT(u1.first_name,' ',COALESCE(u1.last_name,''))), '-') AS driver_name,
                    COALESCE(c.full_name, TRIM(CONCAT(u2.first_name,' ',COALESCE(u2.last_name,''))), '-') AS conductor_name,
                    CASE WHEN a.assigned_date = CURDATE() THEN 'Active' ELSE 'Completed' END AS status
                 FROM sltb_assignments a
                 LEFT JOIN (
                    SELECT
                        t.bus_reg_no,
                        CAST(
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(t.route_id ORDER BY t.effective_from DESC, t.timetable_id DESC SEPARATOR ','),
                                ',',
                                1
                            ) AS UNSIGNED
                        ) AS route_id
                    FROM timetables t
                    WHERE t.operator_type='SLTB'
                    GROUP BY t.bus_reg_no
                 ) tr ON tr.bus_reg_no = a.bus_reg_no
                 LEFT JOIN routes r ON r.route_id = tr.route_id
                 LEFT JOIN sltb_drivers d ON d.sltb_driver_id = a.sltb_driver_id
                 LEFT JOIN users u1 ON u1.user_id = a.sltb_driver_id
                 LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = a.sltb_conductor_id
                 LEFT JOIN users u2 ON u2.user_id = a.sltb_conductor_id
                 WHERE a.bus_reg_no = ?
                 ORDER BY a.assigned_date DESC, a.assignment_id DESC
                 LIMIT {$limit}";
            $st = $this->pdo->prepare($sql);
            $st->execute([trim($busReg)]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get trip history for bus
     */
    public function getTrips(string $busReg, int $limit = 20): array
    {
        try {
            $limit = max(1, min(100, (int)$limit));
            $sql =
                "SELECT
                    st.trip_date,
                    COALESCE(r.route_no, '-') AS route_no,
                    st.turn_no,
                    TIME_FORMAT(st.departure_time, '%H:%i') AS departure_time,
                    TIME_FORMAT(st.arrival_time, '%H:%i') AS arrival_time,
                    st.status
                 FROM sltb_trips st
                 LEFT JOIN routes r ON r.route_id = st.route_id
                 WHERE st.bus_reg_no = ?
                 ORDER BY COALESCE(st.trip_date, '1970-01-01') DESC, st.sltb_trip_id DESC
                 LIMIT {$limit}";
            $st = $this->pdo->prepare($sql);
            $st->execute([trim($busReg)]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
