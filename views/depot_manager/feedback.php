<?php
use App\Support\Icons;

/* ---------- Safety & defaults (prevents "Undefined array key" notices) ---------- */
$cards = is_array($cards ?? null) ? $cards : [];
$rows  = is_array($rows  ?? null) ? $rows  : [];
$ids   = is_array($ids   ?? null) ? $ids   : array_column($rows, 'id');

foreach ($cards as &$c) {
  // normalize keys that the template expects
  $c['value']     = (string)($c['value']     ?? '');
  $c['label']     = (string)($c['label']     ?? '');
  $c['class']     = (string)($c['class']     ?? '');            // e.g., value color class
  $c['accent']    = (string)($c['accent']    ?? '');            // e.g., icon accent class
  $c['icon']      = (string)($c['icon']      ?? 'circle');      // used by Icons::svg
  $c['trend']     = (string)($c['trend']     ?? 'up');          // 'up' | 'down'
  $c['trendText'] = (string)($c['trendText'] ?? '');            // e.g., "+5% from last week"
}
unset($c);

// tiny helper
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<section class="section">
  <div class="head-between">
    <div>
      <h1 class="h1 primary">Passenger Feedback System</h1>
      <p class="muted">Track and manage passenger complaints and feedback</p>
    </div>
  </div>

  <!-- Stats cards -->
  <div class="grid grid-4 gap-4 mt-4">
    <?php foreach ($cards as $c): ?>
      <div class="card p-16">
        <div class="flex-between">
          <div>
            <div class="value <?= h($c['class']) ?>"><?= h($c['value']) ?></div>
            <p class="muted"><?= h($c['label']) ?></p>
          </div>
          <div class="icon <?= h($c['accent']) ?>">
            <?= Icons::svg($c['icon'] ?: 'circle','',32,32) ?>
          </div>
        </div>
        <div class="trend mt-8">
          <?= Icons::svg(($c['trend']==='down'?'trending-down':'trending-up'),'',16,16); ?>
          <span class="<?= $c['trend']==='down' ? 'text-red' : 'text-green' ?>"><?= h($c['trendText']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
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
          <?php foreach ($rows as $i => $r): ?>
            <?php
              // Safe reads for row fields
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
                <div class="stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?= $s <= $rate ? 'on' : '' ?>"><?= Icons::svg('star','',16,16) ?></span>
                  <?php endfor; ?>
                </div>
              </td>
              <td><button class="btn icon-only btn-outline" title="View"><?= Icons::svg('eye','',14,14) ?></button></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="9" class="muted" style="text-align:center;padding:16px;">No feedback available.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
