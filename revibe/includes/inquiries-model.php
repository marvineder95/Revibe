<?php
/**
 * Datenmodell für Kundenanfragen (inquiries)
 */

require_once __DIR__ . '/database.php';

initDatabase();

/**
 * Neue Anfrage speichern
 */
function createInquiry($data) {
    $db = getDbConnection();
    if (!$db) return false;

    $id = generateInquiryId();
    $token = bin2hex(random_bytes(32));
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $db->prepare('
            INSERT INTO inquiries (
                id, token, status, firstname, lastname, company, email, phone, message,
                date_start, date_end, duration_days, event_address, pricing_json,
                transport_distance_km, transport_duration_min, transport_costs, transport_error,
                created_at, updated_at
            ) VALUES (
                :id, :token, :status, :firstname, :lastname, :company, :email, :phone, :message,
                :date_start, :date_end, :duration_days, :event_address, :pricing_json,
                :transport_distance_km, :transport_duration_min, :transport_costs, :transport_error,
                :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':id' => $id,
            ':token' => $token,
            ':status' => $data['status'] ?? 'new',
            ':firstname' => $data['firstname'] ?? '',
            ':lastname' => $data['lastname'] ?? '',
            ':company' => $data['company'] ?? '',
            ':email' => $data['email'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':message' => $data['message'] ?? '',
            ':date_start' => $data['date_start'] ?? '',
            ':date_end' => $data['date_end'] ?? '',
            ':duration_days' => max(1, (int)($data['duration_days'] ?? 1)),
            ':event_address' => $data['event_address'] ?? '',
            ':pricing_json' => !empty($data['pricing_json']) ? (is_string($data['pricing_json']) ? $data['pricing_json'] : json_encode($data['pricing_json'])) : '{}',
            ':transport_distance_km' => (float)($data['transport_distance_km'] ?? 0),
            ':transport_duration_min' => (int)($data['transport_duration_min'] ?? 0),
            ':transport_costs' => (float)($data['transport_costs'] ?? 0),
            ':transport_error' => $data['transport_error'] ?? '',
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        return getInquiryById($id);
    } catch (PDOException $e) {
        error_log('Fehler beim Erstellen der Anfrage: ' . $e->getMessage());
        return false;
    }
}

/**
 * Anfrage anhand ID laden
 */
function getInquiryById($id) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM inquiries WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['pricing_json'] = json_decode($row['pricing_json'] ?? '{}', true);
        }
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Anfrage: ' . $e->getMessage());
        return null;
    }
}

/**
 * Anfrage anhand öffentlichem Token laden
 */
function getInquiryByToken($token) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM inquiries WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        if ($row) {
            $row['pricing_json'] = json_decode($row['pricing_json'] ?? '{}', true);
        }
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Anfrage per Token: ' . $e->getMessage());
        return null;
    }
}

/**
 * Status einer Anfrage aktualisieren
 */
function updateInquiryStatus($id, $status) {
    $db = getDbConnection();
    if (!$db) return false;

    $allowed = ['new', 'offer_sent', 'accepted', 'declined', 'invoiced'];
    if (!in_array($status, $allowed, true)) return false;

    try {
        $stmt = $db->prepare('UPDATE inquiries SET status = :status, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (PDOException $e) {
        error_log('Fehler beim Aktualisieren des Anfrage-Status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Alle Anfragen laden (für Admin)
 */
function getAllInquiries($limit = 100, $offset = 0) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['pricing_json'] = json_decode($row['pricing_json'] ?? '{}', true);
        }
        return $rows;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Anfragen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Anfragen nach Status zählen
 */
function countInquiriesByStatus($status) {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM inquiries WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log('Fehler beim Zählen der Anfragen: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Eindeutige ID generieren
 */
function generateInquiryId() {
    return 'inquiry_' . bin2hex(random_bytes(8));
}
