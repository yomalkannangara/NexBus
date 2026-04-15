(function () {
  const NB = window.NBCharts;

  function normalize(raw) {
    const PAL = [NB.colors.green, NB.colors.red, NB.colors.gold, NB.colors.maroon];
    const STATUS_COLOR = {
      'Active':'#16a34a','active':'#16a34a',
      'Maintenance':'#f59e0b','maintenance':'#f59e0b',
      'Inactive':'#dc2626','inactive':'#dc2626',
      'On Time':'#16a34a','OnTime':'#16a34a','Ontime':'#16a34a',
      'Delayed':'#b91c1c','delayed':'#b91c1c',
      'Cancelled':'#ef4444','cancelled':'#ef4444',
      'Breakdown':'#f59e0b','breakdown':'#f59e0b',
    };
    if (!raw) return [];
    if (Array.isArray(raw) && raw.every((v) => !isNaN(+v))) {
      const L = ['Active','Maintenance','Inactive','Breakdown'];
      return raw.slice(0, L.length).map((v, i) => ({
        label: L[i] || `Cat ${i+1}`, value: +v, color: PAL[i % PAL.length],
      }));
    }
    if (Array.isArray(raw)) {
      return raw.map((it, i) => {
        if (Array.isArray(it)) return { label: it[0], value: +it[1]||0, color: it[2]||PAL[i%PAL.length] };
        // DB format: {status, total}
        const lbl = it?.label ?? it?.status ?? `Cat ${i+1}`;
        const val = +it?.value || +it?.total || 0;
        return { label: lbl, value: val, color: STATUS_COLOR[lbl] || PAL[i % PAL.length] };
      });
    }
    if (typeof raw === 'object') {
      return Object.keys(raw).map((k, i) => ({ label: k, value: +raw[k]||0, color: PAL[i%PAL.length] }));
    }
    return [];
  }

  NB.onReady(function () {
    const cvs = document.getElementById("busStatusChart");
    if (!cvs) return;

    const D = NB.getData();
    const fromServer = !!D._fromServer;
    let list = normalize(D.busStatus);
    const hasData = list.some((d) => (+d.value || 0) > 0);
    if (!hasData) {
      if (fromServer) {
        // Real data from DB — just nothing yet; show empty state
        list = [];
      } else {
        list = [
          { label: "Active",      value: 0, color: NB.colors.green },
          { label: "Maintenance", value: 0, color: NB.colors.gold },
          { label: "Inactive",    value: 0, color: '#dc2626' },
        ];
      }
    }

    // Aspect matches other half-width cards
    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
      if (!list.length) {
        ctx.fillStyle = '#9ca3af'; ctx.font = '14px ui-sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('No bus status data available', W / 2, H / 2);
        NB.setLegend(cvs.parentNode, []);
        return;
      }
      const cx = W / 2,
        cy = H / 2,
        R = Math.min(W, H) * 0.36,
        r = R * 0.62;

      const total = list.reduce((s, d) => s + (+d.value || 0), 0) || 1;
      let a0 = -Math.PI / 2;

      ctx.shadowColor = "rgba(0,0,0,.15)";
      ctx.shadowBlur = 12;
      list.forEach((seg) => {
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

      ctx.shadowBlur = 0;
      ctx.globalCompositeOperation = "destination-out";
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.fill();
      ctx.globalCompositeOperation = "source-over";

      ctx.fillStyle = "#2b2b2b";
      ctx.font = "700 16px ui-sans-serif";
      ctx.textAlign = "center";
      ctx.fillText("Bus Status", cx, cy - 6);
      ctx.fillStyle = "#6b7280";
      ctx.font = "12px ui-sans-serif";
      ctx.fillText(total + " total", cx, cy + 14);

      NB.setLegend(
        cvs.parentNode,
        list.map((d) => ({ label: `${d.label} (${d.value})`, color: d.color }))
      );
    });
  });
})();
