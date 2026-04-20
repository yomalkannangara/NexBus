<?php
$S              = $S ?? [];
$recent         = $recent ?? [];
$filter         = in_array(($filter ?? 'all'), ['all','unread','alert'], true) ? $filter : 'all';
$unreadCount    = (int)($unread_count ?? 0);
$msg            = $msg ?? null;
$chatThread     = $chat_thread ?? [];
$myUserId       = (int)($my_user_id ?? 0);
$hasDepotOfficer= (bool)($has_depot_officer ?? false);
$chatPartners   = $chat_partners ?? [];
$activeChatUserId = (int)($active_chat_user_id ?? 0);
$activeChatPartner = $active_chat_partner ?? null;
$activePartnerName = trim((string)($activeChatPartner['officer_name'] ?? 'Depot Officer'));
$activePartnerDepot = trim((string)($activeChatPartner['depot_name'] ?? ''));
$activeChatHref = '/TS/messages?filter=' . rawurlencode($filter)
  . ($activeChatUserId > 0 ? '&chat_user_id=' . $activeChatUserId : '')
  . '#chat';

$flashMap = [
    'read'       => ['type'=>'ok',  'text'=>'Message marked as read.'],
    'read_all'   => ['type'=>'ok',  'text'=>'All messages marked as read.'],
    'sent'       => ['type'=>'ok',  'text'=>'Your message was sent to the Depot Officer.'],
    'error'      => ['type'=>'err', 'text'=>'Unable to update message status.'],
    'send_error' => ['type'=>'err', 'text'=>'Failed to send message. Please try again.'],
];

