<?php
$fullName    = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
$initial     = strtoupper(substr($fullName ?: 'O', 0, 1));
$msg         = $msg ?? null;
$msgMap = [
  'updated'      => ['type'=>'success', 'text'=>'Profile updated successfully.'],
  'pw_changed'   => ['type'=>'success', 'text'=>'Password changed successfully.'],
  'update_failed'=> ['type'=>'error',   'text'=>'Failed to update profile. Please try again.'],
  'pw_error'     => ['type'=>'error',   'text'=>'Current password is incorrect.'],
];
$flash = $msgMap[$msg] ?? null;
?>
<section id="profilePage">


  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="prof-flash prof-flash--<?= $flash['type'] ?>">
    <?php if ($flash['type']==='success'): ?>
      <svg width="18" height="18" fill="none" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" stroke="currentColor" stroke-width="2"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <?php else: ?>
      <svg width="18" height="18" fill="none" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($flash['text']) ?>
  </div>
  <?php endif; ?>

  <!-- Hero card -->
  <div class="prof-hero">
    <div class="prof-avatar"><?= $initial ?></div>
    <div class="prof-hero-info">
      <div class="prof-hero-name"><?= htmlspecialchars($fullName ?: 'Bus Owner') ?></div>
      <div class="prof-hero-email"><?= htmlspecialchars($me['email'] ?? '') ?></div>
      <span class="prof-role-badge">Bus Owner</span>
    </div>
    <div class="prof-hero-stats">
      <div class="prof-stat">
        <div class="prof-stat-val"><?= htmlspecialchars($me['company_name'] ?? '—') ?></div>
        <div class="prof-stat-lbl">Company</div>
      </div>
      <div class="prof-stat-divider"></div>
      <div class="prof-stat">
        <div class="prof-stat-val"><?= htmlspecialchars($me['reg_no'] ?? '—') ?></div>
        <div class="prof-stat-lbl">Reg. No.</div>
      </div>
      <div class="prof-stat-divider"></div>
      <div class="prof-stat">
        <div class="prof-stat-val"><?= htmlspecialchars($me['phone'] ?? '—') ?></div>
        <div class="prof-stat-lbl">Phone</div>
      </div>
    </div>
  </div>

  <!-- Main body: two columns -->
  <div class="prof-body">

    <!-- LEFT: Personal + Password -->
    <div class="prof-col">

      <!-- Personal Information -->
      <div class="prof-card">
        <div class="prof-card-header">
          <span class="prof-card-icon prof-icon--person">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <h3 class="prof-card-title">Personal Information</h3>
        </div>
        <form method="POST" class="prof-form">
          <input type="hidden" name="action" value="update_profile">
          <div class="prof-form-grid">
            <div class="prof-field">
              <label class="prof-label">First Name</label>
              <input type="text" name="first_name" class="prof-input"
                value="<?= htmlspecialchars($me['first_name'] ?? '') ?>" required placeholder="First name">
            </div>
            <div class="prof-field">
              <label class="prof-label">Last Name</label>
              <input type="text" name="last_name" class="prof-input"
                value="<?= htmlspecialchars($me['last_name'] ?? '') ?>" placeholder="Last name">
            </div>
            <div class="prof-field">
              <label class="prof-label">Email Address</label>
              <input type="email" name="email" class="prof-input"
                value="<?= htmlspecialchars($me['email'] ?? '') ?>" required placeholder="you@example.com">
            </div>
            <div class="prof-field">
              <label class="prof-label">Phone Number</label>
              <input type="tel" name="phone" class="prof-input"
                value="<?= htmlspecialchars($me['phone'] ?? '') ?>" placeholder="+94 77 000 0000">
            </div>
          </div>
          <div class="prof-form-footer">
            <button class="prof-btn prof-btn--primary" type="submit">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- Change Password -->
      <div class="prof-card">
        <div class="prof-card-header">
          <span class="prof-card-icon prof-icon--lock">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <h3 class="prof-card-title">Change Password</h3>
        </div>
        <form method="POST" class="prof-form">
          <input type="hidden" name="action" value="change_password">
          <div class="prof-form-grid">
            <div class="prof-field prof-field--full">
              <label class="prof-label">Current Password</label>
              <input type="password" name="current_password" class="prof-input" required placeholder="Enter current password">
            </div>
            <div class="prof-field">
              <label class="prof-label">New Password</label>
              <input type="password" name="new_password" id="newPw" class="prof-input" required placeholder="New password">
            </div>
            <div class="prof-field">
              <label class="prof-label">Confirm New Password</label>
              <input type="password" name="confirm_password" id="confirmPw" class="prof-input" placeholder="Confirm new password">
            </div>
          </div>
          <div class="prof-form-footer">
            <button class="prof-btn prof-btn--primary" type="submit">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Update Password
            </button>
          </div>
        </form>
      </div>

    </div><!-- /prof-col left -->

    <!-- RIGHT: Company + Danger -->
    <div class="prof-col">

      <!-- Company Information -->
      <div class="prof-card">
        <div class="prof-card-header">
          <span class="prof-card-icon prof-icon--company">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </span>
          <h3 class="prof-card-title">Company Information</h3>
        </div>
        <form method="POST" class="prof-form">
          <input type="hidden" name="action" value="update_profile">
          <!-- repeat personal fields hidden so they stay unchanged -->
          <input type="hidden" name="first_name" value="<?= htmlspecialchars($me['first_name'] ?? '') ?>">
          <input type="hidden" name="last_name"  value="<?= htmlspecialchars($me['last_name']  ?? '') ?>">
          <input type="hidden" name="email"      value="<?= htmlspecialchars($me['email']      ?? '') ?>">
          <input type="hidden" name="phone"      value="<?= htmlspecialchars($me['phone']      ?? '') ?>">
          <div class="prof-form-grid">
            <div class="prof-field prof-field--full">
              <label class="prof-label">Company Name</label>
              <input type="text" name="company_name" class="prof-input"
                value="<?= htmlspecialchars($me['company_name'] ?? '') ?>" placeholder="Your company name">
            </div>
            <div class="prof-field">
              <label class="prof-label">Registration No.</label>
              <input type="text" name="reg_no" class="prof-input"
                value="<?= htmlspecialchars($me['reg_no'] ?? '') ?>" placeholder="NTC-XXXX">
            </div>
            <div class="prof-field">
              <label class="prof-label">Company Phone</label>
              <input type="tel" name="company_phone" class="prof-input"
                value="<?= htmlspecialchars($me['contact_phone'] ?? '') ?>" placeholder="Company contact">
            </div>
            <div class="prof-field prof-field--full">
              <label class="prof-label">Company Email</label>
              <input type="email" name="company_email" class="prof-input"
                value="<?= htmlspecialchars($me['contact_email'] ?? '') ?>" placeholder="company@example.com">
            </div>
          </div>
          <div class="prof-form-footer">
            <button class="prof-btn prof-btn--primary" type="submit">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Save Company Info
            </button>
          </div>
        </form>
      </div>

      <!-- Danger Zone -->
      <div class="prof-card prof-card--danger">
        <div class="prof-card-header">
          <span class="prof-card-icon prof-icon--danger">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
          </span>
          <h3 class="prof-card-title">Danger Zone</h3>
        </div>
        <p class="prof-danger-desc">Once you delete your account, all data will be permanently removed. This action cannot be undone.</p>
        <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will permanently delete your account.');">
          <input type="hidden" name="action" value="delete_account">
          <button class="prof-btn prof-btn--danger" type="submit">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
            Delete My Account
          </button>
        </form>
      </div>

    </div><!-- /prof-col right -->

  </div><!-- /prof-body -->

</section>

<script>
// Confirm password match
document.addEventListener('DOMContentLoaded', function(){
  const form = document.querySelector('form[action=""]');
  const np = document.getElementById('newPw');
  const cp = document.getElementById('confirmPw');
  if (np && cp) {
    cp.addEventListener('input', function(){
      cp.setCustomValidity(cp.value && cp.value !== np.value ? 'Passwords do not match' : '');
    });
  }
});
</script>
