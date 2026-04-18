<?php
$S          = $S ?? [];
$recent     = $recent ?? [];
$filter     = in_array(($filter ?? 'all'), ['all','unread','message','alert'], true) ? $filter : 'all';
$unreadCount = (int)($unread_count ?? 0);
$msg        = $msg ?? null;

$flashMap = [
    'read'       => ['type'=>'ok',  'text'=>'Message marked as read.'],
    'read_all'   => ['type'=>'ok',  'text'=>'All messages marked as read.'],
    'sent'       => ['type'=>'ok',  'text'=>'Your message was sent to the Depot Officer.'],
    'error'      => ['type'=>'err', 'text'=>'Unable to update message status.'],
    'send_error' => ['type'=>'err', 'text'=>'Failed to send message. Please try again.'],
];

$catMeta = [
    'schedule_change'     => ['icon'=>'📅', 'label'=>'Schedule Change',     'color'=>'#0369a1','bg'=>'#e0f2fe'],
    'route_deviation'     => ['icon'=>'🔀', 'label'=>'Route Deviation',     'color'=>'#b45309','bg'=>'#fef3c7'],
    'breakdown_alert'     => ['icon'=>'🔧', 'label'=>'Breakdown Alert',     'color'=>'#b91c1c','bg'=>'#fee2e2'],
    'strike_notice'       => ['icon'=>'✊', 'label'=>'Strike / Union Notice','color'=>'#7c3aed','bg'=>'#ede9fe'],
    'poya_schedule'       => ['icon'=>'🌕', 'label'=>'Poya Day Schedule',   'color'=>'#065f46','bg'=>'#d1fae5'],
    'passenger_complaint' => ['icon'=>'😠', 'label'=>'Passenger Complaint', 'color'=>'#9a3412','bg'=>'#ffedd5'],
    'general_update'      => ['icon'=>'📢', 'label'=>'General Update',      'color'=>'#374151','bg'=>'#f3f4f6'],
];

