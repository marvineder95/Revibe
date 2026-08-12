<?php
/**
 * Datenmodell für Vermietungen / Reservierungen (rentals)
 */

require_once __DIR__ . '/database.php';

initDatabase();

/**
 * Neue Reservierung erstellen
 */
function createRental($data) {
    $db = getDbConnection();
    if (!$db) return false;

    $id = generateRentalId();
    $now = date('Y-m-d H:i:s');

    // Daten ins Format Y-m-d normalisieren (Eingabe kann d.m.Y oder Y-m-d sein)
    $dateStart = date('Y-m-d', strtotime($data['date_start']));
    $dateEnd = date('Y-m-d', strtotime($data['date_end']));

    try {
        $stmt = $db->prepare('
            INSERT INTO rentals (
                id, jukebox_id, inquiry_id, offer_id, date_start, date_end, status, created_at, updated_at
            ) VALUES (
                :id, :jukebox_id, :inquiry_id, :offer_id, :date_start, :date_end, :status, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':id' => $id,
            ':jukebox_id' => $data['jukebox_id'],
            ':inquiry_id' => $data['inquiry_id'] ?? null,
            ':offer_id' => $data['offer_id'] ?? null,
            ':date_start' => $dateStart,
            ':date_end' => $dateEnd,
            ':status' => $data['status'] ?? 'reserved',
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        return getRentalById($id);
    } catch (PDOException $e) {
        error_log('Fehler beim Erstellen der Reservierung: ' . $e->getMessage());
        return false;
    }
}

/**
 * Reservierung anhand ID laden
 */
function getRentalById($id) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM rentals WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Reservierung: ' . $e->getMessage());
        return null;
    }
}

/**
 * Alle Reservierungen einer Jukebox laden
 */
