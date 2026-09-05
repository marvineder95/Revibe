<?php
/**
 * PDF-Generierung für Angebote und Rechnungen
 * Verwendet TCPDF mit Revibe-Branding
 */

require_once __DIR__ . '/tcpdf/tcpdf.php';

// Revibe Brand-Farben
const REVIBE_BLUE = [0, 102, 177];      // #0066B1
const REVIBE_RED = [229, 26, 34];       // #E51A22
const REVIBE_PURPLE = [132, 83, 131];   // #845383
const REVIBE_DARK = [32, 33, 33];       // #202121
const REVIBE_GRAY = [128, 128, 128];    // #808080
const REVIBE_LIGHT = [245, 245, 240];   // #F5F5F0
const REVIBE_CREAM = [250, 249, 246];   // #FAF9F6
const REVIBE_WHITE = [255, 255, 255];

/**
 * PDF-Hilfsklasse mit gemeinsamen Layout-Elementen
 */
class RevibePdf extends TCPDF {
    private $documentType;
    private $documentNumber;
    private $logoPath;

    public function __construct($documentType, $documentNumber) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->documentType = $documentType;
        $this->documentNumber = $documentNumber;
        $this->logoPath = ROOT_PATH . 'assets/images/RevibeLogoPdf.png';

        // Meta-Daten
        $this->SetCreator('Revibe');
        $this->SetAuthor('Revibe');
        $this->SetTitle($documentType . ' ' . $documentNumber);

