<?php $kpi = $kpi ?? ['delayedToday'=>0,'avgRating'=>0,'speedViol'=>0,'longWaitPct'=>0]; ?>
<section class="page-hero"><h1>Analytics Dashboard</h1><p>Bus performance metrics and operational insights</p></section>
<!-- ===== Topic Area: Filters ===== -->
<section class="filters-panel">
  <div class="filters-title">
    <!-- funnel icon -->
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
      <path fill="currentColor" d="M3 4h18l-7 8v5l-4 3v-8L3 4z"/>
    </svg>
    <span>Analytics Filters</span>
  </div>

  <div class="filters-grid-3">
    <div class="field">
      <label>Depot</label>
      <div class="nb-select">
        <select>
          <option>All Depots</option>
          <option>Colombo</option>
          <option>Kandy</option>
          <option>Galle</option>
        </select>
      </div>
    </div>

    <div class="field">
      <label>Bus Owner</label>
      <div class="nb-select">
        <select>
          <option>All Bus Owners</option>
          <option>Owner A</option>
          <option>Owner B</option>
        </select>
      </div>
    </div>

    <div class="field">
      <label>Route</label>
      <div class="nb-select">
        <select>
          <option>All Routes</option>
          <?php foreach(($routes ?? []) as $r): ?>
            <option value="<?= htmlspecialchars($r['route_no']) ?>"><?= htmlspecialchars($r['route_no']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
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

<!-- ===== Live Route Summary Table ===== -->
<section class="chart-card" style="margin-bottom:1.5rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
    <h2 style="margin:0">Live Route Summary</h2>
    <span style="font-size:.75rem;color:#6b7280" id="live-updated-at-table"></span>
  </div>
  <div style="overflow-x:auto">
    <table class="nb-table" style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr style="border-bottom:2px solid #e5e7eb">
          <th style="padding:.5rem .75rem;text-align:left">Route</th>
          <th style="padding:.5rem .75rem;text-align:left">Buses Live</th>
          <th style="padding:.5rem .75rem;text-align:left">Avg Speed</th>
          <th style="padding:.5rem .75rem;text-align:left">Violations</th>
        </tr>
      </thead>
      <tbody id="live-route-tbody">
        <tr><td colspan="4" style="padding:.75rem;text-align:center;color:#6b7280">Loading…</td></tr>
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
  .nb-table tbody tr:nth-child(even) { background:#f9fafb; }
  .nb-table td, .nb-table th { border-bottom:1px solid #f0f0f0; padding:.5rem .75rem; }
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
