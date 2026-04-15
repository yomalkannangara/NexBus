<?php
require __DIR__ . '/../bootstrap/autoload.php';
$cfg = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$depotId = 1;
$from = date('Y-m-01');
$to   = date('Y-m-d');

echo "Testing fixed model queries for depot $depotId ($from to $to)\n\n";

// 1. Attendance (fixed: no a.status reference)
try {
    $st = $pdo->prepare("
        SELECT a.attendance_key,
               SUM(CASE WHEN a.mark_absent = 0 THEN 1 ELSE 0 END) AS present_days,
               SUM(CASE WHEN a.mark_absent = 1 THEN 1 ELSE 0 END) AS absent_days,
               COUNT(*) AS total_days
        FROM depot_attendance a
        WHERE a.sltb_depot_id = ? AND a.work_date BETWEEN ? AND ?
        GROUP BY a.attendance_key LIMIT 3
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "attendance: " . count($rows) . "+ rows (sample):\n";
    foreach ($rows as $r) echo "  " . $r['attendance_key'] . " present={$r['present_days']} absent={$r['absent_days']}\n";
} catch (Exception $e) { echo "attendance ERROR: " . $e->getMessage() . "\n"; }

// 2. Trip completion (fixed: no start_delay_seconds)
try {
    $st = $pdo->prepare("
        SELECT COALESCE(t.trip_date, CURDATE()) AS trip_date, COUNT(*) AS total_trips,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS delayed,
               SUM(CASE WHEN t.status='Cancelled' THEN 1 ELSE 0 END) AS cancelled
        FROM sltb_trips t WHERE t.sltb_depot_id = ? AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
        GROUP BY COALESCE(t.trip_date, CURDATE()) LIMIT 5
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\ntrip_completion: " . count($rows) . " date rows\n";
    foreach ($rows as $r) echo "  {$r['trip_date']}: total={$r['total_trips']} done={$r['completed']} delayed={$r['delayed']}\n";
} catch (Exception $e) { echo "trip_completion ERROR: " . $e->getMessage() . "\n"; }

// 3. Driver performance (fixed: uses sltb_depot_id directly)
try {
    $st = $pdo->prepare("
        SELECT sd.full_name AS driver_name, COUNT(*) AS trips_assigned,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed
        FROM sltb_trips t JOIN sltb_drivers sd ON sd.sltb_driver_id = t.sltb_driver_id
        WHERE t.sltb_depot_id = ? AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
        GROUP BY t.sltb_driver_id, sd.full_name LIMIT 3
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\ndriver_performance: " . count($rows) . " drivers\n";
    foreach ($rows as $r) echo "  {$r['driver_name']}: {$r['trips_assigned']} trips, {$r['completed']} done\n";
} catch (Exception $e) { echo "driver_performance ERROR: " . $e->getMessage() . "\n"; }

// Check routes table
try {
    $rows = $pdo->query("SHOW TABLES LIKE '%route%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nRoute-related tables: " . (implode(', ', $rows) ?: 'NONE') . "\n";
    $rows2 = $pdo->query("SHOW TABLES LIKE '%assign%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Assignment tables: " . (implode(', ', $rows2) ?: 'NONE') . "\n";
    // Check sltb_drivers columns
    $cols = $pdo->query("DESCRIBE sltb_drivers")->fetchAll(PDO::FETCH_COLUMN);
    echo "sltb_drivers columns: " . implode(', ', $cols) . "\n";
} catch (Exception $e) { echo "meta check ERROR: " . $e->getMessage() . "\n"; }

// Test tripCompletion with backtick-quoted 'delayed'
try {
    $st = $pdo->prepare("
        SELECT COALESCE(t.trip_date, CURDATE()) AS trip_date, COUNT(*) AS total_trips,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                              AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`,
               SUM(CASE WHEN t.status='Cancelled' THEN 1 ELSE 0 END) AS cancelled
        FROM sltb_trips t WHERE t.sltb_depot_id = ? AND COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?
        GROUP BY COALESCE(t.trip_date, CURDATE()) LIMIT 5
    ");
    $st->execute([1, date('Y-m-01'), date('Y-m-d')]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\ntrip_completion (backtick): " . count($rows) . " rows\n";
    foreach ($rows as $r) echo "  {$r['trip_date']}: total={$r['total_trips']} done={$r['completed']} delayed={$r['delayed']}\n";
} catch (Exception $e) { echo "trip_completion (backtick) ERROR: " . $e->getMessage() . "\n"; }

echo "\nDone.\n";

// Table row counts
foreach (['depot_attendance','sltb_trips','sltb_buses','sltb_drivers','sltb_conductors'] as $t) {
    try { $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); echo "$t: $n rows\n"; }
    catch (Exception $e) { echo "$t: MISSING or ERROR — " . $e->getMessage() . "\n"; }
}

// Trips date range
try {
    $r = $pdo->query("SELECT MIN(trip_date), MAX(trip_date), COUNT(*) FROM sltb_trips")->fetch();
    echo "\nsltb_trips date range: $r[0] to $r[1], total: $r[2] rows\n";
} catch (Exception $e) { echo "sltb_trips check error: " . $e->getMessage() . "\n"; }

// Bus depot distribution
try {
    $rows = $pdo->query("SELECT sltb_depot_id, COUNT(*) AS n FROM sltb_buses GROUP BY sltb_depot_id ORDER BY n DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nBuses per depot:\n";
    foreach ($rows as $r) echo "  Depot {$r['sltb_depot_id']}: {$r['n']} buses\n";
} catch (Exception $e) { echo "buses depot check: " . $e->getMessage() . "\n"; }

// Try the attendance query with depot 1
try {
    $depotId = 1;
    $st = $pdo->prepare("SELECT COUNT(*) FROM depot_attendance WHERE sltb_depot_id=? AND work_date BETWEEN ? AND ?");
    $st->execute([$depotId, $from, $to]);
    echo "\ndepot_attendance rows for depot $depotId ($from to $to): " . $st->fetchColumn() . "\n";
} catch (Exception $e) { echo "depot_attendance query error: " . $e->getMessage() . "\n"; }

// Try the trips query with depot 1
try {
    $depotId = 1;
    $st = $pdo->prepare("SELECT COUNT(*) FROM sltb_trips t INNER JOIN sltb_buses b ON b.reg_no = t.bus_reg_no AND b.sltb_depot_id = ? WHERE COALESCE(t.trip_date, CURDATE()) BETWEEN ? AND ?");
    $st->execute([$depotId, $from, $to]);
    echo "sltb_trips for depot $depotId buses ($from to $to): " . $st->fetchColumn() . "\n";
} catch (Exception $e) { echo "trips query error: " . $e->getMessage() . "\n"; }

// Check sltb_trips columns
try {
    $cols = $pdo->query("DESCRIBE sltb_trips")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nsltb_trips columns: " . implode(', ', array_column($cols, 'Field')) . "\n";
} catch (Exception $e) { echo "DESCRIBE sltb_trips error: " . $e->getMessage() . "\n"; }

// Check depot_attendance columns
try {
    $cols = $pdo->query("DESCRIBE depot_attendance")->fetchAll(PDO::FETCH_ASSOC);
    echo "depot_attendance columns: " . implode(', ', array_column($cols, 'Field')) . "\n";
} catch (Exception $e) { echo "DESCRIBE depot_attendance error: " . $e->getMessage() . "\n"; }

echo "\nDone.\n";
