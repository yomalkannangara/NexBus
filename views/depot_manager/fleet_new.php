<?php
$summary = $summary ?? [];
$rows = $rows ?? [];
$routes = $routes ?? [];
$buses = $buses ?? [];
$filters = $filters ?? [];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$busClassColors = [
    'Normal' => '#64748B',      // Gray
    'Semi Luxury' => '#3B82F6', // Blue
    'Luxury' => '#F59E0B',      // Gold
];

$statusColors = [
    'Active' => '#10B981',
    'Maintenance' => '#F59E0B',
    'Inactive' => '#EF4444',
];

// Build list of unique models, years for filter dropdowns
$modelsList = [];
$yearsList = [];
foreach ($rows as $r) {
    if (!empty($r['bus_model']) && !in_array($r['bus_model'], $modelsList)) {
        $modelsList[] = $r['bus_model'];
    }
    if (!empty($r['year_of_manufacture']) && !in_array($r['year_of_manufacture'], $yearsList)) {
        $yearsList[] = $r['year_of_manufacture'];
    }
}
sort($modelsList);
rsort($yearsList);
?>

<section id="fleetPage" class="section fleet-section">
  <!-- Header with title and action button -->
  <div class="fleet-header">
    <div class="fleet-header-left">
      <h1 class="title-heading">Fleet Management</h1>
      <p class="title-sub">Monitor and manage your entire bus fleet in real-time</p>
    </div>
    <button id="btnAddBus" class="btn btn-primary fleet-add-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Add New Bus
    </button>
  </div>

  <!-- Summary KPI Cards -->
  <div class="fleet-kpi-grid">
    <?php if (!empty($summary)): 
      foreach ($summary as $c): 
        $icon_class = '';
        $label = strtolower($c['label'] ?? '');
        if (strpos($label, 'active') !== false) $icon_class = 'accent-green';
        elseif (strpos($label, 'maintenance') !== false) $icon_class = 'accent-yellow';
        elseif (strpos($label, 'inactive') !== false) $icon_class = 'accent-red';
        else $icon_class = 'accent-blue';
    ?>
      <div class="fleet-kpi-card <?= $icon_class ?>">
        <div class="kpi-top">
          <span class="kpi-value <?= htmlspecialchars($c['class'] ?? '') ?>"><?= htmlspecialchars($c['value'] ?? '0') ?></span>
          <div class="kpi-dot <?= $icon_class ?>"></div>
        </div>
        <p class="kpi-label"><?= htmlspecialchars($c['label'] ?? '') ?></p>
      </div>
    <?php endforeach; 
    else: ?>
      <div class="empty-note">No summary available.</div>
    <?php endif; ?>
  </div>

  <!-- Filter Panel -->
  <div class="fleet-filter-section">
    <form id="fleetFilterForm" method="get" class="fleet-filters-form">
      <div class="fleet-filter-header">
        <h3>Filters</h3>
        <button type="button" class="fleet-filter-toggle" id="fleetFilterToggle">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
          </svg>
          <span id="activeFilterCount"></span>
        </button>
      </div>

      <div class="fleet-filters-panel" id="fleetFiltersPanel" style="display: none;">
        <div class="fleet-filters-grid">
          <div class="filter-control">
            <label>Route</label>
            <select name="route" id="fleetFilterRoute" class="fleet-select">
              <option value="">All Routes</option>
              <?php if (!empty($routes)): 
                foreach ($routes as $r): ?>
                  <option value="<?= h($r['route_no']) ?>" <?= isset($filters['route']) && $filters['route'] == $r['route_no'] ? 'selected' : '' ?>>
                    <?= h(($r['route_no'] ?? '') . ' - ' . ($r['name'] ?? '')) ?>
                  </option>
                <?php endforeach; 
              endif; ?>
            </select>
          </div>

          <div class="filter-control">
            <label>Bus Number</label>
            <select name="bus" id="fleetFilterBus" class="fleet-select">
              <option value="">All Buses</option>
              <?php if (!empty($buses)): 
                foreach ($buses as $b): 
                  $busReg = (string)($b['reg_no'] ?? ''); ?>
                  <option value="<?= h($busReg) ?>" <?= ($filters['bus'] ?? '') === $busReg ? 'selected' : '' ?>>
                    <?= h($busReg) ?>
                  </option>
                <?php endforeach; 
              endif; ?>
            </select>
          </div>

          <div class="filter-control">
            <label>Status</label>
            <select name="status" id="fleetFilterStatus" class="fleet-select">
              <option value="">All Statuses</option>
              <option value="Active" <?= ($filters['status'] ?? '')==='Active' ? 'selected' : '' ?>>Active</option>
              <option value="Maintenance" <?= ($filters['status'] ?? '')==='Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
              <option value="Inactive" <?= ($filters['status'] ?? '')==='Inactive' ? 'selected' : '' ?>>Out of Service</option>
            </select>
          </div>

          <div class="filter-control">
            <label>Capacity</label>
            <select name="capacity" id="fleetFilterCapacity" class="fleet-select">
              <option value="">All Capacities</option>
              <option value="small" <?= ($filters['capacity'] ?? '')==='small' ? 'selected' : '' ?>>Small (&lt;30 seats)</option>
              <option value="medium" <?= ($filters['capacity'] ?? '')==='medium' ? 'selected' : '' ?>>Medium (30-50 seats)</option>
              <option value="large" <?= ($filters['capacity'] ?? '')==='large' ? 'selected' : '' ?>>Large (&gt;50 seats)</option>
            </select>
          </div>

          <div class="filter-control">
            <label>Bus Class</label>
            <select name="bus_class" id="fleetFilterBusClass" class="fleet-select">
              <option value="">All Classes</option>
              <option value="Normal" <?= ($filters['bus_class'] ?? '')==='Normal' ? 'selected' : '' ?>>Normal</option>
              <option value="Semi Luxury" <?= ($filters['bus_class'] ?? '')==='Semi Luxury' ? 'selected' : '' ?>>Semi Luxury</option>
              <option value="Luxury" <?= ($filters['bus_class'] ?? '')==='Luxury' ? 'selected' : '' ?>>Luxury</option>
            </select>
          </div>

          <div class="filter-control">
            <label>Year of Manufacture</label>
            <select name="year_range" id="fleetFilterYearRange" class="fleet-select">
              <option value="">All Years</option>
              <option value="before-2010" <?= ($filters['year_range'] ?? '')==='before-2010' ? 'selected' : '' ?>>Before 2010</option>
              <option value="2010-2015" <?= ($filters['year_range'] ?? '')==='2010-2015' ? 'selected' : '' ?>>2010–2015</option>
              <option value="2015-2020" <?= ($filters['year_range'] ?? '')==='2015-2020' ? 'selected' : '' ?>>2015–2020</option>
              <option value="after-2020" <?= ($filters['year_range'] ?? '')==='after-2020' ? 'selected' : '' ?>>After 2020</option>
            </select>
          </div>

          <div class="filter-control">
            <label>Bus Model</label>
            <select name="model" id="fleetFilterModel" class="fleet-select">
              <option value="">All Models</option>
              <?php if (!empty($modelsList)): 
                foreach ($modelsList as $mod): ?>
                  <option value="<?= h($mod) ?>" <?= ($filters['model'] ?? '')===$mod ? 'selected' : '' ?>>
                    <?= h($mod) ?>
                  </option>
                <?php endforeach; 
              endif; ?>
            </select>
          </div>
        </div>

        <div class="fleet-filter-actions">
          <button type="button" class="btn-filter-reset" id="fleetResetFilters">Reset</button>
          <button type="submit" class="btn-filter-apply" id="fleetApplyFilters">Apply Filters</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Fleet Cards Grid -->
  <div class="fleet-cards-container">
    <div class="fleet-cards-header">
      <h3 class="fleet-cards-title">Fleet Overview</h3>
      <div class="fleet-view-toggle">
        <span class="bus-count"><?= count($rows) ?> buses</span>
      </div>
    </div>

    <?php if (!empty($rows)): ?>
      <div class="fleet-cards-grid">
        <?php foreach ($rows as $r): 
          $reg_no = (string)($r['reg_no'] ?? '');
          $status = (string)($r['status'] ?? '');
          $capacity = (int)($r['capacity'] ?? 0);
          $chassis_no = (string)($r['chassis_no'] ?? '');
          $route = h($r['route'] ?? '—');
          $route_no = h($r['route_no'] ?? '');
          $bus_model = (string)($r['bus_model'] ?? '—');
          $year_manufacture = (int)($r['year_of_manufacture'] ?? 0);
          $manufacture_date = (string)($r['manufacture_date'] ?? '');
          $bus_class = (string)($r['bus_class'] ?? 'Normal');
          $latRaw = $r['current_lat'] ?? null;
          $lngRaw = $r['current_lng'] ?? null;
          $hasCoords = is_numeric($latRaw) && is_numeric($lngRaw);
          $latAttr = $hasCoords ? h((string)$latRaw) : '';
          $lngAttr = $hasCoords ? h((string)$lngRaw) : '';
          $coordText = $hasCoords ? (round((float)$latRaw, 6) . ', ' . round((float)$lngRaw, 6)) : '—';
          $statusColor = $statusColors[$status] ?? '#6B7280';
          $classColor = $busClassColors[$bus_class] ?? '#9CA3AF';
        ?>
          <div class="fleet-card js-bus-card"
               data-reg="<?= h($reg_no) ?>"
               data-bus-class="<?= h($bus_class) ?>"
               data-bus-model="<?= h($bus_model) ?>"
               data-year="<?= $year_manufacture > 0 ? (int)$year_manufacture : '' ?>"
               data-capacity="<?= $capacity ?>"
               data-chassis="<?= h($chassis_no) ?>"
               data-manufacture-date="<?= h($manufacture_date) ?>"
               data-current-route="<?= h($route) ?>">
            <div class="fleet-card-header">
              <div class="bus-badge-section">
                <span class="bus-badge"><?= h($reg_no) ?></span>
                <span class="bus-class-badge" style="background-color: <?= $classColor ?>;">
                  🚌
                </span>
              </div>
              <span class="status-badge" style="background-color: <?= $statusColor ?>; color: #fff;">
                <?= h($status) ?>
              </span>
            </div>

            <div class="fleet-card-body">
              <div class="card-route-info">
                <span class="card-label">Route:</span>
                <span class="card-value"><?= $route ?></span>
              </div>

              <div class="card-row">
                <div class="card-info">
                  <span class="card-label">Model:</span>
                  <span class="card-value"><?= h($bus_model) ?></span>
                </div>
                <div class="card-info">
                  <span class="card-label">Year:</span>
                  <span class="card-value"><?= $year_manufacture > 0 ? (int)$year_manufacture : '—' ?></span>
                </div>
              </div>

              <div class="card-row">
                <div class="card-info">
                  <span class="card-label">Capacity:</span>
                  <span class="card-value"><?= $capacity ?> seats</span>
                </div>
                <div class="card-info">
                  <span class="card-label">Class:</span>
                  <span class="card-value"><?= h($bus_class) ?></span>
                </div>
              </div>

              <div class="card-location-info">
                <span class="card-label">Current Location:</span>
                <?php if ($hasCoords): ?>
                  <a href="/M/dashboard?bus=<?= urlencode($reg_no) ?>&lat=<?= $latAttr ?>&lng=<?= $lngAttr ?>"
                     class="location-link js-location-link"
                     data-lat="<?= $latAttr ?>"
                     data-lng="<?= $lngAttr ?>"
                     title="View on dashboard map">
                    <span class="js-location-name" data-lat="<?= $latAttr ?>" data-lng="<?= $lngAttr ?>">
                      <?= $coordText ?>
                    </span>
                  </a>
                <?php else: ?>
                  <span class="card-value">—</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="fleet-card-footer">
              <button type="button" class="card-action-btn js-view-profile" data-reg="<?= h($reg_no) ?>" title="View Details">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
              <button type="button" class="card-action-btn js-edit-profile" data-reg="<?= h($reg_no) ?>" title="Edit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
              <button type="button" class="card-action-btn btn-danger js-delete-profile" data-reg="<?= h($reg_no) ?>" title="Delete">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="fleet-empty-state">
        <div class="empty-icon">🚌</div>
        <h3>No buses found</h3>
        <p>Start by adding your first bus to the fleet</p>
        <button id="btnAddBusEmpty" class="btn btn-primary">Add First Bus</button>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============= MODALS ============= -->

