<?php
// Earnings view — no inline JS, modal-based Add/Edit
// Expects: $earnings (array). Optional: $buses (owner's buses for dropdown).
// Uses BASE_URL and posts to data-endpoint (default shown below).
?>
<section id="earningsPage"
         data-endpoint="<?= BASE_URL; ?>/earnings">

  <header class="page-header">
    <div>
      <h2 class="page-title">Earnings & Expenses</h2>
      <p class="page-subtitle">Track and manage bus route revenue and income reports.</p>
    </div>
    <div class="header-actions">
      <button type="button"
              class="export-report-btn-alt js-export"
              data-export-href="<?= BASE_URL; ?>/earnings/export">
        Export Report
      </button>
      <button type="button" id="btnAddEarning" class="add-income-btn">
        Add Income Record
      </button>
    </div>
  </header>

  <div class="card">
    <h3 class="card-title">Revenue Tracking</h3>

    <div class="table-container">
      <table class="data-table earnings-table" id="earnings-table">
        <thead>
          <tr>
            <th style="width:150px;">Date</th>
            <th>Route & Destination</th>
            <th>Bus Reg. No</th>
            <th>Total Revenue</th>
            <th>Source</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($earnings)): ?>
            <?php foreach ($earnings as $e): ?>
              <?php
                // Normalize keys expected by JS (id, date, bus_reg_no, amount, source)
                $row = [
                  'id'          => (int)($e['id'] ?? $e['earning_id'] ?? 0),
                  'date'        => $e['date'] ?? '',
                  'bus_reg_no'  => $e['bus_reg_no'] ?? $e['bus_id'] ?? '',
                  'amount'      => (float)($e['amount'] ?? $e['total_revenue'] ?? 0),
                  'source'      => $e['source'] ?? $e['notes'] ?? '',
                  // The next two are just for display; not posted to table
                  'route'       => $e['route'] ?? '',
                  'route_number'=> $e['route_number'] ?? '',
                ];
                $dataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
                <td>
                  <div class="date-cell">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <rect x="2" y="3" width="12" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/>
                      <path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <?= htmlspecialchars($row['date']) ?>
                  </div>
                </td>

                <td>
                  <div class="route-cell">
                    <span class="badge badge-yellow"><?= htmlspecialchars($row['route_number']) ?></span>
                    <span><?= htmlspecialchars($row['route']) ?></span>
                  </div>
                </td>

                <td><strong><?= htmlspecialchars($row['bus_reg_no']) ?></strong></td>

                <td>
                  <strong class="revenue-amount">LKR <?= number_format($row['amount']); ?></strong>
                </td>

                <td><?= htmlspecialchars($row['source']) ?></td>

                <td>
                  <div class="action-buttons">
                    <button type="button"
                            class="icon-btn icon-btn-edit js-earning-edit"
                            title="Edit"
                            data-earning="<?= $dataJson ?>">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                        <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <button type="button"
                            class="icon-btn icon-btn-delete js-earning-del"
                            title="Delete"
                            data-earning-id="<?= (int)$row['id'] ?>">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                        <path d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:#6B7280;">
                No earnings records found. Click “Add Income Record” to add your first entry.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Earnings Modal (hidden by default) -->
  <div id="earningModal" class="modal" hidden>
    <div class="modal__backdrop" data-close="1"></div>
    <div class="modal__dialog">
      <div class="modal__header">
        <h3 id="earningModalTitle">Add Income</h3>
        <button type="button" class="modal__close" id="btnCloseEarning" aria-label="Close">×</button>
      </div>

      <form id="earningForm" class="modal__form" autocomplete="off">
        <input type="hidden" id="f_e_id" name="earning_id" value="">

        <div class="form-grid">
          <div class="form-field">
            <label for="f_e_date">Date <span class="req">*</span></label>
            <input type="date" id="f_e_date" name="date" required>
          </div>

            <div class="form-field">
            <label for="f_e_bus">Bus Reg. No <span class="req">*</span></label>
            <select id="f_e_bus" name="bus_reg_no" required>
                <option value="">-- Select Bus --</option>
                <?php if (!empty($buses) && is_array($buses)): ?>
                <?php foreach ($buses as $b): ?>
                    <?php $reg = is_array($b) ? ($b['reg_no'] ?? '') : (string)$b; ?>
                    <?php if ($reg !== ''): ?>
                    <option value="<?= htmlspecialchars($reg) ?>"><?= htmlspecialchars($reg) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php else: ?>
                <option value="" disabled>(No buses found for your account)</option>
                <?php endif; ?>
            </select>
            </div>


          <div class="form-field">
            <label for="f_e_amount">Amount (LKR) <span class="req">*</span></label>
            <input type="number" id="f_e_amount" name="amount" step="0.01" min="0" required>
          </div>

          <div class="form-field">
            <label for="f_e_source">Source / Note</label>
            <input type="text" id="f_e_source" name="source" maxlength="120" placeholder="Ticket sales, charter, etc.">
          </div>
        </div>

        <div class="modal__footer">
          <button type="button" class="btn-secondary" id="btnCancelEarning">Cancel</button>
          <button type="submit" class="btn-primary" id="btnSubmitEarning">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modern Toast Notification -->
  <div id="toastNotification" class="toast-notification">
    <div class="toast-icon"></div>
    <div class="toast-message"></div>
    <button class="toast-close">&times;</button>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div id="deleteConfirmModal" class="modal" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" style="max-width: 400px; padding: 0;">
      <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
        <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
          <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Delete Record
        </h3>
        <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
      </div>
      <div class="modal__form" style="padding-top: 10px;">
        <p style="color: #4B5563; font-size: 15px; margin: 0;">Are you sure you want to delete this earning record? This action cannot be undone.</p>
      </div>
      <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
        <button type="button" class="btn-secondary" id="btnCancelDelete" style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
        <button type="button" class="btn-primary" id="btnConfirmDelete" style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
      </div>
    </div>
  </div>
