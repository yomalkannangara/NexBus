<section class="page-hero"><h1>Bus Management Dashboard</h1><p>National Transport Commission – Sri Lanka</p></section>

<section class="kpi-wrap">
  <div class="kpi-card"><h3>Total Buses</h3><div class="num"><?= ($stats['p']+$stats['s']) ?></div><div class="trend">+12% from yesterday</div></div>
  <div class="kpi-card"><h3>Registered Bus companies</h3><div class="num"><?= $stats['owners'] ?></div><div class="trend">+3% from yesterday</div></div>
  <div class="kpi-card"><h3>Active Depots</h3><div class="num"><?= $stats['depots'] ?></div><div class="trend">0% from yesterday</div></div>
  <div class="kpi-card"><h3>Active Routes</h3><div class="num"><?= $stats['routes'] ?></div><div class="trend">+5% from yesterday</div></div>
  <div class="kpi-card"><h3>Today's Complaints</h3><div class="num"><?= $stats['complaints'] ?></div><div class="trend">-2% from yesterday</div></div>
  <div class="kpi-card"><h3>Delayed Buses Today</h3><div class="num"><?= $stats['delayed'] ?></div><div class="trend down">+8% from yesterday</div></div>
  <div class="kpi-card"><h3>Broken Buses Today</h3><div class="num"><?= $stats['broken'] ?></div><div class="trend">-1% from yesterday</div></div>
</section>
<section class="filters"><h2>Bus Location Filters</h2><div class="filter-grid"><div><label>Route</label>
<select id="map-filter-route" onchange="applyMapFilters()"><option value="">All Routes</option>
<?php foreach($routes as $r) echo '<option value="'.htmlspecialchars($r['route_no']).'">'.htmlspecialchars($r['route_no']).'</option>'; ?>
</select></div><div><label>Bus Number</label><select id="map-filter-bus" onchange="applyMapFilters()"><option value="">All Buses</option></select></div>

<!-- Live fleet stats row -->
<div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-top:.5rem;padding-top:.5rem;border-top:1px solid #e5e7eb">
  <span style="font-size:.8rem;color:#6b7280">Live fleet:</span>
  <span class="lf-badge lf-badge--green" id="db-live-count">– buses</span>
  <span class="lf-badge lf-badge--red"   id="db-speed-viols">– speeding</span>
  <span style="font-size:.8rem;color:#6b7280;margin-left:auto" id="db-map-updated"></span>
</div>
</div></section>

<!-- ══════════════ LIVE BUS MAP ══════════════ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<section style="margin:0 0 1.5rem;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.12)">
  <div id="admin-bus-map" style="width:100%;height:480px;"></div>
</section>