<!-- Create/Edit Bus Modal -->
<div id="modalBusForm" class="fleet-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card">
    <div class="fleet-modal-header">
      <h2 class="fleet-modal-title" id="modalBusFormTitle">Add New Bus to Fleet</h2>
      <button class="fleet-modal-close" data-close-modal aria-label="Close dialog">&times;</button>
    </div>

    <div class="fleet-modal-body">
      <form id="busForm" class="bus-form">
        <!-- Row 1: Bus Number, Status -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus Number / Registration <span class="required">*</span></label>
            <input type="text" id="form_reg_no" class="form-input" placeholder="e.g., NB-1234" required>
          </div>

          <div class="form-group">
            <label class="form-label">Initial Status</label>
            <select id="form_status" class="form-select">
              <option value="Active">Active (Ready)</option>
              <option value="Maintenance">Under Maintenance</option>
              <option value="Inactive">Out of Service</option>
            </select>
          </div>
        </div>

        <!-- Row 2: Chassis, Capacity -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Chassis Number</label>
            <input type="text" id="form_chassis_no" class="form-input" placeholder="Optional">
          </div>

          <div class="form-group">
            <label class="form-label">Seating Capacity <span class="required">*</span></label>
            <input type="number" id="form_capacity" class="form-input" min="1" max="120" placeholder="e.g., 54" required>
          </div>
        </div>

        <!-- Row 3: Bus Model, Year -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus Model</label>
            <input type="text" id="form_bus_model" class="form-input" placeholder="e.g., Ashok Leyland Viking">
          </div>

          <div class="form-group">
            <label class="form-label">Year of Manufacture</label>
            <input type="number" id="form_year_manufacture" class="form-input" min="1980" max="2026" placeholder="e.g., 2018">
          </div>
        </div>

        <!-- Row 4: Manufacture Date -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Manufacture Date</label>
            <input type="date" id="form_manufacture_date" class="form-input">
          </div>
        </div>

        <!-- Bus Class Selector -->
        <div class="form-group">
          <label class="form-label">Bus Class</label>
          <div class="bus-class-selector">
            <input type="hidden" id="form_bus_class" value="Normal">
            
            <button type="button" class="class-option active" data-class="Normal">
              <span class="class-icon">🚌</span>
              <span class="class-name">Normal</span>
              <span class="class-desc">Standard service</span>
            </button>
            
            <button type="button" class="class-option" data-class="Semi Luxury">
              <span class="class-icon">🚎</span>
              <span class="class-name">Semi Luxury</span>
              <span class="class-desc">Comfortable seating</span>
            </button>
            
            <button type="button" class="class-option" data-class="Luxury">
              <span class="class-icon">🚐</span>
              <span class="class-name">Luxury</span>
              <span class="class-desc">Premium experience</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <div class="fleet-modal-footer">
      <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-primary" id="btnSaveBusForm">Add Bus</button>
    </div>
  </div>
