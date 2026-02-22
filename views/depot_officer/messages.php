<?php
/**
 * views/depot_officer/messages.php
 * Revamped Depot Officer Messages Page
 * ─────────────────────────────────────────────────────────────────────────────
 * Variables passed from DepotOfficerController::messages():
 *   $me     – current user array
 *   $staff  – array of depot staff (same-depot users)
 *   $recent – array of recent messages/notifications
 *   $msg    – flash key ('sent' | 'error' | null)
 */

/** @var array $me, $staff, $recent */
$me     = $me     ?? ($_SESSION['user'] ?? []);
$staff  = $staff  ?? [];
$recent = $recent ?? [];
$msg    = $msg    ?? null;

$myId   = (int)($me['user_id'] ?? 0);

/* ── helpers ─────────────────────────────────────────────────────────────── */
function msgDisplayName(array $p): string {
    if (!empty($p['full_name']))  return $p['full_name'];
    $n = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
    return $n !== '' ? $n : ($p['name'] ?? 'Unknown');
}

function msgRoleBadge(string $role): string {
    $map = [
        'DepotOfficer'      => ['#7c3aed','#ede9fe','Officer'],
        'Driver'            => ['#0369a1','#e0f2fe','Driver'],
        'Conductor'         => ['#065f46','#d1fae5','Conductor'],
        'DepotManager'      => ['#92400e','#fef3c7','Manager'],
        'SLTBTimekeeper'    => ['#be185d','#fce7f3','SLTB TK'],
        'PrivateTimekeeper' => ['#9d174d','#fdf2f8','Pvt TK'],
    ];
    [$color,$bg,$label] = $map[$role] ?? ['#6b7280','#f3f4f6',$role];
    return '<span class="msg-role-tag" style="background:'.$bg.';color:'.$color.'">'
           .htmlspecialchars($label).'</span>';
}

/* Group staff by role for the targeting sidebar */
$byRole = [];
foreach ($staff as $s) {
    $r = $s['role'] ?? 'Other';
    $byRole[$r][] = $s;
}

/* Templates */
$templates = [
    ['id'=>'delay',       'icon'=>'⏱️', 'label'=>'Trip Delay',       'text'=>'DELAY ALERT: Bus [REG] on Route [NO] is delayed by approx. [MIN] minutes. Please adjust schedules accordingly.'],
    ['id'=>'breakdown',   'icon'=>'🔧', 'label'=>'Breakdown',         'text'=>'BREAKDOWN: Bus [REG] has broken down near [LOCATION] on Route [NO]. Dispatch replacement immediately.'],
    ['id'=>'override',    'icon'=>'🔄', 'label'=>'Schedule Override',  'text'=>'SCHEDULE OVERRIDE: Assignment for Trip [ID] has been modified. New departure: [TIME]. Driver/Conductor please confirm receipt.'],
    ['id'=>'maintenance', 'icon'=>'🛠️', 'label'=>'Maintenance Notice', 'text'=>'MAINTENANCE NOTICE: Bus [REG] is scheduled for maintenance on [DATE]. Ensure replacement is arranged.'],
    ['id'=>'headcount',   'icon'=>'📋', 'label'=>'Attendance Check',   'text'=>'ATTENDANCE CHECK: All staff please confirm availability for tomorrow [DATE] by [TIME].'],
];

/* Flash messages */
$flashMessages = [
    'sent'  => ['type'=>'success','text'=>'Message sent successfully.'],
    'error' => ['type'=>'error',  'text'=>'Failed to send message. Please try again.'],
];
?>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE STYLES  (scoped to .msg-page)
     ═══════════════════════════════════════════════════════════════════════ -->
<style>
/* ── layout shell ─────────────────────────────────────────────────────── */
.msg-page { display:flex; flex-direction:column; gap:0; height:calc(100vh - 80px); min-height:0; }

