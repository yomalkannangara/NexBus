<?php
// Content-only Fleet view (structure only)
// Expects: $buses (array), BASE_URL defined by layout.
?>

<header class="page-header">
  <div>
    <h2 class="page-title">Fleet Management</h2>
    <p class="page-subtitle">Manage and monitor your bus fleet</p>
  </div>
  <a href="#" id="btnAddBus" class="add-bus-btn">
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
                  <a href="#" class="icon-btn icon-btn-edit js-edit-bus" title="Edit" 
                     data-bus='<?= htmlspecialchars(json_encode($b, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                  <a href="#" class="icon-btn icon-btn-delete js-del-bus" title="Delete" data-bus-reg="<?= htmlspecialchars($b['bus_number'] ?? ''); ?>">
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

<!-- Add/Edit Bus Modal -->
<div id="busModal" class="bus-modal" hidden>
  <div class="bus-modal__backdrop"></div>
  <div class="bus-modal__panel card">
    <header class="page-header" style="margin-bottom:16px;">
      <div>
        <h2 class="page-title" id="busModalTitle">Add New Bus</h2>
        <p class="page-subtitle">Enter bus details below</p>
      </div>
    </header>

    <form id="busForm" action="#" method="post">
      <input type="hidden" id="bus_id" name="bus_id">
      <input type="hidden" name="action" id="bus_action" value="create">

      <div class="filter-grid">
        <div class="form-group">
          <label class="form-label">Registration Number *</label>
          <input type="text" name="reg_no" id="bus_reg_no" class="search-input" placeholder="e.g., WP ABC-1234" required>
        </div>
        <div class="form-group">
          <label class="form-label">Chassis Number *</label>
          <input type="text" name="chassis_no" id="bus_chassis_no" class="search-input" placeholder="e.g., CHASSIS123456" required>
        </div>
      </div>

      <div class="filter-grid">
        <div class="form-group">
          <label class="form-label">Capacity (Seats) *</label>
          <input type="number" name="capacity" id="bus_capacity" class="search-input" placeholder="e.g., 50" min="1" max="200" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="bus_status" class="search-input">
            <option value="Active">Active</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Out of Service">Out of Service</option>
          </select>
        </div>
      </div>

      <div class="filter-actions" style="margin-top: 20px;">
        <a href="#" id="btnCancelBusModal" class="advanced-filter-btn">Cancel</a>
        <button type="submit" class="export-btn" id="btnSubmitBusModal">Add Bus</button>
      </div>
    </form>
  </div>
</div>

<style>
  .bus-modal[hidden]{display:none}
  .bus-modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center}
  .bus-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.4)}
  .bus-modal__panel{position:relative;width:min(920px,95vw);max-height:90vh;overflow:auto}
</style>

<script>
(function() {
  const modal = document.getElementById('busModal');
  const form = document.getElementById('busForm');
  const btnAdd = document.getElementById('btnAddBus');
  const btnCancel = document.getElementById('btnCancelBusModal');
  const btnSubmit = document.getElementById('btnSubmitBusModal');
  const modalTitle = document.getElementById('busModalTitle');
  const actionInput = document.getElementById('bus_action');

  // Open modal for adding new bus
  if (btnAdd) {
    btnAdd.addEventListener('click', function(e) {
      e.preventDefault();
      form.reset();
      document.getElementById('bus_id').value = '';
      document.getElementById('bus_reg_no').disabled = false; // Enable for new bus
      actionInput.value = 'create';
      modalTitle.textContent = 'Add New Bus';
      btnSubmit.textContent = 'Add Bus';
      modal.removeAttribute('hidden');
    });
  }

  // Open modal for editing existing bus
  const editBtns = document.querySelectorAll('.js-edit-bus');
  editBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      
      let busData = {};
      try {
        busData = JSON.parse(this.getAttribute('data-bus') || '{}');
      } catch(err) {
        console.error('Failed to parse bus data:', err);
        return;
      }

      // Fill form with existing data
      document.getElementById('bus_reg_no').value = busData.bus_number || '';
      document.getElementById('bus_chassis_no').value = busData.chassis_no || '';
      document.getElementById('bus_capacity').value = busData.capacity || '';
      document.getElementById('bus_status').value = busData.status || 'Active';
      document.getElementById('bus_reg_no').disabled = true; // Disable reg_no for edit
      
      actionInput.value = 'update';
      modalTitle.textContent = 'Edit Bus';
      btnSubmit.textContent = 'Update Bus';
      modal.removeAttribute('hidden');
    });
  });

  // Close modal
  if (btnCancel) {
    btnCancel.addEventListener('click', function(e) {
      e.preventDefault();
      modal.setAttribute('hidden', '');
    });
  }

  // Close on backdrop click
  const backdrop = modal.querySelector('.bus-modal__backdrop');
  if (backdrop) {
    backdrop.addEventListener('click', function() {
      modal.setAttribute('hidden', '');
    });
  }

  // Handle form submission
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(form);
      const baseUrl = '<?= BASE_URL; ?>' || '';
      
      fetch(baseUrl + '/B/fleet', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (response.ok) {
          window.location.href = baseUrl + '/B/fleet?msg=saved';
        } else {
          alert('Error saving bus. Please try again.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error saving bus. Please try again.');
      });
    });
  }
})();
</script>

