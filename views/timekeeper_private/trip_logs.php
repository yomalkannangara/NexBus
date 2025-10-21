<h1>Trip Logs</h1>

<form method="get" class="card">
  <input type="hidden" name="module" value="timekeeper_private"><input type="hidden" name="page" value="trip_logs">
  <div class="grid-3">
    <div><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
    <div><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
    <div class="mt-3"><button type="submit">Filter</button></div>
  </div>
</form>

<table class="table mt-2">
  <thead><tr><th>Time</th><th>Bus</th><th>Route</th><th>Status</th><th>Delay</th><th>Speed</th><th>Heading</th></tr></thead>
  <tbody>
  <?php foreach (($rows ?? []) as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['operational_status'] ?? '') ?></td>
      <td><?= (float)($r['avg_delay_min'] ?? 0) ?></td>
      <td><?= (float)($r['speed'] ?? 0) ?></td>
      <td><?= htmlspecialchars($r['heading'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
