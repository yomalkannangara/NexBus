/* NeXBus — combined app.js (hardened)
   Includes: dashboard, drivers, earnings, feedback, fleet, performance
   Guards every module; only runs where elements exist.
*/
(function () {
  'use strict';

  // ---------- tiny helpers ----------
  const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));
  const $ = (sel, root) => (root || document).querySelector(sel);
  const onReady = (fn) => (document.readyState !== 'loading') ? fn() : document.addEventListener('DOMContentLoaded', fn);
  const safe = (name, fn) => { try { fn(); } catch (e) { console.error(`[app.js] ${name} failed:`, e); } };

  // ---------- toast ----------
  function toast(msg, ok = true) {
    let el = document.getElementById('toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast';
      el.style.cssText = 'position:fixed;right:16px;bottom:16px;padding:10px 14px;border-radius:8px;background:#111827;color:#fff;display:none;z-index:9999';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.style.background = ok ? '#059669' : '#B91C1C';
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 2200);
  }

  // robust POST: same-origin cookies, follow redirects; fallback to normal submit
  async function postFormOrFallback(form) {
    const url = form.getAttribute('action');
    const body = new FormData(form);
    try {
      const res = await fetch(url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        redirect: 'follow',
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return true;
    } catch (err) {
      console.warn('[app.js] AJAX failed; falling back to full submit:', err);
      form.submit(); // degrade gracefully
      return false;
    }
  }

  // ---------- global: confirm deletes (modern modal) ----------
  function confirmDeletes() {
    $$('.js-del').forEach(a => {
      if (a.hasAttribute('data-driver-id') || a.hasAttribute('data-conductor-id')) return; // handled elsewhere
      a.addEventListener('click', e => {
        e.preventDefault();
        const msg = a.getAttribute('data-confirm') || a.title || a.textContent || 'Delete this item?';
        const href = a.getAttribute('href');

        // Create modern confirmation modal
        showConfirmModal(msg.trim(), () => {
          // On confirm, navigate to delete URL
          window.location.href = href;
        });
      });
    });
  }

  // Modern confirmation modal
  function showConfirmModal(message, onConfirm) {
    // Create modal elements
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

    // Add styles if not already present
    if (!document.getElementById('confirm-modal-styles')) {
      const style = document.createElement('style');
      style.id = 'confirm-modal-styles';
      style.textContent = `
        .confirm-modal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;animation:fadeIn .15s ease-out}
        .confirm-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(2px)}
        .confirm-modal__panel{position:relative;background:#fff;border-radius:16px;padding:40px 32px 32px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:slideUp .2s cubic-bezier(.2,.8,.2,1)}
        .confirm-modal__icon{display:flex;justify-content:center;margin-bottom:24px}
        .confirm-modal__title{font-size:22px;font-weight:700;color:#111827;text-align:center;margin:0 0 32px}
        .confirm-modal__message{font-size:15px;color:#6B7280;text-align:center;margin:0 0 28px;line-height:1.5}
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
        document.body.removeChild(modal);
        document.body.style.overflow = '';
      }, 150);
    };

    btnCancel.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    btnConfirm.addEventListener('click', () => {
      closeModal();
      if (onConfirm) onConfirm();
    });

    // ESC key to close
    const handleEsc = (e) => {
      if (e.key === 'Escape') {
        closeModal();
        document.removeEventListener('keydown', handleEsc);
      }
    };
    document.addEventListener('keydown', handleEsc);
  }


  // ---------- global: normalize status badges ----------
  function normalizeStatusBadges() {
    const remove = [
      'status-active', 'status-inactive', 'status-maintenance', 'status-delayed',
      'status-available', 'status-leave', 'status-out', 'status-open',
      'status-progress', 'status-resolved'
    ];
    $$('.js-status-badge, .status-badge').forEach(badge => {
      const t = (badge.textContent || '').trim().toLowerCase();
      remove.forEach(c => badge.classList.remove(c));
      if (t === 'active') badge.classList.add('status-active');
      else if (t === 'inactive' || t === 'suspended') badge.classList.add('status-inactive');
      else if (t === 'maintenance' || t === 'maint.' || t === 'maint') badge.classList.add('status-maintenance');
      else if (t === 'delayed') badge.classList.add('status-delayed');
      else if (t === 'out of service' || t === 'out') badge.classList.add('status-out');
      else if (t === 'on leave' || t === 'leave') badge.classList.add('status-leave');
      else if (t === 'in progress' || t === 'progress') badge.classList.add('status-progress');
      else if (t === 'resolved' || t === 'closed') badge.classList.add('status-resolved');
      else badge.classList.add('status-available');
    });
  }

  // ---------- feedback: normalize type badges ----------
  function normalizeTypeBadges() {
    $$('.js-type-badge, .type-badge').forEach(badge => {
      const t = (badge.textContent || '').trim().toLowerCase();
      badge.classList.remove('type-complaint', 'type-feedback');
      if (t === 'complaint') badge.classList.add('type-complaint');
      else badge.classList.add('type-feedback');
    });
  }

  // ---------- dashboard: header date ----------
  function setupHeaderDate() {
    const el = document.querySelector('.header-date .js-today');
    if (!el) return;
    function updateDateTime() {
      const now = new Date();
      const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      el.textContent = 'Today: ' + now.toLocaleDateString(undefined, opts);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
  }

  // ---------- earnings ----------
  function flagNegativeRevenues() {
    $$('.revenue-amount').forEach(el => {
      const raw = (el.textContent || '').replace(/[^\d.\-]/g, '');
      const val = parseFloat(raw);
      if (!isNaN(val) && val < 0) el.classList.add('negative');
    });
  }

  // ---------- feedback helpers ----------
  function findRowByRef(ref) {
    if (!ref) return null;
    const esc = (window.CSS && CSS.escape) ? CSS.escape(ref) : ref.replace(/["\\]/g, '\\$&');
    return document.querySelector(`tr[data-ref="${esc}"]`);
  }
  function setStatusBadgeText(ref, status) {
    const row = findRowByRef(ref);
    if (!row) return;
    const badge = row.querySelector('.js-status-badge, .status-badge');
    if (!badge) return;
    const s = String(status || 'Open');
    badge.textContent = s;
    badge.dataset.status = s;
    normalizeStatusBadges();
  }
  function mirrorRefToSelectors(ref) {
    $$('.js-ref-select, .js-ref-select-2').forEach(sel => {
      if (!sel) return;
      const has = Array.prototype.some.call(sel.options, o => o.value === ref);
      if (has) sel.value = ref;
    });
  }
  function twoWaySyncRefs() {
    const s1 = document.querySelector('.js-ref-select');
    const s2 = document.querySelector('.js-ref-select-2');
    if (!s1 || !s2) return;
    s1.addEventListener('change', () => { if (!s2.value) s2.value = s1.value; });
    s2.addEventListener('change', () => { if (!s1.value) s1.value = s2.value; });
  }

  // ---------- feedback: view (dialog + fallback) ----------
  function setupFeedbackView() {
    const dialog = $('#feedback-dialog');
    const dRef = dialog ? $('.js-dialog-ref', dialog) : null;
    const dMsg = dialog ? $('.js-dialog-msg', dialog) : null;
    const useBtn = dialog ? $('.js-use-id-btn', dialog) : null;

    $$('.js-view').forEach(btn => {
      btn.addEventListener('click', () => {
        const ref = btn.getAttribute('data-ref') || '';
        const msg = btn.getAttribute('data-message') || 'No message';

        if (dialog && typeof dialog.showModal === 'function') {
          dRef.textContent = ref;
          dMsg.textContent = msg;
          dialog.showModal();
        } else {
          alert(`Feedback ID: ${ref}\n\n${msg}`);
          mirrorRefToSelectors(ref);
        }
      });
    });

    if (dialog && useBtn) {
      useBtn.addEventListener('click', (e) => {
        e.preventDefault();
        mirrorRefToSelectors(dRef.textContent || '');
        dialog.close();
      });
    }

    twoWaySyncRefs();
  }

  // ---------- feedback: AJAX submit ----------
  function wireFeedbackAjax() {
    const statusForm = $('.js-update-status-form');
    const responseForm = $('.js-send-response-form');

    if (statusForm) {
      statusForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const refSel = $('.js-ref-select', statusForm);
        const stSel = $('.js-status-select', statusForm);
        const ref = (refSel && refSel.value) || '';
        const status = (stSel && stSel.value) || '';
        if (!ref || !status) { toast('Select a feedback ID and status', false); return; }

        const ok = await postFormOrFallback(statusForm);
        if (ok) { setStatusBadgeText(ref, status); toast(`Status updated: ${ref} → ${status}`); }
      });
    }

    if (responseForm) {
      responseForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const refSel = $('.js-ref-select-2', responseForm);
        const ta = $('.js-response', responseForm);
        const ref = (refSel && refSel.value) || '';
        const txt = (ta && ta.value || '').trim();
        if (!ref || !txt) { toast('Enter a response and select an ID', false); return; }

        const ok = await postFormOrFallback(responseForm);
        if (ok && ta) { ta.value = ''; toast(`Response sent to ${ref}`); }
      });
    }
  }

  // ---------- performance ----------
  function setupExportButton() {
    const exportBtn = document.querySelector('.js-export');
    const timeSel = document.querySelector('.js-time-filter');
    if (!exportBtn) return;
    exportBtn.addEventListener('click', () => {
      const base = exportBtn.getAttribute('data-export-href') || '/reports/export';
      const range = (timeSel && timeSel.value) ? timeSel.value : '6m';
      window.location.href = `${base}?range=${encodeURIComponent(range)}`;
    });
  }
  function markRankBadges() {
    $$('.rank-badge').forEach(badge => {
      const n = parseInt(badge.textContent, 10);
      badge.classList.remove('rank-1', 'rank-2', 'rank-3', 'rank-other');
      if (n === 1) badge.classList.add('rank-1');
      else if (n === 2) badge.classList.add('rank-2');
      else if (n === 3) badge.classList.add('rank-3');
      else badge.classList.add('rank-other');
    });
  }

  // ---------- drivers modal ----------
  function setupDriverModal() {
    const modal = document.getElementById('driverModal');
    const form = document.getElementById('driverForm');
    const title = document.getElementById('driverModalTitle');
    const btnAdd = document.getElementById('btnAddDriver');
    const btnAddConductor = document.getElementById('btnAddConductor');
    const btnCancel = document.getElementById('btnCancelModal');
    if (!modal || !form || !title) return;

    let mode = 'create';
    let entity = 'driver';

    const fields = {
      id: document.getElementById('f_id'),
      name: document.getElementById('f_name'),
      phone: document.getElementById('f_phone'),
      license_no: document.getElementById('f_license_no'),
      status: document.getElementById('f_status'),
    };

    const openModal = () => { modal.hidden = false; document.body.style.overflow = 'hidden'; };
    const closeModal = () => { modal.hidden = true; document.body.style.overflow = ''; };

    const setLicenseRequired = (req) => {
      if (!fields.license_no) return;
      if (req) fields.license_no.setAttribute('required', 'required');
      else fields.license_no.removeAttribute('required');
    };

    const resetFields = () => {
      Object.keys(fields).forEach(k => { if (fields[k]) fields[k].value = ''; });
      if (fields.status) fields.status.value = 'Active';
    };

    function setCreate() {
      entity = 'driver';
      mode = 'create';
      title.textContent = 'Add New Driver';
      const btn = document.getElementById('btnSubmitModal');
      if (btn) btn.textContent = 'Add Driver';
      setLicenseRequired(true);
      resetFields();
    }
    function setCreateConductor() {
      entity = 'conductor';
      mode = 'create';
      title.textContent = 'Add New Conductor';
      const btn = document.getElementById('btnSubmitModal');
      if (btn) btn.textContent = 'Add Conductor';
      setLicenseRequired(false);
      resetFields();
    }

    function setEditDriver(d) {
      entity = 'driver'; mode = 'update';
      const id = parseInt(d.private_driver_id || d.id || 0, 10) || 0;
      const btn = document.getElementById('btnSubmitModal');
      title.textContent = 'Edit Driver'; if (btn) btn.textContent = 'Update Driver';
      if (fields.id) fields.id.value = id;
      if (fields.name) fields.name.value = d.full_name || d.name || '';
      if (fields.phone) fields.phone.value = d.phone || '';
      if (fields.license_no) fields.license_no.value = d.license_no || '';
      if (fields.status) fields.status.value = d.status || 'Active';
      setLicenseRequired(true);
    }
    function setEditConductor(c) {
      entity = 'conductor'; mode = 'update';
      const id = parseInt(c.private_conductor_id || c.id || 0, 10) || 0;
      const btn = document.getElementById('btnSubmitModal');
      title.textContent = 'Edit Conductor'; if (btn) btn.textContent = 'Update Conductor';
      if (fields.id) fields.id.value = id;
      if (fields.name) fields.name.value = c.full_name || '';
      if (fields.phone) fields.phone.value = c.phone || '';
      if (fields.status) fields.status.value = c.status || 'Active';
      setLicenseRequired(false);
    }

    btnAdd?.addEventListener('click', e => { e.preventDefault(); setCreate(); openModal(); });
    btnAddConductor?.addEventListener('click', e => { e.preventDefault(); setCreateConductor(); openModal(); });

    $$('.js-edit-driver').forEach(a => a.addEventListener('click', e => { e.preventDefault(); try { setEditDriver(JSON.parse(a.dataset.driver || '{}')); openModal(); } catch (_) { } }));
    $$('.js-view-driver').forEach(a => a.addEventListener('click', e => { e.preventDefault(); try { setEditDriver(JSON.parse(a.dataset.driver || '{}')); openModal(); } catch (_) { } }));
    $$('.js-edit-conductor').forEach(a => a.addEventListener('click', e => { e.preventDefault(); try { setEditConductor(JSON.parse(a.dataset.conductor || '{}')); openModal(); } catch (_) { } }));
    $$('.js-view-conductor').forEach(a => a.addEventListener('click', e => { e.preventDefault(); try { setEditConductor(JSON.parse(a.dataset.conductor || '{}')); openModal(); } catch (_) { } }));

    btnCancel?.addEventListener('click', e => { e.preventDefault(); closeModal(); });
    modal.querySelector('.driver-modal__backdrop')?.addEventListener('click', closeModal);
    window.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    // translate fields -> backend and submit
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const tmp = document.createElement('form');
      tmp.method = 'POST';
      tmp.action = '/B/drivers';

      const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = (v == null ? '' : String(v)); tmp.appendChild(i); };
      const operatorId = form.getAttribute('data-operator-id') || document.body.getAttribute('data-operator-id') || '';
      const rawStatus = (fields.status?.value || 'Active').trim();
      const statusVal = (rawStatus === 'Suspended') ? 'Suspended' : 'Active';

      if (entity === 'driver') {
        add('action', 'update'); // server accepts create/update by presence of id?
        if (fields.id?.value) add('private_driver_id', fields.id.value);
        else add('action', 'create');
        if (operatorId) add('private_operator_id', operatorId);
        add('full_name', fields.name?.value || '');
        add('license_no', fields.license_no?.value || '');
        add('phone', fields.phone?.value || '');
        add('status', statusVal);
      } else {
        add('action', 'update_conductor');
        if (fields.id?.value) add('private_conductor_id', fields.id.value);
        else add('action', 'create_conductor');
        if (operatorId) add('private_operator_id', operatorId);
        add('full_name', fields.name?.value || '');
        add('phone', fields.phone?.value || '');
        add('status', statusVal);
      }

      document.body.appendChild(tmp);
      tmp.submit();
    });
  }

  // ---------- deletes ----------
  function wireDriverAndConductorDeletes() {
    $$('.js-del[data-driver-id]').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.getAttribute('data-driver-id');
        if (!id || !confirm('Delete this driver?')) return;
        const f = document.createElement('form');
        f.method = 'POST'; f.action = '/B/drivers';
        const mk = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
        mk('action', 'delete'); mk('private_driver_id', id);
        document.body.appendChild(f); f.submit();
      });
    });
    $$('.js-del[data-conductor-id]').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.getAttribute('data-conductor-id');
        if (!id || !confirm('Delete this conductor?')) return;
        const f = document.createElement('form');
        f.method = 'POST'; f.action = '/B/drivers';
        const mk = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
        mk('action', 'delete_conductor'); mk('private_conductor_id', id);
        document.body.appendChild(f); f.submit();
      });
    });
  }

  // ---------- status toggles ----------
  function wireStatusToggles() {
    $$('.js-toggle-driver-status').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        let d = {}; try { d = JSON.parse(this.getAttribute('data-driver') || '{}'); } catch (_) { }
        const cur = (d.status || 'Active').toLowerCase();
        const next = (cur === 'suspended') ? 'Active' : 'Suspended';
        const msg = next === 'Suspended' ? 'Suspend this driver?' : 'Activate this driver?';
        if (!confirm(msg)) return;
        const f = document.createElement('form'); f.method = 'POST'; f.action = '/B/drivers';
        const mk = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = (v == null ? '' : String(v)); f.appendChild(i); };
        mk('action', 'update'); mk('private_driver_id', d.private_driver_id || d.id || '');
        mk('full_name', d.full_name || d.name || ''); mk('license_no', d.license_no || ''); mk('phone', d.phone || '');
        mk('status', next); document.body.appendChild(f); f.submit();
      });
    });

    $$('.js-toggle-conductor-status').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        let c = {}; try { c = JSON.parse(this.getAttribute('data-conductor') || '{}'); } catch (_) { }
        const cur = (c.status || 'Active').toLowerCase();
        const next = (cur === 'suspended') ? 'Active' : 'Suspended';
        const msg = next === 'Suspended' ? 'Suspend this conductor?' : 'Activate this conductor?';
        if (!confirm(msg)) return;
        const f = document.createElement('form'); f.method = 'POST'; f.action = '/B/drivers';
        const mk = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = (v == null ? '' : String(v)); f.appendChild(i); };
        mk('action', 'update_conductor'); mk('private_conductor_id', c.private_conductor_id || c.id || '');
        mk('full_name', c.full_name || ''); mk('phone', c.phone || ''); mk('status', next);
        document.body.appendChild(f); f.submit();
      });
    });
  }

  // ---------- fleet: assign driver/conductor ----------
  function setupAssignModal() {
    const modal = document.getElementById('assignModal');
    const form = document.getElementById('assignForm');
    if (!modal || !form) return;

    const btnClose = document.getElementById('assignClose');
    const btnCancel = document.getElementById('assignCancel');
    const regNoInput = document.getElementById('assign_reg_no');
    const driverInp = document.getElementById('assign_driver_id');
    const condInp = document.getElementById('assign_conductor_id');

    let triggerBtn = null;

    const open = (regNo) => {
      regNoInput && (regNoInput.value = regNo || '');
      if (driverInp) driverInp.value = '';
      if (condInp) condInp.value = '';
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    };
    const close = () => {
      modal.hidden = true;
      document.body.style.overflow = '';
    };

    $$('.js-assign').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        triggerBtn = btn;
        const regNo = btn.getAttribute('data-bus-reg') || '';
        const d = btn.getAttribute('data-driver-id') || '';
        const c = btn.getAttribute('data-conductor-id') || '';
        open(regNo);
        if (driverInp) driverInp.value = (d && d !== '0') ? d : '';
        if (condInp) condInp.value = (c && c !== '0') ? c : '';
      });
    });

    btnClose?.addEventListener('click', (e) => { e.preventDefault(); close(); });
    btnCancel?.addEventListener('click', (e) => { e.preventDefault(); close(); });
    modal.querySelector('.modal__backdrop')?.addEventListener('click', close);
    window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const busId = (busIdInput?.value || '').trim();
      const drvVal = (driverInp?.value || '').trim();
      const conVal = (condInp?.value || '').trim();

      if (!busId) { toast('Missing bus ID', false); return; }
      // Allow unassigning by selecting "Unassigned" (value 0) or empty
      // if (!drvVal && !conVal) { toast('Enter driver or conductor', false); return; }

      const ok = await postFormOrFallback(form);
      if (ok) {
        // optimistic row update
        const esc = (window.CSS && CSS.escape) ? CSS.escape(busId) : busId;
        const row = document.querySelector(`tr[data-bus-id="${esc}"]`);
        if (row) {
          const tdDriver = row.querySelector('.td-driver');
          const tdCond = row.querySelector('.td-conductor');

          let drvText = drvVal;
          if (driverInp.tagName === 'SELECT' && driverInp.selectedIndex > -1) {
            const opt = driverInp.options[driverInp.selectedIndex];
            drvText = opt.text;
            if (!drvVal || drvVal === '0') drvText = '';
          }

          let conText = conVal;
          if (condInp.tagName === 'SELECT' && condInp.selectedIndex > -1) {
            const opt = condInp.options[condInp.selectedIndex];
            conText = opt.text;
            if (!conVal || conVal === '0') conText = '';
          }

          if (tdDriver) tdDriver.innerHTML = drvText ? drvText : '<span class="text-secondary">Unassigned</span>';
          if (tdCond) tdCond.innerHTML = conText ? conText : '<span class="text-secondary">Unassigned</span>';
        }
        // keep button dataset in sync for next open
        if (triggerBtn) {
          triggerBtn.setAttribute('data-driver-id', (drvVal && drvVal !== '0') ? drvVal : '0');
          triggerBtn.setAttribute('data-conductor-id', (conVal && conVal !== '0') ? conVal : '0');
        }
        close();
        toast('Assignment saved');
      }
    });
  }

  // ---------- fleet: filter & search ----------
  function setupFleetFilters() {
    const statusFilter = $('#filter-status');
    const assignmentFilter = $('#filter-assignment');
    const routeFilter = $('#filter-route');
    const capacityFilter = $('#filter-capacity');
    const searchInput = $('#fleet-search');
    const table = $('#fleet-table');

    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    // Debounce helper
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Main filter function
    function filterTable() {
      const status = statusFilter?.value || 'all';
      const assignment = assignmentFilter?.value || 'all';
      const route = routeFilter?.value || 'all';
      const capacity = capacityFilter?.value || 'all';
      const searchTerm = searchInput?.value.toLowerCase().trim() || '';

      const rows = Array.from(tbody.querySelectorAll('tr'));

      rows.forEach(row => {
        let visible = true;

        // Status filter
        if (status !== 'all') {
          const rowStatus = row.dataset.status || '';
          visible = visible && (rowStatus === status);
        }

        // Assignment filter
        if (assignment !== 'all' && visible) {
          const hasDriver = row.dataset.driverAssigned === '1';
          const hasConductor = row.dataset.conductorAssigned === '1';

          switch (assignment) {
            case 'fully':
              visible = visible && (hasDriver && hasConductor);
              break;
            case 'missing-driver':
              visible = visible && !hasDriver;
              break;
            case 'missing-conductor':
              visible = visible && !hasConductor;
              break;
            case 'unassigned':
              visible = visible && (!hasDriver && !hasConductor);
              break;
          }
        }

        // Route filter
        if (route !== 'all' && visible) {
          const rowRoute = row.dataset.routeNumber || '';
          visible = visible && (rowRoute === route);
        }

        // Capacity filter
        if (capacity !== 'all' && visible) {
          const cap = parseInt(row.dataset.capacity) || 0;
          switch (capacity) {
            case 'small':
              visible = visible && (cap < 30);
              break;
            case 'medium':
              visible = visible && (cap >= 30 && cap <= 50);
              break;
            case 'large':
              visible = visible && (cap > 50);
              break;
          }
        }

        // Search filter
        if (searchTerm && visible) {
          const busNumber = (row.dataset.busNumber || '').toLowerCase();
          const driverName = (row.dataset.driverName || '').toLowerCase();
          const conductorName = (row.dataset.conductorName || '').toLowerCase();

          visible = visible && (
            busNumber.includes(searchTerm) ||
            driverName.includes(searchTerm) ||
            conductorName.includes(searchTerm)
          );
        }

        // Show/hide row
        row.style.display = visible ? '' : 'none';
      });

      // Refresh pagination after filtering
      if (window.refreshFleetPagination) {
        window.refreshFleetPagination();
      }
    }

    // Attach event listeners
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (assignmentFilter) assignmentFilter.addEventListener('change', filterTable);
    if (routeFilter) routeFilter.addEventListener('change', filterTable);
    if (capacityFilter) capacityFilter.addEventListener('change', filterTable);
    if (searchInput) searchInput.addEventListener('input', debounce(filterTable, 300));
  }

  // ---------- fleet: table sorting and pagination ----------
  function setupFleetSortingAndPagination() {
    const table = $('#fleet-table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    // State management
    let currentSort = { key: null, direction: 'asc' };
    let currentPage = 1;
    let rowsPerPage = 10;
    let allRows = [];

    // Elements
    const sortableHeaders = $$('th.sortable', table);
    const rowsPerPageSelect = $('#rows-per-page');
    const prevBtn = $('#prev-page');
    const nextBtn = $('#next-page');
    const pagesContainer = $('#pagination-pages');
    const statsEl = $('#pagination-stats');

    // Initialize: collect all rows
    function initRows() {
      allRows = Array.from(tbody.querySelectorAll('tr'));
    }

    // Get visible rows (after filters applied by setupFleetFilters)
    function getVisibleRows() {
      return allRows.filter(row => row.style.display !== 'none');
    }

    // Sorting functions
    function sortRows(rows, sortKey, direction) {
      const sorted = [...rows].sort((a, b) => {
        let valA, valB;

        switch (sortKey) {
          case 'status':
            valA = (a.dataset.status || '').trim().toLowerCase();
            valB = (b.dataset.status || '').trim().toLowerCase();
            break;
          case 'route_number':
            valA = (a.dataset.routeNumber || '').trim();
            valB = (b.dataset.routeNumber || '').trim();
            break;
          case 'location':
            valA = (a.dataset.location || '').trim().toLowerCase();
            valB = (b.dataset.location || '').trim().toLowerCase();
            break;
          default:
            return 0;
        }

        if (valA < valB) return direction === 'asc' ? -1 : 1;
        if (valA > valB) return direction === 'asc' ? 1 : -1;
        return 0;
      });
      return sorted;
    }

    // Apply sorting
    function applySorting() {
      if (!currentSort.key) return;

      const visibleRows = getVisibleRows();
      const sorted = sortRows(visibleRows, currentSort.key, currentSort.direction);

      // Re-append sorted rows
      sorted.forEach(row => tbody.appendChild(row));

      // Update header indicators
      sortableHeaders.forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (th.dataset.sortKey === currentSort.key) {
          th.classList.add(`sort-${currentSort.direction}`);
        }
      });
    }

    // Pagination: calculate page count
    function getTotalPages() {
      const visibleRows = getVisibleRows();
      return Math.ceil(visibleRows.length / rowsPerPage) || 1;
    }

    // Pagination: render page numbers
    function renderPageNumbers() {
      if (!pagesContainer) return;

      pagesContainer.innerHTML = '';
      const totalPages = getTotalPages();

      if (totalPages <= 1) return;

      const maxVisible = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
      let endPage = Math.min(totalPages, startPage + maxVisible - 1);

      if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
      }

      // First page
      if (startPage > 1) {
        const btn = document.createElement('button');
        btn.className = 'page-number';
        btn.textContent = '1';
        btn.addEventListener('click', () => goToPage(1));
        pagesContainer.appendChild(btn);

        if (startPage > 2) {
          const ellipsis = document.createElement('span');
          ellipsis.className = 'page-number ellipsis';
          ellipsis.textContent = '...';
          pagesContainer.appendChild(ellipsis);
        }
      }

      // Page numbers
      for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-number';
        if (i === currentPage) btn.classList.add('active');
        btn.textContent = i;
        btn.addEventListener('click', () => goToPage(i));
        pagesContainer.appendChild(btn);
      }

      // Last page
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          const ellipsis = document.createElement('span');
          ellipsis.className = 'page-number ellipsis';
          ellipsis.textContent = '...';
          pagesContainer.appendChild(ellipsis);
        }

        const btn = document.createElement('button');
        btn.className = 'page-number';
        btn.textContent = totalPages;
        btn.addEventListener('click', () => goToPage(totalPages));
        pagesContainer.appendChild(btn);
      }
    }

    // Pagination: show current page
    function showCurrentPage() {
      const visibleRows = getVisibleRows();
      const totalPages = getTotalPages();

      // Ensure current page is within bounds
      if (currentPage > totalPages) currentPage = totalPages;
      if (currentPage < 1) currentPage = 1;

      const startIdx = (currentPage - 1) * rowsPerPage;
      const endIdx = startIdx + rowsPerPage;

      // Hide all rows first
      allRows.forEach(row => {
        if (row.style.display !== 'none') {
          row.style.display = 'none';
        }
      });

      // Show only rows for current page
      visibleRows.forEach((row, idx) => {
        if (idx >= startIdx && idx < endIdx) {
          row.style.display = '';
        }
      });

      // Update pagination controls
      if (prevBtn) prevBtn.disabled = currentPage === 1;
      if (nextBtn) nextBtn.disabled = currentPage === totalPages;

      // Update stats
      if (statsEl) {
        const start = visibleRows.length === 0 ? 0 : startIdx + 1;
        const end = Math.min(endIdx, visibleRows.length);
        statsEl.textContent = `Showing ${start}-${end} of ${visibleRows.length}`;
      }

      renderPageNumbers();
    }

    // Navigate to specific page
    function goToPage(page) {
      currentPage = page;
      showCurrentPage();
    }

    // Complete update: sort → paginate
    function updateTableDisplay() {
      applySorting();
      showCurrentPage();
    }

    // Event: sortable header click
    sortableHeaders.forEach(th => {
      th.addEventListener('click', () => {
        const sortKey = th.dataset.sortKey;

        if (currentSort.key === sortKey) {
          // Toggle direction
          currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
          // New sort column
          currentSort.key = sortKey;
          currentSort.direction = 'asc';
        }

        updateTableDisplay();
      });
    });

    // Event: rows per page change
    if (rowsPerPageSelect) {
      rowsPerPageSelect.addEventListener('change', () => {
        rowsPerPage = parseInt(rowsPerPageSelect.value) || 10;
        currentPage = 1; // Reset to first page
        updateTableDisplay();
      });
    }

    // Event: previous page
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        if (currentPage > 1) goToPage(currentPage - 1);
      });
    }

    // Event: next page
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        const totalPages = getTotalPages();
        if (currentPage < totalPages) goToPage(currentPage + 1);
      });
    }

    // Initialize
    initRows();
    updateTableDisplay();

    // Expose refresh function for filters
    window.refreshFleetPagination = function () {
      currentPage = 1; // Reset to first page when filters change
      updateTableDisplay();
    };
  }

  // ---------- boot ----------
  onReady(function () {
    console.info('[app.js] booting…');

    safe('confirmDeletes', confirmDeletes);
    safe('normalizeStatusBadges', normalizeStatusBadges);
    safe('normalizeTypeBadges', normalizeTypeBadges);
    safe('setupHeaderDate', setupHeaderDate);
    safe('flagNegativeRevenues', flagNegativeRevenues);

    // feedback
    safe('setupFeedbackView', setupFeedbackView);
    safe('wireFeedbackAjax', wireFeedbackAjax);

    // performance
    safe('setupExportButton', setupExportButton);
    safe('markRankBadges', markRankBadges);

    // drivers/conductors
    safe('setupDriverModal', setupDriverModal);
    safe('wireDriverAndConductorDeletes', wireDriverAndConductorDeletes);
    safe('wireStatusToggles', wireStatusToggles);

    // fleet assign
    safe('setupAssignModal', setupAssignModal);

    // fleet filters & search
    safe('setupFleetFilters', setupFleetFilters);

    // fleet sorting & pagination
    safe('setupFleetSortingAndPagination', setupFleetSortingAndPagination);

    console.info('[app.js] ready.');
  });
})();
/* NeXBus — Feedback page (works with BusOwnerController::feedback) */
(function () {
  'use strict';

  // ------- helpers -------
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const page = $('#feedbackPage');
  const BASE = page?.dataset.endpoint?.replace(/\/+$/, '') || ''; // /B/feedback

  function toast(msg, ms = 2600) {
    const t = $('#toast'); if (!t) return;
    t.textContent = msg;
    t.style.display = 'block'; t.style.opacity = '1';
    setTimeout(() => { t.style.opacity = '0'; }, ms - 400);
    setTimeout(() => { t.style.display = 'none'; }, ms);
  }

  // ------- badges -------
  const STATUS_CLASS = { 'open': 'status-open', 'in progress': 'status-progress', 'resolved': 'status-resolved', 'closed': 'status-closed' };
  const TYPE_CLASS = { 'complaint': 'type-complaint', 'feedback': 'type-feedback' };

  function applyStatusClass(badgeEl, statusText) {
    if (!badgeEl) return;
    const key = String(statusText || badgeEl.textContent || '').trim().toLowerCase();
    badgeEl.className = badgeEl.className.split(' ').filter(c => !/^status-/.test(c)).join(' ').trim();
    badgeEl.classList.add('status-badge', STATUS_CLASS[key] || 'status-open');
    if (statusText) badgeEl.textContent = statusText;
    badgeEl.dataset.status = badgeEl.textContent;
  }

  function normalizeBadges() {
    $$('.js-status-badge').forEach(b => applyStatusClass(b));
    $$('.js-type-badge').forEach(tb => {
      const t = String(tb.textContent || '').trim().toLowerCase();
      tb.className = tb.className.split(' ').filter(c => !/^type-/.test(c)).join(' ').trim();
      tb.classList.add('type-badge', TYPE_CLASS[t] || 'type-feedback');
      tb.textContent = t ? (t[0].toUpperCase() + t.slice(1)) : 'Feedback';
    });
  }

  // ------- modal view -------
  const dlg = $('#feedback-dialog');
  if (dlg) {
    $$('.js-view').forEach(btn => {
      btn.addEventListener('click', () => {
        const ref = btn.getAttribute('data-ref') || '';
        const msg = btn.getAttribute('data-message') || 'No message';
        $('.js-dialog-ref', dlg).textContent = ref;
        $('.js-dialog-msg', dlg).textContent = msg;
        if (typeof dlg.showModal === 'function') dlg.showModal();
        else dlg.setAttribute('open', 'open');
      });
    });
    const useBtn = $('.js-use-id-btn', dlg);
    if (useBtn) {
      useBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const ref = $('.js-dialog-ref', dlg).textContent.trim();
        [$('.js-ref-select'), $('.js-ref-select-2')].forEach(s => { if (s) s.value = ref; });
        dlg.close ? dlg.close() : dlg.removeAttribute('open');
        toast(`Selected feedback ID ${ref}`);
      });
    }
  }

  // ------- post helper (single endpoint, action in body) -------
  async function postTo(form, actionName) {
    if (!BASE) { toast('Endpoint is missing'); return { ok: false }; }
    const btn = form.querySelector('button[type="submit"]');
    try {
      btn && (btn.disabled = true);

      const fd = new FormData(form);
      fd.append('action', actionName); // ← match BusOwnerController::feedback

      // Controller returns a redirect (HTML). We only care that it was accepted.
      const res = await fetch(BASE, { method: 'POST', body: fd, redirect: 'follow' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return { ok: true };
    } catch (err) {
      toast(err.message || 'Request failed');
      return { ok: false };
    } finally {
      btn && (btn.disabled = false);
    }
  }

  // ------- Update Status -------
  const statusForm = $('.js-update-status-form');
  if (statusForm) {
    statusForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const refSel = $('.js-ref-select', statusForm);
      const stSel = $('.js-status-select', statusForm);
      if (!refSel.value || !stSel.value) { toast('Select Feedback ID and Status'); return; }

      const r = await postTo(statusForm, 'update_status');

      // optimistic UI update
      if (r.ok) {
        const ref = refSel.value;
        const newStatus = stSel.value;
        const row = document.querySelector(`tr[data-ref="${CSS.escape(ref)}"]`);
        if (row) {
          const badge = $('.js-status-badge', row);
          applyStatusClass(badge, newStatus);
          row.classList.add('row-updated');
          setTimeout(() => row.classList.remove('row-updated'), 1200);
        }
        toast(`Status updated to "${newStatus}"`);
      }
    });
  }

  // ------- Send Response -------
  const respForm = $('.js-send-response-form');
  if (respForm) {
    respForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const refSel = $('.js-ref-select-2', respForm);
      const txt = $('.js-response', respForm);
      if (!refSel.value || !txt.value.trim()) { toast('Choose an ID and enter a response'); return; }

      const r = await postTo(respForm, 'send_response');
      if (r.ok) { toast('Response sent'); txt.value = ''; }
    });
  }

  // ------- init -------
  normalizeBadges();
})();
// ------- modal view -------
(function () {
  'use strict';
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const dlg = $('#feedback-dialog');
  if (dlg) {
    $$('.js-view').forEach(btn => {
      btn.addEventListener('click', () => {
        const ref = btn.getAttribute('data-ref') || '';
        const msg = btn.getAttribute('data-message') || 'No message';
        const resp = btn.getAttribute('data-response') || '';

        $('.js-dialog-ref', dlg).textContent = ref;
        $('.js-dialog-msg', dlg).textContent = msg;
        const replyEl = $('.js-dialog-reply', dlg);
        const replyBox = $('.js-dialog-reply-block', dlg);

        if (resp && resp.trim() !== '') {
          replyEl.textContent = resp;
          replyBox.style.display = '';
        } else {
          replyEl.textContent = 'No reply yet.';
          replyBox.style.display = '';
        }

        if (typeof dlg.showModal === 'function') dlg.showModal();
        else dlg.setAttribute('open', 'open');
      });
    });

    const useBtn = $('.js-use-id-btn', dlg);
    if (useBtn) {
      useBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const ref = $('.js-dialog-ref', dlg).textContent.trim();
        [$('.js-ref-select'), $('.js-ref-select-2')].forEach(s => { if (s) s.value = ref; });
        dlg.close ? dlg.close() : dlg.removeAttribute('open');
        toast(`Selected feedback ID ${ref}`);
      });
    }
  }
})();
document.addEventListener('DOMContentLoaded', () => {
  const pwForm = document.querySelector('form[action="change_password"]');
  if (pwForm) {
    pwForm.addEventListener('submit', e => {
      const npw = pwForm.querySelector('[name="new_password"]').value.trim();
      if (npw.length < 6) {
        alert('Password must be at least 6 characters.');
        e.preventDefault();
      }
    });
  }
});

