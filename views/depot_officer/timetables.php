<?php
/* vars: regular_rows, special_rows, count_regular, count_special, depot_name, msg */
$regularRows  = $regular_rows ?? [];
$specialRows  = $special_rows ?? [];
$depotName    = $depot_name   ?? 'Colombo Depot';
$msg          = $msg          ?? null;

$dayMap = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

/**
 * Group flat timetable rows into one entry per (bus, route).
 * Collects unique departure times (sorted) → Turn 1/2/3
 * Collects unique days (sorted) → Days Active
 */
function groupTimetableRows(array $rows, string $typeLabel, array $dayMap): array {
    $groups = [];
    foreach ($rows as $r) {
        $key = ($r['bus_reg_no'] ?? '') . '||' . ($r['route_id'] ?? '0');
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'bus_reg_no'     => (string)($r['bus_reg_no']  ?? '-'),
                'route_no'       => (string)($r['route_no']    ?? '-'),
                'route_name'     => (string)($r['route_name']  ?? ''),
                'days'           => [],
                'departures'     => [],
                'type'           => $typeLabel,
                'effective_from' => $r['effective_from'] ?? null,
                'effective_to'   => $r['effective_to']   ?? null,
            ];
        }
        $dayIdx = (int)($r['day_of_week'] ?? -1);
        if ($dayIdx >= 0 && !in_array($dayIdx, $groups[$key]['days'], true)) {
            $groups[$key]['days'][] = $dayIdx;
        }
        $dep = substr((string)($r['departure_time'] ?? ''), 0, 5);
        if ($dep && !in_array($dep, $groups[$key]['departures'], true)) {
            $groups[$key]['departures'][] = $dep;
        }
    }
    foreach ($groups as &$g) {
        sort($g['days']);
        sort($g['departures']);
        $g['day_labels'] = array_map(fn($i) => $dayMap[$i], $g['days']);
    }
    unset($g);
    return array_values($groups);
}

$regularGroups = groupTimetableRows($regularRows, 'Regular', $dayMap);
$specialGroups = groupTimetableRows($specialRows, 'Special', $dayMap);
$allGroups = array_merge($regularGroups, $specialGroups);

// Collect unique routes for the route filter dropdown
$routeOptions = [];
foreach ($allGroups as $g) {
    $rk = (string)($g['route_no'] ?? '');
    if ($rk !== '' && !isset($routeOptions[$rk])) {
        $routeOptions[$rk] = $rk;
    }
}
ksort($routeOptions, SORT_NATURAL);
?>


<style>
/* ── Timetables Page ──────────────────────────────── */
.tt2-page { display: grid; gap: 20px; }

