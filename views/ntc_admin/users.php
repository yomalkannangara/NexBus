<section class="page-hero">
  <h1>User & Role Management</h1>
  <p>Manage user accounts and permissions</p>
</section>

<section class="kpi-wrap">
  <div class="mini-card">
        <div class="mini-num"><?= $counts['dm'] ?></div>
 <div class="mini-lable">Depot Managers</div>
  </div>
  <div class="mini-card">
        <div class="mini-num"><?= $counts['admin'] ?></div>
         <div class="mini-lable">NTC Admins</div>
  </div>
  <div class="mini-card">
         <div class="mini-num"><?= $counts['owner'] ?></div>
          <div class="mini-lable">Bus companies</div>

  </div>
  <div class="mini-card">
         <div class="mini-num"><?= $counts['tk'] ?></div>
          <div class="mini-lable">Timekeepers</div>

  </div>
</section>

<!-- Filters -->
<div class="filters">
  <form method="get" class="filter-grid" action="/A/users">
    <label>Role
      <select name="role">
        <option value="">All</option>
        <option value="NTCAdmin" <?= (!empty($filters['role']) && $filters['role']==='NTCAdmin')?'selected':'' ?>>NTCAdmin</option>
        <option value="DepotManager" <?= (!empty($filters['role']) && $filters['role']==='DepotManager')?'selected':'' ?>>DepotManager</option>
        <option value="DepotOfficer" <?= (!empty($filters['role']) && $filters['role']==='DepotOfficer')?'selected':'' ?>>DepotOfficer</option>
        <option value="SLTBTimekeeper" <?= (!empty($filters['role']) && $filters['role']==='SLTBTimekeeper')?'selected':'' ?>>SLTBTimekeeper</option>
        <option value="PrivateTimekeeper" <?= (!empty($filters['role']) && $filters['role']==='PrivateTimekeeper')?'selected':'' ?>>PrivateTimekeeper</option>
        <option value="PrivateBusOwner" <?= (!empty($filters['role']) && $filters['role']==='PrivateBusOwner')?'selected':'' ?>>PrivateBusOwner</option>
        <option value="Passenger" <?= (!empty($filters['role']) && $filters['role']==='Passenger')?'selected':'' ?>>Passenger</option>
      </select>
    </label>

    <label>Status
      <select name="status">
        <option value="">All</option>
        <option value="Active" <?= (!empty($filters['status']) && $filters['status']==='Active')?'selected':'' ?>>Active</option>
        <option value="Suspended" <?= (!empty($filters['status']) && $filters['status']==='Suspended')?'selected':'' ?>>Suspended</option>
      </select>
    </label>

    <label>Linked org (Depot / Private company)
      <select name="link">
        <option value="">Any</option>
        <option value="none" <?= (!empty($filters['link']) && $filters['link']==='none')?'selected':'' ?>>Unlinked</option>
        <optgroup label="Private companies">
          <?php foreach($owners as $o): 
            $val = 'owner:' . $o['private_operator_id'];
            $sel = (!empty($filters['link']) && $filters['link']===$val) ? 'selected' : '';
          ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="SLTB Depots">
          <?php foreach($depots as $d): 
            $val = 'depot:' . $d['sltb_depot_id'];
            $sel = (!empty($filters['link']) && $filters['link']===$val) ? 'selected' : '';
          ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      </select>
    </label>

    <div class="form-actions">
      <button class="btn primary" type="submit">Apply</button>
      <a class="btn" href="/A/users">Reset</a>
    </div>
  </form>
</div>

