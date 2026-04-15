<?php
/* vars: rows, date, routes, buses, filters, last_sync, has_running */
$rows       = $rows       ?? [];
$date       = $date       ?? date('Y-m-d');
$filters    = $filters    ?? [];
$lastSync   = $last_sync  ?? date('H:i:s');
$hasRunning = $has_running ?? false;

$statusMeta = [
    'Planned'     => ['label' => 'Scheduled', 'cls' => 'st-planned'],
    'InProgress'  => ['label' => 'Running',   'cls' => 'st-running'],
    'Delayed'     => ['label' => 'Delayed',   'cls' => 'st-delayed'],
    'Completed'   => ['label' => 'Completed', 'cls' => 'st-completed'],
    'Cancelled'   => ['label' => 'Cancelled', 'cls' => 'st-cancelled'],
];

function fmtTime(?string $t): string {
    if (!$t) return '—';
    return substr($t, 0, 5);
}
function fmtLastUpdated(?string $ts): string {
    if (!$ts) return '—';
    $ts = trim($ts);
    if (!$ts || $ts === '0000-00-00 00:00:00') return '—';
    $t = strtotime($ts);
    if (!$t) return '—';
    $diff = time() - $t;
    if ($diff < 60)   return 'Just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return date('d M H:i', $t);
}
?>
<style>
/* ── Trip Logs – Live Feed ──────────────────────── */
.tl-page { display: grid; gap: 18px; }

/* Hero */
.tl-hero {
    background: linear-gradient(135deg, #7B1C3E 0%, #a8274e 100%);
    border-bottom: 4px solid #f3b944;
    border-radius: 14px; color: #fff;
    padding: 24px 28px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.tl-hero-left { display: flex; align-items: center; gap: 14px; }
.tl-hero h1 { margin: 0; font-size: 1.45rem; font-weight: 800; }
.tl-hero p  { margin: 4px 0 0; opacity: .8; font-size: .88rem; }

/* LIVE badge */
.live-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #16a34a; border: 2px solid rgba(255,255,255,.5);
    border-radius: 99px; padding: 4px 12px;
    font-size: .75rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #fff;
    box-shadow: 0 0 0 0 rgba(22,163,74,.7);
    animation: livePulseOuter 2s ease-in-out infinite;
}
@keyframes livePulseOuter {
    0%,100% { box-shadow: 0 0 0 0 rgba(22,163,74,.6); }
    50%      { box-shadow: 0 0 0 6px rgba(22,163,74,0); }
}
.live-dot {
    width: 7px; height: 7px; border-radius: 50%; background: #fff;
    animation: liveDot 1.2s ease-in-out infinite;
}
@keyframes liveDot { 0%,100%{opacity:1} 50%{opacity:.3} }

/* Live Feed indicator */
.live-feed-pill {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,.12); border: 1.5px solid rgba(255,255,255,.3);
    border-radius: 12px; padding: 8px 16px;
    font-size: .82rem; font-weight: 600; color: #fff; white-space: nowrap;
}
.live-feed-pill .sync-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #4ade80;
    flex-shrink: 0; animation: liveDot 1.5s ease-in-out infinite;
}
.live-feed-pill .sync-label { opacity: .75; font-size: .74rem; }
.live-feed-pill .countdown  { font-variant-numeric: tabular-nums; min-width: 30px; }

