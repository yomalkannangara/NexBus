/**
 * liveFleet.js – polls /live/buses/pull (fetches from external API with
 * 10-second file cache, enriches with DB metadata),
 * applies URL-param filters (route_no, depot_id, owner_id),
 * and updates KPI cards, speed chart, status donut, live fleet table.
 *
 * Page-level override: set window._NEXBUS_LIVE_API before loading this
 * script to use a different endpoint (e.g. /B/live for owner-scoped data).
 */
(function () {
  'use strict';

  // Allow pages to override the API endpoint (e.g. bus owner performance page
  // uses /B/live which is scoped server-side to the logged-in operator).
  const API         = (typeof window._NEXBUS_LIVE_API === 'string' && window._NEXBUS_LIVE_API)
                        ? window._NEXBUS_LIVE_API
                        : '/live/buses/pull';
  const SPEED_LIMIT = 60;
  const REFRESH_MS  = 15000;
  const NB          = window.NBCharts;

  /* ── read active filters from URL ───────────────────────────── */
  const _p         = new URLSearchParams(window.location.search);
  const F_ROUTE    = (_p.get('route_no')  || '').trim();
  const F_DEPOT_RAW = (_p.get('depot_id') || '').trim();
  // Owner filter: prefer window._NEXBUS_OWNER_ID (server-injected) then URL param
  const F_OWNER_RAW = (
    (window._NEXBUS_OWNER_ID && String(window._NEXBUS_OWNER_ID) !== '0')
      ? String(window._NEXBUS_OWNER_ID)
      : (_p.get('owner_id') || '').trim()
  );
  const F_DEPOT     = (/^\d+$/.test(F_DEPOT_RAW) && +F_DEPOT_RAW > 0) ? String(+F_DEPOT_RAW) : '';
  const F_OWNER     = (/^\d+$/.test(F_OWNER_RAW) && +F_OWNER_RAW > 0) ? String(+F_OWNER_RAW) : '';

  function el(id) { return document.getElementById(id); }

  /* ── filter live buses client-side ──────────────────────────── */
  function normalizeRouteValue(v) {
    const raw = String(v ?? '').trim();
    if (!raw) return '';
    const digits = raw.replace(/\D+/g, '');
    if (digits) return String(parseInt(digits, 10));
    return raw.toLowerCase();
  }

  function applyFilters(buses) {
    let r = buses;
    if (F_ROUTE) {
      const norm = normalizeRouteValue(F_ROUTE);
      r = r.filter(b => {
        const route = b.routeNo ?? b.route_no ?? b.route ?? b.routeNumber ?? '';
        return normalizeRouteValue(route) === norm;
      });
    }
    if (F_DEPOT) {
      r = r.filter(b => String(b.depotId ?? b.depot_id ?? '') === F_DEPOT);
    }
    if (F_OWNER) {
      r = r.filter(b => String(b.ownerId ?? b.owner_id ?? '') === F_OWNER);
    }
    return r;
  }

  /* ── KPIs ────────────────────────────────────────────────────── */
  function updateKPIs(buses) {
    const total    = buses.length;
    const avgSpeed = total
      ? (buses.reduce((s, b) => s + (+b.speedKmh || 0), 0) / total).toFixed(1)
      : '–';

    if (el('kpi-active-buses')) el('kpi-active-buses').textContent = total;
    // kpi-avg-speed is the live fleet average
    if (el('kpi-avg-speed'))    el('kpi-avg-speed').textContent    = avgSpeed + ' km/h';
    if (el('live-updated-at'))  el('live-updated-at').textContent  = 'Live · ' + new Date().toLocaleTimeString();
    const tbl = el('live-updated-at-table');
    if (tbl) tbl.textContent = total + ' bus' + (total !== 1 ? 'es' : '') + ' · ' + new Date().toLocaleTimeString();
  }

  /* ── live fleet speed bar chart ─────────────────────────────── */
  function drawSpeedChart(buses) {
    const cvs = el('liveSpeedChart');
    if (!cvs || !NB) return;

    const sorted = [...buses].sort((a, b) => b.speedKmh - a.speedKmh).slice(0, 12);
    const labels = sorted.map(b => b.busId);
    const vals   = sorted.map(b => +b.speedKmh || 0);
    const max    = Math.max(SPEED_LIMIT + 20, Math.ceil(Math.max(...vals, SPEED_LIMIT + 10) / 10) * 10);

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      if (!labels.length) {
        ctx.fillStyle='#9ca3af'; ctx.font='14px ui-sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText('No buses match current filter', W/2, H/2); return;
      }
      const pad = { l: 46, r: 16, t: 24, b: 54 };
      const iw  = W - pad.l - pad.r, ih = H - pad.t - pad.b;
      const barW = Math.min(32, (iw / labels.length) * 0.6);

      ctx.strokeStyle = NB.colors.grid; ctx.lineWidth = 1; ctx.setLineDash([3, 6]);
      for (let k = 0; k <= 5; k++) {
        const y = pad.t + ih * (k / 5);
        ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
      }
      ctx.setLineDash([]);

      const limitY = pad.t + ih - (SPEED_LIMIT / max) * ih;
      ctx.strokeStyle='#ef4444'; ctx.lineWidth=1.5; ctx.setLineDash([5,4]);
      ctx.beginPath(); ctx.moveTo(pad.l, limitY); ctx.lineTo(W - pad.r, limitY); ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle='#ef4444'; ctx.font='10px ui-sans-serif'; ctx.textAlign='left';
      ctx.fillText(SPEED_LIMIT + ' km/h', pad.l + 4, limitY - 4);

      vals.forEach((v, i) => {
        const slotW = iw / labels.length;
        const x = pad.l + i * slotW + (slotW - barW) / 2;
        const h = (v / max) * ih, y = pad.t + ih - h, r = 5;
        const over = v > SPEED_LIMIT;
        const g = ctx.createLinearGradient(0, y, 0, y + h);
        g.addColorStop(0, over ? '#fca5a5' : '#86efac');
        g.addColorStop(1, over ? NB.colors.red : NB.colors.green);
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.moveTo(x, y + r); ctx.arcTo(x, y, x + r, y, r);
        ctx.lineTo(x + barW - r, y); ctx.arcTo(x + barW, y, x + barW, y + r, r);
        ctx.lineTo(x + barW, y + h); ctx.lineTo(x, y + h); ctx.closePath(); ctx.fill();

        ctx.fillStyle='#374151'; ctx.font='bold 10px ui-sans-serif'; ctx.textAlign='center';
        ctx.fillText(v, x + barW / 2, Math.max(y - 2, pad.t + 10));
      });

      ctx.fillStyle='#6b7280'; ctx.font='11px ui-sans-serif'; ctx.textAlign='center';
      labels.forEach((lb, i) => {
        const slotW = iw / labels.length, x = pad.l + i * slotW + slotW / 2;
        ctx.save(); ctx.translate(x, H - 6); ctx.rotate(-Math.PI / 6);
        ctx.fillText(lb, 0, 0); ctx.restore();
      });

      ctx.textAlign='right'; ctx.fillStyle='#6b7280';
      for (let yv = 0; yv <= max; yv += Math.max(10, Math.round(max / 6 / 10) * 10)) {
        const y = pad.t + ih - (yv / max) * ih;
        ctx.fillText(yv, pad.l - 5, y + 4);
      }

      NB.setLegend(cvs.parentNode, [
        { label: 'Normal', color: NB.colors.green },
        { label: 'Over ' + SPEED_LIMIT + ' km/h', color: NB.colors.red }
      ]);
    });
  }

  /* ── live fleet status donut ────────────────────────────────── */
  function drawStatusChart(buses) {
    const cvs = el('liveStatusChart');
    if (!cvs || !NB) return;

    const total   = buses.length || 0;
    const speeding = buses.filter(b => b.speedKmh > SPEED_LIMIT).length;
    const normal   = total - speeding;
    const list = [
      { label: 'Normal',   value: normal,   color: NB.colors.green },
      { label: 'Speeding', value: speeding,  color: NB.colors.red   },
    ];

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2;
      const R  = Math.min(W, H) * 0.36;
      const r  = R * 0.62;
      const t  = list.reduce((s, d) => s + (d.value || 0), 0) || 1;
      let a0   = -Math.PI / 2;

      if (!total) {
        ctx.fillStyle='#9ca3af'; ctx.font='13px ui-sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText('No live data', cx, cy); return;
      }

      ctx.shadowColor='rgba(0,0,0,.15)'; ctx.shadowBlur=12;
      list.forEach(seg => {
        const ang = (seg.value / t) * Math.PI * 2;
        if (ang <= 0) return;
        ctx.beginPath(); ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, R, a0, a0 + ang);
        ctx.closePath(); ctx.fillStyle = seg.color; ctx.fill();
        a0 += ang;
      });

      ctx.shadowBlur = 0;
      const cardBg = getComputedStyle(cvs.closest('.chart-card') || document.body).backgroundColor || '#fff';
      ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.fillStyle = cardBg.startsWith('rgba(0') ? '#1e1e2e' : '#ffffff';
      ctx.fill();

      ctx.textAlign='center'; ctx.textBaseline='middle';
      ctx.fillStyle='#111827'; ctx.font='bold 22px ui-sans-serif';
      ctx.fillText(total, cx, cy - 8);
      ctx.fillStyle='#6b7280'; ctx.font='12px ui-sans-serif';
      ctx.fillText('Live Buses', cx, cy + 10);

      NB.setLegend(cvs.parentNode, list.map(d => ({
        label: d.label + ' (' + d.value + ')', color: d.color
      })));
    });
  }