</div>

<!-- Bus Profile Modal -->
<div id="modalBusProfile" class="fleet-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card fleet-modal-large">
    <div class="fleet-modal-header">
      <h2 class="fleet-modal-title" id="profileBusTitle">Bus Profile</h2>
      <button class="fleet-modal-close" data-close-modal>&times;</button>
    </div>

    <div class="fleet-modal-body">
      <div id="busProfileContent" class="bus-profile-content">
        <!-- Profile content filled by JS -->
      </div>
    </div>

    <div class="fleet-modal-footer" id="profileModalFooter">
      <button type="button" class="btn btn-outline-secondary" data-close-modal>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        Close
      </button>
      <div class="modal-actions-group">
        <button type="button" class="btn btn-outline-warning" id="btnProfileEdit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
          Edit Details
        </button>
        <button type="button" class="btn btn-danger-outline" id="btnProfileDelete">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
          Delete Bus
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="modalConfirmDelete" class="fleet-modal fleet-modal-small" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card delete-modal-card">
    <div class="delete-modal-header">
      <div class="delete-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 6h18"></path>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          <line x1="10" y1="11" x2="10" y2="17"></line>
          <line x1="14" y1="11" x2="14" y2="17"></line>
        </svg>
      </div>
      <h2 class="delete-modal-title">Delete Bus</h2>
      <p class="delete-modal-subtitle">This action cannot be undone</p>
    </div>

    <div class="delete-modal-body">
      <div class="bus-delete-info">
        <div class="bus-delete-reg" id="delBusReg">BUS-001</div>
        <div class="bus-delete-details">
          Are you sure you want to permanently delete this bus from your fleet? All associated data will be removed.
        </div>
      </div>
    </div>

    <div class="delete-modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-close-modal>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        Cancel
      </button>
      <button type="button" class="btn btn-danger-destructive" id="btnConfirmDelete">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
        Delete Bus
      </button>
    </div>
  </div>