        // Standard-Font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Margen
        $this->SetMargins(15, 55, 15);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(15);
        $this->SetAutoPageBreak(true, 25);
    }

    /**
     * Header mit Logo, Firmeninfos und Dokumententyp
     */
    public function Header() {
        // Hintergrund-Header-Balken weiß
        $this->SetFillColor(REVIBE_WHITE[0], REVIBE_WHITE[1], REVIBE_WHITE[2]);
        $this->Rect(0, 0, 210, 42, 'F');

        // Logo einfügen (links)
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 15, 14, 35, 14, '', 'L', 'T', 0, false);
        }

        // Dokumententyp rechts in Brand-Blau
        $this->SetY(11);
        $this->SetFont('dejavusans', 'B', 18);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(0, 10, strtoupper($this->documentType), 0, 1, 'R');

        // Dokumentennummer rechts in Grau
        $this->SetFont('dejavusans', '', 10);
        $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
        $this->Cell(0, 6, $this->documentNumber, 0, 1, 'R');

        // Farbiger Akzentstreifen unter dem Header
        $this->SetFillColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Rect(0, 42, 210, 4, 'F');
    }

    /**
     * Footer mit Seitenzahl und Branding
     */
    public function Footer() {
        $this->SetY(-18);

        // Trennlinie
        $this->SetDrawColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(3);

        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
        $this->Cell(0, 5, COMPANY_NAME . ' | ' . COMPANY_STREET . ', ' . COMPANY_ZIP . ' ' . COMPANY_CITY . ' | ' . COMPANY_EMAIL . ' | ' . COMPANY_WEB, 0, 0, 'L');
        $this->Cell(0, 5, 'Seite ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    /**
     * Empfänger-Adresse ausgeben
     */
    public function addRecipientAddress($inquiry) {
        $this->SetY(52);
        $this->SetX(15);

        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(0, 7, 'Empfänger', 0, 1, 'L');

        $this->SetFont('dejavusans', '', 10);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);

        $name = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));
        if (!empty($inquiry['company'])) {
            $this->Cell(0, 5, $inquiry['company'], 0, 1, 'L');
        }
        $this->Cell(0, 5, $name, 0, 1, 'L');

        // Adresse aus Inquiry extrahieren
        $address = $inquiry['event_address'] ?? '';
        $lines = array_filter(array_map('trim', explode("\n", $address)));
        foreach ($lines as $line) {
            $this->Cell(0, 5, $line, 0, 1, 'L');
        }
        $this->Ln(2);
    }

    /**
     * Dokumentendetails (Datum, Gültigkeit, etc.)
     */
    public function addDocumentDetails($details) {
        $this->SetY(52);
        $this->SetX(120);

        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(0, 7, 'Dokumentendetails', 0, 1, 'L');

        $this->SetFont('dejavusans', '', 10);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);

        foreach ($details as $label => $value) {
            $this->SetX(120);
            $this->SetFont('dejavusans', 'B', 9);
            $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
            $this->Cell(40, 6, $label . ':', 0, 0, 'L');
            $this->SetFont('dejavusans', '', 9);
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
            $this->Cell(0, 6, $value, 0, 1, 'L');
        }
        $this->Ln(5);
    }

    /**
     * Begrüßungstext / Einleitung
     */
    public function addIntroText($text) {
        $this->SetFont('dejavusans', '', 10);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        $this->MultiCell(0, 6, $text, 0, 'L');
        $this->Ln(4);
    }

    /**
     * Positions-Tabelle
     */
    public function addPositionsTable($items, $pricing) {
        // Header
        $this->SetFillColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->SetFont('dejavusans', 'B', 9);
        $this->SetTextColor(REVIBE_WHITE[0], REVIBE_WHITE[1], REVIBE_WHITE[2]);

        $this->Cell(80, 9, 'Beschreibung', 0, 0, 'L', true);
        $this->Cell(25, 9, 'Menge', 0, 0, 'C', true);
        $this->Cell(30, 9, 'Einheit', 0, 0, 'R', true);
        $this->Cell(35, 9, 'Gesamt', 0, 1, 'R', true);

        // Zeilen
        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);

        $fill = false;
        foreach ($items as $item) {
            if ($fill) {
                $this->SetFillColor(REVIBE_CREAM[0], REVIBE_CREAM[1], REVIBE_CREAM[2]);
            } else {
                $this->SetFillColor(REVIBE_WHITE[0], REVIBE_WHITE[1], REVIBE_WHITE[2]);
            }
            $this->Cell(80, 8, $item['name'], 0, 0, 'L', true);
            $this->Cell(25, 8, $item['quantity'], 0, 0, 'C', true);
            $this->Cell(30, 8, $item['unit_price'], 0, 0, 'R', true);
            $this->Cell(35, 8, $item['total'], 0, 1, 'R', true);
            $fill = !$fill;
        }

        // Trennlinie vor Summen
        $this->Ln(2);
        $this->SetDrawColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
        $this->SetLineWidth(0.2);
        $this->Line(120, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);

        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);

        $itemsSubtotal = ($pricing['rental_subtotal'] ?? 0) + ($pricing['transport_net'] ?? 0) + ($pricing['custom_net'] ?? 0);
        $this->Cell(135, 7, 'Zwischensumme', 0, 0, 'R');
        $this->Cell(35, 7, formatMoneyPdf($itemsSubtotal), 0, 1, 'R');

        if (!empty($pricing['duration_discount_amount']) && $pricing['duration_discount_amount'] > 0) {
            $this->SetTextColor(REVIBE_RED[0], REVIBE_RED[1], REVIBE_RED[2]);
            $this->Cell(135, 7, 'Mietdauer-Rabatt (' . formatMoneyRaw($pricing['duration_discount_percent']) . '%)', 0, 0, 'R');
            $this->Cell(35, 7, '-' . formatMoneyPdf($pricing['duration_discount_amount']), 0, 1, 'R');
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        }

        if (!empty($pricing['quantity_discount_amount']) && $pricing['quantity_discount_amount'] > 0) {
            $this->SetTextColor(REVIBE_RED[0], REVIBE_RED[1], REVIBE_RED[2]);
            $this->Cell(135, 7, 'Mengenrabatt (' . formatMoneyRaw($pricing['quantity_discount_percent']) . '%)', 0, 0, 'R');
            $this->Cell(35, 7, '-' . formatMoneyPdf($pricing['quantity_discount_amount']), 0, 1, 'R');
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        }

        if (!empty($pricing['coupon_discount_amount']) && $pricing['coupon_discount_amount'] > 0) {
            $this->SetTextColor(REVIBE_RED[0], REVIBE_RED[1], REVIBE_RED[2]);
            $this->Cell(135, 7, 'Coupon ' . $pricing['coupon_code'] . ' (' . formatMoneyRaw($pricing['coupon_discount_percent']) . '%)', 0, 0, 'R');
            $this->Cell(35, 7, '-' . formatMoneyPdf($pricing['coupon_discount_amount']), 0, 1, 'R');
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        }

        $this->SetFont('dejavusans', 'B', 9);
        $this->Cell(135, 7, 'Mietkosten netto', 0, 0, 'R');
        $this->Cell(35, 7, formatMoneyPdf($pricing['rental_net']), 0, 1, 'R');

        $this->SetFont('dejavusans', '', 9);
        if (!empty($pricing['transport_net']) && $pricing['transport_net'] > 0) {
            $this->Cell(135, 7, 'Transport netto', 0, 0, 'R');
            $this->Cell(35, 7, formatMoneyPdf($pricing['transport_net']), 0, 1, 'R');
        }

        $this->SetFont('dejavusans', 'B', 9);
        $this->Cell(135, 7, 'Gesamt netto', 0, 0, 'R');
        $this->Cell(35, 7, formatMoneyPdf($pricing['total_net']), 0, 1, 'R');

        $this->SetFont('dejavusans', '', 9);
        $this->Cell(135, 7, 'USt. (' . formatMoneyRaw($pricing['tax_rate']) . '%)', 0, 0, 'R');
        $this->Cell(35, 7, formatMoneyPdf($pricing['tax_amount']), 0, 1, 'R');

        $this->SetFont('dejavusans', 'B', 11);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(135, 9, 'Gesamt brutto', 0, 0, 'R');
        $this->Cell(35, 9, formatMoneyPdf($pricing['total_gross']), 0, 1, 'R');
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);

        if (!empty($pricing['contract_fee_amount']) && $pricing['contract_fee_amount'] > 0) {
            $this->SetFont('dejavusans', '', 9);
            $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
            $this->Cell(135, 7, 'Vertragsgebühr (' . formatMoneyRaw($pricing['contract_fee_percent']) . '%)', 0, 0, 'R');
            $this->Cell(35, 7, formatMoneyPdf($pricing['contract_fee_amount']), 0, 1, 'R');

            $this->SetFont('dejavusans', 'B', 11);
            $this->SetTextColor(REVIBE_RED[0], REVIBE_RED[1], REVIBE_RED[2]);
            $this->Cell(135, 9, 'Gesamtbetrag inkl. Vertragsgebühr', 0, 0, 'R');
            $this->Cell(35, 9, formatMoneyPdf($pricing['total_with_fee']), 0, 1, 'R');
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        }

        $this->Ln(5);
    }

    /**
     * Fußnoten / Hinweise
     */
    public function addNotes($notes) {
        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
        foreach ($notes as $note) {
            $this->MultiCell(0, 5, $note, 0, 'L');
        }
    }

    /**
     * Bankdaten für Rechnung
     */
    public function addBankDetails() {
        $this->Ln(6);

        // Hintergrundbox
        $startY = $this->GetY();
        $this->SetFillColor(REVIBE_CREAM[0], REVIBE_CREAM[1], REVIBE_CREAM[2]);
        $this->Rect(15, $startY, 180, 40, 'F');
        $this->SetDrawColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->SetLineWidth(0.5);
        $this->Rect(15, $startY, 180, 40, 'D');

        $this->SetY($startY + 4);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(0, 6, 'Zahlungsinformationen', 0, 1, 'L');

        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
        $this->Cell(60, 6, 'Bank:', 0, 0, 'L');
        $this->Cell(0, 6, COMPANY_BANK_NAME, 0, 1, 'L');
        $this->Cell(60, 6, 'IBAN:', 0, 0, 'L');
        $this->Cell(0, 6, COMPANY_IBAN, 0, 1, 'L');
        $this->Cell(60, 6, 'BIC:', 0, 0, 'L');
        $this->Cell(0, 6, COMPANY_BIC, 0, 1, 'L');
        $this->SetFont('dejavusans', 'B', 9);
        $this->SetTextColor(REVIBE_RED[0], REVIBE_RED[1], REVIBE_RED[2]);
        $this->Cell(60, 6, 'Zahlungsziel:', 0, 0, 'L');
        $this->Cell(0, 6, '14 Tage nach Rechnungsdatum', 0, 1, 'L');
        $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
    }

    /**
     * Kundenunterschrift in die Rechnung einfügen
     */
    public function addSignature($signatureData, $signerName = '') {
        if (empty($signatureData)) return;

        // Base64-Präfix entfernen
        if (strpos($signatureData, 'data:image') === 0) {
            $signatureData = substr($signatureData, strpos($signatureData, ',') + 1);
        }

        $imageBlob = base64_decode($signatureData, true);
        if ($imageBlob === false || empty($imageBlob)) return;

        $this->Ln(8);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(REVIBE_BLUE[0], REVIBE_BLUE[1], REVIBE_BLUE[2]);
        $this->Cell(0, 6, 'Unterschrift', 0, 1, 'L');

        if (!empty($signerName)) {
            $this->SetFont('dejavusans', '', 9);
            $this->SetTextColor(REVIBE_DARK[0], REVIBE_DARK[1], REVIBE_DARK[2]);
            $this->Cell(0, 6, $signerName, 0, 1, 'L');
        }

        $this->Ln(2);
        $this->Image('@' . $imageBlob, $this->GetX(), $this->GetY(), 60, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->Ln(22);

        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(REVIBE_GRAY[0], REVIBE_GRAY[1], REVIBE_GRAY[2]);
        $this->Cell(0, 5, 'Mit Ihrer Unterschrift akzeptieren Sie die angebotenen Leistungen und Geschäftsbedingungen.', 0, 1, 'L');
    }
}

