<?php
/**
 * Datenmodell für Angebote (offers)
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/inquiries-model.php';

initDatabase();

/**
 * Neues Angebot erstellen
 */
function createOffer($inquiryId, $pdfPath, $validDays = 3) {
    $db = getDbConnection();
    if (!$db) return false;

    $inquiry = getInquiryById($inquiryId);
    if (!$inquiry) return false;

    $id = generateOfferId();
    $offerNumber = generateNextOfferNumber();
    $token = bin2hex(random_bytes(32));
    $now = date('Y-m-d H:i:s');
    $validUntil = date('Y-m-d H:i:s', strtotime("+$validDays days", strtotime($now)));

    try {
        $stmt = $db->prepare('
            INSERT INTO offers (
                id, inquiry_id, offer_number, token, pdf_path, valid_until, status, created_at, updated_at
            ) VALUES (
                :id, :inquiry_id, :offer_number, :token, :pdf_path, :valid_until, :status, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':id' => $id,
            ':inquiry_id' => $inquiryId,
            ':offer_number' => $offerNumber,
            ':token' => $token,
            ':pdf_path' => $pdfPath,
            ':valid_until' => $validUntil,
            ':status' => 'pending',
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        updateInquiryStatus($inquiryId, 'offer_sent');

        return getOfferById($id);
    } catch (PDOException $e) {
        error_log('Fehler beim Erstellen des Angebots: ' . $e->getMessage());
        return false;
    }
}

/**
 * Angebot anhand ID laden
 */
function getOfferById($id) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM offers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden des Angebots: ' . $e->getMessage());
        return null;
    }
}

/**
 * Angebot anhand öffentlichem Token laden
 */
function getOfferByToken($token) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM offers WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden des Angebots per Token: ' . $e->getMessage());
        return null;
    }
}

/**
 * PDF-Pfad eines Angebots aktualisieren
 */
function updateOfferPdfPath($id, $pdfPath) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('UPDATE offers SET pdf_path = :pdf_path, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':pdf_path' => $pdfPath,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Aktualisieren des Angebots-Pfads: ' . $e->getMessage());
        return false;
    }
}

/**
 * Angebot annehmen
 */
function acceptOffer($id) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE offers 
            SET status = :status, accepted_at = :accepted_at, updated_at = :updated_at 
            WHERE id = :id
        ');
        $result = $stmt->execute([
            ':id' => $id,
            ':status' => 'accepted',
            ':accepted_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $offer = getOfferById($id);
            if ($offer) {
                updateInquiryStatus($offer['inquiry_id'], 'accepted');
                confirmRentalsByOffer($offer['id']);
            }
        }

        return $result;
    } catch (PDOException $e) {
        error_log('Fehler beim Annehmen des Angebots: ' . $e->getMessage());
        return false;
    }
}

/**
 * Angebot ablehnen
 */
function declineOffer($id) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE offers 
            SET status = :status, declined_at = :declined_at, updated_at = :updated_at 
            WHERE id = :id
        ');
        $result = $stmt->execute([
            ':id' => $id,
            ':status' => 'declined',
            ':declined_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $offer = getOfferById($id);
            if ($offer) {
                updateInquiryStatus($offer['inquiry_id'], 'declined');
                cancelRentalsByInquiry($offer['inquiry_id']);
            }
        }

        return $result;
    } catch (PDOException $e) {
        error_log('Fehler beim Ablehnen des Angebots: ' . $e->getMessage());
        return false;
    }
}

/**
 * Prüft, ob ein Angebot noch gültig ist
 */
function isOfferValid($offer) {
    if (!$offer || $offer['status'] !== 'pending') {
        return false;
    }
    return strtotime($offer['valid_until']) > time();
}

/**
 * Alle Angebote laden (für Admin)
 */
function getAllOffers($limit = 100, $offset = 0) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('
            SELECT o.*, i.firstname, i.lastname, i.company, i.email 
            FROM offers o 
            LEFT JOIN inquiries i ON o.inquiry_id = i.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Angebote: ' . $e->getMessage());
        return [];
    }
}

/**
 * Angebote nach Status zählen
 */
function countOffersByStatus($status) {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM offers WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log('Fehler beim Zählen der Angebote: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Nächste Angebotsnummer generieren
 */
function generateNextOfferNumber() {
    $db = getDbConnection();
    if (!$db) return 'ANG-' . date('Y') . '-00001';

    $year = date('Y');
    $prefix = 'ANG-' . $year . '-';

    try {
        $stmt = $db->prepare('SELECT offer_number FROM offers WHERE offer_number LIKE :prefix ORDER BY offer_number DESC LIMIT 1');
        $stmt->execute([':prefix' => $prefix . '%']);
        $row = $stmt->fetch();

        if ($row) {
            $lastNumber = (int)substr($row['offer_number'], -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string)$nextNumber, 5, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log('Fehler beim Generieren der Angebotsnummer: ' . $e->getMessage());
        return $prefix . '00001';
    }
}

/**
 * Eindeutige ID generieren
 */
function generateOfferId() {
    return 'offer_' . bin2hex(random_bytes(8));
}
