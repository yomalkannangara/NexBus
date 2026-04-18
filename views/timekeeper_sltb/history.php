<?php
$S         = $S         ?? [];
$histRows  = $hist_rows ?? [];
$histBuses = $hist_buses ?? [];

function tke_hist_delay_text(int $seconds): string {
    if ($seconds <= 0) {
        return '—';
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
:root { --maroon:#7B1C3E; --maroonDark:#5a1530; --gold:#f3b944; }

.tke-hero {
    background: linear-gradient(135deg, var(--maroon) 0%, #a8274e 100%);
    border-bottom: 4px solid var(--gold);
    border-radius: 14px; color: #fff;
    padding: 22px 26px 18px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    margin-bottom: 18px;
}
.tke-hero h1 { margin: 0; font-size: 1.4rem; font-weight: 800; }
.tke-hero p  { margin: 4px 0 0; opacity: .8; font-size: .86rem; }
.tke-hero-badge {
    background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.3);
    color: #fff; padding: 6px 14px; border-radius: 99px;
    font-size: .78rem; font-weight: 700; letter-spacing: .04em;
}

.tke-hist-filter {
    background: #fff; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07);
    border-left: 4px solid var(--gold);
    padding: 12px 18px; margin-bottom: 14px;
}
.tke-hist-filter form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
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
.tke-table { width: 100%; border-collapse: collapse; min-width: 860px; }
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
.tke-empty { padding: 40px; text-align: center; color: #9ca3af; }
.tke-empty p { margin: 0; font-size: .9rem; }

.hist-badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: .72rem; font-weight: 800; }
.hist-completed { background: #dcfce7; color: #14532d; }
.hist-delayed   { background: #ffedd5; color: #9a3412; }
.hist-cancelled { background: #fee2e2; color: #991b1b; }
.hist-absent    { background: #e5e7eb; color: #374151; }

@media(max-width:700px){ .tke-hist-filter form { gap: 8px; } }
</style>

<!-- ══ HERO ═══════════════════════════════════════════════════════════ -->
<div class="tke-hero">
    <div>
        <h1>&#128652; SLTB Timekeeper Portal</h1>
        <p><?= htmlspecialchars($S['depot_name'] ?? 'Depot') ?> — National Transport Commission</p>
    </div>
    <div><span class="tke-hero-badge">Trip History</span></div>
</div>

<!-- ══ FILTER ═════════════════════════════════════════════════════════ -->
<div class="tke-hist-filter">
    <form method="get" action="/TS/history">
        <div class="tke-hist-field">
            <span class="tke-hist-label">From</span>
            <input type="date" name="h_from" value="<?= htmlspecialchars($h_from ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="tke-hist-field">
            <span class="tke-hist-label">To</span>
            <input type="date" name="h_to" value="<?= htmlspecialchars($h_to ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
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

<!-- ══ TABLE ══════════════════════════════════════════════════════════ -->
<div class="tke-card">
    <div class="tke-card-head">
        <h2>Trip History</h2>
        <span class="meta">
            <?= count($histRows) ?> record<?= count($histRows) !== 1 ? 's' : '' ?>
            <?php if (($h_from ?? date('Y-m-d')) === date('Y-m-d') && ($h_to ?? date('Y-m-d')) === date('Y-m-d')): ?>
            &nbsp;·&nbsp;<span id="tkeAutoRefreshLabel" style="font-size:.7rem;opacity:.75;">Auto-refresh in <span id="tkeCountdown">30</span>s</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="tke-wrap">
    <table class="tke-table">
        <thead><tr>
            <th>Date</th><th>Bus No</th><th>Route</th><th>Turn</th>
            <th>Dep Time</th><th>Arr Time</th><th>Start Delay</th><th>End Delay</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php if (empty($histRows)): ?>
        <tr><td colspan="9" class="tke-empty">
            <p>No records found for the selected range.</p>
        </td></tr>
        <?php else: foreach ($histRows as $hr):
            $hs = $hr['ui_status'] ?? '';
            $hBadgeCls = match($hs) {
                'Completed' => 'hist-completed',
                'Delayed'   => 'hist-delayed',
                'Cancelled' => 'hist-cancelled',
                default     => 'hist-absent',
            };
        ?>
        <tr>
            <td style="white-space:nowrap;"><?= htmlspecialchars((string)($hr['date'] ?? '')) ?></td>
            <td><a class="bus-link" href="/TS/dashboard?focus_bus=<?= urlencode((string)($hr['bus_reg_no'] ?? '')) ?>">
                <?= htmlspecialchars((string)($hr['bus_reg_no'] ?? '—')) ?>
            </a></td>
            <td>
                <div style="font-weight:700;"><?= htmlspecialchars($hr['route_no'] ?? '') ?></div>
                <div style="font-size:.74rem;color:#6b7280;"><?= htmlspecialchars($hr['route_name'] ?? '') ?></div>
            </td>
            <td class="mono"><?= (int)($hr['turn_no'] ?? 0) > 0 ? (int)$hr['turn_no'] : '—' ?></td>
            <td class="mono"><?= htmlspecialchars($hr['dep_time'] ?? '—') ?></td>
            <td class="mono"><?= htmlspecialchars($hr['arr_time'] ?? '—') ?></td>
            <td class="mono"><?= htmlspecialchars(tke_hist_delay_text((int)($hr['start_delay_seconds'] ?? 0))) ?></td>
            <td class="mono"><?= htmlspecialchars(tke_hist_delay_text((int)($hr['end_delay_seconds'] ?? 0))) ?></td>
            <td>
                <span class="hist-badge <?= $hBadgeCls ?>"><?= htmlspecialchars($hs) ?></span>
                <?php if (!empty($hr['cancel_reason']) && $hs === 'Cancelled'): ?>
                <div style="font-size:.7rem;color:#6b7280;margin-top:3px;">
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

<?php
$isViewingToday = (($h_from ?? date('Y-m-d')) === date('Y-m-d')) && (($h_to ?? date('Y-m-d')) === date('Y-m-d'));
if ($isViewingToday):
?>
<script>
(function () {
    var remaining = 30;
    var countdownEl = document.getElementById('tkeCountdown');

    var timer = setInterval(function () {
        remaining--;
        if (countdownEl) countdownEl.textContent = String(remaining);
        if (remaining <= 0) {
            clearInterval(timer);
            // Preserve current filter params on reload
            location.reload();
        }
    }, 1000);

    // Stop countdown if user is hovering the filter form (about to change dates)
    var filterForm = document.querySelector('.tke-hist-filter form');
    if (filterForm) {
        filterForm.addEventListener('mouseenter', function () { clearInterval(timer); if (countdownEl) countdownEl.parentElement.textContent = 'Auto-refresh paused'; });
    }
})();
</script>
<?php endif; ?>