/**
 * Angebots-PDF erzeugen
 */
function generateOfferPdf($inquiry, $offerNumber, $validUntil) {
    ensurePdfDirectories();

    $pdf = new RevibePdf('Angebot', $offerNumber);
    $pdf->AddPage();

    $pdf->addRecipientAddress($inquiry);

    $pdf->addDocumentDetails([
        'Angebotsnummer' => $offerNumber,
        'Datum' => date('d.m.Y', strtotime($inquiry['created_at'])),
        'Gültig bis' => date('d.m.Y', strtotime($validUntil)),
        'Mietdauer' => date('d.m.Y', strtotime($inquiry['date_start'])) . ' - ' . date('d.m.Y', strtotime($inquiry['date_end']))
    ]);

    $name = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));
    $pdf->addIntroText("Sehr geehrte/r " . $name . ",\n\nvielen Dank für Ihre Anfrage. Wir freuen uns, Ihnen folgendes unverbindliches Angebot zu unterbreiten:");

    // Positionsdaten aufbereiten
    $pricing = $inquiry['pricing_json'] ?? [];
    $items = [];
    if (!empty($pricing['items'])) {
        foreach ($pricing['items'] as $item) {
            $items[] = [
                'name' => $item['name'] . ' (' . ($item['days'] ?? $inquiry['duration_days'] ?? 1) . ' Tage)',
                'quantity' => '1',
                'unit_price' => formatMoneyPdf($item['price_day']) . ' / Tag',
                'total' => formatMoneyPdf($item['total'])
            ];
        }
    }

    $transportItem = buildPdfTransportItem($inquiry, $pricing);
    if ($transportItem) {
        $items[] = $transportItem;
    }

    foreach (buildPdfCustomItems($pricing) as $customItem) {
        $items[] = $customItem;
    }

    $pdf->addPositionsTable($items, $pricing);

    $notes = [
        'Dieses Angebot ist unverbindlich und gilt bis zum ' . date('d.m.Y', strtotime($validUntil)) . '.',
        'Bei Annahme des Angebots erhalten Sie umgehend eine verbindliche Rechnung.',
        'Die genaue Anlieferungsuhrzeit wird gerne mit Ihnen individuell abgestimmt – die Box steht Ihnen ab diesem Zeitpunkt 24 Stunden lang zur Verfügung!'
    ];

    if (!empty($inquiry['transport_error'])) {
        $notes[] = 'Transportkosten: Die automatische Berechnung der Transportkosten war nicht möglich. Die Transportkosten sind daher nicht in diesem Angebot enthalten und werden Ihnen nachträglich in einem separaten Angebot mitgeteilt.';
    }

    $pdf->addNotes($notes);

    $filename = sanitizeFileName('Angebot_' . $offerNumber . '.pdf');
    $path = PDF_UPLOAD_PATH . 'offers/' . $filename;
    $pdf->Output($path, 'F');

    return [
        'path' => $path,
        'filename' => $filename,
        'url' => PDF_UPLOAD_URL . 'offers/' . $filename
    ];
}

