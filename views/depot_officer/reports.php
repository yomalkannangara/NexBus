<?php /** @var array $kpis */
	$dq = '';
	if (!empty($from)) $dq .= '&from=' . urlencode((string)$from);
	if (!empty($to)) $dq .= '&to=' . urlencode((string)$to);
	if (!empty($filters['route'])) $dq .= '&route=' . urlencode((string)$filters['route']);
	if (!empty($filters['bus_id'])) $dq .= '&bus_reg=' . urlencode((string)$filters['bus_id']);
	if (!empty($filters['status'])) $dq .= '&status=' . urlencode((string)$filters['status']);
?>

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
		<form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:nowrap;overflow-x:auto;padding:6px 4px;">
			<input type="hidden" name="module" value="depot_officer">
			<input type="hidden" name="page" value="reports">

			<div class="filter-item" style="min-width:160px;">
				<label style="display:block;font-size:12px;margin-bottom:6px;">From</label>
				<input type="date" name="from" value="<?= htmlspecialchars($from) ?>" style="width:100%;padding:8px 10px;border:1px solid #d9c78a;border-radius:8px;">
			</div>

			<div class="filter-item" style="min-width:160px;">
				<label style="display:block;font-size:12px;margin-bottom:6px;">To</label>
				<input type="date" name="to" value="<?= htmlspecialchars($to) ?>" style="width:100%;padding:8px 10px;border:1px solid #d9c78a;border-radius:8px;">
			</div>

			<div class="filter-item" style="min-width:220px;">
				<label style="display:block;font-size:12px;margin-bottom:6px;">Route</label>
				<div class="nb-select">
					<select name="route">
						<option value="">All Routes</option>
						<?php foreach (($routes ?? []) as $r): ?>
							<option value="<?= htmlspecialchars($r['route_id']) ?>" <?= (!empty($filters['route']) && $filters['route']==$r['route_id']) ? 'selected' : '' ?>>
								<?= htmlspecialchars($r['route_no'] . ' — ' . ($r['name'] ?? '')) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="filter-item" style="min-width:180px;">
				<label style="display:block;font-size:12px;margin-bottom:6px;">Bus ID</label>
				<div class="nb-select">
					<select name="bus_id">
						<option value="">All Buses</option>
						<?php foreach (($buses ?? []) as $b): ?>
							<option value="<?= htmlspecialchars($b['reg_no']) ?>" <?= (!empty($filters['bus_id']) && $filters['bus_id']==$b['reg_no']) ? 'selected' : '' ?>>
								<?= htmlspecialchars($b['reg_no'] . ' ' . ($b['make'] ?? '')) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="filter-item" style="min-width:160px;">
				<label style="display:block;font-size:12px;margin-bottom:6px;">Status</label>
				<div class="nb-select">
					<select name="status">
						<option value="">Any Status</option>
						<option value="Completed" <?= (!empty($filters['status']) && $filters['status']=='Completed') ? 'selected' : '' ?>>Completed</option>
						<option value="InProgress" <?= (!empty($filters['status']) && $filters['status']=='InProgress') ? 'selected' : '' ?>>In Progress</option>
						<option value="Planned" <?= (!empty($filters['status']) && $filters['status']=='Planned') ? 'selected' : '' ?>>Planned</option>
						<option value="Cancelled" <?= (!empty($filters['status']) && $filters['status']=='Cancelled') ? 'selected' : '' ?>>Cancelled</option>
					</select>
				</div>
			</div>

			<div style="flex:0 0 auto;display:flex;gap:8px;align-items:flex-end;">
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
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['delayed'] ?></div>
		<div class="hint">in selected period</div>
	</article>

	<article class="kpi2 tone-green">
		<header><h3>Total Trips</h3><span class="ico">
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h6v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.78c.61-.55 1-1.34 1-2.22V5a3 3 0 0 0-3-3H7A3 3 0 0 0 4 5v11zm2-8h12v5H6V8zM7 18a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm10 0a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['trips'] ?></div>
		<div class="hint">completed trips</div>
	</article>

	<article class="kpi2 tone-orange">
		<header><h3>Avg Delay</h3><span class="ico">
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M6 2v6l4 4-4 4v6h12v-6l-4-4 4-4V2H6zM8 4h8v1.17L12 9 8 5.17V4zm8 16H8v-1.17L12 15l4 3.83V20z"/></svg>
		</span></header>
		<div class="value"><?= number_format((float)$kpis['avgDelayMin'], 1) ?><span style="font-size:0.6em;margin-left:4px;">min</span></div>
		<div class="hint">per delayed trip</div>
	</article>

	<article class="kpi2 tone-blue">
		<header><h3>Breakdowns</h3><span class="ico">
			<svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M22.7 19.3l-4.4-4.4c.5-1 .7-2.1.7-3.3 0-4.4-3.6-8-8-8-1.2 0-2.3.2-3.3.7L4.7 1.3 1.3 4.7l3.9 3.9C5 9.3 5 10.1 5 11c0 4.4 3.6 8 8 8 .9 0 1.7 0 2.1-.2l3.9 3.9 3.4-3.4-0.2-0.1zM10 18c-3.3 0-6-2.7-6-6 0-.6 0-1.1.1-1.6l7.5 7.5c-.5.1-1 .1-1.6.1z"/></svg>
		</span></header>
		<div class="value"><?= (int)$kpis['breakdowns'] ?></div>
		<div class="hint">maintenance events</div>
	</article>
