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
        <?php foreach($routes as $r): ?>
          <option value="<?= (int)$r['route_id'] ?>">
            <?= htmlspecialchars($r['route_no'].' — '.($r['name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <i class="sel-chevron">▾</i>
    </div>
  </div>

  <!-- Bus (optional) -->
  <div class="field">
    <label>Select Bus (Optional)</label>
    <div class="select-wrap">
      <input type="text" name="bus_id" placeholder="Choose a bus number (optional)">
      <i class="sel-chevron">#</i>
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
      <div class="select-wrap icon-left">
        <span class="left-ic">📅</span>
        <input type="date" name="date">
      </div>
    </div>
    <div class="field">
      <label>Time (Optional)</label>
      <div class="select-wrap icon-left">
        <span class="left-ic">🕒</span>
        <input type="time" name="time">
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="field">
    <label class="req">Description</label>
    <textarea name="description" required placeholder="Please provide details..."></textarea>
  </div>

  <!-- Photo upload -->
  

  <div class="form-actions">
    <button class="btn">Submit</button>
  </div>
</form>
