<?php /** @var array $rows,$buses,$drivers,$conductors,$routes,$msg */ ?>
<section class="section">
  <div class="title-card">
    <h1 class="title-heading">SLTB Daily Bus Assignments</h1>
    <p class="title-sub">Manage today’s driver and conductor allocations</p>
  </div>

  <?php if(!empty($msg)): ?>
    <div class="notice success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="table-card">
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
          <tr>
            <td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
            <td><?= htmlspecialchars($r['route_name'] ?? '-') ?></td>
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
        <select name="bus_reg_no" required>
          <?php foreach($buses as $b): ?>
            <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
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
</script>
