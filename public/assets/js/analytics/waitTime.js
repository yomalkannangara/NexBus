(function () {
  const NB = window.NBCharts;

  NB.onReady(function () {
    const cvs = document.getElementById("waitTimeChart"); if (!cvs) return;
    const D = NB.getData(), F = window.ANALYTICS_DUMMY || {};
    const fromServer = !!D._fromServer;

    let list = Array.isArray(D?.waitTime) && D.waitTime.length ? D.waitTime : (F.waitTime || []);

    // If every segment is 0: show sample only when server hasn't responded yet
    const hasData = list.some(d => (+d.value || 0) > 0);
    const showNoData = fromServer && !hasData;
    if (!hasData && !fromServer) {
      list = [
        { label: "Under 5 min", value: 65, color: '#16a34a' },
        { label: "5–10 min", value: 15, color: '#f3b944' },
        { label: "10–15 min", value: 10, color: '#e05a7a' },
        { label: "15–20 min", value: 6, color: '#80143c' },
        { label: "Over 20 min", value: 4, color: '#3d0820' },
      ];
    }

    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      if (showNoData) {
        ctx.fillStyle = '#9ca3af'; ctx.font = '14px ui-sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('No wait-time data for selected filters', W / 2, H / 2);
        return;
      }
      const cx = W / 2, cy = H / 2;
      const R = Math.min(W, H) * 0.36;
      const r = R * 0.62;
      const total = list.reduce((s, d) => s + (+d.value || 0), 0) || 1;
      let a0 = -Math.PI / 2;

      // ── draw pie segments ──────────────────────────────────────────
      ctx.shadowColor = 'rgba(0,0,0,.15)';
      ctx.shadowBlur = 10;
      list.forEach(seg => {
        const ang = (+seg.value / total) * Math.PI * 2;
        if (ang <= 0) return;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, R, a0, a0 + ang);
        ctx.closePath();
        ctx.fillStyle = seg.color;
        ctx.fill();
        a0 += ang;
      });

      // ── solid white centre hole (avoids destination-out transparency bug) ──
      ctx.shadowBlur = 0;
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      // use the card background colour — white in light mode, dark in dark mode
      const cardBg = getComputedStyle(cvs.closest('.chart-card') || document.body)
        .backgroundColor || '#ffffff';
      ctx.fillStyle = cardBg.startsWith('rgba(0') ? '#1e1e2e' : '#ffffff';
      ctx.fill();

      // ── centre labels ──────────────────────────────────────────────
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = '#111827';
      ctx.font = 'bold 15px ui-sans-serif';
      ctx.fillText('Wait Time', cx, cy - 9);
      ctx.fillStyle = '#6b7280';
      ctx.font = '12px ui-sans-serif';
      ctx.fillText(hasData ? '' : 'Sample data', cx, cy + 9);

      NB.setLegend(cvs.parentNode, list.map(d => ({
        label: `${d.label}  ${d.value}%`,
        color: d.color
      })));
    });
  });
})();

