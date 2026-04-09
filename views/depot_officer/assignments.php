<?php /** @var array $rows,$buses,$drivers,$conductors,$routes,$msg */ ?>
<style>
.badge.shift-morning  { background:#dbeafe; color:#1e40af; }
.badge.shift-evening  { background:#fef3c7; color:#92400e; }
.badge.shift-night    { background:#ede9fe; color:#5b21b6; }
.shift-radio-opt:has(input:checked) { border-color:#2563eb; background:#eff6ff; }
.shift-radio-opt { transition: border-color .15s, background .15s; }
</style>
<section class="section">
  <div class="title-card">
    <h1 class="title-heading">SLTB Daily Bus Assignments</h1>
    <p class="title-sub">Manage today’s driver and conductor allocations</p>
  </div>

  <?php if(!empty($msg) && $msg === 'created'): ?>
    <div class="notice success">Assignment created successfully.</div>
  <?php elseif(!empty($_GET['msg']) && $_GET['msg'] === 'conflict_driver'): ?>
    <div class="notice warning">Driver already assigned to bus <strong><?= htmlspecialchars($_GET['exists'] ?? '') ?></strong> on this date. <button id="btnOverrideDriver" class="btn small">Override</button></div>
  <?php elseif(!empty($_GET['msg']) && $_GET['msg'] === 'conflict_conductor'): ?>
    <div class="notice warning">Conductor already assigned to bus <strong><?= htmlspecialchars($_GET['exists'] ?? '') ?></strong> on this date. <button id="btnOverrideConductor" class="btn small">Override</button></div>
  <?php elseif(!empty($msg)): ?>
    <div class="notice"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="table-card">
    <div class="filters" style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <label>Shift:
        <select id="filter-shift">
          <option value="all">All Shifts</option>
          <option value="Morning">Morning</option>
          <option value="Evening">Evening</option>
          <option value="Night">Night</option>
        </select>
      </label>
      <label>Route:
        <select id="filter-route">
          <option value="all">All Routes</option>
          <?php foreach($routes as $rt): ?>
            <option value="<?= htmlspecialchars($rt['route_no']) ?>"><?= htmlspecialchars($rt['route_no'] . ' — ' . $rt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Destination:
        <input type="text" id="filter-dest" placeholder="Filter by destination...">
      </label>
    </div>
    <table class="styled-table">
      <thead>
        <tr>
          <th>Bus Number</th>
          <th>Shift</th>
          <th>Route</th>
          <th>Route&nbsp;No</th>
          <th>Status</th>
          <th>Capacity</th>
          <th>Assigned Driver</th>
          <th>Assigned Conductor</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr
            data-route-no="<?= htmlspecialchars($r['route_no'] ?? '') ?>"
            data-route-display="<?= htmlspecialchars($r['route_display'] ?? ($r['route_start'].' → '.$r['route_end'])) ?>"
            data-assignment-id="<?= (int)$r['assignment_id'] ?>"
            data-assigned-date="<?= htmlspecialchars($r['assigned_date'] ?? date('Y-m-d')) ?>"
            data-shift="<?= htmlspecialchars($r['shift'] ?? 'Morning') ?>"
            data-bus-reg="<?= htmlspecialchars($r['bus_reg_no'] ?? '') ?>"
            data-driver-id="<?= (int)($r['sltb_driver_id'] ?? 0) ?>"
            data-conductor-id="<?= (int)($r['sltb_conductor_id'] ?? 0) ?>"
          >
            <td><a href="/O/bus_profile?bus_reg_no=<?= urlencode($r['bus_reg_no'] ?? '') ?>"><?= htmlspecialchars($r['bus_reg_no']) ?></a></td>
            <td><span class="badge shift-<?= strtolower($r['shift'] ?? 'morning') ?>"><?= htmlspecialchars($r['shift'] ?? '-') ?></span></td>
            <td><?= htmlspecialchars($r['route_start'] ? ($r['route_start'] . ' → ' . $r['route_end']) : ($r['route_name'] ?? '-')) ?></td>
            <td><span class="tag"><?= htmlspecialchars($r['route_no'] ?? '-') ?></span></td>
            <td>
              <span class="badge <?= strtolower($r['bus_status'] ?? 'Active') ?>">
                <?= htmlspecialchars($r['bus_status'] ?? 'Active') ?>
              </span>
            </td>
            <td><?= htmlspecialchars(($r['capacity'] ?? '0') . ' seats') ?></td>
            <td><?= $r['driver_name'] ? htmlspecialchars($r['driver_name']) : '<span class="muted">Unassigned</span>' ?></td>
            <td><?= $r['conductor_name'] ? htmlspecialchars($r['conductor_name']) : '<span class="muted">Unassigned</span>' ?></td>
            <td class="actions">
              <button type="button" class="button outline btn-row-edit" title="Edit Assignment">Edit</button>
              <form method="post" class="inline" onsubmit="return confirm('Delete this assignment?')">
                <input type="hidden" name="action" value="delete_assignment">
                <input type="hidden" name="assignment_id" value="<?= (int)$r['assignment_id'] ?>">
                <button class="button outline btn-row-delete" title="Delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <button id="btnAddAssignment" class="btn-add">+ Add Assignment</button>
</section>

<!-- Modal Popup -->
<div id="addModal" class="modal hidden">
  <div class="modal-content card">
    <h2>Assign Bus</h2>
    <form method="post">
      <input type="hidden" name="action" value="create_assignment">
      <label>Date <input type="date" name="assigned_date" id="add-assigned-date" value="<?= date('Y-m-d') ?>"></label>
      <input type="hidden" name="shift" id="add-shift" value="">
      <input type="hidden" name="timetable_id" id="add-timetable-id" value="">
      <div style="margin-bottom:10px;">
        <span style="display:block;font-size:0.82rem;color:var(--text-muted,#6b7280);margin-bottom:6px;">Shift (from timetable departure time)</span>
        <div id="add-shift-options" class="shift-radio-group" style="display:flex;gap:10px;flex-wrap:wrap;">
          <span class="muted">Select bus and date to load available shifts</span>
        </div>
      </div>
      <label>Bus
        <input list="buses" name="bus_reg_no" id="bus-select" required placeholder="Type or pick bus reg no">
        <datalist id="buses">
          <?php foreach($buses as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars(($b['reg_no'] . ' — Route ' . ($b['route_no'] ?? 'N/A') . ' — ' . (($b['route_name'] ?? '') ?: 'Destination N/A'))) ?></option>
          <?php endforeach; ?>
        </datalist>
      </label>
      <label>Route Number <input type="text" id="bus-route-no" readonly></label>
      <label>Destination <input type="text" id="bus-route-name" readonly></label>
      <label>Seat Capacity <input type="text" id="bus-capacity" readonly placeholder="Select a bus"></label>
      <label>Driver
        <select name="sltb_driver_id" id="add-driver" required>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['sltb_driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div id="add-driver-warn" class="notice warning" style="display:none;padding:6px 10px;margin:2px 0 6px;"></div>
      <label>Conductor
        <select name="sltb_conductor_id" id="add-conductor" required>
          <?php foreach($conductors as $c): ?>
            <option value="<?= (int)$c['sltb_conductor_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div id="add-conductor-warn" class="notice warning" style="display:none;padding:6px 10px;margin:2px 0 6px;"></div>
      <label id="label-override-remark" style="display:none;">
        Override remark
        <textarea name="override_remark" id="override-remark" placeholder="Explain why you are overriding the existing assignment" style="width:100%;min-height:80px;"></textarea>
      </label>
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Save</button>
        <button type="button" id="closeModal" class="btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal hidden">
  <div class="modal-content card">
    <h2>Edit Assignment</h2>
    <form method="post">
      <input type="hidden" name="action" value="update_assignment">
      <input type="hidden" name="assignment_id" id="edit-assignment-id">
      <label>Date <input type="date" name="assigned_date" id="edit-assigned-date" required></label>
      <label>Shift
        <select name="shift" id="edit-shift" required>
          <option>Morning</option><option>Evening</option><option>Night</option>
        </select>
      </label>
      <label>Bus
        <input list="buses" name="bus_reg_no" id="edit-bus-select" required placeholder="Type or pick bus reg no">
      </label>
      <label>Route Number <input type="text" id="edit-bus-route-no" readonly></label>
      <label>Destination <input type="text" id="edit-bus-route-name" readonly></label>
      <label>Seat Capacity <input type="text" id="edit-bus-capacity" readonly placeholder="Select a bus"></label>
      <label>Driver
        <select name="sltb_driver_id" id="edit-driver" required>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['sltb_driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div id="edit-driver-warn" class="notice warning" style="display:none;padding:6px 10px;margin:2px 0 6px;"></div>
      <label>Conductor
        <select name="sltb_conductor_id" id="edit-conductor" required>
          <?php foreach($conductors as $c): ?>
            <option value="<?= (int)$c['sltb_conductor_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div id="edit-conductor-warn" class="notice warning" style="display:none;padding:6px 10px;margin:2px 0 6px;"></div>
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Update</button>
        <button type="button" id="closeEditModal" class="btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById('addModal');
document.getElementById('btnAddAssignment').onclick = ()=>modal.classList.remove('hidden');
document.getElementById('closeModal').onclick = ()=>modal.classList.add('hidden');

// Build a JS map of bus -> route info for auto-fill
const busInfo = {};
  <?php foreach($buses as $b): ?>
    busInfo[<?= json_encode(strtoupper(trim((string)$b['reg_no']))) ?>] = {
    route_no: <?= json_encode($b['route_no'] ?? '') ?>,
    route_name: <?= json_encode($b['route_name'] ?? '') ?>,
    route_start: <?= json_encode($b['route_start'] ?? '') ?>,
    route_end: <?= json_encode($b['route_end'] ?? '') ?>,
    capacity: <?= (int)($b['capacity'] ?? 0) ?>
  };
<?php endforeach; ?>

// Map of shift -> { driver_id -> bus_reg_no, ... } and { conductor_id -> bus_reg_no } built from today's loaded rows
const shiftDriverMap    = {}; // shiftDriverMap['Morning'][driverId]    = 'NB-1001'
const shiftConductorMap = {}; // shiftConductorMap['Morning'][conductorId] = 'NB-1001'
<?php foreach($rows as $r): ?>
<?php if(($r['sltb_driver_id'] ?? 0) || ($r['sltb_conductor_id'] ?? 0)): ?>
(function(){
  const sh = <?= json_encode($r['shift'] ?? 'Morning') ?>;
  const bus = <?= json_encode($r['bus_reg_no'] ?? '') ?>;
  <?php if(!empty($r['sltb_driver_id'])): ?>
  if(!shiftDriverMap[sh]) shiftDriverMap[sh] = {};
  shiftDriverMap[sh][<?= (int)$r['sltb_driver_id'] ?>] = bus;
  <?php endif; ?>
  <?php if(!empty($r['sltb_conductor_id'])): ?>
  if(!shiftConductorMap[sh]) shiftConductorMap[sh] = {};
  shiftConductorMap[sh][<?= (int)$r['sltb_conductor_id'] ?>] = bus;
  <?php endif; ?>
})();
<?php endif; ?>
<?php endforeach; ?>

const busSelect = document.getElementById('bus-select');
const addDateInput = document.getElementById('add-assigned-date');
const addShiftInput = document.getElementById('add-shift');
const addTimetableInput = document.getElementById('add-timetable-id');
const addShiftOptions = document.getElementById('add-shift-options');
const routeNoInput = document.getElementById('bus-route-no');
const routeNameInput = document.getElementById('bus-route-name');
const editModal = document.getElementById('editModal');
const editBusSelect = document.getElementById('edit-bus-select');
const editRouteNoInput = document.getElementById('edit-bus-route-no');
const editRouteNameInput = document.getElementById('edit-bus-route-name');
function normalizeBusReg(value) {
  return (value || '').trim().toUpperCase();
}
function fillBusRouteFor(inputEl, routeNoEl, routeNameEl, capacityEl) {
  const v = normalizeBusReg(inputEl.value);
  const info = busInfo[v] || {route_no:'', route_name:'', route_start:'', route_end:'', capacity:0};
  routeNoEl.value = info.route_no || 'N/A';
  routeNameEl.value = info.route_name || (info.route_start && info.route_end ? (info.route_start + ' → ' + info.route_end) : 'N/A');
  if (capacityEl) capacityEl.value = info.capacity ? info.capacity + ' seats' : 'N/A';
}
function fillBusRoute() {
  fillBusRouteFor(busSelect, routeNoInput, routeNameInput, document.getElementById('bus-capacity'));
}
function fillEditBusRoute() {
  fillBusRouteFor(editBusSelect, editRouteNoInput, editRouteNameInput, document.getElementById('edit-bus-capacity'));
}
busSelect.addEventListener('change', fillBusRoute);
busSelect.addEventListener('input', fillBusRoute);
fillBusRoute();
editBusSelect.addEventListener('change', fillEditBusRoute);
editBusSelect.addEventListener('input', fillEditBusRoute);

function renderAddShiftOptions(items) {
  addShiftOptions.innerHTML = '';
  if (!items || !items.length) {
    addShiftInput.value = '';
    addTimetableInput.value = '';
    addShiftOptions.innerHTML = '<span class="muted">No timetable departures available for selected date</span>';
    checkAddConflicts();
    return;
  }
  items.forEach((item, idx) => {
    const option = document.createElement('label');
    option.className = 'shift-radio-opt';
    option.style.cssText = 'display:flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-weight:500;';
    const val = (item.departure_label || '').slice(0,5);
    option.innerHTML = '<input type="radio" name="add_shift_pick" value="'+ val +'" data-timetable-id="'+ (item.timetable_id || '') +'" ' + (idx===0?'checked':'') + '> ' +
      '<span>' + (item.departure_label || val) + '</span>';
    addShiftOptions.appendChild(option);
  });
  const first = addShiftOptions.querySelector('input[name="add_shift_pick"]:checked');
  addShiftInput.value = first?.value || '';
  addTimetableInput.value = first?.dataset.timetableId || '';
  addShiftOptions.querySelectorAll('input[name="add_shift_pick"]').forEach(r => {
    r.addEventListener('change', () => {
      addShiftInput.value = r.value || '';
      addTimetableInput.value = r.dataset.timetableId || '';
      checkAddConflicts();
    });
  });
  checkAddConflicts();
}

async function loadAddShiftOptions() {
  const bus = normalizeBusReg(busSelect.value);
  const date = addDateInput.value;
  if (!bus || !date) {
    renderAddShiftOptions([]);
    return;
  }
  addShiftOptions.innerHTML = '<span class="muted">Loading shifts...</span>';
  try {
    const res = await fetch('/O/assignments/shifts?bus_reg_no=' + encodeURIComponent(bus) + '&date=' + encodeURIComponent(date), {
      headers: {'Accept': 'application/json'}
    });
    const data = await res.json();
    if (!data || !data.ok) {
      renderAddShiftOptions([]);
      return;
    }
    renderAddShiftOptions(data.items || []);
  } catch (e) {
    renderAddShiftOptions([]);
  }
}

document.getElementById('closeEditModal').onclick = ()=>editModal.classList.add('hidden');

// Live conflict warnings for Add modal
function checkAddConflicts() {
  const shift   = addShiftInput.value || '';
  const bus     = normalizeBusReg(busSelect.value);
  const driverId   = parseInt(document.getElementById('add-driver').value) || 0;
  const conductorId = parseInt(document.getElementById('add-conductor').value) || 0;
  const dWarn = document.getElementById('add-driver-warn');
  const cWarn = document.getElementById('add-conductor-warn');
  const dMap = shiftDriverMap[shift] || {};
  const cMap = shiftConductorMap[shift] || {};
  if (driverId && dMap[driverId] && dMap[driverId] !== bus) {
    dWarn.textContent = '⚠ This driver is already assigned to bus ' + dMap[driverId] + ' for the ' + shift + ' shift today.';
    dWarn.style.display = 'block';
  } else { dWarn.style.display = 'none'; }
  if (conductorId && cMap[conductorId] && cMap[conductorId] !== bus) {
    cWarn.textContent = '⚠ This conductor is already assigned to bus ' + cMap[conductorId] + ' for the ' + shift + ' shift today.';
    cWarn.style.display = 'block';
  } else { cWarn.style.display = 'none'; }
}
addDateInput.addEventListener('change', loadAddShiftOptions);
busSelect.addEventListener('change', loadAddShiftOptions);
busSelect.addEventListener('input', loadAddShiftOptions);
document.getElementById('add-driver').addEventListener('change', checkAddConflicts);
document.getElementById('add-conductor').addEventListener('change', checkAddConflicts);
busSelect.addEventListener('change', checkAddConflicts);
busSelect.addEventListener('input', checkAddConflicts);
loadAddShiftOptions();

// Live conflict warnings for Edit modal
function checkEditConflicts(currentAssignmentId) {
  const shift   = document.getElementById('edit-shift').value;
  const bus     = normalizeBusReg(editBusSelect.value);
  const driverId   = parseInt(document.getElementById('edit-driver').value) || 0;
  const conductorId = parseInt(document.getElementById('edit-conductor').value) || 0;
  const dWarn = document.getElementById('edit-driver-warn');
  const cWarn = document.getElementById('edit-conductor-warn');
  const dMap = shiftDriverMap[shift] || {};
  const cMap = shiftConductorMap[shift] || {};
  if (driverId && dMap[driverId] && dMap[driverId] !== bus) {
    dWarn.textContent = '⚠ This driver is already assigned to bus ' + dMap[driverId] + ' for the ' + shift + ' shift today.';
    dWarn.style.display = 'block';
  } else { dWarn.style.display = 'none'; }
  if (conductorId && cMap[conductorId] && cMap[conductorId] !== bus) {
    cWarn.textContent = '⚠ This conductor is already assigned to bus ' + cMap[conductorId] + ' for the ' + shift + ' shift today.';
    cWarn.style.display = 'block';
  } else { cWarn.style.display = 'none'; }
}
document.getElementById('edit-shift').addEventListener('change', () => checkEditConflicts());
document.getElementById('edit-driver').addEventListener('change', () => checkEditConflicts());
document.getElementById('edit-conductor').addEventListener('change', () => checkEditConflicts());
editBusSelect.addEventListener('change', () => checkEditConflicts());
editBusSelect.addEventListener('input', () => checkEditConflicts());

document.querySelectorAll('.styled-table tbody tr .btn-row-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const tr = btn.closest('tr');
    if (!tr) return;
    document.getElementById('edit-assignment-id').value = tr.dataset.assignmentId || '';
    document.getElementById('edit-assigned-date').value = tr.dataset.assignedDate || '';
    document.getElementById('edit-shift').value = tr.dataset.shift || 'Morning';
    editBusSelect.value = tr.dataset.busReg || '';
    document.getElementById('edit-driver').value = tr.dataset.driverId || '';
    document.getElementById('edit-conductor').value = tr.dataset.conductorId || '';
    fillEditBusRoute();
    checkEditConflicts();
    editModal.classList.remove('hidden');
  });
});

// Show override remark/UI when override button clicked
const overrideLabel = document.getElementById('label-override-remark');
function showOverride() {
  if (overrideLabel) overrideLabel.style.display = 'block';
  if (modal) modal.classList.remove('hidden');
}
document.getElementById('btnOverrideDriver')?.addEventListener('click', showOverride);
document.getElementById('btnOverrideConductor')?.addEventListener('click', showOverride);

// Table filtering by shift, route number and destination
  const filterShift = document.getElementById('filter-shift');
  const filterRoute = document.getElementById('filter-route');
const filterDest = document.getElementById('filter-dest');
const tableRows = Array.from(document.querySelectorAll('.styled-table tbody tr'));
function filterTable() {
  const s = filterShift.value;
  const r = filterRoute.value;
  const d = (filterDest.value || '').toLowerCase();
  tableRows.forEach(tr=>{
    const sh = (tr.dataset.shift || '').toString();
    const rn = (tr.dataset.routeNo || '').toString();
    const rnDisplay = (tr.dataset.routeDisplay || '').toLowerCase();
    let ok = true;
    if (s && s !== 'all' && sh !== s) ok = false;
    if (r && r !== 'all' && rn !== r) ok = false;
    if (d && !rnDisplay.includes(d)) ok = false;
    tr.style.display = ok ? '' : 'none';
  });
}
filterShift.addEventListener('change', filterTable);
filterRoute.addEventListener('change', filterTable);
filterDest.addEventListener('input', filterTable);
</script>
