<?php
/** @var array|null $me */
/** @var string|null $msg */
$me = $me ?? null;
?>
<section class="page-head">
  <h2>My Profile</h2>
  <div class="muted">Manage your personal info, password, and account.</div>
</section>

<?php if (!empty($msg)): ?>
  <div class="card notice <?= (str_contains($msg,'failed') || str_contains($msg,'incorrect') || str_contains($msg,'error')) ? 'error' : 'success' ?>" style="padding:12px;">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<!-- Profile details -->
<div class="card">
  <div class="section-title"><h3>Profile details</h3></div>
  <form method="post" class="form big" autocomplete="on">
    <input type="hidden" name="action" value="update_profile">

    <div class="grid-2">
      <div class="field">
        <label class="req">Full name</label>
        <div class="select-wrap no-caret">
          <input type="text" name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="req">Email</label>
        <div class="select-wrap no-caret">
          <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="field">
        <label>Phone</label>
        <div class="select-wrap no-caret">
          <input type="text" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn">Save changes</button>
    </div>
  </form>
</div>

<!-- Change password -->
<div class="card">
  <div class="section-title"><h3>Change password</h3></div>
  <form method="post" class="form big" autocomplete="off">
    <input type="hidden" name="action" value="update_password">

    <div class="grid-2">
      <div class="field">
        <label class="req">Current password</label>
        <div class="select-wrap no-caret">
          <input type="password" name="current_password" required>
        </div>
      </div>

      <div class="field">
        <label class="req">New password</label>
        <div class="select-wrap no-caret">
          <input type="password" name="new_password" required minlength="6">
        </div>
      </div>

      <div class="field">
        <label class="req">Confirm new password</label>
        <div class="select-wrap no-caret">
          <input type="password" name="confirm_password" required minlength="6">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn">Update password</button>
    </div>
    <div class="form-hint muted" style="margin-top:6px;">Use at least 6 characters. Avoid reusing old passwords.</div>
  </form>
</div>

<!-- Danger zone -->
<div class="card danger-card">
  <div class="section-title"><h3>Danger zone</h3></div>
  <p class="muted" style="margin:8px 0 12px;">
    Soft delete will anonymize your data and suspend login. Hard delete removes your user row (your passenger record will be anonymized and unlinked).
  </p>

  <form method="post" class="form big">
    <input type="hidden" name="action" value="delete_account">

    <div class="grid-2">
      <div class="field">
        <label>Delete mode</label>
        <div class="radio-row">
          <label class="radio-pill">
            <input type="radio" name="mode" value="soft" checked>
            <span>Soft delete (recommended)</span>
          </label>
          <label class="radio-pill">
            <input type="radio" name="mode" value="hard">
            <span>Hard delete</span>
          </label>
        </div>
      </div>

      <div class="field">
        <label class="req">Type <b>DELETE</b> to confirm</label>
        <div class="select-wrap no-caret">
          <input type="text" name="confirm" placeholder="DELETE" required>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn ghost" type="submit" onclick="return confirm('This action cannot be undone. Proceed?')">Delete my account</button>
    </div>
  </form>
</div>
