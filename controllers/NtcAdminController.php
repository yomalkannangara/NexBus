<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\ntc_admin\DashboardModel;  // folders: models/ntc_admin/
use App\models\ntc_admin\FareModel;
use App\models\ntc_admin\TimetableModel;
use App\models\ntc_admin\UserModel;
use App\models\ntc_admin\OrgModel;
use App\models\ntc_admin\ProfileModel; // add at top with the other use lines
use App\models\ntc_admin\RouteModel;
use App\models\ntc_admin\AnalyticsModel;


class NtcAdminController extends BaseController {
      public function __construct()
    {
    parent::__construct();
        $this->setLayout('admin'); // or 'staff' / 'owner' / 'passenger'
        $this->requireLogin(['NTCAdmin']);
        }
    public function dashboard() {
        $m = new DashboardModel();
        $this->view('ntc_admin','dashboard',[ 'stats'=>$m->stats(), 'routes'=>$m->routes() ]);
    }
    public function fares() {
        $m = new FareModel();
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
            $m->create($_POST);
            $this->redirect('/A/fares?msg=created');
        }
        if (isset($_GET['delete'])) {
            $m->delete($_GET['delete']);
            $this->redirect('/A/fares?msg=deleted');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
            $m->update($_POST);
            $this->redirect('/A/fares?msg=updated');
        }

        $routes = $m->routes();
        $routeLookup = [];
        foreach ($routes as $r) {
            $routeLookup[$r['route_id']] = $r;
        }

        $routeGroups = [];
        foreach ($m->all() as $f) {
            $rid = $f['route_id'];
            if (!isset($routeGroups[$rid])) {
                $routeGroups[$rid] = [
                    'route_id'     => $rid,
                    'route_no'     => $routeLookup[$rid]['route_no'] ?? '',
                    'name'         => $routeLookup[$rid]['name'] ?? '',
                    'active_types' => [],
                    'fares'        => [],
                ];
            }
            $typeFlags = [
                'super_luxury'    => (int)$f['is_super_luxury_active'] === 1,
                'luxury'          => (int)$f['is_luxury_active'] === 1,
                'semi_luxury'     => (int)$f['is_semi_luxury_active'] === 1,
                'normal_service'  => (int)$f['is_normal_service_active'] === 1,
            ];
            foreach ($typeFlags as $k => $on) {
                if ($on && !in_array($k, $routeGroups[$rid]['active_types'], true)) {
                    $routeGroups[$rid]['active_types'][] = $k;
                }
            }
            $routeGroups[$rid]['fares'][] = $f;
        }

        $this->view('ntc_admin','fares',[
            'routes'      => $routes,
            'routeGroups' => $routeGroups
        ]);
    }
