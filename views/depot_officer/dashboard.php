<?php
$me          = $me ?? [];
$depot       = $depot ?? ['name' => 'Unknown Depot'];
$counts      = $counts ?? ['delayed' => 0, 'breaks' => 0];
$todayDelayed = $todayDelayed ?? [];
$stats       = $stats ?? ['activeBuses'=>0,'maintBuses'=>0,'driversOnDuty'=>0,'conductorsOnDuty'=>0,'tripsCompleted'=>0,'delayedTrips'=>0];

$user     = $_SESSION['user'] ?? [];
$greeting = (int)date('H') < 12 ? 'Good Morning' : ((int)date('H') < 17 ? 'Good Afternoon' : 'Good Evening');
$uname    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$uname    = $uname !== '' ? $uname : ($user['name'] ?? ($user['full_name'] ?? 'Officer'));
?>

<style>
/* â”€â”€ Depot Officer Dashboard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

/* Hero banner */
.do-dash-hero {
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
.do-dash-bubble { position:absolute; border-radius:50%; background:rgba(255,255,255,.07); pointer-events:none; }
.do-dash-bubble--1 { width:160px; height:160px; top:-60px;  right:160px; }
.do-dash-bubble--2 { width:100px; height:100px; bottom:-40px; right:100px; background:rgba(243,185,68,.12); }
.do-dash-bubble--3 { width:60px;  height:60px;  top:10px;  right:280px; background:rgba(255,255,255,.05); }

.do-dash-chip {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
  border-radius: 999px; padding: 4px 12px;
  font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.9);
  letter-spacing: .3px; margin-bottom: 10px; backdrop-filter: blur(4px);
}
.do-dash-greeting {
  font-size: 1.55rem; font-weight: 400; color: #fff;
  margin-bottom: 6px; line-height: 1.3;
  opacity: 0; animation: doHeroIn .5s .1s cubic-bezier(.22,.68,0,1.2) forwards;
}
.do-dash-greeting strong { font-weight: 800; }
.do-dash-sub {
  font-size: .92rem; color: rgba(255,255,255,.72); margin: 0;
  opacity: 0; animation: doHeroIn .5s .22s cubic-bezier(.22,.68,0,1.2) forwards;
}
@keyframes doHeroIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
.do-dash-bus-icon {
  width: 110px; height: 80px; opacity: .55; flex-shrink: 0;
  animation: doBusFloat 4s ease-in-out infinite;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,.18));
}
.do-dash-bus-icon svg { width: 100%; height: 100%; }
@keyframes doBusFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-7px)} }
@media(max-width:640px) { .do-dash-hero{padding:22px 20px;} .do-dash-bus-icon{display:none;} .do-dash-greeting{font-size:1.2rem;} }

/* KPI stats grid â€” 3 Ã— 2 */
.do-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 28px;
}
@media(max-width:880px)  { .do-stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:520px)  { .do-stats-grid { grid-template-columns: 1fr; } }

