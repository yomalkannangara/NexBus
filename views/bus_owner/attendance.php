<?php
/* vars from BusOwnerController::attendance()
   $drivers, $conductors, $records, $summary, $history,
   $date, $histFrom, $histTo, $msg
*/
$today    = date('Y-m-d');
$prevDay  = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDay  = date('Y-m-d', strtotime($date . ' +1 day'));
$canNext  = ($date < $today);

$statusLabels = ['Present'=>'Present','Absent'=>'Absent','Late'=>'Late','Half_Day'=>'Half Day'];
$statusColors = ['Present'=>'#16a34a','Absent'=>'#dc2626','Late'=>'#d97706','Half_Day'=>'#7c3aed'];

function attStatus(array $records, string $type, int $id): string {
    return $records[$type.'__'.$id]['status'] ?? 'Absent';
}
function attNote(array $records, string $type, int $id): string {
    return htmlspecialchars($records[$type.'__'.$id]['notes'] ?? '');
}

$pct = $summary['total'] > 0
    ? round(($summary['present'] / $summary['total']) * 100)
    : 0;
?>
<style>
/* ── Attendance Page — NexBus maroon/gold theme ── */
.att-hero {
    background: linear-gradient(135deg,#80143c 0%,#a8274e 100%);
    border-bottom: 4px solid #f3b944;
    color:#fff;
    padding:28px 32px 24px;
    border-radius:14px;
    margin-bottom:28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
}
.att-hero h1 { margin:0; font-size:1.6rem; font-weight:700; }
.att-hero p  { margin:4px 0 0; opacity:.8; font-size:.95rem; }
.att-hero-right { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.day-nav { display:flex; align-items:center; gap:6px; background:rgba(255,255,255,.15); border-radius:10px; padding:6px 10px; }
.day-nav a, .day-nav span { color:#fff; text-decoration:none; font-size:1rem; padding:2px 6px; border-radius:5px; }
.day-nav a:hover { background:rgba(255,255,255,.25); }
.day-nav .day-label { font-weight:600; font-size:.95rem; min-width:110px; text-align:center; }

.att-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:28px; }

@keyframes attCardIn {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes attValPop {
    0%   { transform:scale(.85); opacity:.4; }
    60%  { transform:scale(1.08); }
    100% { transform:scale(1);   opacity:1; }
}

.att-stat-card {
    background:#fff;
    border-radius:12px;
    padding:20px 18px 16px;
    box-shadow:0 2px 8px rgba(17,24,39,.06);
    border-left:4px solid var(--color);
    display:flex; flex-direction:column; gap:4px;
    position:relative; overflow:hidden;
    cursor:default;
    /* entrance */
    opacity:0;
    animation:attCardIn .45s cubic-bezier(.22,.68,0,1.2) forwards;
    /* hover */
    transition:transform .15s ease, box-shadow .15s ease;
}
/* staggered delays for each card */
.att-stat-card:nth-child(1) { animation-delay:.05s; }
.att-stat-card:nth-child(2) { animation-delay:.12s; }
.att-stat-card:nth-child(3) { animation-delay:.19s; }
.att-stat-card:nth-child(4) { animation-delay:.26s; }
.att-stat-card:nth-child(5) { animation-delay:.33s; }

.att-stat-card:hover {
    transform:translateY(-3px);
    box-shadow:0 8px 22px rgba(17,24,39,.10);
}
/* subtle colour shimmer on hover */
.att-stat-card::before {
    content:'';
    position:absolute; inset:0;
    background:linear-gradient(120deg, transparent 60%, rgba(255,255,255,.55) 100%);
    opacity:0;
    transition:opacity .25s;
    pointer-events:none;
}
.att-stat-card:hover::before { opacity:1; }

.att-stat-card .val {
    font-size:2rem; font-weight:700; color:var(--color); line-height:1;
    display:inline-block;
    animation:attValPop .5s cubic-bezier(.22,.68,0,1.2) forwards;
    animation-delay:inherit;
}
.att-stat-card .lbl { font-size:.82rem; color:#6b7280; font-weight:500; }
.att-stat-card .sub { font-size:.78rem; color:#9ca3af; }

.pct-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; margin-top:8px; overflow:hidden; }
.pct-bar      { height:100%; border-radius:99px; background:var(--color); width:0; transition:width .8s cubic-bezier(.22,.68,0,1.2); }

/* Date picker bar */
.date-bar {
    background:#fff;
    border-radius:12px;
    padding:14px 20px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    box-shadow:0 10px 28px rgba(17,24,39,.08);
    border-left:4px solid #f3b944;
    margin-bottom:22px;
}
.date-bar label { font-weight:600; font-size:.9rem; color:#80143c; }
.date-bar input[type=date] {
    border:1.5px solid #e8d39a;
    border-radius:8px;
    padding:7px 12px;
    font-size:.9rem;
    color:#2b2b2b;
    background:#fffdf6;
    cursor:pointer;
}
.date-bar input[type=date]:focus { outline:none; border-color:#f3b944; box-shadow:0 0 0 3px rgba(243,185,68,.2); }
.date-bar .go-btn {
    background:#80143c;
    color:#fff;
    border:none;
    border-radius:8px;
    padding:8px 18px;
    font-size:.88rem;
    font-weight:600;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
    transition:background .2s;
}
.date-bar .go-btn:hover { background:#a8274e; }

/* Toast */
.att-toast {
    background:#fef3c7;
    border:1px solid #f3b944;
    border-radius:10px;
    padding:12px 20px;
    color:#80143c;
    font-weight:600;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

/* Mark form layout */
.mark-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
    margin-bottom:28px;
    align-items:stretch;
}
@media(max-width:900px){ .mark-grid { grid-template-columns:1fr; } }

.mark-card {
    background:#fff;
    border-radius:14px;
    box-shadow:0 10px 28px rgba(17,24,39,.08);
    overflow:hidden;
    display:flex;
    flex-direction:column;
}
.mark-card .staff-table-wrap { flex:1; }
.mark-card .submit-bar { margin-top:auto; }
.mark-card-head {
    background:linear-gradient(90deg,#80143c,#a8274e);
    border-bottom:3px solid #f3b944;
    color:#fff;
    padding:14px 20px;
    display:flex; align-items:center; gap:10px;
}
.mark-card-head h3 { margin:0; font-size:1rem; font-weight:600; }
.mark-card-head .badge {
    background:rgba(255,255,255,.25);
    border-radius:99px;
    padding:2px 10px;
    font-size:.78rem;
    font-weight:600;
}

.staff-table { width:100%; border-collapse:collapse; }
.staff-table th {
    background:#fff8f0;
    padding:10px 14px;
    font-size:.8rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#80143c;
    border-bottom:1px solid #e8d39a;
    text-align:left;
}
.staff-table td { padding:10px 14px; border-bottom:1px solid #fdf3e3; vertical-align:middle; }
.staff-table tr:last-child td { border-bottom:none; }
.staff-table tr:hover td { background:#fffdf6; }

.staff-name { font-weight:600; color:#2b2b2b; font-size:.9rem; }
.staff-suspended { color:#9ca3af; font-size:.75rem; display:block; }

.att-select {
    border:1.5px solid #e8d39a;
    border-radius:8px;
    padding:6px 10px;
    font-size:.85rem;
    background:#fff;
    color:#2b2b2b;
    cursor:pointer;
    font-weight:500;
    min-width:110px;
}
.att-select:focus { outline:none; border-color:#f3b944; box-shadow:0 0 0 3px rgba(243,185,68,.2); }
.att-select.is-present { border-color:#16a34a; background:#f0fdf4; color:#16a34a; }
.att-select.is-absent  { border-color:#dc2626; background:#fef2f2; color:#dc2626; }
.att-select.is-late    { border-color:#d97706; background:#fffbeb; color:#d97706; }
.att-select.is-half    { border-color:#80143c; background:#fce8ef; color:#80143c; }

.note-input {
    border:1.5px solid #e8d39a;
    border-radius:8px;
    padding:5px 10px;
    font-size:.82rem;
    width:100%;
    box-sizing:border-box;
    color:#6b7280;
}
.note-input:focus { outline:none; border-color:#f3b944; box-shadow:0 0 0 3px rgba(243,185,68,.2); }

.empty-staff { padding:24px; text-align:center; color:#9ca3af; font-size:.9rem; }

/* ── Suspended row lock ────────────────────────────────────────── */
.att-row--suspended {
    background: repeating-linear-gradient(
        45deg,
        #f9fafb,
        #f9fafb 6px,
        #f3f4f6 6px,
        #f3f4f6 12px
    );
    opacity: .72;
    pointer-events: none;
    user-select: none;
}
.att-row--suspended td { color: #9ca3af !important; }
.att-row--suspended .staff-name { text-decoration: line-through; color: #9ca3af; }
.att-lock-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #F3F4F6;
    border: 1px solid #D1D5DB;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: .72rem;
    font-weight: 700;
    color: #6B7280;
    vertical-align: middle;
    margin-left: 6px;
    letter-spacing: .3px;
}
.att-lock-badge svg { flex-shrink: 0; }

.submit-bar {
    display:flex; justify-content:flex-end; align-items:center; gap:14px;
    padding:16px 20px;
    background:#fff8f0;
    border-top:1px solid #e8d39a;
}
.btn-save {
    background:#80143c;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:11px 32px;
    font-size:.95rem;
    font-weight:700;
    cursor:pointer;
    letter-spacing:.02em;
    transition:background .2s;
}
.btn-save:hover { background:#a8274e; }

/* Status pill */
.status-pill {
    display:inline-block;
    padding:3px 12px;
    border-radius:99px;
    font-size:.75rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
}
.pill-present  { background:#d1fae5; color:#065f46; }
.pill-absent   { background:#fee2e2; color:#991b1b; }
.pill-late     { background:#fef3c7; color:#92400e; }
.pill-half_day { background:#fce8ef; color:#80143c; }

/* History section */
.history-section {
    background:#fff;
    border-radius:14px;
    box-shadow:0 10px 28px rgba(17,24,39,.08);
    overflow:hidden;
    margin-bottom:28px;
}
.history-head {
    padding:16px 22px;
    border-bottom:1px solid #e8d39a;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
}
.history-head h3 { margin:0; font-size:1rem; font-weight:700; color:#80143c; }
.hist-filter { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.hist-filter label { font-size:.82rem; color:#6b7280; font-weight:600; }
.hist-filter input[type=date] {
    border:1.5px solid #e8d39a;
    border-radius:7px;
    padding:5px 10px;
    font-size:.82rem;
    background:#fffdf6;
    color:#2b2b2b;
}
.hist-filter input[type=date]:focus { outline:none; border-color:#f3b944; }
.hist-filter .go-sm {
    background:#80143c;
    color:#fff;
    border:none;
    border-radius:7px;
    padding:6px 14px;
    font-size:.82rem;
    font-weight:600;
    cursor:pointer;
    transition:background .2s;
}
.hist-filter .go-sm:hover { background:#a8274e; }

.history-table { width:100%; border-collapse:collapse; }
.history-table th {
    background:#fff8f0;
    padding:10px 16px;
    font-size:.78rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#80143c;
    border-bottom:1px solid #e8d39a;
    text-align:left;
}
.history-table td { padding:10px 16px; border-bottom:1px solid #fdf3e3; font-size:.88rem; color:#2b2b2b; }
.history-table tr:last-child td { border-bottom:none; }
.history-table tr:hover td { background:#fffdf6; }
.history-table .type-badge {
    display:inline-block; padding:2px 9px; border-radius:99px; font-size:.72rem; font-weight:700;
    text-transform:uppercase;
}
.type-driver    { background:#fce8ef; color:#80143c; }
.type-conductor { background:#fef3c7; color:#92400e; }

.empty-hist { padding:32px; text-align:center; color:#9ca3af; font-size:.9rem; }
</style>

<?php if (!empty($msg) && $msg === 'saved'): ?>
<div class="att-toast">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#80143c" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
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
            <a href="/B/attendance?date=<?= $prevDay ?>" title="Previous day">&#8592;</a>
            <span class="day-label"><?= date('d M Y', strtotime($date)) ?></span>
            <?php if ($canNext): ?>
            <a href="/B/attendance?date=<?= $nextDay ?>" title="Next day">&#8594;</a>
            <?php else: ?>
            <span style="opacity:.3; cursor:default;">&#8594;</span>
            <?php endif; ?>
        </div>
        <?php if ($date !== $today): ?>
        <a href="/B/attendance" class="go-btn" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid #f3b944;border-radius:9px;padding:8px 16px;text-decoration:none;font-size:.85rem;font-weight:600;">
            &#8635; Today
        </a>
        <?php endif; ?>
    </div>
</section>

<!-- Summary Stats (last 30 days) -->
<div class="att-stats">
    <div class="att-stat-card" style="--color:#16a34a">
        <div class="val"><?= $summary['present'] ?></div>
        <div class="lbl">Present</div>
        <div class="sub">Last 30 days</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $pct ?>%"></div></div>
    </div>
    <div class="att-stat-card" style="--color:#dc2626">
        <div class="val"><?= $summary['absent'] ?></div>
        <div class="lbl">Absent</div>
        <div class="sub">Last 30 days</div>
    </div>
    <div class="att-stat-card" style="--color:#d97706">
        <div class="val"><?= $summary['late'] ?></div>
        <div class="lbl">Late</div>
        <div class="sub">Last 30 days</div>
    </div>
    <div class="att-stat-card" style="--color:#7c3aed">
        <div class="val"><?= $summary['half'] ?></div>
        <div class="lbl">Half Day</div>
        <div class="sub">Last 30 days</div>
    </div>
    <div class="att-stat-card" style="--color:#80143c">
        <div class="val"><?= $pct ?>%</div>
        <div class="lbl">Attendance Rate</div>
        <div class="sub"><?= $summary['total'] ?> total records</div>
        <div class="pct-bar-wrap"><div class="pct-bar" style="width:<?= $pct ?>%"></div></div>
    </div>
</div>

<!-- Date picker bar -->
<form method="get" action="/B/attendance" class="date-bar">
    <label for="att-date">&#128197; Select Date:</label>
    <input type="date" id="att-date" name="date"
           value="<?= htmlspecialchars($date) ?>"
           max="<?= $today ?>">
    <button type="submit" class="go-btn">View &amp; Mark</button>
</form>

<!-- Mark Attendance Forms -->
<div class="mark-grid">

    <!-- Drivers Form -->
    <form method="post" action="/B/attendance">
    <input type="hidden" name="work_date" value="<?= htmlspecialchars($date) ?>">
    <div class="mark-card">
        <div class="mark-card-head">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h3>Drivers</h3>
            <span class="badge"><?= count($drivers) ?></span>
        </div>
        <?php if (empty($drivers)): ?>
        <div class="empty-staff">No drivers found for your fleet.</div>
        <?php else: ?>
        <div class="staff-table-wrap">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($drivers as $d):
                $isSuspended = ($d['status'] === 'Suspended');
                $sel = $isSuspended ? 'Absent' : attStatus($records, 'Driver', $d['id']);
                $cls = 'is-' . strtolower(str_replace('_','-',$sel));
                $rowClass = $isSuspended ? 'att-row--suspended' : '';
            ?>
            <tr class="<?= $rowClass ?>">
                <td>
                    <span class="staff-name"><?= htmlspecialchars($d['full_name']) ?></span>
                    <?php if ($isSuspended): ?>
                    <span class="att-lock-badge">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        Suspended
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <select name="attendance[Driver][<?= $d['id'] ?>]"
                            class="att-select <?= $cls ?>"
                            onchange="syncClass(this)"
                            <?= $isSuspended ? 'disabled' : '' ?>>
                        <?php foreach ($statusLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $sel===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes[Driver][<?= $d['id'] ?>]"
                           class="note-input"
                           value="<?= $isSuspended ? '' : attNote($records,'Driver',$d['id']) ?>"
                           placeholder="<?= $isSuspended ? 'Unavailable — staff suspended' : 'Optional note…' ?>"
                           <?= $isSuspended ? 'disabled' : '' ?>>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="submit-bar">
            <span style="font-size:.82rem;color:#64748b;">Marking for <?= date('d M Y', strtotime($date)) ?></span>
            <button type="submit" class="btn-save">&#10003; Save Attendance</button>
        </div>
        <?php endif; ?>
    </div>
    </form>

    <!-- Conductors Form -->
    <form method="post" action="/B/attendance">
    <input type="hidden" name="work_date" value="<?= htmlspecialchars($date) ?>">
    <div class="mark-card">
        <div class="mark-card-head" style="background:linear-gradient(90deg,#80143c,#a8274e);border-bottom:3px solid #f3b944;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>Conductors</h3>
            <span class="badge"><?= count($conductors) ?></span>
        </div>
        <?php if (empty($conductors)): ?>
        <div class="empty-staff">No conductors found for your fleet.</div>
        <?php else: ?>
        <div class="staff-table-wrap">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($conductors as $c):
                $isSuspended = ($c['status'] === 'Suspended');
                $sel = $isSuspended ? 'Absent' : attStatus($records, 'Conductor', $c['id']);
                $cls = 'is-' . strtolower(str_replace('_','-',$sel));
                $rowClass = $isSuspended ? 'att-row--suspended' : '';
            ?>
            <tr class="<?= $rowClass ?>">
                <td>
                    <span class="staff-name"><?= htmlspecialchars($c['full_name']) ?></span>
                    <?php if ($isSuspended): ?>
                    <span class="att-lock-badge">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        Suspended
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <select name="attendance[Conductor][<?= $c['id'] ?>]"
                            class="att-select <?= $cls ?>"
                            onchange="syncClass(this)"
                            <?= $isSuspended ? 'disabled' : '' ?>>
                        <?php foreach ($statusLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $sel===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes[Conductor][<?= $c['id'] ?>]"
                           class="note-input"
                           value="<?= $isSuspended ? '' : attNote($records,'Conductor',$c['id']) ?>"
                           placeholder="<?= $isSuspended ? 'Unavailable — staff suspended' : 'Optional note…' ?>"
                           <?= $isSuspended ? 'disabled' : '' ?>>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="submit-bar">
            <span style="font-size:.82rem;color:#64748b;">Marking for <?= date('d M Y', strtotime($date)) ?></span>
            <button type="submit" class="btn-save">&#10003; Save Attendance</button>
        </div>
        <?php endif; ?>
    </div>
    </form>

</div>


<!-- History Section -->
<section class="history-section">
    <div class="history-head">
        <h3>&#128200; Attendance History</h3>
        <form method="get" action="/B/attendance" class="hist-filter" id="hist-date-form">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($histFrom) ?>" max="<?= $today ?>">
            <label>To</label>
            <input type="date" name="to"   value="<?= htmlspecialchars($histTo)   ?>" max="<?= $today ?>">
            <button type="submit" class="go-sm">Filter</button>
        </form>
    </div>

    <?php if (empty($history)): ?>
    <div class="empty-hist">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="17" y2="13"/><line x1="7" y1="17" x2="13" y2="17"/></svg>
        <p>No attendance records found for the selected period.</p>
    </div>
    <?php else: ?>

    <!-- ── Search & Filter bar ───────────────────────────────────── -->
    <div class="hist-search-bar">
        <!-- Name search -->
        <div class="hist-search-input-wrap">
            <svg class="hist-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" id="hist-name-search" class="hist-search-input"
                   placeholder="Search by name…" autocomplete="off">
            <button type="button" id="hist-search-clear" class="hist-clear-btn" hidden aria-label="Clear search">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Staff type filter -->
        <div class="hist-filter-group">
            <label class="hist-filter-label" for="hist-type-filter">Type</label>
            <select id="hist-type-filter" class="hist-filter-select">
                <option value="all">All Staff</option>
                <option value="driver">Driver</option>
                <option value="conductor">Conductor</option>
            </select>
        </div>

        <!-- Status filter -->
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

        <!-- Result count badge -->
        <span class="hist-result-count" id="hist-result-count"></span>
    </div>

    <style>
    /* ── History search/filter bar ─────────────────────────────────── */
    .hist-search-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 22px;
        background: #FFFDF6;
        border-bottom: 1px solid #e8d39a;
        flex-wrap: wrap;
    }
    .hist-search-input-wrap {
        position: relative;
        flex: 1;
        min-width: 180px;
    }
    .hist-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        pointer-events: none;
    }
    .hist-search-input {
        width: 100%;
        padding: 8px 32px 8px 34px;
        border: 1.5px solid #e8d39a;
        border-radius: 8px;
        font-size: .85rem;
        background: #fff;
        color: #2b2b2b;
        box-sizing: border-box;
        transition: border-color .18s, box-shadow .18s;
    }
    .hist-search-input:focus {
        outline: none;
        border-color: #f3b944;
        box-shadow: 0 0 0 3px rgba(243,185,68,.18);
    }
    .hist-clear-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #9CA3AF;
        padding: 2px;
        display: flex;
        align-items: center;
    }
    .hist-clear-btn:hover { color: #80143c; }
    .hist-filter-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .hist-filter-label {
        font-size: .78rem;
        font-weight: 700;
        color: #80143c;
        white-space: nowrap;
    }
    .hist-filter-select {
        border: 1.5px solid #e8d39a;
        border-radius: 8px;
        padding: 7px 10px;
        font-size: .82rem;
        background: #fff;
        color: #2b2b2b;
        cursor: pointer;
        font-weight: 500;
    }
    .hist-filter-select:focus {
        outline: none;
        border-color: #f3b944;
        box-shadow: 0 0 0 3px rgba(243,185,68,.18);
    }
    .hist-result-count {
        margin-left: auto;
        font-size: .75rem;
        font-weight: 700;
        color: #80143c;
        background: #fce8ef;
        border: 1px solid #f9a8c0;
        border-radius: 999px;
        padding: 3px 10px;
        white-space: nowrap;
    }
    /* No-results row */
    .hist-no-results td {
        text-align: center;
        padding: 28px;
        color: #9ca3af;
        font-size: .88rem;
        font-style: italic;
    }
    </style>

    <div style="overflow-x:auto;">
    <table class="history-table" id="hist-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Name</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="hist-tbody">
        <?php foreach ($history as $h):
            $pillClass = 'pill-' . strtolower($h['status']);
            $typeClass = 'type-' . strtolower($h['staff_type']);
        ?>
        <tr data-name="<?= strtolower(htmlspecialchars($h['full_name'] ?? '')) ?>"
            data-type="<?= strtolower($h['staff_type']) ?>"
            data-status="<?= strtolower(str_replace(' ','_',$h['status'])) ?>">
            <td><?= date('d M Y', strtotime($h['work_date'])) ?></td>
            <td><span class="type-badge <?= $typeClass ?>"><?= $h['staff_type'] ?></span></td>
            <td style="font-weight:600;"><?= htmlspecialchars($h['full_name'] ?? '—') ?></td>
            <td><span class="status-pill <?= $pillClass ?>"><?= str_replace('_',' ',$h['status']) ?></span></td>
            <td style="color:#64748b;"><?= htmlspecialchars($h['notes'] ?? '') ?: '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <script>
    (function () {
        var searchInput  = document.getElementById('hist-name-search');
        var clearBtn     = document.getElementById('hist-search-clear');
        var typeFilter   = document.getElementById('hist-type-filter');
        var statusFilter = document.getElementById('hist-status-filter');
        var countBadge   = document.getElementById('hist-result-count');
        var tbody        = document.getElementById('hist-tbody');

        if (!tbody) return;

        var allRows = Array.from(tbody.querySelectorAll('tr[data-name]'));
        var noResRow = null; // injected when needed

        function filterRows() {
            var term   = searchInput.value.toLowerCase().trim();
            var type   = typeFilter.value;
            var status = statusFilter.value;
            var visible = 0;

            allRows.forEach(function (row) {
                var nameMatch   = !term   || row.dataset.name.includes(term);
                var typeMatch   = type   === 'all' || row.dataset.type   === type;
                var statusMatch = status === 'all' || row.dataset.status === status;
                var show = nameMatch && typeMatch && statusMatch;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Update count badge
            countBadge.textContent = visible + ' record' + (visible !== 1 ? 's' : '');

            // Show/hide no-results row
            if (noResRow) noResRow.remove();
            if (visible === 0) {
                noResRow = document.createElement('tr');
                noResRow.className = 'hist-no-results';
                noResRow.innerHTML = '<td colspan="5">No records match your search.</td>';
                tbody.appendChild(noResRow);
            }

            // Clear button visibility
            clearBtn.hidden = !searchInput.value;
        }

        // Wire events
        searchInput.addEventListener('input', filterRows);
        typeFilter.addEventListener('change', filterRows);
        statusFilter.addEventListener('change', filterRows);
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.hidden = true;
            filterRows();
            searchInput.focus();
        });

        // Run on load to set initial count
        filterRows();
    })();
    </script>

    <?php endif; ?>
</section>

<script>
// Sync the select colour class when the user changes it
function syncClass(sel) {
    const map = {'Present':'is-present','Absent':'is-absent','Late':'is-late','Half_Day':'is-half'};
    sel.className = 'att-select ' + (map[sel.value] || '');
}

// ── Entrance animations ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // 1. Count-up animation on .val elements
    document.querySelectorAll('.att-stat-card .val').forEach(function (el) {
        var raw  = el.textContent.trim();
        var isPct = raw.endsWith('%');
        var target = parseInt(raw, 10);
        if (isNaN(target) || target === 0) return;

        el.textContent = isPct ? '0%' : '0';

        // start after the card's entrance animation delay
        var cardDelay = parseFloat(getComputedStyle(el.closest('.att-stat-card')).animationDelay) || 0;
        setTimeout(function () {
            var duration = 700;
            var start    = performance.now();
            function step(now) {
                var progress = Math.min((now - start) / duration, 1);
                // ease-out cubic
                var eased = 1 - Math.pow(1 - progress, 3);
                var cur   = Math.round(eased * target);
                el.textContent = isPct ? cur + '%' : cur;
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }, cardDelay * 1000 + 100);
    });

    // 2. Trigger progress bars after their card animates in
    document.querySelectorAll('.pct-bar').forEach(function (bar) {
        var targetWidth = bar.style.width; // e.g. "72%"
        bar.style.width = '0';
        var card = bar.closest('.att-stat-card');
        var delay = parseFloat(getComputedStyle(card).animationDelay) || 0;
        setTimeout(function () {
            bar.style.width = targetWidth;
        }, delay * 1000 + 200);
    });
});
</script>
