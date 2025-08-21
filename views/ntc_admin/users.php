<section class="page-hero">
  <h1>User & Role Management</h1>
  <p>Manage user accounts and permissions</p>
</section>

<section class="kpi-wrap">
  <div class="kpi-card">
    <h3>Depot Managers</h3>
    <div class="num"><?= $counts['dm'] ?></div>
  </div>
  <div class="kpi-card">
    <h3>NTC Admins</h3>
    <div class="num"><?= $counts['admin'] ?></div>
  </div>
  <div class="kpi-card">
     <h3>Bus Owners</h3>
    <div class="num"><?= $counts['owner'] ?></div>
  </div>
    <div class="kpi-card">
     <h3>Timekeepers</h3>
    <div class="num"><?= $counts['tk'] ?></div>
  </div>
</section>



<div id="addUPanel" class="panel">
  <form method="post" class="form-grid narrow">
    <input type="hidden" name="action" value="create">

    <label>Full Name
      <input name="full_name" required>
    </label>

    <label>Email
      <input type="email" name="email">
    </label>

    <label>Phone
      <input name="phone">
    </label>

    <label>Password
      <input type="password" name="password">
    </label>

    <label>Owner/Company Name
      <input name="org_name" placeholder="e.g., D.K. Perera Bus Service">
    </label>

    <label>Registration No
      <input name="org_reg_no" placeholder="e.g., BR-012345">
    </label>

    <label>Role
      <select name="role" required>
        <option value="NTCAdmin">NTCAdmin</option>
        <option value="DepotManager">DepotManager</option>
        <option value="DepotOfficer">DepotOfficer</option>
        <option value="SLTBTimekeeper">SLTBTimekeeper</option>
        <option value="PrivateTimekeeper">PrivateTimekeeper</option>
        <option value="PrivateBusOwner">PrivateBusOwner</option>
        <option value="Passenger">Passenger</option>
      </select>
    </label>

    <label>Private Owner
      <select name="private_operator_id">
        <option value="">-- none --</option>
        <?php foreach($owners as $o): ?>
          <option value="<?=htmlspecialchars($o['private_operator_id'])?>">
            <?=htmlspecialchars($o['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>SLTB Depot
      <select name="sltb_depot_id">
        <option value="">-- none --</option>
        <?php foreach($depots as $d): ?>
          <option value="<?=htmlspecialchars($d['sltb_depot_id'])?>">
            <?=htmlspecialchars($d['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="form-actions">
      <button class="btn primary">Create</button>
      <button type="button" class="btn" id="cancelAddU">Cancel</button>
    </div>
  </form>
</div>

<section class="table-panel">
  <div class="table-panel-head"><h2>Users</h2>
  <a class="btn primary" id="showAddU">+ Add User</a>
</div>
  <table class="table users">
    <thead>
      <tr>
        <th>User</th>
        <th>Contact</th>
        <th>Role</th>
        <th>Linked Depot</th>
        <th>Status</th>
        <th>Last Login</th>
        <th>Action</th>

      </tr>
    </thead>
    <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td>
          <div class="avatar"><?= strtoupper(substr(htmlspecialchars($u['full_name']), 0, 1)) ?></div>
          <div class="user-meta">
            <div class="name"><?=htmlspecialchars($u['full_name'])?></div>
          </div>
      </td>
          <td>
            <?=htmlspecialchars($u['email'])?><br>
            <?=htmlspecialchars($u['phone'])?>
          </td>
          <td><span class="badge"><?=htmlspecialchars($u['role'])?></span></td>
          <td>
            <?php
              $link = '';
              if ($u['role']==='PrivateBusOwner') {
                $q = db()->prepare('SELECT name FROM private_bus_owners WHERE private_operator_id = (SELECT private_operator_id FROM users WHERE user_id=?)');
                $q->execute([$u['user_id']]);
                $link = $q->fetchColumn();
                echo htmlspecialchars($link ?: '-');
              } elseif (in_array($u['role'], ['DepotManager','DepotOfficer','SLTBTimekeeper'])) {
                $q = db()->prepare('SELECT name FROM sltb_depots WHERE sltb_depot_id = (SELECT sltb_depot_id FROM users WHERE user_id=?)');
                $q->execute([$u['user_id']]);
                $link = $q->fetchColumn();
                echo htmlspecialchars($link ?: '-');
              } else {
                echo '-';
              }
            ?>
          </td>
          <td><span class="status <?= strtolower($u['status']) ?>"><?=htmlspecialchars($u['status'])?></span></td>
          <td><?=htmlspecialchars($u['last_login'])?></td>
                  <td>
          <a class="icon-btn warn" title="Edit">✎</a>
          <a class="icon-btn info" title="Permissions">⚙</a>
          <a class="icon-btn danger" title="Delete">🗑</a>
        </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
