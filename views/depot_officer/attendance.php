<?php /** @var array $staff,$records */ ?>
<style>
/* ─── Attendance Page Styles ─────────────────────────────────────────── */
.attendance-page {
    padding: 24px;
    background: #f9fafb;
    min-height: 100vh;
}

.attendance-header {
    margin-bottom: 24px;
}

.attendance-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1f2937;
    margin: 0 0 12px 0;
}

.notice {
    padding: 12px 16px;
    border-radius: 8px;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
    margin-bottom: 16px;
    font-weight: 600;
}

/* ─── Filter Bar (Single Line) ───────────────────────────────────────── */
.attendance-filters {
    display: flex;
    gap: 12px;
    align-items: center;
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.attendance-filters label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
}

.attendance-filters input[type="date"],
.attendance-filters input[type="text"],
.attendance-filters select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
}

.attendance-filters input[type="date"]:focus,
.attendance-filters input[type="text"]:focus,
.attendance-filters select:focus {
    outline: none;
    border-color: #7f1d1d;
    box-shadow: 0 0 0 3px rgba(127,29,29,.1);
}

.attendance-filters button {
    padding: 8px 16px;
    background: linear-gradient(135deg, #7f1d1d, #a01c2e);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: opacity .15s;
}

.attendance-filters button:hover {
    opacity: 0.9;
}

.attendance-filters-spacer {
    flex: 1;
    min-width: 150px;
}

/* ─── Table Styles ──────────────────────────────────────────────────── */
.attendance-table-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.attendance-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.attendance-table thead {
    background: linear-gradient(135deg, #7f1d1d 0%, #a01c2e 100%);
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
    background-color: #f9fafb;
}

.attendance-table td {
    padding: 14px 16px;
    vertical-align: middle;
}

.attendance-table td:nth-child(1) {
    font-weight: 700;
    color: #111827;
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
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
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
    border-color: #7f1d1d;
    box-shadow: 0 0 0 3px rgba(127,29,29,.1);
}

/* ─── Save Button ──────────────────────────────────────────────────── */
.attendance-save-btn {
    margin-top: 24px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    transition: opacity .15s;
    box-shadow: 0 4px 12px rgba(16,185,129,.3);
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

        <!-- Filter Bar (Single Line) -->
        <div class="attendance-filters">
            <label>Date:</label>
            <input type="date" id="attendanceDate" name="date" value="<?= htmlspecialchars($date) ?>">

            <button type="button" onclick="updateDate()">Go</button>

            <div class="attendance-filters-spacer"></div>

            <label>Filter by letter:</label>
            <select id="attendance-letter" class="alpha-select" aria-label="Filter by initial letter">
                <option value="all">All</option>
                <?php foreach(range('A','Z') as $letter): ?>
                    <option value="<?= $letter ?>"><?= $letter ?></option>
                <?php endforeach; ?>
            </select>

            <label style="flex: 1; min-width: 200px;">Search:</label>
            <input type="text" id="attendance-search" class="alpha-filter-search" placeholder="Name or role..." style="flex: 1; min-width: 150px;">
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
                            <strong><?= htmlspecialchars($s['full_name'] ?? ($s['first_name'] . ' ' . $s['last_name'])) ?></strong>
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
                            <strong style="font-size:14px;color:#7f1d1d;"><?= $shifts ?></strong>
                            <span style="font-size:12px;color:#9ca3af;"> shift<?= $shifts !== 1 ? 's' : '' ?></span>
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
// ─── Date Navigation ───────────────────────────────────────────────
function updateDate() {
    const date = document.getElementById('attendanceDate').value;
    window.location.href = '/O/attendance?date=' + encodeURIComponent(date);
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