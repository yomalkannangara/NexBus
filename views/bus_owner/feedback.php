<?php
// Content-only Feedback view (owner)
?>
<section id="feedbackPage" data-endpoint="<?= BASE_URL; ?>/B/feedback">

<header class="page-header">
  <div>
    <h2 class="page-title">Passenger Feedback System</h2>
    <p class="page-subtitle">Track and manage passenger complaints and feedback</p>
  </div>
</header>

<!-- Quick Response Section -->
<div class="card">
  <h3 class="card-title">Quick Response</h3>

  <div class="quick-response-form">
    <!-- Update Status -->
    <form class="form-row js-update-status-form" data-action="update_status" method="post" novalidate>
      <div class="form-group">
        <label class="form-label">Select Feedback ID</label>
        <select class="form-select js-ref-select" name="feedback_ref" required>
          <option value="">Select Feedback ID</option>
          <?php if (!empty($feedback_refs)): ?>
            <?php foreach ($feedback_refs as $r): ?>
              <option value="<?= htmlspecialchars($r['ref_code']); ?>">
                <?= htmlspecialchars($r['ref_code']); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Change Status</label>
        <select class="form-select js-status-select" name="status" required>
          <option value="">Change Status</option>
          <option value="In Progress">In Progress</option>
          <option value="Resolved">Resolved</option>
          <option value="Closed">Closed</option>
          <option value="Open">Open</option>
        </select>
      </div>

      <div class="form-group form-group-btn">
        <button class="update-status-btn" type="submit">Update Status</button>
      </div>
    </form>

    <!-- Send Response -->
    <form class="js-send-response-form" data-action="send_response" method="post" novalidate>
      <div class="form-group">
        <label class="form-label">Enter response or notes...</label>
        <textarea class="form-textarea js-response" name="response" placeholder="Enter response or notes..." rows="5" required></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Feedback ID</label>
          <select class="form-select js-ref-select-2" name="feedback_ref" required>
            <option value="">Select Feedback ID</option>
            <?php if (!empty($feedback_refs)): ?>
              <?php foreach ($feedback_refs as $r): ?>
                <option value="<?= htmlspecialchars($r['ref_code']); ?>">
                  <?= htmlspecialchars($r['ref_code']); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group form-group-btn">
          <button class="send-response-btn" type="submit">Send Response</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Recent Feedback Table -->
<div class="card">
  <h3 class="card-title">Recent Feedback & Complaints</h3>

  <div class="table-container">
    <table class="data-table" id="feedback-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date</th>
          <th>Bus/Route</th>
          <th>Passenger</th>
          <th>Type</th>
          <th>Category</th>
          <th>Status</th>
          <th>Description</th> <!-- was Rating -->
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!empty($feedback_list)): ?>
          <?php foreach ($feedback_list as $f): ?>
            <?php
              $ref   = (string)($f['ref_code']  ?? '');
              $msg   = (string)($f['message']   ?? 'No message');   // complaint text
              $resp  = (string)($f['response']  ?? '');             // reply_text
              $type  = (string)($f['type']      ?? 'Complaint');
              $stat  = (string)($f['status']    ?? 'Open');
              $desc  = $msg; // show message in Description column
            ?>
            <tr data-ref="<?= htmlspecialchars($ref); ?>">
              <td><strong><?= htmlspecialchars($ref); ?></strong></td>
              <td><?= htmlspecialchars($f['date'] ?? ''); ?></td>
              <td><div><strong><?= htmlspecialchars($f['bus_or_route'] ?? ''); ?></strong></div></td>
              <td><?= htmlspecialchars($f['passenger'] ?? ''); ?></td>
              <td>
                <span class="type-badge js-type-badge">
                  <?= htmlspecialchars($type); ?>
                </span>
              </td>
              <td><?= htmlspecialchars($f['category'] ?? ''); ?></td>
              <td>
                <span class="status-badge js-status-badge" data-status="<?= htmlspecialchars($stat); ?>">
                  <?= htmlspecialchars($stat); ?>
                </span>
              </td>

              <!-- Description column -->
              <td>
                <div class="text-secondary" style="max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <?= htmlspecialchars($desc); ?>
                </div>
              </td>

              <td>
                <div class="action-buttons">
                  <button class="icon-btn js-view"
                          type="button"
                          title="View"
                          data-ref="<?= htmlspecialchars($ref); ?>"
                          data-message="<?= htmlspecialchars($msg); ?>"
                          data-response="<?= htmlspecialchars($resp); ?>">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M1 9s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z" stroke="currentColor" stroke-width="2"/>
                      <circle cx="9" cy="9" r="2" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:40px;color:#6B7280;">
              No feedback records found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- View dialog -->
<dialog id="feedback-dialog">
  <form method="dialog">
    <h3 style="margin:0 0 8px;">Feedback <span class="js-dialog-ref"></span></h3>
    <div class="js-dialog-block" style="margin:0 0 10px;">
      <strong>Passenger complaint</strong>
      <p class="js-dialog-msg" style="white-space:pre-wrap;margin:6px 0 0;"></p>
    </div>
    <div class="js-dialog-reply-block" style="margin:14px 0 0;">
      <strong>Owner response</strong>
      <p class="js-dialog-reply" style="white-space:pre-wrap;margin:6px 0 0;"></p>
    </div>
    <menu style="display:flex;gap:8px;justify-content:flex-end;margin:16px 0 0;">
      <button value="close">Close</button>
      <button class="js-use-id-btn" value="use">Use this ID</button>
    </menu>
  </form>
</dialog>

<!-- Tiny toast -->
<div id="toast" style="position:fixed;right:16px;bottom:16px;padding:10px 14px;border-radius:8px;background:#111827;color:#fff;display:none;"></div>

</section>

<script defer src="<?= BASE_URL; ?>/assets/js/feedback.js"></script>
