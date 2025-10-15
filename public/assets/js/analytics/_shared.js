// /assets/js/analytics/_shared.js
export function getJSON() {
  try {
    const el = document.getElementById("analytics-data");
    return el ? JSON.parse(el.textContent || "{}") : {};
  } catch { return {}; }
}
export function ctx2d(canvas) {
  if (!canvas) return null;
  const dpr = Math.max(1, window.devicePixelRatio || 1);
  const w = canvas.width, h = canvas.height;
  canvas.style.width = w + "px"; canvas.style.height = h + "px";
  canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
  const g = canvas.getContext("2d"); g.scale(dpr, dpr); return g;
}
export function arrOr(a, fallback) {
  return Array.isArray(a) && a.length ? a : fallback;
}
export function numOr(n, fallback) {
  return Number.isFinite(+n) ? +n : fallback;
}
