<?php
// Defaults
$filters = $filters ?? ['q' => '', 'active' => ''];
$msg = $msg ?? null;
$err = $err ?? null;
?>

<section class="page-hero">
  <h1>Routes</h1>
  <p>Manage bus routes and stops</p>
</section>

<style>
  /* Page-local styles for actions column (avoid affecting other pages) */
  .routes-actions { display: flex; gap: 8px; align-items: center; }
  .routes-actions .btn {
    display: inline-flex; align-items: center; justify-content: center;
    height: 36px; width: 120px; padding: 0 14px; line-height: 1; border-radius: 8px;
  }
  /* Make Edit look like the neutral button (not orange) */
  .routes-actions .btn-edit-route {
    background: #fff; color: #5a1229; border: 1px solid var(--border);
  }

  /* -------- Route form beautify (page-scoped) -------- */
  #routeForm.route-form { display: grid; gap: 14px; }
  .route-form .grid-2col {
    display: grid; gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  @media (max-width: 800px){ .route-form .grid-2col { grid-template-columns: 1fr; } }
  .route-form .field label { font-size: 12px; font-weight: 600; color: var(--maroon); margin: 0 0 6px; display:block }
  .route-form .field input,
  .route-form .field select { height: 36px; }

  /* Stops block */
  .stops-fieldset { padding: 12px; border-radius: 10px; }
  .stops-fieldset .fieldset-head {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;
  }
  .stop-add { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
  .stop-add input { height: 36px; max-width: 360px; }

  /* side-by-side stop cards (2-up; 1-up on small screens) */
  #stopsContainer {
    display: grid;
    gap: 10px;
    grid-template-columns: 1fr; /* mobile */
  }
  @media (min-width: 700px) {
    #stopsContainer { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  /* Compact stop rows within each card */
  .stop-row {
    display: grid;
    grid-template-columns: 64px 1fr 34px;
    align-items: end;
    gap: 8px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 8px;
  }
  .stop-row label { margin: 0; font-size:12px; color:#6b7280 }
  .stop-row input[type="text"],
  .stop-row input[type="number"] { height: 32px; }

  .btn-stop-del {
    display: grid; place-items: center;
    width: 32px; height: 32px; padding: 0;
    border-radius: 8px; line-height: 1;
    background: #fff; color: #b91c1c;
    border: 1px solid #f87171; cursor: pointer;
    transition: background .15s ease, transform .05s ease;
  }
  .btn-stop-del:hover { background: #fef2f2; }
  .btn-stop-del:active { transform: translateY(1px); }

  /* Actions row */
  .route-form .form-actions { display:flex; gap:10px; justify-content:flex-end; margin-top: 6px; }
</style>

<?php if ($msg): ?>
  <div class="alert success" style="margin-bottom:10px"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
  <div class="alert danger" style="margin-bottom:10px"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- ADD/EDIT ROUTE PANEL -->
<div id="addRoutePanel" class="panel" style="display:none">
  <form method="post" id="routeForm" class="route-form">
    <input type="hidden" name="action" id="route_action" value="create_route">
    <input type="hidden" name="route_id" id="route_id" value="">

    <div class="grid-2col">
      <div class="field">
        <label for="route_no">Route number</label>
        <input id="route_no" name="route_no" placeholder="e.g., 138" required>
      </div>
      <div class="field">
        <label for="route_is_active">Status</label>
        <select id="route_is_active" name="is_active">
          <option value="1" selected>Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>

    <fieldset class="stops-fieldset">
      <div class="fieldset-head">
        <legend style="margin:0;font-weight:700;color:var(--maroon)">Stops</legend>
      </div>

      <!-- Quick add -->
      <div class="stop-add">
        <input type="text" id="newStopName" placeholder="Type a stop name and press Enter or Add">
        <button type="button" class="btn" id="btnAddStopQuick">Add</button>
      </div>

      <div id="stopsContainer"></div>
    </fieldset>

    <textarea name="stops_json" id="stops_json" hidden></textarea>

    <div class="form-actions">
      <button class="btn primary" id="routeSubmitBtn">Save Route</button>
      <button type="button" class="btn" id="cancelAddRoute">Cancel</button>
    </div>

    <script>
      let stops = [];

      function addStop(stopName = "") {
        const index = stops.length + 1;
        const container = document.getElementById("stopsContainer");

        const row = document.createElement("div");
        row.className = "stop-row";
        row.innerHTML = `
          <label>Seq <input type="number" value="${index}" readonly></label>
          <label>Stop <input type="text" value="${stopName}" oninput="updateStops()" placeholder="Stop name"></label>
          <button type="button" class="btn-stop-del" title="Remove stop" onclick="removeStop(${index-1})">&times;</button>
        `;
        container.appendChild(row);

        stops.push({ seq: index, stop: stopName });
        updateStops();
      }

      function addStopFromInput() {
        const inp = document.getElementById('newStopName');
        const name = (inp.value || '').trim();
        if (!name) { inp.focus(); return; }
        addStop(name);
        inp.value = '';
        inp.focus();
      }

      function removeStop(i) {
        stops.splice(i, 1);
        redrawStops();
      }

      function redrawStops() {
        const container = document.getElementById("stopsContainer");
        container.innerHTML = "";
        const oldStops = [...stops];
        stops = [];
        oldStops.forEach(s => addStop(s.stop));
        updateStops();
      }

      function updateStops() {
        const inputs = document.querySelectorAll("#stopsContainer input[type=text]");
        inputs.forEach((input, i) => {
          stops[i].seq = i + 1;
          stops[i].stop = input.value.trim();
        });
        document.getElementById("stops_json").value = JSON.stringify(stops);
      }

      // Exposed helpers used by edit mode
      function resetStops() {
        const container = document.getElementById("stopsContainer");
        container.innerHTML = "";
        stops = [];
        updateStops();
      }
      function setStops(names = []) {
        resetStops();
        (names || []).forEach(n => addStop(n));
        updateStops();
      }
      window.resetStops = resetStops;
      window.setStops   = setStops;

      // Wire quick add
      document.getElementById('btnAddStopQuick')?.addEventListener('click', addStopFromInput);
      document.getElementById('newStopName')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); addStopFromInput(); }
      });
    </script>
  </form>
