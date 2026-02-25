<?php /** @var array $staff,$records */ ?>
<style>
/* ─── Font & Base Styles (Matching Admin) ────────────────────────── */
body {
    font-family: ui-sans-serif, system-ui, Segoe UI, Roboto, Arial;
    color: #2b2b2b;
}

/* ─── Attendance Page Styles ─────────────────────────────────────────── */
.attendance-page {
    padding: 24px;
    background: #f6f7f9;
    min-height: 100vh;
}

.attendance-header {
    margin-bottom: 24px;
}

.attendance-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #2b2b2b;
    margin: 0 0 12px 0;
    font-family: ui-sans-serif, system-ui, Segoe UI, Roboto, Arial;
}

.notice {
    padding: 12px 16px;
    border-radius: 8px;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
    margin-bottom: 16px;
    font-weight: 600;
    font-size: 13px;
}

/* ─── Filter Bar (Compact with Collapsible Panel) ───────────────────── */
.attendance-filters {
    display: flex;
    gap: 12px;
    align-items: center;
    background: #fff;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .08);
    margin-bottom: 24px;
}

.attendance-filter-toggle {
    padding: 8px 12px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    font-family: inherit;
}

.attendance-filter-toggle:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.attendance-filter-toggle.active {
    background: linear-gradient(135deg, #80143c, #80143c);
    color: white;
    border-color: #80143c;
}

.attendance-filters input[type="text"] {
    flex: 1;
    min-width: 180px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
}

.attendance-filters input[type="text"]:focus {
    outline: none;
    border-color: #80143c;
    box-shadow: 0 0 0 3px rgba(128, 20, 60, .1);
}

/* ─── Collapsible Filter Panel ───────────────────────────────────────── */
.attendance-filter-panel {
    display: none;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .08);
}

.attendance-filter-panel.active {
    display: block;
    animation: slideDown .2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.attendance-filter-panel-row {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.attendance-filter-panel label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    font-family: inherit;
}

.attendance-filter-panel input[type="date"],
.attendance-filter-panel select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
}

.attendance-filter-panel input[type="date"]:focus,
.attendance-filter-panel select:focus {
    outline: none;
    border-color: #80143c;
    box-shadow: 0 0 0 3px rgba(128, 20, 60, .1);
}

.attendance-filter-panel-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.attendance-filter-panel-actions button {
    padding: 8px 16px;
    font-weight: 600;
    font-size: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
}

.attendance-filter-panel-actions .btn-apply {
    background: #80143c;
    color: white;
}

.attendance-filter-panel-actions .btn-apply:hover {
    opacity: 0.9;
}

.attendance-filter-panel-actions .btn-clear {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.attendance-filter-panel-actions .btn-clear:hover {
    background: #e5e7eb;
}

/* ─── Table Styles ──────────────────────────────────────────────────── */
.attendance-table-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .08);
    overflow: hidden;
}

.attendance-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    font-family: inherit;
}

.attendance-table thead {
    background: #80143c;
    color: white;
}

.attendance-table th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

.attendance-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color .15s;
}

.attendance-table tbody tr:hover {
    background-color: #f6f7f9;
}

.attendance-table td {
    padding: 14px 16px;
    vertical-align: middle;
}

.attendance-table td:nth-child(1) {
    font-weight: 600;
    color: #2b2b2b;
}

.attendance-table td:nth-child(2) {
    font-size: 12px;
    color: #6b7280;
}

/* ─── Status Badge ─────────────────────────────────────────────────── */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 12px;
}

.status-present {
    background: #d1fae5;
    color: #065f46;
}

.status-absent {
    background: #fee2e2;
    color: #991b1b;
}

/* ─── Action Button ────────────────────────────────────────────────── */
.attendance-btn {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #374151;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    font-family: inherit;
}

.attendance-btn:hover {
    border-color: #9ca3af;
    background: #f3f4f6;
}

