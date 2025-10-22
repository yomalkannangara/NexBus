<?php
// 1. Bootstrap (env, config, db, autoload, session, logging)
require_once __DIR__ . '/../bootstrap/app.php';

// 2. Current path
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim('/' . ltrim($uri, '/'), '/') ?: '/';

// Default → home
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
    '/A/dashboard'     => ['NtcAdminController','dashboard'],
    '/A/fares'         => ['NtcAdminController','fares'],
    '/A/timetables'    => ['NtcAdminController','timetables'],
    '/A/users'         => ['NtcAdminController','users'],
    '/A/depots_owners' => ['NtcAdminController','depots_owners'],
    '/A/analytics'     => ['NtcAdminController','analytics'],
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
    '/O/complaints'    => ['DepotOfficerController','complaints'],
    '/O/reports'       => ['DepotOfficerController','reports'],
    '/O/assignments'   => ['DepotOfficerController','assignments'],   // NEW
    '/O/timetables'    => ['DepotOfficerController','timetables'],    // NEW
    '/O/messages'      => ['DepotOfficerController','messages'],      // NEW
    '/O/trip_logs'     => ['DepotOfficerController','trip_logs'],     // NEW
    '/O/attendance'    => ['DepotOfficerController','attendance'],    // 
  '/O/profile'       => ['DepotOfficerController','profile'],
    
    '/M'               => ['DepotManagerController','dashboard'],
    '/M/dashboard'     => ['DepotManagerController','dashboard'],
    '/M/fleet'    => ['DepotManagerController','fleet'],
    '/M/feedback'       => ['DepotManagerController','feedback'],
    '/M/health'   => ['DepotManagerController','health'],   // NEW
    '/M/drivers'    => ['DepotManagerController','drivers'],    // NEW
    '/M/performance'      => ['DepotManagerController','performance'],      // NEW
    '/M/earnings'     => ['DepotManagerController','earnings'],     // NEW
    


  // Private Timekeeper
    '/TP/dashboard'   => ['TimekeeperPrivateController','dashboard'],
    '/TP/trip_entry'  => ['TimekeeperPrivateController','trip_entry'],
    '/TP/turns'      => ['TimekeeperPrivateController','turns'],
    '/TP/history'     => ['TimekeeperPrivateController','history'],
    '/TP'             => ['TimekeeperPrivateController','dashboard'],
    '/TP/profile'     => ['TimekeeperPrivateController','profile'],

    // SLTB Timekeeper
    '/TS/dashboard'  => ['TimekeeperSltbController','dashboard'],
    '/TS/turns' => ['TimekeeperSltbController','turns'],
    '/TS/history'  => ['TimekeeperSltbController','history'],
    '/TS/trip_entry'    => ['TimekeeperSltbController','entry'],
    '/TS/profile' => ['TimekeeperSltbController','profile'],
    '/TS'            => ['TimekeeperSltbController','dashboard'],

    // Other roles
    
    '/B'            => ['BusOwnerController','dashboard'],
    '/B/dashboard'  => ['BusOwnerController','dashboard'],
    '/B/drivers' => ['BusOwnerController','drivers'],
    '/B/fleet'  => ['BusOwnerController','fleet'],
    '/B/reports'    => ['BusOwnerController','reports'],
    '/B/feedback' => ['BusOwnerController','feedback'],
    '/B/earnings'   => ['BusOwnerController','earnings'],
    '/B/performance'   => ['BusOwnerController','reports'],
    '/B/profile'    => ['BusOwnerController','profile'],
];

// 5. Auth guard: allow only public paths without login
$publicPaths = ['/login','/login/submit','/register','/home','/timetable','/ticket'];
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


// 6. Dispatch
if (isset($routes[$path])) {
    [$c,$m] = $routes[$path];
    run($c,$m);
    exit;
}

http_response_code(404);
echo "<h1>404</h1><p>No route for <code>".htmlspecialchars($path)."</code></p>";
    exit;


