<?php /** @var array $rows,$buses,$drivers,$conductors,$routes,$msg */ ?>
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
    <div class="filters" style="margin-bottom:12px;display:flex;gap:8px;align-items:center;">
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
          <tr data-route-no="<?= htmlspecialchars($r['route_no'] ?? '') ?>" data-route-display="<?= htmlspecialchars($r['route_display'] ?? ($r['route_start'].' → '.$r['route_end'])) ?>">
            <td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
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
              <button class="btn-icon edit" title="Edit"><i class="fa fa-edit"></i></button>
              <form method="post" class="inline" onsubmit="return confirm('Delete this assignment?')">
                <input type="hidden" name="action" value="delete_assignment">
                <input type="hidden" name="assignment_id" value="<?= (int)$r['assignment_id'] ?>">
                <button class="btn-icon delete" title="Delete"><i class="fa fa-trash"></i></button>
              </form>
              <button class="btn-icon assign" title="Assign Driver/Conductor"><i class="fa fa-users"></i></button>
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
      <label>Date <input type="date" name="assigned_date" value="<?= date('Y-m-d') ?>"></label>
      <label>Shift 
        <select name="shift">
          <option>Morning</option><option>Evening</option><option>Night</option>
        </select>
      </label>
      <label>Bus
        <input list="buses" name="bus_reg_no" id="bus-select" required placeholder="Type or pick bus reg no">
        <datalist id="buses">
          <?php foreach($buses as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars(($b['reg_no'] . ' — ' . ($b['route_name'] ?? ''))) ?></option>
          <?php endforeach; ?>
        </datalist>
      </label>
      <label>Route Number <input type="text" id="bus-route-no" readonly></label>
      <label>Destination <input type="text" id="bus-route-name" readonly></label>
      <label>Driver
        <select name="sltb_driver_id" required>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['sltb_driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Conductor
        <select name="sltb_conductor_id" required>
          <?php foreach($conductors as $c): ?>
            <option value="<?= (int)$c['sltb_conductor_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
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

<script>
const modal = document.getElementById('addModal');
document.getElementById('btnAddAssignment').onclick = ()=>modal.classList.remove('hidden');
document.getElementById('closeModal').onclick = ()=>modal.classList.add('hidden');

// Build a JS map of bus -> route info for auto-fill
const busInfo = {};
  <?php foreach($buses as $b): ?>
    busInfo[<?= json_encode($b['reg_no']) ?>] = {
    route_no: <?= json_encode($b['route_no'] ?? '') ?>,
    route_name: <?= json_encode($b['route_name'] ?? '') ?>,
    route_start: <?= json_encode($b['route_start'] ?? '') ?>,
    route_end: <?= json_encode($b['route_end'] ?? '') ?>
  };
<?php endforeach; ?>

const busSelect = document.getElementById('bus-select');
const routeNoInput = document.getElementById('bus-route-no');
const routeNameInput = document.getElementById('bus-route-name');
function fillBusRoute() {
  const v = busSelect.value;
  const info = busInfo[v] || {route_no:'', route_name:'', route_start:'', route_end:''};
  routeNoInput.value = info.route_no || '';
  routeNameInput.value = info.route_name || (info.route_start && info.route_end ? (info.route_start + ' → ' + info.route_end) : '');
}
busSelect.addEventListener('change', fillBusRoute);
fillBusRoute();

// Show override remark/UI when override button clicked
const overrideLabel = document.getElementById('label-override-remark');
function showOverride() {
  if (overrideLabel) overrideLabel.style.display = 'block';
  if (modal) modal.classList.remove('hidden');
}
document.getElementById('btnOverrideDriver')?.addEventListener('click', showOverride);
document.getElementById('btnOverrideConductor')?.addEventListener('click', showOverride);

// Table filtering by route number and destination
  const filterRoute = document.getElementById('filter-route');
const filterDest = document.getElementById('filter-dest');
const tableRows = Array.from(document.querySelectorAll('.styled-table tbody tr'));
function filterTable() {
  const r = filterRoute.value;
  const d = (filterDest.value || '').toLowerCase();
  tableRows.forEach(tr=>{
    const rn = (tr.dataset.routeNo || '').toString();
    const rnDisplay = (tr.dataset.routeDisplay || '').toLowerCase();
    let ok = true;
    if (r && r !== 'all' && rn !== r) ok = false;
    if (d && !rnDisplay.includes(d)) ok = false;
    tr.style.display = ok ? '' : 'none';
  });
}
filterRoute.addEventListener('change', filterTable);
filterDest.addEventListener('input', filterTable);
</script>
