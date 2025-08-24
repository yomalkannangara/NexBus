<section class="page-hero"><h1>Fare Stage Management</h1><p>Configure bus fare stages and pricing</p></section>
<div class="toolbar"><button class="btn primary" id="showAddFare">+ Add Fare Stage</button></div>
<div id="addFarePanel" class="panel">
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="create">
    <label>Route
      <select name="route_id" required><option value="">-- choose route --</option>
        <?php foreach($routes as $r): ?><option value="<?=htmlspecialchars($r['route_id'])?>"><?=htmlspecialchars($r['route_no'].' '.($r['name']??''))?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Stage Number <input type="number" min="1" name="stage_number" required></label>
    <label>Super Luxury Price <input type="number" step="0.01" name="super_luxury"></label>
    <label>Luxury Price <input type="number" step="0.01" name="luxury"></label>
    <label>Semi Luxury Price <input type="number" step="0.01" name="semi_luxury"></label>
    <label>Normal Service Price <input type="number" step="0.01" name="normal_service"></label>
    <label class="inline"><input type="checkbox" name="is_super_luxury_active"> Super Luxury Active</label>
    <label class="inline"><input type="checkbox" name="is_luxury_active"> Luxury Active</label>
    <label class="inline"><input type="checkbox" name="is_semi_luxury_active"> Semi Luxury Active</label>
    <label class="inline"><input type="checkbox" name="is_normal_service_active" checked> Normal Service Active</label>
    <label>Effective From <input type="date" name="effective_from" required></label>
    <label>Effective To <input type="date" name="effective_to"></label>
    <div class="form-actions"><button class="btn primary">Save</button><button type="button" class="btn" id="cancelAddFare">Cancel</button></div>
  </form>
</div>
<section class="table-section"><h2>Fare Stages</h2>
<table><thead><tr><th>Route</th><th>Stage</th><th>Super Lux</th><th>Lux</th><th>Semi Lux</th><th>Normal</th><th>Active</th><th>Actions</th></tr></thead><tbody>
<?php foreach($fares as $f): ?><tr>
  <td><?=htmlspecialchars($f['route_no'])?></td><td><?=htmlspecialchars($f['stage_number'])?></td>
  <td><?=htmlspecialchars($f['super_luxury'])?></td><td><?=htmlspecialchars($f['luxury'])?></td>
  <td><?=htmlspecialchars($f['semi_luxury'])?></td><td><?=htmlspecialchars($f['normal_service'])?></td>
  <td><?= $f['is_super_luxury_active'] ? 'SL ' : '' ?><?= $f['is_luxury_active'] ? 'L ' : '' ?><?= $f['is_semi_luxury_active'] ? 'SeL ' : '' ?><?= $f['is_normal_service_active'] ? 'N' : '' ?></td>
  <td><a class="btn danger" href="?module=ntc_admin&page=fares&delete=<?=htmlspecialchars($f['fare_id'])?>" onclick="return confirm('Delete?')">Delete</a></td>
</tr><?php endforeach; ?></tbody></table></section>