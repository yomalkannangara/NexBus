<?php /** @var array $routes,$buses,$special_tt,$filters */ ?>
<link rel="stylesheet" href="/assets/css/alert.css">
<script defer src="/assets/js/app.js"></script>
<script src="/assets/js/alert.js"></script>

<?php
$filterYears = range((int)date('Y') - 2, (int)date('Y') + 1);
$months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
$filterFrom = htmlspecialchars($filters['from'] ?? '');
$filterTo = htmlspecialchars($filters['to'] ?? '');
$filterMonth = (int)($filters['month'] ?? 0);
$filterYear = htmlspecialchars($filters['year'] ?? '');
$flashMsg = trim((string)($_GET['msg'] ?? ''));
?>

<?php if ($flashMsg === 'created'): ?>
<style>
.dm-success-overlay {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10050;
  padding: 16px;
}
.dm-success-modal {
  width: min(460px, 100%);
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 46px rgba(17, 24, 39, 0.3);
  overflow: hidden;
  animation: dmSuccessPop 240ms ease-out;
  border: 1px solid rgba(128, 20, 60, 0.18);
}
.dm-success-head {
  background: linear-gradient(135deg, #7B1C3E, #9b2450);
  color: #fff;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 3px solid #f3b944;
}
.dm-success-head h3 {
  margin: 0;
  font-size: 1.03rem;
  font-weight: 800;
}
.dm-success-body {
  padding: 18px;
  color: #1f2937;
  line-height: 1.45;
}
.dm-success-row {
  display: flex;
  align-items: center;
  gap: 12px;
}
.dm-success-icon {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  border: 1px solid #86efac;
  color: #15803d;
  flex: 0 0 auto;
}
.dm-success-title {
  margin: 0;
  font-weight: 800;
  color: #111827;
}
.dm-success-sub {
  margin: 4px 0 0;
  font-size: 0.92rem;
  color: #4b5563;
}
.dm-success-foot {
  padding: 14px 18px 16px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
}
.dm-success-btn {
  border: none;
  border-radius: 10px;
  padding: 9px 18px;
  font-weight: 700;
  cursor: pointer;
  color: #fff;
  background: linear-gradient(135deg, #7B1C3E, #a8274e);
  box-shadow: 0 6px 16px rgba(123, 28, 62, 0.28);
}
.dm-success-btn:hover { filter: brightness(1.05); }

@keyframes dmSuccessPop {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<div id="dmSuccessOverlay" class="dm-success-overlay" role="dialog" aria-modal="true" aria-labelledby="dmSuccessTitle">
  <div class="dm-success-modal">
    <div class="dm-success-head">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      <h3 id="dmSuccessTitle">Depot Manager Notice</h3>
    </div>
    <div class="dm-success-body">
      <div class="dm-success-row">
        <span class="dm-success-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
        </span>
        <div>
          <p class="dm-success-title">Timetable Created Successfully</p>
          <p class="dm-success-sub">Your new schedule has been saved and is now visible in the timetable snapshot.</p>
        </div>
      </div>
    </div>
    <div class="dm-success-foot">
      <button type="button" id="dmSuccessOkBtn" class="dm-success-btn">Great</button>
    </div>
  </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function () {
  const overlay = document.getElementById('dmSuccessOverlay');
  const okBtn = document.getElementById('dmSuccessOkBtn');

  function closePopup() {
    if (overlay) overlay.remove();
  }

  if (okBtn) okBtn.addEventListener('click', closePopup);
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closePopup();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.key === 'Enter') {
      closePopup();
    }
  });

  // Remove msg from URL so popup does not reappear on page refresh.
  try {
    const url = new URL(window.location.href);
    url.searchParams.delete('msg');
    window.history.replaceState({}, '', url.toString());
  } catch (e) {}
});
</script>
<?php endif; ?>

