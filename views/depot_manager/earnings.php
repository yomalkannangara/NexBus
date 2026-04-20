<?php
// Redesigned Earnings Dashboard - Depot Manager
// Data from controller: $top, $month, $revenueTrend, $busRanking, $dailyDistribution, $earningsRows
// And JSON versions for Chart.js

// Safe defaults
$top   = is_array($top ?? null) ? $top : [];
$earningsRows = is_array($earningsRows ?? null) ? $earningsRows : [];
$month = is_array($month ?? null) ? $month : [];
$revenueTrend = is_array($revenueTrend ?? null) ? $revenueTrend : ['labels' => [], 'values' => []];
$busRanking = is_array($busRanking ?? null) ? $busRanking : ['top' => [], 'bottom' => []];
$dailyDistribution = is_array($dailyDistribution ?? null) ? $dailyDistribution : ['labels' => [], 'values' => []];
$allBuses = is_array($allBuses ?? null) ? $allBuses : [];
$selectBuses = is_array($selectBuses ?? null) ? $selectBuses : [];
$comparisonData = $comparisonData ?? null;
$compareB1 = $compareB1 ?? null;
$compareB2 = $compareB2 ?? null;
$requestToken = (string)($requestToken ?? '');

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

/* Delete confirmation modal (bus-owner style) */
.modal[hidden] { display: none; }
.modal {
  position: fixed;
  inset: 0;
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.46);
  backdrop-filter: blur(2px);
}
.modal__dialog {
  position: relative;
  width: min(420px, 92vw);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
  border: 1px solid #f1f5f9;
  overflow: hidden;
  animation: dmModalIn 0.2s ease;
}
.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 18px 20px 0 20px;
}
.modal__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
}
.modal__close {
  border: 0;
  background: transparent;
  color: #6b7280;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
}
.modal__close:hover { color: #111827; }
.modal__form {
  padding: 14px 20px 18px 20px;
}
.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 20px 18px 20px;
}
.btn-secondary,
.btn-primary {
  border-radius: 10px;
  padding: 0.62rem 0.95rem;
  font-weight: 600;
  font-size: 0.92rem;
  cursor: pointer;
}
.btn-secondary:hover {
  background: #f3f4f6;
}
.btn-primary:hover {
  filter: brightness(0.95);
}

#deleteConfirmModal .btn-secondary {
  color: #1f2937 !important;
  min-width: 88px;
}

#deleteConfirmModal .btn-primary {
  min-width: 110px;
}

