<?php $S = $S ?? []; $stats = $stats ?? []; ?>
<div class="title-banner">
  <h1>Private TimeKeeper Dashboard</h1>
  <p><?= htmlspecialchars($S['depot_name'] ?? 'My Operator') ?> - National Transport Commission</p>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:16px;margin:16px 0;">
  <div class="card accent-rose">
    <div class="metric-title">Total Buses in Your Fleet</div>
    <div class="metric-value"><?= (int)($stats['total_buses'] ?? 0) ?></div>
    <div class="metric-sub">All buses linked to your operator</div>
  </div>

  <div class="card accent-blue">
    <div class="metric-title">Total Trips Today</div>
    <div class="metric-value"><?= (int)($stats['total_trips_today'] ?? 0) ?></div>
    <div class="metric-sub">All scheduled operator trips for today</div>
  </div>

  <div class="card accent-amber">
    <div class="metric-title">Delayed Buses Today</div>
    <div class="metric-value"><?= (int)($stats['delayed_buses_total'] ?? 0) ?></div>
    <div class="metric-sub">Buses currently marked delayed</div>
  </div>

  <div class="card accent-green">
    <div class="metric-title">Completed Trips Today</div>
    <div class="metric-value"><?= (int)($stats['completed_trips_today'] ?? 0) ?></div>
    <div class="metric-sub">Completed including delayed arrivals</div>
  </div>

  <div class="card accent-indigo">
    <div class="metric-title">Trips Left Today</div>
    <div class="metric-value"><?= (int)($stats['trips_left_today'] ?? 0) ?></div>
    <div class="metric-sub">Scheduled trips not finished yet</div>
  </div>

  <div class="card accent-cyan">
    <div class="metric-title">Currently Running Buses</div>
    <div class="metric-value"><?= (int)($stats['running_buses_now'] ?? 0) ?></div>
    <div class="metric-sub">Good for immediate dispatch awareness</div>
  </div>
</div>

<section class="filters tk-map-filters">
  <h2>Bus Location Filters</h2>
  <div class="filter-grid">
    <div>
      <label for="tk-private-map-filter-route">Route</label>
      <select id="tk-private-map-filter-route" onchange="applyPrivateMapFilters()">
        <option value="">All Routes</option>
      </select>
    </div>
    <div>
      <label for="tk-private-map-filter-bus">Bus Number</label>
      <select id="tk-private-map-filter-bus" onchange="applyPrivateMapFilters()">
        <option value="">All Buses</option>
      </select>
    </div>
  </div>

  <div class="tk-map-live-stats">
    <span class="tk-map-label">Live fleet:</span>
    <span class="lf-badge lf-badge--green" id="tk-private-live-count">- buses</span>
    <span class="lf-badge lf-badge--red" id="tk-private-speed-viols">- speeding</span>
    <span class="tk-map-updated" id="tk-private-map-updated"></span>
  </div>
</section>