/**
 * Angebot für eine Anfrage erstellen, PDF generieren und per E-Mail an den Kunden senden.
 * Wird von contact.php (automatisch) und admin/offers.php (manuell) verwendet.
 *
 * @param string $inquiryId ID der Anfrage
 * @param int $validDays Gültigkeit des Angebots in Tagen
 * @return array|false Erstelltes Angebot oder false bei Fehler
 */
function buildAndSendOffer($inquiryId, $validDays = 3) {
    $inquiry = getInquiryById($inquiryId);
    if (!$inquiry) {
        error_log('buildAndSendOffer: Anfrage nicht gefunden: ' . $inquiryId);
        return false;
    }

    $email = $inquiry['email'] ?? '';
    if (empty($email)) {
        error_log('buildAndSendOffer: Anfrage hat keine E-Mail: ' . $inquiryId);
        return false;
    }

    // 1. Dummy-PDF mit Platzhalter-Nummer erstellen, um Dateipfad zu erhalten
    $offerPdf = generateOfferPdf($inquiry, 'ANG-' . date('Y') . '-00000', '+' . $validDays . ' days');
    if (!$offerPdf) {
        error_log('buildAndSendOffer: Dummy-PDF konnte nicht erstellt werden für Anfrage: ' . $inquiryId);
        return false;
    }

    $dummyOfferPdfPath = $offerPdf['path'];

    // 2. Angebot in Datenbank speichern
    $offer = createOffer($inquiryId, $offerPdf['path'], $validDays);

    if (!$offer) {
        if (!empty($dummyOfferPdfPath) && file_exists($dummyOfferPdfPath)) {
            @unlink($dummyOfferPdfPath);
        }
        error_log('buildAndSendOffer: Angebot konnte nicht gespeichert werden für Anfrage: ' . $inquiryId);
        return false;
    }

    // 3. Reservierungen mit Angebot verknüpfen
    linkRentalsToOffer($inquiryId, $offer['id']);

    // 4. Korrekte Angebotsnummer verwenden und PDF neu generieren
    $offerPdf = generateOfferPdf($inquiry, $offer['offer_number'], $offer['valid_until']);
    if ($offerPdf) {
        updateOfferPdfPath($offer['id'], $offerPdf['path']);
        $offer['pdf_path'] = $offerPdf['path'];
    }

    // 5. Altes Dummy-PDF entfernen
    if (!empty($dummyOfferPdfPath) && file_exists($dummyOfferPdfPath)) {
        @unlink($dummyOfferPdfPath);
    }

    // 6. E-Mail an Kunden senden
    $offerLink = rtrim(BASE_URL, '/') . '/offer.php?token=' . $offer['token'];
    $name = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));

    $realOfferPdfPath = !empty($offer['pdf_path']) ? realpath($offer['pdf_path']) : false;
    $realPdfBasePath = realpath(PDF_UPLOAD_PATH);

    if ($realOfferPdfPath !== false && $realPdfBasePath !== false && strpos($realOfferPdfPath, $realPdfBasePath) === 0 && file_exists($realOfferPdfPath)) {
        $custSubject = __('admin_offer_email_subject', ['company' => COMPANY_NAME]);
        $offerHtmlBody = __('admin_offer_email_body', [
            'name' => $name,
            'offer_link' => $offerLink,
            'valid_until' => date('d.m.Y', strtotime($offer['valid_until'])),
            'company' => COMPANY_NAME
        ]);
        $offerPlainBody = "Hallo {$name},\n\nvielen Dank für Ihre Anfrage. Im Anhang finden Sie Ihr unverbindliches Angebot.\n\nSie können das Angebot online einsehen und annehmen oder ablehnen:\n{$offerLink}\n\nDas Angebot ist gültig bis " . date('d.m.Y', strtotime($offer['valid_until'])) . ".\n\nMit freundlichen Grüßen\n" . COMPANY_NAME . " Team";

        $custHeaders = "From: " . MAIL_SENDER . "\r\n";
        $custHeaders .= "Reply-To: " . MAIL_SENDER . "\r\n";
        $custHeaders .= "X-Mailer: PHP/" . phpversion();

        $pdfContent = file_get_contents($realOfferPdfPath);
        $pdfEncoded = chunk_split(base64_encode($pdfContent));
        $pdfFilename = basename($realOfferPdfPath);

        $outerBoundary = bin2hex(random_bytes(16));
        $innerBoundary = bin2hex(random_bytes(16));
        $custHeaders .= "MIME-Version: 1.0\r\n";
        $custHeaders .= "Content-Type: multipart/mixed; boundary=\"{$outerBoundary}\"\r\n";

        $custBodyMime = "--{$outerBoundary}\r\n";
        $custBodyMime .= "Content-Type: multipart/alternative; boundary=\"{$innerBoundary}\"\r\n\r\n";

        $custBodyMime .= "--{$innerBoundary}\r\n";
        $custBodyMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $custBodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $custBodyMime .= $offerPlainBody . "\r\n\r\n";

        $custBodyMime .= "--{$innerBoundary}\r\n";
        $custBodyMime .= "Content-Type: text/html; charset=UTF-8\r\n";
        $custBodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $custBodyMime .= $offerHtmlBody . "\r\n\r\n";
        $custBodyMime .= "--{$innerBoundary}--\r\n\r\n";

        $custBodyMime .= "--{$outerBoundary}\r\n";
        $custBodyMime .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
        $custBodyMime .= "Content-Transfer-Encoding: base64\r\n";
        $custBodyMime .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
        $custBodyMime .= $pdfEncoded . "\r\n";
        $custBodyMime .= "--{$outerBoundary}--";

        $offerMailSent = mail($email, $custSubject, $custBodyMime, $custHeaders);
        if (!$offerMailSent) {
            error_log('Angebots-E-Mail konnte nicht an ' . $email . ' gesendet werden.');
        }
    } else {
        error_log('Angebots-PDF-Pfad ungültig oder nicht lesbar: ' . ($offer['pdf_path'] ?? 'n/a'));
    }

    return $offer;
}

