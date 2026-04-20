<?php
declare(strict_types=1);

namespace App\controllers;

use App\controllers\BaseController;
use App\models\common\TimekeeperMessageModel;

use App\models\timekeeper_sltb\DashboardModel;
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

    /** Resolve depot id from session or DB */
    private function myDepotId(): int
    {
        $u = $this->me();
        if (!empty($u['sltb_depot_id'])) return (int)$u['sltb_depot_id'];
        if (!empty($u['depot_id']))      return (int)$u['depot_id'];

        $uid = $this->myUserId();
        if ($uid <= 0) return 0;

        try {
            $pdo = $GLOBALS['db'];
            $st = $pdo->prepare("SELECT COALESCE(sltb_depot_id, 0) FROM users WHERE user_id=? LIMIT 1");
            $st->execute([$uid]);
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
        $this->view('timekeeper_sltb', 'dashboard', [
            'stats' => $stats,
            'S' => ['depot_name' => $stats['depot_name'] ?? 'My Depot'],
        ]);
    }

    /** /TS/live — depot-scoped live tracking JSON endpoint for dashboard map */
    public function live(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $depotId = $this->myDepotId();
        if (!isset($GLOBALS['db'])) {
            echo '[]';
            return;
        }

        $pdo = $GLOBALS['db'];

        try {
            $scopeCond = $depotId > 0 ? " AND sb.sltb_depot_id = :depot_id" : "";
            $stmt = $pdo->prepare(
                "SELECT
                     tm.bus_reg_no                   AS busId,
                     tm.operator_type                AS operatorType,
                     ROUND(tm.speed, 1)              AS speedKmh,
                     tm.lat,
                     tm.lng,
                     tm.heading,
                     tm.operational_status           AS operationalStatus,
                     tm.snapshot_at                  AS snapshotAt,
                     r.route_no                      AS routeNo,
                     sd.name                         AS depot,
                     sb.sltb_depot_id                AS depotId
                 FROM tracking_monitoring tm
                 INNER JOIN (
                     SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                     FROM tracking_monitoring
                     GROUP BY bus_reg_no
                 ) latest
                     ON latest.bus_reg_no = tm.bus_reg_no
                    AND latest.max_snap   = tm.snapshot_at
                 JOIN sltb_buses sb
                     ON sb.reg_no = tm.bus_reg_no
                    AND LOWER(COALESCE(sb.status, '')) <> 'inactive'{$scopeCond}
                 LEFT JOIN sltb_depots sd ON sd.sltb_depot_id = sb.sltb_depot_id
                 LEFT JOIN routes r      ON r.route_id = tm.route_id
                 ORDER BY tm.snapshot_at DESC"
            );
            $params = [];
            if ($depotId > 0) {
                $params[':depot_id'] = $depotId;
            }
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[timekeeper-sltb live] query error: ' . $e->getMessage());
            echo '[]';
            return;
        }

        if (empty($rows)) {
            echo '[]';
            return;
        }

        $out = array_map(static function (array $r) use ($depotId): array {
            $rowDepotId = (int)($r['depotId'] ?? 0);
            return [
                'busId'             => $r['busId'],
                'routeNo'           => $r['routeNo'] ?? '',
                'speedKmh'          => (float)($r['speedKmh'] ?? 0),
                'operatorType'      => 'SLTB',
                'depot'             => $r['depot'] ?: ('Depot #' . ($rowDepotId > 0 ? $rowDepotId : $depotId)),
                'depotId'           => $rowDepotId > 0 ? $rowDepotId : ($depotId > 0 ? $depotId : null),
                'owner'             => null,
                'ownerId'           => null,
                'lat'               => $r['lat'] !== null ? (float)$r['lat'] : null,
                'lng'               => $r['lng'] !== null ? (float)$r['lng'] : null,
                'heading'           => $r['heading'] !== null ? (int)$r['heading'] : null,
                'operationalStatus' => $r['operationalStatus'] ?? 'OnTime',
                'snapshotAt'        => $r['snapshotAt'],
                'updatedAt'         => $r['snapshotAt'],
                'inDb'              => true,
            ];
        }, $rows);

        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }

    public function entry()
    {
        $m = new TripEntryModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!$this->csrfValid($_POST['csrf'] ?? '')) {
                echo json_encode(['ok' => false, 'msg' => 'csrf_error']); return;
            }
            try {
                $action = $_POST['action'] ?? '';

                if ($action === 'start') {
                    $tt = (int)($_POST['timetable_id'] ?? 0);
                    echo json_encode($m->start($tt)); return;
                }
                if ($action === 'arrive') {
                    $id = (int)($_POST['trip_id'] ?? 0);
                    echo json_encode($m->arrive($id)); return;
                }
                if ($action === 'cancel') {
                    $id     = (int)($_POST['trip_id'] ?? 0);
                    $reason = trim((string)($_POST['reason'] ?? '')) ?: null;
                    echo json_encode($m->cancel($id, $reason)); return;
                }
                echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
            } catch (\Throwable $e) {
                error_log('[TK-SLTB entry] ' . $e->getMessage());
                echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
            }
            return;
        }

        // --- GET ---
        $this->view('timekeeper_sltb', 'trip_entry', [
            'rows'      => $m->todayList(),
            'location'  => $m->myLocationLabel(),
            'upcoming'  => $m->upcoming(60),
            'csrfToken' => $this->csrfEnsure(),
        ]);
    }

    public function history()
    {
        $m = new TripEntryModel();

        $hFrom = $_GET['h_from'] ?? date('Y-m-d');
        $hTo   = $_GET['h_to']   ?? date('Y-m-d');
        $hBus  = isset($_GET['h_bus']) && $_GET['h_bus'] !== '' ? $_GET['h_bus'] : null;

        $this->view('timekeeper_sltb', 'history', [
            'S'          => $m->info(),
            'hist_rows'  => $m->historyList($hFrom, $hTo, $hBus),
            'hist_buses' => $m->busList(),
            'h_from'     => $hFrom,
            'h_to'       => $hTo,
            'h_bus'      => $hBus ?? '',
        ]);
    }

    /** /TS/messages */
    public function messages(): void
    {
        $uid = $this->myUserId();
        if ($uid <= 0) {
            $this->redirect('/login');
            return;
        }

        $model = new TimekeeperMessageModel();
        $filter = in_array($_GET['filter'] ?? '', ['all', 'unread', 'alert'], true)
            ? (string)$_GET['filter']
            : 'all';

        $action = (string)($_GET['action'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'read' && isset($_GET['id'])) {
            $ok = $model->markRead((int)$_GET['id'], $uid);
            $this->redirect('/TS/messages?filter=' . rawurlencode($filter) . '&msg=' . ($ok ? 'read' : 'error'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'read_all') {
            $ok = $model->markAllRead($uid);
            $this->redirect('/TS/messages?filter=' . rawurlencode($filter) . '&msg=' . ($ok ? 'read_all' : 'error'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'ack' && isset($_GET['id'])) {
            $ok = $model->acknowledge((int)$_GET['id'], $uid);
            header('Content-Type: application/json');
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // ── Manual send to Depot Officer ──────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
            $text     = trim((string)($_POST['message'] ?? ''));
            $priority = in_array($_POST['priority'] ?? '', ['normal','urgent','critical'], true)
                        ? (string)$_POST['priority'] : 'normal';
            $ok = ($text !== '') ? $model->sendToDepotOfficers($uid, $text, $priority) : false;
            $this->redirect('/TS/messages?filter=' . rawurlencode($filter) . '&msg=' . ($ok ? 'sent' : 'send_error'));
            return;
        }

        // ── Poll: new notifications since a given id ──────────────────────
        if ($action === 'poll') {
            $sinceId = (int)($_GET['since_id'] ?? 0);
            $all = $model->recentForUser($uid, 80, 'all');
            $new = array_values(array_filter($all, fn($n) => (int)($n['id'] ?? 0) > $sinceId));
            header('Content-Type: application/json');
            echo json_encode($new);
            exit;
        }

        // ── Direct chat: send message to depot officers ────────────────────
        if ($action === 'chat_send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $text    = trim((string)($_POST['message'] ?? ''));
            $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? 0);
            $dm      = new \App\models\common\DirectMessageModel();
            $doIds   = $dm->depotOfficerIds($depotId);
            $ids     = ($text !== '' && !empty($doIds)) ? $dm->sendToMultiple($uid, $doIds, $text) : [];
            header('Content-Type: application/json');
            echo json_encode(['ok' => !empty($ids), 'id' => $ids[0] ?? null]);
            exit;
        }

        // ── Direct chat: edit a sent message (broadcast to all DOs) ────────
        if ($action === 'chat_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id   = (int)($_POST['id'] ?? 0);
            $text = trim((string)($_POST['message'] ?? ''));
            $dm   = new \App\models\common\DirectMessageModel();
            $ok   = ($id > 0 && $text !== '') ? $dm->editBroadcast($id, $uid, $text) : false;
            header('Content-Type: application/json');
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // ── Direct chat: delete a sent message (broadcast to all DOs) ───────
        if ($action === 'chat_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $dm  = new \App\models\common\DirectMessageModel();
            $ok  = ($id > 0) ? $dm->deleteBroadcast($id, $uid) : false;
            header('Content-Type: application/json');
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // ── Direct chat: poll for new chat messages ────────────────────────
        if ($action === 'chat_poll') {
            $sinceId = (int)($_GET['since_id'] ?? 0);
            $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? 0);
            $dm      = new \App\models\common\DirectMessageModel();
            $doIds   = $dm->depotOfficerIds($depotId);
            $msgs    = $dm->threadWithDepot($uid, $doIds, 50, $sinceId);
            header('Content-Type: application/json');
            echo json_encode($msgs);
            exit;
        }

        // ── Render ─────────────────────────────────────────────────────────
        $depotId  = (int)($_SESSION['user']['sltb_depot_id'] ?? 0);
        $dm       = new \App\models\common\DirectMessageModel();
        $doIds    = $dm->depotOfficerIds($depotId);
        $chatThread = $dm->threadWithDepot($uid, $doIds, 100);
        // Mark DO→TK messages as read on page load
        $dm->markReadFromMultiple($uid, $doIds);

        $tripModel = new TripEntryModel();
        $this->view('timekeeper_sltb', 'messages', [
            'S'            => $tripModel->info(),
            'recent'       => $model->recentForUser($uid, 80, $filter),
            'filter'       => $filter,
            'unread_count' => $model->unreadCount($uid),
            'msg'          => $_GET['msg'] ?? null,
            'chat_thread'  => $chatThread,
            'chat_unread'  => $dm->unreadCount($uid),
            'my_user_id'   => $uid,
            'has_depot_officer' => !empty($doIds),
        ]);
    }

   public function profile(){
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

            // Determine if this is an AJAX/JSON request
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

            if ($ok) {
                // refresh session cache with latest user fields
                if ($fresh = $m->findById($uid)) {
                    $_SESSION['user']['first_name']    = $fresh['first_name']    ?? ($_SESSION['user']['first_name'] ?? null);
                    $_SESSION['user']['last_name']     = $fresh['last_name']     ?? ($_SESSION['user']['last_name'] ?? null);
                    $_SESSION['user']['email']         = $fresh['email']         ?? ($_SESSION['user']['email'] ?? null);
                    $_SESSION['user']['phone']         = $fresh['phone']         ?? ($_SESSION['user']['phone'] ?? null);
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'user' => $fresh ?? $_SESSION['user']]);
                    return;
                }

                return $this->redirect('/TS/profile?msg=updated');
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'msg' => 'update_failed']);
                return;
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
