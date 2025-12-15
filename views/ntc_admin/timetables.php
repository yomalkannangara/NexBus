<?php
// defaults so view doesn't break
$filters = $filters ?? ['route' => '', 'bus' => '', 'operator_type' => ''];
$pagination = $pagination ?? [
    'page'    => 1,
    'pages'   => 1,
    'total'   => is_array($rows ?? null) ? count($rows) : 0,
    'perPage' => is_array($rows ?? null) ? count($rows) : 0,
];

if (!function_exists('tt_query_url')) {
    function tt_query_url(int $page, array $filters): string {
        $params = [];
        if (!empty($filters['route']))         $params['q_route'] = $filters['route'];
        if (!empty($filters['bus']))           $params['q_bus']   = $filters['bus'];
        if (!empty($filters['operator_type'])) $params['q_op']    = $filters['operator_type'];
        if ($page > 1)                         $params['page']    = $page;
        $qs = http_build_query($params);
        return '/A/timetables' . ($qs ? ('?' . $qs) : '');
    }
}
?>

<section class="page-hero">
  <h1>Timetable Management</h1>
  <p>Manage bus schedules and timetables</p>
</section>

<section class="kpi-wrap">
  <div class="mini-card">
        <div class="mini-num"><?= $counts['depots'] ?></div>
        <div class="mini-lable">Active Depots</div>
  </div>
  <div class="mini-card">
        <div class="mini-num"><?= $counts['routes'] ?></div>
        <div class="mini-lable">Active Routes</div>
  </div>
  <div class="mini-card">
        <div class="mini-num"><?= $counts['pbus'] + $counts['sbus'] ?></div>
        <div class="mini-lable">Total Buses</div>
  </div>
  <div class="mini-card">
        <div class="mini-num"><?= $counts['powners'] ?></div>
        <div class="mini-lable">Private Bus Companies</div>
  </div>
</section>