<div class="card tk-map-card">
  <div class="live-header">
    <div class="live-dot"></div>
    <span>Live Bus Locations</span>
  </div>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <div id="tk-private-live-map" class="tk-live-map"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  (function(){
    var mapEl = document.getElementById('tk-private-live-map');
    var routeSelect = document.getElementById('tk-private-map-filter-route');
    var busSelect = document.getElementById('tk-private-map-filter-bus');
    if (!mapEl || !routeSelect || !busSelect || typeof L === 'undefined') return;

    var map = L.map('tk-private-live-map').setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19
    }).addTo(map);

    var markers = {};
    var allBuses = [];
    var filterRoute = '';
    var filterBus = '';
    var focusBus = normId(new URLSearchParams(window.location.search).get('focus_bus') || '');
    var focusedOnce = false;
    var busesRequestInFlight = false;

    function normId(val){
      return String(val || '').trim().toUpperCase();
    }

    function markerKey(bus){
      return normId(bus.busId || bus.busRegNo || '');
    }

    function busIcon(speed){
      var over  = speed > 60;
      var fill  = over ? '#dc2626' : '#1d6f42';
      var ring  = over ? '#fca5a5' : '#86efac';
      var pulse = over ? '#fee2e2' : '#dcfce7';
      var svg =
        '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="54" viewBox="0 0 44 54">'
        +'<ellipse cx="22" cy="52" rx="9" ry="3" fill="rgba(0,0,0,.22)"/>'
        +'<circle cx="22" cy="20" r="19" fill="'+pulse+'" opacity=".55"/>'
        +'<path d="M22 2C13.16 2 6 9.16 6 18c0 10.5 16 32 16 32S38 28.5 38 18C38 9.16 30.84 2 22 2z" fill="'+fill+'" stroke="'+ring+'" stroke-width="2.5"/>'
        +'<circle cx="22" cy="18" r="9" fill="#fff"/>'
        +'<rect x="16" y="14" width="12" height="8" rx="1.5" fill="'+fill+'"/>'
        +'<rect x="17" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
        +'<rect x="23" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
        +'<rect x="17" y="19" width="10" height="1.5" rx=".5" fill="#fff" opacity=".6"/>'
        +'</svg>';

      return L.divIcon({ html: svg, className: '', iconSize:[44,54], iconAnchor:[22,52], popupAnchor:[0,-50] });
    }

    function makePopup(b){
      var speed = Number(b.speedKmh || 0);
      var speedTag = speed > 60
        ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">'+speed.toFixed(1)+' km/h - SPEEDING</span>'
        : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">'+speed.toFixed(1)+' km/h - Normal</span>';

      return '<div style="min-width:150px">'
        +'<b style="font-size:.95rem">Bus '+(b.busId || b.busRegNo || 'N/A')+'</b><br>'
        +'Route: <strong>'+(b.routeNo || '-')+'</strong><br>'
        +speedTag+'<br>'
        +'<small style="color:#6b7280">'+new Date(b.updatedAt || b.snapshotAt || Date.now()).toLocaleTimeString()+'</small>'
        +'</div>';
    }

    function updateRouteDropdown(buses){
      var current = routeSelect.value;
      var routeSet = {};
      var routes = [];

      buses.forEach(function(b){
        var route = String(b.routeNo || '').trim();
        if (route && !routeSet[route]) {
          routeSet[route] = true;
          routes.push(route);
        }
      });

      routes.sort(function(a, b){
        var na = parseInt(a, 10);
        var nb = parseInt(b, 10);
        if (!Number.isNaN(na) && !Number.isNaN(nb) && na !== nb) return na - nb;
        return a.localeCompare(b);
      });

      routeSelect.innerHTML = '<option value="">All Routes</option>';
      routes.forEach(function(route){
        var opt = document.createElement('option');
        opt.value = route;
        opt.textContent = route;
        routeSelect.appendChild(opt);
      });

      routeSelect.value = routeSet[current] ? current : '';
    }

    function updateBusDropdown(buses){
      var current = busSelect.value;
      var ids = [];
      var seen = {};

      buses.forEach(function(b){
        var id = markerKey(b);
        if (id && !seen[id]) {
          seen[id] = true;
          ids.push(id);
        }
      });

      ids.sort(function(a, b){ return a.localeCompare(b); });

      busSelect.innerHTML = '<option value="">All Buses</option>';
      ids.forEach(function(id){
        var opt = document.createElement('option');
        opt.value = id;
        opt.textContent = id;
        busSelect.appendChild(opt);
      });

      busSelect.value = seen[current] ? current : '';
    }

    function shouldShowBus(b){
      var route = String(b.routeNo || '').trim();
      var busId = markerKey(b);
      return (!filterRoute || route === filterRoute) && (!filterBus || busId === filterBus);
    }

    window.applyPrivateMapFilters = function(){
      filterRoute = routeSelect.value;
      filterBus = busSelect.value;

      allBuses.forEach(function(b){
        var id = markerKey(b);
        var mk = markers[id];
        if (!mk) return;

        if (shouldShowBus(b)) {
          if (!map.hasLayer(mk)) map.addLayer(mk);
        } else {
          if (map.hasLayer(mk)) map.removeLayer(mk);
        }
      });
    };

    function renderBuses(buses){
      allBuses = buses.filter(function(b){
        return markerKey(b) && b.lat !== null && b.lng !== null && typeof b.lat !== 'undefined' && typeof b.lng !== 'undefined';
      });

      updateRouteDropdown(allBuses);
      updateBusDropdown(allBuses);
      filterRoute = routeSelect.value;
      filterBus = busSelect.value;

      var seen = {};
      var speedViolations = 0;

      allBuses.forEach(function(b){
        var id = markerKey(b);
        var speed = Number(b.speedKmh || 0);
        seen[id] = true;
        if (speed > 60) speedViolations++;

        var popup = makePopup(b);
        if (markers[id]) {
          markers[id].setLatLng([b.lat, b.lng]).setIcon(busIcon(speed)).bindPopup(popup);
        } else {
          markers[id] = L.marker([b.lat, b.lng], { icon: busIcon(speed) }).bindPopup(popup);
        }

        if (shouldShowBus(b)) {
          if (!map.hasLayer(markers[id])) map.addLayer(markers[id]);
        } else {
          if (map.hasLayer(markers[id])) map.removeLayer(markers[id]);
        }
      });

      Object.keys(markers).forEach(function(id){
        if (!seen[id]) {
          if (map.hasLayer(markers[id])) map.removeLayer(markers[id]);
          delete markers[id];
        }
      });

      if (focusBus && !focusedOnce && markers[focusBus]) {
        if (!map.hasLayer(markers[focusBus])) map.addLayer(markers[focusBus]);
        map.flyTo(markers[focusBus].getLatLng(), Math.max(map.getZoom(), 14), { duration: 0.8 });
        markers[focusBus].openPopup();
        focusedOnce = true;
      }

      var c = document.getElementById('tk-private-live-count');
      var v = document.getElementById('tk-private-speed-viols');
      var u = document.getElementById('tk-private-map-updated');
      if (c) c.textContent = allBuses.length + ' buses';
      if (v) v.textContent = speedViolations + ' speeding';
      if (u) u.textContent = 'Updated ' + new Date().toLocaleTimeString();
    }

    function fetchBuses(){
      if (busesRequestInFlight || document.hidden) return;
      busesRequestInFlight = true;

      fetch('/TP/live')
        .then(function(r){ return r.json(); })
        .then(function(buses){
          if (!Array.isArray(buses)) return;
          renderBuses(buses);
        })
        .catch(function(){})
        .finally(function(){ busesRequestInFlight = false; });
    }

    fetchBuses();
    setInterval(fetchBuses, 15000);
    document.addEventListener('visibilitychange', function(){
      if (!document.hidden) fetchBuses();
    });
  })();
  </script>
