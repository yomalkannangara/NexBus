<?php
// scripts/check_profile_images.php
require_once __DIR__ . '/../bootstrap/autoload.php';
$config = require __DIR__ . '/../config/database.php';
$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $config['driver'] ?: 'mysql', $config['host'] ?: '127.0.0.1', $config['port'] ?: '3306', $config['database'] ?: '', $config['charset'] ?: 'utf8mb4');
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
try {
    $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, $opts);
    $st = $pdo->query("SELECT user_id, email, profile_image FROM users WHERE profile_image IS NOT NULL AND profile_image != ''");
    $rows = $st->fetchAll();
    if (empty($rows)) {
        echo "No users with profile_image set.\n";
        exit(0);
    }
    foreach ($rows as $r) {
        $user = $r['user_id'];
        $img = $r['profile_image'];
        $path = __DIR__ . '/../public' . $img;
        $exists = file_exists($path) ? 'exists' : 'MISSING';
        echo "user_id={$user} email={$r['email']} image={$img} -> {$exists}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
