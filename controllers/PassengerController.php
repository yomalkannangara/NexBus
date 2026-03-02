<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\Passenger\HomeModel;
use App\models\Passenger\FavouritesModel;
use App\models\Passenger\TicketModel;
use App\models\Passenger\FeedbackModel;
use App\models\Passenger\ProfileModel;
use App\models\Passenger\NotificationsModel;


class PassengerController extends BaseController
{
    /** Construct passenger controller, sets passenger layout. */
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('passenger');
    }

    /** Home dashboard. */
    public function home()
    {
        $m = new HomeModel();
        $rid = (isset($_GET['route_id']) && $_GET['route_id'] !== '') ? (int)$_GET['route_id'] : null;
        $otype = (isset($_GET['operator_type']) && $_GET['operator_type'] !== '') ? (string)$_GET['operator_type'] : null;

        $this->view('passenger', 'home', [
            'routes' => $m->routes(),
            'route_id' => $rid,
            'operator_type' => $otype,
            'nextBuses' => $m->nextBuses($rid, $otype, 12)
        ]);
    }

    /** Manage favourites (list + POST add/delete/notify). */
    public function favourites()
    {
        $uid = $this->resolveCurrentPassengerId();
        if ($uid === 0) {
            $this->redirect('/login');
            return;
        }

        $m = new FavouritesModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $routeId = (int)($_POST['route_id'] ?? 0);

            if ($action === 'add') {
                $m->add($uid, $routeId);
                $this->redirect('/favourites');
            }
            if ($action === 'delete') {
                $m->delete($uid, $routeId);
                $this->redirect('/favourites');
            }
            if ($action === 'notify') {
                $m->setNotify($uid, $routeId, ($_POST['on'] ?? '0') === '1');
                $this->redirect('/favourites');
            }
        }
        $favs = $m->onlyFavs($uid);

        // Build quick lookup set of favourited route_ids
        $favSet = array_flip(array_column($favs, 'route_id'));

        // Remove already-favourited routes from dropdown
        $allRoutes = array_filter(
            $m->allRoutes(),
        fn($r) => !isset($favSet[$r['route_id']])
        );

        $this->view('passenger', 'favourites', [
            'routes' => $favs,
            'allRoutes' => $allRoutes
        ]);
    }

    /** Ticket prices calculator. */
    public function ticket()
    {
        $m = new TicketModel();
        $routes = $m->routes();

        $selectedRoute = isset($_POST['route_id']) ? (int)$_POST['route_id'] : null;
        $stops = $selectedRoute ? $m->stops($selectedRoute) : [];
        $fare = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'calc') {
            $routeId = (int)($_POST['route_id'] ?? 0);
            $startIdx = (int)($_POST['start_idx'] ?? 1);
            $endIdx = (int)($_POST['end_idx'] ?? 1);

            $fare = $m->fares($routeId, $startIdx, $endIdx);
        }


        $this->view('passenger', 'ticket', compact('routes', 'selectedRoute', 'stops', 'fare'));
    }

    /** Feedback submission and listing (supports AJAX route->buses). */
    public function feedback()
    {
        $m = new \App\models\Passenger\FeedbackModel();
        $msg = null;

        // --- AJAX: bus list (public – no auth needed) ---
        if (isset($_GET['route_id']) && is_numeric($_GET['route_id'])) {
            header('Content-Type: application/json');
            echo json_encode($m->busesByRoute((int)$_GET['route_id']));
            return;
        }

        $passengerId = $this->resolveCurrentPassengerId();
        if ($passengerId === 0) {
            $this->redirect('/login');
            return;
        }

        // --- Handle form submission ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            try {
                $m->addFeedback($_POST, $passengerId);
                $msg = 'Thanks! Submitted.';
            }
            catch (\Throwable $e) {
                $msg = 'Submit failed.';
            }
        }

        // --- Normal page ---
        $this->view('passenger', 'feedback', [
            'routes' => $m->routes(),
            'msg' => $msg,
            'mine' => $passengerId ? $m->mine($passengerId) : []
        ]);
    }

    /** Timetable search and listing. */
    public function timetable()
    {
        $m = new \App\models\Passenger\TimetableModel();

        // filters (GET or POST are fine; we’ll use GET to keep URLs shareable)
        $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
        $operatorType = isset($_GET['operator_type']) && $_GET['operator_type'] !== '' ? $_GET['operator_type'] : null;
        $dateStr = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d'); // default: today

        $routes = $m->routes();
        $rows = [];
        $stopsByRoute = [];

        if ($routeId) {
            $rows = $m->tripsForDate($routeId, $dateStr, $operatorType);

            // build once for view (full stops + “segment” per each row)
            $stopsByRoute = $m->stopsForRoute($routeId);
            foreach ($rows as &$r) {
                $r['stops_segment'] = $m->segmentStops(
                    $stopsByRoute,
                    (int)($r['start_seq'] ?? 1),
                    (int)($r['end_seq'] ?? count($stopsByRoute))
                );
                $r['duration_min'] = $m->durationMinutes($r['departure_time'], $r['arrival_time']);
                $r['latest_status'] = $m->latestStatus($r['bus_reg_no'], $dateStr); // OnTime / Delayed / …
            }
            unset($r);
        }

        $this->view('passenger', 'timetable', [
            'routes' => $routes,
            'route_id' => $routeId,
            'operator_type' => $operatorType,
            'date' => $dateStr,
            'rows' => $rows,
            'dow' => $m->dowFromDate($dateStr), // 0..6 (Sun..Sat)
        ]);
    }

    /**
     * Notifications page (tabs: all | delays | alerts)
     * - GET mark={id} marks a single item as read (PRG redirect)
     * - POST action=mark_all marks all as read
     */
    public function notifications()
    {
        $uid = $this->resolveCurrentUserId();
        if ($uid === 0) {
            $this->redirect('/login');
            return;
        }

        $m = new NotificationsModel();

        // Single item mark-as-read via GET param `mark`
        if (isset($_GET['mark']) && ctype_digit((string)$_GET['mark'])) {
            $m->markRead($uid, (int)$_GET['mark']);
            $tab = $_GET['tab'] ?? 'alerts';
            $this->redirect('/notifications?tab=' . (in_array($tab, ['all', 'delays', 'alerts'], true) ? $tab : 'alerts'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all') {
            $m->markAllRead($uid);
            $this->redirect('/notifications?tab=alerts');
        }

        $tab = $_GET['tab'] ?? 'alerts';
        $tab = in_array($tab, ['all', 'delays', 'alerts'], true) ? $tab : 'alerts';

        $filter = null;
        if ($tab === 'delays') {
            $filter = ['type' => 'Delay'];
        }
        if ($tab === 'alerts') {
            $filter = ['unread' => true];
        }

        $items = $m->listForUser($uid, $filter, 100);
        $counts = $m->counts($uid);

        $this->view('passenger', 'notifications', [
            'items' => $items,
            'counts' => $counts,
            'tab' => $tab,
        ]);
    }

    /** Profile: view & update details, change password, delete account. */
    public function profile()
    {
        $uid = $this->resolveCurrentUserId();
        if ($uid === 0) {
            $this->redirect('/login');
            return;
        }

        $m = new ProfileModel();
        $msg = $_GET['msg'] ?? null;
        $err = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'update_profile') {
                $res = $m->updateProfile($uid, $_POST);
                return $this->redirect('/profile?msg=' . ($res['ok'] ? 'updated' : urlencode($res['error'] ?? 'error')));
            }

            if ($act === 'update_password') {
                $res = $m->changePassword($uid, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '');
                return $this->redirect('/profile?msg=' . ($res['ok'] ? 'pw_changed' : urlencode($res['error'] ?? 'pw_error')));
            }

            if ($act === 'delete_account') {
                $mode = $_POST['mode'] ?? 'soft';
                $typed = trim($_POST['confirm'] ?? '');
                if ($typed !== 'DELETE') {
                    return $this->redirect('/profile?msg=' . urlencode('Type DELETE to confirm.'));
                }
                $ok = ($mode === 'hard') ? $m->hardDelete($uid) : $m->softDelete($uid);

                // log out and go home (or /logout if you have a route)
                if ($ok) {
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                    return $this->redirect('/logout'); // change to '/' if needed
                }
                return $this->redirect('/profile?msg=delete_failed');
            }
        }

        $meFresh = $m->findByUserId($uid);

        $this->view('passenger', 'profile', [
            'me' => $meFresh,
            'msg' => $msg
        ]);
    }

    /**
     * Resolve the current authenticated user's ID (users.user_id) from session/auth context.
     * Tries user_id then id key in both $this->user and $_SESSION['user'].
     * Returns 0 when unauthenticated or the ID is absent/invalid.
     * Use this for models that query by user_id (notifications, profile).
     */
    private function resolveCurrentUserId(): int
    {
        $id = $this->user['user_id']
            ?? $this->user['id']
            ?? $_SESSION['user']['user_id']
            ?? $_SESSION['user']['id']
            ?? null;
        $id = (int)$id;
        return $id > 0 ? $id : 0;
    }

    /**
     * Resolve the current authenticated user's passenger_id (passengers.passenger_id).
     * Looks up or creates the passengers row for the current user_id.
     * Returns 0 when unauthenticated.
     * Use this for models that query by passenger_id (favourites, feedback/complaints).
     */
    private function resolveCurrentPassengerId(): int
    {
        $userId = $this->resolveCurrentUserId();
        if ($userId === 0) {
            return 0;
        }
        $m = new ProfileModel();
        $pid = $m->ensurePassengerForUser($userId);
        return $pid > 0 ? $pid : 0;
    }

}
