<?php
require_once __DIR__.'/BaseController.php';
require_once __DIR__.'/../models/DashboardModel.php';
require_once __DIR__.'/../models/FareModel.php';
require_once __DIR__.'/../models/TimetableModel.php';
require_once __DIR__.'/../models/UserModel.php';
require_once __DIR__.'/../models/OrgModel.php';

class NtcAdminController extends BaseController {
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
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_depot') {
            $m->createDepot($_POST);
            $this->redirect('?module=ntc_admin&page=timetables&msg=depot_created');
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
            'owners'=>$m->ownersWithBuses(), 'depots'=>$m->depotsWithBuses()
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
    }
    public function analytics() {
        $pdo = db();
        $delayed = (int)$pdo->query("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()")->fetch()['c'];
        $speed_viol = (int)$pdo->query("SELECT COALESCE(SUM(speed_violations),0) s FROM tracking_monitoring WHERE DATE(snapshot_at)=CURDATE()")->fetch()['s'];
        $this->view('ntc_admin','analytics',[ 'delayed'=>$delayed, 'speed_viol'=>$speed_viol, 'rating'=>8.0, 'long_wait'=>15 ]);
    }
}
?>