<?php
// app/views/bus_owner/drivers.php
// Expects: $drivers, $conductors, $opId, $fieldErrors, $oldInput (from controller).

$fieldErrors = $fieldErrors ?? [];
$oldInput = $oldInput ?? [];

$_flashMsgs = [
  'created' => ['Driver added successfully.', true],
  'updated' => ['Driver updated successfully.', true],
  'deleted' => ['Driver deleted successfully.', true],
  'conductor_created' => ['Conductor added successfully.', true],
  'conductor_updated' => ['Conductor updated successfully.', true],
  'conductor_deleted' => ['Conductor deleted successfully.', true],
  'validation_error' => ['Please fix the errors below and try again.', false],
  'error' => ['An error occurred. Please try again.', false],
];
$_flashKey = $_GET['msg'] ?? '';
$_flashData = $_flashMsgs[$_flashKey] ?? null;
?>
<?php if ($_flashData): ?>
  <div id="page-flash" style="
  position:fixed;top:20px;right:20px;z-index:9999;
  background:<?= $_flashData[1] ? '#059669' : '#DC2626'; ?>;
  color:#fff;padding:12px 20px;border-radius:8px;
  font-size:14px;font-weight:600;
  box-shadow:0 4px 16px rgba(0,0,0,.18);
  animation:flashIn .25s ease;
">   <?= htmlspecialchars($_flashData[0]); ?></div>
  <style>
    @keyframes flashIn {
      from {
        opacity: 0;
        transform: translateY(-8px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }
  </style>
  <script>setTimeout(function () { var e = document.getElementById('page-flash'); if (e) { e.style.transition = 'opacity .4s'; e.style.opacity = '0'; setTimeout(function () { e.remove(); }, 400); } }, 2800);</script>
<?php endif; ?>

<?php if (!empty($fieldErrors)): ?>
  <div class="val-error-banner" role="alert">
    <div class="val-error-banner__icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 8v4M12 16h.01" />
      </svg>
    </div>
    <div class="val-error-banner__body">
      <p class="val-error-banner__title">Please correct the following errors:</p>
      <ul class="val-error-banner__list">
        <?php foreach ($fieldErrors as $field => $msg): ?>
          <li><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))); ?>:</strong>
            <?= htmlspecialchars($msg); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<style>
  /* ── Shared validation error banner ─────────────────────────────── */
  .val-error-banner {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    background: #FEF2F2;
    border: 1.5px solid #FCA5A5;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 18px;
  }

  .val-error-banner__icon {
    color: #DC2626;
    flex-shrink: 0;
    margin-top: 1px;
  }

  .val-error-banner__title {
    font-size: 14px;
    font-weight: 700;
    color: #991B1B;
    margin: 0 0 6px;
  }

  .val-error-banner__list {
    margin: 0;
    padding-left: 18px;
  }

  .val-error-banner__list li {
    font-size: 13px;
    color: #7F1D1D;
    margin-bottom: 3px;
  }
</style>

<header class="page-header">
  <div>
    <h2 class="page-title">Drivers & Conductors</h2>
    <p class="page-subtitle">Manage staff information</p>
  </div>

  <div class="header-actions header-actions--tight">
    <a href="#" id="btnAddDriverLocal" class="add-bus-btn" style="margin-right: 5px;">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
      Add New Driver
    </a>
    <a href="#" id="btnAddConductorLocal" class="add-bus-btn">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
      Add New Conductor
    </a>
  </div>
</header>

<?php
// ── Staff KPI computation ─────────────────────────────────────────
$_skpi = [
  'total_drivers' => 0,
  'active_drivers' => 0,
  'suspended_drivers' => 0,
  'total_conductors' => 0,
  'active_conductors' => 0,
  'suspended_conductors' => 0,
];
foreach ($drivers ?? [] as $_d) {
  $_skpi['total_drivers']++;
  if (strtolower($_d['status'] ?? 'active') === 'active')
    $_skpi['active_drivers']++;
  else
    $_skpi['suspended_drivers']++;
}
foreach ($conductors ?? [] as $_c) {
  $_skpi['total_conductors']++;
  if (strtolower($_c['status'] ?? 'active') === 'active')
    $_skpi['active_conductors']++;
  else
    $_skpi['suspended_conductors']++;
}
$_skpi['total_staff'] = $_skpi['total_drivers'] + $_skpi['total_conductors'];
?>

<!-- ── Staff KPI Summary ──────────────────────────────────────────── -->
<div class="staff-kpi-grid">

  <!-- Total Staff -->
  <div class="staff-kpi-card staff-kpi-card--total">
    <div class="staff-kpi-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" />
        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" />
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" />
      </svg>
    </div>
    <div class="staff-kpi-body">
      <span class="staff-kpi-value"><?= $_skpi['total_staff']; ?></span>
      <span class="staff-kpi-label">Total Staff</span>
    </div>
    <div class="staff-kpi-bar staff-kpi-bar--total"></div>
  </div>


  <!-- Active Drivers -->
  <div class="staff-kpi-card staff-kpi-card--active-drv">
    <div class="staff-kpi-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
        <path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>
    <div class="staff-kpi-body">
      <span class="staff-kpi-value"><?= $_skpi['active_drivers']; ?></span>
      <span class="staff-kpi-label">Active Drivers</span>
    </div>
    <div class="staff-kpi-bar staff-kpi-bar--active"></div>
  </div>

  <!-- Suspended Drivers -->
  <div class="staff-kpi-card staff-kpi-card--suspended-drv">
    <div class="staff-kpi-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2" />
        <path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
    </div>
    <div class="staff-kpi-body">
      <span class="staff-kpi-value"><?= $_skpi['suspended_drivers']; ?></span>
      <span class="staff-kpi-label">Suspended Drivers</span>
    </div>
    <div class="staff-kpi-bar staff-kpi-bar--suspended"></div>
  </div>

  <!-- Active Conductors -->
  <div class="staff-kpi-card staff-kpi-card--active-cnd">
    <div class="staff-kpi-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2" />
        <path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <path d="M17 13l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round" />
      </svg>
    </div>
    <div class="staff-kpi-body">
      <span class="staff-kpi-value"><?= $_skpi['active_conductors']; ?></span>
      <span class="staff-kpi-label">Active Conductors</span>
    </div>
    <div class="staff-kpi-bar staff-kpi-bar--active"></div>
  </div>

  <!-- Suspended Conductors -->
  <div class="staff-kpi-card staff-kpi-card--suspended-cnd">
    <div class="staff-kpi-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2" />
        <path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <path d="M17 13l5 5M22 13l-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
    </div>
    <div class="staff-kpi-body">
      <span class="staff-kpi-value"><?= $_skpi['suspended_conductors']; ?></span>
      <span class="staff-kpi-label">Suspended Conductors</span>
    </div>
    <div class="staff-kpi-bar staff-kpi-bar--suspended"></div>
  </div>

</div>

