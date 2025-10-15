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
  <div class="chart-card equal">
    <h2>Bus Status</h2>
    <!-- slightly shorter so legend + canvas ≈ other cards -->
    <canvas id="busStatusChart" width="720"  height="360"></canvas>
  </div>

  <div class="chart-card equal">
    <h2>On-Time Performance</h2>
    <canvas id="onTimeChart" width="720" height="360"></canvas>
  </div>

  <div class="chart-card equal">
    <h2>Revenue</h2>
    <canvas id="revenueChart" width="720" height="360"></canvas>
  </div>

  <div class="chart-card equal">
    <h2>Complaints</h2>
    <canvas id="complaintsChart" width="720" height="360"></canvas>
  </div>

  <div class="chart-card span-2 equal">
    <h2>Utilization</h2>
    <canvas id="utilizationChart" width="1280" height="480"></canvas>
  </div>
</section>

<!-- OPTIONAL: your PHP JSON (keep if you have real data) -->
<script id="analytics-data" type="application/json">
<?= $analyticsJson ?? '{}' ?>
</script>

<!-- 1) Dummy data (used only when your JSON is empty/missing) -->
<script src="../assets/js/analytics/dummyData.js"></script>

<!-- 2) Charts (stand-alone, no imports) -->
<script src="../assets/js/analytics/busStatus.js"></script>
<script src="../assets/js/analytics/onTime.js"></script>
<script src="../assets/js/analytics/revenue.js"></script>
<script src="../assets/js/analytics/complaints.js"></script>
<script src="../assets/js/analytics/utilization.js"></script>
