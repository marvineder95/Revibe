<?php
/**
 * Admin: Anfragen und Angebote verwalten
 */
require_once '../config/config.php';
require_once INCLUDES_PATH . 'pdf.php';
require_once INCLUDES_PATH . 'invoices-model.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && (!empty($_POST['inquiry_id']) || !empty($_POST['offer_id']))) {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $inquiryId = sanitizeInput($_POST['inquiry_id']);
        $action = sanitizeInput($_POST['action']);

        if ($action === 'create_offer') {
            $offer = buildAndSendOffer($inquiryId, 3);
            if ($offer) {
                redirect('/admin/offers.php?success=offer_created');
            } else {
                redirect('/admin/offers.php?error=offer_create');
            }
        }

        if ($action === 'create_invoice') {
            $offerId = sanitizeInput($_POST['offer_id'] ?? '');
            $result = createInvoiceFromOffer($offerId);
            if ($result && empty($result['existing'])) {
                redirect('/admin/offers.php?success=invoice_created');
            } elseif ($result && !empty($result['existing'])) {
                redirect('/admin/offers.php?error=invoice_exists');
            } else {
                redirect('/admin/offers.php?error=invoice_create');
            }
        }
    } else {
        redirect('/admin/offers.php?error=csrf');
    }
}

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

            <!-- Angebotsübersicht -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_offers_title'); ?></h2>
                    <a href="/admin/create-offer.php" class="btn btn-primary"><?php echo __('admin_create_offer_manual_button'); ?></a>
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
                                <tr id="offer-<?php echo e($offer['id']); ?>" class="offer-row<?php echo (!empty($_GET['highlight_offer']) && $_GET['highlight_offer'] === $offer['id']) ? ' offer-row-highlight' : ''; ?>">
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
                                            <a href="<?php echo e(PDF_UPLOAD_URL . 'offers/' . basename($offer['pdf_path'])); ?>" download class="admin-btn admin-btn-edit"><?php echo __('admin_download_pdf'); ?></a>
                                            <?php endif; ?>
                                            <?php if ($offer['status'] === 'pending'): ?>
                                            <a href="<?php echo e(rtrim(BASE_URL, '/') . '/offer.php?token=' . $offer['token']); ?>" target="_blank" class="admin-btn admin-btn-edit">Link</a>
                                            <?php endif; ?>
                                            <?php
                                            $offerInvoice = ($offer['status'] === 'accepted') ? getInvoiceByOfferId($offer['id']) : null;
                                            ?>
                                            <?php if ($offer['status'] === 'accepted' && !$offerInvoice): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="offer_id" value="<?php echo e($offer['id']); ?>">
                                                <input type="hidden" name="action" value="create_invoice">
                                                <button type="submit" class="admin-btn admin-btn-invoice" style="border: none; cursor: pointer;" onclick="return confirm('<?php echo e(__('admin_create_invoice_confirm')); ?>')"><?php echo __('admin_create_invoice'); ?></button>
                                            </form>
                                            <?php elseif ($offerInvoice && !empty($offerInvoice['pdf_path']) && file_exists($offerInvoice['pdf_path'])): ?>
                                            <a href="<?php echo e(PDF_UPLOAD_URL . 'invoices/' . basename($offerInvoice['pdf_path'])); ?>" target="_blank" class="admin-btn admin-btn-edit"><?php echo __('admin_view_invoice_pdf'); ?></a>
                                            <?php endif; ?>
                                            <?php if ($offer['status'] === 'accepted' && !empty($offer['signature'])): ?>
                                            <button type="button" class="admin-btn admin-btn-edit" onclick="document.getElementById('signature-preview-<?php echo e($offer['id']); ?>').style.display = document.getElementById('signature-preview-<?php echo e($offer['id']); ?>').style.display === 'none' ? 'block' : 'none';"><?php echo e(__('admin_show_signature')); ?></button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($offer['status'] === 'accepted' && !empty($offer['signature'])): ?>
                                        <div id="signature-preview-<?php echo e($offer['id']); ?>" style="display: none; margin-top: var(--space-3);">
                                            <img src="<?php echo e($offer['signature']); ?>" alt="<?php echo e(__('admin_signature_alt')); ?>" style="max-width: 200px; max-height: 80px; border: 1px solid var(--color-gray-300); border-radius: var(--radius-sm); background: #fff;">
                                        </div>
                                        <?php endif; ?>
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

<script>
(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var highlightOfferId = urlParams.get('highlight_offer');
    if (highlightOfferId) {
        var row = document.getElementById('offer-' + highlightOfferId);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function() {
                row.classList.add('offer-row-highlight');
            }, 200);
        }
    }
})();
</script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