.msg-topbar {
    background: linear-gradient(135deg, var(--maroon,#7f1d1d) 0%, #a01c2e 100%);
    color:#fff;
    padding: 18px 24px 16px;
    border-radius: 16px 16px 0 0;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(127,29,29,.25);
}
.msg-topbar-icon {
    width:40px; height:40px; border-radius:12px;
    background:rgba(255,255,255,.15);
    display:grid; place-items:center; font-size:20px;
}
.msg-topbar h1 { margin:0; font-size:18px; font-weight:800; letter-spacing:-.2px; }
.msg-topbar .sub { font-size:12px; opacity:.75; margin-top:1px; }
.msg-topbar-actions { margin-left:auto; display:flex; gap:8px; }
.msg-tb-btn {
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.25);
    color:#fff; padding:7px 14px; border-radius:9px;
    font-size:12px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; gap:6px;
    transition: background .15s;
}
.msg-tb-btn:hover { background:rgba(255,255,255,.25); }
.msg-tb-btn.active { background:rgba(255,255,255,.28); }

/* ── three-column body ─────────────────────────────────────────────────── */
.msg-body {
    display: grid;
    grid-template-columns: 260px 1fr 300px;
    flex: 1;
    min-height: 0;
    background: #fafafa;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 16px 16px;
    overflow: hidden;
}

/* ── left: inbox list ──────────────────────────────────────────────────── */
.msg-inbox {
    border-right: 1px solid #e9e3da;
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: #fff;
}
.msg-inbox-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid #f0e8de;
    background: #fffaf5;
    flex-shrink: 0;
}
.msg-inbox-head h3 { margin:0; font-size:13px; font-weight:800; color: var(--maroon,#7f1d1d); text-transform:uppercase; letter-spacing:.6px; }
.msg-inbox-filter {
    display:flex; gap:4px; margin-top:8px;
}
.msg-filter-btn {
    font-size:11px; font-weight:700; padding:4px 9px; border-radius:20px;
    border:1px solid #ddd; background:#fff; cursor:pointer;
    color:#6b7280; transition: all .15s;
}
.msg-filter-btn.active, .msg-filter-btn:hover {
    background: var(--maroon,#7f1d1d); color:#fff; border-color: var(--maroon,#7f1d1d);
}
.msg-search {
    width:100%; margin-top:8px; padding:7px 10px;
    border:1px solid #e5e7eb; border-radius:8px;
    font-size:12px; outline:none; box-sizing:border-box;
    background:#f9fafb;
}
.msg-search:focus { border-color: var(--maroon,#7f1d1d); background:#fff; }

.msg-list { flex:1; overflow-y:auto; }
.msg-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f5f0ea;
    cursor: pointer;
    transition: background .12s;
    display:flex; align-items:flex-start; gap:10px;
}
.msg-item:hover { background: #fffaf5; }
.msg-item.active { background: #fef9ef; border-left:3px solid var(--maroon,#7f1d1d); }
.msg-item.unread .msg-item-subject { font-weight:800; }
.msg-item-avatar {
    width:34px; height:34px; border-radius:50%; flex-shrink:0;
    display:grid; place-items:center; font-size:13px; font-weight:800;
    background: linear-gradient(135deg,#b91c1c,#7f1d1d); color:#fff;
}
.msg-item-avatar.sys { background:linear-gradient(135deg,#1d4ed8,#1e3a8a); }
.msg-item-avatar.alert { background:linear-gradient(135deg,#d97706,#92400e); }
.msg-item-meta { flex:1; min-width:0; }
.msg-item-subject { font-size:13px; color:#111; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-item-preview { font-size:11px; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-item-time { font-size:10px; color:#c4b5a0; margin-top:3px; }
.msg-unread-dot {
    width:7px; height:7px; border-radius:50%;
    background: var(--maroon,#7f1d1d);
    flex-shrink:0; margin-top:4px;
}
.msg-empty {
    padding:40px 20px; text-align:center; color:#9ca3af; font-size:13px;
}

/* ── centre: thread / detail ───────────────────────────────────────────── */
.msg-thread {
    display:flex; flex-direction:column; min-height:0; background:#fff;
    border-right:1px solid #e9e3da;
}
.msg-thread-head {
    padding:14px 20px 12px;
    border-bottom:1px solid #f0e8de;
    background:#fffaf5;
    flex-shrink:0;
    display:flex; align-items:center; gap:12px;
}
.msg-thread-head h2 { margin:0; font-size:15px; font-weight:800; color:#111; }
.msg-thread-head .sub { font-size:12px; color:#9ca3af; }
.msg-thread-status {
    margin-left:auto; font-size:11px; font-weight:700; padding:4px 10px;
    border-radius:20px; background:#fef3c7; color:#92400e;
}
.msg-thread-body {
    flex:1; overflow-y:auto; padding:20px;
    display:flex; flex-direction:column; gap:14px;
}
.msg-bubble-wrap { display:flex; gap:10px; align-items:flex-start; }
.msg-bubble-wrap.me { flex-direction:row-reverse; }
.msg-bubble-avatar {
    width:30px; height:30px; border-radius:50%; flex-shrink:0;
    display:grid; place-items:center; font-size:11px; font-weight:800; color:#fff;
    background:linear-gradient(135deg,#b91c1c,#7f1d1d);
}
.msg-bubble-avatar.sys { background:linear-gradient(135deg,#1d4ed8,#1e3a8a); }
.msg-bubble {
    max-width:72%; padding:10px 14px; border-radius:12px;
    font-size:13px; line-height:1.55; position:relative;
}
.msg-bubble-wrap:not(.me) .msg-bubble {
    background:#f5f0ea; color:#1f1f1f;
    border-radius:4px 12px 12px 12px;
}
.msg-bubble-wrap.me .msg-bubble {
    background:linear-gradient(135deg,#7f1d1d,#a01c2e); color:#fff;
    border-radius:12px 4px 12px 12px;
}
.msg-bubble-wrap.system .msg-bubble {
    background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe;
    border-radius:8px; font-size:12px;
    max-width:90%; margin:0 auto;
}
.msg-bubble-time { font-size:10px; opacity:.55; margin-top:5px; }
.msg-bubble-wrap.me .msg-bubble-time { text-align:right; }
.msg-ack-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px;
    background:rgba(255,255,255,.2);
    margin-top:4px;
}
.msg-ack-badge.pending { background:rgba(255,255,255,.15); }
.msg-ack-badge.ack { background:rgba(134,239,172,.3); color:#14532d; }

/* compose bar */
.msg-compose-bar {
    padding:12px 16px;
    border-top:1px solid #f0e8de;
    background:#fffaf5;
    flex-shrink:0;
}
.msg-compose-inner {
    display:flex; align-items:flex-end; gap:8px;
}
.msg-compose-textarea {
    flex:1; resize:none; border:1px solid #e5e7eb; border-radius:10px;
    padding:9px 12px; font-size:13px; line-height:1.5;
    outline:none; font-family:inherit; background:#fff;
    transition: border-color .15s;
    min-height:42px; max-height:120px;
}
.msg-compose-textarea:focus { border-color: var(--maroon,#7f1d1d); }
.msg-send-btn {
    background:linear-gradient(135deg,#7f1d1d,#a01c2e);
    color:#fff; border:none; border-radius:10px;
    padding:10px 16px; font-weight:800; font-size:13px;
    cursor:pointer; display:flex; align-items:center; gap:6px;
    transition: opacity .15s, transform .1s;
    white-space:nowrap;
}
.msg-send-btn:hover { opacity:.9; }
.msg-send-btn:active { transform:scale(.97); }
.msg-send-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── right: compose panel ──────────────────────────────────────────────── */
.msg-compose-panel {
    display:flex; flex-direction:column; min-height:0; background:#fafafa;
    overflow-y:auto;
}
.msg-compose-panel-head {
    padding:14px 16px 10px;
    border-bottom:1px solid #e9e3da;
    background:#fff;
    flex-shrink:0;
}
.msg-compose-panel-head h3 { margin:0; font-size:13px; font-weight:800; color: var(--maroon,#7f1d1d); text-transform:uppercase; letter-spacing:.6px; }
.msg-cp-section { padding:14px 16px; border-bottom:1px solid #eee; }
.msg-cp-label { font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }

/* targeting */
.msg-scope-tabs { display:flex; gap:4px; flex-wrap:wrap; }
.msg-scope-tab {
    font-size:11px; font-weight:700; padding:5px 10px; border-radius:20px;
    border:1px solid #ddd; background:#fff; cursor:pointer; color:#6b7280;
    transition:all .15s;
}
.msg-scope-tab.active, .msg-scope-tab:hover {
    background:var(--maroon,#7f1d1d); color:#fff; border-color:var(--maroon,#7f1d1d);
}
.msg-scope-panel { margin-top:10px; display:none; }
.msg-scope-panel.shown { display:block; }

/* recipient checklist */
.msg-recipient-search {
    width:100%; padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px;
    font-size:12px; outline:none; box-sizing:border-box; margin-bottom:8px;
}
.msg-recipient-search:focus { border-color:var(--maroon,#7f1d1d); }
.msg-recipient-list {
    max-height:160px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px;
    background:#fff;
}
.msg-recipient-item {
    display:flex; align-items:center; gap:8px; padding:7px 10px;
    border-bottom:1px solid #f5f5f5; cursor:pointer;
    font-size:12px;
}
.msg-recipient-item:last-child { border-bottom:none; }
.msg-recipient-item:hover { background:#fffaf5; }
.msg-recipient-item input[type=checkbox] { accent-color:var(--maroon,#7f1d1d); }
.msg-recipient-item .ri-name { flex:1; font-weight:600; }
.msg-selected-tags { display:flex; flex-wrap:wrap; gap:4px; margin-top:8px; min-height:24px; }
.msg-tag {
    display:inline-flex; align-items:center; gap:4px;
    font-size:11px; font-weight:700;
    background:#fde8e8; color:#7f1d1d;
    padding:3px 8px; border-radius:20px;
}
.msg-tag-rm { cursor:pointer; font-size:13px; line-height:1; }

/* role badge */
.msg-role-tag {
    font-size:10px; font-weight:800; padding:2px 7px; border-radius:20px;
}

/* templates */
.msg-tpl-grid { display:flex; flex-direction:column; gap:6px; }
.msg-tpl-btn {
    display:flex; align-items:center; gap:8px; padding:8px 10px;
    background:#fff; border:1px solid #e5e7eb; border-radius:9px;
    cursor:pointer; font-size:12px; text-align:left; color:#374151;
    transition:all .15s;
}
.msg-tpl-btn:hover { border-color:var(--maroon,#7f1d1d); background:#fffaf5; color:var(--maroon,#7f1d1d); }
.msg-tpl-icon { font-size:16px; }
.msg-tpl-label { font-weight:700; }
.msg-tpl-sub { font-size:10px; color:#9ca3af; margin-top:1px; }

/* textarea */
.msg-body-textarea {
    width:100%; box-sizing:border-box; resize:vertical;
    border:1px solid #e5e7eb; border-radius:9px;
    padding:9px 12px; font-size:13px; line-height:1.55;
    font-family:inherit; background:#fff; outline:none;
    min-height:90px;
    transition:border-color .15s;
}
.msg-body-textarea:focus { border-color:var(--maroon,#7f1d1d); }

/* priority */
.msg-priority-row { display:flex; gap:6px; }
.msg-priority-pill {
    flex:1; padding:6px 4px; text-align:center;
    border:1px solid #e5e7eb; border-radius:8px; cursor:pointer;
    font-size:11px; font-weight:700; color:#6b7280; background:#fff;
    transition:all .15s;
}
.msg-priority-pill.active-normal  { background:#f0fdf4; color:#166534; border-color:#86efac; }
.msg-priority-pill.active-urgent  { background:#fef9c3; color:#854d0e; border-color:#fde047; }
.msg-priority-pill.active-critical{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }
.msg-priority-pill:hover { border-color:var(--maroon,#7f1d1d); }

/* send button full width */
.msg-send-full {
    margin:12px 16px 16px;
    background:linear-gradient(135deg,#7f1d1d,#a01c2e);
    color:#fff; border:none; border-radius:10px;
    padding:12px; font-weight:800; font-size:14px;
    cursor:pointer; width:calc(100% - 32px);
    display:flex; align-items:center; justify-content:center; gap:8px;
    transition:opacity .15s, transform .1s;
    box-shadow: 0 4px 12px rgba(127,29,29,.3);
}
.msg-send-full:hover { opacity:.92; transform:translateY(-1px); }
.msg-send-full:disabled { opacity:.4; cursor:not-allowed; }

/* flash alert */
.msg-flash {
    padding:10px 16px; border-radius:10px; margin:14px 16px 0;
    font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;
}
.msg-flash.success { background:#f0fdf4; color:#166534; border:1px solid #86efac; }
.msg-flash.error   { background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; }

/* stats row */
.msg-stats { display:flex; gap:8px; padding:10px 16px 0; flex-shrink:0; }
.msg-stat {
    flex:1; background:#fff; border:1px solid #e9e3da; border-radius:10px;
    padding:8px 10px; text-align:center;
}
.msg-stat-val { font-size:20px; font-weight:800; color:var(--maroon,#7f1d1d); line-height:1; }
.msg-stat-label { font-size:10px; color:#9ca3af; margin-top:2px; }

/* responsive */
@media(max-width:1100px){
    .msg-body { grid-template-columns:220px 1fr 260px; }
}
@media(max-width:860px){
    .msg-body { grid-template-columns:1fr; grid-template-rows:auto 1fr auto; }
    .msg-inbox, .msg-compose-panel { max-height:260px; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     PAGE HTML
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="msg-page" id="msgPage">

    <!-- TOP BAR -->
    <div class="msg-topbar">
        <div class="msg-topbar-icon">💬</div>
        <div>
            <h1>Depot Messaging Centre</h1>
            <div class="sub">Centralized communications · Depot <?= htmlspecialchars($me['sltb_depot_id'] ?? '—') ?></div>
        </div>
        <div class="msg-topbar-actions">
            <button class="msg-tb-btn" id="btnRefresh" title="Refresh inbox">
                <span>↻</span> Refresh
            </button>
            <button class="msg-tb-btn active" id="btnCompose" title="New message">
                <span>✏️</span> New Message
            </button>
        </div>
    </div>

    <!-- STATS ROW -->
    <div class="msg-stats" style="background:#fff;border-left:1px solid #e9e3da;border-right:1px solid #e9e3da;padding-top:10px;padding-bottom:10px;">
        <?php
            $total  = count($recent);
            $unread = count(array_filter($recent, fn($n) => !($n['is_seen'] ?? $n['read_at'] ?? false)));
            $alerts = count(array_filter($recent, fn($n) => in_array($n['type'] ?? '', ['Delay','Alert','Breakdown'])));
        ?>
        <div class="msg-stat">
            <div class="msg-stat-val"><?= $total ?></div>
            <div class="msg-stat-label">Total</div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-val" style="color:#d97706"><?= $unread ?></div>
            <div class="msg-stat-label">Unread</div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-val" style="color:#dc2626"><?= $alerts ?></div>
            <div class="msg-stat-label">Alerts</div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-val" style="color:#059669"><?= count($staff) ?></div>
            <div class="msg-stat-label">Staff</div>
        </div>
    </div>

    <!-- THREE-COLUMN BODY -->
    <div class="msg-body">

        <!-- ── LEFT: INBOX LIST ─────────────────────────────────────── -->
        <div class="msg-inbox">
            <div class="msg-inbox-head">
                <h3>Inbox</h3>
                <div class="msg-inbox-filter">
                    <button class="msg-filter-btn active" data-filter="all">All</button>
                    <button class="msg-filter-btn" data-filter="unread">Unread</button>
                    <button class="msg-filter-btn" data-filter="alert">Alerts</button>
                    <button class="msg-filter-btn" data-filter="message">Messages</button>
                </div>
                <input type="text" class="msg-search" id="inboxSearch" placeholder="Search messages…">
            </div>

            <div class="msg-list" id="msgList">
                <?php if (empty($recent)): ?>
                    <div class="msg-empty">
                        <div style="font-size:32px;margin-bottom:8px">📭</div>
                        No messages yet.<br>Send the first one!
                    </div>
                <?php else: ?>
                    <?php foreach ($recent as $n):
                        $nid     = (int)($n['notification_id'] ?? $n['id'] ?? 0);
                        $isUnread = !($n['is_seen'] ?? $n['read_at'] ?? false);
                        $type    = $n['type'] ?? 'Message';
                        $text    = $n['message'] ?? '';
                        $preview = mb_strimwidth($text, 0, 60, '…');
                        $name    = msgDisplayName($n);
                        $init    = strtoupper(substr($name,0,1));
                        $avatarCls = in_array($type,['Delay','Alert','Breakdown']) ? 'alert' : (str_contains(strtolower($name),'system') ? 'sys' : '');
                        $timeStr = '';
                        if (!empty($n['created_at'])) {
                            $ts = strtotime($n['created_at']);
                            $diff = time() - $ts;
                            if ($diff < 3600)      $timeStr = round($diff/60).'m ago';
                            elseif ($diff < 86400) $timeStr = round($diff/3600).'h ago';
                            else $timeStr = date('d M', $ts);
                        }
                        $typeIcons = ['Delay'=>'⏱️','Breakdown'=>'🔧','Alert'=>'🚨','Timetable'=>'📅','Message'=>'💬'];
                        $typeIcon  = $typeIcons[$type] ?? '📩';
                    ?>
                    <div class="msg-item <?= $isUnread ? 'unread' : '' ?>"
                         data-id="<?= $nid ?>"
                         data-type="<?= htmlspecialchars(strtolower($type)) ?>"
                         data-unread="<?= $isUnread ? '1' : '0' ?>"
                         data-text="<?= htmlspecialchars($text) ?>"
                         data-name="<?= htmlspecialchars($name) ?>"
                         data-time="<?= htmlspecialchars($n['created_at'] ?? '') ?>"
                         onclick="openThread(this)">
                        <div class="msg-item-avatar <?= $avatarCls ?>"><?= $typeIcon ?></div>
                        <div class="msg-item-meta">
                            <div class="msg-item-subject"><?= htmlspecialchars($name) ?></div>
                            <div class="msg-item-preview"><?= htmlspecialchars($preview) ?></div>
                            <div class="msg-item-time"><?= htmlspecialchars($type) ?> · <?= $timeStr ?></div>
                        </div>
                        <?php if ($isUnread): ?>
                            <div class="msg-unread-dot"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div><!-- /inbox -->

        <!-- ── CENTRE: THREAD VIEW ─────────────────────────────────── -->
        <div class="msg-thread">
            <div class="msg-thread-head" id="threadHead">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#b91c1c,#7f1d1d);display:grid;place-items:center;color:#fff;font-size:16px;">💬</div>
                <div>
                    <h2 id="threadTitle">Select a message</h2>
                    <div class="sub" id="threadSub">Click any message on the left to read it</div>
                </div>
                <div class="msg-thread-status" id="threadStatus" style="display:none"></div>
            </div>

            <div class="msg-thread-body" id="threadBody">
                <!-- placeholder -->
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#d1c4b0;gap:10px;">
                    <div style="font-size:48px">📬</div>
                    <div style="font-weight:700;font-size:14px">No message selected</div>
                    <div style="font-size:12px">Pick a message from the inbox or compose a new one</div>
                </div>
            </div>

            <div class="msg-compose-bar" id="quickReplyBar" style="display:none">
                <div style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Quick Reply</div>
                <div class="msg-compose-inner">
                    <textarea class="msg-compose-textarea" id="quickReplyText" rows="2" placeholder="Type a reply…"></textarea>
                    <button class="msg-send-btn" id="quickReplySend">
                        <span>Send</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
                <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;" id="quickActions">
                    <button class="msg-filter-btn" onclick="ackMessage()">✔ Acknowledge</button>
                    <button class="msg-filter-btn" onclick="escalateMessage()">⬆ Escalate</button>
                    <button class="msg-filter-btn" onclick="archiveMessage()">📁 Archive</button>
                </div>
            </div>
        </div><!-- /thread -->

        <!-- ── RIGHT: COMPOSE PANEL ────────────────────────────────── -->
        <div class="msg-compose-panel">
            <div class="msg-compose-panel-head">
                <h3>✏️ Compose Message</h3>
            </div>

            <?php if (!empty($msg) && isset($flashMessages[$msg])): ?>
                <div class="msg-flash <?= $flashMessages[$msg]['type'] ?>">
                    <?= $flashMessages[$msg]['type'] === 'success' ? '✓' : '✕' ?>
                    <?= htmlspecialchars($flashMessages[$msg]['text']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/O/messages" id="composeForm">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="scope" id="scopeInput" value="individual">
                <!-- hidden checkboxes will be added dynamically for recipient ids -->

                <!-- TARGETING -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">📍 Target</div>
                    <div class="msg-scope-tabs">
                        <button type="button" class="msg-scope-tab active" data-scope="individual">Individual</button>
                        <button type="button" class="msg-scope-tab" data-scope="role">By Role</button>
                        <button type="button" class="msg-scope-tab" data-scope="depot">All Depot</button>
                    </div>

                    <!-- Individual -->
                    <div class="msg-scope-panel shown" id="scope-individual">
                        <input type="text" class="msg-recipient-search" id="recipientSearch" placeholder="Search staff…">
                        <div class="msg-recipient-list" id="recipientList">
                            <?php foreach ($staff as $s):
                                $sid   = (int)($s['user_id'] ?? 0);
                                $sname = htmlspecialchars(msgDisplayName($s));
                                $srole = htmlspecialchars($s['role'] ?? '');
                            ?>
                            <label class="msg-recipient-item" data-name="<?= $sname ?>">
                                <input type="checkbox" name="to[]" value="<?= $sid ?>" class="recipient-cb" onchange="updateTags()">
                                <span class="ri-name"><?= $sname ?></span>
                                <?= msgRoleBadge($s['role'] ?? '') ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="msg-selected-tags" id="selectedTags"></div>
                    </div>

                    <!-- By Role -->
                    <div class="msg-scope-panel" id="scope-role">
                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">
                            <?php foreach (array_keys($byRole) as $roleName): ?>
                            <label class="msg-recipient-item" style="border:1px solid #e9e3da;border-radius:8px;cursor:pointer;">
                                <input type="checkbox" name="role_scope[]" value="<?= htmlspecialchars($roleName) ?>" onchange="updateRoleScope()">
                                <span class="ri-name"><?= htmlspecialchars($roleName) ?></span>
                                <span style="font-size:10px;color:#9ca3af"><?= count($byRole[$roleName]) ?> staff</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- All Depot -->
                    <div class="msg-scope-panel" id="scope-depot">
                        <div style="margin-top:10px;padding:10px;background:#fef3c7;border:1px solid #fde047;border-radius:8px;font-size:12px;color:#78350f;font-weight:600;">
                            ⚠️ This will send to all <?= count($staff) ?> staff in your depot. Use for critical announcements only.
                        </div>
                        <input type="hidden" name="all_depot" id="allDepotInput" value="0">
                    </div>
                </div>

                <!-- TEMPLATES -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">📋 Templates</div>
                    <div class="msg-tpl-grid">
                        <?php foreach ($templates as $tpl): ?>
                        <button type="button" class="msg-tpl-btn"
                                data-template="<?= htmlspecialchars($tpl['text']) ?>"
                                onclick="applyTemplate(this)">
                            <span class="msg-tpl-icon"><?= $tpl['icon'] ?></span>
                            <div>
                                <div class="msg-tpl-label"><?= htmlspecialchars($tpl['label']) ?></div>
                                <div class="msg-tpl-sub">Click to insert</div>
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- PRIORITY -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">🚦 Priority</div>
                    <div class="msg-priority-row">
                        <div class="msg-priority-pill active-normal" data-priority="normal" onclick="setPriority(this,'normal')">Normal</div>
                        <div class="msg-priority-pill" data-priority="urgent" onclick="setPriority(this,'urgent')">Urgent</div>
                        <div class="msg-priority-pill" data-priority="critical" onclick="setPriority(this,'critical')">Critical</div>
                    </div>
                    <input type="hidden" name="priority" id="priorityInput" value="normal">
                </div>

                <!-- MESSAGE BODY -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">📝 Message</div>
                    <textarea name="message" id="messageBody" class="msg-body-textarea"
                              placeholder="Type your message here…" required
                              oninput="updateCharCount(this)"></textarea>
                    <div style="text-align:right;font-size:10px;color:#9ca3af;margin-top:3px;" id="charCount">0 / 500</div>
                </div>

            </form><!-- /composeForm -->

            <button class="msg-send-full" id="sendFullBtn" onclick="submitCompose()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Message
            </button>

        </div><!-- /compose panel -->

    </div><!-- /msg-body -->
</div><!-- /msg-page -->

<!-- ═══════════════════════════════════════════════════════════════════════
     JS
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
'use strict';

/* ─── Inbox filter ──────────────────────────────────────────────────── */
const filterBtns = document.querySelectorAll('.msg-inbox-filter .msg-filter-btn');
const msgItems   = document.querySelectorAll('#msgList .msg-item');
let   activeFilter = 'all';

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        applyInboxFilter();
    });
});

document.getElementById('inboxSearch').addEventListener('input', applyInboxFilter);

function applyInboxFilter() {
    const q = document.getElementById('inboxSearch').value.toLowerCase();
    msgItems.forEach(item => {
        const type   = item.dataset.type || '';
        const unread = item.dataset.unread === '1';
        const text   = (item.dataset.name + ' ' + item.dataset.text).toLowerCase();
        let show = true;
        if (activeFilter === 'unread')  show = unread;
        if (activeFilter === 'alert')   show = ['delay','alert','breakdown'].includes(type);
        if (activeFilter === 'message') show = type === 'message';
        if (q) show = show && text.includes(q);
        item.style.display = show ? '' : 'none';
    });
}

/* ─── Thread open ───────────────────────────────────────────────────── */
let currentItem = null;

window.openThread = function(el) {
    if (currentItem) currentItem.classList.remove('active');
    el.classList.add('active');
    el.querySelector('.msg-unread-dot')?.remove();
    el.dataset.unread = '0';
    currentItem = el;

    const name  = el.dataset.name  || 'Unknown';
    const text  = el.dataset.text  || '';
    const time  = el.dataset.time  || '';
    const type  = el.dataset.type  || 'Message';
    const init  = name.charAt(0).toUpperCase();

    document.getElementById('threadTitle').textContent = name;
    document.getElementById('threadSub').textContent   = type + (time ? ' · ' + formatRelTime(time) : '');

    const statEl = document.getElementById('threadStatus');
    statEl.style.display = '';
    statEl.textContent = capitalise(type);

    const body = document.getElementById('threadBody');
    body.innerHTML = buildBubble(init, name, text, time, type);
    document.getElementById('quickReplyBar').style.display = '';

    // Mark read via background fetch (best-effort)
    const nid = el.dataset.id;
    if (nid) {
        fetch('/O/messages/read?id=' + nid, { method:'POST' }).catch(()=>{});
    }
};

function buildBubble(init, name, text, time, type) {
    const isSystem = ['delay','alert','breakdown','timetable'].includes(type.toLowerCase());
    const wrapCls  = isSystem ? 'system' : '';
    const avCls    = isSystem ? 'sys' : '';
    const typeIcons = {delay:'⏱️',breakdown:'🔧',alert:'🚨',timetable:'📅',message:'💬'};
    const icon = typeIcons[type.toLowerCase()] || '📩';
    const timeStr = formatRelTime(time);

    return `<div class="msg-bubble-wrap ${wrapCls}">
        <div class="msg-bubble-avatar ${avCls}">${isSystem ? icon : esc(init)}</div>
        <div>
            <div class="msg-bubble">${esc(text)}</div>
            <div class="msg-bubble-time">${esc(name)} · ${timeStr}</div>
        </div>
    </div>`;
}

/* ─── Scope / targeting ─────────────────────────────────────────────── */
const scopeTabs  = document.querySelectorAll('.msg-scope-tab');
const scopePanels= document.querySelectorAll('.msg-scope-panel');

scopeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        scopeTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const scope = tab.dataset.scope;
        document.getElementById('scopeInput').value = scope;
        scopePanels.forEach(p => p.classList.remove('shown'));
        const panel = document.getElementById('scope-' + scope);
        if (panel) panel.classList.add('shown');
        if (scope === 'depot') {
            document.getElementById('allDepotInput').value = '1';
        } else {
            document.getElementById('allDepotInput').value = '0';
        }
    });
});

/* ─── Recipient tags ────────────────────────────────────────────────── */
window.updateTags = function() {
    const checked = document.querySelectorAll('.recipient-cb:checked');
    const tags = document.getElementById('selectedTags');
    tags.innerHTML = '';
    checked.forEach(cb => {
        const label = cb.closest('label');
        const name  = label.dataset.name || 'Staff';
        const tag   = document.createElement('span');
        tag.className = 'msg-tag';
        tag.innerHTML = esc(name) + '<span class="msg-tag-rm" onclick="removeRecipient(' + cb.value + ')">×</span>';
        tags.appendChild(tag);
    });
};

window.removeRecipient = function(uid) {
    const cb = document.querySelector('.recipient-cb[value="' + uid + '"]');
    if (cb) { cb.checked = false; updateTags(); }
};

/* recipient search */
document.getElementById('recipientSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#recipientList .msg-recipient-item').forEach(item => {
        item.style.display = item.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
});

/* ─── Role scope → hidden checkboxes ───────────────────────────────── */
const staffByRole = <?php
    $jsRoles = [];
    foreach ($byRole as $r => $members) {
        $jsRoles[$r] = array_map(fn($m) => (int)($m['user_id'] ?? 0), $members);
    }
    echo json_encode($jsRoles, JSON_UNESCAPED_UNICODE);
?>;

window.updateRoleScope = function() {
    // remove old role-generated checkboxes
    document.querySelectorAll('.role-generated-cb').forEach(el => el.remove());
    const checked = document.querySelectorAll('input[name="role_scope[]"]:checked');
    const seen = new Set();
    checked.forEach(cb => {
        const ids = staffByRole[cb.value] || [];
        ids.forEach(uid => {
            if (!seen.has(uid)) {
                seen.add(uid);
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'to[]'; inp.value = uid;
                inp.className = 'role-generated-cb';
                document.getElementById('composeForm').appendChild(inp);
            }
        });
    });
};

/* ─── Templates ─────────────────────────────────────────────────────── */
window.applyTemplate = function(btn) {
    document.getElementById('messageBody').value = btn.dataset.template;
    updateCharCount(document.getElementById('messageBody'));
    document.getElementById('messageBody').focus();
};

/* ─── Priority ──────────────────────────────────────────────────────── */
window.setPriority = function(el, val) {
    document.querySelectorAll('.msg-priority-pill').forEach(p => {
        p.classList.remove('active-normal','active-urgent','active-critical');
    });
    el.classList.add('active-' + val);
    document.getElementById('priorityInput').value = val;
};

/* ─── Char count ────────────────────────────────────────────────────── */
window.updateCharCount = function(ta) {
    const len = ta.value.length;
    document.getElementById('charCount').textContent = len + ' / 500';
    if (len > 500) ta.value = ta.value.slice(0, 500);
};

/* ─── Submit compose ────────────────────────────────────────────────── */
window.submitCompose = function() {
    const form  = document.getElementById('composeForm');
    const scope = document.getElementById('scopeInput').value;
    const text  = document.getElementById('messageBody').value.trim();
    const allDepot = document.getElementById('allDepotInput').value === '1';

    if (!text) { alert('Please enter a message.'); return; }

    // Validate recipients
    if (scope === 'individual') {
        const anyChecked = document.querySelector('.recipient-cb:checked');
        if (!anyChecked) { alert('Please select at least one recipient.'); return; }
    }
    if (scope === 'role') {
        const anyRole = document.querySelector('input[name="role_scope[]"]:checked');
        if (!anyRole) { alert('Please select at least one role.'); return; }
    }

    // For depot-wide, add all staff ids
    if (allDepot) {
        document.querySelectorAll('.depot-generated-cb').forEach(el => el.remove());
        <?php
            $allIds = array_map(fn($s) => (int)($s['user_id'] ?? 0), $staff);
            echo 'const allIds = ' . json_encode($allIds) . ';';
        ?>
        allIds.forEach(uid => {
            const inp = document.createElement('input');
            inp.type='hidden'; inp.name='to[]'; inp.value=uid;
            inp.className='depot-generated-cb';
            form.appendChild(inp);
        });
    }

    form.submit();
};

/* ─── Quick Reply ───────────────────────────────────────────────────── */
document.getElementById('quickReplySend').addEventListener('click', function() {
    const text = document.getElementById('quickReplyText').value.trim();
    if (!text) return;
    // Populate compose panel and submit
    document.getElementById('messageBody').value = text;
    // Target same recipient (best effort: use scope all depot if we can't determine)
    // For MVP: just submit via form (could be enhanced with AJAX)
    document.getElementById('composeForm').submit();
});

/* ─── Quick actions ─────────────────────────────────────────────────── */
window.ackMessage = function() {
    if (!currentItem) return;
    const nid = currentItem.dataset.id;
    // Append acknowledgement bubble
    const body = document.getElementById('threadBody');
    const ackEl = document.createElement('div');
    ackEl.className = 'msg-bubble-wrap me';
    ackEl.innerHTML = '<div class="msg-bubble-avatar" style="background:linear-gradient(135deg,#059669,#065f46)">✓</div>'
        + '<div><div class="msg-bubble">Acknowledged.</div>'
        + '<div class="msg-bubble-time">You · just now</div></div>';
    body.appendChild(ackEl);
    body.scrollTop = body.scrollHeight;
};

window.escalateMessage = function() {
    if (!currentItem) return;
    if (!confirm('Escalate this message to the Depot Manager?')) return;
    const body = document.getElementById('threadBody');
    const el = document.createElement('div');
    el.className = 'msg-bubble-wrap system';
    el.innerHTML = '<div class="msg-bubble-avatar sys">⬆</div>'
        + '<div><div class="msg-bubble">Escalated to Depot Manager.</div>'
        + '<div class="msg-bubble-time">System · just now</div></div>';
    body.appendChild(el);
    body.scrollTop = body.scrollHeight;
};

window.archiveMessage = function() {
    if (!currentItem) return;
    currentItem.style.opacity = '.4';
    currentItem.style.pointerEvents = 'none';
    document.getElementById('threadBody').innerHTML = '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Message archived.</div>';
    document.getElementById('quickReplyBar').style.display = 'none';
};

/* ─── Refresh ───────────────────────────────────────────────────────── */
document.getElementById('btnRefresh').addEventListener('click', () => {
    location.reload();
});

/* ─── Helpers ───────────────────────────────────────────────────────── */
function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
function capitalise(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function formatRelTime(ts) {
    if (!ts) return '';
    const d = new Date(ts.replace(' ','T'));
    if (isNaN(d)) return ts;
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60)   return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
    return d.toLocaleDateString('en-GB',{day:'numeric',month:'short'});
}

/* ─── Auto-resize compose textarea ─────────────────────────────────── */
document.getElementById('messageBody').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

})();
</script>