<style>
  /* ── Staff KPI Summary Grid ─────────────────────────────────────── */
  .staff-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 20px;
  }

  .staff-kpi-card {
    position: relative;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 14px;
    padding: 16px 14px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    overflow: hidden;
    transition: transform .15s ease, box-shadow .15s ease;
  }

  .staff-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0, 0, 0, .09);
  }

  .staff-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .staff-kpi-body {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .staff-kpi-value {
    font-size: 26px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.5px;
  }

  .staff-kpi-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #6B7280;
  }

  .staff-kpi-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 0 0 14px 14px;
  }

  .staff-kpi-progress-wrap {
    height: 4px;
    background: #E5E7EB;
    border-radius: 99px;
    overflow: hidden;
  }

  .staff-kpi-progress-bar {
    height: 100%;
    border-radius: 99px;
    transition: width .6s ease;
  }

  /* ── Colour themes ──────────────────────────────────────────────── */
  .staff-kpi-card--total .staff-kpi-icon {
    background: #EFF6FF;
    color: #3B82F6;
  }

  .staff-kpi-card--total .staff-kpi-value {
    color: #1D4ED8;
  }

  .staff-kpi-card--total .staff-kpi-bar {
    background: #3B82F6;
  }

  .staff-kpi-card--drivers .staff-kpi-icon {
    background: #F5F3FF;
    color: #8B5CF6;
  }

  .staff-kpi-card--drivers .staff-kpi-value {
    color: #7C3AED;
  }

  .staff-kpi-card--drivers .staff-kpi-bar {
    background: #8B5CF6;
  }

  .staff-kpi-progress-bar--drivers {
    background: #8B5CF6;
  }

  .staff-kpi-card--active-drv .staff-kpi-icon {
    background: #ECFDF5;
    color: #10B981;
  }

  .staff-kpi-card--active-drv .staff-kpi-value {
    color: #059669;
  }

  .staff-kpi-card--active-cnd .staff-kpi-icon {
    background: #ECFDF5;
    color: #10B981;
  }

  .staff-kpi-card--active-cnd .staff-kpi-value {
    color: #059669;
  }

  .staff-kpi-bar--active {
    background: #10B981;
  }

  .staff-kpi-card--suspended-drv .staff-kpi-icon {
    background: #FFF7ED;
    color: #F97316;
  }

  .staff-kpi-card--suspended-drv .staff-kpi-value {
    color: #EA580C;
  }

  .staff-kpi-card--suspended-cnd .staff-kpi-icon {
    background: #FFF7ED;
    color: #F97316;
  }

  .staff-kpi-card--suspended-cnd .staff-kpi-value {
    color: #EA580C;
  }

  .staff-kpi-bar--suspended {
    background: #F97316;
  }

  @media (max-width: 1024px) {
    .staff-kpi-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 640px) {
    .staff-kpi-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>

<!-- Driver Filter Bar -->

<div class="filter-bar">
  <div class="filter-group">
    <label for="drv-filter-status">Status:</label>
    <select id="drv-filter-status" class="filter-select">
      <option value="all">All</option>
      <option value="Active">Active</option>
      <option value="Suspended">Suspended</option>
    </select>
  </div>
  <div class="search-container">
    <svg class="search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
      <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" />
      <path d="M12.5 12.5l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
    <input type="text" id="drv-search" class="search-input" placeholder="Search by name, license, or phone…">
  </div>
</div>

<div class="card">
  <h3 class="card-title">Driver Registry</h3>

  <div class="table-container">
    <table class="data-table" id="drivers-table">
      <thead>
        <tr>
          <th>Driver</th>
          <th>License</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!empty($drivers)): ?>
          <?php foreach ($drivers as $d): ?>
            <?php
            $drvId = (int) ($d['private_driver_id'] ?? 0);
            $drvLogs = $driver_logs[$drvId] ?? [];
            ?>
            <tr class="js-profile-row" style="cursor:pointer;" data-type="driver"
              data-profile='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'
              data-logs='<?= htmlspecialchars(json_encode($drvLogs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
              <td>
                <div class="driver-info">
                  <div class="driver-avatar">
                    <?php
                    $name = (string) ($d['full_name'] ?? '');
                    $parts = preg_split('/\s+/', trim($name));
                    $ini = '';
                    if (!empty($parts[0])) {
                      $ini .= strtoupper(substr($parts[0], 0, 1));
                    }
                    if (count($parts) > 1) {
                      $ini .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
                    }
                    echo htmlspecialchars($ini);
                    ?>
                  </div>
                  <div>
                    <div class="driver-name"><?= htmlspecialchars($d['full_name'] ?? ''); ?></div>
                    <div class="driver-id">DRV-<?= (int) ($d['private_driver_id'] ?? 0); ?></div>
                  </div>
                </div>
              </td>

              <td><strong><?= htmlspecialchars($d['license_no'] ?? ''); ?></strong></td>
              <td><?= htmlspecialchars($d['phone'] ?? ''); ?></td>

              <td>
                <?php
                $status = (string) ($d['status'] ?? 'Active');
                $drvMap = ['Active' => 'status-active', 'Suspended' => 'status-suspended'];
                $cls = $drvMap[$status] ?? 'status-active';
                $toggleTitle = (strcasecmp($status, 'Active') === 0) ? 'Suspend' : 'Activate';
                $isSuspended = (strcasecmp($status, 'Suspended') === 0);
                $statusIcon = $isSuspended
                  ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                  : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';
                ?>
                <span class="status-badge <?= $cls; ?>">
                  <?= htmlspecialchars($status); ?>
                </span>
              </td>

              <td>
                <div class="action-buttons">
                  <a href="#" class="icon-btn js-toggle-driver-status-local" title="<?= htmlspecialchars($toggleTitle); ?>"
                    data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                    <?= $statusIcon; ?>
                  </a>



                  <a href="#" class="icon-btn icon-btn-edit js-edit-driver" title="Edit"
                    data-driver='<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    </svg>
                  </a>

                  <a href="#" class="icon-btn icon-btn-delete js-del-local" title="Delete"
                    data-driver-id="<?= (int) ($d['private_driver_id'] ?? 0); ?>">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path
                        d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:40px;color:#6B7280;">
              No drivers found. Click "Add New Driver" to add your first driver.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Driver Pagination -->
  <div class="pagination-container" id="drv-pagination-container">
    <div class="pagination-controls">
      <button class="pagination-btn" id="drv-prev-page" disabled>
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        Previous
      </button>
      <div class="pagination-pages" id="drv-pagination-pages"></div>
      <button class="pagination-btn" id="drv-next-page" disabled>
        Next
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>
  </div>
</div>

<!-- Conductor Filter Bar -->
<div class="filter-bar" style="margin-top:24px;">
  <div class="filter-group">
    <label for="cnd-filter-status">Status:</label>
    <select id="cnd-filter-status" class="filter-select">
      <option value="all">All</option>
      <option value="Active">Active</option>
      <option value="Suspended">Suspended</option>
    </select>
  </div>
  <div class="search-container">
    <svg class="search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
      <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" />
      <path d="M12.5 12.5l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
    <input type="text" id="cnd-search" class="search-input" placeholder="Search by name or phone…">
  </div>
</div>

<div class="card">
  <h3 class="card-title">Conductor Registry</h3>

  <div class="table-container">
    <table class="data-table" id="conductors-table">
      <thead>
        <tr>
          <th>Conductor</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!empty($conductors ?? [])): ?>
          <?php foreach ($conductors as $c): ?>
            <?php
            $cndId = (int) ($c['private_conductor_id'] ?? 0);
            $cndLogs = $conductor_logs[$cndId] ?? [];
            ?>
            <tr class="js-profile-row" style="cursor:pointer;" data-type="conductor"
              data-profile='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'
              data-logs='<?= htmlspecialchars(json_encode($cndLogs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
              <td>
                <div class="driver-info">
                  <div class="driver-avatar">
                    <?php
                    $name = (string) ($c['full_name'] ?? '');
                    $parts = preg_split('/\s+/', trim($name));
                    $ini = '';
                    if (!empty($parts[0])) {
                      $ini .= strtoupper(substr($parts[0], 0, 1));
                    }
                    if (count($parts) > 1) {
                      $ini .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
                    }
                    echo htmlspecialchars($ini);
                    ?>
                  </div>
                  <div>
                    <div class="driver-name"><?= htmlspecialchars($c['full_name'] ?? ''); ?></div>
                    <div class="driver-id">CND-<?= (int) ($c['private_conductor_id'] ?? 0); ?></div>
                  </div>
                </div>
              </td>

              <td><?= htmlspecialchars($c['phone'] ?? ''); ?></td>

              <td>
                <?php
                $status = (string) ($c['status'] ?? 'Active');
                $map = ['Active' => 'status-active', 'Suspended' => 'status-suspended'];
                $cls = $map[$status] ?? 'status-active';
                $toggleTitle = (strcasecmp($status, 'Active') === 0) ? 'Suspend' : 'Activate';
                $isSuspended = (strcasecmp($status, 'Suspended') === 0);
                $statusIcon = $isSuspended
                  ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                  : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';
                ?>
                <span class="status-badge <?= $cls; ?>">
                  <?= htmlspecialchars($status); ?>
                </span>
              </td>

              <td>
                <div class="action-buttons">
                  <a href="#" class="icon-btn js-toggle-conductor-status-local"
                    title="<?= htmlspecialchars($toggleTitle); ?>"
                    data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                    <?= $statusIcon; ?>
                  </a>



                  <a href="#" class="icon-btn icon-btn-edit js-edit-conductor" title="Edit"
                    data-conductor='<?= htmlspecialchars(json_encode($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    </svg>
                  </a>

                  <a href="#" class="icon-btn icon-btn-delete js-del-local" title="Delete"
                    data-conductor-id="<?= (int) ($c['private_conductor_id'] ?? 0); ?>">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                      <path
                        d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" style="text-align:center;padding:40px;color:#6B7280;">
              No conductors found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Conductor Pagination -->
  <div class="pagination-container" id="cnd-pagination-container">
    <div class="pagination-controls">
      <button class="pagination-btn" id="cnd-prev-page" disabled>
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        Previous
      </button>
      <div class="pagination-pages" id="cnd-pagination-pages"></div>
      <button class="pagination-btn" id="cnd-next-page" disabled>
        Next
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>
  </div>
</div>