/**
 * Rechnungs-PDF erzeugen
 *
 * @param array $inquiry Anfragedaten
 * @param string $invoiceNumber Rechnungsnummer
 * @param string|null $offerNumber Angebotsnummer
 * @param string|null $signatureData Base64-kodierte PNG-Unterschrift
 */
function generateInvoicePdf($inquiry, $invoiceNumber, $offerNumber = null, $signatureData = null) {
    ensurePdfDirectories();

    $pdf = new RevibePdf('Rechnung', $invoiceNumber);
    $pdf->AddPage();

    $pdf->addRecipientAddress($inquiry);

    $details = [
        'Rechnungsnummer' => $invoiceNumber,
        'Rechnungsdatum' => date('d.m.Y'),
        'Mietzeitraum' => date('d.m.Y', strtotime($inquiry['date_start'])) . ' - ' . date('d.m.Y', strtotime($inquiry['date_end']))
    ];
    if ($offerNumber) {
        $details['Angebotsnummer'] = $offerNumber;
    }
    $pdf->addDocumentDetails($details);

    $name = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));
    $pdf->addIntroText("Sehr geehrte/r " . $name . ",\n\nwir erlauben uns, Ihnen folgende Rechnung zu stellen:");

    $pricing = $inquiry['pricing_json'] ?? [];
    $items = [];
    if (!empty($pricing['items'])) {
        foreach ($pricing['items'] as $item) {
            $items[] = [
                'name' => $item['name'] . ' (' . ($item['days'] ?? $inquiry['duration_days'] ?? 1) . ' Tage)',
                'quantity' => '1',
                'unit_price' => formatMoneyPdf($item['price_day']) . ' / Tag',
                'total' => formatMoneyPdf($item['total'])
            ];
        }
    }

    $transportItem = buildPdfTransportItem($inquiry, $pricing);
    if ($transportItem) {
        $items[] = $transportItem;
    }

    foreach (buildPdfCustomItems($pricing) as $customItem) {
        $items[] = $customItem;
    }

    $pdf->addPositionsTable($items, $pricing);

    $pdf->addNotes([
        'Vielen Dank für Ihr Vertrauen. Bitte überweisen Sie den Rechnungsbetrag innerhalb von 14 Tagen auf das angegebene Konto.',
        'Es handelt sich um eine verbindliche Rechnung gemäß § 33 Tarifpunkt 5 Gebührengesetz 1957 (Vertragsgebühr sofern anwendbar).',
        'UID: ' . COMPANY_UID
    ]);

    $pdf->addBankDetails();

    // Unterschrift des Kunden einfügen, sofern vorhanden
    if (!empty($signatureData)) {
        $pdf->addSignature($signatureData, $name);
    }

    $filename = sanitizeFileName('Rechnung_' . $invoiceNumber . '.pdf');
    $path = PDF_UPLOAD_PATH . 'invoices/' . $filename;
    $pdf->Output($path, 'F');

    return [
        'path' => $path,
        'filename' => $filename,
        'url' => PDF_UPLOAD_URL . 'invoices/' . $filename
    ];
}

