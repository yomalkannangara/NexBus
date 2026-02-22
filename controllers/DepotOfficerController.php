<?php
namespace App\controllers;

use App\models\depot_officer\DepotOfficerModel;
use App\models\depot_officer\AssignmentModel;
use App\models\depot_officer\SpecialTimetableModel;

class DepotOfficerController extends \App\controllers\BaseController
{
    private DepotOfficerModel $m;

    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');
        $this->m = new \App\models\depot_officer\DepotOfficerModel();
        $this->m->requireDepotOfficer();
        $this->requireLogin(['DepotOfficer']);
    }

    /* =========================================================
       Dashboard
       ========================================================= */
    public function dashboard()
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $this->view('depot_officer', 'dashboard', [
            'me'          => $u,
            'depot'       => $this->m->depot($dep),
            'counts'      => $this->m->dashboardCounts($dep),
            'todayDelayed'=> $this->m->delayedToday($dep),
        ]);
    }

    /* =========================================================
       Assignments
       ========================================================= */
    public function assignments()
    {
        $m       = new AssignmentModel();
        $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
        if (!$depotId) {
            return $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'create_assignment') {
                $res = $m->create($_POST, $depotId);
                if ($res === true || $res === 1 || $res === '1') {
                    return $this->redirect('?module=depot_officer&page=assignments&msg=created');
                }
                if (is_string($res) && strpos($res, 'conflict_driver::') === 0) {
                    $existing = explode('::', $res, 2)[1] ?? '';
                    return $this->redirect('?module=depot_officer&page=assignments&msg=conflict_driver&exists=' . urlencode($existing));
                }
                if (is_string($res) && strpos($res, 'conflict_conductor::') === 0) {
                    $existing = explode('::', $res, 2)[1] ?? '';
                    return $this->redirect('?module=depot_officer&page=assignments&msg=conflict_conductor&exists=' . urlencode($existing));
                }
                return $this->redirect('?module=depot_officer&page=assignments&msg=error');
            }

            if ($act === 'reassign_staff') {
                $ok = $m->reassign(
                    $depotId,
                    (int)$_POST['assignment_id'],
                    (int)$_POST['sltb_driver_id'],
                    (int)$_POST['sltb_conductor_id'],
                    $_POST['shift'] ?? null
                );
                return $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'updated' : 'error'));
            }

            if ($act === 'delete_assignment') {
                $ok = $m->delete((int)$_POST['assignment_id'], $depotId);
                return $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'deleted' : 'error'));
            }
        }

        $this->view('depot_officer', 'assignments', [
            'rows'       => $m->allToday($depotId),
            'buses'      => $m->buses($depotId),
            'drivers'    => $m->drivers($depotId),
            'conductors' => $m->conductors($depotId),
            'routes'     => $m->routes(),
            'today'      => date('Y-m-d'),
            'msg'        => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================================================
       Timetables
       ========================================================= */
    public function timetables()
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);

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
            if ($act === 'edit_special_tt' && !empty($_POST['timetable_id'])) {
                $stm = new SpecialTimetableModel();
                $ok  = $stm->updateSpecial($dep, $_POST);
                return $this->redirect('/O/timetables?msg=' . ($ok ? 'updated' : 'error'));
            }
        }

        $this->view('depot_officer', 'timetables', [
            'me'         => $u,
            'routes'     => $this->m->routes(),
            'buses'      => $this->m->depotBuses($dep),
            'special_tt' => $this->m->specialTimetables($dep),
            'msg'        => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================================================
       Messages  ← REVAMPED
       ========================================================= */
    public function messages(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $uid = (int)($u['user_id'] ?? 0);

        // Silent mark-read called by the view via fetch()
        // e.g. POST /O/messages?action=read&id=123
        if (($_GET['action'] ?? '') === 'read' && isset($_GET['id'])) {
            $this->m->markMessageRead((int)$_GET['id'], $uid);
            http_response_code(204);
            return;
        }

        // Send
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
            $text     = trim($_POST['message'] ?? '');
            $priority = in_array($_POST['priority'] ?? '', ['normal', 'urgent', 'critical'], true)
                        ? $_POST['priority'] : 'normal';
            $scope    = in_array($_POST['scope'] ?? '', ['individual', 'role', 'depot'], true)
                        ? $_POST['scope'] : 'individual';
            $allDepot = ($_POST['all_depot'] ?? '0') === '1';
            $to       = array_values(array_filter(array_map('intval', (array)($_POST['to'] ?? []))));

            $ok = ($text && ($to || $allDepot))
                  ? $this->m->sendMessage($dep, $to, $text, $priority, $scope, $allDepot)
                  : false;

            return $this->redirect('/O/messages?msg=' . ($ok ? 'sent' : 'error'));
        }

        // Render
        $filter = in_array($_GET['filter'] ?? '', ['all', 'unread', 'alert', 'message'], true)
                  ? $_GET['filter'] : 'all';

        $this->view('depot_officer', 'messages', [
            'me'     => $u,
            'staff'  => $this->m->depotStaff($dep),
            'recent' => $this->m->recentMessages($dep, $uid, 50, $filter),
            'msg'    => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================================================
       Trip Logs
       ========================================================= */
    public function trip_logs(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);

        $date    = $_GET['date'] ?? date('Y-m-d');
        $filters = [
            'route'          => $_GET['route']          ?? '',
            'bus_id'         => $_GET['bus_id']         ?? '',
            'departure_time' => $_GET['departure_time'] ?? '',
            'arrival_time'   => $_GET['arrival_time']   ?? '',
            'status'         => $_GET['status']         ?? '',
        ];

        $m    = new \App\models\depot_officer\TrackingModel();
        $rows = $m->logs($date, $date, $filters);

        $this->view('depot_officer', 'trip_logs', [
            'rows'    => $rows,
            'date'    => $date,
            'routes'  => $this->m->routes(),
            'buses'   => $this->m->depotBuses($dep),
            'filters' => $filters,
        ]);
    }

    /* =========================================================
       Reports
       ========================================================= */
    public function reports()
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $filters = [
            'route'  => $_GET['route']  ?? '',
            'bus_id' => $_GET['bus_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $csv = $this->m->buildCsvReport($dep, $from, $to, $filters);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="depot-report-' . $dep . '-' . $from . '-to-' . $to . '.csv"');
            echo $csv;
            exit;
        }

        $this->view('depot_officer', 'reports', [
            'me'      => $u,
            'from'    => $from,
            'to'      => $to,
            'kpis'    => $this->m->kpiSummary($dep, $from, $to, $filters),
            'routes'  => $this->m->routes(),
            'buses'   => $this->m->depotBuses($dep),
            'filters' => $filters,
        ]);
    }

    /* =========================================================
       Attendance
       ========================================================= */
    public function attendance()
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $date = $_GET['date'] ?? date('Y-m-d');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
            $this->m->markAttendanceBulk($dep, $date, $_POST['mark'] ?? []);
            return $this->redirect('/O/attendance?date=' . urlencode($date) . '&msg=saved');
        }

        $this->view('depot_officer', 'attendance', [
            'me'      => $u,
            'date'    => $date,
            'staff'   => $this->m->driversAndConductors($dep),
            'records' => $this->m->attendanceForDate($dep, $date),
            'msg'     => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================================================
       Profile
       ========================================================= */
    public function profile()
    {
        $me = $_SESSION['user'] ?? null;
        if (!$me || empty($me['user_id'])) {
            return $this->redirect('/login');
        }
        $uid = (int)$me['user_id'];

        $m = new \App\models\depot_officer\ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($uid, [
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name'  => trim($_POST['last_name']  ?? ''),
                    'email'      => trim($_POST['email']      ?? ''),
                    'phone'      => trim($_POST['phone']      ?? ''),
                ]);

                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

                if ($ok) {
                    if ($fresh = $m->findById($uid)) {
                        $_SESSION['user']['first_name'] = $fresh['first_name'] ?? ($_SESSION['user']['first_name'] ?? '');
                        $_SESSION['user']['last_name']  = $fresh['last_name']  ?? ($_SESSION['user']['last_name']  ?? '');
                        $_SESSION['user']['email']      = $fresh['email']      ?? ($_SESSION['user']['email']      ?? '');
                        $_SESSION['user']['phone']      = $fresh['phone']      ?? ($_SESSION['user']['phone']      ?? '');
                    }
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'user' => $fresh ?? $_SESSION['user']]);
                        return;
                    }
                    return $this->redirect('/O/profile?msg=updated');
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'msg' => 'update_failed']);
                    return;
                }
                return $this->redirect('/O/profile?msg=update_failed');
            }

            if ($act === 'upload_image') {
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file     = $_FILES['profile_image'];
                    $mimeType = mime_content_type($file['tmp_name']);
                    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                        return $this->redirect('/O/profile?msg=invalid_image');
                    }
                    $ext = match ($mimeType) {
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                    };
                    $filename  = 'profile_' . $uid . '.' . $ext;
                    $uploadDir = dirname(__DIR__) . '/public/uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        if ($m->updateProfileImage($uid, '/uploads/profiles/' . $filename)) {
                            if ($fresh = $m->findById($uid)) {
                                $_SESSION['user']['profile_image'] = $fresh['profile_image'] ?? null;
                            }
                            return $this->redirect('/O/profile?msg=image_updated');
                        }
                    }
                    return $this->redirect('/O/profile?msg=upload_failed');
                }
                return $this->redirect('/O/profile?msg=no_file');
            }

            if ($act === 'delete_image') {
                if ($m->deleteProfileImage($uid)) {
                    if (!empty($_SESSION['user']['profile_image'])) {
                        $filePath = dirname(__DIR__) . '/public' . $_SESSION['user']['profile_image'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $_SESSION['user']['profile_image'] = null;
                    return $this->redirect('/O/profile?msg=image_deleted');
                }
                return $this->redirect('/O/profile?msg=delete_failed');
            }

            if ($act === 'change_password') {
                $ok = $m->changePassword(
                    $uid,
                    $_POST['current_password'] ?? '',
                    $_POST['new_password']     ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                return $this->redirect('/O/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
            }

            return $this->redirect('/O/profile?msg=bad_action');
        }

        $this->view('depot_officer', 'profile', [
            'me'  => $m->findById($uid) ?: $me,
            'msg' => $_GET['msg'] ?? null,
        ]);
    }

    /* =========================================================
       Bus Profile
       ========================================================= */
    public function bus_profile()
    {
        $busReg = $_GET['bus_reg_no'] ?? null;
        if (!$busReg) {
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $m   = new \App\models\depot_officer\BusProfileModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $this->view('depot_officer', 'bus_profile', [
            'bus'         => $bus,
            'tracking'    => $m->getTracking($busReg),
            'assignments' => $m->getAssignments($busReg),
            'trips'       => $m->getTrips($busReg),
        ]);
    }
}