<?php
/** @var array $rows,$buses,$drivers,$conductors,$routes,$msg,$availability */
$availability  = $availability  ?? [];
$avBuses       = (int)($availability['available_buses']      ?? 0);
$totalBuses    = (int)($availability['total_buses']          ?? 0);
$avDrivers     = (int)($availability['available_drivers']    ?? 0);
$totalDrivers  = (int)($availability['total_drivers']        ?? 0);
$avConductors  = (int)($availability['available_conductors'] ?? 0);
$totalConduct  = (int)($availability['total_conductors']     ?? 0);
?>
<style>
/* ── Analytics chips ─────────────────────────────────── */
.avail-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.avail-chip {
  display:flex; align-items:center; gap:10px;
  background:#fff; border:1.5px solid #e5e7eb; border-radius:14px;
  padding:11px 18px; box-shadow:0 1px 4px rgba(0,0,0,.06);
  cursor:pointer; transition:box-shadow .15s, transform .12s; user-select:none;
}
.avail-chip:hover { box-shadow:0 4px 14px rgba(0,0,0,.1); transform:translateY(-2px); }
.avail-chip-icon {
  width:38px; height:38px; border-radius:10px;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.avail-chip-body { display:flex; flex-direction:column; }
.avail-chip-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; }
.avail-chip-value { font-size:22px; font-weight:800; line-height:1; }
.avail-chip-sub   { font-size:11px; color:#9ca3af; margin-top:1px; }
.chip--green .avail-chip-icon { background:#dcfce7; color:#16a34a; }
.chip--green .avail-chip-value { color:#16a34a; }
.chip--red   .avail-chip-icon { background:#fee2e2; color:#dc2626; }
.chip--red   .avail-chip-value { color:#dc2626; }
/* ── Table ───────────────────────────────────────────── */
.asgn-wrap { overflow-x:auto; }
.asgn-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.asgn-table th { background:#80143c; color:#fff; padding:10px 14px; font-weight:600; text-align:left; white-space:nowrap; }
.asgn-table th:first-child { border-radius:8px 0 0 0; }
.asgn-table th:last-child  { border-radius:0 8px 0 0; }
.asgn-table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.asgn-table tbody tr:hover { background:#fdf8fb; }
.asgn-table .muted { color:#9ca3af; font-style:italic; }
/* ── Inline badges ───────────────────────────────────── */
.abadge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11.5px; font-weight:600; }
.abadge-green  { background:#dcfce7; color:#16a34a; }
.abadge-red    { background:#fee2e2; color:#dc2626; }
.abadge-amber  { background:#fef9e7; color:#92400e; }
.abadge-blue   { background:#dbeafe; color:#1d4ed8; }
.abadge-gray   { background:#f3f4f6; color:#6b7280; }
/* ── Filters bar ─────────────────────────────────────── */
.asgn-filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
.asgn-filters label { font-size:12px; font-weight:600; color:#6b7280; }
.asgn-filters select, .asgn-filters input[type=text] {
  padding:7px 10px; border:1.5px solid #d1d5db; border-radius:8px;
  font-size:13px; background:#fff; color:#1f2937;
}
/* ── Add button ──────────────────────────────────────── */
.btn-asgn-add {
  display:inline-flex; align-items:center; gap:7px;
  margin-top:18px; padding:10px 22px;
  background:#80143c; color:#fff; border:none;
  border-radius:12px; font-size:14px; font-weight:700;
  cursor:pointer; transition:background .15s;
}
.btn-asgn-add:hover { background:#60102e; }
/* ── Row action buttons ──────────────────────────────── */
.btn-sm { padding:4px 12px; border-radius:7px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid; }
.btn-sm-edit   { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.btn-sm-delete { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }
.btn-sm-edit:hover   { background:#dbeafe; }
.btn-sm-delete:hover { background:#fecaca; }
/* ── Modal overlay ───────────────────────────────────── */
.asgn-modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;
  display:flex; align-items:center; justify-content:center; padding:16px;
}
.asgn-modal-overlay.hidden { display:none; }
.asgn-modal {
  background:#fff; border-radius:18px;
  box-shadow:0 16px 48px rgba(0,0,0,.22);
  width:100%; max-width:620px; max-height:92vh;
  overflow-y:auto; padding:30px 32px; position:relative;
}
.asgn-modal h2 { font-size:19px; font-weight:800; color:#80143c; margin:0 0 22px; display:flex; align-items:center; gap:9px; }
.asgn-modal-close {
  position:absolute; top:14px; right:16px;
  background:none; border:none; cursor:pointer; font-size:22px; color:#9ca3af; line-height:1;
}
.asgn-modal-close:hover { color:#80143c; }
/* ── Modal form fields ───────────────────────────────── */
.asgn-form-row { margin-bottom:16px; }
.asgn-form-row label { display:block; font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; }
.asgn-form-row input, .asgn-form-row select, .asgn-form-row textarea {
  width:100%; padding:9px 12px; border:1.5px solid #d1d5db;
  border-radius:9px; font-size:13.5px; color:#1f2937;
  background:#fff; transition:border-color .15s; box-sizing:border-box;
}
.asgn-form-row input:focus,.asgn-form-row select:focus { outline:none; border-color:#80143c; }
.asgn-form-row input[readonly] { background:#f9fafb; color:#6b7280; }
.asgn-two-col { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.asgn-section-label {
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
  color:#80143c; border-bottom:1.5px solid #f3d5de; padding-bottom:5px; margin:20px 0 14px;
}
/* ── Turn pills ──────────────────────────────────────── */
.turn-pills { display:flex; flex-wrap:wrap; gap:8px; }
.turn-pill {
  display:flex; align-items:center; gap:6px;
  padding:7px 14px; border:1.5px solid #d1d5db; border-radius:10px;
  cursor:pointer; font-size:13px; font-weight:600;
  transition:border-color .15s, background .15s;
}
.turn-pill input[type=radio] { display:none; }
.turn-pill.selected { border-color:#80143c; background:#fce8ef; color:#80143c; }
.turn-pill-time { font-size:11px; font-weight:400; color:#6b7280; }
.turn-pill.selected .turn-pill-time { color:#b0456a; }
/* ── Recurrence ──────────────────────────────────────── */
.recur-radio-group { display:flex; gap:10px; flex-wrap:wrap; }
.recur-radio-opt {
  display:flex; align-items:center; gap:6px;
  padding:7px 14px; border:1.5px solid #d1d5db; border-radius:10px;
  cursor:pointer; font-size:13px; font-weight:600;
  transition:border-color .15s, background .15s;
}
.recur-radio-opt input { display:none; }
.recur-radio-opt.selected { border-color:#80143c; background:#fce8ef; color:#80143c; }
.day-check-group { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.day-check-lbl {
  padding:5px 11px; border:1.5px solid #d1d5db; border-radius:8px;
  font-size:12px; font-weight:700; cursor:pointer; transition:border-color .15s, background .15s;
}
.day-check-lbl input { display:none; }
.day-check-lbl.checked { border-color:#80143c; background:#fce8ef; color:#80143c; }
/* ── Toggle switch ───────────────────────────────────── */
.toggle-wrap { display:flex; align-items:center; gap:10px; margin-top:6px; }
.toggle-switch { position:relative; width:40px; height:22px; }
.toggle-switch input { display:none; }
.toggle-slider { position:absolute; inset:0; border-radius:999px; background:#d1d5db; cursor:pointer; transition:background .2s; }
.toggle-slider::before { content:''; position:absolute; width:16px; height:16px; left:3px; top:3px; border-radius:50%; background:#fff; transition:transform .2s; }
.toggle-switch input:checked + .toggle-slider { background:#80143c; }
.toggle-switch input:checked + .toggle-slider::before { transform:translateX(18px); }
/* ── Conflict warning ────────────────────────────────── */
.asgn-warn { display:none; padding:7px 12px; border-radius:8px; background:#fef9e7; border:1px solid #fde68a; color:#92400e; font-size:12.5px; margin-top:4px; }
.asgn-warn.show { display:block; }
/* ── Modal action row ────────────────────────────────── */
.asgn-action-row { display:flex; gap:10px; justify-content:flex-end; margin-top:24px; }
.btn-maroon { background:#80143c; color:#fff; border:none; padding:10px 24px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:background .15s; }
.btn-maroon:hover { background:#60102e; }
.btn-outline-maroon { background:#fff; color:#80143c; border:1.5px solid #80143c; padding:10px 20px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
.btn-outline-maroon:hover { background:#fce8ef; }
@media (max-width:640px) {
  .asgn-modal { padding:20px 16px; }
  .asgn-two-col { grid-template-columns:1fr; }
  .avail-bar { gap:8px; }
}
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

  <!-- ── Mini Analytics Bar ── -->
  <?php
  $busColor = $avBuses > 0 ? 'green' : 'red';
  $drvColor = $avDrivers > 0 ? 'green' : 'red';
  $conColor = $avConductors > 0 ? 'green' : 'red';
  ?>
  <div class="avail-bar">
    <div class="avail-chip chip--<?= $busColor ?>" title="Available buses today">
      <div class="avail-chip-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="1" y="7" width="22" height="13" rx="2"/><path d="M1 13h22M5 20v2M19 20v2M7 7V5a2 2 0 012-2h6a2 2 0 012 2v2"/>
        </svg>
      </div>
      <div class="avail-chip-body">
        <span class="avail-chip-label">Available Buses</span>
        <span class="avail-chip-value"><?= $avBuses ?></span>
        <span class="avail-chip-sub">of <?= $totalBuses ?> active</span>
      </div>
    </div>
    <div class="avail-chip chip--<?= $drvColor ?>" title="Available drivers today">
      <div class="avail-chip-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <div class="avail-chip-body">
        <span class="avail-chip-label">Available Drivers</span>
        <span class="avail-chip-value"><?= $avDrivers ?></span>
        <span class="avail-chip-sub">of <?= $totalDrivers ?> active</span>
      </div>
    </div>
    <div class="avail-chip chip--<?= $conColor ?>" title="Available conductors today">
      <div class="avail-chip-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
      </div>
      <div class="avail-chip-body">
        <span class="avail-chip-label">Available Conductors</span>
        <span class="avail-chip-value"><?= $avConductors ?></span>
        <span class="avail-chip-sub">of <?= $totalConduct ?> active</span>
      </div>
    </div>
  </div>

  <!-- ── Assignments Table ── -->
  <div class="table-card">
    <div class="asgn-filters">
      <label>Route:
        <select id="filter-route">
          <option value="all">All Routes</option>
          <?php foreach($routes as $rt): ?>
            <option value="<?= htmlspecialchars($rt['route_no']) ?>"><?= htmlspecialchars($rt['route_no'] . ' — ' . $rt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Destination:
        <input type="text" id="filter-dest" placeholder="Filter by destination…">
      </label>
    </div>
    <div class="asgn-wrap">
      <table class="asgn-table" id="asgnTable">
        <thead>
          <tr>
            <th>Bus</th>
            <th>Turn / Time</th>
            <th>Route</th>
            <th>Route&nbsp;No</th>
            <th>Status</th>
            <th>Capacity</th>
            <th>Driver</th>
            <th>Conductor</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr
              data-assignment-id="<?= (int)$r['assignment_id'] ?>"
              data-assigned-date="<?= htmlspecialchars($r['assigned_date'] ?? date('Y-m-d')) ?>"
              data-shift="<?= htmlspecialchars($r['shift'] ?? '') ?>"
              data-bus-reg="<?= htmlspecialchars($r['bus_reg_no'] ?? '') ?>"
              data-driver-id="<?= (int)($r['sltb_driver_id'] ?? 0) ?>"
              data-conductor-id="<?= (int)($r['sltb_conductor_id'] ?? 0) ?>"
              data-route-no="<?= htmlspecialchars($r['route_no'] ?? '') ?>"
              data-route-display="<?= htmlspecialchars($r['route_display'] ?? ($r['route_start'].' → '.$r['route_end'])) ?>"
              data-timetable-id="<?= (int)($r['timetable_id'] ?? 0) ?>"
            >
              <td><a href="/O/bus_profile?bus_reg_no=<?= urlencode($r['bus_reg_no'] ?? '') ?>" style="color:#80143c;font-weight:700;"><?= htmlspecialchars($r['bus_reg_no']) ?></a></td>
              <td>
                <?php $dep = $r['departure_time'] ?? $r['shift'] ?? ''; ?>
                <span class="abadge abadge-blue"><?= htmlspecialchars(strlen($dep)>5 ? substr($dep,0,5) : $dep) ?></span>
              </td>
              <td><?= htmlspecialchars($r['route_start'] ? ($r['route_start'].' → '.$r['route_end']) : ($r['route_name'] ?? '-')) ?></td>
              <td><span class="abadge abadge-gray"><?= htmlspecialchars($r['route_no'] ?? '-') ?></span></td>
              <td>
                <?php $st = strtolower($r['bus_status'] ?? 'active'); ?>
                <span class="abadge <?= $st==='active'?'abadge-green':($st==='maintenance'?'abadge-amber':'abadge-red') ?>"><?= htmlspecialchars($r['bus_status'] ?? 'Active') ?></span>
              </td>
              <td><?= htmlspecialchars(($r['capacity'] ?? '0').' seats') ?></td>
              <td><?= $r['driver_name'] ? htmlspecialchars($r['driver_name']) : '<span class="muted">Unassigned</span>' ?></td>
              <td><?= $r['conductor_name'] ? htmlspecialchars($r['conductor_name']) : '<span class="muted">Unassigned</span>' ?></td>
              <td style="white-space:nowrap;">
                <button type="button" class="btn-sm btn-sm-edit btn-row-edit">Edit</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this assignment?')">
                  <input type="hidden" name="action" value="delete_assignment">
                  <input type="hidden" name="assignment_id" value="<?= (int)$r['assignment_id'] ?>">
                  <button class="btn-sm btn-sm-delete">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($rows)): ?>
            <tr><td colspan="9" style="text-align:center;padding:28px;color:#9ca3af;">No assignments for today yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <button id="btnAddAssignment" class="btn-asgn-add">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Assignment
  </button>
</section>

<!-- ════════ ADD MODAL ════════ -->
<div id="addModalOverlay" class="asgn-modal-overlay hidden">
  <div class="asgn-modal">
    <button class="asgn-modal-close" id="closeAddModal" aria-label="Close">×</button>
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Assign Bus
    </h2>
    <form method="post" id="addForm">
      <input type="hidden" name="action" value="create_assignment">
      <input type="hidden" name="assigned_date" id="add-assigned-date" value="<?= date('Y-m-d') ?>">
      <input type="hidden" name="shift" id="add-shift" value="">
      <input type="hidden" name="timetable_id" id="add-timetable-id" value="">

      <!-- Bus -->
      <div class="asgn-form-row">
        <label>Bus Registration No</label>
        <input list="add-buses-dl" name="bus_reg_no" id="add-bus" required placeholder="Type or select bus reg no">
        <datalist id="add-buses-dl">
          <?php foreach($buses as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no'].' — Route '.($b['route_no']??'N/A').' — '.($b['route_name']??'')) ?></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="asgn-two-col">
        <div class="asgn-form-row"><label>Route No</label><input type="text" id="add-route-no" readonly></div>
        <div class="asgn-form-row"><label>Seat Capacity</label><input type="text" id="add-capacity" readonly></div>
      </div>
      <div class="asgn-form-row"><label>Destination</label><input type="text" id="add-route-name" readonly></div>

      <!-- Effective Period -->
      <div class="asgn-section-label">Effective Period</div>
      <div class="asgn-two-col">
        <div class="asgn-form-row"><label>From Date</label><input type="date" name="period_from" id="add-period-from" value="<?= date('Y-m-d') ?>"></div>
        <div class="asgn-form-row"><label>To Date</label><input type="date" name="period_to" id="add-period-to" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <div class="asgn-form-row">
        <div class="toggle-wrap">
          <label class="toggle-switch">
            <input type="checkbox" id="add-until-notice" name="until_further_notice" value="1">
            <span class="toggle-slider"></span>
          </label>
          <span style="font-size:13px;font-weight:600;color:#374151;">Until Further Notice</span>
          <span style="font-size:11.5px;color:#9ca3af;">(ignores To Date)</span>
        </div>
      </div>

      <!-- Recurrence -->
      <div class="asgn-section-label">Recurrence</div>
      <div class="asgn-form-row">
        <div class="recur-radio-group" id="add-recur-group">
          <label class="recur-radio-opt selected">
            <input type="radio" name="recurrence" value="weekdays" checked> All Weekdays
          </label>
          <label class="recur-radio-opt">
            <input type="radio" name="recurrence" value="specific"> Specific Days
          </label>
        </div>
      </div>
      <div class="asgn-form-row" id="add-days-row" style="display:none;">
        <label>Select Days</label>
        <div class="day-check-group">
          <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
            <label class="day-check-lbl">
              <input type="checkbox" name="days[]" value="<?= $day ?>"> <?= $day ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Staff -->
      <div class="asgn-section-label">Staff Assignment</div>
      <div class="asgn-form-row">
        <label>Driver</label>
        <select name="sltb_driver_id" id="add-driver" required>
          <option value="">— Select Driver —</option>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['sltb_driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="asgn-warn" id="add-driver-warn"></div>
      </div>
      <div class="asgn-form-row">
        <label>Conductor</label>
        <select name="sltb_conductor_id" id="add-conductor" required>
          <option value="">— Select Conductor —</option>
          <?php foreach($conductors as $c): ?>
            <option value="<?= (int)$c['sltb_conductor_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="asgn-warn" id="add-conductor-warn"></div>
      </div>
      <div class="asgn-form-row" id="add-override-row" style="display:none;">
        <label>Override Remark</label>
        <textarea name="override_remark" rows="3" placeholder="Explain why you are overriding the existing assignment" style="resize:vertical;"></textarea>
      </div>

      <div class="asgn-action-row">
        <button type="button" id="closeAddModal2" class="btn-outline-maroon">Cancel</button>
        <button type="submit" class="btn-maroon">Save Assignment</button>
      </div>
    </form>
  </div>
</div>

<!-- ════════ EDIT MODAL ════════ -->
<div id="editModalOverlay" class="asgn-modal-overlay hidden">
  <div class="asgn-modal">
    <button class="asgn-modal-close" id="closeEditModal" aria-label="Close">×</button>
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit Assignment
    </h2>
    <form method="post" id="editForm">
      <input type="hidden" name="action" value="update_assignment">
      <input type="hidden" name="assignment_id" id="edit-assignment-id">

      <div class="asgn-form-row">
        <label>Date</label>
        <input type="date" name="assigned_date" id="edit-assigned-date" required>
      </div>
      <div class="asgn-form-row">
        <label>Shift / Turn Time</label>
        <input type="text" name="shift" id="edit-shift" required placeholder="e.g. 07:30">
      </div>
      <div class="asgn-form-row">
        <label>Bus Registration</label>
        <input list="add-buses-dl" name="bus_reg_no" id="edit-bus" required placeholder="Type or select bus reg no">
      </div>
      <div class="asgn-two-col">
        <div class="asgn-form-row"><label>Route No</label><input type="text" id="edit-route-no" readonly></div>
        <div class="asgn-form-row"><label>Seat Capacity</label><input type="text" id="edit-capacity" readonly></div>
      </div>
      <div class="asgn-form-row"><label>Destination</label><input type="text" id="edit-route-name" readonly></div>

      <div class="asgn-section-label">Staff</div>
      <div class="asgn-form-row">
        <label>Driver</label>
        <select name="sltb_driver_id" id="edit-driver" required>
          <option value="">— Select Driver —</option>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['sltb_driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="asgn-warn" id="edit-driver-warn"></div>
      </div>
      <div class="asgn-form-row">
        <label>Conductor</label>
        <select name="sltb_conductor_id" id="edit-conductor" required>
          <option value="">— Select Conductor —</option>
          <?php foreach($conductors as $c): ?>
            <option value="<?= (int)$c['sltb_conductor_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="asgn-warn" id="edit-conductor-warn"></div>
      </div>

      <div class="asgn-action-row">
        <button type="button" id="closeEditModal2" class="btn-outline-maroon">Cancel</button>
        <button type="submit" class="btn-maroon">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Modal helpers ── */
function openModal(id) {
  const el = document.getElementById(id);
  if (el.parentNode !== document.body) document.body.appendChild(el);
  el.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  document.body.style.overflow = '';
}
document.getElementById('btnAddAssignment').onclick  = () => openModal('addModalOverlay');
document.getElementById('closeAddModal').onclick     = () => closeModal('addModalOverlay');
document.getElementById('closeAddModal2').onclick    = () => closeModal('addModalOverlay');
document.getElementById('closeEditModal').onclick    = () => closeModal('editModalOverlay');
document.getElementById('closeEditModal2').onclick   = () => closeModal('editModalOverlay');
document.querySelectorAll('.asgn-modal-overlay').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target === ov) closeModal(ov.id); });
});

/* ── Bus info map ── */
const busInfo = {};
<?php foreach($buses as $b): ?>
busInfo[<?= json_encode(strtoupper(trim((string)$b['reg_no']))) ?>] = {
  route_no:    <?= json_encode($b['route_no']    ?? '') ?>,
  route_name:  <?= json_encode($b['route_name']  ?? '') ?>,
  route_start: <?= json_encode($b['route_start'] ?? '') ?>,
  route_end:   <?= json_encode($b['route_end']   ?? '') ?>,
  capacity:    <?= (int)($b['capacity'] ?? 0) ?>
};
<?php endforeach; ?>

/* ── Bus auto-fill helpers ── */
function fillBus(inputId, routeNoId, routeNameId, capacityId) {
  const v    = (document.getElementById(inputId)?.value || '').trim().toUpperCase();
  const info = busInfo[v] || {};
  if (routeNoId)   document.getElementById(routeNoId).value   = info.route_no   || '';
  if (routeNameId) document.getElementById(routeNameId).value = info.route_name || (info.route_start && info.route_end ? info.route_start + ' → ' + info.route_end : '');
  if (capacityId)  document.getElementById(capacityId).value  = info.capacity   ? info.capacity + ' seats' : '';
}
const addBusEl  = document.getElementById('add-bus');
const editBusEl = document.getElementById('edit-bus');
['change','input'].forEach(ev => {
  addBusEl.addEventListener(ev, () => { fillBus('add-bus','add-route-no','add-route-name','add-capacity'); loadTurns(); });
  editBusEl.addEventListener(ev, () => fillBus('edit-bus','edit-route-no','edit-route-name','edit-capacity'));
});

/* ── Turn number pills (Add modal) ── */
let currentTurnConflicts = { drivers: {}, conductors: {} };

async function loadTurns() {
  const bus  = (addBusEl.value || '').trim().toUpperCase();
  const date = document.getElementById('add-period-from').value || '<?= date('Y-m-d') ?>';
  const container = document.getElementById('add-turn-container');
  if (!bus) {
    container.innerHTML = '<span style="color:#9ca3af;font-size:13px;">Select a bus to load available turns</span>';
    document.getElementById('add-shift').value = '';
    document.getElementById('add-timetable-id').value = '';
    return;
  }
  container.innerHTML = '<span style="color:#9ca3af;font-size:13px;">Loading turns…</span>';
  try {
    const res  = await fetch('/O/assignments/shifts?bus_reg_no=' + encodeURIComponent(bus) + '&date=' + encodeURIComponent(date), { headers: { Accept: 'application/json' } });
    const data = await res.json();
    if (!data.ok || !data.items?.length) {
      container.innerHTML = '<span style="color:#9ca3af;font-size:13px;">No timetable turns found for this bus</span>';
      document.getElementById('add-shift').value = '';
      document.getElementById('add-timetable-id').value = '';
      return;
    }
    container.innerHTML = '';
    data.items.forEach((item, idx) => {
      const dep = (item.departure_label || item.departure_time || '').slice(0, 5);
      const arr = (item.arrival_label   || item.arrival_time   || '').slice(0, 5);
      const pill = document.createElement('label');
      pill.className = 'turn-pill' + (idx === 0 ? ' selected' : '');
      pill.innerHTML = '<input type="radio" name="add_turn_pick" value="' + dep + '" data-timetable-id="' + (item.timetable_id || '') + '" ' + (idx === 0 ? 'checked' : '') + '>' +
        'Turn ' + (idx + 1) + '&nbsp;<span class="turn-pill-time">' + dep + (arr ? ' – ' + arr : '') + '</span>';
      pill.querySelector('input').addEventListener('change', function () {
        container.querySelectorAll('.turn-pill').forEach(p => p.classList.remove('selected'));
        pill.classList.add('selected');
        document.getElementById('add-shift').value = dep;
        document.getElementById('add-timetable-id').value = this.dataset.timetableId || '';
        loadConflictsAndCheck();
      });
      container.appendChild(pill);
    });
    const first = data.items[0];
    document.getElementById('add-shift').value = (first.departure_label || first.departure_time || '').slice(0, 5);
    document.getElementById('add-timetable-id').value = first.timetable_id || '';
    loadConflictsAndCheck();
  } catch (e) {
    container.innerHTML = '<span style="color:#dc2626;font-size:13px;">Failed to load turns. Try again.</span>';
  }
}

/* ── Staff conflict check via AJAX ── */
async function loadConflictsAndCheck() {
  const departure = document.getElementById('add-shift').value;
  if (!departure) { currentTurnConflicts = { drivers: {}, conductors: {} }; checkAddConflicts(); return; }
  try {
    const from  = document.getElementById('add-period-from').value || '<?= date('Y-m-d') ?>';
    const until = document.getElementById('add-until-notice').checked;
    const to    = until ? '2099-12-31' : (document.getElementById('add-period-to').value || from);
    const url   = '/O/assignments/staff-conflicts?departure=' + encodeURIComponent(departure)
                + '&period_from=' + encodeURIComponent(from)
                + '&period_to='   + encodeURIComponent(to);
    const res  = await fetch(url, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    if (data.ok) currentTurnConflicts = { drivers: data.drivers || {}, conductors: data.conductors || {} };
  } catch (e) { currentTurnConflicts = { drivers: {}, conductors: {} }; }
  checkAddConflicts();
}

function checkAddConflicts() {
  const bus         = (addBusEl.value || '').trim().toUpperCase();
  const driverId    = parseInt(document.getElementById('add-driver').value)    || 0;
  const conductorId = parseInt(document.getElementById('add-conductor').value) || 0;
  const dWarn = document.getElementById('add-driver-warn');
  const cWarn = document.getElementById('add-conductor-warn');
  const conflictBusD = currentTurnConflicts.drivers[driverId];
  const conflictBusC = currentTurnConflicts.conductors[conductorId];
  if (driverId && conflictBusD && conflictBusD !== bus) { dWarn.textContent = '\u26a0 Assigned to ' + conflictBusD + ' at this time.'; dWarn.classList.add('show'); }
  else dWarn.classList.remove('show');
  if (conductorId && conflictBusC && conflictBusC !== bus) { cWarn.textContent = '\u26a0 Assigned to ' + conflictBusC + ' at this time.'; cWarn.classList.add('show'); }
  else cWarn.classList.remove('show');
  /* Hide conflicting options so staff already assigned elsewhere don't appear */
  filterConflictingOptions('add-driver',    currentTurnConflicts.drivers,    bus);
  filterConflictingOptions('add-conductor', currentTurnConflicts.conductors, bus);
}

function filterConflictingOptions(selectId, conflictMap, currentBus) {
  const sel = document.getElementById(selectId);
  if (!sel) return;
  Array.from(sel.options).forEach(function (opt) {
    if (!opt.value) return; /* keep placeholder */
    const id  = parseInt(opt.value);
    const bus = conflictMap[id];
    /* hide only if conflicting with a DIFFERENT bus */
    opt.hidden   = !!(bus && bus !== currentBus);
    opt.disabled = opt.hidden;
  });
}

document.getElementById('add-period-from').addEventListener('change', loadTurns);
document.getElementById('add-period-to').addEventListener('change', loadConflictsAndCheck);
document.getElementById('add-driver').addEventListener('change', checkAddConflicts);
document.getElementById('add-conductor').addEventListener('change', checkAddConflicts);

/* ── Recurrence UI ── */
document.getElementById('add-recur-group').addEventListener('change', function () {
  const val = this.querySelector('input:checked')?.value;
  document.getElementById('add-days-row').style.display = val === 'specific' ? 'block' : 'none';
  this.querySelectorAll('.recur-radio-opt').forEach(opt => opt.classList.toggle('selected', opt.querySelector('input').checked));
});
document.querySelectorAll('.day-check-lbl').forEach(lbl => {
  lbl.addEventListener('click', function () { setTimeout(() => this.classList.toggle('checked', this.querySelector('input').checked), 0); });
});

/* ── Until Further Notice toggle ── */
document.getElementById('add-until-notice').addEventListener('change', function () {
  const toEl = document.getElementById('add-period-to');
  toEl.disabled = this.checked;
  toEl.style.opacity = this.checked ? '.4' : '1';
  loadConflictsAndCheck();
});

/* ── Edit conflict check (client-side from today's rows) ── */
const shiftDriverMap    = {};
const shiftConductorMap = {};
<?php foreach($rows as $r): ?>
<?php if(($r['sltb_driver_id'] ?? 0) || ($r['sltb_conductor_id'] ?? 0)): ?>
(function(){
  const sh  = <?= json_encode($r['shift'] ?? '') ?>;
  const bus = <?= json_encode($r['bus_reg_no'] ?? '') ?>;
  <?php if(!empty($r['sltb_driver_id'])): ?>
  if (!shiftDriverMap[sh]) shiftDriverMap[sh] = {};
  shiftDriverMap[sh][<?= (int)$r['sltb_driver_id'] ?>] = bus;
  <?php endif; ?>
  <?php if(!empty($r['sltb_conductor_id'])): ?>
  if (!shiftConductorMap[sh]) shiftConductorMap[sh] = {};
  shiftConductorMap[sh][<?= (int)$r['sltb_conductor_id'] ?>] = bus;
  <?php endif; ?>
})();
<?php endif; ?>
<?php endforeach; ?>

function checkEditConflicts() {
  const shift       = document.getElementById('edit-shift').value;
  const bus         = (editBusEl.value || '').trim().toUpperCase();
  const driverId    = parseInt(document.getElementById('edit-driver').value)    || 0;
  const conductorId = parseInt(document.getElementById('edit-conductor').value) || 0;
  const dWarn = document.getElementById('edit-driver-warn');
  const cWarn = document.getElementById('edit-conductor-warn');
  const dMap  = shiftDriverMap[shift]    || {};
  const cMap  = shiftConductorMap[shift] || {};
  if (driverId    && dMap[driverId]    && dMap[driverId]    !== bus) { dWarn.textContent = '⚠ Assigned to ' + dMap[driverId]    + ' for this shift today.'; dWarn.classList.add('show'); }
  else dWarn.classList.remove('show');
  if (conductorId && cMap[conductorId] && cMap[conductorId] !== bus) { cWarn.textContent = '⚠ Assigned to ' + cMap[conductorId] + ' for this shift today.'; cWarn.classList.add('show'); }
  else cWarn.classList.remove('show');
}
['change','input'].forEach(ev => {
  document.getElementById('edit-driver').addEventListener(ev, checkEditConflicts);
  document.getElementById('edit-conductor').addEventListener(ev, checkEditConflicts);
  document.getElementById('edit-shift').addEventListener(ev, checkEditConflicts);
  editBusEl.addEventListener(ev, checkEditConflicts);
});

/* ── Edit modal populate ── */
document.querySelectorAll('.btn-row-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const tr = btn.closest('tr');
    if (!tr) return;
    document.getElementById('edit-assignment-id').value = tr.dataset.assignmentId || '';
    document.getElementById('edit-assigned-date').value = tr.dataset.assignedDate || '';
    document.getElementById('edit-shift').value         = tr.dataset.shift || '';
    editBusEl.value                                      = tr.dataset.busReg || '';
    document.getElementById('edit-driver').value        = tr.dataset.driverId || '';
    document.getElementById('edit-conductor').value     = tr.dataset.conductorId || '';
    fillBus('edit-bus', 'edit-route-no', 'edit-route-name', 'edit-capacity');
    checkEditConflicts();
    openModal('editModalOverlay');
  });
});

/* ── Table filter ── */
const filterRouteEl = document.getElementById('filter-route');
const filterDestEl  = document.getElementById('filter-dest');
const tableRows     = Array.from(document.querySelectorAll('#asgnTable tbody tr'));
function filterTable() {
  const r = filterRouteEl.value;
  const d = (filterDestEl.value || '').toLowerCase();
  tableRows.forEach(tr => {
    const rn = tr.dataset.routeNo || '';
    const rd = (tr.dataset.routeDisplay || '').toLowerCase();
    let ok = true;
    if (r && r !== 'all' && rn !== r) ok = false;
    if (d && !rd.includes(d)) ok = false;
    tr.style.display = ok ? '' : 'none';
  });
}
filterRouteEl.addEventListener('change', filterTable);
filterDestEl.addEventListener('input', filterTable);

/* ── Override banner buttons ── */
function showOverrideField() {
  document.getElementById('add-override-row').style.display = 'block';
  openModal('addModalOverlay');
}
document.getElementById('btnOverrideDriver')?.addEventListener('click', showOverrideField);
document.getElementById('btnOverrideConductor')?.addEventListener('click', showOverrideField);
</script>
