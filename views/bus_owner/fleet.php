<?php
// Content-only Fleet view (structure only)
// Expects: $buses (array), BASE_URL defined by layout.

// Flash message from ?msg= query param
$_flashMsgs = [
    'created'     => ['Bus added successfully.',                                  true],
    'updated'     => ['Bus updated successfully.',                                true],
    'deleted'     => ['Bus deleted successfully.',                                true],
    'saved'       => ['Bus saved successfully.',                                  true],
    'assigned'    => ['Driver & conductor assigned.',                             true],
    'assign_blocked' => ['Assignment locked: bus is Maintenance or Out of Service.', false],
    'duplicate'   => ['A bus with that registration number already exists.',      false],
    'assign_fail' => ['Assignment failed - bus not found or not owned by you.',   false],
    'error'       => ['An error occurred. Please try again.',                    false],
];
$_flashKey  = $_GET['msg'] ?? '';
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
"><?= htmlspecialchars($_flashData[0]); ?></div>
<style>@keyframes flashIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}</style>
<script>setTimeout(function(){var e=document.getElementById('page-flash');if(e){e.style.transition='opacity .4s';e.style.opacity='0';setTimeout(function(){e.remove();},400);}},2800);</script>
<?php endif; ?>

<header class="page-header">
  <div>
    <h2 class="page-title">Fleet Management</h2>
    <p class="page-subtitle">Manage and monitor your bus fleet</p>
  </div>
  <a href="#" id="btnAddBus" class="add-bus-btn">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
      <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    Add New Bus
  </a>
</header>

<?php
// Extract unique routes for the Route filter dropdown
$uniqueRoutes = [];
if (!empty($buses)) {
    foreach ($buses as $b) {
        $routeNum = $b['route_number'] ?? '';
        if ($routeNum !== '' && !in_array($routeNum, $uniqueRoutes)) {
            $uniqueRoutes[] = $routeNum;
        }
    }
    sort($uniqueRoutes);
}
?>

<!-- Filter Bar & Search -->
<div class="filter-bar">
  <div class="filter-group">
    <label for="filter-status">Status:</label>
    <select id="filter-status" class="filter-select">
      <option value="all">All</option>
      <option value="Active">Active</option>
      <option value="Maintenance">Maintenance</option>
      <option value="Inactive">Out of Service</option>
    </select>
  </div>

  <div class="filter-group">
    <label for="filter-assignment">Assignment:</label>
    <select id="filter-assignment" class="filter-select">
      <option value="all">All</option>
      <option value="fully">Fully Assigned</option>
      <option value="missing-driver">Missing Driver</option>
      <option value="missing-conductor">Missing Conductor</option>
      <option value="unassigned">Unassigned</option>
    </select>
  </div>

  <div class="filter-group">
    <label for="filter-route">Route:</label>
    <select id="filter-route" class="filter-select">
      <option value="all">All</option>
      <?php foreach ($uniqueRoutes as $route): ?>
        <option value="<?= htmlspecialchars($route); ?>"><?= htmlspecialchars($route); ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="filter-group">
    <label for="filter-bus-class">Bus Class:</label>
    <select id="filter-bus-class" class="filter-select">
      <option value="all">All</option>
      <option value="Normal">Normal</option>
      <option value="Semi-Luxury">Semi-Luxury</option>
      <option value="AC">AC</option>
    </select>
  </div>

  <div class="filter-group">
    <label for="filter-bus-age">Bus Age:</label>
    <select id="filter-bus-age" class="filter-select">
      <option value="all">All</option>
      <option value="0-5">0-5 years</option>
      <option value="6-10">6-10 years</option>
      <option value="11-15">11-15 years</option>
      <option value="16+">16+ years</option>
      <option value="unknown">Unknown year</option>
    </select>
  </div>

  <div class="search-container">
    <svg class="search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
      <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2"/>
      <path d="M12.5 12.5l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    <input type="text" id="fleet-search" class="search-input" placeholder="Search by bus number, driver, or conductor...">
  </div>
</div>

