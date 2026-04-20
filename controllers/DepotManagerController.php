<?php
namespace App\controllers;

use App\controllers\BaseController;

// Depot Manager models (place under models/depot_manager/)
use App\models\depot_manager\DashboardModel;
use App\models\depot_manager\FleetModel;
use App\models\depot_manager\FeedbackModel;
use App\models\depot_manager\DriverModel;
use App\models\depot_manager\PerformanceModel;
use App\models\depot_manager\EarningsModel;
use App\models\depot_manager\ProfileModel;

class DepotManagerController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Use a non-admin layout for depot roles if you have it
        $this->setLayout('depot_manager'); // e.g. 'staff' or 'depot_manager'
        $this->requireLogin(['DepotManager']); // role guard via BaseController
    }

    /* =========================
       Dashboard
       ========================= */
    public function dashboard()
    {
        $m = new DashboardModel();
        $this->view('depot_manager', 'dashboard', [
            'todayLabel'  => $m->todayLabel(),
            'pageTitle'   => 'Depot Dashboard',
            'subtitle'    => 'Depot Operations Overview',
            'stats'       => $m->stats(),
            'dailyStats'  => $m->dailyStats(),
            'activeCount' => $m->activeCount(),
            'delayed'     => $m->delayedCount(),
            'issues'      => $m->issuesCount(),
            'routes'      => $m->routes(),
            'depotId'     => (int)($_SESSION['user']['sltb_depot_id'] ?? $_SESSION['user']['depot_id'] ?? 0),
            'depotName'   => $m->depotName(),
        ]);
    }

    /* =========================
       Fleet (buses list & CRUD)
       ========================= */
// at top of DepotManagerController:



