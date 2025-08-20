<section class="page-hero"><h1>Analytics Dashboard</h1><p>Bus performance metrics and operational insights</p></section>
<section class="cards">
  <div class="card alert"><div class="card-title">Delayed Buses Today</div><div class="card-value"><?= $delayed ?></div></div>
  <div class="card ok"><div class="card-title">Average Driver Rating</div><div class="card-value"><?= $rating ?></div></div>
  <div class="card warn"><div class="card-title">Speed Violations</div><div class="card-value"><?= $speed_viol ?></div></div>
  <div class="card info"><div class="card-title">Long Wait Times</div><div class="card-value"><?= $long_wait ?>%</div></div>
</section>
<p class="muted">Charts can be added with pure JS later.</p>