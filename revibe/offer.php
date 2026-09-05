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
            $signatureData = sanitizeInput($_POST['signature_data'] ?? '');
            if (acceptOffer($offer['id'], $signatureData)) {
                // Rechnung erstellen (Dummy-Nummer, wird gleich neu generiert)
                $invoicePdf = generateInvoicePdf($inquiry, 'R-' . date('Y') . '-00000', $offer['offer_number'], $signatureData);
                $dummyInvoicePdfPath = $invoicePdf['path'] ?? null;

                if ($invoicePdf) {
                    $invoice = createInvoice($offer['id'], $invoicePdf['path'], $inquiry['pricing_json']['total_with_fee'] ?? $inquiry['pricing_json']['total_gross'] ?? 0);

                    if ($invoice) {
                        // Rechnungsnummer korrigieren und PDF neu generieren
                        $invoicePdf = generateInvoicePdf($inquiry, $invoice['invoice_number'], $offer['offer_number'], $signatureData);
                        if ($invoicePdf) {
                            updateInvoicePdfPath($invoice['id'], $invoicePdf['path']);
                            $invoice['pdf_path'] = $invoicePdf['path'];
                        }

                        // Altes Dummy-PDF entfernen, falls vorhanden
                        if (!empty($dummyInvoicePdfPath) && file_exists($dummyInvoicePdfPath)) {
                            @unlink($dummyInvoicePdfPath);
                        }

                        // Admin-Benachrichtigung senden
                        $adminMailSent = sendAdminInvoiceNotification($offer, $invoice, $inquiry);
                        if (!$adminMailSent) {
                            error_log('Admin-Benachrichtigung nach Angebotsannahme konnte nicht gesendet werden.');
                        }

                        $success = __('offer_success_accepted');
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
                    <p style="color: #22c55e; margin-bottom: 0;"><?php echo e($success); ?></p>
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
                            <form id="offer-form-accept" method="POST" action="" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="signature_data" id="signature-data" value="">
                                <button type="button" class="btn btn-primary offer-action-btn" data-action="accept" data-title="<?php echo e(__('offer_accept')); ?>" data-message="<?php echo e(__('offer_confirm_accept')); ?>">
                                    <?php echo __('offer_accept'); ?>
                                </button>
                            </form>

                            <form id="offer-form-decline" method="POST" action="" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="button" class="btn btn-danger offer-action-btn" style="background: #ef4444; color: #fff;" data-action="decline" data-title="<?php echo e(__('offer_decline')); ?>" data-message="<?php echo e(__('offer_confirm_decline')); ?>">
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

<!-- Custom Offer Modal -->
<div id="offer-modal" class="offer-modal" role="dialog" aria-modal="true" aria-labelledby="offer-modal-title" aria-hidden="true">
    <div class="offer-modal-backdrop"></div>
    <div class="offer-modal-content">
        <button type="button" class="offer-modal-close" aria-label="<?php echo e(__('btn_close')); ?>">&times;</button>
        <h3 id="offer-modal-title" class="offer-modal-title"></h3>
        <p id="offer-modal-message" class="offer-modal-message"></p>

        <div id="signature-area" class="offer-signature-area" style="display: none;">
            <label class="offer-signature-label"><?php echo e(__('offer_signature_label')); ?></label>
            <canvas id="signature-pad" class="offer-signature-pad"></canvas>
            <p id="signature-error" class="offer-signature-error" style="display: none;"><?php echo e(__('offer_signature_required')); ?></p>
            <button type="button" id="signature-clear" class="btn btn-sm btn-dark offer-signature-clear"><?php echo e(__('offer_signature_clear')); ?></button>
        </div>

        <div class="offer-modal-actions">
            <button type="button" class="btn btn-dark offer-modal-cancel"><?php echo e(__('btn_cancel')); ?></button>
            <button type="button" class="btn offer-modal-confirm"></button>
        </div>
    </div>
</div>

<style>
.offer-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}
.offer-modal.is-open {
    display: flex;
}
.offer-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
}
.offer-modal-content {
    position: relative;
    background: var(--color-light, #fff);
    border-radius: var(--radius-xl, 16px);
    padding: var(--space-8, 32px);
    max-width: 420px;
    width: calc(100% - var(--space-8, 32px));
    box-shadow: var(--shadow-xl, 0 25px 50px -12px rgba(0,0,0,0.25));
    text-align: center;
}
.offer-modal-close {
    position: absolute;
    top: var(--space-3, 12px);
    right: var(--space-3, 12px);
    width: 32px;
    height: 32px;
    background: none;
    border: none;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: var(--color-gray-500);
    border-radius: var(--radius-full, 9999px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.offer-modal-close:hover {
    background: var(--color-cream, #FAF9F6);
}
.offer-modal-title {
    margin: 0 0 var(--space-3, 12px) 0;
    font-size: var(--text-xl, 1.25rem);
    font-weight: 700;
    color: var(--color-text, #1a1a1a);
}
.offer-modal-message {
    margin: 0 0 var(--space-6, 24px) 0;
    color: var(--color-text-muted, #5a5a5a);
    line-height: 1.5;
}
.offer-modal-actions {
    display: flex;
    gap: var(--space-3, 12px);
    justify-content: center;
}
@media (max-width: 480px) {
    .offer-modal-actions {
        flex-direction: column;
    }
    .offer-modal-actions .btn {
        width: 100%;
    }
}

.offer-signature-area {
    margin-bottom: var(--space-6, 24px);
    text-align: left;
}

.offer-signature-label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--space-2, 8px);
    color: var(--color-text, #1a1a1a);
}

.offer-signature-pad {
    width: 100%;
    height: 160px;
    border: 2px dashed var(--color-gray-300, #d1d5db);
    border-radius: var(--radius-md, 8px);
    background: var(--color-white, #fff);
    cursor: crosshair;
    touch-action: none;
}

.offer-signature-error {
    color: #ef4444;
    font-size: var(--text-sm, 0.875rem);
    margin-top: var(--space-2, 8px);
    margin-bottom: 0;
}

.offer-signature-clear {
    margin-top: var(--space-2, 8px);
}
</style>

<script>
(function() {
    const modal = document.getElementById('offer-modal');
    if (!modal) return;

    const titleEl = document.getElementById('offer-modal-title');
    const messageEl = document.getElementById('offer-modal-message');
    const confirmBtn = modal.querySelector('.offer-modal-confirm');
    const cancelBtn = modal.querySelector('.offer-modal-cancel');
    const closeBtn = modal.querySelector('.offer-modal-close');
    const backdrop = modal.querySelector('.offer-modal-backdrop');
    const signatureArea = document.getElementById('signature-area');
    const signaturePad = document.getElementById('signature-pad');
    const signatureClear = document.getElementById('signature-clear');
    const signatureError = document.getElementById('signature-error');
    const signatureDataInput = document.getElementById('signature-data');

    let activeForm = null;
    let activeAction = null;
    let isDrawing = false;
    let hasSignature = false;
    let ctx = null;

    function initSignaturePad() {
        if (!signaturePad) return;

        // Größe explizit zurücksetzen, damit CSS-Größe neu berechnet wird
        signaturePad.width = 0;
        signaturePad.height = 0;

        const dpr = window.devicePixelRatio || 1;
        const cssWidth = signaturePad.clientWidth || signaturePad.offsetWidth || 360;
        const cssHeight = signaturePad.clientHeight || signaturePad.offsetHeight || 160;

        signaturePad.width = cssWidth * dpr;
        signaturePad.height = cssHeight * dpr;

        ctx = signaturePad.getContext('2d');
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#1a1a1a';
        ctx.clearRect(0, 0, cssWidth, cssHeight);

        hasSignature = false;
        if (signatureDataInput) signatureDataInput.value = '';
        if (signatureError) signatureError.style.display = 'none';
    }

    function clearSignature() {
        if (!ctx || !signaturePad) return;
        const cssWidth = signaturePad.clientWidth || signaturePad.offsetWidth || signaturePad.width;
        const cssHeight = signaturePad.clientHeight || signaturePad.offsetHeight || signaturePad.height;
        ctx.clearRect(0, 0, cssWidth, cssHeight);
        hasSignature = false;
        if (signatureDataInput) signatureDataInput.value = '';
        if (signatureError) signatureError.style.display = 'none';
    }

    function getPoint(e) {
        const rect = signaturePad.getBoundingClientRect();
        const clientX = e.touches && e.touches.length ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches && e.touches.length ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDrawing(e) {
        if (!ctx) return;
        e.preventDefault();
        isDrawing = true;
        const p = getPoint(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function draw(e) {
        if (!isDrawing || !ctx) return;
        e.preventDefault();
        const p = getPoint(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        hasSignature = true;
        if (signatureError) signatureError.style.display = 'none';
    }

    function stopDrawing() {
        isDrawing = false;
        if (ctx) ctx.beginPath();
    }

    function bindSignatureEvents() {
        if (!signaturePad || signaturePad.dataset.bound === '1') return;
        signaturePad.dataset.bound = '1';

        signaturePad.addEventListener('mousedown', startDrawing);
        signaturePad.addEventListener('mousemove', draw);
        signaturePad.addEventListener('mouseup', stopDrawing);
        signaturePad.addEventListener('mouseleave', stopDrawing);
        signaturePad.addEventListener('touchstart', startDrawing, { passive: false });
        signaturePad.addEventListener('touchmove', draw, { passive: false });
        signaturePad.addEventListener('touchend', stopDrawing);
        signaturePad.addEventListener('touchcancel', stopDrawing);
    }

    function openModal(form, action, titleText, messageText) {
        activeForm = form;
        activeAction = action;
        titleEl.textContent = titleText;
        messageEl.textContent = messageText;

        confirmBtn.className = 'btn offer-modal-confirm';
        confirmBtn.style.cssText = '';
        if (action === 'accept') {
            confirmBtn.classList.add('btn-primary');
            confirmBtn.textContent = <?php echo json_encode(__('offer_accept')); ?>;
            if (signatureArea) signatureArea.style.display = 'block';
            // Init nach dem Reflow, damit das Canvas eine echte CSS-Größe hat
            requestAnimationFrame(function() {
                initSignaturePad();
                bindSignatureEvents();
            });
        } else {
            confirmBtn.classList.add('btn-danger');
            confirmBtn.style.background = '#ef4444';
            confirmBtn.style.color = '#fff';
            confirmBtn.textContent = <?php echo json_encode(__('offer_decline')); ?>;
            if (signatureArea) signatureArea.style.display = 'none';
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        activeForm = null;
        activeAction = null;
    }

    document.querySelectorAll('.offer-action-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const form = document.getElementById('offer-form-' + action);
            if (!form) return;
            openModal(form, action, this.dataset.title, this.dataset.message);
        });
    });

    confirmBtn.addEventListener('click', function() {
        if (!activeForm) return;

        if (activeAction === 'accept') {
            if (!hasSignature || !signaturePad) {
                if (signatureError) signatureError.style.display = 'block';
                return;
            }
            if (signatureDataInput) {
                signatureDataInput.value = signaturePad.toDataURL('image/png');
            }
        }

        activeForm.submit();
    });

    if (signatureClear) {
        signatureClear.addEventListener('click', function(e) {
            e.preventDefault();
            clearSignature();
        });
    }

    cancelBtn.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();
</script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
