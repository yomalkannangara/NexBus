<?php
// NO ICONS HERE.
// Data expected from controller:
//   $cards = $m->cards();    // array of KPI cards
//   $rows  = $m->list();     // recent feedback rows

$cards = is_array($cards ?? null) ? $cards : [];
$rows  = is_array($rows  ?? null) ? $rows  : [];

// Inject demo KPIs if empty OR normalize zeros to demo values
if (!$cards) {
  $cards = [
    ['value' => '27',  'label' => 'Total This Month',    'trendText' => 'vs. last month', 'trend' => '+8.0%', 'trendClass' => 'green'],
    ['value' => '6',   'label' => 'Open Complaints',     'trendText' => 'open now',       'trend' => '',      'trendClass' => 'red'],
    ['value' => '19',  'label' => 'Resolved This Month', 'trendText' => 'resolution rate','trend' => '',      'trendClass' => 'green'],
    ['value' => '4.2', 'label' => 'Average Rating',      'trendText' => 'past 30 days',   'trend' => '+0.2',  'trendClass' => 'green'],
  ];
} else {
  $defaults = [
    'Total This Month'    => '27',
    'Open Complaints'     => '6',
    'Resolved This Month' => '19',
    'Average Rating'      => '4.2',
  ];
  foreach ($cards as &$c) {
    $v = trim((string)($c['value'] ?? ''));
    if ($v === '' || $v === '0' || $v === '0.0') {
      $label = (string)($c['label'] ?? '');
      $c['value'] = $defaults[$label] ?? '1';
    }
  }
  unset($c);
}

$ids = array_values(
  array_filter(
    array_map(fn($r) => $r['id'] ?? null, $rows),
    fn($v) => $v !== null && $v !== ''
  )
);

// Determine correct action URL from current path (router uses /M/feedback for depot manager)
$uriPath  = parse_url($_SERVER['REQUEST_URI'] ?? '/M/feedback', PHP_URL_PATH) ?: '/M/feedback';
$segments = array_values(array_filter(explode('/', $uriPath)));
$moduleSeg = $segments[0] ?? 'M';
$feedbackAction = '/' . $moduleSeg . '/feedback';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<section class="section">

  <div class="title-card">
  <h1 class="title-heading">Passenger Feedback System</h1>
  <p class="title-sub">Track and manage passenger complaints and feedback</p>
