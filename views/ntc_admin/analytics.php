<section class="page-hero"><h1>Analytics Dashboard</h1><p>Bus performance metrics and operational insights</p></section>
<section class="kpi-wrap">
  <div class="kpi-card alert"><h3>Delayed Buses Today</h3><div class="num"><?= $delayed ?></div></div>
  <div class="kpi-card ok"><h3>Average Driver Rating</h3><div class="num"><?= $rating ?></div></div>
  <div class="kpi-card warn"><h3>Speed Violations</h3><div class="num"><?= $speed_viol ?></div></div>
  <div class="kpi-card info"><h3>Long Wait Times</h3><div class="num"><?= $long_wait ?>%</div></div>
</section>
<p class="muted">Charts can be added with pure JS later.</p>