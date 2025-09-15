document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('analytics-data');
  if (!el) return;
  const raw = JSON.parse(el.textContent || '{}').revenue || [];

  // group totals by date (sum Private + SLTB)
  const byDate = {};
  raw.forEach(r => {
    const d = r.date;
    byDate[d] = (byDate[d] || 0) + (Number(r.total) || 0);
  });
  const dates = Object.keys(byDate).sort();
  const values = dates.map(d => byDate[d]);

  const c = document.getElementById('revenueChart');
  if (!c) return;
  const ctx = c.getContext('2d');

  const padding = 40;
  const maxV = Math.max(...values, 1);
  const xStep = (c.width - padding*2) / Math.max(dates.length - 1, 1);
  const yScale = (c.height - padding*2) / maxV;

  // axes
  ctx.strokeStyle = '#888';
  ctx.beginPath();
  ctx.moveTo(padding, padding);
  ctx.lineTo(padding, c.height - padding);
  ctx.lineTo(c.width - padding, c.height - padding);
  ctx.stroke();

  // line
  ctx.strokeStyle = '#80143c';
  ctx.lineWidth = 2;
  ctx.beginPath();
  dates.forEach((d, i) => {
    const x = padding + i * xStep;
    const y = c.height - padding - values[i] * yScale;
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    // point
    ctx.fillStyle = '#80143c';
    ctx.beginPath(); ctx.arc(x, y, 3, 0, Math.PI*2); ctx.fill();
  });
  ctx.stroke();

  // labels
  ctx.fillStyle = '#333'; ctx.font = '12px system-ui';
  dates.forEach((d, i) => {
    const x = padding + i * xStep;
    const y = c.height - padding + 14;
    ctx.fillText(d, x - 24, y);
  });
});
