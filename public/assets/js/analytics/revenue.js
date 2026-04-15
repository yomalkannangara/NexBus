(function () {
  const NB = window.NBCharts;

  NB.onReady(function () {
    const cvs = document.getElementById("revenueChart"); if (!cvs) return;
    const D = NB.getData(), F = window.ANALYTICS_DUMMY || {};
    const fromServer = !!D._fromServer;
    const labels = fromServer ? (D?.revenue?.labels || []) : (D?.revenue?.labels?.length ? D.revenue.labels : (F.revenue?.labels || []));
    const vals = fromServer ? (D?.revenue?.values || []).map(n => +n || 0) : (D?.revenue?.values?.length ? D.revenue.values : (F.revenue?.values || [])).map(n => +n || 0);

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      const pad = { l: 68, r: 16, t: 16, b: 36 }, iw = W - pad.l - pad.r, ih = H - pad.t - pad.b;
      if (!labels.length) {
        ctx.fillStyle = '#9ca3af'; ctx.font = '14px ui-sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('No revenue data for selected filters', W / 2, H / 2); return;
      }
      const maxRaw = Math.max(...vals, 0);
      const yTickCount = 5;
      const max = Math.max(yTickCount, Math.ceil(maxRaw / yTickCount) * yTickCount);
      const barW = iw / labels.length * 0.62;

      // grid
      ctx.strokeStyle = NB.colors.grid; ctx.setLineDash([3, 6]);
      for (let k = 0; k <= 6; k++) { const y = pad.t + ih * (k / 6); ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke(); }
      ctx.setLineDash([]);

      // x labels
      ctx.fillStyle = "#6b7280"; ctx.font = "12px ui-sans-serif";
      labels.forEach((lb, i) => { const x = pad.l + (iw * i) / labels.length + barW * 0.2; ctx.fillText(lb, x, H - 12); });

      // bars
      vals.forEach((v, i) => {
        const x = pad.l + (iw * i) / labels.length + ((iw / labels.length - barW) / 2);
        const h = (v / max) * ih, y = pad.t + ih - h, r = 8;
        const g = ctx.createLinearGradient(0, y, 0, y + h); g.addColorStop(0, "#e05a7a"); g.addColorStop(1, NB.colors.maroonDark);
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.moveTo(x, y + r); ctx.arcTo(x, y, x + r, y, r);
        ctx.lineTo(x + barW - r, y); ctx.arcTo(x + barW, y, x + barW, y + r, r);
        ctx.lineTo(x + barW, y + h); ctx.lineTo(x, y + h); ctx.closePath(); ctx.fill();
        ctx.strokeStyle = "rgba(128,20,60,.25)"; ctx.lineWidth = 1; ctx.stroke();
      });

      // y labels
      ctx.fillStyle = "#6b7280";
      ctx.font = "12px ui-sans-serif";
      ctx.textAlign = 'left';
      for (let i = 0; i <= yTickCount; i++) {
        const yv = (max / yTickCount) * i;
        const y = pad.t + ih - (yv / max) * ih;
        const text = (yv % 1 === 0 ? yv.toFixed(0) : yv.toFixed(1)) + "M";
        ctx.fillText(text, pad.l - 44, y + 4);
      }
    });
  });
})();
