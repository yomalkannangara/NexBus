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
  <header class="page-header">
    <div>
      <h2 class="page-title">Passenger Feedback &amp; Complaints</h2>
      <p class="page-subtitle">Private buses under your operator</p>
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
  <div class="fb-toolbar" style="background:#fff;border-radius:10px;border:1.5px solid #e5e7eb;">
    <div class="fb-toolbar__filters">
      <div class="fb-filter-group">
        <label class="fb-filter-label" for="fb-filter-status">Status:</label>
        <select id="fb-filter-status" class="fb-toolbar__select fb-toolbar__select--primary">
          <option value="">All</option>
          <option value="Not Solved">Not Solved</option>
          <option value="Resolved">Resolved</option>
        </select>
      </div>
      <div class="fb-filter-group">
        <label class="fb-filter-label" for="fb-filter-type">Type:</label>
        <select id="fb-filter-type" class="fb-toolbar__select">
          <option value="">All</option>
          <option value="Complaint">Complaint</option>
          <option value="Feedback">Feedback</option>
          <option value="Suggestion">Suggestion</option>
        </select>
      </div>
    </div>
    <div class="fb-toolbar__search">
      <svg class="fb-toolbar__search-icon" width="18" height="18" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
      <input type="text" id="fb-search" class="fb-toolbar__input" placeholder="Search by ref, passenger, bus/route...">
    </div>
  </div>

  <!-- Feedback Card List -->
  <div class="fb-list" id="fb-list">

    <?php if (empty($feedback_list)): ?>
    <div class="fb-empty">
      <svg width="56" height="56" fill="none" viewBox="0 0 24 24"><path fill="#D1D5DB" d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/></svg>
      <p>No feedback records found.</p>
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
    ?>
    <div class="fb-card js-fb-card"
         data-id="<?= $id ?>"
         data-status="<?= h($stat) ?>"
         data-is-complaint="<?= $isComplaint ? '1' : '0' ?>"
         data-type="<?= h($type) ?>"
         data-search="<?= h(strtolower($ref . ' ' . $passenger . ' ' . $bus)) ?>">

      <div class="fb-card__top">
        <div class="fb-card__ref-row">
          <span class="fb-card__ref"><?= h($ref) ?></span>
          <span class="fb-badge <?= $statClass ?>"><?= h($stat) ?></span>
          <?php if ($type): ?>
          <span class="fb-chip <?= $typeClass ?>"><?= h($type) ?></span>
          <?php endif; ?>
        </div>
        <div class="fb-card__meta">
          <span class="fb-card__meta-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
            <?= h($date) ?>
          </span>
          <span class="fb-card__meta-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <?= h($passenger ?: '') ?>
          </span>
          <span class="fb-card__meta-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><rect x="1" y="8" width="22" height="12" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="5" cy="20" r="2" stroke="currentColor" stroke-width="2"/><circle cx="19" cy="20" r="2" stroke="currentColor" stroke-width="2"/></svg>
            <?= h($bus ?: '') ?>
          </span>
          <?php if ($category): ?>
          <span class="fb-card__meta-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M7 7h.01M3 3h7l11 11a2 2 0 010 2.828l-4.172 4.172a2 2 0 01-2.828 0L3 10V3z"/></svg>
            <?= h($category) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($message): ?>
      <p class="fb-card__message"><?= h($message) ?></p>
      <?php endif; ?>

      <div class="fb-card__footer">
        <div class="fb-card__rating">
          <?php if ($rate > 0): ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg class="fb-star <?= $i <= $rate ? 'fb-star--on' : '' ?>" width="14" height="14" viewBox="0 0 20 20"><path fill="currentColor" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
            <span class="fb-card__rating-num"><?= $rate ?>/5</span>
          <?php else: ?>
            <span class="fb-card__no-rating">No rating</span>
          <?php endif; ?>
        </div>
        <?php if ($reply): ?>
        <span class="fb-card__replied">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M3 10a7 7 0 0014 0V6a7 7 0 00-14 0v4zm11 0v4a4 4 0 01-8 0v-4"/></svg>
          Replied
        </span>
        <?php endif; ?>
        <button class="fb-card__manage-btn js-fb-manage" data-id="<?= $id ?>" type="button">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Manage
        </button>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <!-- Empty filter state -->
  <div class="fb-empty" id="fb-no-results" style="display:none;">
    <svg width="48" height="48" fill="none" viewBox="0 0 24 24"><path fill="#D1D5DB" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
    <p>No results match your filters.</p>
    <button class="fb-clear-btn" id="fb-clear-filters">Clear Filters</button>
  </div>

  <!-- Manage Modal -->
  <div class="fb-modal__backdrop" id="fb-modal" hidden>
    <div class="fb-modal__panel" role="dialog" aria-modal="true" aria-labelledby="fb-modal-title">

      <div class="fb-modal__head">
        <div class="fb-modal__head-inner">
          <h3 class="fb-modal__title" id="fb-modal-title">
            Feedback <span class="fb-modal__ref" id="fb-m-ref"></span>
          </h3>
          <span class="fb-badge" id="fb-m-status-badge"></span>
        </div>
        <button class="fb-modal__close" id="fb-modal-close" type="button" aria-label="Close">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2.5" stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="fb-modal__body">

        <!-- Details -->
        <div class="fb-modal__section">
          <div class="fb-modal__section-title">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Details
          </div>
          <div class="fb-modal__details-grid">
            <div class="fb-modal__detail">
              <div class="fb-modal__detail-label">Passenger</div>
              <div class="fb-modal__detail-val" id="fb-m-passenger"></div>
            </div>
            <div class="fb-modal__detail">
              <div class="fb-modal__detail-label">Bus / Route</div>
              <div class="fb-modal__detail-val" id="fb-m-bus"></div>
            </div>
            <div class="fb-modal__detail">
              <div class="fb-modal__detail-label">Date</div>
              <div class="fb-modal__detail-val" id="fb-m-date"></div>
            </div>
            <div class="fb-modal__detail">
              <div class="fb-modal__detail-label">Type &amp; Category</div>
              <div class="fb-modal__detail-val" id="fb-m-type"></div>
            </div>
            <div class="fb-modal__detail">
              <div class="fb-modal__detail-label">Rating</div>
              <div class="fb-modal__detail-val" id="fb-m-rating"></div>
            </div>
          </div>
        </div>

        <!-- Passenger Message -->
        <div class="fb-modal__section">
          <div class="fb-modal__section-title">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Passenger Message
          </div>
          <div class="fb-modal__message" id="fb-m-message"></div>
        </div>

        <!-- Previous Reply -->
        <div class="fb-modal__section" id="fb-existing-reply-section">
          <div class="fb-modal__section-title">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M3 10a7 7 0 0014 0V6a7 7 0 00-14 0v4zm11 0v4a4 4 0 01-8 0v-4"/></svg>
            Previous Reply
          </div>
          <div class="fb-modal__reply-text" id="fb-m-reply"></div>
        </div>

        <!-- Reply Form -->
        <div class="fb-modal__section">
          <div class="fb-modal__section-title">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Send Reply
          </div>
          <form method="post" action="/B/feedback" id="fb-reply-form">
            <input type="hidden" name="complaint_id" id="fb-m-id">
            <textarea name="message" id="fb-m-message-input" class="fb-modal__textarea" rows="4" placeholder="Type your reply to the passenger"></textarea>
            <div class="fb-modal__actions">
              <button type="submit" name="action" value="reply" class="fb-btn fb-btn--reply">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Reply
              </button>
              <button type="submit" name="action" value="resolve" class="fb-btn fb-btn--resolve fb-btn--hidden" id="fb-btn-resolve">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Mark Resolved
              </button>
              <button type="submit" name="action" value="not_solved" class="fb-btn fb-btn--not-solved fb-btn--hidden" id="fb-btn-not-solved">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Mark Not Solved
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

