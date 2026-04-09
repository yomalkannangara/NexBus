<?php $S = $S ?? []; $stats = $stats ?? []; ?>
<div class="title-banner">
    <h1>Private TimeKeeper Dashboard</h1>
    <p><?= htmlspecialchars($S['depot_name'] ?? 'My Operator') ?> — National Transport Commission</p>
</div>

<!-- your dashboard cards -->
<div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:16px;margin:16px 0;">
  <div class="card accent-rose">
    <div class="metric-title">Total Buses Assigned Today</div>
    <div class="metric-value"><?= (int)($stats['assigned_today'] ?? 0) ?></div>
    <div class="metric-sub">
      <?= ($stats['assigned_delta'] ?? 0) >= 0 ? '+' : '' ?><?= (int)($stats['assigned_delta'] ?? 0) ?> from yesterday
    </div>
  </div>

  <div class="card accent-amber">
    <div class="metric-title">Today's Revenue</div>
    <div class="metric-value">LKR <?= number_format((float)($stats['revenue_today'] ?? 0),0,'.',',') ?></div>
    <div class="metric-sub">+12% from yesterday</div>
  </div>

  <div class="card accent-green">
    <div class="metric-title">Drivers on Duty</div>
    <div class="metric-value"><?= (int)($stats['drivers_on_duty'] ?? 0) ?></div>
    <div class="metric-sub">on shift today</div>
  </div>

  <div class="card accent-indigo">
    <div class="metric-title">Active Routes</div>
    <div class="metric-value"><?= (int)($stats['active_routes'] ?? 0) ?></div>
    <div class="metric-sub">From operator</div>
  </div>

  <div class="card accent-cyan">
    <div class="metric-title">Conductors on Duty</div>
    <div class="metric-value"><?= (int)($stats['conductors_on_duty'] ?? 0) ?></div>
    <div class="metric-sub">on shift today</div>
  </div>

  <div class="card accent-rose">
    <div class="metric-title">Location Updates</div>
    <div class="metric-value"><?= (int)($stats['location_pct'] ?? 0) ?>%</div>
    <div class="metric-sub">Last hour</div>
  </div>
</div>

<style>
  @media (max-width: 1024px) {
    .grid { grid-template-columns: repeat(2, 1fr) !important; }
  }
  @media (max-width: 640px) {
    .grid { grid-template-columns: 1fr !important; gap: 12px !important; margin: 12px 0 !important; }
  }
</style>

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
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map('tk-private-live-map').setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18
    }).addTo(map);

    var markers = {};
    var focusBus = (new URLSearchParams(window.location.search).get('focus_bus') || '').trim();
    var focusedOnce = false;

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

    var busesRequestInFlight = false;

    function fetchBuses(){
      if (busesRequestInFlight || document.hidden) return;
      busesRequestInFlight = true;
      fetch('/api/buses/live')
        .then(function(r){ return r.json(); })
        .then(function(buses){
          if(!Array.isArray(buses)) return;

          var seen = {};
          buses.forEach(function(b){
            var id = markerKey(b);
            if (!id || typeof b.lat === 'undefined' || typeof b.lng === 'undefined') return;
            seen[id] = true;

            var popup = '<div style="min-width:140px">'
              +'<b style="font-size:.95rem">🚌 Bus '+(b.busId || b.busRegNo || 'N/A')+'</b><br>'
              +'Route: <strong>'+b.routeNo+'</strong><br>'
              +(b.speedKmh > 60
                ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">⚡ '+b.speedKmh+' km/h</span>'
                : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">✓ '+b.speedKmh+' km/h</span>')+'<br>'
              +'<small style="color:#6b7280">'+new Date(b.updatedAt).toLocaleTimeString()+'</small>'
              +'</div>';

            if(markers[id]){
              markers[id].setLatLng([b.lat, b.lng]).setIcon(busIcon(b.speedKmh)).bindPopup(popup);
            } else {
              markers[id] = L.marker([b.lat, b.lng], { icon: busIcon(b.speedKmh) }).bindPopup(popup).addTo(map);
            }
          });

          Object.keys(markers).forEach(function(id){
            if(!seen[id]){
              map.removeLayer(markers[id]);
              delete markers[id];
            }
          });

          if (focusBus && !focusedOnce) {
            var marker = markers[normId(focusBus)];
            if (marker) {
              map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 14), { duration: 0.8 });
              marker.openPopup();
              focusedOnce = true;
            }
          }
        })
        .catch(function(){})
        .finally(function(){ busesRequestInFlight = false; });
    }

    fetchBuses();
    setInterval(fetchBuses, 30000);
    document.addEventListener('visibilitychange', function(){
      if (!document.hidden) fetchBuses();
    });
  })();
  </script>
</div>

<style>
  .tk-map-card { margin-top: 16px; }
  .tk-live-map {
    width: 100%;
    height: 360px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
  }
</style>