public function timetables() {
    $m = new TimetableModel();

    // --- create / create route / delete stay the same ---
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_route') {
        $m->createRoute($_POST);
        $this->redirect('/A/timetables?msg=route_created');
        return;
    }
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
        $m->create($_POST);
        $this->redirect('/A/timetables?msg=created');
        return;
    }
    if (isset($_GET['delete'])) {
        $m->delete($_GET['delete']);
        $this->redirect('/A/timetables?msg=deleted');
        return;
    }
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
        $m->update($_POST);
        $this->redirect('/A/timetables?msg=updated');
        return;
    }

    // ---------- FILTERS ----------
    $routeInput = trim($_GET['q_route'] ?? '');
    $routeNumber = '';
    if ($routeInput !== '') {
        $match = [];
        if (preg_match('/^[^|\s]+/', $routeInput, $match)) {
            $routeNumber = $match[0]; // take text before space or "|"
        } else {
            $routeNumber = $routeInput;
        }
    }

    // normalize day-of-week: '' or 0..6
    $dayRaw = trim($_GET['q_dow'] ?? '');
    $daySel = '';
    if ($dayRaw !== '') {
        $d = (int)$dayRaw;
        if ($d < 0) $d = 0;
        if ($d > 6) $d = 6;
        $daySel = (string)$d;
    }

    $filters = [
        'route'         => $routeNumber,
        'bus'           => trim($_GET['q_bus'] ?? ''),
        'operator_type' => trim($_GET['q_op'] ?? ''),
        'dow'           => $daySel,
    ];

    $hasFilters = ($filters['route'] !== '')
        || ($filters['bus'] !== '')
        || in_array($filters['operator_type'], ['Private','SLTB'], true)
        || ($filters['dow'] !== '');

    // default: 30, with filters: 50
    $perPage = $hasFilters ? 50 : 30;
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $result = $m->listTimetables($filters, $perPage, $offset);
    $total  = $result['total'];
    $pages  = max(1, (int)ceil($total / $perPage));

    $this->view('ntc_admin','timetables',[
        'routes'     => $m->routes(),
        'rows'       => $result['rows'],
        'counts'     => $m->counts(),
        'owners'     => $m->ownersWithBuses(),
        'depots'     => $m->depotsWithBuses(),
        'buses'      => $m->depotsWithBuses(), // existing
        'filters'    => $filters,
        'pagination' => [
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
            'perPage' => $perPage,
        ],
        'busList'    => $m->busList(), // for type-ahead list
    ]);
}

    public function users() {
        $m = new UserModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'create') {
                $m->create($_POST);
                $this->redirect('/A/users?msg=created');
            }
            if ($act === 'update') {
                $m->update($_POST);
                $this->redirect('/A/users?msg=updated');
            }
                
            if ($act === 'suspend' || $act === 'unsuspend') {
                $id = (int)($_POST['user_id'] ?? 0);
                $m->setStatus($id, $act === 'suspend' ? 'Suspended' : 'Active');
                $this->redirect('/A/users?msg=' . ($act === 'suspend' ? 'suspended' : 'unsuspended'));
            }

            if ($act === 'delete') {
                $id = (int)($_POST['user_id'] ?? 0);
                // Requires DB foreign keys referencing users(user_id) to be defined with ON DELETE CASCADE (or SET NULL as desired)
                $m->delete($id);
                $this->redirect('/A/users?msg=deleted');
            }
        }

        // Collect filters from GET
        $filters = [
            'role'   => $_GET['role']   ?? '',
            'status' => $_GET['status'] ?? '',
            'link'   => $_GET['link']   ?? '',
        ];

        $this->view('ntc_admin','users',[
            'counts'=>$m->counts(),
            'users'=>$m->list($filters),
            'owners'=>$m->owners(),
            'depots'=>$m->depots(),
            'filters'=>$filters
        ]);
    }
    public function depots_owners() {
        $m = new OrgModel();

        // POST creates (handle first)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create_depot') {
                $m->createDepot($_POST);
                $this->redirect('/A/depots_owners?msg=depot_created');
                return;
            }
            if ($act === 'create_owner') {
                $m->createOwner($_POST);
                $this->redirect('/A/depots_owners?msg=owner_created');
                return;
            }
        }

        // GET deletes
        if (isset($_GET['delete_depot'])) {
            $id = (int)$_GET['delete_depot'];
            if ($id > 0) $m->deleteDepot($id);
            $this->redirect('/A/depots_owners?msg=depot_deleted');
            return;
        }
        if (isset($_GET['delete_owner'])) {
            $id = (int)$_GET['delete_owner'];
            if ($id > 0) $m->deleteOwner($id);
            $this->redirect('/A/depots_owners?msg=owner_deleted');
            return;
        }

        // Render last
        $this->view('ntc_admin', 'depots_owners', [
            'depots' => $m->depots(),
            'owners' => $m->owners(),
            // optional: pass schedules if you wire it later
            // 'scheduleRows' => $m->scheduleRowsLite(),
        ]);
    }

    public function profile() {
        $m = new ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($_POST);
                return $this->redirect('/A/profile?msg=' . ($ok ? 'updated' : 'update_failed'));
            }

            if ($act === 'update_password') {
                $ok = $m->changePassword($_POST);
                return $this->redirect('/A/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
            }

            if ($act === 'save_prefs') {
                $m->savePrefs($_POST);
                return $this->redirect('/A/profile?msg=prefs_saved');
            }
        }

        // Data for the view: pull from session (and optionally DB fresh)
        $meFromSession = $m->sessionUser();
        $meFresh       = $meFromSession && !empty($meFromSession['id'])
                        ? ($m->findById((int)$meFromSession['id']) ?? $meFromSession)
                        : $meFromSession;

        $this->view('ntc_admin','profile',[
            'me'    => $meFresh,
            'theme' => $m->theme(),
            'msg'   => $_GET['msg'] ?? null
        ]);
    }
