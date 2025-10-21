<div class="page-title title-banner">
  <h1>My Profile — Private Timekeeper</h1>
  <p>Update your account details or change your password.</p>
</div>

<?php if (!empty($msg)): ?>
  <div class="notice">Status: <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="cards" style="margin-top:12px;">
  <div class="card">
    <h3>Account Details</h3>
    <form method="post">
      <input type="hidden" name="action" value="update_profile">
      <div class="grid-3">
        <label>
          <div class="small">Full name</div>
          <input type="text" name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>">
        </label>
        <label>
          <div class="small">Email</div>
          <input type="text" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>">
        </label>
        <label>
          <div class="small">Phone</div>
          <input type="text" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
        </label>
      </div>
      <div class="mt-3">
        <button class="button">Save changes</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Change Password</h3>
    <form method="post">
      <input type="hidden" name="action" value="change_password">
      <div class="grid-3">
        <label>
          <div class="small">Current password</div>
          <input type="password" name="current_password">
        </label>
        <label>
          <div class="small">New password</div>
          <input type="password" name="new_password">
        </label>
        <label>
          <div class="small">Confirm new password</div>
          <input type="password" name="confirm_password">
        </label>
      </div>
      <div class="mt-3">
        <button class="button">Update password</button>
      </div>
    </form>
  </div>
</div>