/**
 * Hilfsfunktion: Geldbetrag für PDF formatieren
 */
function formatMoneyPdf($amount) {
    return number_format((float)$amount, 2, ',', '.') . ' €';
}

/**
 * Hilfsfunktion: Zusätzliche Positionen für die PDF-Positions-Tabelle erzeugen
 */
function buildPdfCustomItems($pricing) {
    $items = [];
    foreach ((array)($pricing['custom_items'] ?? []) as $ci) {
        if (empty($ci['name']) || (float)$ci['total'] <= 0) {
            continue;
        }
        $qty = (float)$ci['quantity'];
        $qtyFormatted = ($qty == (int)$qty) ? (string)(int)$qty : number_format($qty, 2, ',', '.');
        $items[] = [
            'name' => $ci['name'],
            'quantity' => $qtyFormatted,
            'unit_price' => formatMoneyPdf($ci['unit_price']),
            'total' => formatMoneyPdf($ci['total'])
        ];
    }
    return $items;
}

/**
 * Hilfsfunktion: Transport-Position für die PDF-Positions-Tabelle erzeugen
 */
function buildPdfTransportItem($inquiry, $pricing) {
    $transportNet = (float)($pricing['transport_net'] ?? 0);
    if ($transportNet <= 0) {
        return null;
    }

    $name = 'Transport';
    $distance = (float)($inquiry['transport_distance_km'] ?? 0);
    if ($distance > 0) {
        $name .= ' (ca. ' . number_format($distance, 0, ',', '.') . ' km)';
    }

    return [
        'name' => $name,
        'quantity' => '1',
        'unit_price' => formatMoneyPdf($transportNet),
        'total' => formatMoneyPdf($transportNet)
    ];
}

