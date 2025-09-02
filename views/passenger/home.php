<section class="filters">
  <div class="pill">
    <select name="route_id" form="filterForm" onchange="document.getElementById('filterForm').submit()">
      <option value="">All Routes</option>
      <?php foreach($routes as $r): ?>
        <option value="<?= (int)$r['route_id'] ?>" <?= (!empty($route_id) && (int)$route_id === (int)$r['route_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($r['route_no']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="pill">
    <select name="operator_type" form="filterForm" onchange="document.getElementById('filterForm').submit()">
      <option value="">All Types</option>
      <option value="SLTB"    <?= (!empty($operator_type) && $operator_type==='SLTB') ? 'selected' : '' ?>>SLTB</option>
      <option value="Private" <?= (!empty($operator_type) && $operator_type==='Private') ? 'selected' : '' ?>>Private</option>
    </select>
  </div>

  <form id="filterForm" method="get" style="display:none">

  </form>
</section>


<section class="map-card">
  <div class="map-placeholder">
    <div class="pin"></div>
    <p>Interactive map with live bus locations</p>
    <small>Tap markers to see bus details</small>
  </div>
</section>

<div class="section-title">
  <h3>Next Bus</h3>
</div>

<div class="cards">
  <?php foreach($nextBuses as $it): ?>
    <div class="bus-card">
      <div class="bus-badge"><?= htmlspecialchars($it['route_no'] ?? '') ?></div>

      <div class="bus-info">
        <div class="bus-title">
          <?= htmlspecialchars($it['name'] ??  '') ?> 
          <br>(<?= htmlspecialchars($it['bus_reg_no'] ??  '') ?>)
        </div>
        <div class="bus-sub">
          departure <?= (int)htmlspecialchars($it['minutes_from_departure'] ) ?> min ago
          <span class="chip"><?= htmlspecialchars($it['operator_type']) ?></span>
        </div>
      </div>

      <div class="bus-eta">
        <div class="min">ETA : <?= (int)($it['eta_min'] ?? 3) ?> min</div>
        <div><span class="bus-dot"></span></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
