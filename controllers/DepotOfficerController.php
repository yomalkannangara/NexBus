<?php
namespace App\controllers;

use App\models\depot_officer\DepotOfficerModel;
use App\models\depot_officer\AssignmentModel;
class DepotOfficerController extends \App\controllers\BaseController {
    private DepotOfficerModel $m;

   public function __construct() {
    parent::__construct();
    $this->setLayout('staff');   // ← switch to the staff layout (not admin)
    $this->m = new \App\models\depot_officer\DepotOfficerModel();
    $this->m->requireDepotOfficer();
    $this->requireLogin(['DepotOfficer']);
}


  public function dashboard() {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $this->view('depot_officer','dashboard',[
            'me'           => $u,
            'depot'        => $this->m->depot($dep),
            'counts'       => $this->m->dashboardCounts($dep),
            'todayDelayed' => $this->m->delayedToday($dep),
            'stats'        => $this->m->dashboardStats($dep),
        ]);
    }

public function assignments()
{
    $m = new AssignmentModel();
    $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
    $actorId = (int)($_SESSION['user']['user_id'] ?? 0);
    $senderRole = (string)($_SESSION['user']['role'] ?? 'DepotOfficer');
    if (!$depotId) { $this->redirect('/login'); return; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';
        if ($act === 'create_assignment') {
            $res = $m->create($_POST, $depotId);
            if ($res === true || $res === 1 || $res === '1') {
                $this->sendAssignmentAutomation(
                    $depotId,
                    $actorId,
                    $senderRole,
                    'created',
                    [
                        'assigned_date' => (string)($_POST['assigned_date'] ?? date('Y-m-d')),
                        'shift' => (string)($_POST['shift'] ?? ''),
                        'bus_reg_no' => (string)($_POST['bus_reg_no'] ?? ''),
                    ],
                    [(int)($_POST['sltb_driver_id'] ?? 0), (int)($_POST['sltb_conductor_id'] ?? 0)]
                );
                $this->redirect('/O/assignments?msg=created');
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_driver::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('/O/assignments?msg=conflict_driver&exists=' . urlencode($existing));
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_conductor::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('/O/assignments?msg=conflict_conductor&exists=' . urlencode($existing));
                return;
            }
            $this->redirect('/O/assignments?msg=error');
            return;
        }
        if ($act === 'update_assignment') {
            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $before = $assignmentId > 0 ? $m->findById($depotId, $assignmentId) : null;
            $ok = $m->update($depotId, $_POST);
            if (is_string($ok) && strpos($ok, 'conflict_driver::') === 0) {
                $existing = explode('::', $ok, 2)[1] ?? '';
                $this->redirect('/O/assignments?msg=conflict_driver&exists=' . urlencode($existing));
                return;
            }
            if (is_string($ok) && strpos($ok, 'conflict_conductor::') === 0) {
                $existing = explode('::', $ok, 2)[1] ?? '';
                $this->redirect('/O/assignments?msg=conflict_conductor&exists=' . urlencode($existing));
                return;
            }
            if ($ok) {
                $after = $assignmentId > 0 ? $m->findById($depotId, $assignmentId) : null;
                $recipients = [
                    (int)($before['sltb_driver_id'] ?? 0),
                    (int)($before['sltb_conductor_id'] ?? 0),
                    (int)($after['sltb_driver_id'] ?? 0),
                    (int)($after['sltb_conductor_id'] ?? 0),
                ];
                $ctx = [
                    'assigned_date' => (string)($after['assigned_date'] ?? $_POST['assigned_date'] ?? date('Y-m-d')),
                    'shift' => (string)($after['shift'] ?? $_POST['shift'] ?? ''),
                    'bus_reg_no' => (string)($after['bus_reg_no'] ?? $_POST['bus_reg_no'] ?? ''),
                ];
                $this->sendAssignmentAutomation($depotId, $actorId, $senderRole, 'updated', $ctx, $recipients);
            }
            $this->redirect('/O/assignments?msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'reassign_staff') {
            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $before = $assignmentId > 0 ? $m->findById($depotId, $assignmentId) : null;
            $ok = $m->reassign(
                $depotId,
                $assignmentId,
                (int)$_POST['sltb_driver_id'],
                (int)$_POST['sltb_conductor_id'],
                $_POST['shift'] ?? null
            );
            if ($ok) {
                $after = $assignmentId > 0 ? $m->findById($depotId, $assignmentId) : null;
                $recipients = [
                    (int)($before['sltb_driver_id'] ?? 0),
                    (int)($before['sltb_conductor_id'] ?? 0),
                    (int)($after['sltb_driver_id'] ?? 0),
                    (int)($after['sltb_conductor_id'] ?? 0),
                ];
                $ctx = [
                    'assigned_date' => (string)($after['assigned_date'] ?? $before['assigned_date'] ?? date('Y-m-d')),
                    'shift' => (string)($after['shift'] ?? $_POST['shift'] ?? $before['shift'] ?? ''),
                    'bus_reg_no' => (string)($after['bus_reg_no'] ?? $before['bus_reg_no'] ?? ''),
                ];
                $this->sendAssignmentAutomation($depotId, $actorId, $senderRole, 'reassigned', $ctx, $recipients);
            }
            $this->redirect('/O/assignments?msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'delete_assignment') {
            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $before = $assignmentId > 0 ? $m->findById($depotId, $assignmentId) : null;
            $ok = $m->delete($assignmentId, $depotId);
            if ($ok && $before) {
                $ctx = [
                    'assigned_date' => (string)($before['assigned_date'] ?? date('Y-m-d')),
                    'shift' => (string)($before['shift'] ?? ''),
                    'bus_reg_no' => (string)($before['bus_reg_no'] ?? ''),
                ];
                $recipients = [
                    (int)($before['sltb_driver_id'] ?? 0),
                    (int)($before['sltb_conductor_id'] ?? 0),
                ];
                $this->sendAssignmentAutomation($depotId, $actorId, $senderRole, 'deleted', $ctx, $recipients, 'urgent');
            }
            $this->redirect('/O/assignments?msg=' . ($ok ? 'deleted' : 'error'));
            return;
        }
    }

    $this->view('depot_officer', 'assignments', [
        'rows'        => $m->allToday($depotId),
        'buses'       => $m->buses($depotId),
        'drivers'     => $m->drivers($depotId),
        'conductors'  => $m->conductors($depotId),
        'routes'      => $m->routes(),
        'today'       => date('Y-m-d'),
        'msg'         => $_GET['msg'] ?? null,
        'availability'=> $m->availability($depotId),
    ]);
}

public function assignmentStaffConflicts()
{
    $m = new AssignmentModel();
    $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
    header('Content-Type: application/json');
    if (!$depotId) { http_response_code(401); echo json_encode(['ok'=>false]); return; }
    $departure = trim((string)($_GET['departure'] ?? ''));
    if (!preg_match('/^\d{2}:\d{2}$/', $departure)) {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_departure']); return;
    }
    $from = trim((string)($_GET['period_from'] ?? date('Y-m-d')));
    $to   = trim((string)($_GET['period_to']   ?? $from));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $from;
    echo json_encode(['ok'=>true] + $m->staffConflictsForTurn((int)$depotId, $departure, $from, $to));
}

public function assignmentShifts()
{
    $m = new AssignmentModel();
    $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
    if (!$depotId) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        return;
    }

    $bus = trim((string)($_GET['bus_reg_no'] ?? ''));
    $date = trim((string)($_GET['date'] ?? date('Y-m-d')));
    if ($bus === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_params']);
        return;
    }

    $rows = $m->shiftsForBus($bus, $date);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'items' => $rows]);
}





    public function timetables() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->redirect('/O/timetables?msg=readonly');
            return;
        }

        $tab = in_array($_GET['tab'] ?? '', ['regular', 'special'], true)
            ? (string)$_GET['tab']
            : 'regular';

        $date = (string)($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        // listUsual returns ALL SLTB timetable rows for this depot.
        // Regular = open-ended schedules (no effective_to).
        // Special = time-bounded overrides (has effective_to).
        $all = $this->m->usualTimetables($dep);
        $regularRows = array_values(array_filter($all, fn($r) => empty(trim((string)($r['effective_to'] ?? '')))));
        $specialRows = array_values(array_filter($all, fn($r) => !empty(trim((string)($r['effective_to'] ?? '')))));

        $depotInfo = $this->m->depot($dep);

        $this->view('depot_officer', 'timetables', [
            'me'            => $u,
            'tab'           => $tab,
            'selected_date' => $date,
            'regular_rows'  => $regularRows,
            'special_rows'  => $specialRows,
            'count_regular' => count($regularRows),
            'count_special' => count($specialRows),
            'depot_name'    => $depotInfo['name'] ?? 'Colombo Depot',
            'msg'           => $_GET['msg'] ?? null,
        ]);
    }

       public function messages(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $uid = (int)($u['user_id'] ?? 0);

        // ── Mark-read (silent AJAX call from the view) ────────────────────
        // Route: POST /O/messages?action=read&id=123
        if (($_GET['action'] ?? '') === 'read' && isset($_GET['id'])) {
            $this->m->markMessageRead((int)$_GET['id'], $uid);
            http_response_code(204);
            exit;
        }

        // ── Acknowledge message ────────────────────────────────────────────
        // Route: POST /O/messages?action=ack&id=123
        if (($_GET['action'] ?? '') === 'ack' && isset($_GET['id'])) {
            $this->m->acknowledgeMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Escalate message ──────────────────────────────────────────────
        // Route: POST /O/messages?action=escalate&id=123
        if (($_GET['action'] ?? '') === 'escalate' && isset($_GET['id'])) {
            $this->m->escalateMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Archive message ───────────────────────────────────────────────
        // Route: POST /O/messages?action=archive&id=123
        if (($_GET['action'] ?? '') === 'archive' && isset($_GET['id'])) {
            $this->m->archiveMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Send ──────────────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
            $text      = trim($_POST['message'] ?? '');
            $priority  = in_array($_POST['priority'] ?? '', ['normal','urgent','critical'], true)
                         ? $_POST['priority'] : 'normal';
            $scope     = in_array($_POST['scope'] ?? '', ['individual','role','depot','bus','route'], true)
                         ? $_POST['scope'] : 'individual';
            $allDepot  = ($_POST['all_depot'] ?? '0') === '1';
            $rawTo     = (array)($_POST['to'] ?? []);

            if ($scope === 'role') {
                $to = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $rawTo)));
            } elseif ($scope === 'bus') {
                $to = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $rawTo)));
            } elseif ($scope === 'route' || $scope === 'individual') {
                $to = array_values(array_filter(array_map('intval', $rawTo)));
            } else {
                $to = [];
            }

            $senderRole = (string)($u['role'] ?? 'DepotOfficer');
            $ok = ($text && ($to || $allDepot))
                ? $this->m->sendMessage($dep, $to, $text, $priority, $scope, $allDepot, $uid, $senderRole)
                  : false;

            $this->redirect('/O/messages?msg=' . ($ok ? 'sent' : 'error'));
            return;
        }

        // ── Render ────────────────────────────────────────────────────────
        $filter = in_array($_GET['filter'] ?? '', ['all','unread','alert','message'], true)
                  ? $_GET['filter'] : 'all';

        $this->view('depot_officer', 'messages', [
            'me'         => $u,
            'staff'      => $this->m->depotStaff($dep),
            'roles'      => $this->m->availableRoles($dep),
            'buses'      => $this->m->depotBusesForMessaging($dep),
            'routes'     => $this->m->depotRoutesForMessaging($dep),
            'recent'     => $this->m->recentMessages($dep, $uid, 50, $filter),
            'msg'        => $_GET['msg'] ?? null,
        ]);
    }

    /**
     * Server-Sent Events (SSE) endpoint for real-time message delivery
     * Route: GET /O/messages/stream (or /O/sse-stream)
     * Opens a persistent connection and pushes new messages to the client
     */
    public function sseStream(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $uid = (int)($u['user_id'] ?? 0);

        // Important: this endpoint stays open for a long time.
        // Release session lock so parallel requests from the same user
        // (page loads, ajax, navigation) do not block at session_start().
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Keep process alive, but close stream ourselves before server hard timeout.
        // This avoids recurring "Maximum execution time" fatals.
        @set_time_limit(0);

        // Track last message ID to avoid sending duplicates
        $lastId = (int)($_GET['last_id'] ?? 0);
        $startTs = time();
        $streamLifetimeSec = 240; // close gracefully before common 300s PHP timeout

        while ((time() - $startTs) < $streamLifetimeSec) {
            if (connection_aborted()) {
                break;
            }

            // Fetch new messages since last_id
            $recent = $this->m->recentMessages($dep, $uid, 50, 'all');
            $recent = array_filter($recent, fn($n) => (int)($n['id'] ?? $n['notification_id'] ?? 0) > $lastId);

            if (!empty($recent)) {
                foreach ($recent as $msg) {
                    $msgId = (int)($msg['id'] ?? $msg['notification_id'] ?? 0);
                    $lastId = max($lastId, $msgId);

                    // Send event to client
                    echo "id: {$msgId}\n";
                    echo "event: message\n";
                    echo "data: " . json_encode([
                        'id'        => $msgId,
                        'type'      => $msg['type'] ?? 'Message',
                        'message'   => $msg['message'] ?? '',
                        'from'      => $msg['full_name'] ?? 'Unknown',
                        'created_at'=> $msg['created_at'] ?? '',
                        'priority'  => $msg['priority'] ?? 'normal',
                    ]) . "\n\n";
                    flush();
                }
            }

            // Heartbeat to keep connection alive
            echo ": heartbeat\n\n";
            flush();

            // Sleep for 1 second before next check
            sleep(1);
        }

        // Close connection gracefully
        echo "event: close\ndata: Connection timeout\n\n";
        exit;
    }

    

