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
    private ComplaintModel $compl;
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
        $this->compl   = new ComplaintModel();
        $this->track   = new TrackingModel();
        $this->report  = new ReportModel();
        $this->att     = new AttendanceModel();
    }

    // Session / role
    public function me(): array { return $this->guard->me(); }
    public function requireDepotOfficer(): void { $this->guard->requireDepotOfficer(); }
    public function myDepotId(array $u): int { return $this->guard->myDepotId($u); }

    // Lookups
    public function depot(int $depotId): ?array { return $this->lookup->depot($depotId); }
    public function depotBuses(int $depotId): array { return $this->lookup->depotBuses($depotId); }
    public function depotDrivers(int $depotId): array { return $this->lookup->depotDrivers($depotId); }
    public function depotStaff(int $depotId): array { return $this->lookup->depotStaff($depotId); }
    public function routes(): array { return $this->lookup->routes(); }

    // Dashboard
    public function dashboardCounts(int $depotId): array { return $this->dash->counts($depotId); }
    public function delayedToday(int $depotId): array { return $this->dash->delayedToday($depotId); }
    public function openComplaints(int $depotId, int $limit=5): array { return $this->dash->openComplaints($depotId, $limit); }

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

    // Complaints
    public function complaintsForDepot(int $depotId, string $status): array { return $this->compl->listByStatus($depotId,$status); }
    public function complaintsAssignedTo(int $depotId, int $userId): array { return $this->compl->listAssignedTo($depotId,$userId); }
    public function assignComplaint(int $depotId, int $complaintId, int $userId): void { $this->compl->take($depotId,$complaintId,$userId); }
    public function replyComplaint(int $depotId, int $complaintId, string $reply, string $status): void { $this->compl->reply($depotId,$complaintId,$reply,$status); }

    // Tracking
    public function trackingLogs(int $depotId, string $from, string $to): array { return $this->track->logs($depotId,$from,$to); }

    // Reports
    public function kpiSummary(int $depotId, string $from, string $to): array { return $this->report->kpis($depotId,$from,$to); }
    public function buildCsvReport(int $depotId, string $from, string $to): string { return $this->report->csv($depotId,$from,$to); }

    // Attendance
    public function attendanceForDate(int $depotId, string $date): array { return $this->att->forDate($depotId,$date); }
    public function markAttendanceBulk(int $depotId, string $date, array $mark): void {
        $validStaff = array_column($this->lookup->depotStaff($depotId),'user_id');
        $this->att->markBulk($depotId,$date,$mark,$validStaff);
    }
}
