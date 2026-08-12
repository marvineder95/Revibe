<?php
/**
 * Admin-Header-Partial
 * Wird von allen Admin-Seiten außer login.php eingebunden.
 * Erwartet, dass die einbindende Seite zuvor $pageTitle setzt.
 */
if (!isset($lang)) {
    $lang = getCurrentLanguage();
}
if (!isset($pageTitle)) {
    $pageTitle = 'Admin';
}
?>
<!DOCTYPE html>
<html lang="<?php echo e($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo e(COMPANY_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo filemtime(ROOT_PATH . 'assets/css/style.css'); ?>">
</head>
<body class="admin-body">
    <div class="admin-app">
        <!-- Mobile Overlay -->
        <div class="admin-overlay" id="adminOverlay" onclick="closeAdminSidebar()"></div>

        <?php require_once ROOT_PATH . 'partials/admin-sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-topbar">
                <button type="button" class="admin-mobile-toggle" onclick="openAdminSidebar()" aria-label="Menü">
                    ☰
                </button>
                <a href="/admin/dashboard.php" class="admin-sidebar-logo">
                    <?php if (file_exists(ROOT_PATH . 'assets/images/RevibeLogoPdf.png')): ?>
                    <img src="<?php echo ASSETS_URL; ?>images/RevibeLogoPdf.png" alt="<?php echo e(COMPANY_NAME); ?>">
                    <?php else: ?>
                    <span><?php echo e(COMPANY_NAME); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin/logout.php" class="btn btn-dark btn-sm"><?php echo __('admin_logout'); ?></a>
            </header>

            <main class="admin-main">
