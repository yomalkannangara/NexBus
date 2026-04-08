<?php
// $summary = $summary ?? [];
// $rows    = $rows    ?? [];
// $routes  = $routes  ?? [];
// $buses   = $buses   ?? [];
?>
<section id="fleetPage" class="section">
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
    <?php if (!empty($summary)): foreach ($summary as $c): 
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
    <?php endforeach; else: ?>
      <div class="empty-note">No summary available.</div>
    <?php endif; ?>
  </div>

  <!-- Filter & Search Bar -->
  <form id="fleetFilterForm" method="get" class="fleet-filter-section">
    <div class="fleet-filter-top">
      <button type="button" class="fleet-filter-toggle" id="fleetFilterToggle">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
        </svg>
        Filters
      </button>
    </div>

    <!-- Advanced Filters (collapsible) -->
    <div class="fleet-filters-panel" id="fleetFiltersPanel" style="display: none;">
      <div class="fleet-filters-grid">
        <div class="filter-control">
          <label>Route</label>
          <select name="route" id="fleetFilterRoute" class="fleet-select">
            <option value="">All Routes</option>
            <?php if (!empty($routes)): foreach ($routes as $r): ?>
              <option value="<?= htmlspecialchars($r['route_no']) ?>" <?= isset($filters['route']) && $filters['route'] == $r['route_no'] ? 'selected' : '' ?>>
                <?= htmlspecialchars(($r['route_no'] ?? '') . ' - ' . ($r['name'] ?? '')) ?>
              </option>
            <?php endforeach; endif; ?>
          </select>
        </div>

        <div class="filter-control">
          <label>Bus Number</label>
          <select name="bus" id="fleetFilterBus" class="fleet-select">
            <option value="">All Buses</option>
            <?php if (!empty($buses)): foreach ($buses as $b): ?>
              <?php $busReg = (string)($b['reg_no'] ?? ''); ?>
              <option value="<?= htmlspecialchars($busReg) ?>" <?= ($filters['bus'] ?? '') === $busReg ? 'selected' : '' ?>>
                <?= htmlspecialchars($busReg) ?>
              </option>
            <?php endforeach; endif; ?>
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
          <label>Assignment</label>
          <select name="assignment" id="fleetFilterAssignment" class="fleet-select">
            <option value="">All Assignments</option>
            <option value="full" <?= ($filters['assignment'] ?? '')==='full' ? 'selected' : '' ?>>Fully Assigned</option>
            <option value="incomplete" <?= ($filters['assignment'] ?? '')==='incomplete' ? 'selected' : '' ?>>Incomplete</option>
            <option value="unassigned" <?= ($filters['assignment'] ?? '')==='unassigned' ? 'selected' : '' ?>>Unassigned</option>
          </select>
        </div>

      </div>

      <div class="fleet-filter-actions">
        <button type="button" class="btn-filter-reset" id="fleetResetFilters">Reset</button>
        <button type="submit" class="btn-filter-apply" id="fleetApplyFilters">Apply Filters</button>
      </div>
    </div>
  </form>

  <!-- Fleet Table with Enhanced Design -->
  <div class="fleet-table-container">
    <div class="fleet-table-header">
      <h3 class="fleet-table-title">Fleet Overview</h3>
      <div class="fleet-view-options">
        <button class="fleet-view-btn active" data-view="table" title="Table view">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
        </button>
        <button class="fleet-view-btn" data-view="cards" title="Card view">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
        </button>
      </div>
    </div>

    <?php if (!empty($rows)): ?>
      <!-- Table View -->
      <div class="fleet-view-content" id="fleetTableView">
        <div class="table-wrap">
          <table class="fleet-table">
            <thead>
              <tr>
                <th>Bus Number</th>
                <th>Route Info</th>
                <th>Status</th>
                <th>Assignment</th>
                <th>Capacity</th>
                <th>Current Location</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $reg_no     = (string)($r['reg_no'] ?? '');
                $status     = (string)($r['status'] ?? '');
                $capacity   = (int)($r['capacity'] ?? 0);
                $chassis_no = (string)($r['chassis_no'] ?? '');
                $route      = htmlspecialchars($r['route'] ?? '—');
                $route_no   = htmlspecialchars($r['route_no'] ?? '');
                $latRaw     = $r['current_lat'] ?? null;
                $lngRaw     = $r['current_lng'] ?? null;
                $hasCoords  = is_numeric($latRaw) && is_numeric($lngRaw);
                $latAttr    = $hasCoords ? htmlspecialchars((string)$latRaw) : '';
                $lngAttr    = $hasCoords ? htmlspecialchars((string)$lngRaw) : '';
                $coordText  = $hasCoords ? (round((float)$latRaw, 6) . ', ' . round((float)$lngRaw, 6)) : '—';
                $location   = htmlspecialchars($coordText);
                
                // Determine status badge color
                $status_badge = $status === 'Active' ? 'badge-green'
                              : ($status === 'Maintenance' ? 'badge-yellow'
                              : ($status === 'Inactive' ? 'badge-red' : 'badge-blue'));

                // Determine assignment status
                $assignment = (isset($r['driver']) && $r['driver']) && (isset($r['conductor']) && $r['conductor']) 
                            ? '<span class="badge badge-green">Fully Assigned</span>'
                            : '<span class="badge badge-yellow">Incomplete</span>';

              ?>
              <tr class="fleet-row">
                <td class="fleet-bus-number">
                  <span class="bus-badge"><?= htmlspecialchars($reg_no) ?></span>
                </td>
                <td>
                  <div class="route-info">
                    <strong><?= $route ?></strong>
                    <span class="route-number"><?= $route_no ?></span>
                  </div>
                </td>
                <td>
                  <span class="badge <?= $status_badge ?>"><?= htmlspecialchars($status) ?></span>
                </td>
                <td><?= $assignment ?></td>
                <td>
                  <span class="capacity-badge"><?= $capacity ?></span>
                  <span class="capacity-label">seats</span>
                </td>
                <td class="location-cell">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; display: inline;">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <a class="js-location-name" data-lat="<?= $latAttr ?>" data-lng="<?= $lngAttr ?>" href="/M/dashboard?bus=<?= rawurlencode($reg_no) ?>" title="Open on dashboard map"><?= $location ?></a>
                </td>
                <td>
                  <div class="fleet-actions">
                    <button class="fleet-action-btn js-edit" 
                            data-reg="<?= htmlspecialchars($reg_no) ?>"
                            data-status="<?= htmlspecialchars($status) ?>"
                            data-capacity="<?= htmlspecialchars($capacity) ?>"
                            data-chassis="<?= htmlspecialchars($chassis_no) ?>"
                            title="Edit bus">✎</button>
                    <button class="fleet-action-btn js-delete" 
                            data-reg="<?= htmlspecialchars($reg_no) ?>"
                            title="Delete bus">🗑</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Card View (initially hidden) -->
      <div class="fleet-view-content" id="fleetCardView" style="display: none;">
        <div class="fleet-cards-grid">
          <?php foreach ($rows as $r): 
            $reg_no     = (string)($r['reg_no'] ?? '');
            $status     = (string)($r['status'] ?? '');
            $capacity   = (int)($r['capacity'] ?? 0);
            $chassis_no = (string)($r['chassis_no'] ?? '');
            $route      = htmlspecialchars($r['route'] ?? '—');
            $route_no   = htmlspecialchars($r['route_no'] ?? '');
            $latRaw     = $r['current_lat'] ?? null;
            $lngRaw     = $r['current_lng'] ?? null;
            $hasCoords  = is_numeric($latRaw) && is_numeric($lngRaw);
            $latAttr    = $hasCoords ? htmlspecialchars((string)$latRaw) : '';
            $lngAttr    = $hasCoords ? htmlspecialchars((string)$lngRaw) : '';
            $coordText  = $hasCoords ? (round((float)$latRaw, 6) . ', ' . round((float)$lngRaw, 6)) : '—';
            $location   = htmlspecialchars($coordText);
            
            $status_icon = $status === 'Active' ? '✓' : ($status === 'Maintenance' ? '⚙' : '⊘');
            $status_color = $status === 'Active' ? 'green' : ($status === 'Maintenance' ? 'yellow' : 'red');
          ?>
            <div class="fleet-card">
              <div class="fleet-card-header">
                <div class="fleet-card-title">
                  <h4><?= htmlspecialchars($reg_no) ?></h4>
                  <span class="status-badge status-<?= $status_color ?>"><?= htmlspecialchars($status) ?></span>
                </div>
              </div>

              <div class="fleet-card-body">
                <div class="card-info-row">
                  <span class="info-label">Route:</span>
                  <span class="info-value"><?= $route ?> <small>(<?= $route_no ?>)</small></span>
                </div>
                <div class="card-info-row">
                  <span class="info-label">Capacity:</span>
                  <span class="info-value"><?= $capacity ?> seats</span>
                </div>
                <div class="card-info-row">
                  <span class="info-label">Current Location:</span>
                  <a class="info-value js-location-name" data-lat="<?= $latAttr ?>" data-lng="<?= $lngAttr ?>" href="/M/dashboard?bus=<?= rawurlencode($reg_no) ?>" title="Open on dashboard map"><?= $location ?></a>
                </div>
              </div>

              <div class="fleet-card-footer">
                <button class="fleet-card-btn js-edit" 
                        data-reg="<?= htmlspecialchars($reg_no) ?>"
                        data-status="<?= htmlspecialchars($status) ?>"
                        data-capacity="<?= htmlspecialchars($capacity) ?>"
                        data-chassis="<?= htmlspecialchars($chassis_no) ?>">Edit</button>
                <button class="fleet-card-btn danger js-delete" 
                        data-reg="<?= htmlspecialchars($reg_no) ?>">Delete</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
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

