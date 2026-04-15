<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    // Inherits parent::__construct() which sets $this->pdo

    /** Cards: delayed / breakdowns today (from tracking_monitoring) */
    public function counts(int $depotId): array
    {
        try {
            $sql = "SELECT
                        SUM(CASE WHEN tm.operational_status='Delayed'   THEN 1 ELSE 0 END) AS delayed,
                        SUM(CASE WHEN tm.operational_status='Breakdown' THEN 1 ELSE 0 END) AS breaks
                    FROM tracking_monitoring tm
                    JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no AND b.sltb_depot_id = ?
                    WHERE tm.track_id IN (
                        SELECT MAX(t2.track_id)
                        FROM tracking_monitoring t2
                        JOIN sltb_buses b2 ON b2.reg_no = t2.bus_reg_no AND b2.sltb_depot_id = ?
                        GROUP BY t2.bus_reg_no
                    )";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $depotId]);
            $row = $st->fetch();
            return [
                'delayed' => (int)($row['delayed'] ?? 0),
                'breaks'  => (int)($row['breaks']  ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['delayed' => 0, 'breaks' => 0];
        }
    }

    /** Latest delayed buses (latest snapshot per bus, limit 20) */
    public function delayedToday(int $depotId): array
    {
        try {
            $sql = "SELECT tm.*, r.route_no
                    FROM tracking_monitoring tm
                    JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no AND b.sltb_depot_id = ?
                    LEFT JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.operational_status = 'Delayed'
                      AND tm.track_id IN (
                          SELECT MAX(t2.track_id)
                          FROM tracking_monitoring t2
                          JOIN sltb_buses b2 ON b2.reg_no = t2.bus_reg_no AND b2.sltb_depot_id = ?
                          GROUP BY t2.bus_reg_no
                      )
                    ORDER BY tm.snapshot_at DESC
                    LIMIT 20";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $depotId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Six KPI stats for the dashboard cards */
    public function stats(int $depotId): array
    {
        $zero = [
            'activeBuses'       => 0,
            'maintBuses'        => 0,
            'driversOnDuty'     => 0,
            'conductorsOnDuty'  => 0,
            'tripsCompleted'    => 0,
            'delayedTrips'      => 0,
            'driversPresent'    => 0,
            'conductorsPresent' => 0,
            'driversAbsent'     => 0,
            'conductorsAbsent'  => 0,
            'attendanceMarked'  => false,
        ];
        try {
            $cnt = function (string $sql, array $p = []): int {
                $st = $this->pdo->prepare($sql);
                $st->execute($p);
                return (int)($st->fetchColumn() ?? 0);
            };
            $d = $depotId;

            // Today's attendance from depot_attendance (present = Present/Late/Half_Day)
            $attPresent = "SELECT COUNT(*) FROM depot_attendance
                           WHERE sltb_depot_id=? AND work_date=CURDATE()
                             AND attendance_key LIKE ? AND status IN ('Present','Late','Half_Day')";
            $attAbsent  = "SELECT COUNT(*) FROM depot_attendance
                           WHERE sltb_depot_id=? AND work_date=CURDATE()
                             AND attendance_key LIKE ? AND status='Absent'";
            $attTotal   = "SELECT COUNT(*) FROM depot_attendance
                           WHERE sltb_depot_id=? AND work_date=CURDATE()";

            $driversPresent    = $cnt($attPresent, [$d, 'driver:%']);
            $conductorsPresent = $cnt($attPresent, [$d, 'conductor:%']);
            $driversAbsent     = $cnt($attAbsent,  [$d, 'driver:%']);
            $conductorsAbsent  = $cnt($attAbsent,  [$d, 'conductor:%']);
            $attendanceMarked  = $cnt($attTotal, [$d]) > 0;

            return [
                // Buses assigned today (by depot officer) — meaningful even before timekeepers act
                'activeBuses'       => $cnt("SELECT COUNT(DISTINCT bus_reg_no) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'maintBuses'        => $cnt("SELECT COUNT(*) FROM sltb_buses WHERE sltb_depot_id=? AND status='Maintenance'", [$d]),
                'driversOnDuty'     => $cnt("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'conductorsOnDuty'  => $cnt("SELECT COUNT(DISTINCT sltb_conductor_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'tripsCompleted'    => $cnt("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=? AND trip_date=CURDATE() AND status='Completed'", [$d]),
                'delayedTrips'      => $cnt("SELECT COUNT(DISTINCT tm.bus_reg_no) FROM tracking_monitoring tm JOIN sltb_buses b ON tm.bus_reg_no=b.reg_no AND b.sltb_depot_id=? WHERE tm.operational_status='Delayed' AND tm.track_id IN (SELECT MAX(t2.track_id) FROM tracking_monitoring t2 JOIN sltb_buses b2 ON b2.reg_no=t2.bus_reg_no AND b2.sltb_depot_id=? GROUP BY t2.bus_reg_no)", [$d, $d]),
                'driversPresent'    => $driversPresent,
                'conductorsPresent' => $conductorsPresent,
                'driversAbsent'     => $driversAbsent,
                'conductorsAbsent'  => $conductorsAbsent,
                'attendanceMarked'  => $attendanceMarked,
            ];
        } catch (\Throwable $e) {
            return $zero;
        }
    }
}

