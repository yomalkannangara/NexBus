/**
 * reportExport.js — CSV and PDF export helpers for all depot officer reports.
 * Pure JS, no library. CSV uses Blob + URL.createObjectURL.
 * PDF uses window.print() with print-specific CSS in reports.css.
 */
(function () {
  'use strict';

  /* ── CSV helpers ─────────────────────────────────────── */
  function escCsv(val) {
    if (val === null || val === undefined) return '';
    const s = String(val);
    if (s.includes(',') || s.includes('"') || s.includes('\n')) {
      return '"' + s.replace(/"/g, '""') + '"';
    }
    return s;
  }

  function rowToCsv(row) {
    return row.map(escCsv).join(',');
  }

  /**
   * downloadCSV(rows, filename)
   * rows: array of arrays (first element is header row)
   */
  function downloadCSV(rows, filename) {
    const csv = rows.map(rowToCsv).join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 200);
  }

  /* ── Per-report CSV builders ─────────────────────────── */

  function exportAttendance(data, summary, depotName, dateFrom, dateTo) {
    const rows = [];
    // Summary row
    rows.push(['SUMMARY', '', '', '', '', '', '', '', '', '']);
    rows.push([
      'Total Staff', summary.total_staff,
      'Avg Attendance %', summary.avg_att_pct,
      'Perfect Attendance', summary.perfect_attendance_count,
      'Most Absent', (summary.most_absent?.name || '—') + ' (' + (summary.most_absent?.absent_days || 0) + ' days)',
      '', '',
    ]);
    rows.push([]);
    // Header
    rows.push(['Name','Role','Present Days','Absent Days','Leave Days','Working Days','Attendance %','Late Arrivals','Trend','Last Absent Date']);
    // Data
    (data || []).forEach(r => {
      rows.push([
        r.full_name || r.name || '',
        r.role || '',
        r.present_days || 0,
        r.absent_days  || 0,
        r.leave_days   || 0,
        r.total_days   || 0,
        r.att_pct      || 0,
        r.late_arrivals || 0,
        r.trend        || '',
        r.last_absent_date || '',
      ]);
    });
    downloadCSV(rows, `staff-attendance_${depotName}_${dateFrom}_${dateTo}.csv`);
  }

  function exportDriverPerformance(data, summary, depotName, dateFrom, dateTo) {
    const rows = [];
    rows.push(['SUMMARY','','','','','']);
    rows.push([
      'Total Drivers', summary.total_drivers,
      'Avg On-Time %', summary.avg_on_time_pct,
      'Avg Score', summary.avg_performance_score,
      'Top Performer', (summary.top_driver?.name || '—') + ' (' + (summary.top_driver?.score || 0) + ')',
    ]);
    rows.push([]);
    rows.push(['Driver','License No','Trips Assigned','Completed','Delayed','Cancelled','On-Time %','Avg Delay (min)','KM Driven','Score','Grade']);
    (data || []).forEach(r => {
      rows.push([
        r.driver_name || r.full_name || '',
        r.license_number || '',
        r.trips_assigned  || 0,
        r.completed       || 0,
        r.delayed         || 0,
        r.cancelled       || 0,
        r.on_time_pct     || 0,
        r.avg_delay_min   || 0,
        r.total_km        || 0,
        r.performance_score || 0,
        r.grade           || '',
      ]);
    });
    downloadCSV(rows, `driver-performance_${depotName}_${dateFrom}_${dateTo}.csv`);
  }

  function exportTripCompletion(byRoute, byBus, summary, depotName, dateFrom, dateTo) {
    const rows = [];
    rows.push(['SUMMARY','','','','']);
    rows.push([
      'Overall Completion Rate', (summary.overall_completion_rate || 0) + '%',
      'Total Scheduled', summary.total_scheduled,
      'Completed', summary.total_completed,
      'Cancelled', summary.total_cancelled,
    ]);
    rows.push([]);
    rows.push(['--- BY ROUTE ---']);
    rows.push(['Route No','Route Name','Scheduled','Completed','Delayed','Cancelled','Absent','Completion %','On-Time %','Most Common Issue']);
    (byRoute || []).forEach(r => {
      rows.push([
        r.route_no || '', r.route_name || '',
        r.total_scheduled || 0, r.completed || 0,
        r.delayed || 0, r.cancelled || 0, r.absent || 0,
        r.completion_rate || 0, r.on_time_rate || 0,
        r.most_frequent_issue || '',
      ]);
    });
    rows.push([]);
    rows.push(['--- BY BUS ---']);
    rows.push(['Bus No','Route','Scheduled','Completed','Cancelled','Absent','Completion %','Utilization %']);
    (byBus || []).forEach(r => {
      rows.push([
        r.bus_reg_no || r.bus_no || '', r.route_no || '',
        r.total_scheduled || 0, r.completed || 0,
        r.cancelled || 0, r.absent || 0,
        r.completion_rate || 0, r.utilization_pct || 0,
      ]);
    });
    downloadCSV(rows, `trip-completion_${depotName}_${dateFrom}_${dateTo}.csv`);
  }

  function exportDelayAnalysis(byRoute, bySlot, byReason, summary, depotName, dateFrom, dateTo) {
    const rows = [];
    rows.push(['SUMMARY','','']);
    rows.push([
      'Total Delayed', summary.total_delayed,
      'Overall Delay Rate', (summary.overall_delay_rate || 0) + '%',
      'Avg Delay (min)', summary.avg_delay_min,
    ]);
    rows.push([]);
    rows.push(['--- BY ROUTE ---']);
    rows.push(['Route No','Route Name','Total Trips','Delayed','Delay Rate %','Avg Delay','Max Delay','Peak Slot','Common Reason']);
    (byRoute || []).forEach(r => {
      rows.push([
        r.route_no || '', r.route_name || '',
        r.total_trips || 0, r.delayed_trips || 0,
        r.delay_pct || 0, r.avg_delay_min || 0, r.max_delay_min || 0,
        r.peak_delay_slot || '', r.most_common_reason || '',
      ]);
    });
    rows.push([]);
    rows.push(['--- BY TIME SLOT ---']);
    rows.push(['Slot','Label','Total Trips','Delayed','Delay Rate %','Avg Delay (min)']);
    (bySlot || []).forEach(r => {
      rows.push([r.slot || '', r.label || '', r.total_trips || 0, r.delayed_trips || 0, r.delay_rate || 0, r.avg_delay_min || 0]);
    });
    rows.push([]);
    rows.push(['--- BY REASON ---']);
    rows.push(['Reason','Count','% of Delays','Avg Delay (min)','Affected Routes']);
    (byReason || []).forEach(r => {
      rows.push([
        humanReason(r.reason), r.count || 0,
        r.percentage || 0, r.avg_delay_min || 0,
        (r.affected_routes || []).join('; '),
      ]);
    });
    downloadCSV(rows, `delay-analysis_${depotName}_${dateFrom}_${dateTo}.csv`);
  }

  function exportBusUtilization(data, summary, depotName, dateFrom, dateTo) {
    const rows = [];
    rows.push(['SUMMARY','','','','']);
    rows.push([
      'Total Buses', summary.total_buses,
      'Active Buses', summary.active_buses,
      'Avg Utilization %', summary.avg_utilization_rate,
      'Service Due Soon', summary.buses_service_due_soon,
      'Overdue', summary.buses_overdue,
    ]);
    rows.push([]);
    rows.push(['Bus No','Make','Year','Route','Driver','Scheduled','Completed','Utilization %','KM Operated','Avg Trips/Day','Next Service','Service Status']);
    (data || []).forEach(r => {
      rows.push([
        r.bus_reg_no || '', r.bus_make || '', r.year || '',
        r.route_no   || '', r.driver_name || '',
        r.total_scheduled || 0, r.completed || 0,
        r.utilization_rate || r.utilization_pct || 0,
        r.total_km || 0, r.avg_trips_per_day || 0,
        r.next_service_date || '', r.service_status || '',
      ]);
    });
    downloadCSV(rows, `bus-utilization_${depotName}_${dateFrom}_${dateTo}.csv`);
  }

  /* ── PDF / Print ─────────────────────────────────────── */
  function printReport(reportTitle, depotName, dateFrom, dateTo) {
    // Inject print header with meta
    let header = document.getElementById('rp-print-header-el');
    if (!header) {
      header = document.createElement('div');
      header.id = 'rp-print-header-el';
      header.className = 'rp-print-header';
      const reportArea = document.querySelector('.rp-page');
      if (reportArea) reportArea.prepend(header);
    }
    const now = new Date().toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    header.innerHTML = `
      <h2>${reportTitle}</h2>
      <p>Depot: <strong>${depotName}</strong> &nbsp;|&nbsp; Period: <strong>${dateFrom} – ${dateTo}</strong> &nbsp;|&nbsp; Generated: ${now}</p>
    `;
    window.print();
  }

  /* ── Helper ─────────────────────────────────────────── */
  function humanReason(reason) {
    if (!reason) return 'Unknown';
    return String(reason).replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  /* ── Public API ─────────────────────────────────────── */
  window.ReportExport = {
    exportAttendance,
    exportDriverPerformance,
    exportTripCompletion,
    exportDelayAnalysis,
    exportBusUtilization,
    printReport,
    downloadCSV,
  };
})();
