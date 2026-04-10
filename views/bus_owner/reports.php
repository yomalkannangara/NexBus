<?php
  $kpi     = $kpi     ?? ['delayedToday'=>0,'avgRating'=>0,'speedViol'=>0,'longWaitPct'=>0];
  $filters = $filters ?? ['route_no'=>'','bus_reg'=>''];
  $curRno  = $filters['route_no'] ?? '';
  $curBus  = $filters['bus_reg']  ?? '';
  $hasFilter = ($curRno !== '' || $curBus !== '');
  $dq = '';
  if ($curRno !== '') $dq .= '&route_no=' . urlencode($curRno);
  if ($curBus !== '') $dq .= '&bus_reg=' . urlencode($curBus);

  // Resolve the logged-in owner's private_operator_id for JS
  $sessionOwnerId = (int)($_SESSION['user']['private_operator_id'] ?? 0);
?>
<style>
/* ═══════════════════════════════════════════════
   PERFORMANCE PAGE — Premium Redesign
   Scoped to .perf-page so owner.css is unaffected
═══════════════════════════════════════════════ */
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
  --purple: #7c3aed;
  --purple-soft: rgba(124,58,237,.1);
  --bg: #f0f2f7;
  --card: #ffffff;
  --border: rgba(0,0,0,.07);
  --shadow-sm: 0 1px 4px rgba(0,0,0,.06);
  --shadow: 0 4px 20px rgba(0,0,0,.08);
  --shadow-lg: 0 10px 40px rgba(0,0,0,.14);
  --radius: 16px;
  --radius-sm: 10px;
  --text: #111827;
  --muted: #6b7280;
  --input-bg: #f5f6fa;
  background: var(--bg);
  min-height: 100%;
  padding-bottom: 3rem;
}

/* ── Hero Banner ─────────────────────────────── */
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
.perf-hero-inner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.perf-hero-left h1 {
  margin: 0 0 6px;
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 800;
  color: #fff;
  letter-spacing: -.5px;
}
.perf-hero-left p {
  margin: 0;
  color: rgba(255,255,255,.72);
  font-size: 14px;
  font-weight: 400;
}
.perf-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 999px;
  padding: 4px 14px;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  margin-top: 10px;
  backdrop-filter: blur(8px);
}
.perf-hero-badge span {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--gold);
  display: inline-block;
  animation: pulse-dot 1.8s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.4); }
}
.perf-hero-filters {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}
.perf-filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.perf-filter-label {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255,255,255,.65);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.perf-select {
  position: relative;
}
.perf-select select {
  appearance: none;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 10px;
  color: #fff;
  font: 600 13px 'Inter', sans-serif;
  padding: 9px 34px 9px 14px;
  outline: none;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: background .2s, border-color .2s;
  min-width: 140px;
}
.perf-select select option { background: var(--pm-dark); color: #fff; }
.perf-select select:focus { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.5); }
.perf-select::after {
  content: '';
  position: absolute;
  right: 12px; top: 50%;
  transform: translateY(-50%) rotate(45deg);
  width: 7px; height: 7px;
  border-right: 2px solid rgba(255,255,255,.7);
  border-bottom: 2px solid rgba(255,255,255,.7);
  pointer-events: none;
}
.perf-clear-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 10px;
  color: rgba(255,255,255,.85);
  font: 600 12px 'Inter', sans-serif;
  padding: 9px 14px;
  cursor: pointer;
  text-decoration: none;
  transition: background .2s;
  align-self: flex-end;
}
.perf-clear-btn:hover { background: rgba(255,255,255,.2); color: #fff; }

/* ── Decorative bus icon ─────────────────────── */
.perf-hero-art {
  position: absolute;
  right: -10px; top: -10px;
  width: 180px; height: 180px;
  opacity: .04;
  pointer-events: none;
}

/* ── Section Title ───────────────────────────── */
.perf-section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 14px;
}
.perf-section-label .label-line {
  width: 4px; height: 18px;
  background: linear-gradient(to bottom, var(--pm), var(--gold));
  border-radius: 4px;
  flex-shrink: 0;
}
.perf-section-label h2 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -.2px;
}
.perf-section-label .label-badge {
  margin-left: auto;
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
  background: #f3f4f6;
  border-radius: 999px;
  padding: 2px 10px;
}

/* ── KPI Grid ────────────────────────────────── */
.perf-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 22px;
}
@media (max-width: 1100px) { .perf-kpi-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 600px)  { .perf-kpi-grid { grid-template-columns: 1fr; } }

