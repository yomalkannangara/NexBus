<?php
// Test buildTrendSvg in complete isolation — no HTML output from attendance.php
function buildTrendSvg(array $data): string {
    if (empty($data)) return 'EMPTY';
    $W = 800; $H = 300;
    $pL = 48; $pR = 24; $pT = 18; $pB = 52;
    $cW = $W - $pL - $pR;
    $cH = $H - $pT - $pB;
    $n  = count($data);
    $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    $cx = fn(int $i): float => $pL + ($n < 2 ? $cW / 2.0 : $i / ($n - 1) * $cW);
    $cy = fn(float $v): float => $pT + $cH - ($v / 100.0 * $cH);
    $pct = fn(array $d, string $k): float => $d['total'] > 0 ? round($d[$k] / $d['total'] * 100, 1) : 0.0;
    $f   = fn(float $v): string => number_format($v, 2, '.', '');

    $o = '<svg>';
    for ($i = 0; $i < $n; $i++) {
        $p = ['x' => $cx($i), 'y' => $cy($pct($data[$i], 'present'))];
        $o .= '<circle cx="' . $f($p['x']) . '" cy="' . $f($p['y']) . '" r="3"/>';
    }
    $o .= '</svg>';
    return $o;
}

$data = [
    ['date'=>'2026-04-15','present'=>10,'absent'=>2,'late'=>3,'half_day'=>1,'total'=>16],
    ['date'=>'2026-04-18','present'=>12,'absent'=>1,'late'=>1,'half_day'=>0,'total'=>14],
];

$svg = buildTrendSvg($data);
echo "Output length: " . strlen($svg) . "\n";
echo $svg . "\n";
echo "\nPHP version: " . PHP_VERSION . "\n";
