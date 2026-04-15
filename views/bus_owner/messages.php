<?php
$sent        = $sent ?? [];
$timekeepers = $timekeepers ?? [];
$timekeeperCount = is_array($timekeepers) ? count($timekeepers) : (int)$timekeepers;
$msg         = $msg ?? null;

$ownerCategories = [
    ['key'=>'schedule_change',  'icon'=>'📅', 'label'=>'Schedule Change',  'priority'=>'normal',  'tpl'=>'Please note there is a schedule change for [route/date]. Please update your records accordingly.'],
    ['key'=>'breakdown_alert',  'icon'=>'🔧', 'label'=>'Breakdown Alert',  'priority'=>'urgent',  'tpl'=>'Bus [number] has broken down at [location]. Please arrange alternative coverage immediately.'],
    ['key'=>'driver_notice',    'icon'=>'🧑‍✈️', 'label'=>'Driver Notice',   'priority'=>'normal',  'tpl'=>'Important notice regarding driver duties for [date/route]. Please follow updated instructions.'],
    ['key'=>'poya_schedule',    'icon'=>'🌕', 'label'=>'Poya Day Schedule', 'priority'=>'normal',  'tpl'=>'Special service schedule applies for the upcoming Poya day. Staff should refer to the updated timetable.'],
    ['key'=>'passenger_complaint','icon'=>'😠','label'=>'Passenger Complaint','priority'=>'urgent','tpl'=>'A passenger complaint was received for [route/bus]. Please investigate and report back.'],
    ['key'=>'general_update',   'icon'=>'📢', 'label'=>'General Update',   'priority'=>'normal',  'tpl'=>''],
];
?>
<style>
:root { --oblu:#1e3a5f; --gold:#f3b944; }
.bmsg-page { display:flex; flex-direction:column; gap:20px; }

/* Hero */
.bmsg-hero { background:linear-gradient(135deg,var(--oblu) 0%,#2d5fa8 100%); border-bottom:4px solid var(--gold); border-radius:14px; color:#fff; padding:20px 24px; }
.bmsg-hero h1 { margin:0 0 4px; font-size:1.3rem; font-weight:800; }
.bmsg-hero p  { margin:0; font-size:.86rem; opacity:.9; }
.bmsg-hero-meta { display:flex; gap:10px; margin-top:10px; flex-wrap:wrap; }
.bmsg-hero-chip { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:5px 14px; font-size:.78rem; font-weight:700; }

/* Flash */
.bmsg-flash { border-radius:10px; padding:10px 14px; font-size:.86rem; font-weight:700; }
.bmsg-flash.ok  { background:#dcfce7; color:#14532d; border:1px solid #86efac; }
.bmsg-flash.err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

/* Compose card */
.bmsg-compose { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.07); overflow:hidden; }
.bmsg-compose-head { background:var(--oblu); color:#fff; padding:14px 18px; font-size:1rem; font-weight:800; display:flex; align-items:center; gap:8px; }
.bmsg-compose-body { padding:20px; display:flex; flex-direction:column; gap:16px; }

/* Category grid */
.bmsg-cat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:10px; }
.bmsg-cat-btn  { display:flex; flex-direction:column; align-items:center; gap:5px; border:2px solid #e5e7eb; border-radius:10px; padding:10px 8px; font-size:.78rem; font-weight:700; cursor:pointer; background:#f9fafb; text-align:center; transition:border-color .15s,background .15s; }
.bmsg-cat-btn:hover { border-color:var(--oblu); background:#f0f4ff; }
.bmsg-cat-btn.active { border-color:var(--oblu); background:#dbeafe; color:var(--oblu); }
.bmsg-cat-icon { font-size:1.5rem; }

/* Priority pills */
.bmsg-priority-row { display:flex; gap:8px; flex-wrap:wrap; }
.bmsg-pill { border:2px solid #e5e7eb; border-radius:999px; padding:6px 18px; font-size:.8rem; font-weight:700; cursor:pointer; background:#fff; }
.bmsg-pill.active-normal   { background:#f0fdf4; border-color:#22c55e; color:#15803d; }
.bmsg-pill.active-urgent   { background:#fefce8; border-color:#d97706; color:#92400e; }
.bmsg-pill.active-critical { background:#fef2f2; border-color:#dc2626; color:#991b1b; }

/* Textarea */
.bmsg-textarea { width:100%; padding:12px 14px; font-size:.9rem; border:1px solid #d1d5db; border-radius:10px; resize:vertical; min-height:110px; font-family:inherit; box-sizing:border-box; }
.bmsg-textarea:focus { outline:none; border-color:var(--oblu); box-shadow:0 0 0 3px rgba(30,58,95,.12); }

/* No timekeepers */
.bmsg-warn { background:#fefce8; border:1px solid #fef08a; border-radius:10px; padding:12px 16px; font-size:.85rem; color:#713f12; font-weight:700; }

/* Submit btn */
.bmsg-submit { align-self:flex-start; border:none; border-radius:10px; background:var(--oblu); color:#fff; padding:11px 26px; font-size:.9rem; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:8px; }
.bmsg-submit:hover { opacity:.9; }
.bmsg-submit:disabled { opacity:.5; cursor:default; }

/* History */
.bmsg-history { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.07); overflow:hidden; }
.bmsg-history-head { background:#f8f9fb; border-bottom:1px solid #e5e7eb; padding:14px 18px; font-size:1rem; font-weight:800; color:var(--oblu); }
.bmsg-history-empty { padding:32px; text-align:center; color:#9ca3af; font-size:.9rem; }
.bmsg-htable { width:100%; border-collapse:collapse; font-size:.84rem; }
.bmsg-htable th { background:#f1f5f9; padding:10px 14px; text-align:left; font-size:.75rem; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
.bmsg-htable td { padding:10px 14px; border-top:1px solid #f3f4f6; vertical-align:top; }
.bmsg-htable tr:hover td { background:#fafafa; }
.bmsg-htag { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:3px 9px; font-size:.68rem; font-weight:800; }
.btag-normal   { background:#f0fdf4; color:#15803d; }
.btag-urgent   { background:#fefce8; color:#92400e; }
.btag-critical { background:#fee2e2; color:#991b1b; }
.bmsg-cat-chip { display:inline-flex; align-items:center; gap:4px; border-radius:7px; padding:3px 8px; font-size:.72rem; font-weight:700; background:#f1f5f9; color:#374151; }
.bmsg-msg-preview { max-width:340px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>

<div class="bmsg-page">

    <div class="bmsg-hero">
        <h1>📨 Messages to Timekeepers</h1>
        <p>Compose and send operational notices directly to your team</p>
        <div class="bmsg-hero-meta">
            <span class="bmsg-hero-chip">👥 <?= $timekeeperCount ?> Timekeeper<?= $timekeeperCount !== 1 ? 's' : '' ?> active</span>
            <span class="bmsg-hero-chip">📤 <?= count($sent) ?> sent today</span>
        </div>
    </div>

    <?php if ($msg === 'sent'): ?>
        <div class="bmsg-flash ok">✓ Message sent successfully to all your timekeepers.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="bmsg-flash err">⚠ Failed to send message. Please check input and try again.</div>
    <?php endif; ?>

    <!-- Compose form -->
    <div class="bmsg-compose">
        <div class="bmsg-compose-head">✏️ Compose Message</div>
        <form class="bmsg-compose-body" method="post" action="/B/messages">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="category" id="bCatInput" value="">
            <input type="hidden" name="priority" id="bPrioInput" value="normal">

            <?php if ($timekeeperCount === 0): ?>
                <div class="bmsg-warn">⚠ No active timekeepers found for your operator account. You must have timekeepers assigned to send messages.</div>
            <?php endif; ?>

            <div>
                <label style="display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:8px">Category (optional)</label>
                <div class="bmsg-cat-grid" id="bCatGrid">
                    <?php foreach ($ownerCategories as $cat): ?>
                        <button type="button" class="bmsg-cat-btn" data-key="<?= htmlspecialchars($cat['key']) ?>"
                            data-prio="<?= htmlspecialchars($cat['priority']) ?>"
                            data-tpl="<?= htmlspecialchars($cat['tpl']) ?>"
                            onclick="bSelectCat(this)">
                            <span class="bmsg-cat-icon"><?= $cat['icon'] ?></span>
                            <span><?= htmlspecialchars($cat['label']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:8px">Priority</label>
                <div class="bmsg-priority-row" id="bPrioRow">
                    <button type="button" class="bmsg-pill active-normal"  data-prio="normal"   onclick="bSetPrio(this)">🟢 Normal</button>
                    <button type="button" class="bmsg-pill"                data-prio="urgent"   onclick="bSetPrio(this)">🟠 Urgent</button>
                    <button type="button" class="bmsg-pill"                data-prio="critical" onclick="bSetPrio(this)">🔴 Critical</button>
                </div>
            </div>

            <div>
                <label for="bMsgText" style="display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:6px">Message</label>
                <textarea id="bMsgText" name="message" class="bmsg-textarea"
                    placeholder="Type your message to all active timekeepers…" required maxlength="500"></textarea>
                <div style="font-size:.72rem;color:#9ca3af;margin-top:3px"><span id="bCharCount">0</span>/500 characters</div>
            </div>

            <button type="submit" class="bmsg-submit" <?= $timekeeperCount === 0 ? 'disabled' : '' ?>>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send to All Timekeepers
            </button>
        </form>
    </div>

    <!-- Sent history -->
    <div class="bmsg-history">
        <div class="bmsg-history-head">📋 Recently Sent Messages</div>
        <?php if (empty($sent)): ?>
            <div class="bmsg-history-empty">No messages sent yet.</div>
        <?php else: ?>
            <table class="bmsg-htable">
                <thead>
                    <tr>
                        <th>Sent</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sent as $row):
                        $priority = strtolower((string)($row['priority'] ?? 'normal'));
                        $catKey   = (string)($row['category'] ?? '');
                        $catLabel = '';
                        foreach ($ownerCategories as $c) {
                            if ($c['key'] === $catKey) { $catLabel = $c['icon'] . ' ' . $c['label']; break; }
                        }
                        $prioClass = match($priority) { 'urgent'=>'btag-urgent','critical'=>'btag-critical', default=>'btag-normal' };
                        $pLabel    = match($priority) { 'urgent'=>'🟠 Urgent','critical'=>'🔴 Critical', default=>'🟢 Normal' };
                    ?>
                    <tr>
                        <td style="white-space:nowrap"><?= htmlspecialchars(date('d M y H:i', strtotime((string)($row['created_at'] ?? 'now')))) ?></td>
                        <td><span class="bmsg-htag <?= $prioClass ?>"><?= $pLabel ?></span></td>
                        <td><?php if ($catLabel): ?><span class="bmsg-cat-chip"><?= htmlspecialchars($catLabel) ?></span><?php else: ?><span style="color:#9ca3af">—</span><?php endif; ?></td>
                        <td class="bmsg-msg-preview" title="<?= htmlspecialchars((string)($row['message'] ?? '')) ?>"><?= htmlspecialchars((string)($row['message'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const catInput  = document.getElementById('bCatInput');
    const prioInput = document.getElementById('bPrioInput');
    const msgText   = document.getElementById('bMsgText');
    const charCount = document.getElementById('bCharCount');

    if (msgText) {
        msgText.addEventListener('input', () => { charCount.textContent = msgText.value.length; });
    }

    window.bSelectCat = function(btn) {
        document.querySelectorAll('#bCatGrid .bmsg-cat-btn').forEach(b => b.classList.remove('active'));
        if (catInput.value === btn.dataset.key) {
            catInput.value = '';
            return;
        }
        btn.classList.add('active');
        catInput.value = btn.dataset.key;
        const tpl = btn.dataset.tpl;
        if (tpl && msgText) { msgText.value = tpl; charCount.textContent = tpl.length; }
        const prio = btn.dataset.prio || 'normal';
        bActivatePill(prio);
    };

    window.bSetPrio = function(btn) { bActivatePill(btn.dataset.prio); };

    function bActivatePill(prio) {
        ['normal','urgent','critical'].forEach(p => {
            const b = document.querySelector(`#bPrioRow [data-prio="${p}"]`);
            if (b) { b.classList.toggle(`active-${p}`, p === prio); }
        });
        prioInput.value = prio;
    }
})();
</script>
