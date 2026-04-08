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
            'me'=>$u,
            'depot'=>$this->m->depot($dep),
            'counts'=>$this->m->dashboardCounts($dep),
            'todayDelayed'=>$this->m->delayedToday($dep),
        ]);
    }

public function assignments()
{
    $m = new AssignmentModel();
    $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
    if (!$depotId) { $this->redirect('/login'); return; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';
        if ($act === 'create_assignment') {
            $res = $m->create($_POST, $depotId);
            if ($res === true || $res === 1 || $res === '1') {
                $this->redirect('?module=depot_officer&page=assignments&msg=created');
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_driver::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('?module=depot_officer&page=assignments&msg=conflict_driver&exists=' . urlencode($existing));
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_conductor::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('?module=depot_officer&page=assignments&msg=conflict_conductor&exists=' . urlencode($existing));
                return;
            }
            $this->redirect('?module=depot_officer&page=assignments&msg=error');
            return;
        }
        if ($act === 'update_assignment') {
            $ok = $m->update($depotId, $_POST);
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'reassign_staff') {
            $ok = $m->reassign(
                $depotId,
                (int)$_POST['assignment_id'],
                (int)$_POST['sltb_driver_id'],
                (int)$_POST['sltb_conductor_id'],
                $_POST['shift'] ?? null
            );
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'delete_assignment') {
            $ok = $m->delete((int)$_POST['assignment_id'], $depotId);
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'deleted' : 'error'));
            return;
        }
    }

    $this->view('depot_officer', 'assignments', [
        'rows'       => $m->allToday($depotId),
        'buses'      => $m->buses($depotId),
        'drivers'    => $m->drivers($depotId),
        'conductors' => $m->conductors($depotId),
        'routes'     => $m->routes(),
        'today'      => date('Y-m-d'),
        'msg'        => $_GET['msg'] ?? null,
    ]);
}





    public function timetables() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->redirect('/O/timetables?msg=readonly');
            return;
        }

        $view = in_array($_GET['view'] ?? '', ['current', 'usual', 'seasonal'], true)
            ? (string)$_GET['view']
            : 'current';

        $date = (string)($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $current = $this->m->currentTimetables($dep, $date);
        $usual = $this->m->usualTimetables($dep);
        $seasonal = $this->m->seasonalTimetables($dep, $date);

        $rows = match ($view) {
            'usual' => $usual,
            'seasonal' => $seasonal,
            default => $current,
        };

        $this->view('depot_officer','timetables',[
            'me'=>$u,
            'selected_view' => $view,
            'selected_date' => $date,
            'rows' => $rows,
            'count_current' => count($current),
            'count_usual' => count($usual),
            'count_seasonal' => count($seasonal),
            'msg'=>$_GET['msg'] ?? null,
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
            $to        = array_values(array_filter(array_map('intval', (array)($_POST['to'] ?? []))));

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

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Persist connection for 5 minutes
        set_time_limit(300);

        // Track last message ID to avoid sending duplicates
        $lastId = (int)($_GET['last_id'] ?? 0);
        $count = 0;
        $maxIterations = 300; // Poll for 5 minutes (1 second per iteration)

        while ($count < $maxIterations) {
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
            $count++;
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

    $this->view('depot_officer', 'trip_logs', [
        'rows' => $rows,
        'date' => $date,
        'routes' => $this->m->routes(),
        'buses'  => $this->m->depotBuses($dep),
        'filters'=> $filters,
    ]);
}


    public function reports() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $filters = [
            'route' => $_GET['route'] ?? '',
            'bus_id' => $_GET['bus_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $tracking = new \App\models\depot_officer\TrackingModel();
            $logs = $tracking->logs($from, $to, $filters);
            $out = fopen('php://temp', 'r+');
            fputcsv($out, ['trip_date', 'route', 'turn_number', 'bus_id', 'departure_time', 'arrival_time', 'status']);
            foreach ($logs as $r) {
                fputcsv($out, [
                    $r['trip_date'] ?? '',
                    $r['route'] ?? '',
                    $r['turn_number'] ?? '',
                    $r['bus_id'] ?? '',
                    $r['departure_time'] ?? '',
                    $r['arrival_time'] ?? '',
                    $r['status'] ?? '',
                ]);
            }
            rewind($out);
            $csv = stream_get_contents($out) ?: '';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="depot-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
            echo $csv; exit;
        }

        $analyticsPack = $this->buildOfficerAnalyticsPack($dep, $from, $to, [
            'route_no' => '',
            'route_id' => (int)($filters['route'] ?? 0),
            'bus_reg' => (string)($filters['bus_id'] ?? ''),
            'status' => (string)($filters['status'] ?? ''),
        ]);

        $this->view('depot_officer','reports',[
            'me'=>$u,
            'from'=>$from,
            'to'=>$to,
            'kpis'=>$analyticsPack['kpis'],
            'analyticsJson'=>json_encode(
                $analyticsPack['chartData'],
                JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK |
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
            'routes'=>$this->m->routes(),
            'buses'=>$this->m->depotBuses($dep),
            'filters'=>$filters,
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
                'avg_delay_min' => 'Avg Delay (min)',
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
                SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql
            GROUP BY COALESCE(r.route_no, '-')
            ORDER BY delayed DESC
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

        $sqlRevenue = "SELECT
                DATE_FORMAT(tm.snapshot_at, '%b %Y') AS month_label,
                YEAR(tm.snapshot_at) AS yr,
                MONTH(tm.snapshot_at) AS mo,
                ROUND(SUM(COALESCE(tm.revenue, 0)) / 1000000, 2) AS revenue_mn
            FROM tracking_monitoring tm
            JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE $whereSql
            GROUP BY YEAR(tm.snapshot_at), MONTH(tm.snapshot_at), DATE_FORMAT(tm.snapshot_at, '%b %Y')
            ORDER BY yr, mo";
        $stRevenue = $GLOBALS['db']->prepare($sqlRevenue);
        $stRevenue->execute($params);
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
            "LOWER(c.type) = 'complaint'",
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
            FROM passenger_feedback c
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
                SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed,
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
                'delayed' => (int)($kpiRow['delayed'] ?? 0),
                'trips' => (int)($tripRow['trips'] ?? 0),
                'avgDelayMin' => (float)($kpiRow['avg_delay'] ?? 0),
                'breakdowns' => (int)($tripRow['breakdowns'] ?? 0),
            ],
            'chartData' => [
                '_fromServer' => true,
                'busStatus' => $busStatus,
                'delayedByRoute' => [
                    'labels' => array_column($delayedRows, 'route_no'),
                    'delayed' => array_map('intval', array_column($delayedRows, 'delayed')),
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
            $mark = $_POST['mark'] ?? [];
            $this->m->markAttendanceBulk($dep, $date, $mark);
            $this->redirect('/O/attendance?date=' . urlencode($date) . '&msg=saved');
            return;
        }

        $this->view('depot_officer','attendance',[
            'me'=>$u,
            'date'=>$date,
            // show drivers & conductors for attendance marking
            'staff'=>$this->m->driversAndConductors($dep),
            'records'=>$this->m->attendanceForDate($dep, $date),
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
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $m = new \App\models\depot_officer\BusProfileModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $this->view('depot_officer','bus_profile',[
            'bus'        => $bus,
            'tracking'   => $m->getTracking($busReg),
            'assignments'=> $m->getAssignments($busReg),
            'trips'      => $m->getTrips($busReg),
        ]);
    }
}