</div>

<!-- Styles -->
<style>
/* ============= TYPOGRAPHY & COLORS ============= */
:root {
  --brand-red: #7B1C2E;
  --brand-red-dark: #5a1420;
  --status-active: #10B981;
  --status-maintenance: #F59E0B;
  --status-inactive: #6B7280;
  --class-normal: #9CA3AF;
  --class-semi: #3B82F6;
  --class-luxury: #F59E0B;
  --border-radius: 8px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; }

.fleet-section {
  font-family: 'Segoe UI', 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* ============= HEADER ============= */
.fleet-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.fleet-header-left h1 {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px;
  color: #1F2937;
  letter-spacing: -0.5px;
}

.fleet-header-left p {
  font-size: 14px;
  color: #6B7280;
  margin: 0;
}

.fleet-add-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background-color: var(--brand-red);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 2px 8px rgba(123, 28, 46, 0.15);
}

.fleet-add-btn:hover {
  background-color: var(--brand-red-dark);
  box-shadow: 0 4px 12px rgba(123, 28, 46, 0.25);
  transform: translateY(-1px);
}

/* ============= KPI GRID ============= */
.fleet-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.fleet-kpi-card {
  background: white;
  padding: 20px;
  border-radius: var(--border-radius);
  border-left: 4px solid transparent;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: var(--transition);
}

