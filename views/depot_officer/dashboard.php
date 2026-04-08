<?php
$me = $me ?? [];
$depot = $depot ?? ['name' => 'Unknown Depot'];
$counts = $counts ?? ['delayed' => 0, 'breaks' => 0];
$todayDelayed = $todayDelayed ?? [];
?>

<div class="container do-dashboard">
  <section class="title-banner do-hero">
    <h1>Depot Dashboard — <?= htmlspecialchars($depot['name'] ?? 'Unknown Depot') ?></h1>
    <p>Live operations snapshot for today: delay monitoring, incidents, and route status visibility.</p>
  </section>

  <div class="cards do-kpi-grid">
    <article class="card accent-amber do-kpi delayed-card" tabindex="0" aria-label="Delayed buses overview">
      <div class="do-kpi-head">
        <h3 class="metric-title">Delayed Today</h3>
        <span class="do-kpi-chip">Live</span>
      </div>
      <div class="metric-value"><?= (int)($counts['delayed'] ?? 0) ?></div>
      <p class="metric-sub">buses running late</p>

      <div class="delayed-dropdown" aria-hidden="true">
        <?php if (!empty($todayDelayed)): ?>
          <ul class="delayed-list">
            <?php foreach ($todayDelayed as $r): ?>
              <li>
                <a class="delayed-link" href="?module=depot_officer&page=bus_profile&bus_reg_no=<?= urlencode($r['bus_reg_no'] ?? '') ?>">
                  <span class="delayed-main">
                    <strong><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></strong>
                    <span>• Route <?= htmlspecialchars($r['route_no'] ?? '-') ?></span>
                  </span>
                  <small class="delayed-sub">+<?= htmlspecialchars($r['avg_delay_min'] ?? 0) ?> min delay</small>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="delayed-empty">No delayed buses</div>
        <?php endif; ?>
      </div>
    </article>

    <article class="card accent-rose do-kpi">
      <div class="do-kpi-head">
        <h3 class="metric-title">Breakdowns Today</h3>
        <span class="do-kpi-chip">Operations</span>
      </div>
      <div class="metric-value"><?= (int)($counts['breaks'] ?? 0) ?></div>
      <p class="metric-sub">maintenance events</p>
    </article>
  </div>

  <section class="card do-map-card">
    <div class="live-header">
      <div class="live-dot"></div>
      <span>Live Bus Locations</span>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <div id="do-live-map" class="do-live-map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  </section>

  <section class="card do-table-card">
    <div class="do-section-head">
      <h2>Latest Delays</h2>
      <span class="do-table-count"><?= count($todayDelayed) ?> records</span>
    </div>

    <?php if (!empty($todayDelayed)): ?>
      <table class="table do-delay-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Bus</th>
            <th>Route</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($todayDelayed as $r): ?>
            <tr>
              <td data-label="Time"><?= htmlspecialchars($r['snapshot_at'] ?? '') ?></td>
              <td data-label="Bus"><strong><?= htmlspecialchars($r['bus_reg_no'] ?? '') ?></strong></td>
              <td data-label="Route"><?= htmlspecialchars($r['route_no'] ?? '-') ?></td>
              <td data-label="Status"><span class="badge do-status"><?= htmlspecialchars($r['operational_status'] ?? 'Unknown') ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="do-empty-state">No delays reported today. Great job!</div>
    <?php endif; ?>
  </section>
</div>

<style>
.do-dashboard {
  padding: 4px 0 16px;
}

.do-hero {
  margin-bottom: 14px;
}

.do-kpi-grid {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  margin-bottom: 16px;
}

.do-kpi {
  position: relative;
  overflow: hidden;
}

.do-kpi-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 4px;
}