function getRentalsByJukebox($jukeboxId, $statuses = []) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        if (empty($statuses)) {
            $stmt = $db->prepare('SELECT * FROM rentals WHERE jukebox_id = :jukebox_id ORDER BY date_start');
            $stmt->execute([':jukebox_id' => $jukeboxId]);
        } else {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $stmt = $db->prepare("SELECT * FROM rentals WHERE jukebox_id = :jukebox_id AND status IN ({$placeholders}) ORDER BY date_start");
            $stmt->execute(array_merge([$jukeboxId], $statuses));
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Reservierungen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Alle Reservierungen laden (optional gefiltert)
 */
function getAllRentals($filters = []) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $where = [];
        $params = [];

        if (!empty($filters['jukebox_id'])) {
            $where[] = 'r.jukebox_id = :jukebox_id';
            $params[':jukebox_id'] = $filters['jukebox_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'r.date_end >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'r.date_start <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $sql = '
            SELECT r.*, j.name as jukebox_name, j.name_en as jukebox_name_en
            FROM rentals r
            LEFT JOIN jukeboxes j ON r.jukebox_id = j.id
        ';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.date_start, j.name';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Reservierungen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Status einer Reservierung aktualisieren
 */
function updateRentalStatus($id, $status) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE rentals
            SET status = :status, updated_at = :updated_at
            WHERE id = :id
        ');
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Aktualisieren des Reservierungs-Status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Reservierungen einer Anfrage stornieren
 */
function cancelRentalsByInquiry($inquiryId) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE rentals
            SET status = :status, updated_at = :updated_at
            WHERE inquiry_id = :inquiry_id AND status != :status
        ');
        return $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':status' => 'cancelled',
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Stornieren der Reservierungen: ' . $e->getMessage());
        return false;
    }
}

/**
 * Reservierungen eines Angebots bestätigen
 */
function confirmRentalsByOffer($offerId) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE rentals
            SET status = :status, offer_id = :offer_id, updated_at = :updated_at
            WHERE inquiry_id = (
                SELECT inquiry_id FROM offers WHERE id = :offer_id_check
            ) AND status = :old_status
        ');
        return $stmt->execute([
            ':status' => 'confirmed',
            ':offer_id' => $offerId,
            ':offer_id_check' => $offerId,
            ':old_status' => 'reserved',
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Bestätigen der Reservierungen: ' . $e->getMessage());
        return false;
    }
}

/**
 * Reservierungen einer Anfrage mit einem Angebot verknüpfen
 */
function linkRentalsToOffer($inquiryId, $offerId) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('
            UPDATE rentals
            SET offer_id = :offer_id, updated_at = :updated_at
            WHERE inquiry_id = :inquiry_id
        ');
        return $stmt->execute([
            ':inquiry_id' => $inquiryId,
            ':offer_id' => $offerId,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Verknüpfen der Reservierungen mit Angebot: ' . $e->getMessage());
        return false;
    }
}

/**
 * Prüft, ob eine Jukebox im angegebenen Zeitraum verfügbar ist.
 * Überschneidung liegt vor, wenn die Zeiträume sich überlappen.
 * Optional kann eine inquiry_id ausgenommen werden (für Updates).
 */
function isJukeboxAvailable($jukeboxId, $dateStart, $dateEnd, $excludeInquiryId = null) {
    $db = getDbConnection();
    if (!$db) return false;

    $start = date('Y-m-d', strtotime($dateStart));
    $end = date('Y-m-d', strtotime($dateEnd));

    try {
        $sql = '
            SELECT COUNT(*) as count
            FROM rentals
            WHERE jukebox_id = :jukebox_id
              AND status IN ("reserved", "confirmed")
              AND date_start <= :date_end
              AND date_end >= :date_start
        ';
        $params = [
            ':jukebox_id' => $jukeboxId,
            ':date_start' => $start,
            ':date_end' => $end
        ];

        if (!empty($excludeInquiryId)) {
            $sql .= ' AND (inquiry_id IS NULL OR inquiry_id != :exclude_inquiry_id)';
            $params[':exclude_inquiry_id'] = $excludeInquiryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return ((int)$result['count']) === 0;
    } catch (PDOException $e) {
        error_log('Fehler bei der Verfügbarkeitsprüfung: ' . $e->getMessage());
        return false;
    }
}

/**
 * Liefert alle blockierenden Reservierungen für einen Zeitraum.
 */
function getConflictingRentals($jukeboxId, $dateStart, $dateEnd, $excludeInquiryId = null) {
    $db = getDbConnection();
    if (!$db) return [];

    $start = date('Y-m-d', strtotime($dateStart));
    $end = date('Y-m-d', strtotime($dateEnd));

    try {
        $sql = '
            SELECT r.*, j.name as jukebox_name
            FROM rentals r
            LEFT JOIN jukeboxes j ON r.jukebox_id = j.id
            WHERE r.jukebox_id = :jukebox_id
              AND r.status IN ("reserved", "confirmed")
              AND r.date_start <= :date_end
              AND r.date_end >= :date_start
        ';
        $params = [
            ':jukebox_id' => $jukeboxId,
            ':date_start' => $start,
            ':date_end' => $end
        ];

        if (!empty($excludeInquiryId)) {
            $sql .= ' AND (r.inquiry_id IS NULL OR r.inquiry_id != :exclude_inquiry_id)';
            $params[':exclude_inquiry_id'] = $excludeInquiryId;
        }

        $sql .= ' ORDER BY r.date_start';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden blockierender Reservierungen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Prüft die Verfügbarkeit aller Jukeboxen im Warenkorb für den Warenkorb-Zeitraum.
 */
function checkCartAvailability($cart) {
    $excludeInquiryId = $cart['inquiry_id'] ?? null;
    $conflicts = [];

    foreach ($cart['items'] as $jukeboxId) {
        if (!isJukeboxAvailable($jukeboxId, $cart['date_start'], $cart['date_end'], $excludeInquiryId)) {
            $conflicts[$jukeboxId] = getConflictingRentals($jukeboxId, $cart['date_start'], $cart['date_end'], $excludeInquiryId);
        }
    }

    return [
        'available' => empty($conflicts),
        'conflicts' => $conflicts
    ];
}

/**
 * Hilfsfunktion: Eindeutige ID generieren
 */
function generateRentalId() {
    return 'rent_' . bin2hex(random_bytes(8));
}
