<?php $depotName = $depot['name'] ?? 'Unknown Depot'; ?>

<div class="card" style="margin-bottom:16px;">
  <h1 style="margin:0 0 6px 0;">Private Timekeeper — <?= htmlspecialchars($depotName) ?></h1>
  <div>Daily tracking snapshot and recent delays</div>
</div>

<div class="cards">
  <div class="card"><h3>Delayed Today</h3><div><?= (int)($todayStats['delayed'] ?? 0) ?></div></div>
  <div class="card"><h3>Breakdowns Today</h3><div><?= (int)($todayStats['breaks'] ?? 0) ?></div></div>
</div>

<h3 class="mt-3">Recent Delays</h3>
<table class="table">
  <thead><tr><th>Time</th><th>Bus</th><th>Status</th><th>Delay (min)</th></tr></thead>
  <tbody>
  <?php foreach (($delayed ?? []) as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['operational_status'] ?? '') ?></td>
      <td><?= (float)($r['avg_delay_min'] ?? 0) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
