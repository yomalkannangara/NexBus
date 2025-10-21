<?php
// app/views/bus_owner/drivers.php
// Content-only view; external app.js handles all behaviors.
// Expects: $drivers, $conductors, and $opId (passed by controller).
?>
<header class="page-header">
  <div>
    <h2 class="page-title">Drivers & Conductors</h2>
    <p class="page-subtitle">Manage staff information</p>
  </div>

  <div class="header-actions header-actions--tight">
    <a href="#" id="btnAddDriver" class="add-bus-btn" style="margin-right: 5px;">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      Add New Driver
    </a>
    <a href="#" id="btnAddConductor" class="add-bus-btn">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      Add New Conductor
    </a>
  </div>
</header>

<div class="card">
  <h3 class="card-title">Driver Registry</h3>

  <div class="table-container">
    <table class="data-table" id="drivers-table">
      <thead>
        <tr>
          <th>Driver</th>
          <th>License</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if (!empty($drivers)): ?>
        <?php foreach ($drivers as $d): ?>
          <tr>
            <td>
              <div class="driver-info">
                <div class="driver-avatar">
                  <?php
                  $name  = (string)($d['full_name'] ?? '');
                  $parts = preg_split('/\s+/', trim($name));
                  $ini   = '';
                  if (!empty($parts[0])) { $ini .= strtoupper(substr($parts[0], 0, 1)); }
                  if (count($parts) > 1) { $ini .= strtoupper(substr($parts[count($parts)-1], 0, 1)); }
                  echo htmlspecialchars($ini);
                  ?>
                </div>
                <div>
                  <div class="driver-name"><?= htmlspecialchars($d['full_name'] ?? ''); ?></div>
                  <div class="driver-id">DRV-<?= (int)($d['private_driver_id'] ?? 0); ?></div>
                </div>
              </div>
            </td>

            <td><strong><?= htmlspecialchars($d['license_no'] ?? ''); ?></strong></td>
            <td><?= htmlspecialchars($d['phone'] ?? ''); ?></td>

            <td>
              <?php
                $status = (string)($d['status'] ?? 'Active');
                $map    = ['Active'=>'status-active','Suspended'=>'status-inactive'];
                $cls    = $map[$status] ?? 'status-active';
                $toggleTitle = (strcasecmp($status, 'Active') === 0) ? 'Suspend' : 'Activate';
              ?>
              <span class="status-badge <?= $cls; ?> js-status-badge">
                <?= htmlspecialchars($status); ?>
              </span>
            </td>

            <td>
              <div class="action-buttons">
                <a href="#"
                   class="icon-btn js-toggle-driver-status" title="<?= htmlspecialchars($toggleTitle); ?>"
                   data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M1 9s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="9" cy="9" r="2" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-edit js-edit-driver" title="Edit"
                   data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-delete js-del" title="Delete"
                   data-driver-id="<?= (int)($d['private_driver_id'] ?? 0); ?>">
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
          <td colspan="5" style="text-align:center;padding:40px;color:#6B7280;">
            No drivers found. Click "Add New Driver" to add your first driver.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h3 class="card-title">Conductor Registry</h3>

  <div class="table-container">
    <table class="data-table" id="conductors-table">
      <thead>
        <tr>
          <th>Conductor</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if (!empty($conductors ?? [])): ?>
        <?php foreach ($conductors as $c): ?>
          <tr>
            <td>
              <div class="driver-info">
                <div class="driver-avatar">
                  <?php
                  $name  = (string)($c['full_name'] ?? '');
                  $parts = preg_split('/\s+/', trim($name));
                  $ini   = '';
                  if (!empty($parts[0])) { $ini .= strtoupper(substr($parts[0], 0, 1)); }
                  if (count($parts) > 1) { $ini .= strtoupper(substr($parts[count($parts)-1], 0, 1)); }
                  echo htmlspecialchars($ini);
                  ?>
                </div>
                <div>
                  <div class="driver-name"><?= htmlspecialchars($c['full_name'] ?? ''); ?></div>
                  <div class="driver-id">CND-<?= (int)($c['private_conductor_id'] ?? 0); ?></div>
                </div>
              </div>
            </td>

            <td><?= htmlspecialchars($c['phone'] ?? ''); ?></td>

            <td>
              <?php
                $status = (string)($c['status'] ?? 'Active');
                $map    = ['Active'=>'status-active','Suspended'=>'status-inactive'];
                $cls    = $map[$status] ?? 'status-active';
                $toggleTitle = (strcasecmp($status, 'Active') === 0) ? 'Suspend' : 'Activate';
              ?>
              <span class="status-badge <?= $cls; ?> js-status-badge">
                <?= htmlspecialchars($status); ?>
              </span>
            </td>

            <td>
              <div class="action-buttons">
                <a href="#"
                   class="icon-btn js-toggle-conductor-status" title="<?= htmlspecialchars($toggleTitle); ?>"
                   data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M1 9s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="9" cy="9" r="2" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-edit js-edit-conductor" title="Edit"
                   data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-delete js-del" title="Delete"
                   data-conductor-id="<?= (int)($c['private_conductor_id'] ?? 0); ?>">
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
          <td colspan="4" style="text-align:center;padding:40px;color:#6B7280;">
            No conductors found.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal (handled by external app.js) -->
<div id="driverModal" class="driver-modal" hidden>
  <div class="driver-modal__backdrop"></div>
  <div class="driver-modal__panel card">
    <header class="page-header" style="margin-bottom:16px;">
      <div>
        <h2 class="page-title" id="driverModalTitle">Add New Driver</h2>
        <p class="page-subtitle">Enter details below</p>
      </div>
    </header>

    <!-- app.js reads this and includes private_operator_id in POST -->
    <form id="driverForm" action="#" method="post" data-operator-id="<?= (int)($opId ?? 0) ?>">
      <input type="hidden" id="f_id" name="">

      <div class="filter-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" id="f_name" class="search-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" id="f_phone" class="search-input">
        </div>
      </div>

      <div class="filter-grid">
        <div class="form-group">
          <label class="form-label">License Number *</label>
          <input type="text" name="license_no" id="f_license_no" class="search-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="f_status" class="form-select">
            <option>Active</option>
            <option>Suspended</option>
          </select>
        </div>
      </div>

      <div class="filter-actions" style="margin-top: 20px;">
        <a href="#" id="btnCancelModal" class="advanced-filter-btn">Cancel</a>
        <button type="submit" class="export-btn" id="btnSubmitModal">Add Driver</button>
      </div>
    </form>
  </div>
</div>

<style>
  .driver-modal[hidden]{display:none}
  .driver-modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center}
  .driver-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.4)}
  .driver-modal__panel{position:relative;width:min(920px,95vw);max-height:90vh;overflow:auto}
</style>
