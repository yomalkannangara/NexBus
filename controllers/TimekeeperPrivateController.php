<?php
declare(strict_types=1);

namespace App\controllers;

// Safety net: make sure the parent class is loaded before PHP parses `extends`
require_once __DIR__ . '/BaseController.php';

use App\models\timekeeper_private\TimekeeperPrivateModel;

class TimekeeperPrivateController extends \App\controllers\BaseController
{
    private TimekeeperPrivateModel $m;

    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');                 // same as Depot Officer
        $this->m = new TimekeeperPrivateModel();
        $this->m->requirePrivateTimekeeper();
    }

    public function dashboard(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);

        $this->view('timekeeper_private', 'dashboard', [
            'me'         => $u,
            'depot'      => $this->m->depot($dep),
            'todayStats' => $this->m->todayStats($dep),
            'delayed'    => $this->m->delayedToday($dep),
        ]);
    }

    public function timetables(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
            $this->m->updateTimetable($_POST);
            $this->redirect('/TP/timetables?msg=updated');
            return;
        }

        $this->view('timekeeper_private', 'timetables', [
            'me'   => $u,
            'depot'=> $this->m->depot($dep),
            'rows' => $this->m->todayTimetables($dep),
            'msg'  => $_GET['msg'] ?? null,
        ]);
    }

    public function trip_logs(): void
    {
        $u    = $this->m->me();
        $dep  = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $this->view('timekeeper_private', 'trip_logs', [
            'me'   => $u,
            'from' => $from,
            'to'   => $to,
            'rows' => $this->m->tripLogs($dep, $from, $to),
        ]);
    }

    public function reports(): void
    {
        $u    = $this->m->me();
        $dep  = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $csv = $this->m->exportCsv($dep, $from, $to);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="private-tk-report-' . $dep . '-' . $from . '-to-' . $to . '.csv"');
            echo $csv;
            exit;
        }

        $this->view('timekeeper_private', 'reports', [
            'me'      => $u,
            'from'    => $from,
            'to'      => $to,
            'summary' => $this->m->kpiSummary($dep, $from, $to),
        ]);
    }

    public function attendance(): void
    {
        $u    = $this->m->me();
        $dep  = $this->m->myDepotId($u);
        $date = $_GET['date'] ?? date('Y-m-d');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
            $this->m->markAttendance($dep, $date, $_POST['mark'] ?? []);
            $this->redirect('/TP/attendance?date=' . $date . '&msg=saved');
            return;
        }

        $this->view('timekeeper_private', 'attendance', [
            'me'      => $u,
            'date'    => $date,
            'staff'   => $this->m->staffList($dep),
            'records' => $this->m->attendanceForDate($dep, $date),
            'msg'     => $_GET['msg'] ?? null,
        ]);
    }
}