public function fleet()
    {
        $m = new FleetModel();

        // Handle AJAX/JS-only actions (create, update, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            $ok  = false;

            if ($act === 'create_bus')    $ok = $m->createBus($_POST);
            if ($act === 'update_bus')    $ok = $m->updateBus($_POST);
            if ($act === 'delete_bus')    $ok = $m->deleteBus($_POST['reg_no'] ?? '');

            // If it's an AJAX call, return JSON
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                      || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => (bool)$ok, 'msg' => $ok ? 'success' : 'error']);
                return;
            }

            // Fallback (shouldn't hit if using JS only)
            return $this->redirect('/M/fleet?msg=' . ($ok ? 'ok' : 'error'));
        }

        // Old GET deletion kept as a harmless fallback (not used by JS flow)
        if (isset($_GET['delete'])) {
            $ok = $m->deleteBus($_GET['delete']);
            return $this->redirect('/M/fleet?msg=' . ($ok ? 'bus_deleted' : 'bus_error'));
        }

        // collect filters from querystring
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'bus'         => trim($_GET['bus'] ?? ''),
            'route'       => trim($_GET['route'] ?? ''),
            'status'      => trim($_GET['status'] ?? ''),
            'capacity'    => trim($_GET['capacity'] ?? ''),
            'assignment'  => trim($_GET['assignment'] ?? ''),
            'bus_class'   => trim($_GET['bus_class'] ?? ''),
            'model'       => trim($_GET['model'] ?? ''),
            'year_range'  => trim($_GET['year_range'] ?? ''),
        ];

        $this->view('depot_manager', 'fleet_new', [
            'summary' => $m->summaryCards($filters),
            'rows'    => $m->list($filters),
            'routes'  => $m->routes(),
            'buses'   => $m->buses(),
            'filters' => $filters,
            'msg'     => $_GET['msg'] ?? null,
        ]);
    }

    public function busProfile()
    {
        $busReg = trim($_GET['reg_no'] ?? '');
        if ($busReg === '') {
            if (!empty($_GET['json'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'msg' => 'missing_reg_no']);
                return;
            }
            return $this->redirect('/M/fleet');
        }

        $m = new FleetModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            if (!empty($_GET['json'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'msg' => 'not_found']);
                return;
            }
            return $this->redirect('/M/fleet');
        }

        if (!empty($_GET['json'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'bus' => $bus]);
            return;
        }

        $this->view('depot_manager', 'bus_profile', [
            'bus' => $bus,
        ]);
    }

    public function reverseGeocode()
    {
        header('Content-Type: application/json; charset=utf-8');

        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'name' => null]);
            return;
        }

        $lat = (float)$lat;
        $lng = (float)$lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'name' => null]);
            return;
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=16&addressdetails=1&lat=' . rawurlencode((string)$lat) . '&lon=' . rawurlencode((string)$lng);

        $payload = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'NexBus/1.0 (depot-manager)',
            ]);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res !== false && $code >= 200 && $code < 300) {
                $payload = json_decode($res, true);
            }
        }

        if (!$payload) {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 5,
                    'header'  => "Accept: application/json\r\nUser-Agent: NexBus/1.0 (depot-manager)\r\n",
                ]
            ]);
            $res = @file_get_contents($url, false, $ctx);
            if ($res !== false) {
                $payload = json_decode($res, true);
            }
        }

        $name = null;
        if (is_array($payload)) {
            $a = (array)($payload['address'] ?? []);
            $name = $a['road']
                ?? $a['suburb']
                ?? $a['neighbourhood']
                ?? $a['city_district']
                ?? $a['city']
                ?? $a['town']
                ?? $a['village']
                ?? $a['county']
                ?? ($payload['display_name'] ?? null);
        }

        if (!$name) {
            $name = number_format($lat, 5, '.', '') . ', ' . number_format($lng, 5, '.', '');
        }

        echo json_encode(['ok' => true, 'name' => $name]);
    }

    /* =========================
       Passenger Feedback / Complaints
       ========================= */
    public function feedback()
    {
        $m = new FeedbackModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            // New UI actions
            if ($act === 'reply') {
                $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
                $msg = $_POST['message'] ?? ($_POST['response'] ?? '');
                // Mark in progress when replying
                if ($id !== '') $m->updateStatus((string)$id, 'In Progress');
                $m->sendResponse((string)$id, (string)$msg);
                return $this->redirect('/M/feedback?msg=replied');
            }

            if ($act === 'resolve') {
                $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
                $note = $_POST['note'] ?? ($_POST['message'] ?? ($_POST['response'] ?? ''));
                if ($id !== '') $m->updateStatus((string)$id, 'Resolved');
                if (trim((string)$note) !== '') $m->sendResponse((string)$id, (string)$note);
                return $this->redirect('/M/feedback?msg=resolved');
            }

            if ($act === 'close') {
                $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
                if ($id !== '') $m->updateStatus((string)$id, 'Closed');
                return $this->redirect('/M/feedback?msg=closed');
            }

            if ($act === 'assign') {
                $m->assign($_POST);
                return $this->redirect('/M/feedback?msg=assigned');
            }
        }

        $this->view('depot_manager', 'feedback', [
            'feedback_refs' => $m->getAllIds(),
            'feedback_list' => $m->getAll(),
        ]);
    }

    /* =========================
       Drivers & Conductors
       ========================= */
    public function drivers()
    {
        $m = new DriverModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            // Driver create/update/delete
            if ($act === 'create_driver' || $act === 'create') {
                $m->createDriver($_POST);
                return $this->redirect('/M/drivers?msg=created');
            }

            if ($act === 'update_driver' || $act === 'update') {
                $m->updateDriver($_POST);
                return $this->redirect('/M/drivers?msg=updated');
            }

            if ($act === 'delete_driver' || $act === 'delete') {
                $m->deleteDriver((int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0));
                return $this->redirect('/M/drivers?msg=deleted');
            }

            // Conductor create/update/delete
            if ($act === 'create_conductor') {
                $m->createConductor($_POST);
                return $this->redirect('/M/drivers?msg=conductor_created');
            }
            if ($act === 'update_conductor') {
                $m->updateConductor($_POST);
                return $this->redirect('/M/drivers?msg=conductor_updated');
            }
            if ($act === 'delete_conductor') {
                $m->deleteConductor((int)($_POST['private_conductor_id'] ?? $_POST['conductor_id'] ?? 0));
                return $this->redirect('/M/drivers?msg=conductor_deleted');
            }
        }

        // Provide drivers/conductors/opId to match bus_owner view shape
        $this->view('depot_manager', 'drivers', [
            'metrics'    => $m->metrics(),
            'recent'     => $m->driverActivities(),
            'recentCon'  => $m->conductorActivities(),
            'drivers'    => $m->allDrivers(),
            'conductors' => $m->allConductors(),
            'opId'       => $m->getResolvedOperatorId(),
        ]);
    }

    /* =========================
       Special Timetables (copied behavior from DepotOfficer)
       Uses depot_officer model helpers to manage special timetables
       but renders the depot_manager view and routes under /M/
       ========================= */
    public function timetables()
    {
        $off = new \App\models\depot_officer\DepotOfficerModel();
        $u = $off->me();
        $dep = $off->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create_special_tt') {
                $ok = $off->createSpecialTimetable($dep, $_POST);
                $this->redirect('/M/timetables?msg=' . ($ok ? 'created' : 'error'));
                return;
            }
            if ($act === 'delete_special_tt' && !empty($_POST['timetable_id'])) {
                $off->deleteSpecialTimetable($dep, (int)$_POST['timetable_id']);
                $this->redirect('/M/timetables?msg=deleted');
                return;
            }
            if ($act === 'edit_special_tt' && !empty($_POST['timetable_id'])) {
                $stm = new \App\models\depot_officer\SpecialTimetableModel();
                $ok = $stm->updateSpecial($dep, $_POST);
                $this->redirect('/M/timetables?msg=' . ($ok ? 'updated' : 'error'));
                return;
            }
        }

        $filters = [
            'from'  => trim($_GET['from'] ?? ''),
            'to'    => trim($_GET['to'] ?? ''),
            'month' => trim($_GET['month'] ?? ''),
            'year'  => trim($_GET['year'] ?? ''),
            'bus'   => trim($_GET['bus'] ?? ''),
            'route' => trim($_GET['route'] ?? ''),
        ];

        if ($filters['month'] !== '' && $filters['year'] === '') {
            $filters['year'] = date('Y');
        }

        $special_tt = $off->specialTimetables($dep);
        
        // Apply bus filter
        if ($filters['bus']) {
            $special_tt = array_filter($special_tt, function($row) use ($filters) {
                return $row['bus_reg_no'] === $filters['bus'];
            });
        }
        
        // Apply route filter
        if ($filters['route']) {
            $special_tt = array_filter($special_tt, function($row) use ($filters) {
                return $row['route_no'] === $filters['route'];
            });
        }
        
        // Apply date range filter
        if ($filters['from'] || $filters['to'] || $filters['year']) {
            $from = $filters['from'];
            $to = $filters['to'];
            if ($from || $to) {
                if (!$from) $from = $to;
                if (!$to) $to = $from;
            } else {
                $year = (int)$filters['year'];
                $month = (int)$filters['month'];
                $from = sprintf('%04d-%02d-01', $year, $month ?: 1);
                $to = $month ? date('Y-m-t', strtotime($from)) : sprintf('%04d-12-31', $year);
            }

            if ($from && $to) {
                $special_tt = array_filter($special_tt, function($row) use ($from, $to) {
                    $start = $row['effective_from'] ?: '0000-00-00';
                    $end = $row['effective_to'] ?: '9999-12-31';
                    return $start <= $to && $end >= $from;
                });
            }
        }
        
        $special_tt = array_values($special_tt);

        // Show latest timetable entries first in card view.
        usort($special_tt, function ($a, $b) {
            return ((int)($b['timetable_id'] ?? 0)) <=> ((int)($a['timetable_id'] ?? 0));
        });

        $this->view('depot_manager', 'timetables', [
            // Use the same depot-scoped route list as the rest of this page.
            'routes' => $off->routes($dep),
            'buses'  => $off->depotBuses($dep),
            'special_tt' => $special_tt,
            'filters' => $filters,
        ]);
    }

    /* =========================
       Performance (scores, top lists)
       ========================= */
    public function performance()
    {
        $m = new PerformanceModel();

        $routeNo = trim($_GET['route_no'] ?? '');
        $busReg  = trim($_GET['bus_reg'] ?? '');
        $filters = [
            'route_no' => $routeNo,
            'bus_reg'  => $busReg,
            'date'     => trim($_GET['date'] ?? ''),
        ];

        $metrics = $m->getPerformanceMetricsForSLTB($filters);
        $kpi = [
            'delayedToday' => $metrics['delayed_buses'],
            'avgRating'    => $metrics['average_rating'] ?? 0,
            'speedViol'    => $metrics['speed_violations'],
            'longWaitPct'  => $metrics['long_wait_rate'],
        ];

        $analytics = [
            '_fromServer' => true,
            'kpi' => $kpi,
            'busStatus' => $m->getFleetStatusComparisonData($filters),
            'delayedByRoute' => $m->getDelayedByRouteData($filters),
            'speedByBus' => $m->getSpeedByBusData($filters),
            'revenue' => $m->getRevenueData($filters),
            'waitTime' => $m->getWaitTimeData($filters),
            'complaintsByRoute' => $m->getComplaintsByRouteData($filters),
        ];

        $this->view('depot_manager', 'performance', [
            'kpi' => $kpi,
            'filters' => $filters,
            'depotName' => method_exists($m, 'depotName') ? $m->depotName() : 'SLTB Depot',
            'analyticsJson' => json_encode(
                $analytics,
                JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK |
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
            'routes' => $m->getSLTBRoutes(),
            'buses' => $m->getSLTBBuses(),
            'msg' => $_GET['msg'] ?? null,
        ]);
    }

    private function performanceFiltersFromQuery(): array
    {
        return [
            'route_no' => trim($_GET['route_no'] ?? ''),
            'bus_reg' => trim($_GET['bus_reg'] ?? ''),
            'date' => trim($_GET['date'] ?? ''),
        ];
    }

    public function delayedModal()
    {
        $m = new PerformanceModel();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($m->getDelayedTodayDetail($this->performanceFiltersFromQuery()), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }

    public function ratingModal()
    {
        $m = new PerformanceModel();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($m->getRatingDetail($this->performanceFiltersFromQuery()), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }

    public function speedModal()
    {
        $m = new PerformanceModel();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($m->getSpeedViolationsDetail($this->performanceFiltersFromQuery()), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }

    public function waitModal()
    {
        $m = new PerformanceModel();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($m->getLongWaitDetail($this->performanceFiltersFromQuery()), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }

    /**
     * /M/live — depot-scoped live tracking JSON endpoint.
     */
    public function depotLive(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? $_SESSION['user']['depot_id'] ?? 0);
        if ($depotId <= 0 || !isset($GLOBALS['db'])) {
            echo '[]';
            return;
        }

        $pdo = $GLOBALS['db'];

        try {
            $stmt = $pdo->prepare(
                "SELECT
                     tm.bus_reg_no          AS busId,
                     tm.operator_type       AS operatorType,
                     ROUND(tm.speed, 1)     AS speedKmh,
                     tm.lat,
                     tm.lng,
                     tm.heading,
                     tm.operational_status  AS operationalStatus,
                     tm.snapshot_at         AS snapshotAt,
                     r.route_no             AS routeNo,
                     d.name                 AS depot,
                     sb.sltb_depot_id       AS depotId
                 FROM tracking_monitoring tm
                 INNER JOIN (
                     SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                     FROM tracking_monitoring
                     WHERE operator_type = 'SLTB'
                     GROUP BY bus_reg_no
                 ) latest
                     ON latest.bus_reg_no = tm.bus_reg_no
                    AND latest.max_snap = tm.snapshot_at
                 JOIN sltb_buses sb
                     ON sb.reg_no = tm.bus_reg_no
                    AND sb.sltb_depot_id = :depot_id
                    AND LOWER(COALESCE(sb.status, 'active')) = 'active'
                 LEFT JOIN sltb_depots d
                     ON d.sltb_depot_id = sb.sltb_depot_id
                 LEFT JOIN routes r
                     ON r.route_id = tm.route_id
                 WHERE tm.operator_type = 'SLTB'
                 ORDER BY tm.snapshot_at DESC"
            );
            $stmt->execute([':depot_id' => $depotId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[depotLive] query error: ' . $e->getMessage());
            echo '[]';
            return;
        }

        if (empty($rows)) {
            echo '[]';
            return;
        }

        $out = array_map(static function (array $r) use ($depotId): array {
            return [
                'busId' => $r['busId'],
                'routeNo' => $r['routeNo'] ?? '',
                'speedKmh' => (float)($r['speedKmh'] ?? 0),
                'operatorType' => 'SLTB',
                'depot' => $r['depot'] ?: ('Depot #' . $depotId),
                'depotId' => $depotId,
                'owner' => null,
                'ownerId' => null,
                'lat' => $r['lat'] !== null ? (float)$r['lat'] : null,
                'lng' => $r['lng'] !== null ? (float)$r['lng'] : null,
                'heading' => $r['heading'] !== null ? (int)$r['heading'] : null,
                'operationalStatus' => $r['operationalStatus'] ?? 'OnTime',
                'snapshotAt' => $r['snapshotAt'],
                'updatedAt' => $r['snapshotAt'],
                'inDb' => true,
            ];
        }, $rows);

        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }

    public function performanceDetails()
    {
        $m = new PerformanceModel();

        $chart = trim($_GET['chart'] ?? 'bus_status');
        $chartMeta = [
            'bus_status' => 'Bus Status',
            'delayed_by_route' => 'Delayed Buses by Route',
            'speed_by_bus' => 'High Speed Violations by Bus',
            'revenue' => 'Revenue',
            'wait_time' => 'Bus Wait Time Distribution',
            'complaints_by_route' => 'Complaints by Route',
        ];
        if (!isset($chartMeta[$chart])) {
            $chart = 'bus_status';
        }

        $routeNo = trim($_GET['route_no'] ?? '');
        $busReg = trim($_GET['bus_reg'] ?? '');
        $filters = [
            'route_no' => $routeNo,
            'bus_reg' => $busReg,
        ];

        $analytics = [
            'busStatus' => $m->getBusStatusData($filters),
            'delayedByRoute' => $m->getDelayedByRouteData($filters),
            'speedByBus' => $m->getSpeedByBusData($filters),
            'revenue' => $m->getRevenueData($filters),
            'waitTime' => $m->getWaitTimeData($filters),
            'complaintsByRoute' => $m->getComplaintsByRouteData($filters),
        ];

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

            $params = [];
            $where = ["x.rn = 1", "x.operator_type = 'SLTB'"];
            $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? $_SESSION['user']['depot_id'] ?? 0);
            if ($depotId > 0) {
                $where[] = 'sb.sltb_depot_id = :depot_id';
                $params[':depot_id'] = $depotId;
            }
            if ($routeNo !== '') {
                $where[] = 'r.route_no = :route_no';
                $params[':route_no'] = $routeNo;
            }
            if ($busReg !== '') {
                $where[] = 'x.bus_reg_no LIKE :bus_reg';
                $params[':bus_reg'] = '%' . $busReg . '%';
            }

            $sql = "SELECT
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
                    ) x
                    LEFT JOIN routes r ON r.route_id = x.route_id
                    LEFT JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY x.snapshot_at DESC
                    LIMIT 250";
            $st = $GLOBALS['db']->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

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
            $columns = [
                'route_no' => 'Route',
                'delayed' => 'Delayed',
                'total' => 'Total',
                'delay_rate' => 'Delay Rate',
            ];
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
                ['title' => 'Buses', 'value' => (string)count($rows), 'hint' => 'In ranking'],
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

        $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? $_SESSION['user']['depot_id'] ?? 0);
        $sumWhere = ["x.rn = 1", "x.operator_type = 'SLTB'"];
        $sumParams = [];
        if ($depotId > 0) {
            $sumWhere[] = 'sb.sltb_depot_id = :sum_depot_id';
            $sumParams[':sum_depot_id'] = $depotId;
        }
        if ($routeNo !== '') {
            $sumWhere[] = 'r.route_no = :sum_route_no';
            $sumParams[':sum_route_no'] = $routeNo;
        }
        if ($busReg !== '') {
            $sumWhere[] = 'x.bus_reg_no LIKE :sum_bus_reg';
            $sumParams[':sum_bus_reg'] = '%' . $busReg . '%';
        }
        $sumWhereSql = implode(' AND ', $sumWhere);

        $sumBaseSql = "FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            LEFT JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no
            LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
            WHERE $sumWhereSql";

        $byRouteColumns = [
            'route_no' => 'Route',
            'total_buses' => 'Total Buses',
            'delayed_buses' => 'Delayed Buses',
            'avg_speed' => 'Avg Speed (km/h)',
        ];
        $stRoute = $GLOBALS['db']->prepare(
            "SELECT
                COALESCE(r.route_no, '-') AS route_no,
                COUNT(*) AS total_buses,
                SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
                ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
             $sumBaseSql
             GROUP BY COALESCE(r.route_no, '-')
             ORDER BY total_buses DESC"
        );
        $stRoute->execute($sumParams);
        $byRouteRows = $stRoute->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $byDepotColumns = [
            'depot_owner' => 'Depot / Owner',
            'total_buses' => 'Total Buses',
            'delayed_buses' => 'Delayed Buses',
            'avg_speed' => 'Avg Speed (km/h)',
        ];
        $stDepot = $GLOBALS['db']->prepare(
            "SELECT
                COALESCE(d.name, 'Depot') AS depot_owner,
                COUNT(*) AS total_buses,
                SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
                ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
             $sumBaseSql
             GROUP BY COALESCE(d.name, 'Depot')
             ORDER BY total_buses DESC"
        );
        $stDepot->execute($sumParams);
        $byDepotRows = $stDepot->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->view('support', 'analytics_detail', [
            'pageTitle' => 'Performance Drilldown',
            'pageSubtitle' => 'Detailed operational data for ' . $chartMeta[$chart],
            'chartLabel' => $chartMeta[$chart],
            'detailPath' => '/M/performance/details',
            'backUrl' => '/M/performance?' . http_build_query(array_filter([
                'route_no' => $routeNo,
                'bus_reg' => $busReg,
            ])),
            'filterValues' => [
                'chart' => $chart,
                'route_no' => $routeNo,
                'bus_reg' => $busReg,
            ],
            'filterOptions' => [
                'routes' => $m->getSLTBRoutes(),
                'buses' => $m->getSLTBBuses(),
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


    /* =========================
       Earnings / Revenue
       ========================= */
    public function earnings()
    {
        $m = new EarningsModel();

        if (!isset($_SESSION['dm_earnings_used_tokens']) || !is_array($_SESSION['dm_earnings_used_tokens'])) {
            $_SESSION['dm_earnings_used_tokens'] = [];
        }

        $respondJson = static function (bool $ok, string $message, int $okCode = 200, int $errCode = 400): void {
            header('Content-Type: application/json');
            http_response_code($ok ? $okCode : $errCode);
            echo json_encode(['success' => $ok, 'message' => $message]);
            exit;
        };

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'add' || $act === 'create') {
                $requestToken = trim((string)($_POST['_request_token'] ?? ''));
                if ($requestToken !== '' && isset($_SESSION['dm_earnings_used_tokens'][$requestToken])) {
                    if ($isAjax) {
                        $respondJson(true, 'Duplicate submission ignored');
                    }
                    return $this->redirect('/M/earnings?msg=added');
                }

                $ok = $m->add($_POST);
                if ($ok && $requestToken !== '') {
                    $_SESSION['dm_earnings_used_tokens'][$requestToken] = time();
                    if (count($_SESSION['dm_earnings_used_tokens']) > 100) {
                        asort($_SESSION['dm_earnings_used_tokens']);
                        $_SESSION['dm_earnings_used_tokens'] = array_slice($_SESSION['dm_earnings_used_tokens'], -100, null, true);
                    }
                }
                if ($isAjax) {
                    $respondJson($ok, $ok ? 'Record created successfully' : 'Failed to create record. Please select a bus from your depot.');
                }
                return $this->redirect('/M/earnings?msg=' . ($ok ? 'added' : 'error'));
            }

            if ($act === 'update') {
                $id = (int)($_POST['earning_id'] ?? 0);
                $ok = $m->update($id, $_POST);
                if ($isAjax) {
                    $respondJson($ok, $ok ? 'Record updated successfully' : 'Failed to update record.');
                }
                return $this->redirect('/M/earnings?msg=' . ($ok ? 'updated' : 'error'));
            }

            if ($act === 'delete') {
                $ok = $m->delete((int)($_POST['earning_id'] ?? 0));
                if ($isAjax) {
                    $respondJson($ok, $ok ? 'Record deleted successfully' : 'Failed to delete record.');
                }
                return $this->redirect('/M/earnings?msg=' . ($ok ? 'deleted' : 'error'));
            }

            if ($act === 'import_csv') {
                $m->importCsv($_FILES['file'] ?? null);
                return $this->redirect('/M/earnings?msg=imported');
            }
        }

        // Enhanced data for redesigned dashboard
        $revenueTrend = $m->revenueTrendChart();
        $busRanking = $m->busPerformanceRanking();
        $dailyDistribution = $m->dailyIncomeDistribution();
        $earningsRows = $m->exportRows();
        
        // Get comparison data if buses are selected
        $comparisonData = null;
        $compareB1 = $_GET['compare_b1'] ?? null;
        $compareB2 = $_GET['compare_b2'] ?? null;
        if ($compareB1 && $compareB2) {
            $comparisonData = $m->compareBuses($compareB1, $compareB2);
        }

        $requestToken = bin2hex(random_bytes(16));
        
        $this->view('depot_manager', 'earnings', [
            // Original data
            'top'   => $m->topSummary(),
            'month' => $m->monthlyOverview(),
            'earningsRows' => $earningsRows,
            
            // New chart data
            'revenueTrend' => $revenueTrend,
            'busRanking' => $busRanking,
            'dailyDistribution' => $dailyDistribution,
            
            // Bus list for dropdown
            'allBuses' => $m->getAllBuses(),
            'selectBuses' => $m->getDepotBusesForSelect(),
            'comparisonData' => $comparisonData,
            'compareB1' => $compareB1,
            'compareB2' => $compareB2,
            'requestToken' => $requestToken,
            
            // Chart.js JSON
            'revenueTrendJson' => json_encode($revenueTrend, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),
            'busRankingTopJson' => json_encode(
                ['labels' => array_map(fn($b) => $b['bus'], $busRanking['top'] ?? []),
                 'values' => array_map(fn($b) => $b['revenue'], $busRanking['top'] ?? [])],
                JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
            ),
            'dailyDistributionJson' => json_encode($dailyDistribution, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),
        ]);
    }

    /** /M/earnings/export - Export depot-scoped SLTB earnings as Excel */
    public function exportEarnings()
    {
        $m = new EarningsModel();
        $earnings = $m->exportRows();

        $filename = 'depot_earnings_report_' . date('Y-m-d') . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $total = array_sum(array_map(static fn($e) => (float)($e['amount'] ?? 0), $earnings));
        $x = static fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<Styles>';
        echo '<Style ss:ID="title"><Font ss:Bold="1" ss:Size="14" ss:Color="#80143C" /></Style>';
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF" /><Interior ss:Color="#80143C" ss:Pattern="Solid" /><Alignment ss:Horizontal="Center" /></Style>';
        echo '<Style ss:ID="total"><Font ss:Bold="1" /><Interior ss:Color="#FEF3C7" ss:Pattern="Solid" /></Style>';
        echo '<Style ss:ID="currency"><NumberFormat ss:Format="#,##0.00" /></Style>';
        echo '<Style ss:ID="totalCurrency"><Font ss:Bold="1" /><Interior ss:Color="#FEF3C7" ss:Pattern="Solid" /><NumberFormat ss:Format="#,##0.00" /></Style>';
        echo '<Style ss:ID="even"><Interior ss:Color="#F9FAFB" ss:Pattern="Solid" /></Style>';
        echo '</Styles>';
        echo '<Worksheet ss:Name="Depot Earnings Report"><Table>';
        echo '<Column ss:Width="100" /><Column ss:Width="130" /><Column ss:Width="140" /><Column ss:Width="120" />';
        echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="title"><Data ss:Type="String">NexBus Depot Earnings Report - Generated ' . date('Y-m-d H:i') . '</Data></Cell></Row>';
        echo '<Row />';
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Date</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Bus Reg. No</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Source</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Amount (LKR)</Data></Cell>';
        echo '</Row>';

        foreach ($earnings as $i => $e) {
            $style = ($i % 2 === 1) ? ' ss:StyleID="even"' : '';
            $amount = (float)($e['amount'] ?? 0);
            echo '<Row>';
            echo '<Cell' . $style . '><Data ss:Type="String">' . $x($e['date'] ?? '') . '</Data></Cell>';
            echo '<Cell' . $style . '><Data ss:Type="String">' . $x($e['bus_reg_no'] ?? '') . '</Data></Cell>';
            echo '<Cell' . $style . '><Data ss:Type="String">' . $x($e['source'] ?? '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $amount . '</Data></Cell>';
            echo '</Row>';
        }

        echo '<Row />';
        echo '<Row>';
        echo '<Cell ss:StyleID="total"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '<Cell ss:StyleID="total"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="total"><Data ss:Type="String">' . count($earnings) . ' record(s)</Data></Cell>';
        echo '<Cell ss:StyleID="totalCurrency"><Data ss:Type="Number">' . $total . '</Data></Cell>';
        echo '</Row>';
        echo '</Table></Worksheet></Workbook>';
        exit;
    }

    /* =========================
       Profile
       ========================= */
    public function profile()
    {
        $m = new ProfileModel();
        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'update_details') {
                $ok = $m->updateDetails($uid, $_POST);
                return $this->redirect('/M/profile?' . ($ok ? 'msg=updated' : 'err=' . urlencode($m->getLastError() ?: 'update_failed')));
            }
            if ($act === 'change_password') {
                $ok = $m->changePassword(
                    $uid,
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                return $this->redirect('/M/profile?' . ($ok ? 'msg=password_changed' : 'err=' . urlencode($m->getLastError() ?: 'password_error')));
            }
        }

        $this->view('depot_manager', 'profile', [
            'account' => $m->getAccount($uid),
            'msg'     => $_GET['msg'] ?? null,
            'err'     => $_GET['err'] ?? null,
        ]);
    }

    public function bus_profile()
    {
        $busReg = trim((string)($_GET['bus_reg_no'] ?? ''));
        if ($busReg === '') {
            return $this->redirect('/M/fleet');
        }

        $m = new FleetModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            return $this->redirect('/M/fleet');
        }

        $this->view('depot_officer', 'bus_profile', [
            'bus'         => $bus,
            'tracking'    => [],
            'assignments' => [],
            'trips'       => [],
            'backUrl'     => '/M/fleet',
        ]);
    }
}
