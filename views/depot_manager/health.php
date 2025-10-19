<?php use App\Support\Icons; ?>
<section class="section">
  <h1 class="h1 primary">Bus Health Monitoring</h1>
  <p class="muted">Vehicle service and maintenance tracking</p>

  <div class="grid grid-3 gap-6 mt-6">
    <?php foreach ($metrics as $m): ?>
      <div class="metric-card <?= $m['accent'] ?>">
        <div class="metric-value"><?= htmlspecialchars($m['value']) ?></div>
        <div class="metric-sub"><?= htmlspecialchars($m['label']) ?></div>
        <div class="metric-icon"><?= Icons::svg($m['icon'],'',28,28) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Ongoing Maintenance</div></div>
    <div class="list">
      <?php foreach ($ongoing as $o): ?>
        <div class="list-row">
          <div class="left">
            <div class="bus"><?= htmlspecialchars($o['bus']) ?></div>
            <div class="muted small"><?= htmlspecialchars($o['task']) ?><br>Started: <?= htmlspecialchars($o['start']) ?></div>
          </div>
          <div class="middle">
            <div class="title"><?= htmlspecialchars($o['workshop']) ?></div>
            <div class="muted small">Est. completion: <?= htmlspecialchars($o['eta']) ?></div>
          </div>
          <div class="right">
            <span class="chip chip-blue"><?= (int)$o['progress'] ?>% Complete</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Completed Maintenance</div></div>
    <div class="list">
      <?php foreach ($completed as $c): ?>
        <div class="list-row">
          <div class="left">
            <div class="bus"><?= htmlspecialchars($c['bus']) ?></div>
            <div class="muted small"><?= htmlspecialchars($c['task']) ?><br>Completed: <?= htmlspecialchars($c['date']) ?></div>
          </div>
          <div class="middle">
            <div class="title"><?= htmlspecialchars($c['vendor']) ?></div>
            <div class="muted small">Cost: Rs. 15,000</div>
          </div>
          <div class="right">
            <div class="stack-right">
              <div class="muted small">Next Service</div>
              <span class="chip chip-green"><?= htmlspecialchars($c['next']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
