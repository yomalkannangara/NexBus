(function(){
  const NB = window.NBCharts;

  NB.onReady(function(){
    const cvs=document.getElementById("delayedByRouteChart"); if(!cvs) return;
    const D=NB.getData(), F=window.ANALYTICS_DUMMY||{};
    const src=(D?.delayedByRoute?.labels?.length?D.delayedByRoute:F.delayedByRoute);
    const labels=src.labels, delayed=src.delayed.map(n=>+n||0), total=src.total.map(n=>+n||0);

    NB.observe(cvs, 7/4, ({ctx,W,H})=>{
      ctx.clearRect(0,0,W,H);
      const pad={l:56,r:16,t:14,b:56}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
      const max=Math.max(10, Math.ceil(Math.max(...total, ...delayed)/5)*5);
      const groupW=iw/labels.length, gap=8;
      const barW=Math.min(28, (groupW-gap)/2);

      // grid dashed
      ctx.strokeStyle=NB.colors.grid; ctx.setLineDash([3,6]);
      for(let y=pad.t; y<=pad.t+ih+0.5; y+=ih/5){ ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke(); }
      ctx.setLineDash([]);

      // axes
      ctx.strokeStyle="#6b7280"; ctx.lineWidth=2;
      ctx.beginPath(); ctx.moveTo(pad.l, pad.t); ctx.lineTo(pad.l, pad.t+ih); ctx.lineTo(W-pad.r, pad.t+ih); ctx.stroke();

      // draw two series
      function bar(x,y,w,h,color){ ctx.fillStyle=color; ctx.fillRect(x,y,w,h); }
      labels.forEach((_,i)=>{
        const center = pad.l + i*groupW + groupW/2;
        const h1 = (delayed[i]/max)*ih, y1=pad.t+ih-h1;
        const h2 = (total[i]/max)*ih,   y2=pad.t+ih-h2;
        bar(center - gap/2 - barW, y1, barW, h1, NB.colors.coral);
        bar(center + gap/2,        y2, barW, h2, NB.colors.maroonDark);
      });

      // labels
      ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif"; ctx.textAlign="center";
      labels.forEach((lb,i)=>{ const x=pad.l + i*groupW + groupW/2; ctx.fillText(lb, x, H-14); });

      for(let yv=0; yv<=max; yv+=Math.max(5,Math.round(max/5))){
        const y=pad.t+ih-(yv/max)*ih; ctx.fillText(String(yv), pad.l-26, y+4);
      }

      NB.setLegend(cvs.parentNode, [
        {label:"Delayed Buses", color: NB.colors.coral},
        {label:"Total Buses",   color: NB.colors.maroonDark}
      ]);
    });
  });
})();
