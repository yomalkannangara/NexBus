<?php use App\Support\Icons; ?>
<section class="section">
  <div class="head-between">
    <div>
      <h1 class="h1 primary">Fleet Management</h1>
      <p class="muted">Manage and monitor your bus fleet</p>
    </div>
    <button class="btn btn-secondary">
      <?= Icons::svg('plus','',16,16) ?> Add New Bus
    </button>
  </div>

  <!-- Summary cards -->
  <div class="grid grid-4 gap-4 mt-4">
    <?php foreach ($summary as $c): ?>
      <div class="card p-16">
        <div class="value <?= htmlspecialchars($c['class']) ?>"><?= htmlspecialchars($c['value']) ?></div>
        <p class="muted"><?= htmlspecialchars($c['label']) ?></p>
      </div>
    <?php endforeach; ?>
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
            <select>
              <option>All Routes</option>
              <option>R001 - Colombo - Kandy</option>
              <option>R045 - Galle - Matara</option>
              <option>R187 - Negombo - Airport</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Bus Number</label>
          <div class="select">
            <select>
              <option>All Buses</option>
              <option>NC-1247</option>
              <option>WP-3456</option>
              <option>CP-7890</option>
            </select>
          </div>
        </div>
        <div class="form-group span-2">
          <label>Search</label>
          <div class="input-icon">
            <span class="icon"><?= Icons::svg('search','',16,16) ?></span>
            <input type="text" placeholder="Search by bus number, route number, or route...">
          </div>
        </div>
      </div>

      <div class="actions mt-4">
        <button class="btn btn-outline secondary"><?= Icons::svg('trending-up','',16,16) ?> Filter</button>
        <button class="btn btn-outline secondary"><?= Icons::svg('dollar-sign','',16,16) ?> Export</button>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Fleet Overview</div>
    </div>
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
        <?php foreach ($rows as $i=>$r): ?>
          <tr class="<?= $i%2===0?'alt':'' ?>">
            <td class="primary fw-600"><?= htmlspecialchars($r['number']) ?></td>
            <td><?= htmlspecialchars($r['route']) ?></td>
            <td><span class="badge badge-outline badge-secondary"><?= htmlspecialchars($r['routeNumber']) ?></span></td>
            <td>
              <?php
                $st = $r['status'];
                $badge = $st==='Active'?'badge-green':($st==='Maintenance'?'badge-yellow':($st==='Out of Service'?'badge-red':''));
              ?>
              <span class="badge <?= $badge ?>"><?= htmlspecialchars($st) ?></span>
            </td>
            <td><span class="inline-icon"><?= Icons::svg('map-pin','',14,14) ?></span><?= htmlspecialchars($r['location']) ?></td>
            <td><?= (int)$r['capacity'] ?> seats</td>
            <td><?= htmlspecialchars($r['nextService']) ?></td>
            <td>
              <div class="actions-inline">
                <button class="btn icon-only btn-outline"><?= Icons::svg('eye','',14,14) ?></button>
                <button class="btn icon-only btn-outline"><?= Icons::svg('edit','',14,14) ?></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