.perf-kpi {
  background: var(--card);
  border-radius: var(--radius);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  cursor: default;
}
.perf-kpi:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.perf-kpi::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  border-radius: 3px 3px 0 0;
  background: var(--kpi-color, var(--pm));
}
.perf-kpi-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 12px;
}
.perf-kpi-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: var(--kpi-bg, var(--pm-soft));
}
.perf-kpi-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .4px;
}
.perf-kpi-value {
  font-size: 32px;
  font-weight: 800;
  color: var(--kpi-color, var(--pm));
  line-height: 1;
  letter-spacing: -1px;
  margin-bottom: 4px;
  transition: color .3s;
}
.perf-kpi-hint {
  font-size: 11.5px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 4px;
}
.perf-kpi-hint .dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--kpi-color, var(--pm));
  display: inline-block;
  flex-shrink: 0;
}

/* Live KPI Pair */
.perf-live-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  margin-bottom: 22px;
}
@media (max-width: 600px) { .perf-live-row { grid-template-columns: 1fr; } }

.perf-kpi-live {
  background: linear-gradient(135deg, var(--pm-dark) 0%, var(--pm) 100%);
  border-radius: var(--radius);
  padding: 22px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
  color: #fff;
  transition: transform .2s, box-shadow .2s;
}
.perf-kpi-live:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.perf-kpi-live::before {
  content: '';
  position: absolute;
  right: -20px; top: -20px;
  width: 100px; height: 100px;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.perf-kpi-live::after {
  content: '';
  position: absolute;
  right: 20px; bottom: -30px;
  width: 80px; height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
}
.perf-kpi-live .live-label {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,.65);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 8px;
}
.perf-kpi-live .live-value {
  font-size: 34px;
  font-weight: 800;
  color: #fff;
  line-height: 1;
  letter-spacing: -1px;
  margin-bottom: 6px;
}
.perf-kpi-live .live-hint {
  font-size: 12px;
  color: rgba(255,255,255,.6);
}
.perf-kpi-live .live-icon {
  position: absolute;
  right: 18px; top: 50%;
  transform: translateY(-50%);
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,.12);
  display: grid;
  place-items: center;
  color: rgba(255,255,255,.9);
}
.live-pulse {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,.12);
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 600;
  color: rgba(255,255,255,.85);
  margin-bottom: 8px;
}
.live-pulse-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #4ade80;
  animation: pulse-dot 1.5s ease-in-out infinite;
}

/* ── Tab Navigation ──────────────────────────── */
.perf-tabs {
  display: flex;
  gap: 4px;
  background: #fff;
  border-radius: 14px;
  padding: 6px;
  margin-bottom: 22px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  overflow-x: auto;
}
.perf-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  border-radius: 10px;
  font: 600 13px 'Inter', sans-serif;
  color: var(--muted);
  background: transparent;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: background .2s, color .2s;
}
.perf-tab:hover { background: #f3f4f6; color: var(--text); }
.perf-tab.active {
  background: var(--pm);
  color: #fff;
  box-shadow: 0 2px 8px rgba(128,20,60,.35);
}
.perf-tab .tab-count {
  background: rgba(255,255,255,.25);
  border-radius: 999px;
  padding: 1px 7px;
  font-size: 11px;
}
.perf-tab:not(.active) .tab-count { background: #f3f4f6; color: var(--muted); }

/* ── Tab Panels ──────────────────────────────── */
.perf-tab-panel { display: none; }
.perf-tab-panel.active { display: block; }

/* ── Charts Grid ─────────────────────────────── */
.perf-charts-grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0,1fr));
  gap: 16px;
  margin-bottom: 22px;
}
.perf-chart-card {
  grid-column: span 6;
  background: var(--card);
  border-radius: var(--radius);
  padding: 22px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: box-shadow .2s;
}
.perf-chart-card:hover { box-shadow: var(--shadow); }
.perf-chart-card.span-12 { grid-column: span 12; }
@media (max-width: 960px) { .perf-chart-card { grid-column: span 12; } }

.perf-chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  gap: 8px;
}
.perf-chart-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -.2px;
  margin: 0;
}
.perf-chart-detail-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: var(--pm);
  background: var(--gold-soft);
  border: 1px solid rgba(243,185,68,.3);
  border-radius: 8px;
  padding: 5px 12px;
  text-decoration: none;
  white-space: nowrap;
  transition: background .2s, transform .15s;
  flex-shrink: 0;
}
.perf-chart-detail-link:hover { background: rgba(243,185,68,.25); transform: translateX(2px); }
.perf-chart-detail-link svg { transition: transform .15s; }
.perf-chart-detail-link:hover svg { transform: translateX(3px); }

