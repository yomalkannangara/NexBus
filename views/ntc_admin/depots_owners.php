<section class="page-hero">
  <h1>Depot & Bus company Management</h1>
  <p>Manage depot facilities and bus owner registrations</p>
</section>

<div class="tabs">
  <button class="tab active" data-tab="depots">Depots</button>
  <button class="tab" data-tab="owners">Bus companies</button>
</div>

<!-- ONE Toolbar -->
<div id="toolbar" class="toolbar">
  <button class="btn primary" id="showAddDepot">+ Add Depot</button>
  <button class="btn primary hide" id="showAddOwner">+ Add Company</button>
</div>

<!-- Add Depot Panel -->
<div id="addDepotPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_depot">
    <label>Depot Name <input name="name" required></label>
    <label>City <input name="city"></label>
    <label>Phone <input name="phone"></label>
    <div class="form-actions">
      <button class="btn primary">Save Depot</button>
      <button type="button" class="btn" id="cancelAddDepot">Cancel</button>
    </div>
  </form>
</div>

<!-- Add Owner Panel -->
<div id="addOwnerPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create_owner">
    <label>Company Name <input name="name" required></label>
    <label>Registration No <input name="reg_no"></label>
    <label>Contact Phone <input name="contact_phone"></label>
    <label>Contact Email <input name="contact_email"></label>

    <div class="form-actions">
      <button class="btn primary">Save Company</button>
      <button type="button" class="btn" id="cancelAddOwner">Cancel</button>
    </div>
  </form>
</div>

<!-- Depots -->
<section id="depots" class="tabcontent show">
  <?php foreach($depots as $d): ?>
    <div class="depot-card">
      <h3><?= htmlspecialchars($d['name']) ?></h3>
      <div>📍 <?= htmlspecialchars($d['city']) ?></div>
      <div>🚌 <?= htmlspecialchars($d['buses'] ?? 0) ?> buses</div>
      <div>👤 <?= htmlspecialchars($d['manager'] ?? 'N/A') ?></div>
      <div>📞 <?= htmlspecialchars($d['phone']) ?></div>
      <div>Assigned Routes:
        <?php if(!empty($d['routes'])): ?>
          <?php foreach($d['routes'] as $r): ?>
            <span class="chip"><?= htmlspecialchars($r) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="chip">None</span>
        <?php endif; ?>
      </div>
      <div class="actions" style="margin-top:8px;">
        <a class="icon-btn warn">✎</a>
        <a class="icon-btn danger">🗑</a>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<!-- Owners -->
<section id="owners" class="tabcontent">
  <?php foreach($owners as $o): ?>
    <div class="depot-card">
      <h3><?= htmlspecialchars($o['name']) ?></h3>
      <div>🔖 <?= htmlspecialchars($o['reg_no']) ?></div>
      <div>📞 <?= htmlspecialchars($o['contact_phone']) ?></div>
      <div>🚌 Fleet Size: <?= htmlspecialchars($o['fleet_size'] ?? 0) ?></div>
      <div>👤 Owner: <?= htmlspecialchars($o['owner_name'] ?? 'N/A') ?></div>
      <div>Assigned Routes:
        <?php if(!empty($o['routes'])): ?>
          <?php foreach($o['routes'] as $r): ?>
            <span class="chip"><?= htmlspecialchars($r) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="chip">None</span>
        <?php endif; ?>
      </div>
      <div class="actions" style="margin-top:8px;">
        <a class="icon-btn warn">✎</a>
        <a class="icon-btn danger">🗑</a>
      </div>
    </div>
  <?php endforeach; ?>
</section>
