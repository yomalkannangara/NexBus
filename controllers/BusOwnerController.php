<?php
namespace App\controllers;

use App\controllers\BaseController;

// Bus Owner models (folders: models/bus_owner/)
use App\models\bus_owner\DashboardModel;
use App\models\bus_owner\BusModel;
use App\models\bus_owner\DriverModel;
use App\models\bus_owner\EarningModel;
use App\models\bus_owner\FeedbackModel;
use App\models\bus_owner\ReportModel;

class BusOwnerController extends BaseController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('owner'); // views/layouts/owner.php
        $this->requireLogin(['PrivateBusOwner']);
        // $this->requireLogin(['PrivateBusOwner']);
    }

    /** /O/dashboard */
    public function dashboard()
    {
        $dm = new DashboardModel();
        $bm = new BusModel();
        $stats = $dm->stats();

        $this->view('bus_owner', 'dashboard', [
            'total_buses'       => (int)($stats['total_buses'] ?? 0),
            'active_buses'      => (int)($stats['active_buses'] ?? 0),
            'total_drivers'     => (int)($stats['total_drivers'] ?? 0),
            'total_revenue'     => (float)($stats['total_revenue'] ?? 0),
            'recent_buses'      => $bm->getRecent(5),
            'maintenance_buses' => $bm->getCountByStatus('Maintenance'),
        ]);
    }

    /** /O/fleet */
    public function fleet()
    {
        $m = new BusModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create') {
                $m->create($_POST);
                return $this->redirect('/B/fleet?msg=created');
            }
            if ($act === 'update') {
                $reg = (string)($_POST['reg_no'] ?? '');
                $m->update($reg, $_POST);
                return $this->redirect('/B/fleet?msg=updated');
            }
            if ($act === 'delete') {
                $reg = (string)($_POST['reg_no'] ?? '');
                $m->delete($reg);
                return $this->redirect('/B/fleet?msg=deleted');
            }
        }

        if (isset($_GET['delete'])) {
            $m->delete((string)$_GET['delete']);
            return $this->redirect('/B/fleet?msg=deleted');
        }

        $this->view('bus_owner', 'fleet', ['buses' => $m->all()]);
    }

    /** /O/drivers */
    public function drivers()
    {
        $m = new DriverModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'create') {
                if (empty($_POST['private_operator_id'])) {
                    $_POST['private_operator_id'] = $m->getResolvedOperatorId();
                }
                $m->create($_POST);
                return $this->redirect('/B/drivers?msg=created');
            }

            if ($act === 'update') {
                $id = (int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0);
                $m->update($id, $_POST);
                return $this->redirect('/B/drivers?msg=updated');
            }

            if ($act === 'delete') {
                $id = (int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0);
                $m->delete($id);
                return $this->redirect('/B/drivers?msg=deleted');
            }

            if ($act === 'create_conductor') {
                if (empty($_POST['private_operator_id'])) {
                    $_POST['private_operator_id'] = $m->getResolvedOperatorId();
                }
                $m->createConductor($_POST);
                return $this->redirect('/B/drivers?msg=conductor_created');
            }

            if ($act === 'update_conductor') {
                $cid = (int)($_POST['private_conductor_id'] ?? $_POST['conductor_id'] ?? 0);
                $m->updateConductor($cid, $_POST);
                return $this->redirect('/B/drivers?msg=conductor_updated');
            }

            if ($act === 'delete_conductor') {
                $cid = (int)($_POST['private_conductor_id'] ?? $_POST['conductor_id'] ?? 0);
                $m->deleteConductor($cid);
                return $this->redirect('/B/drivers?msg=conductor_deleted');
            }
        }

        if (isset($_GET['delete'])) {
            $m->delete((int)$_GET['delete']);
            return $this->redirect('/B/drivers?msg=deleted');
        }

        if (isset($_GET['delete_conductor'])) {
            $m->deleteConductor((int)$_GET['delete_conductor']);
            return $this->redirect('/B/drivers?msg=conductor_deleted');
        }

        // expose operator id so JS can include it for create actions
        $opId = $m->getResolvedOperatorId();

        $this->view('bus_owner', 'drivers', [
            'drivers'     => $m->all(),
            'conductors'  => $m->allConductors(),
            'opId'        => $opId,
        ]);
    }

        public function earnings()
        {
            $m = new EarningModel();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $act = $_POST['action'] ?? '';

                if ($act === 'create') {
                    $m->create($_POST);
                    return $this->redirect('/B/earnings?msg=created');
                }

                if ($act === 'update') {
                    $id = (int)($_POST['earning_id'] ?? 0);
                    $m->update($id, $_POST);
                    return $this->redirect('/B/earnings?msg=updated');
                }

                if ($act === 'delete') {
                    $id = (int)($_POST['earning_id'] ?? 0);
                    $m->delete($id);
                    return $this->redirect('/B/earnings?msg=deleted');
                }
            }

            // Pass buses for dropdown — use false to include ALL statuses while testing
            $this->view('bus_owner', 'earnings', [
                'earnings' => $m->getAll(),
                'buses'    => $m->getMyBuses(false), // false => include all; true => only Active
            ]);
        }


