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

$myId            = (int)($me['user_id'] ?? 0);
$myUserId        = (int)($my_user_id ?? $myId);
$dmConversations = $dm_conversations ?? [];

/*
 * Build a unified inbox: merge notifications ($recent) and DM conversations
 * ($dmConversations) into one list sorted newest-first by timestamp.
 * Each item gets a '_kind' key: 'notification' or 'direct_chat'.
 */
$inboxItems = [];
foreach ($recent as $n) {
    $n['_kind'] = 'notification';
    $n['_ts']   = $n['created_at'] ?? '';
    $inboxItems[] = $n;
}
foreach ($dmConversations as $c) {
    $c['_kind'] = 'direct_chat';
    $c['_ts']   = $c['last_time'] ?? '';
    $inboxItems[] = $c;
}
usort($inboxItems, fn($a, $b) => strcmp($b['_ts'], $a['_ts']));

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

/* SL Operations Categories (each has icon, label, priority hint, body template) */
$slCategories = [
    'schedule_change'    => ['icon'=>'📅', 'label'=>'Schedule Change',    'priority'=>'normal',   'text'=>'TIMETABLE NOTICE: Effective [DATE], the departure times for Route [NO] have changed. New schedule: [DETAILS]. All timekeepers please update your records accordingly.'],
    'route_deviation'    => ['icon'=>'🔀', 'label'=>'Route Deviation',    'priority'=>'urgent',   'text'=>'ROUTE DIVERSION: Due to [REASON — e.g., road closure / flooding / VIP movement] near [LOCATION], buses on Route [NO] will divert via [ALT ROUTE]. Return to normal route when clearance is given.'],
    'breakdown_alert'    => ['icon'=>'🔧', 'label'=>'Breakdown Alert',    'priority'=>'urgent',   'text'=>'BREAKDOWN: Bus [REG] has broken down at [LOCATION] on Route [NO]. Dispatch replacement bus immediately. Contact control room for towing arrangements.'],
    'strike_notice'      => ['icon'=>'✊', 'label'=>'Strike / Union Notice','priority'=>'critical', 'text'=>'UNION NOTICE: A trade union action has been called by [UNION NAME] effective [DATE/TIME]. Services on [ROUTES] may be affected. Follow NTC/SLTB directives. Report all developments to the Depot Manager immediately.'],
    'poya_schedule'      => ['icon'=>'🌕', 'label'=>'Poya Day Schedule',  'priority'=>'normal',   'text'=>'POYA DAY NOTICE: On [DATE] (Poya), all services will operate on the Public Holiday schedule. Reduced frequencies apply. All staff report by [TIME]. No overtime allowances without prior approval.'],
    'passenger_complaint'=> ['icon'=>'😠', 'label'=>'Passenger Complaint','priority'=>'normal',   'text'=>'PASSENGER COMPLAINT FORWARDED: A complaint has been received regarding Bus [REG] / Route [NO] on [DATE]. Issue: [DESCRIPTION]. Relevant driver/conductor should respond to the Depot Officer within 24 hours.'],
    'general_update'     => ['icon'=>'📢', 'label'=>'General Update',     'priority'=>'normal',   'text'=>'GENERAL NOTICE: [Your message here]'],
];