<!-- Fleet Overview Table -->
<div class="card">
  <h3 class="card-title">Fleet Overview</h3>

  <?php if (!empty($buses)): ?>
    <div class="fleet-cards-grid" id="fleet-cards-grid">
      <?php foreach ($buses as $b): ?>
        <?php
          $status = !empty($b['status']) ? (string)$b['status'] : 'Active';
          $statusDisplay = ($status === 'Inactive') ? 'Out of Service' : $status;
          $isAssignmentLocked = in_array(strtolower($status), ['maintenance', 'inactive'], true);
          $map = ['Active'=>'status-active','Maintenance'=>'status-maintenance','Out of Service'=>'status-out','Inactive'=>'status-out'];
          $cls = $map[$status] ?? 'status-active';
          $drvName  = $b['driver_name']     ?? $b['assigned_driver']   ?? $b['driver']    ?? null;
          $condName = $b['conductor_name']  ?? $b['assigned_conductor']?? $b['conductor'] ?? null;
          $hasDriver = !empty($drvName) ? '1' : '0';
          $hasConductor = !empty($condName) ? '1' : '0';
          $routeNo = trim((string)($b['route_number'] ?? ''));
          $routeTagText = $routeNo !== '' ? ('Route ' . $routeNo) : 'Route Unassigned';
          $destinationText = trim((string)($b['route'] ?? '')) !== '' ? (string)$b['route'] : 'Unassigned Destination';
          $busClass = trim((string)($b['bus_class'] ?? '')) !== '' ? (string)$b['bus_class'] : 'Normal';
            $busIconClass = 'fleet-card-bus-icon--normal';
            if ($busClass === 'Semi-Luxury') {
              $busIconClass = 'fleet-card-bus-icon--semi-luxury';
            } elseif ($busClass === 'AC') {
              $busIconClass = 'fleet-card-bus-icon--ac';
            }
        ?>
        <article class="fleet-card js-bus-profile-card"
          data-bus-number="<?= htmlspecialchars($b['bus_number'] ?? ''); ?>"
          data-status="<?= htmlspecialchars($status); ?>"
          data-driver-assigned="<?= $hasDriver; ?>"
          data-conductor-assigned="<?= $hasConductor; ?>"
          data-route-number="<?= htmlspecialchars($b['route_number'] ?? ''); ?>"
          data-route-tag="<?= htmlspecialchars($routeTagText); ?>"
          data-destination="<?= htmlspecialchars($destinationText); ?>"
          data-location="<?= htmlspecialchars($b['current_location'] ?? ''); ?>"
          data-capacity="<?= (int)($b['capacity'] ?? 0); ?>"
          data-bus-class="<?= htmlspecialchars($busClass); ?>"
          data-manufactured-date="<?= htmlspecialchars($b['manufactured_date'] ?? ''); ?>"
          data-manufactured-year="<?= htmlspecialchars((string)($b['manufactured_year'] ?? '')); ?>"
          data-model="<?= htmlspecialchars($b['model'] ?? ''); ?>"
          data-chassis-no="<?= htmlspecialchars($b['chassis_no'] ?? ''); ?>"
          data-driver-license="<?= htmlspecialchars($b['driver_license'] ?? ''); ?>"
          data-conductor-id="<?= htmlspecialchars((string)($b['conductor_id_display'] ?? '')); ?>"
          data-driver-name="<?= htmlspecialchars($drvName ?? ''); ?>"
          data-conductor-name="<?= htmlspecialchars($condName ?? ''); ?>"
          tabindex="0"
        >
          <div class="fleet-card-head">
            <div class="fleet-card-reg-wrap">
              <span class="fleet-card-reg"><?= htmlspecialchars($b['bus_number'] ?? ''); ?></span>
              <span class="fleet-card-bus-icon <?= htmlspecialchars($busIconClass); ?>" aria-hidden="true">🚌</span>
            </div>
            <div class="fleet-card-status-wrap">
              <span class="status-badge <?= $cls; ?>"><?= htmlspecialchars($statusDisplay); ?></span>
              <?php if ($isAssignmentLocked): ?>
                <span class="maintenance-lock-badge" title="Assignments are disabled for Maintenance and Out of Service buses">Assignment Locked</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="fleet-card-body">
            <div class="fleet-card-row fleet-card-row--full">
              <span class="fleet-card-label">Route:</span>
              <div class="destination-wrap">
                <span><?= htmlspecialchars($destinationText); ?></span>
                <span class="route-tag"><?= htmlspecialchars($routeTagText); ?></span>
              </div>
            </div>

            <div class="fleet-card-grid-two">
              <div class="fleet-card-row">
                <span class="fleet-card-label">Model:</span>
                <span class="fleet-card-value"><?= htmlspecialchars($b['model'] ?? '—'); ?></span>
              </div>
              <div class="fleet-card-row">
                <span class="fleet-card-label">Year:</span>
                <span class="fleet-card-value"><?= htmlspecialchars((string)($b['manufactured_year'] ?? '—')); ?></span>
              </div>
              <div class="fleet-card-row">
                <span class="fleet-card-label">Capacity:</span>
                <span class="fleet-card-value"><?= (int)($b['capacity'] ?? 0); ?> seats</span>
              </div>
              <div class="fleet-card-row">
                <span class="fleet-card-label">Class:</span>
                <span class="fleet-card-value"><?= htmlspecialchars($busClass); ?></span>
              </div>
            </div>

            <div class="fleet-card-row fleet-card-row--full">
              <span class="fleet-card-label">Current Location:</span>
              <span class="fleet-card-location"><?= htmlspecialchars($b['current_location'] ?? '—'); ?></span>
            </div>
          </div>

          <div class="fleet-card-footer">
            <div class="action-buttons">
              <a href="#" class="icon-btn icon-btn-edit js-edit-bus" title="Edit"
                 data-bus='<?= htmlspecialchars(json_encode($b, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>'>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                  <path d="M13 2l3 3-9 9H4v-3l9-9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="#" class="icon-btn icon-btn-delete js-del-bus" title="Delete" data-bus-reg="<?= htmlspecialchars($b['bus_number'] ?? ''); ?>">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                  <path d="M2 5h14M7 8v5M11 8v5M3 5l1 10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-10M6 5V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </a>
                <a href="#" class="icon-btn js-assign<?= $isAssignmentLocked ? ' is-disabled' : ''; ?>" title="<?= $isAssignmentLocked ? 'Unavailable while bus is Maintenance or Out of Service' : 'Assign Driver/Conductor'; ?>"
                 data-bus-reg="<?= htmlspecialchars($b['bus_number'] ?? ''); ?>"
                 data-driver-id="<?= isset($b['driver_id']) ? (int)$b['driver_id'] : 0; ?>"
                 data-conductor-id="<?= isset($b['conductor_id']) ? (int)$b['conductor_id'] : 0; ?>"
                  data-assign-disabled="<?= $isAssignmentLocked ? '1' : '0'; ?>"
                  aria-disabled="<?= $isAssignmentLocked ? 'true' : 'false'; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M16 11a4 4 0 1 0-3.999-4A4 4 0 0 0 16 11Zm-8 3a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm8 2c-2.21 0-6 1.11-6 3.33V22h12v-2.67C22 17.11 18.21 16 16 16Zm-8-1c-2.67 0-8 1.34-8 4v3h6v-2.67" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:40px;color:#6B7280;">
      No buses found. Click "Add New Bus" to add your first bus.
    </div>
  <?php endif; ?>
