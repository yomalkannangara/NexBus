<?php
// views/timekeeper_sltb/profile.php
$me          = $me  ?? ($_SESSION['user'] ?? []);
$msg         = $msg ?? null;
$displayName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : ($me['name'] ?? 'SLTB Timekeeper');
$initial     = strtoupper(substr($displayName ?: 'U', 0, 1));
$profileImage = $me['profile_image'] ?? null;

$_isSuccess = ['updated','image_updated','image_deleted','pw_changed'];
$_flashTexts = [
    'updated'       => 'Profile updated successfully.',
    'update_failed' => 'Could not update profile. Please try again.',
    'image_updated' => 'Profile photo updated successfully.',
    'upload_failed' => 'Failed to upload image. Please try again.',
    'image_deleted' => 'Profile photo removed.',
    'delete_failed' => 'Could not remove photo.',
    'invalid_image' => 'Invalid file type. Please use JPG, PNG, or WebP.',
    'no_file'       => 'No file was selected.',
    'pw_changed'    => 'Password changed successfully.',
    'pw_error'      => 'Password change failed. Check your current password.',
    'bad_action'    => 'Invalid action.',
];
$_flashMsg  = (!empty($msg) && isset($_flashTexts[$msg])) ? $_flashTexts[$msg] : null;
$_flashType = $_flashMsg ? (in_array($msg, $_isSuccess, true) ? 'success' : 'error') : null;
?>

<div class="dop-hero">
  <div class="dop-hero-cover" aria-hidden="true"></div>
  <div class="dop-hero-body">
    <form method="post" enctype="multipart/form-data" id="dop-photo-form">
      <input type="hidden" name="action" value="upload_image">
      <input type="file" name="profile_image" id="dop-photo-input"
             accept="image/jpeg,image/png,image/webp" style="display:none">
      <button type="button" class="dop-avatar-btn" title="Change profile photo"
              onclick="document.getElementById('dop-photo-input').click()">
        <?php if ($profileImage): ?>
          <img src="<?= htmlspecialchars($profileImage) ?>?v=<?= time() ?>" alt="Profile photo" class="dop-avatar-img" id="dop-avatar-img">
        <?php else: ?>
          <span class="dop-avatar-initial"><?= htmlspecialchars($initial) ?></span>
        <?php endif; ?>
        <span class="dop-avatar-overlay" aria-hidden="true">
          <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
            <circle cx="12" cy="13" r="4"/>
          </svg>
          <span>Change Photo</span>
        </span>
      </button>
    </form>
    <div class="dop-hero-identity">
      <h1 class="dop-hero-name" id="dop-display-name"><?= htmlspecialchars($displayName) ?></h1>
      <div class="dop-hero-meta">
        <span class="dop-hero-chip dop-chip-role">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= htmlspecialchars($me['role'] ?? 'SLTBTimekeeper') ?>
        </span>
        <?php if (!empty($me['status'])): ?>
        <span class="dop-hero-chip dop-chip-<?= strtolower(htmlspecialchars($me['status'])) ?>">
          <span class="dop-chip-dot"></span>
          <?= htmlspecialchars($me['status']) ?>
        </span>
        <?php endif; ?>
      </div>
      <div class="dop-hero-email" id="dop-display-email">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <?= htmlspecialchars($me['email'] ?? '') ?>
      </div>
    </div>
  </div>
</div>

