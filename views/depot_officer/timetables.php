<?php /** @var array $routes,$buses,$special_tt */ ?>
<div class="container">
<section class="title-banner">
  <h1>Emergency / Seasonal Timetables</h1>
  <p>Create and manage temporary timetable overrides with clear departure/arrival scheduling.</p>
</section>

<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="post" class="card tt-form">
  <input type="hidden" name="action" value="create_special_tt">

  <div class="tt-top-row">
    <label>
      <span>Departure Time</span>
      <input type="time" name="departure_time" required>
    </label>
    <label>
      <span>Arrival Time</span>
      <input type="time" name="arrival_time">
    </label>
  </div>

  <div class="tt-grid">
    <label>
      <span>Bus</span>
      <select name="bus_reg_no">
        <?php foreach($buses as $b): ?><option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Route</span>
      <select name="route_id">
        <?php foreach($routes as $r): ?><option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_no'].' — '.$r['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>
      <span>Effective From</span>
      <input type="date" name="effective_from" required>
    </label>
    <label>
      <span>Effective To</span>
      <input type="date" name="effective_to">
    </label>
    <label>
      <span>Day of Week</span>
      <select name="day_of_week">
        <option value="0">Sunday</option><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option>
      </select>
    </label>
  </div>

  <div class="tt-actions">
    <button type="submit">Save Timetable</button>
  </div>
</form>

<h2 class="tt-heading">Existing Special Timetables</h2>
<table class="table tt-table">
<thead><tr><th>ID</th><th>Bus</th><th>Route</th><th>From</th><th>To</th><th>DOW</th><th>Dep</th><th>Arr</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($special_tt as $r): ?>
<tr
  id="row-<?= (int)$r['timetable_id'] ?>"
  data-id="<?= (int)$r['timetable_id'] ?>"
  data-bus="<?= htmlspecialchars($r['bus_reg_no']) ?>"
  data-route-id="<?= (int)$r['route_id'] ?>"
  data-from="<?= htmlspecialchars($r['effective_from']) ?>"
  data-to="<?= htmlspecialchars($r['effective_to'] ?? '') ?>"
  data-dow="<?= (int)$r['day_of_week'] ?>"
  data-dep="<?= htmlspecialchars(substr($r['departure_time'],0,5)) ?>"
  data-arr="<?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?>"
>
<td><?= (int)$r['timetable_id'] ?></td>
<td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
<td><?= htmlspecialchars($r['route_no'] ?? '') ?></td>
<td><?= htmlspecialchars($r['effective_from']) ?></td>
<td><?= htmlspecialchars($r['effective_to'] ?? '') ?></td>
<td><?= (int)$r['day_of_week'] ?></td>
<td><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></td>
<td><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?></td>
<td class="tt-actions-cell">
  <div class="tt-row-actions">
    <button type="button" class="btn-edit button outline">Edit</button>
    <form method="post" class="tt-inline-form" onsubmit="return confirm('Delete this timetable?')">
      <input type="hidden" name="action" value="delete_special_tt">
      <input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
      <button class="button" type="submit">Delete</button>
    </form>
  </div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<style>
.tt-form {
  padding: 16px;
  display: grid;
  gap: 14px;
}
.tt-form label {
  display: grid;
  gap: 6px;
}
.tt-form label span {
  font-size: 12px;
  color: var(--muted);
  font-weight: 700;
}
.tt-top-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(180px, 1fr));
  gap: 12px;
}
.tt-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(180px, 1fr));
  gap: 12px;
}
.tt-actions {
  display: flex;
  justify-content: flex-end;
}
.tt-heading {
  margin: 16px 0 10px;
  color: var(--maroon);
}
.tt-inline-form {
  display: inline;
}
.tt-actions-cell {
  min-width: 170px;
}
.tt-row-actions {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  width: 100%;
}
.tt-row-actions .button {
  min-width: 72px;
}
.tt-row-actions.is-editing .button {
  min-width: 82px;
}

@media (max-width: 900px) {
  .tt-grid {
    grid-template-columns: repeat(2, minmax(160px, 1fr));
  }
}
@media (max-width: 640px) {
  .tt-top-row,
  .tt-grid {
    grid-template-columns: 1fr;
  }
  .tt-actions {
    justify-content: stretch;
  }
  .tt-actions .button,
  .tt-actions button {
    width: 100%;
  }
  .tt-row-actions {
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}
</style>

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

      // Actions: show Save/Cancel in a fixed, user-friendly position
      const actTd = tds[8];
      actTd.innerHTML='';
      const actionWrap = document.createElement('div');
      actionWrap.className = 'tt-row-actions is-editing';

      const saveBtn = document.createElement('button');
      saveBtn.type='button'; saveBtn.textContent='Save'; saveBtn.className = 'button';
      const cancelBtn = document.createElement('button');
      cancelBtn.type='button'; cancelBtn.textContent='Cancel'; cancelBtn.className = 'button outline';
      actionWrap.appendChild(saveBtn);
      actionWrap.appendChild(cancelBtn);
      actTd.appendChild(actionWrap);

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
</script>