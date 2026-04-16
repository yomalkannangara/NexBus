(function () {
  'use strict';

  var chartInstances = [];
  var heatmaps = [];

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }
    fn();
  }

  function parsePayload() {
    try {
      var el = document.getElementById('admin-analytics-detail-data');
      return el ? JSON.parse(el.textContent || '{}') : {};
    } catch (err) {
      return {};
    }
  }

  function hexToRgba(hex, alpha) {
    var value = String(hex || '').replace('#', '');
    if (value.length === 3) {
      value = value.split('').map(function (c) { return c + c; }).join('');
    }
    if (value.length !== 6) {
      return 'rgba(37,99,235,' + alpha + ')';
    }
    var r = parseInt(value.slice(0, 2), 16);
    var g = parseInt(value.slice(2, 4), 16);
    var b = parseInt(value.slice(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function destroyCharts() {
    chartInstances.forEach(function (c) {
      try {
        c.destroy();
      } catch (err) {
        // no-op
      }
    });
    chartInstances = [];
  }

  function buildChartConfig(chart) {
    var ctype = String(chart.type || 'bar');
    var chartType = 'bar';
    var indexAxis = 'x';

    if (ctype === 'line') {
      chartType = 'line';
    } else if (ctype === 'horizontalBar') {
      chartType = 'bar';
      indexAxis = 'y';
    }

    var stacked = ctype === 'stackedBar';
    var labels = Array.isArray(chart.labels) ? chart.labels : [];
    var datasets = Array.isArray(chart.datasets) ? chart.datasets : [];

    var mappedSets = datasets.map(function (ds, idx) {
      var color = ds.color || ['#2563eb', '#dc2626', '#f59e0b', '#16a34a', '#7c3aed'][idx % 5];
      var base = {
        label: ds.label || ('Series ' + (idx + 1)),
        data: Array.isArray(ds.data) ? ds.data : [],
        borderColor: color,
        backgroundColor: chartType === 'line' ? hexToRgba(color, 0.16) : color,
        borderWidth: 2,
        maxBarThickness: 34,
      };

      if (chartType === 'line') {
        base.backgroundColor = hexToRgba(color, 0.16);
        base.fill = false;
        base.tension = 0.32;
        base.pointRadius = 2;
        base.pointHoverRadius = 4;
      }

      return base;
    });

    var xScale = {
      stacked: stacked,
      title: {
        display: !!chart.xLabel,
        text: chart.xLabel || '',
      },
      ticks: {
        maxRotation: indexAxis === 'x' ? 40 : 0,
        minRotation: 0,
        autoSkip: true,
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.2)',
      },
    };

    var yScale = {
      stacked: stacked,
      beginAtZero: true,
      title: {
        display: !!chart.yLabel,
        text: chart.yLabel || '',
      },
      ticks: {
        precision: 0,
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.2)',
      },
    };

    return {
      type: chartType,
      data: {
        labels: labels,
        datasets: mappedSets,
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: indexAxis,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            display: mappedSets.length > 1,
            position: 'bottom',
          },
          tooltip: {
            enabled: true,
          },
        },
        scales: indexAxis === 'y'
          ? { x: yScale, y: xScale }
          : { x: xScale, y: yScale },
      },
    };
  }

  function fitCanvas(canvas) {
    var parent = canvas.parentElement;
    var cssW = Math.max(280, Math.floor(parent.clientWidth || 640));
    var cssH = Math.max(220, Math.floor(parent.clientHeight || 260));
    var dpr = Math.max(1, window.devicePixelRatio || 1);

    canvas.style.width = cssW + 'px';
    canvas.style.height = cssH + 'px';
    canvas.width = Math.floor(cssW * dpr);
    canvas.height = Math.floor(cssH * dpr);

    var ctx = canvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    return { ctx: ctx, width: cssW, height: cssH };
  }

  function heatColor(value, max) {
    if (max <= 0) {
      return '#f1f5f9';
    }
    var t = Math.max(0, Math.min(1, value / max));
    var hue = Math.round(120 - (120 * t));
    var lightness = 92 - Math.round(40 * t);
    return 'hsl(' + hue + ', 78%, ' + lightness + '%)';
  }

  function drawHeatmap(canvas, chart) {
    var fit = fitCanvas(canvas);
    var ctx = fit.ctx;
    var W = fit.width;
    var H = fit.height;

    var xLabels = Array.isArray(chart.xLabels) ? chart.xLabels : [];
    var yLabels = Array.isArray(chart.yLabels) ? chart.yLabels : [];
    var matrix = Array.isArray(chart.matrix) ? chart.matrix : [];

    ctx.clearRect(0, 0, W, H);

    if (!xLabels.length || !yLabels.length || !matrix.length) {
      ctx.fillStyle = '#9ca3af';
      ctx.font = '14px Segoe UI';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('No heatmap data for selected filters', W / 2, H / 2);
      return;
    }

    var pad = { left: 62, right: 16, top: 18, bottom: 28 };
    var iw = W - pad.left - pad.right;
    var ih = H - pad.top - pad.bottom;

    var rows = yLabels.length;
    var cols = xLabels.length;
    var cellW = iw / Math.max(1, cols);
    var cellH = ih / Math.max(1, rows);

    var max = 0;
    for (var r = 0; r < rows; r++) {
      for (var c = 0; c < cols; c++) {
        var v = Number((matrix[r] || [])[c] || 0);
        if (v > max) {
          max = v;
        }
      }
    }

    for (var rr = 0; rr < rows; rr++) {
      for (var cc = 0; cc < cols; cc++) {
        var val = Number((matrix[rr] || [])[cc] || 0);
        var x = pad.left + (cc * cellW);
        var y = pad.top + (rr * cellH);

        ctx.fillStyle = heatColor(val, max);
        ctx.fillRect(x + 1, y + 1, cellW - 2, cellH - 2);
      }
    }

    ctx.fillStyle = '#475569';
    ctx.font = '11px Segoe UI';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    yLabels.forEach(function (label, i) {
      var y = pad.top + (i * cellH) + (cellH / 2);
      ctx.fillText(label, pad.left - 8, y);
    });

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    xLabels.forEach(function (label, i) {
      if (xLabels.length > 12 && i % 2 !== 0) {
        return;
      }
      var x = pad.left + (i * cellW) + (cellW / 2);
      ctx.fillText(label, x, H - pad.bottom + 6);
    });

    ctx.strokeStyle = 'rgba(148,163,184,.5)';
    ctx.strokeRect(pad.left, pad.top, iw, ih);

    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#64748b';
    ctx.fillText('Low', pad.left, pad.top - 8);
    ctx.textAlign = 'right';
    ctx.fillText('High', W - pad.right, pad.top - 8);
  }

  function renderCharts(payload) {
    destroyCharts();
    heatmaps = [];

    var charts = Array.isArray(payload.charts) ? payload.charts : [];
    if (!charts.length) {
      return;
    }

    charts.forEach(function (chart, index) {
      var canvas = document.getElementById('adb-chart-' + index);
      if (!canvas) {
        return;
      }

      if (chart.type === 'heatmap') {
        heatmaps.push({ canvas: canvas, chart: chart });
        drawHeatmap(canvas, chart);
        return;
      }

      if (typeof window.Chart === 'undefined') {
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#9ca3af';
        ctx.font = '14px Segoe UI';
        ctx.fillText('Chart.js not available', 12, 24);
        return;
      }

      var cfg = buildChartConfig(chart);
      var inst = new window.Chart(canvas.getContext('2d'), cfg);
      chartInstances.push(inst);
    });
  }

  function downloadCsv(filename, rows) {
    var lines = rows.map(function (row) {
      return row.map(function (cell) {
        var text = String(cell == null ? '' : cell);
        if (text.indexOf('"') >= 0 || text.indexOf(',') >= 0 || text.indexOf('\n') >= 0) {
          text = '"' + text.replace(/"/g, '""') + '"';
        }
        return text;
      }).join(',');
    }).join('\n');

    var blob = new Blob([lines], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
  }

  function attachExport(payload) {
    var btn = document.getElementById('adb-export');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', function () {
      var exportRows = Array.isArray(payload.exportRows) ? payload.exportRows : [];
      var rows = [['Section', 'Label', 'Value']];

      if (exportRows.length) {
        exportRows.forEach(function (row) {
          rows.push([
            row.section || 'Data',
            row.label || '',
            row.value || '',
          ]);
        });
      } else {
        var rankings = Array.isArray(payload.rankings) ? payload.rankings : [];
        rankings.forEach(function (ranking) {
          var items = Array.isArray(ranking.items) ? ranking.items : [];
          items.forEach(function (item) {
            rows.push([
              ranking.title || 'Ranking',
              item.label || '',
              item.value || '',
            ]);
          });
        });
      }

      var chartName = String(payload.chart || 'analytics').replace(/[^a-z0-9_\-]/gi, '_');
      var date = new Date();
      var stamp = date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');

      downloadCsv('admin_' + chartName + '_' + stamp + '.csv', rows);
    });
  }

  function throttle(fn, wait) {
    var timeout = null;
    return function () {
      if (timeout) {
        return;
      }
      timeout = setTimeout(function () {
        timeout = null;
        fn();
      }, wait);
    };
  }

  onReady(function () {
    var payload = parsePayload();
    renderCharts(payload);
    attachExport(payload);

    window.addEventListener('resize', throttle(function () {
      heatmaps.forEach(function (entry) {
        drawHeatmap(entry.canvas, entry.chart);
      });
    }, 120));
  });
})();
