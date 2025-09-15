<?php
/** @var array $routes */
/** @var int|null $route_id */
/** @var string|null $operator_type */
/** @var string $date */
/** @var array $rows */
/** @var int $dow */

$DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>
<div class="page-head">
  <h2>Timetable</h2>
  <div class="muted">Find buses by route and date. Cards show bus, times, stops, and live status.</div>
</div>

<form method="get" class="grid-2 card" style="padding:12px;">
  <div class="field">
    <label class="req">Route</label>
    <div class="select-wrap">
      <select name="route_id" required>
        <option value="">Select a Route</option>
        <?php foreach($routes as $r): ?>
          <option value="<?= (int)$r['route_id'] ?>" <?= ($route_id && (int)$route_id===(int)$r['route_id'])?'selected':'' ?>>
            <?= htmlspecialchars($r['route_no']) ?> — <?= htmlspecialchars($r['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="field">
    <label class="req">Date</label>
    <div class="select-wrap no-caret">
      <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
      <i></i>
    </div>
  </div>

  <div class="field">
    <label>Operator</label>
    <div class="select-wrap">
      <select name="operator_type">
        <option value="">All</option>
        <option value="SLTB"    <?= ($operator_type==='SLTB')?'selected':'' ?>>SLTB</option>
        <option value="Private" <?= ($operator_type==='Private')?'selected':'' ?>>Private</option>
      </select>
    </div>
  </div>

  <div class="field" style="align-self:end; text-align:right;">
    <button class="btn">View Timetable</button>
  </div>
</form>

<?php if (!$route_id): ?>
  <div class="notice card" style="padding:12px;">Select a route and date to see trips.</div>
<?php elseif (empty($rows)): ?>
  <div class="notice card error" style="padding:12px;">No trips found for this route on <?= htmlspecialchars($date) ?>.</div>
<?php else: ?>

  <div class="section-title">
    <h3><?= htmlspecialchars($DAYS[$dow]) ?> • <?= htmlspecialchars($date) ?></h3>
    <span class="badge">Route #<?= htmlspecialchars($rows[0]['route_no'] ?? '') ?></span>
  </div>

  <div class="cards">
    <?php foreach($rows as $it): 
      $status = $it['latest_status'] ?? 'Unknown';
      $statusClass = match($status){
        'OnTime'    => 'ok',
        'Delayed'   => 'down',
        'Breakdown' => 'down',
        'OffDuty'   => '',
        default     => ''
      };
    ?>
    <div class="card">
      <!-- Top row -->
      <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <div class="route-chip"><?= htmlspecialchars($it['route_no']) ?></div>
          <div style="font-weight:700;">
            <?= htmlspecialchars($it['route_name']) ?>
            <div class="muted" style="font-size:12px;">
              <?= htmlspecialchars($it['operator_type']) ?> • Bus <?= htmlspecialchars($it['bus_reg_no']) ?>
            </div>
          </div>
        </div>

        <div class="eta <?= $statusClass ?>">
          <span class="dot"></span>
          <?= htmlspecialchars($status) ?>
        </div>
      </div>

      <!-- Time row -->
      <div style="display:flex; align-items:center; gap:16px; margin-top:10px;">
        <div>
          <div style="font-size:13px; color:var(--muted);">Departure</div>
          <div style="font-weight:700;"><?= htmlspecialchars(substr($it['departure_time'],0,5)) ?></div>
        </div>
        <div class="meta-dot">• • •</div>
        <div>
          <div style="font-size:13px; color:var(--muted);">Arrival</div>
          <div style="font-weight:700;"><?= htmlspecialchars(substr($it['arrival_time'] ?? '',0,5)) ?></div>
        </div>
        <?php if(!empty($it['duration_min'])): ?>
          <span class="badge"><?= (int)$it['duration_min'] ?> min</span>
        <?php endif; ?>
      </div>

      <!-- Stops -->
      <div style="margin-top:10px;">
        <div class="muted" style="font-size:12px; margin-bottom:4px;">Stops</div>
        <div class="fav-meta" style="flex-wrap:wrap;">
          <?php foreach(($it['stops_segment'] ?? []) as $s): ?>
            <span class="meta-item"><?= htmlspecialchars($s) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Days row (Mon..Sun, highlight today’s DOW the card belongs to) -->
      <div class="fav-row">
        <div class="fav-alerts">
          <?php foreach ($DAYS as $i=>$d): ?>
            <span class="badge" style="opacity:<?= (int)$i===(int)$it['day_of_week'] ? '1' : '.45' ?>"><?= $d ?></span>
          <?php endforeach; ?>
        </div>
        <div class="muted" style="font-size:12px;">TT#<?= (int)$it['timetable_id'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
