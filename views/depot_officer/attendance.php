<?php
/* vars from DepotOfficerController::attendance()
   $drivers, $conductors, $records, $summary, $history,
   $date, $histFrom, $histTo, $msg
*/
$today   = date('Y-m-d');
$prevDay = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
$canNext = ($date < $today);

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

$summary  = $summary  ?? ['present'=>0,'absent'=>0,'late'=>0,'half'=>0,'total'=>0];
$total    = max(1, (int)$summary['total']);
$pct      = round(($summary['present'] / $total) * 100);
?>
<style>
/* ═══ Attendance Page – NexBus maroon/gold theme ═══════════════════════════ */
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

/* ── Top filter bar ── */
.att-filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    box-shadow: 0 10px 28px rgba(17,24,39,.08);
    border-left: 4px solid #f3b944;
    margin-bottom: 22px;
}
.att-filter-bar label { font-weight: 700; font-size: .85rem; color: #7B1C3E; white-space: nowrap; }
.att-filter-bar input[type=date],
.att-filter-bar input[type=text],
.att-filter-bar select {
    border: 1.5px solid #e8d39a; border-radius: 8px; padding: 7px 12px;
    font-size: .88rem; color: #2b2b2b; background: #fffdf6;
    transition: border-color .18s, box-shadow .18s;
}
.att-filter-bar input[type=text] { min-width: 180px; }
.att-filter-bar input:focus, .att-filter-bar select:focus { outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.2); }
.att-filter-divider { width: 1px; height: 26px; background: #e8d39a; flex-shrink: 0; }
.att-filter-bar .go-btn {
    background: #7B1C3E; color: #fff; border: none; border-radius: 8px;
    padding: 8px 18px; font-size: .88rem; font-weight: 700; cursor: pointer; transition: background .2s;
}
.att-filter-bar .go-btn:hover { background: #a8274e; }

/* Toast */
.att-toast {
    background: #fef3c7; border: 1px solid #f3b944; border-radius: 10px;
    padding: 12px 20px; color: #7B1C3E; font-weight: 600; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}

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

<!-- Summary Stats -->
<div class="att-stats">
    <div class="att-stat-card" style="--color:#16a34a">
        <div class="val"><?= (int)$summary['present'] ?></div>
        <div class="lbl">Present</div>
        <div class="sub">Last 30 days</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $total>0?round($summary['present']/$total*100):0 ?>%"></div></div>
    </div>
    <div class="att-stat-card" style="--color:#dc2626">
        <div class="val"><?= (int)$summary['absent'] ?></div>
        <div class="lbl">Absent</div>
        <div class="sub">Last 30 days</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $total>0?round($summary['absent']/$total*100):0 ?>%"></div></div>
    </div>
    <div class="att-stat-card" style="--color:#d97706">
        <div class="val"><?= (int)$summary['late'] ?></div>
        <div class="lbl">Late</div>
        <div class="sub">Last 30 days</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $total>0?round($summary['late']/$total*100):0 ?>%"></div></div>
    </div>
    <div class="att-stat-card" style="--color:#7c3aed">
        <div class="val"><?= (int)$summary['half'] ?></div>
        <div class="lbl">Half Day</div>
        <div class="sub">Last 30 days</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $total>0?round($summary['half']/$total*100):0 ?>%"></div></div>
    </div>
    <div class="att-stat-card" style="--color:#7B1C3E">
        <div class="val"><?= $pct ?>%</div>
        <div class="lbl">Attendance Rate</div>
        <div class="sub"><?= $summary['total'] ?> total records</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $pct ?>%"></div></div>
    </div>
</div>

<!-- Top Filter Bar -->
<div class="att-filter-bar">
    <label>&#128197; Date:</label>
    <form method="get" action="/O/attendance" style="display:contents;">
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" max="<?= $today ?>" id="att-date-pick">
        <button type="submit" class="go-btn">View &amp; Mark</button>
    </form>
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
        <span style="font-size:.82rem;color:#64748b;">Marking for <?= date('d M Y', strtotime($date)) ?></span>
        <button type="submit" class="btn-save">&#10003;&nbsp;Save Attendance</button>
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
            </tr>
        </thead>
        <tbody id="hist-tbody">
        <?php foreach ($history as $h):
            $st        = strtolower(str_replace(' ','_',(string)($h['status'] ?? 'Present')));
            $pillCls   = 'pill-' . $st;
            $typeLower = strtolower((string)($h['staff_type'] ?? 'driver'));
        ?>
        <tr data-name="<?= strtolower(htmlspecialchars((string)($h['full_name'] ?? ''))) ?>"
            data-type="<?= $typeLower ?>"
            data-status="<?= $st ?>">
            <td><?= date('d M Y', strtotime((string)$h['work_date'])) ?></td>
            <td><span class="type-badge type-<?= $typeLower ?>"><?= htmlspecialchars((string)($h['staff_type'] ?? '—')) ?></span></td>
            <td style="font-weight:600;"><?= htmlspecialchars((string)($h['full_name'] ?? '—')) ?></td>
            <td><span class="status-pill <?= $pillCls ?>"><?= htmlspecialchars(str_replace('_',' ',(string)($h['status'] ?? 'Present'))) ?></span></td>
            <td style="color:#64748b;"><?= htmlspecialchars((string)($h['notes'] ?? '')) ?: '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>

<script>
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

/* ── Entrance animations ── */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.att-stat-card .val').forEach(function(el) {
        var raw    = el.textContent.trim();
        var isPct  = raw.endsWith('%');
        var target = parseInt(raw, 10);
        if (isNaN(target) || target === 0) return;
        el.textContent = isPct ? '0%' : '0';
        var delay = (parseFloat(getComputedStyle(el.closest('.att-stat-card')).animationDelay) || 0) * 1000 + 100;
        setTimeout(function() {
            var dur = 700, start = performance.now();
            requestAnimationFrame(function step(now) {
                var t = Math.min((now - start) / dur, 1);
                var v = Math.round((1 - Math.pow(1 - t, 3)) * target);
                el.textContent = isPct ? v + '%' : v;
                if (t < 1) requestAnimationFrame(step);
            });
        }, delay);
    });
    document.querySelectorAll('.pct-bar').forEach(function(bar) {
        var w = bar.style.width; bar.style.width = '0';
        var d = (parseFloat(getComputedStyle(bar.closest('.att-stat-card')).animationDelay) || 0) * 1000 + 200;
        setTimeout(function() { bar.style.width = w; }, d);
    });
});
</script>

