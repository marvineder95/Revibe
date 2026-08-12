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
            $this->Image($this->logoPath, 15, 9, 70, 28, '', 'L', 'T', 0, false);
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

        $this->Cell(135, 7, 'Zwischensumme', 0, 0, 'R');
        $this->Cell(35, 7, formatMoneyPdf($pricing['rental_subtotal']), 0, 1, 'R');

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
        'Mietdauer' => ($inquiry['duration_days'] ?? 1) . ' Tage'
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

    $pdf->addPositionsTable($items, $pricing);

    $pdf->addNotes([
        'Dieses Angebot ist unverbindlich und gilt bis zum ' . date('d.m.Y', strtotime($validUntil)) . '.',
        'Transportkosten: Die angegebenen Transportkosten verstehen sich als Richtwert. Bei individuellen Anforderkeiten kann der Endpreis davon abweichen.',
        'Bei Annahme des Angebots erhalten Sie umgehend eine verbindliche Rechnung.'
    ]);

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
 * Rechnungs-PDF erzeugen
 */
function generateInvoicePdf($inquiry, $invoiceNumber, $offerNumber = null) {
    ensurePdfDirectories();

    $pdf = new RevibePdf('Rechnung', $invoiceNumber);
    $pdf->AddPage();

    $pdf->addRecipientAddress($inquiry);

    $details = [
        'Rechnungsnummer' => $invoiceNumber,
        'Rechnungsdatum' => date('d.m.Y'),
        'Mietdauer' => ($inquiry['duration_days'] ?? 1) . ' Tage'
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

    $pdf->addPositionsTable($items, $pricing);

    $pdf->addNotes([
        'Vielen Dank für Ihr Vertrauen. Bitte überweisen Sie den Rechnungsbetrag innerhalb von 14 Tagen auf das angegebene Konto.',
        'Es handelt sich um eine verbindliche Rechnung gemäß § 33 Tarifpunkt 5 Gebührengesetz 1957 (Vertragsgebühr sofern anwendbar).',
        'UID: ' . COMPANY_UID
    ]);

    $pdf->addBankDetails();

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
