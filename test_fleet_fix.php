<?php
require_once 'bootstrap/app.php';

use App\models\depot_manager\FleetModel;

$FM = new FleetModel();
$FM->setDepot(1);

// Test 1: Get bus list
echo "=== Testing FleetModel::list() ===\n";
try {
    $buses = $FM->list([]);
    echo "✓ Query successful!\n";
    echo "Bus count: " . count($buses) . "\n";
    
    if (count($buses) > 0) {
        $firstBus = $buses[0];
        echo "\nFirst bus sample:\n";
        echo "  reg_no: " . ($firstBus['reg_no'] ?? 'N/A') . "\n";
        echo "  bus_model: " . ($firstBus['bus_model'] ?? 'N/A') . "\n";
        echo "  year_of_manufacture: " . ($firstBus['year_of_manufacture'] ?? 'N/A') . "\n";
        echo "  manufacture_date: " . ($firstBus['manufacture_date'] ?? 'N/A') . "\n";
        echo "  bus_class: " . ($firstBus['bus_class'] ?? 'N/A') . "\n";
        echo "  status: " . ($firstBus['status'] ?? 'N/A') . "\n";
        echo "  capacity: " . ($firstBus['capacity'] ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Summary cards
echo "\n=== Testing FleetModel::summaryCards() ===\n";
try {
    $cards = $FM->summaryCards([]);
    echo "✓ Query successful!\n";
    echo "Total buses: " . ($cards['total'] ?? 0) . "\n";
    echo "Active: " . ($cards['active'] ?? 0) . "\n";
    echo "Maintenance: " . ($cards['maintenance'] ?? 0) . "\n";
    echo "Out of Service: " . ($cards['out_of_service'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Filters with year range
echo "\n=== Testing FleetModel::list() with year_range filter ===\n";
try {
    $buses = $FM->list(['year_range' => '2015-2020']);
    echo "✓ Query successful!\n";
    echo "Buses in 2015-2020: " . count($buses) . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ All tests completed!\n";
?>