</div>


  <!-- KPI cards -->
  <div class="grid grid-4 gap-4 mt-4">
    <?php if ($cards): ?>
      <?php foreach ($cards as $c): ?>
        <?php
          $val   = h($c['value']     ?? '');
          $label = h($c['label']     ?? '');
          $klass = h($c['class']     ?? '');    // text-accent for the number
          $acc   = h($c['accent']    ?? '');    // colored dot on the right
          $trend = strtolower((string)($c['trend'] ?? ''));   // 'up' | 'down' | ''
          $tText = h($c['trendText'] ?? '');
          $tSym  = $trend === 'down' ? '▼' : ($trend === 'up' ? '▲' : '•');
          $tCls  = $trend === 'down' ? 'text-red' : ($trend === 'up' ? 'text-green' : 'muted');
        ?>
        <div class="card p-16">
          <div class="flex-between">
            <div>
              <div class="value <?= $klass ?>"><?= $val ?></div>
              <p class="muted"><?= $label ?></p>
            </div>
            <div class="kpi-dot <?= $acc ?>"></div>
          </div>
          <div class="trend mt-8">
            <span class="trend-arrow <?= $tCls ?>"><?= $tSym ?></span>
            <span class="<?= $tCls ?>"><?= $tText ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-note">No KPI data available.</div>
    <?php endif; ?>
  </div>

  <!-- Quick Response (wired to controller actions) -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Quick Response</div>
    </div>
    <div class="card__body">
      <form method="post" action="<?= h($feedbackAction) ?>" class="grid grid-3 gap-4" style="align-items:start">
        <div class="stack" style="gap:10px">
          <div class="muted small">Select feedback/complaint</div>
          <div class="select">
            <select name="complaint_id" id="q_complaint_id" required>
              <option value="">Select Feedback ID</option>
              <?php foreach ($ids as $id): ?>
                <option value="<?= h((string)$id) ?>"><?= h((string)$id) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="muted small">Action note / reply (saved to complaints.reply_text)</div>
          <textarea class="textarea" name="message" id="q_message" placeholder="Type reply or resolution note..."></textarea>
          <div class="muted small">Tip: Use Reply for normal responses. Resolve will also set Resolved timestamp.</div>
        </div>

        <div class="stack" style="gap:10px">
          <div class="muted small">Selected details</div>
          <div class="pill" style="display:block" id="q_details">Select an item to preview details here.</div>
          <div class="pill" style="display:block" id="q_current_reply">Reply: —</div>
        </div>

        <div class="stack" style="gap:10px">
          <div class="muted small">Actions</div>
          <button class="btn btn-primary" type="submit" name="action" value="reply">Send Reply</button>
          <button class="btn btn-secondary" type="submit" name="action" value="resolve">Mark Resolved</button>
          <button class="btn btn-outline" type="submit" name="action" value="close">Close</button>
          <p class="muted" style="margin:0">Close keeps any existing reply.</p>
        </div>
      </form>
    </div>
  </div>

  <!-- Manage panel (populated by table selection) -->
  <div class="card mt-6" id="manageCard" style="display:none">
    <div class="card__head">
      <div class="card__title primary">Manage Feedback</div>
    </div>
    <div class="card__body">
      <div class="grid grid-3 gap-4">
        <div class="stack">
          <div class="muted small">ID</div>
          <div class="primary fw-600" id="m_id">—</div>
        </div>
        <div class="stack">
          <div class="muted small">Bus / Route</div>
          <div class="fw-600" id="m_bus">—</div>
          <div class="muted small" id="m_route">—</div>
        </div>
        <div class="stack">
          <div class="muted small">Passenger / Status</div>
          <div class="fw-600" id="m_passenger">—</div>
          <div class="muted small" id="m_status">—</div>
        </div>
      </div>

      <div class="grid grid-2 gap-4 mt-4">
        <div>
          <div class="muted small">Description</div>
          <div class="pill" style="display:block; margin-top:8px" id="m_desc">—</div>
        </div>
        <div>
          <div class="muted small">Current Reply</div>
          <div class="pill" style="display:block; margin-top:8px" id="m_reply">—</div>
        </div>
      </div>

      <div class="grid grid-3 gap-4 mt-4" style="align-items:start">
        <form method="post" action="<?= h($feedbackAction) ?>" class="stack" style="gap:10px">
          <input type="hidden" name="action" value="reply">
          <input type="hidden" name="complaint_id" id="m_reply_id" value="">
          <textarea class="textarea" name="message" id="m_reply_msg" placeholder="Type reply..." required></textarea>
          <button class="btn btn-primary">Save Reply</button>
        </form>

        <form method="post" action="<?= h($feedbackAction) ?>" class="stack" style="gap:10px">
          <input type="hidden" name="action" value="resolve">
          <input type="hidden" name="complaint_id" id="m_resolve_id" value="">
          <textarea class="textarea" name="note" id="m_resolve_note" placeholder="Optional resolution note"></textarea>
          <button class="btn btn-secondary">Resolve</button>
        </form>

        <form method="post" action="<?= h($feedbackAction) ?>" class="stack" style="gap:10px">
          <input type="hidden" name="action" value="close">
          <input type="hidden" name="complaint_id" id="m_close_id" value="">
          <p class="muted" style="margin:0">Close without changing reply.</p>
          <button class="btn btn-outline">Close</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Recent Feedback & Complaints</div>
    </div>
    <div class="table-wrap">
      <table class="table">
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($rows): ?>
          <?php foreach ($rows as $i => $r): ?>
            <?php
              $rid   = (string)($r['id']            ?? '');
              $date  = (string)($r['date']          ?? '');
              $busNo = (string)($r['busNumber']     ?? '');
              $route = (string)($r['route']         ?? '');
              $name  = (string)($r['passengerName'] ?? '');
              $type  = (string)($r['type']          ?? '');
              $cat   = (string)($r['category']      ?? '');
              $stat  = (string)($r['status']        ?? '');
              $rate  = (int)   ($r['rating']        ?? 0);

              $statusMap = [
                'Open'          => 'badge-red',
                'In Progress'   => 'badge-yellow',
                'Under Review'  => 'badge-blue',
                'Resolved'      => 'badge-green',
              ];
              $cls = $statusMap[$stat] ?? 'badge';
            ?>
            <tr class="<?= $i % 2 === 0 ? 'alt' : '' ?>">
              <td class="primary fw-600"><?= h($rid) ?></td>
              <td><?= h($date) ?></td>
              <td>
                <div class="stack">
                  <div class="fw-600"><?= h($busNo) ?></div>
                  <div class="muted small"><?= h($route) ?></div>
                </div>
              </td>
              <td><?= h($name) ?></td>
              <td>
                <?php if (strcasecmp($type, 'Complaint') === 0): ?>
                  <span class="badge badge-red-outline">Complaint</span>
                <?php else: ?>
                  <span class="badge badge-secondary-outline"><?= h($type ?: 'Feedback') ?></span>
                <?php endif; ?>
              </td>
              <td><?= h($cat) ?></td>
              <td><span class="badge <?= h($cls) ?>"><?= h($stat ?: '-') ?></span></td>
              <td>
                <div class="stars" title="<?= h((string)$rate) ?>">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?= $s <= $rate ? 'on' : '' ?>">★</span>
                  <?php endfor; ?>
                </div>
              </td>
              <td>
                <div class="actions-inline">
                  <button
                    class="btn btn-outline small js-manage"
                    type="button"
                    data-id="<?= h($rid) ?>"
                  >Manage</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" class="muted" style="text-align:center;padding:16px;">No feedback available.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
  // Minimal JS: populate Manage panel from PHP-provided rows
  const FEEDBACK_ROWS = <?= json_encode($rows, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }

  function pickRow(id) {
    id = String(id);
    return FEEDBACK_ROWS.find(r => String(r.id) === id);
  }

  function showManage(row) {
    const card = document.getElementById('manageCard');
    if (!card) return;

    document.getElementById('m_id').textContent = row?.id ?? '—';
    document.getElementById('m_bus').textContent = row?.busNumber ?? '—';
    document.getElementById('m_route').textContent = row?.route ?? '—';
    document.getElementById('m_passenger').textContent = row?.passengerName ?? '—';
    document.getElementById('m_status').textContent = row?.status ?? '—';

    document.getElementById('m_desc').innerHTML = row?.description ? esc(row.description).replace(/\n/g,'<br>') : '—';
    document.getElementById('m_reply').innerHTML = row?.reply_text ? esc(row.reply_text).replace(/\n/g,'<br>') : '—';

    document.getElementById('m_reply_id').value = row?.id ?? '';
    document.getElementById('m_resolve_id').value = row?.id ?? '';
    document.getElementById('m_close_id').value = row?.id ?? '';

    // Pre-fill resolve note with current reply (optional convenience)
    const curReply = row?.reply_text ?? '';
    const noteEl = document.getElementById('m_resolve_note');
    if (noteEl && !noteEl.value) noteEl.value = curReply;

    card.style.display = '';
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // Quick Response preview
  function showQuickPreview(row) {
    const details = document.getElementById('q_details');
    const curReply = document.getElementById('q_current_reply');
    if (!details || !curReply) return;

    const parts = [];
    parts.push(`<div><strong>#${esc(row?.id ?? '')}</strong> — ${esc(row?.status ?? '')}</div>`);
    parts.push(`<div class="muted small">${esc(row?.type ?? '')} • ${esc(row?.category ?? '')}</div>`);
    parts.push(`<div class="muted small">Bus: ${esc(row?.busNumber ?? '—')} • Route: ${esc(row?.route ?? '—')}</div>`);
    parts.push(`<div class="muted small">Passenger: ${esc(row?.passengerName ?? '—')}</div>`);
    if (row?.description) {
      parts.push(`<div style="margin-top:8px">${esc(row.description).replace(/\n/g,'<br>')}</div>`);
    }
    details.innerHTML = parts.join('');
    curReply.innerHTML = `Reply: ${row?.reply_text ? esc(row.reply_text).replace(/\n/g,'<br>') : '—'}`;
  }

  const qSel = document.getElementById('q_complaint_id');
  if (qSel) {
    qSel.addEventListener('change', () => {
      const row = pickRow(qSel.value);
      if (row) showQuickPreview(row);
    });
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-manage');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const row = pickRow(id);
    if (row) showManage(row);
  });
</script>
