<?php
try {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=nexbus', 'root', '');
  $sql = file_get_contents(__DIR__ . '/database/migrations/2026_04_08_add_bus_details.sql');
  foreach (explode(';', $sql) as $stmt) {
    $s = trim($stmt);
    if ($s) $pdo->exec($s);
  }
  echo "Migration applied successfully!";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
}
?>
