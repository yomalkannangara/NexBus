<?php
// scripts/run_sql.php
// Usage: php run_sql.php /full/path/to/file.sql

require_once __DIR__ . '/../Support/Env.php';
Env::load(__DIR__ . '/..');
$config = require __DIR__ . '/../config/database.php';

$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s',
    $config['driver'] ?: 'mysql',
    $config['host'] ?: '127.0.0.1',
    $config['port'] ?: '3306',
    $config['database'] ?: '',
    $config['charset'] ?: 'utf8mb4'
);

$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, $opts);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . PHP_EOL);
    exit(2);
}

if ($argc < 2) {
    fwrite(STDERR, "Usage: php run_sql.php path/to/file.sql\n");
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "SQL file not found: $path\n");
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    fwrite(STDERR, "Unable to read file: $path\n");
    exit(1);
}

// Naive split by semicolon — good enough for simple migration files used here.
$statements = array_filter(array_map('trim', explode(';', $sql)), fn($s) => $s !== '');
try {
    $pdo->beginTransaction();
    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
    $pdo->commit();
    fwrite(STDOUT, "Executed " . count($statements) . " statements from $path\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Error executing SQL: " . $e->getMessage() . PHP_EOL);
    exit(3);
}

return 0;
