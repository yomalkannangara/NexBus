<?php
require __DIR__ . '/../bootstrap/autoload.php';
$cfg = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$from = date('Y-m-01');
$to   = date('Y-m-d');
$depotId = 1;

// 1. driver performance (with backtick delayed + employee_no)
try {
    $st = $pdo->prepare("
        SELECT sd.full_name AS driver_name, COALESCE(sd.employee_no,'') AS license_number,
               COUNT(*) AS trips_assigned,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.status='Cancelled' THEN 1 ELSE 0 END) AS cancelled,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                         AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`
        FROM sltb_trips t JOIN sltb_drivers sd ON sd.sltb_driver_id = t.sltb_driver_id
        WHERE t.sltb_depot_id=? AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
          AND t.sltb_driver_id IS NOT NULL
        GROUP BY t.sltb_driver_id, sd.full_name, sd.employee_no LIMIT 3
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "driver_perf: " . count($rows) . " rows " . (count($rows) ? "(first: {$rows[0]['driver_name']} emp={$rows[0]['license_number']})" : "EMPTY") . "\n";
} catch (Exception $e) { echo "driver_perf ERROR: " . $e->getMessage() . "\n"; }

// 2. trip completion (backtick delayed)
try {
    $st = $pdo->prepare("
        SELECT COALESCE(t.trip_date,CURDATE()) AS trip_date, COUNT(*) AS total_trips,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                         AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`,
               SUM(CASE WHEN t.status='Cancelled' THEN 1 ELSE 0 END) AS cancelled
        FROM sltb_trips t WHERE t.sltb_depot_id=? AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
        GROUP BY COALESCE(t.trip_date,CURDATE()) ORDER BY trip_date LIMIT 5
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "trip_completion: " . count($rows) . " date rows " . (count($rows) ? "(first: {$rows[0]['trip_date']} total={$rows[0]['total_trips']} done={$rows[0]['completed']})" : "EMPTY") . "\n";
} catch (Exception $e) { echo "trip_completion ERROR: " . $e->getMessage() . "\n"; }

