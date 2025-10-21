<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class TimekeeperPrivateModel extends BaseModel {
    private SessionGuard $guard;
    private DashboardModel $dash;
    private TimetableModel $tt;
    private TrackingModel $track;
    private ReportModel $report;
    private AttendanceModel $att;

    public function __construct() {
        parent::__construct();
        $this->guard  = new SessionGuard();
        $this->dash   = new DashboardModel();
        $this->tt     = new TimetableModel();
        $this->track  = new TrackingModel();
        $this->report = new ReportModel();
        $this->att    = new AttendanceModel();
    }

    // Session / role
    public function me(): array { return $this->guard->me(); }
    public function requirePrivateTimekeeper(): void { $this->guard->requirePrivateTimekeeper(); }

    // Depot id (tolerant)
    public function myDepotId(array $u): int
{
    // 1) From session (either name)
    if (!empty($u['depot_id']))       return (int)$u['depot_id'];
    if (!empty($u['sltb_depot_id']))  return (int)$u['sltb_depot_id'];

    $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
    if (!$uid) return 0;

    // 2) users.depot_id
    try {
        $st = $this->pdo->prepare("SELECT depot_id FROM users WHERE user_id=?");
        $st->execute([$uid]);
        $dep = (int)($st->fetchColumn() ?: 0);
        if ($dep) { $_SESSION['user']['depot_id'] = $dep; return $dep; }
    } catch (\Throwable $e) {}

    // 3) users.sltb_depot_id
    try {
        $st = $this->pdo->prepare("SELECT sltb_depot_id FROM users WHERE user_id=?");
        $st->execute([$uid]);
        $dep = (int)($st->fetchColumn() ?: 0);
        if ($dep) { $_SESSION['user']['sltb_depot_id'] = $dep; return $dep; }
    } catch (\Throwable $e) {}

    // 4) Optional mapping table (private_depot_users)
    try {
        $st = $this->pdo->prepare("SELECT depot_id FROM private_depot_users WHERE user_id=? ORDER BY is_primary DESC LIMIT 1");
        $st->execute([$uid]);
        $dep = (int)($st->fetchColumn() ?: 0);
        if ($dep) { $_SESSION['user']['depot_id'] = $dep; return $dep; }
    } catch (\Throwable $e) {}

    return 0;
}


    // Dashboard / depot
    public function depot(int $depotId): ?array { return $this->dash->depot($depotId); }
    public function todayStats(int $depotId): array { return $this->dash->todayStats($depotId); }
    public function delayedToday(int $depotId): array { return $this->dash->delayedToday($depotId); }

    // Timetables
    public function todayTimetables(int $depotId): array { return $this->tt->todayTimetables($depotId); }
    public function updateTimetable(array $d): bool { return $this->tt->updateTimetable($d); }

    // Tracking
    public function tripLogs(int $depotId, string $from, string $to): array { return $this->track->tripLogs($depotId,$from,$to); }

    // Reports
    public function kpiSummary(int $depotId, string $from, string $to): array
{
    if ($depotId <= 0) {
        return ['delayed'=>0,'breakdowns'=>0,'avgDelayMin'=>0.0,'speedViolations'=>0,'trips'=>0];
    }
    return $this->report->kpis($depotId, $from, $to);
}

    public function exportCsv(int $depotId, string $from, string $to): string { return $this->report->exportCsv($depotId,$from,$to); }

    // Attendance
    public function staffList(int $depotId): array { return $this->att->staffList($depotId); }
    public function attendanceForDate(int $depotId, string $date): array { return $this->att->attendanceForDate($depotId,$date); }
    public function markAttendance(int $depotId, string $date, array $mark): void { $this->att->markAttendance($depotId,$date,$mark); }
}
