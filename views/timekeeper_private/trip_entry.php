<?php $S=$S??[]; ?>
<div class="title-banner">
    <h1>Trip Entry</h1>
    <p><?= htmlspecialchars($S['depot_name'] ?? 'My Operator') ?> — National Transport Commission</p>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tk-table" id="entryTable">
      <thead>
        <tr>
          <th>Time</th>
          <th>Route</th>
          <th>Bus</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (($rows ?? []) as $r):
        $isCurrent = (int)$r['is_current'] === 1;
        $already   = (int)$r['already_today'] === 1;
      ?>
        <tr class="row" data-tt="<?= (int)$r['timetable_id'] ?>"
          data-sdep="<?= htmlspecialchars($r['sched_dep']) ?>"
          data-sarr="<?= htmlspecialchars($r['sched_arr'] ?? '') ?>"
          data-trip-id="<?= htmlspecialchars((string)($r['trip_id'] ?? '')) ?>"
          data-trip-status="<?= htmlspecialchars((string)($r['trip_status'] ?? '')) ?>">
          <td class="mono" data-label="Time"><?= htmlspecialchars(substr($r['sched_dep'],0,5).' → '.substr($r['sched_arr'] ?? '—',0,5)) ?></td>
          <td data-label="Route">
            <div class="route">
              <div class="route-no"><?= htmlspecialchars($r['route_no']) ?></div>
              <div class="route-name"><?= htmlspecialchars($r['route_name']) ?></div>
            </div>
          </td>
          <td class="mono" data-label="Bus">
            <a class="tk-map-link" href="/TP/dashboard?focus_bus=<?= urlencode((string)($r['bus_reg_no'] ?? '')) ?>">
              <?= htmlspecialchars($r['bus_reg_no']) ?>
            </a>
          </td>
          <td data-label="Status">
            <span class="badge js-badge <?= $already?'gray':($isCurrent?'green':'blue') ?>">
              <?= $already ? 'Recorded' : ($isCurrent ? 'Current' : 'Scheduled') ?>
            </span>
          </td>
            <td data-label="Action">
      <button class="btn btn-start" data-action="start" 
          data-tt="<?= (int)$r['timetable_id'] ?>">Start</button>
            <?php if (!empty($r['trip_id']) && ($r['trip_status'] ?? '') === 'InProgress'): ?>
              <button class="btn btn-cancel" data-action="cancel" data-trip-id="<?= (int)$r['trip_id'] ?>">Stop Trip</button>
            <?php endif; ?>
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .tk-map-link {
    color: var(--maroon);
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  .tk-map-link:hover { color: color-mix(in srgb, var(--maroon) 85%, black); }
</style>

