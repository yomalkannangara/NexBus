<?php
namespace App\models\depot_officer;

// If you still have autoload cache issues, uncomment next line once to force-load base:
// require_once dirname(__DIR__) . '/common/BaseModel.php';

use App\models\common\BaseModel;
use PDO;

class DepotOfficerModel extends BaseModel
{
    private SessionGuard $guard;
    private DepotLookupModel $lookup;
    private DashboardModel $dash;
    private AssignmentModel $assign;
    private SpecialTimetableModel $special;
    private MessageModel $msg;
    private TrackingModel $track;
    private ReportModel $report;
    private AttendanceModel $att;

    public function __construct() {
        parent::__construct();
        $this->guard   = new SessionGuard();
        $this->lookup  = new DepotLookupModel();
        $this->dash    = new DashboardModel();
        $this->assign  = new AssignmentModel();
        $this->special = new SpecialTimetableModel();
        $this->msg     = new MessageModel();
        $this->track   = new TrackingModel();
        $this->report  = new ReportModel();
        $this->att     = new AttendanceModel();
    }

    // Session / role
    public function me(): array { return $this->guard->me(); }
    public function requireDepotOfficer(): void { $this->guard->requireDepotOfficer(); }
    public function myDepotId(array $u): int {
    // 1) try session
    if (!empty($u['sltb_depot_id'])) return (int)$u['sltb_depot_id'];

    // 2) accept either 'user_id' or 'id' from session
    $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
    if (!$uid) return 0;

    // 3) try users.sltb_depot_id
    $st = $this->pdo->prepare("SELECT sltb_depot_id FROM users WHERE user_id=?");
    $st->execute([$uid]);
    $dep = (int)($st->fetchColumn() ?: 0);

    // 4) fallback: users.depot_id (some schemas use this name)
    if (!$dep) {
        $st = $this->pdo->prepare("SELECT depot_id FROM users WHERE user_id=?");
        $st->execute([$uid]);
        $dep = (int)($st->fetchColumn() ?: 0);
    }

    // 5) optional fallback: mapping table if you have one
    if (!$dep) {
        try {
            $st = $this->pdo->prepare("SELECT sltb_depot_id FROM sltb_depot_users WHERE user_id=? ORDER BY is_primary DESC LIMIT 1");
            $st->execute([$uid]);
            $dep = (int)($st->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            // table may not exist; ignore
        }
    }

    // 6) cache into the session for next requests
    if ($dep) $_SESSION['user']['sltb_depot_id'] = $dep;

    return $dep;
}

    // Lookups
public function depot(int $depotId): ?array {
    if (!$depotId) return null;

    // Try sltb_depots first
    try {
        $st = $this->pdo->prepare("SELECT sltb_depot_id AS id, name, code FROM sltb_depots WHERE sltb_depot_id=?");
        $st->execute([$depotId]);
        if ($row = $st->fetch()) return $row;
    } catch (\Throwable $e) {}

    // Fallback to generic depots table
    try {
        $st = $this->pdo->prepare("SELECT depot_id AS id, name, code FROM depots WHERE depot_id=?");
        $st->execute([$depotId]);
        if ($row = $st->fetch()) return $row;
    } catch (\Throwable $e) {}

    return null;
}
    public function depotBuses(int $depotId): array { return $this->lookup->depotBuses($depotId); }
    public function depotDrivers(int $depotId): array { return $this->lookup->depotDrivers($depotId); }
    public function depotStaff(int $depotId): array { return $this->lookup->depotStaff($depotId); }
    public function driversAndConductors(int $depotId): array { return $this->lookup->depotDriversAndConductors($depotId); }
    public function routes(): array { return $this->lookup->routes(); }

    // Dashboard
    public function dashboardCounts(int $depotId): array { return $this->dash->counts($depotId); }
    public function delayedToday(int $depotId): array { return $this->dash->delayedToday($depotId); }
    public function dashboardStats(int $depotId): array { return $this->dash->stats($depotId); }

    // Assignments
    public function todayAssignments(int $depotId): array { return $this->assign->allToday($depotId); }
    public function createAssignment(int $depotId, array $d): mixed { return $this->assign->create($d, $depotId); }
    public function deleteAssignment(int $depotId, int $assignmentId): void { $this->assign->delete($assignmentId, $depotId); }

    // Special timetables
    public function createSpecialTimetable(int $depotId, array $d): bool { return $this->special->createSpecial($depotId,$d); }
    public function deleteSpecialTimetable(int $depotId, int $ttId): void { $this->special->deleteSpecial($depotId,$ttId); }
    public function specialTimetables(int $depotId): array { return $this->special->listSpecial($depotId); }
    public function usualTimetables(int $depotId): array { return $this->special->listUsual($depotId); }
    public function seasonalTimetables(int $depotId, string $refDate): array { return $this->special->listSeasonal($depotId, $refDate); }
    public function currentTimetables(int $depotId, string $refDate): array { return $this->special->listCurrent($depotId, $refDate); }

    // Messages
    public function sendMessage(int $depotId, array $userIds, string $text, string $priority='normal', string $scope='individual', bool $allDepot=false, ?int $senderUserId=null, ?string $senderRole=null, ?string $category=null): bool { return $this->msg->send($depotId,$userIds,$text,$priority,$scope,$allDepot,$senderUserId,$senderRole,$category); }
    public function recentMessages(int $depotId, int $myId, int $limit=20, string $filter='all'): array {
        return call_user_func([$this->msg, 'recent'], $depotId, $myId, $limit, $filter);
    }
    public function markMessageRead(int $messageId, int $userId): void { $this->msg->markRead($messageId, $userId); }
    public function acknowledgeMessage(int $messageId, int $userId): void { $this->msg->acknowledge($messageId, $userId); }
    public function escalateMessage(int $messageId, int $userId): void { $this->msg->escalate($messageId, $userId); }
    public function archiveMessage(int $messageId, int $userId): void { $this->msg->archive($messageId, $userId); }

    // Tracking
    public function trackingLogs(int $depotId, string $from, string $to): array { return $this->track->logs($from, $to); }

    // Reports
    public function kpiSummary(int $depotId, string $from, string $to, array $filters = []): array { return $this->report->kpis($depotId,$from,$to, $filters); }
    public function buildCsvReport(int $depotId, string $from, string $to, array $filters = []): string { return $this->report->csv($depotId,$from,$to, $filters); }

    // Attendance
    public function attendanceForDate(int $depotId, string $date): array { return $this->att->forDate($depotId,$date); }
    public function attendanceSummary(int $depotId, int $days = 30): array { return $this->att->summary($depotId, $days); }
    public function attendanceHistory(int $depotId, string $from, string $to): array { return $this->att->history($depotId, $from, $to); }
    public function markAttendanceBulk(int $depotId, string $date, array $mark): void {
        // Only allow marking attendance for drivers and conductors (by attendance_key)
        $rows = $this->lookup->depotDriversAndConductors($depotId);
        $validKeys = array_column($rows, 'attendance_key');
        $this->att->markBulk($depotId, $date, $mark, $validKeys);
    }

    // Messaging helpers for recipient selection (role/bus/route queries for UI)
    public function availableRoles(int $depotId): array {
        $st = $this->pdo->prepare("SELECT DISTINCT role FROM users WHERE sltb_depot_id=? ORDER BY role");
        $st->execute([$depotId]);
        return array_column($st->fetchAll(PDO::FETCH_ASSOC), 'role');
    }

    public function depotBusesForMessaging(int $depotId): array {
        try {
            $st = $this->pdo->prepare(
                "SELECT reg_no AS bus_id, reg_no AS bus_registration_no FROM sltb_buses WHERE sltb_depot_id=? ORDER BY reg_no"
            );
            $st->execute([$depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function depotRoutesForMessaging(int $depotId): array {
        try {
            $st = $this->pdo->prepare(
                "SELECT route_id, route_name FROM sltb_routes WHERE sltb_depot_id=? ORDER BY route_name"
            );
            $st->execute([$depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            try {
                $st = $this->pdo->prepare(
                    "SELECT route_id, route_no AS route_name FROM routes WHERE is_active=1 ORDER BY route_no+0, route_no"
                );
                $st->execute();
                return $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * HR: Staff Attendance Report
     * Returns one row per staff member summarising their attendance in [from..to].
     */
    public function hrAttendanceReport(int $depotId, string $from, string $to): array
    {
        try {
            // depot_attendance uses mark_absent tinyint (1=absent, 0=present); no status column
            $sql = "
            SELECT
                a.attendance_key,
                COALESCE(
                    d.full_name,
                    c.full_name,
                    CONCAT(TRIM(COALESCE(u.first_name,'')), ' ', TRIM(COALESCE(u.last_name,'')))
                )                                                            AS full_name,
                CASE
                    WHEN a.attendance_key LIKE 'driver:%'    THEN 'Driver'
                    WHEN a.attendance_key LIKE 'conductor:%' THEN 'Conductor'
                    ELSE 'Staff'
                END                                                          AS role,
                SUM(CASE WHEN a.mark_absent = 0 THEN 1 ELSE 0 END)          AS present_days,
                SUM(CASE WHEN a.mark_absent = 1 THEN 1 ELSE 0 END)          AS absent_days,
                0                                                            AS leave_days,
                COUNT(*)                                                     AS total_days,
                MAX(CASE WHEN a.mark_absent = 1 THEN a.work_date ELSE NULL END) AS last_absent_date
            FROM depot_attendance a
            LEFT JOIN sltb_drivers   d ON a.attendance_key LIKE 'driver:%'
                AND d.sltb_driver_id     = CAST(SUBSTRING_INDEX(a.attendance_key,':',-1) AS UNSIGNED)
            LEFT JOIN sltb_conductors c ON a.attendance_key LIKE 'conductor:%'
                AND c.sltb_conductor_id  = CAST(SUBSTRING_INDEX(a.attendance_key,':',-1) AS UNSIGNED)
            LEFT JOIN users          u  ON a.attendance_key LIKE 'user:%'
                AND u.user_id            = CAST(SUBSTRING_INDEX(a.attendance_key,':',-1) AS UNSIGNED)
            WHERE a.sltb_depot_id = ?
              AND a.work_date BETWEEN ? AND ?
            GROUP BY a.attendance_key
            ORDER BY absent_days DESC, full_name
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $from, $to]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $total = max(1, (int)$r['total_days']);
                $r['att_pct']   = round(((int)$r['present_days'] / $total) * 100, 1);
                $r['full_name'] = trim((string)$r['full_name']) ?: 'Unknown';
                $r['trend']     = 'stable'; // no historical split available
                $r['late_arrivals'] = 0;
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            error_log('[hrAttendanceReport] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * HR: Driver Performance Report
     * Returns one row per driver with trip completion/delay/cancellation stats in [from..to].
     */
    public function hrDriverPerformanceReport(int $depotId, string $from, string $to): array
    {
        try {
            $sql = "
            SELECT
                sd.full_name                                                 AS driver_name,
                COALESCE(sd.employee_no, '')                                 AS license_number,
                COUNT(*)                                                     AS trips_assigned,
                SUM(CASE WHEN t.status = 'Completed'  THEN 1 ELSE 0 END)   AS completed,
                SUM(CASE WHEN t.status = 'Cancelled'  THEN 1 ELSE 0 END)   AS cancelled,
                SUM(CASE WHEN t.status = 'InProgress' THEN 1 ELSE 0 END)   AS in_progress,
                SUM(CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN 1 ELSE 0 END)                                 AS `delayed`,
                AVG(
                    CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN TIME_TO_SEC(TIMEDIFF(t.departure_time, t.scheduled_departure_time)) / 60
                         ELSE NULL END
                )                                                            AS avg_delay_min
            FROM sltb_trips t
            JOIN sltb_drivers sd ON sd.sltb_driver_id = t.sltb_driver_id
            WHERE t.sltb_depot_id = ?
              AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
              AND t.sltb_driver_id IS NOT NULL
            GROUP BY t.sltb_driver_id, sd.full_name, sd.employee_no
            ORDER BY trips_assigned DESC, sd.full_name
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $from, $to]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $total = max(1, (int)$r['trips_assigned']);
                $done  = (int)$r['completed'];
                $del   = (int)$r['delayed'];
                $r['on_time_pct']        = max(0.0, round(($done - $del) / $total * 100, 1));
                $r['avg_delay_min']      = $r['avg_delay_min'] !== null ? round((float)$r['avg_delay_min'], 1) : 0.0;
                $r['total_km']           = 0; // not stored in trips table
                // Composite score: on-time 50% + completion 30% + no-cancel 20%
                $completionRate = $done / $total;
                $cancelRate     = (int)$r['cancelled'] / $total;
                $score = ($r['on_time_pct'] * 0.5) + ($completionRate * 30) + ((1 - $cancelRate) * 20);
                $r['performance_score']  = round(min(100, $score), 1);
                $s = $r['performance_score'];
                $r['grade'] = $s >= 85 ? 'A' : ($s >= 70 ? 'B' : ($s >= 55 ? 'C' : 'D'));
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            error_log('[hrDriverPerformanceReport] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trip Completion Rate – daily breakdown of trip statuses for the depot.
     */
    public function tripCompletionReport(int $depotId, string $from, string $to): array
    {
        try {
            $sql = "
            SELECT
                COALESCE(t.trip_date, CURDATE())                              AS trip_date,
                COUNT(*)                                                      AS total_trips,
                SUM(CASE WHEN t.status='Completed'  THEN 1 ELSE 0 END)       AS completed,
                SUM(CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN 1 ELSE 0 END)                                   AS `delayed`,
                SUM(CASE WHEN t.status='Cancelled'  THEN 1 ELSE 0 END)       AS cancelled,
                SUM(CASE WHEN t.status='InProgress' THEN 1 ELSE 0 END)       AS in_progress
            FROM sltb_trips t
            WHERE t.sltb_depot_id = ?
              AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
            GROUP BY COALESCE(t.trip_date, CURDATE())
            ORDER BY trip_date
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $from, $to]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $total = max(1, (int)$r['total_trips']);
                $r['completion_pct'] = round(((int)$r['completed'] / $total) * 100, 1);
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            error_log('[tripCompletionReport] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delay Analysis – per-route delay breakdown for the depot.
     */
    public function delayAnalysisReport(int $depotId, string $from, string $to): array
    {
        try {
            // Delay is computed from departure_time vs scheduled_departure_time
            $sql = "
            SELECT
                COALESCE(r.route_no, 'Unknown')                              AS route_no,
                COALESCE(r.route_no, 'Unknown Route')                        AS route_name,
                COUNT(*)                                                     AS total_trips,
                SUM(CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN 1 ELSE 0 END)                                  AS delayed_trips,
                AVG(
                    CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN TIME_TO_SEC(TIMEDIFF(t.departure_time, t.scheduled_departure_time)) / 60.0
                         ELSE NULL END
                )                                                            AS avg_delay_min,
                MAX(
                    CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                         THEN TIME_TO_SEC(TIMEDIFF(t.departure_time, t.scheduled_departure_time)) / 60.0
                         ELSE 0 END
                )                                                            AS max_delay_min
            FROM sltb_trips t
            LEFT JOIN routes r ON r.route_id = t.route_id
            WHERE t.sltb_depot_id = ?
              AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
            GROUP BY t.route_id, r.route_no
            ORDER BY delayed_trips DESC, avg_delay_min DESC
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $from, $to]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $total = max(1, (int)$r['total_trips']);
                $r['delay_pct']     = round(((int)$r['delayed_trips'] / $total) * 100, 1);
                $r['avg_delay_min'] = $r['avg_delay_min'] !== null ? round((float)$r['avg_delay_min'], 1) : 0.0;
                $r['max_delay_min'] = round((float)($r['max_delay_min'] ?? 0), 1);
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            error_log('[delayAnalysisReport] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Bus Utilization – per-bus trip and assignment counts for the depot.
     */
    public function busUtilizationReport(int $depotId, string $from, string $to): array
    {
        try {
            $days = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
            $sql = "
            SELECT
                b.reg_no                                                      AS bus_reg_no,
                COALESCE(b.bus_model, '')                                     AS bus_make,
                COUNT(DISTINCT t.sltb_trip_id)                               AS total_trips,
                SUM(CASE WHEN t.status='Completed'  THEN 1 ELSE 0 END)       AS completed,
                SUM(CASE WHEN t.departure_time IS NOT NULL
                              AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time
                         THEN 1 ELSE 0 END)                                   AS `delayed`,
                SUM(CASE WHEN t.status='Cancelled'  THEN 1 ELSE 0 END)       AS cancelled,
                COUNT(DISTINCT COALESCE(t.trip_date, CURDATE()))              AS active_days,
                COUNT(DISTINCT a.assignment_id)                               AS assignments_count
            FROM sltb_buses b
            LEFT JOIN sltb_trips t
                   ON t.bus_reg_no = b.reg_no
                  AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
            LEFT JOIN sltb_assignments a
                   ON a.bus_reg_no = b.reg_no
                  AND a.assigned_date BETWEEN ? AND ?
                  AND a.sltb_depot_id = ?
            WHERE b.sltb_depot_id = ?
            GROUP BY b.reg_no, b.bus_model
            ORDER BY total_trips DESC, b.reg_no
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([$from, $to, $from, $to, $depotId, $depotId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['utilization_pct'] = min(100, round(((int)$r['active_days'] / $days) * 100, 1));
                $total = max(1, (int)$r['total_trips']);
                $r['completion_pct'] = (int)$r['total_trips'] > 0
                    ? round(((int)$r['completed'] / $total) * 100, 1)
                    : 0.0;
            }
            unset($r);
            return $rows;
        } catch (\Throwable $e) {
            error_log('[busUtilizationReport] ' . $e->getMessage());
            return [];
        }
    }
}
