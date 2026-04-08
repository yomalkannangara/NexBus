<?php
namespace App\controllers;

use App\models\depot_officer\DepotOfficerModel;
use App\models\depot_officer\AssignmentModel;
class DepotOfficerController extends \App\controllers\BaseController {
    private DepotOfficerModel $m;

   public function __construct() {
    parent::__construct();
    $this->setLayout('staff');   // ← switch to the staff layout (not admin)
    $this->m = new \App\models\depot_officer\DepotOfficerModel();
    $this->m->requireDepotOfficer();
    $this->requireLogin(['DepotOfficer']);
}


  public function dashboard() {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $this->view('depot_officer','dashboard',[
            'me'=>$u,
            'depot'=>$this->m->depot($dep),
            'counts'=>$this->m->dashboardCounts($dep),
            'todayDelayed'=>$this->m->delayedToday($dep),
        ]);
    }

public function assignments()
{
    $m = new AssignmentModel();
    $depotId = $_SESSION['user']['sltb_depot_id'] ?? null;
    if (!$depotId) { $this->redirect('/login'); return; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';
        if ($act === 'create_assignment') {
            $res = $m->create($_POST, $depotId);
            if ($res === true || $res === 1 || $res === '1') {
                $this->redirect('?module=depot_officer&page=assignments&msg=created');
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_driver::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('?module=depot_officer&page=assignments&msg=conflict_driver&exists=' . urlencode($existing));
                return;
            }
            if (is_string($res) && strpos($res, 'conflict_conductor::') === 0) {
                $existing = explode('::', $res, 2)[1] ?? '';
                $this->redirect('?module=depot_officer&page=assignments&msg=conflict_conductor&exists=' . urlencode($existing));
                return;
            }
            $this->redirect('?module=depot_officer&page=assignments&msg=error');
            return;
        }
        if ($act === 'update_assignment') {
            $ok = $m->update($depotId, $_POST);
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'reassign_staff') {
            $ok = $m->reassign(
                $depotId,
                (int)$_POST['assignment_id'],
                (int)$_POST['sltb_driver_id'],
                (int)$_POST['sltb_conductor_id'],
                $_POST['shift'] ?? null
            );
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'updated' : 'error'));
            return;
        }
        if ($act === 'delete_assignment') {
            $ok = $m->delete((int)$_POST['assignment_id'], $depotId);
            $this->redirect('?module=depot_officer&page=assignments&msg=' . ($ok ? 'deleted' : 'error'));
            return;
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





    public function timetables() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->redirect('/O/timetables?msg=readonly');
            return;
        }

        $view = in_array($_GET['view'] ?? '', ['current', 'usual', 'seasonal'], true)
            ? (string)$_GET['view']
            : 'current';

