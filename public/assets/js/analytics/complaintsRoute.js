(function () {
  const NB = window.NBCharts;

  NB.onReady(function () {
    const cvs = document.getElementById('complaintsRouteChart');
    if (!cvs) return;
    const D = NB.getData(), F = window.ANALYTICS_DUMMY || {};

    const src = (D?.complaintsByRoute?.labels?.length) ? D.complaintsByRoute
              : (F?.complaintsByRoute || { labels: [], values: [] });
    const labels = src.labels || [];
    const vals   = (src.values || []).map(n => +n || 0);

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const pad = { l: 46, r: 16, t: 16, b: 54 };
      const iw  = W - pad.l - pad.r;
      const ih  = H - pad.t - pad.b;
      const max = Math.max(5, Math.ceil(Math.max(...vals, 1) / 5) * 5);
      const barW = Math.min(34, (iw / Math.max(labels.length, 1)) * 0.6);

      // grid
      ctx.strokeStyle = NB.colors.grid;
      ctx.setLineDash([3, 6]);
      for (let k = 0; k <= 5; k++) {
        const y = pad.t + ih * (k / 5);
        ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
      }
      ctx.setLineDash([]);

      // bars
      vals.forEach((v, i) => {
        const slotW = iw / Math.max(labels.length, 1);
        const x     = pad.l + i * slotW + (slotW - barW) / 2;
        const h     = (v / max) * ih;
        const y     = pad.t + ih - h;
        const r     = 5;
        // gradient: maroon for complaints
        const g = ctx.createLinearGradient(0, y, 0, y + h);
        g.addColorStop(0, '#f87171');
        g.addColorStop(1, NB.colors.maroonDark);
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

        // value label
        ctx.fillStyle  = '#374151';
        ctx.font       = 'bold 10px ui-sans-serif';
        ctx.textAlign  = 'center';
        ctx.fillText(v, x + barW / 2, Math.max(y - 3, pad.t + 10));
      });

      // x labels (rotated)
      ctx.fillStyle = '#6b7280';
      ctx.font      = '11px ui-sans-serif';
      labels.forEach((lb, i) => {
        const slotW = iw / Math.max(labels.length, 1);
        const x     = pad.l + i * slotW + slotW / 2;
        ctx.save(); ctx.translate(x, H - 6); ctx.rotate(-Math.PI / 6);
        ctx.fillText(lb, 0, 0); ctx.restore();
      });

      // y labels
      ctx.textAlign = 'right';
      ctx.fillStyle = '#6b7280';
      ctx.font      = '11px ui-sans-serif';
      for (let yv = 0; yv <= max; yv += Math.max(1, Math.round(max / 5))) {
        const y = pad.t + ih - (yv / max) * ih;
        ctx.fillText(yv, pad.l - 5, y + 4);
      }

      if (!vals.length) {
        ctx.fillStyle  = '#9ca3af';
        ctx.font       = '14px ui-sans-serif';
        ctx.textAlign  = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('No complaint data available', W / 2, H / 2);
      }
    });
  });
})();
