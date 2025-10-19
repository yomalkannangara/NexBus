<?php use App\Support\Icons; ?>
<section class="section">
  <h1 class="h1 primary">Central Driver Database</h1>
  <p class="muted">SLTB driver and conductor records management</p>

  <div class="grid grid-4 gap-6 mt-6">
    <?php foreach ($metrics as $m): ?>
      <div class="metric-card <?= $m['accent'] ?>">
        <div class="metric-value"><?= htmlspecialchars($m['value']) ?></div>
        <div class="metric-sub"><?= htmlspecialchars($m['label']) ?></div>
        <div class="metric-icon"><?= Icons::svg('user','',28,28) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Driver Recent Activities</div></div>
    <div class="activity-list">
      <?php foreach ($recent as $r): ?>
        <div class="activity-row">
          <span class="activity-icon"><?= Icons::svg('activity','',24,24) ?></span>
          <div class="activity-main">
            <div class="title"><?= htmlspecialchars($r['name']) ?></div>
            <div class="muted small"><?= htmlspecialchars($r['id']) ?> • <?= htmlspecialchars($r['text']) ?></div>
          </div>
          <div class="activity-meta muted small"><?= htmlspecialchars($r['time']) ?></div>
          <div class="activity-status">
            <span class="chip <?= $r['status']==='Active'?'chip-green':'chip-yellow' ?>"><?= htmlspecialchars($r['status']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Conductor Recent Activities</div></div>
    <div class="activity-list">
      <?php foreach ($recentCon as $r): ?>
        <div class="activity-row">
          <span class="activity-icon"><?= Icons::svg('user','',24,24) ?></span>
          <div class="activity-main">
            <div class="title"><?= htmlspecialchars($r['name']) ?></div>
            <div class="muted small"><?= htmlspecialchars($r['id']) ?> • <?= htmlspecialchars($r['text']) ?></div>
          </div>
          <div class="activity-meta muted small"><?= htmlspecialchars($r['time']) ?></div>
          <div class="activity-status">
            <span class="chip chip-green"><?= htmlspecialchars($r['status']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
