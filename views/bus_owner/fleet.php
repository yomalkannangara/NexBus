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
          <th>Next Service</th>
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
            ?>
            <tr>
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
              <td><?= htmlspecialchars($b['next_service'] ?? ''); ?></td>
              <td>
                <div class="action-buttons">
                  <a href="<?= BASE_URL; ?>/fleet/edit/<?= (int)($b['id'] ?? 0); ?>" class="icon-btn" title="View">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M1 9s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z" stroke="currentColor" stroke-width="2"/>
                      <circle cx="9" cy="9" r="2" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </a>
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
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:#6B7280;">
              No buses found. Click "Add New Bus" to add your first bus.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