/**
 * Hilfsfunktion: Dateiname sicher machen
 */
function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9äöüÄÖÜß\-_\.]/u', '_', $filename);
    return trim($filename, '_');
}

/**
 * Verzeichnisse für PDFs sicherstellen
 */
function ensurePdfDirectories() {
    if (!file_exists(PDF_UPLOAD_PATH . 'offers')) {
        mkdir(PDF_UPLOAD_PATH . 'offers', 0755, true);
    }
    if (!file_exists(PDF_UPLOAD_PATH . 'invoices')) {
        mkdir(PDF_UPLOAD_PATH . 'invoices', 0755, true);
    }
}

/**
 * Rechnungs-PDF per E-Mail an den Kunden versenden
 *
 * @param array $invoice Rechnungsdatensatz
 * @param array $inquiry Zugehörige Anfrage
 * @return bool true bei Erfolg, false bei Fehler
 */
function sendInvoiceEmail($invoice, $inquiry) {
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
    $htmlBody = __('admin_invoice_email_body', [
        'name' => $name,
        'company' => COMPANY_NAME
    ]);
    $plainBody = "Hallo {$name},\n\nvielen Dank für die Annahme unseres Angebots. Im Anhang finden Sie Ihre Rechnung.\n\nBitte überweisen Sie den Betrag innerhalb von 14 Tagen auf das in der Rechnung angegebene Konto.\n\nMit freundlichen Grüßen\n" . COMPANY_NAME . " Team";

    $headers = "From: " . MAIL_SENDER . "\r\n";
    $headers .= "Reply-To: " . MAIL_SENDER . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $pdfEncoded = chunk_split(base64_encode($pdfContent));
    $pdfFilename = basename($realPdfPath);

    $outerBoundary = bin2hex(random_bytes(16));
    $innerBoundary = bin2hex(random_bytes(16));
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$outerBoundary}\"\r\n";

    $bodyMime = "--{$outerBoundary}\r\n";
    $bodyMime .= "Content-Type: multipart/alternative; boundary=\"{$innerBoundary}\"\r\n\r\n";

    $bodyMime .= "--{$innerBoundary}\r\n";
    $bodyMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $bodyMime .= $plainBody . "\r\n\r\n";

    $bodyMime .= "--{$innerBoundary}\r\n";
    $bodyMime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $bodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $bodyMime .= $htmlBody . "\r\n\r\n";
    $bodyMime .= "--{$innerBoundary}--\r\n\r\n";

    $bodyMime .= "--{$outerBoundary}\r\n";
    $bodyMime .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
    $bodyMime .= "Content-Transfer-Encoding: base64\r\n";
    $bodyMime .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
    $bodyMime .= $pdfEncoded . "\r\n";
    $bodyMime .= "--{$outerBoundary}--";

    return mail($email, $subject, $bodyMime, $headers);
}

