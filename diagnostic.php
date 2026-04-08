<?php
// Diagnostic script to check fleet data

require __DIR__ . '/bootstrap/app.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=nexbus', 'root', '');
    
    echo "=== DATABASE DIAGNOSTICS ===\n\n";
    
    // Check if bus columns exist
    echo "1. Checking sltb_buses table structure:\n";
    $st = $pdo->query("SHOW COLUMNS FROM sltb_buses LIKE 'bus_%'");
    $cols = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "   - {$c['Field']}: {$c['Type']}\n";
    }
    echo "\n";
    
    // Count buses in database
    echo "2. Bus count per depot:\n";
    $st = $pdo->query("SELECT sltb_depot_id, COUNT(*) as cnt FROM sltb_buses GROUP BY sltb_depot_id");
    $counts = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $c) {
        echo "   Depot {$c['sltb_depot_id']}: {$c['cnt']} buses\n";
    }
    echo "\n";
    
    // Check a sample bus
    echo "3. Sample bus record:\n";
    $st = $pdo->query("SELECT * FROM sltb_buses LIMIT 1");
    $sample = $st->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        foreach ($sample as $k => $v) {
            echo "   {$k}: " . ($v === null ? '(NULL)' : $v) . "\n";
        }
    } else {
        echo "   No buses found!\n";
    }
    echo "\n";
    
    // Test the list query manually
    echo "4. Testing list query for depot_id=1:\n";
    $sql = "
        SELECT
            sb.reg_no, sb.status, sb.capacity, sb.chassis_no,
            sb.bus_model, sb.year_manufacture, sb.manufacture_date, sb.bus_class,
            r.route_no, r.stops_json,
            tm.lat AS current_lat,
            tm.lng AS current_lng
        FROM sltb_buses sb
        LEFT JOIN (
            SELECT bus_reg_no, MAX(timetable_id) AS max_tt
            FROM timetables WHERE operator_type='SLTB'
            GROUP BY bus_reg_no
        ) s1 ON s1.bus_reg_no = sb.reg_no
        LEFT JOIN timetables tt ON tt.timetable_id = s1.max_tt
        LEFT JOIN routes r ON r.route_id = tt.route_id
        LEFT JOIN (
            SELECT x.bus_reg_no, MAX(x.snapshot_at) AS maxsnap
            FROM tracking_monitoring x
            WHERE x.operator_type='SLTB'
            GROUP BY x.bus_reg_no
        ) lg ON lg.bus_reg_no = sb.reg_no
        LEFT JOIN tracking_monitoring tm
          ON tm.bus_reg_no = sb.reg_no
         AND tm.operator_type='SLTB'
         AND tm.snapshot_at = lg.maxsnap
        WHERE sb.sltb_depot_id=1
        LIMIT 5
    ";
    
    $st = $pdo->prepare($sql);
    $st->execute();
    $result = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "   Query returned " . count($result) . " rows\n";
    if ($result) {
        echo "   First row: " . json_encode($result[0]) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
