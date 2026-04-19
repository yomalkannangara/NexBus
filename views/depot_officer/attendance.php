<?php
/* vars from DepotOfficerController::attendance()
   $drivers, $conductors, $records, $history,
   $date, $histFrom, $histTo, $msg, $savedAt
*/
$today   = date('Y-m-d');
$prevDay = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
$canNext = ($date < $today);

/* Edit-mode / lock state for the marking form */
$savedAt     = $savedAt ?? null;
$hasSaved    = !empty($records) && $savedAt !== null;
$isLocked    = $hasSaved && (time() - strtotime($savedAt)) > 86400;
$isEditMode  = $hasSaved && !$isLocked;

/* Merge all staff into one list for the unified table */
$allStaff = [];
foreach (($drivers ?? []) as $d) {
    $d['_type']          = 'Driver';
    $d['_akey']          = $d['attendance_key'] ?? ('driver:' . (int)$d['id']);
    $allStaff[]          = $d;
}
foreach (($conductors ?? []) as $c) {
    $c['_type']          = 'Conductor';
    $c['_akey']          = $c['attendance_key'] ?? ('conductor:' . (int)$c['id']);
    $allStaff[]          = $c;
}

function attStatusDO(array $records, string $key): string {
    return $records[$key]['status'] ?? 'Absent';
}
function attNoteDO(array $records, string $key): string {
    return htmlspecialchars($records[$key]['notes'] ?? '');
}

/**
 * Build a standalone SVG trend chart from PHP data.
 * Used for initial server-side render — no JS/width dependency.
 */
