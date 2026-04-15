(function () {
  const NB = window.NBCharts;

  NB.onReady(function () {
    const cvs = document.getElementById('complaintsRouteChart');
    if (!cvs) return;
    const D = NB.getData(), F = window.ANALYTICS_DUMMY || {};

    const fromServer = !!D._fromServer;
    const src = fromServer ? (D?.complaintsByRoute || { labels: [], values: [] })
      : (D?.complaintsByRoute?.labels?.length ? D.complaintsByRoute
        : (F?.complaintsByRoute || { labels: [], values: [] }));
    const labels = src.labels || [];
    const vals = (src.values || []).map(n => +n || 0);

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const pad = { l: 86, r: 26, t: 16, b: 34 };
      const iw = W - pad.l - pad.r;
      const ih = H - pad.t - pad.b;
      if (!labels.length) {
        ctx.fillStyle = '#9ca3af'; ctx.font = '14px ui-sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('No complaints data for selected filters', W / 2, H / 2); return;
      }
      const max = Math.max(5, Math.ceil(Math.max(...vals, 1) / 5) * 5);
      const rows = Math.max(labels.length, 1);
      const slotH = ih / rows;
      const barH = Math.min(24, slotH * 0.62);

      // grid
      ctx.strokeStyle = NB.colors.grid;
      ctx.setLineDash([3, 6]);
      for (let k = 0; k <= 5; k++) {
        const x = pad.l + iw * (k / 5);
        ctx.beginPath(); ctx.moveTo(x, pad.t); ctx.lineTo(x, H - pad.b); ctx.stroke();
      }
      ctx.setLineDash([]);

      // bars
      vals.forEach((v, i) => {
        const y = pad.t + i * slotH + (slotH - barH) / 2;
        const w = (v / max) * iw;
        const x = pad.l;
        const r = 5;
        // gradient: maroon for complaints
        const g = ctx.createLinearGradient(x, 0, x + w, 0);
        g.addColorStop(0, NB.colors.maroon);
        g.addColorStop(1, '#f3b944');
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.arcTo(x + w, y, x + w, y + r, r);
        ctx.lineTo(x + w, y + barH - r);
        ctx.arcTo(x + w, y + barH, x + w - r, y + barH, r);
        ctx.lineTo(x + r, y + barH);
        ctx.arcTo(x, y + barH, x, y + barH - r, r);
        ctx.lineTo(x, y + r);
        ctx.arcTo(x, y, x + r, y, r);
        ctx.closePath();
        ctx.fill();

        // value label
        ctx.fillStyle = '#374151';
        ctx.font = 'bold 10px ui-sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText(v, Math.min(x + w + 6, W - pad.r - 12), y + barH / 2 + 3);

        // y labels (bus reg)
        ctx.fillStyle = '#6b7280';
        ctx.font = '11px ui-sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(labels[i], pad.l - 8, y + barH / 2 + 4);
      });

      // x labels (complaint counts)
      ctx.fillStyle = '#6b7280';
      ctx.font = '11px ui-sans-serif';
      ctx.textAlign = 'center';
      for (let xv = 0; xv <= max; xv += Math.max(1, Math.round(max / 5))) {
        const x = pad.l + (xv / max) * iw;
        ctx.fillText(xv, x, H - 10);
      }

      if (!vals.length) {
        ctx.fillStyle = '#9ca3af';
        ctx.font = '14px ui-sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('No complaint data available', W / 2, H / 2);
      }
    });
  });
})();
