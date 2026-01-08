<?php
// defaults so view doesn't break
$filters = $filters ?? ['route' => '', 'bus' => '', 'operator_type' => '', 'dow' => ''];
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
        // include day-of-week even if it is "0"
        if (array_key_exists('dow', $filters) && $filters['dow'] !== '' && $filters['dow'] !== null) {
            $params['q_dow'] = $filters['dow'];
        }
        if ($page > 1)                         $params['page']    = $page;
        $qs = http_build_query($params);
        return '/A/timetables' . ($qs ? ('?' . $qs) : '');
    }
}

$grouped = [];
$dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
foreach (($rows ?? []) as $r) {
    $rid = $r['route_id'] ?? ($r['route_display'] ?? uniqid('r'));
    if (!isset($grouped[$rid])) {
        $grouped[$rid] = [
            'title'     => $r['route_display'] ?? '',
            'rows'      => [],
            'buses'     => [],
            'dayCounts' => array_fill(0, 7, 0),
        ];
    }
    $grouped[$rid]['rows'][] = $r;
    if (!empty($r['bus_reg_no'])) $grouped[$rid]['buses'][$r['bus_reg_no']] = true;
    $grouped[$rid]['dayCounts'][(int)$r['day_of_week']]++;
}

// route-based pagination (10 routes/page)
$routesPerPage = 10;
$routeList     = array_values($grouped); // ensure numeric indexing
$routeTotal    = count($routeList);
$routePages    = max(1, (int)ceil($routeTotal / $routesPerPage));
$routePageRaw  = (int)($_GET['page'] ?? 1);
$routePage     = min(max(1, $routePageRaw), $routePages);
$routeSlice    = array_slice($routeList, ($routePage - 1) * $routesPerPage, $routesPerPage);
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

