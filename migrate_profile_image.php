<?php
// Quick migration runner
require_once __DIR__ . '/bootstrap/autoload.php';

$config = require __DIR__ . '/config/database.php';

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
    
    // Add profile_image column
    $sql = "ALTER TABLE `users` 
            ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
            COMMENT 'Path to user profile image' 
            AFTER `status`";
    
    $pdo->exec($sql);
    echo "✓ Migration completed: profile_image column added to users table\n";
    
} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ Column already exists (no action needed)\n";
    } else {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        exit(1);
    }
}
