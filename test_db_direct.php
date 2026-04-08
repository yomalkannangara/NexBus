<?php
// Direct database test without session/model complexity
require_once 'Support/Env.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$name = $_ENV['DB_NAME'] ?? 'nexbus';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database!\n\n";
    
    // Test 1: Simple column check
    echo "=== Testing SELECT with year_of_manufacture ===\n";
    $sql = "SELECT reg_no, bus_model, year_of_manufacture, manufacture_date, bus_class 
            FROM sltb_buses WHERE sltb_depot_id = 1 LIMIT 3";
    $st = $pdo->prepare($sql);
    $st->execute();
    $buses = $st->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Query successful!\n";
    echo "Buses returned: " . count($buses) . "\n\n";
    
    foreach ($buses as $i => $bus) {
        echo "Bus " . ($i+1) . ":\n";
        echo "  reg_no: " . ($bus['reg_no'] ?? 'NULL') . "\n";
        echo "  bus_model: " . ($bus['bus_model'] ?? 'NULL') . "\n";
        echo "  year_of_manufacture: " . ($bus['year_of_manufacture'] ?? 'NULL') . "\n";
        echo "  manufacture_date: " . ($bus['manufacture_date'] ?? 'NULL') . "\n";
        echo "  bus_class: " . ($bus['bus_class'] ?? 'NULL') . "\n\n";
    }
    
    // Test 2: Count buses by depot
    echo "=== Bus count by depot ===\n";
    $sql = "SELECT sltb_depot_id, COUNT(*) as cnt FROM sltb_buses GROUP BY sltb_depot_id ORDER BY sltb_depot_id";
    $st = $pdo->prepare($sql);
    $st->execute();
    $counts = $st->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counts as $row) {
        echo "Depot " . $row['sltb_depot_id'] . ": " . $row['cnt'] . " buses\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
