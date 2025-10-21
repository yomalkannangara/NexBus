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

class DepotManagerController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Use a non-admin layout for depot roles if you have it
        $this->setLayout('depot_manager'); // e.g. 'staff' or 'depot_manager'
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

        $this->view('depot_manager', 'fleet', [
            'summary' => $m->summaryCards(),
            'rows'    => $m->list(),
            'routes'  => $m->routes(),
            'buses'   => $m->buses(),
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

            if ($act === 'assign') {
                $m->assign($_POST);
                return $this->redirect('/D/feedback?msg=assigned');
            }
            if ($act === 'resolve') {
                $m->resolve($_POST);
                return $this->redirect('/D/feedback?msg=resolved');
            }
            if ($act === 'close') {
                $m->close($_POST);
                return $this->redirect('/D/feedback?msg=closed');
            }
            if ($act === 'reply') {
                $m->reply($_POST);
                return $this->redirect('/D/feedback?msg=replied');
            }
        }

        $this->view('depot_manager', 'feedback', [
            'cards' => $m->cards(),
            'rows'  => $m->list(),
        ]);
    }

    /* =========================
       Bus Health & Maintenance
       ========================= */
    public function health()
    {
        $m = new HealthModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'schedule_maintenance') {
                $m->schedule($_POST);
                return $this->redirect('/D/health?msg=scheduled');
            }
            if ($act === 'complete_maintenance') {
                $m->complete($_POST);
                return $this->redirect('/D/health?msg=completed');
            }
        }

        $this->view('depot_manager', 'health', [
            'metrics'   => $m->metrics(),
            'ongoing'   => $m->ongoing(),
            'completed' => $m->completed(),
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

            if ($act === 'create_driver') {
                $m->createDriver($_POST);
                return $this->redirect('/D/drivers?msg=driver_created');
            }
            if ($act === 'update_driver') {
                $m->updateDriver($_POST);
                return $this->redirect('/D/drivers?msg=driver_updated');
            }
            if ($act === 'suspend' || $act === 'unsuspend') {
                $id = (int)($_POST['driver_id'] ?? 0);
                $m->setStatus($id, $act === 'suspend' ? 'Suspended' : 'Active');
                return $this->redirect('/D/drivers?msg=' . ($act === 'suspend' ? 'suspended' : 'unsuspended'));
            }
            if ($act === 'delete_driver') {
                $m->deleteDriver((int)($_POST['driver_id'] ?? 0));
                return $this->redirect('/D/drivers?msg=driver_deleted');
            }
        }

        $this->view('depot_manager', 'drivers', [
            'metrics'   => $m->metrics(),
            'recent'    => $m->driverActivities(),
            'recentCon' => $m->conductorActivities(),
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
}
