<?php
// --- FAVICON SHORT-CIRCUIT (must be first) ---
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim('/' . ltrim($uri, '/'), '/') ?: '/';

if ($path === '/favicon.ico') {
    $ico = __DIR__ . '/assets/images/favicon.ico';
    if (is_file($ico)) {
        header('Content-Type: image/x-icon');
        readfile($ico);
    } else {
        http_response_code(204); // quiet "No Content"
    }
    exit;
}

// 1. Bootstrap (env, config, db, autoload, session, logging)
require_once __DIR__ . '/../bootstrap/app.php';

// Default → home (this won't run for /favicon.ico)
if ($path === '/') {
    header('Location: /home');
    exit;
}


// 3. Dispatcher helper
function run(string $ctrl, string $method): void {
    $fqcn = 'App\\controllers\\' . $ctrl;
    if (!class_exists($fqcn)) {
        http_response_code(404);
        echo "Controller $fqcn not found";
        return;
    }
    $obj = new $fqcn();
    if (!method_exists($obj, $method)) {
        http_response_code(404);
        echo "Method $method not found on $fqcn";
        return;
    }
    $obj->$method();
}

// 4. Route table (flat)
$routes = [
    // Auth
    '/login'        => ['AuthController','loginForm'],
    '/login/submit' => ['AuthController','login'],
    '/logout'       => ['AuthController','logout'],
    '/register'     => ['AuthController','register'],

    // NTC Admin
    '/A'               => ['NtcAdminController','dashboard'],
    '/A/dashboard'     => ['NtcAdminController','dashboard'],
    '/A/fares'         => ['NtcAdminController','fares'],
    '/A/timetables'    => ['NtcAdminController','timetables'],
  '/A/routes'        => ['NtcAdminController','routes'],
    '/A/users'         => ['NtcAdminController','users'],
    '/A/depots_owners' => ['NtcAdminController','depots_owners'],
    '/A/analytics'     => ['NtcAdminController','analytics'],
    '/A/analytics/details' => ['NtcAdminController','analyticsDetails'],
    '/A/profile'       => ['NtcAdminController','profile'],

    // Passenger
    '/home'          => ['PassengerController','home'],
    '/timetable'     => ['PassengerController','timetable'],
    '/favourites'    => ['PassengerController','favourites'],
    '/ticket'        => ['PassengerController','ticket'],
    '/feedback'      => ['PassengerController','feedback'],
    '/profile'       => ['PassengerController','profile'],
    '/notifications' => ['PassengerController','notifications'],

     // Depot Officer
    '/O'               => ['DepotOfficerController','dashboard'],
    '/O/dashboard'     => ['DepotOfficerController','dashboard'],
    '/O/reports'       => ['DepotOfficerController','reports'],
    '/O/reports/details' => ['DepotOfficerController','reportDetails'],
    '/O/assignments'                  => ['DepotOfficerController','assignments'],
    '/O/assignments/staff-conflicts'  => ['DepotOfficerController','assignmentStaffConflicts'],
    '/O/assignments/shifts'           => ['DepotOfficerController','assignmentShifts'],
    '/O/timetables'    => ['DepotOfficerController','timetables'],
    '/O/messages'      => ['DepotOfficerController','messages'],
    '/O/messages/stream' => ['DepotOfficerController','sseStream'],
    '/O/live'          => ['DepotOfficerController','live'],
    '/O/trip_logs'     => ['DepotOfficerController','trip_logs'],
    '/O/attendance'    => ['DepotOfficerController','attendance'],
    '/O/bus_profile'   => ['DepotOfficerController','bus_profile'],
    '/O/profile'       => ['DepotOfficerController','profile'],
    
    '/M'               => ['DepotManagerController','dashboard'],
    '/M/dashboard'     => ['DepotManagerController','dashboard'],
    '/M/timetables'    => ['DepotManagerController','timetables'],
    '/M/fleet'    => ['DepotManagerController','fleet'],
    '/M/feedback'       => ['DepotManagerController','feedback'],
    '/M/drivers'    => ['DepotManagerController','drivers'],    // NEW
    '/M/performance'      => ['DepotManagerController','performance'],      // NEW
    '/M/performance/delayed-modal' => ['DepotManagerController','delayedModal'],
    '/M/performance/rating-modal'  => ['DepotManagerController','ratingModal'],
    '/M/performance/speed-modal'   => ['DepotManagerController','speedModal'],
    '/M/performance/wait-modal'    => ['DepotManagerController','waitModal'],
    '/M/performance/details' => ['DepotManagerController','performanceDetails'],
    '/M/live'             => ['DepotManagerController','depotLive'],
    '/M/earnings'     => ['DepotManagerController','earnings'],     // NEW
    '/M/earnings/export' => ['DepotManagerController','exportEarnings'],
    '/M/profile'      => ['DepotManagerController','profile'],      // NEW



  // Private Timekeeper
    '/TP/dashboard'        => ['TimekeeperPrivateController','dashboard'],
    '/TP/trip_entry'       => ['TimekeeperPrivateController','trip_entry'],
    '/TP/history'          => ['TimekeeperPrivateController','history'],
    '/TP/live'             => ['TimekeeperPrivateController','live'],
    '/TP/messages'         => ['TimekeeperPrivateController','messages'],
    '/TP'                  => ['TimekeeperPrivateController','dashboard'],
    '/TP/profile'          => ['TimekeeperPrivateController','profile'],

    // SLTB Timekeeper
    '/TS/dashboard'        => ['TimekeeperSltbController','dashboard'],
    '/TS/history'          => ['TimekeeperSltbController','history'],
    '/TS/trip_entry'       => ['TimekeeperSltbController','entry'],
    '/TS/live'             => ['TimekeeperSltbController','live'],
    '/TS/messages'         => ['TimekeeperSltbController','messages'],
    '/TS/profile'          => ['TimekeeperSltbController','profile'],
    '/TS'                  => ['TimekeeperSltbController','dashboard'],

    // Other roles
    
    '/B'            => ['BusOwnerController','dashboard'],
    '/B/dashboard'  => ['BusOwnerController','dashboard'],
    '/B/drivers' => ['BusOwnerController','drivers'],
    '/B/fleet'  => ['BusOwnerController','fleet'],
    '/B/fleet/assign' => ['BusOwnerController','fleetAssign'],
    '/B/reports'    => ['BusOwnerController','reports'],
    '/B/reports/delayed-modal' => ['BusOwnerController','delayedModal'],
    '/B/reports/rating-modal'  => ['BusOwnerController','ratingModal'],
    '/B/reports/speed-modal'   => ['BusOwnerController','speedModal'],
    '/B/reports/wait-modal'    => ['BusOwnerController','waitModal'],
    '/B/reports/details' => ['BusOwnerController','reportsDetails'],
    '/B/reports/export' => ['BusOwnerController','exportReports'],
    '/B/feedback' => ['BusOwnerController','feedback'],
    '/B/earnings'   => ['BusOwnerController','earnings'],
    '/B/earnings/export' => ['BusOwnerController','exportEarnings'],
    '/B/performance'   => ['BusOwnerController','reports'],
    '/B/attendance'    => ['BusOwnerController','attendance'],
    '/B/profile'    => ['BusOwnerController','profile'],
    '/B/live'       => ['BusOwnerController','ownerLive'],

    // Live buses (no auth)
    // Writes from external API to tracking_monitoring (call via scheduler/cron)
    '/live/buses/pull'        => ['LiveBusesController','proxy'],
    // DB-read endpoint – serves latest tracking_monitoring rows to the frontend
    '/live/buses/db'          => ['LiveBusesController','dbLive'],
    // Backward-compatible API alias used by dashboards
    '/api/buses/live'         => ['LiveBusesController','dbLive'],
    // Diagnostic helper
    '/live/buses/missing-sql' => ['LiveBusesController','missingSql'],
];

