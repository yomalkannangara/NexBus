<?php
// views/bus_owner/dashboard.php
$user = $_SESSION['user'] ?? [];
$greeting = (int) date('H') < 12 ? 'Good Morning' : ((int) date('H') < 17 ? 'Good Afternoon' : 'Good Evening');
$uname = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$uname = $uname !== '' ? $uname : ($user['name'] ?? ($user['full_name'] ?? 'Owner'));

$totalBuses = (int) ($total_buses ?? 0);
$activeBuses = (int) ($active_buses ?? 0);
$totalDrivers = (int) ($total_drivers ?? 0);
$totalRev = (float) ($total_revenue ?? 0);
$maintBuses = (int) ($maintenance_buses ?? 0);
$activePct = $totalBuses > 0 ? round($activeBuses / $totalBuses * 100) : 0;
?>

<!-- ── Page Header ── -->
<div class="dash-hero">
  <!-- decorative background shapes -->
  <span class="dash-hero-bubble dash-hero-bubble--1" aria-hidden="true"></span>
  <span class="dash-hero-bubble dash-hero-bubble--2" aria-hidden="true"></span>
  <span class="dash-hero-bubble dash-hero-bubble--3" aria-hidden="true"></span>

  <div class="dash-hero-left">
    <div class="dash-greeting-chip">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <span id="dash-live-time">--:--</span>
      &nbsp;·&nbsp;
      <span><?= date('d M Y') ?></span>
    </div>
    <div class="dash-greeting"><?= $greeting ?>, <strong><?= htmlspecialchars($uname) ?></strong> 👋</div>
    <p class="dash-sub">Here's what's happening with your fleet today</p>
  </div>

  <div class="dash-hero-right" aria-hidden="true">
    <div class="dash-hero-bus-icon">
      <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="14" width="56" height="34" rx="6" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.35)" stroke-width="2"/>
        <rect x="8" y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
        <rect x="25" y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
        <rect x="42" y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
        <path d="M4 33h56" stroke="rgba(255,255,255,.3)" stroke-width="1.5"/>
        <circle cx="16" cy="52" r="5" fill="rgba(255,255,255,.25)" stroke="rgba(255,255,255,.5)" stroke-width="2"/>
        <circle cx="48" cy="52" r="5" fill="rgba(255,255,255,.25)" stroke="rgba(255,255,255,.5)" stroke-width="2"/>
        <path d="M4 14V10a4 4 0 014-4h48a4 4 0 014 4v4" stroke="rgba(255,255,255,.3)" stroke-width="2"/>
      </svg>
    </div>
  </div>
</div>

<style>
/* ── Dashboard Hero Banner ─────────────────────────────────────── */
.dash-hero {
  position: relative;
  background: linear-gradient(135deg, #80143c 0%, #a8274e 60%, #c0395f 100%);
  border-bottom: 4px solid #f3b944;
  border-radius: 16px;
  padding: 28px 36px;
  margin-bottom: 26px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(128,20,60,.22);
}

/* Decorative floating bubbles */
.dash-hero-bubble {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,.07);
  pointer-events: none;
}
.dash-hero-bubble--1 { width: 160px; height: 160px; top: -60px; right: 160px; }
.dash-hero-bubble--2 { width: 100px; height: 100px; bottom: -40px; right: 100px; background: rgba(243,185,68,.12); }
.dash-hero-bubble--3 { width: 60px;  height: 60px;  top: 10px;   right: 280px; background: rgba(255,255,255,.05); }

/* Live time chip */
.dash-greeting-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 999px;
  padding: 4px 12px;
  font-size: .78rem;
  font-weight: 600;
  color: rgba(255,255,255,.9);
  letter-spacing: .3px;
  margin-bottom: 10px;
  backdrop-filter: blur(4px);
}

/* Greeting text */
.dash-greeting {
  font-size: 1.55rem;
  font-weight: 400;
  color: #fff;
  margin-bottom: 6px;
  line-height: 1.3;
  opacity: 0;
  animation: dashHeroIn .5s .1s cubic-bezier(.22,.68,0,1.2) forwards;
}
.dash-greeting strong { font-weight: 800; }

.dash-sub {
  font-size: .92rem;
  color: rgba(255,255,255,.72);
  margin: 0;
  opacity: 0;
  animation: dashHeroIn .5s .22s cubic-bezier(.22,.68,0,1.2) forwards;
}

