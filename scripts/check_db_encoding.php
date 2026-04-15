<?php
$f = 'database/CompletedDB.sql';
$raw = file_get_contents($f);

// Detect encoding
$enc = mb_detect_encoding($raw, ['UTF-16LE','UTF-16BE','UTF-8'], true);
echo "Encoding: $enc\n";
echo "File size: " . strlen($raw) . " bytes\n";

// Check first bytes for BOM
$bom2 = substr($raw, 0, 2);
if ($bom2 === "\xFF\xFE") echo "Has UTF-16LE BOM\n";
elseif ($bom2 === "\xFE\xFF") echo "Has UTF-16BE BOM\n";
elseif (substr($raw, 0, 3) === "\xEF\xBB\xBF") echo "Has UTF-8 BOM\n";
else echo "No BOM detected\n";

// Try finding notifications in raw
$pos = strpos($raw, 'notifications');
echo "Raw search for 'notifications': " . ($pos !== false ? $pos : 'NOT FOUND') . "\n";

// Convert from UTF-16LE (strip BOM if present)
$content = $raw;
if ($bom2 === "\xFF\xFE") {
    $content = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
}
$pos2 = strpos($content, 'notifications');
echo "After decode search: " . ($pos2 !== false ? $pos2 : 'NOT FOUND') . "\n";

// Show snippet around notifications
if ($pos2 !== false) {
    echo "\n--- snippet ---\n";
    echo substr($content, max(0, $pos2-20), 400) . "\n";
}
