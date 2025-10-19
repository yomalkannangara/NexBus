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
          <option>138</option>
          <option>99</option>
          <option>120</option>
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
    <div class="value" id="kpi-delayed">47</div>
    <div class="hint">Filtered results</div>
  </article>

  <article class="kpi2 tone-green">
    <header><h3>Average Driver Rating</h3><span class="ico">
      <!-- users -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11a4 4 0 1 0-4-4a4 4 0 0 0 4 4m-8 0a3 3 0 1 0-3-3a3 3 0 0 0 3 3m8 2a7 7 0 0 1 7 7H9a7 7 0 0 1 7-7m-8 1a5 5 0 0 1 5 5H1a5 5 0 0 1 5-5"/></svg>
    </span></header>
    <div class="value" id="kpi-rating">8.0</div>
    <div class="hint">Filtered average</div>
  </article>

  <article class="kpi2 tone-orange">
    <header><h3>Speed Violations</h3><span class="ico">
      <!-- bolt -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M14 3L3 14h7v7l11-11h-7z"/></svg>
    </span></header>
    <div class="value" id="kpi-speed">75</div>
    <div class="hint">Filtered data</div>
  </article>

  <article class="kpi2 tone-blue">
    <header><h3>Long Wait Times</h3><span class="ico">
      <!-- trending up -->
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg>
    </span></header>
    <div class="value" id="kpi-wait">15%</div>
    <div class="hint">Over 10 minutes</div>
  </article>
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
<script src="../assets/js/analytics/chartCore.js"></script>
<script src="../assets/js/analytics/busStatus.js"></script>
<script src="../assets/js/analytics/revenue.js"></script>
<script src="../assets/js/analytics/speedByBus.js"></script>
<script src="../assets/js/analytics/waitTime.js"></script>
<script src="../assets/js/analytics/delayedByRoute.js"></script>
<script src="../assets/js/analytics/complaintsRoute.js"></script>