function ts_time_ago(?string $ts): string {
    if (!$ts) return '';
    $at   = strtotime($ts);
    if ($at === false) return $ts;
    $diff = time() - $at;
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return intdiv($diff, 60) . 'm ago';
    if ($diff < 86400) return intdiv($diff, 3600) . 'h ago';
    return date('d M', $at);
}
?>
<style>
:root { --sltb:#7B1C3E; --gold:#f3b944; }
.ts-msg-page { display:flex; flex-direction:column; gap:0; height:calc(100vh - 80px); min-height:0; color:#111827; }
.ts-msg-header {
    background:linear-gradient(135deg,var(--sltb) 0%,#a8274e 100%);
    border-bottom:4px solid var(--gold); border-radius:14px 14px 0 0;
    color:#fff; padding:16px 22px 0; flex-shrink:0;
}
.ts-msg-title { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
.ts-msg-title h1 { margin:0; font-size:1.2rem; font-weight:800; }
.ts-msg-title p  { margin:3px 0 0; font-size:.8rem; opacity:.85; }
.ts-msg-badge { margin-left:auto; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:5px 14px; font-size:.78rem; font-weight:800; white-space:nowrap; }
.ts-tabs { display:flex; gap:0; }
.ts-tab { padding:9px 22px; font-size:.82rem; font-weight:800; cursor:pointer; border:none; background:rgba(255,255,255,.12); color:rgba(255,255,255,.75); border-radius:10px 10px 0 0; margin-right:4px; transition:all .15s; }
.ts-tab.active { background:#fff; color:var(--sltb); }
.ts-tab .tab-badge { display:inline-flex; align-items:center; justify-content:center; background:#dc2626; color:#fff; border-radius:999px; height:16px; min-width:16px; padding:0 4px; font-size:.65rem; font-weight:900; margin-left:5px; }
.ts-panel { display:none; flex:1; min-height:0; background:#fff; border:1px solid #e5e7eb; border-top:none; border-radius:0 0 14px 14px; overflow:hidden; }
.ts-panel.active { display:flex; flex-direction:column; }
.ts-alerts-inner { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:12px; }
.ts-alert-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; padding:12px 16px; border-bottom:1px solid #f0e8de; background:#fffaf5; flex-shrink:0; }
.ts-filter-group { display:flex; gap:6px; flex-wrap:wrap; }
.ts-filter-a { text-decoration:none; color:var(--sltb); border:1px solid #e8d39a; border-radius:999px; padding:5px 12px; font-size:.75rem; font-weight:700; background:#fffdf6; }
.ts-filter-a.active { background:var(--sltb); color:#fff; border-color:var(--sltb); }
.ts-mark-all { border:none; border-radius:8px; background:var(--sltb); color:#fff; padding:7px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
.ts-flash { border-radius:10px; padding:10px 14px; font-size:.84rem; font-weight:700; }
.ts-flash.ok  { background:#dcfce7; color:#14532d; border:1px solid #86efac; }
.ts-flash.err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.ts-empty { padding:40px 16px; border:1px dashed #d1d5db; border-radius:12px; text-align:center; color:#6b7280; background:#fff; }
.ts-card { background:#fff; border:1px solid #f2e6d2; border-left:4px solid #d1d5db; border-radius:12px; padding:14px 16px; box-shadow:0 2px 8px rgba(0,0,0,.04); }
.ts-card.unread { border-left-color:var(--sltb); background:#fffdf9; }
.ts-card.priority-urgent { border-left-color:#d97706; }
.ts-card.priority-critical { border-left-color:#dc2626; }
.ts-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:8px; }
.ts-sender { font-size:.95rem; font-weight:800; color:#111; margin:0; }
.ts-sender-sub { font-size:.72rem; color:#6b7280; margin-top:2px; }
.ts-badges { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
.tbadge { display:inline-flex; align-items:center; gap:3px; border-radius:999px; padding:3px 8px; font-size:.67rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
.tbadge-msg { background:#e0f2fe; color:#075985; }
.tbadge-alert { background:#ffedd5; color:#9a3412; }
.tbadge-unread { background:#fde8e8; color:#7f1d1d; }
.tbadge-urgent { background:#fef9c3; color:#854d0e; }
.tbadge-critical { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; animation:blink 2s infinite; }
@keyframes blink {0%,100%{opacity:1}50%{opacity:.7}}
.ts-body { font-size:.88rem; color:#1f2937; line-height:1.65; margin:0 0 10px; }
.ts-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.ts-time { font-size:.7rem; color:#9ca3af; }
.ts-btn-read { border:1px solid var(--sltb); background:#fff; color:var(--sltb); border-radius:7px; padding:5px 10px; font-size:.72rem; font-weight:700; cursor:pointer; }
.ts-read-state { font-size:.7rem; color:#4b5563; background:#f3f4f6; border-radius:999px; padding:3px 9px; font-weight:700; }
.ts-chat-inner { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:10px; scroll-behavior:smooth; }
.ts-chat-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; color:#9ca3af; }
.ts-chat-compose { padding:12px 16px; border-top:1px solid #f0e8de; background:#fffaf5; flex-shrink:0; }
.ts-chat-compose-row { display:flex; gap:8px; align-items:flex-end; }
.ts-chat-textarea { flex:1; resize:none; border:1.5px solid #e5e7eb; border-radius:12px; padding:10px 14px; font-size:.88rem; line-height:1.5; font-family:inherit; outline:none; background:#fff; min-height:44px; max-height:110px; transition:border-color .15s; }
.ts-chat-textarea:focus { border-color:var(--sltb); }
.ts-chat-send { background:linear-gradient(135deg,var(--sltb),#a8274e); color:#fff; border:none; border-radius:12px; padding:11px 18px; font-size:.88rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:6px; transition:opacity .15s, transform .1s; white-space:nowrap; }
.ts-chat-send:hover { opacity:.9; }
.ts-chat-send:active { transform:scale(.97); }
.ts-chat-send:disabled { opacity:.4; cursor:not-allowed; }
.ts-chat-hint { font-size:.72rem; color:#9ca3af; margin-top:6px; }
.ts-bubble-row { display:flex; gap:9px; align-items:flex-end; max-width:82%; }
.ts-bubble-row.mine   { align-self:flex-end; flex-direction:row-reverse; }
.ts-bubble-row.theirs { align-self:flex-start; }
.ts-avatar { width:30px; height:30px; border-radius:50%; flex-shrink:0; display:grid; place-items:center; font-size:11px; font-weight:800; color:#fff; }
.ts-bubble-row.mine   .ts-avatar { background:linear-gradient(135deg,var(--sltb),#a8274e); }
.ts-bubble-row.theirs .ts-avatar { background:linear-gradient(135deg,#1d4ed8,#1e3a8a); }
.ts-bubble { padding:9px 14px; border-radius:14px; font-size:.88rem; line-height:1.55; word-break:break-word; }
.ts-bubble-row.mine   .ts-bubble { background:linear-gradient(135deg,var(--sltb),#a8274e); color:#fff; border-radius:14px 4px 14px 14px; }
.ts-bubble-row.theirs .ts-bubble { background:#f3f4f6; color:#111827; border-radius:4px 14px 14px 14px; }
.ts-bubble-meta { font-size:.68rem; opacity:.6; margin-top:4px; }
.ts-bubble-row.mine   .ts-bubble-meta { text-align:right; color:#fff; }
.ts-bubble-row.theirs .ts-bubble-meta { text-align:left; color:#6b7280; }
.ts-chat-date-sep { text-align:center; font-size:.7rem; color:#9ca3af; font-weight:700; letter-spacing:.05em; text-transform:uppercase; margin:6px 0; }
.ts-no-do-warning { background:#fef9c3; border:1px solid #fde047; border-radius:10px; padding:14px 16px; font-size:.84rem; color:#78350f; font-weight:600; margin:12px; }
.ts-chat-layout { display:grid; grid-template-columns:280px minmax(0,1fr); flex:1; min-height:0; }
.ts-chat-list { border-right:1px solid #f0e8de; background:#fffaf5; overflow-y:auto; }
.ts-chat-list-head { padding:14px 16px 10px; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#7b1c3e; }
.ts-chat-link { display:block; padding:12px 14px; border-top:1px solid #f6efe7; text-decoration:none; color:inherit; background:transparent; }
.ts-chat-link:hover { background:#fff3ea; }
.ts-chat-link.active { background:#fff; box-shadow:inset 3px 0 0 var(--sltb); }
.ts-chat-link-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.ts-chat-link-name { font-size:.88rem; font-weight:800; color:#111827; }
.ts-chat-link-badge { min-width:18px; height:18px; padding:0 6px; border-radius:999px; background:#dc2626; color:#fff; font-size:.68rem; font-weight:900; display:inline-flex; align-items:center; justify-content:center; }
.ts-chat-link-meta { margin-top:3px; font-size:.68rem; color:#9a3412; font-weight:700; }
.ts-chat-link-preview { margin-top:6px; font-size:.76rem; color:#4b5563; line-height:1.45; }
.ts-chat-link-time { margin-top:6px; font-size:.68rem; color:#9ca3af; }
.ts-chat-main { display:flex; flex-direction:column; min-width:0; min-height:0; }
.ts-chat-main-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 16px; border-bottom:1px solid #f0e8de; background:#fff; }
.ts-chat-main-head strong { font-size:.92rem; color:#111827; }
.ts-chat-main-head span { font-size:.74rem; color:#6b7280; }
@media (max-width: 900px) {
  .ts-chat-layout { grid-template-columns:1fr; }
  .ts-chat-list { max-height:190px; border-right:none; border-bottom:1px solid #f0e8de; }
}
/* bubble context menu */
.ts-bub-ctx { position:fixed;z-index:2000;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:150px;overflow:hidden;display:none; }
.ts-bub-ctx button { display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border:none;background:none;font-size:13px;color:#374151;cursor:pointer;text-align:left; }
.ts-bub-ctx button:hover { background:#f3f4f6; }
.ts-bub-ctx button.danger { color:#dc2626; }
.ts-bub-ctx button.danger:hover { background:#fef2f2; }
.ts-bub-ctx hr { margin:2px 0;border:none;border-top:1px solid #f3f4f6; }
/* inline edit */
.ts-bub-edit-wrap { display:none;flex-direction:column;gap:6px;padding:4px 0; }
.ts-bub-edit-wrap textarea { width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:.88rem;resize:vertical;min-height:48px;font-family:inherit; }
.ts-bub-edit-actions { display:flex;gap:6px; }
.ts-bub-edit-actions button { padding:4px 12px;border-radius:6px;border:none;font-size:12px;cursor:pointer;font-weight:600; }
.ts-bub-edit-save { background:var(--sltb);color:#fff; }
.ts-bub-edit-cancel { background:#f3f4f6;color:#374151; }
.ts-bub-edited-tag { font-size:.62rem;opacity:.65;margin-left:4px; }
</style>

<div class="ts-msg-page" id="tsMsgPage">
  <div class="ts-msg-header">
    <div class="ts-msg-title">
      <div>
        <h1>Messages</h1>
        <p><?= htmlspecialchars($S['depot_name'] ?? 'Depot') ?> &mdash; SLTB Operations</p>
      </div>
      <div class="ts-msg-badge">Unread: <?= $unreadCount ?></div>
    </div>
    <div class="ts-tabs">
      <button class="ts-tab active" onclick="tsSwitchTab('alerts',this)" id="tabAlerts">
        Alerts &amp; Notices
        <?php if ($unreadCount > 0): ?><span class="tab-badge"><?= min($unreadCount,99) ?></span><?php endif; ?>
      </button>
      <button class="ts-tab" onclick="tsSwitchTab('chat',this)" id="tabChat">
        Chat with Officers
        <span class="tab-badge" id="chatTabBadge" style="display:none">0</span>
      </button>
    </div>
  </div>

  <!-- ALERTS PANEL -->
  <div class="ts-panel active" id="panelAlerts">
    <div class="ts-alert-toolbar">
      <div class="ts-filter-group">
        <a class="ts-filter-a <?= $filter==='all'    ?'active':'' ?>" href="/TS/messages?filter=all">All</a>
        <a class="ts-filter-a <?= $filter==='unread' ?'active':'' ?>" href="/TS/messages?filter=unread">Unread</a>
        <a class="ts-filter-a <?= $filter==='alert'  ?'active':'' ?>" href="/TS/messages?filter=alert">Alerts</a>
        <?php if ($hasDepotOfficer): ?>
          <a class="ts-filter-a" href="<?= htmlspecialchars($activeChatHref) ?>">Chat</a>
        <?php endif; ?>
      </div>
      <?php if ($unreadCount > 0): ?>
        <form method="post" action="/TS/messages?action=read_all&filter=<?= urlencode($filter) ?>">
          <button type="submit" class="ts-mark-all">Mark all read</button>
        </form>
      <?php endif; ?>
    </div>
    <?php if (!empty($msg) && isset($flashMap[$msg])): ?>
      <div style="margin:10px 16px 0"><div class="ts-flash <?= $flashMap[$msg]['type'] ?>"><?= htmlspecialchars($flashMap[$msg]['text']) ?></div></div>
    <?php endif; ?>
    <div class="ts-alerts-inner" id="alertsList">
      <?php if (empty($recent)): ?>
        <div class="ts-empty"><div style="font-size:36px;margin-bottom:8px">No alerts for this filter.</div></div>
      <?php else: ?>
        <?php foreach ($recent as $row):
          $id       = (int)($row['id'] ?? 0);
          $type     = (string)($row['type'] ?? 'Message');
          $isUnread = ((int)($row['is_seen'] ?? 0) === 0);
          $priority = strtolower(trim((string)($row['priority'] ?? 'normal')));
          $srcName  = trim((string)($row['source_name'] ?? ''));
          $isAlert  = in_array($type, ['Delay','Alert','Breakdown','Timetable']);
          $cls      = 'ts-card'.($isUnread?' unread':'').($priority==='urgent'?' priority-urgent':'').($priority==='critical'?' priority-critical':'');
        ?>
        <article class="<?= $cls ?>" id="alert-<?= $id ?>">
          <div class="ts-card-head">
            <div>
              <p class="ts-sender"><?= htmlspecialchars($srcName ?: ($isAlert ? 'System Alert' : 'Depot Officer')) ?></p>
              <div class="ts-sender-sub"><?= htmlspecialchars(ts_time_ago((string)($row['created_at'] ?? ''))) ?></div>
            </div>
            <div class="ts-badges">
              <?php if ($isUnread): ?><span class="tbadge tbadge-unread">New</span><?php endif; ?>
              <?php if ($priority==='urgent'):   ?><span class="tbadge tbadge-urgent">Urgent</span><?php endif; ?>
              <?php if ($priority==='critical'): ?><span class="tbadge tbadge-critical">Critical</span><?php endif; ?>
              <span class="tbadge <?= $isAlert?'tbadge-alert':'tbadge-msg' ?>"><?= htmlspecialchars($type) ?></span>
            </div>
          </div>
          <p class="ts-body"><?= nl2br(htmlspecialchars((string)($row['message'] ?? ''))) ?></p>
          <div class="ts-foot">
            <span class="ts-time"><?= htmlspecialchars(date('d M Y H:i', strtotime((string)($row['created_at'] ?? 'now')))) ?></span>
            <?php if ($isUnread && $id > 0): ?>
              <form method="post" action="/TS/messages?action=read&id=<?= $id ?>&filter=<?= urlencode($filter) ?>" style="display:inline">
                <button type="submit" class="ts-btn-read">Mark read</button>
              </form>
            <?php else: ?>
              <span class="ts-read-state">Read</span>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div><!-- /panelAlerts -->

  <!-- CHAT PANEL -->
  <div class="ts-panel" id="panelChat">
    <?php if (!$hasDepotOfficer): ?>
      <div class="ts-no-do-warning">No route-linked depot officer is currently available. Direct chat will appear once a relevant depot officer is found for your visible routes.</div>
    <?php endif; ?>
    <?php if ($hasDepotOfficer): ?>
    <div class="ts-chat-layout">
      <aside class="ts-chat-list">
        <div class="ts-chat-list-head">Depot Officers</div>
        <?php foreach ($chatPartners as $partner):
          $partnerId = (int)($partner['user_id'] ?? 0);
          $isActivePartner = $partnerId === $activeChatUserId;
          $preview = trim((string)($partner['last_message'] ?? ''));
          $depotName = trim((string)($partner['depot_name'] ?? ''));
          $depotCode = trim((string)($partner['depot_code'] ?? ''));
          $partnerMeta = $depotName !== ''
              ? $depotName . ($depotCode !== '' ? ' (' . $depotCode . ')' : '')
              : $depotCode;
          $chatHref = '/TS/messages?filter=' . rawurlencode($filter) . '&chat_user_id=' . $partnerId . '#chat';
        ?>
        <a class="ts-chat-link<?= $isActivePartner ? ' active' : '' ?>" href="<?= htmlspecialchars($chatHref) ?>">
          <div class="ts-chat-link-top">
            <span class="ts-chat-link-name"><?= htmlspecialchars((string)($partner['officer_name'] ?? ('Depot Officer #' . $partnerId))) ?></span>
            <?php if ((int)($partner['unread_count'] ?? 0) > 0): ?>
              <span class="ts-chat-link-badge"><?= min(99, (int)$partner['unread_count']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($partnerMeta !== ''): ?><div class="ts-chat-link-meta"><?= htmlspecialchars($partnerMeta) ?></div><?php endif; ?>
          <div class="ts-chat-link-preview"><?= htmlspecialchars($preview !== '' ? $preview : 'No messages yet for this officer.') ?></div>
          <?php if (!empty($partner['last_time'])): ?><div class="ts-chat-link-time"><?= htmlspecialchars(ts_time_ago((string)$partner['last_time'])) ?></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </aside>

      <section class="ts-chat-main">
        <div class="ts-chat-main-head">
          <strong><?= htmlspecialchars($activePartnerName) ?></strong>
          <span><?= htmlspecialchars($activePartnerDepot !== '' ? $activePartnerDepot : 'Direct chat with the selected depot officer') ?></span>
        </div>

        <div class="ts-chat-inner" id="chatInner">
          <?php if (empty($chatThread)): ?>
            <div class="ts-chat-empty">
              <div style="font-size:48px">No messages yet</div>
              <div style="font-size:.82rem">Start a conversation with <?= htmlspecialchars($activePartnerName) ?> below.</div>
            </div>
          <?php else: ?>
            <?php $prevDate = null; foreach ($chatThread as $m):
              $isMe    = (int)$m['from_user_id'] === $myUserId;
              $side    = $isMe ? 'mine' : 'theirs';
              $fullN   = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
              $init    = strtoupper(substr($fullN ?: ($isMe ? 'Y' : 'D'), 0, 1));
              $time    = $m['created_at'] ?? '';
              $dateStr = $time ? date('Y-m-d', strtotime($time)) : '';
              $timeDisp= $time ? date('H:i', strtotime($time)) : '';
              if ($dateStr && $dateStr !== $prevDate): $prevDate = $dateStr; ?>
              <div class="ts-chat-date-sep"><?= htmlspecialchars(date('d F Y', strtotime($time))) ?></div>
            <?php endif; ?>
            <div class="ts-bubble-row <?= $side ?>" data-dm-id="<?= (int)($m['id'] ?? 0) ?>"<?= $isMe ? ' style="cursor:pointer"' : '' ?>>
              <div class="ts-avatar"><?= htmlspecialchars($init) ?></div>
              <div>
                <div class="ts-bubble"><?= nl2br(htmlspecialchars((string)($m['message'] ?? ''))) ?></div>
                <?php if ($isMe): ?>
                <div class="ts-bub-edit-wrap">
                  <textarea class="ts-bub-edit-ta"></textarea>
                  <div class="ts-bub-edit-actions">
                    <button type="button" class="ts-bub-edit-save" onclick="tsBubSaveEdit(this)">Save</button>
                    <button type="button" class="ts-bub-edit-cancel" onclick="tsBubCancelEdit(this)">Cancel</button>
                  </div>
                </div>
                <?php endif; ?>
                <div class="ts-bubble-meta">
                  <?= $isMe ? 'You' : htmlspecialchars($fullN ?: 'Depot Officer') ?>
                  <?= (!$isMe && ($m['role'] ?? '')) ? ' &middot; ' . htmlspecialchars($m['role']) : '' ?>
                  &middot; <?= htmlspecialchars($timeDisp) ?>
                  <?= !empty($m['edited_at']) ? '<span class="ts-bub-edited-tag">(edited)</span>' : '' ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="ts-chat-compose">
          <div class="ts-chat-compose-row">
            <textarea class="ts-chat-textarea" id="chatInput" rows="1"
              placeholder="Message <?= htmlspecialchars($activePartnerName) ?>..."
              onkeydown="tsChatKeySubmit(event)"></textarea>
            <button class="ts-chat-send" id="chatSendBtn" onclick="tsSendChat()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Send
            </button>
          </div>
          <div class="ts-chat-hint">Enter to send &middot; Shift+Enter for new line</div>
        </div>
      </section>
    </div>
    <?php endif; ?>
  </div><!-- /panelChat -->
</div>

<!-- bubble context menu (TS) -->
<div id="tsBubCtx" class="ts-bub-ctx" role="menu">
    <button onclick="tsBubAction('copy')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        Copy
    </button>
    <hr>
    <button onclick="tsBubAction('edit')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
    </button>
    <hr>
    <button class="danger" onclick="tsBubAction('delete')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        Delete
    </button>
</div>

<script>
(function(){
'use strict';

var myUserId = <?php echo (int)$myUserId; ?>;
var lastDmId = 0;
var chatUnread = 0;
var activeChatUserId = <?php echo (int)$activeChatUserId; ?>;

// Seed lastDmId from server-rendered bubbles and attach click handlers to mine bubbles
document.querySelectorAll('.ts-bubble-row[data-dm-id]').forEach(function(el){
    var n = parseInt(el.dataset.dmId, 10);
    if (!isNaN(n) && n > lastDmId) lastDmId = n;
    if (el.classList.contains('mine')) {
        var bub = el.querySelector('.ts-bubble');
        if (bub) bub.addEventListener('click', function(e) {
            e.stopPropagation();
            showTsBubCtx(el, bub);
        });
    }
});

// Tab switching
window.tsSwitchTab = function(name, btn) {
    document.querySelectorAll('.ts-tab').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.ts-panel').forEach(function(p){ p.classList.remove('active'); });
    btn.classList.add('active');
    var panelId = 'panel' + name.charAt(0).toUpperCase() + name.slice(1);
    var panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
    if (name === 'chat') { scrollChat(); resetChatBadge(); }
};

if (window.location.hash === '#chat') {
  var chatTab = document.getElementById('tabChat');
  if (chatTab) {
    window.tsSwitchTab('chat', chatTab);
  }
}

function scrollChat() {
    var el = document.getElementById('chatInner');
    if (el) el.scrollTop = el.scrollHeight;
}
scrollChat();

function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}

// Auto-grow textarea
var chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('input', function(){
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 110) + 'px';
    });
}

// Send chat
window.tsChatKeySubmit = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); tsSendChat(); }
};

window.tsSendChat = function() {
    var input = document.getElementById('chatInput');
    var btn   = document.getElementById('chatSendBtn');
    if (!input || !btn) return;
    var text  = input.value.trim();
  if (!text || !activeChatUserId) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('message', text);
  fd.append('chat_user_id', String(activeChatUserId));
    fetch('/TS/messages?action=chat_send', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.ok) {
                var now = new Date();
                var nowStr = now.getFullYear() + '-' +
                    String(now.getMonth()+1).padStart(2,'0') + '-' +
                    String(now.getDate()).padStart(2,'0') + ' ' +
                    String(now.getHours()).padStart(2,'0') + ':' +
                    String(now.getMinutes()).padStart(2,'0') + ':00';
                appendBubble({ id: d.id || null, from_user_id: myUserId, message: text, created_at: nowStr }, true);
                input.value = '';
                input.style.height = 'auto';
                removeEmpty();
                scrollChat();
            } else {
                alert('Failed to send. Please try again.');
            }
        })
        .catch(function(){ alert('Network error.'); })
        .finally(function(){ btn.disabled = false; });
};

function appendBubble(m, isMe) {
    var mid = parseInt(m.id || 0, 10);
    if (mid > 0 && document.querySelector('[data-dm-id="'+mid+'"]')) return;
    var inner = document.getElementById('chatInner');
    if (!inner) return;
    var side  = isMe ? 'mine' : 'theirs';
    var fn    = (m.first_name || '');
    var ln    = (m.last_name  || '');
    var fullN = (fn + ' ' + ln).trim();
    var init  = isMe ? '<?php echo htmlspecialchars(strtoupper(substr((string)($_SESSION['user']['first_name'] ?? 'Y'), 0, 1))); ?>'
                     : (fullN ? fullN.charAt(0).toUpperCase() : 'D');
    var nameDisp = isMe ? 'You' : esc(fullN || 'Depot');
    var role  = (!isMe && m.role) ? ' &middot; ' + esc(m.role) : '';
    var timeDisp = m.created_at ? m.created_at.substring(11,16) : '';
    var editedTag = m.edited_at ? '<span class="ts-bub-edited-tag">(edited)</span>' : '';
    var editWrap = isMe
        ? '<div class="ts-bub-edit-wrap">' +
              '<textarea class="ts-bub-edit-ta"></textarea>' +
              '<div class="ts-bub-edit-actions">' +
              '<button type="button" class="ts-bub-edit-save" onclick="tsBubSaveEdit(this)">Save</button>' +
              '<button type="button" class="ts-bub-edit-cancel" onclick="tsBubCancelEdit(this)">Cancel</button>' +
              '</div>' +
          '</div>'
        : '';
    var row   = document.createElement('div');
    row.className = 'ts-bubble-row ' + side;
    if (mid > 0) { row.dataset.dmId = mid; if (mid > lastDmId) lastDmId = mid; }
    row.innerHTML =
        '<div class="ts-avatar">' + esc(init) + '</div>' +
        '<div>' +
        '<div class="ts-bubble">' + esc(m.message || '').replace(/\n/g,'<br>') + '</div>' +
        editWrap +
        '<div class="ts-bubble-meta">' + nameDisp + role + ' &middot; ' + esc(timeDisp) + editedTag + '</div>' +
        '</div>';
    if (isMe) {
        row.querySelector('.ts-bubble').addEventListener('click', function(e) {
            e.stopPropagation();
            showTsBubCtx(row, this);
        });
    }
    inner.appendChild(row);
}

function removeEmpty() {
    var e = document.querySelector('#chatInner .ts-chat-empty');
    if (e) e.remove();
}

function incrementChatBadge() {
    chatUnread++;
    var b = document.getElementById('chatTabBadge');
    if (b) { b.textContent = chatUnread > 99 ? '99+' : String(chatUnread); b.style.display = ''; }
}
function resetChatBadge() {
    chatUnread = 0;
    var b = document.getElementById('chatTabBadge');
    if (b) b.style.display = 'none';
}

// Poll chat
function pollChat() {
  if (!activeChatUserId) return;
  fetch('/TS/messages?action=chat_poll&since_id=' + lastDmId + '&chat_user_id=' + activeChatUserId)
        .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
        .then(function(msgs) {
            if (!Array.isArray(msgs) || !msgs.length) return;
            var panel = document.getElementById('panelChat');
            var active = panel && panel.classList.contains('active');
            msgs.forEach(function(m) {
                var mid   = parseInt(m.id || 0, 10);
                var isMe  = m.from_user_id == myUserId;
                if (mid > lastDmId) lastDmId = mid;
                appendBubble(m, isMe);
                if (!isMe && !active) incrementChatBadge();
            });
            removeEmpty();
            if (active) { scrollChat(); resetChatBadge(); }
        })
        .catch(function(){});
}
setTimeout(pollChat, 3000);
setInterval(pollChat, 15000);

// Poll alerts
var lastAlertId = 0;
document.querySelectorAll('#alertsList .ts-card[id]').forEach(function(el){
    var n = parseInt(el.id.replace('alert-',''), 10);
    if (!isNaN(n) && n > lastAlertId) lastAlertId = n;
});
function pollAlerts() {
    fetch('/TS/messages?action=poll&since_id=' + lastAlertId)
        .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
        .then(function(data) {
            if (!Array.isArray(data)) return;
            data.forEach(function(n){
                var nid = parseInt(n.id || 0, 10);
                if (nid > 0 && document.getElementById('alert-' + nid)) return;
                var list = document.getElementById('alertsList');
                if (!list) return;
                var empty = list.querySelector('.ts-empty');
                if (empty) empty.remove();
                var isAlert = ['Delay','Alert','Breakdown','Timetable'].indexOf(n.type || '') >= 0;
                var art = document.createElement('article');
                art.id        = 'alert-' + nid;
                art.className = 'ts-card unread';
                art.innerHTML =
                    '<div class="ts-card-head"><div>' +
                    '<p class="ts-sender">' + esc(n.source_name || (isAlert ? 'System Alert' : 'Depot Officer')) + '</p>' +
                    '<div class="ts-sender-sub">Just now</div></div>' +
                    '<div class="ts-badges"><span class="tbadge tbadge-unread">New</span>' +
                    '<span class="tbadge ' + (isAlert ? 'tbadge-alert' : 'tbadge-msg') + '">' + esc(n.type || 'Message') + '</span>' +
                    '</div></div>' +
                    '<p class="ts-body">' + esc(n.message || '').replace(/\n/g,'<br>') + '</p>' +
                    '<div class="ts-foot"><span class="ts-time">Just now</span></div>';
                list.insertBefore(art, list.firstChild);
                if (nid > lastAlertId) lastAlertId = nid;
            });
        })
        .catch(function(){});
}
setTimeout(pollAlerts, 5000);
setInterval(pollAlerts, 20000);

// ── Bubble context menu (own messages only) ──────────────────────────────
var _tsBubCtxRow = null;

function showTsBubCtx(row, bubbleEl) {
    hideTsBubCtx();
    _tsBubCtxRow = row;
    var menu = document.getElementById('tsBubCtx');
    var rect = bubbleEl.getBoundingClientRect();
    menu.style.display = 'block';
    var top  = rect.top + window.scrollY - menu.offsetHeight - 6;
    if (top < 6) top = rect.bottom + window.scrollY + 6;
    var left = rect.right + window.scrollX - menu.offsetWidth;
    if (left < 6) left = 6;
    menu.style.top  = top  + 'px';
    menu.style.left = left + 'px';
}

function hideTsBubCtx() {
    document.getElementById('tsBubCtx').style.display = 'none';
    _tsBubCtxRow = null;
}

window.tsBubAction = function(action) {
    var row = _tsBubCtxRow;
    hideTsBubCtx();
    if (!row) return;
    var bubEl   = row.querySelector('.ts-bubble');
    var editWr  = row.querySelector('.ts-bub-edit-wrap');
    var dmId    = parseInt(row.dataset.dmId || '0', 10);

    if (action === 'copy') {
        var text = bubEl ? bubEl.textContent : '';
        navigator.clipboard.writeText(text).catch(function(){});

    } else if (action === 'edit') {
        var ta = row.querySelector('.ts-bub-edit-ta');
        ta.value = bubEl ? bubEl.textContent.replace(/<br\s*\/?>/gi, '\n') : '';
        bubEl.style.display = 'none';
        editWr.style.display = 'flex';
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);

    } else if (action === 'delete') {
        if (!confirm('Delete this message?')) return;
        var fd = new FormData();
        fd.append('id', String(dmId));
        fetch('/TS/messages?action=chat_delete', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(res){ if (res.ok) row.remove(); })
            .catch(function(){});
    }
};

window.tsBubSaveEdit = function(btn) {
    var row    = btn.closest('.ts-bubble-row');
    if (!row) return;
    var ta     = row.querySelector('.ts-bub-edit-ta');
    var newTxt = ta ? ta.value.trim() : '';
    if (!newTxt) { if (ta) ta.focus(); return; }
    var dmId   = parseInt(row.dataset.dmId || '0', 10);
    if (!dmId) {
        alert('Message not yet confirmed. Please wait a moment and try again.');
        return;
    }
    btn.disabled = true;
    var fd = new FormData();
    fd.append('id', String(dmId));
    fd.append('message', newTxt);
    fetch('/TS/messages?action=chat_edit', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (res.ok) {
                var bubEl  = row.querySelector('.ts-bubble');
                var editWr = row.querySelector('.ts-bub-edit-wrap');
                var meta   = row.querySelector('.ts-bubble-meta');
                bubEl.innerHTML = newTxt.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
                bubEl.style.display = '';
                editWr.style.display = 'none';
                var tag = meta ? meta.querySelector('.ts-bub-edited-tag') : null;
                if (meta && !tag) {
                    tag = document.createElement('span');
                    tag.className = 'ts-bub-edited-tag';
                    tag.textContent = '(edited)';
                    meta.appendChild(tag);
                }
            } else {
                alert('Could not save the edit. Please try again.');
            }
        })
        .catch(function(){ btn.disabled = false; alert('Network error. Please try again.'); });
};

window.tsBubCancelEdit = function(btn) {
    var row = btn.closest('.ts-bubble-row');
    if (!row) return;
    row.querySelector('.ts-bubble').style.display = '';
    row.querySelector('.ts-bub-edit-wrap').style.display = 'none';
};

document.addEventListener('click', function(e) {
    var menu = document.getElementById('tsBubCtx');
    if (menu && menu.style.display !== 'none' && !menu.contains(e.target)) {
        hideTsBubCtx();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') hideTsBubCtx();
});

})();
</script>