/* ── Live Fleet Table ────────────────────────── */
.perf-fleet-table-wrap {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 22px;
}
.perf-fleet-table-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #f3f4f6;
}
.perf-fleet-table-head h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}
.perf-fleet-refresh {
  font-size: 11.5px;
  color: var(--muted);
}
.perf-fleet-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.perf-fleet-table th {
  padding: 10px 16px;
  background: #fafafa;
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .4px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}
.perf-fleet-table td {
  padding: 11px 16px;
  border-bottom: 1px solid #f7f8fa;
  color: var(--text);
  font-weight: 500;
}
.perf-fleet-table tbody tr:hover { background: #fafbff; }
.perf-fleet-table tbody tr:last-child td { border-bottom: none; }
.perf-fleet-table td:nth-child(4) { text-align: right; }
.perf-fleet-table td:nth-child(5),
.perf-fleet-table td:nth-child(6) { text-align: center; }

/* Status badges */
.perf-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.perf-badge--green { background: #dcfce7; color: #15803d; }
.perf-badge--red   { background: #fee2e2; color: #b91c1c; }
.perf-badge--gray  { background: #f3f4f6; color: #6b7280; }
.perf-badge--gold  { background: #fef3c7; color: #d97706; }
.perf-badge--blue  { background: #eff6ff; color: #2563eb; }

.perf-empty-row td {
  text-align: center;
  color: var(--muted);
  padding: 32px 16px;
  font-size: 13px;
}
.perf-empty-icon {
  width: 40px; height: 40px;
  background: #f3f4f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 8px;
}

/* ── Expand button ───────────────────────────── */
.fleet-expand-row td {
  text-align: center;
  padding: 10px;
  border-top: 1px solid #f3f4f6;
}
.fleet-expand-btn {
  background: none;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 20px;
  font: 600 12px 'Inter', sans-serif;
  color: var(--muted);
  cursor: pointer;
  transition: background .2s, border-color .2s, color .2s;
}
.fleet-expand-btn:hover { background: var(--pm); border-color: var(--pm); color: #fff; }

/* ── Chart canvas sizing ─────────────────────── */
.perf-chart-card canvas {
  display: block;
  width: 100% !important;
}
.chart-legend {
  display: flex; flex-wrap: wrap; gap: 8px 14px;
  justify-content: center; margin-top: 10px;
}
.chart-legend .legend-item {
  display: inline-flex; align-items: center;
  gap: 7px; font-size: 12px; color: var(--text); font-weight: 500;
}
.chart-legend .legend-item i {
  width: 10px; height: 10px;
  border-radius: 3px; display: inline-block;
}
</style>

<div class="perf-page">

<!-- ══ Hero Banner ══════════════════════════════════════════════════ -->
<div class="perf-hero">
  <!-- Decorative bus SVG -->
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
      <div class="live-pulse">
        <span class="live-pulse-dot"></span>
        Live Analytics
      </div>
      <h1>Performance Dashboard</h1>
      <p>Real-time fleet metrics and operational intelligence for your buses</p>
    </div>

    <!-- Inline filters -->
    <form method="get" action="/B/performance" class="perf-hero-filters" id="perf-filter-form">
      <div class="perf-filter-group">
        <span class="perf-filter-label">Route</span>
        <div class="perf-select">
          <select name="route_no" onchange="this.form.submit()" id="ft-route">
            <option value="">All Routes</option>
            <?php foreach(($routes ?? []) as $r):
              $rno = htmlspecialchars($r['route_no']);
              $sel = ($curRno === $r['route_no']) ? 'selected' : '';
            ?>
              <option value="<?= $rno ?>" <?= $sel ?>><?= $rno ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="perf-filter-group">
        <span class="perf-filter-label">Bus</span>
        <div class="perf-select">
          <select name="bus_reg" onchange="this.form.submit()" id="ft-bus">
            <option value="">All Buses</option>
            <?php foreach(($buses ?? []) as $b): ?>
              <option value="<?= htmlspecialchars($b['reg_no']) ?>"
                <?= ($curBus === $b['reg_no']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['reg_no']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php if ($hasFilter): ?>
        <a href="/B/performance" class="perf-clear-btn">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Clear
        </a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ══ KPI Cards (DB-sourced) ═══════════════════════════════════════ -->
<div class="perf-section-label">
  <span class="label-line"></span>
  <h2>Fleet Performance KPIs</h2>
  <span class="label-badge">from database</span>
</div>

<div class="perf-kpi-grid">

  <!-- Delayed -->
  <div class="perf-kpi" style="--kpi-color:var(--red);--kpi-bg:var(--red-soft)">
    <div class="perf-kpi-top">
      <div>
        <div class="perf-kpi-label">Delayed Buses Today</div>
      </div>
      <div class="perf-kpi-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--red)">
          <path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1m1 12h-5V7h2v4h3z"/>
        </svg>
      </div>
    </div>
    <div class="perf-kpi-value" id="kpi-delayed"><?= (int)($kpi['delayedToday'] ?? 0) ?></div>
    <div class="perf-kpi-hint"><span class="dot"></span>Live snapshot from database</div>
  </div>

  <!-- Rating -->
  <div class="perf-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-soft)">
    <div class="perf-kpi-top">
      <div>
        <div class="perf-kpi-label">Avg Driver Rating</div>
      </div>
      <div class="perf-kpi-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--green)">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01z"/>
        </svg>
      </div>
    </div>
    <div class="perf-kpi-value" id="kpi-rating">
      <?= $kpi['avgRating'] > 0 ? number_format((float)$kpi['avgRating'],1) : '&ndash;' ?>
    </div>
    <div class="perf-kpi-hint"><span class="dot"></span>Reliability index out of 10</div>
  </div>

  <!-- Speed Violations -->
  <div class="perf-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-soft)">
    <div class="perf-kpi-top">
      <div>
        <div class="perf-kpi-label">Speed Violations</div>
      </div>
      <div class="perf-kpi-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--orange)">
          <path d="M14 3L3 14h7v7l11-11h-7z"/>
        </svg>
      </div>
    </div>
    <div class="perf-kpi-value" id="kpi-speed">
      <?= (int)($kpi['speedViol'] ?? 0) ?: '&ndash;' ?>
    </div>
    <div class="perf-kpi-hint"><span class="dot"></span>Buses over speed limit</div>
  </div>

  <!-- Long Wait -->
  <div class="perf-kpi" style="--kpi-color:var(--blue);--kpi-bg:var(--blue-soft)">
    <div class="perf-kpi-top">
      <div>
        <div class="perf-kpi-label">Long Wait Times</div>
      </div>
      <div class="perf-kpi-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--blue)">
          <path d="M16 6h5v5h-2V9.41l-6.29 6.3l-4-4L2 18.41L.59 17L8.71 8.88l4 4L19.59 6z"/>
        </svg>
      </div>
    </div>
    <div class="perf-kpi-value" id="kpi-wait"><?= (int)($kpi['longWaitPct'] ?? 0) ?>%</div>
    <div class="perf-kpi-hint"><span class="dot"></span>Snapshots with delay &gt;10 min</div>
  </div>

</div>

<!-- ══ Live Fleet KPIs ═══════════════════════════════════════════════ -->
<div class="perf-section-label">
  <span class="label-line"></span>
  <h2>Live Fleet Status</h2>
  <span class="label-badge" id="live-updated-at">Fetching&hellip;</span>
</div>

<div class="perf-live-row">
  <div class="perf-kpi-live">
    <div class="live-label">Active Buses Now</div>
    <div class="live-value" id="kpi-active-buses">&ndash;</div>
    <div class="live-hint">Buses reporting live GPS</div>
    <div class="live-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,.9)">
        <path d="M17 8C8 10 5.9 16.1 3 19h3s2.5-4 9.5-4.5c-1.7 1.1-3.5 3-4.5 4.5h3C15 17 17 14 21 12c-1-1-2-2-2-4z"/>
      </svg>
    </div>
  </div>
  <div class="perf-kpi-live" style="background:linear-gradient(135deg,#14532d 0%,#16a34a 100%)">
    <div class="live-label">Average Fleet Speed</div>
    <div class="live-value" id="kpi-avg-speed">&ndash;</div>
    <div class="live-hint">Fleet average right now</div>
    <div class="live-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,.9)">
        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m1 14.93V15h-2v1.93A8 8 0 0 1 4.07 11H6V9H4.07A8 8 0 0 1 11 4.07V6h2V4.07A8 8 0 0 1 19.93 11H18v2h1.93A8 8 0 0 1 13 16.93z"/>
      </svg>
    </div>
  </div>
</div>

<!-- ══ Tabs ══════════════════════════════════════════════════════════ -->
<div class="perf-tabs" role="tablist">
  <button class="perf-tab active" data-tab="live" role="tab">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
    Live View
  </button>
  <button class="perf-tab" data-tab="analytics" role="tab">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3v18h18V3zm15 4h-4v4h4zm-6 0H8v4h4zm6 6h-4v4h4zm-6 0H8v4h4z"/></svg>
    Analytics Charts
  </button>
  <button class="perf-tab" data-tab="fleet" role="tab">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
    Fleet Table
  </button>
</div>

<!-- ══ Tab: Live View ════════════════════════════════════════════════ -->
<div class="perf-tab-panel active" id="tab-live">
  <div class="perf-charts-grid">
    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Live Bus Status</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=live_status<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="liveStatusChart"></canvas>
    </div>
    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Live Fleet Speed</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=live_speed<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="liveSpeedChart"></canvas>
    </div>
  </div>
</div>

<!-- ══ Tab: Analytics Charts ════════════════════════════════════════ -->
<div class="perf-tab-panel" id="tab-analytics">
  <div class="perf-charts-grid">

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Bus Status Distribution</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=bus_status<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="busStatusChart" data-drill-key="bus_status" data-drill-base="/B/reports/details"></canvas>
    </div>

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Delayed Buses by Route</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=delayed_by_route<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="delayedByRouteChart" data-drill-key="delayed_by_route" data-drill-base="/B/reports/details"></canvas>
    </div>

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Speed Violations by Bus</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=speed_by_bus<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="speedByBusChart" data-drill-key="speed_by_bus" data-drill-base="/B/reports/details"></canvas>
    </div>

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Revenue Overview</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=revenue<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="revenueChart" data-drill-key="revenue" data-drill-base="/B/reports/details"></canvas>
    </div>

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Bus Wait Time Distribution</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=wait_time<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="waitTimeChart" data-drill-key="wait_time" data-drill-base="/B/reports/details"></canvas>
    </div>

    <div class="perf-chart-card">
      <div class="perf-chart-header">
        <h3 class="perf-chart-title">Complaints by Route</h3>
        <a class="perf-chart-detail-link" href="/B/reports/details?chart=complaints_by_route<?= $dq ?>">
          Details
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <canvas id="complaintsRouteChart" data-drill-key="complaints_by_route" data-drill-base="/B/reports/details"></canvas>
    </div>

  </div>
</div>

<!-- ══ Tab: Fleet Table ══════════════════════════════════════════════ -->
<div class="perf-tab-panel" id="tab-fleet">
  <div class="perf-fleet-table-wrap">
    <div class="perf-fleet-table-head">
      <h3>Live Bus Fleet</h3>
      <span class="perf-fleet-refresh" id="live-updated-at-table">&nbsp;</span>
    </div>
    <div style="overflow-x:auto">
      <table class="perf-fleet-table">
        <thead>
          <tr>
            <th>Bus ID</th>
            <th>Route</th>
            <th>Operator</th>
            <th style="text-align:right">Speed (km/h)</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:center">Location</th>
          </tr>
        </thead>
        <tbody id="live-route-tbody">
          <tr class="perf-empty-row">
            <td colspan="6">
              <div class="perf-empty-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#9ca3af"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-10 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
              </div>
              Loading live bus data&hellip;
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div><!-- /.perf-page -->

<!-- Server data for charts -->
<script id="analytics-data" type="application/json">
<?= $analyticsJson ?? '{}' ?>
</script>

<!-- Override liveFleet buildRow to use new badge classes -->
<script>
(function(){
  'use strict';
  // Patch NB.setLegend appearance for perf-page (no-op needed, keeps chart-legend class)

  /* ── Tab switching ── */
  document.querySelectorAll('.perf-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = btn.dataset.tab;
      document.querySelectorAll('.perf-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.perf-tab-panel').forEach(function(p){ p.classList.remove('active'); });
      btn.classList.add('active');
      var panel = document.getElementById('tab-' + target);
      if(panel){ panel.classList.add('active'); }

      // Re-trigger resize so canvases in newly-visible panels render
      setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 50);
    });
  });

  /* ── Override fleet table row builder for new badge styles ── */
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
      ? '<a href="https://maps.google.com/?q='+b.lat+','+b.lng+'" target="_blank" class="perf-badge perf-badge--blue" style="text-decoration:none">📍 Map</a>'
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

  /* ── Patch fleet table expander to use new style ── */
  window._fleetExpand = function(){
    var extras = document.querySelectorAll('tr.fleet-extra');
    var btn    = document.getElementById('fleet-expander');
    var shown  = extras.length && extras[0].style.display !== 'none';
    extras.forEach(function(r){ r.style.display = shown ? 'none' : ''; });
    if(btn){
      var b = btn.querySelector('.fleet-expand-btn');
      if(b) b.textContent = shown
        ? 'Show ' + extras.length + ' more ▼'
        : 'Collapse ▲';
    }
  };
})();
</script>

<!-- Analytics charts (unchanged shared scripts) -->
<script src="/assets/js/analytics/chartCore.js"></script>
<script src="/assets/js/analytics/busStatus.js"></script>
<script src="/assets/js/analytics/revenue.js"></script>
<script src="/assets/js/analytics/speedByBus.js"></script>
<script src="/assets/js/analytics/waitTime.js"></script>
<script src="/assets/js/analytics/delayedByRoute.js"></script>
<script src="/assets/js/analytics/complaintsRoute.js"></script>
<script src="/assets/js/analytics/drilldown.js"></script>

<!--
  OWNER-SCOPED LIVE FLEET HANDLER
  ─────────────────────────────────────────────────────────────────
  This is a completely self-contained script that:
  1. Polls /B/live  — a server-side authenticated endpoint that returns
     ONLY the buses belonging to this owner (JOIN private_buses ON
     private_operator_id = <session value>). Zero global bus leakage.
  2. Does NOT use liveFleet.js (shared/cached). This eliminates any
     browser-cache contamination from the old endpoint.
  3. Updates: kpi-active-buses, kpi-avg-speed, live-updated-at,
     live-updated-at-table, liveStatusChart, liveSpeedChart,
     live-route-tbody.
  ─────────────────────────────────────────────────────────────────
-->
<script>
(function () {
  'use strict';

  /* ── Constants ──────────────────────────────────────────────── */
  const OWNER_LIVE_API = '/B/live';          // owner-scoped, authenticated
  const SPEED_LIMIT    = 60;                 // km/h over which bus is "speeding"
  const REFRESH_MS     = 15000;             // poll every 15 s
  const SHOW_LIMIT     = 8;                 // rows before "show more"
  const NB             = window.NBCharts;   // canvas helper from chartCore.js

  /* ── DOM helpers ────────────────────────────────────────────── */
  function el(id) { return document.getElementById(id); }
  function setText(id, v) { var e = el(id); if (e) e.textContent = v; }
  function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── Update KPI cards ───────────────────────────────────────── */
  function updateKPIs(buses) {
    var total    = buses.length;
    var avgSpeed = total
      ? (buses.reduce(function(s,b){ return s + (+b.speedKmh||0); }, 0) / total).toFixed(1)
      : 0;
    setText('kpi-active-buses', total > 0 ? total : '0');
    setText('kpi-avg-speed',    total > 0 ? avgSpeed + ' km/h' : '0 km/h');
    var ts = 'Live · ' + new Date().toLocaleTimeString();
    setText('live-updated-at', ts);
    setText('live-updated-at-table', total + ' bus' + (total !== 1 ? 'es' : '') + ' · ' + new Date().toLocaleTimeString());
  }

  /* ── Live Status donut ──────────────────────────────────────── */
  function drawStatusChart(buses) {
    var cvs = el('liveStatusChart');
    if (!cvs || !NB) return;
    var total    = buses.length;
    var speeding = buses.filter(function(b){ return b.speedKmh > SPEED_LIMIT; }).length;
    var normal   = total - speeding;
    var list = [
      { label: 'Normal',   value: normal,   color: NB.colors.green },
      { label: 'Speeding', value: speeding,  color: NB.colors.red   }
    ];
    NB.observe(cvs, 7/4, function(c) {
      var ctx = c.ctx, W = c.W, H = c.H;
      ctx.clearRect(0,0,W,H);
      var cx = W/2, cy = H/2;
      var R  = Math.min(W,H)*0.36;
      var r  = R*0.62;
      if (!total) {
        ctx.fillStyle='#9ca3af'; ctx.font='13px Inter,ui-sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText('No live tracking data for your fleet', cx, cy); return;
      }
      var t  = list.reduce(function(s,d){ return s+(d.value||0); }, 0) || 1;
      var a0 = -Math.PI/2;
      ctx.shadowColor='rgba(0,0,0,.15)'; ctx.shadowBlur=12;
      list.forEach(function(seg) {
        var ang = (seg.value/t)*Math.PI*2;
        if (ang <= 0) return;
        ctx.beginPath(); ctx.moveTo(cx,cy);
        ctx.arc(cx,cy,R,a0,a0+ang);
        ctx.closePath(); ctx.fillStyle=seg.color; ctx.fill();
        a0 += ang;
      });
      ctx.shadowBlur=0;
      ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2);
      ctx.fillStyle='#ffffff'; ctx.fill();
      ctx.textAlign='center'; ctx.textBaseline='middle';
      ctx.fillStyle='#111827'; ctx.font='bold 22px Inter,ui-sans-serif';
      ctx.fillText(total, cx, cy-8);
      ctx.fillStyle='#6b7280'; ctx.font='12px Inter,ui-sans-serif';
      ctx.fillText('Your Fleet', cx, cy+10);
      NB.setLegend(cvs.parentNode, list.map(function(d){
        return { label: d.label+' ('+d.value+')', color: d.color };
      }));
    });
  }

  /* ── Live Speed bar chart ───────────────────────────────────── */
  function drawSpeedChart(buses) {
    var cvs = el('liveSpeedChart');
    if (!cvs || !NB) return;
    var sorted = buses.slice().sort(function(a,b){ return b.speedKmh-a.speedKmh; }).slice(0,12);
    var labels = sorted.map(function(b){ return b.busId; });
    var vals   = sorted.map(function(b){ return +b.speedKmh||0; });
    var max    = Math.max(SPEED_LIMIT+20, Math.ceil(Math.max.apply(null,[SPEED_LIMIT+10].concat(vals))/10)*10);
    NB.observe(cvs, 7/4, function(c) {
      var ctx=c.ctx, W=c.W, H=c.H;
      ctx.clearRect(0,0,W,H);
      if (!labels.length) {
        ctx.fillStyle='#9ca3af'; ctx.font='14px Inter,ui-sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText('No live speed data for your fleet', W/2, H/2); return;
      }
      var pad={l:46,r:16,t:24,b:54};
      var iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
      var barW=Math.min(32,(iw/labels.length)*0.6);
      ctx.strokeStyle=NB.colors.grid; ctx.lineWidth=1; ctx.setLineDash([3,6]);
      for(var k=0;k<=5;k++){var y=pad.t+ih*(k/5);ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(W-pad.r,y);ctx.stroke();}
      ctx.setLineDash([]);
      var limitY=pad.t+ih-(SPEED_LIMIT/max)*ih;
      ctx.strokeStyle='#ef4444'; ctx.lineWidth=1.5; ctx.setLineDash([5,4]);
      ctx.beginPath(); ctx.moveTo(pad.l,limitY); ctx.lineTo(W-pad.r,limitY); ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle='#ef4444'; ctx.font='10px Inter,ui-sans-serif'; ctx.textAlign='left';
      ctx.fillText(SPEED_LIMIT+' km/h limit',pad.l+4,limitY-4);
      vals.forEach(function(v,i){
        var slotW=iw/labels.length;
        var x=pad.l+i*slotW+(slotW-barW)/2;
        var h=(v/max)*ih, y=pad.t+ih-h, r=5;
        var over=v>SPEED_LIMIT;
        var g=ctx.createLinearGradient(0,y,0,y+h);
        g.addColorStop(0,over?'#fca5a5':'#86efac');
        g.addColorStop(1,over?NB.colors.red:NB.colors.green);
        ctx.fillStyle=g;
        ctx.beginPath();
        ctx.moveTo(x,y+r); ctx.arcTo(x,y,x+r,y,r);
        ctx.lineTo(x+barW-r,y); ctx.arcTo(x+barW,y,x+barW,y+r,r);
        ctx.lineTo(x+barW,y+h); ctx.lineTo(x,y+h); ctx.closePath(); ctx.fill();
        ctx.fillStyle='#374151'; ctx.font='bold 10px Inter,ui-sans-serif'; ctx.textAlign='center';
        ctx.fillText(v, x+barW/2, Math.max(y-2,pad.t+10));
      });
      ctx.fillStyle='#6b7280'; ctx.font='11px Inter,ui-sans-serif'; ctx.textAlign='center';
      labels.forEach(function(lb,i){
        var slotW=iw/labels.length, x=pad.l+i*slotW+slotW/2;
        ctx.save(); ctx.translate(x,H-6); ctx.rotate(-Math.PI/6);
        ctx.fillText(lb,0,0); ctx.restore();
      });
      ctx.textAlign='right'; ctx.fillStyle='#6b7280';
      var step=Math.max(10,Math.round(max/6/10)*10);
      for(var yv=0;yv<=max;yv+=step){
        var yy=pad.t+ih-(yv/max)*ih;
        ctx.fillText(yv,pad.l-5,yy+4);
      }
      NB.setLegend(cvs.parentNode,[
        {label:'Normal',color:NB.colors.green},
        {label:'Over '+SPEED_LIMIT+' km/h',color:NB.colors.red}
      ]);
    });
  }

  /* ── Fleet table row builder ────────────────────────────────── */
  function buildFleetRow(b) {
    var over = (+b.speedKmh||0) > SPEED_LIMIT;
    var spBadge = over
      ? '<span class="perf-badge perf-badge--red">⚡ '+b.speedKmh+' km/h</span>'
      : '<span class="perf-badge perf-badge--green">'+b.speedKmh+' km/h</span>';
    var status    = over ? 'Speeding' : escHtml(b.operationalStatus||'On Time');
    var statusCls = over ? 'perf-badge--red' : (status==='Delayed'?'perf-badge--gold':'perf-badge--green');
    var routeNo   = b.routeNo ? escHtml(String(b.routeNo)) : '—';
    var locLink   = (b.lat && b.lng)
      ? '<a href="https://maps.google.com/?q='+b.lat+','+b.lng+'" target="_blank" class="perf-badge perf-badge--blue" style="text-decoration:none">📍 Map</a>'
      : '<span class="perf-badge perf-badge--gray">—</span>';
    return '<tr'+(over?' style="background:#fff5f5"':'')+'>'+
      '<td><strong>'+escHtml(b.busId)+'</strong></td>'+
      '<td>'+routeNo+'</td>'+
      '<td>Your Fleet</td>'+
      '<td>'+spBadge+'</td>'+
      '<td><span class="perf-badge '+statusCls+'">'+status+'</span></td>'+
      '<td>'+locLink+'</td>'+
      '</tr>';
  }

  /* ── Update fleet table ─────────────────────────────────────── */
  function updateFleetTable(buses) {
    var tbody = el('live-route-tbody');
    if (!tbody) return;
    if (!buses.length) {
      tbody.innerHTML =
        '<tr class="perf-empty-row"><td colspan="6">'+
        '<div class="perf-empty-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#9ca3af"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>'+
        'No live tracking data found for your fleet.</td></tr>';
      return;
    }
    var visible = buses.slice(0, SHOW_LIMIT);
    var hidden  = buses.slice(SHOW_LIMIT);
    var html = visible.map(buildFleetRow).join('');
    if (hidden.length) {
      html += hidden.map(function(b){
        return buildFleetRow(b).replace('<tr', '<tr class="fleet-extra" style="display:none"');
      }).join('');
      html += '<tr id="fleet-expander" class="fleet-expand-row">'+
        '<td colspan="6">'+
        '<button class="fleet-expand-btn" onclick="window._fleetExpand()">'+
        'Show '+hidden.length+' more ▼</button>'+
        '</td></tr>';
    }
    tbody.innerHTML = html;
  }

  /* ── Show "no data" state ───────────────────────────────────── */
  function showNoData(reason) {
    setText('kpi-active-buses', '0');
    setText('kpi-avg-speed', '0 km/h');
    setText('live-updated-at', 'No data · ' + new Date().toLocaleTimeString());
    setText('live-updated-at-table', 'No data · ' + new Date().toLocaleTimeString());
    var tbody = el('live-route-tbody');
    if (tbody) tbody.innerHTML =
      '<tr class="perf-empty-row"><td colspan="6">'+
      '<div class="perf-empty-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#9ca3af"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.25 2.52.77-1.28-3.52-2.09V8z"/></svg></div>'+
      (reason || 'No live tracking data for your fleet buses.') +
      '</td></tr>';
  }

  /* ── Main fetch-and-render cycle ────────────────────────────── */
  function fetchAndRender() {
    fetch(OWNER_LIVE_API + '?_=' + Date.now())
      .then(function(r) {
        if (r.status === 403) throw new Error('No operator context (session may have expired)');
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function(buses) {
        if (!Array.isArray(buses)) {
          showNoData('Unexpected response from live endpoint.');
          return;
        }
        updateKPIs(buses);
        drawStatusChart(buses);
        drawSpeedChart(buses);
        updateFleetTable(buses);
      })
      .catch(function(err) {
        console.warn('[ownerLiveFleet] ' + err.message);
        showNoData('Could not load live data: ' + err.message);
      });
  }

  /* ── Expand/collapse extra rows ─────────────────────────────── */
  window._fleetExpand = function() {
    var extras = document.querySelectorAll('tr.fleet-extra');
    var btnRow  = el('fleet-expander');
    var shown   = extras.length && extras[0].style.display !== 'none';
    extras.forEach(function(r){ r.style.display = shown ? 'none' : ''; });
    if (btnRow) {
      var b = btnRow.querySelector('.fleet-expand-btn');
      if (b) b.textContent = shown
        ? 'Show ' + extras.length + ' more ▼'
        : 'Collapse ▲';
    }
  };

  /* ── Tab switching ──────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.perf-tab').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var target = btn.dataset.tab;
        document.querySelectorAll('.perf-tab').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.perf-tab-panel').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        var panel = el('tab-' + target);
        if (panel) panel.classList.add('active');
        setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 50);
      });
    });

    /* Boot live fleet immediately, then poll */
    fetchAndRender();
    setInterval(fetchAndRender, REFRESH_MS);
  });

})();
</script>