<?php
/**
 * Datenmodell für Bewertungen / Rezensionen
 */

// Sicherstellen, dass die Datenbank-Tabelle existiert
initDatabase();

/**
 * Eindeutige ID für eine Rezension generieren
 */
function generateReviewId() {
    return 'rev_' . bin2hex(random_bytes(8));
}

/**
 * Alle genehmigten Rezensionen laden (neueste zuerst)
 */
function getApprovedReviews($limit = null) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $sql = '
            SELECT id, name, rating, title, text, status, created_at, updated_at
            FROM reviews
            WHERE status = "approved"
            ORDER BY created_at DESC
        ';
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
        }
        $stmt = $db->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rezensionen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Eine einzelne Rezension laden
 */
function getReviewById($id) {
    $db = getDbConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare('SELECT * FROM reviews WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rezension: ' . $e->getMessage());
        return null;
    }
}

/**
 * Neue Rezension erstellen
 */
function createReview($data) {
    $db = getDbConnection();
    if (!$db) return false;

    $id = generateReviewId();
    $name = sanitizeInput($data['name'] ?? '');
    $rating = max(1, min(5, (int)($data['rating'] ?? 5)));
    $title = sanitizeInput($data['title'] ?? '');
    $text = sanitizeInput($data['text'] ?? '');
    $status = in_array($data['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $data['status'] : 'pending';
    $now = date('Y-m-d H:i:s');

    if (empty($name) || empty($text)) {
        return false;
    }

    try {
        $stmt = $db->prepare('
            INSERT INTO reviews (id, name, rating, title, text, status, created_at, updated_at)
            VALUES (:id, :name, :rating, :title, :text, :status, :created_at, :updated_at)
        ');
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':rating' => $rating,
            ':title' => $title,
            ':text' => $text,
            ':status' => $status,
            ':created_at' => $now,
            ':updated_at' => $now
        ]);
        return $id;
    } catch (PDOException $e) {
        error_log('Fehler beim Erstellen der Rezension: ' . $e->getMessage());
        return false;
    }
}

/**
 * Status einer Rezension aktualisieren
 */
function updateReviewStatus($id, $status) {
    $db = getDbConnection();
    if (!$db) return false;

    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        return false;
    }

    try {
        $stmt = $db->prepare('
            UPDATE reviews
            SET status = :status, updated_at = :updated_at
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Fehler beim Aktualisieren des Rezensionsstatus: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rezension löschen
 */
function deleteReview($id) {
    $db = getDbConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen der Rezension: ' . $e->getMessage());
        return false;
    }
}

/**
 * Anzahl der genehmigten Rezensionen
 */
function getApprovedReviewCount() {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->query('SELECT COUNT(*) as count FROM reviews WHERE status = "approved"');
        $result = $stmt->fetch();
        return (int)$result['count'];
    } catch (PDOException $e) {
        error_log('Fehler beim Zählen der Rezensionen: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Alle Rezensionen für das Admin-Dashboard laden (optional nach Status gefiltert)
 */
function getAllReviews($status = null) {
    $db = getDbConnection();
    if (!$db) return [];

    try {
        $sql = '
            SELECT id, name, rating, title, text, status, created_at, updated_at
            FROM reviews
        ';
        $params = [];
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= ' WHERE status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Rezensionen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Durchschnittsbewertung der genehmigten Rezensionen
 */
function getAverageRating() {
    $db = getDbConnection();
    if (!$db) return 0;

    try {
        $stmt = $db->query('SELECT AVG(rating) as avg FROM reviews WHERE status = "approved"');
        $result = $stmt->fetch();
        return $result['avg'] ? round((float)$result['avg'], 1) : 0;
    } catch (PDOException $e) {
        error_log('Fehler beim Berechnen der Durchschnittsbewertung: ' . $e->getMessage());
        return 0;
    }
}