</div>

<script>
/* ---- Fleet filters on cards ---- */
document.addEventListener('DOMContentLoaded', function () {
  var grid = document.getElementById('fleet-cards-grid');
  if (!grid) return;

  var statusFilter   = document.getElementById('filter-status');
  var assignFilter   = document.getElementById('filter-assignment');
  var routeFilter    = document.getElementById('filter-route');
  var busClassFilter = document.getElementById('filter-bus-class');
  var busAgeFilter   = document.getElementById('filter-bus-age');
  var searchInput    = document.getElementById('fleet-search');

  var allCards = Array.from(grid.querySelectorAll('.fleet-card'));

  var debounceTimer;
  function filterCards() {
    var status     = statusFilter   ? statusFilter.value   : 'all';
    var assignment = assignFilter   ? assignFilter.value   : 'all';
    var route      = routeFilter    ? routeFilter.value    : 'all';
    var busClass   = busClassFilter ? busClassFilter.value : 'all';
    var busAge     = busAgeFilter   ? busAgeFilter.value   : 'all';
    var term       = searchInput    ? searchInput.value.toLowerCase().trim() : '';

    allCards.forEach(function (card) {
      var visible = true;
      if (status !== 'all' && card.dataset.status !== status) visible = false;
      if (assignment !== 'all') {
        var hd = card.dataset.driverAssigned === '1';
        var hc = card.dataset.conductorAssigned === '1';
        if (assignment === 'fully' && !(hd && hc)) visible = false;
        else if (assignment === 'missing-driver' && hd) visible = false;
        else if (assignment === 'missing-conductor' && hc) visible = false;
        else if (assignment === 'unassigned' && (hd || hc)) visible = false;
      }
      if (route !== 'all' && card.dataset.routeNumber !== route) visible = false;
      if (busClass !== 'all' && card.dataset.busClass !== busClass) visible = false;
      if (busAge !== 'all') {
        var year = parseInt(card.dataset.manufacturedYear, 10);
        if (busAge === 'unknown') {
          if (!year || Number.isNaN(year)) visible = visible && true;
          else visible = false;
        } else {
          if (!year || Number.isNaN(year)) {
            visible = false;
          } else {
            var age = new Date().getFullYear() - year;
            if (busAge === '0-5' && (age < 0 || age > 5)) visible = false;
            else if (busAge === '6-10' && (age < 6 || age > 10)) visible = false;
            else if (busAge === '11-15' && (age < 11 || age > 15)) visible = false;
            else if (busAge === '16+' && age < 16) visible = false;
          }
        }
      }
      if (term) {
        var bn = (card.dataset.busNumber || '').toLowerCase();
        var dn = (card.dataset.driverName || '').toLowerCase();
        var cn = (card.dataset.conductorName || '').toLowerCase();
        if (!bn.includes(term) && !dn.includes(term) && !cn.includes(term)) visible = false;
      }
      card.style.display = visible ? '' : 'none';
    });
  }

  if (statusFilter)   statusFilter.addEventListener('change', filterCards);
  if (assignFilter)   assignFilter.addEventListener('change', filterCards);
  if (routeFilter)    routeFilter.addEventListener('change', filterCards);
  if (busClassFilter) busClassFilter.addEventListener('change', filterCards);
  if (busAgeFilter)   busAgeFilter.addEventListener('change', filterCards);
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(filterCards, 250);
    });
  }
  filterCards();
});
</script>

