<?php if(!empty($msg)): ?>
  <div class="card notice success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<section class="page-head">
  <h2>Feedback &amp; Reports</h2>
  <p class="muted">Help us improve our service</p>
</section>

<form method="post" enctype="multipart/form-data" class="card form big">
  <input type="hidden" name="action" value="create">

  <!-- Type -->
  <div class="field">
    <label class="req">Type</label>
    <div class="radio-row">
      <label class="radio-pill">
        <input type="radio" name="type" value="feedback" checked>
        <span>Feedback</span>
      </label>
      <label class="radio-pill">
        <input type="radio" name="type" value="complaint">
        <span>Complaint</span>
      </label>
    </div>
  </div>

  <!-- Route -->
    <div class="field">
      <label class="req">Select Route</label>
      <div class="select-wrap">
        <select name="route_id" required>
          <option value="">Choose a bus route</option>
          <?php foreach(($routes ?? []) as $r): ?>
            <option value="<?= (int)$r['route_id'] ?>">
              <?= htmlspecialchars($r['route_no'].' — '.($r['name'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label>Select Bus (Optional)</label>
      <div class="select-wrap">
        <select name="bus_id" id="busSelect">
          <option value="">Choose a bus</option>
        </select>
      </div>
    </div>



  <!-- Bus type -->
  <div class="field">
    <label class="req">Bus Type</label>
    <div class="radio-row">
      <label class="radio-pill">
        <input type="radio" name="bus_type" value="SLTB" checked>
        <span>SLTB</span>
      </label>
      <label class="radio-pill">
        <input type="radio" name="bus_type" value="Private">
        <span>Private</span>
      </label>
    </div>
  </div>

  <!-- Date & Time (optional) -->
  <div class="grid-2">
    <div class="field">
      <label>Date (Optional)</label>
      <div class="select-wrap">
        <input type="date" name="date">
      </div>
    </div>
    <div class="field">
      <label>Time (Optional)</label>
      <div class="select-wrap">
        <input type="time" name="time">
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="field">
    <label class="req">Description</label>
    <textarea name="description" required placeholder="Please provide details..."></textarea>
  </div>

  <div class="form-actions">
    <button class="btn">Submit</button>
  </div>
</form>

<?php
  // Map route_id -> route_no for pretty chips
  $routeMap = [];
  foreach (($routes ?? []) as $r) { $routeMap[(int)$r['route_id']] = $r['route_no'] ?? $r['route_id']; }
?>

<section class="page-head" style="margin-top:16px">
  <h2>Your Submissions</h2>
  <p class="muted">Status and staff replies</p>
</section>

<?php if (!empty($mine)): ?>
  <?php foreach ($mine as $row): ?>
    <?php
      $status = strtolower($row['status'] ?? 'open');
      $etaClass = 'eta';
      if ($status === 'resolved') $etaClass = 'eta ok';
      elseif ($status === 'closed') $etaClass = 'eta down';
    ?>
    <article class="card">
      <!-- top row -->
      <div class="fav-toprow">
        <div class="fav-title">
          <span class="route-chip">
            <?= htmlspecialchars($routeMap[(int)($row['route_id'] ?? 0)] ?? ($row['route_id'] ?? '—')) ?>
          </span>
          <strong><?= htmlspecialchars(ucfirst($row['category'] ?? 'Feedback')) ?></strong>
          <span class="muted">• <?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at'] ?? 'now'))) ?></span>
        </div>
        <div class="<?= $etaClass ?>">
          <span class="dot"></span><?= htmlspecialchars(ucfirst($row['status'] ?? 'Open')) ?>
        </div>
      </div>

      <!-- meta -->
      <div class="fav-meta" style="margin-top:8px">
        <span class="meta-item">Bus: <?= htmlspecialchars($row['bus_reg_no'] ?? '—') ?></span>
        <span class="meta-item">Type: <?= htmlspecialchars($row['operator_type'] ?? '—') ?></span>
        <span class="muted">#<?= (int)($row['complaint_id'] ?? 0) ?></span>
      </div>

      <!-- description -->
      <?php if (!empty($row['description'])): ?>
        <p class="muted" style="margin:10px 0 0"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
      <?php endif; ?>

      <!-- staff reply -->
      <?php if (!empty($row['reply_text'])): ?>
        <div class="pill" style="margin-top:10px; display:block">
          <strong>Reply:</strong>&nbsp;<?= nl2br(htmlspecialchars($row['reply_text'])) ?>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card"><p class="muted" style="margin:0">No submissions yet.</p></div>
<?php endif; ?>
