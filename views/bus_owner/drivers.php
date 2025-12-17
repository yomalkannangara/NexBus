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
    <a href="#" id="btnAddDriverLocal" class="add-bus-btn" style="margin-right: 5px;">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      Add New Driver
    </a>
    <a href="#" id="btnAddConductorLocal" class="add-bus-btn">
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
                $cls    = $map[$status] ?? 'status-active';
                $toggleTitle = (strcasecmp($status, 'Active') === 0) ? 'Suspend' : 'Activate';
                $isSuspended = (strcasecmp($status, 'Suspended') === 0);
                $statusIcon = $isSuspended 
                  ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                  : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';
              ?>
              <span class="status-badge <?= $cls; ?> js-status-badge">
                <?= htmlspecialchars($status); ?>
              </span>
            </td>

            <td>
              <div class="action-buttons">
                <a href="#"
                   class="icon-btn js-toggle-driver-status-local" title="<?= htmlspecialchars($toggleTitle); ?>"
                   data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <?= $statusIcon; ?>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-edit js-edit-driver" title="Edit"
                   data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-delete js-del-local" title="Delete"
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
                $isSuspended = (strcasecmp($status, 'Suspended') === 0);
                $statusIcon = $isSuspended 
                  ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                  : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';
              ?>
              <span class="status-badge <?= $cls; ?> js-status-badge">
                <?= htmlspecialchars($status); ?>
              </span>
            </td>

            <td>
              <div class="action-buttons">
                <a href="#"
                   class="icon-btn js-toggle-conductor-status-local" title="<?= htmlspecialchars($toggleTitle); ?>"
                   data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <?= $statusIcon; ?>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-edit js-edit-conductor" title="Edit"
                   data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                    <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>

                <a href="#"
                   class="icon-btn icon-btn-delete js-del-local" title="Delete"
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

