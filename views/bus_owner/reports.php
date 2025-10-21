<?php
// Content-only Performance Reports view (structure only)
// Expects: $top_drivers (array) and optionally $metrics from ReportModel::getPerformanceMetrics()
// Uses same classes/hooks as your sample.
?>

<header class="page-header">
  <div>
    <h2 class="page-title">Performance Reports</h2>
    <p class="page-subtitle">Driver performance tracking and analytics</p>
  </div>
  <div class="header-actions">
    <select class="time-filter js-time-filter">
      <option value="6m" selected>6 Months</option>
      <option value="3m">3 Months</option>
      <option value="1m">1 Month</option>
    </select>
    <button class="export-report-btn js-export"
            type="button"
            data-export-href="<?= BASE_URL; ?>/reports/export">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
        <path d="M16 11v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4M13 6L9 2 5 6M9 2v10"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Export Report
    </button>
  </div>
</header>

<!-- Stats Cards -->
<div class="stats-grid stats-grid-4">
  <div class="stat-card stat-red">
    <div class="stat-content">
      <div class="stat-label">Delayed Buses Today</div>
      <div class="stat-value">
        <?= (int)($metrics['delayed_buses'] ?? 47); ?>
      </div>
      <div class="stat-change">Filtered results</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2"/>
        <path d="M24 12v12l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
  </div>

  <div class="stat-card stat-green">
    <div class="stat-content">
      <div class="stat-label">Average Driver Rating</div>
      <div class="stat-value">
        <?= number_format((float)($metrics['average_rating'] ?? 8.0), 1); ?>
      </div>
      <div class="stat-change">Filtered average</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <path d="M24 6l6 12 13 2-9.5 9 2.5 13-12-6.5L12 42l2.5-13L5 20l13-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>

  <div class="stat-card stat-yellow">
    <div class="stat-content">
      <div class="stat-label">Speed Violations</div>
      <div class="stat-value">
        <?= (int)($metrics['speed_violations'] ?? 75); ?>
      </div>
      <div class="stat-change">Filtered data</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <path d="M42 24H28l-4 8-8-16-4 8H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>

  <div class="stat-card stat-maroon">
    <div class="stat-content">
      <div class="stat-label">Long Wait Times</div>
      <div class="stat-value">
        <?= (int)($metrics['long_wait_rate'] ?? 15); ?>%
      </div>
      <div class="stat-change">Over 10 minutes</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <path d="M6 18l12-12 12 12M6 38l12-12 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>
</div>

<!-- Top Performing Drivers Table -->
<div class="card">
  <h3 class="card-title">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
      <path d="M17 11v-1a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10" stroke="currentColor" stroke-width="2"/>
      <circle cx="14" cy="14" r="4" stroke="currentColor" stroke-width="2"/>
    </svg>
    Top Performing Drivers
  </h3>

  <div class="table-container">
    <table class="data-table performance-table" id="performance-table">
      <thead>
        <tr>
          <th style="width: 60px;"></th>
          <th>Driver Name</th>
          <th>Route</th>
          <th>Delaying Rate</th>
          <th>Average Driver Rating</th>
          <th>Speed Violation</th>
          <th>Long Wait Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($top_drivers)): ?>
          <?php
          $rank = 1;
          foreach ($top_drivers as $d):
            $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";
          ?>
            <tr>
              <td><div class="rank-badge <?= $rank_class; ?>"><?= $rank; ?></div></td>
              <td><strong><?= htmlspecialchars($d['name'] ?? ''); ?></strong></td>
              <td><?= htmlspecialchars($d['assignment_route'] ?? ''); ?></td>

              <!-- Placeholders to match structure; replace with real fields if you have them -->
              <td><span class="metric-badge metric-green"><?= rand(2,5); ?>%</span></td>

              <td>
                <div class="rating-with-icon">
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="#F59E0B" aria-hidden="true">
                    <path d="M7 1l1.5 4.5h4.5l-3.5 2.5 1.5 4.5L7 10l-3.5 2.5 1.5-4.5-3.5-2.5h4.5z"/>
                  </svg>
                  <span><?= number_format((float)($d['rating'] ?? 0), 1); ?></span>
                </div>
              </td>

              <td><span class="metric-badge metric-orange"><?= rand(0,3); ?></span></td>
              <td><span class="metric-badge metric-green"><?= rand(3,7); ?>%</span></td>
            </tr>
          <?php
            $rank++;
          endforeach;
          ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:40px;color:#6B7280;">
              No driver performance data available.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
