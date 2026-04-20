<?php
$S          = $S ?? [];
$recent     = $recent ?? [];
$filter     = in_array(($filter ?? 'all'), ['all','unread','message','alert'], true) ? $filter : 'all';
$unreadCount = (int)($unread_count ?? 0);
$chatThread = $chat_thread ?? [];
$chatUnread = (int)($chat_unread ?? 0);
$myUserId = (int)($my_user_id ?? 0);
$hasDepotOfficer = !empty($has_depot_officer);
$chatDepots = $chat_depots ?? [];
$activeChatDepotId = (int)($active_chat_depot_id ?? 0);
$activeChatDepot = $active_chat_depot ?? null;
$activeDepotName = trim((string)($activeChatDepot['depot_name'] ?? 'Relevant Depot'));
$msg        = $msg ?? null;

$flashMap = [
    'read'     => ['type'=>'ok',  'text'=>'Message marked as read.'],
    'read_all' => ['type'=>'ok',  'text'=>'All messages marked as read.'],
    'error'    => ['type'=>'err', 'text'=>'Unable to update message status.'],
];

$catMeta = [
    'schedule_change'  => ['icon'=>'📅', 'label'=>'Schedule Change',  'color'=>'#0369a1','bg'=>'#e0f2fe'],
    'breakdown_alert'  => ['icon'=>'🔧', 'label'=>'Breakdown Alert',  'color'=>'#b91c1c','bg'=>'#fee2e2'],
    'driver_notice'    => ['icon'=>'🧑‍✈️', 'label'=>'Driver Notice',   'color'=>'#1d4ed8','bg'=>'#dbeafe'],
    'poya_schedule'    => ['icon'=>'🌕', 'label'=>'Poya Day Schedule','color'=>'#065f46','bg'=>'#d1fae5'],
    'passenger_complaint'=>['icon'=>'😠', 'label'=>'Passenger Complaint','color'=>'#9a3412','bg'=>'#ffedd5'],
    'general_update'   => ['icon'=>'📢', 'label'=>'General Update',   'color'=>'#374151','bg'=>'#f3f4f6'],
];

