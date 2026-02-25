<section class="filters">
  <div class="pill">
    <select name="route_id" form="filterForm" onchange="document.getElementById('filterForm').submit()">
      <option value="">All Routes</option>
      <?php foreach($routes as $r): ?>
        <option value="<?= (int)$r['route_id'] ?>" <?= (!empty($route_id) && (int)$route_id === (int)$r['route_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($r['route_no']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="pill">
    <select name="operator_type" form="filterForm" onchange="document.getElementById('filterForm').submit()">
      <option value="">All Types</option>
      <option value="SLTB"    <?= (!empty($operator_type) && $operator_type==='SLTB') ? 'selected' : '' ?>>SLTB</option>
      <option value="Private" <?= (!empty($operator_type) && $operator_type==='Private') ? 'selected' : '' ?>>Private</option>
    </select>
  </div>

  <form id="filterForm" method="get" style="display:none">

  </form>
</section>


<section class="map-card">
  <!-- Leaflet live map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <div id="live-bus-map" style="width:100%;height:320px;border-radius:12px;overflow:hidden;"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  (function(){
    var ACTIVE_OPERATOR = <?= json_encode((string)($operator_type ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var ACTIVE_ROUTE_NO = <?php
      $activeRouteNo = '';
      if (!empty($route_id) && !empty($routes) && is_array($routes)) {
        foreach ($routes as $rt) {
          if ((int)($rt['route_id'] ?? 0) === (int)$route_id) {
            $activeRouteNo = (string)($rt['route_no'] ?? '');
            break;
          }
        }
      }
      echo json_encode($activeRouteNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>;

    function normalizeRoute(v){
      var raw = String(v == null ? '' : v).trim();
      if(!raw) return '';
      var digits = raw.replace(/\D+/g, '');
      if(digits) return String(parseInt(digits, 10));
      return raw.toLowerCase();
    }

    var map = L.map('live-bus-map').setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom:18
    }).addTo(map);

    var markers = {};

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
      return L.divIcon({
        html: svg, className:'', iconSize:[44,54], iconAnchor:[22,52], popupAnchor:[0,-50]
      });
    }

    function fetchBuses(){
      fetch('/api/buses/live')
        .then(function(r){ return r.json(); })
        .then(function(buses){
          if(!Array.isArray(buses)) return;
          if (ACTIVE_ROUTE_NO) {
            var targetRoute = normalizeRoute(ACTIVE_ROUTE_NO);
            buses = buses.filter(function(b){
              return normalizeRoute(b.routeNo ?? b.route_no ?? b.route ?? b.routeNumber ?? '') === targetRoute;
            });
          }
          if (ACTIVE_OPERATOR) {
            var targetOp = String(ACTIVE_OPERATOR).toLowerCase();
            buses = buses.filter(function(b){
              return String(b.operatorType || '').toLowerCase() === targetOp;
            });
          }

          var seen = {};
          buses.forEach(function(b){
            seen[b.busId] = true;
            var popup = '<div style="min-width:140px">'
              +'<b style="font-size:.95rem">🚌 Bus '+b.busId+'</b><br>'
              +'Route: <strong>'+b.routeNo+'</strong><br>'
              +(b.speedKmh > 60
                ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">⚡ '+b.speedKmh+' km/h</span>'
                : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">✓ '+b.speedKmh+' km/h</span>')+'<br>'
              +'<small style="color:#6b7280">'+new Date(b.updatedAt).toLocaleTimeString()+'</small>'
              +'</div>';
            if(markers[b.busId]){
              markers[b.busId].setLatLng([b.lat, b.lng])
                .setIcon(busIcon(b.speedKmh))
                .bindPopup(popup);
            } else {
              markers[b.busId] = L.marker([b.lat, b.lng], {icon: busIcon(b.speedKmh)})
                .bindPopup(popup)
                .addTo(map);
            }
          });
          // remove stale markers
          Object.keys(markers).forEach(function(id){
            if(!seen[id]){ map.removeLayer(markers[id]); delete markers[id]; }
          });
        })
        .catch(function(){});
    }

    fetchBuses();
    setInterval(fetchBuses, 15000);
  })();
  </script>
</section>

<div class="section-title">
  <h3>Next Bus</h3>
</div>

<div class="cards">
  <?php foreach($nextBuses as $it): ?>
    <div class="bus-card">
      <div class="bus-badge"><?= htmlspecialchars($it['route_no'] ?? '') ?></div>

      <div class="bus-info">
        <div class="bus-title">
          <?= htmlspecialchars($it['name'] ??  '') ?> 
          <br>(<?= htmlspecialchars($it['bus_reg_no'] ??  '') ?>)
        </div>
        <div class="bus-sub">
          departure <?= (int)htmlspecialchars($it['minutes_from_departure'] ) ?> min ago
          <span class="chip"><?= htmlspecialchars($it['operator_type']) ?></span>
        </div>
      </div>

      <div class="bus-eta">
        <div class="min">ETA : <?= (int)($it['eta_min'] ?? 3) ?> min</div>
        <div><span class="bus-dot"></span></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
