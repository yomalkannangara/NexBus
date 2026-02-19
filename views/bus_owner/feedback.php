<?php
// Bus Owner Feedback (Private only)
// Vars from controller:
//   $feedback_refs (id + ref_code)
//   $feedback_list

$feedback_refs = is_array($feedback_refs ?? null) ? $feedback_refs : [];
$feedback_list = is_array($feedback_list ?? null) ? $feedback_list : [];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<section id="feedbackPage">

  <header class="page-header">
    <div>
      <h2 class="page-title">Passenger Feedback & Complaints</h2>
      <p class="page-subtitle">Private buses only (your operator)</p>
    </div>
  </header>

  <!-- Quick Response (single selector, like depot manager) -->
  <div class="card">
    <h3 class="card-title">Quick Response</h3>

    <form method="post" action="/B/feedback" class="filter-grid" style="grid-template-columns: 1.2fr 1.8fr 1fr; align-items:start; gap:14px;">
      <div class="filter-group">
        <label class="filter-label">Select Feedback ID *</label>
        <select name="complaint_id" id="q_complaint_id" class="search-input" required>
          <option value="">Choose an ID...</option>
          <?php foreach ($feedback_refs as $r): ?>
            <option value="<?= (int)($r['id'] ?? 0) ?>"><?= h($r['ref_code'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
        <div class="muted" style="margin-top:6px">Tip: Reply sets status to In Progress automatically.</div>
      </div>

      <div class="filter-group">
        <label class="filter-label">Reply / Note</label>
        <textarea name="message" id="q_message" class="search-input" rows="4" placeholder="Type reply or note (saved to complaints.reply_text)..."></textarea>
        <div class="muted" style="margin-top:6px" id="q_preview">Select an item to preview details.</div>
      </div>

      <div class="filter-actions" style="align-self:end; display:flex; flex-direction:column; gap:10px;">
        <button class="export-btn" type="submit" name="action" value="reply">Send Reply</button>
        <button class="export-btn" type="submit" name="action" value="resolve">Resolve</button>
        <button class="export-btn" type="submit" name="action" value="close">Close</button>
      </div>
    </form>
  </div>

  <!-- Manage panel (opens from table Manage button) -->
  <div class="card" id="manageCard" style="margin-top:16px; display:none;">
    <h3 class="card-title">Manage Selected Item</h3>
    <div class="filter-grid" style="grid-template-columns:1fr 1fr; gap:14px;">
      <div>
        <div class="muted">Details</div>
        <div class="card" style="margin-top:10px; padding:12px;">
          <div><strong id="m_ref">—</strong> <span class="muted" id="m_status">—</span></div>
          <div class="muted" style="margin-top:6px" id="m_meta">—</div>
          <div style="margin-top:10px" id="m_desc">—</div>
        </div>
      </div>
      <div>
        <div class="muted">Reply</div>
        <div class="card" style="margin-top:10px; padding:12px;">
          <div class="muted" id="m_current_reply">Reply: —</div>
          <form method="post" action="/B/feedback" style="margin-top:10px;">
            <input type="hidden" name="complaint_id" id="m_id" value="">
            <textarea class="search-input" name="message" id="m_message" rows="4" placeholder="Type reply..." required></textarea>
            <div class="filter-actions" style="margin-top:10px; display:flex; gap:10px;">
              <button class="export-btn" type="submit" name="action" value="reply">Save Reply</button>
              <button class="export-btn" type="submit" name="action" value="resolve">Resolve</button>
              <button class="export-btn" type="submit" name="action" value="close">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" style="margin-top:16px;">
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
            <th>Rating</th>
            <th>Reply</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($feedback_list)): ?>
          <?php foreach ($feedback_list as $f): ?>
            <?php
              $id    = (int)($f['id'] ?? 0);
              $ref   = (string)($f['ref_code'] ?? ('C'.str_pad((string)$id, 6, '0', STR_PAD_LEFT)));
              $stat  = (string)($f['status'] ?? 'Open');
              $rate  = (int)($f['rating'] ?? 0);
              $reply = trim((string)($f['response'] ?? ''));
              $replyShort = $reply !== '' ? (strlen($reply) > 60 ? substr($reply, 0, 60) . '…' : $reply) : '—';
            ?>
            <tr>
              <td><strong><?= h($ref) ?></strong></td>
              <td><?= h((string)($f['date'] ?? '')) ?></td>
              <td><strong><?= h((string)($f['bus_or_route'] ?? '')) ?></strong></td>
              <td><?= h((string)($f['passenger'] ?? '')) ?></td>
              <td><?= h((string)($f['type'] ?? '')) ?></td>
              <td><?= h((string)($f['category'] ?? '')) ?></td>
              <td><?= h($stat) ?></td>
              <td><?= $rate > 0 ? h((string)$rate) . '/5' : '—' ?></td>
              <td><?= h($replyShort) ?></td>
              <td>
                <button
                  type="button"
                  class="export-btn js-manage"
                  data-id="<?= $id ?>"
                >Manage</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="10" style="text-align:center;padding:24px;color:#6B7280;">No feedback records found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const ROWS = <?= json_encode($feedback_list, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const refs = <?= json_encode($feedback_refs, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

    function esc(s) {
      return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function findRowById(id) {
      return ROWS.find(r => String(r.id) === String(id));
    }
    function refForId(id) {
      const hit = refs.find(r => String(r.id) === String(id));
      return hit?.ref_code || ('C' + String(id).padStart(6, '0'));
    }

    // Quick preview
    const qSel = document.getElementById('q_complaint_id');
    const qPrev = document.getElementById('q_preview');
    if (qSel && qPrev) {
      qSel.addEventListener('change', () => {
        const row = findRowById(qSel.value);
        if (!row) {
          qPrev.textContent = 'Select an item to preview details.';
          return;
        }
        const parts = [];
        parts.push(`<div><strong>${esc(refForId(row.id))}</strong> — ${esc(row.status)}</div>`);
        parts.push(`<div class="muted" style="margin-top:4px">Bus/Route: ${esc(row.bus_or_route)} • Passenger: ${esc(row.passenger)}</div>`);
        parts.push(`<div class="muted" style="margin-top:4px">Type: ${esc(row.type)} • Category: ${esc(row.category)} • Rating: ${row.rating ? esc(row.rating) + '/5' : '—'}</div>`);
        if (row.message) parts.push(`<div style="margin-top:8px">${esc(row.message).replace(/\n/g,'<br>')}</div>`);
        if (row.response) parts.push(`<div class="muted" style="margin-top:8px"><strong>Reply:</strong><br>${esc(row.response).replace(/\n/g,'<br>')}</div>`);
        qPrev.innerHTML = parts.join('');
      });
    }

    // Manage panel
    function showManage(row) {
      const card = document.getElementById('manageCard');
      if (!card) return;

      document.getElementById('m_ref').textContent = refForId(row.id);
      document.getElementById('m_status').textContent = row.status || '';
      document.getElementById('m_meta').textContent = `Bus/Route: ${row.bus_or_route || '—'} | Passenger: ${row.passenger || '—'} | Rating: ${row.rating ? row.rating + '/5' : '—'}`;
      document.getElementById('m_desc').innerHTML = row.message ? esc(row.message).replace(/\n/g,'<br>') : '—';
      document.getElementById('m_current_reply').innerHTML = `Reply: ${row.response ? esc(row.response).replace(/\n/g,'<br>') : '—'}`;
      document.getElementById('m_id').value = row.id;
      document.getElementById('m_message').value = '';

      card.style.display = '';
      card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-manage');
      if (!btn) return;
      const row = findRowById(btn.getAttribute('data-id'));
      if (row) showManage(row);
    });
  </script>

</section>
        Use this ID
      </button>
    </div>
  </div>
</dialog>

<style>
/* Feedback Dialog Styling */
.feedback-dialog {
  border: none;
  border-radius: 16px;
  padding: 0;
  max-width: 600px;
  width: 90%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.feedback-dialog::backdrop {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
}

.feedback-dialog-content {
  display: flex;
  flex-direction: column;
}

.feedback-dialog-header {
  background: linear-gradient(135deg, #AA1B23, #8B1519);
  color: white;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #F59E0B;
}

.feedback-dialog-title {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  color: white;
}

.feedback-ref-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 16px;
  margin-left: 8px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.dialog-close-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.dialog-close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.feedback-dialog-body {
  padding: 28px 24px;
  background: #FAFAFA;
  max-height: 60vh;
  overflow-y: auto;
}

.feedback-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  border: 2px solid #E5E7EB;
  transition: all 0.2s;
}

.feedback-section:last-child {
  margin-bottom: 0;
}

.feedback-section:hover {
  border-color: var(--maroon);
  box-shadow: 0 4px 12px rgba(170, 27, 35, 0.1);
}

.feedback-section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--maroon);
  margin-bottom: 12px;
  font-weight: 600;
  font-size: 15px;
}

.feedback-section-header svg {
  color: var(--maroon);
}

.feedback-message, .feedback-response {
  margin: 0;
  color: #374151;
  line-height: 1.6;
  white-space: pre-wrap;
  font-size: 14px;
}

.feedback-response {
  font-style: italic;
  color: #6B7280;
}

.feedback-dialog-footer {
  padding: 20px 24px;
  background: white;
  border-top: 2px solid #E5E7EB;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.dialog-btn {
  padding: 11px 20px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.dialog-btn-secondary {
  background: #F3F4F6;
  color: #4B5563;
  border: 2px solid #D1D5DB;
}

.dialog-btn-secondary:hover {
  background: #E5E7EB;
  border-color: #9CA3AF;
  transform: translateY(-1px);
}

.dialog-btn-primary {
  background: linear-gradient(135deg, #AA1B23, #8B1519);
  color: white;
}

.dialog-btn-primary:hover {
  background: linear-gradient(135deg, #8B1519, #6B0F13);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(170, 27, 35, 0.4);
}
</style>

<!-- Tiny toast -->
<div id="toast" style="position:fixed;right:16px;bottom:16px;padding:10px 14px;border-radius:8px;background:#111827;color:#fff;display:none;"></div>

</section>

<style>
/* Feedback Page Custom Styles */
.feedback-notification {
  margin-bottom: 20px;
  padding: 14px 18px;
  border-radius: 10px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
}

.feedback-notification.success {
  background: #DEF7EC;
  color: #03543F;
  border: 1px solid #84E1BC;
}

.feedback-notification.error {
  background: #FDE8E8;
  color: #9B1C1C;
  border: 1px solid #F98080;
}

.feedback-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
  margin-top: 20px;
}

.feedback-action-card {
  background: #FAFAFA;
  border: 2px solid #E5E7EB;
  border-radius: 12px;
  padding: 24px;
  transition: all 0.2s;
}

.feedback-action-card:hover {
  border-color: var(--maroon);
  box-shadow: 0 4px 12px rgba(170, 27, 35, 0.1);
}

.action-card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid #E5E7EB;
}

.action-card-title {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
}

.action-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: var(--text);
  margin-bottom: 8px;
}

.form-select, .form-textarea {
  width: 100%;
  padding: 11px 14px;
  border: 2px solid #D1D5DB;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: var(--maroon);
  box-shadow: 0 0 0 3px rgba(170, 27, 35, 0.1);
}

.form-select:disabled, .form-textarea:disabled {
  background: #F3F4F6;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
  min-height: 120px;
}

.form-hint {
  display: block;
  font-size:12px;
  color: #6B7280;
  margin-top: 6px;
}

.form-error {
  display: block;
  color: #DC2626;
  font-size: 13px;
  font-weight: 500;
  margin-top: 6px;
}

.update-status-btn, .send-response-btn {
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  margin-top: 8px;
}

.update-status-btn {
  background: linear-gradient(135deg, #F59E0B, #D97706);
  color: #fff;
}

.update-status-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #D97706, #B45309);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.send-response-btn {
  background: linear-gradient(135deg, #AA1B23, #8B1519);
  color: #fff;
}

.send-response-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #8B1519, #6B0F13);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(170, 27, 35, 0.4);
}

.update-status-btn:disabled, .send-response-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.update-status-btn:disabled:hover, .send-response-btn:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* Feedback Details Card */
.feedback-details-card {
  margin-bottom: 20px;
  border: 2px solid var(--maroon);
  background: #FFF9F9;
}

.feedback-details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 16px;
  border-bottom: 2px solid #E5E7EB;
  margin-bottom: 16px;
}

.feedback-details-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  display: flex;
  align-items: center;
}

