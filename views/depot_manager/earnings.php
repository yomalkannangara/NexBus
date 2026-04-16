<?php
// Redesigned Earnings Dashboard - Depot Manager
// Data from controller: $top, $buses, $month, $revenueTrend, $busRanking, $dailyDistribution, $busMetrics
// And JSON versions for Chart.js

// Safe defaults
$top   = is_array($top ?? null) ? $top : [];
$buses = is_array($buses ?? null) ? $buses : [];
$month = is_array($month ?? null) ? $month : [];
$revenueTrend = is_array($revenueTrend ?? null) ? $revenueTrend : ['labels' => [], 'values' => []];
$busRanking = is_array($busRanking ?? null) ? $busRanking : ['top' => [], 'bottom' => []];
$dailyDistribution = is_array($dailyDistribution ?? null) ? $dailyDistribution : ['labels' => [], 'values' => []];
$busMetrics = is_array($busMetrics ?? null) ? $busMetrics : [];
$allBuses = is_array($allBuses ?? null) ? $allBuses : [];
$selectBuses = is_array($selectBuses ?? null) ? $selectBuses : [];
$comparisonData = $comparisonData ?? null;
$compareB1 = $compareB1 ?? null;
$compareB2 = $compareB2 ?? null;

// JSON for charts
$revenueTrendJson = $revenueTrendJson ?? json_encode(['labels' => [], 'values' => []], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$busRankingTopJson = $busRankingTopJson ?? json_encode(['labels' => [], 'values' => []], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$dailyDistributionJson = $dailyDistributionJson ?? json_encode(['labels' => [], 'values' => []], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupeesMini(float $v): string {
    if ($v >= 1_000_000) return 'Rs. ' . number_format($v/1_000_000, 1) . 'M';
    if ($v >= 1_000) return 'Rs. ' . number_format($v/1_000, 0) . 'K';
    return 'Rs. ' . number_format($v, 0);
}
?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<style>
.earnings-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.earnings-page { --clr-primary: #80143c; --clr-success: #10b981; --clr-danger: #ef4444; --clr-warning: #f59e0b; }

/* Header */
.earnings-header { padding-bottom: 2rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 2rem; }
.earnings-header h1 { margin: 0; font-size: 2rem; color: var(--clr-primary); }
.earnings-header p { margin: 0.25rem 0 0 0; color: #6b7280; }
.earnings-header__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.header-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.export-report-btn-alt,
.add-income-btn {
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  padding: 0.85rem 1.5rem;
  cursor: pointer;
  border: 1.5px solid transparent;
}
.export-report-btn-alt {
  background: #fff;
  color: #80143c;
  border-color: #80143c;
}
.add-income-btn {
  background: #e9b23a;
  color: #80143c;
  border-color: #e9b23a;
}

.earning-modal[hidden] { display: none; }
.earning-modal {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.earning-modal .modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
}
.earning-modal__panel {
  position: relative;
  width: min(560px, 95vw);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
  padding: 1.25rem;
}
.earning-modal__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.earning-modal__title { margin: 0; color: var(--clr-primary); }
.earning-modal__close { border: 0; background: transparent; font-size: 1.6rem; cursor: pointer; }
.earning-modal__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}
.earning-modal__field { display: flex; flex-direction: column; gap: 0.35rem; }
.earning-modal__field label { font-size: 0.85rem; color: #4b5563; font-weight: 600; }
.earning-modal__field input,
.earning-modal__field select {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.6rem 0.7rem;
  font-size: 0.95rem;
}
.earning-modal__footer { display: flex; justify-content: flex-end; gap: 0.6rem; margin-top: 1rem; }
.earning-modal__btn { border: 0; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; cursor: pointer; }
.earning-modal__btn--cancel { background: #f3f4f6; color: #374151; }
.earning-modal__btn--submit { background: #80143c; color: #fff; }

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s;
}

.kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }

.kpi-card__icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.kpi-card--revenue .kpi-card__icon { background: #dbeafe; color: #0369a1; }
.kpi-card--growth .kpi-card__icon { background: #dcfce7; color: #16a34a; }
.kpi-card--comparison .kpi-card__icon { background: #fef3c7; color: #d97706; }
.kpi-card--fleet .kpi-card__icon { background: #fecaca; color: #dc2626; }

.kpi-label { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; }
.kpi-value { font-size: 1.75rem; font-weight: 700; color: var(--clr-primary); }
.kpi-sub { font-size: 0.875rem; color: #9ca3af; margin-top: 0.5rem; }

.kpi-trend { font-size: 0.875rem; font-weight: 600; margin-top: 0.5rem; }
.kpi-trend--up { color: var(--clr-success); }
.kpi-trend--down { color: var(--clr-danger); }

/* Charts Section */
.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.chart-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1.5rem 0;
    color: var(--clr-primary);
}

.chart-container {
    position: relative;
    height: 300px;
}

/* Bus Performance Grid */
.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.performance-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.performance-card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--clr-primary);
    margin: 0 0 1rem 0;
}

.performance-list { list-style: none; margin: 0; padding: 0; }

.performance-item {
    padding: 0.875rem 0;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.performance-item:last-child { border-bottom: none; }

.performance-bus {
    font-weight: 600;
    color: var(--clr-primary);
}

.performance-stat {
    text-align: right;
    font-size: 0.875rem;
}

.performance-value {
    font-weight: 600;
    color: #1f2937;
    display: block;
}

.performance-label {
    color: #6b7280;
    font-size: 0.75rem;
}

/* Bus Details Table Section */
.table-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.table-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--clr-primary);
    margin: 0 0 1.5rem 0;
}

.bus-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.bus-table th {
    background: #f9fafb;
    padding: 0.75rem;
    font-weight: 600;
    color: var(--clr-primary);
    border-bottom: 2px solid #e5e7eb;
    text-align: left;
}

.bus-table td {
    padding: 1rem 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.bus-table tbody tr:hover { background: #f9fafb; }

.bus-reg { font-weight: 600; color: var(--clr-primary); }
.bus-route { color: #6b7280; }

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active { background: #d1fae5; color: #065f46; }
.status-maintenance { background: #fef3c7; color: #92400e; }
.status-inactive { background: #fee2e2; color: #7f1d1d; }

/* Bus Detail Cards */
.bus-detail-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.bus-detail-card:hover {
    box-shadow: 0 8px 16px rgba(128, 20, 60, 0.15);
    transform: translateY(-2px);
    border-color: var(--clr-primary);
}

/* Empty state */
.empty-message {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #6b7280;
}

.empty-message p { margin: 0; }

/* Responsive */
@media (max-width: 768px) {
    .charts-section { grid-template-columns: 1fr; }
    .performance-grid { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: 1fr; }
    .earnings-header h1 { font-size: 1.5rem; }
  .earnings-header__row { flex-direction: column; }
  .earning-modal__grid { grid-template-columns: 1fr; }
}
</style>

<section id="earningsPage" class="earnings-page" data-endpoint="/M/earnings">
  
  <!-- PAGE HEADER -->
  <div class="earnings-header">
    <div class="earnings-header__row">
      <div>
        <h1>Earnings & Revenue Analytics</h1>
        <p>Comprehensive income analysis and performance tracking for your SLTB fleet</p>
      </div>
      <div class="header-actions">
        <button type="button" class="export-report-btn-alt js-export" data-export-href="/M/earnings/export">Export Report</button>
        <button type="button" id="btnAddEarning" class="add-income-btn">Add Income Record</button>
      </div>
    </div>
  </div>

  <!-- KPI CARDS SECTION -->
  <section class="kpi-grid">
    <?php if ($top): ?>
      <?php foreach ($top as $idx => $t): ?>
        <?php
          $val = h($t['value'] ?? 'Rs. 0');
          $lab = h($t['label'] ?? '');
          $trend = (string)($t['trend'] ?? '');
          $sub = h($t['sub'] ?? '');
          $isUp = strlen($trend) && $trend[0] === '+';
          
          $cardClass = match($lab) {
            'Total Revenue' => 'kpi-card--growth',
            'Latest Day Income' => 'kpi-card--revenue',
            'Highest Day' => 'kpi-card--comparison',
            default => 'kpi-card--fleet',
          };
        ?>
        <div class="kpi-card <?= $cardClass ?>">
          <div class="kpi-card__icon">
            <?php if (str_contains($lab, 'Revenue')): ?>📊
            <?php elseif (str_contains($lab, 'Latest')): ?>💰
            <?php elseif (str_contains($lab, 'Highest')): ?>📈
            <?php else: ?>📉 <?php endif; ?>
          </div>
          <div class="kpi-label"><?= $lab ?></div>
          <div class="kpi-value"><?= $val ?></div>
          <?php if ($trend !== ''): ?>
            <div class="kpi-trend <?= $isUp ? 'kpi-trend--up' : 'kpi-trend--down' ?>">
              <?= $isUp ? '↑ ' : '↓ ' ?><?= h($trend) ?>
            </div>
          <?php endif; ?>
          <?php if ($sub !== ''): ?>
            <div class="kpi-sub"><?= $sub ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-message">
        <p>No earnings data available yet. Start by adding earning records.</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- CHARTS SECTION -->
  <?php if (!empty($revenueTrend['labels']) || !empty($busRanking['top'])): ?>
  <section class="charts-section">
    
    <!-- Revenue Trend Chart (7 days) -->
    <?php if (!empty($revenueTrend['labels'])): ?>
    <div class="chart-card">
      <h3 class="chart-card-title">📈 Revenue Trend (Last 7 Days)</h3>
      <div class="chart-container">
        <canvas id="revenueTrendChart"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top Performers Chart -->
    <?php if (!empty($busRanking['top'])): ?>
    <div class="chart-card">
      <h3 class="chart-card-title">🏆 Top Performing Buses</h3>
      <div class="chart-container">
        <canvas id="topPerformersChart"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <!-- Daily Distribution Chart -->
    <?php if (!empty($dailyDistribution['labels'])): ?>
    <div class="chart-card">
      <h3 class="chart-card-title">📊 Daily Income Distribution (30 Days)</h3>
      <div class="chart-container">
        <canvas id="dailyDistributionChart"></canvas>
      </div>
    </div>
    <?php endif; ?>

  </section>
  <?php endif; ?>

  <!-- PERFORMANCE METRICS SECTION -->
  <?php if (!empty($busRanking['top']) || !empty($busRanking['bottom'])): ?>
  <section class="performance-grid">
    
    <!-- Top 5 Buses -->
    <?php if (!empty($busRanking['top'])): ?>
    <div class="performance-card">
      <h4 class="performance-card-title">🌟 Top 5 Buses</h4>
      <ul class="performance-list">
        <?php foreach ($busRanking['top'] as $bus): ?>
        <li class="performance-item" style="cursor: pointer; transition: all 0.2s;" onclick="scrollToBusCard('<?= h($bus['bus']) ?>')" onmouseover="this.style.background='rgba(128,20,60,0.05);borderRadius=0.375rem'" onmouseout="this.style.background='transparent'">
          <div class="performance-bus"><?= h($bus['bus']) ?></div>
          <div class="performance-stat">
            <span class="performance-value"><?= rupeesMini($bus['revenue']) ?></span>
            <span class="performance-label"><?= $bus['transactions'] ?> trips</span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Bottom 5 Buses -->
    <?php if (!empty($busRanking['bottom'])): ?>
    <div class="performance-card">
      <h4 class="performance-card-title">⚠️ Bottom 5 Buses</h4>
      <ul class="performance-list">
        <?php foreach ($busRanking['bottom'] as $bus): ?>
        <li class="performance-item" style="cursor: pointer; transition: all 0.2s;" onclick="scrollToBusCard('<?= h($bus['bus']) ?>')" onmouseover="this.style.background='rgba(239,68,68,0.05);borderRadius=0.375rem'" onmouseout="this.style.background='transparent'">
          <div class="performance-bus"><?= h($bus['bus']) ?></div>
          <div class="performance-stat">
            <span class="performance-value"><?= rupeesMini($bus['revenue']) ?></span>
            <span class="performance-label"><?= $bus['transactions'] ?> trips</span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

  </section>
  <?php endif; ?>

  <!-- MONTHLY SUMMARY -->
  <?php if (!empty($month)): ?>
  <section style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--clr-primary); margin: 0 0 1rem 0;">📅 Monthly Comparison</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem;">
      <div>
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Current Month</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--clr-primary);"><?= h($month['current'] ?? 'Rs. 0') ?></div>
      </div>
      <div>
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Previous Month</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;"><?= h($month['previous'] ?? 'Rs. 0') ?></div>
      </div>
      <div>
        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Growth Rate</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: <?= (strpos($month['growth'] ?? '+0%', '-') === 0) ? '#ef4444' : '#10b981' ?>;">
          <?= h($month['growth'] ?? '+0.0%') ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- BUS COMPARISON SECTION -->
  <section style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--clr-primary); margin: 0 0 1.5rem 0;">⚖️ Compare Two Buses</h3>
    
    <!-- Compare Bus Selection -->
    <form id="comparisonForm" method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
      <input type="hidden" name="msg" value="">
      
      <div>
        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem;">Bus 1</label>
        <select name="compare_b1" id="compareB1" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
          <option value="">-- Select Bus 1 --</option>
          <?php foreach ($allBuses as $b): ?>
            <option value="<?= h($b) ?>" <?= $compareB1 === $b ? 'selected' : '' ?>>
              <?= h($b) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem;">Bus 2</label>
        <select name="compare_b2" id="compareB2" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
          <option value="">-- Select Bus 2 --</option>
          <?php foreach ($allBuses as $b): ?>
            <option value="<?= h($b) ?>" <?= $compareB2 === $b ? 'selected' : '' ?>>
              <?= h($b) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: flex; align-items: flex-end; gap: 0.75rem;">
        <button type="submit" style="flex: 1; padding: 0.75rem 1.5rem; background: var(--clr-primary); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
          🔍 Compare
        </button>
        <button type="reset" style="padding: 0.75rem 1.5rem; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
          Clear
        </button>
      </div>
    </form>

    <!-- Comparison Results -->
    <?php if ($comparisonData && !empty($comparisonData['bus1']) && !empty($comparisonData['bus2'])): ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        
        <?php foreach (['bus1' => $comparisonData['bus1'], 'bus2' => $comparisonData['bus2']] as $key => $data): ?>
          <div style="background: linear-gradient(135deg, var(--clr-primary) 0%, #b0234f 100%); color: white; border-radius: 0.75rem; padding: 1.5rem;">
            <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
              <?= h($data['reg_no']) ?>
            </div>

            <!-- Metrics -->
            <div>
              <!-- Total Income -->
              <div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.2);">
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.35rem;">Total Income</div>
                <div style="font-size: 1.375rem; font-weight: 700;">
                  <?= rupeesMini($data['total_income']) ?>
                </div>
              </div>

              <!-- This Month -->
              <div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.2);">
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.35rem;">This Month</div>
                <div style="font-size: 1.375rem; font-weight: 700;">
                  <?= rupeesMini($data['this_month']) ?>
                </div>
              </div>

              <!-- Previous Month -->
              <div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.2);">
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.35rem;">Previous Month</div>
                <div style="font-size: 1.375rem; font-weight: 700;">
                  <?= rupeesMini($data['prev_month']) ?>
                </div>
              </div>

              <!-- Last Day -->
              <div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.2);">
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.35rem;">Last Day Income</div>
                <div style="font-size: 1.375rem; font-weight: 700;">
                  <?= rupeesMini($data['last_day']) ?>
                </div>
              </div>

              <!-- Efficiency -->
              <div>
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.35rem;">Efficiency</div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="flex: 1; height: 10px; background: rgba(255,255,255,0.2); border-radius: 5px; overflow: hidden;">
                    <div style="height: 100%; background: rgba(255,255,255,0.8); width: <?= min(100, number_format($data['efficiency'], 0)) ?>%;"></div>
                  </div>
                  <div style="font-size: 1.25rem; font-weight: 700; min-width: 50px;">
                    <?= number_format($data['efficiency'], 0) ?>%
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      </div>
    <?php elseif ($compareB1 || $compareB2): ?>
      <div style="text-align: center; padding: 2rem; color: #6b7280;">
        <p>⚠️ Please select both buses to compare</p>
      </div>
    <?php else: ?>
      <div style="text-align: center; padding: 2rem; color: #6b7280;">
        <p>👉 Select two buses above to view detailed comparison</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- BUS INCOME DETAILS CARDS WITH FILTER -->
  <?php if (!empty($buses)): ?>
  <section style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
    <div style="margin-bottom: 1.5rem;">
      <h3 class="table-title">🚌 Income per Bus (Detailed Breakdown)</h3>
      
      <!-- Filter Section -->
      <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px;">
          <input 
            type="text" 
            id="busFilter" 
            placeholder="🔍 Filter by bus reg. no (e.g., NA-2581)" 
            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;"
          >
        </div>
        <button 
          id="clearFilterBtn" 
          style="padding: 0.75rem 1.5rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer; font-weight: 600; color: #374151; transition: all 0.2s;"
          onmouseover="this.style.background='#e5e7eb'" 
          onmouseout="this.style.background='#f3f4f6'"
        >
          Clear Filter
        </button>
      </div>
      <div id="filterCount" style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
        Showing all <?= count($buses) ?> buses
      </div>
    </div>

    <!-- Bus Cards Grid -->
    <div id="busCardsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
      <?php foreach ($buses as $b): ?>
        <div class="bus-detail-card" id="bus-<?= h(str_replace('-', '', $b['number'] ?? '')) ?>" data-bus-reg="<?= h($b['number'] ?? '') ?>">
          <div style="background: linear-gradient(135deg, var(--clr-primary) 0%, #b0234f 100%); color: white; padding: 1.25rem; border-radius: 0.75rem 0.75rem 0 0; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
              <div style="font-size: 0.875rem; opacity: 0.9;">Bus Registration</div>
              <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;"><?= h($b['number'] ?? '—') ?></div>
            </div>
            <div style="text-align: right;">
              <span class="status-badge status-<?= strtolower($b['status'] ?? 'inactive') ?>" style="
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 0.35rem 0.75rem;
              ">
                <?= h($b['status'] ?? 'Inactive') ?>
              </span>
            </div>
          </div>

          <div style="padding: 1.25rem; border: 1px solid #e5e7eb; border-top: none;">
            <!-- Route -->
            <div style="margin-bottom: 1rem;">
              <div style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Route</div>
              <div style="font-size: 0.95rem; color: #374151; margin-top: 0.25rem;"><?= h($b['route'] ?? '—') ?></div>
            </div>

            <!-- Efficiency Bar -->
            <div style="margin-bottom: 1.25rem;">
              <div style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Efficiency</div>
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="flex: 1; height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden;">
                  <div style="height: 100%; background: linear-gradient(90deg, var(--clr-success), #059669); width: <?= min(100, (int)str_replace('%', '', $b['eff'] ?? '0')) ?>%; transition: width 0.3s;"></div>
                </div>
                <span style="font-weight: 700; color: var(--clr-primary); min-width: 45px; text-align: right;"><?= h($b['eff'] ?? '0%') ?></span>
              </div>
            </div>

            <!-- Income Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
              <div>
                <div style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Latest Day</div>
                <div style="font-size: 1.125rem; font-weight: 700; color: var(--clr-primary); margin-top: 0.35rem;">
                  <?= h($b['daily'] ?? 'Rs. 0') ?>
                </div>
              </div>
              <div>
                <div style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Last 7 Days</div>
                <div style="font-size: 1.125rem; font-weight: 700; color: var(--clr-success); margin-top: 0.35rem;">
                  <?= h($b['weekly'] ?? 'Rs. 0') ?>
                </div>
              </div>
            </div>

            <!-- All Time Total -->
            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-align: center;">
              <div style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">All Time Total</div>
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--clr-primary);">
                <?= h($b['total'] ?? 'Rs. 0') ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Empty State for Filter -->
    <div id="noResultsMessage" style="display: none; text-align: center; padding: 3rem 1.5rem; color: #6b7280;">
      <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">No buses found</div>
      <p>Try adjusting your filter criteria</p>
    </div>
  </section>
  <?php else: ?>
  <div class="empty-message">
    <p>No bus earnings data found. Add earning records to see detailed breakdown.</p>
  </div>
  <?php endif; ?>

  <div id="earningModal" class="earning-modal" hidden>
    <div class="modal__backdrop"></div>
    <div class="earning-modal__panel">
      <div class="earning-modal__header">
        <h2 id="earningModalTitle" class="earning-modal__title">Add Income Record</h2>
        <button type="button" id="btnCloseEarning" class="earning-modal__close" aria-label="Close">&times;</button>
      </div>

      <form id="earningForm" autocomplete="off">
        <input type="hidden" id="f_e_id" name="earning_id" value="">
        <div class="earning-modal__grid">
          <div class="earning-modal__field">
            <label for="f_e_date">Date</label>
            <input type="date" id="f_e_date" name="date" required>
          </div>

          <div class="earning-modal__field">
            <label for="f_e_bus">SLTB Bus (Depot)</label>
            <select id="f_e_bus" name="bus_reg_no" required>
              <option value="">-- Select Bus --</option>
              <?php foreach ($selectBuses as $reg): ?>
                <option value="<?= h($reg) ?>"><?= h($reg) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="earning-modal__field">
            <label for="f_e_amount">Amount (LKR)</label>
            <input type="number" id="f_e_amount" name="amount" step="0.01" min="0" required>
          </div>

          <div class="earning-modal__field">
            <label for="f_e_source">Source</label>
            <input type="text" id="f_e_source" name="source" maxlength="120" placeholder="Ticket sales, online, cash, etc.">
          </div>
        </div>

        <div class="earning-modal__footer">
          <button type="button" id="btnCancelEarning" class="earning-modal__btn earning-modal__btn--cancel">Cancel</button>
          <button type="submit" class="earning-modal__btn earning-modal__btn--submit">Save</button>
        </div>
      </form>
    </div>
  </div>

</section>

<!-- Bus Filter Script -->
<script>
// Scroll to bus detail card when clicked from Top/Bottom 5 lists
function scrollToBusCard(busReg) {
  const busId = 'bus-' + busReg.replace('-', '');
  const element = document.getElementById(busId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    // Optional: Add brief highlight effect
    element.style.transition = 'box-shadow 0.3s ease';
    element.style.boxShadow = '0 0 0 3px rgba(128, 20, 60, 0.3)';
    setTimeout(() => {
      element.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
    }, 600);
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const filterInput = document.getElementById('busFilter');
  const clearBtn = document.getElementById('clearFilterBtn');
  const filterCount = document.getElementById('filterCount');
  const busCards = document.querySelectorAll('.bus-detail-card');
  const busCardsContainer = document.getElementById('busCardsContainer');
  const noResultsMessage = document.getElementById('noResultsMessage');

  function applyFilter() {
    const filterValue = filterInput.value.toLowerCase().trim();
    let visibleCount = 0;

    busCards.forEach(card => {
      const busReg = card.getAttribute('data-bus-reg').toLowerCase();
      const matches = busReg.includes(filterValue) || filterValue === '';
      card.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    // Show/hide no results message
    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    busCardsContainer.style.opacity = visibleCount === 0 ? '0.5' : '1';

    // Update count
    filterCount.textContent = visibleCount === 0 
      ? 'No buses match your filter' 
      : `Showing ${visibleCount} of ${busCards.length} buses`;
  }

  // Filter on input
  filterInput.addEventListener('input', applyFilter);

  // Clear filter button
  clearBtn.addEventListener('click', function() {
    filterInput.value = '';
    filterInput.focus();
    applyFilter();
  });
});
</script>

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  
  // Colors
  const colors = {
    primary: '#80143c',
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#0369a1',
  };

  // Revenue Trend Chart (7 days area chart)
  const revenueTrendCtx = document.getElementById('revenueTrendChart');
  if (revenueTrendCtx) {
    const data = <?= $revenueTrendJson ?>;
    new Chart(revenueTrendCtx, {
      type: 'line',
      data: {
        labels: data.labels || [],
        datasets: [{
          label: 'Daily Revenue',
          data: data.values || [],
          borderColor: colors.primary,
          backgroundColor: 'rgba(128, 20, 60, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 5,
          pointBackgroundColor: colors.primary,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: v => 'Rs.' + (v/1000).toFixed(1) + 'K' }
          }
        }
      }
    });
  }

  // Top Performers Bar Chart
  const topPerfCtx = document.getElementById('topPerformersChart');
  if (topPerfCtx) {
    const data = <?= $busRankingTopJson ?>;
    new Chart(topPerfCtx, {
      type: 'bar',
      data: {
        labels: data.labels || [],
        datasets: [{
          label: 'Revenue',
          data: data.values || [],
          backgroundColor: colors.success,
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { callback: v => 'Rs.' + (v/1000).toFixed(0) + 'K' }
          }
        }
      }
    });
  }

  // Daily Distribution Bar Chart
  const dailyDistCtx = document.getElementById('dailyDistributionChart');
  if (dailyDistCtx) {
    const data = <?= $dailyDistributionJson ?>;
    new Chart(dailyDistCtx, {
      type: 'bar',
      data: {
        labels: data.labels || [],
        datasets: [{
          label: 'Daily Income',
          data: data.values || [],
          backgroundColor: colors.info,
          borderRadius: 4,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: v => 'Rs.' + (v/1000).toFixed(0) + 'K' }
          }
        }
      }
    });
  }
  
});
</script>
