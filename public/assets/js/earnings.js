/* NeXBus — Earnings page JS (modal + create/update/delete + export) */
(function () {
  'use strict';

  // tiny helpers
  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));
  const onReady = (fn) => (document.readyState !== 'loading') ? fn() : document.addEventListener('DOMContentLoaded', fn);

  onReady(function () {
    const page = document.getElementById('earningsPage');
    if (!page) return; // not on this page

    const endpoint = page.getAttribute('data-endpoint') || '/B/earnings';

    // elements
    const modal      = document.getElementById('earningModal');
    const form       = document.getElementById('earningForm');
    const titleEl    = document.getElementById('earningModalTitle');
    const btnAdd     = document.getElementById('btnAddEarning');
    const btnClose   = document.getElementById('btnCloseEarning');
    const btnCancel  = document.getElementById('btnCancelEarning');

    // fields
    const fId     = document.getElementById('f_e_id');
    const fDate   = document.getElementById('f_e_date');
    const fBus    = document.getElementById('f_e_bus');     // may be <select> or <input>
    const fAmount = document.getElementById('f_e_amount');
    const fSource = document.getElementById('f_e_source');

    // open/close modal
    function openModal(){ if (!modal) return; modal.hidden = false; document.body.style.overflow = 'hidden'; }
    function closeModal(){ if (!modal) return; modal.hidden = true;  document.body.style.overflow = ''; }

    // mode handlers
    function setCreate() {
      if (titleEl) titleEl.textContent = 'Add Income';
      if (fId)     fId.value = '';
      if (fDate)   fDate.value = '';
      if (fBus) {
        if (fBus.tagName === 'SELECT') fBus.selectedIndex = 0; else fBus.value = '';
      }
      if (fAmount) fAmount.value = '';
      if (fSource) fSource.value = '';
    }

    function setEdit(d) {
      if (titleEl) titleEl.textContent = 'Edit Income';
      if (fId)     fId.value = d.id || '';
      if (fDate)   fDate.value = (d.date || '').slice(0, 10);
      if (fBus) {
        const v = d.bus_reg_no || '';
        if (fBus.tagName === 'SELECT') fBus.value = v; else fBus.value = v;
      }
      if (fAmount) fAmount.value = (d.amount != null ? d.amount : '');
      if (fSource) fSource.value = d.source || '';
    }

    // wire buttons
    btnAdd   && btnAdd.addEventListener('click', () => { setCreate(); openModal(); });
    btnClose && btnClose.addEventListener('click', closeModal);
    btnCancel&& btnCancel.addEventListener('click', closeModal);
    modal    && modal.querySelector('.modal__backdrop')?.addEventListener('click', closeModal);
    window.addEventListener('keydown', e => { if (e.key === 'Escape' && modal && !modal.hidden) closeModal(); });

    // edit buttons
    $$('.js-earning-edit').forEach(btn => {
      btn.addEventListener('click', () => {
        try {
          const data = JSON.parse(btn.getAttribute('data-earning') || '{}');
          setEdit(data);
          openModal();
        } catch (e) {
          console.error('[earnings] bad data-earning JSON', e);
        }
      });
    });

    // delete buttons
    $$('.js-earning-del').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-earning-id');
        if (!id) return;
        if (!confirm('Delete this record?')) return;

        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('earning_id', id);

        fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return true; })
          .then(() => { window.location.reload(); })
          .catch(err => {
            console.warn('[earnings] AJAX delete failed; fallback submit', err);
            // fallback full submit
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = endpoint;
            const mk = (n, v) => { const i = document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; f.appendChild(i); };
            mk('action','delete'); mk('earning_id', id);
            document.body.appendChild(f); f.submit();
          });
      });
    });

    // submit (create/update)
    form && form.addEventListener('submit', function (e) {
      e.preventDefault();

      // simple required validation
      if (!fDate?.value || !fBus?.value || !fAmount?.value) {
        alert('Please fill Date, Bus Reg. No and Amount.');
        return;
      }

      const fd = new FormData(form);
      const isUpdate = !!(fId && String(fId.value || '').trim() !== '');
      fd.append('action', isUpdate ? 'update' : 'create');
      if (isUpdate) fd.append('earning_id', fId.value);

      fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return true; })
        .then(() => { window.location.reload(); })
        .catch(err => {
          console.warn('[earnings] AJAX save failed; fallback submit', err);
          // graceful fallback
          form.action = endpoint;
          form.method = 'POST';
          form.submit();
        });
    });

    // export button (uses data-export-href from the button)
    const exportBtn = $('.js-export', page);
    if (exportBtn) {
      exportBtn.addEventListener('click', () => {
        const href = exportBtn.getAttribute('data-export-href') || '/earnings/export';
        window.location.href = href;
      });
    }
  });
})();