<!-- Add/Edit Modal (handled by inline JS now) -->
<style>
  /* ============================================
   * Profile Detail Modal
   * ============================================ */
  .profile-modal[hidden] {
    display: none !important;
  }

  .profile-modal {
    position: fixed;
    inset: 0;
    z-index: 1000000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }

  .profile-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .50);
    backdrop-filter: blur(2px);
  }

  .profile-modal__panel {
    position: relative;
    width: min(600px, 100%);
    max-height: 88vh;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  /* Header band */
  .profile-modal__hero {
    background: linear-gradient(135deg, #7F0032 0%, #9B1042 100%);
    padding: 28px 28px 22px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-shrink: 0;
  }

  .profile-modal__avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .18);
    border: 2.5px solid rgba(255, 255, 255, .4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    letter-spacing: .5px;
    flex-shrink: 0;
  }

  .profile-modal__hero-info {
    flex: 1;
    min-width: 0;
  }

  .profile-modal__hero-name {
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 3px;
    line-height: 1.2;
  }

  .profile-modal__hero-id {
    font-size: 13px;
    color: rgba(255, 255, 255, .7);
    margin: 0;
    font-weight: 500;
  }

  .profile-modal__close {
    background: rgba(255, 255, 255, .15);
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    color: #fff;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .18s;
  }

  .profile-modal__close:hover {
    background: rgba(255, 255, 255, .30);
  }

  /* Scrollable body */
  .profile-modal__body {
    overflow-y: auto;
    padding: 0;
    flex: 1;
  }

  /* Info section */
  .profile-modal__section {
    padding: 20px 28px;
    border-bottom: 1.5px solid #F5F0D8;
  }

  .profile-modal__section:last-child {
    border-bottom: none;
  }

  .profile-modal__section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #9CA3AF;
    margin: 0 0 14px;
  }

  .profile-modal__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }

  .profile-modal__grid--triple {
    grid-template-columns: 1fr 1fr 1fr;
  }

  .profile-modal__field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .profile-modal__field-label {
    font-size: 11px;
    font-weight: 600;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: .8px;
  }

  .profile-modal__field-value {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
  }

  .profile-modal__field-value--mono {
    font-family: 'Courier New', monospace;
    font-size: 13.5px;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    padding: 4px 10px;
    display: inline-block;
    color: #374151;
  }

  /* Status badge inside modal (reuse existing but enforce sizing) */
  .profile-modal .status-badge {
    font-size: 13px;
    padding: 5px 16px;
  }

  /* Timeline */
  .profile-modal__timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .profile-modal__tl-item {
    display: flex;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px dashed #F0EBD0;
    position: relative;
  }

  .profile-modal__tl-item:last-child {
    border-bottom: none;
  }

  .profile-modal__tl-dot-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 28px;
  }

  .profile-modal__tl-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-top: 3px;
    flex-shrink: 0;
  }

  .profile-modal__tl-dot--suspend {
    background: #F97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, .15);
  }

  .profile-modal__tl-dot--active {
    background: #10B981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, .15);
  }

  .profile-modal__tl-line {
    flex: 1;
    width: 2px;
    background: #F0EBD0;
    margin-top: 4px;
  }

  .profile-modal__tl-content {
    flex: 1;
    min-width: 0;
  }

  .profile-modal__tl-event {
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 2px;
  }

  .profile-modal__tl-event--suspend {
    color: #C2410C;
  }

  .profile-modal__tl-event--active {
    color: #065F46;
  }

  .profile-modal__tl-date {
    font-size: 12px;
    color: #9CA3AF;
    margin: 0 0 6px;
  }

  .profile-modal__tl-reason {
    font-size: 13px;
    color: #4B5563;
    background: #FAFAFA;
    border: 1px solid #F0EBD0;
    border-radius: 8px;
    padding: 8px 12px;
    margin: 0;
    line-height: 1.5;
  }

  .profile-modal__tl-empty {
    font-size: 13px;
    color: #9CA3AF;
    font-style: italic;
    padding: 6px 0;
  }

  /* Stats strip */
  .profile-modal__stats {
    display: flex;
    gap: 0;
  }

  .profile-modal__stat {
    flex: 1;
    text-align: center;
    padding: 14px 10px;
    border-right: 1.5px solid #F5F0D8;
  }

  .profile-modal__stat:last-child {
    border-right: none;
  }

  .profile-modal__stat-num {
    font-size: 22px;
    font-weight: 700;
    color: #7F0032;
    line-height: 1;
  }

  .profile-modal__stat-lbl {
    font-size: 11px;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-top: 4px;
  }

  /* Row hover highlight */
  .js-profile-row:hover {
    background: rgba(127, 0, 50, .04) !important;
    transition: background .15s;
  }

  /* Toast Styles */
  .toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 1000000;
    display: flex;
    align-items: center;
    gap: 12px;
    transform: translateX(120%);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    min-width: 300px;
    border: 1px solid #E5E7EB;
  }

  .toast-notification.show {
    transform: translateX(0);
  }

  .toast-notification.success {
    border-left: 4px solid #10B981;
  }

  .toast-notification.error {
    border-left: 4px solid #EF4444;
  }

  .toast-message {
    font-weight: 500;
    font-size: 14px;
    color: #1F2937;
  }

  /* Ensure [hidden] works on .modal elements whose CSS sets display:flex */
  .modal[hidden] {
    display: none !important;
  }

  /* Driver modal — same spec as bus-modal */
  .drv-modal[hidden] {
    display: none;
  }

  .drv-modal {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .drv-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .45);
  }

  .drv-modal__panel {
    position: relative;
    width: min(560px, 95vw);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
    overflow: hidden;
  }

  .drv-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 24px 24px 0;
  }

  .drv-modal__title {
    font-size: 20px;
    font-weight: 700;
    color: var(--maroon);
    margin: 0 0 4px;
  }

  .drv-modal__subtitle {
    font-size: 13px;
    color: #6B7280;
    margin: 0;
  }

  .drv-modal__close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #9CA3AF;
    line-height: 1;
    padding: 0;
    margin-left: 12px;
  }

  .drv-modal__close:hover {
    color: #374151;
  }

  .drv-modal__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 20px 24px;
  }

  .drv-modal__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .drv-modal__field[hidden] {
    display: none !important;
  }

  .drv-modal__field--full {
    grid-column: 1 / -1;
  }

  .drv-modal__label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
  }

  .drv-modal__input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    font-size: 14px;
    color: #111827;
    box-sizing: border-box;
    transition: border-color .15s;
    font-family: inherit;
  }

  .drv-modal__input:focus {
    outline: none;
    border-color: var(--maroon);
    box-shadow: 0 0 0 3px rgba(127, 0, 50, .08);
  }

  .drv-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 0 24px 24px;
  }

  .drv-modal__btn {
    padding: 10px 22px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: background .18s;
    display: inline-block;
  }

  .drv-modal__btn--cancel {
    background: #F3F4F6;
    color: #374151;
    border: 1px solid #E5E7EB;
  }

  .drv-modal__btn--cancel:hover {
    background: #E5E7EB;
  }

  .drv-modal__btn--submit {
    background: var(--gold);
    color: var(--maroon);
  }

  .drv-modal__btn--submit:hover {
    background: #F59E0B;
  }
</style>

<!-- Toast Element -->
<div id="toastNotification" class="toast-notification">
  <div class="toast-message"></div>
</div>

<div id="driverModalLocal" class="drv-modal" hidden>
  <div class="drv-modal__backdrop"></div>
  <div class="drv-modal__panel">
    <div class="drv-modal__header">
      <div>
        <h2 class="drv-modal__title" id="driverModalTitleLocal">Add New Driver</h2>
        <p class="drv-modal__subtitle" id="driverModalSubtitleLocal">Enter details below</p>
      </div>
      <button type="button" class="drv-modal__close" id="btnCloseDriverModalX" aria-label="Close">&times;</button>
    </div>

    <!-- app.js reads this and includes private_operator_id in POST -->
    <form id="driverFormLocal" action="" method="post" data-operator-id="<?= (int) ($opId ?? 0) ?>">
      <input type="hidden" name="action" id="f_action" value="create">
      <input type="hidden" id="f_id" name="private_driver_id">

      <div class="drv-modal__grid">
        <div class="drv-modal__field">
          <label class="drv-modal__label">Full Name *</label>
          <input type="text" name="full_name" id="f_name" class="drv-modal__input" placeholder="e.g., Kamal Perera"
            required>
        </div>
        <div class="drv-modal__field">
          <label class="drv-modal__label">Phone</label>
          <input type="tel" name="phone" id="f_phone" class="drv-modal__input" placeholder="e.g., 0771234567">
        </div>
        <div class="drv-modal__field" id="f_license_group">
          <label class="drv-modal__label">License Number *</label>
          <input type="text" name="license_no" id="f_license_no" class="drv-modal__input" placeholder="e.g., B1234567"
            required>
        </div>
        <div class="drv-modal__field">
          <label class="drv-modal__label">Status</label>
          <select name="status" id="f_status" class="drv-modal__input">
            <option>Active</option>
            <option>Suspended</option>
          </select>
        </div>

        <div class="drv-modal__field drv-modal__field--full" id="f_reason_group" hidden>
          <label class="drv-modal__label">Reason for Suspension <span style="color:#DC2626;">*</span></label>
          <textarea name="suspend_reason" id="f_reason" rows="3" class="drv-modal__input"
            placeholder="Enter reason for suspension…" style="resize:vertical;min-height:76px;"></textarea>
          <p id="f_reason_error" style="color:#DC2626;font-size:12px;margin:2px 0 0;display:none;">Please enter a reason
            for suspension.</p>
        </div>
      </div>

      <div class="drv-modal__footer">
        <a href="#" id="btnCancelModalLocal" class="drv-modal__btn drv-modal__btn--cancel">Cancel</a>
        <button type="submit" class="drv-modal__btn drv-modal__btn--submit" id="btnSubmitModalLocal">Add Driver</button>
      </div>
    </form>
  </div>