/* Legacy template list (kept for backward compat with existing JS) */
$templates = [
    ['id'=>'delay',           'icon'=>'⏱️', 'label'=>'Trip Delay',        'text'=>'DELAY ALERT: Bus [REG] on Route [NO] is delayed by approx. [MIN] minutes. Please adjust schedules accordingly.'],
    ['id'=>'breakdown',       'icon'=>'🔧', 'label'=>'Breakdown',          'text'=>'BREAKDOWN: Bus [REG] has broken down near [LOCATION] on Route [NO]. Dispatch replacement immediately.'],
    ['id'=>'override',        'icon'=>'🔄', 'label'=>'Schedule Override',   'text'=>'SCHEDULE OVERRIDE: Assignment for Trip [ID] has been modified. New departure: [TIME]. Driver/Conductor please confirm receipt.'],
    ['id'=>'maintenance',     'icon'=>'🛠️', 'label'=>'Maintenance Notice',  'text'=>'MAINTENANCE NOTICE: Bus [REG] is scheduled for maintenance on [DATE]. Ensure replacement is arranged.'],
    ['id'=>'headcount',       'icon'=>'📋', 'label'=>'Attendance Check',    'text'=>'ATTENDANCE CHECK: All staff please confirm availability for tomorrow [DATE] by [TIME].'],
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
.msg-item.unread {
    background: #fff7f2;
    border-left: 3px solid var(--maroon,#7f1d1d);
}
.msg-item.unread .msg-item-subject { font-weight:800; }
.msg-item.unread .msg-item-preview { color:#6b7280; font-weight:600; }
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
.msg-unread-badge {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:18px;
    padding:0 7px;
    border-radius:999px;
    background: var(--maroon,#7f1d1d);
    color:#fff;
    font-size:10px;
    font-weight:800;
    margin-top:4px;
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

/* category selector */
.msg-cat-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:4px; }
.msg-cat-btn {
    display:flex; align-items:center; gap:6px; padding:7px 9px;
    background:#fff; border:1.5px solid #e5e7eb; border-radius:9px;
    cursor:pointer; font-size:11px; font-weight:700; color:#374151;
    transition:all .15s; text-align:left;
}
.msg-cat-btn:hover { border-color:#a01c2e; background:#fffaf5; }
.msg-cat-btn.active { border-color:#7f1d1d; background:#fde8e8; color:#7f1d1d; }
.msg-cat-icon { font-size:14px; }
.msg-cat-none { color:#9ca3af; font-style:italic; font-size:11px; margin-top:3px; }

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
.msg-stats { display:flex; gap:8px; padding:10px 16px 0; flex-shrink:0; flex-wrap:wrap; }
.msg-stat {
    flex:1 1 180px; min-width:160px; background:#fff; border:1px solid #e9e3da; border-radius:10px;
    padding:8px 10px; text-align:center; position:relative;
}
.msg-stat-val {
    font-size:20px; font-weight:800; color:var(--maroon,#7f1d1d); line-height:1;
    display:inline-flex; align-items:flex-start; justify-content:center; gap:6px;
}
.msg-stat-badge {
    position:static;
    background:linear-gradient(135deg,#dc2626,#991b1b);
    color:#fff; border-radius:999px;
    min-width:20px; height:20px;
    padding:0 6px;
    font-size:11px; font-weight:900;
    display:grid; place-items:center;
    box-shadow:0 2px 8px rgba(220,38,38,.4);
}
.msg-stat-label { font-size:10px; color:#9ca3af; margin-top:2px; }

/* responsive */
@media(max-width:1100px){
    .msg-body { grid-template-columns:220px 1fr 260px; }
}
@media(max-width:860px){
    .msg-body { grid-template-columns:1fr; grid-template-rows:auto 1fr auto; }
    .msg-inbox, .msg-compose-panel { max-height:260px; }
    .msg-stat { flex:1 1 calc(50% - 8px); min-width:140px; }
}

/* ── chat bubble mode ────────────────────────────────────────────────── */
.do-chat-body { display:flex;flex-direction:column;gap:10px;padding:14px 16px; }
.do-bubble-row { display:flex;align-items:flex-end;gap:8px; }
.do-bubble-row.mine  { flex-direction:row-reverse; }
.do-bubble-avatar { width:32px;height:32px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-size:13px;font-weight:700;color:#fff; }
.do-bubble-row.mine  .do-bubble-avatar { background:linear-gradient(135deg,#b91c1c,#7f1d1d); }
.do-bubble-row.theirs .do-bubble-avatar { background:#6b7280; }
.do-bubble-text { max-width:70%; }
.do-bubble-msg { padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word; }
.do-bubble-row.mine   .do-bubble-msg { border-radius:16px 4px 16px 16px;background:linear-gradient(135deg,#b91c1c,#7f1d1d);color:#fff; }
.do-bubble-row.theirs .do-bubble-msg { border-radius:4px 16px 16px 16px;background:#f3f4f6;color:#111827; }
.do-bubble-meta { font-size:10px;color:#9ca3af;margin-top:3px; }
.do-bubble-row.mine   .do-bubble-meta { text-align:right; }
.do-chat-empty { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#d1c4b0;gap:8px;font-size:13px; }
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
            $total  = count($inboxItems);
            $unread = count(array_filter($inboxItems, fn($n) =>
                $n['_kind'] === 'direct_chat'
                    ? (int)($n['unread_count'] ?? 0) > 0
                    : !($n['is_seen'] ?? $n['read_at'] ?? false)
            ));
            $alerts = count(array_filter($inboxItems, fn($n) => in_array($n['type'] ?? '', ['Delay','Alert','Breakdown'])));
        ?>
        <div class="msg-stat">
            <div class="msg-stat-val" id="statTotalVal"><?= $total ?></div>
            <div class="msg-stat-label">Total</div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-val" style="color:#d97706" id="statUnreadVal">
                <span id="statUnreadCount"><?= $unread ?></span>
                <?php if ($unread > 0): ?>
                    <span class="msg-stat-badge" id="statUnreadBadge"><?= min($unread, 99) ?></span>
                <?php else: ?>
                    <span class="msg-stat-badge" id="statUnreadBadge" style="display:none">0</span>
                <?php endif; ?>
            </div>
            <div class="msg-stat-label">Unread</div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-val" style="color:#dc2626" id="statAlertsVal"><?= $alerts ?></div>
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

                <?php if (empty($inboxItems)): ?>
                    <div class="msg-empty">
                        <div style="font-size:32px;margin-bottom:8px">📭</div>
                        No messages yet.<br>Send the first one!
                    </div>
                <?php else: ?>
                    <?php foreach ($inboxItems as $item):
                        if ($item['_kind'] === 'direct_chat'):
                            $pid        = (int)($item['partner_id'] ?? 0);
                            $pname      = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
                            if ($pname === '') $pname = 'Timekeeper';
                            $pRole      = $item['role'] ?? '';
                            $lastMsg    = mb_strimwidth($item['last_message'] ?? '', 0, 60, '…');
                            $convUnread = (int)($item['unread_count'] ?? 0);
                            $convTime   = '';
                            if (!empty($item['last_time'])) {
                                $ts   = strtotime($item['last_time']);
                                $diff = time() - $ts;
                                if ($diff < 0)          $convTime = date('d M, H:i', $ts);
                                elseif ($diff < 3600)   $convTime = round($diff/60).'m ago';
                                elseif ($diff < 86400)  $convTime = round($diff/3600).'h ago';
                                else                    $convTime = date('d M', $ts);
                            }
                            $roleLabel = str_contains($pRole,'SLTB') ? 'SLTB TK' : (str_contains($pRole,'Private') ? 'Pvt TK' : $pRole);
                    ?>
                    <div class="msg-item <?= $convUnread > 0 ? 'unread' : '' ?>"
                         data-id="dm_<?= $pid ?>"
                         data-type="direct_chat"
                         data-partner-id="<?= $pid ?>"
                         data-unread="<?= $convUnread > 0 ? '1' : '0' ?>"
                         data-text="<?= htmlspecialchars($item['last_message'] ?? '') ?>"
                         data-name="<?= htmlspecialchars($pname) ?>"
                         data-time="<?= htmlspecialchars($item['last_time'] ?? '') ?>"
                         onclick="openThread(this)">
                        <div class="msg-item-avatar" style="background:linear-gradient(135deg,#be185d,#9d174d)">💬</div>
                        <div class="msg-item-meta">
                            <div class="msg-item-subject"><?= htmlspecialchars($pname) ?></div>
                            <div class="msg-item-preview"><?= htmlspecialchars($lastMsg) ?></div>
                            <div class="msg-item-time" data-prefix="<?= htmlspecialchars($roleLabel) ?>" data-ts="<?= htmlspecialchars($item['last_time'] ?? '') ?>"><?= $roleLabel ?> · <?= $convTime ?></div>
                        </div>
                        <?php if ($convUnread > 0): ?>
                            <div class="msg-unread-dot"></div>
                            <div class="msg-unread-badge"><?= $convUnread ?></div>
                        <?php endif; ?>
                    </div>
                    <?php else:
                            $nid      = (int)($item['notification_id'] ?? $item['id'] ?? 0);
                            $isUnread = !($item['is_seen'] ?? $item['read_at'] ?? false);
                            $type     = $item['type'] ?? 'Message';
                            $text     = $item['message'] ?? '';
                            $preview  = mb_strimwidth($text, 0, 60, '…');
                            $name     = msgDisplayName($item);
                            $avatarCls = in_array($type,['Delay','Alert','Breakdown']) ? 'alert' : (str_contains(strtolower($name),'system') ? 'sys' : '');
                            $timeStr  = '';
                            if (!empty($item['created_at'])) {
                                $ts   = strtotime($item['created_at']);
                                $diff = time() - $ts;
                                if ($diff < 0)         $timeStr = date('d M, H:i', $ts);
                                elseif ($diff < 3600)  $timeStr = round($diff/60).'m ago';
                                elseif ($diff < 86400) $timeStr = round($diff/3600).'h ago';
                                else                   $timeStr = date('d M', $ts);
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
                         data-time="<?= htmlspecialchars($item['created_at'] ?? '') ?>"
                         onclick="openThread(this)">
                        <div class="msg-item-avatar <?= $avatarCls ?>"><?= $typeIcon ?></div>
                        <div class="msg-item-meta">
                            <div class="msg-item-subject"><?= htmlspecialchars($name) ?></div>
                            <div class="msg-item-preview"><?= htmlspecialchars($preview) ?></div>
                            <div class="msg-item-time" data-prefix="<?= htmlspecialchars($type) ?>" data-ts="<?= htmlspecialchars($item['created_at'] ?? '') ?>"><?= htmlspecialchars($type) ?> · <?= $timeStr ?></div>
                        </div>
                        <?php if ($isUnread): ?>
                            <div class="msg-unread-dot"></div>
                            <div class="msg-unread-badge">Unread</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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

            <div id="chatBar" style="display:none;padding:12px 16px;border-top:1px solid #e9e3da;background:#fff;flex-shrink:0;">
                <div style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Reply</div>
                <div class="msg-compose-inner">
                    <textarea class="msg-compose-textarea" id="chatBarInput" rows="2" placeholder="Type a reply to timekeeper…"></textarea>
                    <button class="msg-send-btn" id="chatBarSend">
                        <span>Send</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
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
                        <button type="button" class="msg-scope-tab" data-scope="bus">By Bus</button>
                        <button type="button" class="msg-scope-tab" data-scope="route">By Route</button>
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
                            <?php foreach ($roles as $roleName): ?>
                            <label class="msg-recipient-item" style="border:1px solid #e9e3da;border-radius:8px;cursor:pointer;">
                                <input type="checkbox" name="to[]" value="<?= htmlspecialchars($roleName) ?>" class="role-cb" onchange="updateRoleTags()">
                                <span class="ri-name"><?= htmlspecialchars($roleName) ?></span>
                                <span style="font-size:10px;color:#9ca3af"><?= count(array_filter($staff, fn($s) => $s['role'] === $roleName)) ?> staff</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="msg-selected-tags" id="selectedRoleTags"></div>
                    </div>

                    <!-- By Bus -->
                    <div class="msg-scope-panel" id="scope-bus">
                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">
                            <?php if (!empty($buses)): ?>
                                <?php foreach ($buses as $b): 
                                    $bid = (string)($b['bus_id'] ?? $b['reg_no'] ?? '');
                                    $breg = htmlspecialchars((string)($b['bus_registration_no'] ?? $b['reg_no'] ?? ''));
                                ?>
                                <label class="msg-recipient-item" style="border:1px solid #e9e3da;border-radius:8px;cursor:pointer;">
                                    <input type="checkbox" name="to[]" value="<?= htmlspecialchars($bid) ?>" class="bus-cb" onchange="updateBusTags()">
                                    <span class="ri-name">🚌 <?= $breg ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:10px;color:#9ca3af;font-size:12px;">No buses assigned to your depot.</div>
                            <?php endif; ?>
                        </div>
                        <div class="msg-selected-tags" id="selectedBusTags"></div>
                    </div>

                    <!-- By Route -->
                    <div class="msg-scope-panel" id="scope-route">
                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">
                            <?php if (!empty($routes)): ?>
                                <?php foreach ($routes as $r):
                                    $rid = (int)($r['route_id'] ?? 0);
                                    $rname = htmlspecialchars($r['route_name'] ?? '');
                                ?>
                                <label class="msg-recipient-item" style="border:1px solid #e9e3da;border-radius:8px;cursor:pointer;">
                                    <input type="checkbox" name="to[]" value="<?= $rid ?>" class="route-cb" onchange="updateRouteTags()">
                                    <span class="ri-name">🛣️ <?= $rname ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:10px;color:#9ca3af;font-size:12px;">No routes assigned to your depot.</div>
                            <?php endif; ?>
                        </div>
                        <div class="msg-selected-tags" id="selectedRouteTags"></div>
                    </div>

                    <!-- All Depot -->
                    <div class="msg-scope-panel" id="scope-depot">
                        <div style="margin-top:10px;padding:10px;background:#fef3c7;border:1px solid #fde047;border-radius:8px;font-size:12px;color:#78350f;font-weight:600;">
                            ⚠️ This will send to all <?= count($staff) ?> staff in your depot. Use for critical announcements only.
                        </div>
                        <input type="hidden" name="all_depot" id="allDepotInput" value="0">
                    </div>
                </div>

                <!-- CATEGORY (SL Operations) -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">🏷️ Category</div>
                    <input type="hidden" name="category" id="categoryInput" value="">
                    <div class="msg-cat-grid" id="categoryGrid">
                        <?php foreach ($slCategories as $catKey => $cat): ?>
                        <button type="button" class="msg-cat-btn"
                            data-cat="<?= htmlspecialchars($catKey) ?>"
                            data-template="<?= htmlspecialchars($cat['text']) ?>"
                            data-priority="<?= htmlspecialchars($cat['priority']) ?>"
                            onclick="selectCategory(this)">
                            <span class="msg-cat-icon"><?= $cat['icon'] ?></span>
                            <span><?= htmlspecialchars($cat['label']) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="msg-cat-none" id="catNoneHint">No category selected — or pick one above to pre-fill a template</div>
                </div>

                <!-- QUICK TEMPLATES (legacy) -->
                <div class="msg-cp-section">
                    <div class="msg-cp-label">📋 Quick Templates</div>
                    <div class="msg-tpl-grid">
                        <?php foreach ($templates as $tpl): ?>
                        <button type="button" class="msg-tpl-btn"
                            data-template-id="<?= htmlspecialchars($tpl['id']) ?>"
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

/* ─── Identity ───────────────────────────────────────────────────────── */
const myUserId = <?= (int)($my_user_id ?? $myId) ?>;

/* ─── Chat state ─────────────────────────────────────────────────────── */
let currentChatPartnerId = null;
let chatLastMsgId        = 0;
let chatPollTimer        = null;
let lastMessageId = 0;

// Seed lastMessageId from already-rendered items so we only fetch truly new ones
document.querySelectorAll('#msgList .msg-item[data-id]').forEach(el => {
    const id = parseInt(el.dataset.id) || 0;
    if (id > lastMessageId) lastMessageId = id;
});

function addMessageToInbox(msg) {
    const msgId   = msg.id ?? msg.notification_id ?? 0;
    const msgType = (msg.type ?? 'Message').toLowerCase();
    const msgText = msg.message ?? '';
    const msgName = msg.full_name ?? msg.from ?? 'System';
    const msgTime = msg.created_at ?? '';

    const msgList = document.getElementById('msgList');
    if (!msgList) return;

    // Skip if already rendered
    if (document.querySelector(`[data-id="${msgId}"]`)) return;

    // Remove empty-state placeholder if present
    const empty = msgList.querySelector('.msg-empty');
    if (empty) empty.remove();

    const typeIcon = {delay:'⏱️',breakdown:'🔧',alert:'🚨',timetable:'📅',message:'💬'}[msgType] || '📩';
    const avatarCls = ['delay','alert','breakdown'].includes(msgType) ? 'alert' : '';

    const item = document.createElement('div');
    item.className = 'msg-item unread';
    item.dataset.id     = msgId;
    item.dataset.type   = msgType;
    item.dataset.unread = '1';
    item.dataset.name   = msgName;
    item.dataset.text   = msgText;
    item.dataset.time   = msgTime;
    item.onclick = function() { openThread(this); };

    item.innerHTML =
        `<div class="msg-item-avatar ${avatarCls}">${typeIcon}</div>` +
        `<div class="msg-item-meta">` +
            `<div class="msg-item-subject">${esc(msgName)}</div>` +
            `<div class="msg-item-preview">${esc(msgText.substring(0,60))}…</div>` +
            `<div class="msg-item-time" data-ts="${esc(msgTime)}">just now</div>` +
        `</div>` +
        `<div class="msg-unread-dot"></div>` +
        `<div class="msg-unread-badge">Unread</div>`;

    msgList.insertBefore(item, msgList.firstChild);
    if (lastMessageId < msgId) lastMessageId = msgId;
    updateStatsBadges();
}

function pollMessages() {
    fetch('/O/messages?action=poll&since_id=' + lastMessageId)
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(msgs => {
            if (!Array.isArray(msgs)) return;
            // Oldest-first so insertBefore keeps newest at top
            msgs.slice().reverse().forEach(addMessageToInbox);
        })
        .catch(() => { /* silent — will retry on next interval */ });
}

// Poll immediately on load (catches messages that arrived between PHP render and JS init)
setTimeout(pollMessages, 2000);
// Then poll every 20 seconds
setInterval(pollMessages, 20000);

/* ─── Poll DM conversations (TK chat items in inbox) ───────────────── */
function pollDmConversations() {
    fetch('/O/messages?action=chat_convs')
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(convs => {
            if (!Array.isArray(convs)) return;
            const msgList = document.getElementById('msgList');
            if (!msgList) return;
            convs.forEach(conv => {
                const pid    = parseInt(conv.partner_id || 0);
                if (!pid) return;
                const itemId = 'dm_' + pid;
                let item     = msgList.querySelector('[data-id="' + itemId + '"]');
                const name   = ((conv.first_name || '') + ' ' + (conv.last_name || '')).trim() || 'Timekeeper';
                const role   = conv.role || '';
                const roleLabel = role.includes('SLTB') ? 'SLTB TK' : (role.includes('Private') ? 'Pvt TK' : role);
                const preview = (conv.last_message || '').substring(0, 60);
                const unread  = parseInt(conv.unread_count || 0);

                if (!item) {
                    // New conversation — add at top of inbox (before first non-DM item)
                    const empty = msgList.querySelector('.msg-empty');
                    if (empty) empty.remove();
                    item = document.createElement('div');
                    item.dataset.id        = itemId;
                    item.dataset.type      = 'direct_chat';
                    item.dataset.partnerId = String(pid);
                    item.dataset.name      = name;
                    item.onclick = function() { openThread(this); };
                    // Insert before the first notification item (after any existing DM items)
                    const firstNotif = msgList.querySelector('.msg-item:not([data-type="direct_chat"])');
                    if (firstNotif) msgList.insertBefore(item, firstNotif);
                    else msgList.appendChild(item);
                }

                // Update content and unread state
                item.dataset.text   = conv.last_message || '';
                item.dataset.unread = unread > 0 ? '1' : '0';
                item.classList.toggle('unread', unread > 0);
                item.innerHTML =
                    '<div class="msg-item-avatar" style="background:linear-gradient(135deg,#be185d,#9d174d)">💬</div>' +
                    '<div class="msg-item-meta">' +
                        '<div class="msg-item-subject">' + esc(name) + '</div>' +
                        '<div class="msg-item-preview">' + esc(preview) + '</div>' +
                        '<div class="msg-item-time" data-prefix="' + esc(roleLabel) + '" data-ts="' + esc(conv.last_time || '') + '">' + esc(roleLabel) + ' · ' + esc(formatRelTime(conv.last_time || '')) + '</div>' +
                    '</div>' +
                    (unread > 0 ? '<div class="msg-unread-dot"></div><div class="msg-unread-badge">' + unread + '</div>' : '');
                item.onclick = function() { openThread(this); };
            });
            updateStatsBadges();
        })
        .catch(() => {});
}
setTimeout(pollDmConversations, 3000);
setInterval(pollDmConversations, 15000);

// Update stat badges (unread, alerts, etc)
function updateStatsBadges() {
    const msgItems = document.querySelectorAll('#msgList .msg-item');
    const total = msgItems.length;
    const unread = Array.from(msgItems).filter(el => el.classList.contains('unread')).length;
    const alerts = Array.from(msgItems).filter(el => ['delay','alert','breakdown'].includes(el.dataset.type)).length;

    const totalEl      = document.getElementById('statTotalVal');
    const unreadCountEl= document.getElementById('statUnreadCount');
    const unreadBadgeEl= document.getElementById('statUnreadBadge');
    const alertsEl     = document.getElementById('statAlertsVal');

    if (totalEl)       totalEl.textContent = String(total);
    if (unreadCountEl) unreadCountEl.textContent = String(unread);
    if (alertsEl)      alertsEl.textContent = String(alerts);

    if (unreadBadgeEl) {
        if (unread > 0) {
            unreadBadgeEl.style.display = '';
            unreadBadgeEl.textContent = String(Math.min(unread, 99));
        } else {
            unreadBadgeEl.style.display = 'none';
            unreadBadgeEl.textContent = '0';
        }
    }
}

// Utility: escape HTML for safe innerHTML injection
function esc(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

/* ─── Inbox filter ──────────────────────────────────────────────────── */
const filterBtns = document.querySelectorAll('.msg-inbox-filter .msg-filter-btn');
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
    const msgItems = document.querySelectorAll('#msgList .msg-item');
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
    // Direct chat thread (TK ↔ DO)
    if (el.dataset.type === 'direct_chat') {
        openChatThread(parseInt(el.dataset.partnerId), el);
        return;
    }

    // Stop any active chat poll when switching back to notifications
    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
    currentChatPartnerId = null;
    document.getElementById('chatBar').style.display = 'none';

    if (currentItem) currentItem.classList.remove('active');
    el.classList.add('active');
    const unreadDot = el.querySelector('.msg-unread-dot');
    if (unreadDot) unreadDot.remove();
    const unreadBadge = el.querySelector('.msg-unread-badge');
    if (unreadBadge) unreadBadge.remove();
    el.classList.remove('unread');
    el.dataset.unread = '0';
    updateStatsBadges();
    currentItem = el;

    const name  = el.dataset.name  || 'Unknown';
    const text  = el.dataset.text  || '';
    const time  = el.dataset.time  || '';
    const type  = el.dataset.type  || 'Message';
    const init  = name.charAt(0).toUpperCase();
    const nid   = el.dataset.id;

    // Store current message ID for quick actions
    currentMessageId = parseInt(nid) || null;

    document.getElementById('threadTitle').textContent = name;
    const threadSubEl = document.getElementById('threadSub');
    threadSubEl.dataset.prefix = type;
    threadSubEl.dataset.ts     = time;
    threadSubEl.textContent    = type + (time ? ' · ' + formatRelTime(time) : '');

    const statEl = document.getElementById('threadStatus');
    statEl.style.display = '';
    statEl.textContent = capitalise(type);

    const body = document.getElementById('threadBody');
    body.innerHTML = buildBubble(init, name, text, time, type);

    // Mark read via background fetch (best-effort)
    if (nid) {
        fetch('/O/messages?action=read&id=' + nid, { method:'POST' }).catch(()=>{});
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
            <div class="msg-bubble-time" data-prefix="${esc(name)}" data-ts="${esc(time)}">${esc(name)} · ${timeStr}</div>
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

/* ─── Role scope → tags ───────────────────────────────────────────────── */
const staffByRole = <?php
    $jsRoles = [];
    foreach ($staff as $m) {
        $r = $m['role'] ?? 'Other';
        if (!isset($jsRoles[$r])) $jsRoles[$r] = [];
        $jsRoles[$r][] = (int)($m['user_id'] ?? 0);
    }
    echo json_encode($jsRoles, JSON_UNESCAPED_UNICODE);
?>;

window.updateRoleTags = function() {
    const checked = document.querySelectorAll('.role-cb:checked');
    const tags = document.getElementById('selectedRoleTags');
    tags.innerHTML = '';
    checked.forEach(cb => {
        const tag = document.createElement('span');
        tag.className = 'msg-tag';
        tag.innerHTML = esc(cb.value) + '<span class="msg-tag-rm" onclick="removeRoleTag(\'' + esc(cb.value) + '\')">×</span>';
        tags.appendChild(tag);
    });
};

window.removeRoleTag = function(role) {
    const cb = document.querySelector('.role-cb[value="' + role + '"]');
    if (cb) { cb.checked = false; updateRoleTags(); }
};

/* ─── Bus scope → tags ──────────────────────────────────────────────── */
const busesByKey = <?php 
    echo json_encode(array_map(fn($b) => ['id' => (string)($b['bus_id'] ?? $b['reg_no'] ?? ''), 'reg' => (string)($b['bus_registration_no'] ?? $b['reg_no'] ?? '')], $buses ?? []), JSON_UNESCAPED_UNICODE);
?>;

window.updateBusTags = function() {
    const checked = document.querySelectorAll('.bus-cb:checked');
    const tags = document.getElementById('selectedBusTags');
    tags.innerHTML = '';
    checked.forEach(cb => {
        const bus = busesByKey.find(b => String(b.id) === String(cb.value));
        const label = bus ? bus.reg : 'Bus ' + cb.value;
        const tag = document.createElement('span');
        tag.className = 'msg-tag';
        tag.innerHTML = '🚌 ' + esc(label) + '<span class="msg-tag-rm" data-bus-id="' + esc(String(cb.value)) + '">×</span>';
        tag.querySelector('.msg-tag-rm')?.addEventListener('click', function() {
            removeBusTag(this.getAttribute('data-bus-id') || '');
        });
        tags.appendChild(tag);
    });
};

window.removeBusTag = function(busId) {
    document.querySelectorAll('.bus-cb').forEach(cb => {
        if (String(cb.value) === String(busId)) cb.checked = false;
    });
    updateBusTags();
};

/* ─── Route scope → tags ────────────────────────────────────────────── */
const routesByKey = <?php 
    echo json_encode(array_map(fn($r) => ['id' => (int)($r['route_id'] ?? 0), 'name' => $r['route_name'] ?? ''], $routes ?? []), JSON_UNESCAPED_UNICODE);
?>;

window.updateRouteTags = function() {
    const checked = document.querySelectorAll('.route-cb:checked');
    const tags = document.getElementById('selectedRouteTags');
    tags.innerHTML = '';
    checked.forEach(cb => {
        const route = routesByKey.find(r => r.id === parseInt(cb.value));
        const label = route ? route.name : 'Route ' + cb.value;
        const tag = document.createElement('span');
        tag.className = 'msg-tag';
        tag.innerHTML = '🛣️ ' + esc(label) + '<span class="msg-tag-rm" onclick="removeRouteTag(' + cb.value + ')">×</span>';
        tags.appendChild(tag);
    });
};

window.removeRouteTag = function(routeId) {
    const cb = document.querySelector('.route-cb[value="' + routeId + '"]');
    if (cb) { cb.checked = false; updateRouteTags(); }
};

/* ─── Templates ─────────────────────────────────────────────────────── */
window.applyTemplate = function(btn) {
    const templateId = btn.dataset.templateId || '';
    let text = btn.dataset.template || '';

    const firstChecked = (selector) => {
        const cb = document.querySelector(selector);
        return cb ? cb.value : '';
    };
    const routeLabelById = (routeId) => {
        if (!routeId) return '';
        const route = routesByKey.find(r => r.id === parseInt(routeId, 10));
        return route ? (route.name || ('Route ' + routeId)) : ('Route ' + routeId);
    };
    const busRegById = (busId) => {
        if (!busId) return '';
        const bus = busesByKey.find(b => String(b.id) === String(busId));
        return bus ? (bus.reg || ('Bus ' + busId)) : ('Bus ' + busId);
    };
    const pad2 = (n) => String(n).padStart(2, '0');
    const formatDate = (d) => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
    const formatTime = (d) => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
    const replaceToken = (token, value) => {
        text = text.split('[' + token + ']').join(value);
    };
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const selectedBus = busRegById(firstChecked('.bus-cb:checked'));
    const selectedRoute = routeLabelById(firstChecked('.route-cb:checked'));

    const reg = selectedBus || '[REG]';
    const route = selectedRoute || '[NO]';
    const min = '[MIN]';
    const location = '[LOCATION]';
    const tripId = '[ID]';

    if (text.includes('[REG]')) replaceToken('REG', reg);
    if (text.includes('[NO]')) replaceToken('NO', route);
    if (text.includes('[MIN]')) replaceToken('MIN', min);
    if (text.includes('[LOCATION]')) replaceToken('LOCATION', location);
    if (text.includes('[ID]')) replaceToken('ID', tripId);
    if (text.includes('[TIME]')) replaceToken('TIME', formatTime(now));
    if (text.includes('[DATE]')) {
        const dateVal = templateId === 'headcount' ? formatDate(tomorrow) : formatDate(now);
        replaceToken('DATE', dateVal);
    }

    const defaults = {
        delay: { priority: 'urgent' },
        breakdown: { priority: 'critical' },
        override: { priority: 'urgent' },
        maintenance: { priority: 'normal' },
        headcount: { priority: 'normal', scope: 'depot' },
    };
    const cfg = defaults[templateId] || null;
    if (cfg && cfg.scope) {
        const tab = document.querySelector('.msg-scope-tab[data-scope="' + cfg.scope + '"]');
        if (tab) tab.click();
    }
    if (cfg && cfg.priority) {
        const pill = document.querySelector('.msg-priority-pill[data-priority="' + cfg.priority + '"]');
        if (pill && typeof window.setPriority === 'function') {
            window.setPriority(pill, cfg.priority);
        }
    }

    const body = document.getElementById('messageBody');
    body.value = text;
    updateCharCount(body);
    body.focus();
};

/* ─── Category selector ─────────────────────────────────────────────── */
window.selectCategory = function(btn) {
    const catVal = btn.dataset.cat;
    const catInput = document.getElementById('categoryInput');
    const hint = document.getElementById('catNoneHint');

    // Toggle: clicking same category deselects
    const alreadyActive = btn.classList.contains('active');
    document.querySelectorAll('.msg-cat-btn').forEach(b => b.classList.remove('active'));

    if (alreadyActive) {
        catInput.value = '';
        hint.style.display = '';
        return;
    }

    btn.classList.add('active');
    catInput.value = catVal;
    hint.style.display = 'none';

    // Apply template text
    const tpl = btn.dataset.template || '';
    if (tpl) {
        const body = document.getElementById('messageBody');
        body.value = tpl;
        updateCharCount(body);
        body.focus();
    }

    // Apply suggested priority
    const pri = btn.dataset.priority || 'normal';
    const pill = document.querySelector('.msg-priority-pill[data-priority="' + pri + '"]');
    if (pill && typeof window.setPriority === 'function') {
        window.setPriority(pill, pri);
    }
};

document.querySelectorAll('.msg-tpl-btn').forEach((btn) => {
    btn.addEventListener('click', function() {
        window.applyTemplate(this);
    });
});

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
        const anyRole = document.querySelector('.role-cb:checked');
        if (!anyRole) { alert('Please select at least one role.'); return; }
    }
    if (scope === 'bus') {
        const anyBus = document.querySelector('.bus-cb:checked');
        if (!anyBus) { alert('Please select at least one bus.'); return; }
    }
    if (scope === 'route') {
        const anyRoute = document.querySelector('.route-cb:checked');
        if (!anyRoute) { alert('Please select at least one route.'); return; }
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

/* ─── Chat bar send button ──────────────────────────────────────────── */
document.getElementById('chatBarSend').addEventListener('click', function() {
    const text = document.getElementById('chatBarInput').value.trim();
    if (text) sendChatReply(text);
});
document.getElementById('chatBarInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); const text = this.value.trim(); if (text) sendChatReply(text); }
});

/* ─── Refresh ───────────────────────────────────────────────────────── */
document.getElementById('btnRefresh').addEventListener('click', () => {
    location.reload();
});

/* ─── Helpers ───────────────────────────────────────────────────────── */
function capitalise(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function formatRelTime(ts) {
    if (!ts) return '';
    const d = new Date(ts.replace(' ','T'));
    if (isNaN(d)) return ts;
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 0)    return d.toLocaleString('en-GB',{day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
    if (diff < 60)   return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
    return d.toLocaleDateString('en-GB',{day:'numeric',month:'short'});
}

function tickRelTimes() {
    document.querySelectorAll('[data-ts]').forEach(el => {
        const ts  = el.dataset.ts  || '';
        const pre = el.dataset.prefix || '';
        if (!ts) return;
        const rel = formatRelTime(ts);
        el.textContent = pre ? pre + ' · ' + rel : rel;
    });
}
setInterval(tickRelTimes, 30000);

let currentMessageId = null;

/* ─── Auto-resize compose textarea ─────────────────────────────────── */
document.getElementById('messageBody').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

/* ─── Direct Chat: open a TK conversation thread ───────────────────── */
window.openChatThread = function(partnerId, el) {
    if (currentItem) currentItem.classList.remove('active');
    el.classList.add('active');
    el.classList.remove('unread');
    el.dataset.unread = '0';
    const dot = el.querySelector('.msg-unread-dot');
    if (dot) dot.remove();
    const badge = el.querySelector('.msg-unread-badge');
    if (badge) badge.remove();
    updateStatsBadges();
    currentItem = el;
    currentChatPartnerId = partnerId;
    currentMessageId = null;

    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
    chatLastMsgId = 0;

    const name = el.dataset.name || 'Timekeeper';
    document.getElementById('threadTitle').textContent = name;
    document.getElementById('threadSub').textContent   = 'Direct Chat · Loading…';
    const statEl = document.getElementById('threadStatus');
    statEl.style.display = '';
    statEl.textContent = 'Chat';

    const body = document.getElementById('threadBody');
    body.innerHTML = '<div class="do-chat-empty"><div style="font-size:36px">💬</div><div>Loading conversation…</div></div>';

    // Show the chat compose bar
    document.getElementById('chatBar').style.display = '';

    fetch('/O/messages?action=chat_load&with=' + partnerId)
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(msgs => {
            document.getElementById('threadSub').textContent = 'Direct Chat · ' + name;
            body.innerHTML = '';
            if (!msgs || msgs.length === 0) {
                body.innerHTML = '<div class="do-chat-empty"><div style="font-size:36px">💬</div><div>No messages yet — send the first one!</div></div>';
            } else {
                msgs.forEach(m => appendDoChatBubble(m, body));
                body.scrollTop = body.scrollHeight;
                chatLastMsgId = Math.max(...msgs.map(m => parseInt(m.id) || 0));
            }
            chatPollTimer = setInterval(pollChatMessages, 10000);
        })
        .catch(() => {
            body.innerHTML = '<div class="do-chat-empty" style="color:#dc2626">Failed to load conversation.</div>';
        });
};

function appendDoChatBubble(m, bodyEl) {
    if (!m || !m.id) return;
    bodyEl = bodyEl || document.getElementById('threadBody');
    if (!bodyEl) return;
    if (bodyEl.querySelector('[data-do-dm-id="' + m.id + '"]')) return;

    // Remove empty placeholder
    const empty = bodyEl.querySelector('.do-chat-empty');
    if (empty) empty.remove();

    const isMe    = (parseInt(m.from_user_id) === myUserId);
    const fname   = (m.first_name || '') + (m.last_name ? ' ' + m.last_name : '');
    const initial = (isMe ? 'D' : (fname || 'T')).charAt(0).toUpperCase();
    const timeStr = formatRelTime(m.created_at || '');

    const row = document.createElement('div');
    row.dataset.doDmId = m.id;
    row.className = 'do-bubble-row ' + (isMe ? 'mine' : 'theirs');

    row.innerHTML =
        '<div class="do-bubble-avatar">' + esc(initial) + '</div>' +
        '<div class="do-bubble-text">' +
            '<div class="do-bubble-msg">' + esc(m.message || '') + '</div>' +
            '<div class="do-bubble-meta" data-prefix="' + esc(isMe ? 'You' : fname) + '" data-ts="' + esc(m.created_at || '') + '">' + esc(isMe ? 'You' : fname) + ' · ' + esc(timeStr) + '</div>' +
        '</div>';

    bodyEl.appendChild(row);
    bodyEl.scrollTop = bodyEl.scrollHeight;
}

function pollChatMessages() {
    if (!currentChatPartnerId) return;
    fetch('/O/messages?action=chat_poll&since_id=' + chatLastMsgId)
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            if (!Array.isArray(data)) return;
            const body = document.getElementById('threadBody');
            data.forEach(m => {
                const pid = parseInt(m.from_user_id) === myUserId
                    ? parseInt(m.to_user_id)
                    : parseInt(m.from_user_id);
                if (pid === currentChatPartnerId) {
                    appendDoChatBubble(m, body);
                    if (parseInt(m.id) > chatLastMsgId) chatLastMsgId = parseInt(m.id);
                }
            });
        })
        .catch(() => {});
}

function sendChatReply(text) {
    if (!currentChatPartnerId || !text) return;
    const ta  = document.getElementById('chatBarInput');
    const btn = document.getElementById('chatBarSend');
    btn.disabled = true;
    const fd = new FormData();
    fd.append('to_user_id', String(currentChatPartnerId));
    fd.append('message', text);
    fetch('/O/messages?action=chat_send', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res.ok) {
                ta.value = '';
                // Append own bubble immediately
                const fakeMsg = {
                    id:           res.id,
                    from_user_id: myUserId,
                    to_user_id:   currentChatPartnerId,
                    message:      text,
                    created_at:   new Date().toISOString().replace('T',' ').slice(0,19),
                    first_name:   '',
                    last_name:    '',
                };
                appendDoChatBubble(fakeMsg, document.getElementById('threadBody'));
                if (res.id > chatLastMsgId) chatLastMsgId = res.id;
            }
        })
        .catch(() => { btn.disabled = false; });
}

})();
</script>