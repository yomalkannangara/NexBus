<?php /** @var array $routes,$buses,$special_tt */ ?>
<div class="container">
<h1>Emergency / Seasonal Timetables</h1>
<?php if(!empty($msg)): ?><div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post" class="card" style="padding:12px;display:grid;grid-template-columns:repeat(6,1fr);gap:8px;">
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

<h2 style="margin-top:16px">Existing Special Timetables</h2>
<table class="table"><tr><th>ID</th><th>Bus</th><th>Route</th><th>From</th><th>To</th><th>DOW</th><th>Dep</th><th>Arr</th><th></th></tr>
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
<td>
  <button type="button" class="btn-edit">Edit</button>
  <form method="post" style="display:inline" onsubmit="return confirm('Delete this timetable?')">
    <input type="hidden" name="action" value="delete_special_tt">
    <input type="hidden" name="timetable_id" value="<?= (int)$r['timetable_id'] ?>">
    <button>Delete</button>
  </form>
</td>
</tr>
<?php endforeach; ?>
</table>
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
</script>