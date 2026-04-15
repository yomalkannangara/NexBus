<?php
  $kpi     = $kpi     ?? ['delayedToday'=>0,'avgRating'=>0,'speedViol'=>0,'longWaitPct'=>0];
  $filters = $filters ?? ['route_no'=>'','bus_reg'=>'','date'=>''];
  $curRno  = $filters['route_no'] ?? '';
  $curBus  = $filters['bus_reg']  ?? '';
  $hasFilter = ($curRno !== '' || $curBus !== '');
  $dq = '';
  if ($curRno !== '') $dq .= '&route_no=' . urlencode($curRno);
  if ($curBus !== '') $dq .= '&bus_reg=' . urlencode($curBus);
  if (!empty($filters['date'])) $dq .= '&date=' . urlencode($filters['date']);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

.perf-page {
  font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
  --pm: #80143c;
  --pm-dark: #5b0e2a;
  --pm-light: #a01850;
  --pm-pale: rgba(128,20,60,.06);
  --gold: #f3b944;
  --gold-soft: rgba(243,185,68,.15);
  --green: #16a34a;
  --green-soft: rgba(22,163,74,.12);
  --red: #dc2626;
  --red-soft: rgba(220,38,38,.12);
  --blue: #2563eb;
  --blue-soft: rgba(37,99,235,.1);
  --orange: #ea580c;
  --orange-soft: rgba(234,88,12,.1);
  --bg: #f5f0f2;
  --card: #ffffff;
  --border: rgba(128,20,60,.08);
  --shadow-sm: 0 2px 8px rgba(128,20,60,.07);
  --shadow: 0 4px 20px rgba(128,20,60,.1);
  --shadow-lg: 0 10px 40px rgba(128,20,60,.16);
  --radius: 18px;
  --text: #1a0810;
  --muted: #7a4e5e;
  background: var(--bg);
  min-height: 100%;
  padding-bottom: 3rem;
}

/* ── Hero ──────────────────────────────────────────────── */
.perf-hero {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius);
  padding: 32px 36px;
  background: linear-gradient(135deg, var(--pm-dark) 0%, var(--pm) 50%, #a8174a 100%);
  margin-bottom: 24px;
  box-shadow: var(--shadow-lg);
}
.perf-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 85% -15%, rgba(243,185,68,.3) 0%, transparent 55%),
    radial-gradient(ellipse at -5% 115%, rgba(255,255,255,.06) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 50%, rgba(255,255,255,.02) 0%, transparent 100%);
  pointer-events: none;
}
/* Hex pattern overlay unique to depot manager */
.perf-hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' viewBox='0 0 60 52'%3E%3Cpolygon points='30,2 56,16 56,36 30,50 4,36 4,16' fill='none' stroke='rgba(255,255,255,0.04)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: 60px 52px;
  pointer-events: none;
}
.perf-hero-inner { position: relative; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.perf-hero-left h1 { margin:0 0 6px; font-size:clamp(22px, 3vw, 30px); font-weight:800; color:#fff; letter-spacing:-.5px; }
.perf-hero-left p { margin:0; color:rgba(255,255,255,.72); font-size:14px; }
.perf-hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.22); border-radius:999px; padding:4px 14px; color:#fff; font-size:12px; font-weight:600; margin-top:10px; backdrop-filter:blur(8px); }
.perf-hero-badge span { width:8px; height:8px; border-radius:50%; background:var(--gold); display:inline-block; animation:pulse-dot 1.8s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(1.4); } }
.perf-hero-filters { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
.perf-filter-group { display:flex; flex-direction:column; gap:4px; }
.perf-filter-label { font-size:11px; font-weight:600; color:rgba(255,255,255,.65); text-transform:uppercase; letter-spacing:.5px; }
.perf-select { position:relative; }
.perf-select select { appearance:none; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25); border-radius:10px; color:#fff; font:600 13px 'Inter', sans-serif; padding:9px 34px 9px 14px; outline:none; cursor:pointer; backdrop-filter:blur(8px); min-width:140px; }
.perf-select select option { background:var(--pm-dark); color:#fff; }
.perf-select::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%) rotate(45deg); width:7px; height:7px; border-right:2px solid rgba(255,255,255,.7); border-bottom:2px solid rgba(255,255,255,.7); pointer-events:none; }
.perf-clear-btn { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.25); border-radius:10px; color:rgba(255,255,255,.85); font:600 12px 'Inter', sans-serif; padding:9px 14px; cursor:pointer; text-decoration:none; align-self:flex-end; }
.perf-clear-btn:hover { background:rgba(255,255,255,.2); color:#fff; }
.perf-hero-art { position:absolute; right:-10px; top:-10px; width:180px; height:180px; opacity:.04; pointer-events:none; }

/* ── Section label ─────────────────────────────────────── */
.perf-section-label { display:flex; align-items:center; gap:10px; margin:0 0 16px; }
.perf-section-label .label-line { width:3px; height:20px; background:linear-gradient(to bottom, var(--pm), var(--gold)); border-radius:4px; flex-shrink:0; }
.perf-section-label h2 { margin:0; font-size:15px; font-weight:700; color:var(--text); letter-spacing:-.2px; }
.perf-section-label .label-badge { margin-left:auto; font-size:11px; font-weight:600; color:var(--pm); background:rgba(128,20,60,.08); border-radius:999px; padding:2px 10px; border:1px solid rgba(128,20,60,.12); }

/* ── KPI Grid — CIRCULAR OUTLINED icons ────────────────── */
.perf-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
@media (max-width:1100px) { .perf-kpi-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px) { .perf-kpi-grid { grid-template-columns:1fr; } }

.perf-kpi {
  background: #ffffff;
  border-radius: var(--radius);
  padding: 22px 20px 18px;
  box-shadow: var(--shadow-sm);
  border: 1.5px solid color-mix(in srgb, var(--kpi-color, var(--pm)) 15%, #e8dde1 85%);
  position: relative;
  overflow: hidden;
  isolation: isolate;
  transition: transform .25s ease, box-shadow .28s ease, border-color .25s ease;
  cursor: pointer;
}
.perf-kpi:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 36px rgba(128,20,60,.14);
  border-color: color-mix(in srgb, var(--kpi-color, var(--pm)) 35%, #e8dde1 65%);
}
/* Top accent bar */
.perf-kpi::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: 3px 3px 0 0;
  background: linear-gradient(90deg, var(--kpi-color, var(--pm)) 0%, color-mix(in srgb, var(--kpi-color, var(--pm)) 55%, #f3b944 45%) 100%);
}
/* Bottom-right decorative circle */
.perf-kpi::after {
  content: '';
  position: absolute;
  right: -28px; bottom: -36px;
  width: 110px; height: 110px;
  border-radius: 50%;
  border: 18px solid color-mix(in srgb, var(--kpi-color, var(--pm)) 9%, #ffffff 91%);
  pointer-events: none;
  z-index: -1;
}

.perf-kpi-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; }

/* CIRCULAR OUTLINED icon container */
.perf-kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;                /* ← circular */
  display: grid;
  place-items: center;
  background: transparent;
  border: 2px solid var(--kpi-color, var(--pm));  /* ← outline */
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--kpi-color, var(--pm)) 10%, transparent 90%);
  flex-shrink: 0;
  transition: box-shadow .25s ease, transform .25s ease;
}
.perf-kpi:hover .perf-kpi-icon {
  box-shadow: 0 0 0 7px color-mix(in srgb, var(--kpi-color, var(--pm)) 15%, transparent 85%);
  transform: scale(1.06);
}

