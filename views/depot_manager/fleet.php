<?php
// Expected from controller:
//   $summary = $summary ?? [];
//   $rows    = $rows    ?? [];
//   $routes  = $routes  ?? [];   // optional: for filter dropdown
//   $buses   = $buses   ?? [];   // optional: for filter dropdown
?>
<section class="section">
    <div class="title-card">
  <h1 class="title-heading">Fleet Management</h1>
  <p class="title-sub">Manage and monitor your bus fleet</p>
  </div>
    <button class="btn btn-secondary">+ Add New Bus</button>

  <!-- Summary cards -->
  <div class="grid grid-4 gap-4 mt-4">
    <?php if (!empty($summary)): ?>
      <?php foreach ($summary as $c): ?>
        <div class="card p-16">
          <div class="value <?= htmlspecialchars($c['class'] ?? '') ?>"><?= htmlspecialchars($c['value'] ?? '0') ?></div>
          <p class="muted"><?= htmlspecialchars($c['label'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No summary available.</div>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Bus Location Filters</div>
    </div>
    <div class="card__body">
      <div class="form-grid">
        <div class="form-group">
          <label>Route Number</label>
          <div class="select">
            <select name="route_no">
              <option value="">All Routes</option>
              <?php foreach ($routes as $r): ?>
                <option value="<?= htmlspecialchars($r['route_no']) ?>">
                  <?= htmlspecialchars(($r['route_no'] ?? '') . ' - ' . ($r['name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Bus Number</label>
          <div class="select">
            <select name="reg_no">
              <option value="">All Buses</option>
              <?php foreach ($buses as $b): ?>
                <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group span-2">
          <label>Search</label>
          <input type="text" class="input" placeholder="Search by bus number, route number, or route...">
        </div>
      </div>

      <div class="actions mt-4">
        <button class="btn btn-outline secondary">Filter</button>
        <button class="btn btn-outline secondary">Export</button>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Fleet Overview</div>
    </div>
    <?php if (!empty($rows)): ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Bus Number</th>
              <th>Route</th>
              <th>Route Number</th>
              <th>Status</th>
              <th>Current Location</th>
              <th>Capacity</th>
              <th>Next Service</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr class="<?= $i % 2 === 0 ? 'alt' : '' ?>">
              <td class="primary fw-600"><?= htmlspecialchars($r['reg_no'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['route'] ?? '—') ?></td>
              <td><span class="badge badge-outline badge-secondary"><?= htmlspecialchars($r['route_no'] ?? '') ?></span></td>
              <td>
                <?php
                  $st = (string)($r['status'] ?? '');
                  $badge = $st === 'Active' ? 'badge-green' : ($st === 'Maintenance' ? 'badge-yellow' : ($st === 'OutOfService' || $st === 'Out of Service' ? 'badge-red' : ''));
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($st) ?></span>
              </td>
              <td><?= htmlspecialchars($r['current_location'] ?? '—') ?></td>
              <td><?= (int)($r['capacity'] ?? 0) ?> seats</td>
              <td><?= htmlspecialchars($r['next_service'] ?? '—') ?></td>
              <td>
                <div class="actions-inline">
                  <button class="btn btn-outline small">View</button>
                  <button class="btn btn-outline small">Edit</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No buses found.</div>
    <?php endif; ?>
  </div>
</section>