<!-- ADD TIMETABLE PANEL -->
<div id="addTTPanel" class="panel">
  <form method="post" class="form-grid form-compact">
    <input type="hidden" name="action" value="create">

    <fieldset id="ownerDepotSection" style="grid-column:1/-1;border:1px dashed #e8d39a;border-radius:8px;padding:10px;margin-bottom:6px">
      <legend>Operator Link</legend>
      <div class="form-grid">
        <label>Operator Type
          <select name="operator_type" id="operator_type" required>
            <option value="Private">Private</option>
            <option value="SLTB">SLTB</option>
          </select>
        </label>

        <label id="ownerWrap">Private Owner
          <select name="private_operator_id" id="private_operator_id">
            <option value="">-- choose owner --</option>
            <?php foreach($owners as $o): ?>
              <option value="<?= htmlspecialchars($o['id']) ?>"><?= htmlspecialchars($o['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label id="depotWrap" style="display:none">SLTB Depot
          <select name="sltb_depot_id" id="sltb_depot_id">
            <option value="">-- choose depot --</option>
            <?php foreach($depots as $d): ?>
              <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </fieldset>

    <label>Route
      <select name="route_id" required>
        <?php foreach($routes as $r): ?>
          <option value="<?= htmlspecialchars($r['route_id']) ?>">
              <?= htmlspecialchars($r['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Bus Reg No
      <select name="bus_reg_no" id="bus_reg_no" required>
        <option value="">-- select owner/depot first --</option>
      </select>
    </label>

    <fieldset style="border:1px dashed #e0d8aa;border-radius:8px;padding:8px;margin-bottom:8px">
      <legend>Days of Week</legend>

      <div style="margin-bottom:6px;display:flex;gap:12px;flex-wrap:wrap">
        <label>
          <input type="checkbox" id="dow_all"> All days
        </label>
        <label>
          <input type="checkbox" id="dow_weekdays"> Weekdays (Mon–Fri)
        </label>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <label><input type="checkbox" class="dow-check" name="days[]" value="0"> Sun</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="1"> Mon</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="2"> Tue</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="3"> Wed</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="4"> Thu</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="5"> Fri</label>
        <label><input type="checkbox" class="dow-check" name="days[]" value="6"> Sat</label>
      </div>
    </fieldset>

    <label>Departure Time <input type="time" name="departure_time" required></label>
    <label>Arrival Time <input type="time" name="arrival_time"></label>

    <fieldset id="autoScheduleBlock" style="border:1px dashed #e0d8aa;border-radius:8px;padding:8px;margin-bottom:8px">
      <legend>Auto Schedule</legend>

      <label style="display:block;margin-bottom:6px">
        <input type="checkbox" name="auto_schedule" id="auto_schedule" value="1">
        Auto-generate multiple turns for the selected days
      </label>

      <div id="autoScheduleOptions" class="form-grid" style="display:none;gap:8px">
        <label>Wait time between trips (minutes)
          <input type="number" name="wait_minutes" min="0" value="0">
        </label>
        <label>Number of turns per day
          <input type="number" name="turns_per_day" min="1" value="1">
        </label>
      </div>

      <p style="font-size:12px;color:#666;margin-top:4px">
        Trip time is calculated from the first departure &amp; arrival time. Extra turns are added after
        <em>trip time + wait time</em> for each turn on the same bus and day.
      </p>
    </fieldset>

    <label>Effective From <input type="date" name="effective_from"></label>
    <label>Effective To <input type="date" name="effective_to"></label>

    <div class="form-actions">
      <button class="btn primary">Save</button>
      <button type="button" class="btn" id="cancelAddTT">Cancel</button>
    </div>
  </form>
</div>

<!-- ADD ROUTE PANEL -->
<div id="addRoutePanel" class="panel">
  <form method="post" class="form-grid form-compact form-inline">
    <input type="hidden" name="action" value="create_route">

    <label>Route No <input name="route_no" required></label>

    <label>Active
      <select name="is_active">
        <option value="1" selected>Yes</option>
        <option value="0">No</option>
      </select>
    </label>

    <fieldset class="stops-fieldset">
      <div class="fieldset-head">
        <legend>Stops</legend>
        <button type="button" class="btn" onclick="addStop()">+ Add Stop</button>
      </div>
      <div id="stopsContainer"></div>
    </fieldset>

    <textarea name="stops_json" id="stops_json" hidden></textarea>

    <script>
      let stops = [];

      function addStop(stopName = "") {
        const index = stops.length + 1;
        const container = document.getElementById("stopsContainer");

        const row = document.createElement("div");
        row.className = "form-grid stop-row";
        row.innerHTML = `
          <label>Seq <input type="number" value="${index}" readonly></label>
          <label>Stop <input type="text" value="${stopName}" oninput="updateStops()"></label>
          <button type="button" class="btn danger" onclick="removeStop(${index-1})">X</button>
        `;
        container.appendChild(row);

        stops.push({ seq: index, stop: stopName });
        updateStops();
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
    </script>

    <div class="form-actions">
      <button class="btn primary">Save Route</button>
      <button type="button" class="btn" id="cancelAddRoute">Cancel</button>
    </div>
  </form>
</div>

<!-- TABLE -->
<section class="table-panel">
  <div class="table-panel-head">
    <h2>Bus Schedules</h2>
    <div>
      <button class="btn primary" id="showAddTT">+ Add Schedule</button>
      <button class="btn primary" id="showAddRoute">+ Add Route</button>
    </div>
  </div>

  <!-- FILTER BAR -->
  <form method="get" action="/A/timetables"
        style="display:grid;grid-template-columns:2fr 2fr 1.2fr auto;gap:8px;margin-bottom:12px;align-items:flex-end">
    <div>
      <label>Route</label>
      <input type="text"
             name="q_route"
             list="routeOptions"
             placeholder="Type or choose route number"
             value="<?= htmlspecialchars($filters['route'] ?? '') ?>">
      <datalist id="routeOptions">
        <?php foreach($routes as $r): ?>
          <option value="<?= htmlspecialchars($r['route_no']) ?>"><?= htmlspecialchars($r['label']) ?></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div>
      <label>Bus Reg No</label>
      <input type="text"
             name="q_bus"
             list="busOptions"
             placeholder="Type or choose bus"
             value="<?= htmlspecialchars($filters['bus'] ?? '') ?>">
      <?php if (!empty($busList ?? [])): ?>
        <datalist id="busOptions">
          <?php foreach($busList as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      <?php endif; ?>
    </div>

    <div>
      <label>Operator</label>
      <select name="q_op">
        <option value="">All</option>
        <option value="Private" <?= (($filters['operator_type'] ?? '') === 'Private') ? 'selected' : '' ?>>Private</option>
        <option value="SLTB"    <?= (($filters['operator_type'] ?? '') === 'SLTB')    ? 'selected' : '' ?>>SLTB</option>
      </select>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn primary" type="submit">Apply</button>
      <a class="btn" href="/A/timetables">Reset</a>
    </div>
  </form>

  <table class="table users">
    <thead>
      <tr>
        <th>Route</th>
        <th>Operator</th>
        <th>Bus</th>
        <th>DOW</th>
        <th>Departure</th>
        <th>Arrival</th>
        <th>Actions</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td class="name"><?= htmlspecialchars($r['route_display']) ?></td>
          <td><?= htmlspecialchars($r['operator_type']) ?></td>
          <td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
          <td><?= ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$r['day_of_week']] ?></td>
          <td><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></td>
          <td><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?></td>

          <td>
            <a class="btn danger"
               href="?module=ntc_admin&page=timetables&delete=<?= htmlspecialchars($r['timetable_id']) ?>"
               onclick="return confirm('Delete schedule?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="7" style="text-align:center;color:#777;padding:12px">
            No schedules found for the selected filters.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if (($pagination['pages'] ?? 1) > 1): ?>
    <div style="margin-top:12px;display:flex;justify-content:flex-end;gap:8px;align-items:center">
      <?php if ($pagination['page'] > 1): ?>
        <a class="btn" href="<?= htmlspecialchars(tt_query_url($pagination['page'] - 1, $filters)) ?>">&laquo; Prev</a>
      <?php endif; ?>

      <span style="font-size:12px;color:#555">
        Page <?= (int)$pagination['page'] ?> of <?= (int)$pagination['pages'] ?>
        (<?= (int)$pagination['total'] ?> schedules)
      </span>

      <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a class="btn" href="<?= htmlspecialchars(tt_query_url($pagination['page'] + 1, $filters)) ?>">Next &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<script>
  window.__OWNERS__ = <?php echo json_encode($owners ?? []); ?>;
  window.__DEPOTS__ = <?php echo json_encode($depots ?? []); ?>;
</script>

<script>
  // extra JS for day-of-week tools + auto-schedule toggle
  document.addEventListener('DOMContentLoaded', function () {
    const dayCheckboxes = Array.from(document.querySelectorAll('.dow-check'));
    const allBox       = document.getElementById('dow_all');
    const weekdaysBox  = document.getElementById('dow_weekdays');
    const autoChk      = document.getElementById('auto_schedule');
    const autoOpts     = document.getElementById('autoScheduleOptions');

    if (allBox) {
      allBox.addEventListener('change', function () {
        const checked = this.checked;
        dayCheckboxes.forEach(cb => { cb.checked = checked; });
      });
    }

    if (weekdaysBox) {
      weekdaysBox.addEventListener('change', function () {
        const checked = this.checked;
        dayCheckboxes.forEach(cb => {
          const v = parseInt(cb.value, 10);
          if (v >= 1 && v <= 5) cb.checked = checked;
        });
      });
    }

    if (autoChk && autoOpts) {
      autoChk.addEventListener('change', function () {
        autoOpts.style.display = this.checked ? '' : 'none';
      });
    }
  });
</script>
