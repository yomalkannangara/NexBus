<?php
// Data expected from controller:
//   $top   = $top   ?? [];   // array: [ ['value','label','trend?','sub?','color'], ... ]
//   $buses = $buses ?? [];   // array: [ ['number','route','daily','weekly','eff'], ... ]
//   $month = $month ?? [];   // array: [ 'current','previous','growth' ]

$top   = is_array($top   ?? null) ? $top   : [];
$buses = is_array($buses ?? null) ? $buses : [];
$month = is_array($month ?? null) ? $month : [];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<section class="section">

  <div class="title-card">
  <h1 class="title-heading">Earnings & Income Tracking</h1>
  <p class="title-sub">Revenue analysis and income monitoring</p>
</div>

  <!-- Top summary strip -->
  <div class="earn-top mt-0">
    <?php if ($top): ?>
      <?php foreach ($top as $t): ?>
        <?php
          $val   = h($t['value'] ?? '');
          $lab   = h($t['label'] ?? '');
          $trend = (string)($t['trend'] ?? '');
          $sub   = h($t['sub'] ?? '');
          $color = h($t['color'] ?? 'maroon');
          $isUp  = strlen($trend) && $trend[0] === '+';
        ?>
        <div class="earn-box <?= $color ?>">
          <div class="earn-value"><?= $val ?></div>
          <div class="earn-sub"><?= $lab ?></div>
          <?php if ($trend !== ''): ?>
            <div class="earn-trend <?= $isUp ? 'text-green' : 'text-red' ?>">
              <?= $isUp ? '▲' : '▼' ?> <?= h($trend) ?>
            </div>
          <?php endif; ?>
          <?php if ($sub !== ''): ?>
            <div class="earn-sub2 muted small"><?= $sub ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No summary yet.</div>
    <?php endif; ?>
  </div>

  <!-- Income per bus -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title primary">Income per Bus</div></div>
    <?php if ($buses): ?>
      <div class="income-list">
        <?php foreach ($buses as $b): ?>
          <div class="income-row">
            <div class="left">
              <div class="bus"><?= h($b['number'] ?? '—') ?></div>
              <div class="muted small"><?= h($b['route'] ?? '—') ?></div>
            </div>
            <div class="right-cols">
              <div class="col">
                <div class="muted small">Daily</div>
                <div class="fw-600"><?= h($b['daily'] ?? 'Rs. 0') ?></div>
              </div>
              <div class="col">
                <div class="muted small">Weekly</div>
                <div class="fw-600"><?= h($b['weekly'] ?? 'Rs. 0') ?></div>
              </div>
              <div class="col">
                <div class="muted small">Efficiency</div>
                <span class="chip chip-gold"><?= h($b['eff'] ?? '0%') ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No bus income data.</div>
    <?php endif; ?>
  </div>

  <!-- Monthly summary -->
  <div class="card mt-6">
    <div class="monthly">
      <div class="muted">Monthly Income Overview</div>
      <div class="months">
        <div class="mcol">
          <div class="big primary"><?= h($month['current'] ?? 'Rs. 0') ?></div>
          <div class="muted small">Current Month</div>
        </div>
        <div class="mcol">
          <div class="big" style="color:#eab308"><?= h($month['previous'] ?? 'Rs. 0') ?></div>
          <div class="muted small">Previous Month</div>
        </div>
        <div class="mcol growth">
          <div class="big <?= (isset($month['growth']) && strpos($month['growth'], '-') === 0) ? 'text-red' : 'text-green' ?>">
            <?= h($month['growth'] ?? '+0.0%') ?>
          </div>
          <div class="muted small">Monthly Growth</div>
        </div>
      </div>
    </div>
  </div>
</section>
