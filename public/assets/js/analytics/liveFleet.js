/**
 * liveFleet.js – fetches live bus data and drives:
 *   • KPI cards (active buses, speed violations, avg speed)
 *   • Speed-by-Bus chart (real speeds + limit line)
 *   • Bus Status donut  (normal / speeding split)
 *   • Live Route summary table
 *
 * Auto-refreshes every 15 seconds.
 */
(function () {
  'use strict';

  const API         = '/api/buses/live';
  const SPEED_LIMIT = 60;   // km/h threshold for a "violation"
  const REFRESH_MS  = 15000;
  const NB          = window.NBCharts;

  /* ── helpers ── */
  function el(id) { return document.getElementById(id); }

  /* ── KPI update ── */
  function updateKPIs(buses) {
    const total    = buses.length;
    const viols    = buses.filter(b => b.speedKmh > SPEED_LIMIT).length;
    const avgSpeed = total
      ? (buses.reduce((s, b) => s + (b.speedKmh || 0), 0) / total).toFixed(1)
      : '–';

    if (el('kpi-active-buses'))  el('kpi-active-buses').textContent  = total;
    if (el('kpi-speed'))         el('kpi-speed').textContent         = viols;
    if (el('kpi-avg-speed'))     el('kpi-avg-speed').textContent     = avgSpeed + ' km/h';
    if (el('live-updated-at'))   el('live-updated-at').textContent   =
      'Live · updated ' + new Date().toLocaleTimeString();
  }

  /* ── Speed-by-Bus bar chart ── */
  function drawSpeedChart(buses) {
    const cvs = el('speedByBusChart');
    if (!cvs || !NB) return;

    /* Top 12 buses sorted by speed desc */
    const sorted = [...buses].sort((a, b) => b.speedKmh - a.speedKmh).slice(0, 12);
    const labels = sorted.map(b => b.busId);
    const vals   = sorted.map(b => +b.speedKmh || 0);
    const max    = Math.max(SPEED_LIMIT + 20, Math.ceil(Math.max(...vals, SPEED_LIMIT + 10) / 10) * 10);

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const pad = { l: 46, r: 16, t: 24, b: 54 };
      const iw  = W - pad.l - pad.r;
      const ih  = H - pad.t - pad.b;
      const barW = Math.min(32, (iw / Math.max(labels.length, 1)) * 0.6);

      /* grid lines */
      ctx.strokeStyle = NB.colors.grid;
      ctx.lineWidth   = 1;
      ctx.setLineDash([3, 6]);
      for (let k = 0; k <= 5; k++) {
        const y = pad.t + ih * (k / 5);
        ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
      }
      ctx.setLineDash([]);

      /* speed-limit dashed red line */
      const limitY = pad.t + ih - (SPEED_LIMIT / max) * ih;
      ctx.strokeStyle = '#ef4444';
      ctx.lineWidth   = 1.5;
      ctx.setLineDash([5, 4]);
      ctx.beginPath(); ctx.moveTo(pad.l, limitY); ctx.lineTo(W - pad.r, limitY); ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle = '#ef4444';
      ctx.font      = '10px ui-sans-serif';
      ctx.textAlign = 'left';
      ctx.fillText(SPEED_LIMIT + ' km/h', pad.l + 4, limitY - 4);

      /* bars */
      vals.forEach((v, i) => {
        const slotW = iw / labels.length;
        const x     = pad.l + i * slotW + (slotW - barW) / 2;
        const h     = (v / max) * ih;
        const y     = pad.t + ih - h;
        const r     = 5;
        const over  = v > SPEED_LIMIT;

        const g = ctx.createLinearGradient(0, y, 0, y + h);
        g.addColorStop(0, over ? '#fca5a5' : '#86efac');
        g.addColorStop(1, over ? NB.colors.red : NB.colors.green);
        ctx.fillStyle = g;

        ctx.beginPath();
        ctx.moveTo(x, y + r);
        ctx.arcTo(x, y, x + r, y, r);
        ctx.lineTo(x + barW - r, y);
        ctx.arcTo(x + barW, y, x + barW, y + r, r);
        ctx.lineTo(x + barW, y + h);
        ctx.lineTo(x, y + h);
        ctx.closePath();
        ctx.fill();

        /* value label above bar */
        ctx.fillStyle  = '#374151';
        ctx.font       = 'bold 10px ui-sans-serif';
        ctx.textAlign  = 'center';
        ctx.fillText(v, x + barW / 2, Math.max(y - 2, pad.t + 10));
      });

      /* x labels */
      ctx.fillStyle = '#6b7280';
      ctx.font      = '11px ui-sans-serif';
      ctx.textAlign = 'center';
      labels.forEach((lb, i) => {
        const slotW = iw / labels.length;
        const x     = pad.l + i * slotW + slotW / 2;
        ctx.save(); ctx.translate(x, H - 6); ctx.rotate(-Math.PI / 6);
        ctx.fillText(lb, 0, 0); ctx.restore();
      });

      /* y labels */
      ctx.textAlign = 'right';
      ctx.fillStyle = '#6b7280';
      ctx.font      = '11px ui-sans-serif';
      for (let yv = 0; yv <= max; yv += Math.max(10, Math.round(max / 6 / 10) * 10)) {
        const y = pad.t + ih - (yv / max) * ih;
        ctx.fillText(yv, pad.l - 5, y + 4);
      }

      /* legend */
      NB.setLegend(cvs.parentNode, [
        { label: 'Normal Speed', color: NB.colors.green },
        { label: 'Over ' + SPEED_LIMIT + ' km/h', color: NB.colors.red }
      ]);
    });
  }

  /* ── Bus Status donut ── */
  function drawStatusChart(buses) {
    const cvs = el('busStatusChart');
    if (!cvs || !NB) return;

    const total   = buses.length || 0;
    const speeding = buses.filter(b => b.speedKmh > SPEED_LIMIT).length;
    const normal   = total - speeding;

    const list = [
      { label: 'Normal',   value: normal,   color: NB.colors.green },
      { label: 'Speeding', value: speeding,  color: NB.colors.red   }
    ];

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const cx = W / 2, cy = H / 2;
      const R  = Math.min(W, H) * 0.36;
      const r  = R * 0.62;
      const t  = list.reduce((s, d) => s + (d.value || 0), 0) || 1;
      let a0   = -Math.PI / 2;

      ctx.shadowColor = 'rgba(0,0,0,.15)';
      ctx.shadowBlur  = 12;
      list.forEach(seg => {
        const ang = (seg.value / t) * Math.PI * 2;
        if (ang <= 0) return;
        ctx.beginPath(); ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, R, a0, a0 + ang);
        ctx.closePath();
        ctx.fillStyle = seg.color;
        ctx.fill();
        a0 += ang;
      });

      ctx.shadowBlur = 0;
      ctx.globalCompositeOperation = 'destination-out';
      ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2); ctx.fill();
      ctx.globalCompositeOperation = 'source-over';

      /* center label */
      ctx.fillStyle  = '#111827';
      ctx.font       = 'bold 22px ui-sans-serif';
      ctx.textAlign  = 'center';
      ctx.fillText(total, cx, cy + 4);
      ctx.fillStyle = '#6b7280';
      ctx.font      = '12px ui-sans-serif';
      ctx.fillText('Live Buses', cx, cy + 20);

      NB.setLegend(cvs.parentNode, list.map(d => ({
        label: d.label + ' (' + d.value + ')',
        color: d.color
      })));
    });
  }

  /* ── Route summary table ── */
  function updateRouteTable(buses) {
    const tbody = el('live-route-tbody');
    if (!tbody) return;

    const byRoute = {};
    buses.forEach(b => {
      const key = b.routeNo || '–';
      if (!byRoute[key]) byRoute[key] = [];
      byRoute[key].push(b);
    });

    const rows = Object.keys(byRoute).sort().map(rno => {
      const grp   = byRoute[rno];
      const avg   = (grp.reduce((s, b) => s + (b.speedKmh || 0), 0) / grp.length).toFixed(1);
      const viols = grp.filter(b => b.speedKmh > SPEED_LIMIT).length;
      const badge = viols > 0
        ? '<span class="lf-badge lf-badge--red">' + viols + ' viol.</span>'
        : '<span class="lf-badge lf-badge--green">OK</span>';
      return '<tr>'
        + '<td><strong>' + rno + '</strong></td>'
        + '<td>' + grp.length + '</td>'
        + '<td>' + avg + ' km/h</td>'
        + '<td>' + badge + '</td>'
        + '</tr>';
    });

    tbody.innerHTML = rows.length
      ? rows.join('')
      : '<tr><td colspan="4" style="text-align:center;color:#6b7280">No live data</td></tr>';
  }

  /* ── Main fetch/update cycle ── */
  function fetchAndUpdate() {
    fetch(API)
      .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(buses => {
        if (!Array.isArray(buses)) return;
        updateKPIs(buses);
        drawSpeedChart(buses);
        drawStatusChart(buses);
        updateRouteTable(buses);
      })
      .catch(() => { /* silently keep previous values on failure */ });
  }

  /* ── Boot ── */
  NB.onReady(function () {
    fetchAndUpdate();
    setInterval(fetchAndUpdate, REFRESH_MS);
  });

})();