</section>

<!-- ===== Analytics Overview ===== -->
<section class="charts-grid">
	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=bus_status<?= $dq ?>">View Bus Status Details</a>
		<h2>Bus Status</h2>
		<canvas id="busStatusChart" data-drill-key="bus_status" data-drill-base="/O/reports/details"></canvas>
	</div>

	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=delayed_by_route<?= $dq ?>">View Delayed Route Details</a>
		<h2>Delayed Trips by Route</h2>
		<canvas id="delayedByRouteChart" data-drill-key="delayed_by_route" data-drill-base="/O/reports/details"></canvas>
	</div>

	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=speed_by_bus<?= $dq ?>">View Speed Violation Details</a>
		<h2>Speed Violations by Bus</h2>
		<canvas id="speedByBusChart" data-drill-key="speed_by_bus" data-drill-base="/O/reports/details"></canvas>
	</div>

	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=revenue<?= $dq ?>">View Revenue Details</a>
		<h2>Revenue</h2>
		<canvas id="revenueChart" data-drill-key="revenue" data-drill-base="/O/reports/details"></canvas>
	</div>

	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=wait_time<?= $dq ?>">View Wait Time Details</a>
		<h2>Bus Wait Time Distribution</h2>
		<canvas id="waitTimeChart" data-drill-key="wait_time" data-drill-base="/O/reports/details"></canvas>
	</div>

	<div class="chart-card">
		<a class="js-chart-detail-btn" style="position:absolute;top:10px;right:10px;z-index:2" href="/O/reports/details?chart=complaints_by_route<?= $dq ?>">View Complaint Details</a>
		<h2>Complaints by Route</h2>
		<canvas id="complaintsRouteChart" data-drill-key="complaints_by_route" data-drill-base="/O/reports/details"></canvas>
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
<script src="/assets/js/analytics/complaintsRoute.js"></script>
<script src="/assets/js/analytics/drilldown.js"></script>

