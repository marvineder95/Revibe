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

$adminUser = $_SESSION['admin_username'] ?? 'Admin';
?>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <a href="/admin/dashboard.php" class="admin-sidebar-logo">
            <img src="<?php echo ASSETS_URL; ?>images/LightLogo.png" alt="<?php echo e(COMPANY_NAME); ?>">
        </a>
    </div>

    <nav class="admin-sidebar-nav">
        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Hauptmenü' : 'Main Menu'; ?></p>
            <a href="/admin/dashboard.php" class="admin-sidebar-link <?php echo isAdminPageActive('dashboard.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <?php echo __('admin_dashboard_title'); ?>
            </a>
            <a href="/admin/calendar.php" class="admin-sidebar-link <?php echo isAdminPageActive('calendar.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </span>
                <?php echo __('admin_calendar'); ?>
            </a>
            <a href="/admin/create.php" class="admin-sidebar-link <?php echo isAdminPageActive('create.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                </span>
                <?php echo __('admin_create_jukebox'); ?>
            </a>
        </div>

        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Katalog' : 'Catalog'; ?></p>
            <a href="/admin/categories.php" class="admin-sidebar-link <?php echo isAdminPageActive('categories.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                </span>
                <?php echo $lang === 'de' ? 'Kategorien' : 'Categories'; ?>
            </a>
            <a href="/admin/discounts.php" class="admin-sidebar-link <?php echo isAdminPageActive('discounts.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </span>
                <?php echo $lang === 'de' ? 'Rabatte' : 'Discounts'; ?>
            </a>
            <a href="/admin/coupons.php" class="admin-sidebar-link <?php echo isAdminPageActive('coupons.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.05 7.07l-2.12-2.12a2 2 0 0 0-2.83 0L2.95 18.09a2 2 0 0 0 0 2.83l2.12 2.12a2 2 0 0 0 2.83 0L21.05 9.9a2 2 0 0 0 0-2.83z"></path><line x1="14.5" y1="7.5" x2="17.5" y2="10.5"></line></svg>
                </span>
                <?php echo $lang === 'de' ? 'Coupons' : 'Coupons'; ?>
            </a>
        </div>

        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Geschäft' : 'Business'; ?></p>
            <a href="/admin/offers.php" class="admin-sidebar-link <?php echo isAdminPageActive('offers.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                </span>
                <?php echo __('admin_offers_title'); ?>
            </a>
            <a href="/admin/reviews.php" class="admin-sidebar-link <?php echo isAdminPageActive('reviews.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </span>
                <?php echo __('admin_reviews_title'); ?>
            </a>
            <a href="/admin/invoices.php" class="admin-sidebar-link <?php echo isAdminPageActive('invoices.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </span>
                <?php echo __('admin_invoices_title'); ?>
            </a>
        </div>

        <div class="admin-sidebar-section">
            <p class="admin-sidebar-section-title"><?php echo $lang === 'de' ? 'Einstellungen' : 'Settings'; ?></p>
            <a href="/admin/settings.php" class="admin-sidebar-link <?php echo isAdminPageActive('settings.php') ? 'active' : ''; ?>">
                <span class="admin-sidebar-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </span>
                <?php echo $lang === 'de' ? 'Einstellungen' : 'Settings'; ?>
            </a>
        </div>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="/admin/logout.php" class="admin-sidebar-logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <?php echo __('admin_logout'); ?>
        </a>
    </div>
</aside>
