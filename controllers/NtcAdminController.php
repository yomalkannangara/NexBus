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
            $this->redirect('/A/fares?msg=created');
        }
        if (isset($_GET['delete'])) {
            $m->delete($_GET['delete']);
            $this->redirect('/A/fares?msg=deleted');
        }
        $this->view('ntc_admin','fares',[ 'routes'=>$m->routes(), 'fares'=>$m->all() ]);
    }
    public function timetables() {
        $m = new TimetableModel();
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_route') {
            $m->createRoute($_POST);
            $this->redirect('/A/timetables?msg=route_created');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
            $m->create($_POST);
            $this->redirect('/A/timetables?msg=created');
        }
        if (isset($_GET['delete'])) {
            $m->delete($_GET['delete']);
            $this->redirect('/A/timetables?msg=deleted');
        }
        $this->view('ntc_admin','timetables',[
            'routes'=>$m->routes(), 'rows'=>$m->all(), 'counts'=>$m->counts(), 
            'owners'=>$m->ownersWithBuses(), 'depots'=>$m->depotsWithBuses(),'buses'=>$m->depotsWithBuses()
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

        $this->view('ntc_admin','users',[
            'counts'=>$m->counts(),
            'users'=>$m->list(),
            'owners'=>$m->owners(),
            'depots'=>$m->depots()
        ]);
    }
    public function depots_owners() {
        $m = new OrgModel();
        $this->view('ntc_admin','depots_owners',[ 'depots'=>$m->depots(), 'owners'=>$m->owners() ]);
         
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_depot') {
            $m->createDepot($_POST);
            $this->redirect('/A/depots_owners?msg=depot_created');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create_owner') {
            $m->createowner($_POST);
            $this->redirect('/A/depots_owners?msg=owner_created');
        }        
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
}
?>