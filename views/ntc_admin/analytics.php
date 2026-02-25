<?php
  $kpi     = $kpi     ?? ['delayedToday'=>0,'avgRating'=>0,'speedViol'=>0,'longWaitPct'=>0];
  $filters = $filters ?? ['route_no'=>'','depot_id'=>null,'owner_id'=>null];
  $curRno  = $filters['route_no']  ?? '';
  $curDep  = (int)($filters['depot_id'] ?? 0);
  $curOwn  = (int)($filters['owner_id'] ?? 0);
  $hasFilter = ($curRno !== '' || $curDep > 0 || $curOwn > 0);
?>
<section class="page-hero"><h1>Analytics Dashboard</h1><p>Bus performance metrics and operational insights</p></section>
<!-- ===== Analytics Filters ===== -->
<section class="filters-panel">
  <div class="filters-title">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 4h18l-7 8v5l-4 3v-8L3 4z"/></svg>
    <span>Analytics Filters</span>
    <?php if ($hasFilter): ?>
      <a href="/A/analytics" class="filter-clear-link" title="Clear all filters">&#10005; Clear</a>
    <?php endif; ?>
  </div>

  <form method="get" action="/A/analytics" class="filters-grid-3" id="analytics-filter-form">
    <!-- Route -->
    <div class="field">
      <label for="ft-route">Route</label>
      <div class="nb-select">
        <select id="ft-route" name="route_no" onchange="this.form.submit()">
          <option value="">All Routes</option>
          <?php foreach(($routes ?? []) as $r):
            $rno = htmlspecialchars($r['route_no']);
            $sel = ($curRno === $r['route_no']) ? 'selected' : '';
          ?>
            <option value="<?= $rno ?>" <?= $sel ?>><?= $rno ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- SLTB Depot -->
    <div class="field">
      <label for="ft-depot">SLTB Depot</label>
      <div class="nb-select">
        <select id="ft-depot" name="depot_id" onchange="if(this.value){document.getElementById('ft-owner').value='';}this.form.submit()">
          <option value="0">All Depots</option>
          <?php foreach(($depots ?? []) as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $curDep===$d['id'] ? 'selected':'' ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Private Owner -->
    <div class="field">
      <label for="ft-owner">Bus Owner</label>
      <div class="nb-select">
        <select id="ft-owner" name="owner_id" onchange="if(this.value){document.getElementById('ft-depot').value=0;}this.form.submit()">
          <option value="0">All Owners</option>
          <?php foreach(($owners ?? []) as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= $curOwn===$o['id'] ? 'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>
</section>

<!-- ===== Topic Area: KPI cards (same grid as your screenshot) ===== -->
<section class="kpi-wrap kpi-wrap--neo">
  <article class="kpi2 tone-red">
    <header><h3>Delayed Buses Today</h3><span class="ico">
      <!-- clock -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg>
    </span></header>
    <div class="value" id="kpi-delayed"><?= (int)($kpi['delayedToday'] ?? 0) ?></div>
    <div class="hint">Live from database</div>
  </article>

  <article class="kpi2 tone-green">
    <header><h3>Average Driver Rating</h3><span class="ico">
      <!-- star -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/></svg>
    </span></header>
    <div class="value" id="kpi-rating"><?= $kpi['avgRating'] > 0 ? number_format($kpi['avgRating'],1) : '–' ?></div>
    <div class="hint">Passenger feedback (/10)</div>
  </article>

  <article class="kpi2 tone-orange">
    <header><h3>Speed Violations</h3><span class="ico">
      <!-- bolt -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M14 3L3 14h7v7l11-11h-7z"/></svg>
    </span></header>
    <div class="value" id="kpi-speed"><?= (int)($kpi['speedViol'] ?? 0) ?: '–' ?></div>
    <div class="hint">Live · buses over 60 km/h</div>
  </article>

  <article class="kpi2 tone-blue">
    <header><h3>Long Wait Times</h3><span class="ico">
      <!-- trending up -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg>
    </span></header>
    <div class="value" id="kpi-wait"><?= (int)($kpi['longWaitPct'] ?? 0) ?>%</div>
    <div class="hint">Snapshots with delay &gt;10 min</div>
  </article>
</section>

<!-- ===== Live Fleet KPIs ===== -->
<section class="kpi-wrap kpi-wrap--neo">
  <article class="kpi2 tone-blue">
    <header><h3>Active Buses Now</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M17 8C8 10 5.9 16.1 3 19h3s2.5-4 9.5-4.5c-1.7 1.1-3.5 3-4.5 4.5h3C15 17 17 14 21 12c-1-1-2-2-2-4z"/></svg>
    </span></header>
    <div class="value" id="kpi-active-buses">–</div>
    <div class="hint" id="live-updated-at">Fetching…</div>
  </article>

  <article class="kpi2 tone-green">
    <header><h3>Average Fleet Speed</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m1 14.93V15h-2v1.93A8 8 0 0 1 4.07 11H6V9H4.07A8 8 0 0 1 11 4.07V6h2V4.07A8 8 0 0 1 19.93 11H18v2h1.93A8 8 0 0 1 13 16.93z"/></svg>
    </span></header>
    <div class="value" id="kpi-avg-speed">–</div>
    <div class="hint">Fleet average right now</div>
  </article>
</section>

<!-- ===== Live Fleet Charts ===== -->
<section class="charts-grid" style="margin-bottom:1.5rem">
  <div class="chart-card">
    <h2>Live Bus Status</h2>
    <canvas id="liveStatusChart"></canvas>
  </div>
  <div class="chart-card">
    <h2>Live Fleet Speed</h2>
    <canvas id="liveSpeedChart"></canvas>
  </div>
</section>

<!-- ===== Live Route Summary Table ===== -->
<section class="chart-card" style="margin-bottom:1.5rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
    <h2 style="margin:0">Live Bus Fleet</h2>
    <span style="font-size:.75rem;color:#6b7280" id="live-updated-at-table"></span>
  </div>
  <?php if ($curDep > 0 || $curOwn > 0): ?>
    <p style="font-size:.78rem;color:#9ca3af;margin:0 0 .5rem">
      ℹ Depot/owner filters apply to analytics charts above. Live table shows all active buses (route filter still applies).
    </p>
  <?php endif; ?>
  <div style="overflow-x:auto">
    <table class="nb-table live-fleet-table">
      <thead>
        <tr>
          <th>Bus ID</th>
          <th>Route</th>
          <th>Operator / Depot</th>
          <th style="text-align:right">Speed (km/h)</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Location</th>
          <th style="text-align:center">In DB</th>
        </tr>
      </thead>
      <tbody id="live-route-tbody">
        <tr><td colspan="7" class="nb-table-empty">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</section>

<section class="charts-grid">
  <div class="chart-card">
    <h2>Bus Status</h2>
    <canvas id="busStatusChart"></canvas>
  </div>

  <div class="chart-card ">
    <h2>Delayed Buses by Route</h2>
    <canvas id="delayedByRouteChart"></canvas>
  </div>
  
  <div class="chart-card">
    <h2>High Speed Violations by Bus</h2>
    <canvas id="speedByBusChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>Revenue</h2>
    <canvas id="revenueChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>Bus Wait Time Distribution</h2>
    <canvas id="waitTimeChart"></canvas>
  </div>


  <div class="chart-card ">
    <h2>Complaints by Route</h2>
    <canvas id="complaintsRouteChart"></canvas>
  </div>
</section>

<!-- keep if you have real PHP data -->
<script id="analytics-data" type="application/json">
<?= $analyticsJson ?? '{}' ?>
</script>

<!-- central dummy values (used if PHP JSON is empty) -->
<script src="../assets/js/analytics/dummyData.js"></script>

<!-- charts (standalone, no imports) -->
<style>
  .lf-badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
  .lf-badge--red   { background:#fee2e2; color:#b91c1c; }
  .lf-badge--green { background:#dcfce7; color:#15803d; }
  .lf-badge--gray  { background:#f3f4f6; color:#6b7280; }
  /* live fleet table */
  .live-fleet-table { width:100%; border-collapse:collapse; font-size:.875rem; table-layout:fixed; }
  .live-fleet-table th, .live-fleet-table td { padding:.5rem .75rem; border-bottom:1px solid #f0f0f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .live-fleet-table th { background:#f9fafb; font-weight:700; color:#374151; text-align:left; }
  .live-fleet-table th:nth-child(1) { width:12%; }
  .live-fleet-table th:nth-child(2) { width:9%; }
  .live-fleet-table th:nth-child(3) { width:26%; }
  .live-fleet-table th:nth-child(4) { width:13%; text-align:right; }
  .live-fleet-table th:nth-child(5) { width:13%; text-align:center; }
  .live-fleet-table th:nth-child(6) { width:12%; text-align:center; }
  .live-fleet-table th:nth-child(7) { width:15%; text-align:center; }
  .live-fleet-table td:nth-child(4) { text-align:right; font-weight:600; }
  .live-fleet-table td:nth-child(5),
  .live-fleet-table td:nth-child(6),
  .live-fleet-table td:nth-child(7) { text-align:center; }
  .live-fleet-table tbody tr:nth-child(even) { background:#f9fafb; }
  .nb-table-empty { padding:.75rem; text-align:center; color:#6b7280; }
</style>
<script src="../assets/js/analytics/chartCore.js"></script>
<script src="../assets/js/analytics/busStatus.js"></script>
<script src="../assets/js/analytics/revenue.js"></script>
<script src="../assets/js/analytics/speedByBus.js"></script>
<script src="../assets/js/analytics/waitTime.js"></script>
<script src="../assets/js/analytics/delayedByRoute.js"></script>
<script src="../assets/js/analytics/complaintsRoute.js"></script>

<!-- Live fleet data (replaces dummy speed/status charts with real API data) -->
<script src="../assets/js/analytics/liveFleet.js"></script>