public function analytics() {
    $m = new AnalyticsModel();

    // ── Read & sanitise filter params from GET ─────────────────────
    $routeNo = trim($_GET['route_no']  ?? '');
    $depotId = (int)($_GET['depot_id']  ?? 0);
    $ownerId = (int)($_GET['owner_id']  ?? 0);
    // depot and owner are mutually exclusive; if both sent, prefer depot
    if ($depotId > 0) $ownerId = 0;

    $filters = [
        'route_no' => $routeNo,
        'depot_id' => $depotId ?: null,
        'owner_id' => $ownerId ?: null,
    ];

    // ── KPIs (live from DB, filter-aware) ─────────────────────────
    $kpi = [
        'delayedToday' => $m->delayedToday($filters),
        'avgRating'    => $m->avgRating($filters),
        'speedViol'    => $m->speedViolationsToday($filters),
        'longWaitPct'  => $m->longWaitPct($filters),
    ];

    // ── Chart datasets (real DB, filter-aware) ────────────────────
    $analytics = [
        '_fromServer'      => true,
        'kpi'              => $kpi,
        'busStatus'        => $m->busStatus($filters),
        'revenue'          => $m->revenueTrends($filters),
        'speedByBus'       => $m->speedByBus($filters),
        'waitTime'         => $m->waitTimeDistribution($filters),
        'delayedByRoute'   => $m->delayedByRoute($filters),
        'complaintsByRoute'=> $m->complaintsByRoute($filters),
    ];

    $this->view('ntc_admin','analytics',[
        'analyticsJson' => json_encode(
            $analytics,
            JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK|
            JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
        ),
        'kpi'     => $kpi,
        'filters' => $filters,
        // All distinct route_nos ordered numerically – includes auto-created live routes
        'routes'  => $GLOBALS['db']->query(
            "SELECT DISTINCT route_no
             FROM routes
             WHERE is_active = 1
             ORDER BY CAST(route_no AS UNSIGNED), route_no"
        )->fetchAll(\PDO::FETCH_ASSOC),
        'depots'  => $m->depots(),
        'owners'  => $m->owners(),
    ]);
}

