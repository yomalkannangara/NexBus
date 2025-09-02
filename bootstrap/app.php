<?php
declare(strict_types=1);

// === 0) Paths & timezone ==============================================
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
date_default_timezone_set(getenv('TZ') ?: 'Asia/Colombo');

// === 1) Env loader ====================================================
require_once BASE_PATH . '/Support/Env.php';
if (class_exists('\\App\\Support\\Env')) {
    \App\Support\Env::load(BASE_PATH);
}

// === 2) Config ========================================================
$config = require BASE_PATH . '/config/app.php';
$dbConf = require BASE_PATH . '/config/database.php';
$GLOBALS['config'] = $config;

// === 3) Autoloader ====================================================
require_once __DIR__ . '/autoload.php';

// === 4) Session =======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session']['name'] ?? 'NEXBUSSESSID');
    session_start();
}

// === 5) Error logging =================================================
$logDir = BASE_PATH . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/error-' . date('Y-m-d') . '.log');

// === 6) DB (PDO) ======================================================
try {
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConf['driver'],
        $dbConf['host'],
        $dbConf['port'],
        $dbConf['database'],
        $dbConf['charset']
    );
    $pdo = new PDO($dsn, $dbConf['username'], $dbConf['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $GLOBALS['db'] = $pdo;
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed.";
    error_log("DB Connection failed: " . $e->getMessage());
    exit;
}

// === 7) Middleware registry (optional) ================================
$GLOBALS['middleware'] = [
    // 'auth' => \App\Middleware\AuthMiddleware::class,
];

// === 8) Router loads later in public/index.php ========================
