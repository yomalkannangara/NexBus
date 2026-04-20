<?php
/* vars: rows, upcoming, S, location */
$rows = $rows ?? [];
$upcoming = $upcoming ?? [];
$S = $S ?? ['depot_name' => 'Operator'];
$location = trim((string)($location ?? 'Common'));

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

function tke_delay_text(int $seconds): string {
    if ($seconds <= 0) {
        return 'On time';
    }
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    $parts = [];
    if ($h > 0) {
        $parts[] = $h . 'h';
    }
    if ($m > 0 || $h > 0) {
        $parts[] = $m . 'm';
    }
    $parts[] = $s . 's';
    return '+' . implode(' ', $parts);
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
.tke-table { width: 100%; border-collapse: collapse; min-width: 660px; }
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
.tke-table tbody tr.tke-row-current td { background: #fff4cc; }
.tke-table tbody tr.tke-row-current:hover td { background: #ffeaa0; }
.tke-table .mono { font-family: 'Courier New', monospace; font-weight: 700; }
.tke-delay-note { font-size: .7rem; color: #9a3412; margin-top: 3px; font-weight: 700; }
.tke-current-note { font-size: .7rem; color: #92400e; margin-top: 3px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
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

</style>

<!-- ══ NOTIFICATION BAR ═══════════════════════════════════════════════ -->
<?php if (!empty($upcoming)): ?>
<div class="tke-notify-bar">
    <div class="tke-notify-bar__title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        Upcoming Departures (next 60 min)
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
        <h1>&#128652; Private Bus Timekeeper Portal</h1>
        <p>
            <?= htmlspecialchars($S['depot_name'] ?? 'Operator') ?>
            &bull; Stop: <?= htmlspecialchars($location !== '' ? $location : 'Common') ?>
        </p>
    </div>
    <div>
        <span class="tke-hero-badge">Bus Timekeeper</span>
    </div>
</div>

<!-- ══ SCHEDULE ══════════════════════════════════════════════════════ -->
<div>
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
            <th>Start Delay</th>
            <th>Crew</th>
            <th>Status</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="tke-empty">
            <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            <p>No trips scheduled for today.</p>
        </td></tr>
        <?php else: foreach ($rows as $r):
            $tripId = (int)($r['trip_id']      ?? 0);
            $ttId   = (int)($r['timetable_id'] ?? 0);
            $status = $r['ui_status'] ?? 'Scheduled';
            $turn   = (int)($r['turn_no']      ?? 0);
            $startDelaySec = (int)($r['start_delay_seconds'] ?? 0);
            $canManage = !empty($r['can_manage']);
            $isCurrentSchedule = !empty($r['is_current_schedule']);
        ?>
        <tr class="<?= $isCurrentSchedule ? 'tke-row-current' : '' ?>" data-trip-id="<?= $tripId ?>" data-tt-id="<?= $ttId ?>">
            <td><a class="bus-link tke-table" href="/TP/dashboard?focus_bus=<?= urlencode((string)($r['bus_reg_no'] ?? '')) ?>">
                <?= htmlspecialchars((string)($r['bus_reg_no'] ?? '—')) ?>
            </a></td>
            <td>
                <div style="font-weight:700;font-size:.85rem;"><?= htmlspecialchars($r['route_no'] ?? '') ?></div>
                <div style="font-size:.75rem;color:#6b7280;"><?= htmlspecialchars($r['route_name'] ?? '') ?></div>
            </td>
            <td class="mono"><?= $turn > 0 ? "Turn $turn" : '—' ?></td>
            <td class="mono"><?= htmlspecialchars(substr($r['sched_dep'] ?? '—', 0, 5)) ?></td>
            <td class="mono"><?= $startDelaySec > 0 ? htmlspecialchars(tke_delay_text($startDelaySec)) : '—' ?></td>
            <td>
                <?php
                $dName  = trim((string)($r['driver_name']    ?? ''));
                $dPhone = trim((string)($r['driver_phone']   ?? ''));
                $cName  = trim((string)($r['conductor_name'] ?? ''));
                $cPhone = trim((string)($r['conductor_phone'] ?? ''));
                if ($dName !== '' || $cName !== ''):?>
                <div style="font-size:.77rem;line-height:1.55;">
                    <?php if ($dName !== ''): ?>
                    <div>
                        <span style="font-weight:700;color:#374151;">🚗 <?= htmlspecialchars($dName) ?></span>
                        <?php if ($dPhone !== ''): ?>
                        <a href="tel:<?= htmlspecialchars($dPhone) ?>" style="margin-left:5px;color:#1d4ed8;font-size:.72rem;text-decoration:none;" title="Call driver">📞 <?= htmlspecialchars($dPhone) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($cName !== ''): ?>
                    <div>
                        <span style="font-weight:700;color:#374151;">🎫 <?= htmlspecialchars($cName) ?></span>
                        <?php if ($cPhone !== ''): ?>
                        <a href="tel:<?= htmlspecialchars($cPhone) ?>" style="margin-left:5px;color:#1d4ed8;font-size:.72rem;text-decoration:none;" title="Call conductor">📞 <?= htmlspecialchars($cPhone) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <span style="color:#9ca3af;font-size:.78rem;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?= tke_badge($status) ?>
                <?php if ($isCurrentSchedule): ?>
                <div class="tke-current-note">Current Scheduled Window</div>
                <?php endif; ?>
                <?php if ($startDelaySec > 0): ?>
                <div class="tke-delay-note">Started <?= htmlspecialchars(tke_delay_text($startDelaySec)) ?> late</div>
                <?php endif; ?>
            </td>
            <td>
                <div class="tke-actions">
                <?php if ($status === 'Scheduled'): ?>
                    <button class="tke-btn tke-btn-start" onclick="tkeStart(<?= $ttId ?>, this)">
                        &#9654; Start Journey
                    </button>
                <?php elseif ($canManage): ?>
                    <button class="tke-btn tke-btn-arrive" onclick="tkeArrive(<?= $tripId ?>, this)">
                        &#10003; Mark Arrived
                    </button>
                    <button class="tke-btn tke-btn-cancel" onclick="tkeOpenCancel(<?= $tripId ?>)">
                        &#215; Cancel Trip
                    </button>
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
    var _cancelTripId = 0;
    var HISTORY_REFRESH_KEY = 'nexbus:tp:history:refresh';

    function announceHistoryUpdate(eventType, tripId) {
        var payload = JSON.stringify({
            type: eventType,
            tripId: Number(tripId || 0),
            at: Date.now()
        });

        try {
            localStorage.setItem(HISTORY_REFRESH_KEY, payload);
        } catch (e) {
            // Ignore storage failures; the trip update itself already succeeded.
        }
    }

    function showToast(msg, type) {
        var t = document.createElement('div');
        t.className = 'tke-toast ' + (type || '');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 2200);
        setTimeout(function () { t.remove(); }, 2600);
    }

    var CSRF_TOKEN = '<?= htmlspecialchars($csrfToken ?? '') ?>';
    function postAction(data, onSuccess, btn) {
        if (btn) btn.disabled = true;
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        fd.append('csrf', CSRF_TOKEN);
        fetch('/TP/trip_entry', { method: 'POST', body: fd })
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

    window.tkeStart = function (ttId, btn) {
        btn.disabled = true;
        btn.textContent = 'Starting\u2026';
        postAction({ action: 'start', timetable_id: ttId }, function (res) {
            showToast('Trip started \u2014 Turn ' + (res.turn || ''), 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
    };

    window.tkeArrive = function (tripId, btn) {
        btn.disabled = true;
        btn.textContent = 'Marking\u2026';
        postAction({ action: 'arrive', trip_id: tripId }, function () {
            announceHistoryUpdate('arrive', tripId);
            showToast('Trip marked as Completed.', 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
    };

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
        btn.textContent = 'Cancelling\u2026';
        postAction({ action: 'cancel', trip_id: _cancelTripId, reason: full }, function () {
            announceHistoryUpdate('cancel', _cancelTripId);
            tkeCloseCancel();
            showToast('Trip cancelled.', 'success');
            setTimeout(function () { location.reload(); }, 900);
        }, btn);
        btn.textContent = 'Confirm Cancellation';
    };

    document.getElementById('tke-cancel-modal').addEventListener('click', function (e) {
        if (e.target === this) tkeCloseCancel();
    });
    document.getElementById('tke-cancel-reason').addEventListener('change', function () {
        this.style.borderColor = this.value ? '' : '#dc2626';
    });
})();
</script>