</div>

<div id="conductorModalLocal" class="drv-modal" hidden>
  <div class="drv-modal__backdrop"></div>
  <div class="drv-modal__panel">
    <div class="drv-modal__header">
      <div>
        <h2 class="drv-modal__title" id="conductorModalTitleLocal">Add New Conductor</h2>
        <p class="drv-modal__subtitle" id="conductorModalSubtitleLocal">Enter details below</p>
      </div>
      <button type="button" class="drv-modal__close" id="btnCloseConductorModalX" aria-label="Close">&times;</button>
    </div>

    <form id="conductorFormLocal" action="" method="post" data-operator-id="<?= (int) ($opId ?? 0) ?>">
      <input type="hidden" name="action" id="fc_action" value="create_conductor">
      <input type="hidden" id="fc_id" name="private_conductor_id">

      <div class="drv-modal__grid">
        <div class="drv-modal__field">
          <label class="drv-modal__label">Full Name *</label>
          <input type="text" name="full_name" id="fc_name" class="drv-modal__input" placeholder="e.g., Nimal Perera"
            required>
        </div>
        <div class="drv-modal__field">
          <label class="drv-modal__label">Phone</label>
          <input type="tel" name="phone" id="fc_phone" class="drv-modal__input" placeholder="e.g., 0771234567">
        </div>

        <div class="drv-modal__field">
          <label class="drv-modal__label">Status</label>
          <select name="status" id="fc_status" class="drv-modal__input">
            <option>Active</option>
            <option>Suspended</option>
          </select>
        </div>

        <div class="drv-modal__field drv-modal__field--full" id="fc_reason_group" hidden>
          <label class="drv-modal__label">Reason for Suspension <span style="color:#DC2626;">*</span></label>
          <textarea name="suspend_reason" id="fc_reason" rows="3" class="drv-modal__input"
            placeholder="Enter reason for suspension..." style="resize:vertical;min-height:76px;"></textarea>
          <p id="fc_reason_error" style="color:#DC2626;font-size:12px;margin:2px 0 0;display:none;">Please enter a
            reason for suspension.</p>
        </div>
      </div>

      <div class="drv-modal__footer">
        <a href="#" id="btnCancelConductorModalLocal" class="drv-modal__btn drv-modal__btn--cancel">Cancel</a>
        <button type="submit" class="drv-modal__btn drv-modal__btn--submit" id="btnSubmitConductorModalLocal">Add
          Conductor</button>
      </div>
    </form>
  </div>
</div>

<!-- Status Confirmation Modal -->
<div id="statusConfirmModal" class="modal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width: 440px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <span id="statusModalTitle">Change Status</span>
      </h3>
      <button type="button" class="modal__close" id="btnCloseStatus">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 10px;">
      <p style="color: #4B5563; font-size: 15px; margin: 0 0 14px;" id="statusModalMsg">Are you sure?</p>
      <!-- Reason field — shown only when suspending -->
      <div id="suspendReasonWrap" hidden style="margin-top: 4px;">
        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Reason <span
            style="color:#DC2626;">*</span></label>
        <textarea id="suspendReasonInput" rows="3" placeholder="Enter reason for suspension…"
          style="width:100%; box-sizing:border-box; border:1.5px solid #E5E7EB; border-radius:8px; padding:8px 10px; font-size:14px; color:#1F2937; resize:vertical; outline:none; font-family:inherit;"
          oninput="this.style.borderColor=this.value.trim()?'#E5E7EB':'#DC2626';"></textarea>
        <p id="suspendReasonError" style="color:#DC2626; font-size:12px; margin:4px 0 0; display:none;">Please enter a
          reason for suspension.</p>
      </div>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCancelStatus"
        style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
      <button type="button" class="btn-primary" id="btnConfirmStatus"
        style="background: #DC2626; border: none; color: white;">Confirm</button>
    </div>
  </div>
</div>

<!-- View Suspend Reason Modal -->
<div id="viewReasonModal" class="modal" hidden>
  <div class="modal__backdrop" id="viewReasonBackdrop"></div>
  <div class="modal__dialog" style="max-width: 420px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #92400E; display: flex; align-items: center; gap: 10px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
        <span id="viewReasonTitle">Suspend Reason</span>
      </h3>
      <button type="button" class="modal__close" id="btnCloseViewReason">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 8px;">
      <p style="font-size:13px; color:#6B7280; margin: 0 0 8px;">Reason recorded for suspension:</p>
      <div id="viewReasonText"
        style="background:#FEF3C7; border:1.5px solid #FCD34D; border-radius:8px; padding:12px 14px; font-size:14px; color:#78350F; white-space:pre-wrap; word-break:break-word;">
      </div>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FFFBEB; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCloseViewReason2"
        style="background: white; border: 1px solid #E5E7EB;">Close</button>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width: 400px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span id="deleteModalTitle">Delete Record</span>
      </h3>
      <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 10px;">
      <p style="color: #4B5563; font-size: 15px; margin: 0;" id="deleteModalMsg">Are you sure you want to delete this
        record? This action cannot be undone.</p>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCancelDelete"
        style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
      <button type="button" class="btn-primary" id="btnConfirmDelete"
        style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
    </div>
  </div>
</div>

<!-- ============================================================
     PROFILE DETAIL MODAL
     ============================================================ -->
<div id="profileDetailModal" class="profile-modal" hidden>
  <div class="profile-modal__backdrop" id="profileModalBackdrop"></div>
  <div class="profile-modal__panel">

    <!-- Hero header -->
    <div class="profile-modal__hero">
      <div class="profile-modal__avatar" id="pmAvatar">?</div>
      <div class="profile-modal__hero-info">
        <h2 class="profile-modal__hero-name" id="pmName">—</h2>
        <p class="profile-modal__hero-id" id="pmAssignedId">—</p>
      </div>
      <button type="button" class="profile-modal__close" id="btnCloseProfileModal" aria-label="Close profile">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    </div>

    <!-- Scrollable body -->
    <div class="profile-modal__body">

      <!-- Basic Details -->
      <div class="profile-modal__section">
        <p class="profile-modal__section-title">Basic Details</p>
        <div class="profile-modal__grid">
          <div class="profile-modal__field">
            <span class="profile-modal__field-label">Full Name</span>
            <span class="profile-modal__field-value" id="pmFullName">—</span>
          </div>
          <div class="profile-modal__field">
            <span class="profile-modal__field-label">Phone Number</span>
            <span class="profile-modal__field-value" id="pmPhone">—</span>
          </div>
        </div>
      </div>

      <!-- Credentials (License – drivers only) -->
      <div class="profile-modal__section" id="pmCredentialsSection">
        <p class="profile-modal__section-title">Credentials</p>
        <div class="profile-modal__grid">
          <div class="profile-modal__field" id="pmLicenseField">
            <span class="profile-modal__field-label">License Number</span>
            <span class="profile-modal__field-value profile-modal__field-value--mono" id="pmLicense">—</span>
          </div>
        </div>
      </div>

      <!-- Current Status -->
      <div class="profile-modal__section">
        <p class="profile-modal__section-title">Current Status</p>
        <div style="display:flex; align-items:center; gap:16px;">
          <span class="status-badge" id="pmStatusBadge">Active</span>
          <span style="font-size:13px; color:#6B7280;" id="pmStatusNote"></span>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="profile-modal__section" style="padding:0;">
        <div class="profile-modal__stats">
          <div class="profile-modal__stat">
            <div class="profile-modal__stat-num" id="pmSuspendCount">0</div>
            <div class="profile-modal__stat-lbl">Times Suspended</div>
          </div>
          <div class="profile-modal__stat">
            <div class="profile-modal__stat-num" id="pmCurrentStatus">—</div>
            <div class="profile-modal__stat-lbl">Current Status</div>
          </div>
          <div class="profile-modal__stat">
            <div class="profile-modal__stat-num" id="pmHistoryCount">0</div>
            <div class="profile-modal__stat-lbl">History Events</div>
          </div>
        </div>
      </div>

      <!-- History & Logs -->
      <div class="profile-modal__section">
        <p class="profile-modal__section-title">History &amp; Logs</p>
        <div class="profile-modal__timeline" id="pmTimeline"></div>
      </div>

    </div><!-- /.profile-modal__body -->
  </div><!-- /.profile-modal__panel -->
