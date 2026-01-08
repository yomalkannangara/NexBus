<?php
// ---- helpers ----
$cityOf = function(array $row): string {
  $c = trim((string)($row['city'] ?? $row['address'] ?? ''));
  return $c !== '' ? $c : 'Unknown';
};

// Build route->buscount from timetable schedules (controller should pass $scheduleRows; fallback empty)
$routeBusCounts = [];
foreach (($scheduleRows ?? []) as $sr) {
  $route = (string)($sr['route_no'] ?? $sr['route_display'] ?? $sr['route_id'] ?? '');
  $bus   = (string)($sr['bus_reg_no'] ?? '');
  if ($route === '' || $bus === '') continue;
  $routeBusCounts[$route][$bus] = true;
}
$routeBusTotals = [];
foreach ($routeBusCounts as $route => $set) $routeBusTotals[$route] = count($set);

// ---- group depots by city ----
$depotsByCity = [];
foreach (($depots ?? []) as $d) {
  $city = $cityOf($d);
  if (!isset($depotsByCity[$city])) $depotsByCity[$city] = ['depots'=>[], 'routes'=>[], 'buses'=>0];
  $depotsByCity[$city]['depots'][] = $d;

  foreach (($d['routes'] ?? []) as $r) $depotsByCity[$city]['routes'][(string)$r] = true;
  $depotsByCity[$city]['buses'] += (int)($d['buses'] ?? 0);
}
ksort($depotsByCity);

// ---- group owners by city ----
$ownersByCity = [];
foreach (($owners ?? []) as $o) {
  $city = $cityOf($o);
  if (!isset($ownersByCity[$city])) $ownersByCity[$city] = ['owners'=>[], 'routes'=>[], 'buses'=>0];
  $ownersByCity[$city]['owners'][] = $o;

  foreach (($o['routes'] ?? []) as $r) $ownersByCity[$city]['routes'][(string)$r] = true;
  $ownersByCity[$city]['buses'] += (int)($o['fleet_size'] ?? 0);
}
ksort($ownersByCity);
?>

<section class="page-hero">
  <h1>Depot & Bus company Management</h1>
  <p>Manage depot facilities and bus owner registrations</p>
</section>

<div class="tabs">
  <button class="tab active" data-tab="depots">Depots</button>
  <button class="tab" data-tab="owners">Bus companies</button>
</div>

<!-- Global Search -->
<div class="search-bar">
  <input type="search" id="globalSearch"
         placeholder="Search cities, depots and companies (city, name, phone, manager, reg no, route)...">
  <button type="button" class="btn" id="clearSearch">Clear</button>
</div>

<!-- ONE Toolbar -->
<div id="toolbar" class="toolbar">
  <button class="btn primary" id="showAddDepot">+ Add Depot</button>
  <button class="btn primary hide" id="showAddOwner">+ Add Company</button>
</div>

<!-- Add Depot Panel -->
<div id="addDepotPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_depot">
    <label>Depot Name <input name="name" required></label>
    <label>City <input name="city"></label>
    <label>Phone <input name="phone"></label>
    <div class="form-actions">
      <button class="btn primary">Save Depot</button>
      <button type="button" class="btn" id="cancelAddDepot">Cancel</button>
    </div>
  </form>
</div>

