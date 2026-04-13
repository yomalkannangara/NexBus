<?php
// Earnings view — analytical dashboard
// Expects: $earnings, $buses, $kpi, $revenueTrend, $revenueByRoute, $filterRoutes
$kpi            = $kpi            ?? ['total_revenue'=>0,'total_expenses'=>0,'top_route'=>'N/A','active_buses'=>0];
$revenueTrend   = $revenueTrend   ?? ['labels'=>[],'values'=>[]];
$revenueByRoute = $revenueByRoute ?? ['labels'=>[],'values'=>[]];
$filterRoutes   = $filterRoutes   ?? [];

// Safe JSON for JS
$trendJson  = json_encode($revenueTrend,  JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$routeJson  = json_encode($revenueByRoute, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<section id="earningsPage" data-endpoint="<?= BASE_URL; ?>/earnings">

  <!-- ═══════════════════════════════════════════
       PAGE HEADER
  ═══════════════════════════════════════════ -->
  <header class="page-header">
    <div>
      <h2 class="page-title">Earnings &amp; Expenses</h2>
      <p class="page-subtitle">Track and manage bus route revenue and income reports.</p>
    </div>
    <div class="header-actions">
      <button type="button"
              class="export-report-btn-alt js-export"
              data-export-href="<?= BASE_URL; ?>/earnings/export">
        Export Report
      </button>
      <button type="button" id="btnAddEarning" class="add-income-btn">
        Add Income Record
      </button>
    </div>
  </header>

  <!-- ═══════════════════════════════════════════
       KPI CARDS
  ═══════════════════════════════════════════ -->
  <div class="enrg-kpi-grid">

    <div class="enrg-kpi-card enrg-kpi-card--revenue">
      <div class="enrg-kpi-card__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
      <div class="enrg-kpi-card__body">
        <p class="enrg-kpi-card__label">Total Revenue</p>
        <p class="enrg-kpi-card__value">LKR <?= number_format($kpi['total_revenue'], 0) ?></p>
        <p class="enrg-kpi-card__sub">All recorded income</p>
      </div>
      <div class="enrg-kpi-card__glow enrg-kpi-card__glow--green"></div>
    </div>


    <div class="enrg-kpi-card enrg-kpi-card--route">
      <div class="enrg-kpi-card__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="enrg-kpi-card__body">
        <p class="enrg-kpi-card__label">Top Performing Route</p>
        <p class="enrg-kpi-card__value"><?= htmlspecialchars($kpi['top_route']) ?></p>
        <p class="enrg-kpi-card__sub">Highest earnings route</p>
      </div>
      <div class="enrg-kpi-card__glow enrg-kpi-card__glow--gold"></div>
    </div>

    <div class="enrg-kpi-card enrg-kpi-card--buses">
      <div class="enrg-kpi-card__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      </div>
      <div class="enrg-kpi-card__body">
        <p class="enrg-kpi-card__label">Active Buses</p>
        <p class="enrg-kpi-card__value"><?= (int)$kpi['active_buses'] ?></p>
        <p class="enrg-kpi-card__sub">Currently operational</p>
      </div>
      <div class="enrg-kpi-card__glow enrg-kpi-card__glow--maroon"></div>
    </div>

  </div><!-- /.enrg-kpi-grid -->

  <!-- ═══════════════════════════════════════════
       CHARTS ROW
  ═══════════════════════════════════════════ -->
  <div class="enrg-charts-row">

    <!-- Line chart — Revenue Trend (2/3 width) -->
    <div class="card enrg-chart-card enrg-chart-card--wide">
      <div class="enrg-chart-card__header">
        <h3 class="card-title" style="margin:0;">Revenue Trend</h3>
        <span class="enrg-chart-badge">Last 7 Entries</span>
      </div>
      <div class="enrg-chart-card__body">
        <canvas id="revenueTrendChart" height="90"></canvas>
      </div>
    </div>

    <!-- Doughnut chart — Income by Route (1/3 width) -->
    <div class="card enrg-chart-card enrg-chart-card--narrow">
      <div class="enrg-chart-card__header">
        <h3 class="card-title" style="margin:0;">Income by Route</h3>
        <span class="enrg-chart-badge">All Time</span>
      </div>
      <div class="enrg-chart-card__body enrg-chart-card__body--donut">
        <canvas id="routeDonutChart"></canvas>
      </div>
    </div>

  </div><!-- /.enrg-charts-row -->

  <!-- ═══════════════════════════════════════════
       FILTER BAR
  ═══════════════════════════════════════════ -->
  <div class="enrg-filter-bar">

    <div class="enrg-filter-group">
      <label class="enrg-filter-label" for="flt-date-from">From</label>
      <input type="date" id="flt-date-from" class="enrg-filter-input" title="Start date">
    </div>

    <div class="enrg-filter-group">
      <label class="enrg-filter-label" for="flt-date-to">To</label>
      <input type="date" id="flt-date-to" class="enrg-filter-input" title="End date">
    </div>

    <div class="enrg-filter-group">
      <label class="enrg-filter-label" for="flt-route">Route</label>
      <select id="flt-route" class="enrg-filter-input">
        <option value="">All Routes</option>
        <?php foreach ($filterRoutes as $rno): ?>
          <option value="<?= htmlspecialchars($rno) ?>">Route <?= htmlspecialchars($rno) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="enrg-filter-group">
      <label class="enrg-filter-label" for="flt-bus">Bus Reg. No</label>
      <select id="flt-bus" class="enrg-filter-input">
        <option value="">All Buses</option>
        <?php if (!empty($buses)): ?>
          <?php foreach ($buses as $b): ?>
            <?php $reg = is_array($b) ? ($b['reg_no'] ?? '') : (string)$b; ?>
            <?php if ($reg !== ''): ?>
              <option value="<?= htmlspecialchars($reg) ?>"><?= htmlspecialchars($reg) ?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="enrg-filter-group enrg-filter-group--search">
      <label class="enrg-filter-label" for="flt-search">Search</label>
      <input type="text" id="flt-search" class="enrg-filter-input" placeholder="Keyword…">
    </div>

    <button type="button" id="btnClearFilters" class="enrg-filter-clear">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Clear
    </button>

  </div><!-- /.enrg-filter-bar -->

  <!-- ═══════════════════════════════════════════
       DATA TABLE
  ═══════════════════════════════════════════ -->
  <div class="card">
    <div class="enrg-table-header">
      <h3 class="card-title" style="margin:0;">Revenue Tracking</h3>
      <span class="enrg-table-count" id="tableRowCount"></span>
    </div>

    <div class="table-container">
      <table class="data-table earnings-table" id="earnings-table">
        <thead>
          <tr>
            <th style="width:150px;">Date</th>
            <th>Route &amp; Destination</th>
            <th>Bus Reg. No</th>
            <th>Total Revenue</th>
            <th>Source</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($earnings)): ?>
            <?php foreach ($earnings as $e): ?>
              <?php
                $row = [
                  'id'          => (int)($e['id'] ?? $e['earning_id'] ?? 0),
                  'date'        => $e['date'] ?? '',
                  'bus_reg_no'  => $e['bus_reg_no'] ?? $e['bus_id'] ?? '',
                  'amount'      => (float)($e['amount'] ?? $e['total_revenue'] ?? 0),
                  'source'      => $e['source'] ?? $e['notes'] ?? '',
                  'route'       => $e['route'] ?? $e['route_name'] ?? '',
                  'route_number'=> $e['route_number'] ?? '',
                ];
                $dataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
              ?>
              <tr data-date="<?= htmlspecialchars($row['date']) ?>"
                  data-bus="<?= htmlspecialchars($row['bus_reg_no']) ?>"
                  data-route="<?= htmlspecialchars($row['route_number']) ?>">
                <td>
                  <div class="date-cell">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <rect x="2" y="3" width="12" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/>
                      <path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <?= htmlspecialchars($row['date']) ?>
                  </div>
                </td>

                <td>
                  <div class="route-cell">
                    <span class="badge badge-yellow"><?= htmlspecialchars($row['route_number']) ?></span>
                    <span><?= htmlspecialchars($row['route']) ?></span>
                  </div>
                </td>

                <td><strong><?= htmlspecialchars($row['bus_reg_no']) ?></strong></td>

                <td>
                  <strong class="revenue-amount">LKR <?= number_format($row['amount']); ?></strong>
                </td>

                <td><?= htmlspecialchars($row['source']) ?></td>

                <td>
                  <div class="action-buttons">
                    <button type="button"
                            class="icon-btn icon-btn-edit js-earning-edit"
                            title="Edit"
                            data-earning="<?= $dataJson ?>">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                        <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <button type="button"
                            class="icon-btn icon-btn-delete js-earning-del"
                            title="Delete"
                            data-earning-id="<?= (int)$row['id'] ?>">
                      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                        <path d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:#6B7280;">
                No earnings records found. Click "Add Income Record" to add your first entry.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div><!-- /.table-container -->

    <!-- Pagination -->
    <div class="pagination-container" id="enrg-pagination-container">
      <div class="pagination-controls">
        <button class="pagination-btn" id="enrg-prev-page" disabled>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Previous
        </button>
        <div class="pagination-pages" id="enrg-pagination-pages"></div>
        <button class="pagination-btn" id="enrg-next-page" disabled>
          Next
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </div>

  </div><!-- /.card -->

  <!-- ═══════════════════════════════════════════
       ADD / EDIT MODAL (unchanged)
  ═══════════════════════════════════════════ -->
  <div id="earningModal" class="enrg-modal" hidden>
    <div class="enrg-modal__backdrop"></div>
    <div class="enrg-modal__panel">
      <div class="enrg-modal__header">
        <div>
          <h2 class="enrg-modal__title" id="earningModalTitle">Add Income Record</h2>
          <p class="enrg-modal__subtitle">Enter details below</p>
        </div>
        <button type="button" class="enrg-modal__close" id="btnCloseEarning" aria-label="Close">&times;</button>
      </div>

      <form id="earningForm" autocomplete="off">
        <input type="hidden" id="f_e_id" name="earning_id" value="">

        <div class="enrg-modal__grid">
          <div class="enrg-modal__field">
            <label class="enrg-modal__label" for="f_e_date">Date <span style="color:#DC2626;">*</span></label>
            <input type="date" id="f_e_date" name="date" class="enrg-modal__input" required>
          </div>
          <div class="enrg-modal__field">
            <label class="enrg-modal__label" for="f_e_bus">Bus Reg. No <span style="color:#DC2626;">*</span></label>
            <select id="f_e_bus" name="bus_reg_no" class="enrg-modal__input" required>
              <option value="">-- Select Bus --</option>
              <?php if (!empty($buses) && is_array($buses)): ?>
              <?php foreach ($buses as $b): ?>
                <?php $reg = is_array($b) ? ($b['reg_no'] ?? '') : (string)$b; ?>
                <?php if ($reg !== ''): ?>
                <option value="<?= htmlspecialchars($reg) ?>"><?= htmlspecialchars($reg) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php else: ?>
              <option value="" disabled>(No buses found for your account)</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="enrg-modal__field">
            <label class="enrg-modal__label" for="f_e_amount">Amount (LKR) <span style="color:#DC2626;">*</span></label>
            <input type="number" id="f_e_amount" name="amount" step="0.01" min="0" class="enrg-modal__input" required>
          </div>
          <div class="enrg-modal__field">
            <label class="enrg-modal__label" for="f_e_source">Source / Note</label>
            <input type="text" id="f_e_source" name="source" maxlength="120" placeholder="Ticket sales, charter, etc." class="enrg-modal__input">
          </div>
        </div>

        <div class="enrg-modal__footer">
          <button type="button" class="enrg-modal__btn enrg-modal__btn--cancel" id="btnCancelEarning">Cancel</button>
          <button type="submit" class="enrg-modal__btn enrg-modal__btn--submit" id="btnSubmitEarning">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast -->
  <div id="toastNotification" class="toast-notification">
    <div class="toast-icon"></div>
    <div class="toast-message"></div>
    <button class="toast-close">&times;</button>
  </div>

  <!-- Delete Confirm Modal -->
  <div id="deleteConfirmModal" class="modal" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" style="max-width: 400px; padding: 0;">
      <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
        <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
          <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Delete Record
        </h3>
        <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
      </div>
      <div class="modal__form" style="padding-top: 10px;">
        <p style="color: #4B5563; font-size: 15px; margin: 0;">Are you sure you want to delete this earning record? This action cannot be undone.</p>
      </div>
      <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
        <button type="button" class="btn-secondary" id="btnCancelDelete" style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
        <button type="button" class="btn-primary" id="btnConfirmDelete" style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
      </div>
    </div>
  </div>

</section><!-- /#earningsPage -->

<!-- ═══════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════ -->
<style>
/* ── KPI Grid ────────────────────────────────────────────── */
.enrg-kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-bottom: 24px;
}
@media (max-width: 860px)  { .enrg-kpi-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 600px)  { .enrg-kpi-grid { grid-template-columns: 1fr; } }

