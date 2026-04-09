<?php
// Bus Owner Feedback (Private only)
// Vars from controller:
//   $feedback_refs (id + ref_code)
//   $feedback_list

$feedback_refs = is_array($feedback_refs ?? null) ? $feedback_refs : [];
$feedback_list = is_array($feedback_list ?? null) ? $feedback_list : [];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ui_status_label(?string $status): string {
  return strcasecmp(trim((string)$status), 'Resolved') === 0 ? 'Resolved' : 'Not Solved';
}
function ui_status_class(?string $status): string {
  return strcasecmp(trim((string)$status), 'Resolved') === 0 ? 'fb-badge--resolved' : 'fb-badge--not-solved';
}
?>

<section id="feedbackPage">

  <!-- Page Header -->
  <header class="fb-page-header">
    <div class="fb-header-text">
      <h2 class="fb-page-title">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Passenger Feedback &amp; Complaints
      </h2>
      <p class="fb-page-subtitle">Manage feedback and complaints for your private fleet</p>
    </div>
    <?php
      // Stats: count complaints only (Resolved/Pending only applies to complaints)
      $complaints_only = array_filter($feedback_list, function($f) {
        $t = strtolower(trim((string)($f['type']     ?? '')));
        $c = strtolower(trim((string)($f['category'] ?? '')));
        return $t === 'complaint' || $c === 'complaint';
      });
      $complaints_resolved = array_filter($complaints_only, fn($f) =>
        strcasecmp(trim((string)($f['status'] ?? '')), 'Resolved') === 0
      );
      $complaints_pending  = array_filter($complaints_only, fn($f) =>
        strcasecmp(trim((string)($f['status'] ?? '')), 'Resolved') !== 0
      );
    ?>
    <div class="fb-header-stats" id="fb-header-stats">
      <div class="fb-hstat">
        <span class="fb-hstat-num" id="fb-stat-total"><?= count($complaints_only) ?></span>
        <span class="fb-hstat-label">Complaints</span>
      </div>
      <div class="fb-hstat-divider"></div>
      <div class="fb-hstat">
        <span class="fb-hstat-num fb-hstat-num--resolved" id="fb-stat-resolved"><?= count($complaints_resolved) ?></span>
        <span class="fb-hstat-label">Resolved</span>
      </div>
      <div class="fb-hstat-divider"></div>
      <div class="fb-hstat">
        <span class="fb-hstat-num fb-hstat-num--pending" id="fb-stat-pending"><?= count($complaints_pending) ?></span>
        <span class="fb-hstat-label">Pending</span>
      </div>
    </div>
  </header>

  <!-- Flash Notification -->
  <?php if (!empty($success)): ?>
  <div class="fb-alert fb-alert--success">
    <svg width="18" height="18" fill="none" viewBox="0 0 20 20"><path fill="currentColor" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
    <?= h($success) ?>
  </div>
  <?php elseif (!empty($error)): ?>
  <div class="fb-alert fb-alert--error">
    <svg width="18" height="18" fill="none" viewBox="0 0 20 20"><path fill="currentColor" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
    <?= h($error) ?>
  </div>
  <?php endif; ?>

  <!-- Toolbar -->
  <div class="fb-toolbar">
    <div class="fb-toolbar__filters">
      <div class="fb-filter-group">
        <label class="fb-filter-label" for="fb-filter-status">Status</label>
        <select id="fb-filter-status" class="fb-select">
          <option value="">All Statuses</option>
          <option value="Not Solved">Not Solved</option>
          <option value="Resolved">Resolved</option>
        </select>
      </div>
      <div class="fb-filter-group">
        <label class="fb-filter-label" for="fb-filter-type">Type</label>
        <select id="fb-filter-type" class="fb-select">
          <option value="">All Types</option>
          <option value="Complaint">Complaint</option>
          <option value="Feedback">Feedback</option>
          <option value="Suggestion">Suggestion</option>
        </select>
      </div>
    </div>
    <div class="fb-toolbar__search">
      <svg class="fb-search-icon" width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
      <input type="text" id="fb-search" class="fb-search-input" placeholder="Search by ref, passenger, bus / route…">
    </div>
  </div>

  <!-- Feedback Card Grid -->
  <div class="fb-list" id="fb-list">

    <?php if (empty($feedback_list)): ?>
    <div class="fb-empty" style="grid-column:1/-1">
      <div class="fb-empty__icon">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24"><path fill="#D1D5DB" d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/></svg>
      </div>
      <p class="fb-empty__text">No feedback records found.</p>
    </div>
    <?php else: ?>
    <?php foreach ($feedback_list as $f):
      $id       = (int)($f['id'] ?? 0);
      $ref      = (string)($f['ref_code'] ?? ('C' . str_pad((string)$id, 6, '0', STR_PAD_LEFT)));
      $statRaw  = (string)($f['status'] ?? 'Open');
      $stat     = ui_status_label($statRaw);
      $rate     = (int)($f['rating'] ?? 0);
      $reply    = trim((string)($f['response'] ?? ''));
      $type     = (string)($f['type'] ?? '');
      $category = (string)($f['category'] ?? '');
      $message  = (string)($f['message'] ?? '');
      $date     = (string)($f['date'] ?? '');
      $passenger= (string)($f['passenger'] ?? '');
      $bus      = (string)($f['bus_or_route'] ?? '');

      $statClass = ui_status_class($statRaw);
      $typeClass = match(strtolower($type)) {
        'complaint'  => 'fb-chip--complaint',
        'suggestion' => 'fb-chip--suggestion',
        default      => 'fb-chip--feedback',
      };
      $isComplaint = strtolower(trim($type)) === 'complaint' || strtolower(trim($category)) === 'complaint';
      $isResolved  = strcasecmp(trim($statRaw), 'Resolved') === 0;
    ?>
    <div class="fb-card js-fb-card <?= $isResolved ? 'fb-card--resolved' : '' ?>"
         data-id="<?= $id ?>"
         data-status="<?= h($stat) ?>"
         data-is-complaint="<?= $isComplaint ? '1' : '0' ?>"
         data-type="<?= h($type) ?>"
         data-search="<?= h(strtolower($ref . ' ' . $passenger . ' ' . $bus . ' ' . $category)) ?>">

      <!-- Card Header -->
      <div class="fb-card__header">
        <div class="fb-card__ref-row">
          <span class="fb-card__ref"><?= h($ref) ?></span>
          <?php if ($isComplaint): ?>
          <span class="fb-badge <?= $statClass ?>"><?= h($stat) ?></span>
          <?php endif; ?>
          <?php if ($type): ?>
          <span class="fb-chip <?= $typeClass ?>"><?= h($type) ?></span>
          <?php endif; ?>
          <?php if ($reply): ?>
          <span class="fb-chip-replied">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>
            Replied
          </span>
          <?php endif; ?>
        </div>
        <div class="fb-card__meta">
          <span class="fb-meta-item">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
            <?= h($date) ?>
          </span>
          <?php if ($passenger): ?>
          <span class="fb-meta-item">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <?= h($passenger) ?>
          </span>
          <?php endif; ?>
          <?php if ($bus): ?>
          <span class="fb-meta-item">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><rect x="1" y="8" width="22" height="12" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="5" cy="20" r="2" stroke="currentColor" stroke-width="2"/><circle cx="19" cy="20" r="2" stroke="currentColor" stroke-width="2"/></svg>
            <?= h($bus) ?>
          </span>
          <?php endif; ?>
          <?php if ($category && strtolower($category) !== strtolower($type)): ?>
          <span class="fb-meta-item">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M7 7h.01M3 3h7l11 11a2 2 0 010 2.828l-4.172 4.172a2 2 0 01-2.828 0L3 10V3z"/></svg>
            <?= h($category) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Message preview -->
      <?php if ($message): ?>
      <p class="fb-card__message"><?= h($message) ?></p>
      <?php endif; ?>

      <!-- Card Footer -->
      <div class="fb-card__footer">
        <div class="fb-card__stars">
          <?php if ($rate > 0): ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg class="fb-star <?= $i <= $rate ? 'fb-star--on' : '' ?>" width="13" height="13" viewBox="0 0 20 20"><path fill="currentColor" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
            <span class="fb-star-num"><?= $rate ?>/5</span>
          <?php else: ?>
            <span class="fb-no-rating">No rating</span>
          <?php endif; ?>
        </div>
        <button class="fb-manage-btn js-fb-manage" data-id="<?= $id ?>" type="button">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Manage
        </button>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <!-- Empty filter state -->
  <div class="fb-empty" id="fb-no-results" style="display:none;">
    <div class="fb-empty__icon">
      <svg width="48" height="48" fill="none" viewBox="0 0 24 24"><path stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
    </div>
    <p class="fb-empty__text">No results match your filters.</p>
    <button class="fb-clear-btn" id="fb-clear-filters">Clear Filters</button>
  </div>

  <!-- ==============================
       MANAGE MODAL (Fixed, no scroll)
       ============================== -->
  <div class="fb-modal__backdrop" id="fb-modal" hidden>
    <div class="fb-modal__panel" role="dialog" aria-modal="true" aria-labelledby="fb-modal-title">

      <!-- Modal Header -->
      <div class="fb-modal__head">
        <div class="fb-modal__head-left">
          <div class="fb-modal__head-icon">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          </div>
          <h3 class="fb-modal__title" id="fb-modal-title">
            Feedback &nbsp;<span class="fb-modal__ref" id="fb-m-ref"></span>
          </h3>
          <span class="fb-badge fb-modal__status-badge" id="fb-m-status-badge"></span>
        </div>
        <button class="fb-modal__close" id="fb-modal-close" type="button" aria-label="Close">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2.5" stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Modal Body: 2-column fixed layout -->
      <div class="fb-modal__body">

        <!-- LEFT COLUMN -->
        <div class="fb-modal__col-left">

          <!-- Info grid -->
          <div class="fb-modal__info-section">
            <div class="fb-modal__info-label">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Details
            </div>
            <div class="fb-modal__info-grid">
              <div class="fb-info-cell">
                <div class="fb-info-cell__label">Passenger</div>
                <div class="fb-info-cell__val" id="fb-m-passenger">—</div>
              </div>
              <div class="fb-info-cell">
                <div class="fb-info-cell__label">Bus / Route</div>
                <div class="fb-info-cell__val" id="fb-m-bus">—</div>
              </div>
              <div class="fb-info-cell">
                <div class="fb-info-cell__label">Date</div>
                <div class="fb-info-cell__val" id="fb-m-date">—</div>
              </div>
              <div class="fb-info-cell">
                <div class="fb-info-cell__label">Type &amp; Category</div>
                <div class="fb-info-cell__val" id="fb-m-type">—</div>
              </div>
            </div>
            <!-- Rating row -->
            <div class="fb-modal__rating-row">
              <span class="fb-info-cell__label">Rating</span>
              <div id="fb-m-rating" class="fb-modal__stars"></div>
            </div>
          </div>

          <!-- Passenger Message -->
          <div class="fb-modal__info-section">
            <div class="fb-modal__info-label">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
              Passenger Message
            </div>
            <div class="fb-modal__message-box" id="fb-m-message"></div>
          </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="fb-modal__col-right">

          <!-- Previous Reply -->
          <div class="fb-modal__info-section" id="fb-existing-reply-section">
            <div class="fb-modal__info-label">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M3 10a7 7 0 0014 0V6a7 7 0 00-14 0v4zm11 0v4a4 4 0 01-8 0v-4"/></svg>
              Previous Reply
            </div>
            <div class="fb-modal__prev-reply" id="fb-m-reply"></div>
          </div>

          <!-- Send Reply Form -->
          <div class="fb-modal__reply-section">
            <div class="fb-modal__info-label">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
              Send Reply
            </div>
            <form method="post" action="/B/feedback" id="fb-reply-form">
              <input type="hidden" name="complaint_id" id="fb-m-id">
              <textarea
                name="message"
                id="fb-m-message-input"
                class="fb-modal__textarea"
                placeholder="Type your reply to the passenger…"></textarea>
              <div class="fb-modal__actions">
                <button type="submit" name="action" value="reply" class="fb-action-btn fb-action-btn--send">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                  Send Reply
                </button>
                <button type="submit" name="action" value="resolve" class="fb-action-btn fb-action-btn--resolve fb-btn--hidden" id="fb-btn-resolve">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Mark Resolved
                </button>
                <button type="submit" name="action" value="not_solved" class="fb-action-btn fb-action-btn--pending fb-btn--hidden" id="fb-btn-not-solved">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Mark Not Solved
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
      <!-- Modal Footer bar -->
      <div class="fb-modal__foot">
        <span class="fb-modal__foot-hint">Press <kbd>Esc</kbd> or click outside to close</span>
      </div>

    </div>
  </div>

