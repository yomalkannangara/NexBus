document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('analytics-data');
  if (!el) return;
  const data = JSON.parse(el.textContent || '{}').complaints || [];

  const c = document.getElementById('complaintsChart');
  if (!c) return;
  const ctx = c.getContext('2d');

  const total = data.reduce((s, d) => s + (Number(d.total)||0), 0) || 1;
  const cx = c.width/2, cy = c.height/2, r = Math.min(cx,cy)-10;
  let start = 0;
  const colors = ['#f59e0b','#ef4444','#10b981','#3b82f6','#a855f7'];

  data.forEach((d,i)=>{
    const val = Number(d.total)||0;
    const sweep = (val/total)*Math.PI*2;
    ctx.beginPath(); ctx.moveTo(cx,cy);
    ctx.arc(cx,cy,r,start,start+sweep); ctx.closePath();
    ctx.fillStyle = colors[i%colors.length]; ctx.fill();
    start += sweep;
  });

  // legend
  ctx.font='12px system-ui'; let y=8;
  data.forEach((d,i)=>{
    ctx.fillStyle = colors[i%colors.length]; ctx.fillRect(8,y+3,10,10);
    ctx.fillStyle = '#333'; ctx.fillText(`${d.category}: ${d.total}`, 24, y);
    y+=18;
  });
});
