<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=nexbus', 'root', '');
$st = $pdo->query('DESCRIBE sltb_buses');
echo "=== sltb_buses COLUMNS ===\n";
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
