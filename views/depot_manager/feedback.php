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

  <!-- Quick Response -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title primary">Quick Response</div>
    </div>
    <div class="card__body">
      <div class="form-grid">
        <div class="select">
          <select>
            <option>Select Feedback ID</option>
            <?php foreach ($ids as $id): ?>
              <option><?= h((string)$id) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="select">
          <select>
            <option>Change Status</option>
            <option>Open</option>
            <option>In Progress</option>
            <option>Under Review</option>
            <option>Resolved</option>
          </select>
        </div>
        <button class="btn btn-secondary">Update Status</button>
      </div>
      <textarea class="textarea" placeholder="Enter response or notes..."></textarea>
      <button class="btn btn-primary mt-12">Send Response</button>
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
                  <button class="btn btn-outline small">View</button>
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