</section>

<style>
/* ─── CSS Variables ───────────────────────────────────── */
:root {
  --fb-maroon:      #80143c;
  --fb-maroon-d:    #5e0f2c;
  --fb-maroon-l:    #f9eef3;
  --fb-gold:        #f3b944;
  --fb-gold-l:      #fffbeb;
  --fb-res:         #059669;
  --fb-res-bg:      #ecfdf5;
  --fb-res-border:  #a7f3d0;
  --fb-ns:          #b45309;
  --fb-ns-bg:       #fffbeb;
  --fb-ns-border:   #fcd34d;
  --fb-border:      #e5e7eb;
  --fb-muted:       #6b7280;
  --fb-card-shadow: 0 2px 8px rgba(0,0,0,.06);
  --fb-modal-w:     860px;
  --fb-modal-h:     540px;
}

/* ─── Page Layout ─────────────────────────────────────── */
#feedbackPage {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ─── Page Header ─────────────────────────────────────── */
.fb-page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.fb-header-text { flex: 1; }
.fb-page-title {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 800;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 10px;
}
.fb-page-title svg { color: var(--fb-maroon); }
.fb-page-subtitle { margin: 0; font-size: 13px; color: var(--fb-muted); }

.fb-header-stats {
  display: flex;
  align-items: center;
  gap: 0;
  background: #fff;
  border: 1.5px solid var(--fb-border);
  border-radius: 12px;
  padding: 10px 20px;
  box-shadow: var(--fb-card-shadow);
}
.fb-hstat { text-align: center; padding: 0 16px; }
.fb-hstat-num {
  display: block;
  font-size: 22px;
  font-weight: 800;
  color: var(--fb-maroon);
  line-height: 1;
}
.fb-hstat-num--resolved { color: var(--fb-res); }
.fb-hstat-num--pending  { color: var(--fb-ns); }
.fb-hstat-label { font-size: 11px; color: var(--fb-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.fb-hstat-divider { width: 1px; height: 36px; background: var(--fb-border); }

/* ─── Alert ───────────────────────────────────────────── */
.fb-alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 18px; border-radius: 10px;
  font-weight: 500; font-size: 14px;
}
.fb-alert--success { background: var(--fb-res-bg); color: #065f46; border: 1px solid var(--fb-res-border); }
.fb-alert--error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

/* ─── Toolbar ─────────────────────────────────────────── */
.fb-toolbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
  background: #fff;
  border: 1.5px solid var(--fb-border);
  border-radius: 12px;
  padding: 14px 18px;
  box-shadow: var(--fb-card-shadow);
}
.fb-toolbar__filters { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.fb-filter-group { display: flex; flex-direction: column; gap: 4px; }
.fb-filter-label { font-size: 11px; font-weight: 700; color: var(--fb-muted); text-transform: uppercase; letter-spacing: .4px; }
.fb-select {
  padding: 8px 14px; border: 1.5px solid var(--fb-border); border-radius: 8px;
  font-size: 13px; font-family: inherit; background: #f9fafb; cursor: pointer;
  color: #111827; min-width: 150px;
  transition: border-color .2s, box-shadow .2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236b7280' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 32px;
}
.fb-select:focus { outline: none; border-color: var(--fb-maroon); box-shadow: 0 0 0 3px rgba(128,20,60,.1); background-color: #fff; }

.fb-toolbar__search { position: relative; min-width: 260px; }
.fb-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.fb-search-input {
  width: 100%; padding: 9px 14px 9px 36px;
  border: 1.5px solid var(--fb-border); border-radius: 8px;
  font-size: 13.5px; font-family: inherit; background: #f9fafb;
  transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
  color: #111827;
}
.fb-search-input:focus { outline: none; border-color: var(--fb-maroon); box-shadow: 0 0 0 3px rgba(128,20,60,.1); background: #fff; }
.fb-search-input::placeholder { color: #9ca3af; }

/* ─── Card Grid ───────────────────────────────────────── */
.fb-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

/* ─── Card ────────────────────────────────────────────── */
.fb-card {
  background: #fff;
  border-radius: 12px;
  border: 1.5px solid var(--fb-border);
  padding: 16px 18px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: border-color .2s, box-shadow .2s, transform .15s;
  box-shadow: var(--fb-card-shadow);
  cursor: default;
}
.fb-card:hover {
  border-color: var(--fb-maroon);
  box-shadow: 0 6px 20px rgba(128,20,60,.12);
  transform: translateY(-2px);
}
.fb-card--resolved { border-left: 3px solid var(--fb-res); }

.fb-card__header { display: flex; flex-direction: column; gap: 6px; }
.fb-card__ref-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.fb-card__ref { font-weight: 800; font-size: 13.5px; color: var(--fb-maroon); letter-spacing: .3px; }

/* Badge */
.fb-badge {
  display: inline-flex; align-items: center;
  padding: 2px 9px; border-radius: 20px;
  font-size: 10.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
}
.fb-badge--not-solved { background: var(--fb-ns-bg); color: var(--fb-ns); border: 1px solid var(--fb-ns-border); }
.fb-badge--resolved   { background: var(--fb-res-bg); color: var(--fb-res); border: 1px solid var(--fb-res-border); }

/* Chips */
.fb-chip {
  display: inline-flex; align-items: center;
  padding: 2px 8px; border-radius: 5px;
  font-size: 10.5px; font-weight: 700; letter-spacing: .2px;
}
.fb-chip--complaint  { background: #fef3c7; color: #92400e; }
.fb-chip--feedback   { background: #dbeafe; color: #1e40af; }
.fb-chip--suggestion { background: #ede9fe; color: #5b21b6; }

.fb-chip-replied {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 8px; background: var(--fb-res-bg); color: var(--fb-res);
  border: 1px solid var(--fb-res-border); border-radius: 20px;
  font-size: 10.5px; font-weight: 700;
}

/* Meta */
.fb-card__meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.fb-meta-item { display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--fb-muted); }
.fb-meta-item svg { flex-shrink: 0; }

/* Message */
.fb-card__message {
  font-size: 13px; color: #374151; line-height: 1.5; margin: 0;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}

/* Footer */
.fb-card__footer {
  display: flex; align-items: center;
  border-top: 1px solid #f3f4f6; padding-top: 10px; margin-top: 2px;
}
.fb-card__stars { display: flex; align-items: center; gap: 2px; flex: 1; }
.fb-star { color: #d1d5db; }
.fb-star--on { color: #f59e0b; }
.fb-star-num { font-size: 11px; color: #9ca3af; margin-left: 4px; }
.fb-no-rating { font-size: 11px; color: #d1d5db; font-style: italic; }

.fb-manage-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px;
  background: var(--fb-maroon);
  color: #fff;
  border: none; border-radius: 8px;
  font-size: 12.5px; font-weight: 700;
  cursor: pointer; font-family: inherit;
  transition: background .2s, transform .15s, box-shadow .2s;
}
.fb-manage-btn:hover {
  background: var(--fb-maroon-d);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(128,20,60,.3);
}

/* Empty */
.fb-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 12px;
  padding: 60px 24px; color: var(--fb-muted); text-align: center;
  grid-column: 1 / -1;
}
.fb-empty__icon { width: 72px; height: 72px; background: #f3f4f6; border-radius: 50%; display: grid; place-items: center; }
.fb-empty__text { margin: 0; font-size: 14px; font-weight: 500; }
.fb-clear-btn {
  padding: 8px 20px; background: none; border: 1.5px solid var(--fb-border);
  border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--fb-muted);
  cursor: pointer; font-family: inherit; transition: all .2s;
}
.fb-clear-btn:hover { border-color: var(--fb-maroon); color: var(--fb-maroon); }

/* ─── MODAL ───────────────────────────────────────────── */
.fb-modal__backdrop {
  position: fixed; inset: 0;
  background: rgba(15,10,25,.6);
  backdrop-filter: blur(4px);
  z-index: 12000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.fb-modal__backdrop[hidden] { display: none !important; }

.fb-modal__panel {
  background: #fff;
  border-radius: 18px;
  width: min(96vw, var(--fb-modal-w));
  height: min(90vh, var(--fb-modal-h));
  display: flex;
  flex-direction: column;
  box-shadow: 0 32px 64px rgba(0,0,0,.3), 0 0 0 1px rgba(0,0,0,.06);
  animation: fb-modal-in .22s cubic-bezier(.34,1.56,.64,1);
  overflow: hidden;
}
@keyframes fb-modal-in {
  from { opacity: 0; transform: scale(.92) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Modal Head */
.fb-modal__head {
  background: linear-gradient(135deg, var(--fb-maroon) 0%, #6b1133 100%);
  padding: 14px 20px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 3px solid var(--fb-gold);
  flex-shrink: 0;
}
.fb-modal__head-left { display: flex; align-items: center; gap: 10px; }
.fb-modal__head-icon {
  width: 34px; height: 34px; background: rgba(255,255,255,.15);
  border-radius: 8px; display: grid; place-items: center;
  color: #fff; flex-shrink: 0;
}
.fb-modal__title {
  margin: 0; font-size: 15px; font-weight: 800; color: #fff;
  display: flex; align-items: center; gap: 0;
}
.fb-modal__ref {
  background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.3);
  padding: 2px 9px; border-radius: 5px; font-size: 12px; font-weight: 700;
}
.fb-modal__status-badge { flex-shrink: 0; }
.fb-modal__close {
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  color: #fff; width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .2s; flex-shrink: 0;
}
.fb-modal__close:hover { background: rgba(255,255,255,.28); }

/* Modal Body — 2 columns, flex, no overflow */
.fb-modal__body {
  display: flex;
  flex: 1;
  overflow: hidden;
  min-height: 0;
}

.fb-modal__col-left {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 18px 16px 18px 20px;
  border-right: 1px solid var(--fb-border);
  overflow: hidden;
}
.fb-modal__col-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 18px 20px 18px 16px;
  overflow: hidden;
}

/* Info section */
.fb-modal__info-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}
.fb-modal__info-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 10.5px; font-weight: 800; color: var(--fb-maroon);
  text-transform: uppercase; letter-spacing: .6px;
  padding-bottom: 6px; border-bottom: 1.5px solid #f3f4f6;
}
.fb-modal__info-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
.fb-info-cell {
  background: #f9fafb; border: 1px solid #efefef;
  border-radius: 8px; padding: 9px 12px;
}
.fb-info-cell__label {
  font-size: 10px; font-weight: 700; color: #9ca3af;
  text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px;
}
.fb-info-cell__val { font-size: 12.5px; color: #111827; font-weight: 600; line-height: 1.3; }

.fb-modal__rating-row {
  display: flex; align-items: center; gap: 10px;
  background: #f9fafb; border: 1px solid #efefef;
  border-radius: 8px; padding: 8px 12px;
}
.fb-modal__rating-row .fb-info-cell__label { margin: 0; }
.fb-modal__stars { display: flex; align-items: center; gap: 2px; flex: 1; }

.fb-modal__message-box {
  flex: 1;
  background: #f9fafb; border: 1px solid #efefef;
  border-radius: 8px; padding: 11px 14px;
  font-size: 13px; color: #374151; line-height: 1.6;
  white-space: pre-wrap; overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #e5e7eb transparent;
  min-height: 0;
  max-height: 100px;
}

/* Previous reply */
.fb-modal__prev-reply {
  background: linear-gradient(135deg, #fafafa, #f5f5f5);
  border: 1px solid #ececec;
  border-left: 3px solid var(--fb-gold);
  border-radius: 8px; padding: 10px 14px;
  font-size: 13px; color: #4b5563; font-style: italic; line-height: 1.6;
  white-space: pre-wrap; overflow-y: auto;
  scrollbar-width: thin;
  max-height: 80px;
  min-height: 44px;
  flex-shrink: 0;
}

/* Reply section */
.fb-modal__reply-section {
  display: flex; flex-direction: column; gap: 8px; flex: 1; min-height: 0;
}
.fb-modal__textarea {
  flex: 1; width: 100%;
  min-height: 0;
  resize: none;
  padding: 10px 13px;
  border: 1.5px solid var(--fb-border); border-radius: 8px;
  font-size: 13px; font-family: inherit; line-height: 1.5;
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
  color: #111827;
}
.fb-modal__textarea:focus {
  outline: none; border-color: var(--fb-maroon);
  box-shadow: 0 0 0 3px rgba(128,20,60,.1);
}
.fb-modal__textarea::placeholder { color: #9ca3af; }

.fb-modal__actions {
  display: flex; gap: 8px; flex-shrink: 0;
}

/* Action buttons */
.fb-action-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 8px 14px; border: none; border-radius: 8px;
  font-size: 12.5px; font-weight: 700; cursor: pointer;
  font-family: inherit; transition: background .18s, transform .15s, box-shadow .18s;
  white-space: nowrap;
}
.fb-action-btn:hover { transform: translateY(-1px); }

.fb-action-btn--send {
  background: var(--fb-maroon); color: #fff; flex: 1;
}
.fb-action-btn--send:hover { background: var(--fb-maroon-d); box-shadow: 0 4px 14px rgba(128,20,60,.3); }

.fb-action-btn--resolve {
  background: var(--fb-res-bg); color: var(--fb-res);
  border: 1.5px solid var(--fb-res-border);
}
.fb-action-btn--resolve:hover { background: #d1fae5; }

.fb-action-btn--pending {
  background: var(--fb-ns-bg); color: var(--fb-ns);
  border: 1.5px solid var(--fb-ns-border);
}
.fb-action-btn--pending:hover { background: #fef3c7; }

.fb-btn--hidden { display: none !important; }

/* Modal foot hint */
.fb-modal__foot {
  padding: 8px 20px;
  background: #f9fafb;
  border-top: 1px solid var(--fb-border);
  display: flex; align-items: center; justify-content: flex-end;
  flex-shrink: 0;
}
.fb-modal__foot-hint { font-size: 11px; color: #9ca3af; }
.fb-modal__foot-hint kbd {
  background: #e5e7eb; border: 1px solid #d1d5db;
  border-radius: 4px; padding: 1px 5px;
  font-size: 10px; font-family: inherit;
  color: #374151;
}

/* ─── Responsive ──────────────────────────────────────── */
@media (max-width: 1100px) {
  .fb-list { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
  .fb-modal__body { flex-direction: column; overflow-y: auto; }
  .fb-modal__col-left,
  .fb-modal__col-right { border-right: none; padding: 16px; }
  .fb-modal__col-left  { border-bottom: 1px solid var(--fb-border); }
  .fb-modal__panel {
    height: min(90vh, 640px);
  }
  .fb-toolbar { flex-direction: column; align-items: stretch; }
  .fb-toolbar__search { min-width: auto; }
}
@media (max-width: 600px) {
  .fb-modal__head { padding: 12px 14px; }
  .fb-header-stats { display: none; }
  .fb-modal__info-grid { grid-template-columns: 1fr; }
  .fb-modal__actions { flex-direction: column; }
  .fb-action-btn--send { flex: none; }
}
</style>

<script>
(function () {
  var ROWS = <?= json_encode($feedback_list, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

  function findRowById(id) {
    return ROWS.find(function(r){ return String(r.id) === String(id); }) || null;
  }
  function refForRow(r) {
    return r.ref_code || ('C' + String(r.id).padStart(6, '0'));
  }

  /* ── Filter ── */
  var cards     = Array.from(document.querySelectorAll('.js-fb-card'));
  var noResults = document.getElementById('fb-no-results');
  var searchEl  = document.getElementById('fb-search');
  var statusEl  = document.getElementById('fb-filter-status');
  var typeEl    = document.getElementById('fb-filter-type');
  var clearBtn  = document.getElementById('fb-clear-filters');

  function applyFilters() {
    var q      = (searchEl ? searchEl.value : '').toLowerCase().trim();
    var status = statusEl ? statusEl.value : '';
    var type   = typeEl   ? typeEl.value   : '';
    var visible = 0;
    cards.forEach(function(card) {
      var ms  = !q      || card.dataset.search.includes(q);
      var mst = !status || card.dataset.status === status;
      var mt  = !type   || card.dataset.type   === type;
      var show = ms && mst && mt;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (noResults) noResults.style.display = visible === 0 ? 'flex' : 'none';
  }

  if (searchEl) searchEl.addEventListener('input', applyFilters);
  if (statusEl) statusEl.addEventListener('change', applyFilters);
  if (typeEl)   typeEl.addEventListener('change', applyFilters);
  if (clearBtn) clearBtn.addEventListener('click', function(){
    if (searchEl) searchEl.value = '';
    if (statusEl) statusEl.value = '';
    if (typeEl)   typeEl.value   = '';
    applyFilters();
  });

  /* ── Modal ── */
  var modal         = document.getElementById('fb-modal');
  var closeBtn      = document.getElementById('fb-modal-close');
  var mRef          = document.getElementById('fb-m-ref');
  var mBadge        = document.getElementById('fb-m-status-badge');
  var mPassenger    = document.getElementById('fb-m-passenger');
  var mBus          = document.getElementById('fb-m-bus');
  var mDate         = document.getElementById('fb-m-date');
  var mType         = document.getElementById('fb-m-type');
  var mRating       = document.getElementById('fb-m-rating');
  var mMessage      = document.getElementById('fb-m-message');
  var mReply        = document.getElementById('fb-m-reply');
  var mReplySection = document.getElementById('fb-existing-reply-section');
  var mId           = document.getElementById('fb-m-id');
  var mMsgInput     = document.getElementById('fb-m-message-input');
  var mResolveBtn   = document.getElementById('fb-btn-resolve');
  var mNotSolvedBtn = document.getElementById('fb-btn-not-solved');

  function isComplaintRow(row) {
    var t = (row && row.type     ? String(row.type)     : '').toLowerCase().trim();
    var c = (row && row.category ? String(row.category) : '').toLowerCase().trim();
    return t === 'complaint' || c === 'complaint';
  }

  function statusClass(s) {
    return ((s||'').toLowerCase() === 'resolved') ? 'fb-badge--resolved' : 'fb-badge--not-solved';
  }
  function statusLabel(s) {
    return ((s||'').toLowerCase() === 'resolved') ? 'Resolved' : 'Not Solved';
  }

  function starsHTML(rate) {
    if (!rate || rate < 1)
      return '<span style="color:#d1d5db;font-style:italic;font-size:12px">No rating</span>';
    var html = '';
    for (var i = 1; i <= 5; i++) {
      html += '<svg class="fb-star'+(i<=rate?' fb-star--on':'')+'" width="13" height="13" viewBox="0 0 20 20">'
             +'<path fill="currentColor" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>'
             +'</svg>';
    }
    return html + '<span style="color:#9ca3af;font-size:11px;margin-left:4px">'+rate+'/5</span>';
  }

  function openModal(row, isComplaint) {
    var ref = refForRow(row);

    if (mRef)   mRef.textContent = ref;
    // Show the Resolved / Not Solved badge ONLY for complaints
    if (mBadge) {
      var byRow = isComplaintRow(row);
      if (isComplaint && byRow) {
        mBadge.textContent  = statusLabel(row.status);
        mBadge.className    = 'fb-badge ' + statusClass(row.status);
        mBadge.style.display = '';
      } else {
        mBadge.textContent  = '';
        mBadge.style.display = 'none';
      }
    }
    if (mPassenger) mPassenger.textContent = row.passenger || '—';
    if (mBus)       mBus.textContent       = row.bus_or_route || '—';
    if (mDate)      mDate.textContent      = row.date || '—';
    if (mType)      mType.textContent      = [row.type, row.category].filter(Boolean).join('  ') || '—';
    if (mRating)    mRating.innerHTML      = starsHTML(parseInt(row.rating)||0);
    if (mMessage)   mMessage.textContent   = row.message || '';
    if (mId)        mId.value              = row.id;
    if (mMsgInput)  mMsgInput.value        = '';

    var byRow  = isComplaintRow(row);
    var showStatusBtns = !!(isComplaint && byRow);
    if (mResolveBtn)   mResolveBtn.classList.toggle('fb-btn--hidden', !showStatusBtns);
    if (mNotSolvedBtn) mNotSolvedBtn.classList.toggle('fb-btn--hidden', !showStatusBtns);

    if (mReply && mReplySection) {
      var r = (row.response || '').trim();
      if (r) { mReply.textContent = r; mReplySection.style.display = ''; }
      else     mReplySection.style.display = 'none';
    }

    if (modal) modal.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (modal) modal.setAttribute('hidden','');
    document.body.style.overflow = '';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (modal) modal.addEventListener('click', function(e){ if(e.target === modal) closeModal(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modal && !modal.hidden) closeModal(); });

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.js-fb-manage');
    if (!btn) return;
    var card = btn.closest('.js-fb-card');
    var isComplaint = !!(card && card.dataset.isComplaint === '1');
    var row = findRowById(btn.dataset.id);
    if (row) openModal(row, isComplaint);
  });

})();
</script>