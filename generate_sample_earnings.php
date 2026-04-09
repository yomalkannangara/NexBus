<?php
/**
 * Sample Data Generator for SLTB Earnings
 * Creates 35 days of realistic earning records across 7 buses
 */

try {
    // Direct database connection
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=nexbus',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sample SLTB buses (NA and NB plates only)
    $buses = [
        'NA-2581',
        'NA-2589',
        'NB-2590',
        'NB-2591',
        'NA-2592',
        'NB-2593',
        'NA-2594'
    ];

    // Delete existing SLTB data
    $pdo->exec('DELETE FROM earnings WHERE operator_type = "SLTB"');
    echo "✓ Cleared existing SLTB earnings data\n";

    $sql = 'INSERT INTO earnings (operator_type, bus_reg_no, date, amount, source) 
            VALUES (?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);

    // Generate 35 days (early March to April 8, 2026)
    $startDate = strtotime('2026-03-05');
    $totalRecords = 0;

    for ($day = 0; $day < 35; $day++) {
        $currentDate = date('Y-m-d', $startDate + ($day * 86400));
        $dayOfWeek = (int)date('N', strtotime($currentDate)); // 1=Mon, 7=Sun

        foreach ($buses as $busIdx => $bus) {
            // Base revenue differs by bus (some buses more profitable)
            $baseRevenue = 15000 + ($busIdx * 2500);

            // Weekend multiplier (Friday=5, Saturday=6, Sunday=7)
            $weekendMultiplier = ($dayOfWeek >= 5) ? 1.3 : 0.95;

            // Random variance (0.7 to 1.3)
            $randomVariance = 0.7 + (rand(0, 100) / 100 * 0.6);

            // Calculate daily revenue
            $dailyRevenue = round($baseRevenue * $weekendMultiplier * $randomVariance);

            // Split into 2-4 transactions per day per bus
            $transactionCount = rand(2, 4);
            $transAmount = round($dailyRevenue / $transactionCount);

            for ($t = 0; $t < $transactionCount; $t++) {
                $stmt->execute(['SLTB', $bus, $currentDate, $transAmount, 'Cash']);
                $totalRecords++;
            }
        }
    }

    echo "\n✅ Sample Data Generated Successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Total Records:  $totalRecords\n";
    echo "📅 Date Range:    2026-03-05 to 2026-04-08 (35 days)\n";
    echo "🚌 Buses:         " . count($buses) . "\n";
    echo "   " . implode(', ', $buses) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n📈 Data Characteristics:\n";
    echo "   • Weekend revenue 30% higher than weekdays\n";
    echo "   • Each bus has unique base revenue (NA-2581: ~15K, NA-2594: ~32K)\n";
    echo "   • Daily variance: ±30% for realistic fluctuation\n";
    echo "   • Multiple transactions per day per bus\n";
    echo "\n✨ Visit /M/earnings to see the dashboard!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
