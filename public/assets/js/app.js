document.addEventListener('DOMContentLoaded', function () {

  // ===== Panels with delegation =====
  document.addEventListener('click', e => {
    const panels = document.querySelectorAll('.panel');

    // Add Fare
    if (e.target.id === 'showAddFare') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addFarePanel')?.classList.add('show');
    }
    if (e.target.id === 'cancelAddFare') {
      document.getElementById('addFarePanel')?.classList.remove('show');
    }

    // Add User
    if (e.target.id === 'showAddU') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addUPanel')?.classList.add('show');
    }
    if (e.target.id === 'cancelAddU') {
      document.getElementById('addUPanel')?.classList.remove('show');
    }

    // Add Timetable
    if (e.target.id === 'showAddTT') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addTTPanel')?.classList.add('show');
    }
    if (e.target.id === 'cancelAddTT') {
      document.getElementById('addTTPanel')?.classList.remove('show');
    }

    // Add Route
    if (e.target.id === 'showAddRoute') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addRoutePanel')?.classList.add('show');
      // switch to create mode + open panel
      setRouteFormMode('create');
      const panel = document.getElementById('addRoutePanel');
      if (panel) panel.style.display = '';
    }
    if (e.target.id === 'cancelAddRoute') {
      document.getElementById('addRoutePanel')?.classList.remove('show');
      // reset to create mode and hide
      setRouteFormMode('create');
      const panel = document.getElementById('addRoutePanel');
      if (panel) panel.style.display = 'none';
    }

    // Add Depot
    if (e.target.id === 'showAddDepot') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addDepotPanel')?.classList.add('show');
    }
    if (e.target.id === 'cancelAddDepot') {
      document.getElementById('addDepotPanel')?.classList.remove('show');
    }

    // Add Owner
    if (e.target.id === 'showAddOwner') {
      panels.forEach(p => p.classList.remove('show'));
      document.getElementById('addOwnerPanel')?.classList.add('show');
    }
    if (e.target.id === 'cancelAddOwner') {
      document.getElementById('addOwnerPanel')?.classList.remove('show');
    }
  });

  // ===== Live bus list by owner/depot =====
  const opSelect    = document.getElementById('operator_type');
  const ownerSelect = document.getElementById('private_operator_id');
  const depotSelect = document.getElementById('sltb_depot_id');
  const busSelect   = document.getElementById('bus_reg_no');
  const ownerWrap   = document.getElementById('ownerWrap');
  const depotWrap   = document.getElementById('depotWrap');

  function populateBuses(list) {
    busSelect.innerHTML = '<option value="">-- select bus --</option>';
    if (!list) return;
    list.forEach(bus => {
      const opt = document.createElement('option');
      opt.value = bus;
      opt.textContent = bus;
      busSelect.appendChild(opt);
    });
  }

  function updateBusOptions() {
    busSelect.innerHTML = '<option value="">-- select owner/depot first --</option>';
    if (opSelect.value === 'Private') {
      const ownerId = ownerSelect.value;
      if (!ownerId) return;
      const owner = (window.__OWNERS__ || []).find(o => o.id == ownerId);
      populateBuses(owner ? owner.buses : []);
    } else if (opSelect.value === 'SLTB') {
      const depotId = depotSelect.value;
      if (!depotId) return;
      const depot = (window.__DEPOTS__ || []).find(d => d.id == depotId);
      populateBuses(depot ? depot.buses : []);
    }
  }

  if (ownerSelect) ownerSelect.addEventListener('change', updateBusOptions);
  if (depotSelect) depotSelect.addEventListener('change', updateBusOptions);

  function updateOperatorFields() {
    if (!opSelect) return;
    if (opSelect.value === 'SLTB') {
      if (ownerWrap) ownerWrap.style.display = 'none';
      if (depotWrap) depotWrap.style.display = 'block';
      updateBusOptions();
    } else {
      if (ownerWrap) ownerWrap.style.display = 'block';
      if (depotWrap) depotWrap.style.display = 'none';
      updateBusOptions();
    }
  }

  if (opSelect) {
    opSelect.addEventListener('change', updateOperatorFields);
    updateOperatorFields(); // run once at load
  }

  // ===== Tabs (common UI) =====
  const toolbar  = document.getElementById('toolbar');
  const depotBtn = document.getElementById('showAddDepot');
  const ownerBtn = document.getElementById('showAddOwner');

  function toggleToolbar(tab) {
    // close all panels when switching
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));

    if (tab === 'depots') {
      depotBtn.classList.remove('hide');
      ownerBtn.classList.add('hide');
    } else if (tab === 'owners') {
      depotBtn.classList.add('hide');
      ownerBtn.classList.remove('hide');
    } else {
      depotBtn.classList.add('hide');
      ownerBtn.classList.add('hide');
    }
  }

  document.addEventListener('click', e => {
    if (e.target.classList.contains('tab')) {
      // switch tab button
      document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
      e.target.classList.add('active');

      // switch tab content
      document.querySelectorAll('.tabcontent').forEach(x => x.classList.remove('show'));
      document.getElementById(e.target.dataset.tab).classList.add('show');

      toggleToolbar(e.target.dataset.tab);
    }
  });

  // run once on page load
  const activeTab = document.querySelector('.tabs .tab.active');
  if (activeTab) {
    toggleToolbar(activeTab.dataset.tab);
  }

  // ===== Sidebar navigation with animation =====


  // ===== Global search (filters both Depots + Owners without reload) =====
  const searchInput = document.getElementById('globalSearch');
  const clearBtn    = document.getElementById('clearSearch');

  function debounce(fn, wait) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  }

  function setQueryParam(q) {
    const url = new URL(location.href);
    if (q) url.searchParams.set('q', q);
    else url.searchParams.delete('q');
    history.replaceState(null, '', url.toString());
  }

  function filterSection(sectionId, q) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    const qq = (q || '').toLowerCase();

    // 1) New UI: accordion cards (cities)
    const cards = section.querySelectorAll('.route-card');
    if (cards.length) {
      cards.forEach(card => {
        const cardText = (card.textContent || '').toLowerCase();
        const showCard = !qq || cardText.includes(qq);
        card.style.display = showCard ? '' : 'none';

        // If expanded, also filter inner table rows for nicer UX
        const innerRows = card.querySelectorAll('tbody tr');
        if (innerRows.length) {
          innerRows.forEach(tr => {
            const showRow = !qq || (tr.textContent || '').toLowerCase().includes(qq);
            tr.style.display = showRow ? '' : 'none';
          });
        }
      });
      return;
    }

    // 2) Legacy UI: plain tables
    const rows = section.querySelectorAll('tbody tr');
    rows.forEach(tr => {
      const show = !qq || (tr.textContent || '').toLowerCase().includes(qq);
      tr.style.display = show ? '' : 'none';
    });
  }

  function updateNoResults(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    const msgId = `noResults-${sectionId}`;
    let msg = document.getElementById(msgId);

    const cards = section.querySelectorAll('.route-card');
    const visibleCount = cards.length
      ? Array.from(cards).filter(el => el.style.display !== 'none').length
      : Array.from(section.querySelectorAll('tbody tr')).filter(el => el.style.display !== 'none').length;

    if (visibleCount === 0) {
      if (!msg) {
        msg = document.createElement('div');
        msg.id = msgId;
        msg.style.cssText = 'text-align:center;color:#777;padding:12px';
        msg.textContent = 'No results found.';
        section.appendChild(msg);
      }
    } else {
      msg?.remove();
    }
  }

  function applyFilter(q) {
    filterSection('depots', q);
    filterSection('owners', q);
    updateNoResults('depots');
    updateNoResults('owners');
    setQueryParam(q);
  }

  if (searchInput) {
    // Prefill from ?q= and apply on load
    const qs = new URLSearchParams(location.search);
    const initialQ = (qs.get('q') || '').trim();
    if (initialQ) searchInput.value = initialQ;
    applyFilter(initialQ);

    // Filter as user types
    searchInput.addEventListener('input', debounce(() => {
      applyFilter(searchInput.value.trim());
    }, 150));

    // Prevent form-style Enter submit; just filter
    searchInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        applyFilter(searchInput.value.trim());
      }
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      if (searchInput) {
        searchInput.value = '';
        applyFilter('');
        searchInput.focus();
      }
    });
  }

  // ===== Routes table sorting =====
  const routesTable = document.getElementById('routesTable');
  if (routesTable) {
    const tbody = routesTable.querySelector('tbody');
    routesTable.querySelectorAll('thead th.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const key = th.dataset.key;
        // toggle between asc and desc; default to asc when unset
        const dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';

        // clear indicators on other headers, keep current toggled
        routesTable.querySelectorAll('thead th.sortable').forEach(h => {
          if (h !== th) { h.classList.remove('asc', 'desc'); h.dataset.dir = ''; }
        });
        th.classList.remove('asc', 'desc');
        th.classList.add(dir);
        th.dataset.dir = dir;

        const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => !tr.classList.contains('empty-row'));
        rows.sort((a, b) => {
          const av = a.dataset[key] ?? '';
          const bv = b.dataset[key] ?? '';
          const an = Number(av), bn = Number(bv);
          let cmp;
          if (!Number.isNaN(an) && !Number.isNaN(bn) && av !== '' && bv !== '') {
            cmp = an - bn;
          } else {
            cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
          }
          return dir === 'asc' ? cmp : -cmp;
        });
        rows.forEach(tr => tbody.appendChild(tr));
      });
    });
  }

  // ===== Week popup for Today schedules =====
  function ensureWeekPopup() {
    let el = document.getElementById('weekPopup');
    if (!el) {
      el = document.createElement('div');
      el.id = 'weekPopup';
      el.className = 'popup-week';
      document.body.appendChild(el);
    }
    return el;
  }

  function renderWeekPopup(weekCounts, route, anchor) {
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const el = ensureWeekPopup();
    const items = days.map((d, i) => `<div class="wk-item"><span class="wk-day">${d}</span><span class="wk-val">${Number(weekCounts[i] || 0)}</span></div>`).join('');
    el.innerHTML = `<div class="wk-head">Route ${route} · Weekly schedules</div><div class="wk-grid">${items}</div>`;

    // position near anchor (account for scroll)
    const r = anchor.getBoundingClientRect();
    const top = r.bottom + window.scrollY + 6;
    const left = Math.min(r.left + window.scrollX, window.scrollX + window.innerWidth - 260);
    el.style.top = `${top}px`;
    el.style.left = `${left}px`;
    el.classList.add('show');
  }

  function hideWeekPopup() {
    const el = document.getElementById('weekPopup');
    if (el) el.classList.remove('show');
  }

  document.addEventListener('click', e => {
    // Robust: works when clicking nested elements inside the button.
    // Also allow legacy id="weekBtn" if any older template still uses it.
    const btn = e.target.closest?.('.week-btn') || (e.target.id === 'weekBtn' ? e.target : null);
    if (btn) {
      try {
        const week = JSON.parse(btn.dataset.week || '[]');
        const route = btn.dataset.route || '';
        renderWeekPopup(week, route, btn);
      } catch {
        hideWeekPopup();
      }
      e.stopPropagation();
      return;
    }

    // click outside closes
    const popup = document.getElementById('weekPopup');
    if (popup && !popup.contains(e.target)) hideWeekPopup();
  });

  window.addEventListener('scroll', hideWeekPopup, { passive: true });
  window.addEventListener('resize', hideWeekPopup);

  // ===== Fare accordion =====
  document.addEventListener('click', e => {
    const btn = e.target.closest('.fare-toggle');
    if (!btn) return;
    const card = btn.closest('.fare-card');
    const open = card.classList.contains('open');
    document.querySelectorAll('.fare-card.open').forEach(c => c.classList.remove('open'));
    if (!open) card.classList.add('open');
  });
});