<div class="container">
<h1>Emergency / Seasonal Timetables</h1>
<form method="post" class="card" style="padding:12px;display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
<input type="hidden" name="action" value="create_special_tt">
<label>Bus<select name="bus_reg_no"><?php foreach($buses as $b): ?><option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option><?php endforeach; ?></select></label>
<label>Route<select name="route_id"><?php foreach($routes as $r): ?><option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option><?php endforeach; ?></select></label>
<label>Start<input type="date" name="effective_from" required></label>
<label>End<input type="date" name="effective_to"></label>
<label>DOW<select name="day_of_week"><option value="0">Sun</option><option value="1">Mon</option><option value="2">Tue</option><option value="3">Wed</option><option value="4">Thu</option><option value="5">Fri</option><option value="6">Sat</option></select></label>
<label>Depart<input type="time" name="departure_time" required></label>
<label>Arrive<input type="time" name="arrival_time"></label>
<div style="grid-column:1/-1"><button type="submit">Save</button></div>
</form>

<form method="get" action="/M/timetables" class="card" style="padding:16px;margin-top:16px;margin-bottom:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;">
  <label style="display:block;">
    Bus
    <select name="bus" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      <option value="">All</option>
      <?php foreach ($buses as $b): ?>
        <option value="<?= htmlspecialchars($b['reg_no']) ?>"<?= (htmlspecialchars($filters['bus'] ?? '') === htmlspecialchars($b['reg_no']) ? ' selected' : '') ?>><?= htmlspecialchars($b['reg_no']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label style="display:block;">
    Route
    <select name="route" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      <option value="">All</option>
      <?php foreach ($routes as $r): ?>
        <option value="<?= htmlspecialchars($r['route_no']) ?>"<?= (htmlspecialchars($filters['route'] ?? '') === htmlspecialchars($r['route_no']) ? ' selected' : '') ?>><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label style="display:block;">
    From
    <input type="date" name="from" value="<?= $filterFrom ?>" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
  </label>
  <label style="display:block;">
    To
    <input type="date" name="to" value="<?= $filterTo ?>" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
  </label>
  <label style="display:block;">
    Month
    <select name="month" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      <option value="">All</option>
      <?php foreach ($months as $num => $label): ?>
        <option value="<?= $num ?>"<?= $filterMonth === $num ? ' selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label style="display:block;">
    Year
    <input type="number" name="year" value="<?= $filterYear ?>" placeholder="Any year" min="1900" max="2099" style="width:100%;margin-top:4px;padding:8px;border:1px solid #d1d5db;border-radius:6px;" />
  </label>
  <div style="grid-column: 1 / -1; display:flex; gap:12px; justify-content:flex-end;">
    <button type="submit" style="padding:10px 18px;background:var(--maroon);color:#fff;border:none;border-radius:8px;cursor:pointer;">Apply filter</button>
    <a href="/M/timetables" style="padding:10px 18px;background:#e5e7eb;color:#111;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Clear</a>
  </div>
</form>

<h2 style="margin-top:16px">Timetable Snapshot</h2>
<div class="cards" style="grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); margin-top:12px; gap:14px;">
<?php if (empty($special_tt)): ?>
  <div class="card" style="padding:18px; text-align:center; color:#4b5563; max-width:280px;">No special timetable entries yet. Use the form above to add a schedule.</div>
<?php endif; ?>
<?php foreach ($special_tt as $r): ?>
  <div class="card accent-indigo" style="padding:18px; border:1px solid rgba(128,20,60,.12); max-width:320px;">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;">
      <div>
        <div style="font-size:14px;color:#64748b;font-weight:500;">Bus</div>
        <div style="font-size:18px;font-weight:700;color:#111;margin-top:2px;"><?= htmlspecialchars($r['bus_reg_no']) ?></div>
        <div style="margin-top:12px;font-size:14px;color:#64748b;font-weight:500;">Route</div>
        <div style="font-size:18px;font-weight:700;color:var(--maroon);margin-top:2px;"><?= htmlspecialchars($r['route_no'] ?? '—') ?></div>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;">
        <span class="badge badge-blue" style="padding:5px 10px;">From <?= htmlspecialchars($r['effective_from'] ?? '-') ?></span>
        <span class="badge badge-gray" style="padding:5px 10px;">To <?= htmlspecialchars($r['effective_to'] ?? 'ongoing') ?></span>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:14px;">
      <div style="background:#f8fafc;padding:14px;border-radius:12px;">
        <div style="font-size:12px;color:#64748b;">Departure</div>
        <div style="font-size:24px;font-weight:700;color:#111;margin-top:6px;"><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></div>
      </div>
      <div style="background:#f8fafc;padding:14px;border-radius:12px;">
        <div style="font-size:12px;color:#64748b;">Arrival</div>
        <div style="font-size:24px;font-weight:700;color:#111;margin-top:6px;"><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '—') ?></div>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;flex-wrap:wrap;">
      <button type="button" class="btn-edit-card" onclick="openEditModal(this)" data-id="<?= (int)$r['timetable_id'] ?>" data-bus="<?= htmlspecialchars($r['bus_reg_no']) ?>" data-route-id="<?= (int)$r['route_id'] ?>" data-from="<?= htmlspecialchars($r['effective_from']) ?>" data-to="<?= htmlspecialchars($r['effective_to'] ?? '') ?>" data-dow="<?= (int)$r['day_of_week'] ?>" data-dep="<?= htmlspecialchars(substr($r['departure_time'],0,5)) ?>" data-arr="<?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?>" style="padding:6px 12px;background:var(--maroon);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Edit</button>
      <form method="post" style="display:inline;" onsubmit="return confirm('Delete this timetable?')">
        <input type="hidden" name="action" value="delete_special_tt">
        <input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
        <button type="submit" style="padding:6px 12px;background:#e5e7eb;color:#d32f2f;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Delete</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>