<style>
/* badge styles (reused on this page) */
.lf-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
.lf-badge--green{background:#dcfce7;color:#15803d}
.lf-badge--red{background:#fee2e2;color:#b91c1c}
.lf-badge--orange{background:#fff7ed;color:#c2410c}

/* Leaflet custom popup */
.bus-popup b{font-size:.95rem}
.bus-popup small{color:#6b7280}
.speed-tag{display:inline-block;padding:1px 7px;border-radius:8px;font-size:.75rem;font-weight:600;margin-top:2px}
.speed-ok{background:#dcfce7;color:#15803d}
.speed-over{background:#fee2e2;color:#b91c1c}
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function(){
  /* ── Map init ── */
  var map = L.map('admin-bus-map', { zoomControl: true }).setView([6.927, 79.861], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(map);

  var markers   = {}; // busId → L.marker
  var allBuses  = []; // latest full data
  var filterRoute = '';
  var filterBus   = '';

  /* ── Beautiful Google-Maps-style SVG pin icon ── */
  function makePinIcon(speed, busId){
    var over  = speed > 60;
    var fill  = over ? '#dc2626' : '#1d6f42'; // deep red or deep green
    var ring  = over ? '#fca5a5' : '#86efac';
    var pulse = over ? '#fee2e2' : '#dcfce7';

    /* Teardrop / map-pin shape with a label bubble */
    var svg =
      '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="54" viewBox="0 0 44 54">'
      /* drop shadow */
      +'<ellipse cx="22" cy="52" rx="9" ry="3" fill="rgba(0,0,0,.22)"/>'
      /* pulse ring (gentle glow) */
      +'<circle cx="22" cy="20" r="19" fill="'+pulse+'" opacity=".55"/>'
      /* pin body */
      +'<path d="M22 2C13.16 2 6 9.16 6 18c0 10.5 16 32 16 32S38 28.5 38 18C38 9.16 30.84 2 22 2z" '
      +'fill="'+fill+'" stroke="'+ring+'" stroke-width="2.5"/>'
      /* white inner circle */
      +'<circle cx="22" cy="18" r="9" fill="#fff"/>'
      /* bus icon (simplified double-deck) */
      +'<rect x="16" y="14" width="12" height="8" rx="1.5" fill="'+fill+'"/>'
      +'<rect x="17" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
      +'<rect x="23" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
      +'<rect x="17" y="19" width="10" height="1.5" rx=".5" fill="#fff" opacity=".6"/>'
      +'</svg>';

    return L.divIcon({
      html       : svg,
      className  : '',
      iconSize   : [44, 54],
      iconAnchor : [22, 52],
      popupAnchor: [0, -50]
    });
  }

  /* ── Pretty popup ── */
  function makePopup(b){
    var over  = b.speedKmh > 60;
    var tag   = over
      ? '<span class="speed-tag speed-over">⚡ '+b.speedKmh+' km/h — SPEEDING</span>'
      : '<span class="speed-tag speed-ok">✓ '+b.speedKmh+' km/h — Normal</span>';
    var upd   = new Date(b.updatedAt).toLocaleTimeString();
    var hdg   = Math.round(b.heading || 0);
    return '<div class="bus-popup">'
      +'<b>🚌 Bus '+b.busId+'</b><br>'
      +'<span style="font-size:.82rem">Route <strong>'+b.routeNo+'</strong></span><br>'
      +tag+'<br>'
      +'<small>Heading '+hdg+'° &nbsp;·&nbsp; Updated '+upd+'</small>'
      +'</div>';
  }

  /* ── Populate bus filter dropdown ── */
  function updateBusDropdown(buses){
    var sel = document.getElementById('map-filter-bus');
    var cur = sel.value;
    sel.innerHTML = '<option value="">All Buses</option>';
    buses.forEach(function(b){
      var o = document.createElement('option');
      o.value = b.busId; o.textContent = b.busId;
      if(b.busId === cur) o.selected = true;
      sel.appendChild(o);
    });
  }

  /* ── Apply current filters to markers ── */
  window.applyMapFilters = function(){
    filterRoute = document.getElementById('map-filter-route').value;
    filterBus   = document.getElementById('map-filter-bus').value;
    allBuses.forEach(function(b){
      var show = (!filterRoute || b.routeNo === filterRoute)
              && (!filterBus   || b.busId   === filterBus);
      var mk = markers[b.busId];
      if(mk){
        if(show){ if(!map.hasLayer(mk)) map.addLayer(mk); }
        else    { if( map.hasLayer(mk)) map.removeLayer(mk); }
      }
    });
  };

  /* ── Fetch & render ── */
  function fetchAndRender(){
    fetch('/live/buses/db')
      .then(function(r){ return r.json(); })
      .then(function(buses){
        if(!Array.isArray(buses)) return;
        allBuses = buses;
        updateBusDropdown(buses);

        var seen   = {};
        var viols  = 0;
        buses.forEach(function(b){
          seen[b.busId] = true;
          if(b.speedKmh > 60) viols++;

          var popup = makePopup(b);
          var icon  = makePinIcon(b.speedKmh, b.busId);
          var show  = (!filterRoute || b.routeNo === filterRoute)
                   && (!filterBus   || b.busId   === filterBus);

          if(markers[b.busId]){
            markers[b.busId]
              .setLatLng([b.lat, b.lng])
              .setIcon(icon)
              .bindPopup(popup);
            if(show){ if(!map.hasLayer(markers[b.busId])) map.addLayer(markers[b.busId]); }
            else    { if( map.hasLayer(markers[b.busId])) map.removeLayer(markers[b.busId]); }
          } else {
            var mk = L.marker([b.lat, b.lng], {icon: icon}).bindPopup(popup);
            if(show) mk.addTo(map);
            markers[b.busId] = mk;
          }
        });

        /* remove stale */
        Object.keys(markers).forEach(function(id){
          if(!seen[id]){ map.removeLayer(markers[id]); delete markers[id]; }
        });

        /* update stats */
        var c = document.getElementById('db-live-count');
        var v = document.getElementById('db-speed-viols');
        var u = document.getElementById('db-map-updated');
        if(c) c.textContent = buses.length+' buses';
        if(v) v.textContent = viols+' speeding';
        if(u) u.textContent = 'Updated '+new Date().toLocaleTimeString();
      })
      .catch(function(){});
  }

  fetchAndRender();
  setInterval(fetchAndRender, 15000);
})();
</script>