<?php
/**
 * Kontaktseite mit Formular + kompakter Preiszusammenfassung
 * Alle Anfrage-Infos (Auswahl, Adresse, Zeitraum, Kontaktdaten) auf einer Seite.
 */
require_once 'config/config.php';
require_once INCLUDES_PATH . 'pdf.php';

setSecurityHeaders();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Jukebox über ?jukebox=ID direkt zur Anfrage hinzufügen
if (!empty($_GET['jukebox'])) {
    $addJukeboxId = sanitizeInput($_GET['jukebox']);
    if ($addJukeboxId && getJukeboxById($addJukeboxId)) {
        cartAddItem($addJukeboxId);
        addToInquiryList($addJukeboxId);
    }
    redirect(BASE_URL . 'contact.php');
    exit;
}

$page = 'contact';

// Formular-Verarbeitung
$formSuccess = false;
$formError = '';

// Aktualisiere Warenkorbdaten bei POST (auch wenn Validierung fehlschlägt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateStart    = sanitizeInput($_POST['date_start'] ?? '');
    $dateEnd      = sanitizeInput($_POST['date_end'] ?? '');
    $couponCode   = strtoupper(sanitizeInput($_POST['coupon_code'] ?? ''));

    $street       = sanitizeInput($_POST['event_street'] ?? '');
    $housenumber  = sanitizeInput($_POST['event_housenumber'] ?? '');
    $zip          = sanitizeInput($_POST['event_zip'] ?? '');
    $city         = sanitizeInput($_POST['event_city'] ?? '');
    $country      = sanitizeInput($_POST['event_country'] ?? '');

    $eventAddress = trim($street . ' ' . $housenumber);
    if ($zip || $city) {
        $eventAddress .= "\n" . trim($zip . ' ' . $city);
    }
    if ($country) {
        $eventAddress .= ($eventAddress ? ', ' : '') . $country;
    }

    cartUpdateRentalData($dateStart, $dateEnd, $eventAddress);
    cartSetCoupon($couponCode);

    if (!empty($eventAddress)) {
        $transport = calculateTransportCosts($eventAddress);
        if (empty($transport['error'])) {
            cartSetTransportData($transport['distance_km'], $transport['duration_min'], '');
        } else {
            cartSetTransportData(0, 0, $transport['error']);
        }
    }
}

$cartItems = getCartItems();
$cart      = getCart();

// Transport berechnen
$transport = ['costs' => 0, 'error' => $cart['transport_error'] ?? ''];
if (!empty($cart['event_address'])) {
    if ($cart['transport_calculated']) {
        $transport['costs'] = computeTransportPrice($cart['transport_distance_km'], $cart['transport_duration_min']);
    } else {
        $transport = calculateTransportCosts($cart['event_address']);
        if (empty($transport['error'])) {
            cartSetTransportData($transport['distance_km'], $transport['duration_min'], '');
        } else {
            cartSetTransportData(0, 0, $transport['error']);
        }
    }
}

