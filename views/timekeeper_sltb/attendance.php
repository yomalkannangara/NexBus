<div class="card" style="margin-bottom:16px;">
  <h1 style="margin:0">Attendance — <?= htmlspecialchars($date) ?></h1>
</div>

<?php if (!empty($msg)): ?>
  <div class="notice"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="action" value="mark">
  <table class="table">
    <thead><tr><th>Name</th><th>Role</th><th>Absent?</th><th>Notes</th></tr></thead>
    <tbody>
    <?php foreach (($staff ?? []) as $s):
      $uid = (int)$s['user_id'];
      $rec = $records[$uid] ?? null;
    ?>
      <tr>
        <td><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($s['role'] ?? '') ?></td>
        <td><input type="checkbox" name="mark[<?= $uid ?>][absent]" <?= !empty($rec['mark_absent']) ? 'checked' : '' ?>></td>
        <td><input type="text" name="mark[<?= $uid ?>][notes]" value="<?= htmlspecialchars($rec['notes'] ?? '') ?>"></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="mt-2"><button class="button">Save</button></div>
</form>
