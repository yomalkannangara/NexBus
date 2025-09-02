<section class="page-hero"><h1>Bus Management Dashboard</h1><p>National Transport Commission – Sri Lanka</p></section>

<section class="kpi-wrap">
  <div class="kpi-card"><h3>Total Buses</h3><div class="num"><?= ($stats['p']+$stats['s']) ?></div><div class="trend">+12% from yesterday</div></div>
  <div class="kpi-card"><h3>Registered Bus companies</h3><div class="num"><?= $stats['owners'] ?></div><div class="trend">+3% from yesterday</div></div>
  <div class="kpi-card"><h3>Active Depots</h3><div class="num"><?= $stats['depots'] ?></div><div class="trend">0% from yesterday</div></div>
  <div class="kpi-card"><h3>Active Routes</h3><div class="num"><?= $stats['routes'] ?></div><div class="trend">+5% from yesterday</div></div>
  <div class="kpi-card"><h3>Today's Complaints</h3><div class="num"><?= $stats['complaints'] ?></div><div class="trend">-2% from yesterday</div></div>
  <div class="kpi-card"><h3>Delayed Buses Today</h3><div class="num"><?= $stats['delayed'] ?></div><div class="trend down">+8% from yesterday</div></div>
  <div class="kpi-card"><h3>Broken Buses Today</h3><div class="num"><?= $stats['broken'] ?></div><div class="trend">-1% from yesterday</div></div>
</section>
<section class="filters"><h2>Bus Location Filters</h2><div class="filter-grid"><div><label>Route</label>
<select><option>All Routes</option>
<?php foreach($routes as $r) echo '<option value="'.htmlspecialchars($r['route_id']).'">'.htmlspecialchars($r['route_no']).'</option>'; ?>
</select></div><div><label>Bus Number</label><select><option>All Buses</option></select></div></div></section>