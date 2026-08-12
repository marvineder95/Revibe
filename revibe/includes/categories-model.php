<?php
/**
 * Kategorien-Datenmodell
 */

require_once __DIR__ . '/database.php';

/**
 * Alle Kategorien laden
 */
function getAllCategories($activeOnly = false) {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $sql = 'SELECT * FROM categories';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Kategorien: ' . $e->getMessage());
        return [];
    }
}

/**
 * Einzelne Kategorie laden
 */
function getCategoryById($id) {
    $db = getDbConnection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Kategorie: ' . $e->getMessage());
        return null;
    }
}

/**
 * Kategorie speichern
 */
function saveCategory($data, $id = null) {
    $db = getDbConnection();
    if (!$db) return false;
    
    $categoryId = $id ?: 'cat_' . bin2hex(random_bytes(4));
    
    $data = [
        ':id' => $categoryId,
        ':name' => sanitizeInput($data['name'] ?? ''),
        ':name_en' => sanitizeInput($data['name_en'] ?? ''),
        ':description' => sanitizeInput($data['description'] ?? ''),
        ':description_en' => sanitizeInput($data['description_en'] ?? ''),
        ':color' => sanitizeInput($data['color'] ?? '#0066B1'),
        ':active' => !empty($data['active']) ? 1 : 0,
        ':sort_order' => !empty($data['sort_order']) ? (int)$data['sort_order'] : 0,
        ':updated_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $existing = getCategoryById($categoryId);
        
        if ($existing) {
            $stmt = $db->prepare('
                UPDATE categories SET
                    name = :name, name_en = :name_en, description = :description,
                    description_en = :description_en, color = :color, active = :active,
                    sort_order = :sort_order, updated_at = :updated_at
                WHERE id = :id
            ');
        } else {
            $data[':created_at'] = date('Y-m-d H:i:s');
            $stmt = $db->prepare('
                INSERT INTO categories (id, name, name_en, description, description_en, color, active, sort_order, created_at, updated_at)
                VALUES (:id, :name, :name_en, :description, :description_en, :color, :active, :sort_order, :created_at, :updated_at)
            ');
        }
        
        $stmt->execute($data);
        return $categoryId;
    } catch (PDOException $e) {
        error_log('Fehler beim Speichern der Kategorie: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kategorie löschen
 */
function deleteCategory($id) {
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        // Prüfen ob Jukeboxen zugeordnet sind
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM jukeboxes WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return false; // Nicht löschen wenn noch verwendet
        }
        
        $stmt = $db->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen der Kategorie: ' . $e->getMessage());
        return false;
    }
}

/**
 * Lokalisierten Kategorie-Namen abrufen
 */
function getCategoryName($category, $lang = null) {
    if (!$category) return '';
    if ($lang === null) $lang = getCurrentLanguage();
    return !empty($category['name_' . $lang]) ? $category['name_' . $lang] : $category['name'];
}
