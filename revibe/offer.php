<?php
/**
 * Öffentliche Angebotsseite
 * Kunde kann Angebot annehmen oder ablehnen.
 */
require_once 'config/config.php';
require_once INCLUDES_PATH . 'pdf.php';

setSecurityHeaders();

$page = 'offer';
$token = sanitizeInput($_GET['token'] ?? '');
$offer = null;
$inquiry = null;
$error = '';
$success = '';

if (empty($token)) {
    $error = __('offer_error_missing_token');
} else {
    $offer = getOfferByToken($token);
    if (!$offer) {
        $error = __('offer_error_not_found');
    } else {
        $inquiry = getInquiryById($offer['inquiry_id']);
        if (!$inquiry) {
            $error = __('offer_error_not_found');
        } elseif (!isOfferValid($offer)) {
            $error = __('offer_error_expired');
            // Abgelaufenes Angebot: Reservierungen freigeben
            if ($offer['status'] === 'pending') {
                cancelRentalsByInquiry($offer['inquiry_id']);
            }
        }
    }
}

// Aktionen verarbeiten
if ($offer && $inquiry && empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('offer_error_csrf');
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');

        if ($action === 'accept') {
            if (acceptOffer($offer['id'])) {
                // Rechnung erstellen (Dummy-Nummer, wird gleich neu generiert)
                $invoicePdf = generateInvoicePdf($inquiry, 'R-' . date('Y') . '-00000', $offer['offer_number']);
                $dummyInvoicePdfPath = $invoicePdf['path'] ?? null;

                if ($invoicePdf) {
                    $invoice = createInvoice($offer['id'], $invoicePdf['path'], $inquiry['pricing_json']['total_with_fee'] ?? $inquiry['pricing_json']['total_gross'] ?? 0);

                    if ($invoice) {
                        // Rechnungsnummer korrigieren und PDF neu generieren
                        $invoicePdf = generateInvoicePdf($inquiry, $invoice['invoice_number'], $offer['offer_number']);
                        if ($invoicePdf) {
                            updateInvoicePdfPath($invoice['id'], $invoicePdf['path']);
                            $invoice['pdf_path'] = $invoicePdf['path'];
                        }

                        // Altes Dummy-PDF entfernen, falls vorhanden
                        if (!empty($dummyInvoicePdfPath) && file_exists($dummyInvoicePdfPath)) {
                            @unlink($dummyInvoicePdfPath);
                        }

                        // Rechnung per E-Mail senden
                        $invoiceMailSent = sendInvoiceEmail($inquiry, $invoice, $offer);

                        $success = __('offer_success_accepted');
                        if (!$invoiceMailSent) {
                            error_log('Rechnungs-E-Mail konnte nicht an ' . ($inquiry['email'] ?? 'n/a') . ' gesendet werden.');
                        }
                    } else {
                        $error = __('offer_error_invoice_create');
                    }
                } else {
                    $error = __('offer_error_invoice_pdf');
                }
            } else {
                $error = __('offer_error_accept');
            }
        } elseif ($action === 'decline') {
            if (declineOffer($offer['id'])) {
                $success = __('offer_success_declined');
            } else {
                $error = __('offer_error_decline');
            }
        }

        // Aktualisierte Daten laden
        $offer = getOfferByToken($token);
    }
}

$page = 'offer';
$metaData = ['url' => BASE_URL . 'offer.php'];
include PARTIALS_PATH . 'header.php';
$lang = getCurrentLanguage();

/**
 * Rechnungs-E-Mail versenden
 *
 * @return bool true bei Erfolg, false bei Fehler
 */
function sendInvoiceEmail($inquiry, $invoice, $offer) {
    $name = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));
    $email = $inquiry['email'] ?? '';

    if (empty($email) || empty($invoice['pdf_path'])) {
        return false;
    }

    // Pfad validieren: muss innerhalb von PDF_UPLOAD_PATH liegen
    $realPdfPath = realpath($invoice['pdf_path']);
    $realBasePath = realpath(PDF_UPLOAD_PATH);
    if ($realPdfPath === false || $realBasePath === false || strpos($realPdfPath, $realBasePath) !== 0 || !file_exists($realPdfPath)) {
        error_log('Rechnungs-PDF nicht lesbar oder Pfad ungültig: ' . ($invoice['pdf_path'] ?? 'n/a'));
        return false;
    }

    $pdfContent = file_get_contents($realPdfPath);
    if ($pdfContent === false) {
        error_log('Rechnungs-PDF konnte nicht gelesen werden: ' . $realPdfPath);
        return false;
    }

    $subject = __('admin_invoice_email_subject', ['company' => COMPANY_NAME]);
    $body = __('admin_invoice_email_body', [
        'name' => $name,
        'company' => COMPANY_NAME
    ]) . "\n";

    $headers = "From: " . MAIL_SENDER . "\r\n";
    $headers .= "Reply-To: " . MAIL_SENDER . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $pdfEncoded = chunk_split(base64_encode($pdfContent));
    $pdfFilename = basename($realPdfPath);

    $boundary = bin2hex(random_bytes(16));
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $bodyMime = "--$boundary\r\n";
    $bodyMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $bodyMime .= $body . "\r\n";
    $bodyMime .= "--$boundary\r\n";
    $bodyMime .= "Content-Type: application/pdf; name=\"$pdfFilename\"\r\n";
    $bodyMime .= "Content-Transfer-Encoding: base64\r\n";
    $bodyMime .= "Content-Disposition: attachment; filename=\"$pdfFilename\"\r\n\r\n";
    $bodyMime .= $pdfEncoded . "\r\n";
    $bodyMime .= "--$boundary--";

    return mail($email, $subject, $bodyMime, $headers);
}
?>

