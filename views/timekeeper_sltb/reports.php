<div class="card" style="margin-bottom:16px;">
  <h1 style="margin:0">Reports</h1>
</div>

<form method="get" class="grid-3" style="margin-bottom:12px;">
  <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
  <input type="date" name="to"   value="<?= htmlspecialchars($to) ?>">
  <button class="button">Apply</button>
  <input type="hidden" name="module" value="TS">
</form>

<div class="cards">
  <div class="card"><h3>Trips</h3><div><?= (int)($summary['trips'] ?? 0) ?></div></div>
  <div class="card"><h3>Delayed</h3><div><?= (int)($summary['delayed'] ?? 0) ?></div></div>
  <div class="card"><h3>Breakdowns</h3><div><?= (int)($summary['breakdowns'] ?? 0) ?></div></div>
  <div class="card"><h3>Avg Delay (min)</h3><div><?= (float)($summary['avgDelayMin'] ?? 0) ?></div></div>
  <div class="card"><h3>Speed Violations</h3><div><?= (int)($summary['speedViolations'] ?? 0) ?></div></div>
</div>

<div class="mt-3">
  <a class="button outline" href="/TS/reports?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">Export CSV</a>
</div>
