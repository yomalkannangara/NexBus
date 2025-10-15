(function(){
  window.ANALYTICS_DUMMY = {
    kpi: { delayedToday: 47, avgRating: 8.0, speedViol: 75, longWaitPct: 15 },

    busStatus: [
      { label: "On Time",     value: 62, color: "#16a34a" },
      { label: "Delayed",     value: 25, color: "#b91c1c" },
      { label: "Cancelled",   value:  5, color: "#ef4444" },
      { label: "Maintenance", value:  8, color: "#f59e0b" }
    ],

    revenue: {
      labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
      values: [3.2,3.8,4.1,4.7,4.9,5.3,5.0,5.6,5.1,5.8,6.2,6.0]
    },

    // NEW
    speedByBus: {
      labels: ["NB-0789","NB-2341","NB-9876","NB-7890","NB-9980","NB-2222","NB-4444","NB-6677","NB-8899"],
      values: [5,9,3,7,12,4,6,11,8] // violations per bus
    },

    // NEW (donut)
    waitTime: [
      { label: "Under 5 min", value: 65, color: "#16a34a" },
      { label: "10–15 min",   value: 10, color: "#f3b944" },
      { label: "15–20 min",   value:  3, color: "#f59e0b" },
      { label: "Over 20 min", value:  2, color: "#b91c1c" }
    ],

    // NEW (grouped bars like your screenshot)
    delayedByRoute: {
      labels: ["138","45","67","99","102","120","85","177"],
      delayed: [10,6,13,4,9,6,8,3],
      total:   [45,31,38,28,35,26,30,20]
    },

    // CHANGED: complaints aggregated by route (bars)
    complaintsByRoute: {
      labels: ["138","45","67","99","102","120","85","177"],
      values: [18,12,9,7,11,8,10,6]
    }
  };
})();