.enrg-kpi-card {
  background: #fff;
  border-radius: 16px;
  padding: 22px 20px 18px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
  border: 1px solid #F5F0D8;
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.enrg-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.11); }

.enrg-kpi-card__icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.enrg-kpi-card--revenue .enrg-kpi-card__icon { background: #D1FAE5; color: #065F46; }
.enrg-kpi-card--expense .enrg-kpi-card__icon  { background: #FEE2E2; color: #991B1B; }
.enrg-kpi-card--route   .enrg-kpi-card__icon  { background: #FEF3C7; color: #92400E; }
.enrg-kpi-card--buses   .enrg-kpi-card__icon  { background: #F5D6E0; color: #7F0032; }

.enrg-kpi-card__body { flex: 1; min-width: 0; }
.enrg-kpi-card__label {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 1px; color: #9CA3AF; margin: 0 0 6px;
}
.enrg-kpi-card__value {
  font-size: 22px; font-weight: 800; color: #111827;
  margin: 0 0 4px; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.enrg-kpi-card__sub { font-size: 12px; color: #9CA3AF; margin: 0; }

/* Glow blobs */
.enrg-kpi-card__glow {
  position: absolute; bottom: -20px; right: -20px;
  width: 80px; height: 80px; border-radius: 50%; opacity: .12;
  pointer-events: none;
}
.enrg-kpi-card__glow--green  { background: #10B981; }
.enrg-kpi-card__glow--red    { background: #EF4444; }
.enrg-kpi-card__glow--gold   { background: #F59E0B; }
.enrg-kpi-card__glow--maroon { background: #7F0032; }

/* ── Charts Row ──────────────────────────────────────────── */
.enrg-charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 18px;
  margin-bottom: 24px;
}
@media (max-width: 860px) { .enrg-charts-row { grid-template-columns: 1fr; } }

.enrg-chart-card { display: flex; flex-direction: column; gap: 0; }
.enrg-chart-card__header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.enrg-chart-badge {
  font-size: 11px; font-weight: 600; color: #92400E;
  background: #FEF3C7; border: 1px solid #FDE68A;
  border-radius: 20px; padding: 3px 10px; white-space: nowrap;
}
.enrg-chart-card__body { flex: 1; min-height: 0; }
.enrg-chart-card__body--donut {
  display: flex; align-items: center; justify-content: center;
  max-height: 220px;
}
.enrg-chart-card--narrow .enrg-chart-card__body canvas {
  max-height: 220px !important;
}

/* ── Filter Bar ──────────────────────────────────────────── */
.enrg-filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
  background: #fff;
  border: 1px solid #F5F0D8;
  border-radius: 14px;
  padding: 16px 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.enrg-filter-group { display: flex; flex-direction: column; gap: 5px; }
.enrg-filter-group--search { flex: 1; min-width: 160px; }
.enrg-filter-label {
  font-size: 11px; font-weight: 700; color: #6B7280;
  text-transform: uppercase; letter-spacing: .6px;
}
.enrg-filter-input {
  padding: 9px 12px; border: 1.5px solid #E5E7EB; border-radius: 8px;
  font-size: 13px; color: #111827; font-family: inherit;
  background: #FAFAFA; transition: border-color .15s;
  min-width: 130px;
}
.enrg-filter-input:focus { outline: none; border-color: #7F0032; background: #fff; }
.enrg-filter-clear {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 14px; border-radius: 8px;
  background: #F3F4F6; border: 1.5px solid #E5E7EB;
  font-size: 13px; font-weight: 600; color: #6B7280;
  cursor: pointer; transition: all .15s; white-space: nowrap; align-self: flex-end;
}
.enrg-filter-clear:hover { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

/* ── Table header with count ─────────────────────────────── */
.enrg-table-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px;
}
.enrg-table-count {
  font-size: 12px; color: #9CA3AF; font-weight: 600;
  background: #F3F4F6; border-radius: 20px; padding: 3px 10px;
}

/* ── Re-use existing modal / toast styles ────────────────── */
.enrg-modal[hidden]            { display: none; }
.enrg-modal                    { position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; }
.enrg-modal__backdrop          { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
.enrg-modal__panel             { position: relative; width: min(560px, 95vw); background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.18); overflow: hidden; }
.enrg-modal__header            { display: flex; align-items: flex-start; justify-content: space-between; padding: 24px 24px 0; }
.enrg-modal__title             { font-size: 20px; font-weight: 700; color: var(--maroon); margin: 0 0 4px; }
.enrg-modal__subtitle          { font-size: 13px; color: #6B7280; margin: 0; }
.enrg-modal__close             { background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF; line-height: 1; padding: 0; margin-left: 12px; }
.enrg-modal__close:hover       { color: #374151; }
.enrg-modal__grid              { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 20px 24px; }
.enrg-modal__field             { display: flex; flex-direction: column; gap: 6px; }
.enrg-modal__label             { font-size: 13px; font-weight: 600; color: #374151; }
.enrg-modal__input             { width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; color: #111827; box-sizing: border-box; transition: border-color .15s; font-family: inherit; background: #fff; }
.enrg-modal__input:focus       { outline: none; border-color: var(--maroon); box-shadow: 0 0 0 3px rgba(127,0,50,.08); }
.enrg-modal__footer            { display: flex; justify-content: flex-end; gap: 10px; padding: 0 24px 24px; }
.enrg-modal__btn               { padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: background .18s; display: inline-block; }
.enrg-modal__btn--cancel       { background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB; }
.enrg-modal__btn--cancel:hover { background: #E5E7EB; }
.enrg-modal__btn--submit       { background: var(--gold); color: var(--maroon); }
.enrg-modal__btn--submit:hover { background: #F59E0B; }

/* Toast */
.toast-notification { position:fixed; top:20px; right:20px; min-width:300px; max-width:500px; background:#fff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,.15); padding:16px 20px; display:none; align-items:center; gap:12px; z-index:999999; animation:slideInRight .3s ease-out; border-left:4px solid #10B981; }
.toast-notification.success { border-left-color:#10B981; }
.toast-notification.error   { border-left-color:#EF4444; }
.toast-notification.show    { display:flex; }
.toast-icon { width:24px;height:24px;flex-shrink:0;border-radius:50%;display:flex;align-items:center;justify-content:center; }
.toast-notification.success .toast-icon { background:#10B981; }
.toast-notification.error   .toast-icon { background:#EF4444; }
.toast-notification.success .toast-icon::before { content:'✓';color:#fff;font-weight:bold;font-size:16px; }
.toast-notification.error   .toast-icon::before { content:'✕';color:#fff;font-weight:bold;font-size:16px; }
.toast-message { flex:1;color:#1F2937;font-size:14px;line-height:1.5; }
.toast-close { background:none;border:none;color:#9CA3AF;font-size:24px;line-height:1;padding:0;width:24px;height:24px;cursor:pointer;flex-shrink:0;transition:color .2s; }
.toast-close:hover { color:#4B5563; }
@keyframes slideInRight  { from { transform:translateX(100%);opacity:0; } to { transform:translateX(0);opacity:1; } }
@keyframes slideOutRight { from { transform:translateX(0);opacity:1; } to { transform:translateX(100%);opacity:0; } }
</style>

<!-- ═══════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── Chart.js colours ────────────────────────────────── */
  const MAROON = '#7F0032';
  const GOLD   = '#F5A623';
  const DONUT_PALETTE = [
    '#7F0032','#F5A623','#10B981','#3B82F6','#8B5CF6',
    '#F97316','#EC4899','#14B8A6'
  ];

  /* ── Line Chart: Revenue Trend ───────────────────────── */
  const trendData   = <?= $trendJson ?>;
  const trendCtx    = document.getElementById('revenueTrendChart');
  if (trendCtx && window.Chart) {
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: trendData.labels,
        datasets: [{
          label: 'Revenue (LKR)',
          data: trendData.values,
          borderColor: MAROON,
          backgroundColor: 'rgba(127,0,50,.08)',
          borderWidth: 2.5,
          pointBackgroundColor: MAROON,
          pointRadius: 5,
          pointHoverRadius: 7,
          fill: true,
          tension: 0.38,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ' LKR ' + ctx.parsed.y.toLocaleString()
            }
          }
        },
        scales: {
          x: { grid: { color: '#F5F0D8' }, ticks: { color: '#6B7280', font: { size: 12 } } },
          y: {
            grid: { color: '#F5F0D8' },
            ticks: {
              color: '#6B7280', font: { size: 12 },
              callback: v => 'LKR ' + v.toLocaleString()
            },
            beginAtZero: true
          }
        }
      }
    });
  }

  /* ── Doughnut Chart: Income by Route ─────────────────── */
  const routeData   = <?= $routeJson ?>;
  const donutCtx    = document.getElementById('routeDonutChart');
  if (donutCtx && window.Chart) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: routeData.labels.length ? routeData.labels : ['No data yet'],
        datasets: [{
          data:            routeData.values.length ? routeData.values : [1],
          backgroundColor: routeData.labels.length ? DONUT_PALETTE : ['#E5E7EB'],
          borderWidth: 3,
          borderColor: '#fff',
          hoverOffset: 8,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '64%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { size: 11 }, color: '#374151', padding: 12, boxWidth: 12, boxHeight: 12 }
          },
          tooltip: {
            callbacks: {
              label: ctx => ' LKR ' + ctx.parsed.toLocaleString()
            }
          }
        }
      }
    });
  }

  /* ── Table filter + pagination ───────────────────────── */
  const tbody    = document.querySelector('#earnings-table tbody');
  const allRows  = tbody ? Array.from(tbody.querySelectorAll('tr[data-date]')) : [];
  let filtered   = allRows.slice();
  const ROWS_PER = 8;
  let currentPage = 1;

  const fltFrom   = document.getElementById('flt-date-from');
  const fltTo     = document.getElementById('flt-date-to');
  const fltRoute  = document.getElementById('flt-route');
  const fltBus    = document.getElementById('flt-bus');
  const fltSearch = document.getElementById('flt-search');
  const btnClear  = document.getElementById('btnClearFilters');
  const countEl   = document.getElementById('tableRowCount');
  const prevBtn   = document.getElementById('enrg-prev-page');
  const nextBtn   = document.getElementById('enrg-next-page');
  const pagesEl   = document.getElementById('enrg-pagination-pages');

  function applyFilter() {
    const from   = fltFrom?.value  || '';
    const to     = fltTo?.value    || '';
    const route  = fltRoute?.value || '';
    const bus    = fltBus?.value   || '';
    const search = (fltSearch?.value || '').toLowerCase().trim();

    filtered = allRows.filter(row => {
      const date = row.dataset.date || '';
      const r    = row.dataset.route || '';
      const b    = (row.dataset.bus || '').toLowerCase();
      const txt  = row.textContent.toLowerCase();
      if (from && date < from) return false;
      if (to   && date > to)   return false;
      if (route && r !== route) return false;
      if (bus  && !b.includes(bus.toLowerCase())) return false;
      if (search && !txt.includes(search)) return false;
      return true;
    });
    currentPage = 1;
    renderPage();
  }

  function totalPages() { return Math.max(1, Math.ceil(filtered.length / ROWS_PER)); }

  function getVisiblePages(current, total) {
    var compactCount = window.innerWidth < 992 ? 5 : 7;
    if (total <= compactCount) {
      return Array.from({ length: total }, function (_, i) { return i + 1; });
    }

    var pages = [1];
    var innerSlots = compactCount - 2;
    var half = Math.floor(innerSlots / 2);
    var start = Math.max(2, current - half);
    var end = Math.min(total - 1, start + innerSlots - 1);

    start = Math.max(2, end - innerSlots + 1);

    if (start > 2) pages.push('...');
    for (var p = start; p <= end; p++) pages.push(p);
    if (end < total - 1) pages.push('...');
    pages.push(total);
    return pages;
  }

  function renderPageNumbers() {
    if (!pagesEl) return;
    pagesEl.innerHTML = '';
    getVisiblePages(currentPage, totalPages()).forEach(function (item) {
      var b = document.createElement('button');
      if (item === '...') {
        b.className = 'page-number ellipsis';
        b.textContent = '...';
        b.type = 'button';
        b.disabled = true;
      } else {
        b.className = 'page-number' + (item === currentPage ? ' active' : '');
        b.textContent = item;
        b.type = 'button';
        b.addEventListener('click', function () {
          currentPage = item;
          renderPage();
        });
      }
      pagesEl.appendChild(b);
    });
  }

  function renderPage() {
    // hide all
    allRows.forEach(r => r.style.display = 'none');
    // remove stale no-results
    tbody.querySelectorAll('.no-flt-row').forEach(r => r.remove());

    if (!filtered.length) {
      const tr = document.createElement('tr');
      tr.className = 'no-flt-row';
      tr.innerHTML = '<td colspan="6" style="text-align:center;padding:30px;color:#9CA3AF;">No records match your filters.</td>';
      tbody.appendChild(tr);
      if (countEl) countEl.textContent = '0 records';
      document.getElementById('enrg-pagination-container').style.display = 'none';
      return;
    }

    const tp = totalPages();
    if (currentPage > tp) currentPage = tp;
    const start = (currentPage - 1) * ROWS_PER;
    filtered.forEach((r, idx) => {
      r.style.display = (idx >= start && idx < start + ROWS_PER) ? '' : 'none';
    });

    if (countEl) countEl.textContent = filtered.length + ' record' + (filtered.length === 1 ? '' : 's');
    document.getElementById('enrg-pagination-container').style.display = '';
    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage >= tp;
    renderPageNumbers();
  }

  [fltFrom, fltTo, fltRoute, fltBus, fltSearch].forEach(el => {
    if (el) el.addEventListener('input', applyFilter);
    if (el && el.tagName === 'SELECT') el.addEventListener('change', applyFilter);
  });
  if (btnClear) btnClear.addEventListener('click', () => {
    [fltFrom, fltTo, fltSearch].forEach(el => { if (el) el.value = ''; });
    [fltRoute, fltBus].forEach(el => { if (el) el.selectedIndex = 0; });
    applyFilter();
  });
  if (prevBtn) prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
  if (nextBtn) nextBtn.addEventListener('click', () => { if (currentPage < totalPages()) { currentPage++; renderPage(); } });

  window.addEventListener('resize', renderPageNumbers);

  renderPage(); // initial

  /* ── Toast ───────────────────────────────────────────── */
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toastNotification');
    if (!toast) return;
    if (toast.parentElement !== document.body) document.body.appendChild(toast);
    toast.querySelector('.toast-message').textContent = message;
    toast.className = 'toast-notification show ' + type;
    setTimeout(() => {
      toast.style.animation = 'slideOutRight .3s ease-out';
      setTimeout(() => { toast.classList.remove('show'); toast.style.animation = ''; }, 300);
    }, 4000);
  }
  document.querySelector('.toast-close')?.addEventListener('click', () => {
    const t = document.getElementById('toastNotification');
    t.style.animation = 'slideOutRight .3s ease-out';
    setTimeout(() => { t.classList.remove('show'); t.style.animation = ''; }, 300);
  });

  /* ── Add / Edit Modal ────────────────────────────────── */
  const endpoint   = document.getElementById('earningsPage')?.dataset?.endpoint || '<?= BASE_URL ?>/earnings';
  const modal      = document.getElementById('earningModal');
  const form       = document.getElementById('earningForm');
  const btnAdd     = document.getElementById('btnAddEarning');
  const btnClose   = document.getElementById('btnCloseEarning');
  const btnCancel  = document.getElementById('btnCancelEarning');
  const modalTitle = document.getElementById('earningModalTitle');

  function closeModal() { modal.setAttribute('hidden',''); form.reset(); }

  if (btnAdd) btnAdd.addEventListener('click', () => {
    modalTitle.textContent = 'Add Income Record';
    form.reset();
    document.getElementById('f_e_id').value = '';
    modal.removeAttribute('hidden');
  });
  if (btnClose)  btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);
  modal?.querySelector('.enrg-modal__backdrop')?.addEventListener('click', closeModal);

  document.querySelectorAll('.js-earning-edit').forEach(btn => {
    btn.addEventListener('click', function () {
      const data = JSON.parse(this.dataset.earning || '{}');
      document.getElementById('f_e_id').value     = data.id      || '';
      document.getElementById('f_e_date').value   = data.date    || '';
      document.getElementById('f_e_bus').value     = data.bus_reg_no || '';
      document.getElementById('f_e_amount').value = data.amount  || '';
      document.getElementById('f_e_source').value = data.source  || '';
      modalTitle.textContent = 'Edit Income Record';
      modal.removeAttribute('hidden');
    });
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      const earningId = document.getElementById('f_e_id').value;
      fd.append('action', earningId ? 'update' : 'create');
      fetch(endpoint, { method:'POST', body:fd })
        .then(async r => {
          const ct = r.headers.get('content-type');
          let result;
          try { result = ct && ct.includes('application/json') ? await r.json() : { success:false, message:'Server error.' }; }
          catch(e) { result = { success:false, message:'Invalid response.' }; }
          if (r.ok && result.success !== false) {
            showToast(result.message || 'Record saved!', 'success');
            setTimeout(() => location.reload(), 1500);
          } else { showToast(result.message || 'Error saving record.', 'error'); }
        })
        .catch(err => showToast('Network error: ' + err.message, 'error'));
    });
  }

  /* ── Delete Modal ────────────────────────────────────── */
  let deleteId = null;
  const deleteModal     = document.getElementById('deleteConfirmModal');
  const btnConfirmDel   = document.getElementById('btnConfirmDelete');
  const btnCancelDel    = document.getElementById('btnCancelDelete');
  const btnCloseDel     = document.getElementById('btnCloseDelete');

  function closeDeleteModal() { deleteModal.setAttribute('hidden',''); deleteId = null; }
  if (btnCancelDel) btnCancelDel.addEventListener('click', closeDeleteModal);
  if (btnCloseDel)  btnCloseDel.addEventListener('click',  closeDeleteModal);
  deleteModal?.querySelector('.modal__backdrop')?.addEventListener('click', closeDeleteModal);

  document.querySelectorAll('.js-earning-del').forEach(btn => {
    btn.addEventListener('click', function () {
      deleteId = this.dataset.earningId;
      if (!deleteId) return;
      if (deleteModal.parentElement !== document.body) document.body.appendChild(deleteModal);
      deleteModal.removeAttribute('hidden');
    });
  });

  if (btnConfirmDel) {
    btnConfirmDel.addEventListener('click', function () {
      if (!deleteId) return;
      const orig = this.textContent;
      this.textContent = 'Deleting…'; this.disabled = true;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('earning_id', deleteId);
      fetch(endpoint, { method:'POST', body:fd })
        .then(async r => {
          if (r.ok) {
            const res = await r.json();
            closeDeleteModal();
            showToast(res.message || 'Deleted!', 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            const err = await r.json();
            showToast(err.message || 'Error deleting record.', 'error');
            this.textContent = orig; this.disabled = false;
          }
        })
        .catch(err => { showToast('Network error: ' + err.message, 'error'); this.textContent = orig; this.disabled = false; });
    });
  }

  /* ── Export ──────────────────────────────────────────── */
  document.querySelector('.js-export')?.addEventListener('click', function () {
    window.location.href = this.dataset.exportHref || '<?= BASE_URL ?>/earnings/export';
  });

}); // DOMContentLoaded
</script>
