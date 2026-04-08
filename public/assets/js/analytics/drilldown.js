(function () {
  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
      return;
    }
    fn();
  }

  function preserveFilters(nextUrl) {
    var current = new URL(window.location.href);
    var next = new URL(nextUrl, window.location.origin);
    current.searchParams.forEach(function (value, key) {
      if (!next.searchParams.has(key)) {
        next.searchParams.set(key, value);
      }
    });
    return next.toString();
  }

  function buildDetailUrl(canvas) {
    var key = canvas.getAttribute("data-drill-key");
    var base = canvas.getAttribute("data-drill-base");
    if (!key || !base) return "";
    var raw = base + (base.indexOf("?") >= 0 ? "&" : "?") + "chart=" + encodeURIComponent(key);
    return preserveFilters(raw);
  }

  function makeLabel(card) {
    var titleEl = card.querySelector("h2");
    var title = titleEl ? (titleEl.textContent || "").trim() : "Chart";
    return "View " + title + " Details";
  }

  onReady(function () {
    var charts = document.querySelectorAll("canvas[data-drill-key]");
    charts.forEach(function (canvas) {
      var card = canvas.closest(".chart-card");
      if (!card) return;
      if (card.querySelector(".js-chart-detail-btn")) return;

      var href = buildDetailUrl(canvas);
      if (!href) return;

      var btn = document.createElement("a");
      btn.className = "js-chart-detail-btn";
      btn.href = href;
      btn.textContent = makeLabel(card);
      btn.setAttribute("aria-label", btn.textContent);

      // Keep this local style-only placement so we don't need new CSS files.
      card.style.position = "relative";
      btn.style.position = "absolute";
      btn.style.top = "10px";
      btn.style.right = "10px";
      btn.style.zIndex = "2";

      card.appendChild(btn);
    });
  });
})();
