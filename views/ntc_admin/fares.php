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
    <!-- active flags now auto-set from non-zero prices -->
    <label>Effective From <input type="date" name="effective_from" required></label>
    <label>Effective To <input type="date" name="effective_to"></label>
    <div class="form-actions"><button class="btn primary">Save</button><button type="button" class="btn" id="cancelAddFare">Cancel</button></div>
  </form>
</div>

<!-- Edit Fare panel -->
<div id="editFarePanel" class="panel">
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="fare_id" id="edit_fare_id">
    <label>Route
      <select name="route_id" id="edit_route_id" required>
        <option value="">-- choose route --</option>
        <?php foreach($routes as $r): ?>
          <option value="<?=htmlspecialchars($r['route_id'])?>"><?=htmlspecialchars($r['route_no'].' '.($r['name']??''))?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Stage Number <input type="number" min="1" name="stage_number" id="edit_stage_number" required></label>
    <label>Super Luxury Price <input type="number" step="0.01" name="super_luxury" id="edit_super_luxury"></label>
    <label>Luxury Price <input type="number" step="0.01" name="luxury" id="edit_luxury"></label>
    <label>Semi Luxury Price <input type="number" step="0.01" name="semi_luxury" id="edit_semi_luxury"></label>
    <label>Normal Service Price <input type="number" step="0.01" name="normal_service" id="edit_normal_service"></label>
    <label>Effective From <input type="date" name="effective_from" id="edit_effective_from" required></label>
    <label>Effective To <input type="date" name="effective_to" id="edit_effective_to"></label>
    <div class="form-actions">
      <button class="btn primary">Update</button>
      <button type="button" class="btn" id="cancelEditFare">Cancel</button>
    </div>
  </form>
</div>

<section class="table-section"><h2>Fare Stages</h2>
  <?php if (empty($routeGroups)): ?>
    <p>No fare stages defined yet.</p>
  <?php else: ?>
    <div class="fare-accordion">
      <?php
        $labels = [
          'super_luxury'   => 'Super Luxury',
          'luxury'         => 'Luxury',
          'semi_luxury'    => 'Semi Luxury',
          'normal_service' => 'Normal Service',
        ];
      ?>
      <?php foreach ($routeGroups as $g): ?>
        <div class="fare-card">
          <button class="fare-head fare-toggle">
            <div class="fare-head-main">
              <div class="fare-title"><?=htmlspecialchars($g['route_no'])?> | <?=htmlspecialchars($g['name'])?></div>
              <div class="fare-meta">
                <?php foreach ($g['active_types'] as $t): ?>
                  <span class="pill"><?=htmlspecialchars($labels[$t] ?? $t)?></span>
                <?php endforeach; ?>
                <?php if (empty($g['active_types'])): ?><span class="pill">No active types</span><?php endif; ?>
              </div>
            </div>
            <span class="fare-chevron">▼</span>
          </button>
          <div class="fare-body">
            <table class="table full">
              <thead>
                <tr>
                  <th>Stage</th>
                  <?php foreach ($g['active_types'] as $t): ?>
                    <th><?=htmlspecialchars($labels[$t] ?? $t)?></th>
                  <?php endforeach; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($g['fares'] as $f): ?>
                  <tr>
                    <td><?=htmlspecialchars($f['stage_number'])?></td>
                    <?php foreach ($g['active_types'] as $t): ?>
                      <td><?=htmlspecialchars($f[$t] ?? '')?></td>
                    <?php endforeach; ?>
                    <td>
                      <a href="#"
                         class="btn fare-update-btn btn-edit-fare"
                         data-fare-id="<?=htmlspecialchars($f['fare_id'])?>"
                         data-route-id="<?=htmlspecialchars($f['route_id'])?>"
                         data-stage-number="<?=htmlspecialchars($f['stage_number'])?>"
                         data-super-luxury="<?=htmlspecialchars($f['super_luxury'])?>"
                         data-luxury="<?=htmlspecialchars($f['luxury'])?>"
                         data-semi-luxury="<?=htmlspecialchars($f['semi_luxury'])?>"
                         data-normal-service="<?=htmlspecialchars($f['normal_service'])?>"
                         data-effective-from="<?=htmlspecialchars($f['effective_from'] ?? '')?>"
                         data-effective-to="<?=htmlspecialchars($f['effective_to'] ?? '')?>">
                        Update
                      </a>
                      <a class="btn danger"
                         href="?module=ntc_admin&page=fares&delete=<?=htmlspecialchars($f['fare_id'])?>"
                         onclick="return confirm('Delete?')">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>