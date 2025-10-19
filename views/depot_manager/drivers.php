<?php
// No icons used.
// Expected from controller:
//   $metrics   = $m->metrics();           // array: [ ['label','value','accent?'], ... ]
//   $recent    = $m->driverActivities();  // array of driver activity rows
//   $recentCon = $m->conductorActivities(); // array of conductor activity rows

$metrics   = is_array($metrics   ?? null) ? $metrics   : [];
$recent    = is_array($recent    ?? null) ? $recent    : [];
$recentCon = is_array($recentCon ?? null) ? $recentCon : [];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function initial(?string $name): string {
  $n = trim((string)$name);
  return strtoupper(substr($n !== '' ? $n : '?', 0, 1));
}
?>
<section class="section">
  <div class="title-card">
  <h1 class="title-heading">Central Driver Database</h1>
  <p class="title-sub">SLTB driver and conductor records management</p>
</div>


  <!-- KPI / Metrics -->
  <div class="grid grid-4 gap-6 mt-6">
    <?php if ($metrics): ?>
      <?php foreach ($metrics as $m): ?>
        <div class="metric-card <?= h($m['accent'] ?? '') ?>">
          <div class="metric-value"><?= h($m['value'] ?? '0') ?></div>
          <div class="metric-sub"><?= h($m['label'] ?? '—') ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No metrics available.</div>
    <?php endif; ?>
  </div>

  <!-- Driver Recent Activities -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Driver Recent Activities</div></div>
    <?php if ($recent): ?>
      <div class="activity-list">
        <?php foreach ($recent as $r): ?>
          <?php
            $name = (string)($r['driver_name'] ?? $r['name'] ?? '');
            $id   = (string)($r['id'] ?? '');
            $text = (string)($r['activity'] ?? $r['text'] ?? '');
            $time = (string)($r['created_at'] ?? $r['time'] ?? '');
            $status = (string)($r['status'] ?? 'Active');
            $chip = ($status === 'Active') ? 'chip-green' : (($status === 'Suspended') ? 'chip-yellow' : 'chip-blue');
          ?>
          <div class="activity-row">
            <span class="avatar"><?= h(initial($name)) ?></span>
            <div class="activity-main">
              <div class="title"><?= h($name) ?></div>
              <div class="muted small"><?= h($id) ?> • <?= h($text) ?></div>
            </div>
            <div class="activity-meta muted small"><?= h($time) ?></div>
            <div class="activity-status">
              <span class="chip <?= $chip ?>"><?= h($status) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No recent driver activity.</div>
    <?php endif; ?>
  </div>

  <!-- Conductor Recent Activities -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title" style="color:var(--primary)">Conductor Recent Activities</div></div>
    <?php if ($recentCon): ?>
      <div class="activity-list">
        <?php foreach ($recentCon as $r): ?>
          <?php
            $name = (string)($r['conductor_name'] ?? $r['name'] ?? '');
            $id   = (string)($r['id'] ?? '');
            $text = (string)($r['activity'] ?? $r['text'] ?? '');
            $time = (string)($r['created_at'] ?? $r['time'] ?? '');
            $status = (string)($r['status'] ?? 'Active');
            $chip = ($status === 'Active') ? 'chip-green' : (($status === 'Suspended') ? 'chip-yellow' : 'chip-blue');
          ?>
          <div class="activity-row">
            <span class="avatar alt"><?= h(initial($name)) ?></span>
            <div class="activity-main">
              <div class="title"><?= h($name) ?></div>
              <div class="muted small"><?= h($id) ?> • <?= h($text) ?></div>
            </div>
            <div class="activity-meta muted small"><?= h($time) ?></div>
            <div class="activity-status">
              <span class="chip <?= $chip ?>"><?= h($status) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No recent conductor activity.</div>
    <?php endif; ?>
  </div>
</section>
