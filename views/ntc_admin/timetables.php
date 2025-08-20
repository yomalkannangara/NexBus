<section class="page-hero"><h1>Timetable Management</h1><p>Manage bus schedules and timetables</p></section>
<div class="toolbar">
  <button class="btn" id="showAddTT">+ Add Schedule</button>
  <button class="btn" id="showAddRoute">+ Add Route</button>
  <button class="btn" id="showAddDepot">+ Add Depot</button>
</div>
<div id="addTTPanel" class="panel">
  <form method="post" class="form-grid"><input type="hidden" name="action" value="create">
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
              <option value="<?=htmlspecialchars($o['id'])?>"><?=htmlspecialchars($o['name'])?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label id="depotWrap" style="display:none">SLTB Depot
          <select name="sltb_depot_id" id="sltb_depot_id">
            <option value="">-- choose depot --</option>
            <?php foreach($depots as $d): ?>
              <option value="<?=htmlspecialchars($d['id'])?>"><?=htmlspecialchars($d['name'])?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </fieldset>
    <label>Route
      <select name="route_id" required><?php foreach($routes as $r): ?><option value="<?=htmlspecialchars($r['route_id'])?>"><?=htmlspecialchars($r['route_no'])?></option><?php endforeach; ?></select>
    </label>
    <label>Bus Reg No
      <select name="bus_reg_no" id="bus_reg_no" required><option value="">-- select owner/depot first --</option></select>
    </label>
    <label>Day of Week <select name="day_of_week" required><option value="0">Sun</option><option value="1">Mon</option><option value="2">Tue</option><option value="3">Wed</option><option value="4">Thu</option><option value="5">Fri</option><option value="6">Sat</option></select></label>
    <label>Departure Time <input type="time" name="departure_time" required></label>
    <label>Arrival Time <input type="time" name="arrival_time"></label>
    <label>Start Seq <input type="number" name="start_seq" min="1"></label>
    <label>End Seq <input type="number" name="end_seq" min="1"></label>
    <label>Effective From <input type="date" name="effective_from"></label>
    <label>Effective To <input type="date" name="effective_to"></label>
    <div class="form-actions"><button class="btn primary">Save</button><button type="button" class="btn" id="cancelAddTT">Cancel</button></div>
  </form>
</div>

<div id="addRoutePanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_route">

    <label>Route No <input name="route_no" required></label>
    <label>Route Name <input name="name"></label>

    <label>Active
      <select name="is_active">
        <option value="1" selected>Yes</option>
        <option value="0">No</option>
      </select>
    </label>

<label>Stops</label>
<div id="stopsContainer"></div>
<button type="button" class="btn" onclick="addStop()">+ Add Stop</button>

<!-- This will still submit stops_json to PHP backend -->
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
    stops.splice(i, 1);  // remove from array
    redrawStops();
  }

  function redrawStops() {
    const container = document.getElementById("stopsContainer");
    container.innerHTML = "";
    const oldStops = [...stops]; // copy
    stops = [];
    oldStops.forEach(s => addStop(s.stop)); // rebuild UI
    updateStops();
  }

  function updateStops() {
    const inputs = document.querySelectorAll("#stopsContainer input[type=text]");
    inputs.forEach((input, i) => {
      stops[i].seq = i + 1;
      stops[i].stop = input.value.trim();
    });

    // keep hidden textarea updated
    document.getElementById("stops_json").value = JSON.stringify(stops);
  }
</script>


    <label>Start Seq <input type="number" name="start_seq" min="1"></label>
    <label>End Seq <input type="number" name="end_seq" min="1"></label>

    <div class="form-actions">
      <button class="btn primary">Save Route</button>
      <button type="button" class="btn" id="cancelAddRoute">Cancel</button>
    </div>
  </form>
</div>


<div id="addDepotPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_depot">
    <label>Depot Name <input name="name" required></label>
    <label>City <input name="city"></label>
    <label>Phone <input name="phone"></label>
    <div class="form-actions"><button class="btn primary">Save Depot</button><button type="button" class="btn" id="cancelAddDepot">Cancel</button></div>
  </form>
</div>

<section class="table-section"><h2>Bus Schedules</h2>
<table><thead><tr><th>Route</th><th>Operator</th><th>Bus</th><th>DOW</th><th>Departure</th><th>Arrival</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
  <td><?=htmlspecialchars($r['route_no'])?></td><td><?=htmlspecialchars($r['operator_type'])?></td><td><?=htmlspecialchars($r['bus_reg_no'])?></td>
  <td><?=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$r['day_of_week']]?></td>
  <td><?=htmlspecialchars(substr($r['departure_time'],0,5))?></td><td><?=htmlspecialchars($r['arrival_time'] ? substr($r['arrival_time'],0,5) : '')?></td>
  <td><a class="btn danger" href="?module=ntc_admin&page=timetables&delete=<?=htmlspecialchars($r['timetable_id'])?>" onclick="return confirm('Delete schedule?')">Delete</a></td>
</tr><?php endforeach; ?></tbody></table></section>

<script>
window.__OWNERS__ = <?php echo json_encode($owners ?? []); ?>;
window.__DEPOTS__ = <?php echo json_encode($depots ?? []); ?>;
</script>