<!-- ============= IMPROVED MODAL DIALOGS ============= -->

<!-- Create Bus Modal -->
<div id="modalCreateBus" class="fleet-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card">
    <div class="fleet-modal-header">
      <h2 class="fleet-modal-title">Add New Bus to Fleet</h2>
      <button class="fleet-modal-close" data-close-modal aria-label="Close dialog">&times;</button>
    </div>

    <div class="fleet-modal-body">
      <div class="fleet-form-group">
        <label class="fleet-form-label">
          Bus Number / Registration <span class="required">*</span>
        </label>
        <input class="fleet-form-input" 
               id="create_reg_no" 
               placeholder="e.g., NA-1234 or SLTB-5678"
               type="text">
        <span class="fleet-form-hint">Enter the vehicle registration number</span>
      </div>

      <div class="fleet-form-group">
        <label class="fleet-form-label">Chassis Number</label>
        <input class="fleet-form-input" 
               id="create_chassis_no" 
               placeholder="Optional - Enter chassis/VIN number"
               type="text">
      </div>

      <div class="fleet-form-row">
        <div class="fleet-form-group">
          <label class="fleet-form-label">Seating Capacity <span class="required">*</span></label>
          <input class="fleet-form-input" 
                 type="number" 
                 min="1" 
                 max="120"
                 step="1" 
                 id="create_capacity" 
                 placeholder="e.g., 54">
        </div>

        <div class="fleet-form-group">
          <label class="fleet-form-label">Initial Status</label>
          <select id="create_status" class="fleet-form-select">
            <option value="Active">Active (Ready)</option>
            <option value="Maintenance">Under Maintenance</option>
            <option value="Inactive">Out of Service</option>
          </select>
        </div>
      </div>
    </div>

    <div class="fleet-modal-footer">
      <button type="button" class="btn-cancel" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-primary" id="btnSaveCreate">Add Bus</button>
    </div>
  </div>
