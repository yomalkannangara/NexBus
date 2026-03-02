<?php
namespace App\controllers;

use App\controllers\BaseController;

// Depot Manager models (place under models/depot_manager/)
use App\models\depot_manager\DashboardModel;
use App\models\depot_manager\FleetModel;
use App\models\depot_manager\FeedbackModel;
use App\models\depot_manager\HealthModel;
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
            'todayLabel'  => $m->todayLabel(),   // e.g. "Sunday 19 October 2025"
            'pageTitle'   => 'Depot Dashboard',
            'subtitle'    => 'Depot Operations Overview',
            'stats'       => $m->stats(),        // KPI tiles
            'dailyStats'  => $m->dailyStats(),   // complaints/delays/issues
            'activeCount' => $m->activeCount(),
            'delayed'     => $m->delayedCount(),
            'issues'      => $m->issuesCount(),
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
            'route'       => trim($_GET['route'] ?? ''),
            'status'      => trim($_GET['status'] ?? ''),
            'capacity'    => trim($_GET['capacity'] ?? ''),
            'assignment'  => trim($_GET['assignment'] ?? ''), // currently unused
            'maintenance' => trim($_GET['maintenance'] ?? ''), // unused
        ];

        $this->view('depot_manager', 'fleet', [
            'summary' => $m->summaryCards($filters),
            'rows'    => $m->list($filters),
            'routes'  => $m->routes(),
            'buses'   => $m->buses(),
            'filters' => $filters,
            'msg'     => $_GET['msg'] ?? null,
        ]);
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

        $this->view('depot_manager', 'timetables', [
            'routes' => $off->routes(),
            'buses'  => $off->depotBuses($dep),
            'special_tt' => $off->specialTimetables($dep),
            'msg'    => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================
       Performance (scores, top lists)
       ========================= */
    public function performance()
    {
        $m = new PerformanceModel();

        $this->view('depot_manager', 'performance', [
            'cards' => $m->cards(),
            'rows'  => $m->topDrivers(),
        ]);
    }

    /* =========================
       Earnings / Revenue
       ========================= */
    public function earnings()
    {
        $m = new EarningsModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'add') {
                $m->add($_POST);
                return $this->redirect('/D/earnings?msg=added');
            }
            if ($act === 'delete') {
                $m->delete((int)($_POST['earning_id'] ?? 0));
                return $this->redirect('/D/earnings?msg=deleted');
            }
            if ($act === 'import_csv') {
                $m->importCsv($_FILES['file'] ?? null);
                return $this->redirect('/D/earnings?msg=imported');
            }
        }

        $this->view('depot_manager', 'earnings', [
            'top'   => $m->topSummary(),
            'buses' => $m->busIncomeDetail(),
            'month' => $m->monthlyOverview(),
        ]);
    }

    /* =========================
       Profile
       ========================= */
    public function profile()
    {
        $m = new ProfileModel();
        $uid = (int)($_SESSION['user']['id'] ?? 0);

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
}