<style>
  .fleet-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
  }
  .fleet-card {
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
  }
  .fleet-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
    border-color: #E3C36D;
  }
  .fleet-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px;
    border-bottom: 1px solid #E5E7EB;
  }
  .fleet-card-reg-wrap { display: flex; align-items: center; gap: 8px; }
  .fleet-card-status-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
  }
  .maintenance-lock-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    border: 1px solid #FCA5A5;
    background: #FEF2F2;
    color: #991B1B;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
  }
  .fleet-card-reg {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
    padding: 4px 10px;
    border-radius: 7px;
    background: #7F1D3A;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .4px;
  }
  .fleet-card-bus-icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #9CA3AF;
    font-size: 13px;
  }
  .fleet-card-bus-icon--normal {
    background: #9CA3AF;
  }
  .fleet-card-bus-icon--semi-luxury {
    background: #FACC15;
  }
  .fleet-card-bus-icon--ac {
    background: #D4AF37;
  }
  .fleet-card-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border-bottom: 1px solid #E5E7EB;
  }
  .fleet-card-grid-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 14px;
  }
  .fleet-card-row { display: flex; flex-direction: column; gap: 4px; }
  .fleet-card-row--full { grid-column: 1 / -1; }
  .fleet-card-label {
    font-size: 11px;
    font-weight: 700;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: .4px;
  }
  .fleet-card-value {
    font-size: 15px;
    font-weight: 700;
    color: #374151;
  }
  .fleet-card-location {
    font-size: 14px;
    font-weight: 700;
    color: #7F1D3A;
  }
  .destination-wrap {
    display: inline-flex;
    flex-direction: column;
    gap: 7px;
    align-items: flex-start;
  }
  .route-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    color: #9A3412;
    font-size: 12px;
    font-weight: 700;
  }
  .fleet-card-footer {
    display: flex;
    justify-content: flex-end;
    padding: 10px 12px;
    background: #F9FAFB;
  }
  .fleet-card-footer .action-buttons { gap: 8px; }
  .fleet-card-footer .icon-btn { width: 34px; height: 34px; }
  .fleet-card-footer .icon-btn.is-disabled {
    pointer-events: none;
    opacity: .45;
    border-color: #D1D5DB;
    color: #9CA3AF;
    background: #F9FAFB;
  }

  @media (max-width: 640px) {
    .fleet-cards-grid { grid-template-columns: 1fr; }
  }
</style>

<!-- Assign Driver/Conductor Modal -->
<div class="modal" id="assignModal" hidden>
  <div class="modal__backdrop"></div>
  <div class="assign-modal__dialog">
    <div class="assign-modal__header">
      <div>
        <h3 class="assign-modal__title">Assign Driver &amp; Conductor</h3>
        <p class="assign-modal__subtitle">Select staff for this bus</p>
      </div>
      <button class="bus-modal__close" id="assignClose" aria-label="Close">&times;</button>
    </div>
    <form id="assignForm" action="<?= BASE_URL; ?>/fleet/assign" method="POST">
      <input type="hidden" name="reg_no" id="assign_reg_no" />
      <div class="assign-modal__grid">
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="assign_driver_id">Driver</label>
          <select id="assign_driver_id" name="driver_id" class="bus-modal__input">
            <option value="">Select Driver</option>
            <option value="0">-- Unassigned --</option>
            <?php if (!empty($drivers)): ?>
              <?php foreach ($drivers as $d): ?>
                <option value="<?= $d['private_driver_id'] ?>">
                  <?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['license_no']) ?>)
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="assign_conductor_id">Conductor</label>
          <select id="assign_conductor_id" name="conductor_id" class="bus-modal__input">
            <option value="">Select Conductor</option>
            <option value="0">-- Unassigned --</option>
            <?php if (!empty($conductors)): ?>
              <?php foreach ($conductors as $c): ?>
                <option value="<?= $c['private_conductor_id'] ?>">
                  <?= htmlspecialchars($c['full_name']) ?> (ID: <?= $c['private_conductor_id'] ?>)
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>
      <div class="assign-modal__footer">
        <button type="button" class="bus-modal__btn bus-modal__btn--cancel" id="assignCancel">Cancel</button>
        <button type="submit" class="bus-modal__btn bus-modal__btn--submit">Assign</button>
      </div>
    </form>
  </div>
</div>

