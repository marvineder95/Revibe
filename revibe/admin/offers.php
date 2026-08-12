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
$pageTitle = __('admin_offers_title');

include PARTIALS_PATH . 'admin-header.php';
?>

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

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
