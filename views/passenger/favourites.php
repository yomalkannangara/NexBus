<?php
$total = count(array_filter($routes, fn($r)=>!empty($r['favourite_id'])));

?>

<section class="fav-header">
  <div>
    <h2>Favourites</h2>
    <div class="fav-sub"><?= $total ?> saved <?= $total===1?'route':'routes' ?></div>
  </div>
  <div class="fav-actions">
    <!-- Add route toggle -->
    <button class="btn ghost" type="button"
      onclick="document.getElementById('fav-add-form').classList.toggle('hidden')">
      + Add
    </button>
    <!-- Edit toggle -->
    <button class="btn ghost" type="button"
      onclick="document.body.classList.toggle('fav-edit')">Edit</button>
  </div>
</section>

<!-- Add Route form (once only, outside loop) -->
<form method="post" id="fav-add-form" class="hidden card">
  <input type="hidden" name="action" value="add">

  <label for="route-select" class="muted" style="font-size:14px; font-weight:600; margin-bottom:6px; display:block;">
    Select Route:
  </label>

  <div class="select-wrap">
    <select id="route-select" name="route_id" required>
      <?php foreach ($allRoutes as $r): ?>
        <option value="<?= $r['route_id'] ?>">
          <?= htmlspecialchars($r['route_no'] . " - " . $r['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn small">Confirm</button>
  </div>
</form>


<?php foreach ($routes as $r): 
  $rid     = (int)$r['route_id'];
  $notify  = (int)($r['notify_enabled'] ?? 0) === 1;
  $active  = (int)($r['is_active'] ?? 1) === 1;
?>

<article class="card fav-card">
  <!-- Delete (trash icon) only visible in edit mode -->
  <div class="fav-edit-ctl">
    <form method="post">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="route_id" value="<?= $rid ?>">
      <button type="submit" class="trash-btn" title="Remove">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
             viewBox="0 0 24 24" fill="currentColor">
          <path d="M6 7v13a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6zm3 3h2v9H9v-9zm4 0h2v9h-2v-9z"/>
          <path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4z"/>
        </svg>
      </button>
    </form>
  </div>

  <div class="fav-left">
    <div class="fav-toprow">
      <span class="eta <?= $active ? 'ok':'down' ?>">
        <?= $active ? '3 min' : 'No service' ?>
        <i class="dot"></i>
      </span>
    </div>

    <div class="fav-title">
      <span class="route-chip"><?= htmlspecialchars($r['route_no']) ?></span>
      <strong><?= htmlspecialchars($r['name'] ?? '') ?></strong>
    </div>

    <div class="fav-meta">
      <span class="meta-item">
        <?php if ($r['minutes_from_departure'] !== null): ?>
          ⏱ Departed <?= $r['minutes_from_departure'] ?> minutes ago 
          (Bus <?= htmlspecialchars($r['latest_bus']) ?>)
        <?php else: ?>
          ⏱ No active bus right now
        <?php endif; ?>
      </span>
      <span class="meta-dot">•</span>
      <span class="meta-item muted">
        <?= htmlspecialchars($r['operator_type'] ?? '—') ?>
      </span>
    </div>

    <div class="fav-row">
      <div class="fav-alerts">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" 
            viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 
                  2 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 
                  3.17 10.5 4v.68C7.63 5.36 6 
                  7.92 6 11v5l-1.7 1.7c-.14.14-.3.33-.3.55 0 
                  .39.31.75.7.75h14c.39 0 .7-.36.7-.75 
                  0-.22-.16-.41-.3-.55L18 16z"/>
        </svg>
        <span>Bus alerts</span>
      </div>
      <form method="post" class="toggle-form">
        <input type="hidden" name="action" value="notify">
        <input type="hidden" name="route_id" value="<?= $rid ?>">
        <input type="hidden" name="on" value="<?= $notify ? '0':'1' ?>">
        <label class="switch">
          <input type="checkbox" <?= $notify ? 'checked' : '' ?> onchange="this.form.submit()">
          <i></i>
        </label>
      </form>
    </div>
  </div>
</article>
<?php endforeach; ?>
