document.addEventListener('DOMContentLoaded', function(){

  // ===== Panels (all use .show with animation) =====
  function bind(btnId, panelId, cancelId, exclusive=false){
    const btn = document.getElementById(btnId),
          panel = document.getElementById(panelId),
          cancel = document.getElementById(cancelId);

    if(btn && panel){
      btn.addEventListener('click', ()=>{
        if(exclusive){
          // close all panels if exclusive
          document.querySelectorAll('.panel').forEach(p=>p.classList.remove('show'));
        }
        panel.classList.add('show');
      });
    }
    if(cancel && panel){
      cancel.addEventListener('click', ()=>{
        panel.classList.remove('show');
      });
    }
  }

  // Add Fare + Add User (independent panels)
  bind('showAddFare','addFarePanel','cancelAddFare');
  bind('showAddU','addUPanel','cancelAddU');

  // Timetable panels (exclusive: only one visible at a time)
  bind('showAddTT','addTTPanel','cancelAddTT', true);
  bind('showAddRoute','addRoutePanel','cancelAddRoute', true);
  bind('showAddDepot','addDepotPanel','cancelAddDepot', true);

  
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
    // always reset first
    busSelect.innerHTML = '<option value="">-- select owner/depot first --</option>';

    if (opSelect.value === 'Private') {
      const ownerId = ownerSelect.value;
      if (!ownerId) return; // nothing selected
      const owner = (window.__OWNERS__ || []).find(o => o.id == ownerId);
      populateBuses(owner ? owner.buses : []);
    } 
    else if (opSelect.value === 'SLTB') {
      const depotId = depotSelect.value;
      if (!depotId) return; // nothing selected
      const depot = (window.__DEPOTS__ || []).find(d => d.id == depotId);
      populateBuses(depot ? depot.buses : []);
    }
  }

  if (ownerSelect) ownerSelect.addEventListener('change', updateBusOptions);
  if (depotSelect) depotSelect.addEventListener('change', updateBusOptions);

  function updateOperatorFields(){
    if (!opSelect) return;
    if(opSelect.value === 'SLTB'){
      if(ownerWrap) ownerWrap.style.display = 'none';
      if(depotWrap) depotWrap.style.display = 'block';
      updateBusOptions();
    } else {
      if(ownerWrap) ownerWrap.style.display = 'block';
      if(depotWrap) depotWrap.style.display = 'none';
      updateBusOptions();
    }
  }

  if(opSelect){
    opSelect.addEventListener('change', updateOperatorFields);
    updateOperatorFields(); // run once at load
  }

  // ===== Tabs (common UI) =====
  document.querySelectorAll('.tab').forEach(btn=>{
    btn.addEventListener('click',()=>{
      document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.tabcontent').forEach(x=>x.classList.remove('show'));
      document.getElementById(btn.dataset.tab).classList.add('show');
      if (btn.dataset.tab === 'depots') {
      depotToolbar.classList.remove('hide');
    } else {
      depotToolbar.classList.add('hide');
    }

    });
  });

});
