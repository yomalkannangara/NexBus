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
            data-sarr="<?= htmlspecialchars($r['sched_arr'] ?? '') ?>">
          <td class="mono"><?= htmlspecialchars(substr($r['sched_dep'],0,5).' → '.substr($r['sched_arr'] ?? '—',0,5)) ?></td>
          <td>
            <div class="route">
              <div class="route-no"><?= htmlspecialchars($r['route_no']) ?></div>
              <div class="route-name"><?= htmlspecialchars($r['route_name']) ?></div>
            </div>
          </td>
          <td class="mono"><?= htmlspecialchars($r['bus_reg_no']) ?></td>
          <td>
            <span class="badge js-badge <?= $already?'gray':($isCurrent?'green':'blue') ?>">
              <?= $already ? 'Recorded' : ($isCurrent ? 'Current' : 'Scheduled') ?>
            </span>
          </td>
          <td>
<button class="btn btn-start" data-action="start" 
        data-tt="<?= (int)$r['timetable_id'] ?>">Start</button>          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

