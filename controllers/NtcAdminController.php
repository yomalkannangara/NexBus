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
            'timekeeperLocations'=>$m->timekeeperLocations(),
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
        'live_status' => 'Live Status Analytics',
        'live_speed' => 'Live Speed Monitoring',
        'bus_status' => 'Bus Status Analytics',
        'delayed_by_route' => 'Delayed Buses Analytics',
        'speed_by_bus' => 'High-Speed Violations Analytics',
        'revenue' => 'Revenue Analytics',
        'wait_time' => 'Wait Time Analytics',
        'complaints_by_route' => 'Complaints Analytics',
    ];

    if (!isset($chartMeta[$chart])) {
        $chart = 'bus_status';
    }

    $filters = $m->sanitizeDetailFilters([
        'route_no' => $_GET['route_no'] ?? '',
        'depot_id' => (int)($_GET['depot_id'] ?? 0),
        'owner_id' => (int)($_GET['owner_id'] ?? 0),
        'bus_reg' => $_GET['bus_reg'] ?? '',
        'status' => $_GET['status'] ?? '',
        'from' => $_GET['from'] ?? '',
        'to' => $_GET['to'] ?? '',
    ]);

    $detail = $m->buildAdminDetailPayload($chart, $filters);

    $routeRows = $GLOBALS['db']->query(
        "SELECT DISTINCT route_no
         FROM routes
         WHERE is_active = 1
         ORDER BY CAST(route_no AS UNSIGNED), route_no"
    )->fetchAll(\PDO::FETCH_ASSOC);

    $busRows = $GLOBALS['db']->query(
        "SELECT reg_no
         FROM (
            SELECT DISTINCT bus_reg_no AS reg_no FROM tracking_monitoring
            UNION
            SELECT DISTINCT bus_reg_no AS reg_no FROM earnings
         ) b
         WHERE reg_no IS NOT NULL AND TRIM(reg_no) <> ''
         ORDER BY reg_no"
    )->fetchAll(\PDO::FETCH_ASSOC);

    $queryForBack = http_build_query(array_filter([
        'route_no' => $filters['route_no'],
        'depot_id' => $filters['depot_id'] ?: null,
        'owner_id' => $filters['owner_id'] ?: null,
        'from' => $filters['from'],
        'to' => $filters['to'],
    ], static fn($v) => $v !== null && $v !== ''));

    $detailJson = json_encode(
        $detail,
        JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK |
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    $this->view('ntc_admin', 'analytics_detail', [
        'pageTitle' => $detail['pageTitle'] ?? 'Analytics Drilldown',
        'pageSubtitle' => $detail['pageSubtitle'] ?? ('Detailed operational data for ' . $chartMeta[$chart]),
        'chartLabel' => $chartMeta[$chart],
        'detailPath' => '/A/analytics/details',
        'backUrl' => '/A/analytics' . ($queryForBack !== '' ? '?' . $queryForBack : ''),
        'filterValues' => array_merge($filters, ['chart' => $chart]),
        'filterOptions' => [
            'routes' => $routeRows,
            'depots' => $m->depots(),
            'owners' => $m->owners(),
            'buses' => $busRows,
            'showDateRange' => true,
            'statuses' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'OnTime', 'label' => 'On Time'],
                ['value' => 'Delayed', 'label' => 'Delayed'],
                ['value' => 'Breakdown', 'label' => 'Breakdown'],
                ['value' => 'OffDuty', 'label' => 'Off Duty'],
            ],
        ],
        'detailData' => $detail,
        'detailJson' => $detailJson,
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