// helper: scroll admin content area (preferred) or fallback to window
function scrollToAdminTop() {
  const container = document.getElementById('content');
  if (container) {
    container.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// ===== Edit User (open + prefill) =====
document.addEventListener('click', e => {
  const editBtn = e.target.closest('.btn-edit');
  if (!editBtn) return;

  e.preventDefault();
  // close other panels and open edit
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
  document.getElementById('editUPanel')?.classList.add('show');

  // NEW: scroll to top so the form is visible
  scrollToAdminTop();

  const d = editBtn.dataset;

  document.getElementById('edit_user_id').value    = d.userId || '';
  document.getElementById('edit_first_name').value = d.firstName || '';
  document.getElementById('edit_last_name').value  = d.lastName || '';
  document.getElementById('edit_email').value      = d.email || '';
  document.getElementById('edit_phone').value      = d.phone || '';
  document.getElementById('edit_role').value       = d.role || 'Passenger';

  const poSel = document.getElementById('edit_private_operator_id');
  const dpSel = document.getElementById('edit_sltb_depot_id');
  if (poSel) poSel.value = d.privateOperatorId || '';
  if (dpSel) dpSel.value = d.sltbDepotId || '';

  const pw = document.getElementById('edit_password');
  if (pw) pw.value = '';

  // optional: focus first field after scroll/open
  setTimeout(() => document.getElementById('edit_first_name')?.focus(), 200);
});

// Cancel edit
document.addEventListener('click', e => {
  if (e.target.id === 'cancelEditU') {
    document.getElementById('editUPanel')?.classList.remove('show');
  }
});

// ===== Route form helpers (create/edit) =====
function setRouteFormMode(mode, data) {
  const panel = document.getElementById('addRoutePanel');
  const form  = document.getElementById('routeForm');
  if (!form) return;

  const actionEl = document.getElementById('route_action');
  const idEl     = document.getElementById('route_id');
  const noEl     = document.getElementById('route_no');
  const actSel   = document.getElementById('route_is_active');
  const submitEl = document.getElementById('routeSubmitBtn');

  if (mode === 'edit') {
    actionEl.value = 'update_route';
    idEl.value     = data.routeId || '';
    noEl.value     = data.routeNo || '';
    actSel.value   = data.isActive === '0' ? '0' : '1';

    // stops: array of strings or objects
    let arr = [];
    try { arr = JSON.parse(data.stops || '[]'); } catch {}
    const names = (arr || []).map(s => {
      if (typeof s === 'string') return s;
      if (s && typeof s === 'object') return s.stop || s.name || s.code || '';
      return '';
    }).filter(Boolean);

    if (typeof window.resetStops === 'function') window.resetStops();
    if (typeof window.setStops === 'function') window.setStops(names);

    if (submitEl) submitEl.textContent = 'Update Route';

    // open panel
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
    if (panel) { panel.classList.add('show'); panel.style.display = ''; }

    // scroll to top smoothly and focus first field
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (noEl) setTimeout(() => noEl.focus(), 200);
  } else {
    actionEl.value = 'create_route';
    idEl.value     = '';
    if (noEl) noEl.value = '';
    if (actSel) actSel.value = '1';
    if (typeof window.resetStops === 'function') window.resetStops();
    if (submitEl) submitEl.textContent = 'Save Route';
  }
}

// Open edit panel from table
document.addEventListener('click', e => {
  const btn = e.target.closest('.btn-edit-route');
  if (!btn) return;

  const data = {
    routeId:  btn.dataset.routeId || '',
    routeNo:  btn.dataset.routeNo || '',
    isActive: btn.dataset.isActive || '1',
    stops:    btn.dataset.stops || '[]'
  };
  setRouteFormMode('edit', data);
});

// Route accordion toggle
document.addEventListener('click', e => {
  const btn = e.target.closest('.route-toggle');
  if (!btn) return;
  const card = btn.closest('.route-card');
  const open = card.classList.contains('open');
  document.querySelectorAll('.route-card.open').forEach(c => {
    c.classList.remove('open');
    c.querySelector('.route-toggle')?.setAttribute('aria-expanded', 'false');
  });
  if (!open) {
    card.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
  } else {
    btn.setAttribute('aria-expanded', 'false');
  }
});

// ===== Edit Fare (open + prefill) =====
document.addEventListener('click', e => {
  const btn = e.target.closest('.btn-edit-fare');
  if (!btn) return;
  e.preventDefault();

  // close other panels and open edit
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
  document.getElementById('editFarePanel')?.classList.add('show');

  const d = btn.dataset;
  const dateOnly = v => (v || '').substring(0, 10);

  // fill form fields
  const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };

  setVal('edit_fare_id', d.fareId);
  setVal('edit_route_id', d.routeId);
  setVal('edit_stage_number', d.stageNumber);
  setVal('edit_super_luxury', d.superLuxury);
  setVal('edit_luxury', d.luxury);
  setVal('edit_semi_luxury', d.semiLuxury);
  setVal('edit_normal_service', d.normalService);
  setVal('edit_effective_from', dateOnly(d.effectiveFrom));
  setVal('edit_effective_to', dateOnly(d.effectiveTo));
});

// Cancel Edit Fare
document.addEventListener('click', e => {
  if (e.target.id === 'cancelEditFare') {
    document.getElementById('editFarePanel')?.classList.remove('show');
  }
});

// ===== Edit Timetable (open + prefill) =====
document.addEventListener('click', e => {
  const btn = e.target.closest('.btn-edit-tt');
  if (!btn) return;
  e.preventDefault();

  // close other panels and open edit
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
  document.getElementById('editTTPanel')?.classList.add('show');

  // NEW: scroll to top so the form is visible
  scrollToAdminTop();

  const d = btn.dataset;
  const dateOnly = v => (v || '').substring(0, 10);
  const timeOnly = v => (v || '').substring(0, 5);
  const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };

  setVal('edit_tt_id', d.ttId);
  setVal('edit_operator_type', d.operatorType);
  setVal('edit_route_id', d.routeId);
  setVal('edit_bus_reg_no', d.busRegNo);
  setVal('edit_day_of_week', d.dayOfWeek);
  setVal('edit_departure_time', timeOnly(d.departureTime));
  setVal('edit_arrival_time', timeOnly(d.arrivalTime));
  setVal('edit_effective_from', dateOnly(d.effectiveFrom));
  setVal('edit_effective_to', dateOnly(d.effectiveTo));

  // optional: focus first field after scroll/open
  setTimeout(() => document.getElementById('edit_operator_type')?.focus(), 200);
});

// Cancel Edit Timetable
document.addEventListener('click', e => {
  if (e.target.id === 'cancelEditTT') {
    document.getElementById('editTTPanel')?.classList.remove('show');
  }
});