.do-kpi-chip {
  font-size: 11px;
  font-weight: 700;
  color: var(--maroon);
  background: color-mix(in srgb, var(--gold) 28%, #fff);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 2px 8px;
}

.do-kpi .metric-value {
  line-height: 1;
  margin-top: 6px;
}

.do-kpi .metric-sub {
  margin-top: 8px;
}

.delayed-card {
  cursor: pointer;
}

.delayed-dropdown {
  position: static;
  margin-top: 10px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: var(--shadow);
  max-height: 280px;
  overflow-y: auto;
  display: none;
}

.delayed-card:hover .delayed-dropdown,
.delayed-card:focus-within .delayed-dropdown,
.delayed-card.open .delayed-dropdown {
  display: block;
}

.delayed-list {
  list-style: none;
  margin: 0;
  padding: 8px;
}

.delayed-list li + li {
  margin-top: 4px;
}

.delayed-link {
  display: block;
  text-decoration: none;
  color: var(--text);
  padding: 10px 12px;
  border-radius: 10px;
  transition: background .18s ease;
}

.delayed-link:hover {
  background: color-mix(in srgb, var(--gold) 14%, #fff);
}

.delayed-main {
  display: flex;
  align-items: center;
  gap: 6px;
}

.delayed-sub {
  display: block;
  margin-top: 4px;
  color: var(--muted);
}

.delayed-empty {
  padding: 12px;
  text-align: center;
  color: var(--muted);
}

.do-table-card {
  padding: 16px;
}

.do-map-card {
  margin-bottom: 16px;
}

.do-live-map {
  width: 100%;
  height: 360px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border);
}

.do-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.do-section-head h2 {
  margin: 0;
  color: var(--maroon);
  font-size: 18px;
}

.do-table-count {
  font-size: 12px;
  color: var(--muted);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 4px 10px;
  background: var(--card);
}

.do-status {
  background: color-mix(in srgb, var(--gold) 28%, #fff);
  color: var(--maroon);
  font-weight: 700;
  border: 1px solid var(--border);
}

.do-empty-state {
  text-align: center;
  color: var(--muted);
  padding: 26px 12px;
  border: 1px dashed var(--border);
  border-radius: 10px;
  background: color-mix(in srgb, var(--gold) 8%, #fff);
}

@media (max-width: 768px) {
  .do-section-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .do-kpi-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const delayedCard = document.querySelector('.delayed-card');
  if (delayedCard) {
    delayedCard.addEventListener('click', function (event) {
      if (event.target.closest('.delayed-link')) return;
      delayedCard.classList.toggle('open');
    });

    document.addEventListener('click', function (event) {
      if (!delayedCard.contains(event.target)) {
        delayedCard.classList.remove('open');
      }
    });
  }

  if (typeof L !== 'undefined' && document.getElementById('do-live-map')) {
    var map = L.map('do-live-map').setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18
    }).addTo(map);

    var markers = {};

    function busIcon(speed) {
      var over = speed > 60;
      var fill = over ? '#dc2626' : '#1d6f42';
      var ring = over ? '#fca5a5' : '#86efac';
      var pulse = over ? '#fee2e2' : '#dcfce7';
      var svg =
        '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="54" viewBox="0 0 44 54">'
        + '<ellipse cx="22" cy="52" rx="9" ry="3" fill="rgba(0,0,0,.22)"/>'
        + '<circle cx="22" cy="20" r="19" fill="'+pulse+'" opacity=".55"/>'
        + '<path d="M22 2C13.16 2 6 9.16 6 18c0 10.5 16 32 16 32S38 28.5 38 18C38 9.16 30.84 2 22 2z" fill="'+fill+'" stroke="'+ring+'" stroke-width="2.5"/>'
        + '<circle cx="22" cy="18" r="9" fill="#fff"/>'
        + '<rect x="16" y="14" width="12" height="8" rx="1.5" fill="'+fill+'"/>'
        + '<rect x="17" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
        + '<rect x="23" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
        + '<rect x="17" y="19" width="10" height="1.5" rx=".5" fill="#fff" opacity=".6"/>'
        + '</svg>';

      return L.divIcon({ html: svg, className: '', iconSize: [44, 54], iconAnchor: [22, 52], popupAnchor: [0, -50] });
    }

    function fetchBuses() {
      fetch('/api/buses/live')
        .then(function(r) { return r.json(); })
        .then(function(buses) {
          if (!Array.isArray(buses)) return;

          var seen = {};
          buses.forEach(function(b) {
            var id = b.busId;
            seen[id] = true;

            var popup = '<div style="min-width:140px">'
              + '<b style="font-size:.95rem">🚌 Bus ' + b.busId + '</b><br>'
              + 'Route: <strong>' + b.routeNo + '</strong><br>'
              + (b.speedKmh > 60
                ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">⚡ ' + b.speedKmh + ' km/h</span>'
                : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">✓ ' + b.speedKmh + ' km/h</span>') + '<br>'
              + '<small style="color:#6b7280">' + new Date(b.updatedAt).toLocaleTimeString() + '</small>'
              + '</div>';

            if (markers[id]) {
              markers[id].setLatLng([b.lat, b.lng]).setIcon(busIcon(b.speedKmh)).bindPopup(popup);
            } else {
              markers[id] = L.marker([b.lat, b.lng], { icon: busIcon(b.speedKmh) }).bindPopup(popup).addTo(map);
            }
          });

          Object.keys(markers).forEach(function(id) {
            if (!seen[id]) {
              map.removeLayer(markers[id]);
              delete markers[id];
            }
          });
        })
        .catch(function(){});
    }

    fetchBuses();
    setInterval(fetchBuses, 15000);
  }
});
</script>