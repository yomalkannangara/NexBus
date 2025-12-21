<?php 
/** @var array $me,$depot,$counts,$todayDelayed */ 
// Ensure variables exist with defaults
$me = $me ?? [];
$depot = $depot ?? ['name' => 'Unknown Depot'];
$counts = $counts ?? ['delayed' => 0, 'breaks' => 0];
$todayDelayed = $todayDelayed ?? [];
?>
<div class="container">
<h1>Depot Dashboard – <?= htmlspecialchars($depot['name'] ?? 'Unknown Depot') ?></h1>

<div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:24px;">
  <div class="card delayed-card" style="position:relative;cursor:pointer;">
    <h3>Delayed Today</h3>
    <div style="font-size:2.5rem;font-weight:bold;color:#d97706;"><?= (int)($counts['delayed'] ?? 0) ?></div>
    <p style="color:#6b7280;margin:4px 0 0;">buses running late</p>
    
    <!-- Delayed buses dropdown -->
    <div class="delayed-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-top:8px;z-index:100;max-height:300px;overflow-y:auto;">
      <?php if (!empty($todayDelayed)): ?>
        <ul style="list-style:none;padding:8px;margin:0;">
          <?php foreach($todayDelayed as $r): ?>
            <li>
              <a href="?module=depot_officer&page=bus_profile&bus_reg_no=<?= urlencode($r['bus_reg_no'] ?? '') ?>" style="display:block;padding:10px 12px;color:#1f2937;text-decoration:none;border-radius:8px;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <strong><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></strong> • Route <?= htmlspecialchars($r['route_no'] ?? '-') ?>
                <br><small style="color:#6b7280;">+<?= htmlspecialchars($r['avg_delay_min'] ?? 0) ?> min delay</small>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div style="padding:12px;color:#6b7280;text-align:center;font-size:0.875rem;">No delayed buses</div>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="card">
    <h3>Breakdowns Today</h3>
    <div style="font-size:2.5rem;font-weight:bold;color:#dc2626;"><?= (int)($counts['breaks'] ?? 0) ?></div>
    <p style="color:#6b7280;margin:4px 0 0;">maintenance events</p>
  </div>
</div>

<div class="card" style="padding:20px;">
  <h2 style="margin:0 0 16px;">Latest Delays</h2>
  <?php if (!empty($todayDelayed)): ?>
    <table class="table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Bus</th>
          <th>Route</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($todayDelayed as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['route_no'] ?? '-') ?></td>
          <td><span class="badge badge-warning"><?= htmlspecialchars($r['operational_status'] ?? 'Unknown') ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color:#6b7280;text-align:center;padding:20px;">No delays reported today. Great job! 🎉</p>
  <?php endif; ?>
</div>

</div>


<style>
.card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
.card h3 { margin:0 0 12px; font-size:1rem; color:#6b7280; font-weight:500; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:12px; text-align:left; border-bottom:1px solid #e5e7eb; }
.table th { background:#f9fafb; font-weight:600; color:#374151; }
.table tbody tr:hover { background:#f9fafb; }
.badge { padding:4px 12px; border-radius:12px; font-size:0.875rem; font-weight:500; }
.badge-warning { background:#fef3c7; color:#92400e; }
.delayed-card:hover { background:#fffbeb; }
.delayed-card:hover .delayed-dropdown { display:block !important; }
</style>

<script>
  // Toggle delayed buses dropdown on hover
  const delayedCard = document.querySelector('.delayed-card');
  if (delayedCard) {
    const dropdown = delayedCard.querySelector('.delayed-dropdown');
    delayedCard.addEventListener('mouseenter', () => {
      dropdown.style.display = 'block';
    });
    delayedCard.addEventListener('mouseleave', () => {
      dropdown.style.display = 'none';
    });
  }
</script>