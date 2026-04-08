<?php
/** @var array $bus */
$bus = $bus ?? [];
$busReg = htmlspecialchars($bus['reg_no'] ?? 'Unknown');
$route = htmlspecialchars($bus['route'] ?? '—');
$busModel = htmlspecialchars($bus['bus_model'] ?? '—');
$year = htmlspecialchars($bus['year_of_manufacture'] ?? '—');
$capacity = htmlspecialchars($bus['capacity'] ?? '—');
$busClass = htmlspecialchars($bus['bus_class'] ?? 'Normal');
$status = htmlspecialchars($bus['status'] ?? '—');
$chassis = htmlspecialchars($bus['chassis_no'] ?? '—');
$manufactureDate = htmlspecialchars($bus['manufacture_date'] ?? '—');
$currentLocation = is_numeric($bus['current_lat'] ?? null) && is_numeric($bus['current_lng'] ?? null)
    ? htmlspecialchars($bus['current_lat'] . ', ' . $bus['current_lng'])
    : '—';
$iconColor = '#64748B';
if (strtolower($busClass) === 'semi luxury') $iconColor = '#3B82F6';
if (strtolower($busClass) === 'luxury') $iconColor = '#F59E0B';
?>

<section id="busProfilePage" class="section fleet-section">
  <div class="fleet-header fleet-header-compact">
    <div class="fleet-header-left">
      <h1 class="title-heading">Bus Profile</h1>
      <p class="title-sub">Details for bus <?= $busReg ?></p>
    </div>
    <a href="/M/fleet" class="btn btn-secondary fleet-back-btn">← Back to Fleet</a>
  </div>

  <div class="fleet-cards-grid" style="grid-template-columns:1fr; gap:20px; margin-bottom:20px;">
    <div class="fleet-card">
      <div class="fleet-card-header" style="align-items:flex-start; gap:16px;">
        <div>
          <div class="bus-badge" style="display:inline-block; margin-bottom:10px;"><?= $busReg ?></div>
          <div style="font-size:0.9rem; color:#6B7280; margin-bottom:8px;">Status</div>
          <span class="status-badge" style="background:<?= $status === 'Active' ? '#D1FAE5' : ($status === 'Maintenance' ? '#FEF3C7' : '#FEE2E2') ?>; color:<?= $status === 'Active' ? '#065F46' : ($status === 'Maintenance' ? '#92400E' : '#B91C1C') ?>;">
            <?= $status ?>
          </span>
        </div>
        <div class="bus-class-badge" style="background-color: <?= $iconColor ?>; width:auto; padding:0 12px; border-radius:999px; font-size:0.95rem;">
          <?= $busClass ?>
        </div>
      </div>

      <div class="fleet-card-body" style="display:grid; gap:16px;">
        <div class="card-row" style="grid-template-columns:repeat(2,minmax(200px,1fr)); display:grid; gap:16px;">
          <div class="card-info">
            <span class="card-label">Route</span>
            <span class="card-value"><?= $route ?></span>
          </div>
          <div class="card-info">
            <span class="card-label">Model</span>
            <span class="card-value"><?= $busModel ?></span>
          </div>
        </div>

        <div class="card-row" style="grid-template-columns:repeat(2,minmax(200px,1fr)); display:grid; gap:16px;">
          <div class="card-info">
            <span class="card-label">Year of Manufacture</span>
            <span class="card-value"><?= $year ?></span>
          </div>
          <div class="card-info">
            <span class="card-label">Capacity</span>
            <span class="card-value"><?= $capacity ?> seats</span>
          </div>
        </div>

        <div class="card-row" style="grid-template-columns:repeat(2,minmax(200px,1fr)); display:grid; gap:16px;">
          <div class="card-info">
            <span class="card-label">Chassis Number</span>
            <span class="card-value"><?= $chassis ?></span>
          </div>
          <div class="card-info">
            <span class="card-label">Manufacture Date</span>
            <span class="card-value"><?= $manufactureDate ?></span>
          </div>
        </div>

        <div class="card-info">
          <span class="card-label">Last Known Location</span>
          <span class="card-value"><?= $currentLocation ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .fleet-back-btn { display:inline-flex; align-items:center; gap:8px; }
  .fleet-header-compact { align-items:center; justify-content:space-between; margin-bottom:20px; }
</style>