</div>

<!-- Edit Bus Modal -->
<div id="modalEditBus" class="fleet-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card">
    <div class="fleet-modal-header">
      <h2 class="fleet-modal-title">Edit Bus Details</h2>
      <button class="fleet-modal-close" data-close-modal aria-label="Close dialog">&times;</button>
    </div>

    <div class="fleet-modal-body">
      <div class="fleet-form-group">
        <label class="fleet-form-label">Bus Number</label>
        <input class="fleet-form-input" 
               id="edit_reg_no" 
               readonly 
               title="Bus number cannot be changed">
      </div>

      <div class="fleet-form-group">
        <label class="fleet-form-label">Chassis Number</label>
        <input class="fleet-form-input" 
               id="edit_chassis_no" 
               placeholder="Enter chassis/VIN number"
               type="text">
      </div>

      <div class="fleet-form-row">
        <div class="fleet-form-group">
          <label class="fleet-form-label">Seating Capacity</label>
          <input class="fleet-form-input" 
                 type="number" 
                 min="1" 
                 max="120"
                 step="1" 
                 id="edit_capacity">
        </div>

        <div class="fleet-form-group">
          <label class="fleet-form-label">Current Status</label>
          <select id="edit_status" class="fleet-form-select">
            <option value="Active">Active (Ready)</option>
            <option value="Maintenance">Under Maintenance</option>
            <option value="Inactive">Out of Service</option>
          </select>
        </div>
      </div>
    </div>

    <div class="fleet-modal-footer">
      <button type="button" class="btn-cancel" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-primary" id="btnSaveEdit">Update Bus</button>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="modalDeleteBus" class="fleet-modal fleet-modal-alert" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="fleet-modal-overlay" data-close-modal></div>
  <div class="fleet-modal-card">
    <div class="fleet-modal-header alert">
      <h2 class="fleet-modal-title">Delete Bus</h2>
      <button class="fleet-modal-close" data-close-modal aria-label="Close dialog">&times;</button>
    </div>

    <div class="fleet-modal-body alert">
      <div class="alert-icon">⚠️</div>
      <p>Are you sure you want to delete bus <strong id="delBusReg"></strong>?</p>
      <p class="alert-subtext">This action cannot be undone. All associated data will be lost.</p>
    </div>

    <div class="fleet-modal-footer">
      <button type="button" class="btn-cancel" data-close-modal>Cancel</button>
      <button type="button" class="btn btn-danger" id="btnConfirmDelete">Delete Bus</button>
    </div>
  </div>
