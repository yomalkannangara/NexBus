<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=nexbus;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$checks = [
    'id_auto_increment' => "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='id' AND EXTRA LIKE '%auto_increment%'",
    'priority' => "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='priority'",
    'metadata' => "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='metadata'",
    'category' => "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notifications' AND COLUMN_NAME='category'",
];

foreach ($checks as $col => $sql) {
    $exists = (int)$pdo->query($sql)->fetchColumn();
    if (!$exists) {
        echo "Applying notifications fix: $col\n";
        if ($col === 'id_auto_increment') {
            $nextId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM notifications")->fetchColumn();
            if ($nextId < 1) {
                $nextId = 1;
            }
            $pdo->exec("ALTER TABLE notifications MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=" . $nextId);
        } elseif ($col === 'priority') {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN priority ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal' AFTER message");
        } elseif ($col === 'metadata') {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN metadata JSON NULL AFTER priority");
        } elseif ($col === 'category') {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN category VARCHAR(60) NULL DEFAULT NULL AFTER metadata");
        }
        echo "  -> Done\n";
    } else {
        echo "Column '$col' already exists, skipping.\n";
    }
}

echo "\n=== Final notifications structure ===\n";
$st = $pdo->query('DESCRIBE notifications');
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  " . $r['Field'] . " | " . $r['Type'] . " | DEFAULT: " . ($r['Default'] ?? 'null') . "\n";
}
echo "\nMigration complete.\n";