function tp_time_ago(?string $ts): string {
    if (!$ts) return '';
    $at   = strtotime($ts);
    if ($at === false) return $ts;
    $diff = time() - $at;
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return intdiv($diff, 60) . ' min ago';
    if ($diff < 86400) return intdiv($diff, 3600) . ' hr ago';
    return date('d M Y', $at);
}
?>
<style>
:root { --owner:#7B1C3E; --gold:#f3b944; }
.tmsg-page { color:#111827; display:flex; flex-direction:column; gap:16px; }
.tmsg-hero { background:linear-gradient(135deg,var(--owner) 0%,#a8274e 100%); border-bottom:4px solid var(--gold); border-radius:14px; color:#fff; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.tmsg-hero h1 { margin:0; font-size:1.3rem; font-weight:800; }
.tmsg-hero p  { margin:3px 0 0; font-size:.84rem; opacity:.9; }
.tmsg-badge { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:6px 16px; font-size:.8rem; font-weight:800; }
.tmsg-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.tmsg-filters { display:flex; gap:8px; flex-wrap:wrap; }
.tmsg-filter { text-decoration:none; color:var(--owner); border:1px solid #e8d39a; border-radius:999px; padding:6px 14px; font-size:.78rem; font-weight:700; background:#fffdf6; }
.tmsg-filter.active { background:var(--owner); color:#fff; border-color:var(--owner); }
.tmsg-mark-all { border:none; border-radius:8px; background:var(--owner); color:#fff; padding:8px 14px; font-size:.78rem; font-weight:700; cursor:pointer; }
.tmsg-flash { border-radius:10px; padding:10px 14px; font-size:.86rem; font-weight:700; }
.tmsg-flash.ok  { background:#dcfce7; color:#14532d; border:1px solid #86efac; }
.tmsg-flash.err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.tmsg-empty { padding:40px 16px; border:1px dashed #d1d5db; border-radius:12px; text-align:center; color:#6b7280; background:#fff; }
.tmsg-list { display:grid; gap:14px; }
.tmsg-card { background:#fff; border:1px solid #e2eaf5; border-left:4px solid #d1d5db; border-radius:12px; padding:16px 18px; box-shadow:0 2px 10px rgba(0,0,0,.05); transition:box-shadow .15s; }
.tmsg-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.09); }
.tmsg-card.unread { border-left-color:var(--owner); }
.tmsg-card.priority-urgent   { border-left-color:#d97706; }
.tmsg-card.priority-critical { border-left-color:#dc2626; }
.tmsg-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px; }
.tmsg-sender-name { font-size:1rem; font-weight:800; color:#111827; margin:0; }
.tmsg-sender-role { font-size:.75rem; color:#6b7280; margin-top:3px; }
.tmsg-badges { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.tbadge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:3px 9px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
.tbadge-msg   { background:#fde8ef; color:#7B1C3E; }
.tbadge-alert { background:#ffedd5; color:#9a3412; }
.tbadge-unread   { background:#fde8e8; color:#7f1d1d; }
.tbadge-urgent   { background:#fef9c3; color:#854d0e; }
.tbadge-critical { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; animation:blink 2s infinite; }
@keyframes blink {0%,100%{opacity:1}50%{opacity:.7}}
.tmsg-cat-chip { display:inline-flex; align-items:center; gap:5px; border-radius:8px; padding:5px 10px; font-size:.78rem; font-weight:700; margin-bottom:10px; }
.tmsg-body { font-size:.9rem; color:#1f2937; line-height:1.65; margin:0 0 12px; }
.tmsg-card-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.tmsg-time { font-size:.72rem; color:#9ca3af; }
.tmsg-btn-read { border:1px solid var(--owner); background:#fff; color:var(--owner); border-radius:7px; padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
.tmsg-btn-ack  { border:none; background:var(--owner); color:#fff; border-radius:7px; padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
.tmsg-btn-ack:disabled { opacity:.5; cursor:default; }
.tmsg-read-state { font-size:.72rem; color:#4b5563; background:#f3f4f6; border-radius:999px; padding:4px 10px; font-weight:700; }
.tchat-card { background:#fff; border:1px solid #e2eaf5; border-radius:12px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,.05); display:flex; flex-direction:column; gap:12px; }
.tchat-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.tchat-head h2 { margin:0; font-size:1.02rem; font-weight:800; color:#111827; }
.tchat-head p { margin:4px 0 0; font-size:.8rem; color:#6b7280; }
.tchat-warning { border:1px solid #facc15; background:#fef9c3; color:#92400e; border-radius:12px; padding:12px 14px; font-size:.88rem; }
.tchat-layout { display:grid; grid-template-columns:280px minmax(0,1fr); gap:14px; align-items:start; }
.tchat-list { border:1px solid #ece7ef; border-radius:12px; background:#fffaf5; overflow:hidden; }
.tchat-list-head { padding:14px 16px; font-size:.74rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:var(--owner); border-bottom:1px solid #f1e6eb; }
.tchat-list-body { display:flex; flex-direction:column; }
.tchat-link { display:block; padding:12px 14px; text-decoration:none; color:inherit; }
.tchat-link + .tchat-link { border-top:1px solid #f5ebf0; }
.tchat-link:hover { background:#fff3ea; }
.tchat-link.active { background:#fff; box-shadow:inset 3px 0 0 var(--owner); }
.tchat-link-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.tchat-link-name { font-size:.88rem; font-weight:800; color:#111827; }
.tchat-link-badge { min-width:18px; height:18px; padding:0 6px; border-radius:999px; background:#dc2626; color:#fff; font-size:.68rem; font-weight:900; display:inline-flex; align-items:center; justify-content:center; }
.tchat-link-meta { margin-top:3px; font-size:.68rem; color:#9a3412; font-weight:700; }
.tchat-link-preview { margin-top:6px; font-size:.76rem; color:#4b5563; line-height:1.45; }
.tchat-link-time { margin-top:6px; font-size:.68rem; color:#9ca3af; }
.tchat-main { display:flex; flex-direction:column; gap:12px; min-width:0; }
.tchat-main-head h2 { margin:0; font-size:1rem; font-weight:800; color:#111827; }
.tchat-main-head p { margin:4px 0 0; font-size:.78rem; color:#6b7280; }
.tchat-thread { background:#fcfbfd; border:1px solid #ece7ef; border-radius:12px; min-height:180px; max-height:420px; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:10px; }
.tchat-empty { padding:28px 16px; text-align:center; color:#6b7280; font-size:.9rem; }
.tchat-row { display:flex; gap:10px; align-items:flex-end; max-width:88%; }
.tchat-row.mine { align-self:flex-end; flex-direction:row-reverse; }
.tchat-avatar { width:32px; height:32px; border-radius:50%; background:#f3e8ee; color:var(--owner); display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:800; flex-shrink:0; }
.tchat-row.mine .tchat-avatar { background:#efe9f8; color:#5b21b6; }
.tchat-bubble { background:#f3f4f6; color:#111827; border-radius:14px 14px 14px 6px; padding:10px 12px; font-size:.9rem; line-height:1.5; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.tchat-row.mine .tchat-bubble { background:#7B1C3E; color:#fff; border-radius:14px 14px 6px 14px; }
.tchat-meta { margin-top:4px; font-size:.72rem; color:#6b7280; }
.tchat-compose { display:flex; gap:10px; align-items:flex-end; }
.tchat-textarea { flex:1; min-height:44px; max-height:110px; resize:none; border:1px solid #d1d5db; border-radius:12px; padding:11px 12px; font:inherit; }
.tchat-send { border:none; background:var(--owner); color:#fff; border-radius:10px; padding:11px 16px; font-size:.83rem; font-weight:800; cursor:pointer; }
.tchat-send:disabled { opacity:.55; cursor:default; }
.tchat-hint { font-size:.74rem; color:#6b7280; }
@media (max-width: 900px) {
    .tchat-layout { grid-template-columns:1fr; }
}
</style>

<div class="tmsg-page">

    <div class="tmsg-hero">
        <div>
            <h1>📨 Messages</h1>
            <p>Notices from your Bus Owner / Operator</p>
        </div>
        <div class="tmsg-badge">Unread: <?= $unreadCount ?></div>
    </div>

    <?php if (!empty($msg) && isset($flashMap[$msg])): ?>
        <div class="tmsg-flash <?= $flashMap[$msg]['type'] ?>"><?= htmlspecialchars($flashMap[$msg]['text']) ?></div>
    <?php endif; ?>

    <div class="tmsg-toolbar">
        <div class="tmsg-filters">
            <a class="tmsg-filter <?= $filter==='all'    ?'active':'' ?>" href="/TP/messages?filter=all">All</a>
            <a class="tmsg-filter <?= $filter==='unread' ?'active':'' ?>" href="/TP/messages?filter=unread">Unread</a>
            <a class="tmsg-filter <?= $filter==='message' ?'active':'' ?>" href="/TP/messages?filter=message">Messages</a>
            <a class="tmsg-filter <?= $filter==='alert'  ?'active':'' ?>" href="/TP/messages?filter=alert">Alerts</a>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="post" action="/TP/messages?action=read_all&filter=<?= urlencode($filter) ?>">
                <button type="submit" class="tmsg-mark-all">✓ Mark all read</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="tmsg-list">
        <?php if (empty($recent)): ?>
            <div class="tmsg-empty"><div style="font-size:36px;margin-bottom:8px">📭</div>No messages yet.</div>
        <?php else: ?>
            <?php foreach ($recent as $row):
                $id       = (int)($row['id'] ?? 0);
                $type     = (string)($row['type'] ?? 'Message');
                $isUnread = ((int)($row['is_seen'] ?? 0) === 0);
                $priority = strtolower(trim((string)($row['priority'] ?? 'normal')));
                $catKey   = trim((string)($row['category'] ?? ''));
                $catInfo  = $catMeta[$catKey] ?? null;
                $srcName  = trim((string)($row['source_name'] ?? ''));
                $srcRole  = trim((string)($row['source_role'] ?? ''));
                $isAlert  = in_array($type, ['Delay','Alert','Breakdown','Timetable']);
                $cardCls  = 'tmsg-card' . ($isUnread?' unread':'') . ($priority==='urgent'?' priority-urgent':'') . ($priority==='critical'?' priority-critical':'');
            ?>
            <article class="<?= $cardCls ?>" id="msg-<?= $id ?>">
                <div class="tmsg-card-head">
                    <div>
                        <p class="tmsg-sender-name"><?= htmlspecialchars($srcName ?: 'Bus Owner') ?></p>
                        <div class="tmsg-sender-role">
                            <?= htmlspecialchars($srcRole ?: 'Bus Owner') ?>
                            · <?= htmlspecialchars(tp_time_ago((string)($row['created_at'] ?? ''))) ?>
                        </div>
                    </div>
                    <div class="tmsg-badges">
                        <?php if ($isUnread): ?><span class="tbadge tbadge-unread">New</span><?php endif; ?>
                        <?php if ($priority==='urgent'):   ?><span class="tbadge tbadge-urgent">🟠 Urgent</span><?php endif; ?>
                        <?php if ($priority==='critical'): ?><span class="tbadge tbadge-critical">🔴 Critical</span><?php endif; ?>
                        <span class="tbadge <?= $isAlert?'tbadge-alert':'tbadge-msg' ?>"><?= htmlspecialchars($type) ?></span>
                    </div>
                </div>

                <?php if ($catInfo): ?>
                    <div class="tmsg-cat-chip" style="background:<?= htmlspecialchars($catInfo['bg']) ?>;color:<?= htmlspecialchars($catInfo['color']) ?>">
                        <?= $catInfo['icon'] ?> <?= htmlspecialchars($catInfo['label']) ?>
                    </div>
                <?php endif; ?>

                <p class="tmsg-body"><?= nl2br(htmlspecialchars((string)($row['message'] ?? ''))) ?></p>

                <div class="tmsg-card-foot">
                    <span class="tmsg-time"><?= htmlspecialchars(date('d M Y H:i', strtotime((string)($row['created_at'] ?? 'now')))) ?></span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <?php if ($isUnread && $id > 0): ?>
                            <form method="post" action="/TP/messages?action=read&id=<?= $id ?>&filter=<?= urlencode($filter) ?>" style="display:inline">
                                <button type="submit" class="tmsg-btn-read">Mark read</button>
                            </form>
                        <?php else: ?>
                            <span class="tmsg-read-state">✓ Read</span>
                        <?php endif; ?>
                        <?php if (in_array($priority,['urgent','critical']) && $id > 0): ?>
                            <button class="tmsg-btn-ack" id="ack-<?= $id ?>" onclick="ackMsg(<?= $id ?>,this)">✔ Acknowledge</button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <section class="tchat-card">
        <div class="tchat-head">
            <div>
                <h2>Direct Chat with Route Depots</h2>
                <p>Choose the relevant depot and message its depot officer directly.</p>
            </div>
            <div class="tmsg-badge">Chat Unread: <?= $chatUnread ?></div>
        </div>

        <?php if (!$hasDepotOfficer): ?>
            <div class="tchat-warning">No route-linked depot officer is currently available for the routes visible to you. Direct chat will appear once a relevant depot officer is found.</div>
        <?php endif; ?>

        <?php if ($hasDepotOfficer): ?>
        <div class="tchat-layout">
            <aside class="tchat-list">
                <div class="tchat-list-head">Relevant Depots</div>
                <div class="tchat-list-body">
                    <?php foreach ($chatDepots as $depot):
                        $depotId = (int)($depot['depot_id'] ?? 0);
                        $isActiveDepot = $depotId === $activeChatDepotId;
                        $preview = trim((string)($depot['last_message'] ?? ''));
                        $depotCode = trim((string)($depot['depot_code'] ?? ''));
                        $chatHref = '/TP/messages?filter=' . rawurlencode($filter) . '&chat_depot_id=' . $depotId;
                    ?>
                    <a class="tchat-link<?= $isActiveDepot ? ' active' : '' ?>" href="<?= htmlspecialchars($chatHref) ?>">
                        <div class="tchat-link-top">
                            <span class="tchat-link-name"><?= htmlspecialchars((string)($depot['depot_name'] ?? ('Depot #' . $depotId))) ?></span>
                            <?php if ((int)($depot['unread_count'] ?? 0) > 0): ?>
                                <span class="tchat-link-badge"><?= min(99, (int)$depot['unread_count']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($depotCode !== ''): ?><div class="tchat-link-meta"><?= htmlspecialchars($depotCode) ?></div><?php endif; ?>
                        <div class="tchat-link-preview"><?= htmlspecialchars($preview !== '' ? $preview : 'No messages yet for this depot.') ?></div>
                        <?php if (!empty($depot['last_time'])): ?><div class="tchat-link-time"><?= htmlspecialchars(tp_time_ago((string)$depot['last_time'])) ?></div><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="tchat-main">
                <div class="tchat-main-head">
                    <div>
                        <h2><?= htmlspecialchars($activeDepotName) ?></h2>
                        <p>Direct chat with the selected depot officer.</p>
                    </div>
                </div>

                <div class="tchat-thread" id="tpChatThread">
                    <?php if (empty($chatThread)): ?>
                        <div class="tchat-empty">No direct messages yet. Start a conversation with <?= htmlspecialchars($activeDepotName) ?> below.</div>
                    <?php else: ?>
                        <?php foreach ($chatThread as $m):
                            $isMe = (int)($m['from_user_id'] ?? 0) === $myUserId;
                            $fullN = trim((string)(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')));
                            $init = strtoupper(substr($fullN ?: ($isMe ? 'Y' : 'D'), 0, 1));
                            $timeDisp = !empty($m['created_at']) ? date('d M H:i', strtotime((string)$m['created_at'])) : '';
                        ?>
                        <div class="tchat-row <?= $isMe ? 'mine' : 'theirs' ?>" data-dm-id="<?= (int)($m['id'] ?? 0) ?>">
                            <div class="tchat-avatar"><?= htmlspecialchars($init) ?></div>
                            <div>
                                <div class="tchat-bubble"><?= nl2br(htmlspecialchars((string)($m['message'] ?? ''))) ?></div>
                                <div class="tchat-meta">
                                    <?= $isMe ? 'You' : htmlspecialchars($fullN ?: 'Depot Officer') ?>
                                    <?= (!$isMe && !empty($m['role'])) ? ' · ' . htmlspecialchars((string)$m['role']) : '' ?>
                                    <?= $timeDisp !== '' ? ' · ' . htmlspecialchars($timeDisp) : '' ?>
                                    <?= !empty($m['edited_at']) ? ' · (edited)' : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tchat-compose">
                    <textarea id="tpChatInput" class="tchat-textarea" rows="1" placeholder="Message <?= htmlspecialchars($activeDepotName) ?>..." onkeydown="tpChatKeySubmit(event)"></textarea>
                    <button id="tpChatSendBtn" class="tchat-send" type="button" onclick="tpSendChat()">Send</button>
                </div>
                <div class="tchat-hint">Enter to send · Shift+Enter for a new line.</div>
            </div>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
function ackMsg(id, btn) {
    btn.disabled = true; btn.textContent = 'Acknowledging…';
    fetch('/TP/messages?action=ack&id='+id, {method:'POST'})
        .then(r=>r.json())
        .then(d=>{
            if(d.ok){ btn.textContent='✔ Acknowledged'; btn.style.background='#059669';
                const c=document.getElementById('msg-'+id); if(c) c.classList.remove('unread');
            } else { btn.disabled=false; btn.textContent='✔ Acknowledge'; }
        })
        .catch(()=>{ btn.disabled=false; btn.textContent='✔ Acknowledge'; });
}

// ── Auto-poll for new messages (depot officer replies) ─────────────────────
(function(){
    var maxId = 0;
    document.querySelectorAll('.tmsg-card[id]').forEach(function(el){
        var n = parseInt(el.id.replace('msg-',''), 10);
        if (!isNaN(n) && n > maxId) maxId = n;
    });
    var lastId = maxId;
    var listEl = document.querySelector('.tmsg-list');
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function addMsg(m) {
        if (!m.id || document.getElementById('msg-'+m.id)) return;
        var empty = listEl.querySelector('.tmsg-empty');
        if (empty) listEl.innerHTML = '';
        var isUnread = m.is_seen == 0;
        var priority = (m.priority||'normal').toLowerCase();
        var isAlert = ['Delay','Alert','Breakdown','Timetable'].indexOf(m.type||'') >= 0;
        var cls = 'tmsg-card'+(isUnread?' unread':'')+(priority==='urgent'?' priority-urgent':'')+(priority==='critical'?' priority-critical':'');
        var srcName = m.source_name || (isAlert ? 'System Alert' : 'Depot Officer');
        var srcRole = m.source_role || 'Depot Officer';
        var timeStr = m.created_at ? new Date(m.created_at.replace(' ','T')).toLocaleString() : '';
        var typeBadgeCls = isAlert ? 'tbadge-alert' : 'tbadge-msg';
        var html = '<article class="'+cls+'" id="msg-'+m.id+'">'
            +'<div class="tmsg-card-head"><div>'
            +'<p class="tmsg-sender-name">'+escHtml(srcName)+'</p>'
            +'<div class="tmsg-sender-role">'+escHtml(srcRole)+'</div></div>'
            +'<div class="tmsg-badges">'
            +(isUnread?'<span class="tbadge tbadge-unread">New</span>':'')
            +(priority==='urgent'?'<span class="tbadge tbadge-urgent">🟠 Urgent</span>':'')
            +(priority==='critical'?'<span class="tbadge tbadge-critical">🔴 Critical</span>':'')
            +'<span class="tbadge '+typeBadgeCls+'">'+escHtml(m.type||'Message')+'</span>'
            +'</div></div>'
            +'<p class="tmsg-body">'+escHtml(m.message||'').replace(/\n/g,'<br>')+'</p>'
            +'<div class="tmsg-card-foot"><span class="tmsg-time">'+escHtml(timeStr)+'</span></div>'
            +'</article>';
        listEl.insertAdjacentHTML('afterbegin', html);
    }
    function poll() {
        fetch('/TP/messages?action=poll&since_id='+lastId)
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!Array.isArray(data)) return;
                data.forEach(function(m){
                    var mid = parseInt(m.id||0, 10);
                    if (mid > lastId) lastId = mid;
                    addMsg(m);
                });
            })
            .catch(function(){});
    }
    setTimeout(poll, 3000);
    setInterval(poll, 20000);
})();

var tpMyUserId = <?= (int)$myUserId ?>;
var tpLastDmId = 0;
var tpActiveChatDepotId = <?= (int)$activeChatDepotId ?>;

document.querySelectorAll('#tpChatThread .tchat-row[data-dm-id]').forEach(function(el){
    var n = parseInt(el.dataset.dmId || '0', 10);
    if (!isNaN(n) && n > tpLastDmId) tpLastDmId = n;
});

function tpEsc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function tpScrollChat() {
    var el = document.getElementById('tpChatThread');
    if (el) el.scrollTop = el.scrollHeight;
}

function tpRemoveEmpty() {
    var el = document.querySelector('#tpChatThread .tchat-empty');
    if (el) el.remove();
}

function tpAppendBubble(m, isMe) {
    var mid = parseInt(m.id || 0, 10);
    if (mid > 0 && document.querySelector('#tpChatThread .tchat-row[data-dm-id="' + mid + '"]')) return;

    var thread = document.getElementById('tpChatThread');
    if (!thread) return;

    var fullN = ((m.first_name || '') + ' ' + (m.last_name || '')).trim();
    var init = isMe ? '<?= htmlspecialchars(strtoupper(substr((string)($_SESSION['user']['first_name'] ?? 'Y'), 0, 1))) ?>' : ((fullN || 'D').charAt(0).toUpperCase());
    var name = isMe ? 'You' : tpEsc(fullN || 'Depot Officer');
    var role = (!isMe && m.role) ? ' · ' + tpEsc(m.role) : '';
    var timeDisp = m.created_at ? tpEsc(m.created_at.substring(11, 16)) : '';
    var edited = m.edited_at ? ' · (edited)' : '';

    var row = document.createElement('div');
    row.className = 'tchat-row ' + (isMe ? 'mine' : 'theirs');
    if (mid > 0) {
        row.dataset.dmId = mid;
        if (mid > tpLastDmId) tpLastDmId = mid;
    }
    row.innerHTML =
        '<div class="tchat-avatar">' + tpEsc(init) + '</div>' +
        '<div>' +
            '<div class="tchat-bubble">' + tpEsc(m.message || '').replace(/\n/g, '<br>') + '</div>' +
            '<div class="tchat-meta">' + name + role + (timeDisp ? ' · ' + timeDisp : '') + edited + '</div>' +
        '</div>';
    thread.appendChild(row);
}

var tpChatInput = document.getElementById('tpChatInput');
if (tpChatInput) {
    tpChatInput.addEventListener('input', function(){
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 110) + 'px';
    });
}

window.tpChatKeySubmit = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        tpSendChat();
    }
};

window.tpSendChat = function() {
    var input = document.getElementById('tpChatInput');
    var btn = document.getElementById('tpChatSendBtn');
    if (!input || !btn) return;
    var text = input.value.trim();
    if (!text || !tpActiveChatDepotId) return;

    btn.disabled = true;
    var fd = new FormData();
    fd.append('message', text);
    fd.append('chat_depot_id', String(tpActiveChatDepotId));
    fetch('/TP/messages?action=chat_send', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (!d.ok) {
                alert('Failed to send. Please try again.');
                return;
            }
            var now = new Date();
            var nowStr = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':00';
            tpAppendBubble({ id: d.id || null, from_user_id: tpMyUserId, message: text, created_at: nowStr }, true);
            tpRemoveEmpty();
            input.value = '';
            input.style.height = 'auto';
            tpScrollChat();
        })
        .catch(function(){ alert('Network error.'); })
        .finally(function(){ btn.disabled = false; });
};

function tpPollChat() {
    if (!tpActiveChatDepotId) return;
    fetch('/TP/messages?action=chat_poll&since_id=' + tpLastDmId + '&chat_depot_id=' + tpActiveChatDepotId)
        .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
        .then(function(msgs){
            if (!Array.isArray(msgs) || !msgs.length) return;
            msgs.forEach(function(m){
                var isMe = m.from_user_id == tpMyUserId;
                tpAppendBubble(m, isMe);
            });
            tpRemoveEmpty();
            tpScrollChat();
        })
        .catch(function(){});
}

if (document.getElementById('tpChatThread')) {
    tpScrollChat();
    setTimeout(tpPollChat, 3000);
    setInterval(tpPollChat, 15000);
}
</script>