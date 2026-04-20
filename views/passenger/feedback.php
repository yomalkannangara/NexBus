<?php if(!empty($msg)): ?>
  <div class="card notice <?= (stripos($msg,'fail') !== false || stripos($msg,'error') !== false) ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<section class="page-head">
  <h2>Feedback &amp; Reports</h2>
  <p class="muted">Help us improve our service</p>
</section>

<form method="post" class="card form big">
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
        <select name="route_id" id="routeSelect" required>
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
      <label>Select Bus <span class="muted" style="font-weight:400;font-size:12px">(Optional)</span></label>
      <div class="select-wrap">
        <select name="bus_id" id="busSelect">
          <option value="">Any bus</option>
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

  <!-- Rating (optional) -->
  <div class="field" id="ratingField">
    <label>Rating (Optional)</label>
    <div class="select-wrap">
      <select name="rating" id="ratingSelect">
        <option value="">Select rating (1-5)</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
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
        <?php if (!empty($row['rating'])): ?>
          <span class="meta-item">Rating: <?= (int)$row['rating'] ?>/5</span>
        <?php endif; ?>
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

<script>
/* Rating field — hide entirely when Complaint is selected, show for Feedback */
(function () {
  const ratingField  = document.getElementById('ratingField');
  const ratingSelect = document.getElementById('ratingSelect');
  const typeRadios   = document.querySelectorAll('input[name="type"]');

  function syncRating() {
    const sel = document.querySelector('input[name="type"]:checked');
    const isComplaint = sel && sel.value === 'complaint';
    if (ratingField) {
      ratingField.style.display = isComplaint ? 'none' : '';
    }
    if (ratingSelect && isComplaint) {
      ratingSelect.value = ''; // clear value so it's not submitted
    }
  }

  typeRadios.forEach(r => r.addEventListener('change', syncRating));
  syncRating(); // run on load
})();
</script>