@keyframes dashHeroIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}

/* Bus illustration on the right */
.dash-hero-right { flex-shrink: 0; }
.dash-hero-bus-icon {
  width: 110px;
  height: 80px;
  opacity: .55;
  animation: dashBusFloat 4s ease-in-out infinite;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,.18));
}
.dash-hero-bus-icon svg { width: 100%; height: 100%; }

@keyframes dashBusFloat {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-7px); }
}

@media (max-width: 640px) {
  .dash-hero { padding: 22px 20px; }
  .dash-hero-right { display: none; }
  .dash-greeting { font-size: 1.2rem; }
}
</style>

<script>
(function () {
  function updateTime() {
    var el = document.getElementById('dash-live-time');
    if (!el) return;
    var now = new Date();
    var h = now.getHours().toString().padStart(2,'0');
    var m = now.getMinutes().toString().padStart(2,'0');
    el.textContent = h + ':' + m;
  }
  updateTime();
  setInterval(updateTime, 10000);
})();
</script>

<!-- ── KPI Cards ── -->
<div class="stats-grid">

  <!-- Total Fleet -->
  <div class="stat-card kpi-fleet">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="7" width="22" height="13" rx="2" />
        <path d="M1 13h22M5 20v2M19 20v2M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Fleet</div>
      <div class="stat-value"><?= $totalBuses ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--fleet" style="width:100%"></div>
      </div>
      <div class="stat-change"><?= $activeBuses ?> active · <?= $maintBuses ?> in maintenance</div>
    </div>
  </div>

  <!-- Active Buses -->
  <div class="stat-card kpi-active">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
        <polyline points="22 4 12 14.01 9 11.01" />
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Active Buses</div>
      <div class="stat-value"><?= $activeBuses ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--active" style="width:<?= $activePct ?>%"></div>
      </div>
      <div class="stat-change"><?= $activePct ?>% fleet utilisation</div>
    </div>
  </div>

  <!-- Total Drivers -->
  <div class="stat-card kpi-drivers">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Staff</div>
      <div class="stat-value"><?= $totalDrivers ?></div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--drivers" style="width:100%"></div>
      </div>
      <div class="stat-change">Registered drivers &amp; conductors</div>
    </div>
  </div>

  <!-- Revenue -->
  <div class="stat-card kpi-revenue">
    <div class="kpi-icon-wrap">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23" />
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value kpi-revenue-val">
        <span class="kpi-currency">LKR</span><?= number_format($totalRev) ?>
      </div>
      <div class="kpi-bar-wrap">
        <div class="kpi-bar kpi-bar--revenue" style="width:100%"></div>
      </div>
      <div class="stat-change">All-time earnings</div>
    </div>
  </div>

</div>

<!-- ── Quick Actions ── -->
<div class="card dash-qa-card">
  <h3 class="card-title">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
    </svg>
    Quick Actions
  </h3>
  <div class="quick-actions-grid">
    <a href="/B/fleet" class="quick-action-btn qa-fleet">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <rect x="1" y="7" width="22" height="13" rx="2" />
          <path d="M1 13h22M5 20v2M19 20v2" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Manage Fleet</span>
        <span class="qa-desc">Add or edit buses</span>
      </div>
    </a>
    <a href="/B/drivers" class="quick-action-btn qa-staff">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Staff</span>
        <span class="qa-desc">Manage drivers &amp; conductors</span>
      </div>
    </a>
    <a href="/B/attendance" class="quick-action-btn qa-attendance">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <polyline points="16 11 18 13 22 9" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Attendance</span>
        <span class="qa-desc">Mark today's attendance</span>
      </div>
    </a>
    <a href="/B/earnings" class="quick-action-btn qa-earnings">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="1" x2="12" y2="23" />
          <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Earnings</span>
        <span class="qa-desc">Record &amp; view income</span>
      </div>
    </a>
    <a href="/B/performance" class="quick-action-btn qa-perf">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" x2="18" y1="20" y2="10" />
          <line x1="12" x2="12" y1="20" y2="4" />
          <line x1="6" x2="6" y1="20" y2="14" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Performance</span>
        <span class="qa-desc">Analytics &amp; reports</span>
      </div>
    </a>
    <a href="/B/feedback" class="quick-action-btn qa-feedback">
      <div class="qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a4 4 0 0 1-4 4H7l-4 3V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
        </svg>
      </div>
      <div class="qa-text">
        <span class="qa-label">Feedback</span>
        <span class="qa-desc">Passenger complaints</span>
      </div>
    </a>
  </div>
