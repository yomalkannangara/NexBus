<?php
// Earnings view — no inline JS, modal-based Add/Edit
// Expects: $earnings (array). Optional: $buses (owner's buses for dropdown).
// Uses BASE_URL and posts to data-endpoint (default shown below).
?>
<section id="earningsPage"
         data-endpoint="<?= BASE_URL; ?>/B/earnings">

  <header class="page-header">
    <div>
      <h2 class="page-title">Earnings & Expenses</h2>
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

  <div class="card">
    <h3 class="card-title">Revenue Tracking</h3>

    <div class="table-container">
      <table class="data-table earnings-table" id="earnings-table">
        <thead>
          <tr>
            <th style="width:150px;">Date</th>
            <th>Route & Destination</th>
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
                // Normalize keys expected by JS (id, date, bus_reg_no, amount, source)
                $row = [
                  'id'          => (int)($e['id'] ?? $e['earning_id'] ?? 0),
                  'date'        => $e['date'] ?? '',
                  'bus_reg_no'  => $e['bus_reg_no'] ?? $e['bus_id'] ?? '',
                  'amount'      => (float)($e['amount'] ?? $e['total_revenue'] ?? 0),
                  'source'      => $e['source'] ?? $e['notes'] ?? '',
                  // The next two are just for display; not posted to table
                  'route'       => $e['route'] ?? '',
                  'route_number'=> $e['route_number'] ?? '',
                ];
                $dataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
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
                No earnings records found. Click “Add Income Record” to add your first entry.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Earnings Modal (hidden by default) -->
  <div id="earningModal" class="modal" hidden>
    <div class="modal__backdrop" data-close="1"></div>
    <div class="modal__dialog">
      <div class="modal__header">
        <h3 id="earningModalTitle">Add Income</h3>
        <button type="button" class="modal__close" id="btnCloseEarning" aria-label="Close">×</button>
      </div>

      <form id="earningForm" class="modal__form" autocomplete="off">
        <input type="hidden" id="f_e_id" name="earning_id" value="">

        <div class="form-grid">
          <div class="form-field">
            <label for="f_e_date">Date <span class="req">*</span></label>
            <input type="date" id="f_e_date" name="date" required>
          </div>

            <div class="form-field">
            <label for="f_e_bus">Bus Reg. No <span class="req">*</span></label>
            <select id="f_e_bus" name="bus_reg_no" required>
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


          <div class="form-field">
            <label for="f_e_amount">Amount (LKR) <span class="req">*</span></label>
            <input type="number" id="f_e_amount" name="amount" step="0.01" min="0" required>
          </div>

          <div class="form-field">
            <label for="f_e_source">Source / Note</label>
            <input type="text" id="f_e_source" name="source" maxlength="120" placeholder="Ticket sales, charter, etc.">
          </div>
        </div>

        <div class="modal__footer">
          <button type="button" class="btn-secondary" id="btnCancelEarning">Cancel</button>
          <button type="submit" class="btn-primary" id="btnSubmitEarning">Save</button>
        </div>
      </form>
    </div>
  </div>
</section>
