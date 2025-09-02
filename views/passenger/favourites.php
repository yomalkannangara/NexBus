<?php
// which routes are already favs?
$favIds = array_map(fn($x)=> (int)$x['route_id'], $favs ?? []);
$favSet = array_flip($favIds);
$total  = count($favIds);
?>

<section class="fav-header">
  <div>
    <h2>Favourites</h2>
    <div class="fav-sub"><?= $total ?> saved <?= $total===1?'route':'routes' ?></div>
  </div>
  <div class="fav-actions">
    <a class="btn ghost" href="?module=passenger&page=home">+ Add</a>
    <button class="btn ghost" type="button" onclick="document.body.classList.toggle('fav-edit')">Edit</button>
  </div>
</section>

<?php foreach ($routes as $r): 
  $rid = (int)$r['route_id'];
  $on  = isset($favSet[$rid]);
  $active = (int)($r['is_active'] ?? 1) === 1;
?>
  <article class="card fav-card">
    <div class="fav-left">
      <div class="fav-toprow">
        <span class="star"><?= $on ? '★' : '☆' ?></span>
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
            <?= htmlspecialchars(
                isset($it['minutes_from_departure']) && $it['minutes_from_departure'] > 0
                    ? "⏱ Departured {$it['minutes_from_departure']} minutes before"
                    : "⏱ Not departured yet"
            ) ?>
        </span>
        <span class="meta-dot">•</span>
        <span class="meta-item muted">Used <?= $on ? 'recently' : '—' ?></span>
      </div>

      <div class="fav-row">
        <div class="fav-alerts">
          <span>🔔 Bus alerts</span>
        </div>
        <form method="post" class="toggle-form">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="route_id" value="<?= $rid ?>">
          <input type="hidden" name="on" value="<?= $on ? '0':'1' ?>">
          <label class="switch">
            <input type="checkbox" <?= $on ? 'checked' : '' ?> onchange="this.form.submit()">
            <i></i>
          </label>
        </form>
      </div>
    </div>

    <div class="fav-edit-ctl">
      <!-- visible when 'Edit' button toggles body.fav-edit -->
      <form method="post">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="route_id" value="<?= $rid ?>">
        <input type="hidden" name="on" value="<?= $on ? '0':'1' ?>">
        <button class="btn pill small" type="submit"><?= $on ? 'Remove' : 'Add' ?></button>
      </form>
    </div>
  </article>
<?php endforeach; ?>
