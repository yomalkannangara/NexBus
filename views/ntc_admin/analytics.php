<section class="page-hero"><h1>Analytics Dashboard</h1><p>Bus performance metrics and operational insights</p></section>
<section class="kpi-wrap">
  <div class="kpi-card alert"><h3>Delayed Buses Today</h3><div class="num"></div></div>
  <div class="kpi-card ok"><h3>Average Driver Rating</h3><div class="num"></div></div>
  <div class="kpi-card warn"><h3>Speed Violations</h3><div class="num"></div></div>
  <div class="kpi-card info"><h3>Long Wait Times</h3><div class="num">%</div></div>
</section>


<!-- Give canvases explicit width/height attributes -->
<div class="chart-card">
  <h2>Bus Status</h2>
  <canvas id="busStatusChart" width="320" height="320"></canvas>
</div>

<div class="chart-card">
  <h2>On-Time Performance</h2>
  <canvas id="onTimeChart" width="500" height="320"></canvas>
</div>
<div class="chart-card">
  <h2>revenueChart</h2>
  <canvas id="busStatusChart" width="320" height="320"></canvas>
</div>

<div class="chart-card">
  <h2>On-Time Performance</h2>
  <canvas id="revenueChart" width="500" height="320"></canvas>
</div>
<div class="chart-card">
  <h2>complaintsChart</h2>
  <canvas id="complaintsChart" width="320" height="320"></canvas>
</div>

<div class="chart-card">
  <h2>OutilizationChart</h2>
  <canvas id="utilizationChart" width="500" height="320"></canvas>
</div>


<!-- Embed JSON safely -->
<script id="analytics-data" type="application/json">
<?= $analyticsJson ?>
</script>


<!-- Separate JS files -->
<script src="../assets/js/analytics/busStatus.js"></script>
<script src="../assets/js/analytics/onTime.js"></script>
<script src="../assets/js/analytics/revenue.js"></script>
<script src="../assets/js/analytics/complaints.js"></script>
<script src="../assets/js/analytics/utilization.js"></script>
