<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\ntc_admin\DashboardModel;  // folders: models/ntc_admin/
use App\models\ntc_admin\FareModel;
use App\models\ntc_admin\TimetableModel;
use App\models\ntc_admin\UserModel;
use App\models\ntc_admin\OrgModel;
use App\models\ntc_admin\ProfileModel; // add at top with the other use lines


class NtcAdminController extends BaseController {
      public function __construct()
    {
    parent::__construct();
$this->setLayout('admin'); // or 'staff' / 'owner' / 'passenger'
}
    public function dashboard() {
        $m = new DashboardModel();
        $this->view('ntc_admin','dashboard',[ 'stats'=>$m->stats(), 'routes'=>$m->routes() ]);
    }
    public function fares() {
        $m = new FareModel();
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
            $m->create($_POST);
            $this->redirect('?module=ntc_admin&page=fares&msg=created');
        }
        if (isset($_GET['delete'])) {
            $m->delete($_GET['delete']);
            $this->redirect('?module=ntc_admin&page=fares&msg=deleted');
        }
        $this->view('ntc_admin','fares',[ 'routes'=>$m->routes(), 'fares'=>$m->all() ]);
    }
    public function timetables() {
        $m = new TimetableModel();
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_route') {
            $m->createRoute($_POST);
            $this->redirect('?module=ntc_admin&page=timetables&msg=route_created');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
            $m->create($_POST);
            $this->redirect('?module=ntc_admin&page=timetables&msg=created');
        }
        if (isset($_GET['delete'])) {
            $m->delete($_GET['delete']);
            $this->redirect('?module=ntc_admin&page=timetables&msg=deleted');
        }
        $this->view('ntc_admin','timetables',[
            'routes'=>$m->routes(), 'rows'=>$m->all(), 'counts'=>$m->counts(), 
            'owners'=>$m->ownersWithBuses(), 'depots'=>$m->depotsWithBuses(),'buses'=>$m->depotsWithBuses()
        ]);
    }
    public function users() {
        $m = new UserModel();
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
            $m->create($_POST);
            $this->redirect('?module=ntc_admin&page=users&msg=created');
        }
        $this->view('ntc_admin','users',[ 'counts'=>$m->counts(), 'users'=>$m->list(), 'owners'=>$m->owners(), 'depots'=>$m->depots() ]);
    }
    public function depots_owners() {
        $m = new OrgModel();
        $this->view('ntc_admin','depots_owners',[ 'depots'=>$m->depots(), 'owners'=>$m->owners() ]);
         
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_depot') {
            $m->createDepot($_POST);
            $this->redirect('?module=ntc_admin&page=depots_owners&msg=depot_created');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_owner') {
            $m->createowner($_POST);
            $this->redirect('?module=ntc_admin&page=depots_owners&msg=owner_created');
        }        
    }

    public function profile() {
        $m = new ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($_POST);
                return $this->redirect('?module=ntc_admin&page=profile&msg=' . ($ok ? 'updated' : 'update_failed'));
            }

            if ($act === 'update_password') {
                $ok = $m->changePassword($_POST);
                return $this->redirect('?module=ntc_admin&page=profile&msg=' . ($ok ? 'pw_changed' : 'pw_error'));
            }

            if ($act === 'save_prefs') {
                $m->savePrefs($_POST);
                return $this->redirect('?module=ntc_admin&page=profile&msg=prefs_saved');
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
        $pdo = $GLOBALS['db'];
        $delayed = (int)$pdo->query("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()")->fetch()['c'];
        $speed_viol = (int)$pdo->query("SELECT COALESCE(SUM(speed_violations),0) s FROM tracking_monitoring WHERE DATE(snapshot_at)=CURDATE()")->fetch()['s'];
        $this->view('ntc_admin','analytics',[ 'delayed'=>$delayed, 'speed_viol'=>$speed_viol, 'rating'=>8.0, 'long_wait'=>15 ]);
    }
}
?>