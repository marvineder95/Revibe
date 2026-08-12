<?php
/**
 * Admin: Angebote verwalten
 */
require_once '../config/config.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// Aktionen verarbeiten
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$offers = getAllOffers();
$lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_offers_title'); ?> | <?php echo COMPANY_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body class="admin-body">
    <div class="admin-app">
        <?php require_once ROOT_PATH . 'partials/admin-sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-topbar">
                <button type="button" class="admin-mobile-toggle" onclick="openAdminSidebar()" aria-label="Menü">☰</button>
                <a href="/admin/dashboard.php" class="admin-sidebar-logo">
                    <?php if (file_exists(ROOT_PATH . 'assets/images/RevibeLogoPdf.png')): ?>
                    <img src="<?php echo ASSETS_URL; ?>images/RevibeLogoPdf.png" alt="<?php echo e(COMPANY_NAME); ?>" style="height: 28px;">
                    <?php else: ?>
                    <span><?php echo e(COMPANY_NAME); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin/logout.php" class="btn btn-dark btn-sm"><?php echo __('admin_logout'); ?></a>
            </header>

            <main class="admin-main">
            <?php if ($success): ?>
            <div style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #22c55e; margin-bottom: 0;"><?php echo e(__('admin_success_' . $success)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #ef4444; margin-bottom: 0;"><?php echo e(__('admin_error_' . $error)); ?></p>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_offers_title'); ?></h2>
                    <a href="/admin/dashboard.php" class="btn btn-dark btn-sm"><?php echo __('admin_back_to_dashboard'); ?></a>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($offers)): ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo __('admin_offer_number'); ?></th>
                                    <th><?php echo __('admin_customer'); ?></th>
                                    <th><?php echo __('admin_date'); ?></th>
                                    <th><?php echo __('admin_status'); ?></th>
                                    <th><?php echo __('admin_amount'); ?></th>
                                    <th><?php echo __('admin_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($offers as $offer): ?>
                                <tr>
                                    <td><strong><?php echo e($offer['offer_number']); ?></strong></td>
                                    <td>
                                        <?php echo e(trim(($offer['firstname'] ?? '') . ' ' . ($offer['lastname'] ?? ''))); ?><br>
                                        <span style="color: var(--color-gray-500); font-size: var(--text-sm);"><?php echo e($offer['email'] ?? ''); ?></span>
                                    </td>
                                    <td><?php echo e(date('d.m.Y', strtotime($offer['created_at']))); ?></td>
                                    <td>
                                        <?php if ($offer['status'] === 'pending'): ?>
                                            <span style="display: inline-block; padding: var(--space-1) var(--space-3); background: rgba(245, 158, 11, 0.2); color: #f59e0b; font-size: var(--text-xs); border-radius: var(--radius-full);"><?php echo __('admin_offer_status_pending'); ?></span>
                                        <?php elseif ($offer['status'] === 'accepted'): ?>
                                            <span style="display: inline-block; padding: var(--space-1) var(--space-3); background: rgba(34, 197, 94, 0.2); color: #22c55e; font-size: var(--text-xs); border-radius: var(--radius-full);"><?php echo __('admin_offer_status_accepted'); ?></span>
                                        <?php else: ?>
                                            <span style="display: inline-block; padding: var(--space-1) var(--space-3); background: rgba(239, 68, 68, 0.2); color: #ef4444; font-size: var(--text-xs); border-radius: var(--radius-full);"><?php echo __('admin_offer_status_declined'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $inquiry = getInquiryById($offer['inquiry_id']);
                                        $amount = $inquiry['pricing_json']['total_with_fee'] ?? $inquiry['pricing_json']['total_gross'] ?? 0;
                                        echo formatMoney($amount);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <?php if (!empty($offer['pdf_path']) && file_exists($offer['pdf_path'])): ?>
                                            <a href="<?php echo e(PDF_UPLOAD_URL . 'offers/' . basename($offer['pdf_path'])); ?>" target="_blank" class="admin-btn admin-btn-edit"><?php echo __('admin_view_pdf'); ?></a>
                                            <?php endif; ?>
                                            <?php if ($offer['status'] === 'pending'): ?>
                                            <a href="<?php echo e(rtrim(BASE_URL, '/') . '/offer.php?token=' . $offer['token']); ?>" target="_blank" class="admin-btn admin-btn-edit">Link</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: var(--space-12);">
                        <p style="color: var(--color-gray-500); margin-bottom: 0;"><?php echo __('admin_no_offers'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            </main>
        </div>
    </div>

    <script>
        function openAdminSidebar() {
            document.getElementById('adminSidebar').classList.add('active');
            document.getElementById('adminOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAdminSidebar() {
            document.getElementById('adminSidebar').classList.remove('active');
            document.getElementById('adminOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
