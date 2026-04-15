<?php
/* vars: report_type, hr_rows, hr_summary, kpis, analyticsJson,
         from, to, routes, buses, filters */
$reportType  = $report_type ?? 'attendance';
$hrRows      = $hr_rows     ?? [];
$hrSummary   = $hr_summary  ?? [];
$from        = $from        ?? date('Y-m-01');
$to          = $to          ?? date('Y-m-d');
$filters     = $filters     ?? [];
$kpis        = $kpis        ?? [];

$reportTypes = [
    'attendance'         => 'Staff Attendance Report',
    'driver_performance' => 'Driver Performance Report',
    'trip_completion'    => 'Trip Completion Rate',
    'delay_analysis'     => 'Delay Analysis Report',
    'bus_utilization'    => 'Bus Utilization Report',
];

$depotId   = $_SESSION['user']['sltb_depot_id'] ?? 0;
$depotName = htmlspecialchars($_SESSION['user']['depot_name'] ?? ('Depot ' . $depotId));

$baseQs = http_build_query([
    'report_type' => $reportType,
    'from'        => $from,
    'to'          => $to,
]);

/* ── Badge/helper functions ─────────────────────────── */
function rpBadge(string $label, string $cls): string {
    return '<span class="rp-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}
function pctBar(float $pct, string $color): string {
    $w = min(100, max(0, $pct));
    return '<div class="rp-pct-wrap">'
         . '<div class="rp-pct-bar"><div class="rp-pct-fill" style="width:' . $w . '%;background:' . $color . ';"></div></div>'
         . '<span class="rp-pct-lbl" style="color:' . $color . ';">' . $pct . '%</span>'
         . '</div>';
}
function pctColor(float $pct, float $good = 85, float $ok = 70): string {
    return $pct >= $good ? '#16a34a' : ($pct >= $ok ? '#f59e0b' : '#dc2626');
}
function gradeClass(string $grade): string {
    return 'badge-' . strtolower(in_array($grade, ['A','B','C','D']) ? $grade : 'staff');
}
function trendArrow(string $trend): string {
    return match($trend) {
        'improving' => '<span class="rp-badge badge-improving">↑ Improving</span>',
        'declining' => '<span class="rp-badge badge-declining">↓ Declining</span>',
        default     => '<span class="rp-badge badge-stable">→ Stable</span>',
    };
}
function slotBadge(string $slot): string {
    $labels = ['early-morning'=>'🌅 Early Morning','morning'=>'🌤 Morning','afternoon'=>'☀ Afternoon','evening'=>'🌇 Evening','night'=>'🌙 Night'];
    return '<span class="rp-badge badge-' . $slot . '">' . htmlspecialchars($labels[$slot] ?? ucfirst($slot)) . '</span>';
}
?>
<link rel="stylesheet" href="/assets/css/reports.css">

<!-- Print-only header -->
<div class="rp-print-header" id="rp-print-header-el">
    <h2><?= htmlspecialchars($reportTypes[$reportType] ?? 'Report') ?></h2>
    <p>Depot: <strong><?= $depotName ?></strong> &nbsp;|&nbsp;
       Period: <strong><?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></strong> &nbsp;|&nbsp;
       Generated: <strong><?= date('d M Y H:i') ?></strong></p>
</div>

<div class="rp-page">

<!-- Hero -->
<section class="rp-hero">
    <div>
        <h1>&#128202; Depot Reports</h1>
        <p><?= htmlspecialchars($reportTypes[$reportType] ?? 'Report') ?> &bull; <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></p>
    </div>
    <div class="rp-hero-right">
        <button class="rp-btn rp-btn-csv" id="rp-csv-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </button>
        <button class="rp-btn rp-btn-pdf" id="rp-print-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / PDF
        </button>
    </div>
</section>

<!-- Filter bar -->
<div class="rp-filter-bar">
<form method="get" action="/O/reports" id="rp-filter-form">
    <div class="rp-field">
        <span class="rp-field-label">Report Type</span>
        <select name="report_type" class="type-select" onchange="this.form.submit()">
            <?php foreach ($reportTypes as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $reportType === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="rp-field">
        <span class="rp-field-label">From</span>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="rp-field">
        <span class="rp-field-label">To</span>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="rp-btn-group">
        <button type="submit" class="rp-btn rp-btn-primary">Apply</button>
        <a class="rp-btn rp-btn-reset" href="/O/reports?report_type=<?= urlencode($reportType) ?>&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">Reset</a>
    </div>
</form>
</div>

<?php if ($reportType === 'attendance'): ?>
<?php /* ═══ REPORT 1 — STAFF ATTENDANCE ══════════════════════════════ */ ?>
<?php
$totalStaff   = (int)($hrSummary['total_staff']              ?? count($hrRows));
$avgAtt       = (float)($hrSummary['avg_att_pct']            ?? 0);
$mostAbsent   = $hrSummary['most_absent_info']               ?? ['name'=>($hrSummary['most_absent']??'—'), 'absent_days'=>0];
$perfectCount = (int)($hrSummary['perfect_attendance_count'] ?? count(array_filter($hrRows, fn($r)=>(float)($r['att_pct']??0)>=100)));
?>
<div class="rp-cards">
    <div class="rp-card" style="--rp-card-accent:#2563eb">
        <div class="rp-card-icon" style="background:#eff6ff;color:#2563eb"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
        <div class="rp-card-lbl">Total Staff</div>
        <div class="rp-card-val"><?= $totalStaff ?></div>
        <div class="rp-card-hint">with records in period</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#16a34a">
        <div class="rp-card-icon" style="background:#f0fdf4;color:#16a34a"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="rp-card-lbl">Avg Attendance</div>
        <div class="rp-card-val"><?= $avgAtt ?>%</div>
        <div class="rp-card-hint">across all staff</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $avgAtt ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#dc2626">
        <div class="rp-card-icon" style="background:#fef2f2;color:#dc2626"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="14"/><line x1="23" y1="8" x2="17" y2="14"/></svg></div>
        <div class="rp-card-lbl">Most Absent</div>
        <div class="rp-card-val" style="font-size:1.05rem"><?= htmlspecialchars((string)($mostAbsent['name']??'—')) ?></div>
        <div class="rp-card-hint"><?= (int)($mostAbsent['absent_days']??0) ?> absent days</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#f59e0b">
        <div class="rp-card-icon" style="background:#fffbeb;color:#f59e0b"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div class="rp-card-lbl">Perfect Attendance</div>
        <div class="rp-card-val"><?= $perfectCount ?></div>
        <div class="rp-card-hint">staff with 100%</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $totalStaff?min(100,round($perfectCount/$totalStaff*100)):0 ?>%"></div></div>
    </div>
</div>

<?php if (!empty($hrRows)): ?>
<div class="rp-chart-card">
    <h3><svg width="15" height="15" fill="none" stroke="#7B1C3E" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg> Attendance % by Staff (top 20)</h3>
    <div class="rp-canvas-wrap rp-canvas-bar"><canvas id="attendanceChart"></canvas></div>
    <p class="rp-chart-note">Charts available in digital version only.</p>
    <div class="rp-chart-legend">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#16a34a"></span>&nbsp;≥ 85%</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#f59e0b"></span>&nbsp;70–84%</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#dc2626"></span>&nbsp;&lt; 70%</span>
    </div>
</div>
<div class="rp-table-card">
    <div class="rp-table-head"><h2>Staff Attendance Details</h2><span class="meta"><?= count($hrRows) ?> staff &bull; <?= date('d M Y',strtotime($from)) ?> – <?= date('d M Y',strtotime($to)) ?></span></div>
    <div class="rp-table-wrap"><table class="rp-table rp-sortable" id="att-table">
        <thead><tr>
            <th>#</th>
            <th class="sortable" data-col="1">Name<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="2">Role<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="3">Present<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="4">Absent<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="5">Leave<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="6">Days<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="7">Att %<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="8">Trend<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="9">Last Absent<span class="sort-ind">⇅</span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($hrRows as $i => $r):
            $pct    = (float)($r['att_pct']??0); $col = pctColor($pct);
            $role   = strtolower((string)($r['role']??''));
            $roleCls = match($role){'driver'=>'badge-driver','conductor'=>'badge-conductor',default=>'badge-staff'};
        ?>
        <tr>
            <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
            <td class="name-cell"><?= htmlspecialchars((string)($r['full_name']??'—')) ?></td>
            <td><?= rpBadge(ucfirst($role?:'Staff'), $roleCls) ?></td>
            <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['present_days']??0) ?></td>
            <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['absent_days']??0) ?></td>
            <td class="num-cell" style="color:#f59e0b;font-weight:700;"><?= (int)($r['leave_days']??0) ?></td>
            <td class="num-cell"><?= (int)($r['total_days']??0) ?></td>
            <td><?= pctBar($pct, $col) ?></td>
            <td><?= trendArrow((string)($r['trend']??'stable')) ?></td>
            <td style="color:#6b7280;font-size:.82rem;"><?= !empty($r['last_absent_date'])?date('d M Y',strtotime((string)$r['last_absent_date'])):'—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php else: ?>