.attendance-btn.marked-absent {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.attendance-btn.marked-absent:hover {
    background: #fecaca;
}

/* ─── Shifts Input ──────────────────────────────────────────────────── */
.attendance-shifts-input {
    width: 60px;
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-family: inherit;
    text-align: center;
}

.attendance-shifts-input:focus {
    outline: none;
    border-color: #80143c;
    box-shadow: 0 0 0 3px rgba(128, 20, 60, .1);
}

/* ─── Notes Input ──────────────────────────────────────────────────── */
.attendance-notes {
    width: 100%;
    max-width: 300px;
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-family: inherit;
}

.attendance-notes:focus {
    outline: none;
    border-color: #80143c;
    box-shadow: 0 0 0 3px rgba(128, 20, 60, .1);
}

/* ─── Save Button ──────────────────────────────────────────────────── */
.attendance-save-btn {
    margin-top: 24px;
    padding: 12px 24px;
    background: #80143c;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: opacity .15s;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .08);
    font-family: inherit;
}

.attendance-save-btn:hover {
    opacity: 0.9;
}

/* ─── Responsive ───────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .attendance-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .attendance-filter-toggle {
        width: 100%;
    }

    .attendance-filters input[type="text"] {
        width: 100%;
    }

    .attendance-filter-panel-row {
        flex-direction: column;
    }

    .attendance-filter-panel label {
        width: 100%;
    }

    .attendance-filter-panel input[type="date"],
    .attendance-filter-panel select {
        width: 100%;
    }

    .attendance-table {
        font-size: 12px;
    }

    .attendance-table th,
    .attendance-table td {
        padding: 10px 8px;
    }
}
</style>

<div class="attendance-page">
    <!-- Header -->
    <div class="attendance-header">
        <h1>📋 Staff Attendance</h1>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="notice">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post" id="attendanceForm">
        <input type="hidden" name="action" value="mark">

        <!-- Compact Filter Bar with Toggle -->
        <div class="attendance-filters">
            <button type="button" class="attendance-filter-toggle" id="filterToggle" onclick="toggleFilterPanel()">
                ⚙️ Filters
            </button>
            <input type="text" id="attendance-search" class="alpha-filter-search" placeholder="🔍 Search staff...">
        </div>

        <!-- Collapsible Filter Panel -->
        <div class="attendance-filter-panel" id="filterPanel">
            <div class="attendance-filter-panel-row">
                <label>Date:</label>
                <input type="date" id="attendanceDate" name="date" value="<?= htmlspecialchars($date) ?>">

                <label>Filter by letter:</label>
                <select id="attendance-letter" class="alpha-select" aria-label="Filter by initial letter">
                    <option value="all">All</option>
                    <?php foreach(range('A','Z') as $letter): ?>
                        <option value="<?= $letter ?>"><?= $letter ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="attendance-filter-panel-actions">
                <button type="button" class="btn-apply" onclick="applyFilters()">Apply Filters</button>
                <button type="button" class="btn-clear" onclick="clearFilters()">Reset</button>
            </div>
        </div>

        <!-- Staff Attendance Table -->
        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 140px;">Mark Absent</th>
                        <th>Shifts Assigned</th>
                        <th style="width: 200px;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($staff as $s): 
                        $akey = $s['attendance_key'] ?? null;
                        $rec = $records[$akey] ?? null;
                        $isAbsent = !empty($rec['mark_absent']);
                        $shifts = (int)($s['shifts_count'] ?? 0);
                    ?>
                    <tr data-name="<?= htmlspecialchars(strtolower($s['full_name'] ?? ($s['first_name'] ?? ''))) ?>" 
                        data-role="<?= htmlspecialchars(strtolower($s['type'] ?? $s['role'] ?? '')) ?>"
                        data-akey="<?= htmlspecialchars($akey) ?>">
                        <td>
                            <?= htmlspecialchars($s['full_name'] ?? ($s['first_name'] . ' ' . $s['last_name'])) ?>
                        </td>
                        <td>
                            <span style="font-size:11px;text-transform:capitalize;"><?= htmlspecialchars($s['type'] ?? $s['role'] ?? '') ?></span>
                        </td>
                        <td>
                            <span class="status-badge <?= $isAbsent ? 'status-absent' : 'status-present' ?>" id="status-<?= htmlspecialchars($akey) ?>">
                                <?= $isAbsent ? '❌ Absent' : '✓ Present' ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="attendance-btn <?= $isAbsent ? 'marked-absent' : '' ?>" 
                                    id="btn-<?= htmlspecialchars($akey) ?>"
                                    data-akey="<?= htmlspecialchars($akey) ?>"
                                    onclick="toggleAbsent(this, event)">
                                <?= $isAbsent ? 'Marked Absent' : 'Mark Absent' ?>
                            </button>
                            <input type="hidden" name="mark[<?= htmlspecialchars($akey) ?>][absent]" 
                                   id="hidden-absent-<?= htmlspecialchars($akey) ?>"
                                   value="<?= $isAbsent ? '1' : '0' ?>">
                        </td>
                        <td>
                            <input type="number" name="mark[<?= htmlspecialchars($akey) ?>][shifts]" 
                                   class="attendance-shifts-input"
                                   min="0" max="3"
                                   placeholder="0"
                                   value="<?= $shifts ?>">
                        </td>
                        <td>
                            <input type="text" name="mark[<?= htmlspecialchars($akey) ?>][notes]" 
                                   class="attendance-notes"
                                   placeholder="Add notes..."
                                   value="<?= htmlspecialchars($rec['notes'] ?? '') ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Save Button -->
        <button type="submit" class="attendance-save-btn">💾 Save Attendance</button>
    </form>
</div>

<script>
// ─── Filter Panel Toggle ───────────────────────────────────────────
function toggleFilterPanel() {
    const panel = document.getElementById('filterPanel');
    const toggle = document.getElementById('filterToggle');
    panel.classList.toggle('active');
    toggle.classList.toggle('active');
}

// ─── Apply Filters (change date) ────────────────────────────────────
function applyFilters() {
    const date = document.getElementById('attendanceDate').value;
    window.location.href = '/O/attendance?date=' + encodeURIComponent(date);
}

// ─── Clear Filters ─────────────────────────────────────────────────
function clearFilters() {
    document.getElementById('attendance-letter').value = 'all';
    document.getElementById('attendance-search').value = '';
    applyFilterRows();
}

// ─── Toggle Absent Button ──────────────────────────────────────────
window.toggleAbsent = function(btn, event) {
    event.preventDefault();
    const akey = btn.dataset.akey;
    const hiddenInput = document.getElementById('hidden-absent-' + akey);
    const statusBadge = document.getElementById('status-' + akey);
    const isCurrentlyAbsent = hiddenInput.value === '1';

    if (isCurrentlyAbsent) {
        // Mark as Present
        hiddenInput.value = '0';
        btn.classList.remove('marked-absent');
        btn.textContent = 'Mark Absent';
        statusBadge.className = 'status-badge status-present';
        statusBadge.innerHTML = '✓ Present';
    } else {
        // Mark as Absent
        hiddenInput.value = '1';
        btn.classList.add('marked-absent');
        btn.textContent = 'Marked Absent';
        statusBadge.className = 'status-badge status-absent';
        statusBadge.innerHTML = '❌ Absent';
    }
};

// ─── Alphabet + Search Filter ──────────────────────────────────────
;(function(){
    const alphaSelect = document.getElementById('attendance-letter');
    const search = document.getElementById('attendance-search');
    const rows = Array.from(document.querySelectorAll('.attendance-table tbody tr'));

    function applyFilter(letter) {
        const q = (search.value || '').toLowerCase();
        rows.forEach(r => {
            const name = r.dataset.name || '';
            const role = r.dataset.role || '';
            let ok = true;
            if (letter && letter !== 'all') ok = name.charAt(0) === letter.toLowerCase();
            if (q) ok = ok && (name.includes(q) || role.includes(q));
            r.style.display = ok ? '' : 'none';
        });
    }

    window.applyFilterRows = applyFilter;

    alphaSelect.addEventListener('change', () => {
        const letter = alphaSelect.value || 'all';
        applyFilter(letter);
    });

    search.addEventListener('input', () => {
        const letter = alphaSelect.value || 'all';
        applyFilter(letter);
    });
})();
</script>