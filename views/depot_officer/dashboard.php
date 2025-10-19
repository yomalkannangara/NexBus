<?php /** @var array $me,$depot,$counts,$todayDelayed,$openCompl */ ?>
<div class="container">
<h1>Depot Dashboard — <?= htmlspecialchars($depot['name'] ?? 'Unknown Depot') ?></h1>
<div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
<div class="card"><h3>Delayed Today</h3><div><?= (int)$counts['delayed'] ?></div></div>
<div class="card"><h3>Breakdowns Today</h3><div><?= (int)$counts['breaks'] ?></div></div>
<div class="card"><h3>Open Complaints</h3><div><?= (int)$counts['compl'] ?></div></div>
</div>
<h2 style="margin-top:20px">Latest Delays</h2>
<table class="table"><tr><th>Time</th><th>Bus</th><th>Route</th><th>Status</th></tr>
<?php foreach($todayDelayed as $r): ?>
<tr><td><?= htmlspecialchars($r['snapshot_at']) ?></td><td><?= htmlspecialchars($r['bus_reg_no']) ?></td><td><?= htmlspecialchars($r['route_no'] ?? '') ?></td><td><?= htmlspecialchars($r['operational_status']) ?></td></tr>
<?php endforeach; ?>
</table>
<h2 style="margin-top:20px">Recent Open Complaints</h2>
<ul><?php foreach($openCompl as $c): ?><li>#<?= (int)$c['complaint_id'] ?> — <?= htmlspecialchars($c['category']) ?> — <?= htmlspecialchars($c['description']) ?></li><?php endforeach; ?></ul>
</div>