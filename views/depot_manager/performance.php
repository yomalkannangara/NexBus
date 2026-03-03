<?php
  $kpi     = $kpi     ?? ['delayed_buses'=>0,'average_rating'=>0,'speed_violations'=>0,'long_wait_rate'=>0];
  $filters = $filters ?? ['route_no'=>'','bus_reg'=>''];
  $curRno  = $filters['route_no'] ?? '';
  $curBus  = $filters['bus_reg']  ?? '';
  $hasFilter = ($curRno !== '' || $curBus !== '');
?>
<style>
/* ===== CSS Variables (from admin.css) ===== */
:root {
  --bg:#f6f7f9; --card:#fff; --primary:#80143c; --accent:#e4b74f;
  --text:#2b2b2b; --border:#e8d39a; --maroon:#80143c; --maroon-2:#80143c;
  --gold:#f3b944; --gold-soft:#f3b944; --muted:#6b7280;
  --ring:rgba(231,179,67,.55); --radius:14px;
  --shadow:0 10px 28px rgba(17,24,39,.08);
  --thickshadow:0 12px 40px rgba(17,24,39,.2);
}

/* ===== Hero ===== */
.page-hero { margin-bottom:18px; padding:18px 20px; color:#fff; background:var(--maroon);
  border-left:3px solid var(--gold); border-radius:10px; box-shadow:var(--shadow); }
.page-hero h1 { margin:0 0 6px; font-weight:400; }
.page-hero p  { margin:0; opacity:.85; font-size:14px; }

/* ===== Filters panel ===== */
.filters-panel { background:var(--card); border:1px solid var(--border);
  border-radius:18px; padding:18px; box-shadow:var(--shadow); margin:12px 0 18px; }
.filters-title { display:flex; align-items:center; gap:10px; color:var(--maroon);
  font-weight:700; margin:2px 0 14px; }
.filters-title svg { color:var(--maroon); }
.filter-clear-link { margin-left:auto; font-size:12px; font-weight:600; color:var(--maroon);
  text-decoration:none; opacity:.7; border:1px solid var(--maroon);
  border-radius:8px; padding:2px 10px; transition:opacity .15s; }