</div>

<script>
(function(){
  // Build options for inline edit
  const BUSES = <?= json_encode(array_values(array_map(fn($b)=>$b['reg_no'], $buses ?? []))) ?>;
  const ROUTES = <?= json_encode(array_values(array_map(fn($x)=>['id'=>(int)$x['route_id'],'label'=>$x['route_no'].' — '.$x['name']], $routes ?? [])), JSON_UNESCAPED_UNICODE) ?>;

  function buildSelect(options, value){
    const sel = document.createElement('select');
    options.forEach(o=>{
      const opt = document.createElement('option');
      if (typeof o === 'string') { opt.value = o; opt.textContent = o; }
      else { opt.value = o.id; opt.textContent = o.label; }
      if (String(opt.value) === String(value)) opt.selected = true;
      sel.appendChild(opt);
    });
    return sel;
  }
  function buildDowSelect(value){
    const labels=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const sel = document.createElement('select');
    labels.forEach((lab,i)=>{
      const opt=document.createElement('option');
      opt.value=i; opt.textContent=lab; if (String(i)===String(value)) opt.selected=true;
      sel.appendChild(opt);
    });
    return sel;
  }
  function toInput(type, value){ const i=document.createElement('input'); i.type=type; if (value) i.value=value; return i; }

  document.querySelectorAll('.btn-edit').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const tr = btn.closest('tr');
      if (!tr || tr.dataset.editing) return;
      tr.dataset.editing = '1';
      const tds = tr.querySelectorAll('td');
      const id  = tr.dataset.id;

      const busSel  = buildSelect(BUSES, tr.dataset.bus);
      const routeSel= buildSelect(ROUTES, tr.dataset.routeId);
      const fromI   = toInput('date', tr.dataset.from);
      const toI     = toInput('date', tr.dataset.to);
      const dowSel  = buildDowSelect(tr.dataset.dow);
      const depI    = toInput('time', tr.dataset.dep);
      const arrI    = toInput('time', tr.dataset.arr);

      // Replace display cells with editors
      tds[1].innerHTML=''; tds[1].appendChild(busSel);
      tds[2].innerHTML=''; tds[2].appendChild(routeSel);
      tds[3].innerHTML=''; tds[3].appendChild(fromI);
      tds[4].innerHTML=''; tds[4].appendChild(toI);
      tds[5].innerHTML=''; tds[5].appendChild(dowSel);
      tds[6].innerHTML=''; tds[6].appendChild(depI);
      tds[7].innerHTML=''; tds[7].appendChild(arrI);

      // Actions: show Save/Cancel and hide delete
      const actTd = tds[8];
      const delForm = actTd.querySelector('form');
      if (delForm) delForm.style.display = 'none';

      const saveBtn = document.createElement('button');
      saveBtn.type='button'; saveBtn.textContent='Save';
      const cancelBtn = document.createElement('button');
      cancelBtn.type='button'; cancelBtn.textContent='Cancel'; cancelBtn.style.marginLeft='8px';
      actTd.appendChild(saveBtn); actTd.appendChild(cancelBtn);

      cancelBtn.addEventListener('click', ()=>{ window.location.reload(); });
      saveBtn.addEventListener('click', ()=>{
        const f = document.createElement('form');
        f.method='post';
        f.innerHTML = ''
          + '<input type="hidden" name="action" value="edit_special_tt">'
          + '<input type="hidden" name="timetable_id" value="'+id+'">'
          + '<input type="hidden" name="bus_reg_no" value="'+busSel.value+'">'
          + '<input type="hidden" name="route_id" value="'+routeSel.value+'">'
          + '<input type="hidden" name="effective_from" value="'+fromI.value+'">'
          + '<input type="hidden" name="effective_to" value="'+toI.value+'">'
          + '<input type="hidden" name="day_of_week" value="'+dowSel.value+'">'
          + '<input type="hidden" name="departure_time" value="'+depI.value+'">'
          + '<input type="hidden" name="arrival_time" value="'+arrI.value+'">';
        document.body.appendChild(f);
        f.submit();
      });
    });
  });
})();