$pricing = calculatePricing($cartItems, $cart['duration_days'], $cart['coupon_code'], $transport['costs']);
$cartAvailability = checkCartAvailability($cart);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isFormRateLimited('contact', 3, 300)) {
        $formError = 'form_error_send';
    } else {
        if (!empty($_POST['website'])) {
            $formError = 'Spam erkannt.';
        } else {
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $formError = 'Sicherheitsfehler. Bitte laden Sie die Seite neu.';
            } else {
                $firstname = sanitizeInput($_POST['firstname'] ?? '');
                $lastname  = sanitizeInput($_POST['lastname'] ?? '');
                $name      = trim($firstname . ' ' . $lastname);
                $company   = sanitizeInput($_POST['company'] ?? '');
                $email     = sanitizeInput($_POST['email'] ?? '');
                $phone     = sanitizePhone($_POST['phone'] ?? '');
                $message   = sanitizeInput($_POST['message'] ?? '');
                $privacy   = isset($_POST['privacy']) ? true : false;

                $errors = [];
                if (empty($firstname)) $errors[] = 'firstname';
                if (empty($lastname))  $errors[] = 'lastname';
                if (empty($email) || !isValidEmail($email) || preg_match('/[\r\n]/', $email)) $errors[] = 'email';
                if (empty($phone)) $errors[] = 'phone';
                if (!$privacy) $errors[] = 'privacy';

                if (empty($errors)) {
                    $lang = getCurrentLanguage();

                    // Finale Verfügbarkeitsprüfung vor Absendung
                    $availability = checkCartAvailability($cart);
                    if (!$availability['available']) {
                        $formError = 'cart_item_not_available';
                    } else {

                    $subject = MAIL_SUBJECT_PREFIX . 'Neue Anfrage von ' . $name;

                    $body = "Neue Jukebox-Anfrage\n";
                    $body .= str_repeat('=', 50) . "\n\n";
                    $body .= "Name: $name\n";
                    if ($company) $body .= "Firma: $company\n";
                    $body .= "E-Mail: $email\n";
                    $body .= "Telefon: $phone\n\n";

                    $body .= "EVENT-DATEN\n";
                    $body .= str_repeat('-', 30) . "\n";
                    $body .= "Adresse: " . $cart['event_address'] . "\n";
                    $body .= "Mietzeitraum: " . $cart['date_start'];
                    if ($cart['date_end'] && $cart['date_end'] !== $cart['date_start']) {
                        $body .= " bis " . $cart['date_end'];
                    }
                    $body .= "\n";
                    $body .= "Mietdauer: " . $pricing['days'] . " Tage\n\n";

                    $body .= "AUSGEWÄHLTE JUKEBOXEN\n";
                    $body .= str_repeat('-', 30) . "\n";
                    foreach ($pricing['items'] as $item) {
                        $body .= "- " . $item['name'];
                        if (!empty($item['category'])) {
                            $body .= " [" . getCategoryName($item['category']) . "]";
                        }
                        $body .= " | " . formatMoneyRaw($item['price_day']) . " €/Tag | " . formatMoney($item['total']) . "\n";
                    }

                    $body .= "\nPREISÜBERSICHT (unverbindlich)\n";
                    $body .= str_repeat('-', 30) . "\n";
                    $body .= "Zwischensumme Jukeboxen: " . formatMoney($pricing['rental_subtotal']) . "\n";
                    if ($pricing['duration_discount_amount'] > 0) {
                        $body .= "Mietdauer-Rabatt (" . formatMoneyRaw($pricing['duration_discount_percent']) . "%): -" . formatMoney($pricing['duration_discount_amount']) . "\n";
                    }
                    if ($pricing['quantity_discount_amount'] > 0) {
                        $body .= "Mengenrabatt (" . formatMoneyRaw($pricing['quantity_discount_percent']) . "%): -" . formatMoney($pricing['quantity_discount_amount']) . "\n";
                    }
                    if ($pricing['coupon_discount_amount'] > 0) {
                        $body .= "Coupon " . $pricing['coupon_code'] . " (" . formatMoneyRaw($pricing['coupon_discount_percent']) . "%): -" . formatMoney($pricing['coupon_discount_amount']) . "\n";
                    }
                    $body .= "Mietkosten netto: " . formatMoney($pricing['rental_net']) . "\n";

                    if (empty($transport['error'])) {
                        $body .= "Transport netto: " . formatMoney($pricing['transport_net']) . "\n";
                    } else {
                        $body .= "Transport: Individuell berechnet\n";
                    }

                    $body .= "Gesamt netto: " . formatMoney($pricing['total_net']) . "\n";
                    $body .= "USt. (" . formatMoneyRaw($pricing['tax_rate']) . "%): " . formatMoney($pricing['tax_amount']) . "\n";
                    $body .= "Gesamt brutto: " . formatMoney($pricing['total_gross']) . "\n";
                    if ($pricing['contract_fee_amount'] > 0) {
                        $body .= "Vertragsgebühr (" . formatMoneyRaw($pricing['contract_fee_percent']) . "%): " . formatMoney($pricing['contract_fee_amount']) . "\n";
                        $body .= "Gesamtbetrag inkl. Vertragsgebühr: " . formatMoney($pricing['total_with_fee']) . "\n";
                    }
                    $body .= "\nHINWEIS: Dies ist eine unverbindliche Preisschätzung.\n\n";

                    if ($message) {
                        $body .= "KUNDENNACHRICHT\n";
                        $body .= str_repeat('-', 30) . "\n$message\n\n";
                    }

                    $body .= "---\n";
                    $body .= "Gesendet am: " . date('d.m.Y H:i') . "\n";
                    $body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

                    $headers = "From: " . MAIL_SENDER . "\r\n";
                    $headers .= "Reply-To: " . $email . "\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    $mailSent = mail(MAIL_RECIPIENT, $subject, $body, $headers);

                    if ($mailSent) {
                        // 1. Inquiry in Datenbank speichern
                        $inquiryData = [
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'company' => $company,
                            'email' => $email,
                            'phone' => $phone,
                            'message' => $message,
                            'date_start' => $cart['date_start'],
                            'date_end' => $cart['date_end'],
                            'duration_days' => $pricing['days'],
                            'event_address' => $cart['event_address'],
                            'pricing_json' => $pricing,
                            'transport_distance_km' => $cart['transport_distance_km'] ?? 0,
                            'transport_duration_min' => $cart['transport_duration_min'] ?? 0,
                            'transport_costs' => $transport['costs'] ?? 0,
                            'transport_error' => $transport['error'] ?? ''
                        ];
                        $inquiry = createInquiry($inquiryData);

                        // Reservierungen für alle ausgewählten Jukeboxen anlegen
                        if ($inquiry) {
                            foreach ($cartItems as $jb) {
                                createRental([
                                    'jukebox_id' => $jb['id'],
                                    'inquiry_id' => $inquiry['id'],
                                    'date_start' => $inquiry['date_start'],
                                    'date_end' => $inquiry['date_end'],
                                    'status' => 'reserved'
                                ]);
                            }
                        }

                        $offerLink = '';
                        if ($inquiry) {
                            // 2. Angebots-PDF erstellen (Dummy-Nummer, wird gleich neu generiert)
                            $offerPdf = generateOfferPdf($inquiry, 'ANG-' . date('Y') . '-00000', '+3 days');
                            $dummyOfferPdfPath = $offerPdf['path'] ?? null;

                            if ($offerPdf) {
                                $offer = createOffer($inquiry['id'], $offerPdf['path'], 3);

                                if ($offer) {
                                    // Reservierungen mit Angebot verknüpfen
                                    linkRentalsToOffer($inquiry['id'], $offer['id']);

                                    // Angebotsnummer korrigieren und PDF neu generieren
                                    $offerPdf = generateOfferPdf($inquiry, $offer['offer_number'], $offer['valid_until']);
                                    if ($offerPdf) {
                                        // Angebot aktualisieren mit korrektem PDF
                                        updateOfferPdfPath($offer['id'], $offerPdf['path']);
                                        $offer['pdf_path'] = $offerPdf['path'];
                                    }

                                    $offerLink = rtrim(BASE_URL, '/') . '/offer.php?token=' . $offer['token'];

                                    // Altes Dummy-PDF entfernen, falls vorhanden
                                    if (!empty($dummyOfferPdfPath) && file_exists($dummyOfferPdfPath)) {
                                        @unlink($dummyOfferPdfPath);
                                    }

                                    // 3. Angebots-E-Mail an Kunden senden
                                    $realOfferPdfPath = !empty($offer['pdf_path']) ? realpath($offer['pdf_path']) : false;
                                    $realPdfBasePath = realpath(PDF_UPLOAD_PATH);

                                    if ($realOfferPdfPath !== false && $realPdfBasePath !== false && strpos($realOfferPdfPath, $realPdfBasePath) === 0 && file_exists($realOfferPdfPath)) {
                                        $custSubject = __('admin_offer_email_subject', ['company' => COMPANY_NAME]);
                                        $custBody = __('admin_offer_email_body', [
                                            'name' => $name,
                                            'link' => $offerLink,
                                            'valid_until' => date('d.m.Y', strtotime($offer['valid_until'])),
                                            'company' => COMPANY_NAME
                                        ]) . "\n";

                                        $custHeaders = "From: " . MAIL_SENDER . "\r\n";
                                        $custHeaders .= "Reply-To: " . MAIL_SENDER . "\r\n";
                                        $custHeaders .= "X-Mailer: PHP/" . phpversion();

                                        $pdfContent = file_get_contents($realOfferPdfPath);
                                        $pdfEncoded = chunk_split(base64_encode($pdfContent));
                                        $pdfFilename = basename($realOfferPdfPath);

                                        $boundary = bin2hex(random_bytes(16));
                                        $custHeaders .= "MIME-Version: 1.0\r\n";
                                        $custHeaders .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

                                        $custBodyMime = "--$boundary\r\n";
                                        $custBodyMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
                                        $custBodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                                        $custBodyMime .= $custBody . "\r\n";
                                        $custBodyMime .= "--$boundary\r\n";
                                        $custBodyMime .= "Content-Type: application/pdf; name=\"$pdfFilename\"\r\n";
                                        $custBodyMime .= "Content-Transfer-Encoding: base64\r\n";
                                        $custBodyMime .= "Content-Disposition: attachment; filename=\"$pdfFilename\"\r\n\r\n";
                                        $custBodyMime .= $pdfEncoded . "\r\n";
                                        $custBodyMime .= "--$boundary--";

                                        $offerMailSent = mail($email, $custSubject, $custBodyMime, $custHeaders);
                                        if (!$offerMailSent) {
                                            error_log('Angebots-E-Mail konnte nicht an ' . $email . ' gesendet werden.');
                                        }
                                    } else {
                                        error_log('Angebots-PDF-Pfad ungültig oder nicht lesbar: ' . ($offer['pdf_path'] ?? 'n/a'));
                                    }
                                }
                            }
                        }

                        // 4. Warenkorb leeren
                        $formSuccess = true;
                        cartClear();
                        clearInquiryList();
                        recordFormSubmission('contact');
                    } else {
                        $formError = 'form_error_send';
                    }
                    }
                } else {
                    $formError = 'form_error_message';
                }
            }
        }
    }
}

