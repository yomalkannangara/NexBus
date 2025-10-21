<?php
// Content-only Fleet view (structure only)
// Expects: $buses (array), BASE_URL defined by layout.
?>

<header class="page-header">
  <div>
    <h2 class="page-title">Fleet Management</h2>
    <p class="page-subtitle">Manage and monitor your bus fleet</p>
  </div>
  <a href="<?= BASE_URL; ?>/fleet/create" class="add-bus-btn">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
      <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    Add New Bus
  </a>
</header>

<!-- Fleet Overview Table -->
<div class="card">
  <h3 class="card-title">Fleet Overview</h3>

  <div class="table-container">
    <table class="data-table" id="fleet-table">
      <thead>
        <tr>
          <th>Bus Number</th>
          <th>Route</th>
          <th>Route Number</th>
          <th>Status</th>
          <th>Current Location</th>
          <th>Capacity</th>
          <th>Assigned Driver</th>
          <th>Assigned Conductor</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!empty($buses)): ?>
          <?php foreach ($buses as $b): ?>
            <?php
              $status = (string)($b['status'] ?? 'Active');
              $map    = ['Active'=>'status-active','Maintenance'=>'status-maintenance','Out of Service'=>'status-out'];
              $cls    = $map[$status] ?? 'status-active';

              // new: resolve assigned names with fallbacks
              $drvName  = $b['driver_name']     ?? $b['assigned_driver']   ?? $b['driver']    ?? null;
              $condName = $b['conductor_name']  ?? $b['assigned_conductor']?? $b['conductor'] ?? null;
            ?>
            <tr data-bus-id="<?= (int)($b['id'] ?? 0); ?>">
              <td><strong><?= htmlspecialchars($b['bus_number'] ?? ''); ?></strong></td>
              <td><?= htmlspecialchars($b['route'] ?? ''); ?></td>
              <td><span class="badge badge-yellow"><?= htmlspecialchars($b['route_number'] ?? ''); ?></span></td>
              <td>
                <span class="status-badge <?= $cls; ?> js-status-badge">
                  <?= htmlspecialchars($status); ?>
                </span>
              </td>
              <td><?= htmlspecialchars($b['current_location'] ?? ''); ?></td>
              <td><?= (int)($b['capacity'] ?? 0); ?> seats</td>
              <td class="td-driver">
                <?php if (!empty($drvName)): ?>
                  <?= htmlspecialchars($drvName); ?>
                <?php else: ?>
                  <span class="text-secondary">Unassigned</span>
                <?php endif; ?>
              </td>
              <td class="td-conductor">
                <?php if (!empty($condName)): ?>
                  <?= htmlspecialchars($condName); ?>
                <?php else: ?>
                  <span class="text-secondary">Unassigned</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-buttons">
                  <a href="<?= BASE_URL; ?>/fleet/edit/<?= (int)($b['id'] ?? 0); ?>" class="icon-btn icon-btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                  <a href="<?= BASE_URL; ?>/fleet/delete/<?= (int)($b['id'] ?? 0); ?>" class="icon-btn icon-btn-delete js-del" title="Delete">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                  </a>
                  <a
                    href="#"
                    class="icon-btn js-assign"
                    title="Assign Driver/Conductor"
                    data-bus-id="<?= (int)($b['id'] ?? 0); ?>"
                    data-driver-id="<?= isset($b['driver_id']) ? (int)$b['driver_id'] : 0; ?>"
                    data-conductor-id="<?= isset($b['conductor_id']) ? (int)$b['conductor_id'] : 0; ?>"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M16 11a4 4 0 1 0-3.999-4A4 4 0 0 0 16 11Zm-8 3a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm8 2c-2.21 0-6 1.11-6 3.33V22h12v-2.67C22 17.11 18.21 16 16 16Zm-8-1c-2.67 0-8 1.34-8 4v3h6v-2.67" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:40px;color:#6B7280;">
              No buses found. Click "Add New Bus" to add your first bus.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Assign Driver/Conductor Modal -->
<div class="modal" id="assignModal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog">
    <div class="modal__header">
      <h3 id="assignModalTitle">Assign Driver & Conductor</h3>
      <button class="modal__close" id="assignClose" aria-label="Close">×</button>
    </div>
    <form class="modal__form" id="assignForm" action="<?= BASE_URL; ?>/fleet/assign" method="POST">
      <input type="hidden" name="bus_id" id="assign_bus_id" />
      <div class="form-grid">
        <div class="form-field">
          <label for="assign_driver_id">Driver ID</label>
          <input type="text" id="assign_driver_id" name="driver_id" placeholder="e.g., 12" />
        </div>
        <div class="form-field">
          <label for="assign_conductor_id">Conductor ID</label>
          <input type="text" id="assign_conductor_id" name="conductor_id" placeholder="e.g., 8" />
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn-secondary" id="assignCancel">Cancel</button>
        <button type="submit" class="btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>
