<?php
/* vars: report_type, hr_rows, hr_summary, kpis, analyticsJson,
         from, to, routes, buses, filters */
$reportType  = $report_type ?? 'attendance';
$hrRows      = $hr_rows     ?? [];
$hrSummary   = $hr_summary  ?? [];
$from        = $from        ?? date('Y-m-d', strtotime('-30 days'));
$to          = $to          ?? date('Y-m-d');
$filters     = $filters     ?? [];
$kpis        = $kpis        ?? [];

$reportTypes = [
    'attendance'        => 'Staff Attendance Report',
    'driver_performance'=> 'Driver Performance Report',
    'trip_completion'   => 'Trip Completion Rate',
    'delay_analysis'    => 'Delay Analysis Report',
    'bus_utilization'   => 'Bus Utilization Report',
];
$isHr  = in_array($reportType, ['attendance', 'driver_performance'], true);
$isOps = !$isHr;

$baseQs = http_build_query([
    'report_type' => $reportType,
    'from'        => $from,
    'to'          => $to,
    'route'       => $filters['route']  ?? '',
    'bus_id'      => $filters['bus_id'] ?? '',
    'status'      => $filters['status'] ?? '',
]);
?>
<style>
/* ── Reports Page ──────────────────────────────── */
.rp-page { display: grid; gap: 20px; }

