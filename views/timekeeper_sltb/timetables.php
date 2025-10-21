<div class="card" style="margin-bottom:16px;">
  <h1 style="margin:0">Timetables — Today</h1>
</div>

<?php if (!empty($msg)): ?>
  <div class="notice"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>ID</th><th>Route</th><th>Bus</th><th>Departs</th><th>Note</th><th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach (($rows ?? []) as $r): ?>
    <tr>
      <td><?= (int)($r['timetable_id'] ?? $r['id'] ?? 0) ?></td>
      <td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['departure_time'] ?? $r['dep_time'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['timekeeper_note'] ?? $r['remarks'] ?? '') ?></td>
      <td>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="timetable_id" value="<?= (int)($r['timetable_id'] ?? $r['id'] ?? 0) ?>">
          <input type="text" name="note" placeholder="Note" style="width:160px">
          <button type="submit" class="button">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