</div>

<style>
  @media (max-width: 1024px) {
    .grid { grid-template-columns: repeat(2, 1fr) !important; }
  }

  @media (max-width: 640px) {
    .grid { grid-template-columns: 1fr !important; gap: 12px !important; margin: 12px 0 !important; }
  }

  .tk-map-filters {
    margin: 4px 0 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px;
    background: #fff;
  }

  .tk-map-filters h2 {
    margin: 0 0 10px;
    font-size: 1rem;
  }

  .tk-map-filters .filter-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr));
    gap: 12px;
  }

  .tk-map-filters label {
    display: block;
    margin-bottom: 6px;
    font-size: .88rem;
    color: #4b5563;
  }

  .tk-map-filters select {
    width: 100%;
    min-height: 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 10px;
    background: #fff;
  }

  .tk-map-live-stats {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
  }

  .tk-map-label,
  .tk-map-updated {
    font-size: .8rem;
    color: #6b7280;
  }

  .tk-map-updated {
    margin-left: auto;
  }

  .lf-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 600;
  }

  .lf-badge--green { background: #dcfce7; color: #15803d; }
  .lf-badge--red { background: #fee2e2; color: #b91c1c; }

  .tk-map-card { margin-top: 16px; }

  .tk-live-map {
    width: 100%;
    height: 360px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
  }

  @media (max-width: 720px) {
    .tk-map-filters .filter-grid {
      grid-template-columns: 1fr;
    }
    .tk-map-updated {
      margin-left: 0;
      width: 100%;
    }
  }
</style>
