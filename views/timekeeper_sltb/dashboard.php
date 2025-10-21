<?php $depotName = $depot['name'] ?? 'Unknown Depot'; ?>
<div class="card page-head">
  <h1 style="margin:0 0 6px;">SLTB Timekeeper — <?= htmlspecialchars($depotName) ?></h1>
  <div class="muted">Daily tracking snapshot and recent delays</div>
</div>

<div class="cards stats">
  <div class="card stat">
    <h3>Delayed Today</h3>
    <div class="stat-num danger"><?= (int)($todayStats['delayed'] ?? 0) ?></div>
  </div>
  <div class="card stat">
    <h3>Breakdowns Today</h3>
    <div class="stat-num warn"><?= (int)($todayStats['breaks'] ?? 0) ?></div>
  </div>
</div>

<h3 class="mt-3">Recent Delays</h3>
<table class="table compact">
  <thead><tr><th>Time</th><th>Bus</th><th>Status</th><th>Delay (min)</th></tr></thead>
  <tbody>
  <?php foreach (($delayed ?? []) as $r): 
    $status = trim((string)($r['operational_status'] ?? ''));
    $pill = 'neutral';
    if (stripos($status, 'delay') !== false)       { $pill = 'delayed'; }
    elseif (stripos($status, 'break') !== false)   { $pill = 'breakdown'; }
    elseif (stripos($status, 'on time') !== false 
         || stripos($status, 'ok') !== false)      { $pill = 'ok'; }
  ?>
    <tr>
      <td class="mono"><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><span class="status-pill <?= $pill ?>"><?= htmlspecialchars($status) ?></span></td>
      <td class="td-num"><span class="mono"><?= (float)($r['avg_delay_min'] ?? 0) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
