<?php
// Expected from controller: $todayLabel, $stats (3), $dailyStats (3), $activeCount, $delayed, $issues
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Tiny inline SVG helper (no external class) */
function _svg(string $name, int $size = 18, string $stroke = 'currentColor'): string {
  $map = [
    'bus'   => '<rect x="3" y="11" width="18" height="7" rx="2"/><path d="M7 11V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v4"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="16.5" cy="18.5" r="1.5"/>',
    'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'routes'=> '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',   // simple clock-like marker
    'check' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'alert' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'pin'   => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
  ];
  $inner = $map[$name] ?? '<circle cx="12" cy="12" r="9"/>';
  $s = (int)$size;
  return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="'.htmlspecialchars($stroke,ENT_QUOTES).'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$inner.'</svg>';
}
?>
<section class="section dashboard">
  <!-- Header strip (light, compact) -->
<div class="title-card">
  <h1 class="title-heading">Bus Management Dashboard</h1>
  <p class="title-sub">National Transport Commission – Sri Lanka</p>
</div>


  <!-- Top 3 cards -->
  <div class="grid grid-3 gap-16 mt-12">
    <?php foreach (($stats ?? []) as $s): ?>
      <?php
        $title = h($s['title'] ?? '');
        $value = h($s['value'] ?? '');
        $trend = strtolower((string)($s['trend'] ?? '')); // up|down
        $change= h($s['change'] ?? '');
        $icon  = (string)($s['icon'] ?? 'bus');
        $ico   = _svg($icon, 18, '#b25b66');              // subtle maroon
        $arrow = $trend === 'down' ? '▼' : '▲';
        $tcls  = $trend === 'down' ? 'text-red' : 'text-green';
      ?>
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-title"><?= $title ?></div>
          <div class="corner-ico"><?= $ico ?></div>
        </div>
        <div class="stat-main">
          <div class="stat-value"><?= $value ?></div>
          <div class="stat-trend">
            <span class="<?= $tcls ?>"><?= $arrow ?></span>
            <span class="muted"><?= $change ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Daily 3 cards -->
  <div class="grid grid-3 gap-16 mt-16">
    <?php foreach (($dailyStats ?? []) as $s): ?>
      <?php
        $title = h($s['title'] ?? '');
        $value = h($s['value'] ?? '');
        $trend = strtolower((string)($s['trend'] ?? ''));
        $change= h($s['change'] ?? '');
        $icon  = (string)($s['icon'] ?? 'alert');
        $ico   = _svg($icon, 18, '#b25b66');
        $arrow = $trend === 'down' ? '▼' : '▲';
        $tcls  = $trend === 'down' ? 'text-red' : 'text-green';
      ?>
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-title"><?= $title ?></div>
          <div class="corner-ico"><?= $ico ?></div>
        </div>
        <div class="stat-main">
          <div class="stat-value"><?= $value ?></div>
          <div class="stat-trend">
            <span class="<?= $tcls ?>"><?= $arrow ?></span>
            <span class="muted"><?= $change ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Quick Actions Section -->
  <div class="quick-actions-section mt-16">
    <div class="quick-actions-header">
      <h3 class="qa-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        Quick Actions
      </h3>
    </div>
    <div class="quick-actions-grid">
      <a href="/M/fleet" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="7" rx="2"/><path d="M7 11V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v4"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="16.5" cy="18.5" r="1.5"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Manage Fleet</div>
          <div class="qa-card-desc">Add or edit buses</div>
        </div>
      </a>

      <a href="/M/drivers" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Staff</div>
          <div class="qa-card-desc">Manage drivers &amp; conductors</div>
        </div>
      </a>

      <a href="/M/health" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-4l-3-3-3 3H6a2 2 0 0 0-2 2v7a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V9a2 2 0 0 0-2-2z"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Health</div>
          <div class="qa-card-desc">Maintenance &amp; issues</div>
        </div>
      </a>

      <a href="/M/earnings" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Earnings</div>
          <div class="qa-card-desc">Record &amp; view income</div>
        </div>
      </a>

      <a href="/M/performance" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Performance</div>
          <div class="qa-card-desc">Analytics &amp; reports</div>
        </div>
      </a>

      <a href="/M/feedback" class="qa-card">
        <div class="qa-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="qa-card-content">
          <div class="qa-card-label">Feedback</div>
          <div class="qa-card-desc">Passenger complaints</div>
        </div>
      </a>
    </div>
  </div>

  <!-- Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

  <section class="filters" style="margin-top:1.5rem"><h2>Bus Location Filters</h2><div class="filter-grid"><div><label>Route</label>
  <select id="map-filter-route" onchange="applyMapFilters()"><option value="">All Routes</option>
  <?php foreach(($routes ?? []) as $r) echo '<option value="'.htmlspecialchars($r['route_no']).'">' .htmlspecialchars($r['route_no']).'</option>'; ?>
  </select></div><div><label>Bus Number</label><select id="map-filter-bus" onchange="applyMapFilters()"><option value="">All Buses</option></select></div>
  <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-top:.5rem;padding-top:.5rem;border-top:1px solid #e5e7eb">
    <span style="font-size:.8rem;color:#6b7280">Live fleet:</span>
    <span class="lf-badge lf-badge--green" id="db-live-count">– buses</span>
    <span class="lf-badge lf-badge--red" id="db-speed-viols">– speeding</span>
    <span style="font-size:.8rem;color:#6b7280;margin-left:auto" id="db-map-updated"></span>
  </div>
  </div></section>

  <section style="margin:0 0 1.5rem;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.12)">
    <div id="depot-bus-map" style="width:100%;height:480px;"></div>
  </section>

  <style>
  .lf-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
  .lf-badge--green{background:#dcfce7;color:#15803d}
  .lf-badge--red{background:#fee2e2;color:#b91c1c}
  .bus-popup b{font-size:.95rem}
  .bus-popup small{color:#6b7280}
  .speed-tag{display:inline-block;padding:1px 7px;border-radius:8px;font-size:.75rem;font-weight:600;margin-top:2px}
  .speed-ok{background:#dcfce7;color:#15803d}
  .speed-over{background:#fee2e2;color:#b91c1c}
  </style>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  (function(){
    var DEPOT_ID = <?= (int)($depotId ?? 0) ?>;

    var map = L.map('depot-bus-map', {zoomControl:true}).setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom:19
    }).addTo(map);

    var markers   = {};
    var allBuses  = [];
    var filterRoute = '';
    var filterBus   = '';

    function makePinIcon(speed){
      var over = speed > 60;
      var fill = over ? '#dc2626' : '#1d6f42';
      var ring = over ? '#fca5a5' : '#86efac';
      var pulse= over ? '#fee2e2' : '#dcfce7';
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
      return L.divIcon({html:svg,className:'',iconSize:[44,54],iconAnchor:[22,52],popupAnchor:[0,-50]});
    }

    function makePopup(b){
      var over = b.speedKmh > 60;
      var tag  = over
        ? '<span class="speed-tag speed-over">⚡ '+b.speedKmh+' km/h — SPEEDING</span>'
        : '<span class="speed-tag speed-ok">✓ '+b.speedKmh+' km/h — Normal</span>';
      var upd  = new Date(b.updatedAt||b.snapshotAt).toLocaleTimeString();
      return '<div class="bus-popup">'
        +'<b>🚌 Bus '+b.busId+'</b><br>'
        +'<span style="font-size:.82rem">Route <strong>'+b.routeNo+'</strong></span><br>'
        +tag+'<br>'
        +'<small>Heading '+Math.round(b.heading||0)+'° &nbsp;·&nbsp; Updated '+upd+'</small>'
        +'</div>';
    }

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

    function fetchAndRender(){
      fetch('/live/buses/pull')
        .then(function(r){ return r.json(); })
        .then(function(buses){
          if(!Array.isArray(buses)) return;
          /* Filter to only this depot's buses */
          if(DEPOT_ID > 0){
            buses = buses.filter(function(b){
              return (b.depotId == DEPOT_ID);
            });
          }
          allBuses = buses;
          updateBusDropdown(buses);

          var seen  = {};
          var viols = 0;
          buses.forEach(function(b){
            seen[b.busId] = true;
            if(b.speedKmh > 60) viols++;
            var popup = makePopup(b);
            var icon  = makePinIcon(b.speedKmh);
            var show  = (!filterRoute || b.routeNo === filterRoute)
                     && (!filterBus   || b.busId   === filterBus);
            if(markers[b.busId]){
              markers[b.busId].setLatLng([b.lat,b.lng]).setIcon(icon).bindPopup(popup);
              if(show){ if(!map.hasLayer(markers[b.busId])) map.addLayer(markers[b.busId]); }
              else    { if( map.hasLayer(markers[b.busId])) map.removeLayer(markers[b.busId]); }
            } else {
              var mk = L.marker([b.lat,b.lng],{icon:icon}).bindPopup(popup);
              if(show) mk.addTo(map);
              markers[b.busId] = mk;
            }
          });
          Object.keys(markers).forEach(function(id){
            if(!seen[id]){ map.removeLayer(markers[id]); delete markers[id]; }
          });
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
</section>
