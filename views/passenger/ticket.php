<h3>Ticket Prices</h3>

<form method="post" class="ticket-form">
  <input type="hidden" name="action" value="calc">

  <div class="field">
    <label>Route</label>
    <div class="select-wrap no-caret">
      <select name="route_id" onchange="this.form.submit()">
        <option value="">-- choose --</option>
        <?php foreach($routes as $r): ?>
          <option value="<?= (int)$r['route_id'] ?>" <?= (!empty($selectedRoute) && (int)$selectedRoute===(int)$r['route_id'])?'selected':'' ?>>
            <?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <?php if(!empty($selectedRoute)): ?>
    <div class="field">
      <label>Start</label>
      <div class="select-wrap no-caret">
        <select name="start_idx">
          <?php foreach($stops as $s): ?>
            <option value="<?= (int)$s['idx'] ?>"
              <?= (!empty($_POST['start_idx']) && (int)$_POST['start_idx'] === (int)$s['idx']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label>Destination</label>
      <div class="select-wrap no-caret">
        <select name="end_idx">
          <?php foreach($stops as $s): ?>
            <option value="<?= (int)$s['idx'] ?>"
              <?= (!empty($_POST['end_idx']) && (int)$_POST['end_idx'] === (int)$s['idx']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn" type="submit" style="width:100%">Calculate</button>
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