</div>

<!-- JS -->
<style>
/* ===== Fleet Page Redesign Styles ===== */

.fleet-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
}

.fleet-header-left h1 {
  margin: 0 0 8px 0;
  font-size: 32px;
  font-weight: 800;
  color: var(--text);
}

.fleet-header-left p {
  margin: 0;
  color: var(--muted);
  font-size: 15px;
}

.fleet-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: linear-gradient(180deg, #7a0f2e, #8f1238);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
  box-shadow: 0 4px 12px rgba(122, 15, 46, 0.2);
}

.fleet-add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(122, 15, 46, 0.3);
}

/* KPI Grid */
.fleet-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

.fleet-kpi-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 16px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
}

.fleet-kpi-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #7a0f2e, #8f1238);
}

.fleet-kpi-card.accent-green::before { background: linear-gradient(90deg, #33c28a, #1f7a54); }
.fleet-kpi-card.accent-yellow::before { background: linear-gradient(90deg, #ffd36e, #f1b425); }
.fleet-kpi-card.accent-red::before { background: linear-gradient(90deg, #ff6b81, #c92c4b); }
.fleet-kpi-card.accent-blue::before { background: linear-gradient(90deg, #4aa3ff, #2b6fff); }

.kpi-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 8px;
}

.kpi-value {
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
}

.kpi-dot {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #eef2f7;
  border: 1px solid var(--line);
}

.kpi-dot.accent-green { background: #e8f7f0; border-color: #c7ecd9; }
.kpi-dot.accent-yellow { background: #fff5d9; border-color: #fde6a7; }
.kpi-dot.accent-red { background: #ffe6ea; border-color: #ffccd5; }
.kpi-dot.accent-blue { background: #e7f0ff; border-color: #cddfff; }

.kpi-label {
  margin: 0;
  font-size: 13px;
  color: var(--muted);
  font-weight: 600;
}

/* Filter Section */
.fleet-filter-section {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
  box-shadow: var(--shadow);
}

.fleet-filter-top {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 0;
}

.fleet-search-box {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: #f9fafb;
  border: 1px solid var(--line);
  border-radius: 10px;
  color: var(--muted);
}

.fleet-search-box svg {
  flex-shrink: 0;
  color: var(--muted);
}

.fleet-search-input {
  flex: 1;
  border: none;
  background: transparent;
  color: var(--text);
  font-size: 14px;
  outline: none;
}

.fleet-search-input::placeholder {
  color: var(--muted);
}

.fleet-filter-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f9fafb;
  border: 1px solid var(--line);
  border-radius: 10px;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
}

.fleet-filter-toggle:hover {
  background: #f0f2f5;
  border-color: var(--brand);
}

.fleet-filter-toggle.active {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}

.fleet-filters-panel {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--line);
}

.fleet-filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.filter-control {
  display: flex;
  flex-direction: column;
}

.filter-control label {
  font-size: 12px;
  color: var(--muted);
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.fleet-select {
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: var(--text);
  font-size: 14px;
  cursor: pointer;
}

.fleet-select:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(122, 15, 46, 0.1);
}

.fleet-filter-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-filter-reset,
.btn-filter-apply {
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
}

.btn-filter-reset {
  background: #f0f2f5;
  color: var(--text);
  border: 1px solid var(--line);
}

.btn-filter-reset:hover {
  background: #e5e7eb;
}

.btn-filter-apply {
  background: linear-gradient(180deg, #7a0f2e, #8f1238);
  color: #fff;
}

.btn-filter-apply:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(122, 15, 46, 0.2);
}

/* Table Container */
.fleet-table-container {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--shadow);
}

.fleet-table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid var(--line);
}

.fleet-table-title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
}

.fleet-view-options {
  display: flex;
  gap: 8px;
  background: #f9fafb;
  padding: 4px;
  border-radius: 8px;
}

.fleet-view-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: var(--muted);
  cursor: pointer;
  transition: all .2s ease;
}

.fleet-view-btn:hover {
  background: #fff;
  color: var(--text);
}

.fleet-view-btn.active {
  background: #fff;
  color: var(--brand);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

/* Fleet Table */
.table-wrap {
  width: 100%;
  overflow-x: auto;
}

.fleet-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.fleet-table thead {
  background: #f9fafb;
  border-bottom: 1px solid var(--line);
}

.fleet-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.fleet-table tbody tr {
  border-bottom: 1px solid var(--line);
  transition: background .15s ease;
}

.fleet-table tbody tr:hover {
  background: #f9fafb;
}

.fleet-table td {
  padding: 14px;
}

.fleet-bus-number {
  font-weight: 700;
  color: var(--text);
}

.bus-badge {
  display: inline-block;
  padding: 6px 10px;
  background: #f0f2f5;
  border-radius: 6px;
  font-weight: 700;
  color: var(--brand);
}

.route-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.route-number {
  font-size: 11px;
  color: var(--muted);
  font-weight: 500;
}

.capacity-badge {
  font-weight: 700;
  color: var(--text);
}

.capacity-label {
  font-size: 12px;
  color: var(--muted);
  margin-left: 3px;
}

.location-cell {
  color: var(--text);
  display: flex;
  align-items: center;
}

.maintenance-alert {
  color: #c92c4b;
  font-weight: 600;
}

.maintenance-due {
  color: #9a5a00;
  font-weight: 600;
}

.fleet-actions {
  display: flex;
  gap: 6px;
}

.fleet-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #f0f2f5;
  border: 1px solid var(--line);
  border-radius: 6px;
  color: var(--text);
  cursor: pointer;
  transition: all .2s ease;
  font-size: 16px;
}

.fleet-action-btn:hover {
  background: var(--brand);
  color: #fff;
  border-color: var(--brand);
}

/* Card View */
.fleet-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  padding: 16px;
}

.fleet-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: all .2s ease;
}

.fleet-card:hover {
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.fleet-card-header {
  padding: 16px;
  border-bottom: 1px solid var(--line);
  background: #f9fafb;
}

.fleet-card-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.fleet-card-title h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.status-green {
  background: #e8f7f0;
  color: #1f7a54;
}

.status-yellow {
  background: #fff4e6;
  color: #9a5a00;
}

.status-red {
  background: #ffe6ea;
  color: #c92c4b;
}

.fleet-card-body {
  padding: 16px;
}

.card-info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--line);
}

.card-info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.info-value {
  font-weight: 600;
  color: var(--text);
}

.fleet-card-footer {
  display: flex;
  gap: 10px;
  padding: 16px;
  border-top: 1px solid var(--line);
  background: #f9fafb;
}

.fleet-card-btn {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--line);
  background: #fff;
  border-radius: 6px;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
}

.fleet-card-btn:hover {
  background: #f0f2f5;
  border-color: var(--text);
}

.fleet-card-btn.danger {
  color: #c92c4b;
  border-color: #c92c4b;
}

.fleet-card-btn.danger:hover {
  background: #ffe6ea;
}

/* Empty State */
.fleet-empty-state {
  padding: 60px 32px;
  text-align: center;
  background: var(--panel);
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.fleet-empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
}

.fleet-empty-state p {
  margin: 0 0 24px 0;
  color: var(--muted);
}

/* Modals */
.fleet-modal {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.fleet-modal[aria-hidden="true"] {
  pointer-events: none;
}

.fleet-modal[aria-hidden="true"] .fleet-modal-overlay,
.fleet-modal[aria-hidden="true"] .fleet-modal-card {
  opacity: 0;
}

.fleet-modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  cursor: pointer;
  transition: opacity .3s ease;
}

.fleet-modal-card {
  position: relative;
  background: var(--panel);
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
  max-width: 500px;
  width: 100%;
  overflow: hidden;
  transition: opacity .3s ease;
}

.fleet-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(180deg, #f9fafb, #fff);
}

.fleet-modal-header.alert {
  background: linear-gradient(180deg, #ffe6ea, #fff);
}

.fleet-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
}

.fleet-modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  color: var(--muted);
  font-size: 24px;
  cursor: pointer;
  transition: all .2s ease;
}