/**
 * Admin-Benachrichtigung versenden, wenn ein Angebot angenommen und eine Rechnung erstellt wurde.
 *
 * @param array $offer Angenommenes Angebot
 * @param array $invoice Erstellte Rechnung
 * @param array $inquiry Zugehörige Anfrage
 * @return bool true bei Erfolg, false bei Fehler
 */
function sendAdminInvoiceNotification($offer, $invoice, $inquiry) {
    $customerName = trim(($inquiry['firstname'] ?? '') . ' ' . ($inquiry['lastname'] ?? ''));
    $customerEmail = $inquiry['email'] ?? '-';
    $offerNumber = $offer['offer_number'] ?? '-';
    $invoiceNumber = $invoice['invoice_number'] ?? '-';
    $amount = formatMoneyPdf($invoice['amount_gross'] ?? 0);

    $subject = '[Revibe] Angebot angenommen – Rechnung erstellt: ' . $offerNumber;
    $plainBody = "Hallo Team,\n\n";
    $plainBody .= "ein Kunde hat soeben ein Angebot angenommen. Die Rechnung wurde automatisch erstellt.\n\n";
    $plainBody .= "Kunde: {$customerName}\n";
    $plainBody .= "E-Mail: {$customerEmail}\n";
    $plainBody .= "Angebot: {$offerNumber}\n";
    $plainBody .= "Rechnung: {$invoiceNumber}\n";
    $plainBody .= "Betrag: {$amount}\n\n";
    $plainBody .= "Bitte im Admin-Bereich prüfen und die Rechnung an den Kunden versenden.\n\n";
    $plainBody .= "Mit freundlichen Grüßen\nRevibe System";

    $htmlBody = "<p>Hallo Team,</p>";
    $htmlBody .= "<p>ein Kunde hat soeben ein Angebot angenommen. Die Rechnung wurde automatisch erstellt.</p>";
    $htmlBody .= "<ul>";
    $htmlBody .= "<li><strong>Kunde:</strong> " . e($customerName) . "</li>";
    $htmlBody .= "<li><strong>E-Mail:</strong> " . e($customerEmail) . "</li>";
    $htmlBody .= "<li><strong>Angebot:</strong> " . e($offerNumber) . "</li>";
    $htmlBody .= "<li><strong>Rechnung:</strong> " . e($invoiceNumber) . "</li>";
    $htmlBody .= "<li><strong>Betrag:</strong> " . e($amount) . "</li>";
    $htmlBody .= "</ul>";
    $htmlBody .= "<p>Bitte im <a href=\"" . rtrim(BASE_URL, '/') . "/admin/invoices.php\">Admin-Bereich</a> prüfen und die Rechnung an den Kunden versenden.</p>";
    $htmlBody .= "<p>Mit freundlichen Grüßen<br>Revibe System</p>";

    $headers = "From: " . MAIL_SENDER . "\r\n";
    $headers .= "Reply-To: " . MAIL_SENDER . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $boundary = bin2hex(random_bytes(16));
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $bodyMime = "--{$boundary}\r\n";
    $bodyMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $bodyMime .= $plainBody . "\r\n\r\n";
    $bodyMime .= "--{$boundary}\r\n";
    $bodyMime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $bodyMime .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $bodyMime .= $htmlBody . "\r\n\r\n";
    $bodyMime .= "--{$boundary}--";

    return mail(MAIL_RECIPIENT, $subject, $bodyMime, $headers);
}
