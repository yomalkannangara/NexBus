(function(){
  const NB = window.NBCharts;

  NB.onReady(function(){
    const cvs = document.getElementById("waitTimeChart"); if(!cvs) return;
    const D = NB.getData(), F = window.ANALYTICS_DUMMY || {};
    let list = Array.isArray(D?.waitTime) && D.waitTime.length ? D.waitTime : (F.waitTime || []);
    if(!list.length){
      list = [
        { label:"Under 5 min", value:65, color: NB.colors.green },
        { label:"10–15 min",  value:10, color: NB.colors.gold  },
        { label:"15–20 min",  value: 3, color: "#f59e0b"       },
        { label:"Over 20 min",value: 2, color: NB.colors.red   },
      ];
    }

    NB.observe(cvs, 7/4, ({ctx,W,H})=>{
      ctx.clearRect(0,0,W,H);
      const cx=W/2, cy=H/2, R=Math.min(W,H)*0.36, r=R*0.62;
      const total=list.reduce((s,d)=>s+(+d.value||0),0)||1;
      let a0=-Math.PI/2;
      ctx.shadowColor="rgba(0,0,0,.15)"; ctx.shadowBlur=12;
      list.forEach(seg=>{
        const ang=(+seg.value/total)*Math.PI*2; if(ang<=0) return;
        ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,R,a0,a0+ang); ctx.closePath();
        ctx.fillStyle=seg.color; ctx.fill(); a0+=ang;
      });
      ctx.shadowBlur=0; ctx.globalCompositeOperation="destination-out";
      ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.fill();
      ctx.globalCompositeOperation="source-over";
      ctx.fillStyle="#2b2b2b"; ctx.font="700 16px ui-sans-serif"; ctx.textAlign="center";
      ctx.fillText("Wait Time", cx, cy-6);
      ctx.fillStyle="#6b7280"; ctx.font="12px ui-sans-serif";
      ctx.fillText(total+"%", cx, cy+14);

      NB.setLegend(cvs.parentNode, list.map(d=>({label:`${d.label} ${d.value}%`, color:d.color})));
    });
  });
})();
