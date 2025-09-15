<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\passenger\HomeModel;  
use App\models\passenger\FavouritesModel;  // folders: models/ntc_admin/
use App\models\passenger\TicketModel;
use App\models\passenger\FeedbackModel;
use App\models\passenger\ProfileModel;
use App\models\Passenger\NotificationsModel;


class PassengerController extends BaseController {
  /** Construct passenger controller, sets passenger layout. */
  public function __construct() { parent::__construct(); $this->setLayout('passenger'); }

  /** Home dashboard. */
  public function home(){
    $m = new HomeModel();
    $rid = (isset($_GET['route_id']) && $_GET['route_id'] !== '') ? (int)$_GET['route_id'] : null;
    $otype = (isset($_GET['operator_type']) && $_GET['operator_type'] !== '') ? (string)$_GET['operator_type']: null;

    $this->view('passenger','home',[
      'routes'=>$m->routes(),
      'route_id'=>$rid,
      'operator_type'=>$otype,
      'nextBuses'=>$m->nextBuses($rid,$otype, 12)
    ]);
  }

  /** Manage favourites (list + POST add/delete/notify). */
  public function favourites(){
      $m = new FavouritesModel();
      $uid = 1; // session user id

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
          'routes'    => $favs,
          'allRoutes' => $allRoutes
      ]);
  }

  /** Ticket prices calculator. */
  public function ticket() {
    $m = new TicketModel();
    $routes = $m->routes();

    $selectedRoute = isset($_POST['route_id']) ? (int)$_POST['route_id'] : null;
    $stops = $selectedRoute ? $m->stops($selectedRoute) : [];
    $fare = null;

    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='calc') {
        $routeId  = (int)($_POST['route_id'] ?? 0);
        $startIdx = (int)($_POST['start_idx'] ?? 1);
        $endIdx   = (int)($_POST['end_idx'] ?? 1);

        $fare = $m->fares($routeId, $startIdx, $endIdx);
    }


    $this->view('passenger','ticket', compact('routes','selectedRoute','stops','fare'));
  }

  /** Feedback submission and listing (supports AJAX route->buses). */
  public function feedback() {
    $m = new \App\Models\Passenger\FeedbackModel();
    $msg = null;

    // Passenger id
   /* $me = $_SESSION['auth'] ?? $_SESSION['user'] ?? 1001;
    $passengerId = $me['passenger_id'] ?? $me['id'] ?? $me['user_id'] ?? 1001;
    $passengerId = $passengerId ? (int)$passengerId : 1001;*/
    $passengerId = 1001;

    // --- AJAX: bus list ---
    if (isset($_GET['route_id']) && is_numeric($_GET['route_id'])) {
        header('Content-Type: application/json');
        echo json_encode($m->busesByRoute((int)$_GET['route_id']));
        return;   // 👈 stop here, don’t render HTML
    }

    // --- Handle form submission ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
        try {
            $m->addFeedback($_POST, $passengerId);
            $msg = 'Thanks! Submitted.';
        } catch (\Throwable $e) {
            $msg = 'Submit failed.';
        }
    }

    // --- Normal page ---
    $this->view('passenger', 'feedback', [
        'routes' => $m->routes(),
        'msg'    => $msg,
        'mine'   => $passengerId ? $m->mine($passengerId) : []
    ]);
  }

  /** Timetable search and listing. */
  public function timetable()
  {
      $m = new \App\Models\Passenger\TimetableModel();

      // filters (GET or POST are fine; we’ll use GET to keep URLs shareable)
      $routeId       = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
      $operatorType  = isset($_GET['operator_type']) && $_GET['operator_type'] !== '' ? $_GET['operator_type'] : null;
      $dateStr       = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d'); // default: today

      $routes = $m->routes();
      $rows   = [];
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

      $this->view('passenger','timetable',[
          'routes'        => $routes,
          'route_id'      => $routeId,
          'operator_type' => $operatorType,
          'date'          => $dateStr,
          'rows'          => $rows,
          'dow'           => $m->dowFromDate($dateStr), // 0..6 (Sun..Sat)
      ]);
  }

  /**
   * Notifications page (tabs: all | delays | alerts)
   * - GET mark={id} marks a single item as read (PRG redirect)
   * - POST action=mark_all marks all as read
   */
    public function notifications()
  {
      $uid = (int)($this->user['id'] ?? 0);
      if ($uid <= 0) { $uid = 36; } // demo fallback

      $m = new NotificationsModel();

      // Single item mark-as-read via GET param `mark`
      if (isset($_GET['mark']) && ctype_digit((string)$_GET['mark'])) {
          $m->markRead($uid, (int)$_GET['mark']);
          $tab = $_GET['tab'] ?? 'alerts';
          $this->redirect('/notifications?tab=' . (in_array($tab, ['all','delays','alerts'], true) ? $tab : 'alerts'));
      }

      if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all') {
          $m->markAllRead($uid);
          $this->redirect('/notifications?tab=alerts');
      }

      $tab = $_GET['tab'] ?? 'alerts';
      $tab = in_array($tab, ['all','delays','alerts'], true) ? $tab : 'alerts';

      $filter = null;
      if ($tab === 'delays') { $filter = ['type' => 'Delay']; }
      if ($tab === 'alerts') { $filter = ['unread' => true]; }

      $items  = $m->listForUser($uid, $filter, 100);
      $counts = $m->counts($uid);

      $this->view('passenger','notifications', [
          'items'  => $items,
          'counts' => $counts,
          'tab'    => $tab,
      ]);
  }
}