</div><!-- /#profileDetailModal -->

<script>
  // View Suspend Reason handler
  (function () {
    const modal = document.getElementById('viewReasonModal');
    const title = document.getElementById('viewReasonTitle');
    const text = document.getElementById('viewReasonText');
    const backdrop = document.getElementById('viewReasonBackdrop');

    function close() { modal.setAttribute('hidden', ''); }

    document.getElementById('btnCloseViewReason').addEventListener('click', close);
    document.getElementById('btnCloseViewReason2').addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);

    document.querySelectorAll('.js-view-reason').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const name = this.dataset.name || '';
        const reason = this.dataset.reason || '';
        title.textContent = 'Suspend Reason — ' + name;
        text.textContent = reason;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.removeAttribute('hidden');
      });
    });
  })();
</script>

<script>
  // Driver/Conductor delete handler
  (function () {
    let deleteType = null; // 'driver' or 'conductor'
    let deleteId = null;
    const deleteModal = document.getElementById('deleteConfirmModal');
    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    const btnCancelDelete = document.getElementById('btnCancelDelete');
    const btnCloseDelete = document.getElementById('btnCloseDelete');
    const modalTitle = document.getElementById('deleteModalTitle');
    const modalMsg = document.getElementById('deleteModalMsg');

    function closeDeleteModal() {
      deleteModal.setAttribute('hidden', '');
      deleteId = null;
      deleteType = null;
    }

    if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
    if (btnCloseDelete) btnCloseDelete.addEventListener('click', closeDeleteModal);

    const deleteBackdrop = deleteModal?.querySelector('.modal__backdrop');
    if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDeleteModal);

    if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDeleteModal);

    document.querySelectorAll('.js-del-local').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        const driverId = this.getAttribute('data-driver-id');
        const conductorId = this.getAttribute('data-conductor-id');

        if (driverId) {
          deleteType = 'driver';
          deleteId = driverId;
          modalTitle.textContent = 'Delete Driver';
          modalMsg.textContent = 'Are you sure you want to delete this driver? This action cannot be undone.';
        } else if (conductorId) {
          deleteType = 'conductor';
          deleteId = conductorId;
          modalTitle.textContent = 'Delete Conductor';
          modalMsg.textContent = 'Are you sure you want to delete this conductor? This action cannot be undone.';
        } else {
          return;
        }

        if (deleteModal && deleteModal.parentElement !== document.body) {
          document.body.appendChild(deleteModal);
        }
        deleteModal.removeAttribute('hidden');
      });
    });

    if (btnConfirmDelete) {
      btnConfirmDelete.addEventListener('click', function () {
        if (!deleteId || !deleteType) return;

        const originalText = btnConfirmDelete.textContent;
        btnConfirmDelete.textContent = 'Deleting...';
        btnConfirmDelete.disabled = true;

        // Submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';

        if (deleteType === 'driver') {
          actionInput.value = 'delete';
          idInput.name = 'driver_id';
        } else {
          actionInput.value = 'delete_conductor';
          idInput.name = 'conductor_id';
        }
        idInput.value = deleteId;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
      });
    }
  })();

  // Status Toggle Handler
  (function () {
    let targetData = null;
    let targetType = null; // 'driver' or 'conductor'
    let nextStatus = null;

    const modal = document.getElementById('statusConfirmModal');
    const btnConfirm = document.getElementById('btnConfirmStatus');
    const btnCancel = document.getElementById('btnCancelStatus');
    const btnClose = document.getElementById('btnCloseStatus');
    const modalTitle = document.getElementById('statusModalTitle');
    const modalMsg = document.getElementById('statusModalMsg');
    const reasonWrap = document.getElementById('suspendReasonWrap');
    const reasonInput = document.getElementById('suspendReasonInput');
    const reasonError = document.getElementById('suspendReasonError');

    function showReasonField(show) {
      if (show) {
        reasonWrap.removeAttribute('hidden');
        reasonInput.value = '';
        reasonInput.style.borderColor = '#E5E7EB';
        reasonError.style.display = 'none';
      } else {
        reasonWrap.setAttribute('hidden', '');
        reasonInput.value = '';
      }
    }

    function closeModal() {
      modal.setAttribute('hidden', '');
      targetData = null;
      targetType = null;
      nextStatus = null;
      showReasonField(false);
      if (btnConfirm) {
        btnConfirm.textContent = 'Confirm';
        btnConfirm.disabled = false;
      }
    }

    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    const backdrop = modal?.querySelector('.modal__backdrop');
    if (backdrop) backdrop.addEventListener('click', closeModal);

    // Driver Status
    document.querySelectorAll('.js-toggle-driver-status-local').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        try {
          targetData = JSON.parse(this.dataset.driver || '{}');
          targetType = 'driver';
          const cur = (targetData.status || 'Active').toLowerCase();
          nextStatus = (cur === 'suspended') ? 'Active' : 'Suspended';

          modalTitle.textContent = (nextStatus === 'Suspended') ? 'Suspend Driver' : 'Activate Driver';
          modalMsg.textContent = (nextStatus === 'Suspended')
            ? `Are you sure you want to suspend ${targetData.full_name}? They will not be able to operate.`
            : `Are you sure you want to activate ${targetData.full_name}?`;
          showReasonField(nextStatus === 'Suspended');
          if (nextStatus === 'Suspended' && targetData.suspend_reason) {
            reasonInput.value = targetData.suspend_reason;
          }
          // Move to body and show
          if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
          }
          modal.removeAttribute('hidden');
        } catch (err) { console.error(err); }
      });
    });

    // Conductor Status
    document.querySelectorAll('.js-toggle-conductor-status-local').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        try {
          targetData = JSON.parse(this.dataset.conductor || '{}');
          targetType = 'conductor';
          const cur = (targetData.status || 'Active').toLowerCase();
          nextStatus = (cur === 'suspended') ? 'Active' : 'Suspended';

          modalTitle.textContent = (nextStatus === 'Suspended') ? 'Suspend Conductor' : 'Activate Conductor';
          modalMsg.textContent = (nextStatus === 'Suspended')
            ? `Are you sure you want to suspend ${targetData.full_name}? They will not be able to operate.`
            : `Are you sure you want to activate ${targetData.full_name}?`;
          showReasonField(nextStatus === 'Suspended');
          if (nextStatus === 'Suspended' && targetData.suspend_reason) {
            reasonInput.value = targetData.suspend_reason;
          }
          // Move to body and show
          if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
          }
          modal.removeAttribute('hidden');
        } catch (err) { console.error(err); }
      });
    });

    // Confirm Action
    if (btnConfirm) {
      btnConfirm.addEventListener('click', function () {
        if (!targetData || !targetType || !nextStatus) return;

        btnConfirm.textContent = 'Saving...';
        btnConfirm.disabled = true;

        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '';

        const add = (n, v) => {
          const i = document.createElement('input');
          i.type = 'hidden'; i.name = n; i.value = (v == null ? '' : String(v));
          f.appendChild(i);
        };

        // Validate reason when suspending
        if (nextStatus === 'Suspended') {
          const reason = reasonInput ? reasonInput.value.trim() : '';
          if (!reason) {
            reasonInput.style.borderColor = '#DC2626';
            reasonError.style.display = 'block';
            btnConfirm.textContent = 'Confirm';
            btnConfirm.disabled = false;
            return;
          }
        }

        if (targetType === 'driver') {
          add('action', 'update');
          add('private_driver_id', targetData.private_driver_id || targetData.id || '');
          add('full_name', targetData.full_name || '');
          add('license_no', targetData.license_no || '');
          add('phone', targetData.phone || '');
          add('status', nextStatus);
          add('suspend_reason', nextStatus === 'Suspended' ? (reasonInput ? reasonInput.value.trim() : '') : '');
        } else {
          add('action', 'update_conductor');
          add('private_conductor_id', targetData.private_conductor_id || targetData.id || '');
          add('full_name', targetData.full_name || '');
          add('phone', targetData.phone || '');
          add('status', nextStatus);
          add('suspend_reason', nextStatus === 'Suspended' ? (reasonInput ? reasonInput.value.trim() : '') : '');
        }

        document.body.appendChild(f);
        f.submit();
      });
    }
  })();
</script>