.fleet-modal-close:hover {
  color: var(--text);
  background: #f0f2f5;
  border-radius: 6px;
}

.fleet-modal-body {
  padding: 24px;
}

.fleet-modal-body.alert {
  text-align: center;
}

.alert-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.alert-subtext {
  font-size: 13px;
  color: var(--muted);
  margin-top: 8px;
}

.fleet-form-group {
  margin-bottom: 18px;
}

.fleet-form-group:last-child {
  margin-bottom: 0;
}

.fleet-form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
}

.required {
  color: #c92c4b;
}

.fleet-form-input,
.fleet-form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: var(--text);
  font-size: 14px;
  font-family: inherit;
}

.fleet-form-input:focus,
.fleet-form-select:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(122, 15, 46, 0.1);
}

.fleet-form-input[readonly] {
  background: #f0f2f5;
  cursor: not-allowed;
}

.fleet-form-hint {
  display: block;
  font-size: 12px;
  color: var(--muted);
  margin-top: 4px;
}

.fleet-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.fleet-modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 16px 24px;
  border-top: 1px solid var(--line);
  background: #f9fafb;
}

.btn-cancel {
  padding: 10px 16px;
  border: 1px solid var(--line);
  background: #fff;
  border-radius: 8px;
  color: var(--text);
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
}

