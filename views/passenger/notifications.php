<?php
// Expect: $items, $counts (all, delays, unread), $tab
$tab = $tab ?? 'alerts';
?>

<!-- Header bar -->
<div class="notif-head">
  <!-- Back -->
  <a href="/home" class="icon-btn" title="Back" aria-label="Back">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M15.5 19.5 8 12l7.5-7.5 1.5 1.5L11 12l6 6-1.5 1.5Z"/></svg>
  </a>
  <h3>Notifications</h3>
  <div class="unread"><?= (int)($counts['unread'] ?? 0) ?> unread notifications</div>
  <div class="spacer"></div>
  <!-- Mark all read -->
  <form method="post" class="notif-actions">
    <input type="hidden" name="action" value="mark_all" />
    <button class="btn" type="submit">Mark all read</button>
  </form>
</div>

<!-- Tabs: My Alerts, Delays, All -->
<nav class="notif-tabs2" role="tablist" aria-label="Notifications filter">
  <a class="tab2<?= $tab==='alerts' ? ' active' : '' ?>" href="/notifications?tab=alerts">My Alerts <span class="count"><?= (int)($counts['unread'] ?? 0) ?></span></a>
  <a class="tab2<?= $tab==='delays' ? ' active' : '' ?>" href="/notifications?tab=delays">Delays <span class="count"><?= (int)($counts['delays'] ?? 0) ?></span></a>
  <a class="tab2<?= $tab==='all' ? ' active' : '' ?>" href="/notifications?tab=all">All <span class="count"><?= (int)($counts['all'] ?? 0) ?></span></a>
</nav>

<!-- List -->
<div class="cards notif-list">
  <?php if (empty($items)): ?>
    <div class="notif-card">
      <div class="icon service" aria-hidden="true">✅</div>
      <div>
        <div class="title">You’re all caught up</div>
        <div class="meta">No notifications</div>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($items as $it): ?>
      <?php $tag = strtolower($it['tag'] ?? 'alert'); ?>
      <a class="notif-card-link" href="/notifications?tab=<?= urlencode($tab) ?>&mark=<?= (int)($it['id'] ?? 0) ?>">
        <div class="notif-card">
          <div class="icon <?= $tag ?>" aria-hidden="true">
            <?php if ($tag === 'delay'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2 1 21h22L12 2Zm1 15h-2v-2h2v2Zm0-4h-2V9h2v4Z"/></svg>
            <?php elseif ($tag === 'service' || $tag === 'timetable'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 21s-6.2-4.3-8.6-7.6C1.2 10.8 2.7 6 7 6c2 0 3.4 1 5 2.9C13.6 7 15 6 17 6c4.3 0 5.8 4.8 3.6 7.4C18.2 16.7 12 21 12 21z"/></svg>
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zM20 18l-2-2v-5a6 6 0 10-12 0v5l-2 2v2h16v-2z"/></svg>
            <?php endif; ?>
          </div>
          <div>
            <div class="title"><?= htmlspecialchars($it['message'] ?? '') ?></div>
            <div class="meta"><?= htmlspecialchars($it['age'] ?? '') ?></div>
          </div>
          <span class="tag <?= $tag ?>"><?= htmlspecialchars($it['tag'] ?? 'alert') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
