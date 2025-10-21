<?php
// $summary = $summary ?? [];
// $rows    = $rows    ?? [];
// $routes  = $routes  ?? [];
// $buses   = $buses   ?? [];
?>
<section id="fleetPage" class="section">
  <div class="title-card">
    <h1 class="title-heading">Fleet Management</h1>
    <p class="title-sub">Manage and monitor your bus fleet</p>
  </div>

  <button id="btnAddBus" class="btn btn-secondary">+ Add New Bus</button>

  <!-- Summary cards -->
  <div class="grid grid-4 gap-4 mt-4">
    <?php if (!empty($summary)): foreach ($summary as $c): ?>
      <div class="card p-16">
        <div class="value <?= htmlspecialchars($c['class'] ?? '') ?>"><?= htmlspecialchars($c['value'] ?? '0') ?></div>
        <p class="muted"><?= htmlspecialchars($c['label'] ?? '') ?></p>
      </div>
    <?php endforeach; else: ?>
      <div class="empty-note">No summary available.</div>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title primary">Bus Location Filters</div></div>
    <div class="card__body">
      <div class="form-grid">
        <div class="form-group">
          <label>Route Number</label>
          <div class="select">
            <select name="route_no">
              <option value="">All Routes</option>
              <?php foreach ($routes as $r): ?>
                <option value="<?= htmlspecialchars($r['route_no']) ?>">
                  <?= htmlspecialchars(($r['route_no'] ?? '') . ' - ' . ($r['name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Bus Number</label>
          <div class="select">
            <select name="reg_no">
              <option value="">All Buses</option>
              <?php foreach ($buses as $b): ?>
                <option value="<?= htmlspecialchars($b['reg_no']) ?>"><?= htmlspecialchars($b['reg_no']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group span-2">
          <label>Search</label>
          <input type="text" class="input" placeholder="Search by bus number, route number, or route...">
        </div>
      </div>

      <div class="actions mt-4">
        <button class="btn btn-outline secondary" type="button">Filter</button>
        <button class="btn btn-outline secondary" type="button">Export</button>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card mt-6">
    <div class="card__head"><div class="card__title primary">Fleet Overview</div></div>
    <?php if (!empty($rows)): ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Bus Number</th>
              <th>Route</th>
              <th>Route Number</th>
              <th>Status</th>
              <th>Current Location</th>
              <th>Capacity</th>
              <th>Next Service</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <?php
              $reg_no     = (string)($r['reg_no'] ?? '');
              $status     = (string)($r['status'] ?? '');
              $capacity   = (string)($r['capacity'] ?? '');
              $chassis_no = (string)($r['chassis_no'] ?? '');
              $badge = $status === 'Active' ? 'badge-green'
                     : ($status === 'Maintenance' ? 'badge-yellow'
                     : ($status === 'Inactive' ? 'badge-red' : ''));
            ?>
            <tr class="<?= $i % 2 === 0 ? 'alt' : '' ?>">
              <td class="primary fw-600"><?= htmlspecialchars($reg_no) ?></td>
              <td><?= htmlspecialchars($r['route'] ?? '—') ?></td>
              <td><span class="badge badge-outline badge-secondary"><?= htmlspecialchars($r['route_no'] ?? '') ?></span></td>
              <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
              <td><?= htmlspecialchars($r['current_location'] ?? '—') ?></td>
              <td><?= (int)($r['capacity'] ?? 0) ?> seats</td>
              <td><?= htmlspecialchars($r['next_service'] ?? '—') ?></td>
              <td>
                <div class="actions-inline">
                  <button class="btn btn-outline small js-edit"
                          type="button"
                          data-reg="<?= htmlspecialchars($reg_no) ?>"
                          data-status="<?= htmlspecialchars($status) ?>"
                          data-capacity="<?= htmlspecialchars($capacity) ?>"
                          data-chassis="<?= htmlspecialchars($chassis_no) ?>">Edit</button>

                  <button class="btn btn-outline small js-delete"
                          type="button"
                          data-reg="<?= htmlspecialchars($reg_no) ?>">Delete</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-note p-16">No buses found.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ============= JS Popups / Modals (no forms posting) ============= -->

<!-- Create Bus -->
<div id="modalCreateBus" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal__overlay" data-close-modal></div>
  <div class="modal__card">
    <div class="modal__head">
      <div class="modal__title">Add New Bus</div>
      <button class="modal__close" data-close-modal>&times;</button>
    </div>
    <div class="modal__body">
      <div class="form-grid modal-grid">
        <div class="form-group">
          <label>Bus Number <span class="req">*</span></label>
          <input class="input" id="create_reg_no" placeholder="e.g., NA-1234">
        </div>
        <div class="form-group">
          <label>Chassis No</label>
          <input class="input" id="create_chassis_no" placeholder="Optional">
        </div>
        <div class="form-group">
          <label>Capacity</label>
          <input class="input" type="number" min="1" step="1" id="create_capacity" placeholder="e.g., 54">
        </div>
        <div class="form-group">
          <label>Status</label>
          <div class="select">
            <select id="create_status">
              <option value="Active">Active</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-primary" id="btnSaveCreate">Save Bus</button>
    </div>
  </div>
</div>

<!-- Edit Bus -->
<div id="modalEditBus" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal__overlay" data-close-modal></div>
  <div class="modal__card">
    <div class="modal__head">
      <div class="modal__title">Edit Bus</div>
      <button class="modal__close" data-close-modal>&times;</button>
    </div>
    <div class="modal__body">
      <div class="form-grid modal-grid">
        <div class="form-group">
          <label>Bus Number</label>
          <input class="input" id="edit_reg_no" readonly>
        </div>
        <div class="form-group">
          <label>Chassis No</label>
          <input class="input" id="edit_chassis_no">
        </div>
        <div class="form-group">
          <label>Capacity</label>
          <input class="input" type="number" min="1" step="1" id="edit_capacity">
        </div>
        <div class="form-group">
          <label>Status</label>
          <div class="select">
            <select id="edit_status">
              <option value="Active">Active</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-primary" id="btnSaveEdit">Update Bus</button>
    </div>
  </div>
</div>

<!-- Delete Confirm -->
<div id="modalDeleteBus" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal__overlay" data-close-modal></div>
  <div class="modal__card">
    <div class="modal__head">
      <div class="modal__title">Delete Bus</div>
      <button class="modal__close" data-close-modal>&times;</button>
    </div>
    <div class="modal__body">
      <p>Are you sure you want to delete <strong id="delBusReg"></strong>?</p>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-secondary" id="btnConfirmDelete">Delete</button>
    </div>
  </div>
</div>

<!-- JS -->
<script src="<?= BASE_URL; ?>/assets/js/fleet.js"></script>