<!-- Add/Edit Modal (handled by inline JS now) -->
<style>
  .driver-modal[hidden]{display:none}
  .driver-modal{position:fixed;inset:0;z-index:999999;display:flex;align-items:center;justify-content:center}
  .driver-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.4)}
  .driver-modal__panel{position:relative;width:min(920px,95vw);max-height:90vh;overflow:auto}

  /* Toast Styles */
  .toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 1000000;
    display: flex;
    align-items: center;
    gap: 12px;
    transform: translateX(120%);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    min-width: 300px;
    border: 1px solid #E5E7EB;
  }
  .toast-notification.show { transform: translateX(0); }
  .toast-notification.success { border-left: 4px solid #10B981; }
  .toast-notification.error { border-left: 4px solid #EF4444; }
  .toast-message { font-weight: 500; font-size: 14px; color: #1F2937; }
</style>

<!-- Toast Element -->
<div id="toastNotification" class="toast-notification">
  <div class="toast-message"></div>
</div>

<div id="driverModalLocal" class="driver-modal" hidden>
  <div class="driver-modal__backdrop"></div>
  <div class="driver-modal__panel card">
    <header class="page-header" style="margin-bottom:16px;">
      <div>
        <h2 class="page-title" id="driverModalTitleLocal">Add New Driver</h2>
        <p class="page-subtitle">Enter details below</p>
      </div>
    </header>

    <!-- app.js reads this and includes private_operator_id in POST -->
    <form id="driverFormLocal" action="" method="post" data-operator-id="<?= (int)($opId ?? 0) ?>">
      <input type="hidden" name="action" id="f_action" value="create">
      <input type="hidden" id="f_id" name="private_driver_id"> <!-- Dynamic name attribute -->

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

<!-- Status Confirmation Modal -->
<div id="statusConfirmModal" class="modal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width: 400px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <span id="statusModalTitle">Change Status</span>
      </h3>
      <button type="button" class="modal__close" id="btnCloseStatus">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 10px;">
      <p style="color: #4B5563; font-size: 15px; margin: 0;" id="statusModalMsg">Are you sure?</p>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCancelStatus" style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
      <button type="button" class="btn-primary" id="btnConfirmStatus" style="background: #DC2626; border: none; color: white;">Confirm</button>
    </div>
  </div>
</div>

      <div class="filter-actions" style="margin-top: 20px;">
        <a href="#" id="btnCancelModalLocal" class="advanced-filter-btn">Cancel</a>
        <button type="submit" class="export-btn" id="btnSubmitModalLocal">Add Driver</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width: 400px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span id="deleteModalTitle">Delete Record</span>
      </h3>
      <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 10px;">
      <p style="color: #4B5563; font-size: 15px; margin: 0;" id="deleteModalMsg">Are you sure you want to delete this record? This action cannot be undone.</p>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCancelDelete" style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
      <button type="button" class="btn-primary" id="btnConfirmDelete" style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
    </div>
  </div>
</div>

<script>
// Driver/Conductor delete handler
(function() {
  let deleteType = null; // 'driver' or 'conductor'
  let deleteId = null;
  const deleteModal = document.getElementById('deleteConfirmModal');
  const btnConfirmDelete = document.getElementById('btnConfirmDelete');
  const btnCancelDelete = document.getElementById('btnCancelDelete');
  const btnCloseDelete = document.getElementById('btnCloseDelete');
  const modalTitle = document.getElementById('deleteModalTitle');
  const modalMsg = document.getElementById('deleteModalMsg');

  function closeDeleteModal() {
    deleteModal.setAttribute('hidden', '');
    deleteId = null;
    deleteType = null;
  }

  if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
  if (btnCloseDelete) btnCloseDelete.addEventListener('click', closeDeleteModal);
  
  const deleteBackdrop = deleteModal?.querySelector('.modal__backdrop');
  if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDeleteModal);

  if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDeleteModal);
 
  document.querySelectorAll('.js-del-local').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      
      const driverId = this.getAttribute('data-driver-id');
      const conductorId = this.getAttribute('data-conductor-id');
      
      if (driverId) {
        deleteType = 'driver';
        deleteId = driverId;
        modalTitle.textContent = 'Delete Driver';
        modalMsg.textContent = 'Are you sure you want to delete this driver? This action cannot be undone.';
      } else if (conductorId) {
        deleteType = 'conductor';
        deleteId = conductorId;
        modalTitle.textContent = 'Delete Conductor';
        modalMsg.textContent = 'Are you sure you want to delete this conductor? This action cannot be undone.';
      } else {
        return;
      }
      
      if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
      }
      deleteModal.removeAttribute('hidden');
    });
  });

  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', function() {
      if (!deleteId || !deleteType) return;

      const originalText = btnConfirmDelete.textContent;
      btnConfirmDelete.textContent = 'Deleting...';
      btnConfirmDelete.disabled = true;

      // Submit form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      
      if (deleteType === 'driver') {
        actionInput.value = 'delete';
        idInput.name = 'driver_id';
      } else {
        actionInput.value = 'delete_conductor';
        idInput.name = 'conductor_id';
      }
      idInput.value = deleteId;
      
      form.appendChild(actionInput);
      form.appendChild(idInput);
      document.body.appendChild(form);
      form.submit();
    });
  }
})();

