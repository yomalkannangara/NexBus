<h3>Notifications</h3>
<div class="notif-list">
  <?php foreach($items as $n): ?>
    <div class="card notif">
      <div style="font-size:18px">🔔</div>
      <div>
        <div><strong><?= htmlspecialchars($n['title']) ?></strong></div>
        <div class="bus-meta"><?= htmlspecialchars($n['age']) ?> • <?= htmlspecialchars($n['meta']) ?></div>
      </div>
      <span class="tag"><?= htmlspecialchars($n['tag']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
