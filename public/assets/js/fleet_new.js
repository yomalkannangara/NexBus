// Fleet Management JavaScript - Handles all interactions for the new Fleet UI

(function() {
  const page = document.getElementById('fleetPage');
  if (!page) return;

  const endpoint = window.location.pathname;
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function normalizeBusReg(value) {
    return String(value || '').trim().toUpperCase();
  }

  function focusBusCardFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const target = normalizeBusReg(params.get('focus_bus') || params.get('bus'));
    if (!target) return;

    const cards = $$('.js-bus-card[data-reg]');
    const match = cards.find(card => normalizeBusReg(card.getAttribute('data-reg')) === target);
    if (!match) return;

    match.scrollIntoView({ behavior: 'smooth', block: 'center' });
    match.classList.add('fleet-card-focus');
    setTimeout(() => {
      match.classList.remove('fleet-card-focus');
    }, 2200);

    if (window.history && typeof window.history.replaceState === 'function') {
      params.delete('focus_bus');
      const qs = params.toString();
      const nextUrl = window.location.pathname + (qs ? ('?' + qs) : '');
      window.history.replaceState({}, document.title, nextUrl);
    }
  }

  // ============= MODAL MANAGEMENT =============
  function openModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('modal-open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('modal-open');
  }

  function wireModalClose(modal) {
    $$('.fleet-modal-overlay, [data-close-modal]', modal).forEach(btn => {
      btn.addEventListener('click', (e) => {
        if (btn.classList.contains('fleet-modal-overlay') || btn.hasAttribute('data-close-modal')) {
          closeModal(modal);
        }
      });
    });
  }

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      $$('.fleet-modal[aria-hidden="false"]').forEach(m => closeModal(m));
    }
  });

  // ============= TOAST NOTIFICATIONS =============
  function toast(msg, ok = true) {
    const t = document.createElement('div');
    t.className = 'fleet-toast ' + (ok ? 'fleet-toast-ok' : 'fleet-toast-error');
    t.textContent = msg;
    t.style.cssText = `
      position: fixed;
      bottom: 24px;
      right: 24px;
      padding: 12px 20px;
      border-radius: 8px;
      color: #fff;
      font-weight: 600;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 3000;
      animation: slideIn 0.3s ease;
    `;
    if (ok) {
      t.style.background = 'linear-gradient(180deg, #1f7a54, #1a6a47)';
    } else {
      t.style.background = 'linear-gradient(180deg, #c92c4b, #b82643)';
    }
    document.body.appendChild(t);
    setTimeout(() => {
      t.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => t.remove(), 300);
    }, 2500);
  }

  // ============= API HELPERS =============
  async function postAction(action, dataObj) {
    const body = new URLSearchParams({ action, ...(dataObj || {}) });
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body
    });
    let ok = res.ok;
    try {
      const j = await res.json();
      ok = ok && !!j.ok;
    } catch (_) {}
    return ok;
  }

  // ============= BUS CLASS SELECTOR =============
  const classOptions = $$('.class-option');
  const busClassInput = $('#form_bus_class');

  classOptions.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const busClass = btn.getAttribute('data-class');
      
      classOptions.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      if (busClassInput) busClassInput.value = busClass;
    });
  });

  // ============= FORM VALIDATION =============
  function validateBusForm() {
    const regNo = $('#form_reg_no')?.value?.trim();
    const capacity = $('#form_capacity')?.value?.trim();
    const yearMfg = $('#form_year_manufacture')?.value?.trim();

    if (!regNo) {
      toast('Bus number is required', false);
      return false;
    }

    if (!capacity || isNaN(capacity) || parseInt(capacity) < 1 || parseInt(capacity) > 120) {
      toast('Please enter a valid capacity (1-120)', false);
      return false;
    }

    if (yearMfg && (isNaN(yearMfg) || parseInt(yearMfg) < 1980 || parseInt(yearMfg) > 2026)) {
      toast('Year must be between 1980 and 2026', false);
      return false;
    }

    return true;
  }

  // ============= CREATE BUS =============
  const modalBusForm = $('#modalBusForm');
  if (modalBusForm) wireModalClose(modalBusForm);

  const btnAddBus = $('#btnAddBus');
  const btnAddBusEmpty = $('#btnAddBusEmpty');

  if (btnAddBus) {
    btnAddBus.addEventListener('click', () => {
      resetBusForm();
      $('#modalBusFormTitle').textContent = 'Add New Bus to Fleet';
      $('#btnSaveBusForm').textContent = 'Add Bus';
      openModal(modalBusForm);
    });
  }

  if (btnAddBusEmpty) {
    btnAddBusEmpty.addEventListener('click', () => {
      resetBusForm();
      $('#modalBusFormTitle').textContent = 'Add New Bus to Fleet';
      $('#btnSaveBusForm').textContent = 'Add Bus';
      openModal(modalBusForm);
    });
  }

  function resetBusForm() {
    $('#form_reg_no').value = '';
    $('#form_status').value = 'Active';
    $('#form_chassis_no').value = '';
    $('#form_capacity').value = '';
    $('#form_bus_model').value = '';
    $('#form_year_manufacture').value = '';
    $('#form_bus_class').value = 'Normal';
    
    $$('.class-option').forEach((btn, idx) => {
      btn.classList.toggle('active', idx === 0);
    });
  }

  const btnSaveBusForm = $('#btnSaveBusForm');
  if (btnSaveBusForm) {
    btnSaveBusForm.addEventListener('click', async () => {
      if (!validateBusForm()) return;

      const isEdit = btnSaveBusForm.getAttribute('data-mode') === 'edit';
      const action = isEdit ? 'update_bus' : 'create_bus';

      const data = {
        reg_no: $('#form_reg_no').value,
        status: $('#form_status').value,
        chassis_no: $('#form_chassis_no').value,
        capacity: $('#form_capacity').value,
        bus_model: $('#form_bus_model').value,
        year_manufacture: $('#form_year_manufacture').value,
        bus_class: $('#form_bus_class').value
      };

      btnSaveBusForm.disabled = true;
      btnSaveBusForm.textContent = isEdit ? 'Updating...' : 'Adding...';

      const ok = await postAction(action, data);

      btnSaveBusForm.disabled = false;
      btnSaveBusForm.textContent = isEdit ? 'Update Bus' : 'Add Bus';

      if (ok) {
        toast(isEdit ? '✓ Bus updated successfully' : '✓ Bus added successfully');
        setTimeout(() => location.reload(), 1200);
      } else {
        toast(isEdit ? 'Failed to update bus' : 'Failed to create bus', false);
      }
    });
  }

  // ============= VIEW BUS PROFILE =============
  const modalBusProfile = $('#modalBusProfile');
  if (modalBusProfile) wireModalClose(modalBusProfile);

  const fleetCardsGrid = $('.fleet-cards-grid');
  if (fleetCardsGrid) {
    fleetCardsGrid.addEventListener('click', async (e) => {
      const viewBtn = e.target.closest('.js-view-profile');
      if (viewBtn) {
        const cardEl = viewBtn.closest('.fleet-card');
        if (cardEl) {
          await displayBusProfile(cardEl);
        }
        return;
      }

      if (e.target.closest('.card-action-btn') || e.target.closest('.js-location-link')) {
        return;
      }

      const cardEl = e.target.closest('.fleet-card');
      if (cardEl) {
        await displayBusProfile(cardEl);
      }
    });
  }

  async function fetchBusProfile(reg) {
    if (!reg) return null;
    try {
      const res = await fetch(`/M/bus_profile?reg_no=${encodeURIComponent(reg)}&json=1`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      });
      if (!res.ok) return null;
      const data = await res.json();
      return data.ok ? data.bus : null;
    } catch (err) {
      return null;
    }
  }

  const busCards = $$('.fleet-card');
  busCards.forEach(card => {
    card.style.cursor = 'pointer';
  });

  async function displayBusProfile(cardEl) {
    const reg = cardEl.getAttribute('data-reg');
    if (!reg) return;
    const remote = await fetchBusProfile(reg);
    const status = remote?.status || cardEl.querySelector('.status-badge')?.textContent?.trim() || '—';
    const route = remote?.route || cardEl.dataset.currentRoute || cardEl.querySelector('.card-route-info .card-value')?.textContent || '—';
    const busModel = remote?.bus_model || cardEl.dataset.busModel || '—';
    const year = remote?.year_of_manufacture || cardEl.dataset.year || cardEl.querySelector('.card-row:first-child .card-info:last-child .card-value')?.textContent || '—';
    const capacityValue = remote?.capacity || cardEl.dataset.capacity || cardEl.querySelector('.card-row:last-child .card-info:first-child .card-value')?.textContent?.replace(' seats', '') || '—';
    const busClassText = remote?.bus_class || cardEl.dataset.busClass || cardEl.querySelector('.card-row:last-child .card-info:last-child .card-value')?.textContent || 'Normal';
    const chassis = remote?.chassis_no || cardEl.dataset.chassis || '—';

    // Normalize the bus class and use a consistent color mapping for the profile icon
    const getBusIcon = (busClassText) => {
      const normalized = String(busClassText).trim().toLowerCase();
      if (normalized === 'semi luxury') return { color: '#3B82F6' };
      if (normalized === 'luxury') return { color: '#F59E0B' };
      return { color: '#64748B' };
    };

    const busIcon = getBusIcon(busClassText);

    $('#profileBusTitle').textContent = `Bus Profile - ${reg}`;
    
    const profileContent = $('#busProfileContent');
    profileContent.innerHTML = `
      <div class="bus-profile-header">
        <div class="bus-profile-avatar">
          <div class="bus-avatar-icon" style="background-color: ${busIcon.color};">
            🚌
          </div>
          <div class="bus-profile-reg">${reg}</div>
          <div class="bus-profile-status status-${status.toLowerCase().replace(' ', '-')}">${status}</div>
        </div>
      </div>

      <div class="bus-profile-grid">
        <div class="profile-section">
          <h3 class="profile-section-title">Vehicle Information</h3>
          <div class="profile-grid">
            <div class="profile-field">
              <label class="profile-field-label">Bus Model</label>
              <div class="profile-field-value">${busModel}</div>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Year of Manufacture</label>
              <div class="profile-field-value">${year}</div>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Bus Class</label>
              <div class="profile-field-value">
                <span class="bus-class-indicator class-${busClassText.toLowerCase().replace(' ', '-')}">${busClassText}</span>
              </div>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Seating Capacity</label>
              <div class="profile-field-value">${capacityValue}</div>
            </div>
          </div>
        </div>

        <div class="profile-section">
          <h3 class="profile-section-title">Additional Details</h3>
          <div class="profile-grid">
            <div class="profile-field">
              <label class="profile-field-label">Chassis Number</label>
              <div class="profile-field-value">${chassis}</div>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Current Route</label>
              <div class="profile-field-value">${route}</div>
            </div>
          </div>
        </div>
      </div>
    `;

    btnProfileEdit.setAttribute('data-reg', reg);
    btnProfileDelete.setAttribute('data-reg', reg);
    openModal(modalBusProfile);
  }

  // ============= EDIT BUS =============
  const btnProfileEdit = $('#btnProfileEdit');
  if (btnProfileEdit) {
    btnProfileEdit.addEventListener('click', () => {
      const reg = btnProfileEdit.getAttribute('data-reg');
      const cardEl = $(`[data-reg="${reg}"]`);
      
      if (cardEl) {
        populateBusFormFromCard(cardEl);
        $('#btnSaveBusForm').setAttribute('data-mode', 'edit');
        $('#btnSaveBusForm').textContent = 'Update Bus';
        $('#modalBusFormTitle').textContent = 'Edit Bus Details';
        
        closeModal(modalBusProfile);
        openModal(modalBusForm);
      }
    });
  }

  function populateBusFormFromCard(cardEl) {
    const reg = cardEl.getAttribute('data-reg');

    // First, try to extract data from the card DOM (limited data available)
    const status = cardEl.querySelector('.status-badge')?.textContent?.trim() || 'Active';
    const route = cardEl.querySelector('.card-route-info .card-value')?.textContent || '';
    const model = cardEl.querySelector('.card-row:first-child .card-info:first-child .card-value')?.textContent || '';
    const year = cardEl.querySelector('.card-row:first-child .card-info:last-child .card-value')?.textContent || '';
    const capacity = cardEl.querySelector('.card-row:last-child .card-info:first-child .card-value')?.textContent?.replace(' seats', '') || '';
    const busClass = cardEl.querySelector('.card-row:last-child .card-info:last-child .card-value')?.textContent || 'Normal';

    // Set basic form fields from card data
    $('#form_reg_no').value = reg;
    $('#form_reg_no').readOnly = true;
    $('#form_capacity').value = capacity;
    $('#form_bus_model').value = model === '—' ? '' : model;
    $('#form_year_manufacture').value = year === '—' ? '' : year;

    // Set bus class radio buttons
    const classOptions = $$('input[name="bus_class"]');
    classOptions.forEach(option => {
      option.checked = option.value === busClass;
    });

    // Set status
    const statusSelect = $('#form_status');
    if (statusSelect) {
      statusSelect.value = status;
    }

    // Note: chassis_no is not available in card display
    // It would need to be fetched via API call for full editing capability
    $('#form_chassis_no').value = '';

    // Form is now populated with available data
  }

  // ============= DELETE BUS =============
  const modalConfirmDelete = $('#modalConfirmDelete');
  if (modalConfirmDelete) wireModalClose(modalConfirmDelete);

  const btnProfileDelete = $('#btnProfileDelete');
  if (btnProfileDelete) {
    btnProfileDelete.addEventListener('click', () => {
      const reg = btnProfileDelete.getAttribute('data-reg');
      $('#delBusReg').textContent = reg;
      $('#btnConfirmDelete').setAttribute('data-reg', reg);
      
      closeModal(modalBusProfile);
      openModal(modalConfirmDelete);
    });
  }

  const deleteCardBtns = $$('.js-delete-profile');
  deleteCardBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const reg = btn.getAttribute('data-reg');
      $('#delBusReg').textContent = reg;
      $('#btnConfirmDelete').setAttribute('data-reg', reg);
      openModal(modalConfirmDelete);
    });
  });

  // ============= CARD EDIT BUTTONS =============
  const editCardBtns = $$('.js-edit-profile');
  editCardBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const reg = btn.getAttribute('data-reg');
      const cardEl = $(`[data-reg="${reg}"]`);

      if (cardEl) {
        populateBusFormFromCard(cardEl);
        $('#btnSaveBusForm').setAttribute('data-mode', 'edit');
        $('#btnSaveBusForm').textContent = 'Update Bus';
        $('#modalBusFormTitle').textContent = 'Edit Bus Details';

        openModal(modalBusForm);
      }
    });
  });

  const btnConfirmDelete = $('#btnConfirmDelete');
  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener('click', async () => {
      const reg = btnConfirmDelete.getAttribute('data-reg');
      if (!reg) {
        toast('No bus selected', false);
        return;
      }

      btnConfirmDelete.disabled = true;
      btnConfirmDelete.textContent = 'Deleting...';

      const ok = await postAction('delete_bus', { reg_no: reg });

      btnConfirmDelete.disabled = false;
      btnConfirmDelete.textContent = 'Delete Bus';

      if (ok) {
        toast('✓ Bus deleted successfully');
        setTimeout(() => location.reload(), 1200);
      } else {
        toast('Failed to delete bus', false);
      }
    });
  }

  // ============= FILTERS =============
  const filterForm = $('#fleetFilterForm');
  const filterToggle = $('#fleetFilterToggle');
  const filtersPanel = $('#fleetFiltersPanel');
  const resetBtn = $('#fleetResetFilters');
  const applyBtn = $('#fleetApplyFilters');

  if (filterToggle && filtersPanel) {
    filterToggle.addEventListener('click', () => {
      const isHidden = filtersPanel.style.display === 'none';
      filtersPanel.style.display = isHidden ? 'block' : 'none';
      filterToggle.classList.toggle('active');
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (filterForm) {
        filterForm.reset();
        filterForm.submit();
      }
    });
  }

  function updateActiveFilterCount() {
    const form = filterForm;
    let count = 0;
    const inputs = form ? $$('select', form) : [];
    inputs.forEach(sel => {
      if (sel.value) count++;
    });
    
    const badge = $('#activeFilterCount');
    if (badge) {
      if (count > 0) {
        badge.textContent = `${count} active`;
        badge.style.display = 'inline-block';
      } else {
        badge.textContent = '';
        badge.style.display = 'none';
      }
    }
  }

  // Update count on load and on filter change
  updateActiveFilterCount();
  $$('.fleet-select').forEach(sel => {
    sel.addEventListener('change', updateActiveFilterCount);
  });

  // Open filter panel if any filters are active
  const anyFilter = $$('.fleet-select').some(sel => sel.value);
  if (anyFilter && filtersPanel) {
    filtersPanel.style.display = 'block';
    filterToggle?.classList.add('active');
  }

  // ============= LIVE LOCATION UPDATE =============
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

    (async () => {
      for (const node of locationNodes) {
        const lat = node.dataset.lat;
        const lng = node.dataset.lng;
        if (!lat || !lng) continue;
        const name = await reverseGeocode(lat, lng);
        node.textContent = name;
      }
    })();
  }

  focusBusCardFromQuery();
})();
