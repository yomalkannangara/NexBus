<?php
// Quick test: does buildTrendSvg produce valid output?
$drivers = $conductors = [];
$date = date('Y-m-d');
$records = $history = [];
$allStaff = [];
$summary = $historyError = null;
$histFrom = $histTo = $date;
$msg = null;
$trendData = [
    ['date'=>'2026-04-15','present'=>10,'absent'=>2,'late'=>3,'half_day'=>1,'total'=>16],
    ['date'=>'2026-04-16','present'=>12,'absent'=>1,'late'=>1,'half_day'=>0,'total'=>14],
];
$today = date('Y-m-d');

// Load only the PHP block (before the HTML/style output)
// We manually paste the buildTrendSvg function here to avoid HTML output
require_once __DIR__ . '/../views/depot_officer/attendance.php';

// Function should already be defined
$svg = buildTrendSvg($trendData);
header('Content-Type: text/plain');
echo "Length: " . strlen($svg) . "\n";
echo substr($svg, 0, 500);
