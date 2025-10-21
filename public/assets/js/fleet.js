// public/assets/js/fleet.js
(function () {
  const page = document.getElementById('fleetPage');
  if (!page) return;

  // Post back to SAME route (no endpoint in HTML)
  const endpoint = window.location.pathname;

  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const $  = (sel, root = document) => root.querySelector(sel);

  // ---------- Modal helpers ----------
  function openModal(el){ if(!el) return; el.setAttribute('aria-hidden','false'); document.documentElement.classList.add('modal-open'); }
  function closeModal(el){ if(!el) return; el.setAttribute('aria-hidden','true'); document.documentElement.classList.remove('modal-open'); }
  function wireClose(modal){ $$('.modal__overlay,[data-close-modal]',modal).forEach(b=>b.addEventListener('click',()=>closeModal(modal))); }

  // ---------- Toast ----------
  function toast(msg, ok=true){
    const t = document.createElement('div');
    t.className = 'toast ' + (ok ? 'ok' : 'error');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 2200);
  }

  // ---------- POST helper ----------
  async function postAction(action, dataObj){
    const body = new URLSearchParams({ action, ...(dataObj||{}) });
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body
    });
    let ok = res.ok;
    try {
      const j = await res.json();
      ok = ok && !!j.ok;
    } catch(_) {}
    return ok;
  }

  // ================= Create =================
  const modalCreate = $('#modalCreateBus'); if (modalCreate) wireClose(modalCreate);
  const btnAdd = $('#btnAddBus');
  if (btnAdd) btnAdd.addEventListener('click', () => openModal(modalCreate));

  $('#btnSaveCreate')?.addEventListener('click', async () => {
    const reg_no     = ($('#create_reg_no')?.value || '').trim();
    const chassis_no = ($('#create_chassis_no')?.value || '').trim();
    const capacity   = ($('#create_capacity')?.value || '').trim();
    const status     = ($('#create_status')?.value || 'Active').trim();

    if (!reg_no) return toast('Bus number is required', false);

    const ok = await postAction('create_bus', { reg_no, chassis_no, capacity, status });
    if (ok) { toast('Bus created'); location.reload(); }
    else { toast('Failed to create bus', false); }
  });

  // ================= Edit =================
  const modalEdit = $('#modalEditBus'); if (modalEdit) wireClose(modalEdit);

  $$('.js-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      $('#edit_reg_no').value     = btn.getAttribute('data-reg') || '';
      $('#edit_status').value     = btn.getAttribute('data-status') || 'Active';
      $('#edit_capacity').value   = btn.getAttribute('data-capacity') || '';
      $('#edit_chassis_no').value = btn.getAttribute('data-chassis') || '';
      openModal(modalEdit);
    });
  });

  $('#btnSaveEdit')?.addEventListener('click', async () => {
    const reg_no     = ($('#edit_reg_no')?.value || '').trim();
    const chassis_no = ($('#edit_chassis_no')?.value || '').trim();
    const capacity   = ($('#edit_capacity')?.value || '').trim();
    const status     = ($('#edit_status')?.value || 'Active').trim();

    if (!reg_no) return toast('Missing bus number', false);

    const ok = await postAction('update_bus', { reg_no, chassis_no, capacity, status });
    if (ok) { toast('Bus updated'); location.reload(); }
    else { toast('Failed to update bus', false); }
  });

  // ================= Delete =================
  const modalDelete = $('#modalDeleteBus'); if (modalDelete) wireClose(modalDelete);

  let pendingDeleteReg = null;
  $$('.js-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      pendingDeleteReg = btn.getAttribute('data-reg') || '';
      $('#delBusReg').textContent = pendingDeleteReg;
      openModal(modalDelete);
    });
  });

  $('#btnConfirmDelete')?.addEventListener('click', async () => {
    if (!pendingDeleteReg) return;
    const ok = await postAction('delete_bus', { reg_no: pendingDeleteReg });
    if (ok) { toast('Bus deleted'); location.reload(); }
    else { toast('Failed to delete bus', false); }
  });

  // ESC closes any open modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') $$('.modal[aria-hidden="false"]').forEach(m => closeModal(m));
  });
})();
