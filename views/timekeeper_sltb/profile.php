

<div class="title-banner">
    <h1>My Profile</h1>
    <p><?= htmlspecialchars($me['full_name'] ?? 'My Name') ?> — <?= htmlspecialchars($me['email'] ?? 'myemail@example.com') ?></p>
</div>
<?php
/** @var array $me */
/** @var string|null $msg */

$flash = [
  'updated'       => 'Profile updated successfully.',
  'update_failed' => 'Could not update profile.',
  'pw_changed'    => 'Password changed.',
  'pw_error'      => 'Password change failed. Check current password or requirements.',
  'bad_action'    => 'Unsupported action.',
];
?>
<div class="tk" style="padding-top:12px;">
  <?php if (!empty($msg) && isset($flash[$msg])): ?>
    <div class="notice"><?= htmlspecialchars($flash[$msg]) ?></div>
  <?php endif; ?>

  <div class="cards">
    <!-- Account details -->
    <div class="card accent-gold">
      <h3 style="margin:0 0 6px;">Account</h3>
      <p class="small" style="margin:0 0 12px;color:var(--muted)">Update your name, email, and phone.</p>

      <form method="post" class="grid-3" style="grid-template-columns:1fr;gap:10px;">
        <input type="hidden" name="action" value="update_profile">

        <label>
          <div class="small">Full name</div>
          <input type="text" name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" required>
        </label>

        <label>
          <div class="small">Email</div>
          <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
        </label>

        <label>
          <div class="small">Phone</div>
          <input type="text" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
        </label>

        <div style="display:flex;gap:8px;margin-top:6px;">
          <button type="submit" class="button">Save changes</button>
          <a href="/TS/dashboard" class="button outline">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Password -->
    <div class="card accent-primary">
      <h3 style="margin:0 0 6px;">Change password</h3>
      <p class="small" style="margin:0 0 12px;color:var(--muted)">Use at least 8 characters.</p>

      <form method="post" class="grid-3" style="grid-template-columns:1fr;gap:10px;" id="pwForm">
        <input type="hidden" name="action" value="change_password">

        <label>
          <div class="small">Current password</div>
          <input type="password" name="current_password" required>
        </label>

        <label>
          <div class="small">New password</div>
          <input type="password" name="new_password" minlength="8" required>
        </label>

        <label>
          <div class="small">Confirm new password</div>
          <input type="password" name="confirm_password" minlength="8" required>
        </label>

        <div style="display:flex;gap:8px;margin-top:6px;">
          <button type="submit" class="button">Update password</button>
          <button type="button" class="button outline" id="pwShow">Show</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // toggle password visibility
  (function(){
    const btn = document.getElementById('pwShow');
    if(!btn) return;
    btn.addEventListener('click', () => {
      document.querySelectorAll('#pwForm input[type="password"], #pwForm input[type="text"]').forEach(inp => {
        inp.type = (inp.type === 'password') ? 'text' : 'password';
      });
      btn.textContent = btn.textContent === 'Show' ? 'Hide' : 'Show';
    });
  })();
</script>