function openEditModal(btn) {
  const ttId = btn.dataset.id;
  const busReg = btn.dataset.bus;
  const routeId = btn.dataset.routeId;
  const effFrom = btn.dataset.from;
  const effTo = btn.dataset.to;
  const dow = btn.dataset.dow;
  const dep = btn.dataset.dep;
  const arr = btn.dataset.arr;

  document.getElementById('modalEditTtId').value = ttId;
  document.getElementById('modalEditBus').value = busReg;
  document.getElementById('modalEditRoute').value = routeId;
  document.getElementById('modalEditFrom').value = effFrom;
  document.getElementById('modalEditTo').value = effTo;
  document.getElementById('modalEditDow').value = dow;
  document.getElementById('modalEditDep').value = dep;
  document.getElementById('modalEditArr').value = arr;

  document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

window.addEventListener('click', (e) => {
  const modal = document.getElementById('editModal');
  if (e.target === modal) {
    modal.style.display = 'none';
  }
});
</script>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4);">
  <div style="background:#fff;margin:10% auto;padding:20px;border-radius:12px;width:90%;max-width:500px;box-shadow:0 8px 24px rgba(0,0,0,0.15);">
    <h2 style="margin:0 0 16px;color:var(--maroon);">Edit Special Timetable</h2>
    <form method="post">
      <input type="hidden" name="action" value="edit_special_tt">
      <input type="hidden" name="timetable_id" id="modalEditTtId">
      
      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Bus</label>
        <select id="modalEditBus" name="bus_reg_no" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
          <?php foreach($buses as $b): ?><option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option><?php endforeach; ?>
        </select>
      </div>

      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Route</label>
        <select id="modalEditRoute" name="route_id" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
          <?php foreach($routes as $r): ?><option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>

      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Effective From</label>
        <input type="date" id="modalEditFrom" name="effective_from" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      </div>

      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Effective To</label>
        <input type="date" id="modalEditTo" name="effective_to" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      </div>

      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Day of Week</label>
        <select id="modalEditDow" name="day_of_week" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
          <option value="0">Sunday</option><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option>
        </select>
      </div>

      <div style="margin-bottom:12px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Departure Time</label>
        <input type="time" id="modalEditDep" name="departure_time" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      </div>

      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Arrival Time</label>
        <input type="time" id="modalEditArr" name="arrival_time" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeEditModal()" style="padding:8px 16px;background:#e5e7eb;color:#111;border:none;border-radius:6px;cursor:pointer;">Cancel</button>
        <button type="submit" style="padding:8px 16px;background:var(--maroon);color:#fff;border:none;border-radius:6px;cursor:pointer;">Save Changes</button>
      </div>
    </form>
  </div>
</div>
