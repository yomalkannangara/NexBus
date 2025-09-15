document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('analytics-data');
  if (!el) return;
  const data = JSON.parse(el.textContent || '{}').onTime || [];

  const c = document.getElementById('onTimeChart');
  if (!c) return;
  const ctx = c.getContext('2d');

  const labels = data.map(d => d.operational_status);
  const values = data.map(d => Number(d.total) || 0);

  const padding = 40, barW = 50, gap = 24;
  const maxV = Math.max(...values, 1);
  const scale = (c.height - padding*2) / maxV;

  // axes
  ctx.strokeStyle = '#888';
  ctx.beginPath();
  ctx.moveTo(padding, padding);
  ctx.lineTo(padding, c.height - padding);
  ctx.lineTo(c.width - padding, c.height - padding);
  ctx.stroke();

  // bars
  values.forEach((v, i) => {
    const x = padding + 20 + i*(barW+gap);
    const h = v * scale;
    const y = c.height - padding - h;
    ctx.fillStyle = '#80143c';
    ctx.fillRect(x, y, barW, h);

    ctx.fillStyle = '#333';
    ctx.font = '12px system-ui';
    ctx.fillText(labels[i], x, c.height - padding + 14);
    ctx.fillText(String(v), x + 10, y - 6);
  });
});
