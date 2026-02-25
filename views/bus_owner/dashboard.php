<?php
// views/bus_owner/dashboard.php
$user     = $_SESSION['user'] ?? [];
$greeting = (int)date('H') < 12 ? 'Good Morning' : ((int)date('H') < 17 ? 'Good Afternoon' : 'Good Evening');
$uname    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$uname    = $uname !== '' ? $uname : ($user['name'] ?? ($user['full_name'] ?? 'Owner'));

$totalBuses  = (int)($total_buses       ?? 0);
$activeBuses = (int)($active_buses      ?? 0);
$totalDrivers= (int)($total_drivers     ?? 0);
$totalRev    = (float)($total_revenue   ?? 0);
$maintBuses  = (int)($maintenance_buses ?? 0);
$activePct   = $totalBuses > 0 ? round($activeBuses / $totalBuses * 100) : 0;
?>

<!-- ── Page Header ── -->
<div class="dash-hero">
  <div class="dash-hero-left">
    <div class="dash-greeting"><?= $greeting ?>, <?= htmlspecialchars($uname) ?> 👋</div>
    <h1 class="dash-title">Dashboard</h1>
    <p class="dash-sub">Here's what's happening with your fleet today</p>
  </div>
  <div class="dash-hero-right">
    <div class="dash-date-pill">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="2" width="13" height="12" rx="2"/><line x1="1" y1="6" x2="14" y2="6"/><line x1="4" y1="1" x2="4" y2="3"/><line x1="10" y1="1" x2="10" y2="3"/></svg>
      <?= date('l, d F Y') ?>
    </div>

  </div>
</div>

<!-- ── KPI Cards ── -->
<div class="stats-grid">

  <!-- Total Fleet -->
  <div class="stat-card kpi-fleet">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="7" width="22" height="13" rx="2"/><path d="M1 13h22M5 20v2M19 20v2M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/></svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Fleet</div>
      <div class="stat-value"><?= $totalBuses ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--fleet" style="width:100%"></div>
      </div>
      <div class="stat-change"><?= $activeBuses ?> active · <?= $maintBuses ?> in maintenance</div>
    </div>
  </div>

  <!-- Active Buses -->
  <div class="stat-card kpi-active">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Active Buses</div>
      <div class="stat-value"><?= $activeBuses ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--active" style="width:<?= $activePct ?>%"></div>
      </div>
      <div class="stat-change"><?= $activePct ?>% fleet utilisation</div>
    </div>
  </div>

  <!-- Total Drivers -->
  <div class="stat-card kpi-drivers">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Staff</div>
      <div class="stat-value"><?= $totalDrivers ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--drivers" style="width:100%"></div>
      </div>
      <div class="stat-change">Registered drivers &amp; conductors</div>
    </div>
  </div>

  <!-- Revenue -->
  <div class="stat-card kpi-revenue">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value kpi-revenue-val">
        <span class="kpi-currency">LKR</span><?= number_format($totalRev) ?>
      </div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--revenue" style="width:100%"></div>
      </div>
      <div class="stat-change">All-time earnings</div>
    </div>
  </div>

</div>

<!-- ── Quick Actions ── -->
<div class="card dash-qa-card">
  <h3 class="card-title">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
    Quick Actions
  </h3>
  <div class="quick-actions-grid">
    <a href="/B/fleet" class="quick-action-btn qa-fleet">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="7" width="22" height="13" rx="2"/><path d="M1 13h22M5 20v2M19 20v2"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Manage Fleet</span>
        <span class="qa-desc">Add or edit buses</span>
      </div>
    </a>
    <a href="/B/drivers" class="quick-action-btn qa-staff">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Staff</span>
        <span class="qa-desc">Manage drivers &amp; conductors</span>
      </div>
    </a>
    <a href="/B/attendance" class="quick-action-btn qa-attendance">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Attendance</span>
        <span class="qa-desc">Mark today's attendance</span>
      </div>
    </a>
    <a href="/B/earnings" class="quick-action-btn qa-earnings">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Earnings</span>
        <span class="qa-desc">Record &amp; view income</span>
      </div>
    </a>
    <a href="/B/performance" class="quick-action-btn qa-perf">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Performance</span>
        <span class="qa-desc">Analytics &amp; reports</span>
      </div>
    </a>
    <a href="/B/feedback" class="quick-action-btn qa-feedback">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H7l-4 3V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Feedback</span>
        <span class="qa-desc">Passenger complaints</span>
      </div>
    </a>
  </div>
</div>

<!-- ── Bottom row ── -->
<div class="two-column-layout">

  <!-- Recent Buses -->
  <div class="card">
    <h3 class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="7" width="22" height="13" rx="2"/><path d="M1 13h22"/></svg>
      Recent Buses Added
    </h3>
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr><th>Reg. Number</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (!empty($recent_buses)): ?>
            <?php foreach ($recent_buses as $b):
              $status = $b['status'] ?? 'Active';
              $cls = match($status) {
                'Maintenance' => 'status-maintenance',
                'Inactive'    => 'status-out',
                default       => 'status-active'
              };
              $reg = $b['reg_no'] ?? ($b['bus_number'] ?? '—');
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($reg) ?></strong></td>
              <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($status) ?></span></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2" class="dash-empty-row">No buses registered yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <a href="/B/fleet" class="dash-view-all">View all buses →</a>
  </div>

  <!-- Alerts & Status -->
  <div class="card">
    <h3 class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      Fleet Status
    </h3>
    <div class="alerts-list">

      <?php if ($maintBuses > 0): ?>
      <div class="alert-item alert-warning">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="alert-content">
          <div class="alert-title"><?= $maintBuses ?> bus<?= $maintBuses > 1 ? 'es' : '' ?> under maintenance</div>
          <div class="alert-time">Review in Fleet management</div>
        </div>
      </div>
      <?php endif; ?>

      <div class="alert-item alert-success">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="alert-content">
          <div class="alert-title"><?= $activeBuses ?> bus<?= $activeBuses !== 1 ? 'es' : '' ?> operational</div>
          <div class="alert-time"><?= $activePct ?>% of fleet is active</div>
        </div>
      </div>

      <?php if ($totalDrivers === 0): ?>
      <div class="alert-item alert-warning">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="alert-content">
          <div class="alert-title">No drivers registered</div>
          <div class="alert-time">Add drivers in the Staff section</div>
        </div>
      </div>
      <?php else: ?>
      <div class="alert-item alert-info">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2"/><path d="M10 8v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="alert-content">
          <div class="alert-title"><?= $totalDrivers ?> staff member<?= $totalDrivers !== 1 ? 's' : '' ?> registered</div>
          <div class="alert-time">Manage in Staff section</div>
        </div>
      </div>
      <?php endif; ?>

      <div class="alert-item alert-neutral">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/><path d="M10 6v4l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="alert-content">
          <div class="alert-title">Today — <?= date('d M Y') ?></div>
          <div class="alert-time">Mark attendance for today's shifts</div>
        </div>
      </div>

    </div>
    <a href="/B/attendance" class="dash-view-all">Mark today's attendance →</a>
  </div>

