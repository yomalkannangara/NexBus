<section class="page-hero"><h1>Bus Management Dashboard</h1><p>National Transport Commission – Sri Lanka</p></section>
<section class="cards">
  <div class="card"><div class="card-title">Total Buses</div><div class="card-value"><?= ($stats['p']+$stats['s']) ?></div></div>
  <div class="card"><div class="card-title">Registered Bus Owners</div><div class="card-value"><?= $stats['owners'] ?></div></div>
  <div class="card"><div class="card-title">Active Depots</div><div class="card-value"><?= $stats['depots'] ?></div></div>
  <div class="card"><div class="card-title">Active Routes</div><div class="card-value"><?= $stats['routes'] ?></div></div>
  <div class="card"><div class="card-title">Today's Complaints</div><div class="card-value"><?= $stats['complaints'] ?></div></div>
  <div class="card"><div class="card-title">Delayed Buses Today</div><div class="card-value"><?= $stats['delayed'] ?></div></div>
  <div class="card"><div class="card-title">Broken Buses Today</div><div class="card-value"><?= $stats['broken'] ?></div></div>
</section>
<section class="filters"><h2>Bus Location Filters</h2><div class="filter-grid"><div><label>Route</label>
<select><option>All Routes</option>
<?php foreach($routes as $r) echo '<option value="'.htmlspecialchars($r['route_id']).'">'.htmlspecialchars($r['route_no']).'</option>'; ?>
</select></div><div><label>Bus Number</label><select><option>All Buses</option></select></div></div></section>