// Status Toggle Handler
(function() {
  let targetData = null;
  let targetType = null; // 'driver' or 'conductor'
  let nextStatus = null;
  
  const modal = document.getElementById('statusConfirmModal');
  const btnConfirm = document.getElementById('btnConfirmStatus');
  const btnCancel = document.getElementById('btnCancelStatus');
  const btnClose = document.getElementById('btnCloseStatus');
  const modalTitle = document.getElementById('statusModalTitle');
  const modalMsg = document.getElementById('statusModalMsg');
  
  function closeModal() {
    modal.setAttribute('hidden', '');
    targetData = null;
    targetType = null;
    nextStatus = null;
    if (btnConfirm) {
      btnConfirm.textContent = 'Confirm';
      btnConfirm.disabled = false;
    }
  }

  if (btnCancel) btnCancel.addEventListener('click', closeModal);
  if (btnClose) btnClose.addEventListener('click', closeModal);
  const backdrop = modal?.querySelector('.modal__backdrop');
  if (backdrop) backdrop.addEventListener('click', closeModal);

  // Driver Status
  document.querySelectorAll('.js-toggle-driver-status-local').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      try {
        targetData = JSON.parse(this.dataset.driver || '{}');
        targetType = 'driver';
        const cur = (targetData.status || 'Active').toLowerCase();
        nextStatus = (cur === 'suspended') ? 'Active' : 'Suspended';
        
        modalTitle.textContent = (nextStatus === 'Suspended') ? 'Suspend Driver' : 'Activate Driver';
        modalMsg.textContent = (nextStatus === 'Suspended') 
          ? `Are you sure you want to suspend ${targetData.full_name}? They will not be able to operate.` 
          : `Are you sure you want to activate ${targetData.full_name}?`;
        
        // Move to body and show
        if (modal.parentElement !== document.body) {
          document.body.appendChild(modal);
        }
        modal.removeAttribute('hidden');
      } catch (err) { console.error(err); }
    });
  });

  // Conductor Status
  document.querySelectorAll('.js-toggle-conductor-status-local').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      try {
        targetData = JSON.parse(this.dataset.conductor || '{}');
        targetType = 'conductor';
        const cur = (targetData.status || 'Active').toLowerCase();
        nextStatus = (cur === 'suspended') ? 'Active' : 'Suspended';
        
        modalTitle.textContent = (nextStatus === 'Suspended') ? 'Suspend Conductor' : 'Activate Conductor';
        modalMsg.textContent = (nextStatus === 'Suspended') 
          ? `Are you sure you want to suspend ${targetData.full_name}? They will not be able to operate.` 
          : `Are you sure you want to activate ${targetData.full_name}?`;
          
        // Move to body and show
        if (modal.parentElement !== document.body) {
          document.body.appendChild(modal);
        }
        modal.removeAttribute('hidden');
      } catch (err) { console.error(err); }
    });
  });

  // Confirm Action
  if (btnConfirm) {
    btnConfirm.addEventListener('click', function() {
      if (!targetData || !targetType || !nextStatus) return;
      
      btnConfirm.textContent = 'Saving...';
      btnConfirm.disabled = true;

      const f = document.createElement('form');
      f.method = 'POST';
      f.action = '';
      
      const add = (n, v) => { 
        const i = document.createElement('input'); 
        i.type = 'hidden'; i.name = n; i.value = (v == null ? '' : String(v)); 
        f.appendChild(i); 
      };

      if (targetType === 'driver') {
        add('action', 'update');
        add('private_driver_id', targetData.private_driver_id || targetData.id || '');
        add('full_name', targetData.full_name || '');
        add('license_no', targetData.license_no || '');
        add('phone', targetData.phone || '');
        add('status', nextStatus);
      } else {
        add('action', 'update_conductor');
        add('private_conductor_id', targetData.private_conductor_id || targetData.id || '');
        add('full_name', targetData.full_name || '');
        add('phone', targetData.phone || '');
        add('status', nextStatus);
      }
      
      document.body.appendChild(f);
      f.submit();
    });
  }
})();
</script>

