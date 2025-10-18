<?php /** @var array $buses,$drivers,$routes,$rows,$today */ ?>
<div class="container">
<h1>Daily Assignments</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card" style="padding:12px;display:grid;grid-template-columns:repeat(5,1fr);gap:8px;">
<input type="hidden" name="action" value="create_assignment">
<label>Bus<select name="bus_reg_no"><?php foreach($buses as $b): ?><option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option><?php endforeach; ?></select></label>
<label>Route<select name="route_id"><?php foreach($routes as $r): ?><option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option><?php endforeach; ?></select></label>
<label>Date<input type="date" name="date" value="<?= htmlspecialchars($today) ?>"></label>
<label>Depart<input type="time" name="departure_time" required></label>
<label>Arrive<input type="time" name="arrival_time"></label>
<div style="grid-column:1/-1"><button type="submit">Assign</button></div>
</form>


<h2 style="margin-top:16px">Today's Timetables</h2>
<table class="table"><tr><th>ID</th><th>Bus</th><th>Route</th><th>Dep</th><th>Arr</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?= (int)$r['timetable_id'] ?></td>
<td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
<td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
<td><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></td>
<td><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?></td>
<td>
<form method="post" style="display:inline" onsubmit="return confirm('Delete assignment?')">
<input type="hidden" name="action" value="delete_assignment">
<input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
<button>Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>