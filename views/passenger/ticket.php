<h3>Ticket Prices</h3>

<form method="post" class="form card">
  <input type="hidden" name="action" value="calc">

  <div class="field">
    <label>Route</label>
    <select name="route_id" onchange="this.form.submit()">
      <option value="">-- choose --</option>
      <?php foreach($routes as $r): ?>
        <option value="<?= (int)$r['route_id'] ?>" <?= (!empty($selectedRoute) && (int)$selectedRoute===(int)$r['route_id'])?'selected':'' ?>>
          <?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if(!empty($selectedRoute)): ?>
    <div class="field">
      <label>Start</label>
      <select name="start_idx">
        <?php foreach($stops as $s): ?>
          <option value="<?= (int)$s['idx'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Destination</label>
      <select name="end_idx">
        <?php foreach($stops as $s): ?>
          <option value="<?= (int)$s['idx'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Calculate</button>
  <?php endif; ?>
</form>

<?php if(!empty($fare)): ?>
  <section class="fare-grid">
    <div class="card fare"><div class="badge">Normal</div><div class="amount">Rs. <?= (float)$fare['normal'] ?></div></div>
    <div class="card fare"><div class="badge">Semi Luxury</div><div class="amount">Rs. <?= (float)$fare['semi_luxury'] ?></div></div>
    <div class="card fare"><div class="badge">Luxury</div><div class="amount">Rs. <?= (float)$fare['luxury'] ?></div></div>
    <div class="card fare"><div class="badge">Super Luxury</div><div class="amount">Rs. <?= (float)$fare['super_luxury'] ?></div></div>
  </section>

  <section class="card journey">
    <div><strong><?= htmlspecialchars($fare['start_name']) ?></strong> → <strong><?= htmlspecialchars($fare['end_name']) ?></strong></div>
    <small><?= (int)$fare['stages'] ?> stage<?= $fare['stages']>1?'s':'' ?> • ~<?= (float)$fare['distance_km'] ?> km</small>
  </section>
<?php endif; ?>
