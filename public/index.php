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

// 4. Route table
$routes = [
    // Auth
    '/login'        => ['AuthController','loginForm'],
    '/login/submit' => ['AuthController','login'],
    '/logout'       => ['AuthController','logout'],

    // NTC Admin
    '/A/dashboard'     => ['NtcAdminController','dashboard'],
    '/A/fares'         => ['NtcAdminController','fares'],
    '/A/timetables'    => ['NtcAdminController','timetables'],
    '/A/users'         => ['NtcAdminController','users'],
    '/A/depots_owners' => ['NtcAdminController','depots_owners'],
    '/A/analytics'     => ['NtcAdminController','analytics'],
    '/A/profile'     => ['NtcAdminController','profile'],


    // Passenger
    '/home'          => ['PassengerController','home'],
    '/timetable'     => ['PassengerController','timetable'],
    '/favourites'    => ['PassengerController','favourites'],
    '/ticket'        => ['PassengerController','ticket'],
    '/feedback'      => ['PassengerController','feedback'],
    '/profile'       => ['PassengerController','profile'],
    '/notifications' => ['PassengerController','notifications'],

    // Other roles
    '/M'   => ['DepotManagerController','home'],
    '/O'   => ['DepotOfficerController','home'],
    '/P'   => ['BusOwnerController','home'],
    '/TP'  => ['TimekeeperPrivateController','home'],
    '/TS'  => ['TimekeeperSltbController','home'],
];

// 5. Guard protected routes (require login)
$protectedPrefixes = ['/A','/M','/O','/P','/TP','/TS'];
foreach ($protectedPrefixes as $pref) {
    if ($path === $pref || str_starts_with($path, $pref.'/')) {
        if (empty($_SESSION['user'])) {
            $_SESSION['intended'] = $path;
            header('Location: /login'); 
            exit;
        }
    }
}


// 6. Dispatch
if (isset($routes[$path])) {
    [$c,$m] = $routes[$path];
    run($c,$m);
    exit;
}

http_response_code(404);
echo "<h1>404</h1><p>No route for <code>".htmlspecialchars($path)."</code></p>";