public function analyticsDetails() {
    $m = new AnalyticsModel();

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
    $depotId = (int)($_GET['depot_id'] ?? 0);
    $ownerId = (int)($_GET['owner_id'] ?? 0);
    if ($depotId > 0) {
        $ownerId = 0;
    }
    $busReg = trim($_GET['bus_reg'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $filters = [
        'route_no' => $routeNo,
        'depot_id' => $depotId ?: null,
        'owner_id' => $ownerId ?: null,
    ];

    $analytics = [
        'busStatus' => $m->busStatus($filters),
        'revenue' => $m->revenueTrends($filters),
        'speedByBus' => $m->speedByBus($filters),
        'waitTime' => $m->waitTimeDistribution($filters),
        'delayedByRoute' => $m->delayedByRoute($filters),
        'complaintsByRoute' => $m->complaintsByRoute($filters),
    ];

    $rows = [];
    $columns = [];
    $summaryCards = [];
    $secondaryRows = [];
    $secondaryColumns = [];
    $secondaryTitle = '';

    if ($chart === 'bus_status') {
        $columns = [
            'bus_reg_no' => 'Bus ID',
            'operator_type' => 'Operator',
            'depot_owner' => 'Depot / Owner',
            'route_no' => 'Route',
            'operational_status' => 'Status',
            'speed' => 'Speed (km/h)',
            'avg_delay_min' => 'Avg Delay (min)',
            'snapshot_at' => 'Last Snapshot',
        ];

        $where = ["x.rn = 1"];
        $params = [];
        if ($routeNo !== '') {
            $where[] = "CAST(COALESCE(r.route_no, '0') AS UNSIGNED) = CAST(:route_no AS UNSIGNED)";
            $params[':route_no'] = $routeNo;
        }
        if ($depotId > 0) {
            $where[] = 'sb.sltb_depot_id = :depot_id';
            $params[':depot_id'] = $depotId;
        }
        if ($ownerId > 0) {
            $where[] = 'pb.private_operator_id = :owner_id';
            $params[':owner_id'] = $ownerId;
        }
        if ($busReg !== '') {
            $where[] = 'x.bus_reg_no LIKE :bus_reg';
            $params[':bus_reg'] = '%' . $busReg . '%';
        }
        if ($status !== '') {
            $where[] = 'x.operational_status = :status';
            $params[':status'] = $status;
        }

        $sql = "SELECT
                    x.bus_reg_no,
                    x.operator_type,
                    COALESCE(d.name, pbo.name, 'Unknown') AS depot_owner,
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
                LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
                LEFT JOIN private_buses pb ON pb.reg_no = x.bus_reg_no
                LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY x.snapshot_at DESC
                LIMIT 250";
        $st = $GLOBALS['db']->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $statusRows = $analytics['busStatus'] ?? [];
        $total = 0;
        $delayed = 0;
        foreach ($statusRows as $s) {
            $cnt = (int)($s['total'] ?? 0);
            $total += $cnt;
            if (strcasecmp((string)($s['status'] ?? ''), 'Delayed') === 0) {
                $delayed += $cnt;
            }
        }
        $summaryCards = [
            ['title' => 'Total Buses', 'value' => (string)$total, 'hint' => 'Latest snapshots'],
            ['title' => 'Delayed', 'value' => (string)$delayed, 'hint' => 'Current delayed buses'],
            ['title' => 'Filtered Rows', 'value' => (string)count($rows), 'hint' => 'In detail table'],
        ];

        $secondaryColumns = [
            'depot' => 'Depot / Owner',
            'total_buses' => 'Total Buses',
            'delayed_buses' => 'Delayed Buses',
        ];
        $sql2 = "SELECT
                    COALESCE(d.name, pbo.name, 'Unknown') AS depot,
                    COUNT(*) AS total_buses,
                    SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses
                FROM (
                    SELECT tm.*,
                           ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                    FROM tracking_monitoring tm
                ) x
                LEFT JOIN routes r ON r.route_id = x.route_id
                LEFT JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no
                LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
                LEFT JOIN private_buses pb ON pb.reg_no = x.bus_reg_no
                LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY COALESCE(d.name, pbo.name, 'Unknown')
                ORDER BY total_buses DESC";
        $st2 = $GLOBALS['db']->prepare($sql2);
        $st2->execute($params);
        $secondaryRows = $st2->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $secondaryTitle = 'Breakdown by Depot / Owner';
    }

    if ($chart === 'delayed_by_route') {
        $columns = [
            'route_no' => 'Route',
            'delayed' => 'Delayed Buses',
            'total' => 'Total Buses',
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
            ['title' => 'Routes', 'value' => (string)count($rows), 'hint' => 'Ranked by delayed buses'],
            ['title' => 'Total Delayed', 'value' => (string)array_sum(array_column($rows, 'delayed')), 'hint' => 'Across listed routes'],
        ];
    }

    if ($chart === 'speed_by_bus') {
        $columns = [
            'bus_reg_no' => 'Bus ID',
            'violations' => 'Speed Violations',
        ];
        $labels = $analytics['speedByBus']['labels'] ?? [];
        $values = $analytics['speedByBus']['values'] ?? [];
        foreach ($labels as $i => $label) {
            $rows[] = [
                'bus_reg_no' => $label,
                'violations' => (int)($values[$i] ?? 0),
            ];
        }
        $summaryCards = [
            ['title' => 'Buses', 'value' => (string)count($rows), 'hint' => 'With highest violations'],
            ['title' => 'Total Violations', 'value' => (string)array_sum(array_column($rows, 'violations')), 'hint' => 'From chart data'],
        ];
    }

    if ($chart === 'revenue') {
        $columns = [
            'period' => 'Month',
            'revenue_mn' => 'Revenue (LKR Mn)',
        ];
        $labels = $analytics['revenue']['labels'] ?? [];
        $values = $analytics['revenue']['values'] ?? [];
        foreach ($labels as $i => $label) {
            $rows[] = [
                'period' => $label,
                'revenue_mn' => number_format((float)($values[$i] ?? 0), 2),
            ];
        }
        $summaryCards = [
            ['title' => 'Months', 'value' => (string)count($rows), 'hint' => 'Included in trend'],
            ['title' => 'Total Revenue', 'value' => number_format(array_sum(array_map('floatval', $values)), 2) . ' Mn', 'hint' => 'Summed trend'],
        ];
    }

    if ($chart === 'wait_time') {
        $columns = [
            'bucket' => 'Wait Time Bucket',
            'share' => 'Share (%)',
        ];
        $items = $analytics['waitTime'] ?? [];
        foreach ($items as $it) {
            $rows[] = [
                'bucket' => (string)($it['label'] ?? ''),
                'share' => (string)($it['value'] ?? 0) . '%',
            ];
        }
        $summaryCards = [
            ['title' => 'Buckets', 'value' => (string)count($rows), 'hint' => 'Wait-time categories'],
        ];
    }

    if ($chart === 'complaints_by_route') {
        $columns = [
            'route_no' => 'Route',
            'complaints' => 'Complaints',
        ];
        $labels = $analytics['complaintsByRoute']['labels'] ?? [];
        $values = $analytics['complaintsByRoute']['values'] ?? [];
        foreach ($labels as $i => $label) {
            $rows[] = [
                'route_no' => $label,
                'complaints' => (int)($values[$i] ?? 0),
            ];
        }
        $summaryCards = [
            ['title' => 'Routes', 'value' => (string)count($rows), 'hint' => 'With recorded complaints'],
            ['title' => 'Total Complaints', 'value' => (string)array_sum(array_column($rows, 'complaints')), 'hint' => 'From filtered data'],
        ];
    }

    $routeRows = $GLOBALS['db']->query(
        "SELECT DISTINCT route_no
         FROM routes
         WHERE is_active = 1
         ORDER BY CAST(route_no AS UNSIGNED), route_no"
    )->fetchAll(\PDO::FETCH_ASSOC);

    $busRows = $GLOBALS['db']->query(
        "SELECT DISTINCT bus_reg_no AS reg_no
         FROM tracking_monitoring
         ORDER BY bus_reg_no"
    )->fetchAll(\PDO::FETCH_ASSOC);

    $summaryWhere = ["x.rn = 1"];
    $summaryParams = [];
    if ($routeNo !== '') {
        $summaryWhere[] = "CAST(COALESCE(r.route_no, '0') AS UNSIGNED) = CAST(:sum_route_no AS UNSIGNED)";
        $summaryParams[':sum_route_no'] = $routeNo;
    }
    if ($depotId > 0) {
        $summaryWhere[] = 'sb.sltb_depot_id = :sum_depot_id';
        $summaryParams[':sum_depot_id'] = $depotId;
    }
    if ($ownerId > 0) {
        $summaryWhere[] = 'pb.private_operator_id = :sum_owner_id';
        $summaryParams[':sum_owner_id'] = $ownerId;
    }
    if ($busReg !== '') {
        $summaryWhere[] = 'x.bus_reg_no LIKE :sum_bus_reg';
        $summaryParams[':sum_bus_reg'] = '%' . $busReg . '%';
    }
    if ($status !== '') {
        $summaryWhere[] = 'x.operational_status = :sum_status';
        $summaryParams[':sum_status'] = $status;
    }
    $summaryWhereSql = implode(' AND ', $summaryWhere);

    $byRouteColumns = [
        'route_no' => 'Route',
        'total_buses' => 'Total Buses',
        'delayed_buses' => 'Delayed Buses',
        'avg_speed' => 'Avg Speed (km/h)',
    ];
    $byDepotColumns = [
        'depot_owner' => 'Depot / Owner',
        'total_buses' => 'Total Buses',
        'delayed_buses' => 'Delayed Buses',
        'avg_speed' => 'Avg Speed (km/h)',
    ];

    $summaryBaseSql = "FROM (
            SELECT tm.*,
                   ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
            FROM tracking_monitoring tm
        ) x
        LEFT JOIN routes r ON r.route_id = x.route_id
        LEFT JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no
        LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
        LEFT JOIN private_buses pb ON pb.reg_no = x.bus_reg_no
        LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
        WHERE $summaryWhereSql";

    $stRoute = $GLOBALS['db']->prepare(
        "SELECT
            COALESCE(r.route_no, '-') AS route_no,
            COUNT(*) AS total_buses,
            SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
            ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
         $summaryBaseSql
         GROUP BY COALESCE(r.route_no, '-')
         ORDER BY total_buses DESC"
    );
    $stRoute->execute($summaryParams);
    $byRouteRows = $stRoute->fetchAll(\PDO::FETCH_ASSOC) ?: [];

    $stDepot = $GLOBALS['db']->prepare(
        "SELECT
            COALESCE(d.name, pbo.name, 'Unknown') AS depot_owner,
            COUNT(*) AS total_buses,
            SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
            ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
         $summaryBaseSql
         GROUP BY COALESCE(d.name, pbo.name, 'Unknown')
         ORDER BY total_buses DESC"
    );
    $stDepot->execute($summaryParams);
    $byDepotRows = $stDepot->fetchAll(\PDO::FETCH_ASSOC) ?: [];

    $queryForBack = http_build_query(array_filter([
        'route_no' => $routeNo,
        'depot_id' => $depotId ?: null,
        'owner_id' => $ownerId ?: null,
    ], static fn($v) => $v !== null && $v !== ''));

    $this->view('support', 'analytics_detail', [
        'pageTitle' => 'Analytics Drilldown',
        'pageSubtitle' => 'Detailed operational data for ' . $chartMeta[$chart],
        'chartLabel' => $chartMeta[$chart],
        'detailPath' => '/A/analytics/details',
        'backUrl' => '/A/analytics' . ($queryForBack !== '' ? '?' . $queryForBack : ''),
        'filterValues' => [
            'chart' => $chart,
            'route_no' => $routeNo,
            'depot_id' => $depotId,
            'owner_id' => $ownerId,
            'bus_reg' => $busReg,
        ],
        'filterOptions' => [
            'routes' => $routeRows,
            'depots' => $m->depots(),
            'owners' => $m->owners(),
            'buses' => $busRows,
        ],
        'summaryCards' => $summaryCards,
        'columns' => $columns,
        'rows' => $rows,
        'secondaryTitle' => $secondaryTitle,
        'secondaryColumns' => $secondaryColumns,
        'secondaryRows' => $secondaryRows,
        'byRouteColumns' => $byRouteColumns,
        'byRouteRows' => $byRouteRows,
        'byDepotColumns' => $byDepotColumns,
        'byDepotRows' => $byDepotRows,
    ]);
}

    /**
     * Routes management page (list + simple create/toggle/delete)
     */
    public function routes() {
        $m = new RouteModel();

        // Handle create / update / toggle
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create_route') {
                try {
                    $m->create($_POST);
                    return $this->redirect('/A/routes?msg=created');
                } catch (\Throwable $e) {
                    return $this->redirect('/A/routes?err=' . urlencode($e->getMessage()));
                }
            }
            if ($act === 'update_route') {
                try {
                    $m->update($_POST);
                    return $this->redirect('/A/routes?msg=updated');
                } catch (\Throwable $e) {
                    return $this->redirect('/A/routes?err=' . urlencode($e->getMessage()));
                }
            }
            if ($act === 'toggle_active') {
                $routeId = (int)($_POST['route_id'] ?? 0);
                $active  = (int)($_POST['is_active'] ?? 1) === 1;
                if ($routeId > 0) {
                    $m->setActive($routeId, $active);
                }
                return $this->redirect('/A/routes?msg=status_updated');
            }
        }

        // Handle delete via GET for consistency with other pages
        if (isset($_GET['delete'])) {
            $id = (int)$_GET['delete'];
            if ($id > 0) {
                try {
                    $m->delete($id);
                    return $this->redirect('/A/routes?msg=deleted');
                } catch (\Throwable $e) {
                    return $this->redirect('/A/routes?err=' . urlencode('Delete failed: ' . $e->getMessage()));
                }
            }
        }

        // Filters
        $filters = [
            'q'      => trim($_GET['q_route'] ?? ''),
            'active' => isset($_GET['q_active']) && $_GET['q_active'] !== '' ? $_GET['q_active'] : '',
        ];

        // pagination
        $perPage = 30;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $result = $m->listPaged($filters, $perPage, $offset);
        $rows   = $result['rows'];
        $total  = (int)$result['total'];
        $pages  = max(1, (int)ceil($total / $perPage));

        // full list for datalist suggestions
        $routeOptions = $m->list([]);

        $this->view('ntc_admin', 'routes', [
            'rows'         => $rows,
            'filters'      => $filters,
            'routeOptions' => $routeOptions,
            'pagination'   => [
                'page'    => $page,
                'pages'   => $pages,
                'total'   => $total,
                'perPage' => $perPage,
            ],
            'msg'          => $_GET['msg'] ?? null,
            'err'          => $_GET['err'] ?? null,
        ]);
    }
}
?>