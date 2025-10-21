<section id="profilePage">
  <header class="page-header">
    <div>
      <h2 class="page-title">My Profile</h2>
      <p class="page-subtitle">View and manage your account details</p>
    </div>
  </header>

  <?php if (!empty($msg)): ?>
    <div class="alert-success card" style="padding:14px; margin-bottom:18px;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3 class="card-title">Personal Information</h3>
    <form method="POST" class="filter-grid">
      <input type="hidden" name="action" value="update_profile">

      <div class="filter-group">
        <label class="filter-label">Full Name</label>
        <input type="text" name="full_name" class="search-input" 
               value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" required>
      </div>

      <div class="filter-group">
        <label class="filter-label">Email</label>
        <input type="email" name="email" class="search-input"
               value="<?= htmlspecialchars($me['email'] ?? '') ?>" required>
      </div>

      <div class="filter-group">
        <label class="filter-label">Phone</label>
        <input type="text" name="phone" class="search-input"
               value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
      </div>



      <div class="filter-actions">
        <button class="export-btn" type="submit">Save Changes</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3 class="card-title">Change Password</h3>
    <form method="POST" class="filter-grid">
      <input type="hidden" name="action" value="change_password">

      <div class="filter-group">
        <label class="filter-label">Current Password</label>
        <input type="password" name="current_password" class="search-input" required>
      </div>

      <div class="filter-group">
        <label class="filter-label">New Password</label>
        <input type="password" name="new_password" class="search-input" required>
      </div>

      <div class="filter-actions">
        <button class="export-btn" type="submit">Update Password</button>
      </div>
    </form>
  </div>


</section>
