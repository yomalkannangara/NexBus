<section class="section">
  <h1 class="title-heading">SLTB Trip Log</h1>

  <form id="filter" class="filters" method="get" onsubmit="return false;">
    <input type="date" id="from" value="<?= htmlspecialchars($from) ?>">
    <input type="date" id="to"   value="<?= htmlspecialchars($to) ?>">
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
    const from = document.getElementById('from').value || '';
    const to   = document.getElementById('to').value   || from;
    const qs = new URLSearchParams({ module:'depot_officer', page:'triplog', from, to });
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
  .filters { display:flex; gap:8px; margin:12px 0; }
  .filters input, .filters button { padding:8px 10px; }
</style>