<style>
  .assign-modal__dialog   { position: relative; z-index: 1; width: min(500px, 92vw); background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.18); overflow: hidden; animation: dialog-in .18s cubic-bezier(.2,.8,.2,1) both; }
  .assign-modal__header   { display: flex; align-items: flex-start; justify-content: space-between; padding: 22px 24px 0; }
  .assign-modal__title    { font-size: 18px; font-weight: 700; color: var(--maroon); margin: 0 0 4px; }
  .assign-modal__subtitle { font-size: 12px; color: #6B7280; margin: 0; }
  .assign-modal__grid     { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px 24px; }
  .assign-modal__footer   { display: flex; justify-content: flex-end; gap: 10px; padding: 0 24px 22px; }
</style>

<script>
(function() {
  var modal      = document.getElementById('assignModal');
  var form       = document.getElementById('assignForm');
  var btnClose   = document.getElementById('assignClose');
  var btnCancel  = document.getElementById('assignCancel');
  var driverSel  = document.getElementById('assign_driver_id');
  var condSel    = document.getElementById('assign_conductor_id');
  var regNoInp   = document.getElementById('assign_reg_no');

  if (!modal || !form) return;

  var currentRegNo = '';

  function openModal(regNo, driverId, conductorId) {
    currentRegNo = regNo || '';
    if (regNoInp)  regNoInp.value  = currentRegNo;
    if (driverSel) driverSel.value = (driverId && driverId !== '0') ? driverId : '';
    if (condSel)   condSel.value   = (conductorId && conductorId !== '0') ? conductorId : '';
    modal.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.setAttribute('hidden', '');
    document.body.style.overflow = '';
    currentRegNo = '';
  }

  // Wire every assign button
  document.querySelectorAll('.js-assign').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (btn.getAttribute('data-assign-disabled') === '1') {
        alert('This bus is Maintenance or Out of Service. Assignment is disabled.');
        return;
      }
      openModal(
        btn.getAttribute('data-bus-reg')       || '',
        btn.getAttribute('data-driver-id')     || '',
        btn.getAttribute('data-conductor-id')  || ''
      );
    });
  });

  if (btnClose)  btnClose.addEventListener('click',  function(e) { e.preventDefault(); closeModal(); });
  if (btnCancel) btnCancel.addEventListener('click', function(e) { e.preventDefault(); closeModal(); });

  var backdrop = modal.querySelector('.modal__backdrop');
  if (backdrop) backdrop.addEventListener('click', closeModal);

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
  });

  // Submit: build a fresh form (avoids any hidden-input staleness)
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    var regNo       = currentRegNo || (regNoInp ? regNoInp.value.trim() : '');
    var driverId    = driverSel ? driverSel.value   : '';
    var conductorId = condSel   ? condSel.value     : '';

    if (!regNo) {
      alert('Cannot determine bus - please close and try again.');
      return;
    }

    var f = document.createElement('form');
    f.method = 'POST';
    f.action = form.getAttribute('action');

    function addField(name, value) {
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = name; i.value = (value == null ? '' : String(value));
      f.appendChild(i);
    }

    addField('reg_no',       regNo);
    addField('driver_id',    driverId);
    addField('conductor_id', conductorId);

    document.body.appendChild(f);
    f.submit();
  });
})();
</script>

<!-- Add/Edit Bus Modal -->
<div id="busModal" class="bus-modal" hidden>
  <div class="bus-modal__backdrop"></div>
  <div class="bus-modal__panel">
    <div class="bus-modal__header">
      <div>
        <h2 class="bus-modal__title" id="busModalTitle">Add New Bus</h2>
        <p class="bus-modal__subtitle">Enter bus details below</p>
      </div>
      <button type="button" class="bus-modal__close" id="btnCloseBusModal" aria-label="Close">&times;</button>
    </div>

    <form id="busForm" action="<?= BASE_URL; ?>/fleet" method="post">
      <input type="hidden" id="bus_id" name="bus_id">
      <input type="hidden" name="action" id="bus_action" value="create">
      <input type="hidden" id="bus_reg_no_hidden" name="reg_no">

      <div class="bus-modal__grid">
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_reg_no">Registration Number *</label>
          <input type="text" id="bus_reg_no" class="bus-modal__input" placeholder="e.g., WP ABC-1234" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_chassis_no">Chassis Number *</label>
          <input type="text" name="chassis_no" id="bus_chassis_no" class="bus-modal__input" placeholder="e.g., CHASSIS123456" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_capacity">Capacity (Seats) *</label>
          <input type="number" name="capacity" id="bus_capacity" class="bus-modal__input" placeholder="e.g., 50" min="1" max="200" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_manufactured_date">Manufactured Date *</label>
          <input type="date" name="manufactured_date" id="bus_manufactured_date" class="bus-modal__input" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_manufactured_year">Year *</label>
          <input type="number" name="manufactured_year" id="bus_manufactured_year" class="bus-modal__input" placeholder="e.g., 2024" min="1900" max="2100" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_model">Model *</label>
          <input type="text" name="model" id="bus_model" class="bus-modal__input" placeholder="e.g., Ashok Leyland Viking" required>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_class">Bus Class *</label>
          <select name="bus_class" id="bus_class" class="bus-modal__input" required>
            <option value="Normal">Normal</option>
            <option value="Semi-Luxury">Semi-Luxury</option>
            <option value="AC">AC</option>
          </select>
        </div>
        <div class="bus-modal__field">
          <label class="bus-modal__label" for="bus_status">Status</label>
          <select name="status" id="bus_status" class="bus-modal__input">
            <option value="Active">Active</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Inactive">Out of Service</option>
          </select>
        </div>
      </div>

      <div class="bus-modal__footer">
        <a href="#" id="btnCancelBusModal" class="bus-modal__btn bus-modal__btn--cancel">Cancel</a>
        <button type="submit" class="bus-modal__btn bus-modal__btn--submit" id="btnSubmitBusModal">Add Bus</button>
      </div>
    </form>
  </div>
