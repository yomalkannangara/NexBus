<?php
namespace App\controllers;

use App\models\depot_officer\DepotOfficerModel;

class DepotOfficerController extends \App\controllers\BaseController {
    private DepotOfficerModel $m;

   public function __construct() {
    parent::__construct();
    $this->setLayout('staff');   // ← switch to the staff layout (not admin)
    $this->m = new \App\models\depot_officer\DepotOfficerModel();
    $this->m->requireDepotOfficer();
    $this->requireLogin(['depotOfficer']);
}


    public function dashboard() {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $this->view('depot_officer','dashboard',[
            'me'=>$u,
            'depot'=>$this->m->depot($dep),
            'counts'=>$this->m->dashboardCounts($dep),
            'todayDelayed'=>$this->m->delayedToday($dep),
            'openCompl'=>$this->m->openComplaints($dep,5),
        ]);
    }

    public function assignments() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create_assignment') {
                $ok = $this->m->createAssignment($dep, $_POST);
                return $this->redirect('/O/assignments?msg=' . ($ok ? 'assigned' : 'error'));
            }
            if ($act === 'delete_assignment' && !empty($_POST['timetable_id'])) {
                $this->m->deleteAssignment($dep, (int)$_POST['timetable_id']);
                return $this->redirect('/O/assignments?msg=deleted');
            }
        }

        $this->view('depot_officer','assignments',[
            'me'=>$u,
            'buses'=>$this->m->depotBuses($dep),
            'drivers'=>$this->m->depotDrivers($dep),
            'routes'=>$this->m->routes(),
            'today'=>date('Y-m-d'),
            'rows'=>$this->m->todayAssignments($dep),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    public function timetables() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create_special_tt') {
                $ok = $this->m->createSpecialTimetable($dep, $_POST);
                return $this->redirect('/O/timetables?msg=' . ($ok ? 'created' : 'error'));
            }
            if ($act === 'delete_special_tt' && !empty($_POST['timetable_id'])) {
                $this->m->deleteSpecialTimetable($dep, (int)$_POST['timetable_id']);
                return $this->redirect('/O/timetables?msg=deleted');
            }
        }

        $this->view('depot_officer','timetables',[
            'me'=>$u,
            'routes'=>$this->m->routes(),
            'buses'=>$this->m->depotBuses($dep),
            'special_tt'=>$this->m->specialTimetables($dep),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    public function messages() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
            $text = trim($_POST['message'] ?? '');
            $to   = $_POST['to'] ?? [];
            $ok   = $this->m->sendMessage($dep, $to, $text);
            return $this->redirect('/O/messages?msg=' . ($ok ? 'sent' : 'error'));
        }

        $this->view('depot_officer','messages',[
            'me'=>$u,
            'staff'=>$this->m->depotStaff($dep),
            'recent'=>$this->m->recentMessages($dep, $u['user_id'] ?? 0),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    public function complaints() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'take') {
                $this->m->assignComplaint($dep, (int)$_POST['complaint_id'], (int)$u['user_id']);
                return $this->redirect('/O/complaints?msg=taken');
            }
            if ($act === 'reply') {
                $this->m->replyComplaint($dep, (int)$_POST['complaint_id'], $_POST['reply_text'] ?? '', $_POST['status'] ?? 'In Progress');
                return $this->redirect('/O/complaints?msg=updated');
            }
        }

        $this->view('depot_officer','complaints',[
            'me'=>$u,
            'open'=>$this->m->complaintsForDepot($dep, 'Open'),
            'inprog'=>$this->m->complaintsForDepot($dep, 'In Progress'),
            'mine'=>$this->m->complaintsAssignedTo($dep, (int)($u['user_id'] ?? 0)),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    public function trip_logs() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $this->view('depot_officer','trip_logs',[
            'me'=>$u,
            'from'=>$from,
            'to'=>$to,
            'rows'=>$this->m->trackingLogs($dep, $from, $to),
        ]);
    }

    public function reports() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $csv = $this->m->buildCsvReport($dep, $from, $to);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="depot-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
            echo $csv; exit;
        }

        $this->view('depot_officer','reports',[
            'me'=>$u,
            'from'=>$from,
            'to'=>$to,
            'kpis'=>$this->m->kpiSummary($dep, $from, $to),
        ]);
    }

    public function attendance() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $date = $_GET['date'] ?? date('Y-m-d');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
            $mark = $_POST['mark'] ?? [];
            $this->m->markAttendanceBulk($dep, $date, $mark);
            return $this->redirect('/O/attendance?date=' . urlencode($date) . '&msg=saved');
        }

        $this->view('depot_officer','attendance',[
            'me'=>$u,
            'date'=>$date,
            'staff'=>$this->m->depotStaff($dep),
            'records'=>$this->m->attendanceForDate($dep, $date),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }
}
