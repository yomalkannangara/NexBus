(function () {
  const NB = window.NBCharts;

  function normalize(raw) {
    const ORDER = ["On Time", "Delayed", "Cancelled", "Maintenance"];
    const PAL = [NB.colors.green, NB.colors.red, NB.colors.redSoft, NB.colors.gold];
    if (!raw) return [];
    if (Array.isArray(raw) && raw.every((v) => !isNaN(+v))) {
      return raw.slice(0, ORDER.length).map((v, i) => ({
        label: ORDER[i] || `Cat ${i + 1}`,
        value: +v,
        color: PAL[i % PAL.length],
      }));
    }
    if (Array.isArray(raw)) {
      return raw.map((it, i) =>
        Array.isArray(it)
          ? { label: it[0] ?? ORDER[i], value: +it[1] || 0, color: it[2] || PAL[i % PAL.length] }
          : { label: it?.label ?? ORDER[i], value: +it?.value || 0, color: it?.color || PAL[i % PAL.length] }
      );
    }
    if (typeof raw === "object") {
      return Object.keys(raw).map((k, i) => ({ label: k, value: +raw[k] || 0, color: PAL[i % PAL.length] }));
    }
    return [];
  }

  NB.onReady(function () {
    const cvs = document.getElementById("busStatusChart");
    if (!cvs) return;

    const D = NB.getData();
    const F = window.ANALYTICS_DUMMY || {};
    let list = normalize(D.busStatus);
    if (!list.length || !list.some((d) => (+d.value || 0) > 0)) {
      list = (F.busStatus || [
        { label: "On Time", value: 62, color: NB.colors.green },
        { label: "Delayed", value: 25, color: NB.colors.red },
        { label: "Cancelled", value: 5, color: NB.colors.redSoft },
        { label: "Maintenance", value: 8, color: NB.colors.gold },
      ]).slice();
    }

    // Aspect matches other half-width cards
    NB.observe(cvs, 7 / 4, ({ ctx, W, H }) => {
      ctx.clearRect(0, 0, W, H);
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
