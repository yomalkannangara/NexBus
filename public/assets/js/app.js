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


});
