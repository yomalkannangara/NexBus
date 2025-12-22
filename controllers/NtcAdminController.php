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
    // dummy data (same as before)
    $analytics = [
        "busStatus"   => [
            ["status"=>"Active", "total"=>120],
            ["status"=>"Maintenance", "total"=>25],
            ["status"=>"Inactive", "total"=>15],
        ],
        "onTime"      => [
            ["operational_status"=>"OnTime", "total"=>85],
            ["operational_status"=>"Delayed", "total"=>10],
            ["operational_status"=>"Breakdown", "total"=>5],
        ],
        "revenue"     => [
            ["date"=>"2025-05-01","operator_type"=>"Private","total"=>45000],
            ["date"=>"2025-05-01","operator_type"=>"SLTB","total"=>38000],
            ["date"=>"2025-05-02","operator_type"=>"Private","total"=>50000],
            ["date"=>"2025-05-02","operator_type"=>"SLTB","total"=>40000],
        ],
        "complaints"  => [
            ["category"=>"Cleanliness","total"=>12],
            ["category"=>"Driver Behaviour","total"=>8],
            ["category"=>"Delay","total"=>15],
        ],
        "utilization" => [
            ["route_no"=>"138","utilization"=>75],
            ["route_no"=>"100","utilization"=>60],
            ["route_no"=>"199","utilization"=>80],
        ],
    ];

    $this->view('ntc_admin','analytics',[
        'analyticsJson' => json_encode(
            $analytics,
            JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK|
            JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
        )
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