<style>
	/* Force KPI cards into single horizontal line - depot officer specific */
	.kpi-wrap--neo {
		display: grid !important;
		grid-template-columns: repeat(4, 1fr) !important;
		gap: 16px !important;
		margin-top: 24px !important;
		margin-bottom: 20px !important;
	}

	/* Responsive adjustments for depot officer page */
	@media (max-width: 1200px) {
		.kpi-wrap--neo {
			grid-template-columns: repeat(2, 1fr) !important;
		}
	}

	@media (max-width: 768px) {
		.kpi-wrap--neo {
			grid-template-columns: 1fr !important;
		}
	}

	/* Appealing color styling for KPI cards */
	.kpi2 {
		border: 1px solid #e5e7eb !important;
		border-top: 4px solid !important;
		border-radius: 12px !important;
		background: #fff !important;
		padding: 16px !important;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
		transition: box-shadow 0.3s ease, transform 0.3s ease !important;
	}

	.kpi2:hover {
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
		transform: translateY(-2px) !important;
	}

	.kpi2.tone-red {
		border-top-color: #d0302c !important;
		background: linear-gradient(135deg, rgba(208, 48, 44, 0.08) 0%, #fff 100%) !important;
	}

	.kpi2.tone-green {
		border-top-color: #1e9e4a !important;
		background: linear-gradient(135deg, rgba(30, 158, 74, 0.08) 0%, #fff 100%) !important;
	}

	.kpi2.tone-orange {
		border-top-color: #e06a00 !important;
		background: linear-gradient(135deg, rgba(224, 106, 0, 0.08) 0%, #fff 100%) !important;
	}

	.kpi2.tone-blue {
		border-top-color: #3b82f6 !important;
		background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, #fff 100%) !important;
	}

	.kpi2 header {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		margin-bottom: 8px !important;
	}

	.kpi2 h3 {
		margin: 0 !important;
		font-size: 15px !important;
		font-weight: 600 !important;
		color: #1f2937 !important;
	}

	.kpi2 .ico {
		width: 36px !important;
		height: 36px !important;
		border-radius: 50% !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		background: rgba(0, 0, 0, 0.05) !important;
		flex-shrink: 0 !important;
	}

	.kpi2.tone-red .ico {
		color: #d0302c !important;
	}

	.kpi2.tone-green .ico {
		color: #1e9e4a !important;
	}

	.kpi2.tone-orange .ico {
		color: #e06a00 !important;
	}

	.kpi2.tone-blue .ico {
		color: #3b82f6 !important;
	}

	.kpi2 .value {
		font-size: 28px !important;
		font-weight: 800 !important;
		margin: 6px 0 !important;
		color: #1f2937 !important;
	}

	.kpi2.tone-red .value {
		color: #d0302c !important;
	}

	.kpi2.tone-green .value {
		color: #1e9e4a !important;
	}

	.kpi2.tone-orange .value {
		color: #e06a00 !important;
	}

	.kpi2.tone-blue .value {
		color: #3b82f6 !important;
	}

	.kpi2 .hint {
		margin-top: 4px !important;
		font-size: 12px !important;
		color: #6b7280 !important;
	}

	/* Chart cards styling to match admin look */
	.chart-card {
		background: var(--card);
		border: 1px solid var(--border);
		border-radius: 12px;
		padding: 18px;
		box-shadow: var(--shadow);
		margin: 0;
	}

	.chart-card h2 {
		margin: 0 0 12px 0;
		font-size: 15px;
		font-weight: 600;
		color: #1f2937;
	}

	.chart-card canvas {
		max-height: 280px;
		width: 100% !important;
	}

	.charts-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
		gap: 16px;
		margin-top: 24px;
		margin-bottom: 20px;
	}

	@media (max-width: 1400px) {
		.charts-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (max-width: 768px) {
		.charts-grid {
			grid-template-columns: 1fr;
		}
	}

	/* Enhanced chart legend styling */
	.chart-legend {
		display: flex;
		flex-wrap: wrap;
		gap: 12px;
		justify-content: center;
		margin-top: 16px;
		padding-top: 12px;
		border-top: 1px solid #e5e7eb;
	}

	.legend-item {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 6px 12px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
		font-size: 13px;
		font-weight: 500;
		color: #374151;
		transition: all 0.2s ease;
	}

	.legend-item:hover {
		background: #f3f4f6;
		border-color: #d1d5db;
		transform: translateY(-1px);
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	}

	.legend-item i {
		width: 12px;
		height: 12px;
		border-radius: 3px;
		display: inline-block;
		flex-shrink: 0;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}
</style>
