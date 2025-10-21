
<div class="title-banner">
    <h1>Trip Logs</h1>
    <p><?= htmlspecialchars($S['depot_name'] ?? 'My Depot') ?> — National Transport Commission</p>
</div>
<form method="get" class="grid-3" style="margin-bottom:12px; display:grid; grid-template-columns: repeat(4, minmax(140px,1fr)) auto; gap:8px;">
  <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
  <input type="date" name="to"   value="<?= htmlspecialchars($to) ?>">

  <select name="route_id">
    <option value="">All Routes</option>
    <?php foreach (($routes ?? []) as $rt): ?>
      <option value="<?= (int)$rt['route_id'] ?>" <?= ((int)($route_id ?? 0)===(int)$rt['route_id'])?'selected':'' ?>>
        <?= htmlspecialchars($rt['route_no'].' – '.$rt['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="turn_no">
    <option value="">All Turns</option>
    <?php for ($i=1; $i<=12; $i++): ?>
      <option value="<?= $i ?>" <?= ((int)($turn_no ?? 0)===$i)?'selected':'' ?>><?= $i ?></option>
    <?php endfor; ?>
  </select>

  <button class="button">Filter</button>
</form>

<div class="card" style="margin-bottom:10px;">
  <div class="card-title">Trip Records (<?= (int)($count ?? 0) ?> results)</div>
  <div class="table-wrap" style="overflow:auto;">
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left; padding:8px 10px;">Date</th>
          <th style="text-align:left; padding:8px 10px;">Route</th>
          <th style="text-align:left; padding:8px 10px;">Turn Number</th>
          <th style="text-align:left; padding:8px 10px;">Bus ID</th>
          <th style="text-align:left; padding:8px 10px;">Departure Time</th>
          <th style="text-align:left; padding:8px 10px;">Arrival Time</th>
          <th style="text-align:left; padding:8px 10px;">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="padding:10px; color:#666;">No records.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $route = ($r['route_no'] ?? '') . ' – ' . ($r['route_name'] ?? '');
            $status = $r['ui_status'] ?? '';
            $badgeClass = $status==='Completed' ? 'badge-green'
                       : ($status==='Delayed' ? 'badge-amber'
                       : ($status==='Cancelled' ? 'badge-red' : 'badge-gray'));
          ?>
          <tr>
            <td style="padding:8px 10px;"><?= htmlspecialchars($r['date'] ?? '') ?></td>
            <td style="padding:8px 10px;"><?= htmlspecialchars($route) ?></td>
            <td style="padding:8px 10px;"><?= (int)($r['turn_no'] ?? 0) ?></td>
            <td style="padding:8px 10px;"><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
            <td style="padding:8px 10px;"><?= htmlspecialchars($r['dep_time'] ?? '') ?></td>
            <td style="padding:8px 10px;"><?= htmlspecialchars($r['arr_time'] ?? '—') ?></td>
            <td style="padding:8px 10px;">
              <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px}
.badge-green{background:#dcfce7;color:#166534}
.badge-amber{background:#fef3c7;color:#92400e}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-gray{background:#f3f4f6;color:#374151}
</style>
