<?php /** @var array $staff,$records */ ?>
<div class="container">
<h1>Staff Attendance</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="get" style="display:flex;gap:8px;align-items:end">
<input type="hidden" name="module" value="depot_officer"><input type="hidden" name="page" value="attendance">
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
<button>Go</button>
</form>


<form method="post">
<input type="hidden" name="action" value="mark">
	<div class="alpha-filter-wrapper">
		<div class="alpha-filter-header">
			<strong>Filter by letter:</strong>
			<span id="attendance-alpha" class="alpha-filter-buttons">
				<button type="button" class="alpha-btn active" data-letter="all">All</button>
				<?php foreach(range('A','Z') as $letter): ?>
					<button type="button" class="alpha-btn" data-letter="<?= $letter ?>"><?= $letter ?></button>
				<?php endforeach; ?>
			</span>
		</div>
		<input type="text" id="attendance-search" class="alpha-filter-search" placeholder="Search name or role...">
	</div>

	<table class="table"><tr><th>User</th><th>Role</th><th>Absent?</th><th>Notes</th></tr>
	<?php foreach($staff as $s): $akey = $s['attendance_key'] ?? ($s['attendance_key'] ?? null); $rec = $records[$akey] ?? null; ?>
		<tr data-name="<?= htmlspecialchars(strtolower($s['full_name'] ?? ($s['first_name'] ?? ''))) ?>" data-role="<?= htmlspecialchars(strtolower($s['type'] ?? $s['role'] ?? '')) ?>">
			<td><?= htmlspecialchars($s['full_name']) ?></td>
			<td><?= htmlspecialchars($s['type'] ?? $s['role'] ?? '') ?></td>
			<td><input type="checkbox" name="mark[<?= htmlspecialchars($akey) ?>][absent]" value="1" <?= !empty($rec['mark_absent'])?'checked':'' ?>></td>
			<td><input type="text" name="mark[<?= htmlspecialchars($akey) ?>][notes]" value="<?= htmlspecialchars($rec['notes'] ?? '') ?>" style="width:100%"></td>
		</tr>
	<?php endforeach; ?>
	</table>
<button type="submit">Save Attendance</button>
</form>
</div>
<script>
// Alphabet + search filter for attendance rows
;(function(){
	const alphaWrap = document.getElementById('attendance-alpha');
	const search = document.getElementById('attendance-search');
	const rows = Array.from(document.querySelectorAll('.table tbody tr'));

	function applyFilter(letter){
		const q = (search.value || '').toLowerCase();
		rows.forEach(r => {
			const name = r.dataset.name || '';
			const role = r.dataset.role || '';
			let ok = true;
			if (letter && letter !== 'all') ok = name.charAt(0) === letter.toLowerCase();
			if (q) ok = ok && (name.includes(q) || role.includes(q));
			r.style.display = ok ? '' : 'none';
		});
	}

	alphaWrap.addEventListener('click', (ev)=>{
		const btn = ev.target.closest('.alpha-btn');
		if (!btn) return;
		const letter = btn.dataset.letter || 'all';
		// highlight active
		Array.from(alphaWrap.querySelectorAll('.alpha-btn')).forEach(b=>b.classList.remove('active'));
		btn.classList.add('active');
		applyFilter(letter);
	});

	search.addEventListener('input', ()=>{
		const active = alphaWrap.querySelector('.alpha-btn.active');
		applyFilter(active ? active.dataset.letter : 'all');
	});
})();
</script>