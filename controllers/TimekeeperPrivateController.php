<?php
declare(strict_types = 1)
;

namespace App\controllers;

use App\controllers\BaseController;
use App\models\common\TimekeeperMessageModel;
use App\models\timekeeper_private\DashboardModel;
use App\models\timekeeper_private\TripEntryModel;
use App\models\timekeeper_private\ProfileModel;

class TimekeeperPrivateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('staff');
        $this->requireLogin(['PrivateTimekeeper']);
    }

    private function myOpId(): int
    {
        $u = $_SESSION['user'] ?? [];
        $sid = (int)($u['private_operator_id'] ?? 0);
        if ($sid > 0) {
            return $sid;
        }

        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        if ($uid <= 0) {
            return 0;
        }

        try {
            $pdo = $GLOBALS['db'];
            $st = $pdo->prepare("SELECT COALESCE(private_operator_id,0) FROM users WHERE user_id=? LIMIT 1");
            $st->execute([$uid]);
            return (int)($st->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function myUserId(): int
    {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['user_id'] ?? $u['id'] ?? 0);
    }

    /** /TP/dashboard */
    public function dashboard()
    {
        $op = $this->myOpId();
        $m = new DashboardModel($op);
        $S = $m->info(); // ['depot_name'=>operator name]
        $stats = $m->stats();

        $this->view('timekeeper_private', 'dashboard', [
            'S' => $S,
            'stats' => $stats
        ]);
    }

    /** /TP/live — operator-scoped live tracking JSON endpoint for dashboard map */
    public function live(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $opId = $this->myOpId();
        if (!isset($GLOBALS['db'])) {
            echo '[]';
            return;
        }

        $pdo = $GLOBALS['db'];

        try {
            $scopeCond = $opId > 0 ? " AND pb.private_operator_id = :op_id" : "";
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
                     pbo.name                        AS owner,
                     pb.private_operator_id          AS ownerId
                 FROM tracking_monitoring tm
                 INNER JOIN (
                     SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                     FROM tracking_monitoring
                     GROUP BY bus_reg_no
                 ) latest
                     ON latest.bus_reg_no = tm.bus_reg_no
                    AND latest.max_snap   = tm.snapshot_at
                 JOIN private_buses pb
                     ON pb.reg_no = tm.bus_reg_no
                    AND LOWER(COALESCE(pb.status, '')) <> 'inactive'{$scopeCond}
                 LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
                 LEFT JOIN routes r               ON r.route_id = tm.route_id
                 ORDER BY tm.snapshot_at DESC"
            );
            $params = [];
            if ($opId > 0) {
                $params[':op_id'] = $opId;
            }
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[timekeeper-private live] query error: ' . $e->getMessage());
            echo '[]';
            return;
        }

        if (empty($rows)) {
            echo '[]';
            return;
        }

        $out = array_map(static function (array $r) use ($opId): array {
            $rowOwnerId = (int)($r['ownerId'] ?? 0);
            return [
                'busId'             => $r['busId'],
                'routeNo'           => $r['routeNo'] ?? '',
                'speedKmh'          => (float)($r['speedKmh'] ?? 0),
                'operatorType'      => 'Private',
                'depot'             => null,
                'depotId'           => null,
                'owner'             => $r['owner'] ?: ('Owner #' . ($rowOwnerId > 0 ? $rowOwnerId : $opId)),
                'ownerId'           => $rowOwnerId > 0 ? $rowOwnerId : ($opId > 0 ? $opId : null),
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

    /** /TP/history */
    public function history()
    {
        $op = $this->myOpId();
        $m  = new TripEntryModel($op);

        $h_from = $_GET['h_from'] ?? date('Y-m-d');
        $h_to   = $_GET['h_to']   ?? date('Y-m-d');
        $h_bus  = $_GET['h_bus']  ?? '';

        $hist_rows  = $m->historyList($h_from, $h_to, $h_bus ?: null);
        $hist_buses = $m->busList();

        $this->view('timekeeper_private', 'history', [
            'S'          => $m->info(),
            'hist_rows'  => $hist_rows,
            'hist_buses' => $hist_buses,
            'h_from'     => $h_from,
            'h_to'       => $h_to,
            'h_bus'      => $h_bus,
        ]);
    }

    /** /TP/messages */
    public function messages(): void
    {
        $uid = $this->myUserId();
        if ($uid <= 0) {
            $this->redirect('/login');
            return;
        }

        $model = new TimekeeperMessageModel();
        $filter = in_array($_GET['filter'] ?? '', ['all', 'unread', 'message', 'alert'], true)
            ? (string)$_GET['filter']
            : 'all';

        $action = (string)($_GET['action'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'read' && isset($_GET['id'])) {
            $ok = $model->markRead((int)$_GET['id'], $uid);
            $this->redirect('/TP/messages?filter=' . rawurlencode($filter) . '&msg=' . ($ok ? 'read' : 'error'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'read_all') {
            $ok = $model->markAllRead($uid);
            $this->redirect('/TP/messages?filter=' . rawurlencode($filter) . '&msg=' . ($ok ? 'read_all' : 'error'));
            return;
        }

        $opId = $this->myOpId();
        $dash = new DashboardModel($opId);

        $this->view('timekeeper_private', 'messages', [
            'S'           => $dash->info(),
            'recent'      => $model->recentForUser($uid, 80, $filter),
            'filter'      => $filter,
            'unread_count'=> $model->unreadCount($uid),
            'msg'         => $_GET['msg'] ?? null,
        ]);
    }

    /** /TP/trip_entry (GET list, POST start/arrive/cancel) */
    public function trip_entry()
    {
        $op = $this->myOpId();
        $m  = new TripEntryModel($op);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                $act = $_POST['action'] ?? '';
                if ($act === 'start') {
                    $tt = (int)($_POST['timetable_id'] ?? $_POST['tt'] ?? 0);
                    echo json_encode($m->start($tt));
                    return;
                }
                if ($act === 'arrive') {
                    $id = (int)($_POST['trip_id'] ?? 0);
                    echo json_encode($m->arrive($id));
                    return;
                }
                if ($act === 'cancel') {
                    $id     = (int)($_POST['trip_id'] ?? 0);
                    $reason = trim((string)($_POST['reason'] ?? '')) ?: null;
                    echo json_encode($m->cancel($id, $reason));
                    return;
                }
                echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
            } catch (\Throwable $e) {
                error_log('[TK-Private trip_entry] ' . $e->getMessage());
                echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
            }
            return;
        }

        $this->view('timekeeper_private', 'trip_entry', [
            'S'        => $m->info(),
            'location' => $m->myLocationLabel(),
            'rows'     => $m->todayList(),
            'upcoming' => $m->upcoming(60),
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
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name' => trim($_POST['last_name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? '')
                ]);

                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

                if ($ok) {
                    if ($fresh = $m->findById($uid)) {
                        $_SESSION['user']['first_name'] = $fresh['first_name'] ?? $_SESSION['user']['first_name'] ?? '';
                        $_SESSION['user']['last_name'] = $fresh['last_name'] ?? $_SESSION['user']['last_name'] ?? '';
                        $_SESSION['user']['email'] = $fresh['email'] ?? $_SESSION['user']['email'] ?? '';
                        $_SESSION['user']['phone'] = $fresh['phone'] ?? $_SESSION['user']['phone'] ?? '';
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'user' => $fresh ?? $_SESSION['user']]);
                        return;
                    }

                    return $this->redirect('/TP/profile?msg=updated');
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'msg' => 'update_failed']);
                    return;
                }

                return $this->redirect('/TP/profile?msg=update_failed');
            }

            if ($act === 'upload_image') {
                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_image'];
                    $mimeType = mime_content_type($file['tmp_name']);
                    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                        return $this->redirect('/TP/profile?msg=invalid_image');
                    }
                    $ext = match ($mimeType) {
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                        };
                    $filename = "profile_" . $uid . "." . $ext;
                    $uploadDir = dirname(__DIR__) . '/public/uploads/profiles/';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0755, true);
                    $uploadPath = $uploadDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        if ($m->updateProfileImage($uid, '/uploads/profiles/' . $filename)) {
                            if ($fresh = $m->findById($uid)) {
                                $_SESSION['user']['profile_image'] = $fresh['profile_image'] ?? null;
                            }
                            return $this->redirect('/TP/profile?msg=image_updated');
                        }
                    }
                    return $this->redirect('/TP/profile?msg=upload_failed');
                }
                return $this->redirect('/TP/profile?msg=no_file');
            }

            if ($act === 'delete_image') {
                if ($m->deleteProfileImage($uid)) {
                    // Delete file from disk if it exists
                    if (!empty($_SESSION['user']['profile_image'])) {
                        $filePath = dirname(__DIR__) . '/public' . $_SESSION['user']['profile_image'];
                        if (file_exists($filePath))
                            unlink($filePath);
                    }
                    $_SESSION['user']['profile_image'] = null;
                    return $this->redirect('/TP/profile?msg=image_deleted');
                }
                return $this->redirect('/TP/profile?msg=delete_failed');
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

        $this->view('timekeeper_private', 'profile', [
            'me' => $meFresh,
            'msg' => $_GET['msg'] ?? null
        ]);
    }
}
