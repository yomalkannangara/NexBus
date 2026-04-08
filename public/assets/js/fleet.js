// public/assets/js/fleet.js
(function () {
  const page = document.getElementById('fleetPage');
  if (!page) return;

  const endpoint = window.location.pathname;

  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const $ = (sel, root = document) => root.querySelector(sel);

  // ---------- Modal helpers (updated for new classes) ----------
  function openModal(el) {
    if (!el) return;
    el.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('modal-open');
  }

  function closeModal(el) {
    if (!el) return;
    el.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('modal-open');
  }

  function wireClose(modal) {
    $$('.fleet-modal-overlay,[data-close-modal]', modal).forEach(b => {
      b.addEventListener('click', (e) => {
        if (b.classList.contains('fleet-modal-overlay') || b.hasAttribute('data-close-modal')) {
          closeModal(modal);
        }
      });
    });
  }

  // ---------- Toast notification ----------
  function toast(msg, ok = true) {
    const t = document.createElement('div');
    t.className = 'fleet-toast ' + (ok ? 'fleet-toast-ok' : 'fleet-toast-error');
    t.textContent = msg;
    t.style.cssText = `
      position: fixed;
      bottom: 24px;
      right: 24px;
      padding: 12px 20px;
      border-radius: 8px;
      color: #fff;
      font-weight: 600;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 3000;
      animation: slideIn 0.3s ease;
    `;
    if (ok) {
      t.style.background = 'linear-gradient(180deg, #1f7a54, #1a6a47)';
    } else {
      t.style.background = 'linear-gradient(180deg, #c92c4b, #b82643)';
    }
    document.body.appendChild(t);
    setTimeout(() => {
      t.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => t.remove(), 300);
    }, 2500);
  }

  // ---------- POST helper ----------
  async function postAction(action, dataObj) {
    const body = new URLSearchParams({ action, ...(dataObj || {}) });
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body
    });
    let ok = res.ok;
    try {
      const j = await res.json();
      ok = ok && !!j.ok;
    } catch (_) { }
    return ok;
  }

  // ================= Create Bus =================
  const modalCreate = $('#modalCreateBus');
  if (modalCreate) wireClose(modalCreate);

  const btnAdd = $('#btnAddBus');
  if (btnAdd) btnAdd.addEventListener('click', () => openModal(modalCreate));

  const btnSaveCreate = $('#btnSaveCreate');
  if (btnSaveCreate) {
    btnSaveCreate.addEventListener('click', async () => {
      const reg_no = ($('#create_reg_no')?.value || '').trim();
      const chassis_no = ($('#create_chassis_no')?.value || '').trim();
      const capacity = ($('#create_capacity')?.value || '').trim();
      const status = ($('#create_status')?.value || 'Active').trim();

      if (!reg_no) {
        toast('Bus number is required', false);
        return;
      }

      if (!capacity || isNaN(capacity)) {
        toast('Please enter a valid capacity', false);
        return;
      }

      btnSaveCreate.disabled = true;
      btnSaveCreate.textContent = 'Adding...';

      const ok = await postAction('create_bus', { reg_no, chassis_no, capacity, status });

      btnSaveCreate.disabled = false;
      btnSaveCreate.textContent = 'Add Bus';

      if (ok) {
        toast('✓ Bus added successfully');
        setTimeout(() => location.reload(), 1200);
      } else {
        toast('Failed to create bus', false);
      }
    });
  }

  // ================= Edit Bus =================
  const modalEdit = $('#modalEditBus');
  if (modalEdit) wireClose(modalEdit);

  const editButtons = $$('.js-edit');
  editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      $('#edit_reg_no').value = btn.getAttribute('data-reg') || '';
      $('#edit_status').value = btn.getAttribute('data-status') || 'Active';
      $('#edit_capacity').value = btn.getAttribute('data-capacity') || '';
      $('#edit_chassis_no').value = btn.getAttribute('data-chassis') || '';
      openModal(modalEdit);
    });
  });

  const btnSaveEdit = $('#btnSaveEdit');
  if (btnSaveEdit) {
    btnSaveEdit.addEventListener('click', async () => {
      const reg_no = ($('#edit_reg_no')?.value || '').trim();
      const chassis_no = ($('#edit_chassis_no')?.value || '').trim();
      const capacity = ($('#edit_capacity')?.value || '').trim();
      const status = ($('#edit_status')?.value || 'Active').trim();

      if (!reg_no) {
        toast('Missing bus number', false);
        return;
      }

      if (!capacity || isNaN(capacity)) {
        toast('Please enter a valid capacity', false);
        return;
      }

      btnSaveEdit.disabled = true;
      btnSaveEdit.textContent = 'Updating...';

      const ok = await postAction('update_bus', { reg_no, chassis_no, capacity, status });

      btnSaveEdit.disabled = false;
      btnSaveEdit.textContent = 'Update Bus';

      if (ok) {
        toast('✓ Bus updated successfully');
        setTimeout(() => location.reload(), 1200);
      } else {
        toast('Failed to update bus', false);
      }
    });
  }

  // ================= Delete Bus =================
  const modalDelete = $('#modalDeleteBus');
  if (modalDelete) wireClose(modalDelete);

  let pendingDeleteReg = null;

  const deleteButtons = $$('.js-delete');
  deleteButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      pendingDeleteReg = btn.getAttribute('data-reg') || '';
      const delBusReg = $('#delBusReg');
      if (delBusReg) delBusReg.textContent = pendingDeleteReg;
      openModal(modalDelete);
    });
  });

  const btnConfirmDelete = $('#btnConfirmDelete');
  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', async () => {
      if (!pendingDeleteReg) {
        toast('No bus selected for deletion', false);
        return;
      }

      btnConfirmDelete.disabled = true;
      btnConfirmDelete.textContent = 'Deleting...';

      const ok = await postAction('delete_bus', { reg_no: pendingDeleteReg });

      btnConfirmDelete.disabled = false;
      btnConfirmDelete.textContent = 'Delete Bus';

      if (ok) {
        toast('✓ Bus deleted successfully');
        setTimeout(() => location.reload(), 1200);
      } else {
        toast('Failed to delete bus', false);
      }
    });
  }



  $('#btnConfirmDelete')?.addEventListener('click', async () => {
    if (!pendingDeleteReg) return;
    const ok = await postAction('delete_bus', { reg_no: pendingDeleteReg });
    if (ok) { toast('✓ Bus deleted successfully'); setTimeout(() => location.reload(), 1200); }
    else { toast('Failed to delete bus', false); }
  });

  // ESC closes any open modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') $$('.fleet-modal[aria-hidden="false"]').forEach(m => closeModal(m));
  });
})();