.close-details-btn {
  background: none;
  border: none;
  font-size: 28px;
  color: #9CA3AF;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-details-btn:hover {
  background: #F3F4F6;
  color: var(--text);
}

.feedback-details-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.detail-item {
  padding: 12px;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #E5E7EB;
}

.detail-label {
  font-size: 12px;
  color: #6B7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.detail-value {
  font-size: 14px;
  color: var(--text);
  font-weight: 500;
}

/* Loading state */
.btn-loading {
  position: relative;
  pointer-events: none;
}

.btn-loading::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  top: 50%;
  left: 50%;
  margin-left: -8px;
  margin-top: -8px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
  to { transform: rotate(360deg); }
}

/* Row update highlight animation */
.row-updated {
  background: #FFF9F9 !important;
  animation: highlightFade 1.2s ease-out;
}

@keyframes highlightFade {
  0% { background: #FEE2E2; }
  100% { background: transparent; }
}
</style>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  // Enhanced form validation and button enabling + Optimistic UI Updates
  (function() {
    // Update Status Form
    const statusForm = document.querySelector('.js-update-status-form');
    const statusFeedbackSelect = statusForm?.querySelector('.js-ref-select');
    const statusValueSelect = statusForm?.querySelector('.js-status-select');
    const updateBtn = statusForm?.querySelector('.update-status-btn');

    // Send Response Form
    const responseForm = document.querySelector('.js-send-response-form');
    const responseFeedbackSelect = responseForm?.querySelector('.js-ref-select-2');
    const responseTextarea = responseForm?.querySelector('.js-response');
    const sendBtn = responseForm?.querySelector('.send-response-btn');

    // Notification area
    const notificationArea = document.getElementById('feedbackNotification');

    // Show notification
    function showNotification(message, type = 'success') {
      if (!notificationArea) return;
      
      notificationArea.className = 'feedback-notification ' + type;
      notificationArea.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          ${type === 'success' 
            ? '<path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill="currentColor"/>'
            : '<path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" fill="currentColor"/>'
          }
        </svg>
        ${message}
      `;
      notificationArea.style.display = 'flex';
      
      setTimeout(() => {
        notificationArea.style.display = 'none';
      }, 4000);
    }

    // Update status badge in table
    function updateStatusBadge(feedbackRef, newStatus) {
      const row = document.querySelector(`tr[data-ref="${CSS.escape(feedbackRef)}"]`);
      if (!row) return;
      
      const badge = row.querySelector('.js-status-badge');
      if (!badge) return;
      
      // Update badge text and data attribute
      badge.textContent = newStatus;
      badge.setAttribute('data-status', newStatus);
      
      // Remove all status classes
      badge.className = 'status-badge js-status-badge';
      
      // Add appropriate class based on status
      const statusLower = newStatus.toLowerCase();
      if (statusLower === 'open') badge.classList.add('status-open');
      else if (statusLower === 'in progress') badge.classList.add('status-progress');
      else if (statusLower === 'resolved') badge.classList.add('status-resolved');
      else if (statusLower === 'closed') badge.classList.add('status-closed');
      
      // Add highlight animation
      row.classList.add('row-updated');
      setTimeout(() => row.classList.remove('row-updated'), 1200);
    }

    // Enable/disable Update Status button
    function checkStatusFormValidity() {
      if (statusFeedbackSelect && statusValueSelect && updateBtn) {
        const isValid = statusFeedbackSelect.value && statusValueSelect.value;
        updateBtn.disabled = !isValid;
      }
    }

    // Enable/disable Send Response button
    function checkResponseFormValidity() {
      if (responseFeedbackSelect && responseTextarea && sendBtn) {
        const isValid = responseFeedbackSelect.value && responseTextarea.value.trim();
        sendBtn.disabled = !isValid;
      }
    }

    // Attach listeners
    statusFeedbackSelect?.addEventListener('change', checkStatusFormValidity);
    statusValueSelect?.addEventListener('change', checkStatusFormValidity);
    responseFeedbackSelect?.addEventListener('change', checkResponseFormValidity);
    responseTextarea?.addEventListener('input', checkResponseFormValidity);

    // Handle Update Status form submission
    if (statusForm) {
      statusForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (updateBtn.disabled) return;
        
        const feedbackRef = statusFeedbackSelect.value;
        const newStatus = statusValueSelect.value;
        
        // Add loading state
        updateBtn.classList.add('btn-loading');
        updateBtn.disabled = true;
        const originalText = updateBtn.innerHTML;
        updateBtn.innerHTML = '';
        
        try {
          const formData = new FormData(this);
          
          const response = await fetch('<?= BASE_URL; ?>/B/feedback', {
            method: 'POST',
            body: formData
          });
          
          if (response.ok) {
            // Optimistically update the UI
            updateStatusBadge(feedbackRef, newStatus);
            
            // Show success message
            showNotification(`Status updated to "${newStatus}" for ${feedbackRef}`, 'success');
            
            // Reset form
            statusForm.reset();
            checkStatusFormValidity();
          } else {
            showNotification('Failed to update status. Please try again.', 'error');
          }
        } catch (error) {
          console.error('Error:', error);
          showNotification('Network error. Please try again.', 'error');
        } finally {
          // Remove loading state
          updateBtn.classList.remove('btn-loading');
          updateBtn.innerHTML = originalText;
          checkStatusFormValidity();
        }
        
        return false;
      });
    }

    // Handle Send Response form submission
    if (responseForm) {
      responseForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (sendBtn.disabled) return;
        
        const feedbackRef = responseFeedbackSelect.value;
        
        // Add loading state
        sendBtn.classList.add('btn-loading');
        sendBtn.disabled = true;
        const originalText = sendBtn.innerHTML;
        sendBtn.innerHTML = '';
        
        try {
          const formData = new FormData(this);
          
          const response = await fetch('<?= BASE_URL; ?>/B/feedback', {
            method: 'POST',
            body: formData
          });
          
          if (response.ok) {
            // Show success message
            showNotification(`Response sent successfully for ${feedbackRef}`, 'success');
            
            // Reset form
            responseForm.reset();
            checkResponseFormValidity();
          } else {
            showNotification('Failed to send response. Please try again.', 'error');
          }
        } catch (error) {
          console.error('Error:', error);
          showNotification('Network error. Please try again.', 'error');
        } finally {
          // Remove loading state
          sendBtn.classList.remove('btn-loading');
          sendBtn.innerHTML = originalText;
          checkResponseFormValidity();
        }
        
        return false;
      });
    }

    // View Dialog functionality
    const dialog = document.getElementById('feedback-dialog');
    const viewBtns = document.querySelectorAll('.js-view');
    
    viewBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const ref = this.getAttribute('data-ref') || '';
        const msg = this.getAttribute('data-message') || 'No message';
        const resp = this.getAttribute('data-response') || '';
        
        const refEl = dialog.querySelector('.js-dialog-ref');
        const msgEl = dialog.querySelector('.js-dialog-msg');
        const replyEl = dialog.querySelector('.js-dialog-reply');
        const replyBlock = dialog.querySelector('.js-dialog-reply-block');
        
        if (refEl) refEl.textContent = ref;
        if (msgEl) msgEl.textContent = msg;
        
        if (replyEl && replyBlock) {
          if (resp && resp.trim()) {
            replyEl.textContent = resp;
            replyBlock.style.display = '';
          } else {
            replyEl.textContent = 'No response yet.';
            replyBlock.style.display = '';
          }
        }
        
        if (dialog && typeof dialog.showModal === 'function') {
          dialog.showModal();
        } else if (dialog) {
          dialog.setAttribute('open', 'open');
        }
      });
    });

    // Use This ID button functionality
    const useIdBtn = dialog?.querySelector('.js-use-id-btn');
    if (useIdBtn) {
      useIdBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const refEl = dialog.querySelector('.js-dialog-ref');
        const ref = refEl?.textContent.trim() || '';
        
        if (statusFeedbackSelect) statusFeedbackSelect.value = ref;
        if (responseFeedbackSelect) responseFeedbackSelect.value = ref;
        
        checkStatusFormValidity();
        checkResponseFormValidity();
        
        if (dialog.close) {
          dialog.close();
        } else {
          dialog.removeAttribute('open');
        }
        
        showNotification(`Selected feedback ID ${ref}`, 'success');
      });
    }
  })();
});
</script>
