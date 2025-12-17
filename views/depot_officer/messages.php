<?php 
/** @var array $staff,$recent,$msg */ 
// Helper function to get display name
function getStaffDisplayName($person) {
    if (!empty($person['full_name'])) return $person['full_name'];
    if (!empty($person['first_name'])) {
        $name = $person['first_name'];
        if (!empty($person['last_name'])) $name .= ' ' . $person['last_name'];
        return $name;
    }
    return 'Unknown';
}
?>
<div class="container">
<h1>Depot Messaging</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card" style="padding:12px;">
<input type="hidden" name="action" value="send">
<label>Recipients (same depot):<br>
<select name="to[]" multiple size="6" style="min-width:320px">
<?php foreach($staff as $s): ?>
<option value="<?= (int)$s['user_id'] ?>">[<?= htmlspecialchars($s['role']) ?>] <?= htmlspecialchars(getStaffDisplayName($s)) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Message<br><textarea name="message" rows="3" style="width:100%" required></textarea></label>
<button type="submit">Send</button>
</form>


<h2 style="margin-top:16px">Recent Messages</h2>
<table class="table"><tr><th>When</th><th>To (User)</th><th>Type</th><th>Text</th></tr>
<?php foreach($recent as $n): ?>
<tr><td><?= htmlspecialchars($n['created_at']) ?></td><td><?= htmlspecialchars(getStaffDisplayName($n)) ?></td><td><?= htmlspecialchars($n['type']) ?></td><td><?= htmlspecialchars($n['message']) ?></td></tr>
<?php endforeach; ?>
</table>
</div>