// 3. delay analysis (LEFT JOIN routes + delayed_trips alias)
try {
    $st = $pdo->prepare("
        SELECT COALESCE(r.route_no,'Unknown') AS route_no, COALESCE(r.route_no,'Unknown Route') AS route_name,
               COUNT(*) AS total_trips,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                         AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS delayed_trips,
               AVG(CASE WHEN t.departure_time > t.scheduled_departure_time
                        THEN TIME_TO_SEC(TIMEDIFF(t.departure_time, t.scheduled_departure_time))/60.0
                        ELSE NULL END) AS avg_delay_min
        FROM sltb_trips t LEFT JOIN routes r ON r.route_id=t.route_id
        WHERE t.sltb_depot_id=? AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
        GROUP BY t.route_id, r.route_no LIMIT 3
    ");
    $st->execute([$depotId, $from, $to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "delay_analysis: " . count($rows) . " rows " . (count($rows) ? "(first: {$rows[0]['route_no']} trips={$rows[0]['total_trips']} delayed={$rows[0]['delayed_trips']})" : "EMPTY") . "\n";
} catch (Exception $e) { echo "delay_analysis ERROR: " . $e->getMessage() . "\n"; }

// 4. bus utilization (backtick delayed + sltb_assignments)
try {
    $st = $pdo->prepare("
        SELECT b.reg_no AS bus_reg_no, COUNT(DISTINCT t.sltb_trip_id) AS total_trips,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                         AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`,
               COUNT(DISTINCT COALESCE(t.trip_date,CURDATE())) AS active_days
        FROM sltb_buses b LEFT JOIN sltb_trips t ON t.bus_reg_no=b.reg_no
             AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
        WHERE b.sltb_depot_id=?
        GROUP BY b.reg_no ORDER BY total_trips DESC LIMIT 5
    ");
    $st->execute([$from, $to, $depotId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "bus_utilization: " . count($rows) . " rows " . (count($rows) ? "(first: {$rows[0]['bus_reg_no']} trips={$rows[0]['total_trips']})" : "EMPTY") . "\n";
} catch (Exception $e) { echo "bus_util ERROR: " . $e->getMessage() . "\n"; }

// 5. bus utilization with bus_model and assignments join (full model query)
try {
    $st = $pdo->prepare("
        SELECT b.reg_no AS bus_reg_no, COALESCE(b.bus_model,'') AS bus_make,
               COUNT(DISTINCT t.sltb_trip_id) AS total_trips,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                         AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`,
               COUNT(DISTINCT COALESCE(t.trip_date,CURDATE())) AS active_days,
               COUNT(DISTINCT a.assignment_id) AS assignments_count
        FROM sltb_buses b
        LEFT JOIN sltb_trips t ON t.bus_reg_no=b.reg_no AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
        LEFT JOIN sltb_assignments a ON a.bus_reg_no=b.reg_no AND a.assigned_date BETWEEN ? AND ? AND a.sltb_depot_id=?
        WHERE b.sltb_depot_id=?
        GROUP BY b.reg_no, b.bus_model ORDER BY total_trips DESC LIMIT 5
    ");
    $st->execute([$from, $to, $from, $to, $depotId, $depotId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "bus_utilization_full: " . count($rows) . " rows " . (count($rows) ? "(first: {$rows[0]['bus_reg_no']} model={$rows[0]['bus_make']} trips={$rows[0]['total_trips']} asgn={$rows[0]['assignments_count']})" : "EMPTY") . "\n";
} catch (Exception $e) { echo "bus_util_full ERROR: " . $e->getMessage() . "\n"; }
try {
    $cols = $pdo->query("DESCRIBE routes")->fetchAll(PDO::FETCH_COLUMN);
    echo "routes columns: " . implode(', ', $cols) . "\n";
    $cols2 = $pdo->query("DESCRIBE sltb_buses")->fetchAll(PDO::FETCH_COLUMN);
    echo "sltb_buses columns: " . implode(', ', $cols2) . "\n";
    $cols3 = $pdo->query("DESCRIBE sltb_assignments")->fetchAll(PDO::FETCH_COLUMN);
    echo "sltb_assignments columns: " . implode(', ', $cols3) . "\n";
} catch (Exception $e) { echo "describe ERROR: " . $e->getMessage() . "\n"; }

// Get drivers, buses, routes for seeding
try {
    $drivers = $pdo->query("SELECT sltb_driver_id, full_name FROM sltb_drivers WHERE sltb_depot_id=1 ORDER BY sltb_driver_id LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nDepot 1 drivers (".count($drivers)."):\n";
    foreach ($drivers as $d) echo "  {$d['sltb_driver_id']}: {$d['full_name']}\n";
    $buses = $pdo->query("SELECT reg_no FROM sltb_buses WHERE sltb_depot_id=1 ORDER BY reg_no LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
    echo "Depot 1 buses (first 20): " . implode(', ', $buses) . "\n";
    $routes = $pdo->query("SELECT route_id, route_no FROM routes WHERE is_active=1 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "Routes: "; foreach ($routes as $r) echo "{$r['route_id']}={$r['route_no']} "; echo "\n";
    $timetables = $pdo->query("SELECT timetable_id, route_id, departure_time, arrival_time, turn_no FROM sltb_timetable LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Timetable sample:\n"; foreach ($timetables as $t) echo "  tt={$t['timetable_id']} route={$t['route_id']} dep={$t['departure_time']}\n";
    $conductors = $pdo->query("SELECT sltb_conductor_id FROM sltb_conductors WHERE sltb_depot_id=1 LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
    echo "Depot 1 conductors: " . implode(', ', $conductors) . "\n";
} catch (Exception $e) { echo "seed prep ERROR: ".$e->getMessage()."\n"; }
try {
    $r = $pdo->query("SELECT COUNT(*) FROM sltb_drivers WHERE sltb_depot_id=1")->fetchColumn();
    echo "\nDrivers in depot 1: $r\n";
    $r = $pdo->query("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=1")->fetchColumn();
    echo "All-time trips depot 1: $r\n";
    $rows = $pdo->query("SELECT YEAR(trip_date) AS yr, MONTH(trip_date) AS mo, COUNT(*) AS n, COUNT(DISTINCT sltb_driver_id) AS drivers FROM sltb_trips WHERE sltb_depot_id=1 GROUP BY yr,mo ORDER BY yr,mo")->fetchAll(PDO::FETCH_ASSOC);
    echo "Trips by month:\n";
    foreach ($rows as $r) echo "  {$r['yr']}-{$r['mo']}: {$r['n']} trips, {$r['drivers']} unique drivers\n";
    $r2 = $pdo->query("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_trips WHERE sltb_depot_id=1")->fetchColumn();
    echo "All-time unique drivers with trips in depot 1: $r2\n";

    // Count for last-30-days range (default controller range)
    $from30 = date('Y-m-d', strtotime('-30 days'));
    $to30   = date('Y-m-d');
    $st = $pdo->prepare("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_trips WHERE sltb_depot_id=1 AND trip_date BETWEEN ? AND ?");
    $st->execute([$from30, $to30]);
    echo "\nDrivers with trips in last 30 days ($from30 – $to30): " . $st->fetchColumn() . "\n";
    $st2 = $pdo->prepare("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=1 AND trip_date BETWEEN ? AND ?");
    $st2->execute([$from30, $to30]);
    echo "Total trips in that range: " . $st2->fetchColumn() . "\n";
} catch (Exception $e) { echo "audit ERROR: ".$e->getMessage()."\n"; }

// Verify actual model driver performance query (no LIMIT)
try {
    $from30 = date('Y-m-d', strtotime('-30 days'));
    $to30   = date('Y-m-d');
    $st = $pdo->prepare("
        SELECT sd.full_name AS driver_name, COUNT(*) AS trips_assigned,
               SUM(CASE WHEN t.status='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN t.departure_time IS NOT NULL AND t.scheduled_departure_time IS NOT NULL
                    AND t.departure_time > t.scheduled_departure_time THEN 1 ELSE 0 END) AS `delayed`
        FROM sltb_trips t JOIN sltb_drivers sd ON sd.sltb_driver_id = t.sltb_driver_id
        WHERE t.sltb_depot_id=1 AND COALESCE(t.trip_date,CURDATE()) BETWEEN ? AND ?
          AND t.sltb_driver_id IS NOT NULL
        GROUP BY t.sltb_driver_id, sd.full_name, sd.employee_no
        ORDER BY trips_assigned DESC
    ");
    $st->execute([$from30, $to30]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\nFull driver perf (last 30 days, no LIMIT): " . count($rows) . " drivers\n";
    foreach ($rows as $r) echo "  {$r['driver_name']}: {$r['trips_assigned']} trips, {$r['completed']} done, {$r['delayed']} delayed\n";
} catch (Exception $e) { echo "model query ERROR: ".$e->getMessage()."\n"; }