/* ── live fleet table (max 5 rows + expander) ────────────────── */
  const SHOW_LIMIT = 5;

  function buildRow(b) {
    const over     = (+b.speedKmh || 0) > SPEED_LIMIT;
    const spBadge  = over
      ? '<span class="lf-badge lf-badge--red">⚡ ' + b.speedKmh + '</span>'
      : '<span class="lf-badge lf-badge--green">' + b.speedKmh + '</span>';

    // operatorType comes from raw API; depot/owner added by PHP enrichment
    const opType   = b.operatorType || b.operator_type || '';
    const opLabel  = opType === 'SLTB'
      ? 'SLTB' + (b.depot  ? ' · ' + escHtml(b.depot)  : '')
      : opType === 'Private'
      ? 'Private' + (b.owner ? ' · ' + escHtml(b.owner) : '')
      : (opType ? escHtml(opType) : '<span style="color:#9ca3af">–</span>');

    // inDb: trust explicit true, OR infer from enrichment fields populated only by DB lookup
    const isInDb = b.inDb === true || b.inDb === 1
      || !!b.depotId || !!b.ownerId
      || (b.operatorType === 'SLTB'    && !!b.depot)
      || (b.operatorType === 'Private' && !!b.owner);
    const inDb = isInDb
      ? '<span class="lf-badge lf-badge--green">✓</span>'
      : '<span class="lf-badge lf-badge--red">✗ New</span>';

    const status   = over ? 'Speeding' : (escHtml(b.operationalStatus || 'OnTime'));
    const statusCls = over ? 'red' : (status === 'Delayed' ? 'red' : 'green');

    const locLink  = (b.lat && b.lng)
      ? '<a href="https://maps.google.com/?q='+b.lat+','+b.lng+'" target="_blank" style="font-size:.75rem;color:#3b82f6">Map</a>'
      : '<span style="color:#d1d5db">–</span>';

    return '<tr'+(over?' style="background:#fff5f5"':'')+'>'  
      + '<td><strong>' + escHtml(b.busId) + '</strong></td>'
      + '<td>' + escHtml(String(b.routeNo || '–')) + '</td>'
      + '<td>' + opLabel + '</td>'
      + '<td>' + spBadge + '</td>'
      + '<td><span class="lf-badge lf-badge--'+statusCls+'">' + status + '</span></td>'
      + '<td>' + locLink + '</td>'
      + '</tr>';
  }

  function updateFleetTable(buses, totalFromApi) {
    const tbody = el('live-route-tbody');
    if (!tbody) return;

    if (!buses.length) {
      const active = [];
      if (F_ROUTE) active.push('route ' + F_ROUTE);
      if (F_DEPOT) active.push('depot ' + F_DEPOT);
      if (F_OWNER) active.push('owner ' + F_OWNER);
      const msg = active.length
        ? 'No buses found for ' + active.join(' + ') + ' (' + totalFromApi + ' total live)'
        : 'No live buses found';
      tbody.innerHTML = '<tr><td colspan="7" class="nb-table-empty">'+msg+'</td></tr>';
      return;
    }

    const visible = buses.slice(0, SHOW_LIMIT);
    const hidden  = buses.slice(SHOW_LIMIT);
    let html = visible.map(buildRow).join('');

    if (hidden.length) {
      // hidden rows get a class we toggle
      html += hidden.map(b => buildRow(b).replace('<tr', '<tr class="fleet-extra" style="display:none"')).join('');
      html += '<tr id="fleet-expander">'  
        + '<td colspan="7" style="text-align:center;padding:.4rem .75rem">'  
        + '<button onclick="window._fleetExpand()" '
        + 'style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:3px 14px;font-size:.8rem;cursor:pointer;color:#374151">'  
        + 'Show ' + hidden.length + ' more ▼</button>'  
        + '</td></tr>';
    }

    tbody.innerHTML = html;
  }

  window._fleetExpand = function () {
    const extras = document.querySelectorAll('tr.fleet-extra');
    const btn    = el('fleet-expander');
    const shown  = extras.length && extras[0].style.display !== 'none';
    extras.forEach(r => r.style.display = shown ? 'none' : '');
    if (btn) {
      const b = btn.querySelector('button');
      if (b) b.textContent = shown
        ? 'Show ' + extras.length + ' more ▼'
        : 'Collapse ▲';
    }
  };

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── main fetch/update cycle ─────────────────────────────────── */
  function fetchAndUpdate() {
    fetch(API + '?_=' + Date.now())  // reads latest DB snapshots (no external API call)
      .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(buses => {
        if (!Array.isArray(buses)) { showApiDown(); return; }
        if (buses.length === 0)    { showApiDown(); return; }
        // Debug: open browser DevTools console (?lfdebug)
        if (window.location.search.includes('lfdebug')) {
          console.log('[liveFleet] first bus from DB:', JSON.stringify(buses[0], null, 2));
        }
        const filtered = applyFilters(buses);
        updateKPIs(filtered);
        drawSpeedChart(filtered);
        drawStatusChart(filtered);
        updateFleetTable(filtered, buses.length);
      })
      .catch(() => showApiDown());
  }

  function showApiDown() {
    const tbody = el('live-route-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="nb-table-empty">No live bus data available – DB has no recent snapshots (check live bus scheduler)</td></tr>';
    if (el('live-updated-at'))       el('live-updated-at').textContent       = 'No recent data · ' + new Date().toLocaleTimeString();
    if (el('live-updated-at-table')) el('live-updated-at-table').textContent = 'No data · ' + new Date().toLocaleTimeString();
  }

  /* ── boot ────────────────────────────────────────────────────── */
  NB.onReady(function () {
    fetchAndUpdate();
    setInterval(fetchAndUpdate, REFRESH_MS);
  });

})();