(function(){
  function ready(f){document.readyState!=="loading"?f():document.addEventListener("DOMContentLoaded",f);}
  function J(){try{const el=document.getElementById("analytics-data");return el?JSON.parse(el.textContent||"{}"):{}}catch{return {}}}
  function ctx2d(c){if(!c)return null;const dpr=Math.max(1,window.devicePixelRatio||1);const w=c.width,h=c.height;c.style.width=w+"px";c.style.height=h+"px";c.width=Math.round(w*dpr);c.height=Math.round(h*dpr);const g=c.getContext("2d");g.scale(dpr,dpr);return g;}
  ready(function(){
    const cvs=document.getElementById("complaintsChart"); if(!cvs) return;
    const ctx=ctx2d(cvs); if(!ctx) return;
    const D=J(); const F=window.ANALYTICS_DUMMY||{};
    const labels=(Array.isArray(D?.complaints?.labels)&&D.complaints.labels.length?D.complaints.labels:F.complaints.labels);
    const pts=(Array.isArray(D?.complaints?.points)&&D.complaints.points.length?D.complaints.points:F.complaints.points).map(n=>+n||0);

    const W=cvs.width/(window.devicePixelRatio||1), H=cvs.height/(window.devicePixelRatio||1);
    const pad={l:60,r:16,t:16,b:56}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
    const max=Math.max(30, Math.ceil(Math.max(...pts)/5)*5);
    const X=i=>pad.l+(iw*i)/(labels.length-1), Y=v=>pad.t+ih-(v/max)*ih;

    ctx.strokeStyle="rgba(232,211,154,.45)"; ctx.lineWidth=1; ctx.setLineDash([3,4]);
    for(let k=0;k<=6;k++){const y=pad.t+ih*(k/6); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke();}
    ctx.setLineDash([]);

    pts.forEach((v,i)=>{ctx.beginPath(); ctx.arc(X(i),Y(v),5,0,Math.PI*2); ctx.fillStyle="#b02a3b"; ctx.fill(); ctx.lineWidth=2; ctx.strokeStyle="rgba(176,42,59,.25)"; ctx.stroke();});

    ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
    for(let yv=0; yv<=max; yv+=6) ctx.fillText(yv, pad.l-26, Y(yv)+4);

    labels.forEach((lb,i)=>{ctx.save(); ctx.translate(X(i),H-6); ctx.rotate(-Math.PI/6); ctx.fillText(lb,0,0); ctx.restore();});
  });
})();
