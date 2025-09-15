document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('analytics-data');
  if (!el) return;
  const rows = JSON.parse(el.textContent || '{}').utilization || [];

  const c = document.getElementById('utilizationChart');
  if (!c) return;
  const ctx = c.getContext('2d');

  const padding = 50;
  const barH = 20, gap = 14;
  const maxV = 100; // utilization is a percent
  const scale = (c.width - padding*2) / maxV;

  // axes
  ctx.strokeStyle = '#888';
  ctx.beginPath();
  ctx.moveTo(padding, padding);
  ctx.lineTo(padding, c.height - padding);
  ctx.lineTo(c.width - padding, c.height - padding);
  ctx.stroke();

  // bars
  rows.forEach((r, i) => {
    const val = Number(r.utilization) || 0;
    const y = padding + i*(barH+gap);
    ctx.fillStyle = '#80143c';
    ctx.fillRect(padding+1, y, val*scale, barH);
    ctx.fillStyle = '#333';
    ctx.font = '12px system-ui';
    ctx.fillText(`${r.route_no}`, 10, y+barH-4);
    ctx.fillText(`${val}%`, padding + val*scale + 6, y+barH-4);
  });
});
