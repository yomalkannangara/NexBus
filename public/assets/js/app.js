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
    }
    if (e.target.id === 'cancelAddRoute') {
      document.getElementById('addRoutePanel')?.classList.remove('show');
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
    const rows = document.querySelectorAll(`#${sectionId} tbody tr`);
    const qq = (q || '').toLowerCase();
    rows.forEach(tr => {
      const show = !qq || tr.textContent.toLowerCase().includes(qq);
      tr.style.display = show ? '' : 'none';
    });
  }

  function applyFilter(q) {
    filterSection('depots', q);
    filterSection('owners', q);
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
});
// ===== Edit User (open + prefill) =====
document.addEventListener('click', e => {
  const editBtn = e.target.closest('.btn-edit');
  if (!editBtn) return;

  e.preventDefault();
  // close other panels and open edit
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('show'));
  document.getElementById('editUPanel')?.classList.add('show');

  // pull data-* off the clicked button
  const d = editBtn.dataset;
  // fill form fields
  document.getElementById('edit_user_id').value            = d.userId || '';
  document.getElementById('edit_full_name').value          = d.fullName || '';
  document.getElementById('edit_email').value              = d.email || '';
  document.getElementById('edit_phone').value              = d.phone || '';
  document.getElementById('edit_role').value               = d.role || 'Passenger';

  // selects: may be "" or an id
  const poSel = document.getElementById('edit_private_operator_id');
  const dpSel = document.getElementById('edit_sltb_depot_id');
  if (poSel) poSel.value = d.privateOperatorId || '';
  if (dpSel) dpSel.value = d.sltbDepotId || '';
  
  // clear password field each open
  const pw = document.getElementById('edit_password');
  if (pw) pw.value = '';
});

// Cancel edit
document.addEventListener('click', e => {
  if (e.target.id === 'cancelEditU') {
    document.getElementById('editUPanel')?.classList.remove('show');
  }
});

