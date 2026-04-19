<?php $S=$S??[]; ?>
<div class="title-banner">
    <h1>Turn Management</h1>
    <p><?= htmlspecialchars($S['depot_name'] ?? 'My Depot') ?> — National Transport Commission</p>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tk-table" id="turnTable">
      <thead>
        <tr>
          <th>Turn #</th>
          <th>Route</th>
          <th>Bus</th>
          <th>Status</th>
          <th>Delay</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows ?? [])): ?>
        <tr><td colspan="6" style="text-align:center;padding:28px;color:#9ca3af;">No trips currently running at this depot.</td></tr>
      <?php else: ?>
      <?php foreach (($rows ?? []) as $r): $delay = (int)($r['delay_min'] ?? 0); $tripStatus = $r['trip_status'] ?? 'InProgress'; ?>
        <tr class="row" data-trip-id="<?= (int)$r['sltb_trip_id'] ?>">
          <td class="mono" data-label="Turn #"><?= (int)$r['turn_no'] ?></td>
          <td data-label="Route">
            <div class="route">
              <div class="route-no"><?= htmlspecialchars($r['route_no']) ?></div>
              <div class="route-name"><?= htmlspecialchars($r['route_name']) ?></div>
            </div>
          </td>
          <td class="mono" data-label="Bus">
            <a class="tk-map-link" href="/TS/dashboard?focus_bus=<?= urlencode((string)($r['bus_reg_no'] ?? '')) ?>">
              <?= htmlspecialchars($r['bus_reg_no']) ?>
            </a>
          </td>
          <td data-label="Status">
            <?php if ($tripStatus === 'Delayed' || $delay > 0): ?>
              <span class="badge orange">Delayed</span>
            <?php else: ?>
              <span class="badge green">Running</span>
            <?php endif; ?>
          </td>
          <td class="<?= $delay>0?'text-red':'' ?>" data-label="Delay">
            <?= $delay>0 ? "Started {$delay} min late" : "On time" ?>
          </td>
          <td data-label="Action">
            <button class="btn btn-complete" data-action="complete" data-trip-id="<?= (int)$r['sltb_trip_id'] ?>">&#10003; Close</button>
            <button class="btn btn-cancel"   data-action="cancel"   data-trip-id="<?= (int)$r['sltb_trip_id'] ?>">&#215; Stop Trip</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Cancel reason modal -->
<div id="tm-cancel-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:14px;padding:28px 26px;width:100%;max-width:440px;box-shadow:0 12px 40px rgba(0,0,0,.2);">
    <h3 style="margin:0 0 16px;color:#80143c;">Stop Trip — Reason Required</h3>
    <textarea id="tm-cancel-reason" rows="4" placeholder="Describe why this trip is being stopped…"
      style="width:100%;box-sizing:border-box;border:1.5px solid #d1d5db;border-radius:8px;padding:10px;font-size:14px;resize:vertical;"></textarea>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
      <button id="tm-cancel-abort" class="btn" style="background:#f3f4f6;color:#374151;border:none;">Back</button>
      <button id="tm-cancel-confirm" class="btn" style="background:#dc2626;color:#fff;border:none;">Confirm Stop</button>
    </div>
  </div>
</div>

<style>
  .tk-map-link { color:var(--maroon);font-weight:700;text-decoration:underline;text-underline-offset:2px; }
  .tk-map-link:hover { color:color-mix(in srgb,var(--maroon) 85%,black); }
  .badge.orange { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa; }
</style>

<script>
(function () {
  var ENDPOINT = '/TS/turn_management';
  var CSRF_TOKEN = '<?= htmlspecialchars($csrfToken ?? '') ?>';
  var pendingTripId = 0;
  var overlay   = document.getElementById('tm-cancel-overlay');
  var reasonEl  = document.getElementById('tm-cancel-reason');
  var abortBtn  = document.getElementById('tm-cancel-abort');
  var confirmBtn= document.getElementById('tm-cancel-confirm');

  function showToast(msg, type) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;z-index:99999;';
    t.style.background = type === 'success' ? '#16a34a' : '#dc2626';
    t.style.color = '#fff';
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 3000);
  }

  function postAction(data, onSuccess, btn) {
    var fd = new FormData();
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fd.append('csrf', CSRF_TOKEN);
    fetch(ENDPOINT, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) {
          onSuccess();
        } else {
          showToast('Error: ' + (res.msg || 'failed'), 'error');
          if (btn) { btn.disabled = false; }
        }
      })
      .catch(function () {
        showToast('Network error. Please try again.', 'error');
        if (btn) { btn.disabled = false; }
      });
  }

  document.getElementById('turnTable').addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-action]');
    if (!btn) return;
    var action = btn.dataset.action;
    var tripId = parseInt(btn.dataset.tripId || btn.closest('tr').dataset.tripId);
    if (!tripId) return;

    if (action === 'complete') {
      if (!confirm('Mark this trip as Completed (arrived at end depot)?')) return;
      btn.disabled = true;
      btn.textContent = 'Closing…';
      postAction({ action: 'complete', trip_id: tripId }, function () {
        showToast('Trip closed successfully.', 'success');
        setTimeout(function () { location.reload(); }, 900);
      }, btn);
    }

    if (action === 'cancel') {
      pendingTripId = tripId;
      reasonEl.value = '';
      overlay.style.display = 'flex';
      reasonEl.focus();
    }
  });

  abortBtn.addEventListener('click', function () {
    overlay.style.display = 'none';
    pendingTripId = 0;
  });

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) { overlay.style.display = 'none'; pendingTripId = 0; }
  });

  confirmBtn.addEventListener('click', function () {
    var reason = reasonEl.value.trim();
    if (!reason) { reasonEl.style.borderColor = '#dc2626'; reasonEl.focus(); return; }
    reasonEl.style.borderColor = '';
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Stopping…';
    var id = pendingTripId;
    postAction({ action: 'cancel', trip_id: id, reason: reason }, function () {
      overlay.style.display = 'none';
      pendingTripId = 0;
      showToast('Trip stopped and depot notified.', 'success');
      setTimeout(function () { location.reload(); }, 900);
    }, confirmBtn);
  });
})();
</script>

