<?php
/**
 * Admin: Rechnungen verwalten
 */
require_once '../config/config.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['invoice_id']) && !empty($_POST['action'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $invoiceId = sanitizeInput($_POST['invoice_id']);
        $action = sanitizeInput($_POST['action']);

        if ($action === 'mark_paid') {
            if (markInvoicePaid($invoiceId)) {
                redirect('/admin/invoices.php?success=invoice_paid');
            } else {
                redirect('/admin/invoices.php?error=invoice_paid');
            }
        }
    } else {
        redirect('/admin/invoices.php?error=csrf');
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$invoices = getAllInvoices();
$totalRevenue = getTotalRevenue();
$openRevenue = getOpenRevenue();
$lang = getCurrentLanguage();
$pageTitle = __('admin_invoices_title');

// Zusätzliche Erfolgs-/Fehlermeldungen
$langTranslations = getTranslations($lang);

include PARTIALS_PATH . 'admin-header.php';
?>

            <?php if ($success): ?>
            <div style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #22c55e; margin-bottom: 0;">
                    <?php
                    if ($success === 'invoice_paid') {
                        echo $langTranslations['admin_invoice_marked_paid'] ?? 'Rechnung als bezahlt markiert.';
                    } else {
                        echo e(__('admin_success_' . $success));
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #ef4444; margin-bottom: 0;">
                    <?php
                    if ($error === 'invoice_paid') {
                        echo $langTranslations['admin_invoice_mark_paid_error'] ?? 'Rechnung konnte nicht als bezahlt markiert werden.';
                    } elseif ($error === 'csrf') {
                        echo e(__('admin_error_csrf'));
                    } else {
                        echo e(__('admin_error_' . $error));
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Umsatz-Übersicht -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-8);">
                <div style="background: var(--color-cream); padding: var(--space-5); border-radius: var(--radius-lg); text-align: center;">
                    <p style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary); margin-bottom: var(--space-2);"><?php echo formatMoney($totalRevenue); ?></p>
                    <p style="color: var(--color-gray-500); margin-bottom: 0;"><?php echo __('admin_total_revenue'); ?></p>
                </div>
                <div style="background: var(--color-cream); padding: var(--space-5); border-radius: var(--radius-lg); text-align: center;">
                    <p style="font-size: var(--text-3xl); font-weight: 700; color: #f59e0b; margin-bottom: var(--space-2);"><?php echo formatMoney($openRevenue); ?></p>
                    <p style="color: var(--color-gray-500); margin-bottom: 0;"><?php echo __('admin_open_revenue'); ?></p>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_invoices_title'); ?></h2>
                    <a href="/admin/dashboard.php" class="btn btn-dark btn-sm"><?php echo __('admin_back_to_dashboard'); ?></a>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($invoices)): ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo __('admin_invoice_number'); ?></th>
                                    <th><?php echo __('admin_offer_number'); ?></th>
                                    <th><?php echo __('admin_customer'); ?></th>
                                    <th><?php echo __('admin_date'); ?></th>
                                    <th><?php echo __('admin_status'); ?></th>
                                    <th><?php echo __('admin_amount'); ?></th>
                                    <th><?php echo __('admin_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><strong><?php echo e($invoice['invoice_number']); ?></strong></td>
                                    <td><?php echo e($invoice['offer_number'] ?? '-'); ?></td>
                                    <td>
                                        <?php echo e(trim(($invoice['firstname'] ?? '') . ' ' . ($invoice['lastname'] ?? ''))); ?><br>
                                        <span style="color: var(--color-gray-500); font-size: var(--text-sm);"><?php echo e($invoice['email'] ?? ''); ?></span>
                                    </td>
                                    <td><?php echo e(date('d.m.Y', strtotime($invoice['created_at']))); ?></td>
                                    <td>
                                        <?php if ($invoice['status'] === 'paid'): ?>
                                            <span style="display: inline-block; padding: var(--space-1) var(--space-3); background: rgba(34, 197, 94, 0.2); color: #22c55e; font-size: var(--text-xs); border-radius: var(--radius-full);"><?php echo __('admin_invoice_status_paid'); ?></span>
                                        <?php else: ?>
                                            <span style="display: inline-block; padding: var(--space-1) var(--space-3); background: rgba(245, 158, 11, 0.2); color: #f59e0b; font-size: var(--text-xs); border-radius: var(--radius-full);"><?php echo __('admin_invoice_status_open'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatMoney($invoice['amount_gross']); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <?php if (!empty($invoice['pdf_path']) && file_exists($invoice['pdf_path'])): ?>
                                            <a href="<?php echo e(PDF_UPLOAD_URL . 'invoices/' . basename($invoice['pdf_path'])); ?>" target="_blank" class="admin-btn admin-btn-edit"><?php echo __('admin_view_pdf'); ?></a>
                                            <?php endif; ?>
                                            <?php if ($invoice['status'] !== 'paid'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="invoice_id" value="<?php echo e($invoice['id']); ?>">
                                                <input type="hidden" name="action" value="mark_paid">
                                                <button type="submit" class="admin-btn admin-btn-edit" style="background: #22c55e; color: #fff; border: none; cursor: pointer;" onclick="return confirm('<?php echo e(__('admin_mark_paid')); ?>?')"><?php echo __('admin_mark_paid'); ?></button>
                                            </form>
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
                        <p style="color: var(--color-gray-500); margin-bottom: 0;"><?php echo __('admin_no_invoices'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            </main>
        </div>
    </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
