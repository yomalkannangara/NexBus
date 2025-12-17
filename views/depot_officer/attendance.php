<?php /** @var array $staff,$records */ ?>
<div class="container">
<h1>Staff Attendance</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="get" style="display:flex;gap:8px;align-items:end">
<input type="hidden" name="module" value="depot_officer"><input type="hidden" name="page" value="attendance">
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
<button>Go</button>
</form>


<form method="post">
<input type="hidden" name="action" value="mark">
<table class="table"><tr><th>User</th><th>Role</th><th>Absent?</th><th>Notes</th></tr>
<?php foreach($staff as $s): $akey = $s['attendance_key'] ?? ($s['attendance_key'] ?? null); $rec = $records[$akey] ?? null; ?>
<tr>
<td><?= htmlspecialchars($s['full_name']) ?></td>
<td><?= htmlspecialchars($s['type'] ?? $s['role'] ?? '') ?></td>
<td><input type="checkbox" name="mark[<?= htmlspecialchars($akey) ?>][absent]" value="1" <?= !empty($rec['mark_absent'])?'checked':'' ?>></td>
<td><input type="text" name="mark[<?= htmlspecialchars($akey) ?>][notes]" value="<?= htmlspecialchars($rec['notes'] ?? '') ?>" style="width:100%"></td>
</tr>
<?php endforeach; ?>
</table>
<button type="submit">Save Attendance</button>
</form>
</div>