<div class="rp-table-card"><div class="rp-empty"><svg width="44" height="44" fill="none" stroke="#e5e7eb" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><h4>No Data Found</h4><p>No attendance data for the selected period.</p></div></div>
<?php endif; ?>
<?php
$_attChart = json_encode(array_map(fn($r)=>['full_name'=>$r['full_name']??'','att_pct'=>(float)($r['att_pct']??0)], array_slice($hrRows,0,20)), JSON_HEX_TAG|JSON_HEX_AMP|JSON_NUMERIC_CHECK);
$_attCsv   = json_encode(array_map(fn($r)=>['full_name'=>$r['full_name']??'','role'=>$r['role']??'','present_days'=>$r['present_days']??0,'absent_days'=>$r['absent_days']??0,'leave_days'=>$r['leave_days']??0,'total_days'=>$r['total_days']??0,'att_pct'=>$r['att_pct']??0,'trend'=>$r['trend']??''], $hrRows), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$_attSum   = json_encode(['total_staff'=>$totalStaff,'avg_att_pct'=>$avgAtt,'perfect_attendance_count'=>$perfectCount,'most_absent'=>['name'=>$mostAbsent['name']??'—','absent_days'=>$mostAbsent['absent_days']??0]], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    if(window.ReportCharts) ReportCharts.buildAttendanceChart('attendanceChart',<?= $_attChart ?>);
    document.getElementById('rp-csv-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.exportAttendance(<?= $_attCsv ?>,<?= $_attSum ?>,'<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
    document.getElementById('rp-print-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.printReport('Staff Attendance Report','<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
});
</script>

<?php elseif ($reportType === 'driver_performance'): ?>
<?php /* ═══ REPORT 2 — DRIVER PERFORMANCE ══════════════════════════ */ ?>
<?php
$avgOnTime = (float)($hrSummary['on_time_pct'] ?? 0);
$avgScore  = count($hrRows) ? round(array_sum(array_column($hrRows,'performance_score'))/count($hrRows),1) : 0;
$topRow    = !empty($hrRows) ? array_reduce($hrRows,fn($c,$r)=>((float)($r['performance_score']??0)>(float)($c['performance_score']??0))?$r:$c,$hrRows[0]) : null;
?>
<div class="rp-cards">
    <div class="rp-card" style="--rp-card-accent:#2563eb">
        <div class="rp-card-icon" style="background:#eff6ff;color:#2563eb"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="rp-card-lbl">Total Drivers</div>
        <div class="rp-card-val"><?= count($hrRows) ?></div>
        <div class="rp-card-hint">with trips in period</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#16a34a">
        <div class="rp-card-icon" style="background:#f0fdf4;color:#16a34a"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="rp-card-lbl">Avg On-Time %</div>
        <div class="rp-card-val"><?= $avgOnTime ?>%</div>
        <div class="rp-card-hint">across all drivers</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $avgOnTime ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#f59e0b">
        <div class="rp-card-icon" style="background:#fffbeb;color:#f59e0b"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div class="rp-card-lbl">Avg Score /100</div>
        <div class="rp-card-val"><?= $avgScore ?></div>
        <div class="rp-card-hint">performance index</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $avgScore ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#7B1C3E">
        <div class="rp-card-icon" style="background:#fce8ef;color:#7B1C3E"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></div>
        <div class="rp-card-lbl">Top Performer</div>
        <div class="rp-card-val" style="font-size:1.05rem"><?= htmlspecialchars((string)($topRow['driver_name']??'—')) ?></div>
        <div class="rp-card-hint">Score: <?= $topRow['performance_score']??0 ?> &nbsp; <?= rpBadge((string)($topRow['grade']??'—'), gradeClass((string)($topRow['grade']??''))) ?></div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= min(100,(float)($topRow['performance_score']??0)) ?>%"></div></div>
    </div>
</div>

<?php if (!empty($hrRows)): ?>
<div class="rp-chart-card">
    <h3><svg width="15" height="15" fill="none" stroke="#7B1C3E" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Driver Performance Scores (reference at 70)</h3>
    <div class="rp-canvas-wrap rp-canvas-hbar"><canvas id="driverPerfChart"></canvas></div>
    <p class="rp-chart-note">Charts available in digital version only.</p>
    <div class="rp-chart-legend">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#16a34a"></span>&nbsp;A ≥85</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#f59e0b"></span>&nbsp;B 70–84</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#ea580c"></span>&nbsp;C 55–69</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#dc2626"></span>&nbsp;D &lt;55</span>
    </div>
</div>
<div class="rp-table-card">
    <div class="rp-table-head"><h2>Driver Performance Details</h2><span class="meta"><?= count($hrRows) ?> driver<?= count($hrRows)!==1?'s':'' ?> &bull; <?= date('d M Y',strtotime($from)) ?> – <?= date('d M Y',strtotime($to)) ?></span></div>
    <div class="rp-table-wrap"><table class="rp-table rp-sortable" id="drv-table">
        <thead><tr>
            <th>#</th>
            <th class="sortable" data-col="1">Driver<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="2">Trips<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="3">Done<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="4">Delayed<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="5">Cancelled<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="6">On-Time %<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="7">Avg Delay<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="8">Score<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="9">Grade<span class="sort-ind">⇅</span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($hrRows as $i => $r):
            $otp   = (float)($r['on_time_pct']??0); $otCol = pctColor($otp);
            $score = (float)($r['performance_score']??0);
            $grade = (string)($r['grade']??'—');
            $sCls  = 'score-'.strtolower(in_array($grade,['A','B','C','D'])?$grade:'staff');
        ?>
        <tr>
            <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
            <td class="name-cell"><?= htmlspecialchars((string)($r['driver_name']??'—')) ?></td>
            <td class="num-cell" style="font-weight:700;"><?= (int)($r['trips_assigned']??0) ?></td>
            <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed']??0) ?></td>
            <td class="num-cell" style="color:#f59e0b;font-weight:700;"><?= (int)($r['delayed']??0) ?></td>
            <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled']??0) ?></td>
            <td><?= pctBar($otp, $otCol) ?></td>
            <td class="num-cell" style="color:#f59e0b;"><?= number_format((float)($r['avg_delay_min']??0),1) ?> min</td>
            <td><span class="rp-score-badge <?= $sCls ?>"><?= $score ?></span></td>
            <td><?= rpBadge($grade, gradeClass($grade)) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php else: ?>
<div class="rp-table-card"><div class="rp-empty"><svg width="44" height="44" fill="none" stroke="#e5e7eb" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><h4>No Data Found</h4><p>No driver trip data found for the selected period.</p></div></div>
<?php endif; ?>
<?php $_drvChart = json_encode(array_slice(array_map(fn($r)=>['driver_name'=>$r['driver_name']??'','performance_score'=>(float)($r['performance_score']??0),'grade'=>$r['grade']??''], $hrRows),0,20), JSON_HEX_TAG|JSON_HEX_AMP|JSON_NUMERIC_CHECK); ?>
<?php $_drvCsv = json_encode(array_map(fn($r)=>['driver_name'=>$r['driver_name']??'','trips_assigned'=>$r['trips_assigned']??0,'completed'=>$r['completed']??0,'delayed'=>$r['delayed']??0,'cancelled'=>$r['cancelled']??0,'on_time_pct'=>$r['on_time_pct']??0,'avg_delay_min'=>$r['avg_delay_min']??0,'performance_score'=>$r['performance_score']??0,'grade'=>$r['grade']??''], $hrRows), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    if(window.ReportCharts) ReportCharts.buildPerformanceChart('driverPerfChart',<?= $_drvChart ?>);
    var sum={total_drivers:<?= count($hrRows) ?>,avg_on_time_pct:<?= $avgOnTime ?>,avg_performance_score:<?= $avgScore ?>,top_driver:{name:'<?= addslashes((string)($topRow['driver_name']??'')) ?>',score:<?= (float)($topRow['performance_score']??0) ?>}};
    document.getElementById('rp-csv-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.exportDriverPerformance(<?= $_drvCsv ?>,sum,'<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
    document.getElementById('rp-print-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.printReport('Driver Performance Report','<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
});
</script>

<?php elseif ($reportType === 'trip_completion'): ?>
<?php /* ═══ REPORT 3 — TRIP COMPLETION RATE ══════════════════════════ */ ?>
<?php
$dailyRows = $hrRows;
$byRoute   = $hrSummary['by_route'] ?? [];
$byBus     = $hrSummary['by_bus']   ?? [];
$totSched   = (int)array_sum(array_column($dailyRows,'total_trips'));
$totDone    = (int)array_sum(array_column($dailyRows,'completed'));
$totCxl     = (int)array_sum(array_column($dailyRows,'cancelled'));
$overallCR  = $totSched>0 ? round($totDone/$totSched*100,1) : 0;
$bestRow    = !empty($dailyRows)?array_reduce($dailyRows,fn($c,$r)=>((float)($r['completion_pct']??0)>(float)($c['completion_pct']??0))?$r:$c,$dailyRows[0]):null;
?>
<div class="rp-cards">
    <div class="rp-card" style="--rp-card-accent:#16a34a">
        <div class="rp-card-icon" style="background:#f0fdf4;color:#16a34a"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="rp-card-lbl">Overall Completion</div>
        <div class="rp-card-val"><?= $overallCR ?>%</div>
        <div class="rp-card-hint">completed / scheduled</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $overallCR ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#2563eb">
        <div class="rp-card-icon" style="background:#eff6ff;color:#2563eb"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></div>
        <div class="rp-card-lbl">Total Scheduled</div>
        <div class="rp-card-val"><?= $totSched ?></div>
        <div class="rp-card-hint">in selected period</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#dc2626">
        <div class="rp-card-icon" style="background:#fef2f2;color:#dc2626"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <div class="rp-card-lbl">Cancelled</div>
        <div class="rp-card-val"><?= $totCxl ?></div>
        <div class="rp-card-hint"><?= $totSched>0?round($totCxl/$totSched*100,1):0 ?>% of total</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $totSched>0?min(100,round($totCxl/$totSched*100)):0 ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#f59e0b">
        <div class="rp-card-icon" style="background:#fffbeb;color:#f59e0b"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div class="rp-card-lbl">Best Day</div>
        <div class="rp-card-val" style="font-size:1.1rem"><?= $bestRow?date('d M',strtotime((string)($bestRow['trip_date']??$from))):'—' ?></div>
        <div class="rp-card-hint"><?= $bestRow?($bestRow['completion_pct']??0).'% completion':'—' ?></div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $bestRow?min(100,(float)($bestRow['completion_pct']??0)):0 ?>%"></div></div>
    </div>
</div>

<div style="background:#fff;border-radius:12px;box-shadow:0 4px 18px rgba(17,24,39,.08);overflow:hidden;">
<div class="rp-tabs">
    <button class="rp-tab active" data-tab="tc-daily">Daily Breakdown</button>
    <?php if (!empty($byRoute)): ?><button class="rp-tab" data-tab="tc-route">By Route</button><?php endif; ?>
    <?php if (!empty($byBus)):   ?><button class="rp-tab" data-tab="tc-bus">By Bus</button><?php endif; ?>
</div>
<div id="tc-daily" class="rp-tab-panel" style="display:block;">
<?php if (!empty($dailyRows)): ?>
<div style="padding:16px 20px 0;">
    <div class="rp-bar-list">
    <?php foreach ($dailyRows as $r):
        $tot=max(1,(int)($r['total_trips']??0));$c=(int)($r['completed']??0);$d=(int)($r['delayed']??0);$x=(int)($r['cancelled']??0);
        $cP=round($c/$tot*100);$dP=round($d/$tot*100);$xP=round($x/$tot*100);
    ?>
    <div class="rp-bar-row">
        <div class="rp-bar-name"><?= date('d M',strtotime((string)$r['trip_date'])) ?></div>
        <div class="rp-bar-stacked">
            <?php if($cP):?><div class="rp-bar-seg" style="width:<?=$cP?>%;background:#16a34a;" title="Completed:<?=$c?>"></div><?php endif;?>
            <?php if($dP):?><div class="rp-bar-seg" style="width:<?=$dP?>%;background:#f59e0b;" title="Delayed:<?=$d?>"></div><?php endif;?>
            <?php if($xP):?><div class="rp-bar-seg" style="width:<?=$xP?>%;background:#dc2626;" title="Cancelled:<?=$x?>"></div><?php endif;?>
            <?php $rem=max(0,100-$cP-$dP-$xP);if($rem):?><div class="rp-bar-seg" style="width:<?=$rem?>%;background:#d1d5db;"></div><?php endif;?>
        </div>
        <div class="rp-bar-val"><?= (float)($r['completion_pct']??0) ?>%</div>
    </div>
    <?php endforeach; ?>
    </div>
    <div class="rp-chart-legend" style="margin-top:8px;padding-top:8px;">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#16a34a"></span>&nbsp;Completed</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#f59e0b"></span>&nbsp;Delayed</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#dc2626"></span>&nbsp;Cancelled</span>
    </div>
</div>
<div style="overflow-x:auto;"><table class="rp-table rp-sortable" id="tc-daily-table">
    <thead><tr>
        <th class="sortable" data-col="0">Date<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="1">Total<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="2">Completed<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="3">Delayed<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="4">Cancelled<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="5">In Progress<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="6">Completion %<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($dailyRows as $r): $pct=(float)($r['completion_pct']??0);$col=pctColor($pct,90,70); ?>
    <tr>
        <td class="name-cell"><?= date('d M Y',strtotime((string)$r['trip_date'])) ?></td>
        <td class="num-cell" style="font-weight:700;"><?= (int)($r['total_trips']??0) ?></td>
        <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed']??0) ?></td>
        <td class="num-cell" style="color:#f59e0b;font-weight:700;"><?= (int)($r['delayed']??0) ?></td>
        <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled']??0) ?></td>
        <td class="num-cell" style="color:#6b7280;"><?= (int)($r['in_progress']??0) ?></td>
        <td><?= pctBar($pct,$col) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php else: ?>
<div class="rp-empty"><h4>No Data Found</h4><p>No trip data for the selected period.</p></div>
<?php endif; ?>
</div>
<?php if (!empty($byRoute)): ?>
<div id="tc-route" class="rp-tab-panel" style="display:none;overflow-x:auto;">
<table class="rp-table rp-sortable" id="tc-route-table">
    <thead><tr>
        <th class="sortable" data-col="0">Route No<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="1">Route Name<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="2">Scheduled<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="3">Completed<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="4">Delayed<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="5">Cancelled<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="6">Completion %<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($byRoute as $r): $cp=(float)($r['completion_rate']??0);$cCol=pctColor($cp); ?>
    <tr>
        <td class="name-cell"><?= htmlspecialchars((string)($r['route_no']??'—')) ?></td>
        <td><?= htmlspecialchars((string)($r['route_name']??'—')) ?></td>
        <td class="num-cell font-bold"><?= (int)($r['total_scheduled']??0) ?></td>
        <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed']??0) ?></td>
        <td class="num-cell" style="color:#f59e0b;font-weight:700;"><?= (int)($r['delayed']??0) ?></td>
        <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled']??0) ?></td>
        <td><?= pctBar($cp,$cCol) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php if (!empty($byBus)): ?>
<div id="tc-bus" class="rp-tab-panel" style="display:none;overflow-x:auto;">
<table class="rp-table rp-sortable" id="tc-bus-table">
    <thead><tr>
        <th class="sortable" data-col="0">Bus No<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="1">Scheduled<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="2">Completed<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="3">Cancelled<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="4">Completion %<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="5">Utilization %<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($byBus as $r): $cp=(float)($r['completion_rate']??0);$up=(float)($r['utilization_pct']??0); ?>
    <tr>
        <td class="name-cell"><?= htmlspecialchars((string)($r['bus_reg_no']??'—')) ?></td>
        <td class="num-cell"><?= (int)($r['total_scheduled']??0) ?></td>
        <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed']??0) ?></td>
        <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled']??0) ?></td>
        <td><?= pctBar($cp,pctColor($cp)) ?></td>
        <td><?= pctBar($up,pctColor($up,80,60)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div><!-- tabbed card -->
<script>
document.addEventListener('DOMContentLoaded',function(){
    var sum={overall_completion_rate:<?= $overallCR ?>,total_scheduled:<?= $totSched ?>,total_completed:<?= $totDone ?>,total_cancelled:<?= $totCxl ?>};
    var byRoute=<?= json_encode($byRoute,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    var byBus=<?= json_encode($byBus,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    document.getElementById('rp-csv-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.exportTripCompletion(byRoute,byBus,sum,'<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
    document.getElementById('rp-print-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.printReport('Trip Completion Rate','<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
});
</script>

<?php elseif ($reportType === 'delay_analysis'): ?>
<?php /* ═══ REPORT 4 — DELAY ANALYSIS ══════════════════════════════ */ ?>
<?php
$byRoute  = $hrSummary['by_route']  ?? $hrRows;
$bySlot   = $hrSummary['by_slot']   ?? [];
$byReason = $hrSummary['by_reason'] ?? [];
$totDel   = (int)array_sum(array_column($byRoute,'delayed_trips'));
$totAll   = max(1,(int)array_sum(array_column($byRoute,'total_trips')));
$dRate    = round($totDel/$totAll*100,1);
$avgDel   = count($byRoute)?round(array_sum(array_column($byRoute,'avg_delay_min'))/count($byRoute),1):0;
$wSlot    = !empty($bySlot)?array_reduce($bySlot,fn($c,$r)=>((float)($r['delay_rate']??0)>(float)($c['delay_rate']??0))?$r:$c,$bySlot[0]):null;
?>
<div class="rp-cards">
    <div class="rp-card" style="--rp-card-accent:#ea580c">
        <div class="rp-card-icon" style="background:#fff7ed;color:#ea580c"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
        <div class="rp-card-lbl">Total Delayed</div>
        <div class="rp-card-val"><?= $totDel ?></div>
        <div class="rp-card-hint">in selected period</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:100%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#dc2626">
        <div class="rp-card-icon" style="background:#fef2f2;color:#dc2626"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        <div class="rp-card-lbl">Delay Rate</div>
        <div class="rp-card-val"><?= $dRate ?>%</div>
        <div class="rp-card-hint">delayed / total trips</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $dRate ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#f59e0b">
        <div class="rp-card-icon" style="background:#fffbeb;color:#f59e0b"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="rp-card-lbl">Avg Delay</div>
        <div class="rp-card-val"><?= $avgDel ?><span style="font-size:.5em;margin-left:3px;">min</span></div>
        <div class="rp-card-hint">across all routes</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= min(100,$avgDel) ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#7c3aed">
        <div class="rp-card-icon" style="background:#f5f3ff;color:#7c3aed"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="rp-card-lbl">Worst Time Slot</div>
        <div class="rp-card-val" style="font-size:1.05rem"><?= $wSlot?htmlspecialchars((string)$wSlot['label']):'—' ?></div>
        <div class="rp-card-hint"><?= $wSlot?($wSlot['delay_rate']??0).'% delay rate':'No slot data' ?></div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $wSlot?min(100,(float)($wSlot['delay_rate']??0)):0 ?>%"></div></div>
    </div>
</div>

<div style="background:#fff;border-radius:12px;box-shadow:0 4px 18px rgba(17,24,39,.08);overflow:hidden;">
<div class="rp-tabs">
    <button class="rp-tab active" data-tab="da-route">By Route</button>
    <button class="rp-tab" data-tab="da-slot">By Time Slot</button>
    <button class="rp-tab" data-tab="da-reason">By Reason</button>
</div>
<div id="da-route" class="rp-tab-panel" style="display:block;">
<?php if (!empty($byRoute)): ?>
<div style="padding:16px 20px 0;">
    <div class="rp-bar-list">
    <?php $maxD=max(1,max(array_column($byRoute,'delayed_trips')??[0]));
    foreach (array_slice($byRoute,0,12) as $r): $del=(int)($r['delayed_trips']??0);$bp=round($del/$maxD*100);$dp=(float)($r['delay_pct']??0);$c=$dp>=50?'#dc2626':($dp>=25?'#f59e0b':'#16a34a'); ?>
    <div class="rp-bar-row">
        <div class="rp-bar-name" title="<?= htmlspecialchars((string)($r['route_name']??'')) ?>"><?= htmlspecialchars((string)($r['route_no']??'—')) ?></div>
        <div class="rp-bar-track"><div class="rp-bar-fill" style="width:0%;background:<?= $c ?>;" data-target="<?= $bp ?>"><?php if($bp>=15):?><span><?= $del ?></span><?php endif;?></div></div>
        <div class="rp-bar-val"><?= $dp ?>%</div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<div style="overflow-x:auto;"><table class="rp-table rp-sortable" id="da-route-table">
    <thead><tr>
        <th>#</th>
        <th class="sortable" data-col="1">Route<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="2">Name<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="3">Total<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="4">Delayed<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="5">Delay Rate<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="6">Avg Delay<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="7">Max Delay<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($byRoute as $i=>$r): $dp=(float)($r['delay_pct']??0);$c=$dp>=50?'#dc2626':($dp>=25?'#f59e0b':'#16a34a'); ?>
    <tr>
        <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
        <td class="name-cell"><?= htmlspecialchars((string)($r['route_no']??'—')) ?></td>
        <td style="color:#4b5563;"><?= htmlspecialchars((string)($r['route_name']??'—')) ?></td>
        <td class="num-cell"><?= (int)($r['total_trips']??0) ?></td>
        <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['delayed_trips']??0) ?></td>
        <td><?= pctBar($dp,$c) ?></td>
        <td class="num-cell" style="color:#f59e0b;"><?= number_format((float)($r['avg_delay_min']??0),1) ?> min</td>
        <td class="num-cell" style="color:#dc2626;"><?= number_format((float)($r['max_delay_min']??0),1) ?> min</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php else: ?>
<div class="rp-empty"><h4>No Delay Data</h4><p>No delay records for the selected period.</p></div>
<?php endif; ?>
</div>
<div id="da-slot" class="rp-tab-panel" style="display:none;">
<?php if (!empty($bySlot)): ?>
<div style="padding:16px 20px 0;">
    <div class="rp-canvas-wrap rp-canvas-bar" style="max-width:700px;"><canvas id="delaySlotChart"></canvas></div>
    <p class="rp-chart-note">Charts available in digital version only.</p>
</div>
<div style="overflow-x:auto;"><table class="rp-table rp-sortable" id="da-slot-table">
    <thead><tr>
        <th class="sortable" data-col="0">Time Slot<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="1">Total<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="2">Delayed<span class="sort-ind">⇅</span></th>
        <th class="sortable" data-col="3">Delay Rate<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="4">Avg Delay<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($bySlot as $r): $dr=(float)($r['delay_rate']??0);$c=$dr>=60?'#dc2626':($dr>=35?'#ea580c':($dr>=20?'#f59e0b':'#16a34a')); ?>
    <tr>
        <td><?= slotBadge((string)($r['slot']??'')) ?></td>
        <td class="num-cell"><?= (int)($r['total_trips']??0) ?></td>
        <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['delayed_trips']??0) ?></td>
        <td><?= pctBar($dr,$c) ?></td>
        <td class="num-cell" style="color:#f59e0b;"><?= number_format((float)($r['avg_delay_min']??0),1) ?> min</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php else: ?><div class="rp-empty"><h4>No Time Slot Data</h4></div><?php endif; ?>
</div>
<div id="da-reason" class="rp-tab-panel" style="display:none;">
<?php if (!empty($byReason)): ?>
<div style="padding:16px 20px 0;max-width:320px;margin:0 auto;">
    <div class="rp-canvas-wrap rp-canvas-donut"><canvas id="delayReasonChart"></canvas></div>
    <p class="rp-chart-note">Charts available in digital version only.</p>
</div>
<div style="overflow-x:auto;"><table class="rp-table rp-sortable" id="da-reason-table">
    <thead><tr>
        <th class="sortable" data-col="0">Reason<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="1">Count<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="2">% of Delays<span class="sort-ind">⇅</span></th>
        <th class="sortable num-cell" data-col="3">Avg Delay<span class="sort-ind">⇅</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($byReason as $r): ?>
    <tr>
        <td class="name-cell"><?= htmlspecialchars(ucwords(str_replace(['-','_'],' ',(string)($r['reason']??'')))) ?></td>
        <td class="num-cell" style="font-weight:700;"><?= (int)($r['count']??0) ?></td>
        <td class="num-cell" style="color:#7B1C3E;font-weight:700;"><?= number_format((float)($r['percentage']??0),1) ?>%</td>
        <td class="num-cell" style="color:#f59e0b;"><?= number_format((float)($r['avg_delay_min']??0),1) ?> min</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php else: ?><div class="rp-empty"><h4>No Reason Data</h4></div><?php endif; ?>
</div>
</div><!-- tabbed -->
<?php
$_slotJs   = json_encode($bySlot,   JSON_HEX_TAG|JSON_HEX_AMP|JSON_NUMERIC_CHECK);
$_reasonJs = json_encode($byReason, JSON_HEX_TAG|JSON_HEX_AMP|JSON_NUMERIC_CHECK);
$_routeJs  = json_encode($byRoute,  JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>
<script>
window._rp_slotData=<?= $_slotJs ?>;window._rp_reasonData=<?= $_reasonJs ?>;window._rp_avgDelay=<?= $dRate ?>;
document.addEventListener('DOMContentLoaded',function(){
    var sum={total_delayed:<?= $totDel ?>,overall_delay_rate:<?= $dRate ?>,avg_delay_min:<?= $avgDel ?>};
    document.getElementById('rp-csv-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.exportDelayAnalysis(<?= $_routeJs ?>,window._rp_slotData,window._rp_reasonData,sum,'<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
    document.getElementById('rp-print-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.printReport('Delay Analysis Report','<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
});
</script>

<?php elseif ($reportType === 'bus_utilization'): ?>
<?php /* ═══ REPORT 5 — BUS UTILIZATION ══════════════════════════════ */ ?>
<?php
$totBuses  = (int)($hrSummary['total_buses']            ?? count($hrRows));
$actBuses  = (int)($hrSummary['active_buses']           ?? count(array_filter($hrRows,fn($r)=>(int)($r['total_trips']??0)>0)));
$avgUtil   = (float)($hrSummary['avg_utilization']      ?? (count($hrRows)?round(array_sum(array_column($hrRows,'utilization_pct'))/count($hrRows),1):0));
$overdue   = (int)($hrSummary['buses_overdue']          ?? 0);
$dueSoon   = (int)($hrSummary['buses_service_due_soon'] ?? 0);
?>
<?php if ($overdue > 0): ?>
<div class="rp-alert-banner" id="rp-maint-alert">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
    <span><strong><?= $overdue ?> bus<?= $overdue!==1?'es':'' ?></strong> have overdue maintenance. Immediate action required.</span>
    <button class="rp-alert-close" onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>
<div class="rp-cards">
    <div class="rp-card" style="--rp-card-accent:#16a34a">
        <div class="rp-card-icon" style="background:#f0fdf4;color:#16a34a"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="rp-card-lbl">Fleet Utilization</div>
        <div class="rp-card-val"><?= $avgUtil ?>%</div>
        <div class="rp-card-hint">avg across all buses</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $avgUtil ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#2563eb">
        <div class="rp-card-icon" style="background:#eff6ff;color:#2563eb"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3"/><rect x="9" y="11" width="14" height="10" rx="1"/></svg></div>
        <div class="rp-card-lbl">Active Buses</div>
        <div class="rp-card-val"><?= $actBuses ?><span style="font-size:.5em;color:#9ca3af;margin-left:4px;">/ <?= $totBuses ?></span></div>
        <div class="rp-card-hint">with trips in period</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $totBuses>0?round($actBuses/$totBuses*100):0 ?>%"></div></div>
    </div>
    <div class="rp-card" style="--rp-card-accent:#f59e0b">
        <div class="rp-card-icon" style="background:#fffbeb;color:#f59e0b"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg></div>
        <div class="rp-card-lbl">Service Due Soon</div>
        <div class="rp-card-val"><?= $dueSoon ?></div>
        <div class="rp-card-hint">check maintenance schedule</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $totBuses>0?min(100,round($dueSoon/$totBuses*100)):0 ?>%"></div></div>
    </div>
    <div class="rp-card <?= $overdue>0?'rp-card-urgent':'' ?>" style="--rp-card-accent:#dc2626">
        <div class="rp-card-icon" style="background:#fef2f2;color:#dc2626"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
        <div class="rp-card-lbl">Overdue Service</div>
        <div class="rp-card-val"><?= $overdue ?></div>
        <div class="rp-card-hint">buses past service date</div>
        <div class="rp-card-bar"><div class="rp-card-bar-fill" style="width:<?= $totBuses>0?min(100,round($overdue/$totBuses*100)):0 ?>%"></div></div>
    </div>
</div>

<?php if (!empty($hrRows)): ?>
<div class="rp-chart-card">
    <h3><svg width="15" height="15" fill="none" stroke="#7B1C3E" stroke-width="2" viewBox="0 0 24 24"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3"/><rect x="9" y="11" width="14" height="10" rx="1"/></svg> Bus Utilization Rates</h3>
    <div class="rp-canvas-wrap rp-canvas-hbar"><canvas id="busUtilChart"></canvas></div>
    <p class="rp-chart-note">Charts available in digital version only.</p>
    <div class="rp-chart-legend">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#16a34a"></span>&nbsp;≥ 80%</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#f59e0b"></span>&nbsp;60–79%</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#dc2626"></span>&nbsp;&lt; 60%</span>
    </div>
</div>
<div class="rp-table-card">
    <div class="rp-table-head"><h2>Bus Utilization Details</h2><span class="meta"><?= count($hrRows) ?> bus<?= count($hrRows)!==1?'es':'' ?> &bull; <?= date('d M Y',strtotime($from)) ?> – <?= date('d M Y',strtotime($to)) ?></span></div>
    <div class="rp-table-wrap"><table class="rp-table rp-sortable" id="util-table">
        <thead><tr>
            <th>#</th>
            <th class="sortable" data-col="1">Bus No<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="2">Make<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="3">Total<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="4">Completed<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="5">Delayed<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="6">Cancelled<span class="sort-ind">⇅</span></th>
            <th class="sortable num-cell" data-col="7">Active Days<span class="sort-ind">⇅</span></th>
            <th class="sortable" data-col="8">Utilization<span class="sort-ind">⇅</span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($hrRows as $i=>$r): $u=(float)($r['utilization_pct']??0);$uC=pctColor($u,80,60); ?>
        <tr>
            <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
            <td class="name-cell"><?= htmlspecialchars((string)($r['bus_reg_no']??'—')) ?></td>
            <td style="color:#6b7280;font-size:.82rem;"><?= htmlspecialchars((string)($r['bus_make']??'—')) ?></td>
            <td class="num-cell" style="font-weight:700;"><?= (int)($r['total_trips']??0) ?></td>
            <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed']??0) ?></td>
            <td class="num-cell" style="color:#f59e0b;font-weight:700;"><?= (int)($r['delayed']??0) ?></td>
            <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled']??0) ?></td>
            <td class="num-cell" style="color:#2563eb;font-weight:700;"><?= (int)($r['active_days']??0) ?></td>
            <td><?= pctBar($u,$uC) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php else: ?>
<div class="rp-table-card"><div class="rp-empty"><h4>No Bus Data</h4><p>No bus data found for the selected period.</p></div></div>
<?php endif; ?>
<?php $_utilChart=json_encode(array_map(fn($r)=>['bus_reg_no'=>$r['bus_reg_no']??'','utilization_pct'=>(float)($r['utilization_pct']??0)],$hrRows),JSON_HEX_TAG|JSON_HEX_AMP|JSON_NUMERIC_CHECK);
$_utilCsv=json_encode(array_map(fn($r)=>['bus_reg_no'=>$r['bus_reg_no']??'','bus_make'=>$r['bus_make']??'','total_trips'=>$r['total_trips']??0,'completed'=>$r['completed']??0,'delayed'=>$r['delayed']??0,'cancelled'=>$r['cancelled']??0,'active_days'=>$r['active_days']??0,'assignments_count'=>$r['assignments_count']??0,'utilization_pct'=>$r['utilization_pct']??0],$hrRows),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$_utilSum=json_encode(['total_buses'=>$totBuses,'active_buses'=>$actBuses,'avg_utilization_rate'=>$avgUtil,'buses_service_due_soon'=>$dueSoon,'buses_overdue'=>$overdue],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    if(window.ReportCharts) ReportCharts.buildUtilizationChart('busUtilChart',<?= $_utilChart ?>);
    document.getElementById('rp-csv-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.exportBusUtilization(<?= $_utilCsv ?>,<?= $_utilSum ?>,'<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
    document.getElementById('rp-print-btn').addEventListener('click',function(){
        if(window.ReportExport) ReportExport.printReport('Bus Utilization Report','<?= addslashes($depotName) ?>','<?= $from ?>','<?= $to ?>');
    });
});
</script>

<?php endif; /* end report type switch */ ?>

</div><!-- /.rp-page -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="/assets/js/reports/reportCharts.js"></script>
<script src="/assets/js/reports/reportExport.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* Animate CSS bar fills */
    document.querySelectorAll('.rp-bar-fill[data-target]').forEach(function (bar) {
        var t = parseFloat(bar.dataset.target) || 0;
        bar.style.width = '0%';
        setTimeout(function () { bar.style.width = t + '%'; }, 150);
    });

    /* Tab switching */
    document.querySelectorAll('.rp-tabs').forEach(function (tabBar) {
        tabBar.addEventListener('click', function (e) {
            var btn = e.target.closest('.rp-tab');
            if (!btn) return;
            var target = btn.dataset.tab;
            tabBar.querySelectorAll('.rp-tab').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var container = tabBar.parentElement;
            container.querySelectorAll('.rp-tab-panel').forEach(function (p) {
                p.style.display = (p.id === target) ? 'block' : 'none';
            });
            /* Lazy-init charts in delay analysis tabs */
            if (target === 'da-slot' && window.ReportCharts && !window._slotChartDone) {
                window._slotChartDone = true;
                if ((window._rp_slotData||[]).length) ReportCharts.buildDelaySlotChart('delaySlotChart', window._rp_slotData, window._rp_avgDelay||0);
            }
            if (target === 'da-reason' && window.ReportCharts && !window._reasonChartDone) {
                window._reasonChartDone = true;
                if ((window._rp_reasonData||[]).length) ReportCharts.buildDelayReasonChart('delayReasonChart', window._rp_reasonData);
            }
        });
    });

    /* Sortable tables */
    document.querySelectorAll('.rp-sortable').forEach(function (table) {
        var sortDir = {};
        table.querySelectorAll('thead th.sortable').forEach(function (th) {
            th.style.cursor = 'pointer';
            th.addEventListener('click', function () {
                var col = parseInt(th.dataset.col);
                var asc = !sortDir[col];
                sortDir = {};
                sortDir[col] = asc;
                table.querySelectorAll('thead th').forEach(function (h) {
                    h.classList.remove('sort-asc','sort-desc');
                    var ind = h.querySelector('.sort-ind');
                    if (ind) ind.textContent = '⇅';
                });
                th.classList.add(asc ? 'sort-asc' : 'sort-desc');
                var ind = th.querySelector('.sort-ind');
                if (ind) ind.textContent = asc ? '▲' : '▼';
                var tbody = table.querySelector('tbody');
                var rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort(function (a, b) {
                    var va = (a.cells[col] ? a.cells[col].textContent.replace(/[^0-9.\-]/g,'') : '');
                    var vb = (b.cells[col] ? b.cells[col].textContent.replace(/[^0-9.\-]/g,'') : '');
                    var na = parseFloat(va), nb = parseFloat(vb);
                    if (!isNaN(na) && !isNaN(nb)) return asc ? na - nb : nb - na;
                    return asc ? va.localeCompare(vb) : vb.localeCompare(va);
                });
                rows.forEach(function (r) { tbody.appendChild(r); });
            });
        });
    });
});
</script>
