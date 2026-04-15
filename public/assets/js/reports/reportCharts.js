/**
 * reportCharts.js — All Chart.js chart builders for the depot officer reports.
 * Each function is exported as a property of window.ReportCharts.
 *
 * Rules applied to ALL charts:
 *  - System color palette (no Chart.js defaults)
 *  - Tooltips: dark bg #1F2937
 *  - Grid: #F3E8E8, 1px
 *  - Legend: bottom
 *  - Responsive, maintainAspectRatio: false
 *  - Destroy existing instance before re-render
 */
(function () {
  'use strict';

  /* ── Palette ─────────────────────────────────────────── */
  const C = {
    maroon:  '#7B1C3E',
    green:   '#16A34A',
    amber:   '#F59E0B',
    red:     '#DC2626',
    blue:    '#2563EB',
    purple:  '#7C3AED',
    orange:  '#EA580C',
    grey:    '#9CA3AF',
    dark:    '#1F2937',
  };

  /* ── Instance registry (destroy before re-render) ────── */
  const chartInstances = {};

  function destroyChart(canvasId) {
    if (chartInstances[canvasId]) {
      chartInstances[canvasId].destroy();
      delete chartInstances[canvasId];
    }
  }

  /* ── Shared defaults ────────────────────────────────── */
  const sharedDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { font: { family: 'system-ui,sans-serif', size: 12 }, padding: 14 } },
      tooltip: {
        backgroundColor: C.dark,
        titleColor: '#F9FAFB',
        bodyColor: '#D1D5DB',
        cornerRadius: 8,
        padding: 10,
      },
    },
    scales: {
      x: { grid: { color: '#F3E8E8', lineWidth: 1 }, ticks: { font: { family: 'system-ui,sans-serif', size: 11 } } },
      y: { grid: { color: '#F3E8E8', lineWidth: 1 }, ticks: { font: { family: 'system-ui,sans-serif', size: 11 } } },
    },
  };

  function mergeDeep(target, source) {
    const out = Object.assign({}, target);
    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        out[key] = mergeDeep(target[key] || {}, source[key]);
      } else {
        out[key] = source[key];
      }
    }
    return out;
  }

  function getCanvas(canvasId) {
    const el = document.getElementById(canvasId);
    if (!el) { console.warn('[ReportCharts] canvas not found:', canvasId); return null; }
    return el;
  }

  /* ════════════════════════════════════════════════════
     1. ATTENDANCE CHART (vertical bar)
        data: [{ name, att_pct }]
  ════════════════════════════════════════════════════ */
  function buildAttendanceChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const labels = data.map(d => {
      const parts = (d.full_name || d.name || '').split(' ');
      return parts.length >= 2 ? parts[0][0] + '.' + parts[parts.length - 1] : parts[0] || '?';
    });
    const values = data.map(d => parseFloat(d.att_pct) || 0);
    const colors = values.map(v => v >= 85 ? C.green : v >= 70 ? C.amber : C.red);

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Attendance %',
          data: values,
          backgroundColor: colors,
          borderWidth: 0,
          borderRadius: 4,
        }],
      },
      options: mergeDeep(sharedDefaults, {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: (ctx) => data[ctx[0].dataIndex]?.full_name || data[ctx[0].dataIndex]?.name || '',
              label: (ctx) => ` Attendance: ${ctx.raw}%`,
            },
          },
        },
        scales: {
          y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
        },
      }),
    });
  }

  /* ════════════════════════════════════════════════════
     2. DRIVER PERFORMANCE CHART (horizontal bar)
        data: [{ driver_name, performance_score, grade }]
  ════════════════════════════════════════════════════ */
  function buildPerformanceChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const gradeColor = { A: C.green, B: C.amber, C: C.orange, D: C.red };
    const labels = data.map(d => d.driver_name || d.full_name || '—');
    const values = data.map(d => parseFloat(d.performance_score) || 0);
    const colors = data.map(d => gradeColor[d.grade] || C.grey);

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Performance Score',
          data: values,
          backgroundColor: colors,
          borderWidth: 0,
          borderRadius: 4,
        }],
      },
      options: mergeDeep(sharedDefaults, {
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          annotation: {
            annotations: {
              refLine: {
                type: 'line', xMin: 70, xMax: 70,
                borderColor: C.red, borderWidth: 2, borderDash: [6, 4],
                label: { content: 'Min 70', display: true, position: 'end', color: C.red, font: { size: 11 } },
              },
            },
          },
        },
        scales: {
          x: { min: 0, max: 100, ticks: { callback: v => v } },
          y: { ticks: { font: { size: 11 } } },
        },
      }),
    });
  }

  /* ════════════════════════════════════════════════════
     3. TRIP COMPLETION CHART (stacked bar per route)
        data: [{ route_no, completed, delayed, cancelled, absent, total_scheduled }]
  ════════════════════════════════════════════════════ */
  function buildCompletionChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const labels = data.map(d => d.route_no || '—');

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Completed',
            data: data.map(d => parseInt(d.completed) || 0),
            backgroundColor: C.green, borderWidth: 0,
          },
          {
            label: 'Delayed',
            data: data.map(d => parseInt(d.delayed) || 0),
            backgroundColor: C.amber, borderWidth: 0,
          },
          {
            label: 'Cancelled',
            data: data.map(d => parseInt(d.cancelled) || 0),
            backgroundColor: C.red, borderWidth: 0,
          },
          {
            label: 'Absent',
            data: data.map(d => parseInt(d.absent) || 0),
            backgroundColor: C.grey, borderWidth: 0,
          },
        ],
      },
      options: mergeDeep(sharedDefaults, {
        plugins: {
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const total = data[ctx.dataIndex]?.total_scheduled || 1;
                const pct = Math.round((ctx.raw / total) * 100);
                return ` ${ctx.dataset.label}: ${ctx.raw} (${pct}%)`;
              },
            },
          },
        },
        scales: {
          x: { stacked: true },
          y: { stacked: true, ticks: { stepSize: 1 } },
        },
      }),
    });
  }

  /* ════════════════════════════════════════════════════
     4a. DELAY BY TIME SLOT CHART (vertical bar)
         data: [{ slot, label, delayedTrips, delayRate }]
         avgDelayRate: number (reference line)
  ════════════════════════════════════════════════════ */
  function buildDelaySlotChart(canvasId, data, avgDelayRate) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const avg = parseFloat(avgDelayRate) || 0;
    const colors = data.map(d => {
      const r = parseFloat(d.delay_rate || d.delayRate) || 0;
      return r >= 60 ? C.red : r >= 35 ? C.orange : r >= 20 ? C.amber : C.green;
    });

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: data.map(d => d.label || d.slot),
        datasets: [{
          label: 'Delay Rate %',
          data: data.map(d => parseFloat(d.delay_rate || d.delayRate) || 0),
          backgroundColor: colors,
          borderWidth: 0,
          borderRadius: 4,
        }],
      },
      options: mergeDeep(sharedDefaults, {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const row = data[ctx.dataIndex];
                return [
                  ` Delay Rate: ${ctx.raw}%`,
                  ` Delayed: ${row.delayed_trips || row.delayedTrips || 0} trips`,
                ];
              },
            },
          },
        },
        scales: {
          y: {
            min: 0, max: 100, ticks: { callback: v => v + '%' },
            afterDataLimits: (axis) => { axis.max = Math.max(100, axis.max); },
          },
        },
      }),
    });

    // Draw reference line annotation (avg delay rate)
    if (avg > 0) {
      const chart = chartInstances[canvasId];
      const origDraw = chart.draw.bind(chart);
      chart.draw = function() {
        origDraw();
        const ctx2 = chart.ctx;
        const yScale = chart.scales.y;
        const xScale = chart.scales.x;
        const y = yScale.getPixelForValue(avg);
        ctx2.save();
        ctx2.strokeStyle = C.maroon;
        ctx2.setLineDash([6, 4]);
        ctx2.lineWidth = 2;
        ctx2.beginPath();
        ctx2.moveTo(xScale.left, y);
        ctx2.lineTo(xScale.right, y);
        ctx2.stroke();
        ctx2.restore();
      };
    }
  }

  /* ════════════════════════════════════════════════════
     4b. DELAY REASON DONUT CHART
         data: [{ reason, count }]
  ════════════════════════════════════════════════════ */
  const reasonColors = {
    'vehicle-breakdown': C.red,
    'vehicle breakdown': C.red,
    'road-obstruction':  C.orange,
    'road obstruction':  C.orange,
    'flooding':          C.blue,
    'driver-absent':     C.amber,
    'driver absent':     C.amber,
    'accident':          C.maroon,
    'emergency':         C.purple,
    'strike':            C.grey,
  };

  function buildDelayReasonChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const labels = data.map(d => humanReason(d.reason));
    const values = data.map(d => parseInt(d.count) || 0);
    const colors = data.map(d => {
      const key = (d.reason || '').toLowerCase().replace(/_/g, '-');
      return reasonColors[key] || C.grey;
    });

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderWidth: 3,
          borderColor: '#fff',
          hoverOffset: 8,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { family: 'system-ui,sans-serif', size: 12 }, padding: 14, boxWidth: 14 },
          },
          tooltip: {
            backgroundColor: C.dark, titleColor: '#F9FAFB', bodyColor: '#D1D5DB',
            cornerRadius: 8, padding: 10,
            callbacks: {
              label: (ctx) => {
                const total = values.reduce((a, b) => a + b, 0) || 1;
                const pct = Math.round((ctx.raw / total) * 100);
                return ` ${ctx.raw} (${pct}%)`;
              },
            },
          },
        },
      },
    });
  }

  /* ════════════════════════════════════════════════════
     5. BUS UTILIZATION CHART (horizontal bar)
        data: [{ bus_reg_no, utilization_pct }]
  ════════════════════════════════════════════════════ */
  function buildUtilizationChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    // Sort descending by utilization
    const sorted = [...data].sort((a, b) => (parseFloat(b.utilization_pct) || 0) - (parseFloat(a.utilization_pct) || 0));
    const display = sorted.slice(0, 20);

    const colors = display.map(d => {
      const v = parseFloat(d.utilization_pct) || 0;
      return v >= 80 ? C.green : v >= 60 ? C.amber : C.red;
    });

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: display.map(d => d.bus_reg_no || d.busNumber || '—'),
        datasets: [{
          label: 'Utilization %',
          data: display.map(d => parseFloat(d.utilization_pct) || 0),
          backgroundColor: colors,
          borderWidth: 0,
          borderRadius: 4,
        }],
      },
      options: mergeDeep(sharedDefaults, {
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (ctx) => ` Utilization: ${ctx.raw}%` } },
        },
        scales: {
          x: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
          y: { ticks: { font: { size: 11 } } },
        },
      }),
    });
  }

  /* ── Helper ─────────────────────────────────────────── */
  function humanReason(reason) {
    if (!reason) return 'Unknown';
    return reason.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  /* ════════════════════════════════════════════════════
     6. GRADE DISTRIBUTION DONUT
        data: [{ grade, performance_score }]   (full hrRows)
  ════════════════════════════════════════════════════ */
  function buildGradeDonutChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const counts = { A: 0, B: 0, C: 0, D: 0 };
    data.forEach(d => { if (counts[d.grade] !== undefined) counts[d.grade]++; });
    const labels = ['Grade A (≥85)', 'Grade B (70–84)', 'Grade C (55–69)', 'Grade D (<55)'];
    const values = [counts.A, counts.B, counts.C, counts.D];
    const colors = [C.green, C.amber, C.orange, C.red];

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderColor: '#fff',
          borderWidth: 3,
          hoverOffset: 8,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { family: 'system-ui,sans-serif', size: 12 }, padding: 12, boxWidth: 14 },
          },
          tooltip: {
            backgroundColor: C.dark, titleColor: '#F9FAFB', bodyColor: '#D1D5DB',
            cornerRadius: 8, padding: 10,
            callbacks: {
              label: (ctx) => {
                const total = values.reduce((a, b) => a + b, 0) || 1;
                return ` ${ctx.raw} driver${ctx.raw !== 1 ? 's' : ''} (${Math.round(ctx.raw / total * 100)}%)`;
              },
            },
          },
        },
      },
    });
  }

  /* ════════════════════════════════════════════════════
     7. DRIVER TRIP BEHAVIOUR STACKED BAR
        data: [{ driver_name, completed, delayed, cancelled }]
  ════════════════════════════════════════════════════ */
  function buildDriverStackedChart(canvasId, data) {
    const canvas = getCanvas(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);

    const display = data.slice(0, 15); // top 15 by trips assigned
    const shortName = n => {
      const p = (n || '').split(' ');
      return p.length >= 2 ? p[0] + ' ' + p[p.length - 1][0] + '.' : p[0] || '?';
    };
    const labels  = display.map(d => shortName(d.driver_name));
    const onTime  = display.map(d => Math.max(0, (parseInt(d.completed) || 0) - (parseInt(d.delayed) || 0)));
    const delayed = display.map(d => parseInt(d.delayed)   || 0);
    const cancel  = display.map(d => parseInt(d.cancelled) || 0);

    chartInstances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'On-Time',  data: onTime,  backgroundColor: C.green,  borderWidth: 0, borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 3, bottomRight: 3 } },
          { label: 'Delayed',  data: delayed, backgroundColor: C.amber,  borderWidth: 0, borderRadius: 0 },
          { label: 'Cancelled',data: cancel,  backgroundColor: C.red,    borderWidth: 0, borderRadius: { topLeft: 3, topRight: 3, bottomLeft: 0, bottomRight: 0 } },
        ],
      },
      options: mergeDeep(sharedDefaults, {
        plugins: {
          legend: { display: true },
          tooltip: {
            callbacks: {
              title: (ctx) => display[ctx[0].dataIndex]?.driver_name || '',
              label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw} trip${ctx.raw !== 1 ? 's' : ''}`,
            },
          },
        },
        scales: {
          x: { stacked: true, ticks: { font: { size: 11 } } },
          y: { stacked: true, ticks: { stepSize: 1 } },
        },
      }),
    });
  }

  /* ── Public API ─────────────────────────────────────── */
  window.ReportCharts = {
    buildAttendanceChart,
    buildPerformanceChart,
    buildCompletionChart,
    buildDelaySlotChart,
    buildDelayReasonChart,
    buildUtilizationChart,
    buildGradeDonutChart,
    buildDriverStackedChart,
    destroyAll: function() {
      Object.keys(chartInstances).forEach(id => {
        chartInstances[id].destroy();
        delete chartInstances[id];
      });
    },
  };
})();