.btn-cancel:hover {
  background: #f0f2f5;
}

.btn-danger {
  background: linear-gradient(180deg, #c92c4b, #b82643);
  color: #fff;
  border: none;
}

.btn-danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(201, 44, 75, 0.3);
}

/* Responsive */
@media (max-width: 1200px) {
  .fleet-kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .fleet-filters-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .fleet-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .fleet-kpi-grid {
    grid-template-columns: 1fr;
  }

  .fleet-filter-top {
    flex-direction: column;
  }

  .fleet-filters-grid {
    grid-template-columns: 1fr;
  }

  .fleet-table {
    font-size: 12px;
  }

  .fleet-table th,
  .fleet-table td {
    padding: 10px 8px;
  }

  .fleet-cards-grid {
    grid-template-columns: 1fr;
  }

  .fleet-form-row {
    grid-template-columns: 1fr;
  }

  .fleet-modal-card {
    max-width: calc(100% - 40px);
  }
}

@media (max-width: 640px) {
  .fleet-header-left h1 {
    font-size: 24px;
  }

  .fleet-add-btn {
    width: 100%;
    justify-content: center;
  }

  .fleet-filter-toggle {
    width: 100%;
    justify-content: center;
  }

  .fleet-actions {
    justify-content: center;
  }
}
</style>

