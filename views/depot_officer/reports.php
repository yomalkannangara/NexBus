<?php /** @var array $kpis */ ?>
<div class="container">

	<!-- Page title banner -->
	<div class="title-banner">
		<h1>Depot Reports</h1>
		<p>View performance KPIs and drill into trends with built‑in charts</p>
	</div>

	<!-- Filters card -->
	<div class="card accent-rose filter-card" style="margin-bottom:14px;">
		<form method="get" class="filters" style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:10px;align-items:end;">
			<input type="hidden" name="module" value="depot_officer">
			<input type="hidden" name="page" value="reports">

			<label>
				<span class="metric-title">From</span>
				<input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
			</label>

			<label>
				<span class="metric-title">To</span>
				<input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
			</label>

			<button class="button" type="submit">View</button>
			<a class="button outline" href="?module=depot_officer&page=reports&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">Export CSV</a>
		</form>
	</div>

	<!-- KPI cards -->
	<div class="cards" style="margin-bottom:14px;">
		<div class="card">
			<div class="metric-title">Trips Logged</div>
			<div class="metric-value"><?= (int)$kpis['trips'] ?></div>
			<div class="metric-sub">in selected range</div>
		</div>
		<div class="card">
			<div class="metric-title">Delays</div>
			<div class="metric-value"><?= (int)$kpis['delayed'] ?></div>
			<div class="metric-sub">reported delays</div>
		</div>
		<div class="card">
			<div class="metric-title">Breakdowns</div>
			<div class="metric-value"><?= (int)$kpis['breakdowns'] ?></div>
			<div class="metric-sub">maintenance events</div>
		</div>
		<div class="card">
			<div class="metric-title">Avg Delay (min)</div>
			<div class="metric-value"><?= number_format((float)$kpis['avgDelayMin'], 2) ?></div>
			<div class="metric-sub">per delayed trip</div>
		</div>
		<div class="card">
			<div class="metric-title">Speed Violations</div>
			<div class="metric-value"><?= (int)$kpis['speedViolations'] ?></div>
			<div class="metric-sub">over threshold</div>
		</div>
	</div>

	<!-- Analytics charts (reused from analytics page) -->
	<div class="card" style="margin-bottom:10px;">
		<h2 style="margin:0 0 10px;">Analytics Overview</h2>
		<section class="charts-grid">
			<div class="chart-card">
				<h3>Bus Status</h3>
				<canvas id="busStatusChart"></canvas>
			</div>

			<div class="chart-card">
				<h3>Delayed Buses by Route</h3>
				<canvas id="delayedByRouteChart"></canvas>
			</div>

			<div class="chart-card">
				<h3>High Speed Violations by Bus</h3>
				<canvas id="speedByBusChart"></canvas>
			</div>

			<div class="chart-card">
				<h3>Revenue</h3>
				<canvas id="revenueChart"></canvas>
			</div>

			<div class="chart-card">
				<h3>Bus Wait Time Distribution</h3>
				<canvas id="waitTimeChart"></canvas>
			</div>

			<div class="chart-card">
				<h3>Complaints by Route</h3>
				<canvas id="complaintsRouteChart"></canvas>
			</div>
		</section>
	</div>

	<!-- Analytics JSON data: page can pass $analyticsJson or this falls back to {} -->
	<script id="analytics-data" type="application/json">
		<?= $analyticsJson ?? '{}' ?>
	</script>

	<!-- Minimal page-scoped styles for charts layout (non-invasive) -->
	<style>
		.charts-grid{ display:grid; gap:12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
		@media (min-width: 1200px){ .charts-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); } }
		@media (max-width: 720px){ .charts-grid{ grid-template-columns: 1fr; } }
		.chart-card{ background:#fff; border-radius:12px; box-shadow: var(--shadow, 0 10px 28px rgba(17,24,39,.08)); padding:10px; border-left:6px solid var(--gold, #f3b944); }
		.chart-card h3{ margin:0 0 6px; font-size:15px; color:var(--maroon, #80143c); }
		.chart-card canvas{ width:100%; height:200px; }
	</style>

	<!-- Charts assets (absolute paths to public assets) -->
	<script src="/assets/js/analytics/dummyData.js"></script>
	<script src="/assets/js/analytics/chartCore.js"></script>
	<script src="/assets/js/analytics/busStatus.js"></script>
	<script src="/assets/js/analytics/revenue.js"></script>
	<script src="/assets/js/analytics/speedByBus.js"></script>
	<script src="/assets/js/analytics/waitTime.js"></script>
	<script src="/assets/js/analytics/delayedByRoute.js"></script>
	<script src="/assets/js/analytics/complaintsRoute.js"></script>

</div>

