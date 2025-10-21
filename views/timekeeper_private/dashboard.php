<?php $S = $S ?? []; $stats = $stats ?? []; ?>
<div class="title-banner">
    <h1>Private TimeKeeper Dashboard</h1>
    <p><?= htmlspecialchars($S['depot_name'] ?? 'My Operator') ?> — National Transport Commission</p>
</div>

<!-- your dashboard cards -->
<div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:16px;margin:16px 0;">
  <div class="card accent-rose">
    <div class="metric-title">Total Buses Assigned Today</div>
    <div class="metric-value"><?= (int)($stats['assigned_today'] ?? 0) ?></div>
    <div class="metric-sub">
      <?= ($stats['assigned_delta'] ?? 0) >= 0 ? '+' : '' ?><?= (int)($stats['assigned_delta'] ?? 0) ?> from yesterday
    </div>
  </div>

  <div class="card accent-amber">
    <div class="metric-title">Today's Revenue</div>
    <div class="metric-value">LKR <?= number_format((float)($stats['revenue_today'] ?? 0),0,'.',',') ?></div>
    <div class="metric-sub">+12% from yesterday</div>
  </div>

  <div class="card accent-green">
    <div class="metric-title">Drivers on Duty</div>
    <div class="metric-value"><?= (int)($stats['drivers_on_duty'] ?? 0) ?></div>
    <div class="metric-sub">on shift today</div>
  </div>

  <div class="card accent-indigo">
    <div class="metric-title">Active Routes</div>
    <div class="metric-value"><?= (int)($stats['active_routes'] ?? 0) ?></div>
    <div class="metric-sub">From operator</div>
  </div>

  <div class="card accent-cyan">
    <div class="metric-title">Conductors on Duty</div>
    <div class="metric-value"><?= (int)($stats['conductors_on_duty'] ?? 0) ?></div>
    <div class="metric-sub">on shift today</div>
  </div>

  <div class="card accent-rose">
    <div class="metric-title">Location Updates</div>
    <div class="metric-value"><?= (int)($stats['location_pct'] ?? 0) ?>%</div>
    <div class="metric-sub">Last hour</div>
  </div>
</div>

<!-- Map placeholder panel -->
<div class="card">
  <div style="display:flex;align-items:center;gap:8px;font-weight:600;color:#4a0e25;margin-bottom:10px;">
    <div style="width:10px;height:10px;border-radius:999px;background:#e11d48;box-shadow:0 0 0 4px rgba(225,29,72,.15)"></div>
    <span>Live Bus Locations</span>
  </div>
  <div style="height:360px;border:1px dashed #e5e7eb;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#fdf2f8,#f1f5f9);">
    <p style="color:#6b7280;font-weight:600;margin:0;">Map Placeholder</p>
  </div>
</div>