<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1 class="reveal" style="text-align: center; margin-bottom: var(--space-6);">
                <?php echo __('offer_title'); ?>
            </h1>

            <?php if ($error): ?>
                <div class="reveal" style="padding: var(--space-6); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); text-align: center;">
                    <p style="color: #ef4444; margin-bottom: 0;"><?php echo e($error); ?></p>
                </div>
            <?php elseif ($success): ?>
                <div class="reveal" style="padding: var(--space-6); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); text-align: center;">
                    <p style="color: #22c55e; margin-bottom: var(--space-4);"><?php echo e($success); ?></p>
                    <?php if (!empty($invoice) && !empty($invoice['pdf_path']) && file_exists($invoice['pdf_path'])): ?>
                        <a href="<?php echo e(PDF_UPLOAD_URL . 'invoices/' . basename($invoice['pdf_path'])); ?>" target="_blank" class="btn btn-primary">
                            <?php echo __('offer_download_invoice'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif ($offer && $inquiry): ?>
                <div class="reveal contact-form" style="margin-bottom: var(--space-6);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4); margin-bottom: var(--space-6);">
                        <div>
                            <p style="color: var(--color-gray-500); margin-bottom: var(--space-1);"><?php echo __('offer_number'); ?></p>
                            <p style="font-size: var(--text-lg); font-weight: 600;"><?php echo e($offer['offer_number']); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <p style="color: var(--color-gray-500); margin-bottom: var(--space-1);"><?php echo __('offer_valid_until'); ?></p>
                            <p style="font-size: var(--text-lg); font-weight: 600;"><?php echo e(date('d.m.Y', strtotime($offer['valid_until']))); ?></p>
                        </div>
                    </div>

                    <p style="margin-bottom: var(--space-4);">
                        <?php echo __('offer_intro'); ?>
                    </p>

                    <div style="background: var(--color-cream); padding: var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                        <p style="margin-bottom: var(--space-2);"><strong><?php echo __('offer_customer'); ?></strong><br>
                            <?php echo e(trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''))); ?><br>
                            <?php echo e($inquiry['email'] ?? ''); ?></p>

                        <?php if (!empty($inquiry['pricing_json']['total_with_fee']) && $inquiry['pricing_json']['total_with_fee'] > 0): ?>
                            <p style="font-size: var(--text-xl); font-weight: 700; color: var(--color-primary); margin-top: var(--space-4);">
                                <?php echo formatMoney($inquiry['pricing_json']['total_with_fee']); ?>
                            </p>
                        <?php else: ?>
                            <p style="font-size: var(--text-xl); font-weight: 700; color: var(--color-primary); margin-top: var(--space-4);">
                                <?php echo formatMoney($inquiry['pricing_json']['total_gross'] ?? 0); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                        <?php if (!empty($offer['pdf_path']) && file_exists($offer['pdf_path'])): ?>
                            <a href="<?php echo e(PDF_UPLOAD_URL . 'offers/' . basename($offer['pdf_path'])); ?>" target="_blank" class="btn btn-dark">
                                <?php echo __('offer_view_pdf'); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($offer['status'] === 'pending'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('<?php echo e(__('offer_confirm_accept')); ?>')">
                                    <?php echo __('offer_accept'); ?>
                                </button>
                            </form>

                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="btn btn-danger" style="background: #ef4444; color: #fff;" onclick="return confirm('<?php echo e(__('offer_confirm_decline')); ?>')">
                                    <?php echo __('offer_decline'); ?>
                                </button>
                            </form>
                        <?php elseif ($offer['status'] === 'accepted'): ?>
                            <p style="color: #22c55e; font-weight: 600;"><?php echo __('offer_status_accepted'); ?></p>
                        <?php elseif ($offer['status'] === 'declined'): ?>
                            <p style="color: #ef4444; font-weight: 600;"><?php echo __('offer_status_declined'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