</section>

<style>
:root {
  --fb-maroon:      #80143c;
  --fb-maroon-d:    #5e0f2c;
  --fb-gold:        #f3b944;
  --fb-not-solved:     #b45309;
  --fb-not-solved-bg:  #fffbeb;
  --fb-res:         #059669;
  --fb-res-bg:      #ecfdf5;
}

#feedbackPage {
  display:flex;
  flex-direction:column;
  gap:14px;
}

.page-header { margin: 4px 0 0; }
.page-title { margin: 0 0 6px; }
.page-subtitle { margin: 0; }

/* Alert */
.fb-alert {
  display: flex; align-items: center; gap: 10px;
  padding: 13px 18px; border-radius: 10px;
  font-weight: 500; font-size: 14px; margin: 0;
}
.fb-alert--success { background:#ecfdf5; color:#065f46; border:1px solid #6ee7b7; }
.fb-alert--error   { background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; }

/* Toolbar */
.fb-toolbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin: 0; padding:14px 18px; }
.fb-toolbar__filters { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.fb-filter-group { display:flex; flex-direction:column; gap:4px; }
.fb-filter-label { font-size:11.5px; font-weight:600; color:#6b7280; letter-spacing:.3px; padding-left:2px; }
.fb-toolbar__select {
  padding:7px 12px; border:1.5px solid var(--fb-gold); border-radius:7px;
  font-size:13px; font-family:inherit; background:#fff; cursor:pointer;
  transition:border-color .2s, box-shadow .2s; min-width:160px;
}
.fb-toolbar__select--primary { border-color:var(--fb-maroon); }
.fb-toolbar__select:focus { outline:none; border-color:var(--fb-maroon); box-shadow:0 0 0 3px rgba(128,20,60,.1); }
.fb-toolbar__search { position:relative; min-width:280px; width:100%; max-width:420px; }
.fb-toolbar__search-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none; }
.fb-toolbar__input {
  width:100%; padding:8px 12px 8px 34px;
  border:1.5px solid var(--fb-gold); border-radius:7px;
  font-size:13.5px; font-family:inherit;
  transition:border-color .2s, box-shadow .2s; box-sizing:border-box;
}
.fb-toolbar__input:focus { outline:none; border-color:var(--fb-maroon); box-shadow:0 0 0 3px rgba(128,20,60,.1); }

/* Card List */
.fb-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin:0; }
.fb-card {
  background:#fff; border-radius:10px; border:1.5px solid #e5e7eb;
  padding:14px 16px;
  transition:border-color .2s, box-shadow .2s, transform .15s;
}
.fb-card:hover { border-color:var(--fb-maroon); box-shadow:0 3px 12px rgba(128,20,60,.1); transform:translateY(-1px); }
.fb-card__top { margin-bottom:7px; }
.fb-card__ref-row { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:5px; }
.fb-card__ref { font-weight:700; font-size:14px; color:var(--fb-maroon); }