// Adressfelder vorbereiten
$eventStreet      = '';
$eventHousenumber = '';
$eventZip         = '';
$eventCity        = '';
$eventCountry     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventStreet      = sanitizeInput($_POST['event_street'] ?? '');
    $eventHousenumber = sanitizeInput($_POST['event_housenumber'] ?? '');
    $eventZip         = sanitizeInput($_POST['event_zip'] ?? '');
    $eventCity        = sanitizeInput($_POST['event_city'] ?? '');
    $eventCountry     = sanitizeInput($_POST['event_country'] ?? '');
} else {
    // Aus Session / Warenkorb vorausfüllen
    $eventStreet      = $cart['event_street'] ?? '';
    $eventHousenumber = $cart['event_housenumber'] ?? '';
    $eventZip         = $cart['event_zip'] ?? '';
    $eventCity        = $cart['event_city'] ?? '';
    $eventCountry     = $cart['event_country'] ?? '';

    // Fallback: alte Sessions enthalten nur den kombinierten String
    if (empty($eventStreet) && empty($eventZip) && !empty($cart['event_address'])) {
        $parsed = parseEventAddress($cart['event_address']);
        $eventStreet      = $parsed['street'];
        $eventHousenumber = $parsed['housenumber'];
        $eventZip         = $parsed['zip'];
        $eventCity        = $parsed['city'];
        $eventCountry     = $parsed['country'];
    }
}

