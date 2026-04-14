<?php
/* vars: tab, regular_rows, special_rows, count_regular, count_special,
         selected_date, depot_name, msg */
$tab          = $tab          ?? 'regular';
$regularRows  = $regular_rows ?? [];
$specialRows  = $special_rows ?? [];
$selectedDate = $selected_date ?? date('Y-m-d');
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
$specialGroups = groupTimetableRows($specialRows, 'Special',  $dayMap);
$groups = ($tab === 'special') ? $specialGroups : $regularGroups;
$isSpecialTab = ($tab === 'special');
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

/* Tabs */
.tt2-tabs {
    background: #fff; border-radius: 14px;
    box-shadow: 0 4px 16px rgba(17,24,39,.07); padding: 14px 18px;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.tt2-tab {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 9px 18px; border-radius: 99px;
    border: 2px solid #e5e7eb; background: #f9fafb;
    text-decoration: none; font-weight: 700; font-size: .88rem; color: #6b7280;
    transition: border-color .18s, background .18s, color .18s;
}
.tt2-tab:hover { border-color: #f3b944; color: #7B1C3E; background: #fffdf6; }
.tt2-tab.active-regular { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.tt2-tab.active-special { border-color: #7c3aed; background: #f5f3ff; color: #5b21b6; }
.tt2-tab-count {
    min-width: 22px; height: 22px; border-radius: 99px; padding: 0 7px;
    font-size: .72rem; font-weight: 800; display: grid; place-items: center;
    background: rgba(0,0,0,.08);
}
.tt2-tab.active-regular .tt2-tab-count { background: #bfdbfe; color: #1d4ed8; }
.tt2-tab.active-special .tt2-tab-count { background: #ddd6fe; color: #5b21b6; }

/* Special date filter bar */
.tt2-date-bar {
    background: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 10px;
    padding: 10px 16px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.tt2-date-bar label { font-size: .82rem; font-weight: 700; color: #5b21b6; }
.tt2-date-bar input[type=date] {
    border: 1.5px solid #c4b5fd; border-radius: 7px; padding: 6px 10px;
    font-size: .82rem; background: #fff; color: #2b2b2b;
}
.tt2-date-bar input[type=date]:focus { outline: none; border-color: #7c3aed; }
.tt2-date-bar .go-btn {
    background: #7c3aed; color: #fff; border: none; border-radius: 7px;
    padding: 7px 16px; font-size: .82rem; font-weight: 700; cursor: pointer; transition: background .2s;
}
.tt2-date-bar .go-btn:hover { background: #5b21b6; }

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
.tt2-card-head h2 { margin: 0; font-size: 1rem; font-weight: 800; }
.tt2-card-head .meta { font-size: .78rem; opacity: .8; }

/* Notice */
.tt2-notice {
    margin: 12px 20px 0;
    background: #fef3c7; border: 1px solid #f3b944; border-radius: 9px;
    padding: 10px 16px; font-size: .85rem; color: #7B1C3E; font-weight: 600;
}

/* Read-only notice for special */
.tt2-readonly-bar {
    display: flex; align-items: center; gap: 8px;
    margin: 12px 20px 0;
    background: #f5f3ff; border: 1.5px solid #c4b5fd; border-radius: 9px;
    padding: 9px 14px; font-size: .82rem; color: #5b21b6; font-weight: 600;
}

/* Table */
.tt2-table { width: 100%; border-collapse: collapse; }
.tt2-table thead th {
    background: #7B1C3E; color: #fff;
    padding: 11px 16px; font-size: .76rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    text-align: left; white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.1);
}
.tt2-table thead th:last-child { border-right: none; }
.tt2-table tbody td {
    padding: 11px 16px; border-bottom: 1px solid #fdf3e3;
    vertical-align: middle; font-size: .88rem; color: #1f2937;
}
.tt2-table tbody tr:last-child td { border-bottom: none; }
.tt2-table tbody tr:hover td { background: #fffdf6; }

/* Bus number cell */
.tt2-bus {
    font-family: 'Courier New', monospace;
    font-weight: 800; font-size: .9rem; color: #7B1C3E;
    background: #fce8ef; border-radius: 6px; padding: 3px 8px;
    display: inline-block; white-space: nowrap;
}

/* Route cell */
.tt2-route-no  { font-weight: 800; color: #1f2937; font-size: .92rem; }
.tt2-route-sub { font-size: .74rem; color: #6b7280; margin-top: 2px; }

/* Turn cells */
.tt2-turn {
    font-weight: 700; color: #1d4ed8; font-size: .85rem;
    background: #eff6ff; border-radius: 6px; padding: 3px 8px;
    display: inline-block; white-space: nowrap;
}
.tt2-turn-empty { color: #d1d5db; font-size: .8rem; }

/* Days active */
.tt2-days { display: flex; gap: 3px; flex-wrap: wrap; }
.tt2-day-pill {
    background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
    border-radius: 5px; padding: 2px 6px; font-size: .72rem; font-weight: 700;
}

/* Type badges */
.type-badge {
    display: inline-block; padding: 3px 10px; border-radius: 99px;
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
}
.type-regular { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.type-special { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }

/* Effective window */
.tt2-eff { font-size: .78rem; color: #6b7280; white-space: nowrap; }
.tt2-eff strong { color: #5b21b6; }

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
    .tt2-table tbody tr {
        border: 1.5px solid #e8d39a; border-radius: 10px; margin: 10px 16px;
        padding: 12px; background: #fff;
    }
    .tt2-table tbody td { border: none; padding: 5px 0; }
    .tt2-table tbody td::before {
        content: attr(data-label);
        display: block; font-size: .7rem; font-weight: 800;
        text-transform: uppercase; color: #7B1C3E; margin-bottom: 3px;
    }
}
</style>

<div class="tt2-page">

<!-- Hero -->
<section class="tt2-hero">
    <div>
        <h1>&#128641; Depot Timetables</h1>
        <p>Read-only schedule viewer — regular always-active and special manager-imposed schedules</p>
    </div>
    <div class="tt2-depot-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <?= htmlspecialchars($depotName) ?>
    </div>
</section>

<!-- Flash notice -->
<?php if ($msg === 'readonly'): ?>
<div class="tt2-notice">
    &#128274;&ensp;This page is read-only for Depot Officers. Schedules are managed by NTC Admin (regular) and Depot Manager (special).
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tt2-tabs">
    <a href="/O/timetables?tab=regular"
       class="tt2-tab <?= $tab === 'regular' ? 'active-regular' : '' ?>">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Regular Schedules
        <span class="tt2-tab-count"><?= (int)($count_regular ?? count($regularGroups)) ?></span>
    </a>
    <a href="/O/timetables?tab=special"
       class="tt2-tab <?= $tab === 'special' ? 'active-special' : '' ?>">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Special Schedules
        <span class="tt2-tab-count"><?= (int)($count_special ?? count($specialGroups)) ?></span>
    </a>

    <?php if ($isSpecialTab): ?>
    <!-- Date reference filter for special tab -->
    <form method="get" action="/O/timetables" style="display:contents;">
        <input type="hidden" name="tab" value="special">
        <div class="tt2-date-bar">
            <label for="tt2-date">&#128198; Ref. Date:</label>
            <input type="date" id="tt2-date" name="date"
                   value="<?= htmlspecialchars($selectedDate) ?>"
                   max="<?= date('Y-m-d', strtotime('+2 years')) ?>">
            <button type="submit" class="go-btn">Apply</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Main table card -->
<div class="tt2-card">
    <div class="tt2-card-head">
        <h2>
            <?php if ($isSpecialTab): ?>
                <svg width="16" height="16" fill="none" stroke="#f3b944" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Special Schedules <span style="font-weight:400;opacity:.7;">(Manager-Imposed)</span>
            <?php else: ?>
                <svg width="16" height="16" fill="none" stroke="#f3b944" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Regular Schedules <span style="font-weight:400;opacity:.7;">(Always Active)</span>
            <?php endif; ?>
        </h2>
        <span class="meta">
            <?= htmlspecialchars($depotName) ?> &bull;
            <?= count($groups) ?> bus<?= count($groups) !== 1 ? 'es' : '' ?>
            <?= $isSpecialTab ? '&bull; Ref: ' . htmlspecialchars($selectedDate) : '' ?>
        </span>
    </div>

    <?php if ($isSpecialTab): ?>
    <div class="tt2-readonly-bar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Special schedules are read-only — managed exclusively by the Depot Manager.
    </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
    <div class="tt2-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <p>No <?= $isSpecialTab ? 'special' : 'regular' ?> schedules found for <?= htmlspecialchars($depotName) ?>.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;padding-bottom:4px;">
    <table class="tt2-table">
        <thead>
            <tr>
                <th>Bus Number</th>
                <th>Route</th>
                <th>Turn 1</th>
                <th>Turn 2</th>
                <th>Turn 3</th>
                <th>Days Active</th>
                <?php if ($isSpecialTab): ?><th>Effective Period</th><?php endif; ?>
                <th>Type</th>
                <th>Trips / Week</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $g):
            $deps = $g['departures'];
            $t1   = $deps[0] ?? null;
            $t2   = $deps[1] ?? null;
            $t3   = $deps[2] ?? null;
            $isSpecialRow = ($g['type'] === 'Special');
        ?>
        <tr>
            <td data-label="Bus Number">
                <span class="tt2-bus"><?= htmlspecialchars($g['bus_reg_no']) ?></span>
            </td>
            <td data-label="Route">
                <div class="tt2-route-no"><?= htmlspecialchars($g['route_no']) ?></div>
                <?php if (!empty($g['route_name']) && $g['route_name'] !== $g['route_no']): ?>
                <div class="tt2-route-sub"><?= htmlspecialchars($g['route_name']) ?></div>
                <?php endif; ?>
            </td>
            <td data-label="Turn 1">
                <?= $t1 ? '<span class="tt2-turn">' . htmlspecialchars($t1) . '</span>' : '<span class="tt2-turn-empty">—</span>' ?>
            </td>
            <td data-label="Turn 2">
                <?= $t2 ? '<span class="tt2-turn">' . htmlspecialchars($t2) . '</span>' : '<span class="tt2-turn-empty">—</span>' ?>
            </td>
            <td data-label="Turn 3">
                <?php if ($t3): ?>
                    <span class="tt2-turn"><?= htmlspecialchars($t3) ?></span>
                    <?php if (count($deps) > 3): ?>
                    <span style="font-size:.72rem;color:#9ca3af;margin-left:4px;">+<?= count($deps)-3 ?> more</span>
                    <?php endif; ?>
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
            <?php if ($isSpecialTab): ?>
            <td data-label="Effective Period">
                <?php
                $ef  = trim((string)($g['effective_from'] ?? ''));
                $et  = trim((string)($g['effective_to']   ?? ''));
                $efFmt = $ef ? date('d M Y', strtotime($ef)) : '...';
                $etFmt = $et ? date('d M Y', strtotime($et)) : 'ongoing';
                ?>
                <div class="tt2-eff"><strong><?= htmlspecialchars($efFmt) ?></strong></div>
                <div class="tt2-eff" style="margin-top:2px;">→ <?= htmlspecialchars($etFmt) ?></div>
            </td>
            <?php endif; ?>
            <td data-label="Type">
                <?php if ($isSpecialRow): ?>
                <span class="type-badge type-special">Special</span>
                <?php else: ?>
                <span class="type-badge type-regular">Regular</span>
                <?php endif; ?>
            </td>
            <td data-label="Trips / Week">
                <?php
                $tripsPerWeek = count($g['departures']) * count($g['days']);
                ?>
                <span style="font-size:.9rem;font-weight:800;color:#7B1C3E;"><?= $tripsPerWeek ?></span>
                <span style="font-size:.72rem;color:#9ca3af;margin-left:2px;">trip<?= $tripsPerWeek !== 1 ? 's' : '' ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div><!-- /.tt2-card -->

</div><!-- /.tt2-page -->