<div id="addUPanel" class="panel">
  <form method="post" class="form-grid narrow" action="/A/users">
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

    <label>Private company
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
<!-- EDIT USER (same UI as Add) -->
<div id="editUPanel" class="panel">
  <form method="post" class="form-grid narrow" action="/A/users" id="editUForm">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="user_id" id="edit_user_id">

    <label>Full Name
      <input name="full_name" id="edit_full_name" required>
    </label>

    <label>Email
      <input type="email" name="email" id="edit_email">
    </label>

    <label>Phone
      <input name="phone" id="edit_phone">
    </label>

    <label>New Password (leave blank to keep current)
      <input type="password" name="password" id="edit_password" placeholder="Optional">
    </label>

    <label>Role
      <select name="role" id="edit_role" required>
        <option value="NTCAdmin">NTCAdmin</option>
        <option value="DepotManager">DepotManager</option>
        <option value="DepotOfficer">DepotOfficer</option>
        <option value="SLTBTimekeeper">SLTBTimekeeper</option>
        <option value="PrivateTimekeeper">PrivateTimekeeper</option>
        <option value="PrivateBusOwner">PrivateBusOwner</option>
        <option value="Passenger">Passenger</option>
      </select>
    </label>

    <label>Private company
      <select name="private_operator_id" id="edit_private_operator_id">
        <option value="">-- none --</option>
        <?php foreach($owners as $o): ?>
          <option value="<?=htmlspecialchars($o['private_operator_id'])?>">
            <?=htmlspecialchars($o['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>SLTB Depot
      <select name="sltb_depot_id" id="edit_sltb_depot_id">
        <option value="">-- none --</option>
        <?php foreach($depots as $d): ?>
          <option value="<?=htmlspecialchars($d['sltb_depot_id'])?>">
            <?=htmlspecialchars($d['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="form-actions">
      <button class="btn primary">Update</button>
      <button type="button" class="btn" id="cancelEditU">Cancel</button>
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
        <th>Linked Depot/private bus owner</th>
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
          <td><span class="badge <?=htmlspecialchars($u['role'])?>"><?=htmlspecialchars($u['role'])?></span></td>
          <td>
            <?php
              $link = '';
              if ($u['role']==='PrivateBusOwner') {
                $q = $GLOBALS['db']->prepare('SELECT name FROM private_bus_owners WHERE private_operator_id = (SELECT private_operator_id FROM users WHERE user_id=?)');
                $q->execute([$u['user_id']]);
                $link = $q->fetchColumn();
                echo htmlspecialchars($link ?: '-');
              } elseif (in_array($u['role'], ['DepotManager','DepotOfficer','SLTBTimekeeper'])) {
                $q = $GLOBALS['db']->prepare('SELECT name FROM sltb_depots WHERE sltb_depot_id = (SELECT sltb_depot_id FROM users WHERE user_id=?)');
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
            <!-- Edit opens the modal and prefills via data-* -->
            <a
              class="icon-btn warn btn-edit"
              title="Edit"
              href="#"
              data-user-id="<?= (int)$u['user_id'] ?>"
              data-full-name="<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>"
              data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>"
              data-phone="<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>"
              data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
              data-private-operator-id="<?= htmlspecialchars((string)($u['private_operator_id'] ?? ''), ENT_QUOTES) ?>"
              data-sltb-depot-id="<?= htmlspecialchars((string)($u['sltb_depot_id'] ?? ''), ENT_QUOTES) ?>"
            >✎</a>

            <!-- Suspend/Unsuspend posts to controller -->
            <form method="post" class="inline-form" style="display:inline" onsubmit="return confirm('<?= $u['status']==='Active' ? 'Suspend this user?' : 'Unsuspend this user?' ?>');">
              <input type="hidden" name="action" value="<?= $u['status']==='Active' ? 'suspend' : 'unsuspend' ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="icon-btn info" title="<?= $u['status']==='Active' ? 'Suspend' : 'Unsuspend' ?>" type="submit">
                <?= $u['status']==='Active' ? '⏸' : '▶' ?>
              </button>
            </form>

            <!-- Delete posts to controller -->
            <form method="post" class="inline-form" style="display:inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="icon-btn danger" title="Delete" type="submit">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>