</div>

<style>
  .bus-modal[hidden]          { display: none; }
  .bus-modal                  { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
  .bus-modal__backdrop        { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
  .bus-modal__panel           { position: relative; width: min(520px, 95vw); background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.18); overflow: hidden; }
  .bus-modal__header          { display: flex; align-items: flex-start; justify-content: space-between; padding: 24px 24px 0; }
  .bus-modal__title           { font-size: 20px; font-weight: 700; color: var(--maroon); margin: 0 0 4px; }
  .bus-modal__subtitle        { font-size: 13px; color: #6B7280; margin: 0; }
  .bus-modal__close           { background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF; line-height: 1; padding: 0; margin-left: 12px; }
  .bus-modal__close:hover     { color: #374151; }
  .bus-modal__grid            { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 20px 24px; }
  .bus-modal__field           { display: flex; flex-direction: column; gap: 6px; }
  .bus-modal__label           { font-size: 13px; font-weight: 600; color: #374151; }
  .bus-modal__input           { width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; color: #111827; box-sizing: border-box; transition: border-color .15s; }
  .bus-modal__input:focus     { outline: none; border-color: var(--maroon); box-shadow: 0 0 0 3px rgba(127,0,50,.08); }
  .bus-modal__input[readonly] { background: #F9FAFB; color: #6B7280; cursor: not-allowed; }
  .bus-modal__footer          { display: flex; justify-content: flex-end; gap: 10px; padding: 0 24px 24px; }
  .bus-modal__btn             { padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; transition: background .18s, transform .1s; }
  .bus-modal__btn--cancel     { background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB; }
  .bus-modal__btn--cancel:hover { background: #E5E7EB; }
  .bus-modal__btn--submit     { background: var(--gold); color: var(--maroon); }
  .bus-modal__btn--submit:hover { background: #F59E0B; }
</style>

<script>
(function() {
  var modal        = document.getElementById('busModal');
  var form         = document.getElementById('busForm');
  var btnAdd       = document.getElementById('btnAddBus');
  var btnCancel    = document.getElementById('btnCancelBusModal');
  var btnClose     = document.getElementById('btnCloseBusModal');
  var btnSubmit    = document.getElementById('btnSubmitBusModal');
  var modalTitle   = document.getElementById('busModalTitle');
  var actionInput  = document.getElementById('bus_action');
  var regNoVisible = document.getElementById('bus_reg_no');
  var regNoHidden  = document.getElementById('bus_reg_no_hidden');

  function openModal() { modal.removeAttribute('hidden'); }
  function closeModal() { modal.setAttribute('hidden', ''); }

  // Open for adding new bus
  if (btnAdd) {
    btnAdd.addEventListener('click', function(e) {
      e.preventDefault();
      form.reset();
      regNoVisible.value   = '';
      regNoHidden.value    = '';
      regNoVisible.readOnly = false;
      regNoVisible.style.background = '';
      actionInput.value    = 'create';
      modalTitle.textContent = 'Add New Bus';
      btnSubmit.textContent  = 'Add Bus';
      openModal();
    });
  }

  // Open for editing an existing bus
  document.querySelectorAll('.js-edit-bus').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var busData = {};
      try { busData = JSON.parse(this.getAttribute('data-bus') || '{}'); } catch(err) { return; }

      regNoVisible.value    = busData.bus_number || '';
      regNoHidden.value     = busData.bus_number || '';
      regNoVisible.readOnly = true;
      regNoVisible.style.background = '#f3f4f6';

      document.getElementById('bus_chassis_no').value = busData.chassis_no || '';
      document.getElementById('bus_capacity').value   = busData.capacity   || '';
      document.getElementById('bus_manufactured_date').value = busData.manufactured_date || '';
      document.getElementById('bus_manufactured_year').value = busData.manufactured_year || '';
      document.getElementById('bus_model').value = busData.model || '';
      document.getElementById('bus_class').value = busData.bus_class || 'Normal';
      document.getElementById('bus_status').value     = busData.status     || 'Active';

      actionInput.value      = 'update';
      modalTitle.textContent = 'Edit Bus';
      btnSubmit.textContent  = 'Update Bus';
      openModal();
    });
  });

  // Close handlers
  if (btnCancel) btnCancel.addEventListener('click', function(e) { e.preventDefault(); closeModal(); });
  if (btnClose)  btnClose.addEventListener('click',  function()  { closeModal(); });
  var backdrop = modal ? modal.querySelector('.bus-modal__backdrop') : null;
  if (backdrop) backdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal(); });

  // Form submit - plain native submit, no fetch (session cookie sent automatically)
  if (form) {
    form.addEventListener('submit', function(e) {
      // Sync visible reg_no display field to hidden field (for create mode)
      if (actionInput.value === 'create') {
        regNoHidden.value = regNoVisible.value.trim();
      }
      // Validate
      if (!regNoHidden.value) {
        e.preventDefault();
        regNoVisible.focus();
        regNoVisible.style.borderColor = '#DC2626';
        return;
      }
      regNoVisible.style.borderColor = '';
      // Let the browser submit the form normally - cookies included automatically
    });
  }
})();
</script>

<style>
  .profile-modal[hidden] { display: none !important; }
  .profile-modal { position: fixed; inset: 0; z-index: 1000000; display: flex; align-items: center; justify-content: center; padding: 16px; }
  .profile-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
  .profile-modal__panel { position: relative; width: min(700px, 100%); max-height: 88vh; overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 20px 60px rgba(0,0,0,.22); display: flex; flex-direction: column; }
  .profile-modal__hero { background: linear-gradient(135deg, #7F0032 0%, #9B1042 100%); padding: 24px; display: flex; align-items: center; gap: 14px; }
  .profile-modal__avatar { width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,.2); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .profile-modal__hero-name { color: #fff; margin: 0 0 3px; font-size: 20px; font-weight: 700; }
  .profile-modal__hero-id { color: rgba(255,255,255,.8); margin: 0; font-size: 13px; }
  .profile-modal__close { margin-left: auto; background: rgba(255,255,255,.2); border: 0; width: 34px; height: 34px; border-radius: 50%; color: #fff; font-size: 18px; cursor: pointer; }
  .profile-modal__body { overflow-y: auto; }
  .profile-modal__section { padding: 18px 24px; border-bottom: 1px solid #EFE7D2; }
  .profile-modal__section-title { margin: 0 0 10px; color: #9CA3AF; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
  .profile-modal__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .profile-modal__field { display: flex; flex-direction: column; gap: 2px; }
  .profile-modal__field-label { font-size: 11px; color: #9CA3AF; text-transform: uppercase; font-weight: 700; }
  .profile-modal__field-value { font-size: 14px; font-weight: 600; color: #111827; }
  .profile-modal__field-value--mono { font-family: 'Courier New', monospace; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 3px 8px; display: inline-block; }
  .js-bus-profile-card { cursor: pointer; }
  .js-bus-profile-card:focus-visible { outline: 2px solid #7F0032; outline-offset: 2px; }
</style>

<div id="busProfileModal" class="profile-modal" hidden>
  <div class="profile-modal__backdrop" id="busProfileBackdrop"></div>
  <div class="profile-modal__panel">
    <div class="profile-modal__hero">
      <div class="profile-modal__avatar" id="bpAvatar">B</div>
      <div>
        <h2 class="profile-modal__hero-name" id="bpBusNo">Bus</h2>
        <p class="profile-modal__hero-id" id="bpRouteLine">Destination</p>
      </div>
      <button type="button" class="profile-modal__close" id="btnCloseBusProfile">×</button>
    </div>
    <div class="profile-modal__body">
      <div class="profile-modal__section">
        <p class="profile-modal__section-title">Bus Details</p>
        <div class="profile-modal__grid">
          <div class="profile-modal__field"><span class="profile-modal__field-label">Bus Class</span><span class="profile-modal__field-value" id="bpBusClass">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Model</span><span class="profile-modal__field-value" id="bpModel">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Capacity</span><span class="profile-modal__field-value" id="bpCapacity">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Year</span><span class="profile-modal__field-value" id="bpYear">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Manufactured Date</span><span class="profile-modal__field-value" id="bpDate">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Chassis Number</span><span class="profile-modal__field-value profile-modal__field-value--mono" id="bpChassis">-</span></div>
        </div>
      </div>
      <div class="profile-modal__section">
        <p class="profile-modal__section-title">Assignments</p>
        <div class="profile-modal__grid">
          <div class="profile-modal__field"><span class="profile-modal__field-label">Route</span><span class="profile-modal__field-value" id="bpRouteTag">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Current Location</span><span class="profile-modal__field-value" id="bpLocation">-</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Assigned Driver</span><span class="profile-modal__field-value" id="bpDriver">Unassigned</span></div>
          <div class="profile-modal__field"><span class="profile-modal__field-label">Assigned Conductor</span><span class="profile-modal__field-value" id="bpConductor">Unassigned</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var modal = document.getElementById('busProfileModal');
  if (!modal) return;
  var closeBtn = document.getElementById('btnCloseBusProfile');
  var backdrop = document.getElementById('busProfileBackdrop');

  function text(v, fallback) {
    var t = (v || '').toString().trim();
    return t ? t : fallback;
  }

  function fill(card) {
    var busNo = text(card.dataset.busNumber, '-');
    var driver = text(card.dataset.driverName, 'Unassigned');
    var conductor = text(card.dataset.conductorName, 'Unassigned');
    var driverLicense = text(card.dataset.driverLicense, 'N/A');
    var conductorId = text(card.dataset.conductorId, 'N/A');

    document.getElementById('bpAvatar').textContent = busNo.slice(0, 2).toUpperCase();
    document.getElementById('bpBusNo').textContent = busNo;
    document.getElementById('bpRouteLine').textContent = text(card.dataset.destination, 'Unassigned Destination');
    document.getElementById('bpBusClass').textContent = text(card.dataset.busClass, 'Normal');
    document.getElementById('bpModel').textContent = text(card.dataset.model, '-');
    document.getElementById('bpCapacity').textContent = text(card.dataset.capacity, '0') + ' seats';
    document.getElementById('bpYear').textContent = text(card.dataset.manufacturedYear, '-');
    document.getElementById('bpDate').textContent = text(card.dataset.manufacturedDate, '-');
    document.getElementById('bpChassis').textContent = text(card.dataset.chassisNo, '-');
    document.getElementById('bpRouteTag').textContent = text(card.dataset.routeTag, 'Route Unassigned');
    document.getElementById('bpLocation').textContent = text(card.dataset.location, '-');
    document.getElementById('bpDriver').textContent = driver === 'Unassigned' ? 'Unassigned' : (driver + ' (' + driverLicense + ')');
    document.getElementById('bpConductor').textContent = conductor === 'Unassigned' ? 'Unassigned' : (conductor + ' (ID: ' + conductorId + ')');
  }

  function open(card) {
    fill(card);
    modal.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    modal.setAttribute('hidden', '');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-bus-profile-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
      if (e.target.closest('.action-buttons, .icon-btn, a, button, input, select, textarea, form, label')) return;
      open(card);
    });
    card.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        open(card);
      }
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', close);
  if (backdrop) backdrop.addEventListener('click', close);
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !modal.hasAttribute('hidden')) close();
  });
})();
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" hidden>
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width: 400px; padding: 0;">
    <div class="modal__header" style="border-bottom: none; padding-bottom: 0;">
      <h3 class="modal__title" style="color: #991B1B; display: flex; align-items: center; gap: 10px;">
        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Delete Bus
      </h3>
      <button type="button" class="modal__close" id="btnCloseDelete">&times;</button>
    </div>
    <div class="modal__form" style="padding-top: 10px;">
      <p style="color: #4B5563; font-size: 15px; margin: 0;">Are you sure you want to delete this bus? This action cannot be undone.</p>
    </div>
    <div class="modal__footer" style="border-top: none; background: #FEF2F2; border-radius: 0 0 16px 16px;">
      <button type="button" class="btn-secondary" id="btnCancelDelete" style="background: white; border: 1px solid #E5E7EB;">Cancel</button>
      <button type="button" class="btn-primary" id="btnConfirmDelete" style="background: #DC2626; border: none; color: white;">Yes, Delete</button>
    </div>
  </div>
</div>

<script>
// Bus delete handler
(function() {
  let deleteReg = null;
  const deleteModal = document.getElementById('deleteConfirmModal');
  const btnConfirmDelete = document.getElementById('btnConfirmDelete');
  const btnCancelDelete = document.getElementById('btnCancelDelete');
  const btnCloseDelete = document.getElementById('btnCloseDelete');

  function closeDeleteModal() {
    deleteModal.setAttribute('hidden', '');
    deleteReg = null;
  }

  if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
  if (btnCloseDelete) btnCloseDelete.addEventListener('click', closeDeleteModal);
  
  const deleteBackdrop = deleteModal?.querySelector('.modal__backdrop');
  if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDeleteModal);

  document.querySelectorAll('.js-del-bus').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      deleteReg = this.getAttribute('data-bus-reg');
      if (!deleteReg) return;
      
      if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
      }
      deleteModal.removeAttribute('hidden');
    });
  });

  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', function() {
      if (!deleteReg) return;

      const originalText = btnConfirmDelete.textContent;
      btnConfirmDelete.textContent = 'Deleting...';
      btnConfirmDelete.disabled = true;

      // Submit form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '<?= BASE_URL; ?>/fleet';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'delete';
      
      const regInput = document.createElement('input');
      regInput.type = 'hidden';
      regInput.name = 'reg_no';
      regInput.value = deleteReg;
      
      form.appendChild(actionInput);
      form.appendChild(regInput);
      document.body.appendChild(form);
      form.submit();
    });
  }
})();
</script>

