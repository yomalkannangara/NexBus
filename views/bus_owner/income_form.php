<?php
$title = ($is_edit ? 'Edit' : 'Add') . ' Income Record - NTC Fleet System';
$active_page = 'earnings';
require_once APP_PATH . '/views/layouts/header.php';
require_once APP_PATH . '/views/layouts/sidebar.php';
?>

<main class="main-content">
    <?php require_once APP_PATH . '/views/layouts/flash.php'; ?>
    
    <header class="page-header">
        <div>
            <h2 class="page-title"><?php echo $is_edit ? 'Edit' : 'Add New'; ?> Income Record</h2>
            <p class="page-subtitle">Enter revenue details below</p>
        </div>
    </header>

    <div class="card">
        <form action="<?php echo BASE_URL; ?><?php echo $is_edit ? '/earnings/update/' . $earning['id'] : '/earnings/store'; ?>" method="post">
            
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="search-input" value="<?php echo $earning['date'] ?? date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Route Number *</label>
                    <input type="text" name="route_number" class="search-input" value="<?php echo $earning['route_number'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Route *</label>
                    <input type="text" name="route" class="search-input" value="<?php echo $earning['route'] ?? ''; ?>" required>
                </div>
            </div>

            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Bus ID *</label>
                    <input type="text" name="bus_id" class="search-input" value="<?php echo $earning['bus_id'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Revenue (LKR) *</label>
                    <input type="number" step="0.01" name="total_revenue" class="search-input" value="<?php echo $earning['total_revenue'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="search-input" value="<?php echo $earning['notes'] ?? ''; ?>">
                </div>
            </div>

            <div class="filter-actions" style="margin-top: 20px;">
                <a href="<?php echo BASE_URL; ?>/earnings" class="advanced-filter-btn">Cancel</a>
                <button type="submit" class="export-btn"><?php echo $is_edit ? 'Update' : 'Add'; ?> Record</button>
            </div>
        </form>
    </div>
</main>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>