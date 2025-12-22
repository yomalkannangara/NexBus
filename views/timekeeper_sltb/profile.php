<?php
// views/timekeeper_sltb/profile.php
$me  = $me ?? ($_SESSION['user'] ?? []);
$msg = $msg ?? null;
$displayName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : ($me['name'] ?? 'SLTB Timekeeper');
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
  <div style="display:flex;align-items:center;gap:14px;">
    <!-- Profile Image with Camera Overlay (WhatsApp Style) -->
    <div class="profile-image-container" style="position:relative;width:80px;height:80px;">
      <div class="profile-avatar-lg" aria-hidden="true" style="width:100%;height:100%;">
        <?php if ($profileImage): ?>
          <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#e0e0e0;border-radius:50%;font-size:32px;color:#666;"><?= htmlspecialchars($initial) ?></div>
        <?php endif; ?>
      </div>
      <!-- Camera Icon Overlay -->
      <form method="post" enctype="multipart/form-data" style="position:absolute;bottom:0;right:0;margin:0;">
        <input type="hidden" name="action" value="upload_image">
        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" style="display:none;" id="cameraInput">
        <button type="button" class="camera-btn" title="Change profile picture" onclick="document.getElementById('cameraInput').click();" style="position:absolute;bottom:0;right:0;width:32px;height:32px;border-radius:50%;background:#007bff;border:3px solid white;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;padding:0;">
          📷
        </button>
      </form>
    </div>
    
    <div>
      <h1 style="margin:0"><?= htmlspecialchars($displayName) ?></h1>
      <div class="muted"><?= htmlspecialchars($me['email'] ?? '') ?></div>
      <div style="margin-top:4px">
        <span class="badge-role"><?= htmlspecialchars($me['role'] ?? 'SLTB Timekeeper') ?></span>
        <?php if (!empty($me['status'])): ?>
          <span class="badge-status <?= strtolower($me['status']) ?>"><?= htmlspecialchars($me['status']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <p>Update your account details, profile picture, and change your password.</p>

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
      this.form.submit();
    }
  });
</script>

<div class="grid-2">
  <!-- LEFT COLUMN: PROFILE + PASSWORD -->
  <section class="panel show">
    <!-- Profile Details Section -->
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
        <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
      </label>

      <label>Phone
        <input name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
      </label>

      <div class="form-actions">
        <button class="btn primary">Save Changes</button>
        <?php if ($profileImage): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="delete_image">
            <button type="submit" class="btn warn" onclick="return confirm('Delete your profile picture?');">Delete Image</button>
          </form>
        <?php endif; ?>
        <a href="/TS/dashboard" class="btn">Cancel</a>
      </div>
    </form>

    <hr class="sep">

    <!-- Change Password Section -->
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

<!-- Account Actions -->
<div class="panel" style="margin-top:16px">
  <div class="panel-head"><h2>Account</h2></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/logout" class="btn danger">⇦ Logout</a>
  </div>
</div>

