// Minimal helper used by all charts (no frameworks)
(function () {
  const NB = (window.NBCharts = window.NBCharts || {});

  NB.onReady = (fn) =>
    document.readyState !== "loading"
      ? fn()
      : document.addEventListener("DOMContentLoaded", fn);

  NB.getData = function () {
    try {
      const el = document.getElementById("analytics-data");
      return el ? JSON.parse(el.textContent || "{}") : {};
    } catch {
      return {};
    }
  };

  // Fit canvas to its parent at a given aspect; hi-DPI; prevent oval stretch
  NB.autoCanvas = function (canvas, aspect, opts = {}) {
    const minW = opts.minW || 260;
    const parent = canvas.parentElement || document.body;
    const cssW = Math.max(minW, Math.floor(parent.clientWidth) || minW);
    const cssH = Math.max(160, Math.round(cssW / aspect)); // consistent height
    const dpr = Math.max(1, window.devicePixelRatio || 1);

    // Internal bitmap
    canvas.width = Math.max(1, Math.round(cssW * dpr));
    canvas.height = Math.max(1, Math.round(cssH * dpr));

    // Visual size (prevents CSS stretching)
    canvas.style.width = cssW + "px";
    canvas.style.height = cssH + "px";

    const ctx = canvas.getContext("2d");
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(dpr, dpr);
    return { ctx, W: cssW, H: cssH, dpr };
  };

  NB.observe = function (canvas, aspect, draw) {
    const go = () => draw(NB.autoCanvas(canvas, aspect));
    let raf = 0;
    const queued = () => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(go);
    };
    go();
    if (window.ResizeObserver) {
      const ro = new ResizeObserver(queued);
      ro.observe(canvas.parentElement || canvas);
    }
    window.addEventListener("resize", queued);
    window.addEventListener("orientationchange", queued);
  };

  NB.setLegend = function (container, items) {
    const old = container.querySelector(".chart-legend");
    if (old) old.remove();
    const div = document.createElement("div");
    div.className = "chart-legend";
    items.forEach((it) => {
      const s = document.createElement("span");
      s.className = "legend-item";
      const i = document.createElement("i");
      i.style.background = it.color;
      s.appendChild(i);
      s.appendChild(document.createTextNode(it.label));
      div.appendChild(s);
    });
    container.appendChild(div);
  };

  NB.colors = {
    maroon: "#80143c",
    maroonDark: "#5b0e25",
    gold: "#f3b944",
    green: "#16a34a",
    red: "#b91c1c",
    redSoft: "#ef4444",
    orange: "#e06a00",
    coral: "#e06559",
    grid: "rgba(0,0,0,.15)"
  };
})();
