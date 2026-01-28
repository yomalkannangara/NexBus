<?php 
/** @var array $bus, $assignments, $trips, $tracking */
$bus = $bus ?? [];
$assignments = $assignments ?? [];
$trips = $trips ?? [];
$tracking = $tracking ?? [];
$busReg = htmlspecialchars($bus['bus_reg_no'] ?? 'Unknown');
?>

<section class="page-hero">
  <h1><?= $busReg ?> - Bus Profile</h1>
  <p>Bus information, assignments, and trip history</p>
</section>

<!-- Bus Overview Card -->
<section class="panel show">
  <div class="panel-head"><h2>Bus Information</h2></div>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;padding:20px;">
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Registration Number</div>
      <div style="font-size:1.5rem;font-weight:bold;color:#1f2937;"><?= $busReg ?></div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Make & Model</div>
      <div style="font-size:1.125rem;color:#1f2937;"><?= htmlspecialchars($bus['make_model'] ?? 'N/A') ?></div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Capacity</div>
      <div style="font-size:1.125rem;color:#1f2937;"><?= htmlspecialchars($bus['capacity'] ?? 'N/A') ?> seats</div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">License Expiry</div>
      <div style="font-size:1.125rem;color:#1f2937;"><?= htmlspecialchars($bus['license_expiry'] ?? 'N/A') ?></div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Status</div>
      <div style="font-size:1.125rem;">
        <span class="badge" style="background:<?= ($bus['status'] === 'Active' ? '#ecfdf5' : '#fef2f2') ?>;color:<?= ($bus['status'] === 'Active' ? '#065f46' : '#991b1b') ?>;">
          <?= htmlspecialchars($bus['status'] ?? 'Unknown') ?>
        </span>
      </div>
    </div>
  </div>
</section>

<!-- Current Tracking Status -->
<?php if (!empty($tracking)): ?>
<section class="panel show" style="margin-top:20px;">
  <div class="panel-head"><h2>Current Status</h2></div>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;padding:20px;">
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Route</div>
      <div style="font-size:1.125rem;font-weight:bold;color:#1f2937;"><?= htmlspecialchars($tracking['route_no'] ?? '-') ?></div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Operational Status</div>
      <div style="font-size:1.125rem;">
        <span class="badge" style="background:<?= ($tracking['operational_status'] === 'OnTime' ? '#ecfdf5' : ($tracking['operational_status'] === 'Delayed' ? '#fef3c7' : '#fef2f2')) ?>;color:<?= ($tracking['operational_status'] === 'OnTime' ? '#065f46' : ($tracking['operational_status'] === 'Delayed' ? '#92400e' : '#991b1b')) ?>;">
          <?= htmlspecialchars($tracking['operational_status'] ?? 'Unknown') ?>
        </span>
      </div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Current Speed</div>
      <div style="font-size:1.125rem;color:#1f2937;"><?= htmlspecialchars($tracking['speed'] ?? '0') ?> km/h</div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Average Delay</div>
      <div style="font-size:1.125rem;color:#1f2937;">+<?= htmlspecialchars($tracking['avg_delay_min'] ?? '0') ?> min</div>
    </div>
    <div>
      <div style="font-size:0.875rem;color:#6b7280;margin-bottom:4px;">Last Update</div>
      <div style="font-size:1.125rem;color:#1f2937;"><?= htmlspecialchars($tracking['snapshot_at'] ?? 'N/A') ?></div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Assignment History -->
<section class="panel show" style="margin-top:20px;">
  <div class="panel-head"><h2>Assignment History</h2></div>
  
  <?php if (!empty($assignments)): ?>
    <div style="overflow-x:auto;">
      <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
            <th style="padding:12px;text-align:left;font-weight:600;">Route</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Driver</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Date Assigned</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($assignments as $a): ?>
            <tr style="border-bottom:1px solid #e5e7eb;">
              <td style="padding:12px;"><?= htmlspecialchars($a['route_no'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($a['driver_name'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($a['assigned_date'] ?? '-') ?></td>
              <td style="padding:12px;">
                <span class="badge" style="background:#ecfdf5;color:#065f46;padding:4px 8px;border-radius:6px;font-size:0.875rem;">
                  <?= htmlspecialchars($a['status'] ?? 'Active') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div style="padding:20px;color:#6b7280;text-align:center;">No assignment history found.</div>
  <?php endif; ?>
</section>

<!-- Trip History -->
<section class="panel show" style="margin-top:20px;">
  <div class="panel-head"><h2>Recent Trip History</h2></div>
  
  <?php if (!empty($trips)): ?>
    <div style="overflow-x:auto;">
      <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
            <th style="padding:12px;text-align:left;font-weight:600;">Date</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Route</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Turn</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Departure</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Arrival</th>
            <th style="padding:12px;text-align:left;font-weight:600;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($trips as $t): ?>
            <tr style="border-bottom:1px solid #e5e7eb;">
              <td style="padding:12px;"><?= htmlspecialchars($t['trip_date'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($t['route_no'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($t['turn_no'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($t['departure_time'] ?? '-') ?></td>
              <td style="padding:12px;"><?= htmlspecialchars($t['arrival_time'] ?? '-') ?></td>
              <td style="padding:12px;">
                <span class="badge" style="background:<?= ($t['status'] === 'Completed' ? '#ecfdf5' : ($t['status'] === 'Cancelled' ? '#fef2f2' : '#e0f2fe')) ?>;color:<?= ($t['status'] === 'Completed' ? '#065f46' : ($t['status'] === 'Cancelled' ? '#991b1b' : '#075985')) ?>;padding:4px 8px;border-radius:6px;font-size:0.875rem;">
                  <?= htmlspecialchars($t['status'] ?? 'Unknown') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div style="padding:20px;color:#6b7280;text-align:center;">No trip history found.</div>
  <?php endif; ?>
</section>

<div style="margin-top:20px;padding:20px;text-align:center;">
  <a href="?module=depot_officer&page=dashboard" class="button">← Back to Dashboard</a>
</div>

<style>
  .page-hero { background:linear-gradient(135deg,#7a0f2e 0%,#a01845 100%);color:white;padding:40px;border-radius:12px;margin-bottom:20px; }
  .page-hero h1 { margin:0;font-size:2rem; }
  .page-hero p { margin:8px 0 0;opacity:0.9;font-size:0.95rem; }
  .panel { background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;overflow:hidden; }
  .panel-head { background:#f9fafb;padding:16px;border-bottom:1px solid #e5e7eb; }
  .panel-head h2 { margin:0;font-size:1.125rem;color:#1f2937; }
  .badge { display:inline-block;padding:4px 12px;border-radius:999px;font-weight:600;font-size:0.875rem; }
  .button { display:inline-block;padding:10px 20px;background:#7a0f2e;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:background 0.2s; }
  .button:hover { background:#a01845; }
</style>