        $date = (string)($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $current = $this->m->currentTimetables($dep, $date);
        $usual = $this->m->usualTimetables($dep);
        $seasonal = $this->m->seasonalTimetables($dep, $date);

        $rows = match ($view) {
            'usual' => $usual,
            'seasonal' => $seasonal,
            default => $current,
        };

        $this->view('depot_officer','timetables',[
            'me'=>$u,
            'selected_view' => $view,
            'selected_date' => $date,
            'rows' => $rows,
            'count_current' => count($current),
            'count_usual' => count($usual),
            'count_seasonal' => count($seasonal),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

       public function messages(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $uid = (int)($u['user_id'] ?? 0);

        // ── Mark-read (silent AJAX call from the view) ────────────────────
        // Route: POST /O/messages?action=read&id=123
        if (($_GET['action'] ?? '') === 'read' && isset($_GET['id'])) {
            $this->m->markMessageRead((int)$_GET['id'], $uid);
            http_response_code(204);
            exit;
        }

        // ── Acknowledge message ────────────────────────────────────────────
        // Route: POST /O/messages?action=ack&id=123
        if (($_GET['action'] ?? '') === 'ack' && isset($_GET['id'])) {
            $this->m->acknowledgeMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Escalate message ──────────────────────────────────────────────
        // Route: POST /O/messages?action=escalate&id=123
        if (($_GET['action'] ?? '') === 'escalate' && isset($_GET['id'])) {
            $this->m->escalateMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Archive message ───────────────────────────────────────────────
        // Route: POST /O/messages?action=archive&id=123
        if (($_GET['action'] ?? '') === 'archive' && isset($_GET['id'])) {
            $this->m->archiveMessage((int)$_GET['id'], $uid);
            $this->json(['status' => 'ok']);
            exit;
        }

        // ── Send ──────────────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
            $text      = trim($_POST['message'] ?? '');
            $priority  = in_array($_POST['priority'] ?? '', ['normal','urgent','critical'], true)
                         ? $_POST['priority'] : 'normal';
            $scope     = in_array($_POST['scope'] ?? '', ['individual','role','depot','bus','route'], true)
                         ? $_POST['scope'] : 'individual';
            $allDepot  = ($_POST['all_depot'] ?? '0') === '1';
            $to        = array_values(array_filter(array_map('intval', (array)($_POST['to'] ?? []))));

            $senderRole = (string)($u['role'] ?? 'DepotOfficer');
            $ok = ($text && ($to || $allDepot))
                ? $this->m->sendMessage($dep, $to, $text, $priority, $scope, $allDepot, $uid, $senderRole)
                  : false;

            $this->redirect('/O/messages?msg=' . ($ok ? 'sent' : 'error'));
            return;
        }

        // ── Render ────────────────────────────────────────────────────────
        $filter = in_array($_GET['filter'] ?? '', ['all','unread','alert','message'], true)
                  ? $_GET['filter'] : 'all';

        $this->view('depot_officer', 'messages', [
            'me'         => $u,
            'staff'      => $this->m->depotStaff($dep),
            'roles'      => $this->m->availableRoles($dep),
            'buses'      => $this->m->depotBusesForMessaging($dep),
            'routes'     => $this->m->depotRoutesForMessaging($dep),
            'recent'     => $this->m->recentMessages($dep, $uid, 50, $filter),
            'msg'        => $_GET['msg'] ?? null,
        ]);
    }

    /**
     * Server-Sent Events (SSE) endpoint for real-time message delivery
     * Route: GET /O/messages/stream (or /O/sse-stream)
     * Opens a persistent connection and pushes new messages to the client
     */
    public function sseStream(): void
    {
        $u   = $this->m->me();
        $dep = $this->m->myDepotId($u);
        $uid = (int)($u['user_id'] ?? 0);

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Persist connection for 5 minutes
        set_time_limit(300);

        // Track last message ID to avoid sending duplicates
        $lastId = (int)($_GET['last_id'] ?? 0);
        $count = 0;
        $maxIterations = 300; // Poll for 5 minutes (1 second per iteration)

        while ($count < $maxIterations) {
            // Fetch new messages since last_id
            $recent = $this->m->recentMessages($dep, $uid, 50, 'all');
            $recent = array_filter($recent, fn($n) => (int)($n['id'] ?? $n['notification_id'] ?? 0) > $lastId);

            if (!empty($recent)) {
                foreach ($recent as $msg) {
                    $msgId = (int)($msg['id'] ?? $msg['notification_id'] ?? 0);
                    $lastId = max($lastId, $msgId);

                    // Send event to client
                    echo "id: {$msgId}\n";
                    echo "event: message\n";
                    echo "data: " . json_encode([
                        'id'        => $msgId,
                        'type'      => $msg['type'] ?? 'Message',
                        'message'   => $msg['message'] ?? '',
                        'from'      => $msg['full_name'] ?? 'Unknown',
                        'created_at'=> $msg['created_at'] ?? '',
                        'priority'  => $msg['priority'] ?? 'normal',
                    ]) . "\n\n";
                    flush();
                }
            }

            // Heartbeat to keep connection alive
            echo ": heartbeat\n\n";
            flush();

            // Sleep for 1 second before next check
            sleep(1);
            $count++;
        }

        // Close connection gracefully
        echo "event: close\ndata: Connection timeout\n\n";
        exit;
    }

    

public function trip_logs(): void{
    $u = $this->m->me();
    $dep = $this->m->myDepotId($u);

    $date = $_GET['date'] ?? date('Y-m-d');
    $filters = [
        'route' => $_GET['route'] ?? '',
        'bus_id' => $_GET['bus_id'] ?? '',
        'departure_time' => $_GET['departure_time'] ?? '',
        'arrival_time' => $_GET['arrival_time'] ?? '',
        'status' => $_GET['status'] ?? '',
    ];

    $from = $date; $to = $date;

    $m = new \App\models\depot_officer\TrackingModel();
    $rows = $m->logs($from, $to, $filters);

    $this->view('depot_officer', 'trip_logs', [
        'rows' => $rows,
        'date' => $date,
        'routes' => $this->m->routes(),
        'buses'  => $this->m->depotBuses($dep),
        'filters'=> $filters,
    ]);
}


    public function reports() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d');

        $filters = [
            'route' => $_GET['route'] ?? '',
            'bus_id' => $_GET['bus_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $csv = $this->m->buildCsvReport($dep, $from, $to, $filters);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="depot-report-'.$dep.'-'.$from.'-to-'.$to.'.csv"');
            echo $csv; exit;
        }

        $this->view('depot_officer','reports',[
            'me'=>$u,
            'from'=>$from,
            'to'=>$to,
            'kpis'=>$this->m->kpiSummary($dep, $from, $to, $filters),
            'routes'=>$this->m->routes(),
            'buses'=>$this->m->depotBuses($dep),
            'filters'=>$filters,
        ]);
    }

    public function attendance() {
        $u = $this->m->me(); $dep = $this->m->myDepotId($u);
        $date = $_GET['date'] ?? date('Y-m-d');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
            $mark = $_POST['mark'] ?? [];
            $this->m->markAttendanceBulk($dep, $date, $mark);
            $this->redirect('/O/attendance?date=' . urlencode($date) . '&msg=saved');
            return;
        }

        $this->view('depot_officer','attendance',[
            'me'=>$u,
            'date'=>$date,
            // show drivers & conductors for attendance marking
            'staff'=>$this->m->driversAndConductors($dep),
            'records'=>$this->m->attendanceForDate($dep, $date),
            'msg'=>$_GET['msg'] ?? null,
        ]);
    }

    /** /O/profile — account details + change password */
    public function profile() {
        $me = $_SESSION['user'] ?? null;
        if (!$me || empty($me['user_id'])) {
            $this->redirect('/login');
            return;
        }
        $uid = (int)$me['user_id'];

        $m = new \App\models\depot_officer\ProfileModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $ok = $m->updateProfile($uid, [
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name'  => trim($_POST['last_name'] ?? ''),
                    'email'      => trim($_POST['email'] ?? ''),
                    'phone'      => trim($_POST['phone'] ?? ''),
                ]);

                // Detect AJAX/JSON request
                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

                if ($ok) {
                    // refresh session copy (optional but keeps UI consistent)
                    if ($fresh = $m->findById($uid)) {
                        $_SESSION['user']['first_name'] = $fresh['first_name'] ?? ($_SESSION['user']['first_name'] ?? '');
                        $_SESSION['user']['last_name']  = $fresh['last_name']  ?? ($_SESSION['user']['last_name'] ?? '');
                        $_SESSION['user']['email']      = $fresh['email']      ?? ($_SESSION['user']['email'] ?? '');
                        $_SESSION['user']['phone']      = $fresh['phone']      ?? ($_SESSION['user']['phone'] ?? '');
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'user' => $fresh ?? $_SESSION['user']]);
                        return;
                    }

                    $this->redirect('/O/profile?msg=updated');
                    return;
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'msg' => 'update_failed']);
                    return;
                }

                $this->redirect('/O/profile?msg=update_failed');
                return;
            }

            if ($act === 'upload_image') {
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_image'];
                    $mimeType = mime_content_type($file['tmp_name']);
                    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                        $this->redirect('/O/profile?msg=invalid_image');
                        return;
                    }
                    $ext = match($mimeType) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    };
                    $filename = "profile_" . $uid . "." . $ext;
                    $uploadDir = dirname(__DIR__) . '/public/uploads/profiles/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $uploadPath = $uploadDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        if ($m->updateProfileImage($uid, '/uploads/profiles/' . $filename)) {
                            if ($fresh = $m->findById($uid)) {
                                $_SESSION['user']['profile_image'] = $fresh['profile_image'] ?? null;
                            }
                            $this->redirect('/O/profile?msg=image_updated');
                            return;
                        }
                    }
                    $this->redirect('/O/profile?msg=upload_failed');
                    return;
                }
                $this->redirect('/O/profile?msg=no_file');
                return;
            }

            if ($act === 'delete_image') {
                if ($m->deleteProfileImage($uid)) {
                    // Delete file from disk if it exists
                    if (!empty($_SESSION['user']['profile_image'])) {
                        $filePath = dirname(__DIR__) . '/public' . $_SESSION['user']['profile_image'];
                        if (file_exists($filePath)) unlink($filePath);
                    }
                    $_SESSION['user']['profile_image'] = null;
                    $this->redirect('/O/profile?msg=image_deleted');
                    return;
                }
                $this->redirect('/O/profile?msg=delete_failed');
                return;
            }

            if ($act === 'change_password') {
                $ok = $m->changePassword(
                    $uid,
                    $_POST['current_password'] ?? '',
                    $_POST['new_password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                $this->redirect('/O/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
                return;
            }

            $this->redirect('/O/profile?msg=bad_action');
            return;
        }

        $meFresh = $m->findById($uid) ?: $me;

        $this->view('depot_officer','profile', [
            'me'  => $meFresh,
            'msg' => $_GET['msg'] ?? null,
        ]);
    }

    public function bus_profile()
    {
        $busReg = $_GET['bus_reg_no'] ?? null;
        if (!$busReg) {
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $m = new \App\models\depot_officer\BusProfileModel();
        $bus = $m->getBusByReg($busReg);
        if (empty($bus)) {
            return $this->redirect('?module=depot_officer&page=dashboard');
        }

        $this->view('depot_officer','bus_profile',[
            'bus'        => $bus,
            'tracking'   => $m->getTracking($busReg),
            'assignments'=> $m->getAssignments($busReg),
            'trips'      => $m->getTrips($busReg),
        ]);
    }
}
