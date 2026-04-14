<?php $timekeeperLocations = $timekeeperLocations ?? []; ?>

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
<section class="table-panel">
  <div class="filters">
    <form method="get" class="form-inline" action="/A/users">
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
        <button type="button" class="btn" onclick="location.href='/A/users'">Reset</button>

      </div>
    </form>
  </div>
</section>

<div id="addUPanel" class="panel">
  <form method="post" class="form-grid narrow" action="/A/users">
    <input type="hidden" name="action" value="create">

    <label>Employee ID
      <input name="employee_id" required>
    </label>

    <label>First Name
      <input name="first_name" required>
    </label>

    <label>Last Name (optional)
      <input name="last_name" >
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
      <select name="role" id="create_role" required>
        <option value="NTCAdmin">NTCAdmin</option>
        <option value="DepotManager">DepotManager</option>
        <option value="DepotOfficer">DepotOfficer</option>
        <option value="SLTBTimekeeper">SLTBTimekeeper</option>
        <option value="PrivateTimekeeper">PrivateTimekeeper</option>
        <option value="PrivateBusOwner">PrivateBusOwner</option>
        <option value="Passenger">Passenger</option>
      </select>
    </label>

    <label id="create_private_operator_wrap" style="display:none;">Private company
      <select name="private_operator_id">
        <option value="">-- none --</option>
        <?php foreach($owners as $o): ?>
          <option value="<?=htmlspecialchars($o['private_operator_id'])?>">
            <?=htmlspecialchars($o['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label id="create_sltb_depot_wrap" style="display:none;">SLTB Depot
      <select name="sltb_depot_id">
        <option value="">-- none --</option>
        <?php foreach($depots as $d): ?>
          <option value="<?=htmlspecialchars($d['sltb_depot_id'])?>">
            <?=htmlspecialchars($d['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label id="create_timekeeper_location_wrap" style="display:none;">Timekeeper Location
      <input
        name="timekeeper_location"
        id="create_timekeeper_location"
        list="timekeeper_locations_list"
        placeholder="Search and select route stop">
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

    <label>First Name
      <input name="first_name" id="edit_first_name" required>
    </label>

    <label>Last Name
      <input name="last_name" id="edit_last_name" required>
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

    <label id="edit_private_operator_wrap" style="display:none;">Private company
      <select name="private_operator_id" id="edit_private_operator_id">
        <option value="">-- none --</option>
        <?php foreach($owners as $o): ?>
          <option value="<?=htmlspecialchars($o['private_operator_id'])?>">
            <?=htmlspecialchars($o['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label id="edit_sltb_depot_wrap" style="display:none;">SLTB Depot
      <select name="sltb_depot_id" id="edit_sltb_depot_id">
        <option value="">-- none --</option>
        <?php foreach($depots as $d): ?>
          <option value="<?=htmlspecialchars($d['sltb_depot_id'])?>">
            <?=htmlspecialchars($d['name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label id="edit_timekeeper_location_wrap" style="display:none;">Timekeeper Location
      <input
        name="timekeeper_location"
        id="edit_timekeeper_location"
        list="timekeeper_locations_list"
        placeholder="Search and select route stop">
    </label>

    <div class="form-actions">
      <button class="btn primary">Update</button>
      <button type="button" class="btn" id="cancelEditU">Cancel</button>
    </div>
  </form>
</div>

<datalist id="timekeeper_locations_list">
  <?php foreach ($timekeeperLocations as $loc): ?>
    <option value="<?= htmlspecialchars((string)$loc) ?>"></option>
  <?php endforeach; ?>
</datalist>

<section class="table-panel">
  <div class="table-panel-head"><h2>Users</h2>
    <a class="btn primary" id="showAddU">+ Add User</a>
  </div>
  <table class="table users">
    <thead>
      <tr>
        <th>Name</th>
        <th>Contact</th>
        <th>Role</th>
        <th>Linked Depot/private bus owner/location</th>
        <th>Status</th>
        <th>Last Login</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($users as $u): ?>
        <?php $displayName = trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')); ?>
        <tr>
          <td>
          <div class="user-meta">
            <div class="name"><?= htmlspecialchars($displayName) ?></div>
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
              } elseif (in_array($u['role'], ['DepotManager','DepotOfficer'])) {
                $q = $GLOBALS['db']->prepare('SELECT name FROM sltb_depots WHERE sltb_depot_id = (SELECT sltb_depot_id FROM users WHERE user_id=?)');
                $q->execute([$u['user_id']]);
                $link = $q->fetchColumn();
                echo htmlspecialchars($link ?: '-');
              } elseif (in_array($u['role'], ['SLTBTimekeeper','PrivateTimekeeper'], true)) {
                echo htmlspecialchars((string)($u['timekeeper_location'] ?: 'Common'));
              } else {
                echo '-';
              }
            ?>
          </td>
          <td>
            <form method="post" class="inline-form inline" style="display:inline"
                  onsubmit="return confirm('<?= $u['status']==='Active' ? 'Suspend this user?' : 'Unsuspend this user?' ?>');">
              <input type="hidden" name="action" value="<?= $u['status']==='Active' ? 'suspend' : 'unsuspend' ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn status-box <?= strtolower($u['status']) ?>" type="submit">
                <?= htmlspecialchars($u['status']) ?>
              </button>
            </form>
          </td>
          <td class="actions">
            <a
              class="btn danger btn-edit"
              href="#"
              title="Update"
              data-user-id="<?= (int)$u['user_id'] ?>"
              data-first-name="<?= htmlspecialchars($u['first_name'] ?? '', ENT_QUOTES) ?>"
              data-last-name="<?= htmlspecialchars($u['last_name'] ?? '', ENT_QUOTES) ?>"
              data-email="<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>"
              data-phone="<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>"
              data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
              data-private-operator-id="<?= htmlspecialchars((string)($u['private_operator_id'] ?? ''), ENT_QUOTES) ?>"
              data-sltb-depot-id="<?= htmlspecialchars((string)($u['sltb_depot_id'] ?? ''), ENT_QUOTES) ?>"
              data-timekeeper-location="<?= htmlspecialchars((string)($u['timekeeper_location'] ?? ''), ENT_QUOTES) ?>"
            >Update</a>

            <form method="post" class="inline-form inline" style="display:inline"
                  onsubmit="return confirm('Delete this user? This cannot be undone.');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<script>
(function () {
  function byId(id) { return document.getElementById(id); }

  function syncRole(mode) {
    var isEdit = mode === 'edit';

    var roleEl = byId(isEdit ? 'edit_role' : 'create_role');
    if (!roleEl) return;

    var ownerWrap = byId(isEdit ? 'edit_private_operator_wrap' : 'create_private_operator_wrap');
    var depotWrap = byId(isEdit ? 'edit_sltb_depot_wrap' : 'create_sltb_depot_wrap');
    var locWrap = byId(isEdit ? 'edit_timekeeper_location_wrap' : 'create_timekeeper_location_wrap');

    var ownerSelect = isEdit
      ? byId('edit_private_operator_id')
      : document.querySelector('#addUPanel select[name="private_operator_id"]');
    var depotSelect = isEdit
      ? byId('edit_sltb_depot_id')
      : document.querySelector('#addUPanel select[name="sltb_depot_id"]');
    var locInput = byId(isEdit ? 'edit_timekeeper_location' : 'create_timekeeper_location');

    var role = roleEl.value || '';
    var isOwner = role === 'PrivateBusOwner';
    var isDepot = role === 'DepotManager' || role === 'DepotOfficer';
    var isTimekeeper = role === 'SLTBTimekeeper' || role === 'PrivateTimekeeper';

    if (ownerWrap) ownerWrap.style.display = isOwner ? 'block' : 'none';
    if (depotWrap) depotWrap.style.display = isDepot ? 'block' : 'none';
    if (locWrap) locWrap.style.display = isTimekeeper ? 'block' : 'none';

    if (ownerSelect) {
      ownerSelect.required = isOwner;
      if (!isOwner) ownerSelect.value = '';
    }
    if (depotSelect) {
      depotSelect.required = isDepot;
      if (!isDepot) depotSelect.value = '';
    }
    if (locInput) {
      locInput.required = isTimekeeper;
      if (!isTimekeeper) {
        locInput.value = '';
      } else if (!locInput.value.trim()) {
        locInput.value = 'Common';
      }
    }
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'create_role') syncRole('create');
    if (e.target && e.target.id === 'edit_role') syncRole('edit');
  });

  document.addEventListener('click', function (e) {
    if (e.target && e.target.closest('#showAddU')) {
      setTimeout(function () { syncRole('create'); }, 0);
    }
    if (e.target && e.target.closest('.btn-edit')) {
      setTimeout(function () { syncRole('edit'); }, 0);
    }
  });

  syncRole('create');
  syncRole('edit');
})();
</script>