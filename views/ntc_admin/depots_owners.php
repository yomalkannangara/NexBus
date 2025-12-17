<section class="page-hero">
  <h1>Depot & Bus company Management</h1>
  <p>Manage depot facilities and bus owner registrations</p>
</section>

<div class="tabs">
  <button class="tab active" data-tab="depots">Depots</button>
  <button class="tab" data-tab="owners">Bus companies</button>
</div>

<!-- Global Search -->
<div class="search-bar">
  <input type="search" id="globalSearch"
         placeholder="Search depots and companies (name, address, phone, manager, reg no, route)...">
  <button type="button" class="btn" id="clearSearch">Clear</button>
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
    <label>Address <input name="address"></label>
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
    <label>Contact Email (manager's email is preferred) <input name="contact_email"></label>

    <div class="form-actions">
      <button class="btn primary">Save Company</button>
      <button type="button" class="btn" id="cancelAddOwner">Cancel</button>
    </div>
  </form>
</div>

<!-- Depots -->
<section id="depots" class="tabcontent show">
  <div class="table-section">
    <div class="table-panel-head">
      <h3>Depots</h3>
    </div>
    <table class="table full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Address</th>
          <th>Buses</th>
          <th>Manager</th>
          <th>Phone</th>
          <th>Routes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($depots as $d): ?>
          <tr>
            <td class="name"><?= htmlspecialchars($d['name']) ?></td>
            <td><?= htmlspecialchars($d['address']) ?></td>
            <td><?= htmlspecialchars($d['buses'] ?? 0) ?></td>
            <td><?= htmlspecialchars($d['manager'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($d['phone']) ?></td>
            <td>
              <?php if(!empty($d['routes'])): ?>
                <?php foreach($d['routes'] as $r): ?>
                  <span class="chip"><?= htmlspecialchars($r) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="chip">None</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a class="icon-btn warn" title="Edit">✎</a>
              <a class="icon-btn danger" title="Delete">🗑</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Owners -->
<section id="owners" class="tabcontent">
  <div class="table-section">
    <div class="table-panel-head">
      <h3>Bus companies</h3>
    </div>
    <table class="table full">
      <thead>
        <tr>
          <th>Company</th>
          <th>Reg No</th>
          <th>Contact Phone</th>
          <th>Fleet Size</th>
          <th>Owner</th>
          <th>Routes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($owners as $o): ?>
          <tr>
            <td class="name"><?= htmlspecialchars($o['name']) ?></td>
            <td><?= htmlspecialchars($o['reg_no']) ?></td>
            <td><?= htmlspecialchars($o['contact_phone']) ?></td>
            <td><?= htmlspecialchars($o['fleet_size'] ?? 0) ?></td>
            <td><?= htmlspecialchars($o['owner_name'] ?? 'N/A') ?></td>
            <td>
              <?php if(!empty($o['routes'])): ?>
                <?php foreach($o['routes'] as $r): ?>
                  <span class="chip"><?= htmlspecialchars($r) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="chip">None</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a class="icon-btn warn" title="Edit">✎</a>
              <a class="icon-btn danger" title="Delete">🗑</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