<div class="dop-shell">
  <section class="dop-card">
    <div class="dop-card-head">
      <div class="dop-card-icon dop-icon-blue">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <h2 class="dop-card-title">Account Information</h2>
        <p class="dop-card-sub">Keep your name, email, and contact number up to date.</p>
      </div>
    </div>
    <form method="post" class="dop-form" id="dop-profile-form">
      <input type="hidden" name="action" value="update_profile">
      <div class="dop-field">
        <label class="dop-label" for="dop-first-name">First Name</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input id="dop-first-name" name="first_name" class="dop-input"
                 value="<?= htmlspecialchars($me['first_name'] ?? '') ?>" required autocomplete="given-name">
        </div>
      </div>
      <div class="dop-field">
        <label class="dop-label" for="dop-last-name">Last Name</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input id="dop-last-name" name="last_name" class="dop-input"
                 value="<?= htmlspecialchars($me['last_name'] ?? '') ?>" autocomplete="family-name">
        </div>
      </div>
      <div class="dop-field">
        <label class="dop-label" for="dop-email">Email Address</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <input id="dop-email" type="email" name="email" class="dop-input"
                 value="<?= htmlspecialchars($me['email'] ?? '') ?>" required autocomplete="email">
        </div>
      </div>
      <div class="dop-field">
        <label class="dop-label" for="dop-phone">Phone Number</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.15 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.06 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
          <input id="dop-phone" name="phone" class="dop-input"
                 value="<?= htmlspecialchars($me['phone'] ?? '') ?>" autocomplete="tel">
        </div>
      </div>
      <div class="dop-form-footer">
        <button type="submit" class="dop-btn dop-btn-primary" id="dop-save-btn">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Save Changes
        </button>
        <a href="/TS/dashboard" class="dop-btn dop-btn-ghost">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Cancel
        </a>
        <button type="button" class="dop-btn dop-btn-ghost dop-btn-danger" id="dop-delete-photo-btn"
                <?= $profileImage ? '' : 'style="display:none"' ?>>
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          Remove Photo
        </button>
      </div>
    </form>
  </section>

  <section class="dop-card">
    <div class="dop-card-head">
      <div class="dop-card-icon dop-icon-amber">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div>
        <h2 class="dop-card-title">Security &amp; Password</h2>
        <p class="dop-card-sub">Use a strong, unique password and update it regularly.</p>
      </div>
    </div>
    <form method="post" class="dop-form" id="dop-pw-form">
      <input type="hidden" name="action" value="change_password">
      <div class="dop-field">
        <label class="dop-label" for="dop-cur-pw">Current Password</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input id="dop-cur-pw" type="password" name="current_password" class="dop-input" required autocomplete="current-password">
          <button type="button" class="dop-pw-toggle" data-target="dop-cur-pw" aria-label="Toggle visibility">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="dop-eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div class="dop-field">
        <label class="dop-label" for="dop-new-pw">New Password</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input id="dop-new-pw" type="password" name="new_password" class="dop-input" required minlength="8" autocomplete="new-password">
          <button type="button" class="dop-pw-toggle" data-target="dop-new-pw" aria-label="Toggle visibility">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="dop-eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="dop-strength-wrap" id="dop-strength-wrap" aria-live="polite">
          <div class="dop-strength-bar"><div class="dop-strength-fill" id="dop-strength-fill"></div></div>
          <span class="dop-strength-label" id="dop-strength-label"></span>
        </div>
      </div>
      <div class="dop-field">
        <label class="dop-label" for="dop-conf-pw">Confirm New Password</label>
        <div class="dop-input-wrap">
          <svg class="dop-input-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input id="dop-conf-pw" type="password" name="confirm_password" class="dop-input" required minlength="8" autocomplete="new-password">
          <button type="button" class="dop-pw-toggle" data-target="dop-conf-pw" aria-label="Toggle visibility">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="dop-eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="dop-match-hint" id="dop-match-hint"></div>
      </div>
      <div class="dop-form-footer">
        <button type="submit" class="dop-btn dop-btn-amber">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Update Password
        </button>
      </div>
    </form>
  </section>
</div>

