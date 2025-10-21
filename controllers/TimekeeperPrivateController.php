<?php
declare(strict_types=1);

namespace App\controllers;

use App\controllers\BaseController;
use App\models\timekeeper_private\DashboardModel;
use App\models\timekeeper_private\TripHistoryModel;
use App\models\timekeeper_private\TripEntryModel;
use App\models\timekeeper_private\TurnModel;
use App\models\timekeeper_private\profileModel;

class TimekeeperPrivateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');
        $this->requireLogin(['PrivateTimekeeper']);
    }

    private function myOpId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['private_operator_id'] ?? 0);
    }

    /** /TP/dashboard */
    public function dashboard()
    {
        $op = $this->myOpId();
        $m  = new DashboardModel($op);
        $S  = $m->info();   // ['depot_name'=>operator name]
        $stats = $m->stats();

        $this->view('timekeeper_private','dashboard',[
            'S'     => $S,
            'stats' => $stats
        ]);
    }

    /** /TP/history */
    public function history()
    {
        $op = $this->myOpId();
        $m  = new TripHistoryModel($op);

        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');

        [$rows, $count] = $m->list($from, $to);

        $this->view('timekeeper_private','history',[
            'S'     => $m->info(),
            'from'  => $from,
            'to'    => $to,
            'rows'  => $rows,
            'count' => $count
        ]);
    }

    /** /TP/trip_entry (GET list, POST start) */
public function trip_entry()
{
    $op = $this->myOpId();
    $m  = new TripEntryModel($op);

    if ($_SERVER['REQUEST_METHOD']==='POST') {
        header('Content-Type: application/json');
        $act = $_POST['action'] ?? '';
        if ($act === 'start') {
            $tt = (int)($_POST['timetable_id'] ?? $_POST['tt'] ?? 0);
            echo json_encode($m->start($tt)); return;
        }
        echo json_encode(['ok'=>false,'msg'=>'Unknown action']); return;
    }

    $this->view('timekeeper_private','trip_entry',[
        'S'    => $m->info(),
        'rows' => $m->todayList()
    ]);
}


    /** /TP/turns (GET running, POST complete) */
    public function turns()
    {
        $op = $this->myOpId();
        $m  = new TurnModel($op);

        if ($_SERVER['REQUEST_METHOD']==='POST') {
            header('Content-Type: application/json');
            if (($_POST['action'] ?? '') === 'complete') {
                $id = (int)($_POST['private_trip_id'] ?? $_POST['sltb_trip_id'] ?? 0); // accept both keys
                echo json_encode(['ok'=>$m->complete($id)]); return;
            }
            echo json_encode(['ok'=>false]); return;
        }

        $this->view('timekeeper_private','turn_management',[
            'S'    => $m->info(),
            'rows' => $m->running()
        ]);
    }
    public function profile()
    {
        $me = $_SESSION['user'] ?? null;
        if (!$me || empty($me['user_id'])) {
            return $this->redirect('/login');
        }
        $uid = (int)$me['user_id'];

        $m = new ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($uid, [
                    'full_name' => trim($_POST['full_name'] ?? ''),
                    'email'     => trim($_POST['email'] ?? ''),
                    'phone'     => trim($_POST['phone'] ?? '')
                ]);

                if ($ok) {
                    if ($fresh = $m->findById($uid)) {
                        $_SESSION['user']['full_name'] = $fresh['full_name'] ?? $_SESSION['user']['full_name'] ?? '';
                        $_SESSION['user']['email']     = $fresh['email']     ?? $_SESSION['user']['email'] ?? '';
                        $_SESSION['user']['phone']     = $fresh['phone']     ?? $_SESSION['user']['phone'] ?? '';
                    }
                    return $this->redirect('/TP/profile?msg=updated');
                }
                return $this->redirect('/TP/profile?msg=update_failed');
            }

            if ($act === 'change_password') {
                $ok = $m->changePassword(
                    $uid,
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                return $this->redirect('/TP/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
            }

            return $this->redirect('/TP/profile?msg=bad_action');
        }

        $meFresh = $m->findById($uid) ?: $me;

        $this->view('timekeeper_private','profile',[
            'me'  => $meFresh,
            'msg' => $_GET['msg'] ?? null
        ]);
    }
}