<!-- Add Owner Panel -->
<div id="addOwnerPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_owner">
    <label>Company Name <input name="name" required></label>
    <label>Registration No <input name="reg_no"></label>
    <label>City <input name="city"></label>
    <label>Contact Phone <input name="contact_phone"></label>
    <label>Contact Email (manager's email is preferred) <input name="contact_email"></label>

    <div class="form-actions">
      <button class="btn primary">Save Company</button>
      <button type="button" class="btn" id="cancelAddOwner">Cancel</button>
    </div>
  </form>
</div>

<!-- Depots (City accordion) -->
<section id="depots" class="tabcontent show">
  <div class="table-section">
    <div class="table-panel-head">
      <h3>Depots by City</h3>
    </div>

    <div class="route-accordion city-accordion">
      <?php foreach($depotsByCity as $city => $g): ?>
        <?php
          $routes = array_keys($g['routes']);
          sort($routes, SORT_NATURAL);
          $depotCount = count($g['depots']);
          $routeCount = count($routes);
          $busTotal   = (int)$g['buses'];
        ?>
        <article class="route-card city-card" data-city="<?= htmlspecialchars($city) ?>">
          <button class="route-head route-toggle" type="button" aria-expanded="false">
            <div class="route-head-main">
              <div class="route-title"><?= htmlspecialchars($city) ?></div>
              <div class="route-meta">
                <span class="pill">Routes: <?= $routeCount ?></span>
                <span class="pill">Depots: <?= $depotCount ?></span>
                <span class="pill">Buses: <?= $busTotal ?></span>
              </div>
            </div>
            <span class="route-chevron" aria-hidden="true">▾</span>
          </button>

          <div class="route-body">
            <div class="route-legend">
              Affiliated routes:
              <?php if ($routes): ?>
                <?php foreach($routes as $rno): ?>
                  <span class="chip">
                    <?= htmlspecialchars($rno) ?>
                    <strong>(<?= (int)($routeBusTotals[$rno] ?? 0) ?> buses)</strong>
                  </span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="chip">None</span>
              <?php endif; ?>
            </div>

            <table class="table full condensed">
              <thead>
                <tr>
                  <th>Depot</th>
                  <th>Manager</th>
                  <th>Phone</th>
                  <th>Buses</th>
                  <th style="width:140px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($g['depots'] as $d): ?>
                  <tr>
                    <td class="name"><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['manager'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($d['phone']) ?></td>
                    <td><?= htmlspecialchars($d['buses'] ?? 0) ?></td>
                    <td class="actions">
                      <a class="btn timetable-update-btn"
                         href="?module=ntc_admin&page=depots_owners&edit_depot=<?= htmlspecialchars($d['id'] ?? $d['sltb_depot_id'] ?? '') ?>">Update</a>
                      <a class="btn danger"
                         href="?module=ntc_admin&page=depots_owners&delete_depot=<?= htmlspecialchars($d['id'] ?? $d['sltb_depot_id'] ?? '') ?>"
                         onclick="return confirm('Delete depot?')">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (empty($depotsByCity)): ?>
        <div style="text-align:center;color:#777;padding:12px">No depots found.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Owners (City accordion) -->
<section id="owners" class="tabcontent">
  <div class="table-section">
    <div class="table-panel-head">
      <h3>Bus companies by City</h3>
    </div>

    <div class="route-accordion city-accordion">
      <?php foreach($ownersByCity as $city => $g): ?>
        <?php
          $routes = array_keys($g['routes']);
          sort($routes, SORT_NATURAL);
          $companyCount = count($g['owners']);
          $routeCount   = count($routes);
          $busTotal     = (int)$g['buses'];
        ?>
        <article class="route-card city-card" data-city="<?= htmlspecialchars($city) ?>">
          <button class="route-head route-toggle" type="button" aria-expanded="false">
            <div class="route-head-main">
              <div class="route-title"><?= htmlspecialchars($city) ?></div>
              <div class="route-meta">
                <span class="pill">Routes: <?= $routeCount ?></span>
                <span class="pill">Companies: <?= $companyCount ?></span>
                <span class="pill">Buses: <?= $busTotal ?></span>
              </div>
            </div>
            <span class="route-chevron" aria-hidden="true">▾</span>
          </button>

          <div class="route-body">
            <div class="route-legend">
              Affiliated routes:
              <?php if ($routes): ?>
                <?php foreach($routes as $rno): ?>
                  <span class="chip">
                    <?= htmlspecialchars($rno) ?>
                    <strong>(<?= (int)($routeBusTotals[$rno] ?? 0) ?> buses)</strong>
                  </span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="chip">None</span>
              <?php endif; ?>
            </div>

            <table class="table full condensed">
              <thead>
                <tr>
                  <th>Company</th>
                  <th>Owner</th>
                  <th>Reg No</th>
                  <th>Contact Phone</th>
                  <th>Fleet Size</th>
                  <th style="width:140px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($g['owners'] as $o): ?>
                  <tr>
                    <td class="name"><?= htmlspecialchars($o['name']) ?></td>
                    <td><?= htmlspecialchars(trim((string)($o['owner_name'] ?? '')) !== '' ? $o['owner_name'] : 'N/A') ?></td>
                    <td><?= htmlspecialchars($o['reg_no']) ?></td>
                    <td><?= htmlspecialchars($o['contact_phone']) ?></td>
                    <td><?= htmlspecialchars($o['fleet_size'] ?? 0) ?></td>
                    <td class="actions">
                      <a class="btn timetable-update-btn"
                         href="?module=ntc_admin&page=depots_owners&edit_owner=<?= htmlspecialchars($o['id'] ?? $o['private_operator_id'] ?? '') ?>">Update</a>
                      <a class="btn danger"
                         href="?module=ntc_admin&page=depots_owners&delete_owner=<?= htmlspecialchars($o['id'] ?? $o['private_operator_id'] ?? '') ?>"
                         onclick="return confirm('Delete company?')">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (empty($ownersByCity)): ?>
        <div style="text-align:center;color:#777;padding:12px">No bus companies found.</div>
      <?php endif; ?>
    </div>
  </div>
</section>