.do-kpi-card {
  background: #fff;
  border-radius: 14px;
  padding: 20px 22px;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
.do-kpi-icon {
  flex-shrink: 0; width: 50px; height: 50px;
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
}
.do-kpi-body   { flex: 1; min-width: 0; }
.do-kpi-label  { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 6px; }
.do-kpi-value  { font-size: 32px; font-weight: 800; color: var(--text,#2b2b2b); line-height: 1; margin-bottom: 8px; }
.do-kpi-bar-wrap { height: 4px; background: #f3f4f6; border-radius: 4px; margin-bottom: 6px; overflow: hidden; }
.do-kpi-bar      { height: 4px; border-radius: 4px; min-width: 6%; transition: width .8s ease; }
.do-kpi-sub    { font-size: 11px; color: #6b7280; font-weight: 400; }
.do-kpi-live   { display:inline-block; font-size:10px; font-weight:700; background:#fee2e2; color:#b91c1c; border-radius:999px; padding:2px 7px; margin-left:6px; vertical-align:middle; }

/* Colour variants */
.do-kpi--green  .do-kpi-icon { background:#dcfce7; color:#16a34a; }
.do-kpi--green  .do-kpi-label { color:#16a34a; }
.do-kpi--green  .do-kpi-bar   { background:#16a34a; }

.do-kpi--red    .do-kpi-icon { background:#fee2e2; color:#dc2626; }
.do-kpi--red    .do-kpi-label { color:#dc2626; }
.do-kpi--red    .do-kpi-bar   { background:#dc2626; }

.do-kpi--blue   .do-kpi-icon { background:#eff6ff; color:#2563eb; }
.do-kpi--blue   .do-kpi-label { color:#2563eb; }
.do-kpi--blue   .do-kpi-bar   { background:#2563eb; }

.do-kpi--purple .do-kpi-icon { background:#faf5ff; color:#7c3aed; }
.do-kpi--purple .do-kpi-label { color:#7c3aed; }
.do-kpi--purple .do-kpi-bar   { background:#7c3aed; }

.do-kpi--teal   .do-kpi-icon { background:#f0fdfa; color:#0d9488; }
.do-kpi--teal   .do-kpi-label { color:#0d9488; }
.do-kpi--teal   .do-kpi-bar   { background:#0d9488; }

.do-kpi--orange .do-kpi-icon { background:#fff7ed; color:#d97706; }
.do-kpi--orange .do-kpi-label { color:#d97706; }
.do-kpi--orange .do-kpi-bar   { background:#d97706; }

/* Quick Actions */
.do-qa-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
@media(max-width:780px) { .do-qa-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:460px) { .do-qa-grid { grid-template-columns: 1fr; } }

.do-qa-btn {
  display: flex; flex-direction: row; align-items: center;
  gap: 14px; padding: 14px 16px;
  border: 1px solid #f1f1f1; border-radius: 14px;
  background: #fff; text-decoration: none;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
  transition: transform .15s, box-shadow .15s;
  color: var(--text,#2b2b2b);
}
.do-qa-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
.do-qa-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.do-qa-text  { display: flex; flex-direction: column; gap: 2px; }
.do-qa-label { font-size: 13px; font-weight: 700; color: var(--text,#2b2b2b); line-height: 1.2; }
.do-qa-desc  { font-size: 11px; color: #9ca3af; font-weight: 400; line-height: 1.3; }

.do-qa--maroon .do-qa-icon { background:#fce8ef; color:#80143c; }
.do-qa--gold   .do-qa-icon { background:#fef9e7; color:#92400e; }
.do-qa--green  .do-qa-icon { background:#f0fdf4; color:#16a34a; }
.do-qa--teal   .do-qa-icon { background:#f0fdfa; color:#0d9488; }
.do-qa--blue   .do-qa-icon { background:#eff6ff; color:#1d4ed8; }
.do-qa--purple .do-qa-icon { background:#faf5ff; color:#7c3aed; }

/* Card title */
.do-section-title {
  font-size: 16px; font-weight: 700; color: var(--maroon,#80143c);
  margin: 0 0 18px; display: flex; align-items: center; gap: 8px;
}

/* Live map */
.do-live-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; font-weight:700; font-size:15px; color:var(--maroon,#80143c); }
.do-live-dot { width:10px; height:10px; border-radius:50%; background:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,.25); animation:doPulse 1.8s ease-in-out infinite; }
@keyframes doPulse { 0%,100%{box-shadow:0 0 0 3px rgba(22,163,74,.25)}50%{box-shadow:0 0 0 7px rgba(22,163,74,.08)} }
.do-live-map { width:100%; height:360px; border-radius:12px; overflow:hidden; border:1px solid var(--border,#e4d39a); }
</style>

<script>
(function () {
  function tick() {
    var el = document.getElementById('do-live-time');
    if (!el) return;
    var n = new Date(), h = String(n.getHours()).padStart(2,'0'), m = String(n.getMinutes()).padStart(2,'0');
    el.textContent = h + ':' + m;
  }
  tick();
  setInterval(tick, 10000);
})();
</script>

<!-- â”€â”€ Hero Banner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="do-dash-hero">
  <span class="do-dash-bubble do-dash-bubble--1" aria-hidden="true"></span>
  <span class="do-dash-bubble do-dash-bubble--2" aria-hidden="true"></span>
  <span class="do-dash-bubble do-dash-bubble--3" aria-hidden="true"></span>

  <div>
    <div class="do-dash-chip">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <span id="do-live-time">--:--</span>&nbsp;Â·&nbsp;<span><?= date('d M Y') ?></span>
    </div>
    <div class="do-dash-greeting"><?= $greeting ?>, <strong><?= htmlspecialchars($uname) ?></strong> ðŸ‘‹</div>
    <p class="do-dash-sub"><?= htmlspecialchars($depot['name'] ?? 'My Depot') ?> â€” SLTB Depot Operations</p>
  </div>

  <div class="do-dash-bus-icon" aria-hidden="true">
    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="4" y="14" width="56" height="34" rx="6" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.35)" stroke-width="2"/>
      <rect x="8"  y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
      <rect x="25" y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
      <rect x="42" y="20" width="14" height="10" rx="2" fill="rgba(255,255,255,.22)"/>
      <path d="M4 33h56" stroke="rgba(255,255,255,.3)" stroke-width="1.5"/>
      <circle cx="16" cy="52" r="5" fill="rgba(255,255,255,.25)" stroke="rgba(255,255,255,.5)" stroke-width="2"/>
      <circle cx="48" cy="52" r="5" fill="rgba(255,255,255,.25)" stroke="rgba(255,255,255,.5)" stroke-width="2"/>
      <path d="M4 14V10a4 4 0 014-4h48a4 4 0 014 4v4" stroke="rgba(255,255,255,.3)" stroke-width="2"/>
    </svg>
  </div>
</div>

<!-- â”€â”€ Six KPI Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<?php
$activeBuses      = (int)($stats['activeBuses']      ?? 0);
$maintBuses       = (int)($stats['maintBuses']       ?? 0);
$driversOnDuty    = (int)($stats['driversOnDuty']    ?? 0);
$conductorsOnDuty = (int)($stats['conductorsOnDuty'] ?? 0);
$tripsCompleted   = (int)($stats['tripsCompleted']   ?? 0);
$delayedTrips     = (int)($stats['delayedTrips']     ?? $counts['delayed'] ?? 0);

// visual bar widths â€” relative to a sensible ceiling
$barW = fn(int $n, int $max=20): string => $n > 0 ? min(100, (int)round($n/$max*100)).'%' : '6%';
?>

<div class="do-stats-grid">

  <!-- Active Buses Today (green) -->
  <div class="do-kpi-card do-kpi--green">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="7" width="22" height="13" rx="2"/>
        <path d="M1 13h22M5 20v2M19 20v2M7 7V5a2 2 0 012-2h6a2 2 0 012 2v2"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Active Buses Today</div>
      <div class="do-kpi-value"><?= $activeBuses ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($activeBuses) ?>"></div></div>
      <div class="do-kpi-sub">Buses in service today</div>
    </div>
  </div>

  <!-- Buses on Maintenance (red) -->
  <div class="do-kpi-card do-kpi--red">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Buses on Maintenance</div>
      <div class="do-kpi-value"><?= $maintBuses ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($maintBuses, 10) ?>"></div></div>
      <div class="do-kpi-sub">Under repair / servicing</div>
    </div>
  </div>

  <!-- Drivers On Duty Today (blue) -->
  <div class="do-kpi-card do-kpi--blue">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Drivers On Duty</div>
      <div class="do-kpi-value"><?= $driversOnDuty ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($driversOnDuty) ?>"></div></div>
      <div class="do-kpi-sub">Assigned today</div>
    </div>
  </div>

  <!-- Conductors On Duty Today (purple) -->
  <div class="do-kpi-card do-kpi--purple">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Conductors On Duty</div>
      <div class="do-kpi-value"><?= $conductorsOnDuty ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($conductorsOnDuty) ?>"></div></div>
      <div class="do-kpi-sub">Assigned today</div>
    </div>
  </div>

  <!-- Trips Completed Today (teal) -->
  <div class="do-kpi-card do-kpi--teal">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Trips Completed</div>
      <div class="do-kpi-value"><?= $tripsCompleted ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($tripsCompleted, 30) ?>"></div></div>
      <div class="do-kpi-sub">Completed today</div>
    </div>
  </div>

  <!-- Delayed Trips Today (orange + Live badge) -->
  <div class="do-kpi-card do-kpi--orange">
    <div class="do-kpi-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
    <div class="do-kpi-body">
      <div class="do-kpi-label">Delayed Trips<span class="do-kpi-live">Live</span></div>
      <div class="do-kpi-value"><?= $delayedTrips ?></div>
      <div class="do-kpi-bar-wrap"><div class="do-kpi-bar" style="width:<?= $barW($delayedTrips, 10) ?>"></div></div>
      <div class="do-kpi-sub">Running late today</div>
    </div>
  </div>

</div>

<!-- â”€â”€ Quick Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card" style="margin-bottom:24px;">
  <h3 class="do-section-title">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
    </svg>
    Quick Actions
  </h3>
  <div class="do-qa-grid">

    <a href="/O/assignments" class="do-qa-btn do-qa--maroon">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Assignments</span>
        <span class="do-qa-desc">Manage driver &amp; conductor assignments</span>
      </div>
    </a>

    <a href="/O/timetables" class="do-qa-btn do-qa--gold">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Timetables</span>
        <span class="do-qa-desc">View &amp; manage schedules</span>
      </div>
    </a>

    <a href="/O/attendance" class="do-qa-btn do-qa--green">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
          <polyline points="16 11 18 13 22 9"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Attendance</span>
        <span class="do-qa-desc">Mark staff attendance</span>
      </div>
    </a>

    <a href="/O/trip_logs" class="do-qa-btn do-qa--teal">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Trip Logs</span>
        <span class="do-qa-desc">View daily trip records</span>
      </div>
    </a>

    <a href="/O/reports" class="do-qa-btn do-qa--blue">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Reports</span>
        <span class="do-qa-desc">Analytics &amp; exports</span>
      </div>
    </a>

    <a href="/O/messages" class="do-qa-btn do-qa--purple">
      <div class="do-qa-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a4 4 0 01-4 4H7l-4 3V5a4 4 0 014-4h10a4 4 0 014 4z"/>
        </svg>
      </div>
      <div class="do-qa-text">
        <span class="do-qa-label">Messages</span>
        <span class="do-qa-desc">Staff communications</span>
      </div>
    </a>

  </div>
</div>

<!-- â”€â”€ Live Bus Map â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card">
  <div class="do-live-header">
    <div class="do-live-dot"></div>
    Live Bus Locations
    <span style="margin-left:auto;font-size:.78rem;font-weight:500;color:#6b7280;background:#f3f4f6;padding:2px 10px;border-radius:20px" id="do-map-count">Loadingâ€¦</span>
  </div>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <div id="do-live-map" class="do-live-map"></div>
  <p style="font-size:.75rem;color:#9ca3af;margin-top:.5rem">Shows buses for this depot Â· auto-refreshes every 30 s</p>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  (function () {
    if (typeof L === 'undefined') return;
    var map = L.map('do-live-map').setView([6.927, 79.861], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18
    }).addTo(map);

    var markers = {};

    function busIcon(speed) {
      var over = speed > 60;
      var fill  = over ? '#dc2626' : '#1d6f42';
      var ring  = over ? '#fca5a5' : '#86efac';
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

    var inFlight = false;
    function fetchBuses() {
      if (inFlight || document.hidden) return;
      inFlight = true;
      fetch('/api/buses/live')
        .then(function (r) { return r.json(); })
        .then(function (buses) {
          if (!Array.isArray(buses)) return;
          var seen = {};
          buses.forEach(function (b) {
            var id = b.busId;
            seen[id] = true;
            var popup = '<div style="min-width:140px"><b>ðŸšŒ ' + b.busId + '</b><br>Route: <strong>'
              + b.routeNo + '</strong><br>'
              + (b.speedKmh > 60
                ? '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">âš¡ '+b.speedKmh+' km/h</span>'
                : '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:8px;font-size:.75rem;font-weight:600">âœ“ '+b.speedKmh+' km/h</span>')
              + '<br><small style="color:#6b7280">'+new Date(b.updatedAt).toLocaleTimeString()+'</small></div>';
            if (markers[id]) {
              markers[id].setLatLng([b.lat, b.lng]).setIcon(busIcon(b.speedKmh)).bindPopup(popup);
            } else {
              markers[id] = L.marker([b.lat, b.lng], { icon: busIcon(b.speedKmh) }).bindPopup(popup).addTo(map);
            }
          });
          Object.keys(markers).forEach(function (id) {
            if (!seen[id]) { map.removeLayer(markers[id]); delete markers[id]; }
          });
          var el = document.getElementById('do-map-count');
          if (el) el.textContent = Object.keys(seen).length + ' bus' + (Object.keys(seen).length !== 1 ? 'es' : '') + ' tracked';
        })
        .catch(function () {})
        .finally(function () { inFlight = false; });
    }

    fetchBuses();
    setInterval(fetchBuses, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) fetchBuses(); });
  })();
  </script>
</div>

<?php // â”€â”€â”€ end of dashboard view â”€â”€â”€ ?>