/* Hero */
.rp-hero {
    background: linear-gradient(135deg, #7B1C3E 0%, #a8274e 100%);
    border-bottom: 4px solid #f3b944;
    border-radius: 14px; color: #fff;
    padding: 24px 28px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.rp-hero h1 { margin: 0; font-size: 1.45rem; font-weight: 800; }
.rp-hero p  { margin: 4px 0 0; opacity: .8; font-size: .88rem; }
.rp-hero-right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

/* Filter bar */
.rp-filter-bar {
    background: #fff; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07);
    border-left: 4px solid #f3b944;
    padding: 14px 20px;
}
.rp-filter-bar form {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
}
.rp-field { display: grid; gap: 4px; }
.rp-field-label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #7B1C3E; }
.rp-field select,
.rp-field input[type=date] {
    border: 1.5px solid #e8d39a; border-radius: 8px;
    padding: 8px 10px; font-size: .84rem; background: #fffdf6; color: #2b2b2b;
    transition: border-color .18s, box-shadow .18s;
}
.rp-field select:focus, .rp-field input:focus {
    outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.18);
}
.rp-field select.type-select {
    min-width: 240px; font-weight: 700; color: #7B1C3E;
    border-color: #f3b944; background: #fffdf6;
}
.rp-btn-group { display: flex; gap: 8px; align-items: flex-end; }
.rp-btn {
    padding: 9px 18px; border-radius: 8px;
    font-size: .85rem; font-weight: 700; cursor: pointer; border: none;
    white-space: nowrap; transition: background .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.rp-btn-primary { background: #7B1C3E; color: #fff; }
.rp-btn-primary:hover { background: #a8274e; }
.rp-btn-outline {
    background: #fff; color: #7B1C3E;
    border: 2px solid #7B1C3E; text-decoration: none;
}
.rp-btn-outline:hover { background: #fce8ef; }
.rp-btn-pdf {
    background: #1d4ed8; color: #fff; text-decoration: none;
}
.rp-btn-pdf:hover { background: #1e40af; }

/* Summary cards */
.rp-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.rp-card {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(17,24,39,.07);
    border-top: 4px solid var(--accent);
    padding: 18px 20px;
    display: grid; gap: 4px;
    opacity: 0; animation: rpCardIn .4s cubic-bezier(.22,.68,0,1.2) forwards;
    transition: transform .15s, box-shadow .15s;
}
.rp-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(17,24,39,.1); }
.rp-card:nth-child(1){animation-delay:.05s}
.rp-card:nth-child(2){animation-delay:.12s}
.rp-card:nth-child(3){animation-delay:.19s}
@keyframes rpCardIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.rp-card-val  { font-size: 1.9rem; font-weight: 800; color: var(--accent); line-height: 1.1; }
.rp-card-lbl  { font-size: .78rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
.rp-card-hint { font-size: .74rem; color: #9ca3af; margin-top: 2px; }

/* Table card */
.rp-table-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 8px 24px rgba(17,24,39,.08); overflow: hidden;
}
.rp-table-head {
    background: linear-gradient(90deg, #7B1C3E, #a8274e);
    border-bottom: 3px solid #f3b944;
    color: #fff; padding: 13px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.rp-table-head h2 { margin: 0; font-size: .98rem; font-weight: 800; }
.rp-table-head .meta { font-size: .78rem; opacity: .8; }
.rp-table-wrap { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.rp-table thead th {
    background: #7B1C3E; color: #fff;
    padding: 10px 14px; font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    text-align: left; white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.1);
}
.rp-table thead th:last-child { border-right: none; }
.rp-table tbody td {
    padding: 10px 14px; border-bottom: 1px solid #fdf3e3;
    font-size: .86rem; color: #1f2937; vertical-align: middle;
}
.rp-table tbody tr:last-child td { border-bottom: none; }
.rp-table tbody tr:hover td { background: #fffdf6; }
.rp-table .name-cell { font-weight: 700; }
.rp-table .num-cell  { font-variant-numeric: tabular-nums; text-align: right; }

/* Role badge */
.role-pill {
    display: inline-block; padding: 2px 8px; border-radius: 99px;
    font-size: .7rem; font-weight: 800; text-transform: uppercase;
}
.role-driver    { background: #fce8ef; color: #7B1C3E; }
.role-conductor { background: #fef3c7; color: #92400e; }
.role-staff     { background: #eff6ff; color: #1d4ed8; }

/* Attendance % bar */
.att-pct-wrap { display: flex; align-items: center; gap: 8px; }
.att-pct-bar  { flex: 1; height: 6px; background: #f1f5f9; border-radius: 99px; overflow: hidden; min-width: 60px; }
.att-pct-fill { height: 100%; border-radius: 99px; transition: width .6s cubic-bezier(.22,.68,0,1.2); }
.att-pct-label { font-weight: 700; font-size: .82rem; min-width: 38px; text-align: right; }

/* On-Time % dot meter */
.pct-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

/* Empty state */
.rp-empty { padding: 36px; text-align: center; color: #9ca3af; }
.rp-empty p { margin: 8px 0 0; font-size: .9rem; }

/* ── CSS Bar Chart ─────────────────────────── */
.rp-chart-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07); padding: 20px 24px;
}
.rp-chart-card h3 {
    margin: 0 0 20px; font-size: .95rem; font-weight: 800; color: #7B1C3E;
    display: flex; align-items: center; gap: 8px;
}
.rp-bar-chart { display: flex; flex-direction: column; gap: 10px; }
.rp-bar-row { display: grid; grid-template-columns: 160px 1fr 60px; align-items: center; gap: 10px; }
.rp-bar-name { font-size: .8rem; font-weight: 600; color: #374151; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; text-align: right; }
.rp-bar-track { height: 22px; background: #f1f5f9; border-radius: 6px; overflow: hidden; position: relative; }
.rp-bar-fill  { height: 100%; border-radius: 6px; transition: width .7s cubic-bezier(.22,.68,0,1.2); display: flex; align-items: center; padding-left: 8px; }
.rp-bar-fill span { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.9); white-space: nowrap; }
.rp-bar-val   { font-size: .82rem; font-weight: 800; color: #374151; text-align: left; }
.rp-chart-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #fdf3e3; }
.rp-legend-item { display: flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 600; color: #4b5563; }
.rp-legend-dot  { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

/* ── Operational section (kept intact) ── */
.rp-ops-section { display: grid; gap: 16px; }
.kpi-wrap--neo {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 16px !important;
}
@media(max-width:1100px){ .kpi-wrap--neo{ grid-template-columns:repeat(2,1fr) !important; } }
@media(max-width:640px) { .kpi-wrap--neo{ grid-template-columns:1fr !important; } }
.kpi2 { border:1px solid #e5e7eb !important; border-top-width:4px !important; border-radius:12px !important;
         background:#fff !important; padding:16px !important;
         box-shadow:0 2px 8px rgba(0,0,0,.06) !important; transition:box-shadow .3s,transform .3s !important; }
.kpi2:hover { box-shadow:0 4px 12px rgba(0,0,0,.1) !important; transform:translateY(-2px) !important; }
.kpi2.tone-red   { border-top-color:#d0302c !important; }
.kpi2.tone-green { border-top-color:#1e9e4a !important; }
.kpi2.tone-orange{ border-top-color:#e06a00 !important; }
.kpi2.tone-blue  { border-top-color:#3b82f6 !important; }
.kpi2 header { display:flex !important; justify-content:space-between !important; align-items:center !important; margin-bottom:8px !important; }
.kpi2 h3 { margin:0 !important; font-size:15px !important; font-weight:600 !important; color:#1f2937 !important; }
.kpi2 .ico { width:36px !important; height:36px !important; border-radius:50% !important;
              display:flex !important; align-items:center !important; justify-content:center !important;
              background:rgba(0,0,0,.05) !important; flex-shrink:0 !important; }
.kpi2.tone-red .ico { color:#d0302c !important; } .kpi2.tone-green .ico { color:#1e9e4a !important; }
.kpi2.tone-orange .ico { color:#e06a00 !important; } .kpi2.tone-blue .ico { color:#3b82f6 !important; }
.kpi2 .value { font-size:28px !important; font-weight:800 !important; margin:6px 0 !important; color:#1f2937 !important; }
.kpi2.tone-red .value { color:#d0302c !important; } .kpi2.tone-green .value { color:#1e9e4a !important; }
.kpi2.tone-orange .value { color:#e06a00 !important; } .kpi2.tone-blue .value { color:#3b82f6 !important; }
.kpi2 .hint { margin-top:4px !important; font-size:12px !important; color:#6b7280 !important; }
.chart-card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:18px;
              box-shadow:var(--shadow); margin:0; position:relative; }
.chart-card h2 { margin:0 0 12px; font-size:15px; font-weight:600; color:#1f2937; }
.chart-card canvas { max-height:280px; width:100% !important; }
.charts-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(400px,1fr)); gap:16px; }
@media(max-width:768px){ .charts-grid{ grid-template-columns:1fr; } }

@media(max-width:900px) {
    .rp-bar-row { grid-template-columns: 100px 1fr 50px; }
    .rp-filter-bar form { gap: 8px; }
}
</style>

<div class="rp-page">

<!-- Hero -->
<section class="rp-hero">
    <div>
        <h1>&#128202; Depot Reports</h1>
        <p><?= htmlspecialchars($reportTypes[$reportType] ?? 'Report') ?> &bull; <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></p>
    </div>
    <div class="rp-hero-right">
        <a class="rp-btn rp-btn-outline" href="/O/reports?<?= $baseQs ?>&export=csv">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
        <button class="rp-btn rp-btn-pdf" id="rp-print-btn" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / PDF
        </button>
    </div>
</section>

<!-- Filter bar -->
<div class="rp-filter-bar">
<form method="get" action="/O/reports">
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
    <?php if ($isOps): ?>
    <div class="rp-field">
        <span class="rp-field-label">Route</span>
        <select name="route">
            <option value="">All Routes</option>
            <?php foreach (($routes ?? []) as $r): ?>
            <option value="<?= htmlspecialchars($r['route_id']) ?>" <?= (!empty($filters['route']) && $filters['route']==$r['route_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['route_no'] . (isset($r['name']) ? ' — ' . $r['name'] : '')) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="rp-field">
        <span class="rp-field-label">Bus ID</span>
        <select name="bus_id">
            <option value="">All Buses</option>
            <?php foreach (($buses ?? []) as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>" <?= (!empty($filters['bus_id']) && $filters['bus_id']==$b['reg_no']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['reg_no'] . (isset($b['make']) ? ' ' . $b['make'] : '')) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="rp-btn-group">
        <button type="submit" class="rp-btn rp-btn-primary">Apply</button>
        <a class="rp-btn rp-btn-outline" href="/O/reports?report_type=<?= urlencode($reportType) ?>">Reset</a>
    </div>
</form>
</div>

<?php if ($reportType === 'attendance'): ?>
<!-- ══ Staff Attendance Report ══════════════════════════════════════ -->

<!-- Summary cards -->
<div class="rp-cards">
    <div class="rp-card" style="--accent:#7B1C3E">
        <div class="rp-card-lbl">Total Staff</div>
        <div class="rp-card-val"><?= (int)($hrSummary['total_staff'] ?? 0) ?></div>
        <div class="rp-card-hint">with records in period</div>
    </div>
    <div class="rp-card" style="--accent:#16a34a">
        <div class="rp-card-lbl">Avg Attendance %</div>
        <div class="rp-card-val"><?= $hrSummary['avg_att_pct'] ?? 0 ?>%</div>
        <div class="rp-card-hint">across all staff</div>
    </div>
    <div class="rp-card" style="--accent:#dc2626">
        <div class="rp-card-lbl">Most Absent Staff</div>
        <div class="rp-card-val" style="font-size:1rem;padding-top:8px;"><?= htmlspecialchars((string)($hrSummary['most_absent'] ?? '—')) ?></div>
        <div class="rp-card-hint">highest absent days</div>
    </div>
</div>

<?php if (!empty($hrRows)): ?>
<!-- Bar chart – Attendance % per staff -->
<div class="rp-chart-card">
    <h3>
        <svg width="16" height="16" fill="none" stroke="#7B1C3E" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        Attendance % by Staff Member
    </h3>
    <div class="rp-bar-chart" id="att-bar-chart">
        <?php
        $chartRows = array_slice($hrRows, 0, 15); // cap at 15 for readability
        foreach ($chartRows as $i => $r):
            $pct  = (float)($r['att_pct'] ?? 0);
            $color = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');
        ?>
        <div class="rp-bar-row">
            <div class="rp-bar-name" title="<?= htmlspecialchars($r['full_name']) ?>"><?= htmlspecialchars($r['full_name']) ?></div>
            <div class="rp-bar-track">
                <div class="rp-bar-fill" style="width:0%;background:<?= $color ?>;"
                     data-target="<?= $pct ?>">
                    <?php if ($pct >= 20): ?><span><?= $pct ?>%</span><?php endif; ?>
                </div>
            </div>
            <div class="rp-bar-val"><?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
        <?php if (count($hrRows) > 15): ?>
        <div style="font-size:.75rem;color:#9ca3af;text-align:center;padding:4px 0;">
            Showing top 15 of <?= count($hrRows) ?> staff
        </div>
        <?php endif; ?>
    </div>
    <div class="rp-chart-legend">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#16a34a"></span> ≥ 90% (Good)</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#d97706"></span> 70–89% (Fair)</span>
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#dc2626"></span> &lt; 70% (Low)</span>
    </div>
</div>

<!-- Table -->
<div class="rp-table-card">
    <div class="rp-table-head">
        <h2>Staff Attendance Details</h2>
        <span class="meta"><?= count($hrRows) ?> staff &bull; <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></span>
    </div>
    <div class="rp-table-wrap">
    <table class="rp-table">
        <thead><tr>
            <th>#</th><th>Name</th><th>Role</th>
            <th class="num-cell">Present Days</th>
            <th class="num-cell">Absent Days</th>
            <th class="num-cell">Leave Days</th>
            <th>Attendance %</th>
            <th>Last Absent Date</th>
        </tr></thead>
        <tbody>
        <?php foreach ($hrRows as $i => $r):
            $pct  = (float)($r['att_pct'] ?? 0);
            $color = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');
            $roleCls = match(strtolower((string)($r['role'] ?? ''))) {
                'driver'    => 'role-driver',
                'conductor' => 'role-conductor',
                default     => 'role-staff',
            };
        ?>
        <tr>
            <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
            <td class="name-cell"><?= htmlspecialchars((string)($r['full_name'] ?? '—')) ?></td>
            <td><span class="role-pill <?= $roleCls ?>"><?= htmlspecialchars((string)($r['role'] ?? '—')) ?></span></td>
            <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['present_days'] ?? 0) ?></td>
            <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['absent_days'] ?? 0) ?></td>
            <td class="num-cell" style="color:#d97706;font-weight:700;"><?= (int)($r['leave_days'] ?? 0) ?></td>
            <td>
                <div class="att-pct-wrap">
                    <div class="att-pct-bar">
                        <div class="att-pct-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $color ?>;"></div>
                    </div>
                    <span class="att-pct-label" style="color:<?= $color ?>;"><?= $pct ?>%</span>
                </div>
            </td>
            <td style="color:#6b7280;font-size:.82rem;">
                <?= !empty($r['last_absent_date']) ? date('d M Y', strtotime((string)$r['last_absent_date'])) : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php else: ?>
<div class="rp-table-card"><div class="rp-empty">
    <svg width="44" height="44" fill="none" stroke="#e5e7eb" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <p>No attendance data found for the selected period.</p>
</div></div>
<?php endif; ?>

<?php elseif ($reportType === 'driver_performance'): ?>
<!-- ══ Driver Performance Report ══════════════════════════════════ -->

<!-- Summary cards -->
<div class="rp-cards">
    <div class="rp-card" style="--accent:#1d4ed8">
        <div class="rp-card-lbl">Total Trips</div>
        <div class="rp-card-val"><?= (int)($hrSummary['total_trips'] ?? 0) ?></div>
        <div class="rp-card-hint">in selected period</div>
    </div>
    <div class="rp-card" style="--accent:#16a34a">
        <div class="rp-card-lbl">On-Time %</div>
        <div class="rp-card-val"><?= $hrSummary['on_time_pct'] ?? 0 ?>%</div>
        <div class="rp-card-hint">average across drivers</div>
    </div>
    <div class="rp-card" style="--accent:#d97706">
        <div class="rp-card-lbl">Avg Delay</div>
        <div class="rp-card-val"><?= $hrSummary['avg_delay_min'] ?? 0 ?><span style="font-size:.55em;margin-left:3px;">min</span></div>
        <div class="rp-card-hint">per delayed departure</div>
    </div>
</div>

<?php if (!empty($hrRows)): ?>
<!-- Bar chart – Trips per driver -->
<div class="rp-chart-card">
    <h3>
        <svg width="16" height="16" fill="none" stroke="#7B1C3E" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Trips Assigned per Driver
    </h3>
    <div class="rp-bar-chart" id="drv-bar-chart">
        <?php
        $chartRows = array_slice($hrRows, 0, 15);
        $maxTrips  = max(1, max(array_column($chartRows, 'trips_assigned')));
        foreach ($chartRows as $r):
            $trips = (int)($r['trips_assigned'] ?? 0);
            $done  = (int)($r['completed'] ?? 0);
            $barPct = round(($trips / $maxTrips) * 100);
            $donePct = $trips > 0 ? round(($done / $trips) * 100) : 0;
        ?>
        <div class="rp-bar-row">
            <div class="rp-bar-name" title="<?= htmlspecialchars($r['driver_name'] ?? '') ?>"><?= htmlspecialchars((string)($r['driver_name'] ?? '—')) ?></div>
            <div class="rp-bar-track">
                <div class="rp-bar-fill" style="width:0%;background:#7B1C3E;" data-target="<?= $barPct ?>">
                    <?php if ($barPct >= 15): ?><span><?= $trips ?> trips</span><?php endif; ?>
                </div>
            </div>
            <div class="rp-bar-val"><?= number_format((float)($r['on_time_pct'] ?? 0), 0) ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="rp-chart-legend">
        <span class="rp-legend-item"><span class="rp-legend-dot" style="background:#7B1C3E"></span> Bar = Trips Assigned</span>
        <span class="rp-legend-item" style="margin-left:auto;font-size:.74rem;color:#6b7280;">Value = On-Time %</span>
    </div>
</div>

<!-- Table -->
<div class="rp-table-card">
    <div class="rp-table-head">
        <h2>Driver Performance Details</h2>
        <span class="meta"><?= count($hrRows) ?> driver<?= count($hrRows) !== 1 ? 's' : '' ?> &bull; <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></span>
    </div>
    <div class="rp-table-wrap">
    <table class="rp-table">
        <thead><tr>
            <th>#</th><th>Driver Name</th>
            <th class="num-cell">Trips Assigned</th>
            <th class="num-cell">Completed</th>
            <th class="num-cell">Delayed</th>
            <th class="num-cell">Cancelled</th>
            <th>On-Time %</th>
            <th class="num-cell">Avg Delay (min)</th>
        </tr></thead>
        <tbody>
        <?php foreach ($hrRows as $i => $r):
            $otp   = (float)($r['on_time_pct'] ?? 0);
            $otCol = $otp >= 90 ? '#16a34a' : ($otp >= 70 ? '#d97706' : '#dc2626');
        ?>
        <tr>
            <td style="color:#9ca3af;font-size:.78rem;"><?= $i+1 ?></td>
            <td class="name-cell"><?= htmlspecialchars((string)($r['driver_name'] ?? '—')) ?></td>
            <td class="num-cell" style="font-weight:700;"><?= (int)($r['trips_assigned'] ?? 0) ?></td>
            <td class="num-cell" style="color:#16a34a;font-weight:700;"><?= (int)($r['completed'] ?? 0) ?></td>
            <td class="num-cell" style="color:#d97706;font-weight:700;"><?= (int)($r['delayed'] ?? 0) ?></td>
            <td class="num-cell" style="color:#dc2626;font-weight:700;"><?= (int)($r['cancelled'] ?? 0) ?></td>
            <td>
                <div class="att-pct-wrap">
                    <div class="att-pct-bar">
                        <div class="att-pct-fill" style="width:<?= min(100,$otp) ?>%;background:<?= $otCol ?>;"></div>
                    </div>
                    <span class="att-pct-label" style="color:<?= $otCol ?>;"><?= $otp ?>%</span>
                </div>
            </td>
            <td class="num-cell" style="color:#d97706;"><?= number_format((float)($r['avg_delay_min'] ?? 0), 1) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php else: ?>
<div class="rp-table-card"><div class="rp-empty">
    <svg width="44" height="44" fill="none" stroke="#e5e7eb" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg>
    <p>No driver trip data found for the selected period.</p>
</div></div>
<?php endif; ?>

<?php else: ?>
<!-- ══ Operational Reports (Trip Completion / Delay / Bus Utilization) ════ -->
<div class="rp-ops-section">
    <section class="kpi-wrap kpi-wrap--neo">
        <article class="kpi2 tone-red">
            <header><h3>Delayed Trips</h3><span class="ico"><svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg></span></header>
            <div class="value"><?= (int)($kpis['delayed'] ?? 0) ?></div>
            <div class="hint">in selected period</div>
        </article>
        <article class="kpi2 tone-green">
            <header><h3>Total Trips</h3><span class="ico"><svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 001 1h1a1 1 0 001-1v-1h6v1a1 1 0 001 1h1a1 1 0 001-1v-1.78c.61-.55 1-1.34 1-2.22V5a3 3 0 00-3-3H7A3 3 0 004 5v11z"/></svg></span></header>
            <div class="value"><?= (int)($kpis['trips'] ?? 0) ?></div>
            <div class="hint">completed trips</div>
        </article>
        <article class="kpi2 tone-orange">
            <header><h3>Avg Delay</h3><span class="ico"><svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M6 2v6l4 4-4 4v6h12v-6l-4-4 4-4V2H6zM8 4h8v1.17L12 9 8 5.17V4zm8 16H8v-1.17L12 15l4 3.83V20z"/></svg></span></header>
            <div class="value"><?= number_format((float)($kpis['avgDelayMin'] ?? 0), 1) ?><span style="font-size:.6em;margin-left:4px;">min</span></div>
            <div class="hint">per delayed trip</div>
        </article>
        <article class="kpi2 tone-blue">
            <header><h3>Cancellations</h3><span class="ico"><svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M22.7 19.3l-4.4-4.4c.5-1 .7-2.1.7-3.3 0-4.4-3.6-8-8-8-1.2 0-2.3.2-3.3.7L4.7 1.3 1.3 4.7l3.9 3.9C5 9.3 5 10.1 5 11c0 4.4 3.6 8 8 8 .9 0 1.7 0 2.1-.2l3.9 3.9 3.4-3.4-0.2-0.1z"/></svg></span></header>
            <div class="value"><?= (int)($kpis['breakdowns'] ?? 0) ?></div>
            <div class="hint">maintenance events</div>
        </article>
    </section>

    <section class="charts-grid">
        <div class="chart-card">
            <a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=bus_status&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Details</a>
            <h2>Bus Status Distribution</h2>
            <canvas id="busStatusChart" data-drill-key="bus_status" data-drill-base="/O/reports/details"></canvas>
        </div>
        <div class="chart-card">
            <a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=delayed_by_route&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Details</a>
            <h2>Delayed Trips by Route</h2>
            <canvas id="delayedByRouteChart" data-drill-key="delayed_by_route" data-drill-base="/O/reports/details"></canvas>
        </div>
        <div class="chart-card">
            <a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=speed_by_bus&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Details</a>
            <h2>Speed Violations by Bus</h2>
            <canvas id="speedByBusChart" data-drill-key="speed_by_bus" data-drill-base="/O/reports/details"></canvas>
        </div>
        <div class="chart-card">
            <a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=wait_time&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Details</a>
            <h2>Bus Wait Time Distribution</h2>
            <canvas id="waitTimeChart" data-drill-key="wait_time" data-drill-base="/O/reports/details"></canvas>
        </div>
        <div class="chart-card">
            <a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=complaints_by_route&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Details</a>
            <h2>Complaints by Route</h2>
            <canvas id="complaintsRouteChart" data-drill-key="complaints_by_route" data-drill-base="/O/reports/details"></canvas>
        </div>
    </section>

    <script id="analytics-data" type="application/json"><?= $analyticsJson ?? '{}' ?></script>
    <script src="/assets/js/analytics/dummyData.js"></script>
    <script src="/assets/js/analytics/chartCore.js"></script>
    <script src="/assets/js/analytics/busStatus.js"></script>
    <script src="/assets/js/analytics/revenue.js"></script>
    <script src="/assets/js/analytics/speedByBus.js"></script>
    <script src="/assets/js/analytics/waitTime.js"></script>
    <script src="/assets/js/analytics/delayedByRoute.js"></script>
    <script src="/assets/js/analytics/complaintsRoute.js"></script>
    <script src="/assets/js/analytics/drilldown.js"></script>
</div>
<?php endif; ?>

</div><!-- /.rp-page -->

<script>
/* Animate CSS bar chart fills on page load */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.rp-bar-fill[data-target]').forEach(function (bar) {
        var target = parseFloat(bar.dataset.target) || 0;
        bar.style.width = '0%';
        setTimeout(function () { bar.style.width = target + '%'; }, 120);
    });
});
</script>
