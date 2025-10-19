<?php /** @var array $routes,$buses,$special_tt */ ?>
<div class="container">
<h1>Emergency / Seasonal Timetables</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card" style="padding:12px;display:grid;grid-template-columns:repeat(6,1fr);gap:8px;">
<input type="hidden" name="action" value="create_special_tt">
<label>Bus<select name="bus_reg_no"><?php foreach($buses as $b): ?><option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option><?php endforeach; ?></select></label>
<label>Route<select name="route_id"><?php foreach($routes as $r): ?><option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option><?php endforeach; ?></select></label>
<label>Start<input type="date" name="effective_from" required></label>
<label>End<input type="date" name="effective_to"></label>
<label>DOW<select name="day_of_week"><option value="0">Sun</option><option value="1">Mon</option><option value="2">Tue</option><option value="3">Wed</option><option value="4">Thu</option><option value="5">Fri</option><option value="6">Sat</option></select></label>
<label>Depart<input type="time" name="departure_time" required></label>
<label>Arrive<input type="time" name="arrival_time"></label>
<div style="grid-column:1/-1"><button type="submit">Save</button></div>
</form>


<h2 style="margin-top:16px">Existing Special Timetables</h2>
<table class="table"><tr><th>ID</th><th>Bus</th><th>Route</th><th>From</th><th>To</th><th>DOW</th><th>Dep</th><th>Arr</th><th></th></tr>
<?php foreach($special_tt as $r): ?>
<tr>
<td><?= (int)$r['timetable_id'] ?></td>
<td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
<td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
<td><?= htmlspecialchars($r['effective_from']) ?></td>
<td><?= htmlspecialchars($r['effective_to'] ?? '') ?></td>
<td><?= (int)$r['day_of_week'] ?></td>
<td><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></td>
<td><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?></td>
<td>
<form method="post" style="display:inline" onsubmit="return confirm('Delete this timetable?')">
<input type="hidden" name="action" value="delete_special_tt">
<input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
<button>Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>