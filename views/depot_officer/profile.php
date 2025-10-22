<?php
// views/depot_officer/profile.php
$me  = $me ?? ($_SESSION['user'] ?? []);
$msg = $msg ?? null;
$displayName = $me['full_name'] ?? ($me['name'] ?? '');
$initial = strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 1));
?>
<section class="page-hero">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="profile-avatar-lg" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
    <div>
      <h1 style="margin:0">My Profile — Depot Officer</h1>
      <div class="muted">Manage your account details and password.</div>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert <?= in_array($msg, ['updated','pw_changed']) ? 'success' : 'warn' ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>
</section>

<div class="grid-2">
  <section class="panel show">
    <div class="panel-head"><h2>Account Details</h2></div>
    <form method="post" class="form-grid narrow">
      <input type="hidden" name="action" value="update_profile">

      <label>Full Name
        <input name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? ($me['name'] ?? '')) ?>" required>
      </label>

      <label>Email
        <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
      </label>

      <label>Phone
        <input name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
      </label>

      <div class="form-actions">
        <button class="btn primary">Save Changes</button>
        <a href="/O/dashboard" class="btn">Cancel</a>
      </div>
    </form>

    <hr class="sep">

    <div class="panel-head"><h2>Change Password</h2></div>
    <form method="post" class="form-grid narrow">
      <input type="hidden" name="action" value="change_password">

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

<div class="panel" style="margin-top:16px">
  <div class="panel-head"><h2>Account</h2></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/logout" class="btn danger">⇦ Logout</a>
  </div>
</div>
