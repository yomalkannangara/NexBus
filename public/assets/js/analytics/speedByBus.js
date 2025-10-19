(function(){
  const NB = window.NBCharts;

  NB.onReady(function(){
    const cvs=document.getElementById("speedByBusChart"); if(!cvs) return;
    const D=NB.getData(), F=window.ANALYTICS_DUMMY||{};
    const labels=(D?.speedByBus?.labels?.length?D.speedByBus.labels:F.speedByBus.labels);
    const vals=(D?.speedByBus?.values?.length?D.speedByBus.values:F.speedByBus.values).map(n=>+n||0);

    NB.observe(cvs, 7/4, ({ctx,W,H})=>{
      ctx.clearRect(0,0,W,H);
      const pad={l:56,r:16,t:16,b:56}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
      const max=Math.max(5, Math.ceil(Math.max(...vals)/5)*5);
      const barW=iw/labels.length*0.6;

      // grid
      ctx.strokeStyle=NB.colors.grid; ctx.setLineDash([3,6]);
      for(let k=0;k<=5;k++){const y=pad.t+ih*(k/5); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke();}
      ctx.setLineDash([]);

      // x labels rotated a bit
      ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
      labels.forEach((lb,i)=>{const x=pad.l+(iw*i)/labels.length+barW*0.2; ctx.save(); ctx.translate(x,H-8); ctx.rotate(-Math.PI/6); ctx.fillText(lb,0,0); ctx.restore();});

      // bars
      vals.forEach((v,i)=>{
        const x=pad.l+(iw*i)/labels.length + ((iw/labels.length - barW)/2);
        const h=(v/max)*ih, y=pad.t+ih-h, r=8;
        const g=ctx.createLinearGradient(0,y,0,y+h); g.addColorStop(0,"#ffb078"); g.addColorStop(1,NB.colors.orange);
        ctx.fillStyle=g;
        ctx.beginPath();
        ctx.moveTo(x, y+r); ctx.arcTo(x,y,x+r,y,r);
        ctx.lineTo(x+barW-r, y); ctx.arcTo(x+barW,y, x+barW, y+r, r);
        ctx.lineTo(x+barW, y+h); ctx.lineTo(x, y+h);
        ctx.closePath(); ctx.fill();
        ctx.strokeStyle="rgba(128,20,60,.2)"; ctx.lineWidth=1; ctx.stroke();
      });

      // y labels
      for(let yv=0; yv<=max; yv+=Math.max(1,Math.round(max/6))){
        const y=pad.t+ih-(yv/max)*ih; ctx.fillText(yv, pad.l-26, y+4);
      }
    });
  });
})();