/* Hero */
.tt2-hero {
    background: linear-gradient(135deg, #7B1C3E 0%, #a8274e 100%);
    border-bottom: 4px solid #f3b944;
    border-radius: 14px;
    color: #fff;
    padding: 26px 30px 22px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.tt2-hero h1 { margin: 0; font-size: 1.5rem; font-weight: 800; }
.tt2-hero p  { margin: 4px 0 0; opacity: .82; font-size: .9rem; }
.tt2-depot-badge {
    background: rgba(255,255,255,.18); border: 1.5px solid rgba(255,255,255,.4);
    border-radius: 10px; padding: 8px 16px; font-size: .85rem; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
}

/* Filter bar */
.tt2-filters {
    background: #fff; border-radius: 14px;
    box-shadow: 0 4px 20px rgba(17,24,39,.09);
    border-left: 4px solid #f3b944;
    overflow: hidden;
}
.tt2-filter-section {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 13px 20px;
}
.tt2-filter-section + .tt2-filter-section {
    border-top: 1px solid #f3f4f6;
}
.tt2-filter-row {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.tt2-filter-label {
    font-size: .72rem; font-weight: 800; color: #7B1C3E;
    text-transform: uppercase; letter-spacing: .06em;
    white-space: nowrap; min-width: 70px;
}
/* Type radio pills */
.tt2-type-group { display:flex; gap:6px; flex-wrap:wrap; }
.tt2-type-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 99px; cursor: pointer;
    border: 2px solid #e5e7eb; background: #f9fafb;
    font-size: .82rem; font-weight: 700; color: #6b7280;
    transition: all .15s; user-select: none;
}
.tt2-type-pill:hover { border-color: #f3b944; color: #7B1C3E; }
.tt2-type-pill input[type=radio] { display: none; }
.tt2-type-pill.sel-all   { border-color: #7B1C3E; background: #fce8ef; color: #7B1C3E; }
.tt2-type-pill.sel-regular { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.tt2-type-pill.sel-special { border-color: #7c3aed; background: #f5f3ff; color: #5b21b6; }
/* Route select */
.tt2-route-select {
    border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 7px 30px 7px 10px;
    font-size: .82rem; font-weight: 600; color: #374151; background: #f9fafb;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 9px center;
    cursor: pointer; min-width: 130px;
}
.tt2-route-select:focus { outline: none; border-color: #7B1C3E; }
/* Day pills */
.tt2-day-filters { display:flex; gap:5px; flex-wrap:wrap; }
.tt2-day-toggle {
    padding: 5px 10px; border-radius: 6px; cursor: pointer; user-select: none;
    font-size: .75rem; font-weight: 700; border: 1.5px solid #e5e7eb;
    background: #f9fafb; color: #6b7280; transition: all .15s;
}
.tt2-day-toggle:hover { border-color: #bbf7d0; color: #15803d; }
.tt2-day-toggle.active { background: #f0fdf4; color: #15803d; border-color: #22c55e; }
/* Date range */
.tt2-period-wrap { display:flex; align-items:center; gap:8px; flex-wrap:nowrap; }
.tt2-period-wrap input[type=date] {
    border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 7px 10px;
    font-size: .82rem; color: #374151; background: #f9fafb;
    max-width: 160px; min-width: 120px;
}
.tt2-period-wrap input[type=date]:focus { outline: none; border-color: #7B1C3E; }
.tt2-period-sep { font-size: .8rem; color: #9ca3af; font-weight: 600; }
/* Clear btn */
.tt2-clear-btn {
    margin-left: auto; padding: 7px 16px; border-radius: 8px;
    border: 1.5px solid #e5e7eb; background: #f9fafb;
    font-size: .8rem; font-weight: 700; color: #6b7280; cursor: pointer;
    transition: all .15s; display: flex; align-items: center; gap: 6px;
}
.tt2-clear-btn:hover { border-color: #f3b944; color: #7B1C3E; background: #fffdf6; }

/* Filter divider */
.tt2-filter-divider { display: none; }

/* Result summary bar */
.tt2-summary-bar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    font-size: .82rem; color: #6b7280;
    padding: 11px 20px;
    background: #fffdf6;
    border-top: 1px solid #f3f4f6;
}
.tt2-summary-count { font-weight: 800; color: #7B1C3E; font-size: 1rem; }
.tt2-summary-badges { display:flex; gap:6px; flex-wrap:wrap; }

/* Table card */
.tt2-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 8px 24px rgba(17,24,39,.08); overflow: hidden;
}
.tt2-card-head {
    background: linear-gradient(90deg, #7B1C3E, #a8274e);
    border-bottom: 3px solid #f3b944;
    color: #fff; padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.tt2-card-head h2 { margin: 0; font-size: 1rem; font-weight: 800; display:flex; align-items:center; gap:8px; }
.tt2-card-head .meta { font-size: .78rem; opacity: .8; }

/* Notice */
.tt2-notice {
    background: #fef3c7; border: 1px solid #f3b944; border-radius: 9px;
    padding: 10px 16px; font-size: .85rem; color: #7B1C3E; font-weight: 600;
}

/* Read-only bar */
.tt2-readonly-bar {
    display: flex; align-items: center; gap: 8px;
    margin: 0;
    background: #f5f3ff; border-bottom: 1px solid #e0d9fb;
    padding: 8px 20px; font-size: .8rem; color: #5b21b6; font-weight: 600;
}

/* Table */
.tt2-table { width: 100%; border-collapse: collapse; }
.tt2-table thead th {
    background: #fce8ef; color: #7B1C3E;
    padding: 10px 16px; font-size: .73rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .07em;
    text-align: left; white-space: nowrap;
    border-bottom: 2px solid #f3b944;
    border-right: 1px solid #fbd5e0;
}
.tt2-table thead th:last-child { border-right: none; }
.tt2-table tbody td {
    padding: 12px 16px; border-bottom: 1px solid #fdf3e3;
    vertical-align: middle; font-size: .88rem; color: #1f2937;
}
.tt2-table tbody tr:last-child td { border-bottom: none; }
.tt2-table tbody tr:nth-child(even) td { background: #fdfaf5; }
.tt2-table tbody tr:hover td { background: #fff8ee !important; }
.tt2-table tbody tr.tt2-hidden { display: none; }

/* Bus number cell */
.tt2-bus {
    font-family: 'Courier New', monospace;
    font-weight: 800; font-size: .88rem; color: #7B1C3E;
    background: linear-gradient(135deg,#fce8ef,#fbd5e0); border-radius: 6px; padding: 4px 9px;
    display: inline-block; white-space: nowrap; border: 1px solid #f5c0cc;
    letter-spacing: .03em;
}

/* Route cell */
.tt2-route-no  { font-weight: 800; color: #1f2937; font-size: .92rem; }
.tt2-route-sub { font-size: .74rem; color: #6b7280; margin-top: 2px; }

/* Departure times */
.tt2-turn {
    font-weight: 700; color: #1d4ed8; font-size: .83rem;
    background: linear-gradient(135deg,#eff6ff,#dbeafe); border-radius: 6px; padding: 3px 9px;
    display: inline-block; white-space: nowrap; border: 1px solid #bfdbfe;
}
.tt2-turns-wrap { display:flex; flex-wrap:wrap; gap:5px; align-items:center; }
.tt2-turn-empty { color: #d1d5db; font-size: .8rem; }

/* Days active */
.tt2-days { display: flex; gap: 3px; flex-wrap: wrap; }
.tt2-day-pill {
    background: linear-gradient(135deg,#f0fdf4,#dcfce7); color: #15803d; border: 1px solid #bbf7d0;
    border-radius: 5px; padding: 2px 7px; font-size: .72rem; font-weight: 800;
}

/* Type badges */
.type-badge {
    display: inline-block; padding: 3px 10px; border-radius: 99px;
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
}
.type-regular { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.type-special { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }

/* Effective period */
.tt2-eff { font-size: .78rem; color: #6b7280; white-space: nowrap; }
.tt2-eff-always { font-size: .78rem; color: #15803d; font-weight: 700; }
.tt2-eff strong { color: #5b21b6; }

/* No results row */
.tt2-no-results td {
    padding: 36px; text-align: center; color: #9ca3af; font-size: .9rem;
}

/* Empty state */
.tt2-empty {
    padding: 40px; text-align: center; color: #9ca3af;
    display: grid; place-items: center; gap: 10px;
}
.tt2-empty svg { opacity: .35; }
.tt2-empty p { margin: 0; font-size: .9rem; }

@media(max-width: 900px) {
    .tt2-table thead, .tt2-table tbody, .tt2-table th, .tt2-table td, .tt2-table tr { display: block; }
    .tt2-table thead tr { display: none; }
    .tt2-table tbody tr { border: 1.5px solid #e8d39a; border-radius: 10px; margin: 10px 16px; padding: 12px; background: #fff; }
    .tt2-table tbody tr.tt2-hidden { display: none; }
    .tt2-table tbody td { border: none; padding: 5px 0; }
    .tt2-table tbody td::before {
        content: attr(data-label);
        display: block; font-size: .7rem; font-weight: 800;
        text-transform: uppercase; color: #7B1C3E; margin-bottom: 3px;
    }
    .tt2-filter-row { flex-direction: column; align-items: flex-start; }
    .tt2-clear-btn { margin-left: 0; }
}
</style>

<div class="tt2-page">

<!-- Hero -->
<section class="tt2-hero">
    <div>
        <h1>Depot Timetables</h1>
        <p>Read-only schedule viewer — regular always-active and special manager-imposed schedules</p>
    </div>
    <div class="tt2-depot-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <?= htmlspecialchars($depotName) ?>
    </div>
</section>

<?php if ($msg === 'readonly'): ?>
<div class="tt2-notice">
    &#128274;&ensp;This page is read-only for Depot Officers. Schedules are managed by NTC Admin (regular) and Depot Manager (special).
</div>
<?php endif; ?>

<!-- ── Filter Bar ── -->
<div class="tt2-filters" id="tt2Filters">
    <!-- Row 1: Type -->
    <div class="tt2-filter-section">
        <span class="tt2-filter-label">Type</span>
        <div class="tt2-type-group" id="typeGroup">
            <label class="tt2-type-pill sel-all"><input type="radio" name="tt2type" value="all" checked> All</label>
            <label class="tt2-type-pill"><input type="radio" name="tt2type" value="regular">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Regular
            </label>
            <label class="tt2-type-pill"><input type="radio" name="tt2type" value="special">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Special
            </label>
        </div>
    </div>

    <hr class="tt2-filter-divider">

    <!-- Row 2: Route -->
    <div class="tt2-filter-section">
        <span class="tt2-filter-label">Route</span>
        <select class="tt2-route-select" id="routeFilter">
            <option value="">All Routes</option>
            <?php foreach ($routeOptions as $rk => $rl): ?>
            <option value="<?= htmlspecialchars($rk) ?>">Route <?= htmlspecialchars($rl) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="tt2-clear-btn" id="tt2ClearBtn" type="button">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear Filters
        </button>
    </div>

    <hr class="tt2-filter-divider">

    <!-- Row 2: Days Active -->
    <div class="tt2-filter-section">
        <span class="tt2-filter-label">Days Active</span>
        <div class="tt2-day-filters" id="dayFilters">
            <?php foreach ($dayMap as $di => $dl): ?>
            <span class="tt2-day-toggle" data-day="<?= $di ?>"><?= $dl ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <hr class="tt2-filter-divider">

    <!-- Row 3: Active During Period -->
    <div class="tt2-filter-section">
        <span class="tt2-filter-label">Active During</span>
        <div class="tt2-period-wrap">
            <input type="date" id="periodFrom" placeholder="From">
            <span class="tt2-period-sep">→</span>
            <input type="date" id="periodTo" placeholder="To">
        </div>
        <span style="font-size:.75rem;color:#9ca3af;font-style:italic;">
            Regular schedules are always active. Special schedules are filtered by their effective date window.
        </span>
    </div>

    <hr class="tt2-filter-divider">

    <!-- Summary -->
    <div class="tt2-summary-bar">
        Showing <span class="tt2-summary-count" id="visibleCount"><?= count($allGroups) ?></span>
        of <?= count($allGroups) ?> schedules
        <span class="tt2-summary-badges">
            <span class="type-badge type-regular"><?= count($regularGroups) ?> Regular</span>
            <span class="type-badge type-special"><?= count($specialGroups) ?> Special</span>
        </span>
    </div>
</div>

<!-- ── Table Card ── -->
<div class="tt2-card">
    <div class="tt2-card-head">
        <h2>
            <svg width="16" height="16" fill="none" stroke="#f3b944" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            All Schedules
            <span style="font-weight:400;opacity:.7;">(Regular &amp; Special)</span>
        </h2>
        <span class="meta"><?= htmlspecialchars($depotName) ?> &bull; <?= count($allGroups) ?> total</span>
    </div>

    <div class="tt2-readonly-bar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Read-only — regular schedules managed by NTC Admin; special schedules by Depot Manager.
    </div>

    <?php if (empty($allGroups)): ?>
    <div class="tt2-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <p>No schedules found for <?= htmlspecialchars($depotName) ?>.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;padding-bottom:4px;margin-top:12px;">
    <table class="tt2-table" id="tt2Table">
        <thead>
            <tr>
                <th>Bus Number</th>
                <th>Route</th>
                <th>Departure Times</th>
                <th>Days Active</th>
                <th>Effective Period</th>
                <th>Type</th>
                <th>Trips / Week</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allGroups as $g):
            $deps        = $g['departures'];
            $isSpecialRow = ($g['type'] === 'Special');
            $effFrom     = trim((string)($g['effective_from'] ?? ''));
            $effTo       = trim((string)($g['effective_to']   ?? ''));
            $tripsPerWeek = count($deps) * count($g['days']);
            $daysAttr    = implode(',', $g['days']);
        ?>
        <tr data-type="<?= $isSpecialRow ? 'special' : 'regular' ?>"
            data-days="<?= htmlspecialchars($daysAttr) ?>"
            data-route="<?= htmlspecialchars((string)($g['route_no'] ?? '')) ?>"
            data-eff-from="<?= htmlspecialchars($effFrom) ?>"
            data-eff-to="<?= htmlspecialchars($effTo) ?>">

            <td data-label="Bus Number">
                <span class="tt2-bus"><?= htmlspecialchars($g['bus_reg_no']) ?></span>
            </td>

            <td data-label="Route">
                <div class="tt2-route-no"><?= htmlspecialchars($g['route_no']) ?></div>
                <?php if (!empty($g['route_name']) && $g['route_name'] !== $g['route_no']): ?>
                <div class="tt2-route-sub"><?= htmlspecialchars($g['route_name']) ?></div>
                <?php endif; ?>
            </td>

            <td data-label="Departure Times">
                <?php if (!empty($deps)): ?>
                <div class="tt2-turns-wrap">
                    <?php foreach ($deps as $dep): ?>
                    <span class="tt2-turn"><?= htmlspecialchars($dep) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <span class="tt2-turn-empty">—</span>
                <?php endif; ?>
            </td>

            <td data-label="Days Active">
                <?php if (!empty($g['day_labels'])): ?>
                <div class="tt2-days">
                    <?php foreach ($g['day_labels'] as $dl): ?>
                    <span class="tt2-day-pill"><?= htmlspecialchars($dl) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <span style="color:#9ca3af;font-size:.8rem;">—</span>
                <?php endif; ?>
            </td>

            <td data-label="Effective Period">
                <?php if ($isSpecialRow && ($effFrom || $effTo)): ?>
                <div class="tt2-eff"><strong><?= $effFrom ? date('d M Y', strtotime($effFrom)) : '...' ?></strong></div>
                <div class="tt2-eff" style="margin-top:2px;">→ <?= $effTo ? date('d M Y', strtotime($effTo)) : 'ongoing' ?></div>
                <?php else: ?>
                <span class="tt2-eff-always">&#10003; Always Active</span>
                <?php endif; ?>
            </td>

            <td data-label="Type">
                <?php if ($isSpecialRow): ?>
                <span class="type-badge type-special">Special</span>
                <?php else: ?>
                <span class="type-badge type-regular">Regular</span>
                <?php endif; ?>
            </td>

            <td data-label="Trips / Week">
                <span style="font-size:.9rem;font-weight:800;color:#7B1C3E;"><?= $tripsPerWeek ?></span>
                <span style="font-size:.72rem;color:#9ca3af;margin-left:2px;">trip<?= $tripsPerWeek !== 1 ? 's' : '' ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr class="tt2-no-results tt2-hidden" id="tt2NoResults">
            <td colspan="7">No schedules match the selected filters.</td>
        </tr>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.tt2-page -->

<script>
(function () {
    'use strict';

    const table       = document.getElementById('tt2Table');
    if (!table) return;

    const rows        = Array.from(table.querySelectorAll('tbody tr:not(#tt2NoResults)'));
    const noResults   = document.getElementById('tt2NoResults');
    const countEl     = document.getElementById('visibleCount');
    const clearBtn    = document.getElementById('tt2ClearBtn');
    const dayToggles  = Array.from(document.querySelectorAll('.tt2-day-toggle'));
    const routeSel    = document.getElementById('routeFilter');
    const periodFrom  = document.getElementById('periodFrom');
    const periodTo    = document.getElementById('periodTo');
    const typePills   = Array.from(document.querySelectorAll('.tt2-type-pill'));
    const typeGroup   = document.getElementById('typeGroup');

    // ── Highlight active type pill ──────────────────────────────
    function syncTypePills() {
        const val = document.querySelector('input[name="tt2type"]:checked')?.value ?? 'all';
        typePills.forEach(p => {
            p.classList.remove('sel-all','sel-regular','sel-special');
            if (p.querySelector('input').value === val) {
                p.classList.add('sel-' + val);
            }
        });
    }

    // ── Core filter ─────────────────────────────────────────────
    function applyFilters() {
        const selType   = document.querySelector('input[name="tt2type"]:checked')?.value ?? 'all';
        const selRoute  = routeSel.value;
        const selDays   = dayToggles.filter(d => d.classList.contains('active')).map(d => d.dataset.day);
        const fromVal   = periodFrom.value;   // 'YYYY-MM-DD' or ''
        const toVal     = periodTo.value;

        let visible = 0;

        rows.forEach(row => {
            const rType    = row.dataset.type;       // 'regular' | 'special'
            const rRoute   = row.dataset.route;
            const rDays    = row.dataset.days.split(',').filter(Boolean);
            const rEffFrom = row.dataset.effFrom;    // '' for regular
            const rEffTo   = row.dataset.effTo;      // '' for regular

            // 1. Type filter
            if (selType !== 'all' && rType !== selType) {
                hide(row); return;
            }

            // 2. Route filter
            if (selRoute && rRoute !== selRoute) {
                hide(row); return;
            }

            // 3. Day filter (any selected day must be in row's days)
            if (selDays.length > 0 && !selDays.some(d => rDays.includes(d))) {
                hide(row); return;
            }

            // 4. Period filter
            if (fromVal || toVal) {
                if (rType === 'special') {
                    // Must overlap: effFrom <= toVal AND effTo >= fromVal
                    if (fromVal && rEffTo && rEffTo < fromVal) { hide(row); return; }
                    if (toVal   && rEffFrom && rEffFrom > toVal) { hide(row); return; }
                }
                // Regular schedules always pass the period filter
            }

            show(row);
            visible++;
        });

        if (countEl) countEl.textContent = visible;
        if (noResults) noResults.classList.toggle('tt2-hidden', visible > 0);
    }

    function hide(row) { row.classList.add('tt2-hidden'); }
    function show(row) { row.classList.remove('tt2-hidden'); }

    // ── Day toggle pill clicks ───────────────────────────────────
    dayToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.classList.toggle('active');
            applyFilters();
        });
    });

    // ── Type radio changes ───────────────────────────────────────
    typeGroup.addEventListener('change', () => {
        syncTypePills();
        applyFilters();
    });

    // ── Route + period inputs ────────────────────────────────────
    routeSel.addEventListener('change', applyFilters);
    periodFrom.addEventListener('change', applyFilters);
    periodTo.addEventListener('change',  applyFilters);

    // ── Clear all filters ────────────────────────────────────────
    clearBtn.addEventListener('click', () => {
        document.querySelector('input[name="tt2type"][value="all"]').checked = true;
        syncTypePills();
        dayToggles.forEach(d => d.classList.remove('active'));
        routeSel.value    = '';
        periodFrom.value  = '';
        periodTo.value    = '';
        applyFilters();
    });

    // Init
    syncTypePills();
    applyFilters();
}());
</script>

