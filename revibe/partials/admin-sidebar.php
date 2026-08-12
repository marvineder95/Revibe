<?php
/**
 * Admin-Sidebar-Navigation
 * Wird von allen Admin-Seiten eingebunden.
 */
if (!isset($lang)) {
    $lang = getCurrentLanguage();
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
function isAdminPageActive($path) {
    $current = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($current, '/admin/' . $path) !== false;
}
?>
<!-- Mobile Overlay -->
<div class="admin-overlay" id="adminOverlay" onclick="closeAdminSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <a href="/admin/dashboard.php" class="admin-sidebar-logo">
            <?php if (file_exists(ROOT_PATH . 'assets/images/RevibeLogoPdf.png')): ?>
            <img src="<?php echo ASSETS_URL; ?>images/RevibeLogoPdf.png" alt="<?php echo e(COMPANY_NAME); ?>">
            <?php else: ?>
            <span class="admin-sidebar-logo-icon">🎵</span>
            <?php endif; ?>
        </a>
    </div>

    <nav class="admin-sidebar-nav">
        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Hauptmenü' : 'Main Menu'; ?></p>
            <a href="/admin/dashboard.php" class="admin-sidebar-link <?php echo isAdminPageActive('dashboard.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">📊</span>
                <?php echo __('admin_dashboard_title'); ?>
            </a>
            <a href="/admin/calendar.php" class="admin-sidebar-link <?php echo isAdminPageActive('calendar.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">📅</span>
                <?php echo __('admin_calendar'); ?>
            </a>
            <a href="/admin/create.php" class="admin-sidebar-link <?php echo isAdminPageActive('create.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">➕</span>
                <?php echo __('admin_create_jukebox'); ?>
            </a>
        </div>

        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Katalog' : 'Catalog'; ?></p>
            <a href="/admin/categories.php" class="admin-sidebar-link <?php echo isAdminPageActive('categories.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">🏷️</span>
                <?php echo $lang === 'de' ? 'Kategorien' : 'Categories'; ?>
            </a>
            <a href="/admin/discounts.php" class="admin-sidebar-link <?php echo isAdminPageActive('discounts.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">📉</span>
                <?php echo $lang === 'de' ? 'Rabatte' : 'Discounts'; ?>
            </a>
            <a href="/admin/coupons.php" class="admin-sidebar-link <?php echo isAdminPageActive('coupons.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">🎟️</span>
                <?php echo $lang === 'de' ? 'Coupons' : 'Coupons'; ?>
            </a>
        </div>

        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Geschäft' : 'Business'; ?></p>
            <a href="/admin/offers.php" class="admin-sidebar-link <?php echo isAdminPageActive('offers.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">📄</span>
                <?php echo __('admin_offers_title'); ?>
            </a>
            <a href="/admin/invoices.php" class="admin-sidebar-link <?php echo isAdminPageActive('invoices.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">💶</span>
                <?php echo __('admin_invoices_title'); ?>
            </a>
            <a href="/admin/settings.php" class="admin-sidebar-link <?php echo isAdminPageActive('settings.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">⚙️</span>
                <?php echo $lang === 'de' ? 'Einstellungen' : 'Settings'; ?>
            </a>
        </div>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="/admin/logout.php" class="admin-sidebar-logout">
            <span>🚪</span>
            <?php echo __('admin_logout'); ?>
        </a>
    </div>
</aside>
