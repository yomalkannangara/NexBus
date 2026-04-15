<?php
$raw = file_get_contents('database/CompletedDB.sql');
$content = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');

// Find and show the notifications CREATE TABLE block
$pos = strpos($content, 'CREATE TABLE `notifications`');
if ($pos === false) { echo "NOT FOUND\n"; exit; }

$end = strpos($content, ';', $pos);
echo substr($content, $pos, $end - $pos + 1) . "\n";
