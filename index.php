<?php
require_once __DIR__ . '/config/database.php';
$module = $_GET['module'] ?? 'ntc_admin';
$page   = $_GET['page'] ?? 'dashboard';

function call($controller, $method) {
  require_once __DIR__ . '/controllers/' . $controller . '.php';
  $cls = new $controller();
  if (!method_exists($cls, $method)) { http_response_code(404); echo "<h1>404</h1>"; exit; }
  $cls->$method();
}

switch ($module) {
  case 'ntc_admin':
    require_once __DIR__ . '/controllers/NtcAdminController.php';
    $c = new NtcAdminController();
    switch ($page) {
      case 'dashboard': $c->dashboard(); break;
      case 'fares': $c->fares(); break;
      case 'timetables': $c->timetables(); break;
      case 'users': $c->users(); break;
      case 'depots_owners': $c->depots_owners(); break;
      case 'analytics': $c->analytics(); break;
      default: http_response_code(404); echo "<h1>404</h1>"; break;
    } break;
  case 'depot_manager': call('DepotManagerController','home'); break;
  case 'depot_officer': call('DepotOfficerController','home'); break;
  case 'bus_owner': call('BusOwnerController','home'); break;
  case 'passenger': call('PassengerController','home'); break;
  case 'timekeeper_private': call('TimekeeperPrivateController','home'); break;
  case 'timekeeper_sltb': call('TimekeeperSltbController','home'); break;
  default: http_response_code(404); echo "<h1>404</h1>"; break;
}
?>