@keyframes dmModalIn {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

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
        <li class="performance-item" style="cursor: pointer; transition: all 0.2s;" onclick="scrollToEarningRow('<?= h($bus['bus']) ?>')" onmouseover="this.style.background='rgba(128,20,60,0.05);borderRadius=0.375rem'" onmouseout="this.style.background='transparent'">
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
        <li class="performance-item" style="cursor: pointer; transition: all 0.2s;" onclick="scrollToEarningRow('<?= h($bus['bus']) ?>')" onmouseover="this.style.background='rgba(239,68,68,0.05);borderRadius=0.375rem'" onmouseout="this.style.background='transparent'">
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

  <!-- REVENUE TRACKING TABLE -->
  <section style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:1rem;">
      <h3 class="table-title" style="margin:0;">Revenue Tracking</h3>
      <span id="tableRowCount" style="font-size:0.875rem; color:#6b7280;"></span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.75rem; margin-bottom:1rem;">
      <input type="date" id="fltDateFrom" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:0.5rem;" aria-label="From date">
      <input type="date" id="fltDateTo" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:0.5rem;" aria-label="To date">
      <select id="fltBus" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:0.5rem;">
        <option value="">All Buses</option>
        <?php foreach ($selectBuses as $reg): ?>
          <option value="<?= h($reg) ?>"><?= h($reg) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="fltSearch" placeholder="Search source or bus" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:0.5rem;">
      <button id="btnClearFilters" type="button" style="padding:0.65rem 1rem; border:1px solid #d1d5db; border-radius:0.5rem; background:#f9fafb; font-weight:600; cursor:pointer;">Clear</button>
    </div>

    <div style="overflow:auto;">
      <table class="bus-table" id="earningsTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Bus Reg. No</th>
            <th>Total Revenue</th>
            <th>Source</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($earningsRows)): ?>
            <?php foreach ($earningsRows as $r): ?>
              <?php
                $row = [
                  'id' => (int)($r['earning_id'] ?? 0),
                  'date' => (string)($r['date'] ?? ''),
                  'bus_reg_no' => (string)($r['bus_reg_no'] ?? ''),
                  'amount' => (float)($r['amount'] ?? 0),
                  'source' => (string)($r['source'] ?? ''),
                ];
                $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
              ?>
              <tr data-date="<?= h($row['date']) ?>" data-bus="<?= h($row['bus_reg_no']) ?>" data-search="<?= h(strtolower($row['source'] . ' ' . $row['bus_reg_no'])) ?>">
                <td><?= h($row['date']) ?></td>
                <td><span class="bus-reg"><?= h($row['bus_reg_no']) ?></span></td>
                <td><strong>LKR <?= number_format($row['amount'], 2) ?></strong></td>
                <td><?= h($row['source'] !== '' ? $row['source'] : 'Cash') ?></td>
                <td>
                  <div style="display:flex; gap:0.5rem;">
                    <button type="button" class="js-earning-edit" data-earning="<?= $rowJson ?>" style="border:1px solid #d1d5db; background:#fff; color:#111827; border-radius:8px; padding:0.35rem 0.55rem; cursor:pointer;">Edit</button>
                    <button type="button" class="js-earning-del" data-earning-id="<?= (int)$row['id'] ?>" style="border:1px solid #fecaca; background:#fff1f2; color:#9f1239; border-radius:8px; padding:0.35rem 0.55rem; cursor:pointer;">Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center; padding:2rem; color:#6b7280;">No earnings records found for buses in this depot.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div id="tableNoResults" style="display:none; text-align:center; padding:1rem; color:#6b7280;">No records match the selected filters.</div>

    <div id="tablePager" style="display:flex; justify-content:flex-end; align-items:center; gap:0.75rem; margin-top:1rem;">
      <button id="btnPrevPage" type="button" style="padding:0.45rem 0.8rem; border:1px solid #d1d5db; border-radius:8px; background:#fff; cursor:pointer;">Prev</button>
      <span id="pageInfo" style="font-size:0.875rem; color:#4b5563;">Page 1</span>
      <button id="btnNextPage" type="button" style="padding:0.45rem 0.8rem; border:1px solid #d1d5db; border-radius:8px; background:#fff; cursor:pointer;">Next</button>
    </div>
  </section>

  <div id="earningModal" class="earning-modal" hidden>
    <div class="modal__backdrop"></div>
    <div class="earning-modal__panel">
      <div class="earning-modal__header">
        <h2 id="earningModalTitle" class="earning-modal__title">Add Income Record</h2>
        <button type="button" id="btnCloseEarning" class="earning-modal__close" aria-label="Close">&times;</button>
      </div>

      <form id="earningForm" autocomplete="off">
        <input type="hidden" id="f_e_id" name="earning_id" value="">
        <input type="hidden" id="f_e_req_token" name="_request_token" value="<?= h($requestToken) ?>">
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
          <button type="submit" id="btnSubmitEarning" class="earning-modal__btn earning-modal__btn--submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div id="deleteConfirmModal" class="modal" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" style="max-width: 400px; padding: 0;">
      <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
        <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
          <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Delete Record
        </h3>
        <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
      </div>
      <div class="modal__form" style="padding-top: 10px;">
        <p style="color: #4B5563; font-size: 15px; margin: 0;">Are you sure you want to delete this earning record? This action cannot be undone.</p>
      </div>
      <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
        <button type="button" class="btn-secondary" id="btnCancelDelete" style="background: white; border: 1px solid #E5E7EB; color: #1f2937;">No</button>
        <button type="button" class="btn-primary" id="btnConfirmDelete" style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
      </div>
    </div>
  </div>

</section>

