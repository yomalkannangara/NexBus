<?php /** @var array $kpis */ ?>
<div class="container">
<h1>Depot Reports</h1>
<form method="get" style="display:flex;gap:8px;align-items:end">
<input type="hidden" name="module" value="depot_officer"><input type="hidden" name="page" value="reports">
<label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
<label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
<button>View</button>
<a class="button" href="?module=depot_officer&page=reports&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">Export CSV</a>
</form>
<div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
<div class="card"><h3>Trips Logged</h3><div><?= (int)$kpis['trips'] ?></div></div>
<div class="card"><h3>Delays</h3><div><?= (int)$kpis['delayed'] ?></div></div>
<div class="card"><h3>Breakdowns</h3><div><?= (int)$kpis['breakdowns'] ?></div></div>
<div class="card"><h3>Avg Delay (min)</h3><div><?= number_format((float)$kpis['avgDelayMin'],2) ?></div></div>
<div class="card"><h3>Speed Violations</h3><div><?= (int)$kpis['speedViolations'] ?></div></div>
</div>
</div>