.fleet-kpi-card.accent-blue { border-left-color: #3B82F6; }
.fleet-kpi-card.accent-green { border-left-color: var(--status-active); }
.fleet-kpi-card.accent-yellow { border-left-color: var(--status-maintenance); }
.fleet-kpi-card.accent-red { border-left-color: #EF4444; }

.fleet-kpi-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.kpi-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.kpi-value {
  font-size: 28px;
  font-weight: 700;
  color: #1F2937;
}

.kpi-label {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  font-weight: 500;
}

/* ============= FILTERS ============= */
.fleet-filter-section {
  background: white;
  border-radius: var(--border-radius);
  padding: 0;
  margin-bottom: 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fleet-filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #E5E7EB;
}

.fleet-filter-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1F2937;
}

.fleet-filter-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: var(--brand-red);
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: var(--transition);
}

.fleet-filter-toggle:hover {
  color: var(--brand-red-dark);
}

#activeFilterCount {
  display: inline-block;
  background-color: var(--brand-red);
  color: white;
  border-radius: 12px;
  padding: 2px 8px;
  font-size: 12px;
  min-width: 20px;
  text-align: center;
}

#activeFilterCount:empty { display: none; }

.fleet-filters-panel {
  padding: 20px;
  background: #F9FAFB;
}

