<?php
declare(strict_types=1);

namespace App\controllers;

use App\controllers\BaseController;

use App\models\timekeeper_sltb\DashboardModel;
use App\models\timekeeper_sltb\TripHistoryModel;
use App\models\timekeeper_sltb\TurnModel;
use App\models\timekeeper_sltb\TripEntryModel;
use App\models\timekeeper_sltb\ProfileModel;

class TimekeeperSltbController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');               // shared staff chrome
        $this->requireLogin(['SLTBTimekeeper']); // role guard via BaseController
        
    }

    /* ---------- helpers ---------- */

    private function me(): array {
        return $_SESSION['user'] ?? [];
    }

    private function myUserId(): int {
        $u = $this->me();
        return (int)($u['user_id'] ?? $u['id'] ?? 0);
    }

    /** Resolve depot id from session or DB (works if either sltb_depot_id or depot_id exists) */
    private function myDepotId(): int
    {
        $u = $this->me();
        if (!empty($u['sltb_depot_id'])) return (int)$u['sltb_depot_id'];
        if (!empty($u['depot_id']))      return (int)$u['depot_id'];

        $uid = $this->myUserId();
        if ($uid <= 0) return 0;

        try {
            $pdo = $GLOBALS['db'];
            $st = $pdo->prepare("SELECT COALESCE(sltb_depot_id, depot_id, 0) FROM users WHERE (id=? OR user_id=?) LIMIT 1");
            $st->execute([$uid, $uid]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** CSRF token for TS module */
    private function csrfEnsure(): string {
        if (empty($_SESSION['csrf_ts'])) {
            $_SESSION['csrf_ts'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_ts'];
    }
    private function csrfValid(?string $t): bool {
        return is_string($t) && hash_equals($_SESSION['csrf_ts'] ?? '', $t);
    }

    /* ---------- pages ---------- */

    public function dashboard()
    {
        $m = new DashboardModel();
        $stats = $m->stats();
        $this->view('timekeeper_sltb', 'dashboard', [ 'stats' => $stats ]);
    }

    public function entry()
    {
        $m = new TripEntryModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (($_POST['action'] ?? '') === 'start') {
                $tt = (int)($_POST['timetable_id'] ?? 0);
                echo json_encode($m->start($tt)); return;
            }
            echo json_encode(['ok'=>false,'msg'=>'Unknown action']); return;
        }

        $this->view('timekeeper_sltb', 'trip_entry', [
            'rows' => $m->todayList()
        ]);
    }

    public function turns()
    {
        $m = new TurnModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (($_POST['action'] ?? '') === 'complete') {
                $id = (int)($_POST['sltb_trip_id'] ?? 0);
                echo json_encode(['ok' => $m->complete($id)]); return;
            }
            echo json_encode(['ok'=>false]); return;
        }

        $this->view('timekeeper_sltb', 'turn_management', [
            'rows' => $m->running()
        ]);
    }

    public function history()
    {
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');
        $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
        $turnNo  = isset($_GET['turn_no'])  ? (int)$_GET['turn_no']  : null;

        $m = new TripHistoryModel();
        $rows   = $m->list($from, $to, $routeId, $turnNo);
        $routes = $m->routesForDepot();

        $this->view('timekeeper_sltb', 'history', [
            'from' => $from,
            'to'   => $to,
            'rows' => $rows,
            'routes' => $routes,
            'route_id' => $routeId,
            'turn_no'  => $turnNo,
            'count' => count($rows),
        ]);
    }

   public function profile()
{
    // Require login
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
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name'  => trim($_POST['last_name'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
                'phone'      => trim($_POST['phone'] ?? '')
            ]);

            if ($ok) {
                // refresh session cache with latest user fields
                if ($fresh = $m->findById($uid)) {
                    $_SESSION['user']['first_name']    = $fresh['first_name']    ?? ($_SESSION['user']['first_name'] ?? null);
                    $_SESSION['user']['last_name']     = $fresh['last_name']     ?? ($_SESSION['user']['last_name'] ?? null);
                    $_SESSION['user']['email']         = $fresh['email']         ?? ($_SESSION['user']['email'] ?? null);
                    $_SESSION['user']['phone']         = $fresh['phone']         ?? ($_SESSION['user']['phone'] ?? null);
                }
                return $this->redirect('/TS/profile?msg=updated');
            }
            return $this->redirect('/TS/profile?msg=update_failed');
        }

        if ($act === 'upload_image') {
            if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $mimeType = mime_content_type($file['tmp_name']);
                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                    return $this->redirect('/TS/profile?msg=invalid_image');
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
                        return $this->redirect('/TS/profile?msg=image_updated');
                    }
                }
                return $this->redirect('/TS/profile?msg=upload_failed');
            }
            return $this->redirect('/TS/profile?msg=no_file');
        }

        if ($act === 'delete_image') {
            if ($m->deleteProfileImage($uid)) {
                // Delete file from disk if it exists
                if (!empty($_SESSION['user']['profile_image'])) {
                    $filePath = dirname(__DIR__) . '/public' . $_SESSION['user']['profile_image'];
                    if (file_exists($filePath)) unlink($filePath);
                }
                $_SESSION['user']['profile_image'] = null;
                return $this->redirect('/TS/profile?msg=image_deleted');
            }
            return $this->redirect('/TS/profile?msg=delete_failed');
        }

        if ($act === 'change_password') {
            $ok = $m->changePassword(
                $uid,
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['confirm_password'] ?? ''
            );
            return $this->redirect('/TS/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
        }

        return $this->redirect('/TS/profile?msg=bad_action');
    }

    // GET → load data for the form
    $meFresh = $m->findById($uid) ?: $me;

    $this->view('timekeeper_sltb','profile',[
        'me'  => $meFresh,
        'msg' => $_GET['msg'] ?? null
    ]);
}
}


