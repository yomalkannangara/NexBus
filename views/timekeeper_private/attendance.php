<?php if (!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<h1>Attendance</h1>

<form method="get" class="card">
  <input type="hidden" name="module" value="timekeeper_private"><input type="hidden" name="page" value="attendance">
  <div class="grid-3">
    <div><label>Date</label><input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></div>
    <div class="mt-3"><button type="submit">Load</button></div>
  </div>
</form>

<form method="post" class="card mt-2">
  <input type="hidden" name="action" value="mark">
  <table class="table">
    <thead><tr><th>User</th><th>Role</th><th>Absent</th><th>Notes</th></tr></thead>
    <tbody>
      <?php
      $rec = $records ?? [];
      foreach (($staff ?? []) as $s):
        $uid = (int)$s['user_id'];
        $row = $rec[$uid] ?? null;
      ?>
        <tr>
          <td><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['role'] ?? '') ?></td>
          <td><input type="checkbox" name="mark[<?= $uid ?>][absent]" <?= !empty($row['mark_absent']) ? 'checked' : '' ?>></td>
          <td><input type="text" name="mark[<?= $uid ?>][notes]" value="<?= htmlspecialchars($row['notes'] ?? '') ?>"></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="mt-2"><button type="submit">Save</button></div>
</form>