<script>
// Bus delete handler
(function() {
  const deleteBtns = document.querySelectorAll('.js-del-bus');
  
  deleteBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      
      const busReg = this.getAttribute('data-bus-reg');
      
      // Use the same modern confirmation modal
      if (typeof showConfirmModal === 'function') {
        // Modal is defined in bus_owner.js, but we can create our own version here
        createConfirmModal('Are you sure you want to delete this bus?', () => {
          // Create and submit form
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = ('<?= BASE_URL; ?>' || '') + '/B/fleet';
          
          const actionInput = document.createElement('input');
          actionInput.type = 'hidden';
          actionInput.name = 'action';
          actionInput.value = 'delete';
          
          const regInput = document.createElement('input');
          regInput.type = 'hidden';
          regInput.name = 'reg_no';
          regInput.value = busReg;
          
          form.appendChild(actionInput);
          form.appendChild(regInput);
          document.body.appendChild(form);
          form.submit();
        });
      }
    });
  });
  
  function createConfirmModal(message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'confirm-modal';
    modal.innerHTML = `
      <div class="confirm-modal__backdrop"></div>
      <div class="confirm-modal__panel">
        <div class="confirm-modal__icon">
          <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
            <circle cx="24" cy="24" r="22" stroke="#DC2626" stroke-width="2"/>
            <path d="M24 16v12M24 32h.01" stroke="#DC2626" stroke-width="3" stroke-linecap="round"/>
          </svg>
        </div>
        <h3 class="confirm-modal__title">Confirm Delete</h3>
        <div class="confirm-modal__actions">
          <button class="confirm-modal__btn confirm-modal__btn--cancel">Cancel</button>
          <button class="confirm-modal__btn confirm-modal__btn--confirm">Delete</button>
        </div>
      </div>
    `;

    if (!document.getElementById('confirm-modal-styles')) {
      const style = document.createElement('style');
      style.id = 'confirm-modal-styles';
      style.textContent = `
        .confirm-modal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;animation:fadeIn .15s ease-out}
        .confirm-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px)}
        .confirm-modal__panel{position:relative;background:#fff;border-radius:16px;padding:40px 32px 32px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:slideUp .2s cubic-bezier(.2,.8,.2,1)}
        .confirm-modal__icon{display:flex;justify-content:center;margin-bottom:24px}
        .confirm-modal__title{font-size:22px;font-weight:700;color:#111827;text-align:center;margin:0 0 32px}
        .confirm-modal__actions{display:flex;gap:12px;justify-content:center}
        .confirm-modal__btn{padding:11px 32px;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;border:none}
        .confirm-modal__btn--cancel{background:#F3F4F6;color:#4B5563}
        .confirm-modal__btn--cancel:hover{background:#E5E7EB;transform:translateY(-1px)}
        .confirm-modal__btn--confirm{background:#DC2626;color:#fff}
        .confirm-modal__btn--confirm:hover{background:#B91C1C;transform:translateY(-1px);box-shadow:0 4px 12px rgba(220,38,38,.4)}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes slideUp{from{opacity:0;transform:translateY(20px)scale(.95)}to{opacity:1;transform:translateY(0)scale(1)}}
      `;
      document.head.appendChild(style);
    }

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    const btnCancel = modal.querySelector('.confirm-modal__btn--cancel');
    const btnConfirm = modal.querySelector('.confirm-modal__btn--confirm');
    const backdrop = modal.querySelector('.confirm-modal__backdrop');

    const closeModal = () => {
      modal.style.animation = 'fadeOut .15s ease-in forwards';
      setTimeout(() => {
        if (document.body.contains(modal)) {
          document.body.removeChild(modal);
        }
        document.body.style.overflow = '';
      }, 150);
    };

    btnCancel.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    btnConfirm.addEventListener('click', () => {
      closeModal();
      if (onConfirm) onConfirm();
    });

    const handleEsc = (e) => {
      if (e.key === 'Escape') {
        closeModal();
        document.removeEventListener('keydown', handleEsc);
      }
    };
    document.addEventListener('keydown', handleEsc);
  }
})();
</script>