<script>
function scrollToEarningRow(busReg) {
  const row = document.querySelector('#earningsTable tbody tr[data-bus="' + busReg + '"]');
  if (!row) return;

  row.scrollIntoView({ behavior: 'smooth', block: 'center' });
  row.style.transition = 'background-color 0.2s ease';
  row.style.backgroundColor = 'rgba(128, 20, 60, 0.12)';
  setTimeout(function () {
    row.style.backgroundColor = '';
  }, 800);
}
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

  const endpoint = document.getElementById('earningsPage')?.dataset?.endpoint || '/M/earnings';

  // Revenue table filtering + pagination
  const tableBody = document.querySelector('#earningsTable tbody');
  const allRows = tableBody ? Array.from(tableBody.querySelectorAll('tr[data-date]')) : [];
  let filteredRows = allRows.slice();
  const rowsPerPage = 10;
  let currentPage = 1;

  const fltDateFrom = document.getElementById('fltDateFrom');
  const fltDateTo = document.getElementById('fltDateTo');
  const fltBus = document.getElementById('fltBus');
  const fltSearch = document.getElementById('fltSearch');
  const btnClearFilters = document.getElementById('btnClearFilters');
  const tableRowCount = document.getElementById('tableRowCount');
  const tableNoResults = document.getElementById('tableNoResults');
  const tablePager = document.getElementById('tablePager');
  const btnPrevPage = document.getElementById('btnPrevPage');
  const btnNextPage = document.getElementById('btnNextPage');
  const pageInfo = document.getElementById('pageInfo');

  function tableTotalPages() {
    return Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
  }

  function renderTablePage() {
    allRows.forEach(function (r) { r.style.display = 'none'; });

    if (!filteredRows.length) {
      if (tableNoResults) tableNoResults.style.display = 'block';
      if (tablePager) tablePager.style.display = 'none';
      if (tableRowCount) tableRowCount.textContent = '0 records';
      return;
    }

    if (tableNoResults) tableNoResults.style.display = 'none';
    if (tablePager) tablePager.style.display = 'flex';

    const totalPages = tableTotalPages();
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * rowsPerPage;

    filteredRows.forEach(function (r, i) {
      r.style.display = (i >= start && i < start + rowsPerPage) ? '' : 'none';
    });

    if (tableRowCount) {
      tableRowCount.textContent = filteredRows.length + ' record' + (filteredRows.length === 1 ? '' : 's');
    }
    if (pageInfo) pageInfo.textContent = 'Page ' + currentPage + ' of ' + totalPages;
    if (btnPrevPage) btnPrevPage.disabled = currentPage === 1;
    if (btnNextPage) btnNextPage.disabled = currentPage >= totalPages;
  }

  function applyTableFilters() {
    const from = fltDateFrom?.value || '';
    const to = fltDateTo?.value || '';
    const bus = (fltBus?.value || '').toLowerCase();
    const search = (fltSearch?.value || '').toLowerCase().trim();

    filteredRows = allRows.filter(function (row) {
      const date = row.dataset.date || '';
      const rowBus = (row.dataset.bus || '').toLowerCase();
      const txt = (row.dataset.search || '').toLowerCase();
      if (from && date < from) return false;
      if (to && date > to) return false;
      if (bus && rowBus !== bus) return false;
      if (search && !(txt.includes(search))) return false;
      return true;
    });

    currentPage = 1;
    renderTablePage();
  }

  [fltDateFrom, fltDateTo, fltBus, fltSearch].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', applyTableFilters);
    if (el.tagName === 'SELECT') el.addEventListener('change', applyTableFilters);
  });

  if (btnClearFilters) {
    btnClearFilters.addEventListener('click', function () {
      if (fltDateFrom) fltDateFrom.value = '';
      if (fltDateTo) fltDateTo.value = '';
      if (fltBus) fltBus.selectedIndex = 0;
      if (fltSearch) fltSearch.value = '';
      applyTableFilters();
    });
  }
  if (btnPrevPage) {
    btnPrevPage.addEventListener('click', function () {
      if (currentPage > 1) {
        currentPage -= 1;
        renderTablePage();
      }
    });
  }
  if (btnNextPage) {
    btnNextPage.addEventListener('click', function () {
      if (currentPage < tableTotalPages()) {
        currentPage += 1;
        renderTablePage();
      }
    });
  }
  renderTablePage();

  // Add / edit modal
  const modal = document.getElementById('earningModal');
  const form = document.getElementById('earningForm');
  const btnAdd = document.getElementById('btnAddEarning');
  const btnClose = document.getElementById('btnCloseEarning');
  const btnCancel = document.getElementById('btnCancelEarning');
  const btnSubmit = document.getElementById('btnSubmitEarning');
  const modalTitle = document.getElementById('earningModalTitle');
  let isSubmitting = false;

  function closeModal() {
    if (!modal || !form) return;
    modal.setAttribute('hidden', '');
    form.reset();
    isSubmitting = false;
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Save';
    }
    const idField = document.getElementById('f_e_id');
    if (idField) idField.value = '';
  }

  if (btnAdd) {
    btnAdd.addEventListener('click', function () {
      if (!modal || !form || !modalTitle) return;
      form.reset();
      const idField = document.getElementById('f_e_id');
      if (idField) idField.value = '';
      modalTitle.textContent = 'Add Income Record';
      modal.removeAttribute('hidden');
    });
  }

  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);
  modal?.querySelector('.modal__backdrop')?.addEventListener('click', closeModal);

  document.querySelectorAll('.js-earning-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const payload = this.getAttribute('data-earning') || '{}';
      let data = {};
      try {
        data = JSON.parse(payload);
      } catch (e) {
        data = {};
      }

      const idField = document.getElementById('f_e_id');
      const dateField = document.getElementById('f_e_date');
      const busField = document.getElementById('f_e_bus');
      const amountField = document.getElementById('f_e_amount');
      const sourceField = document.getElementById('f_e_source');

      if (idField) idField.value = data.id || '';
      if (dateField) dateField.value = data.date || '';
      if (busField) busField.value = data.bus_reg_no || '';
      if (amountField) amountField.value = data.amount || '';
      if (sourceField) sourceField.value = data.source || '';

      if (modalTitle) modalTitle.textContent = 'Edit Income Record';
      modal?.removeAttribute('hidden');
    });
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (isSubmitting) return;

      isSubmitting = true;
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Saving...';
      }

      const fd = new FormData(form);
      const earningId = (document.getElementById('f_e_id')?.value || '').trim();
      fd.append('action', earningId ? 'update' : 'create');

      fetch(endpoint, {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(async function (res) {
          const data = await res.json();
          if (res.ok && data.success) {
            window.location.reload();
            return;
          }
          alert(data.message || 'Failed to save record.');
          isSubmitting = false;
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Save';
          }
        })
        .catch(function () {
          alert('Network error while saving record.');
          isSubmitting = false;
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Save';
          }
        });
    });
  }

  // Delete action (bus-owner style modal)
  let deleteId = null;
  const deleteModal = document.getElementById('deleteConfirmModal');
  const btnConfirmDel = document.getElementById('btnConfirmDelete');
  const btnCancelDel = document.getElementById('btnCancelDelete');
  const btnCloseDel = document.getElementById('btnCloseDelete');

  function closeDeleteModal() {
    if (!deleteModal) return;
    deleteModal.setAttribute('hidden', '');
    deleteId = null;
    if (btnConfirmDel) {
      btnConfirmDel.disabled = false;
      btnConfirmDel.textContent = 'Yes, Delete';
    }
  }

  if (btnCancelDel) btnCancelDel.addEventListener('click', closeDeleteModal);
  if (btnCloseDel) btnCloseDel.addEventListener('click', closeDeleteModal);
  deleteModal?.querySelector('.modal__backdrop')?.addEventListener('click', closeDeleteModal);

  document.addEventListener('click', function (event) {
    const target = event.target instanceof Element ? event.target.closest('.js-earning-del') : null;
    if (!target) return;

    // Force custom modal flow and block any other attached handlers (including legacy confirm dialogs).
    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
      event.stopImmediatePropagation();
    }

    deleteId = target.getAttribute('data-earning-id');
    if (!deleteId || !deleteModal) return;
    if (deleteModal.parentElement !== document.body) document.body.appendChild(deleteModal);
    deleteModal.removeAttribute('hidden');
  }, true);

  if (btnConfirmDel) {
    btnConfirmDel.addEventListener('click', function () {
      if (!deleteId) return;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('earning_id', deleteId);

      const originalText = btnConfirmDel.textContent;
      btnConfirmDel.disabled = true;
      btnConfirmDel.textContent = 'Deleting...';

      fetch(endpoint, {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(async function (res) {
          const data = await res.json();
          if (res.ok && data.success) {
            window.location.reload();
            return;
          }
          alert(data.message || 'Failed to delete record.');
          btnConfirmDel.disabled = false;
          btnConfirmDel.textContent = originalText;
        })
        .catch(function () {
          alert('Network error while deleting record.');
          btnConfirmDel.disabled = false;
          btnConfirmDel.textContent = originalText;
        });
    });
  }

  // Export
  document.querySelector('.js-export')?.addEventListener('click', function () {
    window.location.href = this.dataset.exportHref || '/M/earnings/export';
  });
  
});
</script>
