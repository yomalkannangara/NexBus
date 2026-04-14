<?php
/* vars: rows, point, upcoming, hist_rows, hist_buses, h_from, h_to, h_bus, active_tab */
$point     = $point     ?? 'start';
$rows      = $rows      ?? [];
$upcoming  = $upcoming  ?? [];
$histRows  = $hist_rows ?? [];
$histBuses = $hist_buses ?? [];
$activeTab = $active_tab ?? 'schedule';
$me        = $_SESSION['user'] ?? [];
$depotName = 'Depot';

$roleLabel = ($point === 'end') ? 'End-Point Timekeeper' : 'Start-Point Timekeeper';

/* ── Cancel reasons ── */
$cancelReasons = [
    'Driver absent',
    'Bus breakdown / mechanical fault',
    'Traffic obstruction',
    'Accident on route',
    'Bus not returned from previous trip',
    'Emergency — police/government order',
    'Weather conditions',
    'Other',
];

/* ── Badge map ── */
function tke_badge(string $status): string {
    return match($status) {
        'Scheduled' => '<span class="tke-badge tke-badge--blue">Scheduled</span>',
        'Running'   => '<span class="tke-badge tke-badge--green"><span class="tke-pulse"></span>Running</span>',
        'Delayed'   => '<span class="tke-badge tke-badge--orange">Delayed</span>',
        'Completed' => '<span class="tke-badge tke-badge--grey">Completed</span>',
        'Cancelled' => '<span class="tke-badge tke-badge--red">Cancelled</span>',
        'Absent'    => '<span class="tke-badge tke-badge--darkgrey">Absent</span>',
        default     => '<span class="tke-badge tke-badge--grey">'.htmlspecialchars($status).'</span>',
    };
}
?>