public function trip_logs(): void{
    $u = $this->m->me();
    $dep = $this->m->myDepotId($u);

    $date = $_GET['date'] ?? date('Y-m-d');
    $filters = [
        'route' => $_GET['route'] ?? '',
        'bus_id' => $_GET['bus_id'] ?? '',
        'departure_time' => $_GET['departure_time'] ?? '',
        'arrival_time' => $_GET['arrival_time'] ?? '',
        'status' => $_GET['status'] ?? '',
    ];

    $from = $date; $to = $date;

    $m = new \App\models\depot_officer\TrackingModel();
    $rows = $m->logs($from, $to, $filters);

    $hasRunning = count(array_filter($rows, fn($r) => in_array($r['status'] ?? '', ['InProgress', 'Delayed']))) > 0;

    $this->view('depot_officer', 'trip_logs', [
        'rows'      => $rows,
        'date'      => $date,
        'routes'    => $this->m->routes(),
        'buses'     => $this->m->depotBuses($dep),
        'filters'   => $filters,
        'last_sync' => date('H:i:s'),
        'has_running'=> $hasRunning,
    ]);
}


    public function reports() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');

        $validTypes = ['attendance','driver_performance','trip_completion','delay_analysis','bus_utilization'];
        $reportType = in_array($_GET['report_type'] ?? '', $validTypes, true)
            ? (string)$_GET['report_type']
            : 'attendance';

        $filters = [
            'route'  => $_GET['route']  ?? '',
            'bus_id' => $_GET['bus_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        /* ── CSV export for HR reports ── */
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            if ($reportType === 'attendance') {
                $rows = $this->m->hrAttendanceReport($dep, $from, $to);
                $out = fopen('php://temp', 'r+');
                fputcsv($out, ['Name','Role','Present Days','Absent Days','Leave Days','Attendance %','Last Absent Date']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['full_name'] ?? '',
                        $r['role']      ?? '',
                        $r['present_days'] ?? 0,
                        $r['absent_days']  ?? 0,
                        $r['leave_days']   ?? 0,
                        $r['att_pct']      ?? 0,
                        $r['last_absent_date'] ?? '',
                    ]);
                }
                rewind($out);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="attendance-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
                echo stream_get_contents($out); exit;
            }
            if ($reportType === 'driver_performance') {
                $rows = $this->m->hrDriverPerformanceReport($dep, $from, $to);
                $out = fopen('php://temp', 'r+');
                fputcsv($out, ['Driver Name','Trips Assigned','Completed','Delayed','Cancelled','On-Time %','Avg Delay (min)']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['driver_name']    ?? '',
                        $r['trips_assigned'] ?? 0,
                        $r['completed']      ?? 0,
                        $r['delayed']        ?? 0,
                        $r['cancelled']      ?? 0,
                        $r['on_time_pct']    ?? 0,
                        $r['avg_delay_min']  ?? 0,
                    ]);
                }
                rewind($out);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="driver-performance-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
                echo stream_get_contents($out); exit;
            }
            /* existing operational CSV export */
            $tracking = new \App\models\depot_officer\TrackingModel();
            $logs = $tracking->logs($from, $to, $filters);
            $out = fopen('php://temp', 'r+');
            fputcsv($out, ['trip_date','route','turn_number','bus_id','departure_time','arrival_time','status']);
            foreach ($logs as $r) {
                fputcsv($out, [
                    $r['trip_date'] ?? '', $r['route'] ?? '', $r['turn_number'] ?? '',
                    $r['bus_id'] ?? '', $r['departure_time'] ?? '', $r['arrival_time'] ?? '', $r['status'] ?? '',
                ]);
            }
            rewind($out);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="depot-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
            echo stream_get_contents($out); exit;
        }

        /* ── Fetch HR data for the two HR report types ── */
        $hrRows     = [];
        $hrSummary  = [];
        if ($reportType === 'attendance') {
            $hrRows = $this->m->hrAttendanceReport($dep, $from, $to);
            $totalStaff  = count($hrRows);
            $avgAtt      = $totalStaff ? round(array_sum(array_column($hrRows, 'att_pct')) / $totalStaff, 1) : 0;
            $mostAbsent  = $totalStaff ? $hrRows[0]['full_name'] . ' (' . $hrRows[0]['absent_days'] . ' days)' : '—';
            $hrSummary   = ['total_staff'=>$totalStaff, 'avg_att_pct'=>$avgAtt, 'most_absent'=>$mostAbsent];
        } elseif ($reportType === 'driver_performance') {
            $hrRows = $this->m->hrDriverPerformanceReport($dep, $from, $to);
            $totalTrips  = (int)array_sum(array_column($hrRows, 'trips_assigned'));
            $avgOnTime   = count($hrRows) ? round(array_sum(array_column($hrRows, 'on_time_pct')) / count($hrRows), 1) : 0;
            $avgDelay    = count($hrRows) ? round(array_sum(array_column($hrRows, 'avg_delay_min')) / count($hrRows), 1) : 0;
            $hrSummary   = ['total_trips'=>$totalTrips, 'on_time_pct'=>$avgOnTime, 'avg_delay_min'=>$avgDelay];
        }

        /* ── Operational analytics (kept for operational report types) ── */
        $analyticsPack = $this->buildOfficerAnalyticsPack($dep, $from, $to, [
            'route_no' => '',
            'route_id' => (int)($filters['route'] ?? 0),
            'bus_reg'  => (string)($filters['bus_id'] ?? ''),
            'status'   => (string)($filters['status'] ?? ''),
        ]);

        $this->view('depot_officer', 'reports', [
            'me'           => $u,
            'from'         => $from,
            'to'           => $to,
            'report_type'  => $reportType,
            'hr_rows'      => $hrRows,
            'hr_summary'   => $hrSummary,
            'kpis'         => $analyticsPack['kpis'],
            'analyticsJson'=> json_encode(
                $analyticsPack['chartData'],
                JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK |
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
            'routes'       => $this->m->routes(),
            'buses'        => $this->m->depotBuses($dep),
            'filters'      => $filters,
        ]);
    }

    public function reportDetails() {
        $u = $this->m->me();
        $dep = $this->m->myDepotId($u);

        $chart = trim($_GET['chart'] ?? 'bus_status');
        $chartMeta = [
            'bus_status' => 'Bus Status',
            'delayed_by_route' => 'Delayed Trips by Route',
            'speed_by_bus' => 'Speed Violations by Bus',
            'revenue' => 'Revenue',
            'wait_time' => 'Bus Wait Time Distribution',
            'complaints_by_route' => 'Complaints by Route',
        ];
        if (!isset($chartMeta[$chart])) {
            $chart = 'bus_status';
        }

        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $_GET['to'] ?? date('Y-m-d');
        $routeNo = trim($_GET['route_no'] ?? '');
        $routeId = (int)($_GET['route'] ?? 0);
        $busReg = trim($_GET['bus_reg'] ?? ($_GET['bus_id'] ?? ''));
        $status = trim($_GET['status'] ?? '');

        $filters = [
            'route_no' => $routeNo,
            'route_id' => $routeId,
            'bus_reg' => $busReg,
            'status' => $status,
        ];

        $pack = $this->buildOfficerAnalyticsPack($dep, $from, $to, $filters);
        $analytics = $pack['chartData'];

        $rows = [];
        $columns = [];
        $summaryCards = [];

        if ($chart === 'bus_status') {
            $columns = [
                'bus_reg_no' => 'Bus ID',
                'route_no' => 'Route',
                'operational_status' => 'Status',
                'speed' => 'Speed (km/h)',
                'avg_delay_min' => 'Wait Time (min)',
                'snapshot_at' => 'Last Snapshot',
            ];
            $rows = $pack['busStatusRows'];
            $statusRows = $analytics['busStatus'] ?? [];
            $total = array_sum(array_map(static fn($r) => (int)($r['value'] ?? 0), $statusRows));
            $delayed = 0;
            foreach ($statusRows as $s) {
                if (strcasecmp((string)($s['label'] ?? ''), 'Delayed') === 0) {
                    $delayed += (int)($s['value'] ?? 0);
                }
            }
            $summaryCards = [
                ['title' => 'Total Buses', 'value' => (string)$total, 'hint' => 'Latest snapshots'],
                ['title' => 'Delayed', 'value' => (string)$delayed, 'hint' => 'Current delayed buses'],
                ['title' => 'Filtered Rows', 'value' => (string)count($rows), 'hint' => 'In detail table'],
            ];
        }

        if ($chart === 'delayed_by_route') {
            $columns = ['route_no' => 'Route', 'delayed' => 'Delayed', 'total' => 'Total', 'delay_rate' => 'Delay Rate'];
            $labels = $analytics['delayedByRoute']['labels'] ?? [];
            $delayed = $analytics['delayedByRoute']['delayed'] ?? [];
            $total = $analytics['delayedByRoute']['total'] ?? [];
            foreach ($labels as $i => $label) {
                $d = (int)($delayed[$i] ?? 0);
                $t = (int)($total[$i] ?? 0);
                $rows[] = [
                    'route_no' => $label,
                    'delayed' => $d,
                    'total' => $t,
                    'delay_rate' => $t > 0 ? round(($d / $t) * 100, 1) . '%' : '0%',
                ];
            }
            $summaryCards = [
                ['title' => 'Routes', 'value' => (string)count($rows), 'hint' => 'Included in chart'],
                ['title' => 'Total Delayed', 'value' => (string)array_sum(array_column($rows, 'delayed')), 'hint' => 'Across listed routes'],
            ];
        }

        if ($chart === 'speed_by_bus') {
            $columns = ['bus_reg_no' => 'Bus ID', 'violations' => 'Speed Violations'];
            $labels = $analytics['speedByBus']['labels'] ?? [];
            $values = $analytics['speedByBus']['values'] ?? [];
            foreach ($labels as $i => $label) {
                $rows[] = ['bus_reg_no' => $label, 'violations' => (int)($values[$i] ?? 0)];
            }
            $summaryCards = [
                ['title' => 'Buses', 'value' => (string)count($rows), 'hint' => 'With violations'],
                ['title' => 'Total Violations', 'value' => (string)array_sum(array_column($rows, 'violations')), 'hint' => 'Chart total'],
            ];
        }

        if ($chart === 'revenue') {
            $columns = ['period' => 'Month', 'revenue_mn' => 'Revenue (LKR Mn)'];
            $labels = $analytics['revenue']['labels'] ?? [];
            $values = $analytics['revenue']['values'] ?? [];
            foreach ($labels as $i => $label) {
                $rows[] = ['period' => $label, 'revenue_mn' => number_format((float)($values[$i] ?? 0), 2)];
            }
            $summaryCards = [
                ['title' => 'Months', 'value' => (string)count($rows), 'hint' => 'Trend points'],
                ['title' => 'Total Revenue', 'value' => number_format(array_sum(array_map('floatval', $values)), 2) . ' Mn', 'hint' => 'Summed trend'],
            ];
        }

        if ($chart === 'wait_time') {
            $columns = ['bucket' => 'Wait Time Bucket', 'count' => 'Count'];
            foreach (($analytics['waitTime'] ?? []) as $item) {
                $rows[] = [
                    'bucket' => (string)($item['label'] ?? ''),
                    'count' => (int)($item['value'] ?? 0),
                ];
            }
            $summaryCards = [
                ['title' => 'Buckets', 'value' => (string)count($rows), 'hint' => 'Wait-time groups'],
                ['title' => 'Total Records', 'value' => (string)array_sum(array_column($rows, 'count')), 'hint' => 'Across all buckets'],
            ];
        }

        if ($chart === 'complaints_by_route') {
            $columns = ['route_no' => 'Route', 'complaints' => 'Complaints'];
            $labels = $analytics['complaintsByRoute']['labels'] ?? [];
            $values = $analytics['complaintsByRoute']['values'] ?? [];
            foreach ($labels as $i => $label) {
                $rows[] = ['route_no' => $label, 'complaints' => (int)($values[$i] ?? 0)];
            }
            $summaryCards = [
                ['title' => 'Routes', 'value' => (string)count($rows), 'hint' => 'With complaints'],
                ['title' => 'Total Complaints', 'value' => (string)array_sum(array_column($rows, 'complaints')), 'hint' => 'Chart total'],
            ];
        }

        $routeOptions = [];
        foreach (($this->m->routes() ?? []) as $r) {
            if (!empty($r['route_no'])) {
                $routeOptions[] = ['route_no' => $r['route_no']];
            }
        }

        $byRouteColumns = [
            'route_no' => 'Route',
            'total_buses' => 'Total Buses',
            'delayed_buses' => 'Delayed Buses',
            'avg_speed' => 'Avg Speed (km/h)',
        ];
        $routeBuckets = [];
        foreach (($pack['busStatusRows'] ?? []) as $r) {
            $key = (string)($r['route_no'] ?? '-');
            if (!isset($routeBuckets[$key])) {
                $routeBuckets[$key] = ['route_no' => $key, 'total_buses' => 0, 'delayed_buses' => 0, 'speed_sum' => 0.0];
            }
            $routeBuckets[$key]['total_buses']++;
            if (strcasecmp((string)($r['operational_status'] ?? ''), 'Delayed') === 0) {
                $routeBuckets[$key]['delayed_buses']++;
            }
            $routeBuckets[$key]['speed_sum'] += (float)($r['speed'] ?? 0);
        }
        $byRouteRows = [];
        foreach ($routeBuckets as $rb) {
            $count = max(1, (int)$rb['total_buses']);
            $rb['avg_speed'] = number_format(((float)$rb['speed_sum']) / $count, 1);
            unset($rb['speed_sum']);
            $byRouteRows[] = $rb;
        }

        $depot = $this->m->depot($dep);
        $depotName = (string)($depot['name'] ?? ('Depot #' . $dep));
        $totalBuses = count($pack['busStatusRows'] ?? []);
        $delayedBuses = 0;
        $speedTotal = 0.0;
        foreach (($pack['busStatusRows'] ?? []) as $r) {
            if (strcasecmp((string)($r['operational_status'] ?? ''), 'Delayed') === 0) {
                $delayedBuses++;
            }
            $speedTotal += (float)($r['speed'] ?? 0);
        }
        $byDepotColumns = [
            'depot_owner' => 'Depot / Owner',
            'total_buses' => 'Total Buses',
            'delayed_buses' => 'Delayed Buses',
            'avg_speed' => 'Avg Speed (km/h)',
        ];
        $byDepotRows = [[
            'depot_owner' => $depotName,
            'total_buses' => $totalBuses,
            'delayed_buses' => $delayedBuses,
            'avg_speed' => $totalBuses > 0 ? number_format($speedTotal / $totalBuses, 1) : '0.0',
        ]];

        $this->view('support', 'analytics_detail', [
            'pageTitle' => 'Depot Reports Drilldown',
            'pageSubtitle' => 'Detailed operational data for ' . $chartMeta[$chart],
            'chartLabel' => $chartMeta[$chart],
            'detailPath' => '/O/reports/details',
            'backUrl' => '/O/reports?' . http_build_query(array_filter([
                'from' => $from,
                'to' => $to,
                'route' => $routeId ?: null,
                'bus_id' => $busReg,
                'status' => $status,
            ])),
            'filterValues' => [
                'chart' => $chart,
                'route_no' => $routeNo,
                'bus_reg' => $busReg,
                'from' => $from,
                'to' => $to,
            ],
            'filterOptions' => [
                'routes' => $routeOptions,
                'buses' => $this->m->depotBuses($dep),
                'showDateRange' => true,
            ],
            'summaryCards' => $summaryCards,
            'columns' => $columns,
            'rows' => $rows,
            'byRouteColumns' => $byRouteColumns,
            'byRouteRows' => $byRouteRows,
            'byDepotColumns' => $byDepotColumns,
            'byDepotRows' => $byDepotRows,
        ]);
    }

    private function buildOfficerAnalyticsPack(int $depotId, string $from, string $to, array $filters): array
    {
        $params = [
            ':depot_id' => $depotId,
            ':from' => $from,
            ':to' => $to,
        ];

        $where = [
            'sb.sltb_depot_id = :depot_id',
            'DATE(tm.snapshot_at) BETWEEN :from AND :to',
        ];

        if (!empty($filters['route_no'])) {
            $where[] = 'r.route_no = :route_no';
            $params[':route_no'] = $filters['route_no'];
        } elseif (!empty($filters['route_id'])) {
            $where[] = 'tm.route_id = :route_id';
            $params[':route_id'] = (int)$filters['route_id'];
        }
        if (!empty($filters['bus_reg'])) {
            $where[] = 'tm.bus_reg_no LIKE :bus_reg';
            $params[':bus_reg'] = '%' . $filters['bus_reg'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'tm.operational_status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);

        $sqlLatest = "SELECT
                x.bus_reg_no,
                COALESCE(r.route_no, '-') AS route_no,
                COALESCE(x.operational_status, 'Unknown') AS operational_status,
                ROUND(COALESCE(x.speed, 0), 1) AS speed,
                ROUND(COALESCE(x.avg_delay_min, 0), 1) AS avg_delay_min,
                DATE_FORMAT(x.snapshot_at, '%Y-%m-%d %H:%i') AS snapshot_at
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
                LEFT JOIN routes r ON r.route_id = tm.route_id
                WHERE $whereSql
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            WHERE x.rn = 1
            ORDER BY x.snapshot_at DESC
            LIMIT 250";
        $stLatest = $GLOBALS['db']->prepare($sqlLatest);
        $stLatest->execute($params);
        $busStatusRows = $stLatest->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $statusCountMap = [];
        foreach ($busStatusRows as $r) {
            $k = $r['operational_status'] ?? 'Unknown';
            if (!isset($statusCountMap[$k])) {
                $statusCountMap[$k] = 0;
            }
            $statusCountMap[$k]++;
        }
        $busStatus = [];
        foreach ($statusCountMap as $label => $value) {
            $busStatus[] = ['label' => $label, 'value' => (int)$value];
        }

        $sqlDelayed = "SELECT
                COALESCE(r.route_no, '-') AS route_no,
                COUNT(*) AS total,
                SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delay_count
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql
            GROUP BY COALESCE(r.route_no, '-')
            ORDER BY delay_count DESC
            LIMIT 10";
        $stDelayed = $GLOBALS['db']->prepare($sqlDelayed);
        $stDelayed->execute($params);
        $delayedRows = $stDelayed->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $sqlSpeed = "SELECT tm.bus_reg_no, COALESCE(SUM(tm.speed_violations), 0) AS violations
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql
            GROUP BY tm.bus_reg_no
            ORDER BY violations DESC
            LIMIT 10";
        $stSpeed = $GLOBALS['db']->prepare($sqlSpeed);
        $stSpeed->execute($params);
        $speedRows = $stSpeed->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Revenue comes from the `earnings` table (tracking_monitoring has no revenue column)
        $revWhere = ['sb.sltb_depot_id = :depot_id', 'e.date BETWEEN :from AND :to', "e.operator_type = 'SLTB'"];
        $revParams = [':depot_id' => $depotId, ':from' => $from, ':to' => $to];
        if (!empty($filters['bus_reg'])) {
            $revWhere[] = 'e.bus_reg_no LIKE :bus_reg';
            $revParams[':bus_reg'] = '%' . $filters['bus_reg'] . '%';
        }
        $revWhereSql = implode(' AND ', $revWhere);
        $sqlRevenue = "SELECT
                DATE_FORMAT(e.date, '%b %Y') AS month_label,
                YEAR(e.date) AS yr,
                MONTH(e.date) AS mo,
                ROUND(SUM(e.amount) / 1000000, 2) AS revenue_mn
            FROM earnings e
            JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no
            WHERE $revWhereSql
            GROUP BY YEAR(e.date), MONTH(e.date), DATE_FORMAT(e.date, '%b %Y')
            ORDER BY yr, mo";
        $stRevenue = $GLOBALS['db']->prepare($sqlRevenue);
        $stRevenue->execute($revParams);
        $revenueRows = $stRevenue->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $sqlWait = "SELECT
                SUM(CASE WHEN tm.avg_delay_min < 5 THEN 1 ELSE 0 END) AS under_5,
                SUM(CASE WHEN tm.avg_delay_min >= 5 AND tm.avg_delay_min < 10 THEN 1 ELSE 0 END) AS between_5_10,
                SUM(CASE WHEN tm.avg_delay_min >= 10 AND tm.avg_delay_min < 15 THEN 1 ELSE 0 END) AS between_10_15,
                SUM(CASE WHEN tm.avg_delay_min >= 15 THEN 1 ELSE 0 END) AS over_15
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql";
        $stWait = $GLOBALS['db']->prepare($sqlWait);
        $stWait->execute($params);
        $waitRow = $stWait->fetch(\PDO::FETCH_ASSOC) ?: [];

        $fbParams = [
            ':depot_id' => $depotId,
            ':from' => $from,
            ':to' => $to,
        ];
        $fbWhere = [
            'sb.sltb_depot_id = :depot_id',
            "DATE(c.created_at) BETWEEN :from AND :to",
            "LOWER(c.category) = 'complaint'",
        ];
        if (!empty($filters['route_no'])) {
            $fbWhere[] = 'r.route_no = :route_no';
            $fbParams[':route_no'] = $filters['route_no'];
        } elseif (!empty($filters['route_id'])) {
            $fbWhere[] = 'c.route_id = :route_id';
            $fbParams[':route_id'] = (int)$filters['route_id'];
        }
        if (!empty($filters['bus_reg'])) {
            $fbWhere[] = 'c.bus_reg_no LIKE :bus_reg';
            $fbParams[':bus_reg'] = '%' . $filters['bus_reg'] . '%';
        }
        $sqlComplaints = "SELECT COALESCE(r.route_no, '-') AS route_no, COUNT(*) AS cnt
            FROM complaints c
            LEFT JOIN routes r ON r.route_id = c.route_id
            JOIN sltb_buses sb ON sb.reg_no = c.bus_reg_no
            WHERE " . implode(' AND ', $fbWhere) . "
            GROUP BY COALESCE(r.route_no, '-')
            ORDER BY cnt DESC
            LIMIT 10";
        $stComplaints = $GLOBALS['db']->prepare($sqlComplaints);
        $stComplaints->execute($fbParams);
        $complaintsRows = $stComplaints->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $sqlKpi = "SELECT
                SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delay_count,
                ROUND(AVG(COALESCE(tm.avg_delay_min, 0)), 1) AS avg_delay
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql";
        $stKpi = $GLOBALS['db']->prepare($sqlKpi);
        $stKpi->execute($params);
        $kpiRow = $stKpi->fetch(\PDO::FETCH_ASSOC) ?: [];

        $tripParams = [':depot_id' => $depotId, ':from' => $from, ':to' => $to];
        $tripWhere = [
            'sb.sltb_depot_id = :depot_id',
            'COALESCE(t.trip_date, CURDATE()) BETWEEN :from AND :to',
        ];
        if (!empty($filters['route_id'])) {
            $tripWhere[] = 't.route_id = :route_id';
            $tripParams[':route_id'] = (int)$filters['route_id'];
        }
        if (!empty($filters['bus_reg'])) {
            $tripWhere[] = 't.bus_reg_no LIKE :bus_reg';
            $tripParams[':bus_reg'] = '%' . $filters['bus_reg'] . '%';
        }
        $sqlTrips = "SELECT
                COUNT(*) AS trips,
                SUM(CASE WHEN t.status = 'Cancelled' THEN 1 ELSE 0 END) AS breakdowns
            FROM sltb_trips t
            JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
            WHERE " . implode(' AND ', $tripWhere);
        $stTrips = $GLOBALS['db']->prepare($sqlTrips);
        $stTrips->execute($tripParams);
        $tripRow = $stTrips->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'kpis' => [
                'delayed' => (int)($kpiRow['delay_count'] ?? 0),
                'trips' => (int)($tripRow['trips'] ?? 0),
                'avgDelayMin' => (float)($kpiRow['avg_delay'] ?? 0),
                'breakdowns' => (int)($tripRow['breakdowns'] ?? 0),
            ],
            'chartData' => [
                '_fromServer' => true,
                'busStatus' => $busStatus,
                'delayedByRoute' => [
                    'labels' => array_column($delayedRows, 'route_no'),
                    'delayed' => array_map('intval', array_column($delayedRows, 'delay_count')),
                    'total' => array_map('intval', array_column($delayedRows, 'total')),
                ],
                'speedByBus' => [
                    'labels' => array_column($speedRows, 'bus_reg_no'),
                    'values' => array_map('intval', array_column($speedRows, 'violations')),
                ],
                'revenue' => [
                    'labels' => array_column($revenueRows, 'month_label'),
                    'values' => array_map('floatval', array_column($revenueRows, 'revenue_mn')),
                ],
                'waitTime' => [
                    ['label' => 'Under 5 min', 'value' => (int)($waitRow['under_5'] ?? 0), 'color' => '#16a34a'],
                    ['label' => '5-10 min', 'value' => (int)($waitRow['between_5_10'] ?? 0), 'color' => '#f3b944'],
                    ['label' => '10-15 min', 'value' => (int)($waitRow['between_10_15'] ?? 0), 'color' => '#f59e0b'],
                    ['label' => 'Over 15 min', 'value' => (int)($waitRow['over_15'] ?? 0), 'color' => '#b91c1c'],
                ],
                'complaintsByRoute' => [
                    'labels' => array_column($complaintsRows, 'route_no'),
                    'values' => array_map('intval', array_column($complaintsRows, 'cnt')),
                ],
            ],
            'busStatusRows' => $busStatusRows,
        ];
    }

    public function attendance() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $date = $_GET['date'] ?? date('Y-m-d');
        if ($date > date('Y-m-d')) {
            $date = date('Y-m-d');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['work_date'] ?? $date;
            if ($date > date('Y-m-d')) {
                $date = date('Y-m-d');
            }

            $attendancePost = (array)($_POST['attendance'] ?? []);
            $notesPost = (array)($_POST['notes'] ?? []);

            $mark = [];
            foreach ($attendancePost as $akey => $status) {
                $status = (string)$status;
                if (!in_array($status, ['Present','Absent','Late','Half_Day'], true)) {
                    $status = 'Present';
                }
                $mark[(string)$akey] = [
                    'status' => $status,
                    'absent' => $status === 'Absent' ? 1 : 0,
                    'notes' => trim((string)($notesPost[$akey] ?? '')),
                ];
            }

            $this->m->markAttendanceBulk($dep, $date, $mark);
            $this->redirect('/O/attendance?date=' . urlencode($date) . '&msg=saved');
            return;
        }

        $histFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-13 days'));
        $histTo   = $_GET['to']   ?? date('Y-m-d');

        $staffRows = $this->m->driversAndConductors($dep);
        $drivers = [];
        $conductors = [];
        foreach ($staffRows as $s) {
            $t = strtolower((string)($s['type'] ?? ''));
            if (!in_array($t, ['driver', 'conductor'], true)) {
                continue;
            }
            $s['status'] = $s['status'] ?? 'Active';
            if ($t === 'driver') {
                $drivers[] = $s;
            } else {
                $conductors[] = $s;
            }
        }

        $history = [];
        $historyError = null;
        try {
            $history = $this->m->attendanceHistory($dep, $histFrom, $histTo);
        } catch (\Throwable $e) {
            $historyError = $e->getMessage();
            $history = [];
        }

        $this->view('depot_officer','attendance',[
            'me'=>$u,
            'date'=>$date,
            'drivers'=>$drivers,
            'conductors'=>$conductors,
            'records'=>$this->m->attendanceForDate($dep, $date),
            'summary'=>$this->m->attendanceSummary($dep, 30),
            'history'=>$history,
            'history_error'=>$historyError,
            'histFrom'=>$histFrom,
            'histTo'=>$histTo,
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    /** /O/profile — account details + change password */
    public function profile() {
        $me = $_SESSION['user'] ?? null;
        if (!$me || empty($me['user_id'])) {
            $this->redirect('/login');
            return;
        }
        $uid = (int)$me['user_id'];

        $m = new \App\models\depot_officer\ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($uid, [
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name'  => trim($_POST['last_name'] ?? ''),
                    'email'      => trim($_POST['email'] ?? ''),
                    'phone'      => trim($_POST['phone'] ?? ''),
                ]);

                // Detect AJAX/JSON request
                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

                if ($ok) {
                    // refresh session copy (optional but keeps UI consistent)
                    if ($fresh = $m->findById($uid)) {
                        $_SESSION['user']['first_name'] = $fresh['first_name'] ?? ($_SESSION['user']['first_name'] ?? '');
                        $_SESSION['user']['last_name']  = $fresh['last_name']  ?? ($_SESSION['user']['last_name'] ?? '');
                        $_SESSION['user']['email']      = $fresh['email']      ?? ($_SESSION['user']['email'] ?? '');
                        $_SESSION['user']['phone']      = $fresh['phone']      ?? ($_SESSION['user']['phone'] ?? '');
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'user' => $fresh ?? $_SESSION['user']]);
                        return;
                    }

                    $this->redirect('/O/profile?msg=updated');
                    return;
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'msg' => 'update_failed']);
                    return;
                }

                $this->redirect('/O/profile?msg=update_failed');
                return;
            }

            if ($act === 'upload_image') {
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_image'];
                    $mimeType = mime_content_type($file['tmp_name']);
                    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                        $this->redirect('/O/profile?msg=invalid_image');
                        return;
                    }
                    $ext = match($mimeType) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    };
                    $filename = "profile_" . $uid . "." . $ext;
                    $uploadDir = dirname(__DIR__) . '/public/uploads/profiles/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $uploadPath = $uploadDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        if ($m->updateProfileImage($uid, '/uploads/profiles/' . $filename)) {
                            if ($fresh = $m->findById($uid)) {
                                $_SESSION['user']['profile_image'] = $fresh['profile_image'] ?? null;
                            }
                            $this->redirect('/O/profile?msg=image_updated');
                            return;
                        }
                    }
                    $this->redirect('/O/profile?msg=upload_failed');
                    return;
                }
                $this->redirect('/O/profile?msg=no_file');
                return;
            }

            if ($act === 'delete_image') {
                if ($m->deleteProfileImage($uid)) {
                    // Delete file from disk if it exists
                    if (!empty($_SESSION['user']['profile_image'])) {
                        $filePath = dirname(__DIR__) . '/public' . $_SESSION['user']['profile_image'];
                        if (file_exists($filePath)) unlink($filePath);
                    }
                    $_SESSION['user']['profile_image'] = null;
                    $this->redirect('/O/profile?msg=image_deleted');
                    return;
                }
                $this->redirect('/O/profile?msg=delete_failed');
                return;
            }

            if ($act === 'change_password') {
                $ok = $m->changePassword(
                    $uid,
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                $this->redirect('/O/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
                return;
            }

            $this->redirect('/O/profile?msg=bad_action');
            return;
        }

        $meFresh = $m->findById($uid) ?: $me;

        $this->view('depot_officer','profile', [
            'me'  => $meFresh,
            'msg' => $_GET['msg'] ?? null,
        ]);
    }

    public function bus_profile()
    {
        $busReg = $_GET['bus_reg_no'] ?? null;
        if (!$busReg) {
            return $this->redirect('/O/dashboard');
        }

        $m = new \App\models\depot_officer\BusProfileModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            return $this->redirect('/O/dashboard');
        }

        $this->view('depot_officer','bus_profile',[
            'bus'        => $bus,
            'tracking'   => $m->getTracking($busReg),
            'assignments'=> $m->getAssignments($busReg),
            'trips'      => $m->getTrips($busReg),
        ]);
    }

    private function sendAssignmentAutomation(
        int $depotId,
        int $senderUserId,
        string $senderRole,
        string $event,
        array $context,
        array $recipientIds,
        string $priority = 'normal'
    ): void {
        $bus = trim((string)($context['bus_reg_no'] ?? ''));
        $date = trim((string)($context['assigned_date'] ?? date('Y-m-d')));
        $shift = trim((string)($context['shift'] ?? ''));

        $labels = [
            'created' => 'Assignment created',
            'updated' => 'Assignment updated',
            'reassigned' => 'Staff reassigned',
            'deleted' => 'Assignment deleted',
        ];
        $label = $labels[$event] ?? 'Assignment updated';

        $message = "OPERATION UPDATE: {$label} for bus {$bus} on {$date}";
        if ($shift !== '') {
            $message .= " ({$shift} shift)";
        }
        $message .= '.';

        $recipients = array_values(array_unique(array_filter(array_map('intval', $recipientIds), static fn($v) => $v > 0)));
        if (!$recipients) {
            return;
        }

        $this->m->sendMessage($depotId, $recipients, $message, $priority, 'individual', false, $senderUserId, $senderRole);
    }
}
