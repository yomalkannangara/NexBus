<?php
// views/bus_owner/fleet_create.php
// Add New Bus Form
?>
<header class="page-header">
  <div>
    <h2 class="page-title">Add New Bus</h2>
    <p class="page-subtitle">Register a new bus to your fleet</p>
  </div>
  <a href="<?= BASE_URL; ?>/fleet" class="export-report-btn-alt">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
      <path d="M15 10l-5-5m0 0L5 10m5-5v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    Back to Fleet
  </a>
</header>

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <h3 class="card-title">Bus Information</h3>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert-item alert-warning" style="margin-bottom: 20px;">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <div class="alert-content">
        <div class="alert-title">Error creating bus</div>
        <div class="alert-time">Please check the form and try again</div>
      </div>
    </div>
  <?php endif; ?>

  <form action="<?= BASE_URL; ?>/fleet/store" method="POST">
    <div class="filter-grid">
      <div class="filter-group">
        <label class="filter-label">
          Registration Number <span style="color: #DC2626;">*</span>
        </label>
        <input 
          type="text" 
          name="reg_no" 
          class="search-input" 
          placeholder="e.g., WP ABC-1234" 
          required
          pattern="[A-Z]{2,3}\s?[A-Z]{2,4}-\d{4}"
          title="Format: WP ABC-1234"
        />
      </div>

      <div class="filter-group">
        <label class="filter-label">
          Chassis Number <span style="color: #DC2626;">*</span>
        </label>
        <input 
          type="text" 
          name="chassis_no" 
          class="search-input" 
          placeholder="e.g., CHASSIS123456" 
          required
        />
      </div>

      <div class="filter-group">
        <label class="filter-label">
          Capacity (Seats) <span style="color: #DC2626;">*</span>
        </label>
        <input 
          type="number" 
          name="capacity" 
          class="search-input" 
          placeholder="e.g., 50" 
          min="1" 
          max="200" 
          required
        />
      </div>

      <div class="filter-group">
        <label class="filter-label">
          Status <span style="color: #DC2626;">*</span>
        </label>
        <select name="status" class="search-input" required>
          <option value="Active" selected>Active</option>
          <option value="Maintenance">Maintenance</option>
          <option value="Out of Service">Out of Service</option>
        </select>
      </div>
    </div>

    <div class="filter-actions">
      <a href="<?= BASE_URL; ?>/fleet" class="filter-btn" style="text-decoration: none;">
        Cancel
      </a>
      <button type="submit" class="export-btn">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Add Bus
      </button>
    </div>
  </form>
</div>
