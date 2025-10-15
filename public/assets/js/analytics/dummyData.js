(function(){
  window.ANALYTICS_DUMMY = {
    kpi: { delayedToday: 47, avgRating: 8.0, speedViol: 75, longWaitPct: 15 },
    busStatus: [
      { label: "On Time", value: 62, color: "#16a34a" },
      { label: "Delayed", value: 25, color: "#b91c1c" },
      { label: "Cancelled", value: 5,  color: "#ef4444" },
      { label: "Maintenance", value: 8, color: "#f59e0b" }
    ],
    onTime: {
      labels: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
      values: [88,84,90,92,87,93,95]
    },
    revenue: {
      labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
      values: [3.2,3.8,4.1,4.7,4.9,5.3,5.0,5.6,5.1,5.8,6.2,6.0]
    },
    complaints: {
      labels: ["NB-0789","NB-2341","NB-9876","NB-7890","NB-9980","NB-2222","NB-4444","NB-4567","NB-6677","NB-8899"],
      points: [15,22,9,18,7,6,12,19,27,11]
    },
    utilization: {
      labels: ["138","45","67","99","102","120","85","177"],
      values: [72,68,75,80,74,77,69,83]
    }
  };
})();
