<?php
// Expecting from controller:
//   $cards = $cards ?? [];
//   $rows  = $rows  ?? [];
?>
<section class="section">
    <div class="title-card">
  <h1 class="title-heading">Performance Reports</h1>
  <p class="title-sub">Driver performance tracking and analytics</p>
  </div>
  <!-- KPI cards -->
  <div class="kpis mt-6">
    <?php if (!empty($cards)): ?>
      <?php foreach ($cards as $c): ?>
        <div class="kpi <?= htmlspecialchars($c['color'] ?? '') ?>">
          <div class="kpi-value"><?= htmlspecialchars($c['value'] ?? '') ?></div>
          <div class="kpi-title"><?= htmlspecialchars($c['title'] ?? '') ?></div>
          <div class="kpi-sub"><?= htmlspecialchars($c['sub'] ?? '') ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No KPI data.</div>
    <?php endif; ?>
  </div>

  <!-- Top drivers table -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title">Top Performing Drivers</div>
    </div>

    <?php if (!empty($rows)): ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Driver Name</th>
              <th>Route</th>
              <th>Delaying Rate</th>
              <th>Average Driver Rating</th>
              <th>Speed Violation</th>
              <th>Long Wait Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $r): ?>
              <tr class="<?= $i % 2 === 0 ? 'alt' : '' ?>">
                <td><?= (int)($r['rank'] ?? ($i+1)) ?></td>
                <td class="primary fw-600"><?= htmlspecialchars($r['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['route'] ?? '—') ?></td>
                <td><span class="chip chip-green"><?= htmlspecialchars($r['delay'] ?? '0%') ?></span></td>
                <td><?= htmlspecialchars($r['rating'] ?? '0.0') ?></td>
                <td><span class="chip chip-orange"><?= htmlspecialchars($r['speed'] ?? '0') ?></span></td>
                <td><span class="chip chip-green"><?= htmlspecialchars($r['wait'] ?? '0%') ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No performance rows.</div>
    <?php endif; ?>
  </div>
</section>