.fleet-filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.filter-control {
  display: flex;
  flex-direction: column;
}

.filter-control label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.fleet-select {
  padding: 8px 12px;
  border: 1px solid #D1D5DB;
  border-radius: 6px;
  background: white;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
  transition: var(--transition);
}

.fleet-select:hover { border-color: var(--brand-red); }
.fleet-select:focus {
  outline: none;
  border-color: var(--brand-red);
  box-shadow: 0 0 0 3px rgba(123, 28, 46, 0.1);
}

.fleet-filter-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-filter-reset, .btn-filter-apply {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-filter-reset {
  background: white;
  color: #6B7280;
  border: 1px solid #D1D5DB;
}

.btn-filter-reset:hover {
  background: #F3F4F6;
  border-color: #9CA3AF;
}

.btn-filter-apply {
  background: var(--brand-red);
  color: white;
}

.btn-filter-apply:hover {
  background: var(--brand-red-dark);
  box-shadow: 0 2px 8px rgba(123, 28, 46, 0.15);
}

/* ============= CARDS GRID ============= */
.fleet-cards-container {
  margin-bottom: 32px;
}

.fleet-cards-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.fleet-cards-title {
  font-size: 16px;
  font-weight: 600;
  color: #1F2937;
  margin: 0;
}

.bus-count {
  font-size: 14px;
  color: #6B7280;
  font-weight: 500;
}

.fleet-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.fleet-card {
  background: white;
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: var(--transition);
  border: 1px solid #E5E7EB;
  cursor: pointer;
  display: flex;
  flex-direction: column;
}

.fleet-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
  border-color: var(--brand-red);
}

.fleet-card-header {
  padding: 16px;
  background: #F9FAFB;
  border-bottom: 1px solid #E5E7EB;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.bus-badge-section {
  display: flex;
  align-items: center;
  gap: 8px;
}

.bus-badge {
  font-family: 'Courier New', monospace;
  background: var(--brand-red);
  color: white;
  padding: 6px 10px;
  border-radius: 4px;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 1px;
}

.bus-class-badge {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 600;
  color: white;
}

.status-badge {
  padding: 6px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.fleet-card-body {
  padding: 16px;
  flex: 1;
}

.card-route-info {
  display: flex;
  flex-direction: column;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #E5E7EB;
}

.card-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.card-info {
  display: flex;
  flex-direction: column;
}

.card-label {
  font-size: 11px;
  font-weight: 600;
  color: #6B7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.card-value {
  font-size: 14px;
  color: #1F2937;
  font-weight: 500;
}

.card-location-info {
  display: flex;
  flex-direction: column;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #E5E7EB;
}

.location-link {
  color: var(--brand-red);
  text-decoration: none;
  font-weight: 500;
  transition: var(--transition);
}

.location-link:hover {
  color: var(--brand-red-dark);
  text-decoration: underline;
}

.fleet-card-footer {
  padding: 12px 16px;
  background: #F9FAFB;
  border-top: 1px solid #E5E7EB;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.card-action-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #D1D5DB;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151;
  transition: var(--transition);
}

.card-action-btn:hover {
  background: #EEF2FF;
  border-color: var(--brand-red);
  color: var(--brand-red);
}

.card-action-btn.btn-danger:hover {
  background: #FEE2E2;
  border-color: #EF4444;
  color: #EF4444;
}

/* ============= EMPTY STATE ============= */
.fleet-empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: var(--border-radius);
  border: 2px dashed #D1D5DB;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.fleet-empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1F2937;
  margin: 0 0 8px;
}