.perf-kpi-label { font-size:11px; font-weight:700; color:#7a4e5e; text-transform:uppercase; letter-spacing:.7px; line-height:1.3; display:block; }
.perf-kpi-value { font-size:clamp(32px, 3vw, 40px); font-weight:800; color:var(--kpi-color, var(--pm)); line-height:.95; letter-spacing:-1.2px; margin-bottom:12px; margin-top:8px; }
.perf-kpi-hint { font-size:12px; color:#8a6472; display:flex; align-items:center; gap:8px; line-height:1.4; margin-top:8px; flex-wrap:wrap; }
.perf-kpi-hint .dot { width:6px; height:6px; border-radius:50%; background:var(--kpi-color, var(--pm)); flex-shrink:0; display:none; }
.perf-kpi-hint .kpi-click-hint { margin-left:auto; color:var(--kpi-color, var(--pm)); font-weight:700; white-space:nowrap; font-size:11px; flex-shrink:0; }

/* ── Live Fleet Status ─────────────────────────────────── */
.perf-live-row { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:22px; }
@media (max-width:600px) { .perf-live-row { grid-template-columns:1fr; } }
.perf-kpi-live {
  background: linear-gradient(135deg, var(--pm-dark) 0%, var(--pm) 100%);
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
  color: #fff;
}
/* Decorative rings for live cards */
.perf-kpi-live::before {
  content: '';
  position: absolute;
  right: -30px; top: -30px;
  width: 120px; height: 120px;
  border-radius: 50%;
  border: 20px solid rgba(255,255,255,.06);
}
.perf-kpi-live::after {
  content: '';
  position: absolute;
  right: 10px; bottom: -40px;
  width: 100px; height: 100px;
  border-radius: 50%;
  border: 14px solid rgba(255,255,255,.04);
}
.perf-kpi-live .live-label { font-size:12px; font-weight:600; color:rgba(255,255,255,.65); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.perf-kpi-live .live-value { font-size:36px; font-weight:800; color:#fff; line-height:1; letter-spacing:-1.2px; margin-bottom:6px; }
.perf-kpi-live .live-hint { font-size:12px; color:rgba(255,255,255,.6); }
.perf-kpi-live .live-icon {
  position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
  width: 50px; height: 50px;
  border-radius: 50%;           /* circular live icons too */
  border: 2px solid rgba(255,255,255,.3);
  background: rgba(255,255,255,.1);
  display: grid; place-items: center;
  color: rgba(255,255,255,.9);
}
.live-pulse { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border-radius:999px; padding:3px 10px; font-size:11px; font-weight:600; color:rgba(255,255,255,.85); margin-bottom:8px; }
.live-pulse-dot { width:6px; height:6px; border-radius:50%; background:#4ade80; animation:pulse-dot 1.5s ease-in-out infinite; }

/* ── Tabs ──────────────────────────────────────────────── */
.perf-tabs {
  display: flex; gap: 4px;
  background: #fff;
  border-radius: 14px;
  padding: 6px;
  margin-bottom: 22px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  overflow-x: auto;
}
.perf-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:10px; font:600 13px 'Inter', sans-serif; color:var(--muted); background:transparent; border:none; cursor:pointer; white-space:nowrap; transition:background .2s, color .2s; }
.perf-tab:hover { background:rgba(128,20,60,.06); color:var(--pm); }
.perf-tab.active { background:var(--pm); color:#fff; box-shadow:0 2px 10px rgba(128,20,60,.35); }
.perf-tab .tab-count { background:rgba(255,255,255,.25); border-radius:999px; padding:1px 7px; font-size:11px; }
.perf-tab:not(.active) .tab-count { background:rgba(128,20,60,.08); color:var(--pm); }
.perf-tab-panel { display:none; }
.perf-tab-panel.active { display:block; }

/* ── Charts ────────────────────────────────────────────── */
.perf-charts-grid { display:grid; grid-template-columns:repeat(12, minmax(0,1fr)); gap:16px; margin-bottom:22px; }
.perf-chart-card {
  grid-column: span 6;
  background: var(--card);
  border-radius: var(--radius);
  padding: 22px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
}
/* Chart card left accent strip */
.perf-chart-card::before {
  content: '';
  position: absolute;
  left: 0; top: 16px; bottom: 16px;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(to bottom, var(--pm), var(--gold));
  opacity: .5;
}
.perf-chart-card.span-12 { grid-column:span 12; }
@media (max-width:960px) { .perf-chart-card { grid-column:span 12; } }
.perf-chart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:8px; padding-left:12px; }
.perf-chart-title { font-size:14px; font-weight:700; color:var(--text); margin:0; }
.perf-chart-detail-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--pm); background:var(--gold-soft); border:1px solid rgba(243,185,68,.3); border-radius:8px; padding:5px 12px; text-decoration:none; white-space:nowrap; }
.perf-chart-detail-link:hover { background:rgba(243,185,68,.25); }
.perf-chart-card canvas { display:block; width:100% !important; }

/* ── Fleet Table ───────────────────────────────────────── */
.perf-fleet-table-wrap { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; margin-bottom:22px; }
.perf-fleet-table-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid rgba(128,20,60,.07); }
.perf-fleet-table-head h3 { margin:0; font-size:14px; font-weight:700; color:var(--text); }
.perf-fleet-refresh { font-size:11.5px; color:var(--muted); }
.perf-fleet-table { width:100%; border-collapse:collapse; font-size:13px; }
.perf-fleet-table th { padding:10px 16px; background:rgba(128,20,60,.03); font-size:11px; font-weight:700; color:var(--pm); text-transform:uppercase; letter-spacing:.4px; text-align:left; border-bottom:1px solid rgba(128,20,60,.07); }
.perf-fleet-table td { padding:11px 16px; border-bottom:1px solid rgba(128,20,60,.04); color:var(--text); font-weight:500; }
.perf-fleet-table tbody tr:hover { background:rgba(128,20,60,.035); }
.perf-fleet-table tbody tr:last-child td { border-bottom:none; }
.perf-fleet-table td:nth-child(4) { text-align:right; }
.perf-fleet-table td:nth-child(5), .perf-fleet-table td:nth-child(6) { text-align:center; }
.perf-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight:600; white-space:nowrap; }
.perf-badge--green { background:#dcfce7; color:#15803d; }
.perf-badge--red { background:#fee2e2; color:#b91c1c; }
.perf-badge--gray { background:rgba(128,20,60,.06); color:var(--pm); }
.perf-badge--gold { background:#fef3c7; color:#d97706; }
.perf-badge--blue { background:#eff6ff; color:#2563eb; }
.perf-empty-row td { text-align:center; color:var(--muted); padding:32px 16px; font-size:13px; }
.perf-empty-icon { width:44px; height:44px; background:rgba(128,20,60,.06); border-radius:50%; border:2px solid rgba(128,20,60,.15); display:flex; align-items:center; justify-content:center; margin:0 auto 8px; }
.fleet-expand-row td { text-align:center; padding:10px; border-top:1px solid rgba(128,20,60,.06); }
.fleet-expand-btn { background:none; border:1px solid rgba(128,20,60,.2); border-radius:8px; padding:6px 20px; font:600 12px 'Inter', sans-serif; color:var(--pm); cursor:pointer; }

/* ── Chart legend ──────────────────────────────────────── */
.chart-legend { display:flex; flex-wrap:wrap; gap:8px 14px; justify-content:center; margin-top:10px; }
.chart-legend .legend-item { display:inline-flex; align-items:center; gap:7px; font-size:12px; color:var(--text); font-weight:500; }
.chart-legend .legend-item i { width:12px; height:12px; border-radius:50%; display:inline-block; }
</style>

<div class="perf-page">
  <div class="perf-hero">
    <svg class="perf-hero-art" viewBox="0 0 200 120" fill="white" xmlns="http://www.w3.org/2000/svg">
      <rect x="10" y="20" width="170" height="80" rx="12"/>
      <rect x="20" y="30" width="60" height="35" rx="4" fill="rgba(0,0,0,.3)"/>
      <rect x="90" y="30" width="60" height="35" rx="4" fill="rgba(0,0,0,.3)"/>
      <circle cx="45" cy="110" r="14"/><circle cx="145" cy="110" r="14"/>
      <rect x="165" y="40" width="20" height="40" rx="4"/>
      <rect x="0" y="45" width="15" height="30" rx="4"/>
    </svg>

    <div class="perf-hero-inner">
      <div class="perf-hero-left">
        <div class="live-pulse"><span class="live-pulse-dot"></span>Live Analytics</div>
        <h1>Performance Dashboard</h1>
        <p>Real-time fleet metrics and operational intelligence for <?= htmlspecialchars($depotName ?? 'your depot') ?></p>
        <div class="perf-hero-badge"><span></span><?= htmlspecialchars($depotName ?? 'SLTB Depot') ?></div>
      </div>
      <div class="perf-hero-filters">
        <form method="get" action="/M/performance" class="perf-hero-filters">
          <div class="perf-filter-group">
            <label class="perf-filter-label" for="ft-route">Route</label>
            <div class="perf-select">
              <select id="ft-route" name="route_no" onchange="this.form.submit()">
                <option value="">All Routes</option>
                <?php foreach(($routes ?? []) as $r): ?>
                  <option value="<?= htmlspecialchars($r['route_no']) ?>" <?= ($curRno === $r['route_no']) ? 'selected' : '' ?>><?= htmlspecialchars($r['route_no']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="perf-filter-group">
            <label class="perf-filter-label" for="ft-bus">Bus</label>
            <div class="perf-select">
              <select id="ft-bus" name="bus_reg" onchange="this.form.submit()">
                <option value="">All Buses</option>
                <?php foreach(($buses ?? []) as $b): ?>
                  <option value="<?= htmlspecialchars($b['reg_no']) ?>" <?= ($curBus === $b['reg_no']) ? 'selected' : '' ?>><?= htmlspecialchars($b['reg_no']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php if ($hasFilter): ?>
            <a href="/M/performance" class="perf-clear-btn">Clear</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="perf-section-label">
    <span class="label-line"></span>
    <h2>Fleet Performance KPIs</h2>
    <span class="label-badge">from database</span>
  </div>

  <div class="perf-kpi-grid">
    <div class="perf-kpi perf-kpi--clickable" id="kpi-delayed-card" style="--kpi-color:var(--red);--kpi-bg:var(--red-soft)" onclick="openDelayedModal()" title="Click to view delayed bus details">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Delayed Buses Today</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--red)"><path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-delayed"><?= (int)($kpi['delayedToday'] ?? 0) ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Live snapshot from database <span class="kpi-click-hint">&nbsp;· tap for details →</span></div>
    </div>

    <div class="perf-kpi perf-kpi--clickable" id="kpi-rating-card" style="--kpi-color:var(--green);--kpi-bg:var(--green-soft)" onclick="openRatingModal()" title="Click to view driver rating details">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Avg Driver Rating</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--green)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-rating"><?= ($kpi['avgRating'] > 0) ? number_format((float)$kpi['avgRating'],1) : '&ndash;' ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Composite reliability score out of 10 <span class="kpi-click-hint">&nbsp;&middot; tap for details &rarr;</span></div>
    </div>

    <div class="perf-kpi perf-kpi--clickable" id="kpi-speed-card" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-soft)" onclick="openSpeedModal()" title="Click to view speed violation details">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Speed Violations</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--orange)"><path d="M14 3L3 14h7v7l11-11h-7z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-speed"><?= (int)($kpi['speedViol'] ?? 0) ?: '&ndash;' ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Buses over speed limit <span class="kpi-click-hint">&nbsp;&middot; tap for details &rarr;</span></div>
    </div>

    <div class="perf-kpi perf-kpi--clickable" id="kpi-wait-card" style="--kpi-color:var(--blue);--kpi-bg:var(--blue-soft)" onclick="openWaitModal()" title="Click to view long wait time details">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Long Wait Times</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--blue)"><path d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-wait"><?= (int)($kpi['longWaitPct'] ?? 0) ?>%</div>
      <div class="perf-kpi-hint"><span class="dot"></span>Snapshots with delay &gt;10 min <span class="kpi-click-hint">&nbsp;&middot; tap for details &rarr;</span></div>
    </div>
  </div>

  <div class="perf-section-label">
    <span class="label-line"></span>
    <h2>Live Fleet Status</h2>
    <span class="label-badge" id="live-updated-at">Fetching…</span>
  </div>

  <div class="perf-live-row">
    <div class="perf-kpi-live">
      <div class="live-label">Active Buses Now</div>
      <div class="live-value" id="kpi-active-buses">&ndash;</div>
      <div class="live-hint">Buses reporting live GPS</div>
      <div class="live-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,.9)"><path d="M17 8C8 10 5.9 16.1 3 19h3s2.5-4 9.5-4.5c-1.7 1.1-3.5 3-4.5 4.5h3C15 17 17 14 21 12c-1-1-2-2-2-4z"/></svg></div>
    </div>
    <div class="perf-kpi-live" style="background:linear-gradient(135deg,#14532d 0%,#16a34a 100%)">
      <div class="live-label">Average Fleet Speed</div>
      <div class="live-value" id="kpi-avg-speed">&ndash;</div>
      <div class="live-hint">Fleet average right now</div>
      <div class="live-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,.9)"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m1 14.93V15h-2v1.93A8 8 0 0 1 4.07 11H6V9H4.07A8 8 0 0 1 11 4.07V6h2V4.07A8 8 0 0 1 19.93 11H18v2h1.93A8 8 0 0 1 13 16.93z"/></svg></div>
    </div>
  </div>

  <div class="perf-tabs" role="tablist">
    <button class="perf-tab active" data-tab="live" role="tab"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg> Live View</button>
    <button class="perf-tab" data-tab="analytics" role="tab"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3v18h18V3zm15 4h-4v4h4zm-6 0H8v4h4zm6 6h-4v4h4zm-6 0H8v4h4z"/></svg> Analytics Charts</button>
    <button class="perf-tab" data-tab="fleet" role="tab"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg> Fleet Table</button>
  </div>

  <div class="perf-tab-panel active" id="tab-live">
    <div class="perf-charts-grid">
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Live Bus Status</h3></div><canvas id="liveStatusChart"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Live Fleet Speed</h3></div><canvas id="liveSpeedChart"></canvas></div>
    </div>
  </div>

  <div class="perf-tab-panel" id="tab-analytics">
    <div class="perf-charts-grid">
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Bus Status Distribution</h3></div><canvas id="busStatusChart" data-drill-key="bus_status" data-drill-base="/M/performance/details"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Delayed Buses by Route</h3></div><canvas id="delayedByRouteChart" data-drill-key="delayed_by_route" data-drill-base="/M/performance/details"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Speed Violations by Bus</h3></div><canvas id="speedByBusChart" data-drill-key="speed_by_bus" data-drill-base="/M/performance/details"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Revenue Overview</h3></div><canvas id="revenueChart" data-drill-key="revenue" data-drill-base="/M/performance/details"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Bus Wait Time Distribution</h3></div><canvas id="waitTimeChart" data-drill-key="wait_time" data-drill-base="/M/performance/details"></canvas></div>
      <div class="perf-chart-card"><div class="perf-chart-header"><h3 class="perf-chart-title">Complaints by Bus</h3></div><canvas id="complaintsRouteChart" data-drill-key="complaints_by_route" data-drill-base="/M/performance/details"></canvas></div>
    </div>
  </div>

  <div class="perf-tab-panel" id="tab-fleet">
    <div class="perf-fleet-table-wrap">
      <div class="perf-fleet-table-head"><h3>Live Bus Fleet</h3><span class="perf-fleet-refresh" id="live-updated-at-table">&nbsp;</span></div>
      <div style="overflow-x:auto">
        <table class="perf-fleet-table">
          <thead>
            <tr>
              <th>Bus ID</th>
              <th>Route</th>
              <th>Operator / Depot</th>
              <th style="text-align:right">Speed (km/h)</th>
              <th style="text-align:center">Status</th>
              <th style="text-align:center">Location</th>
            </tr>
          </thead>
          <tbody id="live-route-tbody">
            <tr class="perf-empty-row"><td colspan="6"><div class="perf-empty-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#9ca3af"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>Loading live bus data&hellip;</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>

  <script>
  (function(){
  'use strict';

  document.querySelectorAll('.perf-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = btn.dataset.tab;
      document.querySelectorAll('.perf-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.perf-tab-panel').forEach(function(p){ p.classList.remove('active'); });
      btn.classList.add('active');
      var panel = document.getElementById('tab-' + target);
      if(panel){ panel.classList.add('active'); }
      setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 50);
    });
  });

  window._NEXBUS_LIVE_API = '/M/live';

  window._perfBuildRow = function(b) {
    var SPEED_LIMIT = 60;
    var over = (+b.speedKmh || 0) > SPEED_LIMIT;
    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    var spBadge = over
      ? '<span class="perf-badge perf-badge--red">⚡ ' + b.speedKmh + ' km/h</span>'
      : '<span class="perf-badge perf-badge--green">' + b.speedKmh + ' km/h</span>';

    var opType  = b.operatorType || b.operator_type || '';
    var opLabel = opType === 'SLTB'
      ? 'SLTB' + (b.depot  ? ' · ' + esc(b.depot)  : '')
      : opType === 'Private'
      ? 'Private' + (b.owner ? ' · ' + esc(b.owner) : '')
      : (opType ? esc(opType) : '<span style="color:#9ca3af">–</span>');

    var status    = over ? 'Speeding' : esc(b.operationalStatus || 'On Time');
    var statusCls = over ? 'perf-badge--red' : (status === 'Delayed' ? 'perf-badge--gold' : 'perf-badge--green');

    var locLink = (b.lat && b.lng)
      ? '<a href="/M/dashboard?bus='+encodeURIComponent(String(b.busId || ''))+'" class="perf-badge perf-badge--blue" style="text-decoration:none">📍 Map</a>'
      : '<span class="perf-badge perf-badge--gray">–</span>';

    return '<tr' + (over ? ' style="background:#fff5f5"' : '') + '>'
      + '<td><strong>' + esc(b.busId) + '</strong></td>'
      + '<td>' + esc(String(b.routeNo || '–')) + '</td>'
      + '<td>' + opLabel + '</td>'
      + '<td>' + spBadge + '</td>'
      + '<td><span class="perf-badge ' + statusCls + '">' + status + '</span></td>'
      + '<td>' + locLink + '</td>'
      + '</tr>';
  };

  window._perfFleetExpand = function(){
    var extras = document.querySelectorAll('tr.fleet-extra');
    var btn    = document.getElementById('fleet-expander');
    var shown  = extras.length && extras[0].style.display !== 'none';
    extras.forEach(function(r){ r.style.display = shown ? 'none' : ''; });
    if(btn){
      var b = btn.querySelector('button');
      if(b) b.textContent = shown
        ? 'Show ' + extras.length + ' more ▼'
        : 'Collapse ▲';
    }
  };
  })();
  </script>

  <style>
  .perf-page .perf-kpi--clickable { cursor: pointer; }
  .perf-page .kpi-click-hint { font-size: 11px; font-weight: 700; opacity: 1; }

  .dm-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(4px);
    z-index: 9000;
    align-items: flex-start;
    justify-content: center;
    padding: 80px 16px 24px;
    overflow-y: auto;
  }
  .dm-overlay.open { display: flex; }
  .dm-modal {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    width: 100%; max-width: 920px;
    overflow: hidden;
    animation: dm-slide-in .25s cubic-bezier(.34,1.56,.64,1);
    margin: auto;
  }
  @keyframes dm-slide-in {
    from { opacity:0; transform:translateY(-22px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
  }
  .dm-header {
    background: linear-gradient(130deg,#5b0e2a 0%,#80143c 55%,#b01e4e 100%);
    padding: 22px 26px;
    display: flex; align-items: center;
    justify-content: space-between; gap: 12px;
  }
  .dm-header-left { display:flex; align-items:center; gap:14px; }
  .dm-header-icon {
    width:44px; height:44px; border-radius:13px;
    background:rgba(255,255,255,.15);
    display:grid; place-items:center; flex-shrink:0;
  }
  .dm-header-title {
    color:#fff; font-size:17px; font-weight:800;
    letter-spacing:-.3px; margin:0 0 2px;
    font-family:'Inter',ui-sans-serif,sans-serif;
  }
  .dm-header-sub {
    color:rgba(255,255,255,.65); font-size:12px;
    font-weight:500; margin:0;
    font-family:'Inter',ui-sans-serif,sans-serif;
  }
  .dm-close {
    width:34px; height:34px; border-radius:9px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.2);
    color:rgba(255,255,255,.9); font-size:18px; line-height:1;
    cursor:pointer; display:grid; place-items:center;
    transition:background .2s; flex-shrink:0;
  }
  .dm-close:hover { background:rgba(255,255,255,.25); }
  .dm-body { padding:24px 26px; display:flex; flex-direction:column; gap:28px; }
  .dm-section-label { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
  .dm-section-label .dm-line {
    width:4px; height:18px; border-radius:4px; flex-shrink:0;
    background:linear-gradient(to bottom,#80143c,#f3b944);
  }
  .dm-section-label h3 {
    margin:0; font-size:13.5px; font-weight:700; color:#111827;
    letter-spacing:-.15px; font-family:'Inter',ui-sans-serif,sans-serif;
  }
  .dm-section-label .dm-lbl-badge {
    margin-left:auto; font-size:11px; font-weight:600;
    color:#6b7280; background:#f3f4f6;
    border-radius:999px; padding:2px 10px;
  }
  .dm-table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #f0f1f4; }
  .dm-table { width:100%; border-collapse:collapse; font-size:13px; font-family:'Inter',ui-sans-serif,sans-serif; }
  .dm-table th {
    padding:10px 14px; background:#fafafa;
    font-size:11px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.4px;
    text-align:left; border-bottom:1px solid #f0f0f0;
    white-space:nowrap;
  }
  .dm-table td {
    padding:11px 14px; border-bottom:1px solid #f7f8fa;
    color:#111827; font-weight:500; white-space:nowrap;
  }
  .dm-table tbody tr:hover { background:#fafbff; }
  .dm-table tbody tr:last-child td { border-bottom:none; }
  .dm-bd { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:600; }
  .dm-bd-red   { background:#fee2e2; color:#b91c1c; }
  .dm-bd-gold  { background:#fef3c7; color:#d97706; }
  .dm-bd-green { background:#dcfce7; color:#15803d; }
  .dm-bd-gray  { background:#f3f4f6; color:#6b7280; }
  .dm-empty {
    text-align:center; padding:40px 20px;
    color:#9ca3af; font-size:13px;
    font-family:'Inter',ui-sans-serif,sans-serif;
  }
  .dm-empty svg { display:block; margin:0 auto 10px; }
  .dm-spinner {
    display:flex; align-items:center; justify-content:center;
    padding:48px 0; gap:10px;
    color:#6b7280; font-size:13px;
    font-family:'Inter',ui-sans-serif,sans-serif;
  }
  .dm-spin-ring {
    width:26px; height:26px;
    border:3px solid #f3f4f6;
    border-top-color:#80143c;
    border-radius:50%;
    animation:dm-spin .7s linear infinite;
  }
  @keyframes dm-spin { to { transform:rotate(360deg); } }
  @media(max-width:640px){
    .dm-body { padding:14px; }
    .dm-header { padding:16px 14px; }
  }
  </style>

  <div class="dm-overlay" id="delayedModal" role="dialog" aria-modal="true" aria-labelledby="dm-title">
    <div class="dm-modal">
      <div class="dm-header">
        <div class="dm-header-left">
          <div class="dm-header-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg></div>
          <div>
            <p class="dm-header-title" id="dm-title">Delayed Buses Today</p>
            <p class="dm-header-sub" id="dm-header-date">Loading&hellip;</p>
          </div>
        </div>
        <button class="dm-close" onclick="closeDelayedModal()" aria-label="Close">&times;</button>
      </div>
      <div class="dm-body" id="dm-body"><div class="dm-spinner"><span class="dm-spin-ring"></span> Fetching data&hellip;</div></div>
    </div>
  </div>

  <div class="dm-overlay" id="ratingModal" role="dialog" aria-modal="true" aria-labelledby="rm-title">
    <div class="dm-modal">
      <div class="dm-header" style="background:linear-gradient(130deg,#14532d 0%,#16a34a 55%,#22c55e 100%)">
        <div class="dm-header-left">
          <div class="dm-header-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/></svg></div>
          <div>
            <p class="dm-header-title" id="rm-title">Avg Driver Rating</p>
            <p class="dm-header-sub" id="rm-header-sub">Loading&hellip;</p>
          </div>
        </div>
        <button class="dm-close" onclick="closeRatingModal()" aria-label="Close">&times;</button>
      </div>
      <div class="dm-body" id="rm-body"><div class="dm-spinner"><span class="dm-spin-ring"></span> Fetching data&hellip;</div></div>
    </div>
  </div>

  <div class="dm-overlay" id="speedModal" role="dialog" aria-modal="true" aria-labelledby="sm-title">
    <div class="dm-modal">
      <div class="dm-header" style="background:linear-gradient(130deg,#7c2d12 0%,#ea580c 55%,#f97316 100%)">
        <div class="dm-header-left">
          <div class="dm-header-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M14 3L3 14h7v7l11-11h-7z"/></svg></div>
          <div>
            <p class="dm-header-title" id="sm-title">Speed Violations</p>
            <p class="dm-header-sub" id="sm-header-sub">Loading&hellip;</p>
          </div>
        </div>
        <button class="dm-close" onclick="closeSpeedModal()" aria-label="Close">&times;</button>
      </div>
      <div class="dm-body" id="sm-body"><div class="dm-spinner"><span class="dm-spin-ring" style="border-top-color:#ea580c"></span> Fetching data&hellip;</div></div>
    </div>
  </div>

  <div class="dm-overlay" id="waitModal" role="dialog" aria-modal="true" aria-labelledby="wm-title">
    <div class="dm-modal">
      <div class="dm-header" style="background:linear-gradient(130deg,#1e3a8a 0%,#2563eb 55%,#3b82f6 100%)">
        <div class="dm-header-left">
          <div class="dm-header-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg></div>
          <div>
            <p class="dm-header-title" id="wm-title">Long Wait Times</p>
            <p class="dm-header-sub" id="wm-header-sub">Loading&hellip;</p>
          </div>
        </div>
        <button class="dm-close" onclick="closeWaitModal()" aria-label="Close">&times;</button>
      </div>
      <div class="dm-body" id="wm-body"><div class="dm-spinner"><span class="dm-spin-ring" style="border-top-color:#2563eb"></span> Fetching data&hellip;</div></div>
    </div>
  </div>

  <script>
  (function(){
  'use strict';

  var _spinner = '<div class="dm-spinner"><span class="dm-spin-ring"></span> Fetching data&hellip;</div>';
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function buildReportQuery() {
    var p = new URLSearchParams(window.location.search || '');
    p.set('_', Date.now());
    return p.toString();
  }
  function formatReportDate(reportDate) {
    if (!reportDate) {
      return new Date().toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    }
    var dt = new Date(reportDate + 'T00:00:00');
    if (isNaN(dt.getTime())) return reportDate;
    return dt.toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'});
  }
  function ratingBar(v) {
    v = parseFloat(v)||0;
    var pct = Math.min(100, (v/10)*100);
    var col = v >= 7 ? '#16a34a' : v >= 4 ? '#f3b944' : '#dc2626';
    return '<div style="display:flex;align-items:center;gap:8px">'
      +'<div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden">'
      +'<div style="width:'+pct+'%;height:100%;background:'+col+';border-radius:4px"></div></div>'
      +'<span style="font-weight:700;color:'+col+';min-width:28px">'+v.toFixed(1)+'</span></div>';
  }
  function statusBadge(s) {
    s = String(s||'On Time');
    if (s === 'Delayed')  return '<span class="dm-bd dm-bd-gold">&#9203; '+esc(s)+'</span>';
    if (s === 'Speeding') return '<span class="dm-bd dm-bd-red">&#9889; '+esc(s)+'</span>';
    if (s === 'On Time')  return '<span class="dm-bd dm-bd-green">&#10003; On Time</span>';
    return '<span class="dm-bd dm-bd-gray">'+esc(s)+'</span>';
  }
  function sectionLabel(title, badge, color) {
    color = color || 'linear-gradient(to bottom,#80143c,#f3b944)';
    return '<div class="dm-section-label">'
      +'<span class="dm-line" style="background:'+color+'"></span>'
      +'<h3>'+title+'</h3>'
      +(badge ? '<span class="dm-lbl-badge">'+badge+'</span>' : '')
      +'</div>';
  }

  window.openDelayedModal = function() {
    document.getElementById('delayedModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadDelayedData();
  };
  window.closeDelayedModal = function() {
    document.getElementById('delayedModal').classList.remove('open');
    document.body.style.overflow = '';
  };
  document.getElementById('delayedModal').addEventListener('click', function(e){ if(e.target===this) closeDelayedModal(); });

  function loadDelayedData() {
    var body = document.getElementById('dm-body');
    document.getElementById('dm-header-date').textContent = 'Loading…';
    body.innerHTML = _spinner;
    fetch('/M/performance/delayed-modal?' + buildReportQuery())
      .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
      .then(function(data){
        var summary = data.routeSummary || [];
        var buses = data.delayedBuses || [];
        var dayLabel = formatReportDate(data.reportDate || '');
        document.getElementById('dm-header-date').textContent = dayLabel + ' · ' + buses.length + ' delayed bus' + (buses.length!==1 ? 'es' : '') + ' found';
        var html = '';
        html += '<div>' + sectionLabel('Route Performance Summary', summary.length+' route'+(summary.length!==1?'s':''));
        if (!summary.length) {
          html += '<div class="dm-empty">No tracking data available for today.</div>';
        } else {
          html += '<div class="dm-table-wrap"><table class="dm-table"><thead><tr><th>Route</th><th>Total Buses</th><th>Delayed Buses</th><th>Avg Speed (km/h)</th></tr></thead><tbody>';
          summary.forEach(function(r){
            var d = parseInt(r.delayed_buses,10)||0;
            var dc = d > 0 ? '<span class="dm-bd dm-bd-red">'+d+'</span>' : '<span class="dm-bd dm-bd-green">0</span>';
            html += '<tr><td><strong>'+esc(r.route_no)+'</strong></td><td>'+esc(r.total_buses)+'</td><td>'+dc+'</td><td>'+parseFloat(r.avg_speed||0).toFixed(1)+'</td></tr>';
          });
          html += '</tbody></table></div>';
        }
        html += '</div>';
        html += '<div>' + sectionLabel('Detailed Delayed Buses', buses.length+' record'+(buses.length!==1?'s':''));
        if (!buses.length) {
          html += '<div class="dm-empty">No delayed buses for today.</div>';
        } else {
          html += '<div class="dm-table-wrap"><table class="dm-table"><thead><tr><th>Bus ID</th><th>Depot</th><th>Route</th><th>Status</th><th>Speed (km/h)</th><th>Avg Delay (min)</th><th>Last Snapshot</th></tr></thead><tbody>';
          buses.forEach(function(b){
            html += '<tr><td><strong>'+esc(b.bus_reg_no)+'</strong></td><td>'+esc(b.owner_name)+'</td><td>'+esc(b.route_no)+'</td><td>'+statusBadge(b.operational_status)+'</td><td>'+parseFloat(b.speed||0).toFixed(1)+'</td><td><span class="dm-bd dm-bd-red">'+parseFloat(b.avg_delay_min||0).toFixed(1)+' min</span></td><td style="color:#6b7280;font-size:12px">'+esc(b.snapshot_at)+'</td></tr>';
          });
          html += '</tbody></table></div>';
        }
        html += '</div>';
        body.innerHTML = html;
      })
      .catch(function(err){ body.innerHTML = '<div class="dm-empty"><p>Could not load data: '+esc(err.message)+'</p></div>'; });
  }

  window.openRatingModal = function() {
    document.getElementById('ratingModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadRatingData();
  };
  window.closeRatingModal = function() {
    document.getElementById('ratingModal').classList.remove('open');
    document.body.style.overflow = '';
  };
  document.getElementById('ratingModal').addEventListener('click', function(e){ if(e.target===this) closeRatingModal(); });
  function loadRatingData() {
    var body = document.getElementById('rm-body');
    document.getElementById('rm-header-sub').textContent = 'Loading…';
    body.innerHTML = _spinner.replace('dm-spin-ring','dm-spin-ring" style="border-top-color:#16a34a');
    fetch('/M/performance/rating-modal?' + buildReportQuery())
      .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
      .then(function(data){
        var summary = data.summary || {};
        var buses = data.buses || [];
        var dayLabel = formatReportDate(data.reportDate || '');
        document.getElementById('rm-header-sub').textContent = dayLabel + ' · ' + buses.length + ' bus' + (buses.length!==1?'es':'') + ' tracked';
        var html = '';
        if (summary.bus_count > 0) {
          html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:4px">';
          [{label:'Fleet Avg Rating', value: parseFloat(summary.fleet_avg||0).toFixed(1)+' / 10'},{label:'Best Rating', value: parseFloat(summary.best||0).toFixed(1)+' / 10'},{label:'Worst Rating', value: parseFloat(summary.worst||0).toFixed(1)+' / 10'},{label:'Buses Tracked', value: summary.bus_count}].forEach(function(s){
            html += '<div style="background:#f9fafb;border-radius:12px;padding:14px 16px;text-align:center"><div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">'+esc(s.label)+'</div><div style="font-size:20px;font-weight:800;color:#111827">'+esc(String(s.value))+'</div></div>';
          });
          html += '</div>';
        }
        html += sectionLabel('Rating Per Bus', buses.length+' bus'+(buses.length!==1?'es':''), 'linear-gradient(to bottom,#16a34a,#84cc16)');
        if (!buses.length) {
          html += '<div class="dm-empty"><p>No tracking data for today.</p></div>';
        } else {
          html += '<div class="dm-table-wrap"><table class="dm-table"><thead><tr><th>Bus ID</th><th>Route</th><th>Driver</th><th>Avg Rating (0-10)</th><th>Avg Speed (km/h)</th><th>Snapshots</th><th>Last Snapshot</th></tr></thead><tbody>';
          buses.forEach(function(b){
            html += '<tr><td><strong>'+esc(b.bus_reg_no)+'</strong></td><td>'+esc(b.route_no)+'</td><td>'+esc(b.driver_name)+'</td><td>'+ratingBar(b.avg_rating)+'</td><td>'+parseFloat(b.avg_speed||0).toFixed(1)+'</td><td>'+esc(b.snapshots)+'</td><td style="color:#6b7280;font-size:12px">'+esc(b.last_snapshot)+'</td></tr>';
          });
          html += '</tbody></table></div>';
        }
        body.innerHTML = html;
      })
      .catch(function(err){ body.innerHTML = '<div class="dm-empty"><p>Could not load: '+esc(err.message)+'</p></div>'; });
  }

  window.openSpeedModal = function() {
    document.getElementById('speedModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadSpeedData();
  };
  window.closeSpeedModal = function() {
    document.getElementById('speedModal').classList.remove('open');
    document.body.style.overflow = '';
  };
  document.getElementById('speedModal').addEventListener('click', function(e){ if(e.target===this) closeSpeedModal(); });
  function loadSpeedData() {
    var body = document.getElementById('sm-body');
    document.getElementById('sm-header-sub').textContent = 'Loading…';
    body.innerHTML = _spinner.replace('dm-spin-ring','dm-spin-ring" style="border-top-color:#ea580c');
    fetch('/M/performance/speed-modal?' + buildReportQuery())
      .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
      .then(function(data){
        var summary = data.summary || {};
        var buses = data.buses || [];
        var dayLabel = formatReportDate(data.reportDate || '');
        document.getElementById('sm-header-sub').textContent = dayLabel + ' · ' + buses.length + ' offending bus' + (buses.length!==1?'es':'');
        var html = '';
        if (summary.bus_count > 0) {
          html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:4px">';
          [{label:'Total Violations',value:parseInt(summary.total_violations||0)},{label:'Fleet Max Speed',value:parseFloat(summary.fleet_max_speed||0).toFixed(1)+' km/h'},{label:'Buses Tracked',value:parseInt(summary.bus_count||0)}].forEach(function(s){
            html += '<div style="background:#fff7ed;border-radius:12px;padding:14px 16px;text-align:center;border:1px solid rgba(234,88,12,.12)"><div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">'+esc(s.label)+'</div><div style="font-size:20px;font-weight:800;color:#ea580c">'+esc(String(s.value))+'</div></div>';
          });
          html += '</div>';
        }
        html += sectionLabel('Speed Violations Per Bus', buses.length+' bus'+(buses.length!==1?'es':'')+' with violations', 'linear-gradient(to bottom,#ea580c,#f3b944)');
        if (!buses.length) {
          html += '<div class="dm-empty"><p>No speed violations recorded today.</p></div>';
        } else {
          html += '<div class="dm-table-wrap"><table class="dm-table"><thead><tr><th>Bus ID</th><th>Route</th><th>Driver</th><th>Violations</th><th>Max Speed</th><th>Avg Speed</th><th>Snapshots</th><th>Last Snapshot</th></tr></thead><tbody>';
          buses.forEach(function(b){
            var viol = parseInt(b.total_violations||0);
            html += '<tr><td><strong>'+esc(b.bus_reg_no)+'</strong></td><td>'+esc(b.route_no||'-')+'</td><td>'+esc(b.driver_name||'-')+'</td><td><span class="dm-bd dm-bd-red">&#9889; '+viol+'</span></td><td><span style="font-weight:700;color:#ea580c">'+parseFloat(b.max_speed||0).toFixed(1)+' km/h</span></td><td>'+parseFloat(b.avg_speed||0).toFixed(1)+' km/h</td><td>'+esc(b.snapshots)+'</td><td style="color:#6b7280;font-size:12px">'+esc(b.last_snapshot)+'</td></tr>';
          });
          html += '</tbody></table></div>';
        }
        body.innerHTML = html;
      })
      .catch(function(err){ body.innerHTML = '<div class="dm-empty"><p>Could not load: '+esc(err.message)+'</p></div>'; });
  }

  window.openWaitModal = function() {
    document.getElementById('waitModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadWaitData();
  };
  window.closeWaitModal = function() {
    document.getElementById('waitModal').classList.remove('open');
    document.body.style.overflow = '';
  };
  document.getElementById('waitModal').addEventListener('click', function(e){ if(e.target===this) closeWaitModal(); });
  function loadWaitData() {
    var body = document.getElementById('wm-body');
    document.getElementById('wm-header-sub').textContent = 'Loading…';
    body.innerHTML = _spinner.replace('dm-spin-ring','dm-spin-ring" style="border-top-color:#2563eb');
    fetch('/M/performance/wait-modal?' + buildReportQuery())
      .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
      .then(function(data){
        var buckets = data.buckets || {};
        var buses = data.buses || [];
        var dayLabel = formatReportDate(data.reportDate || '');
        document.getElementById('wm-header-sub').textContent = dayLabel + ' · ' + buses.length + ' bus' + (buses.length!==1?'es':'') + ' with delay ≥10 min';
        var html = '';
        if (Object.keys(buckets).length) {
          var total = parseInt(buckets.total||0) || 1;
          html += sectionLabel('Delay Distribution Today', total+' total snapshot'+(total!==1?'s':''), 'linear-gradient(to bottom,#2563eb,#60a5fa)');
          html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:4px">';
          [{label:'Under 5 min',key:'under_5',color:'#16a34a'},{label:'5–10 min',key:'b5_10',color:'#84cc16'},{label:'10–15 min',key:'b10_15',color:'#f3b944'},{label:'Over 15 min',key:'over_15',color:'#b91c1c'}].forEach(function(bd){
            var cnt = parseInt(buckets[bd.key]||0);
            var pct = Math.round((cnt/total)*100);
            html += '<div style="background:#f0f9ff;border-radius:12px;padding:14px 16px;text-align:center;border:1px solid rgba(37,99,235,.1)"><div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">'+esc(bd.label)+'</div><div style="font-size:22px;font-weight:800;color:'+bd.color+'">'+cnt+'</div><div style="font-size:11px;color:#9ca3af;margin-top:2px">'+pct+'% of snapshots</div></div>';
          });
          html += '</div>';
        }
        html += sectionLabel('Buses With Long Wait (&ge;10 min)', buses.length+' bus'+(buses.length!==1?'es':''), 'linear-gradient(to bottom,#2563eb,#60a5fa)');
        if (!buses.length) {
          html += '<div class="dm-empty"><p>No buses with long waits today.</p></div>';
        } else {
          html += '<div class="dm-table-wrap"><table class="dm-table"><thead><tr><th>Bus ID</th><th>Route</th><th>Driver</th><th>Avg Delay</th><th>Status</th><th>Speed (km/h)</th><th>Last Snapshot</th></tr></thead><tbody>';
          buses.forEach(function(b){
            var delay = parseFloat(b.avg_delay_min||0);
            html += '<tr><td><strong>'+esc(b.bus_reg_no)+'</strong></td><td>'+esc(b.route_no)+'</td><td>'+esc(b.driver_name)+'</td><td><span class="dm-bd dm-bd-gold">'+delay.toFixed(1)+' min</span></td><td>'+statusBadge(b.operational_status)+'</td><td>'+parseFloat(b.speed||0).toFixed(1)+'</td><td style="color:#6b7280;font-size:12px">'+esc(b.snapshot_at)+'</td></tr>';
          });
          html += '</tbody></table></div>';
        }
        body.innerHTML = html;
      })
      .catch(function(err){ body.innerHTML = '<div class="dm-empty"><p>Could not load: '+esc(err.message)+'</p></div>'; });
  }

  document.addEventListener('keydown', function(e){
    if (e.key !== 'Escape') return;
    closeDelayedModal();
    closeRatingModal();
    closeSpeedModal();
    closeWaitModal();
  });

  })();
  </script>

<script id="analytics-data" type="application/json"><?= $analyticsJson ?? '{}' ?></script>
<?php $jsBase = __DIR__ . '/../../public/assets/js/analytics/'; $jsv = static function(string $base, string $file): string { $p = $base . $file; return '?v=' . (is_file($p) ? filemtime($p) : time()); }; ?>
<script src="/assets/js/analytics/chartCore.js<?= $jsv($jsBase,'chartCore.js') ?>"></script>
<script src="/assets/js/analytics/busStatus.js<?= $jsv($jsBase,'busStatus.js') ?>"></script>
<script src="/assets/js/analytics/revenue.js<?= $jsv($jsBase,'revenue.js') ?>"></script>
<script src="/assets/js/analytics/speedByBus.js<?= $jsv($jsBase,'speedByBus.js') ?>"></script>
<script src="/assets/js/analytics/waitTime.js<?= $jsv($jsBase,'waitTime.js') ?>"></script>
<script src="/assets/js/analytics/delayedByRoute.js<?= $jsv($jsBase,'delayedByRoute.js') ?>"></script>
<script src="/assets/js/analytics/complaintsRoute.js<?= $jsv($jsBase,'complaintsRoute.js') ?>"></script>
<script src="/assets/js/analytics/drilldown.js<?= $jsv($jsBase,'drilldown.js') ?>"></script>
<script src="/assets/js/analytics/liveFleet.js<?= $jsv($jsBase,'liveFleet.js') ?>"></script>
