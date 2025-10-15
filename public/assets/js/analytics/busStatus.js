(function () {
  function ready(fn){document.readyState!=="loading"?fn():document.addEventListener("DOMContentLoaded",fn);}
  function getJSON(){try{const el=document.getElementById("analytics-data");return el?JSON.parse(el.textContent||"{}"):{}}catch{return {}}}
  function ctx2d(c){if(!c)return null;const dpr=Math.max(1,window.devicePixelRatio||1);const w=c.width,h=c.height;c.style.width=w+"px";c.style.height=h+"px";c.width=Math.round(w*dpr);c.height=Math.round(h*dpr);const g=c.getContext("2d");g.scale(dpr,dpr);return g;}

  ready(function(){
    const canvas=document.getElementById("busStatusChart"); if(!canvas) return;
    const ctx=ctx2d(canvas); if(!ctx) return;

    const DEFAULT = (window.ANALYTICS_DUMMY && window.ANALYTICS_DUMMY.busStatus) || [];
    const ORDER    = ["On Time","Delayed","Cancelled","Maintenance"];
    const PALETTE  = ["#16a34a","#b91c1c","#ef4444","#f59e0b","#0ea5e9","#f97316"];

    function normalize(raw){
      if (!raw) return [];
      if (Array.isArray(raw) && raw.length && raw.every(v => v!==null && v!=="" && !isNaN(+v))){
        return raw.slice(0, ORDER.length).map((v,i)=>({label:ORDER[i]||`Cat ${i+1}`, value:+v, color:PALETTE[i%PALETTE.length]}));
      }
      if (Array.isArray(raw) && raw.length){
        return raw.map((it,i)=>{
          if (Array.isArray(it))  return {label: it[0] ?? ORDER[i] ?? `Cat ${i+1}`, value: +it[1]||0, color: it[2] || PALETTE[i%PALETTE.length]};
          if (it && typeof it==="object") return {label: it.label ?? ORDER[i] ?? `Cat ${i+1}`, value: +it.value||0, color: it.color || PALETTE[i%PALETTE.length]};
          return {label: ORDER[i] ?? `Cat ${i+1}`, value: 0, color: PALETTE[i%PALETTE.length]};
        });
      }
      if (typeof raw==="object"){
        return Object.keys(raw).map((k,i)=>({label:k, value:+raw[k]||0, color:PALETTE[i%PALETTE.length]}));
      }
      return [];
    }

    const fromPHP = getJSON().busStatus;
    let list = normalize(fromPHP);
    if (!list.length || list.reduce((s,d)=>s+(+d.value||0),0)<=0) list = DEFAULT.slice();

    // draw donut
    const W=canvas.width/(window.devicePixelRatio||1), H=canvas.height/(window.devicePixelRatio||1);
    const cx=W/2, cy=H/2, R=Math.min(W,H)*0.42, r=R*0.62;
    const total=list.reduce((s,d)=>s+(+d.value||0),0)||1;
    let a0=-Math.PI/2;

    ctx.shadowColor="rgba(0,0,0,.15)"; ctx.shadowBlur=12;
    list.forEach(seg=>{
      const ang=(seg.value/total)*Math.PI*2; if(ang<=0) return;
      ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,R,a0,a0+ang); ctx.closePath();
      ctx.fillStyle=seg.color; ctx.fill(); a0+=ang;
    });

    // hole + labels
    ctx.shadowBlur=0; ctx.globalCompositeOperation="destination-out";
    ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.fill();
    ctx.globalCompositeOperation="source-over";
    ctx.fillStyle="#2b2b2b"; ctx.font="700 16px ui-sans-serif"; ctx.textAlign="center";
    ctx.fillText("Bus Status", cx, cy-6);
    ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif"; ctx.fillText(total+" total", cx, cy+14);

    // HTML legend (wraps nicely)
    const old = canvas.parentNode.querySelector('.chart-legend'); if (old) old.remove();
    const div = document.createElement('div'); div.className='chart-legend';
    list.forEach(seg=>{
      const span=document.createElement('span'); span.className='legend-item';
      const sw=document.createElement('i'); sw.style.background=seg.color;
      span.appendChild(sw); span.appendChild(document.createTextNode(`${seg.label} (${seg.value})`));
      div.appendChild(span);
    });
    canvas.parentNode.appendChild(div);

    // KPIs (fallbacks from dummy if PHP absent)
    const K = (getJSON().kpi || window.ANALYTICS_DUMMY?.kpi || {});
    const delayed=document.querySelector('.kpi-card.alert .num');  if(delayed) delayed.textContent = Number.isFinite(+K.delayedToday)?+K.delayedToday:47;
    const rating =document.querySelector('.kpi-card.ok .num');     if(rating)  rating.textContent  = Number.isFinite(+K.avgRating)?(+K.avgRating).toFixed(1):"8.0";
    const viol   =document.querySelector('.kpi-card.warn .num');   if(viol)    viol.textContent    = Number.isFinite(+K.speedViol)?+K.speedViol:75;
    const wait   =document.querySelector('.kpi-card.info .num');   if(wait)    wait.textContent    = (Number.isFinite(+K.longWaitPct)?+K.longWaitPct:15)+"%";
  });
})();
