<?php
  $pageTitle    = $pageTitle ?? 'Analytics Drilldown';
  $pageSubtitle = $pageSubtitle ?? 'Detailed analytics';
  $chartLabel   = $chartLabel ?? 'Analytics';
  $detailPath   = $detailPath ?? '/A/analytics/details';
  $backUrl      = $backUrl ?? '/A/analytics';

  $filterValues  = $filterValues ?? [];
  $filterOptions = $filterOptions ?? [];

  $detailData = $detailData ?? [];
  $kpis       = $detailData['kpis'] ?? [];
  $insights   = $detailData['insights'] ?? [];
  $charts     = $detailData['charts'] ?? [];
  $rankings   = $detailData['rankings'] ?? [];

  $curChart = (string)($filterValues['chart'] ?? 'bus_status');
  $curRoute = (string)($filterValues['route_no'] ?? '');
  $curDepot = (int)($filterValues['depot_id'] ?? 0);
  $curOwner = (int)($filterValues['owner_id'] ?? 0);
  $curBus   = (string)($filterValues['bus_reg'] ?? '');
  $curStat  = (string)($filterValues['status'] ?? '');
  $curFrom  = (string)($filterValues['from'] ?? '');
  $curTo    = (string)($filterValues['to'] ?? '');

  $hasFilter = ($curRoute !== '' || $curDepot > 0 || $curOwner > 0 || $curBus !== '' || $curStat !== '');

  $clearQs = http_build_query([
    'chart' => $curChart,
    'from'  => $curFrom,
    'to'    => $curTo,
  ]);

  // Cache buster for admin detail renderer
  $jsBase = __DIR__ . '/../../public/assets/js/analytics/admin/';
  $jsv = static function(string $base, string $file): string {
    $p = $base . $file;
    return '?v=' . (is_file($p) ? filemtime($p) : time());
  };
?>