</div>

<section class="table-panel">
  <div class="table-panel-head">
    <h2>Routes</h2>
    <div>
      <button class="btn primary" id="showAddRoute">+ Add Route</button>
    </div>
  </div>

  <!-- FILTER BAR -->
  <form method="get" action="/A/routes" style="display:grid;grid-template-columns:2fr 1.2fr auto;gap:8px;margin-bottom:12px;align-items:flex-end">
    <div>
      <label>Route number</label>
      <input type="text" name="q_route" placeholder="e.g., 138" list="routes_suggest" value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
      <!-- typeable dropdown suggestions -->
      <datalist id="routes_suggest">
        <?php foreach (($routeOptions ?? []) as $opt): ?>
          <option value="<?= htmlspecialchars($opt['route_no']) ?>" label="<?= htmlspecialchars($opt['label'] ?? $opt['route_no']) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div>
      <label>Status</label>
      <select name="q_active">
        <option value="" <?= ($filters['active'] === '') ? 'selected' : '' ?>>All</option>
        <option value="1" <?= ($filters['active'] === '1') ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= ($filters['active'] === '0') ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn primary" type="submit">Apply</button>
      <a class="btn" href="/A/routes">Reset</a>
    </div>
  </form>

  <table class="table users" id="routesTable">
    <thead>
      <tr>
        <th class="sortable" data-key="route_no">Route</th>
        <th class="sortable" data-key="start">Start</th>
        <th class="sortable" data-key="end">End</th>
        <th class="sortable" data-key="stops_count">Stops</th>
        <th class="sortable" data-key="today_schedules">Today</th>
        <th class="sortable" data-key="buses_count">Buses</th>
        <th class="sortable" data-key="is_active">Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach(($rows ?? []) as $r): ?>
        <tr
          data-route_no="<?= htmlspecialchars($r['route_no']) ?>"
          data-start="<?= htmlspecialchars($r['start'] ?? '') ?>"
          data-end="<?= htmlspecialchars($r['end'] ?? '') ?>"
          data-stops_count="<?= (int)($r['stops_count'] ?? 0) ?>"
          data-today_schedules="<?= (int)($r['today_schedules'] ?? 0) ?>"
          data-buses_count="<?= (int)($r['buses_count'] ?? 0) ?>"
          data-is_active="<?= (int)($r['is_active'] ?? 0) ?>"
        >
          <td class="name"><?= htmlspecialchars($r['route_no']) ?></td>
          <td><?= htmlspecialchars($r['start'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['end'] ?? '') ?></td>
          <td><?= (int)($r['stops_count'] ?? 0) ?></td>
          <td>
            <button type="button"
              class="btn week-btn"
              data-route="<?= htmlspecialchars($r['route_no']) ?>"
              data-week='<?= htmlspecialchars(json_encode($r['week_counts'] ?? [])) ?>'>
              <?= (int)($r['today_schedules'] ?? 0) ?>
            </button>
          </td>
          <td><?= (int)($r['buses_count'] ?? 0) ?></td>
          <td>
            <?php if ((int)$r['is_active'] === 1): ?>
              <span class="badge success">Active</span>
            <?php else: ?>
              <span class="badge">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="routes-actions">
            <button type="button"
              class="btn btn-edit-route"
              data-route-id="<?= (int)$r['route_id'] ?>"
              data-route-no="<?= htmlspecialchars($r['route_no']) ?>"
              data-is-active="<?= (int)$r['is_active'] ?>"
              data-stops='<?= htmlspecialchars($r['stops_json'] ?? "[]", ENT_QUOTES) ?>'>
              Edit
            </button>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="route_id" value="<?= (int)$r['route_id'] ?>">
              <input type="hidden" name="is_active" value="<?= (int)$r['is_active'] === 1 ? 0 : 1 ?>">
              <button class="btn" type="submit">
                <?= (int)$r['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
            <a class="btn danger" href="/A/routes?delete=<?= (int)$r['route_id'] ?>" onclick="return confirm('Delete this route?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows ?? [])): ?>
        <tr class="empty-row">
          <td colspan="8" style="text-align:center;color:#777;padding:12px">No routes found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php
    // Simple pager
    $pg = $pagination ?? null;
    if ($pg && ($pg['pages'] ?? 1) > 1):
      $cur  = (int)$pg['page'];
      $last = (int)$pg['pages'];
      $qs   = [];
      if (($filters['q'] ?? '') !== '')      $qs['q_route']  = $filters['q'];
      if (($filters['active'] ?? '') !== '') $qs['q_active'] = $filters['active'];
      $mk = function($p) use ($qs) {
        $qs['page'] = $p;
        return '/A/routes?' . http_build_query($qs);
      };
  ?>
    <div style="display:flex;justify-content:center;align-items:center;gap:10px;margin-top:10px">
      <a class="btn<?= $cur<=1 ? ' disabled' : '' ?>" href="<?= $cur<=1 ? 'javascript:void(0)' : $mk($cur-1) ?>"<?= $cur<=1 ? ' style="pointer-events:none;opacity:.6"' : '' ?>>Prev</a>
      <span style="font-size:13px;color:#555">Page <?= $cur ?> of <?= $last ?> · <?= (int)$pg['total'] ?> total</span>
      <a class="btn<?= $cur>=$last ? ' disabled' : '' ?>" href="<?= $cur>=$last ? 'javascript:void(0)' : $mk($cur+1) ?>"<?= $cur>=$last ? ' style="pointer-events:none;opacity:.6"' : '' ?>>Next</a>
    </div>
  <?php endif; ?>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('showAddRoute');
    const panel  = document.getElementById('addRoutePanel');
    const cancel = document.getElementById('cancelAddRoute');
    if (addBtn && panel) {
      addBtn.addEventListener('click', () => panel.style.display = '');
    }
    if (cancel && panel) {
      cancel.addEventListener('click', () => panel.style.display = 'none');
    }

    // Edit route button handler
    document.getElementById('routesTable').addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-edit-route')) {
        const btn = e.target;
        const routeId = btn.getAttribute('data-route-id');
        const routeNo = btn.getAttribute('data-route-no');
        const isActive = btn.getAttribute('data-is-active');
        const stopsJson = btn.getAttribute('data-stops');

        // Fill the form with existing data
        document.getElementById('route_id').value = routeId;
        document.getElementById('route_no').value = routeNo;
        document.getElementById('route_is_active').value = isActive;
        setStops(JSON.parse(stopsJson));

        // Change action to update
        document.getElementById('route_action').value = 'update_route';

        // Show the panel
        panel.style.display = '';
        // Smooth scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });
</script>
