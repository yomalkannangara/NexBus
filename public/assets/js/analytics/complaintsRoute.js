(function(){
  const NB = window.NBCharts;

  NB.onReady(function(){
    const cvs=document.getElementById("complaintsRouteChart"); if(!cvs) return;
    const D=NB.getData(), F=window.ANALYTICS_DUMMY||{};
    const labels=(D?.complaintsByRoute?.labels?.length?D.complaintsByRoute.labels:F.complaintsByRoute.labels);
    const vals=(D?.complaintsByRoute?.values?.length?D.complaintsByRoute.values:F.complaintsByRoute.values).map(n=>+n||0);

    NB.observe(cvs, 7/4, ({ctx,W,H})=>{
      ctx.clearRect(0,0,W,H);
      const pad={l:56,r:16,t:16,b:56}, iw=W-pad.l-pad.r, ih=H-pad.t-pad.b;
      const max=Math.max(10, Math.ceil(Math.max(...vals)/5)*5);
      const barW=iw/labels.length*0.6;

      ctx.strokeStyle=NB.colors.grid; ctx.setLineDash([3,6]);
      for(let k=0;k<=5;k++){const y=pad.t+ih*(k/5); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(W-pad.r,y); ctx.stroke();}
      ctx.setLineDash([]);

      ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
      labels.forEach((lb,i)=>{const x=pad.l+(iw*i)/labels.length+barW*0.2; ctx.fillText(lb, x, H-12);});

      vals.forEach((v,i)=>{
        const x=pad.l+(iw*i)/labels.length + ((iw/labels.length - barW)/2);
        const h=(v/max)*ih, y=pad.t+ih-h;
        ctx.fillStyle=NB.colors.maroon; ctx.fillRect(x, y, barW, h);
      });

      for(let yv=0; yv<=max; yv+=Math.max(5,Math.round(max/5))){
        const y=pad.t+ih-(yv/max)*ih; ctx.fillText(yv, pad.l-26, y+4);
      }
    });
  });
})();
