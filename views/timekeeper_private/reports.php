<h1>Reports</h1>

<form method="get" class="card">
  <input type="hidden" name="module" value="timekeeper_private"><input type="hidden" name="page" value="reports">
  <div class="grid-3">
    <div><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
    <div><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
    <div class="mt-3">
      <button type="submit">Refresh</button>
      <a class="button" href="/TP/reports?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">Export CSV</a>
    </div>
  </div>
</form>

<div class="cards mt-2">
  <div class="card"><h3>Total Logs</h3><div><?= (int)($summary['total'] ?? 0) ?></div></div>
  <div class="card"><h3>Delayed</h3><div><?= (int)($summary['delayed'] ?? 0) ?></div></div>
  <div class="card"><h3>Breakdowns</h3><div><?= (int)($summary['breakdowns'] ?? 0) ?></div></div>
  <div class="card"><h3>Avg Delay (min)</h3><div><?= number_format((float)($summary['avg_delay_min'] ?? 0),1) ?></div></div>
  <div class="card"><h3>Speed Violations</h3><div><?= (int)($summary['speed_violations'] ?? 0) ?></div></div>
</div>