</div>

<!-- ── Live Fleet Map ── -->
<div class="card dash-map-card" style="margin-bottom:1.5rem">
  <h3 class="card-title">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
      stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
      <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" />
      <circle cx="12" cy="10" r="3" />
    </svg>
    Live Fleet Tracker
    <span id="bo-live-count"
      style="margin-left:auto;font-size:.78rem;font-weight:500;color:#6b7280;background:#f3f4f6;padding:2px 10px;border-radius:20px">Loading…</span>
  </h3>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <div id="bo-live-map" style="width:100%;height:360px;border-radius:10px;overflow:hidden;"></div>
  <p style="font-size:.75rem;color:#9ca3af;margin-top:.5rem">Shows only your private-company buses · auto-refreshes
    every 15 s</p>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
    (function () {
      var OWNER_ID = <?= json_encode((int) (($_SESSION['user']['private_operator_id'] ?? 0))) ?>;
      var q = new URLSearchParams(window.location.search || '');
      var focusBus = String(q.get('bus') || q.get('focus_bus') || '').trim();
      var focusLat = parseFloat(q.get('lat') || '');
      var focusLng = parseFloat(q.get('lng') || '');
      var focusDone = false;

      var map = L.map('bo-live-map').setView([6.927, 79.861], 10);
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
          + '<circle cx="22" cy="20" r="19" fill="' + pulse + '" opacity=".55"/>'
          + '<path d="M22 2C13.16 2 6 9.16 6 18c0 10.5 16 32 16 32S38 28.5 38 18C38 9.16 30.84 2 22 2z" fill="' + fill + '" stroke="' + ring + '" stroke-width="2.5"/>'
          + '<circle cx="22" cy="18" r="9" fill="#fff"/>'
          + '<rect x="16" y="14" width="12" height="8" rx="1.5" fill="' + fill + '"/>'
          + '<rect x="17" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
          + '<rect x="23" y="15" width="4" height="3" rx=".5" fill="#fff" opacity=".9"/>'
          + '<rect x="17" y="19" width="10" height="1.5" rx=".5" fill="#fff" opacity=".6"/>'
          + '</svg>';
        return L.divIcon({
          html: svg, className: '', iconSize: [44, 54], iconAnchor: [22, 52], popupAnchor: [0, -50]
        });
      }

      function fetchBuses() {
        fetch('/live/buses/pull')
          .then(function (r) { return r.json(); })
          .then(function (buses) {
            if (!Array.isArray(buses)) return;

            // Keep only this owner's private buses
            buses = buses.filter(function (b) {
              return String(b.operatorType || '').toLowerCase() === 'private'
                && (b.ownerId === OWNER_ID || b.owner_id === OWNER_ID);
            });

            var countEl = document.getElementById('bo-live-count');
            if (countEl) countEl.textContent = buses.length + ' bus' + (buses.length !== 1 ? 'es' : '') + ' live';

            var seen = {};
            buses.forEach(function (b) {
              seen[b.busId] = true;
              var speed = b.speedKmh ?? b.speed ?? 0;
              var busIdEnc = encodeURIComponent(b.busId);
              var popup = '<div style="min-width:160px;font-family:inherit">'
                + '<b style="font-size:.95rem">🚌 ' + b.busId + '</b><br>'
                + 'Route: <strong>' + (b.routeNo ?? b.route_no ?? '—') + '</strong><br>'
                + (speed > 60
                  ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">⚡ ' + speed + ' km/h</span>'
                  : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">✓ ' + speed + ' km/h</span>') + '<br>'
                + (b.owner ? '<small style="color:#6b7280">' + b.owner + '</small><br>' : '')
                + '<small style="color:#6b7280">' + new Date(b.updatedAt || b.snapshotAt || Date.now()).toLocaleTimeString() + '</small>'
                + '<div style="margin-top:8px;border-top:1px solid #e5e7eb;padding-top:7px">'
                + '<a href="/B/fleet?focus=' + busIdEnc + '" '
                + 'style="display:inline-flex;align-items:center;gap:4px;background:#80143c;color:#fff;'
                + 'padding:5px 12px;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;'
                + 'transition:background .2s" '
                + 'onmouseover="this.style.background=\'#5e0f2c\'" onmouseout="this.style.background=\'#80143c\'">'
                + '<svg width="11" height="11" fill="none" viewBox="0 0 24 24" style="flex-shrink:0">'
                + '<path stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'
                + '</svg>View Fleet Profile</a>'
                + '</div>'
                + '</div>';

              if (markers[b.busId]) {
                markers[b.busId].setLatLng([b.lat, b.lng])
                  .setIcon(busIcon(speed))
                  .bindPopup(popup);
              } else {
                markers[b.busId] = L.marker([b.lat, b.lng], { icon: busIcon(speed) })
                  .bindPopup(popup)
                  .addTo(map);
              }
            });
            // remove stale markers
            Object.keys(markers).forEach(function (id) {
              if (!seen[id]) { map.removeLayer(markers[id]); delete markers[id]; }
            });

            if (!focusDone) {
              if (focusBus && markers[focusBus]) {
                map.setView(markers[focusBus].getLatLng(), 14);
                markers[focusBus].openPopup();
                focusDone = true;
              } else if (Number.isFinite(focusLat) && Number.isFinite(focusLng)) {
                map.setView([focusLat, focusLng], 14);
                focusDone = true;
              }
            }
          })
          .catch(function () {
            var countEl = document.getElementById('bo-live-count');
            if (countEl) countEl.textContent = 'Unavailable';
          });
      }

      fetchBuses();
      setInterval(fetchBuses, 15000);
    })();
  </script>