/** /O/feedback */
public function feedback()
{
    $m = new \App\models\bus_owner\FeedbackModel();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';

        if ($act === 'update_status') {
            $m->updateStatus(
                $_POST['feedback_ref'] ?? ($_POST['id'] ?? ''),
                $_POST['status']       ?? 'Open'
            );
            return $this->redirect('/B/feedback?msg=status_updated');
        }

        if ($act === 'send_response') {
            $m->sendResponse(
                $_POST['feedback_ref'] ?? ($_POST['id'] ?? ''),
                $_POST['response']     ?? ''
            );
            return $this->redirect('/B/feedback?msg=response_sent');
        }
    }

    $this->view('bus_owner', 'feedback', [
        'feedback_refs' => $m->getAllIds(),
        'feedback_list' => $m->getAll(),   // includes passenger name + reply_text
    ]);
}


    /** /O/performance */
// at top of controller with other use lines:

// ---------------------------------------------------------
// Performance Reports — single action
// Route idea: /B/reports  (or ?module=bus_owner&page=reports)
// ---------------------------------------------------------
public function reports()
{
    $m = new ReportModel();

    // Optional: honor the dropdown filter (UI-only for now)
    $range = $_GET['range'] ?? '6m';
    if (!in_array($range, ['6m','3m','1m'], true)) {
        $range = '6m';
    }

    // Fetch metrics + top drivers
    $metrics    = $m->getPerformanceMetrics();
    $driversRaw = $m->topDrivers(10);

    // Normalize to fields expected by the view
    $topDrivers = array_map(function ($r) {
        return [
            'name'             => $r['full_name'] ?? '',
            'assignment_route' => '',    // no route in current schema; leave blank
            'rating'           => null,  // view will show 0.0 if null
        ];
    }, $driversRaw ?: []);

    // Render view: views/bus_owner/reports.php
    $this->view('bus_owner', 'reports', [
        'metrics'      => $metrics,
        'top_drivers'  => $topDrivers,
        'range'        => $range,
        'msg'          => $_GET['msg'] ?? null,
    ]);
}
public function profile()
{
    $m = new \App\models\bus_owner\ProfileModel();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';

        if ($act === 'update_profile') {
            $ok = $m->updateProfile($_POST);
            return $this->redirect('/B/profile?msg=' . ($ok ? 'updated' : 'update_failed'));
        }

        if ($act === 'change_password') {
            $ok = $m->changePassword($_POST);
            return $this->redirect('/B/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
        }

        if ($act === 'delete_account') {
            $ok = $m->deleteAccount();
            session_destroy();
            return $this->redirect('/login?msg=' . ($ok ? 'account_deleted' : 'delete_failed'));
        }
    }

    $this->view('bus_owner', 'profile', [
        'me'  => $m->getProfile(),
        'msg' => $_GET['msg'] ?? null
    ]);
}

}
