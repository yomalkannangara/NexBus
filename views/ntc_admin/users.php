<section class="page-hero"><h1>User & Role Management</h1><p>Manage user accounts and permissions</p></section>
<section class="cards"><div class="card"><div class="card-title">Depot Managers</div><div class="card-value"><?= $counts['dm'] ?></div></div>
<div class="card"><div class="card-title">NTC Admins</div><div class="card-value"><?= $counts['admin'] ?></div></div>
<div class="card"><div class="card-title">Bus Owners</div><div class="card-value"><?= $counts['owner'] ?></div></div></section>
<div class="toolbar"><button class="btn" id="showAddU">+ Add User</button></div>
<div id="addUPanel" class="panel">
  <form method="post" class="form-grid narrow"><input type="hidden" name="action" value="create">
    <label>Full Name <input name="full_name" required></label>
    <label>Email <input type="email" name="email"></label>
    <label>Phone <input name="phone"></label>
    <label>Password <input type="password" name="password"></label>
    <label>Owner/Company Name <input name="org_name" placeholder="e.g., D.K. Perera Bus Service"></label>
<label>Registration No <input name="org_reg_no" placeholder="e.g., BR-012345"></label>

    <label>Role <select name="role" required>
      <option value="NTCAdmin">NTCAdmin</option><option value="DepotManager">DepotManager</option><option value="DepotOfficer">DepotOfficer</option>
      <option value="SLTBTimekeeper">SLTBTimekeeper</option><option value="PrivateTimekeeper">PrivateTimekeeper</option><option value="PrivateBusOwner">PrivateBusOwner</option>
<option value="Passenger">Passenger</option>
    </select></label>
    <label>Private Owner <select name="private_operator_id"><option value="">-- none --</option>
      <?php foreach($owners as $o): ?><option value="<?=htmlspecialchars($o['private_operator_id'])?>"><?=htmlspecialchars($o['name'])?></option><?php endforeach; ?>
    </select></label>
    <label>SLTB Depot <select name="sltb_depot_id"><option value="">-- none --</option>
      <?php foreach($depots as $d): ?><option value="<?=htmlspecialchars($d['sltb_depot_id'])?>"><?=htmlspecialchars($d['name'])?></option><?php endforeach; ?>
    </select></label>
    <div class="form-actions"><button class="btn primary">Create</button><button type="button" class="btn" id="cancelAddU">Cancel</button></div>
  </form>
</div>
<section class="table-section"><h2>Users</h2>
<table><thead><tr><th>User</th><th>Contact</th><th>Role</th><th>Linked Depot</th><th>Status</th><th>Last Login</th></tr></thead><tbody>
<?php foreach($users as $u): ?><tr>
  <td><?=htmlspecialchars($u['full_name'])?></td><td><?=htmlspecialchars($u['email'])?><br><?=htmlspecialchars($u['phone'])?></td>
  <td><?=htmlspecialchars($u['role'])?></td>
  <td>
    <?php
    $link = '';
    if ($u['role']==='PrivateBusOwner') {
      $q = db()->prepare('SELECT name FROM private_bus_owners WHERE private_operator_id = (SELECT private_operator_id FROM users WHERE user_id=?)');
      $q->execute([$u['user_id']]); $link = $q->fetchColumn();
      echo htmlspecialchars($link ?: '-');
    } elseif (in_array($u['role'], ['DepotManager','DepotOfficer','SLTBTimekeeper'])) {
      $q = db()->prepare('SELECT name FROM sltb_depots WHERE sltb_depot_id = (SELECT sltb_depot_id FROM users WHERE user_id=?)');
      $q->execute([$u['user_id']]); $link = $q->fetchColumn();
      echo htmlspecialchars($link ?: '-');
    } else { echo '-'; }
    ?>
  </td>
  <td><?=htmlspecialchars($u['status'])?></td><td><?=htmlspecialchars($u['last_login'])?></td>
</tr><?php endforeach; ?></tbody></table></section>