$metaData = ['url' => BASE_URL . 'contact.php'];
include PARTIALS_PATH . 'header.php';
$lang = getCurrentLanguage();
?>

<section class="section">
    <div class="container">
        <?php if ($formSuccess): ?>
        <div class="form-success reveal" style="max-width: 800px; margin: 0 auto;">
            <h3><?php echo __('form_success_title'); ?></h3>
            <p><?php echo __('form_success_message'); ?></p>
        </div>
        <?php else: ?>

        <?php if ($formError): ?>
        <div class="reveal" style="max-width: 800px; margin: 0 auto var(--space-6); padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md);">
            <p style="color: #ef4444; margin-bottom: 0;"><?php echo __($formError); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate class="reveal">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div style="position: absolute; left: -9999px;">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <!-- 1. Auswahl Übersicht -->
            <div style="margin-bottom: var(--space-8);">
                <h2 style="font-size: var(--text-2xl); margin-bottom: var(--space-4);"><?php echo $lang === 'de' ? 'Ihre Auswahl' : 'Your Selection'; ?></h2>

                <?php if (empty($cartItems)): ?>
                <div style="text-align: center; padding: var(--space-8); background: var(--color-gray-800); border-radius: var(--radius-lg);">
                    <p style="color: var(--color-gray-500); margin-bottom: var(--space-4);"><?php echo __('cart_empty_title'); ?></p>
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-primary btn-lg"><?php echo __('cart_add_boxes'); ?></a>
                </div>
                <?php else: ?>
                <div style="margin-bottom: var(--space-4);">
                    <?php foreach ($cartItems as $jb): ?>
                    <div class="cart-item" data-id="<?php echo e($jb['id']); ?>" style="display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3); background: var(--color-gray-800); border-radius: var(--radius-md); margin-bottom: var(--space-2);">
                        <img src="<?php echo e(getJukeboxImageUrl($jb['main_image'])); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo e(getLocalizedValue($jb, 'name')); ?></div>
                            <div style="font-size: var(--text-sm); color: var(--color-gray-500);"><?php echo formatPrice($jb['price_day']); ?></div>
                        </div>
                        <div style="font-weight: 600; flex-shrink: 0; margin-right: var(--space-2);"><?php echo formatMoney((float)$jb['price_day'] * max(1, $cart['duration_days'])); ?></div>
                        <button type="button" class="btn btn-dark btn-sm cart-remove" data-id="<?php echo e($jb['id']); ?>" title="<?php echo __('cart_remove'); ?>" style="flex-shrink: 0; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0; font-size: 18px;">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-4);">
                    <!-- Adresse getrennt -->
                    <div>
                        <label class="form-label"><?php echo $lang === 'de' ? 'Adresse der Veranstaltung' : 'Event Address'; ?></label>
                        <div class="form-row" style="margin-bottom: var(--space-2);">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" name="event_street" class="form-input" placeholder="<?php echo $lang === 'de' ? 'Straße' : 'Street'; ?>" value="<?php echo e($eventStreet); ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <input type="text" name="event_housenumber" class="form-input" placeholder="<?php echo $lang === 'de' ? 'Nr.' : 'No.'; ?>" value="<?php echo e($eventHousenumber); ?>">
                            </div>
                        </div>
                        <div class="form-row" style="margin-bottom: var(--space-2);">
                            <div class="form-group" style="flex: 1;">
                                <input type="text" name="event_zip" class="form-input" placeholder="<?php echo $lang === 'de' ? 'PLZ' : 'ZIP'; ?>" value="<?php echo e($eventZip); ?>">
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <input type="text" name="event_city" class="form-input" placeholder="<?php echo $lang === 'de' ? 'Ort' : 'City'; ?>" value="<?php echo e($eventCity); ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" name="event_country" class="form-input" placeholder="<?php echo $lang === 'de' ? 'Land' : 'Country'; ?>" value="<?php echo e($eventCountry); ?>">
                        </div>
                        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($cart['event_address'])): ?>
                        <p style="font-size: var(--text-xs); color: var(--color-gray-500); margin-top: var(--space-2); margin-bottom: 0;">
                            <?php echo $lang === 'de' ? 'Bitte überprüfen und vervollständigen Sie die Adresse.' : 'Please review and complete the address.'; ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('request_rental_period'); ?></label>
                            <div class="date-input-wrapper">
                                <input type="text" id="contact-date" class="form-input" value="<?php echo $cart['date_start'] ? e($cart['date_start'] . ($cart['date_end'] && $cart['date_end'] !== $cart['date_start'] ? ' - ' . $cart['date_end'] : '')) : ''; ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ">
                            </div>
                            <input type="hidden" name="date_start" id="contact-date-start" value="<?php echo e($cart['date_start']); ?>">
                            <input type="hidden" name="date_end" id="contact-date-end" value="<?php echo e($cart['date_end']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __('request_discount_code'); ?></label>
                            <input type="text" name="coupon_code" class="form-input" value="<?php echo e($cart['coupon_code']); ?>" placeholder="CODE" style="text-transform: uppercase;">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 2. Kontaktdaten & Kosten -->
            <div class="contact-grid" style="align-items: start; <?php echo empty($cartItems) ? 'grid-template-columns: 1fr !important;' : ''; ?>">
                <div>
                    <h2 style="font-size: var(--text-2xl); margin-bottom: var(--space-4);"><?php echo __('contact_form_title'); ?></h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('form_firstname'); ?> *</label>
                            <input type="text" name="firstname" class="form-input" required value="<?php echo e($_POST['firstname'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __('form_lastname'); ?> *</label>
                            <input type="text" name="lastname" class="form-input" required value="<?php echo e($_POST['lastname'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo __('form_company'); ?></label>
                        <input type="text" name="company" class="form-input" value="<?php echo e($_POST['company'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('form_email'); ?> *</label>
                            <input type="email" name="email" class="form-input" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __('form_phone'); ?> *</label>
                            <input type="tel" name="phone" class="form-input" required value="<?php echo e($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo $lang === 'de' ? 'Ihre Nachricht' : 'Your Message'; ?></label>
                        <textarea name="message" class="form-textarea" rows="4"><?php echo e($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="privacy" required <?php echo (isset($_POST['privacy']) ? 'checked' : ''); ?>>
                            <span><?php echo __('form_privacy'); ?></span>
                        </label>
                    </div>

                    <?php if (!empty($cartItems) && !$cartAvailability['available']): ?>
                    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-md); padding: var(--space-4); margin-bottom: var(--space-4); color: var(--color-error, #ef4444); font-size: var(--text-sm);">
                        <strong><?php echo __('cart_item_not_available'); ?></strong>
                        <?php foreach ($cartAvailability['conflicts'] as $jukeboxId => $conflicts): ?>
                            <?php foreach ($conflicts as $conflict): ?>
                            <div style="margin-top: var(--space-1);">
                                <?php echo __('cart_item_not_available_period', [
                                    'name' => e(getLocalizedValue(getJukeboxById($jukeboxId), 'name')),
                                    'start' => date('d.m.Y', strtotime($conflict['date_start'])),
                                    'end' => date('d.m.Y', strtotime($conflict['date_end']))
                                ]); ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-lg" style="margin-top: var(--space-2); min-width: 260px;" <?php echo (!empty($cartItems) && !$cartAvailability['available']) ? 'disabled' : ''; ?>>
                        <?php echo __('form_submit'); ?>
                    </button>

                    <p style="color: var(--color-gray-500); font-size: var(--text-xs); margin-top: var(--space-4);">
                        <?php echo __('form_required'); ?>
                    </p>
                </div>

                <div>
                    <?php if (!empty($cartItems)): ?>
                    <div class="contact-info-card" style="position: sticky; top: 100px;">
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-3);"><?php echo $lang === 'de' ? 'Kostenübersicht' : 'Cost Overview'; ?></h3>
                        <?php include PARTIALS_PATH . 'pricing-compact.php'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var lang = '<?php echo e($lang); ?>';
    var defaultDates = <?php echo json_encode($cart['date_start'] ? ($cart['date_end'] && $cart['date_end'] !== $cart['date_start'] ? [$cart['date_start'], $cart['date_end']] : $cart['date_start']) : null); ?>;

    flatpickr('#contact-date', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'd.m.Y',
        locale: lang === 'de' ? 'de' : 'en',
        allowInput: true,
        defaultDate: defaultDates,
        onChange: function(selectedDates, dateStr) {
            var start = selectedDates[0] ? formatDate(selectedDates[0]) : '';
            var end = selectedDates[1] ? formatDate(selectedDates[1]) : '';
            document.getElementById('contact-date-start').value = start;
            document.getElementById('contact-date-end').value = end || start;
            triggerPricingUpdate();
        }
    });

    function formatDate(date) {
        var d = date.getDate().toString().padStart(2, '0');
        var m = (date.getMonth() + 1).toString().padStart(2, '0');
        var y = date.getFullYear();
        return d + '.' + m + '.' + y;
    }

    // Live-Preisberechnung auf contact.php (mit Debounce + API-Schutz)
    var pricingTimeout;
    var lastPricingParams = '';

    function triggerPricingUpdate() {
        clearTimeout(pricingTimeout);
        pricingTimeout = setTimeout(function() {
            var street = document.querySelector('input[name="event_street"]')?.value || '';
            var housenumber = document.querySelector('input[name="event_housenumber"]')?.value || '';
            var zip = document.querySelector('input[name="event_zip"]')?.value || '';
            var city = document.querySelector('input[name="event_city"]')?.value || '';
            var country = document.querySelector('input[name="event_country"]')?.value || '';
            var dateStart = document.getElementById('contact-date-start').value;
            var dateEnd = document.getElementById('contact-date-end').value;
            var coupon = document.querySelector('input[name="coupon_code"]')?.value || '';

            // Nur berechnen wenn Adresse halbwegs vollständig ist (mindestens PLZ + Ort)
            if (!zip.trim() || !city.trim()) {
                return;
            }

            var params = 'date_start=' + encodeURIComponent(dateStart)
                + '&date_end=' + encodeURIComponent(dateEnd)
                + '&street=' + encodeURIComponent(street)
                + '&housenumber=' + encodeURIComponent(housenumber)
                + '&zip=' + encodeURIComponent(zip)
                + '&city=' + encodeURIComponent(city)
                + '&country=' + encodeURIComponent(country)
                + '&coupon=' + encodeURIComponent(coupon);

            // Verhindere doppelte Anfragen mit identischen Parametern
            if (params === lastPricingParams) return;
            lastPricingParams = params;

            var container = document.querySelector('.contact-info-card');
            if (container) {
                container.style.opacity = '0.6';
            }

            fetch('includes/ajax.php?action=updateCartContact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && container) {
                        container.innerHTML = '<h3 style="font-size: var(--text-lg); margin-bottom: var(--space-3);">' + (lang === 'de' ? 'Kostenübersicht' : 'Cost Overview') + '</h3>' + data.pricingHtml;
                        if (typeof data.available !== 'undefined' && !data.available) {
                            window.location.reload();
                        }
                    }
                })
                .finally(function() {
                    if (container) container.style.opacity = '1';
                });
        }, 800);
    }

    // Listener auf allen relevanten Feldern
    ['event_street', 'event_housenumber', 'event_zip', 'event_city', 'event_country', 'coupon_code'].forEach(function(name) {
        var el = document.querySelector('input[name="' + name + '"]');
        if (el) {
            el.addEventListener('input', triggerPricingUpdate);
            el.addEventListener('change', triggerPricingUpdate);
        }
    });

    // Jukeboxen aus Kontaktformular entfernen
    document.querySelectorAll('.cart-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            fetch('includes/ajax.php?action=cartRemove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    });
});
</script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
