<?php /** @var array $kpis */ ?>

<section class="page-hero">
	<h1>Depot Reports</h1>
	<p>Depot performance metrics and operational insights</p>
</section>

<!-- ===== Topic Area: Filters ===== -->
<section class="filters-panel">
	<div class="filters-title">
		<!-- funnel icon -->
		<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
			<path fill="currentColor" d="M3 4h18l-7 8v5l-4 3v-8L3 4z"/>
		</svg>
		<span>Report Filters</span>
	</div>

	<div class="filters-grid-3">
		<form method="get" style="display:contents;">
			<input type="hidden" name="module" value="depot_officer">
			<input type="hidden" name="page" value="reports">

			<div class="field">
				<label>From</label>
				<input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
			</div>

			<div class="field">
				<label>To</label>
				<input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
			</div>

			<div style="display:flex;gap:8px;align-items:flex-end;">
				<button class="button" type="submit">View</button>
				<a class="button outline" href="?module=depot_officer&page=reports&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">Export CSV</a>
			</div>
		</form>
	</div>
</section>

<!-- ===== Topic Area: KPI cards ===== -->
<section class="kpi-wrap kpi-wrap--neo">
	<article class="kpi2 tone-red">
		<header><h3>Delayed Trips</h3><span class="ico">
			<!-- clock -->
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['delayed'] ?></div>
		<div class="hint">in selected range</div>
	</article>

	<article class="kpi2 tone-green">
		<header><h3>Total Trips</h3><span class="ico">
			<!-- activity -->
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M3 12a9 9 0 0 1 9-9a9.75 9.75 0 0 1 6.74 2.74L21 8m0 0v-4m0 4h-4m-2.26 8.26A9 9 0 1 1 21 12"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['trips'] ?></div>
		<div class="hint">completed trips</div>
	</article>

	<article class="kpi2 tone-orange">
		<header><h3>Avg Delay</h3><span class="ico">
			<!-- clock -->
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg>
		</span></header>
		<div class="value"><?= number_format((float)$kpis['avgDelayMin'], 1) ?><span style="font-size:0.6em;margin-left:4px;">min</span></div>
		<div class="hint">per delayed trip</div>
	</article>

	<article class="kpi2 tone-blue">
		<header><h3>Breakdowns</h3><span class="ico">
			<!-- alert-circle -->
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8m3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5m-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11m3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['breakdowns'] ?></div>
		<div class="hint">maintenance events</div>
	</article>
</section>

<!-- ===== Analytics Overview ===== -->
<section class="charts-grid">
	<div class="chart-card">
		<h2>Bus Status</h2>
		<canvas id="busStatusChart"></canvas>
	</div>

	<div class="chart-card">
		<h2>Delayed Trips by Route</h2>
		<canvas id="delayedByRouteChart"></canvas>
	</div>

	<div class="chart-card">
		<h2>Speed Violations by Bus</h2>
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
</section>

<!-- Analytics JSON data: page can pass $analyticsJson or this falls back to {} -->
<script id="analytics-data" type="application/json">
	<?= $analyticsJson ?? '{}' ?>
</script>

<!-- Charts assets (absolute paths to public assets) -->
<script src="/assets/js/analytics/dummyData.js"></script>
<script src="/assets/js/analytics/chartCore.js"></script>
<script src="/assets/js/analytics/busStatus.js"></script>
<script src="/assets/js/analytics/revenue.js"></script>
<script src="/assets/js/analytics/speedByBus.js"></script>
<script src="/assets/js/analytics/waitTime.js"></script>
<script src="/assets/js/analytics/delayedByRoute.js"></script>