function ts_time_ago(?string $ts): string {
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
:root { --sltb:#7B1C3E; --gold:#f3b944; }
.tmsg-page { color:#111827; display:flex; flex-direction:column; gap:16px; }
.tmsg-hero { background:linear-gradient(135deg,var(--sltb) 0%,#a8274e 100%); border-bottom:4px solid var(--gold); border-radius:14px; color:#fff; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.tmsg-hero h1 { margin:0; font-size:1.3rem; font-weight:800; }
.tmsg-hero p  { margin:3px 0 0; font-size:.84rem; opacity:.9; }
.tmsg-badge { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:6px 16px; font-size:.8rem; font-weight:800; }
.tmsg-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.tmsg-filters { display:flex; gap:8px; flex-wrap:wrap; }
.tmsg-filter { text-decoration:none; color:var(--sltb); border:1px solid #e8d39a; border-radius:999px; padding:6px 14px; font-size:.78rem; font-weight:700; background:#fffdf6; }
.tmsg-filter.active { background:var(--sltb); color:#fff; border-color:var(--sltb); }
.tmsg-mark-all { border:none; border-radius:8px; background:var(--sltb); color:#fff; padding:8px 14px; font-size:.78rem; font-weight:700; cursor:pointer; }
.tmsg-flash { border-radius:10px; padding:10px 14px; font-size:.86rem; font-weight:700; }
.tmsg-flash.ok  { background:#dcfce7; color:#14532d; border:1px solid #86efac; }
.tmsg-flash.err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.tmsg-empty { padding:40px 16px; border:1px dashed #d1d5db; border-radius:12px; text-align:center; color:#6b7280; background:#fff; }
.tmsg-list { display:grid; gap:14px; }
.tmsg-card { background:#fff; border:1px solid #f2e6d2; border-left:4px solid #d1d5db; border-radius:12px; padding:16px 18px; box-shadow:0 2px 10px rgba(0,0,0,.05); transition:box-shadow .15s; }
.tmsg-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.09); }
.tmsg-card.unread { border-left-color:var(--sltb); }
.tmsg-card.priority-urgent   { border-left-color:#d97706; }
.tmsg-card.priority-critical { border-left-color:#dc2626; }
.tmsg-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px; }
.tmsg-sender-name { font-size:1rem; font-weight:800; color:#111827; margin:0; }
.tmsg-sender-role { font-size:.75rem; color:#6b7280; margin-top:3px; }
.tmsg-badges { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.tbadge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:3px 9px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
.tbadge-msg   { background:#e0f2fe; color:#075985; }
.tbadge-alert { background:#ffedd5; color:#9a3412; }
.tbadge-unread   { background:#fde8e8; color:#7f1d1d; }
.tbadge-urgent   { background:#fef9c3; color:#854d0e; }
.tbadge-critical { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; animation:blink 2s infinite; }
@keyframes blink {0%,100%{opacity:1}50%{opacity:.7}}
.tmsg-cat-chip { display:inline-flex; align-items:center; gap:5px; border-radius:8px; padding:5px 10px; font-size:.78rem; font-weight:700; margin-bottom:10px; }
.tmsg-body { font-size:.9rem; color:#1f2937; line-height:1.65; margin:0 0 12px; }
.tmsg-card-foot { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.tmsg-time { font-size:.72rem; color:#9ca3af; }
.tmsg-btn-read { border:1px solid var(--sltb); background:#fff; color:var(--sltb); border-radius:7px; padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
.tmsg-btn-ack  { border:none; background:var(--sltb); color:#fff; border-radius:7px; padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
.tmsg-btn-ack:disabled { opacity:.5; cursor:default; }
.tmsg-read-state { font-size:.72rem; color:#4b5563; background:#f3f4f6; border-radius:999px; padding:4px 10px; font-weight:700; }

/* Compose card */
.tmsg-compose { background:#fff; border:1.5px solid #f2e6d2; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.05); overflow:hidden; }
.tmsg-compose-head { background:linear-gradient(90deg,var(--sltb),#a8274e); border-bottom:3px solid var(--gold); color:#fff; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; gap:10px; cursor:pointer; }
.tmsg-compose-head h3 { margin:0; font-size:.92rem; font-weight:800; display:flex; align-items:center; gap:8px; }
.tmsg-compose-head .compose-toggle { font-size:.75rem; opacity:.8; }
.tmsg-compose-body { padding:16px 18px; display:none; }
.tmsg-compose-body.open { display:block; }
.tmsg-compose textarea { width:100%; border:1.5px solid #e5e7eb; border-radius:9px; padding:10px 12px; font-size:.88rem; line-height:1.6; resize:vertical; min-height:90px; box-sizing:border-box; }
.tmsg-compose textarea:focus { outline:none; border-color:var(--sltb); }
.tmsg-priority-row { display:flex; align-items:center; gap:8px; margin-top:10px; flex-wrap:wrap; }
.tmsg-prio-label { font-size:.75rem; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
.tmsg-prio-btn { padding:5px 13px; border-radius:99px; border:1.5px solid #e5e7eb; background:#f9fafb; font-size:.75rem; font-weight:700; color:#6b7280; cursor:pointer; transition:all .15s; }
.tmsg-prio-btn.sel-normal   { border-color:#6b7280; background:#f3f4f6; color:#374151; }
.tmsg-prio-btn.sel-urgent   { border-color:#d97706; background:#fef3c7; color:#92400e; }
.tmsg-prio-btn.sel-critical { border-color:#dc2626; background:#fee2e2; color:#991b1b; }
.tmsg-send-btn { margin-top:12px; background:var(--sltb); color:#fff; border:none; border-radius:9px; padding:9px 22px; font-size:.88rem; font-weight:800; cursor:pointer; }
.tmsg-send-btn:hover { background:#9b2550; }
</style>

<div class="tmsg-page">

    <div class="tmsg-hero">
        <div>
            <h1>📨 Messages</h1>
            <p><?= htmlspecialchars($S['depot_name'] ?? 'Depot') ?> — operational notices from Depot Officer</p>
        </div>
        <div class="tmsg-badge">Unread: <?= $unreadCount ?></div>
    </div>

    <?php if (!empty($msg) && isset($flashMap[$msg])): ?>
        <div class="tmsg-flash <?= $flashMap[$msg]['type'] ?>"><?= htmlspecialchars($flashMap[$msg]['text']) ?></div>
    <?php endif; ?>

    <div class="tmsg-toolbar">
        <div class="tmsg-filters">
            <a class="tmsg-filter <?= $filter==='all'     ?'active':'' ?>" href="/TS/messages?filter=all">All</a>
            <a class="tmsg-filter <?= $filter==='unread'  ?'active':'' ?>" href="/TS/messages?filter=unread">Unread</a>
            <a class="tmsg-filter <?= $filter==='message' ?'active':'' ?>" href="/TS/messages?filter=message">Messages</a>
            <a class="tmsg-filter <?= $filter==='alert'   ?'active':'' ?>" href="/TS/messages?filter=alert">Alerts</a>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="post" action="/TS/messages?action=read_all&filter=<?= urlencode($filter) ?>">
                <button type="submit" class="tmsg-mark-all">✓ Mark all read</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- ── Compose: Send to Depot Officer ── -->
    <div class="tmsg-compose">
        <div class="tmsg-compose-head" onclick="toggleCompose()">
            <h3>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                Send Message to Depot Officer
            </h3>
            <span class="compose-toggle" id="composeToggleLabel">▼ Open</span>
        </div>
        <div class="tmsg-compose-body" id="composeBody">
            <form method="post" action="/TS/messages?action=send&filter=<?= urlencode($filter) ?>">
                <textarea name="message" id="composeText" placeholder="Type your message to the Depot Officer…" required></textarea>
                <input type="hidden" name="priority" id="composePriority" value="normal">
                <div class="tmsg-priority-row">
                    <span class="tmsg-prio-label">Priority:</span>
                    <button type="button" class="tmsg-prio-btn sel-normal"  onclick="setPrio('normal',this)">Normal</button>
                    <button type="button" class="tmsg-prio-btn"             onclick="setPrio('urgent',this)">🟠 Urgent</button>
                    <button type="button" class="tmsg-prio-btn"             onclick="setPrio('critical',this)">🔴 Critical</button>
                </div>
                <div><button type="submit" class="tmsg-send-btn">Send ➤</button></div>
            </form>
        </div>
    </div>

    <div class="tmsg-list">
        <?php if (empty($recent)): ?>
            <div class="tmsg-empty"><div style="font-size:36px;margin-bottom:8px">📭</div>No messages for this filter.</div>
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
                        <p class="tmsg-sender-name"><?= htmlspecialchars($srcName ?: 'Depot Officer') ?></p>
                        <div class="tmsg-sender-role">
                            <?= htmlspecialchars($srcRole ?: 'Depot Officer') ?>
                            · <?= htmlspecialchars(ts_time_ago((string)($row['created_at'] ?? ''))) ?>
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
                            <form method="post" action="/TS/messages?action=read&id=<?= $id ?>&filter=<?= urlencode($filter) ?>" style="display:inline">
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
</div>

<script>
function ackMsg(id, btn) {
    btn.disabled = true; btn.textContent = 'Acknowledging…';
    fetch('/TS/messages?action=ack&id='+id, {method:'POST'})
        .then(r=>r.json())
        .then(d=>{
            if(d.ok){ btn.textContent='✔ Acknowledged'; btn.style.background='#059669';
                const c=document.getElementById('msg-'+id); if(c) c.classList.remove('unread');
            } else { btn.disabled=false; btn.textContent='✔ Acknowledge'; }
        })
        .catch(()=>{ btn.disabled=false; btn.textContent='✔ Acknowledge'; });
}

function toggleCompose() {
    const body  = document.getElementById('composeBody');
    const label = document.getElementById('composeToggleLabel');
    const open  = body.classList.toggle('open');
    label.textContent = open ? '▲ Close' : '▼ Open';
}

function setPrio(val, btn) {
    document.getElementById('composePriority').value = val;
    document.querySelectorAll('.tmsg-prio-btn').forEach(b => {
        b.classList.remove('sel-normal','sel-urgent','sel-critical');
    });
    btn.classList.add('sel-' + val);
}
</script>