.fleet-empty-state p {
  font-size: 14px;
  color: #6B7280;
  margin: 0 0 24px;
}

/* ============= MODALS ============= */
.fleet-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
}

.fleet-modal[aria-hidden="false"] {
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fleet-modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: transparent;
  cursor: pointer;
}

.fleet-modal-card {
  position: relative;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: min(90%, 500px);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
  margin: auto;
}

.fleet-modal-large {
  max-width: 700px;
}

.fleet-modal-small {
  max-width: 420px;
  width: min(90%, 420px);
  margin: auto;
}

.fleet-modal-header {
  padding: 24px;
  border-bottom: 1px solid #E5E7EB;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.fleet-modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #1F2937;
  margin: 0;
}

.fleet-modal-close {
  background: transparent;
  border: none;
  font-size: 24px;
  color: #6B7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.fleet-modal-close:hover {
  color: #1F2937;
  background: #F3F4F6;
  border-radius: 6px;
}

.fleet-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.fleet-modal-footer {
  padding: 20px 24px;
  border-top: 1px solid #E5E7EB;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* ============= DELETE MODAL STYLES ============= */
.delete-modal-card {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.delete-modal-header {
  text-align: center;
  padding: 32px 24px 24px;
  background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
  border-bottom: none;
}

.delete-icon {
  margin: 0 auto 16px;
  width: 64px;
  height: 64px;
  background: #EF4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3);
}

.delete-modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 4px;
}

.delete-modal-subtitle {
  font-size: 14px;
  color: #6B7280;
  margin: 0;
  font-weight: 500;
}

.delete-modal-body {
  padding: 24px;
  background: white;
}

.bus-delete-info {
  text-align: center;
}

.bus-delete-reg {
  font-size: 18px;
  font-weight: 700;
  color: #1F2937;
  background: #F3F4F6;
  padding: 12px 20px;
  border-radius: 8px;
  display: inline-block;
  margin-bottom: 16px;
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.bus-delete-details {
  color: #6B7280;
  line-height: 1.5;
  font-size: 14px;
}

.delete-modal-footer {
  padding: 20px 24px;
  border-top: 1px solid #E5E7EB;
  display: flex;
  justify-content: center;
  gap: 12px;
  background: white;
}

.btn-danger-destructive {
  background: #DC2626;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-danger-destructive:hover {
  background: #B91C1C;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.btn-outline-secondary {
  background: white;
  color: #6B7280;
  border: 1px solid #D1D5DB;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-outline-secondary:hover {
  background: #F9FAFB;
  border-color: #9CA3AF;
  color: #374151;
}

/* ============= MODAL FOOTER IMPROVEMENTS ============= */
#profileModalFooter {
  justify-content: space-between !important;
}

.modal-actions-group {
  display: flex;
  gap: 12px;
}

.btn-outline-warning {
  background: white;
  color: #D97706;
  border: 1px solid #F59E0B;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-outline-warning:hover {
  background: #FFFBEB;
  border-color: #D97706;
  color: #92400E;
}

.btn-danger-outline {
  background: white;
  color: #DC2626;
  border: 1px solid #EF4444;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-danger-outline:hover {
  background: #FEF2F2;
  border-color: #B91C1C;
  color: #991B1B;
}

/* ============= FORMS ============= */
.bus-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.required {
  color: #EF4444;
}

.form-input, .form-select {
  padding: 10px 12px;
  border: 1px solid #D1D5DB;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  transition: var(--transition);
}

.form-input:focus, .form-select:focus {
  outline: none;
  border-color: var(--brand-red);
  box-shadow: 0 0 0 3px rgba(123, 28, 46, 0.1);
}

/* Bus Class Selector */
.bus-class-selector {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.class-option {
  padding: 16px;
  border: 2px solid #D1D5DB;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  text-align: center;
}

.class-option:hover {
  border-color: var(--brand-red);
  background: #FEF2F3;
}

.class-option.active {
  border-color: var(--brand-red);
  background: var(--brand-red);
  color: white;
}

.class-icon {
  font-size: 24px;
}

.class-name {
  font-weight: 600;
  font-size: 13px;
}

.class-desc {
  font-size: 11px;
  opacity: 0.7;
}

/* ============= BUS PROFILE STYLES ============= */
.bus-profile-content {
  max-width: none;
}

.bus-profile-header {
  text-align: center;
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid #E5E7EB;
}

.bus-profile-avatar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.bus-avatar-icon {
  width: 80px;
  height: 80px;
  background: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.bus-profile-reg {
  font-size: 24px;
  font-weight: 700;
  color: #1F2937;
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.bus-profile-status {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.bus-profile-status.status-active {
  background: #D1FAE5;
  color: #065F46;
}

.bus-profile-status.status-maintenance {
  background: #FEF3C7;
  color: #92400E;
}

.bus-profile-status.status-inactive {
  background: #FEE2E2;
  color: #991B1B;
}

.bus-profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

.profile-section {
  background: #F9FAFB;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #E5E7EB;
}

.profile-section-title {
  font-size: 16px;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-section-title::before {
  content: '';
  width: 4px;
  height: 16px;
  background: var(--brand-red);
  border-radius: 2px;
}

.profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.profile-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.profile-field-label {
  font-size: 12px;
  font-weight: 600;
  color: #6B7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.profile-field-value {
  font-size: 14px;
  color: #1F2937;
  font-weight: 500;
  padding: 8px 0;
}

.bus-class-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.bus-class-indicator.class-normal {
  background: #F1F5F9;
  color: #475569;
}

.bus-class-indicator.class-semi-luxury {
  background: #EEF2FF;
  color: #3730A3;
}

.bus-class-indicator.class-luxury {
  background: #FFFBEB;
  color: #92400E;
}

.delete-message {
  font-size: 14px;
  color: #1F2937;
  margin: 0 0 12px;
}

.delete-warning {
  font-size: 13px;
  color: #EF4444;
  margin: 0;
  font-weight: 500;
}

/* Buttons */
.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition);
}

.btn-primary {
  background: var(--brand-red);
  color: white;
}

.btn-primary:hover {
  background: var(--brand-red-dark);
  box-shadow: 0 2px 8px rgba(123, 28, 46, 0.15);
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #D1D5DB;
}

.btn-secondary:hover {
  background: #F3F4F6;
  border-color: #9CA3AF;
}

.btn-danger {
  background: #FEE2E2;
  color: #DC2626;
}

.btn-danger:hover {
  background: #FECACA;
}

/* ============= RESPONSIVE ============= */
@media (max-width: 768px) {
  .fleet-header {
    flex-direction: column;
    gap: 16px;
  }

  .fleet-add-btn {
    width: 100%;
    justify-content: center;
  }

  .fleet-filters-grid {
    grid-template-columns: 1fr;
  }

  .fleet-cards-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .bus-class-selector {
    grid-template-columns: 1fr;
  }

  .fleet-modal-card {
    max-width: 95%;
    max-height: 95vh;
  }
}

@media (max-width: 480px) {
  .fleet-header-left h1 {
    font-size: 24px;
  }

  .fleet-kpi-grid {
    grid-template-columns: 1fr;
  }

  .card-row {
    grid-template-columns: 1fr;
  }

  .fleet-modal-header {
    padding: 16px;
  }

  .fleet-modal-body {
    padding: 16px;
  }

  .bus-profile-content {
    grid-template-columns: 1fr;
  }
}
</style>

<script src="/assets/js/fleet_new.js"></script>