function buildTrendSvg(array $data): string {
    if (empty($data)) return '';
    $W = 800; $H = 300;
    $pL = 48; $pR = 24; $pT = 18; $pB = 52;
    $cW = $W - $pL - $pR;  // 728
    $cH = $H - $pT - $pB;  // 230
    $n  = count($data);
    $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    $cx = fn(int $i): float => $pL + ($n < 2 ? $cW / 2.0 : $i / ($n - 1) * $cW);
    $cy = fn(float $v): float => $pT + $cH - ($v / 100.0 * $cH);
    $pct = fn(array $d, string $k): float => $d['total'] > 0 ? round($d[$k] / $d['total'] * 100, 1) : 0.0;
    $f   = fn(float $v): string => number_format($v, 2, '.', '');

    $series = [
        ['key'=>'present',  'color'=>'#16a34a', 'op'=>'.12'],
        ['key'=>'absent',   'color'=>'#dc2626', 'op'=>'.08'],
        ['key'=>'late',     'color'=>'#d97706', 'op'=>'.08'],
        ['key'=>'half_day', 'color'=>'#7c3aed', 'op'=>'.08'],
    ];

    $o = '<svg xmlns="http://www.w3.org/2000/svg"'
       . " viewBox=\"0 0 $W $H\" width=\"$W\" height=\"$H\""
       . ' style="display:block;width:100%;height:300px;">' . "\n";

    // Grid + Y labels
    for ($g = 0; $g <= 5; $g++) {
        $v  = $g * 20;
        $gy = $f($cy((float)$v));
        $sw = $g === 0 ? '1.5' : '0.7';
        $o .= "  <line x1=\"$pL\" x2=\"" . ($pL+$cW) . "\" y1=\"$gy\" y2=\"$gy\" stroke=\"#e8d39a\" stroke-width=\"$sw\"/>\n";
        $o .= "  <text x=\"" . ($pL-6) . "\" y=\"" . $f($cy((float)$v)+4) . '" text-anchor="end" font-size="11" fill="#9ca3af">' . $v . "%</text>\n";
    }

    // X labels
    $step = max(1, (int)ceil($n / 14));
    for ($i = 0; $i < $n; $i++) {
        if ($i % $step !== 0 && $i !== $n - 1) continue;
        $xp  = $f($cx($i));
        $yp  = $H - $pB + 16;
        $pts = explode('-', $data[$i]['date']);
        $lbl = htmlspecialchars((int)$pts[2] . ' ' . $months[(int)$pts[1]]);
        $o  .= "  <text x=\"$xp\" y=\"$yp\" text-anchor=\"middle\" font-size=\"11\" fill=\"#6b7280\" transform=\"rotate(-35 $xp $yp)\">$lbl</text>\n";
    }

    // Series: compute points for all series
    $allPts = [];
    foreach ($series as $s) {
        $key = $s['key'];
        $pts2 = [];
        for ($i = 0; $i < $n; $i++) {
            $v = $pct($data[$i], $key);
            // nudge 0%-lines 1px above x-axis border so they don't vanish under it
            $pts2[] = ['x' => $cx($i), 'y' => $v <= 0.0 ? $cy(0.0) - 1.0 : $cy($v)];
        }
        $allPts[$key] = $pts2;
    }

    // Pass 1: all fills
    $aB = $f($cy(0.0));
    foreach ($series as $s) {
        $key = $s['key']; $color = $s['color']; $op = $s['op'];
        $pts2 = $allPts[$key];
        $fp = "M{$pL},{$aB}";
        foreach ($pts2 as $p) { $fp .= ' L' . $f($p['x']) . ',' . $f($p['y']); }
        $fp .= ' L' . ($pL+$cW) . ",{$aB} Z";
        $o .= "  <path d=\"$fp\" fill=\"$color\" fill-opacity=\"$op\" stroke=\"none\"/>\n";
    }

    // Pass 2: all polylines (drawn on top of all fills)
    foreach ($series as $s) {
        $key = $s['key']; $color = $s['color'];
        $pts2 = $allPts[$key];
        $poly = implode(' ', array_map(fn($p) => $f($p['x']).',' . $f($p['y']), $pts2));
        $o .= "  <polyline points=\"$poly\" fill=\"none\" stroke=\"$color\" stroke-width=\"2.5\" stroke-linejoin=\"round\" stroke-linecap=\"round\"/>\n";
    }

    // Pass 3: all dots (on top of everything)
    if ($n <= 60) {
        foreach ($series as $s) {
            $key = $s['key']; $color = $s['color'];
            $pts2 = $allPts[$key];
            foreach ($pts2 as $p) {
                $o .= "  <circle cx=\"" . $f($p['x']) . "\" cy=\"" . $f($p['y']) . "\" r=\"3.5\" fill=\"#fff\" stroke=\"$color\" stroke-width=\"2\"/>\n";
            }
        }
    }
    $o .= '</svg>';
    return $o;
}
?>
<style>
.att-hero {
    background: linear-gradient(135deg,#7B1C3E 0%,#a8274e 100%);
    border-bottom: 4px solid #f3b944;
    color: #fff;
    padding: 28px 32px 24px;
    border-radius: 14px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    flex-shrink: 0;
}
.att-hero h1 { margin: 0; font-size: 1.6rem; font-weight: 700; }
.att-hero p  { margin: 4px 0 0; opacity: .8; font-size: .95rem; }
.att-hero-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.day-nav { display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.15); border-radius: 10px; padding: 6px 10px; }
.day-nav a, .day-nav span { color: #fff; text-decoration: none; font-size: 1rem; padding: 2px 6px; border-radius: 5px; }
.day-nav a:hover { background: rgba(255,255,255,.25); }
.day-nav .day-label { font-weight: 600; font-size: .95rem; min-width: 110px; text-align: center; }

/* Stats row */
.att-stats { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 16px; margin-bottom: 24px; }
@keyframes attCardIn { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
@keyframes attValPop { 0% { transform: scale(.85); opacity: .4; } 60% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
.att-stat-card {
    background: #fff; border-radius: 12px; padding: 20px 18px 16px;
    box-shadow: 0 2px 8px rgba(17,24,39,.06); border-left: 4px solid var(--color);
    display: flex; flex-direction: column; gap: 4px;
    position: relative; overflow: hidden; cursor: default;
    opacity: 0; animation: attCardIn .45s cubic-bezier(.22,.68,0,1.2) forwards;
    transition: transform .15s, box-shadow .15s;
}
.att-stat-card:nth-child(1){animation-delay:.05s}.att-stat-card:nth-child(2){animation-delay:.12s}
.att-stat-card:nth-child(3){animation-delay:.19s}.att-stat-card:nth-child(4){animation-delay:.26s}
.att-stat-card:nth-child(5){animation-delay:.33s}
.att-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 22px rgba(17,24,39,.10); }
.att-stat-card::before { content:''; position:absolute; inset:0; background:linear-gradient(120deg,transparent 60%,rgba(255,255,255,.55) 100%); opacity:0; transition:opacity .25s; pointer-events:none; }
.att-stat-card:hover::before { opacity: 1; }
.att-stat-card .val { font-size: 2rem; font-weight: 700; color: var(--color); line-height: 1; display: inline-block; animation: attValPop .5s cubic-bezier(.22,.68,0,1.2) forwards; animation-delay: inherit; }
.att-stat-card .lbl { font-size: .82rem; color: #6b7280; font-weight: 500; }
.att-stat-card .sub { font-size: .78rem; color: #9ca3af; }
.pct-bar-wrap { background: #f1f5f9; border-radius: 99px; height: 8px; margin-top: 8px; overflow: hidden; }
.pct-bar      { height: 100%; border-radius: 99px; background: var(--color); width: 0; transition: width .8s cubic-bezier(.22,.68,0,1.2); }

/* ── Trend Section ── */
.trend-section {
    background: #fff; border-radius: 14px;
    box-shadow: 0 10px 28px rgba(17,24,39,.08); overflow: hidden; margin-bottom: 28px;
    flex-shrink: 0;
}
.trend-head {
    background: linear-gradient(90deg, #7B1C3E, #a8274e);
    border-bottom: 3px solid #f3b944;
    color: #fff; padding: 14px 20px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.trend-head h3 { margin: 0; font-size: 1rem; font-weight: 700; flex: 1; }
.trend-presets { display: flex; gap: 6px; flex-wrap: wrap; }
.trend-preset-btn {
    background: rgba(255,255,255,.18); color: #fff;
    border: 1.5px solid rgba(255,255,255,.35);
    border-radius: 7px; padding: 5px 13px; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: background .15s, border-color .15s; white-space: nowrap;
}
.trend-preset-btn:hover { background: rgba(255,255,255,.32); }
.trend-preset-btn.active { background: #f3b944; color: #7B1C3E; border-color: #f3b944; }
.trend-filter-bar {
    display: flex; flex-direction: row; align-items: center; gap: 6px 16px; flex-wrap: wrap;
    padding: 12px 20px; background: #fffdf6; border-bottom: 1px solid #e8d39a;
    flex-shrink: 0;
}
.trend-filter-group {
    display: inline-flex; flex-direction: row; align-items: center;
    gap: 7px; flex-shrink: 0;
}
.trend-filter-label {
    font-size: .78rem; font-weight: 700; color: #7B1C3E;
    white-space: nowrap; display: inline; flex-shrink: 0;
}
.trend-filter-input, .trend-filter-select {
    border: 1.5px solid #e8d39a; border-radius: 8px; padding: 6px 10px;
    font-size: .82rem; background: #fff; color: #2b2b2b; flex-shrink: 0;
}
.trend-filter-input:focus, .trend-filter-select:focus {
    outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.2);
}
.trend-custom-range { display: flex; flex-direction: row; align-items: center; gap: 8px; flex-wrap: nowrap; flex-shrink: 0; }
.trend-custom-range[hidden] { display: none !important; }
.trend-apply-btn {
    background: #7B1C3E; color: #fff; border: none; border-radius: 7px;
    padding: 6px 14px; font-size: .82rem; font-weight: 700; cursor: pointer; transition: background .2s;
}
.trend-apply-btn:hover { background: #a8274e; }
.trend-bar-divider { width: 1px; height: 22px; background: #e8d39a; flex-shrink: 0; align-self: center; margin: 0 4px; }
.trend-line-toggles { display: inline-flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.trend-line-toggle {
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    font-size: .78rem; font-weight: 700; white-space: nowrap;
}
.trend-line-toggle input[type=checkbox] { cursor: pointer; accent-color: var(--tc); width: 14px; height: 14px; }
.trend-chart-wrap { padding: 20px 20px 12px; position: relative; flex-shrink: 0; }
#trend-svg-wrap { min-height: 300px; position: relative; overflow: visible; }
.trend-chart-wrap svg { display: block; width: 100%; height: 300px; overflow: visible; }
.trend-no-data { text-align:center; padding:48px; color:#9ca3af; font-size:.9rem; font-style:italic; }

/* ── Top filter bar ── */
.att-filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex; flex-direction: row; align-items: center; gap: 10px; flex-wrap: wrap;
    box-shadow: 0 10px 28px rgba(17,24,39,.08);
    border-left: 4px solid #f3b944;
    margin-bottom: 22px;
    flex-shrink: 0;
}
.att-filter-bar label { font-weight: 700; font-size: .85rem; color: #7B1C3E; white-space: nowrap; display: inline; flex-shrink: 0; }
.att-filter-bar input[type=date],
.att-filter-bar input[type=text],
.att-filter-bar select {
    border: 1.5px solid #e8d39a; border-radius: 8px; padding: 7px 12px;
    font-size: .88rem; color: #2b2b2b; background: #fffdf6;
    transition: border-color .18s, box-shadow .18s;
    flex-shrink: 0; width: auto;
}
.att-filter-bar input[type=text] { min-width: 160px; max-width: 200px; }
.att-filter-bar input[type=date] { width: auto; }
.att-filter-bar select { min-width: 110px; }
.att-filter-bar input:focus, .att-filter-bar select:focus { outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.2); }
.att-filter-divider { width: 1px; height: 26px; background: #e8d39a; flex-shrink: 0; align-self: center; }
.att-filter-bar .go-btn {
    background: #7B1C3E; color: #fff; border: none; border-radius: 8px;
    padding: 8px 18px; font-size: .88rem; font-weight: 700; cursor: pointer; transition: background .2s; flex-shrink: 0;
}
.att-filter-bar .go-btn:hover { background: #a8274e; }

/* Toast */
.att-toast {
    background: #fef3c7; border: 1px solid #f3b944; border-radius: 10px;
    padding: 12px 20px; color: #7B1C3E; font-weight: 600; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
/* History edit button */
.hist-edit-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: #7B1C3E; color: #fff; border-radius: 7px;
    padding: 4px 12px; font-size: .78rem; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    transition: background .18s;
}
.hist-edit-btn:hover { background: #a8274e; color: #fff; }

/* ── Unified Attendance Table Card ── */
.att-table-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 10px 28px rgba(17,24,39,.08); overflow: hidden; margin-bottom: 28px;
}
.att-table-head {
    background: linear-gradient(90deg,#7B1C3E,#a8274e);
    border-bottom: 3px solid #f3b944;
    color: #fff; padding: 14px 20px;
    display: flex; align-items: center; gap: 10px; justify-content: space-between;
}
.att-table-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }
.att-table-head .badge {
    background: rgba(255,255,255,.25);
    border-radius: 99px; padding: 2px 10px; font-size: .78rem; font-weight: 600;
}
.att-main-table { width: 100%; border-collapse: collapse; }
.att-main-table th {
    background: #fff8f0; padding: 10px 16px;
    font-size: .78rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .05em; color: #7B1C3E;
    border-bottom: 1px solid #e8d39a; text-align: left; white-space: nowrap;
}
.att-main-table td { padding: 10px 16px; border-bottom: 1px solid #fdf3e3; vertical-align: middle; }
.att-main-table tr:last-child td { border-bottom: none; }
.att-main-table tr:hover td { background: #fffdf6; }
.att-main-table .muted { color: #9ca3af; font-style: italic; }
.staff-name-cell { font-weight: 600; color: #1f2937; font-size: .9rem; }
.staff-type-pill {
    display: inline-block; padding: 2px 9px; border-radius: 99px; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em;
}
.type-driver    { background: #fce8ef; color: #7B1C3E; }
.type-conductor { background: #fef3c7; color: #92400e; }

/* ── Status toggle pill buttons ── */
.status-toggle { display: flex; gap: 5px; }
.status-toggle-btn {
    padding: 5px 13px; border-radius: 99px; border: 1.5px solid #e5e7eb;
    font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    cursor: pointer; background: #f9fafb; color: #6b7280;
    transition: background .15s, border-color .15s, color .15s, transform .1s;
    white-space: nowrap;
}
.status-toggle-btn:hover { transform: scale(1.04); }
.status-toggle-btn.active-present { background: #d1fae5; border-color: #16a34a; color: #065f46; }
.status-toggle-btn.active-absent  { background: #fee2e2; border-color: #dc2626; color: #991b1b; }
.status-toggle-btn.active-leave   { background: #fef3c7; border-color: #d97706; color: #92400e; }
.status-toggle-btn.active-late    { background: #fef9c3; border-color: #ca8a04; color: #854d0e; }
.status-toggle-btn.active-half    { background: #fce8ef; border-color: #7B1C3E; color: #7B1C3E; }

.note-input {
    border: 1.5px solid #e8d39a; border-radius: 8px; padding: 5px 10px;
    font-size: .82rem; width: 100%; min-width: 120px; box-sizing: border-box; color: #2b2b2b;
}
.note-input:focus { outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.2); }

/* Suspended row */
.att-row--suspended {
    background: repeating-linear-gradient(45deg,#f9fafb,#f9fafb 6px,#f3f4f6 6px,#f3f4f6 12px) !important;
    opacity: .7; pointer-events: none; user-select: none;
}
.att-row--suspended td { color: #9ca3af !important; }
.att-row--suspended .staff-name-cell { text-decoration: line-through; color: #9ca3af; }
.att-lock-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 999px;
    padding: 2px 8px; font-size: .70rem; font-weight: 700; color: #6b7280;
    vertical-align: middle; margin-left: 6px;
}

/* Save bar */
.att-save-bar {
    display: flex; justify-content: flex-end; align-items: center; gap: 14px;
    padding: 16px 20px;
    background: #fff8f0; border-top: 1px solid #e8d39a;
}
.btn-save {
    background: #7B1C3E; color: #fff; border: none;
    border-radius: 10px; padding: 11px 32px;
    font-size: .95rem; font-weight: 700; cursor: pointer;
    letter-spacing: .02em; transition: background .2s;
}
.btn-save:hover { background: #a8274e; }

/* ── History Section ── */
.history-section {
    background: #fff; border-radius: 14px;
    box-shadow: 0 10px 28px rgba(17,24,39,.08); overflow: hidden; margin-bottom: 28px;
}
.history-head {
    padding: 16px 22px; border-bottom: 1px solid #e8d39a;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.history-head h3 { margin: 0; font-size: 1rem; font-weight: 800; color: #7B1C3E; }
.hist-filter { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.hist-filter label { font-size: .82rem; color: #6b7280; font-weight: 600; }
.hist-filter input[type=date] {
    border: 1.5px solid #e8d39a; border-radius: 7px; padding: 5px 10px;
    font-size: .82rem; background: #fffdf6; color: #2b2b2b;
}
.hist-filter input[type=date]:focus { outline: none; border-color: #f3b944; }
.hist-filter .go-sm {
    background: #7B1C3E; color: #fff; border: none; border-radius: 7px;
    padding: 6px 14px; font-size: .82rem; font-weight: 700; cursor: pointer; transition: background .2s;
}
.hist-filter .go-sm:hover { background: #a8274e; }

/* History search bar */
.hist-search-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 20px; background: #fffdf6; border-bottom: 1px solid #e8d39a; flex-wrap: wrap;
}
.hist-search-wrap { position: relative; }
.hist-search-icon { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.hist-search-input {
    padding: 7px 28px 7px 30px; border: 1.5px solid #e8d39a; border-radius: 8px;
    font-size: .82rem; background: #fff; color: #2b2b2b; width: 180px;
}
.hist-search-input:focus { outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.18); }
.hist-clear-btn { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 2px; display: flex; align-items: center; }
.hist-clear-btn:hover { color: #7B1C3E; }
.hist-bar-divider { width: 1px; height: 22px; background: #e8d39a; flex-shrink: 0; }
.hist-filter-group { display: flex; align-items: center; gap: 5px; }
.hist-filter-label { font-size: .76rem; font-weight: 700; color: #7B1C3E; white-space: nowrap; }
.hist-filter-select {
    border: 1.5px solid #e8d39a; border-radius: 8px; padding: 6px 8px;
    font-size: .80rem; background: #fff; color: #2b2b2b; cursor: pointer;
}
.hist-filter-select:focus { outline: none; border-color: #f3b944; }
.hist-result-count {
    margin-left: auto; font-size: .73rem; font-weight: 700; color: #7B1C3E;
    background: #fce8ef; border: 1px solid #f9a8c0; border-radius: 999px; padding: 3px 10px; white-space: nowrap;
}

/* History table */
.history-table { width: 100%; border-collapse: collapse; }
.history-table th {
    background: #f9f4e8; padding: 10px 18px;
    font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em;
    color: #7B1C3E; border-bottom: 1px solid #e8d39a; text-align: left;
}
.history-table td { padding: 10px 18px; border-bottom: 1px solid #fdf3e3; font-size: .88rem; color: #1f2937; }
.history-table tr:last-child td { border-bottom: none; }
.history-table tr:hover td { background: #fffdf6; }
.history-table .type-badge {
    display: inline-block; padding: 2px 9px; border-radius: 99px;
    font-size: .72rem; font-weight: 800; text-transform: uppercase;
}
.status-pill { display: inline-block; padding: 3px 12px; border-radius: 99px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.pill-present  { background: #d1fae5; color: #065f46; }
.pill-absent   { background: #fee2e2; color: #991b1b; }
.pill-late     { background: #fef3c7; color: #92400e; }
.pill-leave    { background: #fef3c7; color: #92400e; }
.pill-half_day { background: #fce8ef; color: #7B1C3E; }

.hist-no-results td { text-align: center; padding: 28px; color: #9ca3af; font-size: .88rem; font-style: italic; }
.empty-hist { padding: 32px; text-align: center; color: #9ca3af; font-size: .9rem; }

@media(max-width:768px){
    .att-filter-bar { gap: 8px; }
    .status-toggle { flex-wrap: wrap; }
    .att-main-table th, .att-main-table td { padding: 8px 10px; }
}
</style>

<?php if (!empty($msg) && $msg === 'saved'): ?>
<div class="att-toast">
    <svg width="18" height="18" fill="none" stroke="#7B1C3E" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    Attendance saved successfully for <?= date('d M Y', strtotime($date)) ?>.
</div>
<?php elseif (!empty($msg) && $msg === 'expired'): ?>
<div class="att-toast" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b;">
    <svg width="18" height="18" fill="none" stroke="#991b1b" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    This attendance record is older than 24 hours and can no longer be edited.
</div>
<?php endif; ?>

<!-- Hero -->
<section class="att-hero">
    <div>
        <h1>Staff Attendance</h1>
        <p>Mark and track daily attendance for your drivers and conductors</p>
    </div>
    <div class="att-hero-right">
        <div class="day-nav">
            <a href="/O/attendance?date=<?= $prevDay ?>" title="Previous day">&#8592;</a>
            <span class="day-label"><?= date('d M Y', strtotime($date)) ?></span>
            <?php if ($canNext): ?>
            <a href="/O/attendance?date=<?= $nextDay ?>" title="Next day">&#8594;</a>
            <?php else: ?>
            <span style="opacity:.3;cursor:default;">&#8594;</span>
            <?php endif; ?>
        </div>
        <?php if ($date !== $today): ?>
        <a href="/O/attendance" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid #f3b944;border-radius:9px;padding:8px 16px;text-decoration:none;font-size:.85rem;font-weight:600;">
            &#8635; Today
        </a>
        <?php endif; ?>
    </div>
</section>

<!-- Trend Chart Section -->
<section class="trend-section">
    <div class="trend-head">
        <h3>&#128200; Attendance Trend</h3>
        <div class="trend-presets">
            <button class="trend-preset-btn" data-days="7">7 Days</button>
            <button class="trend-preset-btn" data-days="14">14 Days</button>
            <button class="trend-preset-btn active" data-days="30">30 Days</button>
            <button class="trend-preset-btn" data-days="90">90 Days</button>
            <button class="trend-preset-btn" data-days="0">Custom</button>
        </div>
    </div>

    <div class="trend-filter-bar">
        <!-- Custom date range -->
        <div class="trend-custom-range" id="trend-custom-wrap" hidden>
            <label class="trend-filter-label">From</label>
            <input type="date" id="trend-from" class="trend-filter-input" max="<?= $today ?>">
            <label class="trend-filter-label">To</label>
            <input type="date" id="trend-to" class="trend-filter-input" max="<?= $today ?>">
            <button type="button" id="trend-apply-btn" class="trend-apply-btn">Apply</button>
        </div>

        <!-- Person filter -->
        <div class="trend-filter-group">
            <label class="trend-filter-label" for="trend-person-input">Person</label>
            <input type="text" id="trend-person-input" class="trend-filter-input"
                   list="trend-person-list" placeholder="All staff&hellip;"
                   autocomplete="off" style="width:160px;">
            <datalist id="trend-person-list">
                <?php foreach ($allStaff as $_ts): ?>
                <option value="<?= htmlspecialchars($_ts['full_name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <span class="trend-bar-divider"></span>

        <!-- Role filter -->
        <div class="trend-filter-group">
            <label class="trend-filter-label" for="trend-role-select">Role</label>
            <select id="trend-role-select" class="trend-filter-select">
                <option value="all">All Roles</option>
                <option value="driver">Driver</option>
                <option value="conductor">Conductor</option>
            </select>
        </div>

        <span class="trend-bar-divider"></span>

        <!-- Status lines to show -->
        <div class="trend-filter-group" style="flex-wrap:wrap;gap:6px;">
            <label class="trend-filter-label" style="align-self:center;">Show</label>
            <div class="trend-line-toggles">
                <label class="trend-line-toggle" style="--tc:#16a34a">
                    <input type="checkbox" name="trend-line" value="present" checked> Present
                </label>
                <label class="trend-line-toggle" style="--tc:#dc2626">
                    <input type="checkbox" name="trend-line" value="absent" checked> Absent
                </label>
                <label class="trend-line-toggle" style="--tc:#d97706">
                    <input type="checkbox" name="trend-line" value="late" checked> Late
                </label>
                <label class="trend-line-toggle" style="--tc:#7c3aed">
                    <input type="checkbox" name="trend-line" value="half_day"> Half Day
                </label>
            </div>
        </div>
    </div>

    <div class="trend-chart-wrap">
        <div id="trend-svg-wrap"><?php if (!empty($trendData)) { echo buildTrendSvg($trendData); } ?></div>
        <div id="trend-no-data" class="trend-no-data"<?php if (!empty($trendData)): ?> hidden<?php endif; ?>>No attendance records found for the selected period.</div>
    </div>
</section>

<!-- Top Filter Bar -->
<div class="att-filter-bar">
    <label>&#128197; Date:</label>
    <input form="att-date-form" type="date" name="date" value="<?= htmlspecialchars($date) ?>" max="<?= $today ?>" id="att-date-pick">
    <button form="att-date-form" type="submit" class="go-btn">View &amp; Mark</button>
    <form id="att-date-form" method="get" action="/O/attendance" style="display:none;"></form>
    <span class="att-filter-divider"></span>
    <label for="att-name-search">&#128269; Name:</label>
    <input type="text" id="att-name-search" placeholder="Search staff name…" autocomplete="off">
    <span class="att-filter-divider"></span>
    <label for="att-role-filter">Role:</label>
    <select id="att-role-filter">
        <option value="all">All Roles</option>
        <option value="driver">Drivers</option>
        <option value="conductor">Conductors</option>
    </select>
</div>

<!-- Attendance Marking Table -->
<form method="post" action="/O/attendance" id="attendanceForm">
<input type="hidden" name="work_date" value="<?= htmlspecialchars($date) ?>">

<div class="att-table-card">
    <div class="att-table-head">
        <div style="display:flex;align-items:center;gap:10px;">
            <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            <h3>Mark Attendance — <?= date('d M Y', strtotime($date)) ?></h3>
        </div>
        <span class="badge"><?= count($allStaff) ?> staff</span>
    </div>

    <?php if (empty($allStaff)): ?>
    <div style="padding:32px;text-align:center;color:#9ca3af;">No staff found for your depot.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="att-main-table" id="attTable">
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allStaff as $s):
            $isSuspended = ($s['status'] ?? '') === 'Suspended';
            $akey        = $s['_akey'];
            $type        = $s['_type'];
            $sel         = $isSuspended ? 'Absent' : attStatusDO($records, $akey);
            /* map status to toggle class */
            $selSlug     = strtolower(str_replace(['_',' '],'-',$sel));
            $rowCls      = $isSuspended ? 'att-row--suspended' : '';
            $typeCls     = strtolower($type);
        ?>
        <tr class="<?= $rowCls ?>"
            data-name="<?= strtolower(htmlspecialchars($s['full_name'])) ?>"
            data-role="<?= $typeCls ?>">

            <input type="hidden" name="attendance[<?= htmlspecialchars($akey) ?>]"
                   id="hid-<?= htmlspecialchars($akey) ?>"
                   value="<?= htmlspecialchars($sel) ?>">

            <td>
                <span class="staff-name-cell"><?= htmlspecialchars($s['full_name']) ?></span>
                <?php if ($isSuspended): ?>
                <span class="att-lock-badge">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    Suspended
                </span>
                <?php endif; ?>
            </td>

            <td><span class="staff-type-pill type-<?= $typeCls ?>"><?= $type ?></span></td>

            <td>
                <?php if ($isSuspended): ?>
                <span class="muted">Locked</span>
                <?php else: ?>
                <div class="status-toggle" data-akey="<?= htmlspecialchars($akey) ?>">
                    <?php
                    $opts = ['Present'=>'present','Absent'=>'absent','Late'=>'late','Half_Day'=>'half'];
                    foreach ($opts as $val => $slug):
                        $isActive = ($sel === $val);
                    ?>
                    <button type="button"
                            class="status-toggle-btn<?= $isActive ? ' active-'.$slug : '' ?>"
                            data-val="<?= $val ?>"
                            data-slug="<?= $slug ?>">
                        <?= $val === 'Half_Day' ? 'Half Day' : $val ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </td>

            <td>
                <input type="text"
                       name="notes[<?= htmlspecialchars($akey) ?>]"
                       class="note-input"
                       value="<?= $isSuspended ? '' : attNoteDO($records, $akey) ?>"
                       placeholder="<?= $isSuspended ? 'Staff suspended' : 'Optional note…' ?>"
                       <?= $isSuspended ? 'disabled' : '' ?>>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="att-save-bar">
        <?php if ($isLocked): ?>
        <span style="color:#991b1b;font-size:.82rem;font-weight:600;">&#128274; Attendance locked — edit window has expired (24 h limit).</span>
        <?php else: ?>
        <span style="font-size:.82rem;color:#64748b;"><?= $isEditMode ? '&#9998; Editing saved attendance for ' : 'Marking for ' ?><?= date('d M Y', strtotime($date)) ?></span>
        <button type="submit" class="btn-save">&#10003;&nbsp;<?= $isEditMode ? 'Update Attendance' : 'Save Attendance' ?></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</form>

<!-- Attendance History Section -->
<section class="history-section" id="attendance-history">
    <div class="history-head">
        <h3>&#128200; Attendance History</h3>
        <form method="get" action="/O/attendance" class="hist-filter">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($histFrom) ?>" max="<?= $today ?>">
            <label>To</label>
            <input type="date" name="to"   value="<?= htmlspecialchars($histTo)   ?>" max="<?= $today ?>">
            <button type="submit" class="go-sm">Filter</button>
        </form>
    </div>

    <!-- Search & filter bar for history -->
    <div class="hist-search-bar">
        <div class="hist-search-wrap">
            <svg class="hist-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" id="hist-name-search" class="hist-search-input" placeholder="Search by name…" autocomplete="off">
            <button type="button" id="hist-search-clear" class="hist-clear-btn" hidden aria-label="Clear">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <span class="hist-bar-divider"></span>
        <div class="hist-filter-group">
            <label class="hist-filter-label" for="hist-type-filter">Role</label>
            <select id="hist-type-filter" class="hist-filter-select">
                <option value="all">All Staff</option>
                <option value="driver">Driver</option>
                <option value="conductor">Conductor</option>
            </select>
        </div>
        <span class="hist-bar-divider"></span>
        <div class="hist-filter-group">
            <label class="hist-filter-label" for="hist-status-filter">Status</label>
            <select id="hist-status-filter" class="hist-filter-select">
                <option value="all">All Statuses</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="half_day">Half Day</option>
            </select>
        </div>
        <span class="hist-result-count" id="hist-result-count"></span>
    </div>

    <?php if (empty($history)): ?>
    <div class="empty-hist">
        <svg width="36" height="36" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="17" y2="13"/><line x1="7" y1="17" x2="13" y2="17"/></svg>
        <p>No attendance records found for the selected period.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="history-table" id="hist-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Role</th>
                <th>Name</th>
                <th>Status</th>
                <th>Notes</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="hist-tbody">
        <?php
        $shownEditDates = [];
        foreach ($history as $h):
            $st        = strtolower(str_replace(' ','_',(string)($h['status'] ?? 'Present')));
            $pillCls   = 'pill-' . $st;
            $typeLower = strtolower((string)($h['staff_type'] ?? 'driver'));
            $hDate     = (string)($h['work_date'] ?? '');
            $updatedAt = (string)($h['updated_at'] ?? '');
            $canEditRow = $updatedAt && (time() - strtotime($updatedAt)) <= 86400;
            $showEditBtn = $canEditRow && !in_array($hDate, $shownEditDates, true);
            if ($showEditBtn) { $shownEditDates[] = $hDate; }
        ?>
        <tr data-name="<?= strtolower(htmlspecialchars((string)($h['full_name'] ?? ''))) ?>"
            data-type="<?= $typeLower ?>"
            data-status="<?= $st ?>">
            <td><?= date('d M Y', strtotime((string)$h['work_date'])) ?></td>
            <td><span class="type-badge type-<?= $typeLower ?>"><?= htmlspecialchars((string)($h['staff_type'] ?? '—')) ?></span></td>
            <td style="font-weight:600;"><?= htmlspecialchars((string)($h['full_name'] ?? '—')) ?></td>
            <td><span class="status-pill <?= $pillCls ?>"><?= htmlspecialchars(str_replace('_',' ',(string)($h['status'] ?? 'Present'))) ?></span></td>
            <td style="color:#64748b;"><?= htmlspecialchars((string)($h['notes'] ?? '')) ?: '—' ?></td>
            <td><?php if ($showEditBtn): ?>
                <a href="/O/attendance?date=<?= urlencode($hDate) ?>" class="hist-edit-btn" title="Edit attendance for this date">&#9998; Edit</a>
            <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>

<script>
/* ── Disable all inputs when attendance is locked ── */
(function () {
    var form = document.getElementById('attendanceForm');
    if (form && form.dataset.locked === '1') {
        form.querySelectorAll('input, select, textarea, button[type=submit]').forEach(function (el) {
            el.disabled = true;
        });
        form.querySelectorAll('.status-toggle-btn').forEach(function (el) {
            el.disabled = true;
            el.style.cursor = 'not-allowed';
        });
    }
})();

/* ── Status toggle pill buttons ── */
document.querySelectorAll('.status-toggle').forEach(function(group) {
    var akey   = group.dataset.akey;
    var hidden = document.getElementById('hid-' + akey);
    group.querySelectorAll('.status-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            group.querySelectorAll('.status-toggle-btn').forEach(function(b) {
                b.className = 'status-toggle-btn';
            });
            btn.classList.add('active-' + btn.dataset.slug);
            if (hidden) hidden.value = btn.dataset.val;
        });
    });
});

/* ── Attendance table live filter (name + role) ── */
(function() {
    var nameEl = document.getElementById('att-name-search');
    var roleEl = document.getElementById('att-role-filter');
    var rows   = Array.from(document.querySelectorAll('#attTable tbody tr'));
    function filterAtt() {
        var nm = (nameEl ? nameEl.value : '').toLowerCase().trim();
        var rl = roleEl ? roleEl.value : 'all';
        rows.forEach(function(tr) {
            var nOk = !nm || (tr.dataset.name || '').includes(nm);
            var rOk = rl === 'all' || tr.dataset.role === rl;
            tr.style.display = (nOk && rOk) ? '' : 'none';
        });
    }
    if (nameEl) nameEl.addEventListener('input', filterAtt);
    if (roleEl) roleEl.addEventListener('change', filterAtt);
})();

/* ── History table live filter ── */
(function() {
    var searchInput  = document.getElementById('hist-name-search');
    var clearBtn     = document.getElementById('hist-search-clear');
    var typeFilter   = document.getElementById('hist-type-filter');
    var statusFilter = document.getElementById('hist-status-filter');
    var countBadge   = document.getElementById('hist-result-count');
    var tbody        = document.getElementById('hist-tbody');
    if (!tbody) return;

    var allRows  = Array.from(tbody.querySelectorAll('tr[data-name]'));
    var noResRow = null;

    function filterRows() {
        var term   = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var type   = typeFilter   ? typeFilter.value   : 'all';
        var status = statusFilter ? statusFilter.value : 'all';
        var visible = 0;
        allRows.forEach(function(row) {
            var ok = (!term   || row.dataset.name.includes(term))
                  && (type   === 'all' || row.dataset.type   === type)
                  && (status === 'all' || row.dataset.status === status);
            row.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        if (countBadge) countBadge.textContent = visible + ' record' + (visible !== 1 ? 's' : '');
        if (noResRow) { noResRow.remove(); noResRow = null; }
        if (visible === 0 && allRows.length > 0) {
            noResRow = document.createElement('tr');
            noResRow.className = 'hist-no-results';
            noResRow.innerHTML = '<td colspan="5">No records match your search.</td>';
            tbody.appendChild(noResRow);
        }
        if (clearBtn) clearBtn.hidden = !(searchInput && searchInput.value);
    }

    if (searchInput)  searchInput.addEventListener('input', filterRows);
    if (clearBtn)     clearBtn.addEventListener('click', function() { searchInput.value = ''; filterRows(); searchInput.focus(); });
    if (typeFilter)   typeFilter.addEventListener('change', filterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRows);
    filterRows();
})();

</script>
<script>
/* ── Attendance Trend Chart — pure SVG, zero dependencies ── */
document.addEventListener('DOMContentLoaded', function() {
  try {

    var today     = '<?= $today ?>';
    var trendFrom = '<?= date('Y-m-d', strtotime('-29 days')) ?>';
    var trendTo   = today;
    var trendAkey = '';
    var trendRole = 'all';
    var lastData  = [];

    /* Staff map: name → akey */
    var trendStaffMap = {};
    <?php foreach ($allStaff as $_sm): ?>
    trendStaffMap[<?= json_encode(htmlspecialchars_decode($_sm['full_name']), JSON_UNESCAPED_UNICODE) ?>] = <?= json_encode($_sm['_akey']) ?>;
    <?php endforeach; ?>

    var SERIES = [
        { key: 'present',  label: 'Present',  color: '#16a34a', fillAlpha: '.12' },
        { key: 'absent',   label: 'Absent',   color: '#dc2626', fillAlpha: '.08' },
        { key: 'late',     label: 'Late',     color: '#d97706', fillAlpha: '.08' },
        { key: 'half_day', label: 'Half Day', color: '#7c3aed', fillAlpha: '.08', dash: '6 4' }
    ];

    var svgWrap    = document.getElementById('trend-svg-wrap');
    var noDataEl   = document.getElementById('trend-no-data');
    var presetBtns = document.querySelectorAll('.trend-preset-btn');
    var customWrap = document.getElementById('trend-custom-wrap');
    var fromEl     = document.getElementById('trend-from');
    var toEl       = document.getElementById('trend-to');
    var applyBtn   = document.getElementById('trend-apply-btn');
    var personInp  = document.getElementById('trend-person-input');
    var roleSelect = document.getElementById('trend-role-select');
    var lineChecks = document.querySelectorAll('input[name="trend-line"]');

    if (fromEl) fromEl.value = trendFrom;
    if (toEl)   toEl.value   = trendTo;

    /* ── Preset buttons ── */
    presetBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            presetBtns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var days = parseInt(btn.dataset.days, 10);
            if (days === 0) {
                customWrap.removeAttribute('hidden');
            } else {
                customWrap.setAttribute('hidden', '');
                trendTo   = today;
                trendFrom = isoDate(new Date(Date.now() - (days - 1) * 86400000));
                if (fromEl) fromEl.value = trendFrom;
                if (toEl)   toEl.value   = trendTo;
                fetchTrend();
            }
        });
    });

    if (applyBtn) applyBtn.addEventListener('click', function() {
        if (!fromEl.value || !toEl.value) return;
        trendFrom = fromEl.value; trendTo = toEl.value; fetchTrend();
    });

    if (personInp) {
        personInp.addEventListener('change', function() {
            var n = personInp.value.trim();
            trendAkey = trendStaffMap.hasOwnProperty(n) ? trendStaffMap[n] : '';
            fetchTrend();
        });
        personInp.addEventListener('input', function() {
            if (!personInp.value.trim()) { trendAkey = ''; fetchTrend(); }
        });
    }
    if (roleSelect) roleSelect.addEventListener('change', function() { trendRole = roleSelect.value; fetchTrend(); });
    lineChecks.forEach(function(cb) { cb.addEventListener('change', function() { drawSvg(lastData); }); });

    /* ── Fetch ── */
    function fetchTrend() {
        noDataEl.hidden = true;
        svgWrap.style.opacity = '0.4';
        var url = '/O/attendance?action=trend&from=' + trendFrom + '&to=' + trendTo;
        if (trendAkey) url += '&akey=' + encodeURIComponent(trendAkey);
        if (trendRole && trendRole !== 'all') url += '&role=' + encodeURIComponent(trendRole);
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function(data) {
                svgWrap.style.opacity = '';
                if (data && data.error) { showMsg(data.error); return; }
                lastData = Array.isArray(data) ? data : [];
                drawSvg(lastData);
            })
            .catch(function(e) { svgWrap.style.opacity = ''; showMsg('Could not load: ' + e.message); });
    }

    function showMsg(msg) {
        svgWrap.innerHTML = '';
        noDataEl.textContent = msg;
        noDataEl.hidden = false;
    }

    /* ── SVG renderer ── */
    function drawSvg(data) {
        svgWrap.innerHTML = '';
        if (!data || data.length === 0) { showMsg('No attendance records found for the selected period.'); return; }
        noDataEl.hidden = true;

        /* which series are visible */
        var vis = {};
        if (lineChecks.length > 0) {
            lineChecks.forEach(function(cb) { vis[cb.value] = cb.checked; });
        } else {
            SERIES.forEach(function(s) { vis[s.key] = true; });
        }

        var W = svgWrap.clientWidth || svgWrap.offsetWidth
               || (svgWrap.parentElement ? svgWrap.parentElement.clientWidth : 0)
               || 800;
        var H = 300;
        var PAD = { top: 18, right: 24, bottom: 52, left: 48 };
        var cW = W - PAD.left - PAD.right;
        var cH = H - PAD.top  - PAD.bottom;
        var n  = data.length;
        var ns = 'http://www.w3.org/2000/svg';

        function pct(d, key) {
            return d.total > 0 ? Math.round(d[key] / d.total * 100) : 0;
        }
        function cx(i) { return PAD.left + (n < 2 ? cW / 2 : i / (n - 1) * cW); }
        function cy(v) { return PAD.top + cH - (v / 100 * cH); }

        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('width', W);
        svg.setAttribute('height', H);
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.style.display = 'block'; svg.style.overflow = 'visible';

        /* grid lines + Y labels */
        for (var g = 0; g <= 5; g++) {
            var v = g * 20;
            var gy = cy(v);
            var gl = document.createElementNS(ns, 'line');
            gl.setAttribute('x1', PAD.left); gl.setAttribute('x2', PAD.left + cW);
            gl.setAttribute('y1', gy); gl.setAttribute('y2', gy);
            gl.setAttribute('stroke', '#e8d39a'); gl.setAttribute('stroke-width', g === 0 ? '1.5' : '0.7');
            svg.appendChild(gl);
            var yt = document.createElementNS(ns, 'text');
            yt.setAttribute('x', PAD.left - 6); yt.setAttribute('y', gy + 4);
            yt.setAttribute('text-anchor', 'end');
            yt.setAttribute('font-size', '11'); yt.setAttribute('fill', '#9ca3af');
            yt.textContent = v + '%';
            svg.appendChild(yt);
        }

        /* X axis labels — show at most 14 */
        var step = Math.ceil(n / 14);
        for (var i = 0; i < n; i++) {
            if (i % step !== 0 && i !== n - 1) continue;
            var xl = document.createElementNS(ns, 'text');
            xl.setAttribute('x', cx(i)); xl.setAttribute('y', H - PAD.bottom + 16);
            xl.setAttribute('text-anchor', 'middle');
            xl.setAttribute('font-size', '11'); xl.setAttribute('fill', '#6b7280');
            xl.setAttribute('transform', 'rotate(-35 ' + cx(i) + ' ' + (H - PAD.bottom + 16) + ')');
            xl.textContent = dispDate(data[i].date);
            svg.appendChild(xl);
        }

        /* tooltip crosshair group */
        var tipGroup = document.createElementNS(ns, 'g');
        tipGroup.style.display = 'none';
        var tipLine = document.createElementNS(ns, 'line');
        tipLine.setAttribute('stroke', '#cbd5e1'); tipLine.setAttribute('stroke-width', '1.5');
        tipLine.setAttribute('stroke-dasharray', '4 3');
        tipGroup.appendChild(tipLine);
        svg.appendChild(tipGroup);

        /* pre-compute points for all visible series */
        var visSeries = SERIES.filter(function(s) { return vis[s.key]; });
        var allPts = {};
        visSeries.forEach(function(s) {
            allPts[s.key] = data.map(function(d, i) {
                var v = pct(d, s.key);
                return { x: cx(i), y: v <= 0 ? cy(0) - 1 : cy(v) };
            });
        });

        /* Pass 1: fills */
        var areaBottom = cy(0);
        visSeries.forEach(function(s) {
            var pts = allPts[s.key];
            var fillPath = 'M' + PAD.left + ',' + areaBottom;
            pts.forEach(function(p) { fillPath += ' L' + p.x + ',' + p.y; });
            fillPath += ' L' + (PAD.left + cW) + ',' + areaBottom + ' Z';
            var fill = document.createElementNS(ns, 'path');
            fill.setAttribute('d', fillPath);
            fill.setAttribute('fill', s.color);
            fill.setAttribute('fill-opacity', s.fillAlpha);
            fill.setAttribute('stroke', 'none');
            svg.appendChild(fill);
        });

        /* Pass 2: lines (on top of all fills) */
        visSeries.forEach(function(s) {
            var pts = allPts[s.key];
            var polyline = document.createElementNS(ns, 'polyline');
            polyline.setAttribute('points', pts.map(function(p) { return p.x + ',' + p.y; }).join(' '));
            polyline.setAttribute('fill', 'none');
            polyline.setAttribute('stroke', s.color);
            polyline.setAttribute('stroke-width', '2.5');
            if (s.dash) polyline.setAttribute('stroke-dasharray', s.dash);
            polyline.setAttribute('stroke-linejoin', 'round');
            polyline.setAttribute('stroke-linecap', 'round');
            svg.appendChild(polyline);
        });

        /* Pass 3: dots (on top of everything) */
        if (n <= 60) {
            visSeries.forEach(function(s) {
                allPts[s.key].forEach(function(p) {
                    var dot = document.createElementNS(ns, 'circle');
                    dot.setAttribute('cx', p.x); dot.setAttribute('cy', p.y); dot.setAttribute('r', '3.5');
                    dot.setAttribute('fill', '#fff'); dot.setAttribute('stroke', s.color); dot.setAttribute('stroke-width', '2');
                    svg.appendChild(dot);
                });
            });
        }

        /* invisible hover zones + tooltip */
        var tooltip = document.createElement('div');
        tooltip.style.cssText = 'position:absolute;background:#1f2937;color:#fff;border-radius:8px;padding:9px 13px;font-size:.78rem;pointer-events:none;display:none;z-index:9;min-width:120px;line-height:1.6;box-shadow:0 4px 16px rgba(0,0,0,.22);';
        svgWrap.style.position = 'relative';
        svgWrap.appendChild(tooltip);

        for (var hi = 0; hi < n; hi++) {
            (function(idx) {
                var hx = cx(idx);
                var zone = document.createElementNS(ns, 'rect');
                var zw = n < 2 ? cW : (n < 3 ? cW / 2 : cW / (n - 1));
                zone.setAttribute('x', hx - zw / 2); zone.setAttribute('y', PAD.top);
                zone.setAttribute('width', zw); zone.setAttribute('height', cH);
                zone.setAttribute('fill', 'transparent');
                zone.style.cursor = 'crosshair';
                zone.addEventListener('mouseenter', function() {
                    tipLine.setAttribute('x1', hx); tipLine.setAttribute('x2', hx);
                    tipLine.setAttribute('y1', PAD.top); tipLine.setAttribute('y2', PAD.top + cH);
                    tipGroup.style.display = '';
                    var d = data[idx];
                    var html = '<div style="font-weight:700;margin-bottom:4px;border-bottom:1px solid rgba(255,255,255,.15);padding-bottom:4px;">' + dispDate(d.date) + '</div>';
                    SERIES.forEach(function(s) {
                        if (!vis[s.key]) return;
                        var v = pct(d, s.key);
                        html += '<div style="display:flex;align-items:center;gap:7px;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + s.color + ';"></span>' + s.label + ': <b>' + v + '%</b></div>';
                    });
                    tooltip.innerHTML = html;
                    tooltip.style.display = 'block';
                    var svgRect  = svg.getBoundingClientRect();
                    var wrapRect = svgWrap.getBoundingClientRect();
                    var tx = hx * W / (svgRect.width || W) - (wrapRect.left - svgRect.left);
                    var tipW = tooltip.offsetWidth || 140;
                    tooltip.style.left  = Math.min(tx + 14, W - tipW - 10) + 'px';
                    tooltip.style.top   = (PAD.top + 10) + 'px';
                });
                zone.addEventListener('mouseleave', function() {
                    tipGroup.style.display = 'none';
                    tooltip.style.display  = 'none';
                });
                svg.appendChild(zone);
            })(hi);
        }

        svgWrap.appendChild(svg);
    }

    /* ── Legend below chart ── */
    function buildLegend() {
        var leg = document.createElement('div');
        leg.style.cssText = 'display:flex;gap:18px;flex-wrap:wrap;padding:0 24px 16px;justify-content:center;';
        SERIES.forEach(function(s) {
            var item = document.createElement('label');
            item.style.cssText = 'display:flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;cursor:pointer;color:#374151;';
            var swatch = document.createElement('span');
            swatch.style.cssText = 'display:inline-block;width:22px;height:3px;border-radius:2px;background:' + s.color + ';';
            if (s.dash) swatch.style.background = 'repeating-linear-gradient(90deg,' + s.color + ' 0,' + s.color + ' 5px,transparent 5px,transparent 8px)';
            item.appendChild(swatch);
            item.appendChild(document.createTextNode(s.label));
            leg.appendChild(item);
        });
        return leg;
    }

    /* insert legend once after svg-wrap */
    var legEl = buildLegend();
    svgWrap.parentNode.insertBefore(legEl, svgWrap.nextSibling);

    /* redraw on resize */
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() { if (lastData.length) drawSvg(lastData); }, 120);
    });

    function isoDate(d) {
        var mm = d.getMonth() + 1, dd = d.getDate();
        return d.getFullYear() + '-' + (mm < 10 ? '0' + mm : mm) + '-' + (dd < 10 ? '0' + dd : dd);
    }
    function dispDate(s) {
        var p = s.split('-');
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1];
    }

    /* PHP already rendered the initial chart server-side.
       JS takes over only for filter changes and resize. */
    var initialData = <?= json_encode($trendData ?? [], JSON_UNESCAPED_UNICODE) ?>;
    lastData = initialData;

  } catch(e) {
    var _nd = document.getElementById('trend-no-data');
    if (_nd) { _nd.textContent = 'Setup error: ' + e.message; _nd.hidden = false; _nd.style.color = '#dc2626'; }
    console.error('[Trend] Setup error:', e);
  }
});
</script>

