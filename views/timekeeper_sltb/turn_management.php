
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
        <?php foreach (($rows ?? []) as $r): $delay = (int)($r['delay_min'] ?? 0); ?>
          <tr class="row" data-trip-id="<?= (int)$r['sltb_trip_id'] ?>">
            <td class="mono"><?= (int)$r['turn_no'] ?></td>
            <td>
              <div class="route">
                <div class="route-no"><?= htmlspecialchars($r['route_no']) ?></div>
                <div class="route-name"><?= htmlspecialchars($r['route_name']) ?></div>
              </div>
            </td>
            <td class="mono"><?= htmlspecialchars($r['bus_reg_no']) ?></td>
            <td><span class="badge green">Running</span></td>
            <td class="<?= $delay>0?'text-red':'' ?>">
              <?= $delay>0 ? "Started {$delay} min late" : "On time" ?>
            </td>
            <td><button class="btn btn-complete" data-action="complete">Close</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

