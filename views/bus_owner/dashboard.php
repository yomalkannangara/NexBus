<?php
// views/bus_owner/dashboard.php
// Expects: $total_buses, $active_buses, $total_drivers, $total_revenue
//          $recent_buses (array of rows incl. reg_no, status), $maintenance_buses (int)
// BASE_URL is defined in the layout as '/B'
?>
<header class="page-header">
  <div>
    <h2 class="page-title">Dashboard</h2>
    <p class="page-subtitle">Welcome to NTC Fleet Management System</p>
  </div>

</header>

<div class="stats-grid">
  <div class="stat-card stat-maroon">
    <div class="stat-content">
      <div class="stat-label">Total Fleet Size</div>
      <div class="stat-value"><?= (int)($total_buses ?? 0); ?></div>
      <div class="stat-change positive">All buses</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><rect x="6" y="20" width="36" height="20" rx="4" stroke="currentColor" stroke-width="2"/><path d="M6 26h36M16 32h.02M32 32h.02" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
  </div>

  <div class="stat-card stat-green">
    <div class="stat-content">
      <div class="stat-label">Active Buses</div>
      <div class="stat-value"><?= (int)($active_buses ?? 0); ?></div>
      <div class="stat-change positive">Currently operational</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M24 44c11.046 0 20-8.954 20-20S35.046 4 24 4 4 12.954 4 24s8.954 20 20 20z" stroke="currentColor" stroke-width="2"/><path d="M18 24l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
  </div>

  <div class="stat-card stat-yellow">
    <div class="stat-content">
      <div class="stat-label">Total Drivers</div>
      <div class="stat-value"><?= (int)($total_drivers ?? 0); ?></div>
      <div class="stat-change positive">Registered drivers</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M34 42v-4a8 8 0 0 0-8-8h-8a8 8 0 0 0-8 8v4M22 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
  </div>

  <div class="stat-card stat-orange">
    <div class="stat-content">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value"><span class="stat-value-currency">LKR</span> <?= number_format((float)($total_revenue ?? 0)); ?></div>
      <div class="stat-change positive">All time</div>
    </div>
    <div class="stat-icon">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2"/><path d="M24 16v-4M24 36v-4M32 24h4M12 24h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
  </div>
</div>

<div class="card">
  <h3 class="card-title">Quick Actions</h3>
  <div class="quick-actions-grid">
    <a href="<?= BASE_URL; ?>/fleet" class="quick-action-btn action-maroon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Add New Bus
    </a>
    <a href="<?= BASE_URL; ?>/drivers" class="quick-action-btn action-yellow">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" stroke="currentColor" stroke-width="2"/></svg>
      Add Driver
    </a>
    <a href="<?= BASE_URL; ?>/earnings" class="quick-action-btn action-orange">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Record Income
    </a>
    <a href="<?= BASE_URL; ?>/performance" class="quick-action-btn action-green">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M3 3v18h18M7 16l4-4 4 4 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      View Reports
    </a>
  </div>
</div>

<div class="two-column-layout">
  <div class="card">
    <h3 class="card-title">Recent Buses Added</h3>
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr><th>Reg. Number</th><th>Route</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (!empty($recent_buses)): ?>
            <?php foreach ($recent_buses as $b): 
              $status = $b['status'] ?? 'Active';
              $cls = match($status) {
                'Maintenance'    => 'status-maintenance',
                'Out of Service' => 'status-out',
                default          => 'status-active'
              };
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($b['bus_number'] ?? ''); ?></strong></td>
              <td class="text-secondary"><?= htmlspecialchars($b['route'] ?? ''); ?></td>
              <td><span class="status-badge <?= $cls; ?>"><?= htmlspecialchars($status); ?></span></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3" style="text-align:center;padding:24px;color:#6B7280;">No recent buses.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3 class="card-title">System Alerts</h3>
    <div class="alerts-list">
      <?php if ((int)($maintenance_buses ?? 0) > 0): ?>
      <div class="alert-item alert-warning">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="alert-content">
          <div class="alert-title"><?= (int)$maintenance_buses; ?> bus(es) in maintenance</div>
          <div class="alert-time">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="currentColor"/><path d="M7 3v4l2 2" stroke="currentColor" stroke-linecap="round"/></svg>
            Check fleet management
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="alert-item alert-success">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="alert-content">
          <div class="alert-title">System running normally</div>
          <div class="alert-time">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="currentColor"/><path d="M7 3v4l2 2" stroke="currentColor" stroke-linecap="round"/></svg>
            All systems operational
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
