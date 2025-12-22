<?php
namespace App\models\depot_officer;

// If you still have autoload cache issues, uncomment next line once to force-load base:
// require_once dirname(__DIR__) . '/common/BaseModel.php';

use App\models\common\BaseModel;

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

    // Assignments
    public function todayAssignments(int $depotId): array { return $this->assign->todayAssignments($depotId); }
    public function createAssignment(int $depotId, array $d): bool { return $this->assign->createAssignment($depotId,$d); }
    public function deleteAssignment(int $depotId, int $ttId): void { $this->assign->deleteAssignment($depotId,$ttId); }

    // Special timetables
    public function createSpecialTimetable(int $depotId, array $d): bool { return $this->special->createSpecial($depotId,$d); }
    public function deleteSpecialTimetable(int $depotId, int $ttId): void { $this->special->deleteSpecial($depotId,$ttId); }
    public function specialTimetables(int $depotId): array { return $this->special->listSpecial($depotId); }

    // Messages
    public function sendMessage(int $depotId, array $userIds, string $text): bool { return $this->msg->send($depotId,$userIds,$text); }
    public function recentMessages(int $depotId, int $myId, int $limit=20): array { return $this->msg->recent($depotId,$limit); }

    // Tracking
    public function trackingLogs(int $depotId, string $from, string $to): array { return $this->track->logs($depotId,$from,$to); }

    // Reports
    public function kpiSummary(int $depotId, string $from, string $to): array { return $this->report->kpis($depotId,$from,$to); }
    public function buildCsvReport(int $depotId, string $from, string $to): string { return $this->report->csv($depotId,$from,$to); }

    // Attendance
    public function attendanceForDate(int $depotId, string $date): array { return $this->att->forDate($depotId,$date); }
    public function markAttendanceBulk(int $depotId, string $date, array $mark): void {
        // Only allow marking attendance for drivers and conductors (by attendance_key)
        $rows = $this->lookup->depotDriversAndConductors($depotId);
        $validKeys = array_column($rows, 'attendance_key');
        $this->att->markBulk($depotId, $date, $mark, $validKeys);
    }
}
