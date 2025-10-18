<?php /** @var string $from,$to */ ?>
<div class="container">
<h1>Trip / Tracking Logs</h1>
<form method="get" style="display:flex;gap:8px;align-items:end">
<input type="hidden" name="module" value="depot_officer"><input type="hidden" name="page" value="trip_logs">
<label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
<label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
<button>Filter</button>
</form>
<table class="table"><tr><th>When</th><th>Bus</th><th>Route</th><th>Status</th><th>Speed</th><th>Delay(min)</th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?= htmlspecialchars($r['snapshot_at']) ?></td><td><?= htmlspecialchars($r['bus_reg_no']) ?></td><td><?= htmlspecialchars($r['route_no'] ?? '') ?></td><td><?= htmlspecialchars($r['operational_status']) ?></td><td><?= htmlspecialchars($r['speed']) ?></td><td><?= htmlspecialchars($r['avg_delay_min']) ?></td></tr>
<?php endforeach; ?>
</table>
</div>