/* Filter bar */
.tl-filters {
    background: #fff; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07);
    padding: 12px 16px;
    display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end;
}
.tl-filters label { display: grid; gap: 3px; }
.tl-filters .fl-label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #7B1C3E; }
.tl-filters input[type=date],
.tl-filters input[type=time],
.tl-filters select {
    border: 1.5px solid #e8d39a; border-radius: 8px;
    padding: 7px 10px; font-size: .83rem; background: #fffdf6; color: #2b2b2b;
    transition: border-color .18s;
}
.tl-filters input:focus, .tl-filters select:focus { outline: none; border-color: #f3b944; box-shadow: 0 0 0 3px rgba(243,185,68,.18); }
.tl-filters .fl-apply {
    background: #7B1C3E; color: #fff; border: none; border-radius: 8px;
    padding: 8px 18px; font-size: .85rem; font-weight: 700; cursor: pointer;
    white-space: nowrap; transition: background .2s; align-self: flex-end;
}
.tl-filters .fl-apply:hover { background: #a8274e; }
.tl-filters .fl-reset {
    background: #f3f4f6; color: #4b5563; border: 1.5px solid #e5e7eb;
    border-radius: 8px; padding: 8px 14px; font-size: .85rem; font-weight: 700;
    cursor: pointer; white-space: nowrap; text-decoration: none; align-self: flex-end;
    transition: background .2s;
}
.tl-filters .fl-reset:hover { background: #e5e7eb; }

/* Table card */
.tl-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 8px 24px rgba(17,24,39,.08); overflow: hidden;
}
.tl-card-head {
    background: linear-gradient(90deg, #7B1C3E, #a8274e);
    border-bottom: 3px solid #f3b944;
    color: #fff; padding: 13px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.tl-card-head h2 { margin: 0; font-size: .98rem; font-weight: 800; }
.tl-card-head .meta { font-size: .78rem; opacity: .8; }

/* Auto-refresh notice */
.tl-refresh-bar {
    display: flex; align-items: center; gap: 8px;
    background: #f0fdf4; border-bottom: 1px solid #bbf7d0;
    padding: 8px 20px; font-size: .8rem; color: #15803d; font-weight: 600;
}
.tl-refresh-bar svg { flex-shrink: 0; }

/* Table */
.tl-table-wrap { overflow-x: auto; }
.tl-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.tl-table thead th {
    background: #7B1C3E; color: #fff;
    padding: 11px 14px; font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    text-align: left; white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.1);
}
.tl-table thead th:last-child { border-right: none; }
.tl-table tbody td {
    padding: 11px 14px; border-bottom: 1px solid #fdf3e3;
    vertical-align: middle; font-size: .86rem; color: #1f2937;
}
.tl-table tbody tr:last-child td { border-bottom: none; }
.tl-table tbody tr:hover td { background: #fffdf6; }

/* Running row highlight */
.tl-table tbody tr.row-running td { background: #f0fdf4; }
.tl-table tbody tr.row-running:hover td { background: #dcfce7; }

/* Delayed row highlight */
.tl-table tbody tr.row-delayed td { background: #fff7ed; }
.tl-table tbody tr.row-delayed:hover td { background: #ffedd5; }

/* Bus badge */
.tl-bus {
    font-family: 'Courier New', monospace; font-weight: 900;
    font-size: .88rem; color: #7B1C3E;
    background: #fce8ef; border-radius: 6px; padding: 3px 8px;
    white-space: nowrap; display: inline-block;
}

/* Turn badge */
.tl-turn {
    display: inline-block; min-width: 28px; text-align: center;
    background: #eff6ff; color: #1d4ed8; border-radius: 6px;
    padding: 3px 8px; font-weight: 800; font-size: .82rem;
}

/* Time cell */
.tl-time { font-variant-numeric: tabular-nums; font-weight: 600; }
.tl-time-actual { color: #1f2937; }
.tl-time-none { color: #d1d5db; }

/* Driver */
.tl-driver { font-weight: 600; color: #374151; }
.tl-driver-none { color: #9ca3af; font-style: italic; }

/* Status badges */
.st-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 99px;
    font-size: .73rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em;
    white-space: nowrap;
}
.st-planned   { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.st-running   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.st-delayed   { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.st-completed { background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
.st-cancelled { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Running pulse dot */
@keyframes runPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.85)} }
.st-dot {
    width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0;
}
.st-running .st-dot { animation: runPulse 1s ease-in-out infinite; }

/* Last updated */
.tl-updated { font-size: .78rem; color: #6b7280; white-space: nowrap; }

/* ID chip */
.tl-id { font-size: .72rem; color: #9ca3af; font-variant-numeric: tabular-nums; }

/* Empty */
.tl-empty {
    padding: 40px; text-align: center; color: #9ca3af;
    display: flex; flex-direction: column; align-items: center; gap: 10px;
}
.tl-empty p { margin: 0; font-size: .9rem; }

@media(max-width:768px) {
    .tl-hero { flex-direction: column; align-items: flex-start; }
    .tl-filters { gap: 6px; }
}
</style>

<div class="tl-page">

<!-- Hero -->
<section class="tl-hero">
    <div class="tl-hero-left">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
                <h1>SLTB Trip Logs</h1>
                <span class="live-badge"><span class="live-dot"></span>LIVE</span>
            </div>
            <p>Real-time trip tracking from SLTB Timekeeper entries &bull; <?= date('d M Y', strtotime($date)) ?></p>
        </div>
    </div>
    <div class="live-feed-pill">
        <span class="sync-dot"></span>
        <div>
            <div class="sync-label">Last sync</div>
            <div><strong><?= htmlspecialchars($lastSync) ?></strong>
            <?php if ($hasRunning): ?>
            &mdash; next in <span class="countdown" id="countdown">30s</span>
            <?php else: ?>
            &mdash; <span style="opacity:.7;">no active trips</span>
            <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Filter bar -->
<div class="tl-filters">
    <label>
        <span class="fl-label">Date</span>
        <input type="date" id="fl-date" value="<?= htmlspecialchars($date) ?>" max="<?= date('Y-m-d') ?>">
    </label>
    <label>
        <span class="fl-label">Route</span>
        <select id="fl-route">
            <option value="">All routes</option>
            <?php foreach (($routes ?? []) as $r): ?>
            <option value="<?= htmlspecialchars($r['route_id']) ?>"
                <?= (!empty($filters['route']) && $filters['route'] == $r['route_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['route_no'] . (isset($r['name']) ? ' — ' . $r['name'] : '')) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span class="fl-label">Bus</span>
        <select id="fl-bus">
            <option value="">All buses</option>
            <?php foreach (($buses ?? []) as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"
                <?= (!empty($filters['bus_id']) && $filters['bus_id'] == $b['reg_no']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['reg_no'] . (isset($b['make']) ? ' ' . $b['make'] : '')) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span class="fl-label">Status</span>
        <select id="fl-status">
            <option value="">Any status</option>
            <option value="Planned"    <?= ($filters['status'] ?? '') === 'Planned'    ? 'selected' : '' ?>>Scheduled</option>
            <option value="InProgress" <?= ($filters['status'] ?? '') === 'InProgress' ? 'selected' : '' ?>>Running</option>
            <option value="Delayed"    <?= ($filters['status'] ?? '') === 'Delayed'    ? 'selected' : '' ?>>Delayed</option>
            <option value="Completed"  <?= ($filters['status'] ?? '') === 'Completed'  ? 'selected' : '' ?>>Completed</option>
            <option value="Cancelled"  <?= ($filters['status'] ?? '') === 'Cancelled'  ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </label>
    <label>
        <span class="fl-label">Sched. Dep From</span>
        <input type="time" id="fl-dep" value="<?= htmlspecialchars($filters['departure_time'] ?? '') ?>">
    </label>
    <button class="fl-apply" id="fl-apply-btn">Apply</button>
    <a class="fl-reset" href="/O/trip_logs">Reset</a>
</div>

<!-- Table card -->
<div class="tl-card">
    <div class="tl-card-head">
        <h2>&#128203; Trip Feed</h2>
        <span class="meta"><?= count($rows) ?> trip<?= count($rows) !== 1 ? 's' : '' ?> &bull; <?= date('d M Y', strtotime($date)) ?></span>
    </div>

    <?php if ($hasRunning): ?>
    <div class="tl-refresh-bar">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Running trips detected &mdash; page auto-refreshes every 30 seconds.
    </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
    <div class="tl-empty">
        <svg width="48" height="48" fill="none" stroke="#e5e7eb" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <p>No trips found for <?= date('d M Y', strtotime($date)) ?>.</p>
    </div>
    <?php else: ?>
    <div class="tl-table-wrap">
    <table class="tl-table">
        <thead>
            <tr>
                <th>Bus No</th>
                <th>Route</th>
                <th>Driver</th>
                <th>Turn</th>
                <th>Sched. Dep</th>
                <th>Actual Dep</th>
                <th>Sched. Arr</th>
                <th>Actual Arr</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $status   = (string)($r['status'] ?? 'Planned');
            $meta     = $statusMeta[$status] ?? ['label'=>$status,'cls'=>'st-planned'];
            $isRunning = $status === 'InProgress';
            $isDelayed = $status === 'Delayed';
            $driver   = (string)($r['driver'] ?? '—');
        ?>
        <tr class="<?= $isRunning ? 'row-running' : ($isDelayed ? 'row-delayed' : '') ?>">
            <td><span class="tl-bus"><?= htmlspecialchars((string)($r['bus_id'] ?? '-')) ?></span></td>
            <td style="font-weight:700;"><?= htmlspecialchars((string)($r['route'] ?? '—')) ?></td>
            <td>
                <?php if ($driver && $driver !== '—'): ?>
                <span class="tl-driver"><?= htmlspecialchars($driver) ?></span>
                <?php else: ?>
                <span class="tl-driver-none">Not assigned</span>
                <?php endif; ?>
            </td>
            <td><span class="tl-turn"><?= htmlspecialchars((string)($r['turn_number'] ?? '—')) ?></span></td>
            <td>
                <?php $sd = fmtTime($r['scheduled_dep'] ?? null); ?>
                <?php if ($sd !== '—'): ?>
                <span class="tl-time"><?= htmlspecialchars($sd) ?></span>
                <?php else: ?>
                <span class="tl-time-none">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php $ad = fmtTime($r['actual_dep'] ?? null); ?>
                <?php if ($ad !== '—'): ?>
                <span class="tl-time tl-time-actual"><?= htmlspecialchars($ad) ?></span>
                <?php else: ?>
                <span class="tl-time-none">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php $sa = fmtTime($r['scheduled_arr'] ?? null); ?>
                <?php if ($sa !== '—'): ?>
                <span class="tl-time"><?= htmlspecialchars($sa) ?></span>
                <?php else: ?>
                <span class="tl-time-none">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php $aa = fmtTime($r['actual_arr'] ?? null); ?>
                <?php if ($aa !== '—'): ?>
                <span class="tl-time tl-time-actual"><?= htmlspecialchars($aa) ?></span>
                <?php else: ?>
                <span class="tl-time-none">—</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="st-badge <?= $meta['cls'] ?>">
                    <?php if ($isRunning): ?><span class="st-dot"></span><?php endif; ?>
                    <?= htmlspecialchars($meta['label']) ?>
                </span>
            </td>
            <td>
                <span class="tl-updated"><?= htmlspecialchars(fmtLastUpdated($r['last_updated'] ?? null)) ?></span>
                <div class="tl-id">#<?= (int)($r['timekeeper_id'] ?? 0) ?></div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.tl-page -->

<script>
(function () {
    /* ── Filter apply ── */
    document.getElementById('fl-apply-btn').addEventListener('click', function () {
        var p = new URLSearchParams({
            date:           document.getElementById('fl-date').value   || '',
            route:          document.getElementById('fl-route').value  || '',
            bus_id:         document.getElementById('fl-bus').value    || '',
            departure_time: document.getElementById('fl-dep').value    || '',
            status:         document.getElementById('fl-status').value || '',
        });
        window.location.href = '/O/trip_logs?' + p.toString();
    });

    /* ── Auto-refresh every 30 s when running trips exist ── */
    var hasRunning = <?= $hasRunning ? 'true' : 'false' ?>;
    var countdown  = document.getElementById('countdown');

    if (hasRunning) {
        var secs = 30;
        var tick = setInterval(function () {
            secs--;
            if (countdown) countdown.textContent = secs + 's';
            if (secs <= 0) {
                clearInterval(tick);
                window.location.reload();
            }
        }, 1000);
    }
})();
</script>
