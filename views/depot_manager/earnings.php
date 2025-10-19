<?php
// Safe defaults if the controller didn't pass data
$top = $top ?? [
  ['value'=>'Rs. 845,500','label'=>'Daily Income','trend'=>'+5.2% from yesterday','color'=>'maroon'],
  ['value'=>'Rs. 1,250,000','label'=>'Highest Income','sub'=>'December 31, 2024','color'=>'green'],
  ['value'=>'Rs. 425,000','label'=>'Lowest Income','sub'=>'January 1, 2025','color'=>'red'],
];

$buses = $buses ?? [
  ['number'=>'NC-1247','route'=>'Colombo - Kandy','daily'=>'Rs. 12,500','weekly'=>'Rs. 87,500','eff'=>'95%'],
  ['number'=>'WP-3456','route'=>'Galle - Matara','daily'=>'Rs. 8,750','weekly'=>'Rs. 61,250','eff'=>'88%'],
  ['number'=>'CP-7890','route'=>'Negombo - Airport','daily'=>'Rs. 15,200','weekly'=>'Rs. 106,400','eff'=>'98%'],
  ['number'=>'SP-2134','route'=>'Kurunegala - Anuradhapura','daily'=>'Rs. 0','weekly'=>'Rs. 45,600','eff'=>'0%'],
  ['number'=>'EP-5678','route'=>'Trincomalee - Batticaloa','daily'=>'Rs. 9,800','weekly'=>'Rs. 68,600','eff'=>'92%'],
];

$month = $month ?? ['current'=>'Rs. 24.5M','previous'=>'Rs. 23.2M','growth'=>'+5.6%'];
?>
<section class="section">
  <!-- Top summary strip -->
  <div class="earn-top mt-0">
    <?php foreach ($top as $t): ?>
      <div class="earn-box <?= htmlspecialchars($t['color']) ?>">
        <div class="earn-value"><?= htmlspecialchars($t['value']) ?></div>
        <div class="earn-sub"><?= htmlspecialchars($t['label']) ?></div>
        <?php if (!empty($t['trend'])): ?>
          <div class="earn-trend text-green">▲ <?= htmlspecialchars($t['trend']) ?></div>
        <?php endif; ?>
        <?php if (!empty($t['sub'])): ?>
          <div class="earn-sub2 muted small"><?= htmlspecialchars($t['sub']) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Income per bus -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title primary">Income per Bus</div></div>
    <div class="income-list">
      <?php foreach ($buses as $b): ?>
        <div class="income-row">
          <div class="left">
            <div class="bus"><?= htmlspecialchars($b['number']) ?></div>
            <div class="muted small"><?= htmlspecialchars($b['route']) ?></div>
          </div>
          <div class="right-cols">
            <div class="col">
              <div class="muted small">Daily</div>
              <div class="fw-600"><?= htmlspecialchars($b['daily']) ?></div>
            </div>
            <div class="col">
              <div class="muted small">Weekly</div>
              <div class="fw-600"><?= htmlspecialchars($b['weekly']) ?></div>
            </div>
            <div class="col">
              <div class="muted small">Efficiency</div>
              <span class="chip chip-gold"><?= htmlspecialchars($b['eff']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Monthly summary -->
  <div class="card mt-6">
    <div class="monthly">
      <div class="muted">Monthly Income Overview</div>
      <div class="months">
        <div class="mcol">
          <div class="big primary"><?= htmlspecialchars($month['current']) ?></div>
          <div class="muted small">Current Month</div>
        </div>
        <div class="mcol">
          <div class="big" style="color:#eab308"><?= htmlspecialchars($month['previous']) ?></div>
          <div class="muted small">Previous Month</div>
        </div>
        <div class="mcol growth">
          <div class="big text-green"><?= htmlspecialchars($month['growth']) ?></div>
          <div class="muted small">Monthly Growth</div>
        </div>
      </div>
    </div>
  </div>
</section>
