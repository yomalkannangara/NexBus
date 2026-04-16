<?php
$file = 'database/CompletedDB.sql';
$raw  = file_get_contents($file);

// Detect and strip BOM / UTF-16LE
if (substr($raw, 0, 2) === "\xFF\xFE") {
    $sql   = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
    $hasBom = true;
} else {
    $sql   = $raw;
    $hasBom = false;
}

$newDef = "CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('Message','Delay','Timetable','Alert','Urgent','Breakdown','System') NOT NULL DEFAULT 'Message',
  `message` varchar(255) NOT NULL,
  `priority` enum('normal','urgent','critical') NOT NULL DEFAULT 'normal',
  `metadata` longtext DEFAULT NULL,
  `category` varchar(60) DEFAULT NULL,
  `is_seen` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notif_depot_type_time` (`user_id`,`type`,`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

$new = preg_replace('/CREATE TABLE `notifications`[^;]+;/s', $newDef, $sql, 1, $count);
if ($count === 0) {
    echo "PATTERN NOT MATCHED - could not replace\n";
    exit(1);
}
echo "Replaced notifications CREATE TABLE\n";

if ($hasBom) {
    $out = "\xFF\xFE" . mb_convert_encoding($new, 'UTF-16LE', 'UTF-8');
} else {
    $out = $new;
}
file_put_contents($file, $out);
echo "Done\n";
