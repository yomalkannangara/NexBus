(function(){
  function ready(f){document.readyState!=="loading"?f():document.addEventListener("DOMContentLoaded",f);}
  function J(){try{const el=document.getElementById("analytics-data");return el?JSON.parse(el.textContent||"{}"):{}}catch{return {}}}
  function ctx2d(c){if(!c)return null;const dpr=Math.max(1,window.devicePixelRatio||1);const w=c.width,h=c.height;c.style.width=w+"px";c.style.height=h+"px";c.width=Math.round(w*dpr);c.height=Math.round(h*dpr);const g=c.getContext("2d");g.scale(dpr,dpr);return g;}
  ready(function(){
    const cvs=document.getElementById("revenueChart"); if(!cvs) return;
    const ctx=ctx2d(cvs); if(!ctx) return;
    const D=J(); const F=window.ANALYTICS_DUMMY||{};
    const labels=(Array.isArray(D?.revenue?.labels)&&D.revenue.labels.length?D.revenue.labels:F.revenue.labels);
    const vals=(Array.isArray(D?.revenue?.values)&&D.revenue.values.length?D.revenue.values:F.revenue.values).map(n=>+n||0);

    const W=cvs.width/(window.devicePixelRatio||1), H=cvs.height/(window.devicePixelRatio||1);
    const pad={l:48,r:16,t:16,b:36}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
    const max=Math.max(7, Math.ceil(Math.max(...vals)));
    const barW=iw/labels.length*0.62;

    ctx.strokeStyle="rgba(232,211,154,.45)"; ctx.lineWidth=1; ctx.setLineDash([3,4]);
    for(let k=0;k<=6;k++){const y=pad.t+ih*(k/6); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke();}
    ctx.setLineDash([]);

    ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
    labels.forEach((lb,i)=>{const x=pad.l+(iw*i)/labels.length+barW*0.2; ctx.fillText(lb, x, H-12);});

    vals.forEach((v,i)=>{
      const x=pad.l+(iw*i)/labels.length + ((iw/labels.length - barW)/2);
      const h=(v/max)*ih, y=pad.t+ih-h, r=8;
      const g=ctx.createLinearGradient(0,y,0,y+h); g.addColorStop(0,"#f6d36d"); g.addColorStop(1,"#f3b944");
      ctx.fillStyle=g;
      ctx.beginPath();
      ctx.moveTo(x, y+r);
      ctx.arcTo(x, y, x+r, y, r);
      ctx.lineTo(x+barW-r, y);
      ctx.arcTo(x+barW, y, x+barW, y+r, r);
      ctx.lineTo(x+barW, y+h); ctx.lineTo(x, y+h);
      ctx.closePath(); ctx.fill();
      ctx.strokeStyle="rgba(128,20,60,.25)"; ctx.lineWidth=1; ctx.stroke();
    });

    for(let yv=0; yv<=max; yv+=1){
      const y=pad.t+ih-(yv/max)*ih;
      ctx.fillText(yv+"M", pad.l-34, y+4);
    }
  });
})();
