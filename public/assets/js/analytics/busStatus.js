document.addEventListener('DOMContentLoaded', () => {
  try {
    const jsonEl = document.getElementById('analytics-data');
    if (!jsonEl) return console.error('analytics-data script tag not found');
    const analyticsData = JSON.parse(jsonEl.textContent || '{}');

    const canvas = document.getElementById('busStatusChart');
    if (!canvas) return console.error('#busStatusChart not found');
    const ctx = canvas.getContext('2d');

    const data = (analyticsData.busStatus || []).map(d => ({ label: d.status, value: Number(d.total) || 0 }));
    if (!data.length) return console.warn('busStatus: no data');

    const total = data.reduce((s, d) => s + d.value, 0) || 1;
    let start = 0;
    const cx = canvas.width / 2, cy = canvas.height / 2, r = Math.min(cx, cy) - 10;
    const colors = ['#80143c', '#e4b74f', '#6b7280', '#2b2b2b'];

    data.forEach((d, i) => {
      const sweep = (d.value / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, r, start, start + sweep);
      ctx.closePath();
      ctx.fillStyle = colors[i % colors.length];
      ctx.fill();
      start += sweep;
    });

    // simple labels
    ctx.font = '12px system-ui';
    ctx.textBaseline = 'top';
    let y = 8;
    data.forEach((d, i) => {
      ctx.fillStyle = colors[i % colors.length];
      ctx.fillRect(8, y + 3, 10, 10);
      ctx.fillStyle = '#333';
      ctx.fillText(`${d.label}: ${d.value}`, 24, y);
      y += 18;
    });
  } catch (e) {
    console.error('busStatus.js error:', e);
  }
});