<script src="/assets/js/fleet.js"></script>
<script>
(function() {
  // View switching
  const viewBtns = document.querySelectorAll('.fleet-view-btn');
  const tableView = document.getElementById('fleetTableView');
  const cardView = document.getElementById('fleetCardView');

  viewBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const view = btn.getAttribute('data-view');
      viewBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      if (view === 'cards') {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
      } else {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
      }
    });
  });

  // Filter toggle
  const filterToggle = document.getElementById('fleetFilterToggle');
  const filtersPanel = document.getElementById('fleetFiltersPanel');

  if (filterToggle) {
    filterToggle.addEventListener('click', () => {
      const isHidden = filtersPanel.style.display === 'none';
      filtersPanel.style.display = isHidden ? 'block' : 'none';
      filterToggle.classList.toggle('active');
    });

    // if any filters already applied on page load, open panel
    const anyFilter = <?= (!empty($filters['search']) || !empty($filters['bus']) || !empty($filters['route']) || !empty($filters['status']) || !empty($filters['capacity']) || !empty($filters['assignment'])) ? 'true' : 'false'; ?>;
    if (anyFilter) {
      filtersPanel.style.display = 'block';
      filterToggle.classList.add('active');
    }
  }

  // Reset filters (clears form and submits)
  const resetBtn = document.getElementById('fleetResetFilters');
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      const form = document.getElementById('fleetFilterForm');
      if (!form) return;
      form.reset();
      form.submit();
    });
  }

  // Add bus from empty state
  const btnAddEmpty = document.getElementById('btnAddBusEmpty');
  if (btnAddEmpty) {
    btnAddEmpty.addEventListener('click', () => {
      document.getElementById('btnAddBus').click();
    });
  }

  const locationNodes = Array.from(document.querySelectorAll('.js-location-name'));
  if (locationNodes.length) {
    const locationCache = new Map();

    const reverseGeocode = async (lat, lng) => {
      const key = `${Number(lat).toFixed(5)},${Number(lng).toFixed(5)}`;
      if (locationCache.has(key)) return locationCache.get(key);

      const url = `/M/reverseGeocode?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
      try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Reverse geocode failed');
        const data = await res.json();
        const place = (data && data.ok && data.name)
          ? String(data.name)
          : `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
        locationCache.set(key, place);
        return place;
      } catch (_) {
        const fallback = `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
        locationCache.set(key, fallback);
        return fallback;
      }
    };

    const run = async () => {
      for (const node of locationNodes) {
        const lat = node.dataset.lat;
        const lng = node.dataset.lng;
        if (!lat || !lng) continue;
        const name = await reverseGeocode(lat, lng);
        node.textContent = name;
      }
    };

    run();
  }

})();
</script>