// 5. Auth guard: allow only public paths without login
$publicPaths = ['/login','/login/submit','/register','/home','/timetable','/ticket','/live/buses/pull','/live/buses/db','/api/buses/live','/live/buses/missing-sql'];
if (!in_array($path, $publicPaths, true) && empty($_SESSION['user'])) {
    // optionally remember intended URL
    $_SESSION['intended'] = $path;

// Render a tiny page that alerts then redirects (no flash/session message needed)
   // 401 page when not logged in (no flash)
http_response_code(401);
$msg  = 'Please log in to continue';
$next = '/login'; // or "/login?next=" . rawurlencode($path)
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/css/alert.css">
  <script src="/assets/js/alert.js"></script>
</head>
<body>
  <script>
    // will WAIT until user presses OK
    alert(<?= json_encode($msg) ?>).then(function () {
      window.location.href = <?= json_encode($next) ?>;
    });
  </script>
  <noscript>
    <!-- JS off fallback -->
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($next, ENT_QUOTES) ?>">
  </noscript>
</body>
</html>
<?php
exit;

}


// 6. Role-based route prefix protection
// Role names must exactly match what AuthController stores in $_SESSION['user']['role'].
$rolePolicies = [
    '/A'  => ['NTCAdmin'],
    '/M'  => ['DepotManager'],
    '/O'  => ['DepotOfficer'],
    '/B'  => ['PrivateBusOwner'],
    '/TP' => ['PrivateTimekeeper'],
    '/TS' => ['SLTBTimekeeper'],
];
foreach ($rolePolicies as $prefix => $allowedRoles) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        \App\Middleware\AuthMiddleware::requireRole($allowedRoles);
        break;
    }
}

// 7. Dispatch
if (isset($routes[$path])) {
    [$c,$m] = $routes[$path];
    run($c,$m);
    exit;
}

// Silence browser/devtools auto-discovery requests quietly
if (str_starts_with($path, '/.well-known/') || $path === '/favicon.ico') {
    http_response_code(204);
    exit;
}

http_response_code(404);
echo "<h1>404</h1><p>No route for <code>".htmlspecialchars($path)."</code></p>";
exit;


