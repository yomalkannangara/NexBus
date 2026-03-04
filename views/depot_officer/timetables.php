<?php
$rows = $rows ?? [];
$selectedView = $selected_view ?? 'current';
$selectedDate = $selected_date ?? date('Y-m-d');
$msg = $msg ?? null;
$countCurrent = (int)($count_current ?? 0);
$countUsual = (int)($count_usual ?? 0);
$countSeasonal = (int)($count_seasonal ?? 0);

$flashMap = [
    'readonly' => 'This timetable page is read-only for Depot Officer. Admin manages usual schedules and Depot Manager manages emergency schedules.',
];

$dayLabels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<div class="container tt-viewer">
  <section class="title-banner">
    <h1>Depot Timetables</h1>
    <p>Read-only timetable viewer: usual schedules from NTC Admin + seasonal/emergency schedules from Depot Manager.</p>
  </section>

  <?php if (!empty($msg) && isset($flashMap[$msg])): ?>
    <div class="notice"><?= htmlspecialchars($flashMap[$msg]) ?></div>
  <?php endif; ?>

  <section class="card tt-toolbar">
    <div class="tt-filter-row">
      <a class="tt-tab <?= $selectedView === 'current' ? 'active' : '' ?>" href="/O/timetables?view=current&date=<?= urlencode($selectedDate) ?>">
        Current Schedule
        <span class="tt-count"><?= $countCurrent ?></span>
      </a>
      <a class="tt-tab <?= $selectedView === 'usual' ? 'active' : '' ?>" href="/O/timetables?view=usual&date=<?= urlencode($selectedDate) ?>">
        Usual Schedule
        <span class="tt-count"><?= $countUsual ?></span>
      </a>
      <a class="tt-tab <?= $selectedView === 'seasonal' ? 'active' : '' ?>" href="/O/timetables?view=seasonal&date=<?= urlencode($selectedDate) ?>">
        Seasonal / Emergency
        <span class="tt-count"><?= $countSeasonal ?></span>
      </a>
    </div>

    <form method="get" class="tt-date-form">
      <input type="hidden" name="view" value="<?= htmlspecialchars($selectedView) ?>">
      <label>
        <span>Reference Date</span>
        <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
      </label>
      <button type="submit" class="button">Apply</button>
    </form>
  </section>

  <section class="card tt-table-card">
    <div class="tt-head">
      <h2>
        <?php if ($selectedView === 'current'): ?>Current Schedule<?php endif; ?>
        <?php if ($selectedView === 'usual'): ?>Usual Schedule<?php endif; ?>
        <?php if ($selectedView === 'seasonal'): ?>Seasonal / Emergency Schedule<?php endif; ?>
      </h2>
      <span class="tt-meta">Reference: <?= htmlspecialchars($selectedDate) ?></span>
    </div>

    <?php if (empty($rows)): ?>
      <div class="tt-empty">No timetable records found for this filter.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table tt-table">
          <thead>
            <tr>
              <th>Route</th>
              <th>Bus</th>
              <th>Day</th>
              <th>Departure</th>
              <th>Arrival</th>
              <th>Effective Window</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
              $dayIdx = (int)($r['day_of_week'] ?? -1);
              $dayText = $dayLabels[$dayIdx] ?? (string)$dayIdx;

              $from = trim((string)($r['effective_from'] ?? ''));
              $to = trim((string)($r['effective_to'] ?? ''));
              $window = ($from === '' && $to === '')
                  ? 'Always active'
                  : (($from ?: '...') . ' → ' . ($to ?: '...'));
              ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars((string)($r['route_no'] ?? '-')) ?></strong>
                  <?php if (!empty($r['route_name'])): ?>
                    <div class="tt-sub"><?= htmlspecialchars((string)$r['route_name']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string)($r['bus_reg_no'] ?? '-')) ?></td>
                <td><?= htmlspecialchars($dayText) ?></td>
                <td><?= htmlspecialchars(substr((string)($r['departure_time'] ?? ''), 0, 5)) ?></td>
                <td><?= htmlspecialchars(($r['arrival_time'] ?? null) ? substr((string)$r['arrival_time'], 0, 5) : '—') ?></td>
                <td><?= htmlspecialchars($window) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<style>
.tt-viewer { display:grid; gap:14px; }
.tt-toolbar { display:grid; gap:12px; padding:14px; }
.tt-filter-row { display:flex; flex-wrap:wrap; gap:8px; }

.tt-tab {
  text-decoration:none;
  border:1px solid var(--border);
  color:var(--text);
  background:#fff;
  border-radius:10px;
  padding:8px 12px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  font-weight:700;
}
.tt-tab.active {
  border-color:var(--maroon);
  color:var(--maroon);
  background:color-mix(in srgb, var(--gold) 16%, #fff);
}
.tt-count {
  border-radius:999px;
  border:1px solid var(--border);
  min-width:24px;
  height:24px;
  padding:0 8px;
  display:grid;
  place-items:center;
  font-size:12px;
}

.tt-date-form {
  display:flex;
  flex-wrap:wrap;
  align-items:flex-end;
  gap:10px;
}
.tt-date-form label { display:grid; gap:4px; }
.tt-date-form label span { font-size:12px; color:var(--muted); font-weight:700; }

.tt-table-card { padding:14px; }
.tt-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; }
.tt-head h2 { margin:0; color:var(--maroon); font-size:18px; }
.tt-meta { font-size:12px; color:var(--muted); }

.tt-sub { font-size:12px; color:var(--muted); margin-top:2px; }
.tt-empty { border:1px dashed var(--border); border-radius:10px; padding:20px; text-align:center; color:var(--muted); }

@media (max-width: 760px) {
  .tt-head { flex-direction:column; align-items:flex-start; }
  .tt-tab { width:100%; justify-content:space-between; }
  .tt-date-form { width:100%; }
}
</style>