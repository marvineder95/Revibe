<?php
/**
 * Datenmodell für Rechnungen (invoices)
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/offers-model.php';

initDatabase();

/**
 * Neue Rechnung erstellen
 */
function createInvoice($offerId, $pdfPath, $amountGross) {
    $db = getDbConnection();
    if (!$db) return false;

    $offer = getOfferById($offerId);
    if (!$offer) return false;

    $id = generateInvoiceId();
    $invoiceNumber = generateNextInvoiceNumber();
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $db->prepare('
            INSERT INTO invoices (
                id, offer_id, inquiry_id, invoice_number, pdf_path, amount_gross, status, created_at, updated_at
            ) VALUES (
                :id, :offer_id, :inquiry_id, :invoice_number, :pdf_path, :amount_gross, :status, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':id' => $id,
            ':offer_id' => $offerId,
            ':inquiry_id' => $offer['inquiry_id'],
            ':invoice_number' => $invoiceNumber,
            ':pdf_path' => $pdfPath,
            ':amount_gross' => (float)$amountGross,
            ':status' => 'open',
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        updateInquiryStatus($offer['inquiry_id'], 'invoiced');

        return getInvoiceById($id);
    } catch (PDOException $e) {
        error_log('Fehler beim Erstellen der Rechnung: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rechnung anhand ID laden
 */
function getInvoiceById($id) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM invoices WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rechnung: ' . $e->getMessage());
        return null;
    }
}

/**
 * Rechnung anhand Nummer laden
 */
function getInvoiceByNumber($number) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM invoices WHERE invoice_number = :number LIMIT 1');
        $stmt->execute([':number' => $number]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rechnung per Nummer: ' . $e->getMessage());
        return null;
    }
}

/**
 * PDF-Pfad einer Rechnung aktualisieren
 */
function updateInvoicePdfPath($id, $pdfPath) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('UPDATE invoices SET pdf_path = :pdf_path, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':pdf_path' => $pdfPath,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Aktualisieren des Rechnungs-Pfads: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rechnung als bezahlt markieren
 */
function markInvoicePaid($id) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('UPDATE invoices SET status = :status, paid_at = :paid_at, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':status' => 'paid',
            ':paid_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Markieren der Rechnung als bezahlt: ' . $e->getMessage());
        return false;
    }
}

/**
 * Alle Rechnungen laden (für Admin)
 */
function getAllInvoices($limit = 100, $offset = 0) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('
            SELECT inv.*, i.firstname, i.lastname, i.company, i.email, o.offer_number
            FROM invoices inv
            LEFT JOIN inquiries i ON inv.inquiry_id = i.id
            LEFT JOIN offers o ON inv.offer_id = o.id
            ORDER BY inv.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Rechnungen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Rechnungen nach Status zählen
 */
function countInvoicesByStatus($status) {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM invoices WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log('Fehler beim Zählen der Rechnungen: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Gesamtumsatz berechnen
 */
function getTotalRevenue() {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare('SELECT SUM(amount_gross) as total FROM invoices');
        $stmt->execute();
        return (float)($stmt->fetch()['total'] ?? 0);
    } catch (PDOException $e) {
        error_log('Fehler beim Berechnen des Umsatzes: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Offenen Umsatz berechnen
 */
function getOpenRevenue() {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare('SELECT SUM(amount_gross) as total FROM invoices WHERE status = :status');
        $stmt->execute([':status' => 'open']);
        return (float)($stmt->fetch()['total'] ?? 0);
    } catch (PDOException $e) {
        error_log('Fehler beim Berechnen des offenen Umsatzes: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Nächste Rechnungsnummer generieren
 */
function generateNextInvoiceNumber() {
    $db = getDbConnection();
    if (!$db) return 'R-' . date('Y') . '-00001';

    $year = date('Y');
    $prefix = 'R-' . $year . '-';

    try {
        $stmt = $db->prepare('SELECT invoice_number FROM invoices WHERE invoice_number LIKE :prefix ORDER BY invoice_number DESC LIMIT 1');
        $stmt->execute([':prefix' => $prefix . '%']);
        $row = $stmt->fetch();

        if ($row) {
            $lastNumber = (int)substr($row['invoice_number'], -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string)$nextNumber, 5, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log('Fehler beim Generieren der Rechnungsnummer: ' . $e->getMessage());
        return $prefix . '00001';
    }
}

/**
 * Eindeutige ID generieren
 */
function generateInvoiceId() {
    return 'invoice_' . bin2hex(random_bytes(8));
}
