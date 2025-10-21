<div class="card" style="margin-bottom:16px;">
  <h1 style="margin:0">Trip Logs</h1>
</div>

<form method="get" class="grid-3" style="margin-bottom:12px;">
  <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
  <input type="date" name="to"   value="<?= htmlspecialchars($to) ?>">
  <button class="button">Filter</button>
</form>

<table class="table">
  <thead><tr><th>Time</th><th>Bus</th><th>Route</th><th>Status</th><th>Delay</th><th>Speed</th></tr></thead>
  <tbody>
  <?php foreach (($rows ?? []) as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['operational_status'] ?? '') ?></td>
      <td><?= (float)($r['avg_delay_min'] ?? 0) ?></td>
      <td><?= (float)($r['speed'] ?? 0) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