<style>
/* ── TKE (Trip Entry) styles ────────────────────────────────── */
:root { --maroon:#7B1C3E; --maroonDark:#5a1530; --gold:#f3b944; }

/* Notification bar */
.tke-notify-bar {
    background: linear-gradient(135deg, #fffbea 0%, #fff9e0 100%);
    border: 1px solid #fde68a; border-left: 4px solid var(--gold);
    border-radius: 10px; padding: 12px 18px;
    display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start;
}
.tke-notify-bar__title {
    font-size: .8rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .05em; color: #92400e; margin-bottom: 6px; width: 100%;
    display: flex; align-items: center; gap: 6px;
}
.tke-notify-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.tke-notify-pill {
    background: #fff; border: 1.5px solid #fcd34d; border-radius: 99px;
    padding: 4px 12px; font-size: .78rem; font-weight: 600; color: #78350f;
    display: flex; align-items: center; gap: 6px;
    transition: border-color .15s, box-shadow .15s;
}
.tke-notify-pill:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.tke-notify-pill .remind-badge {
    background: #f59e0b; color: #fff; font-size: .65rem; font-weight: 800;
    padding: 1px 6px; border-radius: 99px; text-transform: uppercase;
}

/* Hero */
.tke-hero {
    background: linear-gradient(135deg, var(--maroon) 0%, #a8274e 100%);
    border-bottom: 4px solid var(--gold);
    border-radius: 14px; color: #fff;
    padding: 22px 26px 18px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.tke-hero h1 { margin: 0; font-size: 1.4rem; font-weight: 800; }
.tke-hero p  { margin: 4px 0 0; opacity: .8; font-size: .86rem; }
.tke-hero-badge {
    background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.3);
    color: #fff; padding: 6px 14px; border-radius: 99px;
    font-size: .78rem; font-weight: 700; letter-spacing: .04em;
}

/* Tabs */
.tke-tabs {
    display: flex; gap: 4px; background: #f3f4f6;
    border-radius: 10px; padding: 4px; width: fit-content;
}
.tke-tab {
    padding: 8px 20px; border-radius: 7px; border: none; cursor: pointer;
    font-size: .85rem; font-weight: 700; color: #6b7280; background: transparent;
    transition: background .2s, color .2s, box-shadow .2s;
}
.tke-tab.active {
    background: #fff; color: var(--maroon);
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.tke-tab-panel { display: none; }
.tke-tab-panel.active { display: block; }

/* Table card */
.tke-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07); overflow: hidden;
}
.tke-card-head {
    background: linear-gradient(90deg, var(--maroon), #a8274e);
    border-bottom: 3px solid var(--gold);
    color: #fff; padding: 12px 18px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
}
.tke-card-head h2 { margin: 0; font-size: .95rem; font-weight: 800; }
.tke-card-head .meta { font-size: .76rem; opacity: .8; }
.tke-wrap { overflow-x: auto; }
.tke-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.tke-table thead th {
    background: var(--maroon); color: #fff;
    padding: 10px 14px; font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em; text-align: left; white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.1);
}
.tke-table thead th:last-child { border-right: none; }
.tke-table tbody td {
    padding: 10px 14px; border-bottom: 1px solid #fdf3e3;
    font-size: .86rem; color: #1f2937; vertical-align: middle;
}
.tke-table tbody tr:last-child td { border-bottom: none; }
.tke-table tbody tr:hover td { background: #fffdf6; }
.tke-table .mono { font-family: 'Courier New', monospace; font-weight: 700; }
.tke-table .bus-link {
    color: var(--maroon); font-weight: 700;
    text-decoration: underline; text-underline-offset: 2px;
}
.tke-table .bus-link:hover { color: var(--maroonDark); }

/* Badges */
.tke-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 99px;
    font-size: .72rem; font-weight: 800; letter-spacing: .03em; white-space: nowrap;
}
.tke-badge--blue     { background: #dbeafe; color: #1e40af; }
.tke-badge--green    { background: #dcfce7; color: #14532d; }
.tke-badge--orange   { background: #ffedd5; color: #9a3412; }
.tke-badge--grey     { background: #f3f4f6; color: #374151; }
.tke-badge--red      { background: #fee2e2; color: #991b1b; }
.tke-badge--darkgrey { background: #e5e7eb; color: #1f2937; }
.tke-pulse {
    width: 7px; height: 7px; border-radius: 50%; background: #16a34a; flex-shrink: 0;
    animation: tkePulse 1.4s infinite;
}
@keyframes tkePulse {
    0%,100%{ opacity:1; transform:scale(1); }
    50%{ opacity:.5; transform:scale(1.4); }
}

/* Action buttons */
.tke-actions { display: flex; flex-wrap: wrap; gap: 6px; }
.tke-btn {
    padding: 6px 13px; border-radius: 7px; border: none; cursor: pointer;
    font-size: .78rem; font-weight: 700; transition: background .18s, transform .12s, box-shadow .18s;
    white-space: nowrap;
}
.tke-btn:active { transform: scale(.96); }
.tke-btn-start  { background: #16a34a; color: #fff; }
.tke-btn-start:hover  { background: #15803d; box-shadow: 0 3px 10px rgba(22,163,74,.3); }
.tke-btn-arrive { background: #1d4ed8; color: #fff; }
.tke-btn-arrive:hover { background: #1e40af; box-shadow: 0 3px 10px rgba(29,78,216,.3); }
.tke-btn-cancel { background: #dc2626; color: #fff; }
.tke-btn-cancel:hover { background: #b91c1c; box-shadow: 0 3px 10px rgba(220,38,38,.3); }

/* Empty state */
.tke-empty { padding: 40px; text-align: center; color: #9ca3af; }
.tke-empty svg { margin-bottom: 12px; }
.tke-empty p { margin: 0; font-size: .9rem; }

/* History filter */
.tke-hist-filter {
    background: #fff; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07);
    border-left: 4px solid var(--gold);
    padding: 12px 18px; margin-bottom: 14px;
}
.tke-hist-filter form {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
}
.tke-hist-field { display: grid; gap: 3px; }
.tke-hist-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--maroon); }
.tke-hist-field input,
.tke-hist-field select {
    border: 1.5px solid #e8d39a; border-radius: 7px;
    padding: 7px 10px; font-size: .83rem; background: #fffdf6; color: #2b2b2b;
}
.tke-hist-field input:focus,
.tke-hist-field select:focus { outline: none; border-color: var(--gold); }
.tke-hist-submit {
    background: var(--maroon); color: #fff; border: none;
    padding: 8px 16px; border-radius: 7px; font-size: .83rem; font-weight: 700;
    cursor: pointer; align-self: flex-end;
}
.tke-hist-submit:hover { background: #a8274e; }

/* Hist status badges */
.hist-badge {
    display: inline-block; padding: 3px 10px; border-radius: 99px;
    font-size: .72rem; font-weight: 800;
}
.hist-completed { background: #dcfce7; color: #14532d; }
.hist-delayed   { background: #ffedd5; color: #9a3412; }
.hist-cancelled { background: #fee2e2; color: #991b1b; }
.hist-absent    { background: #e5e7eb; color: #374151; }

/* Cancel modal */
.tke-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
    z-index: 1000; align-items: center; justify-content: center;
}
.tke-modal-overlay.open { display: flex; }
.tke-modal {
    background: #fff; border-radius: 16px; width: 420px; max-width: 95%;
    overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.2);
    animation: tkeModalIn .2s ease;
}
@keyframes tkeModalIn { from{opacity:0;transform:scale(.92)} to{opacity:1;transform:scale(1)} }
.tke-modal-head {
    background: #dc2626; color: #fff; padding: 14px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.tke-modal-head h3 { margin: 0; font-size: .95rem; font-weight: 800; }
.tke-modal-head button { background: none; border: none; color: #fff; cursor: pointer; font-size: 1.2rem; line-height: 1; }
.tke-modal-body { padding: 18px; }
.tke-modal-body label { display: block; font-size: .78rem; font-weight: 700; color: #374151; margin-bottom: 5px; margin-top: 14px; }
.tke-modal-body label:first-child { margin-top: 0; }
.tke-modal-body select,
.tke-modal-body textarea {
    width: 100%; border: 1.5px solid #d1d5db; border-radius: 8px;
    padding: 8px 10px; font-size: .84rem; font-family: inherit;
    box-sizing: border-box;
}
.tke-modal-body select:focus,
.tke-modal-body textarea:focus { outline: none; border-color: #dc2626; }
.tke-modal-body textarea { resize: vertical; min-height: 70px; }
.tke-modal-foot {
    padding: 14px 18px; border-top: 1px solid #f1f5f9;
    display: flex; gap: 8px; justify-content: flex-end;
}
.tke-modal-cancel-btn { background: #f3f4f6; color: #374151; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.tke-modal-confirm-btn { background: #dc2626; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.tke-modal-confirm-btn:disabled { opacity: .5; cursor: not-allowed; }

/* Toast */
.tke-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    z-index: 2000; background: #1f2937; color: #fff;
    padding: 11px 22px; border-radius: 10px; font-size: .86rem; font-weight: 600;
    box-shadow: 0 6px 24px rgba(0,0,0,.2);
    animation: tkeSlideUp .25s ease;
    pointer-events: none;
}
.tke-toast.success { background: #16a34a; }
.tke-toast.error   { background: #dc2626; }
@keyframes tkeSlideUp { from{opacity:0;transform:translateX(-50%) translateY(12px)} to{opacity:1;transform:translateX(-50%) translateY(0)} }

@media(max-width:700px){
    .tke-bar-row { grid-template-columns: 1fr; }
    .tke-hist-filter form { gap: 8px; }
}
</style>

<!-- ══ NOTIFICATION BAR ═══════════════════════════════════════════════ -->
<?php if (!empty($upcoming)): ?>
<div class="tke-notify-bar">
    <div class="tke-notify-bar__title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <?= $point === 'start' ? 'Upcoming Departures' : 'Expected Arrivals' ?> (next 60 min)
    </div>
    <div class="tke-notify-pills">
        <?php foreach ($upcoming as $u): ?>
        <div class="tke-notify-pill">
            <strong><?= htmlspecialchars($u['route_no']) ?></strong>
            <?= htmlspecialchars($u['bus_reg_no']) ?> &bull; <?= htmlspecialchars($u['eta_label']) ?>
            <?php if ($u['reminder']): ?>
            <span class="remind-badge">10 min</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══ HERO ══════════════════════════════════════════════════════════ -->
<div class="tke-hero">
    <div>
        <h1>&#128652; Trip Entry</h1>
        <p>National Transport Commission — SLTB Timekeeper</p>
    </div>
    <div>
        <span class="tke-hero-badge"><?= htmlspecialchars($roleLabel) ?></span>
    </div>
</div>

<!-- ══ TABS ══════════════════════════════════════════════════════════ -->
<div class="tke-tabs">
    <button class="tke-tab <?= $activeTab === 'schedule' ? 'active' : '' ?>"
            onclick="tkeSwitchTab('schedule')">Turn Schedule</button>
    <button class="tke-tab <?= $activeTab === 'history' ? 'active' : '' ?>"
            onclick="tkeSwitchTab('history')">History</button>
</div>

<!-- ══ SCHEDULE TAB ═══════════════════════════════════════════════════ -->
<div id="tke-panel-schedule" class="tke-tab-panel <?= $activeTab === 'schedule' ? 'active' : '' ?>">
<div class="tke-card">
    <div class="tke-card-head">
        <h2>Today's Turn Schedule — <?= date('d M Y') ?></h2>
        <span class="meta"><?= count($rows) ?> entr<?= count($rows) === 1 ? 'y' : 'ies' ?></span>
    </div>
    <div class="tke-wrap">
    <table class="tke-table">
        <thead><tr>
            <th>Bus No</th>
            <th>Route</th>
            <th>Turn</th>
            <th>Scheduled Dep</th>
            <?php if ($point === 'end'): ?>
            <th>Expected Arr</th>
            <th>From Depot</th>
            <?php endif; ?>
            <th>Status</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="<?= $point === 'end' ? 8 : 6 ?>" class="tke-empty">
            <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            <p>No trips scheduled for today.</p>
        </td></tr>
        <?php else: foreach ($rows as $r):
            $tripId   = (int)($r['trip_id'] ?? 0);
            $ttId     = (int)($r['timetable_id'] ?? 0);
            $status   = $r['ui_status'] ?? 'Scheduled';
            $turn     = (int)($r['turn_no'] ?? 0);
        ?>
        <tr data-trip-id="<?= $tripId ?>" data-tt-id="<?= $ttId ?>">
            <td><a class="bus-link tke-table" href="/TS/dashboard?focus_bus=<?= urlencode((string)($r['bus_reg_no'] ?? '')) ?>">
                <?= htmlspecialchars((string)($r['bus_reg_no'] ?? '—')) ?>
            </a></td>
            <td>
                <div style="font-weight:700;font-size:.85rem;"><?= htmlspecialchars($r['route_no']) ?></div>
                <div style="font-size:.75rem;color:#6b7280;"><?= htmlspecialchars($r['route_name']) ?></div>
            </td>
            <td class="mono"><?= $turn > 0 ? "Turn $turn" : '—' ?></td>
            <td class="mono"><?= htmlspecialchars(substr($r['sched_dep'] ?? '—', 0, 5)) ?></td>
            <?php if ($point === 'end'): ?>
            <td class="mono"><?= htmlspecialchars(substr($r['sched_arr'] ?? '—', 0, 5)) ?></td>
            <td style="font-size:.78rem;color:#6b7280;"><?= htmlspecialchars((string)($r['origin_depot'] ?? '—')) ?></td>
            <?php endif; ?>
            <td><?= tke_badge($status) ?></td>
            <td>
                <div class="tke-actions">
                <?php if ($point === 'start'): ?>
                    <?php if ($status === 'Scheduled'): ?>
                        <button class="tke-btn tke-btn-start" onclick="tkeStart(<?= $ttId ?>)">
                            &#9654; Start Journey
                        </button>
                    <?php elseif ($status === 'Running' || $status === 'Delayed'): ?>
                        <button class="tke-btn tke-btn-cancel" onclick="tkeOpenCancel(<?= $tripId ?>)">
                            &#215; Cancel Trip
                        </button>
                    <?php endif; ?>
                <?php elseif ($point === 'end'): ?>
                    <?php if ($status === 'Running' || $status === 'Delayed'): ?>
                        <button class="tke-btn tke-btn-arrive" onclick="tkeArrive(<?= $tripId ?>, this)">
                            &#10003; Mark Arrived
                        </button>
                        <button class="tke-btn tke-btn-cancel" onclick="tkeOpenCancel(<?= $tripId ?>)">
                            &#215; Cancel Trip
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>

<!-- ══ HISTORY TAB ════════════════════════════════════════════════════ -->
<div id="tke-panel-history" class="tke-tab-panel <?= $activeTab === 'history' ? 'active' : '' ?>">

<div class="tke-hist-filter">
<form method="get" action="/TS/entry">
    <input type="hidden" name="tab" value="history">
    <div class="tke-hist-field">
        <span class="tke-hist-label">From</span>
        <input type="date" name="h_from" value="<?= htmlspecialchars($h_from) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="tke-hist-field">
        <span class="tke-hist-label">To</span>
        <input type="date" name="h_to" value="<?= htmlspecialchars($h_to) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="tke-hist-field">
        <span class="tke-hist-label">Bus No</span>
        <select name="h_bus">
            <option value="">All Buses</option>
            <?php foreach ($histBuses as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>" <?= ($h_bus ?? '') === $b ? 'selected' : '' ?>>
                <?= htmlspecialchars($b) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="tke-hist-submit">Filter</button>
</form>
</div>

<div class="tke-card">
    <div class="tke-card-head">
        <h2>Trip History</h2>
        <span class="meta"><?= count($histRows) ?> record<?= count($histRows) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="tke-wrap">
    <table class="tke-table" style="min-width:680px">
        <thead><tr>
            <th>Date</th><th>Bus No</th><th>Route</th><th>Turn</th>
            <th>Dep Time</th><th>Arr Time</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php if (empty($histRows)): ?>
        <tr><td colspan="7" class="tke-empty">
            <p>No records found for the selected range.</p>
        </td></tr>
        <?php else: foreach ($histRows as $hr):
            $hs = $hr['ui_status'] ?? '';
            $hBadgeCls = match($hs) {
                'Completed' => 'hist-completed',
                'Delayed'   => 'hist-delayed',
                'Cancelled' => 'hist-cancelled',
                'Absent'    => 'hist-absent',
                default     => 'hist-absent',
            };
        ?>
        <tr>
            <td style="white-space:nowrap;"><?= htmlspecialchars((string)($hr['date'] ?? '')) ?></td>
            <td><a class="bus-link tke-table" href="/TS/dashboard?focus_bus=<?= urlencode((string)($hr['bus_reg_no'] ?? '')) ?>">
                <?= htmlspecialchars((string)($hr['bus_reg_no'] ?? '—')) ?>
            </a></td>
            <td>
                <div style="font-weight:700;"><?= htmlspecialchars($hr['route_no'] ?? '') ?></div>
                <div style="font-size:.74rem;color:#6b7280;"><?= htmlspecialchars($hr['route_name'] ?? '') ?></div>
            </td>
            <td class="mono"><?= (int)$hr['turn_no'] > 0 ? (int)$hr['turn_no'] : '—' ?></td>
            <td class="mono"><?= htmlspecialchars($hr['dep_time'] ?? '—') ?></td>
            <td class="mono"><?= htmlspecialchars($hr['arr_time'] ?? '—') ?></td>
            <td>
                <span class="hist-badge <?= $hBadgeCls ?>"><?= htmlspecialchars($hs) ?></span>
                <?php if (!empty($hr['cancel_reason']) && $hs === 'Cancelled'): ?>
                <div style="font-size:.7rem;color:#6b7280;margin-top:3px;" title="<?= htmlspecialchars($hr['cancel_reason']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($hr['cancel_reason'], 0, 40, '…')) ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>

<!-- ══ CANCEL MODAL ═══════════════════════════════════════════════════ -->
<div class="tke-modal-overlay" id="tke-cancel-modal">
    <div class="tke-modal">
        <div class="tke-modal-head">
            <h3>&#x26A0;&#xFE0F; Cancel Trip</h3>
            <button onclick="tkeCloseCancel()" aria-label="Close">&times;</button>
        </div>
        <div class="tke-modal-body">
            <label for="tke-cancel-reason">Cancellation Reason <span style="color:#dc2626">*</span></label>
            <select id="tke-cancel-reason">
                <option value="">— Select reason —</option>
                <?php foreach ($cancelReasons as $cr): ?>
                <option value="<?= htmlspecialchars($cr) ?>"><?= htmlspecialchars($cr) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="tke-cancel-notes">Additional Notes (optional)</label>
            <textarea id="tke-cancel-notes" placeholder="Any extra details..."></textarea>
        </div>
        <div class="tke-modal-foot">
            <button class="tke-modal-cancel-btn" onclick="tkeCloseCancel()">Back</button>
            <button class="tke-modal-confirm-btn" id="tke-cancel-confirm" onclick="tkeSubmitCancel()">
                Confirm Cancellation
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    /* ── State ── */
    var _cancelTripId = 0;

    /* ── Tab switching ── */
    window.tkeSwitchTab = function (tab) {
        document.querySelectorAll('.tke-tab').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.tke-tab-panel').forEach(function (p) { p.classList.remove('active'); });
        document.querySelector('button[onclick="tkeSwitchTab(\'' + tab + '\')"]').classList.add('active');
        document.getElementById('tke-panel-' + tab).classList.add('active');
    };

    /* ── Toast ── */
    function showToast(msg, type) {
        var t = document.createElement('div');
        t.className = 'tke-toast ' + (type || '');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 2200);
        setTimeout(function () { t.remove(); }, 2600);
    }

    /* ── POST helper ── */
    function postAction(data, onSuccess, btn) {
        if (btn) btn.disabled = true;
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        fetch('/TS/entry', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (btn) btn.disabled = false;
                if (res.ok) {
                    onSuccess(res);
                } else {
                    showToast(res.msg || 'Action failed.', 'error');
                }
            })
            .catch(function () {
                if (btn) btn.disabled = false;
                showToast('Network error. Please try again.', 'error');
            });
    }

    /* ── Start Journey ── */
    window.tkeStart = function (ttId) {
        var btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Starting…';
        postAction({ action: 'start', timetable_id: ttId }, function (res) {
            showToast('Trip started — Turn ' + (res.turn || ''), 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
    };

    /* ── Mark Arrived ── */
    window.tkeArrive = function (tripId, btn) {
        btn.disabled = true;
        btn.textContent = 'Marking…';
        postAction({ action: 'arrive', trip_id: tripId }, function () {
            showToast('Trip marked as Completed.', 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
    };

    /* ── Cancel modal open/close ── */
    window.tkeOpenCancel = function (tripId) {
        _cancelTripId = tripId;
        document.getElementById('tke-cancel-reason').value = '';
        document.getElementById('tke-cancel-notes').value  = '';
        document.getElementById('tke-cancel-modal').classList.add('open');
    };
    window.tkeCloseCancel = function () {
        document.getElementById('tke-cancel-modal').classList.remove('open');
        _cancelTripId = 0;
    };

    /* ── Submit cancel ── */
    window.tkeSubmitCancel = function () {
        var reason = document.getElementById('tke-cancel-reason').value.trim();
        var notes  = document.getElementById('tke-cancel-notes').value.trim();
        if (!reason) {
            document.getElementById('tke-cancel-reason').style.borderColor = '#dc2626';
            return;
        }
        var full = reason + (notes ? ': ' + notes : '');
        var btn  = document.getElementById('tke-cancel-confirm');
        btn.disabled = true;
        btn.textContent = 'Cancelling…';
        postAction({ action: 'cancel', trip_id: _cancelTripId, reason: full }, function () {
            tkeCloseCancel();
            showToast('Trip cancelled.', 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
        btn.textContent = 'Confirm Cancellation';
    };

    /* Close modal on overlay click */
    document.getElementById('tke-cancel-modal').addEventListener('click', function (e) {
        if (e.target === this) tkeCloseCancel();
    });

    /* Highlight dropdown on change back to valid */
    document.getElementById('tke-cancel-reason').addEventListener('change', function () {
        this.style.borderColor = this.value ? '' : '#dc2626';
    });
})();
</script>