<script>
  // Add/Edit Driver handler
  (function () {
    const driverModal = document.getElementById('driverModalLocal');
    const driverForm = document.getElementById('driverFormLocal');
    const btnAddDriver = document.getElementById('btnAddDriverLocal');
    const btnCancel = document.getElementById('btnCancelModalLocal');
    const modalTitle = document.getElementById('driverModalTitleLocal');
    const btnSubmit = document.getElementById('btnSubmitModalLocal');

    // Form fields
    const actionInput = document.getElementById('f_action');
    const idInput = document.getElementById('f_id');
    const nameInput = document.getElementById('f_name');
    const phoneInput = document.getElementById('f_phone');
    const licenseInput = document.getElementById('f_license_no');
    const licenseGroup = document.getElementById('f_license_group');
    const statusInput = document.getElementById('f_status');
    const reasonGroup = document.getElementById('f_reason_group');
    const reasonInput = document.getElementById('f_reason');
    const reasonError = document.getElementById('f_reason_error');

    function syncReasonField() {
      const isSuspended = statusInput && statusInput.value === 'Suspended';
      if (reasonGroup) {
        if (isSuspended) {
          reasonGroup.removeAttribute('hidden');
          if (reasonInput) reasonInput.required = true;
        } else {
          reasonGroup.setAttribute('hidden', '');
          if (reasonInput) { reasonInput.required = false; reasonInput.value = ''; }
          if (reasonError) reasonError.style.display = 'none';
        }
      }
    }

    if (statusInput) statusInput.addEventListener('change', syncReasonField);

    function openModal() {
      if (driverModal && driverModal.parentElement !== document.body) {
        document.body.appendChild(driverModal);
      }
      driverModal.removeAttribute('hidden');
    }

    function closeModal() {
      driverModal.setAttribute('hidden', '');
      driverForm.reset();
      if (reasonGroup) reasonGroup.setAttribute('hidden', '');
      if (reasonInput) { reasonInput.required = false; reasonInput.value = ''; }
      if (reasonError) reasonError.style.display = 'none';
    }

    if (btnCancel) btnCancel.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
    const btnCloseX = document.getElementById('btnCloseDriverModalX');
    if (btnCloseX) btnCloseX.addEventListener('click', closeModal);
    const backdrop = driverModal?.querySelector('.drv-modal__backdrop');
    if (backdrop) backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && driverModal && !driverModal.hasAttribute('hidden')) closeModal(); });

    // Add Driver
    if (btnAddDriver) {
      btnAddDriver.addEventListener('click', (e) => {
        e.preventDefault();
        driverForm.reset();
        modalTitle.textContent = 'Add New Driver';
        btnSubmit.textContent = 'Add Driver';

        actionInput.value = 'create';
        idInput.name = 'private_driver_id';
        idInput.value = '';

        // Show License
        licenseGroup.style.display = 'flex';
        licenseInput.required = true;
        licenseInput.disabled = false;

        syncReasonField();
        openModal();
      });
    }


    document.querySelectorAll('.js-edit-driver').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const data = JSON.parse(this.dataset.driver || '{}');

        modalTitle.textContent = 'Edit Driver';
        btnSubmit.textContent = 'Update Driver';
        actionInput.value = 'update';
        idInput.name = 'private_driver_id';
        idInput.value = data.private_driver_id || data.id || '';

        nameInput.value = data.full_name || '';
        phoneInput.value = data.phone || '';
        licenseInput.value = data.license_no || '';
        statusInput.value = data.status || 'Active';
        if (reasonInput) reasonInput.value = data.suspend_reason || '';
        syncReasonField();

        licenseGroup.style.display = 'flex';
        licenseInput.required = true;
        licenseInput.disabled = false;

        openModal();
      });
    });

  })();
</script>

<script>
  // Add/Edit Conductor handler
  (function () {
    const conductorModal = document.getElementById('conductorModalLocal');
    const conductorForm = document.getElementById('conductorFormLocal');
    const btnAddConductor = document.getElementById('btnAddConductorLocal');
    const btnCancelConductor = document.getElementById('btnCancelConductorModalLocal');
    const btnCloseConductor = document.getElementById('btnCloseConductorModalX');
    const modalTitle = document.getElementById('conductorModalTitleLocal');
    const btnSubmit = document.getElementById('btnSubmitConductorModalLocal');

    const actionInput = document.getElementById('fc_action');
    const idInput = document.getElementById('fc_id');
    const nameInput = document.getElementById('fc_name');
    const phoneInput = document.getElementById('fc_phone');
    const statusInput = document.getElementById('fc_status');
    const reasonGroup = document.getElementById('fc_reason_group');
    const reasonInput = document.getElementById('fc_reason');
    const reasonError = document.getElementById('fc_reason_error');

    function syncReasonField() {
      const isSuspended = statusInput && statusInput.value === 'Suspended';
      if (!reasonGroup) return;
      if (isSuspended) {
        reasonGroup.removeAttribute('hidden');
        if (reasonInput) reasonInput.required = true;
      } else {
        reasonGroup.setAttribute('hidden', '');
        if (reasonInput) { reasonInput.required = false; reasonInput.value = ''; }
        if (reasonError) reasonError.style.display = 'none';
      }
    }

    if (statusInput) statusInput.addEventListener('change', syncReasonField);

    function openModal() {
      if (conductorModal && conductorModal.parentElement !== document.body) {
        document.body.appendChild(conductorModal);
      }
      conductorModal.removeAttribute('hidden');
    }

    function closeModal() {
      conductorModal.setAttribute('hidden', '');
      conductorForm.reset();
      if (reasonGroup) reasonGroup.setAttribute('hidden', '');
      if (reasonInput) { reasonInput.required = false; reasonInput.value = ''; }
      if (reasonError) reasonError.style.display = 'none';
    }

    if (btnCancelConductor) btnCancelConductor.addEventListener('click', function (e) { e.preventDefault(); closeModal(); });
    if (btnCloseConductor) btnCloseConductor.addEventListener('click', closeModal);

    const backdrop = conductorModal?.querySelector('.drv-modal__backdrop');
    if (backdrop) backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && conductorModal && !conductorModal.hasAttribute('hidden')) closeModal();
    });

    if (btnAddConductor) {
      btnAddConductor.addEventListener('click', function (e) {
        e.preventDefault();
        conductorForm.reset();
        modalTitle.textContent = 'Add New Conductor';
        btnSubmit.textContent = 'Add Conductor';

        actionInput.value = 'create_conductor';
        idInput.name = 'private_conductor_id';
        idInput.value = '';

        syncReasonField();
        openModal();
      });
    }

    document.querySelectorAll('.js-edit-conductor').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const data = JSON.parse(this.dataset.conductor || '{}');

        modalTitle.textContent = 'Edit Conductor';
        btnSubmit.textContent = 'Update Conductor';
        actionInput.value = 'update_conductor';
        idInput.name = 'private_conductor_id';
        idInput.value = data.private_conductor_id || data.id || '';

        nameInput.value = data.full_name || '';
        phoneInput.value = data.phone || '';
        statusInput.value = data.status || 'Active';
        if (reasonInput) reasonInput.value = data.suspend_reason || '';
        syncReasonField();

        openModal();
      });
    });

    if (conductorForm) {
      conductorForm.addEventListener('submit', function (e) {
        const isSuspended = statusInput && statusInput.value === 'Suspended';
        const reason = reasonInput ? reasonInput.value.trim() : '';
        if (isSuspended && !reason) {
          e.preventDefault();
          if (reasonError) reasonError.style.display = 'block';
          if (reasonInput) reasonInput.style.borderColor = '#DC2626';
        }
      });
    }

  })();

  // Toast Notification Logic
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toastNotification');
    if (!toast) return;

    const msgEl = toast.querySelector('.toast-message');
    msgEl.textContent = message;

    toast.className = 'toast-notification ' + type;

    // Move to body to ensure visibility
    if (toast.parentElement !== document.body) {
      document.body.appendChild(toast);
    }

    // Trigger reflow
    void toast.offsetWidth;

    toast.classList.add('show');

    setTimeout(() => {
      toast.classList.remove('show');
    }, 4000);
  }

  // Check for messages from URL
  document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
      if (msg === 'created') showToast('Record added successfully', 'success');
      else if (msg === 'updated') showToast('Record updated successfully', 'success');
      else if (msg === 'deleted') showToast('Record deleted successfully', 'success');
      else if (msg === 'conductor_created') showToast('Conductor added successfully', 'success');
      else if (msg === 'conductor_updated') showToast('Conductor updated successfully', 'success');
      else if (msg === 'conductor_deleted') showToast('Conductor deleted successfully', 'success');
      else if (msg === 'error') showToast('Operation failed. A record with this specific info (e.g. License) already exists.', 'error');

      // Clean URL
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    }
  });
</script>

