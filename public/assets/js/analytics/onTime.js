(function(){
  function ready(f){document.readyState!=="loading"?f():document.addEventListener("DOMContentLoaded",f);}
  function J(){try{const el=document.getElementById("analytics-data");return el?JSON.parse(el.textContent||"{}"):{}}catch{return {}}}
  function ctx2d(c){if(!c)return null;const dpr=Math.max(1,window.devicePixelRatio||1);const w=c.width,h=c.height;c.style.width=w+"px";c.style.height=h+"px";c.width=Math.round(w*dpr);c.height=Math.round(h*dpr);const g=c.getContext("2d");g.scale(dpr,dpr);return g;}
  ready(function(){
    const cvs=document.getElementById("onTimeChart"); if(!cvs) return;
    const ctx=ctx2d(cvs); if(!ctx) return;
    const D=J(); const F=window.ANALYTICS_DUMMY||{};
    const labels=(Array.isArray(D?.onTime?.labels)&&D.onTime.labels.length?D.onTime.labels:F.onTime.labels);
    const vals=(Array.isArray(D?.onTime?.values)&&D.onTime.values.length?D.onTime.values:F.onTime.values).map(n=>+n||0);
    const W=cvs.width/(window.devicePixelRatio||1), H=cvs.height/(window.devicePixelRatio||1);
    const pad={l:48,r:16,t:16,b:36}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
    const X=i=>pad.l+(iw*i)/(vals.length-1), Y=v=>pad.t+ih-(v/100)*ih;

    ctx.strokeStyle="rgba(232,211,154,.45)"; ctx.lineWidth=1; ctx.setLineDash([3,4]);
    for(let k=0;k<=5;k++){const y=pad.t+ih*(k/5); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke();}
    ctx.setLineDash([]);

    ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
    labels.forEach((lb,i)=>ctx.fillText(lb,X(i)-8,H-12));

    ctx.beginPath(); vals.forEach((v,i)=>i?ctx.lineTo(X(i),Y(v)):ctx.moveTo(X(i),Y(v)));
    ctx.lineTo(pad.l+iw,pad.t+ih); ctx.lineTo(pad.l,pad.t+ih); ctx.closePath();
    ctx.fillStyle="rgba(128,20,60,.08)"; ctx.fill();

    ctx.beginPath(); vals.forEach((v,i)=>i?ctx.lineTo(X(i),Y(v)):ctx.moveTo(X(i),Y(v)));
    ctx.strokeStyle="#80143c"; ctx.lineWidth=2.5; ctx.stroke();

    vals.forEach((v,i)=>{ctx.beginPath();ctx.arc(X(i),Y(v),4,0,Math.PI*2);ctx.fillStyle="#f3b944";ctx.fill();ctx.lineWidth=1.5;ctx.strokeStyle="#80143c";ctx.stroke();});

    for(let yv=0; yv<=100; yv+=20) ctx.fillText(yv+"%", pad.l-34, Y(yv)+4);
  });
})();