<!-- EDIT TIMETABLE PANEL -->
<div id="editTTPanel" class="panel">
  <form method="post" class="form-grid form-compact">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="timetable_id" id="edit_tt_id">

    <fieldset style="grid-column:1/-1;border:1px dashed #e8d39a;border-radius:8px;padding:10px;margin-bottom:6px">
      <legend>Operator Link</legend>
      <div class="form-grid">
        <label>Operator Type
          <select name="operator_type" id="edit_operator_type" required>
            <option value="Private">Private</option>
            <option value="SLTB">SLTB</option>
          </select>
        </label>

        <label>Route
          <select name="route_id" id="edit_route_id" required>
            <?php foreach($routes as $r): ?>
              <option value="<?= htmlspecialchars($r['route_id']) ?>"><?= htmlspecialchars($r['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Bus Reg No
          <input type="text" name="bus_reg_no" id="edit_bus_reg_no" required>
        </label>

        <label>Day of Week
          <select name="day_of_week" id="edit_day_of_week" required>
            <option value="0">Sun</option><option value="1">Mon</option><option value="2">Tue</option>
            <option value="3">Wed</option><option value="4">Thu</option><option value="5">Fri</option>
            <option value="6">Sat</option>
          </select>
        </label>
      </div>
    </fieldset>

    <label>Departure Time <input type="time" name="departure_time" id="edit_departure_time" required></label>
    <label>Arrival Time <input type="time" name="arrival_time" id="edit_arrival_time"></label>
    <label>Effective From <input type="date" name="effective_from" id="edit_effective_from"></label>
    <label>Effective To <input type="date" name="effective_to" id="edit_effective_to"></label>

    <div class="form-actions">
      <button class="btn primary">Update</button>
      <button type="button" class="btn" id="cancelEditTT">Cancel</button>
    </div>
  </form>
</div>

<!-- TABLE -->
<section class="table-panel">
  <div class="table-panel-head">
    <h2>Bus Schedules</h2>
    <div>
      <button class="btn primary" id="showAddTT">+ Add Schedule</button>
    </div>
  </div>

  <!-- FILTER BAR -->
  <form method="get" action="/A/timetables"
        style="display:grid;grid-template-columns:2fr 2fr 1.2fr 1.2fr auto;gap:8px;margin-bottom:12px;align-items:flex-end">
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

    <div>
      <label>Day</label>
      <select name="q_dow">
        <?php
          $dowSel = (string)($filters['dow'] ?? '');
          $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        ?>
        <option value="" <?= $dowSel === '' ? 'selected' : '' ?>>All</option>
        <?php for ($i=0; $i<7; $i++): ?>
          <option value="<?= $i ?>" <?= $dowSel === (string)$i ? 'selected' : '' ?>><?= $days[$i] ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn primary" type="submit">Apply</button>
      <a class="btn" href="/A/timetables">Reset</a>
    </div>
  </form>

  <!-- replace flat table with accordion -->
  <div class="route-accordion">
    <?php foreach($routeSlice as $g): ?>
      <?php
        $busCount   = count($g['buses']);
        $daySummary = [];
        $dayGroups  = [];
        foreach ($g['rows'] as $rr) {
          $didx = (int)$rr['day_of_week'];
          $dayGroups[$didx][] = $rr;
        }
        ksort($dayGroups);
        foreach ($g['dayCounts'] as $i => $cnt) {
          if ($cnt > 0) $daySummary[] = "{$dayNames[$i]}: {$cnt}";
        }
      ?>
      <article class="route-card">
        <button class="route-head route-toggle" type="button" aria-expanded="false">
          <div class="route-head-main">
            <div class="route-title"><?= htmlspecialchars($g['title']) ?></div>
            <div class="route-meta">
              <span class="pill">Schedules: <?= count($g['rows']) ?></span>
              <span class="pill">Buses: <?= $busCount ?></span>
              <span class="pill">Per-day: <?= $daySummary ? implode(' · ', $daySummary) : 'No schedules' ?></span>
            </div>
          </div>
          <span class="route-chevron" aria-hidden="true">▾</span>
        </button>

        <div class="route-body">
          <?php foreach($dayGroups as $dIdx => $list): ?>
            <div class="day-block">
              <div class="day-title"><?= $dayNames[$dIdx] ?? '' ?> (<?= count($list) ?>)</div>
              <table class="table full condensed day-table">
                <thead>
                  <tr>
                    <th>Bus</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th style="width:110px">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($list as $r): ?>
                    <tr class="op-row <?= $r['operator_type']==='SLTB' ? 'op-sltb' : 'op-private' ?>">
                      <td><?= htmlspecialchars($r['bus_reg_no']) ?></td>
                      <td><?= htmlspecialchars(substr($r['departure_time'],0,5)) ?></td>
                      <td><?= htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '') ?></td>
                      <td>
                        <a href="#"
                           class="btn timetable-update-btn btn-edit-tt"
                           data-tt-id="<?= htmlspecialchars($r['timetable_id']) ?>"
                           data-operator-type="<?= htmlspecialchars($r['operator_type']) ?>"
                           data-route-id="<?= htmlspecialchars($r['route_id']) ?>"
                           data-bus-reg-no="<?= htmlspecialchars($r['bus_reg_no']) ?>"
                           data-day-of-week="<?= htmlspecialchars($r['day_of_week']) ?>"
                           data-departure-time="<?= htmlspecialchars($r['departure_time']) ?>"
                           data-arrival-time="<?= htmlspecialchars($r['arrival_time'] ?? '') ?>"
                           data-effective-from="<?= htmlspecialchars($r['effective_from'] ?? '') ?>"
                           data-effective-to="<?= htmlspecialchars($r['effective_to'] ?? '') ?>">
                          Update
                        </a>
                        <a class="btn danger"
                           href="?module=ntc_admin&page=timetables&delete=<?= htmlspecialchars($r['timetable_id']) ?>"
                           onclick="return confirm('Delete schedule?')">Delete</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (empty($rows)): ?>
      <div style="text-align:center;color:#777;padding:12px">
        No schedules found for the selected filters.
      </div>
    <?php endif; ?>
  </div>

  <div class="route-legend">
    Operator coding: Private schedules (blue text) and SLTB schedules (red text). Displaying up to 10 routes per page.
  </div>

  <!-- PAGINATION -->
  <?php if ($routePages > 1): ?>
    <div style="margin-top:12px;display:flex;justify-content:flex-end;gap:8px;align-items:center">
      <?php if ($routePage > 1): ?>
        <a class="btn" href="<?= htmlspecialchars(tt_query_url($routePage - 1, $filters)) ?>">&laquo; Prev</a>
      <?php endif; ?>

      <span style="font-size:12px;color:#555">
        Page <?= (int)$routePage ?> of <?= (int)$routePages ?> (<?= (int)$routeTotal ?> routes)
      </span>

      <?php if ($routePage < $routePages): ?>
        <a class="btn" href="<?= htmlspecialchars(tt_query_url($routePage + 1, $filters)) ?>">Next &raquo;</a>
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

<style>
  /* Accordion styles */
  .route-accordion {
    margin-top: 12px;
  }

  .route-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
  }

  .route-head {
    background-color: #f9f9f9;
    cursor: pointer;
    padding: 12px 16px;
    position: relative;
  }

  .route-head:hover {
    background-color: #f1f1f1;
  }

  .route-title {
    font-weight: 500;
    font-size: 16px;
  }

  .route-meta {
    margin-top: 4px;
    font-size: 14px;
    color: #666;
  }

  .pill {
    background-color: #e1f5fe;
    border-radius: 12px;
    padding: 4px 8px;
    margin-right: 8px;
    display: inline-block;
    font-size: 12px;
  }

  .route-chevron {
    position: absolute;
    right: 16px;
    top: 16px;
    font-size: 18px;
    line-height: 1;
    transition: transform 0.3s;
  }

  .route-toggle[aria-expanded="true"] .route-chevron {
    transform: rotate(-180deg);
  }

  .route-body {
    display: none;
    padding: 0 16px 16px;
  }

  .route-toggle[aria-expanded="true"] + .route-body {
    display: block;
  }

  /* Condensed table styles */
  .table.full {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
  }

  .table.full th,
  .table.full td {
    border: 1px solid #e0e0e0;
    padding: 8px 12px;
    text-align: left;
    font-size: 14px;
  }

  .table.full th {
    background-color: #f1f1f1;
    font-weight: 500;
  }

  .table.full tbody tr:hover {
    background-color: #fafafa;
  }

  /* Override some existing styles for the condensed tables */
  .table.users {
    margin-top: 0;
  }

  .table.users th,
  .table.users td {
    padding: 10px 12px;
  }

  /* New styles for operator rows */
  .op-row {
    transition: background-color 0.3s;
  }

  .op-private {
    background-color: #e8f5e9;
  }

  .op-sltb {
    background-color: #fce4ec;
  }

  /* New styles for day groups in accordion */
  .day-block {
    margin-top: 8px;
    border-top: 1px solid #e0e0e0;
    padding-top: 8px;
  }

  .day-title {
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .day-table {
    margin-top: 4px;
  }
</style>