/* Badge */
.fb-badge {
  display:inline-flex; align-items:center;
  padding:3px 10px; border-radius:20px;
  font-size:11.5px; font-weight:700; letter-spacing:.3px; text-transform:uppercase;
}
.fb-badge--not-solved { background:var(--fb-not-solved-bg); color:var(--fb-not-solved); }
.fb-badge--resolved { background:var(--fb-res-bg);     color:var(--fb-res);     }

/* Chip */
.fb-chip { display:inline-flex; align-items:center; padding:2px 9px; border-radius:5px; font-size:11px; font-weight:600; }
.fb-chip--complaint  { background:#fef3c7; color:#92400e; }
.fb-chip--feedback   { background:#dbeafe; color:#1e40af; }
.fb-chip--suggestion { background:#ede9fe; color:#5b21b6; }

/* Meta */
.fb-card__meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.fb-card__meta-item { display:flex; align-items:center; gap:4px; font-size:11.5px; color:#6b7280; }
.fb-card__meta-item svg { flex-shrink:0; }

/* Message */
.fb-card__message {
  font-size:13px; color:#374151; line-height:1.5;
  margin:4px 0 8px;
  display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;
}

/* Footer */
.fb-card__footer {
  display:flex; align-items:center; gap:8px; flex-wrap:wrap;
  border-top:1px solid #f3f4f6; padding-top:10px; margin-top:6px;
}
.fb-card__rating { display:flex; align-items:center; gap:2px; flex:1; }
.fb-star      { color:#d1d5db; }
.fb-star--on  { color:#f59e0b; }
.fb-card__rating-num { font-size:11px; color:#9ca3af; margin-left:4px; }
.fb-card__no-rating  { font-size:11px; color:#d1d5db; font-style:italic; }
.fb-card__replied {
  display:inline-flex; align-items:center; gap:3px;
  font-size:11px; color:var(--fb-res); font-weight:600;
  background:var(--fb-res-bg); padding:2px 7px; border-radius:20px;
}
.fb-card__manage-btn {
  display:inline-flex; align-items:center; gap:4px;
  padding:5px 12px; background:var(--fb-maroon); color:#fff;
  border:none; border-radius:7px; font-size:12.5px; font-weight:600;
  cursor:pointer; transition:background .2s, transform .15s, box-shadow .2s;
  margin-left:auto; font-family:inherit;
}
.fb-card__manage-btn:hover { background:var(--fb-maroon-d); transform:translateY(-1px); box-shadow:0 3px 8px rgba(128,20,60,.25); }

/* Empty */
.fb-empty {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:12px; padding:60px 24px; color:#9ca3af; font-size:15px; text-align:center;
}
.fb-clear-btn {
  padding:8px 20px; background:none; border:1.5px solid #d1d5db;
  border-radius:8px; font-size:13px; font-weight:600; color:#6b7280;
  cursor:pointer; font-family:inherit; transition:all .2s;
}
.fb-clear-btn:hover { border-color:var(--fb-maroon); color:var(--fb-maroon); }

/* Modal */
.fb-modal__backdrop {
  position:fixed; inset:0; background:rgba(0,0,0,.55);
  backdrop-filter:blur(3px); z-index:12000;
  display:flex; align-items:flex-start; justify-content:center;
  padding:84px 20px 20px;
  overflow-y:auto;
  scrollbar-width:none;
  -ms-overflow-style:none;
}
.fb-modal__backdrop::-webkit-scrollbar { width:0; height:0; }
.fb-modal__backdrop[hidden] { display:none !important; }

.fb-modal__panel {
  background:#fff; border-radius:18px;
  width:min(94vw, 900px);
  max-height:calc(100vh - 104px);
  margin:0 auto;
  display:flex; flex-direction:column;
  box-shadow:0 25px 50px rgba(0,0,0,.3);
  animation:fb-modal-in .2s ease; overflow:hidden;
}
@keyframes fb-modal-in {
  from { opacity:0; transform:scale(.95) translateY(10px); }
  to   { opacity:1; transform:scale(1) translateY(0); }
}

.fb-modal__head {
  background:linear-gradient(135deg, var(--fb-maroon), var(--fb-maroon-d));
  padding:16px 22px; display:flex; align-items:center;
  justify-content:space-between; border-bottom:3px solid var(--fb-gold); flex-shrink:0;
}
.fb-modal__head-inner { display:flex; align-items:center; gap:10px; }
.fb-modal__title { margin:0; font-size:17px; font-weight:700; color:#fff; display:flex; align-items:center; gap:7px; }
.fb-modal__ref { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.3); padding:2px 9px; border-radius:5px; font-size:13px; }
.fb-modal__close {
  background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
  color:#fff; width:30px; height:30px; border-radius:7px;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:background .2s; flex-shrink:0;
}
.fb-modal__close:hover { background:rgba(255,255,255,.25); }

.fb-modal__body {
  overflow-y:auto;
  padding:22px 24px;
  flex:1;
  display:flex;
  flex-direction:column;
  gap:18px;
  scrollbar-width:none;
  -ms-overflow-style:none;
}
.fb-modal__body::-webkit-scrollbar { width:0; height:0; }
.fb-modal__section { display:flex; flex-direction:column; gap:10px; }
.fb-modal__section-title {
  display:flex; align-items:center; gap:6px;
  font-size:12px; font-weight:700; color:var(--fb-maroon);
  text-transform:uppercase; letter-spacing:.5px;
  padding-bottom:9px; border-bottom:1.5px solid #f3f4f6;
}
.fb-modal__details-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
.fb-modal__detail { background:#f9fafb; border:1px solid #f0f0f0; border-radius:10px; padding:12px 14px; }
.fb-modal__detail-label { font-size:10.5px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
.fb-modal__detail-val { font-size:13px; color:#111827; font-weight:500; }
.fb-modal__message {
  font-size:13.5px; color:#374151; line-height:1.6;
  background:#f9fafb; border:1px solid #f0f0f0;
  border-radius:10px; padding:13px 15px; white-space:pre-wrap;
}
.fb-modal__reply-text {
  font-size:13px; color:#6b7280; font-style:italic;
  background:#f9fafb; border:1px solid #f0f0f0;
  border-radius:10px; padding:13px 15px; line-height:1.6; white-space:pre-wrap;
}
.fb-modal__textarea {
  width:100%; min-height:120px; padding:12px 14px; border:1.5px solid #d1d5db;
  border-radius:8px; font-size:13.5px; font-family:inherit;
  resize:vertical; transition:border-color .2s, box-shadow .2s; box-sizing:border-box;
}
.fb-modal__textarea:focus { outline:none; border-color:var(--fb-maroon); box-shadow:0 0 0 3px rgba(128,20,60,.1); }
.fb-modal__actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; }

/* Buttons */
.fb-btn {
  display:inline-flex; align-items:center; gap:5px;
  padding:10px 16px; border:none; border-radius:10px;
  font-size:13.5px; font-weight:700; cursor:pointer;
  font-family:inherit; transition:background .2s, transform .15s, box-shadow .2s;
}
.fb-btn:hover { transform:translateY(-1px); }
.fb-btn--reply   { background:var(--fb-maroon); color:#fff; flex:1; }
.fb-btn--reply:hover   { background:var(--fb-maroon-d); box-shadow:0 4px 12px rgba(128,20,60,.3); }
.fb-btn--resolve { background:var(--fb-res-bg); color:var(--fb-res); border:1.5px solid #a7f3d0; }
.fb-btn--resolve:hover { background:#d1fae5; }
.fb-btn--not-solved { background:var(--fb-not-solved-bg); color:var(--fb-not-solved); border:1.5px solid #fcd34d; }
.fb-btn--not-solved:hover { background:#fef3c7; }
.fb-btn--hidden { display:none !important; }

/* Responsive */
@media (max-width:1024px) {
  .fb-list { grid-template-columns:1fr; }
}
@media (max-width:900px) {
  .fb-toolbar { align-items:stretch; }
  .fb-toolbar__search { max-width:none; }
}
@media (max-width:640px) {
  .fb-modal__backdrop { padding:74px 10px 10px; }
  .fb-modal__panel {
    width:100%;
    max-height:calc(100vh - 84px);
    border-radius:12px;
  }
  .fb-modal__head { padding:12px 14px; }
  .fb-modal__body { padding:14px; gap:12px; }
  .fb-toolbar { flex-direction:column; align-items:stretch; }
  .fb-toolbar__filters { gap:10px; }
  .fb-filter-group { min-width:100%; }
  .fb-toolbar__search { min-width:auto; }
  .fb-modal__details-grid { grid-template-columns:1fr; }
  .fb-modal__actions { flex-direction:column; }
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

  /* Filter */
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
      var ms = !q      || card.dataset.search.includes(q);
      var mst= !status || card.dataset.status === status;
      var mt = !type   || card.dataset.type   === type;
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

  /* Modal */
  var modal       = document.getElementById('fb-modal');
  var closeBtn    = document.getElementById('fb-modal-close');
  var mRef        = document.getElementById('fb-m-ref');
  var mBadge      = document.getElementById('fb-m-status-badge');
  var mPassenger  = document.getElementById('fb-m-passenger');
  var mBus        = document.getElementById('fb-m-bus');
  var mDate       = document.getElementById('fb-m-date');
  var mType       = document.getElementById('fb-m-type');
  var mRating     = document.getElementById('fb-m-rating');
  var mMessage    = document.getElementById('fb-m-message');
  var mReply      = document.getElementById('fb-m-reply');
  var mReplySection=document.getElementById('fb-existing-reply-section');
  var mId         = document.getElementById('fb-m-id');
  var mMsgInput   = document.getElementById('fb-m-message-input');
  var mResolveBtn = document.getElementById('fb-btn-resolve');
  var mNotSolvedBtn = document.getElementById('fb-btn-not-solved');

  function isComplaintRow(row) {
    var t = (row && row.type ? String(row.type) : '').toLowerCase().trim();
    var c = (row && row.category ? String(row.category) : '').toLowerCase().trim();
    return t === 'complaint' || c === 'complaint';
  }

  function statusClass(s) {
    var sl = (s||'').toLowerCase();
    if (sl==='resolved') return 'fb-badge--resolved';
    return 'fb-badge--not-solved';
  }

  function statusLabel(s) {
    return (s || '').toLowerCase() === 'resolved' ? 'Resolved' : 'Not Solved';
  }

  function starsHTML(rate) {
    if (!rate || rate < 1) return '<span style="color:#d1d5db;font-style:italic;font-size:13px">No rating</span>';
    var html = '';
    for (var i=1; i<=5; i++) {
      html += '<svg class="fb-star'+(i<=rate?' fb-star--on':'')+'" width="14" height="14" viewBox="0 0 20 20"><path fill="currentColor" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    }
    return html + '<span style="color:#9ca3af;font-size:12px;margin-left:5px">'+rate+'/5</span>';
  }

  function openModal(row, isComplaint) {
    var ref = refForRow(row);
    if (mRef)   mRef.textContent = ref;
    if (mBadge) { mBadge.textContent = statusLabel(row.status); mBadge.className = 'fb-badge '+statusClass(row.status); }
    if (mPassenger) mPassenger.textContent = row.passenger || '';
    if (mBus)       mBus.textContent       = row.bus_or_route || '';
    if (mDate)      mDate.textContent      = row.date || '';
    if (mType)      mType.textContent      = [row.type, row.category].filter(Boolean).join('  ') || '';
    if (mRating)    mRating.innerHTML      = starsHTML(parseInt(row.rating)||0);
    if (mMessage)   mMessage.textContent   = row.message || '';
    if (mId)        mId.value              = row.id;
    if (mMsgInput)  mMsgInput.value        = '';
    var byCard = typeof isComplaint === 'boolean' ? isComplaint : false;
    var byRow = isComplaintRow(row);
    var showStatusButtons = byCard && byRow;
    if (mResolveBtn) mResolveBtn.classList.toggle('fb-btn--hidden', !showStatusButtons);
    if (mNotSolvedBtn) mNotSolvedBtn.classList.toggle('fb-btn--hidden', !showStatusButtons);
    if (mReply && mReplySection) {
      var r = (row.response||'').trim();
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
  if (modal) modal.addEventListener('click', function(e){ if(e.target===modal) closeModal(); });
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