<?php
// views/timekeeper_private/profile.php
$me  = $me ?? ($_SESSION['user'] ?? []);
$msg = $msg ?? null;
$displayName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : ($me['name'] ?? 'Private Timekeeper');
$initial = strtoupper(substr($displayName ?: 'U', 0, 1));
$profileImage = $me['profile_image'] ?? null;

$messages = [
    'updated'       => 'Profile updated successfully.',
    'update_failed' => 'Could not update profile.',
    'image_updated' => 'Profile image uploaded successfully.',
    'upload_failed' => 'Failed to upload image.',
    'image_deleted' => 'Profile image deleted successfully.',
    'delete_failed' => 'Failed to delete image.',
    'invalid_image' => 'Invalid image file. Please use JPG, PNG, or WebP.',
    'no_file'       => 'No file selected.',
    'pw_changed'    => 'Password changed successfully.',
    'pw_error'      => 'Password change failed. Check current password or requirements.',
    'bad_action'    => 'Invalid action.',
];
?>
<section class="page-hero">
  <div class="tk-profile-hero-head">
    <!-- Profile Image with Camera Overlay (WhatsApp Style) -->
    <form method="post" enctype="multipart/form-data" style="margin:0;padding:0;">
      <div class="tk-profile-image-wrap">
        <div class="profile-avatar-lg tk-profile-avatar" aria-hidden="true">
          <?php if ($profileImage): ?>
            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile">
          <?php else: ?>
            <div class="tk-profile-avatar-fallback"><?= htmlspecialchars($initial) ?></div>
          <?php endif; ?>
        </div>
        <!-- Hidden file input and action -->
        <input type="hidden" name="action" value="upload_image">
        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" style="display:none;" id="cameraInput">
        <!-- Camera Icon Overlay -->
        <button type="button" class="tk-camera-btn" title="Change profile picture" onclick="document.getElementById('cameraInput').click();">
          📷
        </button>
      </div>
    </form>
    
    <div class="tk-profile-identity">
      <h1 class="tk-profile-title">Profile Settings</h1>
      <div class="tk-profile-display-name"><?= htmlspecialchars($displayName) ?></div>
      <div class="tk-profile-email"><?= htmlspecialchars($me['email'] ?? '') ?></div>
      <div class="tk-profile-badges">
        <span class="badge-role"><?= htmlspecialchars($me['role'] ?? 'Private Timekeeper') ?></span>
        <?php if (!empty($me['status'])): ?>
          <span class="badge-status <?= strtolower($me['status']) ?>"><?= htmlspecialchars($me['status']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <p class="tk-profile-intro">Review and maintain your account information, profile photo, and password settings in one place.</p>

  <?php if (!empty($msg) && isset($messages[$msg])): ?>
    <div class="alert <?= in_array($msg, ['updated','image_updated','image_deleted','pw_changed']) ? 'success' : 'warn' ?>">
      <?= htmlspecialchars($messages[$msg]) ?>
    </div>
  <?php endif; ?>
</section>

<!-- Auto-submit image upload -->
<script>
  document.getElementById('cameraInput').addEventListener('change', function() {
    if (this.files.length > 0) {
      this.closest('form').submit();
    }
  });
</script>

<!-- AJAX profile update: submit form and refresh UI without full reload -->
<script>
  (function(){
    try {
      const actionInput = document.querySelector('form input[name="action"][value="update_profile"]');
      if (!actionInput) return;
      const form = actionInput.closest('form');
      form.addEventListener('submit', async function(e){
        e.preventDefault();
        const fd = new FormData(form);
        const opts = {
          method: 'POST',
          body: fd,
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        };
        let res;
        try {
          res = await fetch(window.location.pathname, opts);
        } catch (err) {
          form.submit();
          return;
        }
        let data;
        try { data = await res.json(); } catch (e) { data = null; }
        if (!data) { form.submit(); return; }
        document.querySelectorAll('.alert').forEach(n=>n.remove());
        if (data.ok) {
          const u = data.user || {};
          const displayName = ((u.first_name||'') + ' ' + (u.last_name||'')).trim() || u.name || 'User';
          const nameEl = document.querySelector('.tk-profile-display-name');
          if (nameEl) nameEl.textContent = displayName;
          const emailEl = document.querySelector('.page-hero .tk-profile-email');
          if (emailEl) emailEl.textContent = u.email || '';
          form.querySelector('input[name="first_name"]').value = u.first_name || '';
          form.querySelector('input[name="last_name"]').value = u.last_name || '';
          form.querySelector('input[name="email"]').value = u.email || '';
          form.querySelector('input[name="phone"]').value = u.phone || '';

          const msgDiv = document.createElement('div');
          msgDiv.className = 'alert success';
          msgDiv.textContent = 'Profile updated successfully.';
          const hero = document.querySelector('.page-hero');
          if (hero && hero.parentNode) hero.parentNode.insertBefore(msgDiv, hero.nextSibling);
        } else {
          const msgDiv = document.createElement('div');
          msgDiv.className = 'alert warn';
          msgDiv.textContent = data.msg || 'Could not update profile.';
          const hero = document.querySelector('.page-hero');
          if (hero && hero.parentNode) hero.parentNode.insertBefore(msgDiv, hero.nextSibling);
        }
      });
    } catch (e) {
      // no-op
    }
  })();
</script>

<style>
  .page-hero {
    color: #111827;
  }
  .tk-profile-hero-head {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }
  .tk-profile-image-wrap {
    position: relative;
    width: 84px;
    height: 84px;
    flex: 0 0 auto;
  }
  .tk-profile-avatar {
    width: 100%;
    height: 100%;
    border: 2px solid rgba(255,255,255,.6);
    box-shadow: 0 8px 20px rgba(0,0,0,.18);
  }
  .tk-profile-avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e0e0;
    border-radius: 50%;
    font-size: 32px;
    color: #666;
  }
  .tk-camera-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #007bff;
    border: 3px solid #fff;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    padding: 0;
    z-index: 1;
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, -50%);
    transition: opacity .3s ease, transform .3s ease, box-shadow .3s ease;
  }
  .tk-profile-image-wrap:hover .tk-camera-btn,
  .tk-profile-image-wrap:focus-within .tk-camera-btn {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, .35);
    pointer-events: auto;
  }
  .tk-profile-title {
    margin: 2px 0 4px;
    font-size: clamp(1.3rem, 1.8vw, 1.7rem);
    line-height: 1.15;
    letter-spacing: .01em;
  }
  .tk-profile-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.3);
    color: #ffe9a8;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
  }
  .tk-profile-display-name {
    margin: 4px 0 2px;
    color: #111827;
    font-size: 15px;
    font-weight: 700;
  }
  .tk-profile-email {
    color: #111827;
    font-size: 13px;
    opacity: 1;
    font-weight: 500;
  }
  .tk-profile-intro {
    margin: 12px 0 0;
    max-width: 64ch;
    color: #111827;
    font-size: 14px;
    line-height: 1.5;
  }
  .page-hero .badge-role {
    background: #f3f4f6;
    color: #111827;
    border-color: #d1d5db;
  }
  .page-hero .badge-status {
    color: #111827;
    border-color: #d1d5db;
  }
  .page-hero .badge-status.active {
    background: #dcfce7;
    color: #14532d;
    border-color: #bbf7d0;
  }
  .page-hero .badge-status.suspended {
    background: #fee2e2;
    color: #7f1d1d;
    border-color: #fecaca;
  }
  .tk-profile-badges {
    margin-top: 6px;
  }
  .tk-profile-shell {
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  }
  .tk-profile-card {
    background:#fff;
    border-radius:12px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
    overflow:hidden;
    border: 1px solid #e8e8ee;
  }
  .tk-profile-card-body {
    padding:18px;
  }
  .tk-profile-section-title {
    margin: 0;
    font-size: 1.05rem;
    color: #111827;
  }
  .tk-profile-section-head {
    margin: 0 0 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #edf0f5;
  }
  .tk-profile-section-subtitle {
    margin: 5px 0 0;
    color: #374151;
    font-size: 13px;
    line-height: 1.45;
  }
  .tk-profile-actions {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:8px;
  }
  .tk-profile-inline-form {
    margin:10px 0 0;
  }
  .tk-profile-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(220px, 1fr));
    gap: 12px 14px;
    align-items: end;
  }
  .tk-profile-field {
    display: grid;
    gap: 6px;
    font-weight: 600;
    color: #1f2937;
    font-size: 13px;
  }
  .tk-profile-field input {
    width: 100%;
    min-height: 40px;
    padding: 9px 11px;
    border: 1px solid #d7dce4;
    border-radius: 10px;
    background: #fff;
    color: #111827;
    font-size: 14px;
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .tk-profile-field input:focus-visible {
    outline: none;
    border-color: #caa33a;
    box-shadow: 0 0 0 4px rgba(228, 183, 79, .2);
  }
  .tk-profile-actions {
    grid-column: 1 / -1;
  }
  .tk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 132px;
    min-height: 40px;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid #d7dce4;
    background: #fff;
    color: #5a1229;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: filter .15s ease, transform .08s ease;
  }
  .tk-btn:hover {
    filter: brightness(.98);
  }
  .tk-btn:active {
    transform: translateY(1px);
  }
  .tk-btn-primary {
    border-color: #80143c;
    background: #80143c;
    color: #fff;
  }
  .tk-btn-warn {
    border-color: #f59e0b;
    background: #f59e0b;
    color: #fff;
  }
  @media (max-width: 980px) {
    .tk-profile-shell {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 640px) {
    .tk-profile-shell {
      gap: 12px;
    }
    .tk-profile-card-body {
      padding: 14px;
    }
    .tk-profile-form {
      grid-template-columns: 1fr;
      gap: 10px;
    }
    .tk-profile-actions .tk-btn {
      flex: 1 1 100%;
      min-width: 0;
    }
    .tk-profile-intro {
      font-size: 13px;
    }
  }
  @media (hover: none) {
    .tk-camera-btn {
      opacity: 1;
      pointer-events: auto;
      transform: translate(-50%, -50%);
    }
  }
</style>

<div class="tk-profile-shell">
  <section class="tk-profile-card">
    <div class="tk-profile-card-body">
    <div class="tk-profile-section-head">
      <h2 class="tk-profile-section-title">Account Information</h2>
      <p class="tk-profile-section-subtitle">Please ensure your name, email address, and contact number are accurate for official communication and system notices.</p>
    </div>
    <form method="post" class="tk-profile-form">
      <input type="hidden" name="action" value="update_profile">

      <label class="tk-profile-field">First Name
        <input name="first_name" value="<?= htmlspecialchars($me['first_name'] ?? '') ?>" required>
      </label>

      <label class="tk-profile-field">Last Name
        <input name="last_name" value="<?= htmlspecialchars($me['last_name'] ?? '') ?>" required>
      </label>

      <label class="tk-profile-field">Email
        <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
      </label>

      <label class="tk-profile-field">Phone
        <input name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
      </label>

      <div class="tk-profile-actions">
        <button type="submit" class="tk-btn tk-btn-primary">Save Profile</button>
        <a href="/TP/dashboard" class="tk-btn">Cancel</a>
      </div>
    </form>

    <?php if ($profileImage): ?>
      <form method="post" class="tk-profile-inline-form" onsubmit="return confirm('Delete your profile picture?');">
        <input type="hidden" name="action" value="delete_image">
        <div class="tk-profile-actions">
          <button type="submit" class="tk-btn tk-btn-warn">Remove Photo</button>
        </div>
      </form>
    <?php endif; ?>

    </div>
  </section>

  <section class="tk-profile-card">
    <div class="tk-profile-card-body">
    <div class="tk-profile-section-head">
      <h2 class="tk-profile-section-title">Security Settings</h2>
      <p class="tk-profile-section-subtitle">Use a strong password and update it periodically to help protect your account.</p>
    </div>
    <form method="post" class="tk-profile-form">
      <input type="hidden" name="action" value="change_password">

      <label class="tk-profile-field">Current Password
        <input type="password" name="current_password" required>
      </label>

      <label class="tk-profile-field">New Password
        <input type="password" name="new_password" required minlength="8">
      </label>

      <label class="tk-profile-field">Confirm New Password
        <input type="password" name="confirm_password" required minlength="8">
      </label>

      <div class="tk-profile-actions">
        <button type="submit" class="tk-btn tk-btn-warn">Change Password</button>
      </div>
    </form>
    </div>
  </section>
</div>