<style>
.page-hero { display: none !important; }
#dop-toast-container { position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none; }
.dop-toast { display:flex;align-items:center;gap:10px;min-width:280px;max-width:360px;padding:13px 16px;border-radius:12px;font-size:13.5px;font-weight:600;line-height:1.4;box-shadow:0 8px 24px rgba(0,0,0,.16);pointer-events:auto;animation:dop-toast-in .3s cubic-bezier(.34,1.56,.64,1) both;border:1px solid; }
.dop-toast.dop-toast-success { background:#f0fdf4;color:#15803d;border-color:#bbf7d0; }
.dop-toast.dop-toast-error   { background:#fef2f2;color:#b91c1c;border-color:#fecaca; }
.dop-toast.dop-toast-info    { background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe; }
.dop-toast-icon { flex:0 0 auto; }
.dop-toast-text { flex:1; }
.dop-toast-close { flex:0 0 auto;background:none;border:none;cursor:pointer;opacity:.5;padding:0 2px;font-size:16px;line-height:1;color:inherit; }
.dop-toast-close:hover { opacity:1; }
@keyframes dop-toast-in  { from{opacity:0;transform:translateX(40px) scale(.9)} to{opacity:1;transform:translateX(0) scale(1)} }
@keyframes dop-toast-out { from{opacity:1;transform:translateX(0) scale(1)} to{opacity:0;transform:translateX(40px) scale(.8)} }
.dop-hero { position:relative;border-radius:18px;overflow:hidden;margin-bottom:22px;min-height:160px; }
.dop-hero-cover { position:absolute;inset:0;background:linear-gradient(135deg,#63082e 0%,#80143c 40%,#a41b4e 70%,#c02060 100%);z-index:0; }
.dop-hero-cover::after { content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 80% 50%,rgba(255,255,255,.07) 0%,transparent 70%); }
.dop-hero-body { position:relative;z-index:1;display:flex;align-items:center;gap:22px;padding:28px 28px 24px;flex-wrap:wrap; }
.dop-avatar-btn { position:relative;width:96px;height:96px;border-radius:50%;border:4px solid rgba(255,255,255,.35);overflow:hidden;cursor:pointer;background:none;padding:0;flex:0 0 auto;transition:border-color .2s,box-shadow .2s;box-shadow:0 4px 20px rgba(0,0,0,.25); }
.dop-avatar-btn:hover { border-color:rgba(255,255,255,.7);box-shadow:0 6px 28px rgba(0,0,0,.35); }
.dop-avatar-img { width:100%;height:100%;object-fit:cover; }
.dop-avatar-initial { width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15);color:#fff;font-size:38px;font-weight:800; }
.dop-avatar-overlay { position:absolute;inset:0;background:rgba(0,0,0,.52);border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:0;transition:opacity .25s; }
.dop-avatar-btn:hover .dop-avatar-overlay,.dop-avatar-btn:focus-visible .dop-avatar-overlay { opacity:1; }
@media(hover:none){.dop-avatar-overlay{opacity:1}}
.dop-hero-identity { flex:1;min-width:180px; }
.dop-hero-name { margin:0 0 8px;font-size:clamp(1.3rem,2vw,1.75rem);font-weight:800;color:#fff;line-height:1.1;letter-spacing:-.02em; }
.dop-hero-meta { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px; }
.dop-hero-chip { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff; }
.dop-chip-active { background:rgba(74,222,128,.25);border-color:rgba(74,222,128,.4); }
.dop-chip-dot { width:6px;height:6px;border-radius:50%;background:#4ade80;flex:0 0 auto; }
.dop-chip-suspended .dop-chip-dot { background:#f87171; }
.dop-hero-email { display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.8);font-weight:500; }
.dop-shell { display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:18px;align-items:start; }
@media(max-width:900px){.dop-shell{grid-template-columns:1fr}}
.dop-card { background:#fff;border-radius:16px;border:1px solid #e8eaee;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden; }
.dop-card-head { display:flex;align-items:flex-start;gap:12px;padding:20px 22px 18px;border-bottom:1px solid #f0f2f5; }
.dop-card-icon { flex:0 0 auto;width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center; }
.dop-icon-blue  { background:#eff6ff;color:#2563eb; }
.dop-icon-amber { background:#fffbeb;color:#d97706; }
.dop-card-title { margin:0 0 3px;font-size:15px;font-weight:700;color:#111827; }
.dop-card-sub   { margin:0;font-size:12.5px;color:#6b7280;line-height:1.4; }
.dop-form { display:grid;grid-template-columns:repeat(2,1fr);gap:14px 16px;padding:20px 22px; }
.dop-form-footer { grid-column:1/-1;display:flex;flex-wrap:wrap;gap:10px;margin-top:4px;align-items:center; }
.dop-field { display:grid;gap:5px; }
.dop-label { font-size:12.5px;font-weight:700;color:#374151;letter-spacing:.01em; }
.dop-input-wrap { position:relative;display:flex;align-items:center; }
.dop-input-icon { position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none; }
.dop-input { width:100%;padding:10px 36px 10px 34px;border:1.5px solid #d1d5db;border-radius:10px;background:#fafafa;color:#111827;font-size:13.5px;transition:border-color .15s,box-shadow .15s,background .15s;outline:none; }
.dop-input:focus { border-color:#80143c;background:#fff;box-shadow:0 0 0 3px rgba(128,20,60,.12); }
.dop-input:hover:not(:focus) { border-color:#9ca3af; }
.dop-pw-toggle { position:absolute;right:9px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:3px;display:flex;align-items:center;border-radius:6px;transition:color .15s; }
.dop-pw-toggle:hover { color:#374151; }
.dop-pw-toggle.active { color:#80143c; }
.dop-strength-wrap { display:none;align-items:center;gap:8px;margin-top:4px; }
.dop-strength-wrap.visible { display:flex; }
.dop-strength-bar { flex:1;height:4px;background:#e5e7eb;border-radius:999px;overflow:hidden; }
.dop-strength-fill { height:100%;width:0;border-radius:999px;transition:width .35s,background .35s; }
.dop-strength-label { font-size:11px;font-weight:700;min-width:52px;text-align:right; }
.dop-match-hint { font-size:11.5px;font-weight:600;min-height:16px; }
.dop-match-hint.ok { color:#16a34a; }
.dop-match-hint.no { color:#dc2626; }
.dop-btn { display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;font-size:13.5px;font-weight:700;border:1.5px solid transparent;cursor:pointer;text-decoration:none;transition:filter .15s,transform .08s,box-shadow .15s;white-space:nowrap; }
.dop-btn:active { transform:translateY(1px); }
.dop-btn-primary { background:#80143c;color:#fff;border-color:#80143c;box-shadow:0 2px 8px rgba(128,20,60,.25); }
.dop-btn-primary:hover { filter:brightness(1.08);box-shadow:0 4px 14px rgba(128,20,60,.35); }
.dop-btn-amber { background:#d97706;color:#fff;border-color:#d97706;box-shadow:0 2px 8px rgba(217,119,6,.2); }
.dop-btn-amber:hover { filter:brightness(1.08);box-shadow:0 4px 14px rgba(217,119,6,.3); }
.dop-btn-ghost { background:#fff;color:#374151;border-color:#d1d5db; }
.dop-btn-ghost:hover { background:#f9fafb;border-color:#9ca3af;color:#111827; }
.dop-btn-danger { color:#b91c1c;border-color:#fca5a5; }
.dop-btn-danger:hover { background:#fef2f2;border-color:#f87171; }
.dop-btn[data-loading] { opacity:.7;pointer-events:none; }
@media(max-width:640px){
  .dop-hero-body{padding:20px 16px;gap:16px}
  .dop-avatar-btn{width:72px;height:72px}
  .dop-card-head{padding:16px 16px 14px}
  .dop-form{grid-template-columns:1fr;padding:16px;gap:12px}
  .dop-form-footer .dop-btn{flex:1 1 100%;justify-content:center}
}
</style>

<script>
(function(){
  function showToast(text,type){
    var icons={success:'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',error:'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',info:'<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'};
    var t=document.createElement('div');
    t.className='dop-toast dop-toast-'+(type||'info');
    t.innerHTML='<span class="dop-toast-icon">'+(icons[type]||icons.info)+'</span><span class="dop-toast-text">'+text+'</span><button class="dop-toast-close" aria-label="Dismiss">&times;</button>';
    t.querySelector('.dop-toast-close').addEventListener('click',function(){dismiss(t);});
    document.getElementById('dop-toast-container').appendChild(t);
    var timer=setTimeout(function(){dismiss(t);},5000);
    t._timer=timer;
    function dismiss(el){clearTimeout(el._timer);el.style.animation='dop-toast-out .25s ease forwards';el.addEventListener('animationend',function(){el.remove();});}
  }
  <?php if($_flashMsg): ?>
  document.addEventListener('DOMContentLoaded',function(){showToast(<?=json_encode($_flashMsg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT)?>,<?=json_encode($_flashType)?>);});
  <?php endif; ?>
  document.addEventListener('DOMContentLoaded',function(){
    var form=document.getElementById('dop-profile-form');
    if(!form)return;
    form.addEventListener('submit',async function(e){
      e.preventDefault();
      var btn=form.querySelector('[type="submit"]');
      btn.setAttribute('data-loading','1');
      var oldText=btn.innerHTML;
      btn.innerHTML='<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Saving\u2026';
      var res,data;
      try{res=await fetch(location.pathname,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});data=await res.json();}
      catch(err){btn.removeAttribute('data-loading');btn.innerHTML=oldText;showToast('Network error. Please try again.','error');return;}
      btn.removeAttribute('data-loading');btn.innerHTML=oldText;
      if(data&&data.ok){
        var u=data.user||{};
        var dn=((u.first_name||'')+' '+(u.last_name||'')).trim()||'User';
        var nameEl=document.getElementById('dop-display-name');if(nameEl)nameEl.textContent=dn;
        var emailEl=document.getElementById('dop-display-email');if(emailEl)emailEl.childNodes[emailEl.childNodes.length-1].textContent=u.email||'';
        showToast('Profile updated successfully.','success');
      }else{showToast((data&&data.msg)||'Could not update profile.','error');}
    });
  });
  document.addEventListener('DOMContentLoaded',function(){
    var inp=document.getElementById('dop-photo-input');
    if(inp)inp.addEventListener('change',function(){
      if(this.files.length>0){
        var reader=new FileReader();
        reader.onload=function(ev){
          var btn=document.querySelector('.dop-avatar-btn');
          if(btn){btn.innerHTML='<img src="'+ev.target.result+'" alt="Profile photo" class="dop-avatar-img" id="dop-avatar-img">'+btn.querySelector('.dop-avatar-overlay').outerHTML;}
        };
        reader.readAsDataURL(inp.files[0]);
        document.getElementById('dop-photo-form').submit();
      }
    });
  });
  document.addEventListener('DOMContentLoaded',function(){
    var delBtn=document.getElementById('dop-delete-photo-btn');
    if(!delBtn)return;
    delBtn.addEventListener('click',async function(){
      if(!confirm('Remove your profile photo?'))return;
      delBtn.setAttribute('data-loading','1');
      var fd=new FormData();fd.append('action','delete_image');
      try{await fetch(location.pathname,{method:'POST',body:fd});}catch(e){}
      var avatarBtn=document.querySelector('.dop-avatar-btn');
      if(avatarBtn){
        var initial=(document.getElementById('dop-display-name').textContent||'U').charAt(0).toUpperCase();
        avatarBtn.innerHTML='<span class="dop-avatar-initial">'+initial+'</span><span class="dop-avatar-overlay" aria-hidden="true"><svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span>Change Photo</span></span>';
      }
      delBtn.style.display='none';delBtn.removeAttribute('data-loading');
      showToast('Profile photo removed.','success');
    });
  });
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.dop-pw-toggle').forEach(function(btn){
      btn.addEventListener('click',function(){
        var target=document.getElementById(this.getAttribute('data-target'));if(!target)return;
        var shown=target.type==='text';target.type=shown?'password':'text';
        this.classList.toggle('active',!shown);
        var svg=this.querySelector('svg');
        if(shown){svg.innerHTML='<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';}
        else{svg.innerHTML='<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';}
      });
    });
  });
  document.addEventListener('DOMContentLoaded',function(){
    var newPw=document.getElementById('dop-new-pw');
    var fill=document.getElementById('dop-strength-fill');
    var label=document.getElementById('dop-strength-label');
    var wrap=document.getElementById('dop-strength-wrap');
    if(!newPw||!fill)return;
    function score(pw){if(!pw)return 0;var s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;return s;}
    var levels=[{pct:'20%',color:'#ef4444',text:'Weak'},{pct:'40%',color:'#f97316',text:'Fair'},{pct:'60%',color:'#eab308',text:'Moderate'},{pct:'80%',color:'#22c55e',text:'Strong'},{pct:'100%',color:'#16a34a',text:'Very Strong'}];
    newPw.addEventListener('input',function(){
      var pw=this.value;if(!pw){wrap.classList.remove('visible');return;}
      wrap.classList.add('visible');
      var s=Math.min(score(pw),5)-1;if(s<0)s=0;
      var lv=levels[s];fill.style.width=lv.pct;fill.style.background=lv.color;label.textContent=lv.text;label.style.color=lv.color;
    });
  });
  document.addEventListener('DOMContentLoaded',function(){
    var newPw=document.getElementById('dop-new-pw');
    var confPw=document.getElementById('dop-conf-pw');
    var hint=document.getElementById('dop-match-hint');
    if(!newPw||!confPw||!hint)return;
    function check(){
      if(!confPw.value){hint.textContent='';hint.className='dop-match-hint';return;}
      if(confPw.value===newPw.value){hint.textContent='\u2713 Passwords match';hint.className='dop-match-hint ok';}
      else{hint.textContent='\u2717 Passwords do not match';hint.className='dop-match-hint no';}
    }
    confPw.addEventListener('input',check);newPw.addEventListener('input',check);
  });
})();
</script>
