<?php
require __DIR__ . '/../bootstrap/autoload.php';
$cfg = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}",
    $cfg['username'], $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$conductors = $pdo->query("SELECT sltb_conductor_id FROM sltb_conductors WHERE sltb_depot_id=1 LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);
echo "Conductors: " . implode(', ', $conductors) . "\n";

$tables = $pdo->query("SHOW TABLES LIKE '%timetable%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Timetable tables: " . (implode(', ', $tables) ?: 'NONE') . "\n";

$tt = $pdo->query("SELECT * FROM timetables LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Timetable sample:\n";
foreach ($tt as $t) echo "  " . json_encode($t) . "\n";

$maxId = (int)$pdo->query("SELECT MAX(sltb_trip_id) FROM sltb_trips")->fetchColumn();
echo "Max trip ID: $maxId\n";
