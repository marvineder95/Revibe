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

        if ($action === 'send_invoice') {
            $invoice = getInvoiceById($invoiceId);
            if (!$invoice) {
                redirect('/admin/invoices.php?error=invoice_not_found');
            }

            $inquiry = getInquiryById($invoice['inquiry_id']);
            if (!$inquiry) {
                redirect('/admin/invoices.php?error=invoice_not_found');
            }

            if (sendInvoiceEmail($invoice, $inquiry)) {
                redirect('/admin/invoices.php?success=invoice_sent');
            } else {
                redirect('/admin/invoices.php?error=invoice_send');
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
$paidRevenue = $totalRevenue - $openRevenue;
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
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-5); margin-bottom: var(--space-8);">
                <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter active" data-filter="all">
                    <div class="admin-dashboard-stat-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="admin-dashboard-stat-content">
                        <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Gesamtumsatz' : 'Total Revenue'; ?></div>
                        <div class="admin-dashboard-stat-value"><?php echo formatMoney($totalRevenue); ?></div>
                        <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Alle Rechnungen' : 'All invoices'; ?></div>
                    </div>
                </button>
                <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="open">
                    <div class="admin-dashboard-stat-icon amber">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="admin-dashboard-stat-content">
                        <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Offener Umsatz' : 'Open Revenue'; ?></div>
                        <div class="admin-dashboard-stat-value"><?php echo formatMoney($openRevenue); ?></div>
                        <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Noch nicht bezahlt' : 'Not yet paid'; ?></div>
                    </div>
                </button>
                <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="paid">
                    <div class="admin-dashboard-stat-icon green">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="admin-dashboard-stat-content">
                        <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Bezahlte Rechnungen' : 'Paid Invoices'; ?></div>
                        <div class="admin-dashboard-stat-value"><?php echo formatMoney($paidRevenue); ?></div>
                        <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Bereits bezahlt' : 'Already paid'; ?></div>
                    </div>
                </button>
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
                                <tr data-status="<?php echo e($invoice['status']); ?>">
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
                                                <input type="hidden" name="action" value="send_invoice">
                                                <button type="submit" class="admin-btn admin-btn-send" style="border: none; cursor: pointer;" onclick="return confirm('<?php echo e(__('admin_send_invoice_confirm')); ?>')"><?php echo __('admin_send_invoice'); ?></button>
                                            </form>
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

<script>
(function() {
    var table = document.querySelector('.admin-table');
    var statFilters = document.querySelectorAll('.admin-dashboard-stat-filter');
    if (!table) return;

    var rows = table.querySelectorAll('tbody tr');
    var noResults = document.createElement('div');
    noResults.className = 'admin-empty-state';
    noResults.style.display = 'none';
    noResults.style.padding = 'var(--space-12) var(--space-6)';
    noResults.style.textAlign = 'center';
    noResults.innerHTML = '<p style="color: var(--color-gray-500); margin-bottom: 0;"><?php echo $lang === "de" ? "Keine Rechnungen für diesen Filter vorhanden." : "No invoices found for this filter."; ?></p>';
    table.parentNode.insertBefore(noResults, table.nextSibling);

    var activeFilter = 'all';

    function applyFilter() {
        var visibleCount = 0;
        rows.forEach(function(row) {
            var status = row.getAttribute('data-status');
            var show = activeFilter === 'all' ||
                (activeFilter === 'open' && status !== 'paid') ||
                (activeFilter === 'paid' && status === 'paid');
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    statFilters.forEach(function(button) {
        button.addEventListener('click', function() {
            activeFilter = this.getAttribute('data-filter');
            statFilters.forEach(function(btn) {
                btn.classList.toggle('active', btn === this);
            }, this);
            applyFilter();
        });
    });
})();
</script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
