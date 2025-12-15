<?php
// views/ntc_admin/profile.php
// Vars expected from controller: $me, $theme, $msg
$displayName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : ($me['name'] ?? '');
$initial = strtoupper(substr($displayName ?: 'U', 0, 1));
?>
<section class="page-hero">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="profile-avatar-lg" aria-hidden="true"><?= $initial ?></div>
    <div>
      <h1 style="margin:0"><?= htmlspecialchars($displayName) ?></h1>
      <div class="muted"><?= htmlspecialchars($me['email'] ?? '') ?></div>
      <div style="margin-top:4px">
        <span class="badge-role"><?= htmlspecialchars($me['role'] ?? '') ?></span>
        <?php if (!empty($me['status'])): ?>
          <span class="badge-status <?= strtolower($me['status']) ?>"><?= htmlspecialchars($me['status']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <p>Update your account details, change password, and set preferences.</p>

  <?php if (!empty($msg)): ?>
    <div class="alert <?= in_array($msg, ['updated','pw_changed','prefs_saved']) ? 'success' : 'warn' ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>
</section>

<div class="grid-2">
  <!-- LEFT COLUMN: PROFILE + PASSWORD -->
  <section class="panel show">
    <div class="panel-head"><h2>Profile</h2></div>
    <form method="post" class="form-grid narrow">
      <input type="hidden" name="action" value="update_profile">

      <label>First Name
        <input name="first_name" value="<?= htmlspecialchars($me['first_name'] ?? '') ?>" required>
      </label>

      <label>Last Name
        <input name="last_name" value="<?= htmlspecialchars($me['last_name'] ?? '') ?>" required>
      </label>

      <label>Email
        <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>">
      </label>

      <label>Phone
        <input name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
      </label>

      <div class="form-actions">
        <button class="btn primary">Save Changes</button>
        <a href="/A/dashboard" class="btn">Cancel</a>
      </div>
    </form>

    <hr class="sep">

    <div class="panel-head"><h2>Change Password</h2></div>
    <form method="post" class="form-grid narrow">
      <input type="hidden" name="action" value="update_password">

      <label>Current Password
        <input type="password" name="current_password" required>
      </label>

      <label>New Password
        <input type="password" name="new_password" required minlength="8">
      </label>

      <label>Confirm New Password
        <input type="password" name="confirm_password" required minlength="8">
      </label>

      <div class="form-actions">
        <button class="btn warn">Update Password</button>
      </div>
    </form>
  </section>

</div>

<!-- optional quick actions -->
<div class="panel" style="margin-top:16px">
  <div class="panel-head"><h2>Account</h2></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/auth/logout" class="btn danger">⇦ Logout</a>
  </div>
</div>



