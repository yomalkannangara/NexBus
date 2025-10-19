<?php
// Safe defaults if the controller didn't pass data
$cards = $cards ?? [
  ['title'=>'Delayed Buses Today','value'=>'47','sub'=>'Filtered results','color'=>'red'],
  ['title'=>'Average Driver Rating','value'=>'8.0','sub'=>'Filtered average','color'=>'green'],
  ['title'=>'Speed Violations','value'=>'75','sub'=>'Filtered data','color'=>'yellow'],
  ['title'=>'Long Wait Times','value'=>'15%','sub'=>'Over 10 minutes','color'=>'maroon'],
];

$rows = $rows ?? [
  ['rank'=>1,'name'=>'Sunil Perera','route'=>'Colombo - Kandy','delay'=>'2%','rating'=>'4.8','speed'=>'1','wait'=>'3%'],
  ['rank'=>2,'name'=>'Pradeep Kumar','route'=>'Trincomalee - Batticaloa','delay'=>'4%','rating'=>'4.7','speed'=>'2','wait'=>'5%'],
  ['rank'=>3,'name'=>'Ravi Fernando','route'=>'Negombo - Airport','delay'=>'5%','rating'=>'4.6','speed'=>'0','wait'=>'4%'],
  ['rank'=>4,'name'=>'Anil Jayawardana','route'=>'Galle - Matara','delay'=>'6%','rating'=>'4.5','speed'=>'3','wait'=>'7%'],
  ['rank'=>5,'name'=>'Mahesh Silva','route'=>'Kurunegala - Anuradhapura','delay'=>'7%','rating'=>'4.4','speed'=>'1','wait'=>'8%'],
];
?>
<section class="section">
  <h1 class="h1 primary">Performance Reports</h1>
  <p class="muted">Driver performance tracking and analytics</p>

  <!-- KPI cards -->
  <div class="kpis mt-6">
    <?php foreach ($cards as $c): ?>
      <div class="kpi <?= htmlspecialchars($c['color']) ?>">
        <div class="kpi-value"><?= htmlspecialchars($c['value']) ?></div>
        <div class="kpi-title"><?= htmlspecialchars($c['title']) ?></div>
        <div class="kpi-sub"><?= htmlspecialchars($c['sub']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Top drivers table -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title">Top Performing Drivers</div>
    </div>
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
          <?php foreach ($rows as $i=>$r): ?>
            <tr class="<?= $i%2===0 ? 'alt' : '' ?>">
              <td><?= (int)$r['rank'] ?></td>
              <td class="primary fw-600"><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['route']) ?></td>
              <td><span class="chip chip-green"><?= htmlspecialchars($r['delay']) ?></span></td>
              <td><?= htmlspecialchars($r['rating']) ?></td>
              <td><span class="chip chip-orange"><?= htmlspecialchars($r['speed']) ?></span></td>
              <td><span class="chip chip-green"><?= htmlspecialchars($r['wait']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
