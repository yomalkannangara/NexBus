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
  --bg: #f0f2f7;
  --card: #ffffff;
  --border: rgba(0,0,0,.07);
  --shadow-sm: 0 1px 4px rgba(0,0,0,.06);
  --shadow: 0 4px 20px rgba(0,0,0,.08);
  --shadow-lg: 0 10px 40px rgba(0,0,0,.14);
  --radius: 16px;
  --text: #111827;
  --muted: #6b7280;
  background: var(--bg);
  min-height: 100%;
  padding-bottom: 3rem;
}

.perf-hero {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius);
  padding: 32px 36px;
  background: linear-gradient(130deg, var(--pm-dark) 0%, var(--pm) 45%, #b01e4e 100%);
  margin-bottom: 24px;
  box-shadow: var(--shadow-lg);
}
.perf-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 80% -20%, rgba(243,185,68,.25) 0%, transparent 60%),
    radial-gradient(ellipse at -10% 110%, rgba(255,255,255,.08) 0%, transparent 50%);
  pointer-events: none;
}
.perf-hero-inner { position: relative; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.perf-hero-left h1 { margin:0 0 6px; font-size:clamp(22px, 3vw, 30px); font-weight:800; color:#fff; letter-spacing:-.5px; }
.perf-hero-left p { margin:0; color:rgba(255,255,255,.72); font-size:14px; }
.perf-hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); border-radius:999px; padding:4px 14px; color:#fff; font-size:12px; font-weight:600; margin-top:10px; backdrop-filter:blur(8px); }
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

.perf-section-label { display:flex; align-items:center; gap:10px; margin:0 0 14px; }
.perf-section-label .label-line { width:4px; height:18px; background:linear-gradient(to bottom, var(--pm), var(--gold)); border-radius:4px; flex-shrink:0; }
.perf-section-label h2 { margin:0; font-size:15px; font-weight:700; color:var(--text); letter-spacing:-.2px; }
.perf-section-label .label-badge { margin-left:auto; font-size:11px; font-weight:600; color:var(--muted); background:#f3f4f6; border-radius:999px; padding:2px 10px; }

.perf-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
@media (max-width:1100px) { .perf-kpi-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px) { .perf-kpi-grid { grid-template-columns:1fr; } }
.perf-kpi { background: radial-gradient(circle at 100% -25%, color-mix(in srgb, var(--kpi-color, var(--pm)) 10%, #fff 90%) 0%, transparent 44%), linear-gradient(180deg, #ffffff 0%, #fcfdff 100%); border-radius:var(--radius); padding:18px 18px 16px; box-shadow:var(--shadow-sm); border:1px solid color-mix(in srgb, var(--kpi-color, var(--pm)) 12%, #d7dce3 88%); position:relative; overflow:hidden; isolation:isolate; transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; cursor:pointer; }
.perf-kpi:hover { transform:translateY(-4px); box-shadow:0 14px 30px rgba(17,24,39,.12); border-color: color-mix(in srgb, var(--kpi-color, var(--pm)) 28%, #d7dce3 72%); }
.perf-kpi::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; border-radius:3px 3px 0 0; background:linear-gradient(90deg, var(--kpi-color, var(--pm)) 0%, color-mix(in srgb, var(--kpi-color, var(--pm)) 65%, #ffffff 35%) 100%); }
.perf-kpi::after { content:''; position:absolute; inset:auto -26px -34px auto; width:110px; height:110px; border-radius:50%; background:radial-gradient(circle, color-mix(in srgb, var(--kpi-color, var(--pm)) 14%, #ffffff 86%) 0%, transparent 72%); pointer-events:none; z-index:-1; }
.perf-kpi-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; }
.perf-kpi-icon { width:44px; height:44px; border-radius:14px; display:grid; place-items:center; background:var(--kpi-bg, rgba(128,20,60,.08)); border:1px solid color-mix(in srgb, var(--kpi-color, var(--pm)) 14%, #fff 86%); box-shadow:inset 0 1px 0 rgba(255,255,255,.55); }
.perf-kpi-label { font-size:11px; font-weight:700; color:#5b6474; text-transform:uppercase; letter-spacing:.7px; line-height:1.3; }
.perf-kpi-value { font-size:clamp(34px, 3vw, 40px); font-weight:800; color:var(--kpi-color, var(--pm)); line-height:.95; letter-spacing:-1.2px; margin-bottom:8px; }
.perf-kpi-hint { font-size:12px; color:#667085; display:flex; align-items:flex-start; justify-content:space-between; gap:8px; line-height:1.3; }
.perf-kpi-hint .dot { width:6px; height:6px; border-radius:50%; background:var(--kpi-color, var(--pm)); display:inline-block; flex-shrink:0; margin-top:5px; }
.perf-kpi-hint .kpi-click-hint { margin-left:auto; color:var(--kpi-color, var(--pm)); font-weight:700; white-space:nowrap; font-size:11px; }

.perf-live-row { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:22px; }
@media (max-width:600px) { .perf-live-row { grid-template-columns:1fr; } }
.perf-kpi-live { background:linear-gradient(135deg, var(--pm-dark) 0%, var(--pm) 100%); border-radius:var(--radius); padding:22px; box-shadow:var(--shadow); position:relative; overflow:hidden; color:#fff; }
.perf-kpi-live::before { content:''; position:absolute; right:-20px; top:-20px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.05); }
.perf-kpi-live::after { content:''; position:absolute; right:20px; bottom:-30px; width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.04); }
.perf-kpi-live .live-label { font-size:12px; font-weight:600; color:rgba(255,255,255,.65); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.perf-kpi-live .live-value { font-size:34px; font-weight:800; color:#fff; line-height:1; letter-spacing:-1px; margin-bottom:6px; }
.perf-kpi-live .live-hint { font-size:12px; color:rgba(255,255,255,.6); }
.perf-kpi-live .live-icon { position:absolute; right:18px; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,.12); display:grid; place-items:center; color:rgba(255,255,255,.9); }
.live-pulse { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border-radius:999px; padding:3px 10px; font-size:11px; font-weight:600; color:rgba(255,255,255,.85); margin-bottom:8px; }
.live-pulse-dot { width:6px; height:6px; border-radius:50%; background:#4ade80; animation:pulse-dot 1.5s ease-in-out infinite; }

.perf-tabs { display:flex; gap:4px; background:#fff; border-radius:14px; padding:6px; margin-bottom:22px; box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow-x:auto; }
.perf-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:10px; font:600 13px 'Inter', sans-serif; color:var(--muted); background:transparent; border:none; cursor:pointer; white-space:nowrap; }
.perf-tab:hover { background:#f3f4f6; color:var(--text); }
.perf-tab.active { background:var(--pm); color:#fff; box-shadow:0 2px 8px rgba(128,20,60,.35); }
.perf-tab .tab-count { background:rgba(255,255,255,.25); border-radius:999px; padding:1px 7px; font-size:11px; }
.perf-tab:not(.active) .tab-count { background:#f3f4f6; color:var(--muted); }
.perf-tab-panel { display:none; }
.perf-tab-panel.active { display:block; }

.perf-charts-grid { display:grid; grid-template-columns:repeat(12, minmax(0,1fr)); gap:16px; margin-bottom:22px; }
.perf-chart-card { grid-column:span 6; background:var(--card); border-radius:var(--radius); padding:22px; box-shadow:var(--shadow-sm); border:1px solid var(--border); position:relative; overflow:hidden; }
.perf-chart-card.span-12 { grid-column:span 12; }
@media (max-width:960px) { .perf-chart-card { grid-column:span 12; } }
.perf-chart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:8px; }
.perf-chart-title { font-size:14px; font-weight:700; color:var(--text); margin:0; }
.perf-chart-detail-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--pm); background:var(--gold-soft); border:1px solid rgba(243,185,68,.3); border-radius:8px; padding:5px 12px; text-decoration:none; white-space:nowrap; }
.perf-chart-detail-link:hover { background:rgba(243,185,68,.25); }
.perf-chart-card canvas { display:block; width:100% !important; }

.perf-fleet-table-wrap { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; margin-bottom:22px; }
.perf-fleet-table-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f3f4f6; }
.perf-fleet-table-head h3 { margin:0; font-size:14px; font-weight:700; color:var(--text); }
.perf-fleet-refresh { font-size:11.5px; color:var(--muted); }
.perf-fleet-table { width:100%; border-collapse:collapse; font-size:13px; }
.perf-fleet-table th { padding:10px 16px; background:#fafafa; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; text-align:left; border-bottom:1px solid #f0f0f0; }
.perf-fleet-table td { padding:11px 16px; border-bottom:1px solid #f7f8fa; color:var(--text); font-weight:500; }
.perf-fleet-table tbody tr:hover { background:#fafbff; }
.perf-fleet-table tbody tr:last-child td { border-bottom:none; }
.perf-fleet-table td:nth-child(4) { text-align:right; }
.perf-fleet-table td:nth-child(5), .perf-fleet-table td:nth-child(6) { text-align:center; }
.perf-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight:600; white-space:nowrap; }
.perf-badge--green { background:#dcfce7; color:#15803d; }
.perf-badge--red { background:#fee2e2; color:#b91c1c; }
.perf-badge--gray { background:#f3f4f6; color:#6b7280; }
.perf-badge--gold { background:#fef3c7; color:#d97706; }
.perf-badge--blue { background:#eff6ff; color:#2563eb; }
.perf-empty-row td { text-align:center; color:var(--muted); padding:32px 16px; font-size:13px; }
.perf-empty-icon { width:40px; height:40px; background:#f3f4f6; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; }
.fleet-expand-row td { text-align:center; padding:10px; border-top:1px solid #f3f4f6; }
.fleet-expand-btn { background:none; border:1px solid #e5e7eb; border-radius:8px; padding:6px 20px; font:600 12px 'Inter', sans-serif; color:var(--muted); cursor:pointer; }

.chart-legend { display:flex; flex-wrap:wrap; gap:8px 14px; justify-content:center; margin-top:10px; }
.chart-legend .legend-item { display:inline-flex; align-items:center; gap:7px; font-size:12px; color:var(--text); font-weight:500; }
.chart-legend .legend-item i { width:10px; height:10px; border-radius:3px; display:inline-block; }
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
    <div class="perf-kpi" id="kpi-delayed-card" style="--kpi-color:var(--red);--kpi-bg:var(--red-soft)">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Delayed Buses Today</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--red)"><path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-delayed"><?= (int)($kpi['delayedToday'] ?? 0) ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Live snapshot from database</div>
    </div>

    <div class="perf-kpi" id="kpi-rating-card" style="--kpi-color:var(--green);--kpi-bg:var(--green-soft)">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Avg Driver Rating</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--green)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-rating"><?= ($kpi['avgRating'] > 0) ? number_format((float)$kpi['avgRating'],1) : '&ndash;' ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Composite reliability score out of 10</div>
    </div>

    <div class="perf-kpi" id="kpi-speed-card" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-soft)">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Speed Violations</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--orange)"><path d="M14 3L3 14h7v7l11-11h-7z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-speed"><?= (int)($kpi['speedViol'] ?? 0) ?: '&ndash;' ?></div>
      <div class="perf-kpi-hint"><span class="dot"></span>Buses over speed limit</div>
    </div>

    <div class="perf-kpi" id="kpi-wait-card" style="--kpi-color:var(--blue);--kpi-bg:var(--blue-soft)">
      <div class="perf-kpi-top"><div><div class="perf-kpi-label">Long Wait Times</div></div><div class="perf-kpi-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--blue)"><path d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/></svg></div></div>
      <div class="perf-kpi-value" id="kpi-wait"><?= (int)($kpi['longWaitPct'] ?? 0) ?>%</div>
      <div class="perf-kpi-hint"><span class="dot"></span>Snapshots with delay >10 min</div>
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

  function tabButtons() { return document.querySelectorAll('.perf-tab'); }
  function tabPanels() { return document.querySelectorAll('.perf-tab-panel'); }

  tabButtons().forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = btn.getAttribute('data-tab');
      tabButtons().forEach(function(b){ b.classList.toggle('active', b === btn); });
      tabPanels().forEach(function(p){ p.classList.toggle('active', p.id === 'tab-' + target); });
    });
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