.filter-clear-link:hover { opacity:1; background:var(--maroon); color:#fff; }
.filters-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.filters-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:18px; }
@media (max-width:900px) { .filters-grid-3,.filters-grid-2 { grid-template-columns:1fr; } }
.field label { display:block; margin-bottom:6px; color:var(--maroon); font-weight:600; }
.nb-select { position:relative; }
.nb-select select { width:100%; appearance:none; -webkit-appearance:none; -moz-appearance:none;
  background:#f5f6f8; border:2px solid var(--border); border-radius:12px;
  padding:10px 40px 10px 12px;
  font:500 14px/1.2 ui-sans-serif,system-ui,'Segoe UI',Roboto; outline:none; color:#374151; }
.nb-select::after { content:""; position:absolute; right:12px; top:50%;
  transform:translateY(-50%) rotate(45deg); width:8px; height:8px;
  border-right:2px solid #9aa0a6; border-bottom:2px solid #9aa0a6; pointer-events:none; }
.nb-select select:focus { border-color:var(--gold); box-shadow:0 0 0 3px var(--ring); }

/* ===== KPI cards ===== */
.kpi-wrap { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
.kpi-wrap--neo { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:16px; }
@media (max-width:1100px) { .kpi-wrap--neo { grid-template-columns:repeat(2,1fr); } }
@media (max-width:640px)  { .kpi-wrap--neo { grid-template-columns:1fr; } }
.kpi2 { --tone:#80143c; background:var(--card); border-radius:18px;
  border:1px solid var(--tone); border-left:5px solid var(--tone);
  box-shadow:var(--shadow); padding:12px 18px; }
.kpi2 header { display:flex; align-items:center; justify-content:space-between; margin-bottom:3px; }
.kpi2 h3 { margin:0; font:600 16px/1.1 ui-sans-serif; color:#384256; }
.kpi2 .ico { width:40px; height:40px; border-radius:999px;
  display:grid; place-items:center; color:var(--tone); }
.kpi2 .value { font:800 28px/1.1 ui-sans-serif; color:var(--tone); }
.kpi2 .hint  { margin-top:4px; font-size:12px; color:#9aa0a6; }
.kpi2.tone-red    { --tone:#d0302c; }
.kpi2.tone-green  { --tone:#1e9e4a; }
.kpi2.tone-orange { --tone:#e06a00; }
.kpi2.tone-blue   { --tone:#3b82f6; }

/* ===== Charts grid ===== */
.charts-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr));
  gap:16px; align-items:start; }
.chart-card { grid-column:span 6; background:var(--card); border:1px solid var(--border);
  border-radius:var(--radius); box-shadow:var(--shadow);
  padding:20px; text-align:center; margin:0; max-width:none; overflow:hidden; }
.chart-card.span-2 { grid-column:span 12; }
@media (max-width:980px) { .chart-card { grid-column:span 12; } }
.chart-card h2 { margin:0 0 12px; font-weight:600; color:var(--primary);
  font-size:clamp(14px,1.6vw,18px); line-height:1.2;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chart-card canvas { display:block; width:100% !important; }
.chart-legend { display:flex; flex-wrap:wrap; gap:8px 12px; justify-content:center; margin-top:6px; }
.chart-legend .legend-item { display:inline-flex; align-items:center; gap:8px; font-size:12px; color:var(--text); }
.chart-legend .legend-item i { width:10px; height:10px; border-radius:2px; display:inline-block; }
.chart-card canvas, #busStatusChart, #waitTimeChart {
  width:auto !important; max-width:100%;
  height:auto !important; aspect-ratio:auto !important; max-height:none !important; display:block; }

/* ===== Live fleet table ===== */
.lf-badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
.lf-badge--red   { background:#fee2e2; color:#b91c1c; }
.lf-badge--green { background:#dcfce7; color:#15803d; }
.lf-badge--gray  { background:#f3f4f6; color:#6b7280; }
.live-fleet-table { width:100%; border-collapse:collapse; font-size:.875rem; table-layout:fixed; }
.live-fleet-table th, .live-fleet-table td { padding:.5rem .75rem; border-bottom:1px solid #f0f0f0;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
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

<section class="page-hero"><h1>SLTB Fleet Analytics</h1><p>Performance metrics and operational insights for SLTB buses</p></section>

<!-- ===== Analytics Filters ===== -->
<section class="filters-panel">
  <div class="filters-title">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 4h18l-7 8v5l-4 3v-8L3 4z"/></svg>
    <span>Analytics Filters</span>
    <?php if ($hasFilter): ?>
      <a href="/M/performance" class="filter-clear-link" title="Clear all filters">&#10005; Clear</a>
    <?php endif; ?>
  </div>

  <form method="get" action="/M/performance" class="filters-grid-3" id="analytics-filter-form">
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

    <!-- Bus -->
    <div class="field">
      <label for="ft-bus">Bus</label>
      <div class="nb-select">
        <select id="ft-bus" name="bus_reg" onchange="this.form.submit()">
          <option value="">All Buses</option>
          <?php foreach(($buses ?? []) as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"
              <?= ($curBus === $b['reg_no']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['reg_no']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- spacer -->
    <div></div>
  </form>
</section>

<!-- ===== KPI cards ===== -->
<section class="kpi-wrap kpi-wrap--neo">
  <article class="kpi2 tone-red">
    <header><h3>Delayed Buses Today</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg>
    </span></header>
    <div class="value" id="kpi-delayed"><?= (int)($kpi['delayed_buses'] ?? 0) ?></div>
    <div class="hint">Live from database</div>
  </article>

  <article class="kpi2 tone-green">
    <header><h3>Average Driver Rating</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/></svg>
    </span></header>
    <div class="value" id="kpi-rating"><?= (($kpi['average_rating'] ?? 0) > 0) ? number_format((float)$kpi['average_rating'],1) : '&ndash;' ?></div>
    <div class="hint">Reliability index (/10)</div>
  </article>

  <article class="kpi2 tone-orange">
    <header><h3>Speed Violations</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M14 3L3 14h7v7l11-11h-7z"/></svg>
    </span></header>
    <div class="value" id="kpi-speed"><?= (int)($kpi['speed_violations'] ?? 0) ?: '&ndash;' ?></div>
    <div class="hint">Live &middot; buses over limit</div>
  </article>

  <article class="kpi2 tone-blue">
    <header><h3>Long Wait Times</h3><span class="ico">
      <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg>
    </span></header>
    <div class="value" id="kpi-wait"><?= (int)($kpi['long_wait_rate'] ?? 0) ?>%</div>
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

<!-- ===== Live Bus Fleet Table ===== -->
<section class="chart-card" style="margin-bottom:1.5rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
    <h2 style="margin:0">Live Bus Fleet</h2>
    <span style="font-size:.75rem;color:#6b7280" id="live-updated-at-table"></span>
  </div>
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

<!-- ===== Analytics Charts ===== -->
<section class="charts-grid">
  <div class="chart-card">
    <h2>Bus Status</h2>
    <canvas id="busStatusChart"></canvas>
  </div>

  <div class="chart-card">
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

  <div class="chart-card">
    <h2>Complaints by Route</h2>
    <canvas id="complaintsRouteChart"></canvas>
  </div>
</section>

<!-- Server data for charts -->
<script id="analytics-data" type="application/json">
<?= $analyticsJson ?? '{}' ?>
</script>

<?php
  $jsBase = __DIR__ . '/../../public/assets/js/analytics/';
  $jsv = static function(string $base, string $file): string {
    $p = $base . $file;
    return '?v=' . (is_file($p) ? filemtime($p) : time());
  };
?>
<!-- Dummy values (fallback when PHP data is empty) -->
<script src="/assets/js/analytics/dummyData.js<?= $jsv($jsBase,'dummyData.js') ?>"></script>

<!-- Charts -->
<script src="/assets/js/analytics/chartCore.js<?= $jsv($jsBase,'chartCore.js') ?>"></script>
<script src="/assets/js/analytics/busStatus.js<?= $jsv($jsBase,'busStatus.js') ?>"></script>
<script src="/assets/js/analytics/revenue.js<?= $jsv($jsBase,'revenue.js') ?>"></script>
<script src="/assets/js/analytics/speedByBus.js<?= $jsv($jsBase,'speedByBus.js') ?>"></script>
<script src="/assets/js/analytics/waitTime.js<?= $jsv($jsBase,'waitTime.js') ?>"></script>
<script src="/assets/js/analytics/delayedByRoute.js<?= $jsv($jsBase,'delayedByRoute.js') ?>"></script>
<script src="/assets/js/analytics/complaintsRoute.js<?= $jsv($jsBase,'complaintsRoute.js') ?>"></script>

<!-- Live fleet (same as admin) -->
<script src="/assets/js/analytics/liveFleet.js<?= $jsv($jsBase,'liveFleet.js') ?>"></script>
