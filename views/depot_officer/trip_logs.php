<section class="section">
  <h1 class="title-heading">SLTB Trip Log</h1>

  <form id="filter" class="filters" method="get" onsubmit="return false;">
    <div class="filter-group">
      <input type="date" id="date" name="date" value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>">
    </div>

    <div class="filter-group">
      <select id="route" name="route">
        <option value="">All routes</option>
        <?php foreach (($routes ?? []) as $r): ?>
          <option value="<?= htmlspecialchars($r['route_id']) ?>" <?= (!empty($filters['route']) && $filters['route']==$r['route_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['route_no'] . ' — ' . ($r['name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <select id="bus_id" name="bus_id">
        <option value="">All buses</option>
        <?php foreach (($buses ?? []) as $b): ?>
          <option value="<?= htmlspecialchars($b['reg_no']) ?>" <?= (!empty($filters['bus_id']) && $filters['bus_id']==$b['reg_no']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($b['reg_no'] . ' ' . ($b['make'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group time-group">
      <label for="departure_time" class="time-label">Dep (Start)</label>
      <input type="time" id="departure_time" name="departure_time" placeholder="Start" value="<?= htmlspecialchars($filters['departure_time'] ?? '') ?>">
    </div>

    <div class="filter-group time-group">
      <label for="arrival_time" class="time-label">Arr (End)</label>
      <input type="time" id="arrival_time" name="arrival_time" placeholder="End" value="<?= htmlspecialchars($filters['arrival_time'] ?? '') ?>">
    </div>

    <div class="filter-group">
      <select id="status" name="status">
        <option value="">Any status</option>
        <option value="Planned" <?= (!empty($filters['status']) && $filters['status']=='Planned') ? 'selected' : '' ?>>Planned</option>
        <option value="InProgress" <?= (!empty($filters['status']) && $filters['status']=='InProgress') ? 'selected' : '' ?>>In Progress</option>
        <option value="Completed" <?= (!empty($filters['status']) && $filters['status']=='Completed') ? 'selected' : '' ?>>Completed</option>
        <option value="Cancelled" <?= (!empty($filters['status']) && $filters['status']=='Cancelled') ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <button class="btn btn-primary" id="apply">Apply</button>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Route</th>
          <th>Turn Number</th>
          <th>Bus ID</th>
          <th>Departure Time</th>
          <th>Arrival Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['trip_date']) ?></td>
            <td><?= htmlspecialchars($r['route'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['turn_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['bus_id']) ?></td>
            <td><?= htmlspecialchars($r['departure_time'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['arrival_time'] ?? '-') ?></td>
            <td>
              <span class="badge status-<?= strtolower($r['status']) ?>">
                <?= htmlspecialchars($r['status']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center">No trips found for the selected range.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  document.getElementById('apply').addEventListener('click', function(){
    const date = document.getElementById('date').value || '';
    const route = document.getElementById('route').value || '';
    const bus_id = document.getElementById('bus_id').value || '';
    const departure_time = document.getElementById('departure_time').value || '';
    const arrival_time = document.getElementById('arrival_time').value || '';
    const status = document.getElementById('status').value || '';

    const qs = new URLSearchParams({
      module: 'depot_officer',
      page: 'triplog',
      date, route, bus_id, departure_time, arrival_time, status
    });
    window.location.search = qs.toString();
  });
</script>

<style>
  .table-wrap { overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; }
  table.table { width:100%; border-collapse:collapse; font-size:14px; }
  .table th, .table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
  .table th { text-align:left; font-weight:600; background:#f8fafc; }
  .badge { padding:4px 8px; border-radius:999px; font-weight:600; font-size:12px; }
  .status-planned    { background:#eef2ff; color:#3730a3; }
  .status-inprogress { background:#e0f2fe; color:#075985; }
  .status-completed  { background:#ecfdf5; color:#065f46; }
  .status-cancelled  { background:#fef2f2; color:#991b1b; }
  .filters { display:flex; gap:8px; margin:12px 0; align-items:center; flex-wrap:nowrap; overflow-x:auto; padding:8px 0; }
  .filter-group { display:flex; align-items:center; gap:4px; white-space:nowrap; }
  .filter-group input, .filter-group select { padding:8px 10px; }
  .time-group { gap:2px; }
  .time-label { font-size:11px; color:#6b7280; font-weight:500; }
  .filters button { padding:8px 12px; white-space:nowrap; }
</style>
