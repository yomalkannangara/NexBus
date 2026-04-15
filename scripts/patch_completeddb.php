<?php
/**
 * Update CompletedDB.sql: patch notifications table to add priority, metadata, category columns.
 * Preserves UTF-16LE BOM encoding.
 */

$file = 'database/CompletedDB.sql';
$raw  = file_get_contents($file);

// Strip UTF-16LE BOM and decode to UTF-8 for string operations
$hasBom  = (substr($raw, 0, 2) === "\xFF\xFE");
$content = $hasBom ? mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE') : $raw;

// ─── Find the exact notifications CREATE TABLE block ─────────────────────────
// Old CREATE TABLE definition (without priority/metadata/category)
$old = 'CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum(\'Message\',\'Delay\',\'Timetable\',\'Alert\',\'Urgent\',\'Breakdown\',\'System\') NOT NULL DEFAULT \'Message\',
  `message` varchar(255) NOT NULL,
  `is_seen` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';

$new = 'CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum(\'Message\',\'Delay\',\'Timetable\',\'Alert\',\'Urgent\',\'Breakdown\',\'System\') NOT NULL DEFAULT \'Message\',
  `message` varchar(255) NOT NULL,
  `priority` enum(\'normal\',\'urgent\',\'critical\') NOT NULL DEFAULT \'normal\',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `category` varchar(60) DEFAULT NULL,
  `is_seen` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';

// Try an exact replacement first
if (strpos($content, $old) !== false) {
    $updated = str_replace($old, $new, $content);
    $replaced = true;
} else {
    // Fallback: use regex to handle any whitespace/line-ending variations
    // Find start of the CREATE TABLE block
    $startMarker = 'CREATE TABLE `notifications` (';
    $endMarker   = ') ENGINE=InnoDB';

    $startPos = strpos($content, $startMarker);
    if ($startPos === false) {
        echo "ERROR: Could not find notifications CREATE TABLE.\n";
        exit(1);
    }

    // Find the ENGINE line that closes this specific table block
    $endPos = strpos($content, $endMarker, $startPos);
    if ($endPos === false) {
        echo "ERROR: Could not find end of notifications table.\n";
        exit(1);
    }

    // Find the semicolon after the ENGINE line
    $semiPos = strpos($content, ';', $endPos);
    if ($semiPos === false) {
        echo "ERROR: Could not find closing semicolon.\n";
        exit(1);
    }

    $old_block = substr($content, $startPos, $semiPos - $startPos + 1);
    echo "Found block to replace (" . strlen($old_block) . " chars):\n";
    echo $old_block . "\n\n";

    $updated  = substr($content, 0, $startPos) . $new . substr($content, $semiPos + 1);
    $replaced = true;
}

if (!$replaced) {
    echo "ERROR: Replacement failed.\n";
    exit(1);
}

// Verify the new columns appear
if (strpos($updated, '`priority`') === false || strpos($updated, '`metadata`') === false || strpos($updated, '`category`') === false) {
    echo "ERROR: New columns not present after replacement.\n";
    exit(1);
}

// Re-encode to UTF-16LE with BOM and write back
$out = ($hasBom ? "\xFF\xFE" : '') . mb_convert_encoding($updated, 'UTF-16LE', 'UTF-8');
file_put_contents($file, $out);

echo "SUCCESS: CompletedDB.sql updated.\n";
echo "New notifications table definition written.\n";

// Verify
$verify = mb_convert_encoding(substr(file_get_contents($file), 2), 'UTF-8', 'UTF-16LE');
$p1 = strpos($verify, '`priority`');
$p2 = strpos($verify, '`metadata`');
$p3 = strpos($verify, '`category`');
echo "Verify priority at: $p1\n";
echo "Verify metadata at: $p2\n";
echo "Verify category at: $p3\n";
