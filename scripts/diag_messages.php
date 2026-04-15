<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=nexbus;charset=utf8mb4','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// notifications table structure
$st = $pdo->query('DESCRIBE notifications');
echo "=== notifications ===\n";
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) echo $r['Field'] . ' | ' . $r['Type'] . ' | ' . $r['Null'] . ' | ' . ($r['Default'] ?? 'null') . "\n";

// users table - roles
$st = $pdo->query('SELECT DISTINCT role FROM users ORDER BY role');
echo "\n=== user roles ===\n";
foreach($st->fetchAll(PDO::FETCH_COLUMN) as $r) echo $r . "\n";

// users with private_operator_id column?
$st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='private_operator_id'");
echo "\n=== users.private_operator_id exists: " . $st->fetchColumn() . " ===\n";

// all users columns
$st = $pdo->query('DESCRIBE users');
echo "\n=== users table cols ===\n";
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) echo $r['Field'] . ' | ' . $r['Type'] . "\n";

// private operator related tables
$st = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME");
echo "\n=== ALL tables ===\n";
foreach($st->fetchAll(PDO::FETCH_COLUMN) as $r) echo $r . "\n";
