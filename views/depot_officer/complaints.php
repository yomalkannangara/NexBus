<?php /** @var array $open,$inprog,$mine */ ?>
<div class="container">
<h1>Passenger Complaints</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<h2>Open</h2>
<table class="table"><tr><th>ID</th><th>Bus</th><th>Category</th><th>Description</th><th>Created</th><th></th></tr>
<?php foreach($open as $c): ?>
<tr>
<td>#<?= (int)$c['complaint_id'] ?></td>
<td><?= htmlspecialchars($c['bus_reg_no']) ?></td>
<td><?= htmlspecialchars($c['category']) ?></td>
<td><?= htmlspecialchars($c['description']) ?></td>
<td><?= htmlspecialchars($c['created_at']) ?></td>
<td>
<form method="post" style="display:inline">
<input type="hidden" name="action" value="take">
<input type="hidden" name="complaint_id" value="<?= (int)$c['complaint_id'] ?>">
<button>Take</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>


<h2>In Progress</h2>
<table class="table"><tr><th>ID</th><th>Bus</th><th>Category</th><th>Description</th><th>Reply</th><th>Status</th><th>Action</th></tr>
<?php foreach($inprog as $c): ?>
<tr>
<td>#<?= (int)$c['complaint_id'] ?></td>
<td><?= htmlspecialchars($c['bus_reg_no']) ?></td>
<td><?= htmlspecialchars($c['category']) ?></td>
<td><?= htmlspecialchars($c['description']) ?></td>
<td>
<form method="post" style="display:grid;gap:4px;grid-template-columns:1fr auto;align-items:center">
<input type="hidden" name="action" value="reply">
<input type="hidden" name="complaint_id" value="<?= (int)$c['complaint_id'] ?>">
<textarea name="reply_text" rows="2" placeholder="Type reply..."><?= htmlspecialchars($c['reply_text'] ?? '') ?></textarea>
<select name="status"><option>In Progress</option><option>Resolved</option><option>Closed</option></select>
<button>Update</button>
</form>
</td>
<td><?= htmlspecialchars($c['status']) ?></td>
<td></td>
</tr>
<?php endforeach; ?>
</table>


<h2>Assigned to Me</h2>
<ul><?php foreach($mine as $c): ?><li>#<?= (int)$c['complaint_id'] ?> — <?= htmlspecialchars($c['status']) ?> — <?= htmlspecialchars($c['description']) ?></li><?php endforeach; ?></ul>
</div>