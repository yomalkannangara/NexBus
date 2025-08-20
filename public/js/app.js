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

  // ===== Operator type toggle in Add Schedule form =====
  const opSelect = document.getElementById('operator_type');
  const ownerWrap = document.getElementById('ownerWrap');
  const depotWrap = document.getElementById('depotWrap');

  function updateOperatorFields(){
    if (!opSelect) return;
    if(opSelect.value === 'SLTB'){
      if(ownerWrap) ownerWrap.style.display = 'none';
      if(depotWrap) depotWrap.style.display = 'block';
    } else {
      if(ownerWrap) ownerWrap.style.display = 'block';
      if(depotWrap) depotWrap.style.display = 'none';
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
    });
  });

});
