<?php
$acct = $account ?? [
  'full_name' => $_SESSION['user']['full_name'] ?? '',
  'email'     => $_SESSION['user']['email'] ?? '',
  'phone'     => $_SESSION['user']['phone'] ?? '',
];
$role = $_SESSION['user']['role'] ?? 'Depot Manager';
?>
<div class="title-card title-card--split">
  <div>
    <h1 class="title-heading">My Profile</h1>
    <p class="title-sub">Manage your account details and password</p>
  </div>
  <div class="title-meta">
    <div class="muted"><?= htmlspecialchars($role) ?></div>
    <div><?= date('l d F Y'); ?></div>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="toast ok">Done: <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
  <div class="toast error">Error: <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="grid grid-3 gap-6">
  <div class="metric-card accent-blue" style="grid-column: span 2;">
    <div class="fw-600">Account details</div>
    <form class="mt-12" method="post" action="/M/profile">
      <input type="hidden" name="action" value="update_details">
      <div class="form-grid">
        <div class="form-group">
          <label>Full name <span class="req">*</span></label>
          <input class="input" type="text" name="full_name" required value="<?= htmlspecialchars($acct['full_name']) ?>">
        </div>
        <div class="form-group">
          <label>Email <span class="req">*</span></label>
          <input class="input" type="email" name="email" required value="<?= htmlspecialchars($acct['email']) ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input class="input" type="text" name="phone" value="<?= htmlspecialchars($acct['phone']) ?>">
        </div>
      </div>
      <div class="actions mt-12">
        <button type="submit" class="btn btn-secondary">Save changes</button>
        <a href="/M/profile" class="btn btn-outline secondary">Cancel</a>
      </div>
    </form>
  </div>

  <div class="metric-card accent-yellow">
    <div class="fw-600">Change password</div>
    <form class="mt-12" method="post" action="/M/profile">
      <input type="hidden" name="action" value="change_password">
      <div class="form-grid" style="grid-template-columns:1fr;">
        <div class="form-group">
          <label>Current password</label>
          <input class="input" type="password" name="current_password" autocomplete="current-password">
        </div>
        <div class="form-group">
          <label>New password <span class="req">*</span></label>
          <input class="input" type="password" name="new_password" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label>Confirm new password <span class="req">*</span></label>
          <input class="input" type="password" name="confirm_password" required autocomplete="new-password">
        </div>
      </div>
      <div class="actions mt-12">
        <button type="submit" class="btn btn-primary">Update password</button>
      </div>
    </form>
  </div>
</div>