<script>
// Add/Edit Driver & Conductor handler
(function() {
  const modal = document.getElementById('driverModalLocal');
  const form = document.getElementById('driverFormLocal');
  const btnAddDriver = document.getElementById('btnAddDriverLocal');
  const btnAddConductor = document.getElementById('btnAddConductorLocal');
  const btnCancel = document.getElementById('btnCancelModalLocal');
  const modalTitle = document.getElementById('driverModalTitleLocal');
  const btnSubmit = document.getElementById('btnSubmitModalLocal');
  
  // Form fields
  const actionInput = document.getElementById('f_action');
  const idInput = document.getElementById('f_id');
  const nameInput = document.getElementById('f_name');
  const phoneInput = document.getElementById('f_phone');
  const licenseInput = document.getElementById('f_license_no');
  const licenseGroup = licenseInput.closest('.form-group');
  const statusInput = document.getElementById('f_status');

  function openModal() {
    console.log('Opening modal...');
    // Move to body to prevent z-index/stacking issues
    if (modal && modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
    modal.removeAttribute('hidden');
  }

  function closeModal() {
    modal.setAttribute('hidden', '');
    form.reset();
  }

  if (btnCancel) btnCancel.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
  
  const backdrop = modal?.querySelector('.driver-modal__backdrop');
  if (backdrop) backdrop.addEventListener('click', closeModal);

  // Add Driver
  if (btnAddDriver) {
    console.log('Add Driver button found');
    btnAddDriver.addEventListener('click', (e) => {
      console.log('Add Driver clicked');
      e.preventDefault();
      form.reset();
      modalTitle.textContent = 'Add New Driver';
      btnSubmit.textContent = 'Add Driver';
      
      actionInput.value = 'create';
      idInput.name = 'private_driver_id';
      idInput.value = '';
      
      // Show License
      licenseGroup.style.display = 'flex';
      licenseInput.required = true;
      licenseInput.disabled = false;
      
      openModal();
    });
  } else {
    console.error('Add Driver button NOT found');
  }

  // Add Conductor
  if (btnAddConductor) {
    console.log('Add Conductor button found');
    btnAddConductor.addEventListener('click', (e) => {
      console.log('Add Conductor clicked');
      e.preventDefault();
      form.reset();
      modalTitle.textContent = 'Add New Conductor';
      btnSubmit.textContent = 'Add Conductor';
      
      actionInput.value = 'create_conductor';
      idInput.name = 'private_conductor_id';
      idInput.value = '';
      
      // Hide License
      licenseGroup.style.display = 'none';
      licenseInput.required = false;
      licenseInput.disabled = true;
      
      openModal();
    });
  } else {
    console.error('Add Conductor button NOT found');
  }

  // Edit logic
  document.querySelectorAll('.js-edit-driver').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const data = JSON.parse(this.dataset.driver || '{}');
      
      modalTitle.textContent = 'Edit Driver';
      btnSubmit.textContent = 'Update Driver';
      actionInput.value = 'update';
      idInput.name = 'private_driver_id';
      idInput.value = data.private_driver_id || data.id || '';
      
      nameInput.value = data.full_name || '';
      phoneInput.value = data.phone || '';
      licenseInput.value = data.license_no || '';
      statusInput.value = data.status || 'Active';
      
      // Show License
      licenseGroup.style.display = 'flex';
      licenseInput.required = true;
      licenseInput.disabled = false;
      
      openModal();
    });
  });

  document.querySelectorAll('.js-edit-conductor').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const data = JSON.parse(this.dataset.conductor || '{}');
      
      modalTitle.textContent = 'Edit Conductor';
      btnSubmit.textContent = 'Update Conductor';
      actionInput.value = 'update_conductor';
      idInput.name = 'private_conductor_id';
      idInput.value = data.private_conductor_id || data.id || '';
      
      nameInput.value = data.full_name || '';
      phoneInput.value = data.phone || '';
      statusInput.value = data.status || 'Active';
      
      // Hide License
      licenseGroup.style.display = 'none';
      licenseInput.required = false;
      licenseInput.disabled = true;
      
      openModal();
    });
  });

})();

// Toast Notification Logic
function showToast(message, type = 'success') {
  const toast = document.getElementById('toastNotification');
  if (!toast) return;
  
  const msgEl = toast.querySelector('.toast-message');
  msgEl.textContent = message;
  
  toast.className = 'toast-notification ' + type;
  
  // Move to body to ensure visibility
  if (toast.parentElement !== document.body) {
    document.body.appendChild(toast);
  }
  
  // Trigger reflow
  void toast.offsetWidth;
  
  toast.classList.add('show');
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, 4000);
}

// Check for messages from URL
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const msg = urlParams.get('msg');
  
  if (msg) {
    if (msg === 'created') showToast('Record added successfully', 'success');
    else if (msg === 'updated') showToast('Record updated successfully', 'success');
    else if (msg === 'deleted') showToast('Record deleted successfully', 'success');
    else if (msg === 'conductor_created') showToast('Conductor added successfully', 'success');
    else if (msg === 'conductor_updated') showToast('Conductor updated successfully', 'success');
    else if (msg === 'conductor_deleted') showToast('Conductor deleted successfully', 'success');
    else if (msg === 'error') showToast('Operation failed. A record with this specific info (e.g. License) already exists.', 'error');
    
    // Clean URL
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
  }
});
</script>
