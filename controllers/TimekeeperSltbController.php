<?php
declare(strict_types=1);

namespace App\controllers;

// Ensure the parent is loaded even if autoload is case-sensitive on some envs
require_once __DIR__ . '/BaseController.php';

use App\models\timekeeper_sltb\DashboardModel;
use App\models\timekeeper_sltb\TimetableModel;
use App\models\timekeeper_sltb\TrackingModel;
use App\models\timekeeper_sltb\ReportModel;
use App\models\timekeeper_sltb\AttendanceModel;

class TimekeeperSltbController extends \App\controllers\BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');                  // shared staff chrome
        $this->requireLogin(['SLTBTimekeeper']);    // role guard via BaseController
    }

    /** Resolve depot id from session/db, tolerant to depot_id / sltb_depot_id / mapping table */
    private function myDepotId(array $u): int
    {
        if (!empty($u['depot_id']))      return (int)$u['depot_id'];
        if (!empty($u['sltb_depot_id'])) return (int)$u['sltb_depot_id'];

        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        if (!$uid) return 0;

        $pdo = $GLOBALS['db'];

        // users.depot_id
        try {
            $st = $pdo->prepare("SELECT depot_id FROM users WHERE user_id=?");
            $st->execute([$uid]);
            $dep = (int)($st->fetchColumn() ?: 0);
            if ($dep) { $_SESSION['user']['depot_id'] = $dep; return $dep; }
        } catch (\Throwable $e) {}

        // users.sltb_depot_id
        try {
            $st = $pdo->prepare("SELECT sltb_depot_id FROM users WHERE user_id=?");
            $st->execute([$uid]);
            $dep = (int)($st->fetchColumn() ?: 0);
            if ($dep) { $_SESSION['user']['sltb_depot_id'] = $dep; return $dep; }
        } catch (\Throwable $e) {}

        // optional mapping table (if present)
        try {
            $st = $pdo->prepare("SELECT sltb_depot_id FROM sltb_depot_users WHERE user_id=? ORDER BY is_primary DESC LIMIT 1");
            $st->execute([$uid]);
            $dep = (int)($st->fetchColumn() ?: 0);
            if ($dep) { $_SESSION['user']['sltb_depot_id'] = $dep; return $dep; }
        } catch (\Throwable $e) {}

        return 0;
    }

    public function dashboard(): void
    {
        $u   = $_SESSION['user'] ?? [];
        $dep = $this->myDepotId($u);

        $dash = new DashboardModel();
        $this->view('timekeeper_sltb', 'dashboard', [
            'me'         => $u,
            'depot'      => $dash->depot($dep),
            'todayStats' => $dash->todayStats($dep),
            'delayed'    => $dash->delayedToday($dep),
        ]);
    }

    public function timetables(): void
    {
        $u   = $_SESSION['user'] ?? [];
        $dep = $this->myDepotId($u);

        $tt = new TimetableModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update')) {
            $tt->updateTimetable($_POST);
            $this->redirect('/TS/timetables?msg=updated');
            return;
        }

        $this->view('timekeeper_sltb', 'timetables', [
            'me'   => $u,
            'depot'=> (new DashboardModel())->depot($dep),
            'rows' => $tt->todayTimetables($dep),
            'msg'  => $_GET['msg'] ?? null,
        ]);
    }

    public function trip_logs(): void
    {
        $u    = $_SESSION['user'] ?? [];
        $dep  = $this->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $track = new TrackingModel();

        $this->view('timekeeper_sltb', 'trip_logs', [
            'me'   => $u,
            'from' => $from,
            'to'   => $to,
            'rows' => $track->logs($dep, $from, $to),
        ]);
    }

    public function reports(): void
    {
        $u    = $_SESSION['user'] ?? [];
        $dep  = $this->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $report = new ReportModel();

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $csv = $report->csv($dep, $from, $to);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sltb-tk-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
            echo $csv; exit;
        }

        $this->view('timekeeper_sltb', 'reports', [
            'me'      => $u,
            'from'    => $from,
            'to'      => $to,
            'summary' => $report->kpis($dep, $from, $to),
        ]);
    }

public function attendance(): void
{
    $u    = $_SESSION['user'] ?? [];
    $dep  = $this->myDepotId($u);
    $date = $_GET['date'] ?? date('Y-m-d');

    $att   = new \App\models\timekeeper_sltb\AttendanceModel();
    $staff = $att->staffList($dep); // load staff first so we know who to save

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'mark')) {
        // Build a normalized $mark array with defaults (unchecked checkboxes don’t post)
        $mark = [];
        foreach ($staff as $s) {
            $uid   = (int)$s['user_id'];
            $row   = $_POST['mark'][$uid] ?? [];
            $abs   = !empty($row['absent']) ? 1 : 0;
            $notes = trim($row['notes'] ?? '');
            $mark[$uid] = ['absent' => $abs, 'notes' => $notes];
        }
        $att->markAttendance($dep, $date, $mark);
        $this->redirect('/TS/attendance?date=' . urlencode($date) . '&msg=saved');
        return;
    }

    $this->view('timekeeper_sltb', 'attendance', [
        'me'      => $u,
        'date'    => $date,
        'staff'   => $staff,
        'records' => $att->attendanceForDate($dep, $date),
        'msg'     => $_GET['msg'] ?? null,
    ]);
}

}
