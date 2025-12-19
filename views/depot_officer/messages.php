<?php 
/** @var array $staff,$recent,$msg */ 
// Helper function to get display name
function getStaffDisplayName($person) {
    if (!empty($person['full_name'])) return $person['full_name'];
    if (!empty($person['first_name'])) {
        $name = $person['first_name'];
        if (!empty($person['last_name'])) $name .= ' ' . $person['last_name'];
        return $name;
    }
    return 'Unknown';
}
?>
<div class="container">
<h1>Depot Messaging</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card" style="padding:12px;">
<input type="hidden" name="action" value="send">
<label>Recipients (same depot):<br>
    <div class="alpha-filter-wrapper">
        <div class="alpha-filter-header">
            <strong>Filter by letter:</strong>
            <span id="alpha-filter" class="alpha-filter-buttons">
                <button type="button" class="alpha-btn active" data-letter="all">All</button>
                <?php foreach(range('A','Z') as $letter): ?>
                    <button type="button" class="alpha-btn" data-letter="<?= $letter ?>"><?= $letter ?></button>
                <?php endforeach; ?>
            </span>
        </div>
        <input type="text" id="staff-search" class="alpha-filter-search" placeholder="Search name...">
    </div>
    <select name="to[]" id="recipients" multiple size="6" style="min-width:320px">
        <?php foreach($staff as $s): ?>
            <option value="<?= (int)$s['user_id'] ?>">[<?= htmlspecialchars($s['role']) ?>] <?= htmlspecialchars(getStaffDisplayName($s)) ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>Message<br><textarea name="message" rows="3" style="width:100%" required></textarea></label>
<button type="submit">Send</button>
</form>


<h2 style="margin-top:16px">Recent Messages</h2>
<table class="table"><tr><th>When</th><th>To (User)</th><th>Type</th><th>Text</th></tr>
<?php foreach($recent as $n): ?>
<tr><td><?= htmlspecialchars($n['created_at']) ?></td><td><?= htmlspecialchars(getStaffDisplayName($n)) ?></td><td><?= htmlspecialchars($n['type']) ?></td><td><?= htmlspecialchars($n['message']) ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<script>
// Alphabet filter for recipients select
;(function(){
    const select = document.getElementById('recipients');
    const alpha = document.getElementById('alpha-filter');
    const search = document.getElementById('staff-search');
    const opts = Array.from(select.options);

    function normalizeName(txt){
        // remove leading [ROLE] if present
        return txt.replace(/^\[[^\]]+\]\s*/,'').trim().toLowerCase();
    }

    function applyFilter(letter){
        const q = (search.value||'').toLowerCase();
        opts.forEach(o=>{
            const name = normalizeName(o.text);
            let show = true;
            if (letter && letter !== 'all') {
                show = name.charAt(0) === letter.toLowerCase();
            }
            if (q) show = show && name.includes(q);
            o.style.display = show ? '' : 'none';
        });
    }

    alpha.addEventListener('click', (ev)=>{
        const btn = ev.target.closest('.alpha-btn');
        if (!btn) return;
        const letter = btn.dataset.letter || 'all';
        applyFilter(letter);
    });

    search.addEventListener('input', ()=>applyFilter(document.querySelector('.alpha-btn[data-letter].active')?.dataset?.letter || 'all'));
})();
</script>