</section>

<style>
/* Modern Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  min-width: 300px;
  max-width: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  padding: 16px 20px;
  display: none;
  align-items: center;
  gap: 12px;
  z-index: 999999;
  animation: slideInRight 0.3s ease-out;
  border-left: 4px solid #10B981;
}

.toast-notification.success {
  border-left-color: #10B981;
}

.toast-notification.error {
  border-left-color: #EF4444;
}

.toast-notification.show {
  display: flex;
}

.toast-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-notification.success .toast-icon {
  background: #10B981;
}

.toast-notification.error .toast-icon {
  background: #EF4444;
}

.toast-notification.success .toast-icon::before {
  content: '✓';
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.toast-notification.error .toast-icon::before {
  content: '✕';
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.toast-message {
  flex: 1;
  color: #1F2937;
  font-size: 14px;
  line-height: 1.5;
}

.toast-close {
  background: none;
  border: none;
  color: #9CA3AF;
  font-size: 24px;
  line-height: 1;
  padding: 0;
  width: 24px;
  height: 24px;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.2s;
}

.toast-close:hover {
  color: #4B5563;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOutRight {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}
</style>

<script>
// Earnings Page JavaScript - Modal and CRUD Operations
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('earningModal');
  const form = document.getElementById('earningForm');
  const btnAdd = document.getElementById('btnAddEarning');
  const btnClose = document.getElementById('btnCloseEarning');
  const btnCancel = document.getElementById('btnCancelEarning');
  const modalTitle = document.getElementById('earningModalTitle');
  const endpoint = document.getElementById('earningsPage')?.dataset?.endpoint || '<?= BASE_URL; ?>/earnings';
  console.log('Earnings endpoint:', endpoint);

  // Modern Toast Notification Function
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toastNotification');
    
    // Move to body if not already there to ensure z-index works properly
    if (toast && toast.parentElement !== document.body) {
      document.body.appendChild(toast);
    }
    
    const messageEl = toast.querySelector('.toast-message');
    
    messageEl.textContent = message;
    toast.className = 'toast-notification show ' + type;
    
    // Auto-hide after 4 seconds
    setTimeout(() => {
      toast.style.animation = 'slideOutRight 0.3s ease-out';
      setTimeout(() => {
        toast.classList.remove('show');
        toast.style.animation = '';
      }, 300);
    }, 4000);
  }

  // Close toast on button click
  document.querySelector('.toast-close')?.addEventListener('click', function() {
    const toast = document.getElementById('toastNotification');
    toast.style.animation = 'slideOutRight 0.3s ease-out';
    setTimeout(() => {
      toast.classList.remove('show');
      toast.style.animation = '';
    }, 300);
  });

  // Open modal for adding new earning
  if (btnAdd) {
    btnAdd.addEventListener('click', function() {
      modalTitle.textContent = 'Add Income Record';
      form.reset();
      document.getElementById('f_e_id').value = '';
      modal.removeAttribute('hidden');
    });
  }

  // Close modal
  function closeModal() {
    modal.setAttribute('hidden', '');
    form.reset();
  }

  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  // Close on backdrop click
  const backdrop = modal?.querySelector('.modal__backdrop');
  if (backdrop) {
    backdrop.addEventListener('click', closeModal);
  }

  // Handle edit button clicks
  document.querySelectorAll('.js-earning-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      const data = JSON.parse(this.dataset.earning || '{}');
      
      document.getElementById('f_e_id').value = data.id || '';
      document.getElementById('f_e_date').value = data.date || '';
      document.getElementById('f_e_bus').value = data.bus_reg_no || '';
      document.getElementById('f_e_amount').value = data.amount || '';
      document.getElementById('f_e_source').value = data.source || '';
      
      modalTitle.textContent = 'Edit Income Record';
      modal.removeAttribute('hidden');
    });
  });

  // Handle delete button clicks - Custom Modal
  let deleteId = null;
  const deleteModal = document.getElementById('deleteConfirmModal');
  const btnConfirmDelete = document.getElementById('btnConfirmDelete');
  const btnCancelDelete = document.getElementById('btnCancelDelete');
  const btnCloseDelete = document.getElementById('btnCloseDelete');

  function closeDeleteModal() {
    deleteModal.setAttribute('hidden', '');
    deleteId = null;
  }

  if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
  if (btnCloseDelete) btnCloseDelete.addEventListener('click', closeDeleteModal);
  
  // Close delete modal on backdrop click
  const deleteBackdrop = deleteModal?.querySelector('.modal__backdrop');
  if (deleteBackdrop) {
    deleteBackdrop.addEventListener('click', closeDeleteModal);
  }

  document.querySelectorAll('.js-earning-del').forEach(btn => {
    btn.addEventListener('click', function() {
      deleteId = this.dataset.earningId;
      if (!deleteId) return;
      
      // Move to body to prevent z-index issues
      if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
      }
      
      deleteModal.removeAttribute('hidden');
    });
  });

  // Handle actual deletion
  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', function() {
      if (!deleteId) return;

      // Show loading state
      const originalText = btnConfirmDelete.textContent;
      btnConfirmDelete.textContent = 'Deleting...';
      btnConfirmDelete.disabled = true;

      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('earning_id', deleteId);
      
      fetch(endpoint, {
        method: 'POST',
        body: formData
      })
      .then(async response => {
        if (response.ok) {
          const result = await response.json();
          closeDeleteModal();
          showToast(result.message || 'Record deleted successfully!', 'success');
          setTimeout(() => window.location.reload(), 1500);
        } else {
          const error = await response.json();
          showToast(error.message || 'Error deleting record. Please try again.', 'error');
          // Reset button
          btnConfirmDelete.textContent = originalText;
          btnConfirmDelete.disabled = false;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        // Reset button
        btnConfirmDelete.textContent = originalText;
        btnConfirmDelete.disabled = false;
      });
    });
  }

  // Handle form submission
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const earningId = document.getElementById('f_e_id').value;
      
      // Set action based on whether we're creating or updating
      formData.append('action', earningId ? 'update' : 'create');
      
      fetch(endpoint, {
        method: 'POST',
        body: formData
      })
      .then(async response => {
        // Try to parse as JSON first
        const contentType = response.headers.get('content-type');
        let result;
        
        try {
          if (contentType && contentType.includes('application/json')) {
            result = await response.json();
          } else {
            // Not JSON, read as text for debugging
            const text = await response.text();
            console.error('Server returned non-JSON response:', text);
            result = { success: false, message: 'Server error. Please check the console for details.' };
          }
        } catch (parseError) {
          console.error('Failed to parse response:', parseError);
          result = { success: false, message: 'Invalid server response' };
        }
        
        if (response.ok && result.success !== false) {
          showToast(result.message || 'Record saved successfully!', 'success');
          setTimeout(() => window.location.reload(), 1500);
        } else {
          showToast(result.message || 'Error saving record. Please check the form and try again.', 'error');
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        showToast('Network error: ' + error.message, 'error');
      });
    });
  }

  // Handle export button
  const btnExport = document.querySelector('.js-export');
  if (btnExport) {
    btnExport.addEventListener('click', function() {
      const exportUrl = this.dataset.exportHref || '<?= BASE_URL; ?>/earnings/export';
      window.location.href = exportUrl + '?range=6m';
    });
  }
});
</script>
