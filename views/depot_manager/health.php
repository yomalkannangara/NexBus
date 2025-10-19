<?php
// Expecting from controller:
//   $metrics   = $metrics   ?? [];
//   $ongoing   = $ongoing   ?? [];
//   $completed = $completed ?? [];
?>
<section class="section">

    <div class="title-card">
  <h1 class="title-heading">Bus Health Monitoring</h1>
  <p class="title-sub">Vehicle service and maintenance tracking</p>
  </div>
  <!-- KPI / Metric cards -->
  <div class="grid grid-3 gap-6 mt-6">
    <?php if (!empty($metrics)): ?>
      <?php foreach ($metrics as $m): ?>
        <div class="metric-card <?= htmlspecialchars($m['accent'] ?? '') ?>">
          <div class="metric-value"><?= htmlspecialchars($m['value'] ?? '0') ?></div>
          <div class="metric-sub"><?= htmlspecialchars($m['label'] ?? '—') ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No metric data.</div>
    <?php endif; ?>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Ongoing Maintenance</div></div>
    <?php if (!empty($ongoing)): ?>
      <div class="list">
        <?php foreach ($ongoing as $o): ?>
          <div class="list-row">
            <div class="left">
              <div class="bus"><?= htmlspecialchars($o['bus'] ?? '—') ?></div>
              <div class="muted small">
                <?= htmlspecialchars($o['task'] ?? '—') ?><br>
                Started: <?= htmlspecialchars($o['start'] ?? '—') ?>
              </div>
            </div>
            <div class="middle">
              <div class="title"><?= htmlspecialchars($o['workshop'] ?? '—') ?></div>
              <div class="muted small">Est. completion: <?= htmlspecialchars($o['eta'] ?? '—') ?></div>
            </div>
            <div class="right">
              <span class="chip chip-blue"><?= (int)($o['progress'] ?? 0) ?>% Complete</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No ongoing maintenance jobs.</div>
    <?php endif; ?>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Completed Maintenance</div></div>
    <?php if (!empty($completed)): ?>
      <div class="list">
        <?php foreach ($completed as $c): ?>
          <div class="list-row">
            <div class="left">
              <div class="bus"><?= htmlspecialchars($c['bus'] ?? '—') ?></div>
              <div class="muted small">
                <?= htmlspecialchars($c['task'] ?? '—') ?><br>
                Completed: <?= htmlspecialchars($c['date'] ?? '—') ?>
              </div>
            </div>
            <div class="middle">
              <div class="title"><?= htmlspecialchars($c['vendor'] ?? '—') ?></div>
              <div class="muted small"><?= htmlspecialchars($c['cost'] ?? '') ?></div>
            </div>
            <div class="right">
              <div class="stack-right">
                <div class="muted small">Next Service</div>
                <span class="chip chip-green"><?= htmlspecialchars($c['next'] ?? '—') ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No completed maintenance records.</div>
    <?php endif; ?>
  </div>
</section>
