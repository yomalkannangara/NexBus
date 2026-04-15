<?php
/**
 * Delete all synthetic seed trips (IDs > 181) and re-seed with corrected data.
 */
require __DIR__ . '/../bootstrap/autoload.php';
$cfg = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}",
    $cfg['username'], $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$deleted = $pdo->exec("DELETE FROM sltb_trips WHERE sltb_trip_id > 181 AND sltb_depot_id = 1");
echo "Deleted $deleted synthetic depot-1 trips.\n";
echo "Remaining trips for depot 1: " . $pdo->query("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=1")->fetchColumn() . "\n";