</div>

<!-- ── Bottom row ── -->
<div class="two-column-layout">

  <!-- Recent Buses -->
  <div class="card">
    <h3 class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="7" width="22" height="13" rx="2" />
        <path d="M1 13h22" />
      </svg>
      Recent Buses Added
    </h3>
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Reg. Number</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recent_buses)): ?>
            <?php foreach ($recent_buses as $b):
              $status = $b['status'] ?? 'Active';
              $cls = match ($status) {
                'Maintenance' => 'status-maintenance',
                'Inactive' => 'status-out',
                default => 'status-active'
              };
              $reg = $b['reg_no'] ?? ($b['bus_number'] ?? '—');
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($reg) ?></strong></td>
                <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($status) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="2" class="dash-empty-row">No buses registered yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <a href="/B/fleet" class="dash-view-all">View all buses →</a>
  </div>

  <!-- Alerts & Status -->
  <div class="card">
    <h3 class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>
      Fleet Status
    </h3>
    <div class="alerts-list">

      <?php if ($maintBuses > 0): ?>
        <div class="alert-item alert-warning">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" />
          </svg>
          <div class="alert-content">
            <div class="alert-title"><?= $maintBuses ?> bus<?= $maintBuses > 1 ? 'es' : '' ?> under maintenance</div>
            <div class="alert-time">Review in Fleet management</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="alert-item alert-success">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2" />
          <path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        <div class="alert-content">
          <div class="alert-title"><?= $activeBuses ?> bus<?= $activeBuses !== 1 ? 'es' : '' ?> operational</div>
          <div class="alert-time"><?= $activePct ?>% of fleet is active</div>
        </div>
      </div>

      <?php if ($totalDrivers === 0): ?>
        <div class="alert-item alert-warning">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" />
          </svg>
          <div class="alert-content">
            <div class="alert-title">No drivers registered</div>
            <div class="alert-time">Add drivers in the Staff section</div>
          </div>
        </div>
      <?php else: ?>
        <div class="alert-item alert-info">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2" />
            <path d="M10 8v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
          <div class="alert-content">
            <div class="alert-title"><?= $totalDrivers ?> staff member<?= $totalDrivers !== 1 ? 's' : '' ?> registered
            </div>
            <div class="alert-time">Manage in Staff section</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="alert-item alert-neutral">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" />
          <path d="M10 6v4l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <div class="alert-content">
          <div class="alert-title">Today — <?= date('d M Y') ?></div>
          <div class="alert-time">Mark attendance for today's shifts</div>
        </div>
      </div>

    </div>
    <a href="/B/attendance" class="dash-view-all">Mark today's attendance →</a>
  </div>