<style>
  .adb-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
  }
  .adb-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .adb-link-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #d5d7df;
    background: #fff;
    color: #5b0e25;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
  }
  .adb-link-btn:hover {
    background: #fff7e6;
  }

  .adb-filters {
    margin-bottom: 16px;
  }
  .adb-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  @media (max-width: 1200px) {
    .adb-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  @media (max-width: 720px) {
    .adb-grid {
      grid-template-columns: 1fr;
    }
  }
  .adb-field label {
    display: block;
    margin-bottom: 6px;
    color: #4a5568;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: .02em;
    text-transform: uppercase;
  }
  .adb-field input[type="date"],
  .adb-field input[list] {
    width: 100%;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px 12px;
    background: #f8fafc;
    font-weight: 600;
  }
  .adb-field input:focus {
    border-color: #f3b944;
    outline: none;
    box-shadow: 0 0 0 3px rgba(243, 185, 68, .2);
  }
  .adb-submit {
    align-self: end;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .adb-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }
  @media (max-width: 1100px) {
    .adb-kpis {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 700px) {
    .adb-kpis {
      grid-template-columns: 1fr;
    }
  }
  .adb-kpi {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    border-left-width: 6px;
    padding: 14px 16px;
    box-shadow: 0 8px 20px rgba(16, 24, 40, .06);
  }
  .adb-kpi h3 {
    margin: 0 0 6px;
    font-size: 13px;
    color: #334155;
    font-weight: 700;
  }
  .adb-kpi .v {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
  }
  .adb-kpi .h {
    margin-top: 4px;
    font-size: 12px;
    color: #64748b;
  }
  .adb-kpi.tone-good { border-left-color: #16a34a; }
  .adb-kpi.tone-warn { border-left-color: #f59e0b; }
  .adb-kpi.tone-bad { border-left-color: #dc2626; }
  .adb-kpi.tone-neutral { border-left-color: #2563eb; }

  .adb-insights {
    background: linear-gradient(135deg, #fffdf7 0%, #fff 100%);
    border: 1px solid #f2d9a6;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(128, 20, 60, .08);
    padding: 14px 16px;
    margin-bottom: 16px;
  }
  .adb-insights h2 {
    margin: 0 0 10px;
    color: #7a102f;
    font-size: 15px;
  }
  .adb-insights ul {
    margin: 0;
    padding-left: 18px;
  }
  .adb-insights li {
    margin-bottom: 6px;
    color: #374151;
    font-size: 13px;
  }

  .adb-charts {
    margin-bottom: 16px;
  }
  .adb-card {
    text-align: left;
    min-height: 330px;
  }
  .adb-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
  }
  .adb-card h2 {
    margin: 0;
    color: #1f2937;
    white-space: normal;
    line-height: 1.25;
  }
  .adb-type {
    font-size: 11px;
    font-weight: 700;
    color: #7a102f;
    background: #fef3c7;
    border: 1px solid #f3d089;
    border-radius: 999px;
    padding: 3px 8px;
    white-space: nowrap;
  }
  .adb-chart-wrap {
    height: 250px;
    position: relative;
  }
  .adb-chart-wrap canvas {
    width: 100% !important;
    height: 100% !important;
  }

  .adb-rankings {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 8px;
  }
  @media (max-width: 1200px) {
    .adb-rankings {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 760px) {
    .adb-rankings {
      grid-template-columns: 1fr;
    }
  }
  .adb-rank-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
    padding: 12px 14px;
  }
  .adb-rank-card h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #1f2937;
  }
  .adb-rank-list {
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .adb-rank-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px dashed #edf2f7;
    font-size: 13px;
  }
  .adb-rank-list li:last-child {
    border-bottom: none;
  }
  .adb-rank-list .lbl {
    color: #374151;
    max-width: 70%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .adb-rank-list .val {
    font-weight: 800;
    color: #111827;
  }
  .adb-rank-card.tone-good { border-top: 4px solid #16a34a; }
  .adb-rank-card.tone-warn { border-top: 4px solid #f59e0b; }
  .adb-rank-card.tone-bad { border-top: 4px solid #dc2626; }
  .adb-rank-card.tone-neutral { border-top: 4px solid #2563eb; }
</style>

<section class="page-hero">
  <h1><?= htmlspecialchars($pageTitle) ?></h1>
  <p><?= htmlspecialchars($pageSubtitle) ?></p>
</section>

<div class="adb-toolbar">
  <div style="font-size:13px;color:#64748b;font-weight:700">Section: <?= htmlspecialchars($chartLabel) ?></div>
  <div class="adb-actions">
    <a href="<?= htmlspecialchars($backUrl) ?>" class="adb-link-btn">Back to Analytics</a>
    <button type="button" id="adb-export" class="adb-link-btn">Export CSV</button>
  </div>
</div>

<section class="filters-panel adb-filters">
  <div class="filters-title">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 4h18l-7 8v5l-4 3v-8L3 4z"/></svg>
    <span>Filters</span>
    <?php if ($hasFilter): ?>
      <a href="<?= htmlspecialchars($detailPath . '?' . $clearQs) ?>" class="filter-clear-link">Clear</a>
    <?php endif; ?>
  </div>

  <form method="get" action="<?= htmlspecialchars($detailPath) ?>" class="filters-grid-3 adb-grid" id="adb-filter-form">
    <input type="hidden" name="chart" value="<?= htmlspecialchars($curChart) ?>">

    <div class="adb-field">
      <label for="adb-route">Route</label>
      <div class="nb-select">
        <select id="adb-route" name="route_no">
          <option value="">All Routes</option>
          <?php foreach (($filterOptions['routes'] ?? []) as $r):
            $rno = (string)($r['route_no'] ?? '');
          ?>
            <option value="<?= htmlspecialchars($rno) ?>" <?= $curRoute === $rno ? 'selected' : '' ?>><?= htmlspecialchars($rno) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="adb-field">
      <label for="adb-depot">SLTB Depot</label>
      <div class="nb-select">
        <select id="adb-depot" name="depot_id">
          <option value="0">All Depots</option>
          <?php foreach (($filterOptions['depots'] ?? []) as $d):
            $id = (int)($d['id'] ?? 0);
          ?>
            <option value="<?= $id ?>" <?= $curDepot === $id ? 'selected' : '' ?>><?= htmlspecialchars((string)($d['name'] ?? 'Depot')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="adb-field">
      <label for="adb-owner">Private Company</label>
      <div class="nb-select">
        <select id="adb-owner" name="owner_id">
          <option value="0">All Companies</option>
          <?php foreach (($filterOptions['owners'] ?? []) as $o):
            $id = (int)($o['id'] ?? 0);
          ?>
            <option value="<?= $id ?>" <?= $curOwner === $id ? 'selected' : '' ?>><?= htmlspecialchars((string)($o['name'] ?? 'Owner')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="adb-field">
      <label for="adb-bus">Bus Reg No</label>
      <input id="adb-bus" name="bus_reg" list="adb-bus-list" value="<?= htmlspecialchars($curBus) ?>" placeholder="Any bus">
      <datalist id="adb-bus-list">
        <?php foreach (($filterOptions['buses'] ?? []) as $b):
          $reg = (string)($b['reg_no'] ?? '');
          if ($reg === '') continue;
        ?>
          <option value="<?= htmlspecialchars($reg) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="adb-field">
      <label for="adb-status">Operational Status</label>
      <div class="nb-select">
        <select id="adb-status" name="status">
          <?php foreach (($filterOptions['statuses'] ?? []) as $s):
            $value = (string)($s['value'] ?? '');
            $label = (string)($s['label'] ?? $value);
          ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $curStat === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="adb-field">
      <label for="adb-from">From</label>
      <input id="adb-from" type="date" name="from" value="<?= htmlspecialchars($curFrom) ?>">
    </div>

    <div class="adb-field">
      <label for="adb-to">To</label>
      <input id="adb-to" type="date" name="to" value="<?= htmlspecialchars($curTo) ?>">
    </div>

    <div class="adb-submit">
      <button class="btn primary" type="submit">Apply</button>
    </div>
  </form>
</section>

<section class="adb-kpis">
  <?php if (empty($kpis)): ?>
    <article class="adb-kpi tone-neutral">
      <h3>No KPI Data</h3>
      <p class="v">0</p>
      <div class="h">Try adjusting filters.</div>
    </article>
  <?php else: ?>
    <?php foreach ($kpis as $k): ?>
      <article class="adb-kpi tone-<?= htmlspecialchars((string)($k['tone'] ?? 'neutral')) ?>">
        <h3><?= htmlspecialchars((string)($k['title'] ?? 'Metric')) ?></h3>
        <p class="v"><?= htmlspecialchars((string)($k['value'] ?? '0')) ?></p>
        <div class="h"><?= htmlspecialchars((string)($k['hint'] ?? '')) ?></div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="adb-insights">
  <h2>Actionable Insights</h2>
  <ul>
    <?php if (empty($insights)): ?>
      <li>No insight generated for the selected filters.</li>
    <?php else: ?>
      <?php foreach ($insights as $line): ?>
        <li><?= htmlspecialchars((string)$line) ?></li>
      <?php endforeach; ?>
    <?php endif; ?>
  </ul>
</section>

<section class="charts-grid adb-charts">
  <?php if (empty($charts)): ?>
    <article class="chart-card adb-card" style="grid-column: span 12">
      <h2>No charts available for this filter combination.</h2>
    </article>
  <?php else: ?>
    <?php foreach ($charts as $i => $chart):
      $ctype = (string)($chart['type'] ?? 'bar');
    ?>
      <article class="chart-card adb-card" style="<?= $ctype === 'heatmap' ? 'grid-column: span 12' : '' ?>">
        <div class="adb-card-head">
          <h2><?= htmlspecialchars((string)($chart['title'] ?? ('Chart ' . ($i + 1)))) ?></h2>
          <span class="adb-type"><?= htmlspecialchars(strtoupper($ctype)) ?></span>
        </div>
        <div class="adb-chart-wrap">
          <canvas id="adb-chart-<?= (int)$i ?>" data-chart-index="<?= (int)$i ?>"></canvas>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="adb-rankings">
  <?php if (empty($rankings)): ?>
    <article class="adb-rank-card tone-neutral">
      <h3>No ranking data</h3>
      <ul class="adb-rank-list"><li><span class="lbl">Adjust filters to view performers</span><strong class="val">--</strong></li></ul>
    </article>
  <?php else: ?>
    <?php foreach ($rankings as $ranking):
      $tone = (string)($ranking['tone'] ?? 'neutral');
      $suffix = (string)($ranking['valueSuffix'] ?? '');
      $items = $ranking['items'] ?? [];
    ?>
      <article class="adb-rank-card tone-<?= htmlspecialchars($tone) ?>">
        <h3><?= htmlspecialchars((string)($ranking['title'] ?? 'Ranking')) ?></h3>
        <ul class="adb-rank-list">
          <?php if (empty($items)): ?>
            <li><span class="lbl">No data</span><strong class="val">0</strong></li>
          <?php else: ?>
            <?php foreach ($items as $item): ?>
              <li>
                <span class="lbl"><?= htmlspecialchars((string)($item['label'] ?? '')) ?></span>
                <strong class="val"><?= htmlspecialchars((string)($item['value'] ?? '0') . $suffix) ?></strong>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<script id="admin-analytics-detail-data" type="application/json">
<?= $detailJson ?? '{}' ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
  var depot = document.getElementById('adb-depot');
  var owner = document.getElementById('adb-owner');
  if (depot && owner) {
    depot.addEventListener('change', function(){
      if (depot.value && depot.value !== '0') owner.value = '0';
    });
    owner.addEventListener('change', function(){
      if (owner.value && owner.value !== '0') depot.value = '0';
    });
  }
})();
</script>
<script src="/assets/js/analytics/admin/detailCharts.js<?= $jsv($jsBase, 'detailCharts.js') ?>"></script>