<script>
  /* ── Name auto-capitalise + field validation ──────────────────────
     Applies to ALL name inputs on this page (driver & conductor forms).
     Rules:
       • Full Name   : letters, spaces, hyphens, apostrophes only;
                       each word auto-capitalised as you type
       • Phone       : digits, spaces, +, hyphens only; 7-15 digits
       • License No  : required; alphanumeric / hyphens
  ─────────────────────────────────────────────────────────────────── */
  (function () {
    'use strict';

    /* ── Utility: Title-case a string ──────────────────────────────
       "JOHN o'NEIL-smith" → "John O'Neil-Smith"                      */
    function toTitleCase(str) {
      return str.replace(/([^\s\-']+)/g, function (word) {
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
      });
    }

    /* ── Show / hide inline error below an input ───────────────── */
    function setError(input, msg) {
      let err = input.parentElement.querySelector('.val-error');
      if (!err) {
        err = document.createElement('p');
        err.className = 'val-error';
        err.style.cssText = 'color:#DC2626;font-size:11px;margin:3px 0 0;';
        input.parentElement.appendChild(err);
      }
      if (msg) {
        err.textContent = msg;
        err.style.display = 'block';
        input.style.borderColor = '#DC2626';
      } else {
        err.style.display = 'none';
        input.style.borderColor = '';
      }
    }

    /* ── Validators ─────────────────────────────────────────────── */
    function validateName(input) {
      const v = input.value.trim();
      if (v === '') { setError(input, 'Full name is required.'); return false; }
      if (!/^[A-Za-z\s\-'\.]+$/.test(v)) {
        setError(input, 'Name may only contain letters, spaces, hyphens, or apostrophes.');
        return false;
      }
      if (v.split(/\s+/).filter(Boolean).length < 1) {
        setError(input, 'Please enter at least a first name.'); return false;
      }
      setError(input, ''); return true;
    }

    function validatePhone(input) {
      const v = input.value.trim();
      if (v === '') { setError(input, ''); return true; } // optional
      // Sri Lankan phone: 0XXXXXXXXX, +94XXXXXXXXX, or 94XXXXXXXXX (10 or 12 digits)
      const clean = v.replace(/[\s\-]/g, '');
      if (!/^(?:\+94|94|0)[1-9]\d{8}$/.test(clean)) {
        setError(input, 'Enter a valid Sri Lankan phone number (e.g. 0771234567 or +94771234567).');
        return false;
      }
      setError(input, ''); return true;
    }

    function validateLicense(input) {
      if (!input || input.disabled || input.closest('[hidden]') !== null || input.offsetParent === null) { return true; }
      const v = input.value.trim();
      if (v === '') { setError(input, 'License number is required.'); return false; }
      if (!/^[A-Za-z0-9\-\/]+$/.test(v)) {
        setError(input, 'License may only contain letters, numbers, and hyphens.'); return false;
      }
      setError(input, ''); return true;
    }

    /* ── Wire up a single form ──────────────────────────────────── */
    function wireForm(formEl, nameId, phoneId, licenseId) {
      if (!formEl) return;

      const nameInput = formEl.querySelector('#' + nameId);
      const phoneInput = phoneId ? formEl.querySelector('#' + phoneId) : null;
      const licenseInput = licenseId ? formEl.querySelector('#' + licenseId) : null;

      /* Auto-capitalise name while typing */
      if (nameInput) {
        nameInput.addEventListener('input', function () {
          const pos = this.selectionStart;
          this.value = toTitleCase(this.value);
          this.setSelectionRange(pos, pos);
          validateName(this);
        });
        nameInput.addEventListener('blur', function () { validateName(this); });
      }

      if (phoneInput) {
        phoneInput.addEventListener('input', function () { validatePhone(this); });
        phoneInput.addEventListener('blur', function () { validatePhone(this); });
      }

      if (licenseInput) {
        licenseInput.addEventListener('input', function () {
          // Auto-uppercase license
          const pos = this.selectionStart;
          this.value = this.value.toUpperCase();
          this.setSelectionRange(pos, pos);
          validateLicense(this);
        });
        licenseInput.addEventListener('blur', function () { validateLicense(this); });
      }

      /* Block submit if invalid */
      formEl.addEventListener('submit', function (e) {
        let ok = true;
        if (nameInput && !validateName(nameInput)) ok = false;
        if (phoneInput && !validatePhone(phoneInput)) ok = false;
        if (licenseInput && !validateLicense(licenseInput)) ok = false;
        if (!ok) e.preventDefault();
      });
    }

    /* ── Wire driver form (id="driverFormLocal") ────────────────── */
    wireForm(
      document.getElementById('driverFormLocal'),
      'f_name', 'f_phone', 'f_license_no'
    );

    /* ── Wire conductor form (id="conductorFormLocal") ──────────── */
    wireForm(
      document.getElementById('conductorFormLocal'),
      'fc_name', 'fc_phone', null
    );

  })();
</script>
<script>
  /* ================================================================
     PROFILE DETAIL MODAL — Row-click handler
     ================================================================ */
  (function () {
    const modal = document.getElementById('profileDetailModal');
    const backdrop = document.getElementById('profileModalBackdrop');
    const btnClose = document.getElementById('btnCloseProfileModal');

    // Elements to populate
    const elAvatar = document.getElementById('pmAvatar');
    const elName = document.getElementById('pmName');
    const elAssignedId = document.getElementById('pmAssignedId');
    const elFullName = document.getElementById('pmFullName');
    const elPhone = document.getElementById('pmPhone');
    const elLicenseField = document.getElementById('pmLicenseField');
    const elLicense = document.getElementById('pmLicense');
    const elCredSection = document.getElementById('pmCredentialsSection');
    const elStatusBadge = document.getElementById('pmStatusBadge');
    const elStatusNote = document.getElementById('pmStatusNote');
    const elSuspendCount = document.getElementById('pmSuspendCount');
    const elCurrentSt = document.getElementById('pmCurrentStatus');
    const elHistoryCount = document.getElementById('pmHistoryCount');
    const elTimeline = document.getElementById('pmTimeline');

    // Helper: initials from full name
    function initials(name) {
      var parts = (name || '').trim().split(/\s+/).filter(Boolean);
      if (!parts.length) return '?';
      var ini = parts[0][0].toUpperCase();
      if (parts.length > 1) ini += parts[parts.length - 1][0].toUpperCase();
      return ini;
    }

    // Escape for safe HTML insertion
    function esc(str) {
      return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /*
     * buildTimeline(logs, data, type)
     *
     * logs  – array of status-log rows from private_staff_status_logs
     *         (each: { old_status, new_status, reason, changed_at_fmt })
     *         ordered newest-first from the server.
     * data  – the raw driver/conductor row.
     * type  – 'driver' | 'conductor'
     *
     * Strategy:
     *  - If logs table has entries, show every log entry.
     *  - Always append a "Registered" baseline at the bottom.
     *  - If no log entries exist yet (pre-migration staff), fall back to
     *    inferring a single event from the current status / suspend_reason.
     */
    function buildTimeline(logs, data, type) {
      var html = '';
      var events = [];  // [{evType:'suspend'|'active', label, date, reason}]
      var currentStatus = (data.status || 'Active').toLowerCase();
      var currentReason = (data.suspend_reason || '').trim();

      if (logs && logs.length > 0) {
        // ── Real log data ──────────────────────────────────────────
        logs.forEach(function (row) {
          var toSuspended = (row.new_status || '').toLowerCase() === 'suspended';
          events.push({
            evType: toSuspended ? 'suspend' : 'active',
            label: toSuspended ? 'Suspended' : 'Reactivated',
            date: row.changed_at_fmt || null,
            reason: row.reason || ''
          });
        });

        // If staff is currently suspended, prefer the latest saved reason from main row
        // so profile timeline stays consistent after editing reason without status change.
        if (currentStatus === 'suspended' && currentReason) {
          var firstSuspendIdx = events.findIndex(function (ev) { return ev.evType === 'suspend'; });
          if (firstSuspendIdx >= 0) {
            events[firstSuspendIdx].reason = currentReason;
          } else {
            events.unshift({ evType: 'suspend', label: 'Suspended', date: null, reason: currentReason });
          }
        }
      } else {
        // ── Fallback: infer from current status/reason ─────────────
        var status = (data.status || 'Active');
        var reason = data.suspend_reason || '';
        var isSuspended = status.toLowerCase() === 'suspended';

        if (isSuspended && reason) {
          events.push({ evType: 'suspend', label: 'Suspended', date: null, reason: reason });
        } else if (!isSuspended && reason) {
          events.push({
            evType: 'active', label: 'Reactivated', date: null,
            reason: 'Previously suspended. Reason: ' + reason
          });
        }
      }

      // Always add a Registered baseline at the end (oldest event)
      events.push({
        evType: 'active',
        label: 'Registered',
        date: null,
        reason: (type === 'driver' ? 'Driver' : 'Conductor') + ' registered and set to Active.'
      });

      if (events.length === 0) {
        return '<p class="profile-modal__tl-empty">No history events recorded.</p>';
      }

      events.forEach(function (ev, idx) {
        var isSusp = ev.evType === 'suspend';
        var dotCls = isSusp ? 'profile-modal__tl-dot--suspend' : 'profile-modal__tl-dot--active';
        var evCls = isSusp ? 'profile-modal__tl-event--suspend' : 'profile-modal__tl-event--active';
        var dateStr = ev.date ? '<p class="profile-modal__tl-date">' + esc(ev.date) + '</p>' : '';
        var showLine = (idx < events.length - 1);
        html += [
          '<div class="profile-modal__tl-item">',
          '<div class="profile-modal__tl-dot-wrap">',
          '<div class="profile-modal__tl-dot ' + dotCls + '"></div>',
          showLine ? '<div class="profile-modal__tl-line"></div>' : '',
          '</div>',
          '<div class="profile-modal__tl-content">',
          '<p class="profile-modal__tl-event ' + evCls + '">' + esc(ev.label) + '</p>',
          dateStr,
          ev.reason
            ? '<p class="profile-modal__tl-reason">' + esc(ev.reason) + '</p>'
            : '',
          '</div>',
          '</div>'
        ].join('');
      });

      return html;
    }

    function openProfile(data, logs, type) {
      var status = (data.status || 'Active');
      var isSuspended = status.toLowerCase() === 'suspended';
      var assignedId = type === 'driver'
        ? 'DRV-' + (data.private_driver_id || '')
        : 'CND-' + (data.private_conductor_id || '');

      // Hero
      elAvatar.textContent = initials(data.full_name);
      elName.textContent = data.full_name || '—';
      elAssignedId.textContent = assignedId;

      // Basic details
      elFullName.textContent = data.full_name || '—';
      elPhone.textContent = data.phone || '—';

      // Credentials — license only for drivers
      if (type === 'driver') {
        elCredSection.removeAttribute('hidden');
        elLicenseField.style.display = '';
        elLicense.textContent = data.license_no || 'N/A';
      } else {
        elCredSection.setAttribute('hidden', '');
      }

      // Status badge
      elStatusBadge.textContent = status;
      elStatusBadge.className = 'status-badge ' + (isSuspended ? 'status-suspended' : 'status-active');
      elStatusNote.textContent = isSuspended && data.suspend_reason
        ? 'Reason: ' + data.suspend_reason
        : '';

      // Quick stats — count from real logs
      var suspendCount = 0;
      if (logs && logs.length > 0) {
        logs.forEach(function (row) {
          if ((row.new_status || '').toLowerCase() === 'suspended') suspendCount++;
        });
      } else {
        // Fallback
        suspendCount = isSuspended ? 1 : (data.suspend_reason ? 1 : 0);
      }
      var historyCount = (logs && logs.length > 0) ? logs.length + 1 : suspendCount + 1;
      elSuspendCount.textContent = suspendCount;
      elCurrentSt.textContent = status;
      elCurrentSt.style.color = isSuspended ? '#F97316' : '#10B981';
      elHistoryCount.textContent = historyCount;

      // Timeline
      elTimeline.innerHTML = buildTimeline(logs, data, type);

      // Show modal
      if (modal.parentElement !== document.body) document.body.appendChild(modal);
      modal.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeProfile() {
      modal.setAttribute('hidden', '');
      document.body.style.overflow = '';
    }

    // Close handlers
    if (btnClose) btnClose.addEventListener('click', closeProfile);
    if (backdrop) backdrop.addEventListener('click', closeProfile);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeProfile();
    });

    // Row click — exclude clicks on action buttons
    document.querySelectorAll('.js-profile-row').forEach(function (row) {
      row.addEventListener('click', function (e) {
        if (e.target.closest('.action-buttons')) return;
        var data = {};
        var logs = [];
        try { data = JSON.parse(row.dataset.profile || '{}'); } catch (err) { }
        try { logs = JSON.parse(row.dataset.logs || '[]'); } catch (err) { }
        var type = row.dataset.type || 'driver';
        openProfile(data, logs, type);
      });
    });

    // Prevent action button clicks from bubbling to row
    document.querySelectorAll('.action-buttons a, .action-buttons button').forEach(function (btn) {
      btn.addEventListener('click', function (e) { e.stopPropagation(); });
    });
  })();
</script>

<script>
  // Driver & Conductor table filter + search + pagination
  document.addEventListener('DOMContentLoaded', function () {

    var ROWS_PER_PAGE = 5;

    function setupTable(opts) {
      // opts: { tableId, searchId, statusId, prevId, nextId, pagesId, containerId }
      var tbody = document.querySelector('#' + opts.tableId + ' tbody');
      if (!tbody) return;
      var search = document.getElementById(opts.searchId);
      var status = document.getElementById(opts.statusId);
      var prevBtn = document.getElementById(opts.prevId);
      var nextBtn = document.getElementById(opts.nextId);
      var pagesEl = document.getElementById(opts.pagesId);
      var container = document.getElementById(opts.containerId);

      var allRows = Array.from(tbody.querySelectorAll('tr:not(.no-results-row)'));
      var filteredRows = allRows.slice();
      var currentPage = 1;

      function totalPages() {
        return Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
      }

      function getVisiblePages(current, total) {
        var compactCount = window.innerWidth < 992 ? 5 : 7;
        if (total <= compactCount) {
          return Array.from({ length: total }, function (_, i) { return i + 1; });
        }

        var pages = [1];
        var innerSlots = compactCount - 2;
        var half = Math.floor(innerSlots / 2);
        var start = Math.max(2, current - half);
        var end = Math.min(total - 1, start + innerSlots - 1);

        start = Math.max(2, end - innerSlots + 1);

        if (start > 2) pages.push('...');
        for (var p = start; p <= end; p++) pages.push(p);
        if (end < total - 1) pages.push('...');
        pages.push(total);
        return pages;
      }

      function renderPageNumbers() {
        if (!pagesEl) return;
        pagesEl.innerHTML = '';
        getVisiblePages(currentPage, totalPages()).forEach(function (item) {
          var b = document.createElement('button');
          if (item === '...') {
            b.className = 'page-number ellipsis';
            b.textContent = '...';
            b.type = 'button';
            b.disabled = true;
          } else {
            b.className = 'page-number' + (item === currentPage ? ' active' : '');
            b.textContent = item;
            b.type = 'button';
            b.addEventListener('click', function () { goToPage(item); });
          }
          pagesEl.appendChild(b);
        });
      }

      function showPage() {
        var tp = totalPages();
        if (currentPage > tp) currentPage = tp;
        if (currentPage < 1) currentPage = 1;

        // Remove old no-results row
        var noRow = tbody.querySelector('.no-results-row');
        if (noRow) noRow.remove();

        // Hide all data rows first
        allRows.forEach(function (r) { r.style.display = 'none'; });

        if (filteredRows.length === 0) {
          // Show "no results" row
          var tr = document.createElement('tr');
          tr.className = 'no-results-row';
          var colspan = (allRows[0] ? allRows[0].cells.length : 5);
          tr.innerHTML = '<td colspan="' + colspan + '" style="text-align:center;padding:30px;color:#9CA3AF;">No records match your filter.</td>';
          tbody.appendChild(tr);
          if (container) container.style.display = 'none';
          return;
        }

        // Show current page slice
        var startIdx = (currentPage - 1) * ROWS_PER_PAGE;
        var endIdx = startIdx + ROWS_PER_PAGE;
        filteredRows.forEach(function (r, idx) {
          r.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
        });

        if (container) container.style.display = '';
        if (prevBtn) prevBtn.disabled = (currentPage === 1);
        if (nextBtn) nextBtn.disabled = (currentPage >= tp);
        renderPageNumbers();
      }

      function goToPage(p) { currentPage = p; showPage(); }

      function applyFilter() {
        var q = search ? search.value.toLowerCase().trim() : '';
        var st = status ? status.value : 'all';

        filteredRows = allRows.filter(function (row) {
          if (row.cells.length === 1) return false; // empty-state row
          var text = row.textContent.toLowerCase();
          var badge = row.querySelector('.status-badge');
          var rowSt = badge ? badge.textContent.trim() : '';
          var matchQ = !q || text.includes(q);
          var matchSt = st === 'all' || rowSt === st;
          return matchQ && matchSt;
        });

        currentPage = 1;
        showPage();
      }

      if (search) search.addEventListener('input', applyFilter);
      if (status) status.addEventListener('change', applyFilter);
      if (prevBtn) prevBtn.addEventListener('click', function () { if (currentPage > 1) goToPage(currentPage - 1); });
      if (nextBtn) nextBtn.addEventListener('click', function () { if (currentPage < totalPages()) goToPage(currentPage + 1); });
      window.addEventListener('resize', renderPageNumbers);

      // Initial render — always show pagination
      if (allRows.length > 0) {
        showPage();
      } else {
        if (container) container.style.display = 'none';
      }
    }

    setupTable({
      tableId: 'drivers-table',
      searchId: 'drv-search',
      statusId: 'drv-filter-status',
      prevId: 'drv-prev-page',
      nextId: 'drv-next-page',
      pagesId: 'drv-pagination-pages',
      containerId: 'drv-pagination-container'
    });

    setupTable({
      tableId: 'conductors-table',
      searchId: 'cnd-search',
      statusId: 'cnd-filter-status',
      prevId: 'cnd-prev-page',
      nextId: 'cnd-next-page',
      pagesId: 'cnd-pagination-pages',
      containerId: 'cnd-pagination-container'
    });
  });
</script>