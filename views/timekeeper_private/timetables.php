<?php if (!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<h1>Today’s Timetables — <?= htmlspecialchars($depot['name'] ?? 'Unknown Depot') ?></h1>

<table class="table">
  <thead><tr><th>#</th><th>Bus</th><th>Route</th><th>Departure</th><th>Arrival</th><th>Remarks</th><th>Update</th></tr></thead>
  <tbody>
  <?php foreach (($rows ?? []) as $r): ?>
    <tr>
      <td><?= (int)$r['timetable_id'] ?></td>
      <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['departure_time'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['arrival_time'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
      <td>
        <form method="post" class="grid-2">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
          <input type="time" name="departure_time" value="<?= htmlspecialchars($r['departure_time'] ?? '') ?>">
          <input type="text" name="remarks" placeholder="remarks…">
          <button type="submit">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
