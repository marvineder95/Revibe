<?php
/**
 * Rabattregeln-Datenmodell
 */

require_once __DIR__ . '/database.php';

/**
 * Alle Rabattregeln laden (optional gefiltert nach Typ)
 */
function getAllDiscountRules($type = null, $activeOnly = true) {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $sql = 'SELECT * FROM discount_rules WHERE 1=1';
        $params = [];
        
        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params[':type'] = $type;
        }
        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY threshold ASC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rabattregeln: ' . $e->getMessage());
        return [];
    }
}

/**
 * Passende Rabattregel für einen bestimmten Wert ermitteln
 * Gibt den höchsten passenden Rabatt zurück
 */
function getApplicableDiscount($type, $value) {
    $rules = getAllDiscountRules($type, true);
    $bestDiscount = 0;
    
    foreach ($rules as $rule) {
        if ((int)$value >= (int)$rule['threshold'] && (float)$rule['discount_percent'] > $bestDiscount) {
            $bestDiscount = (float)$rule['discount_percent'];
        }
    }
    
    return $bestDiscount;
}

/**
 * Rabattregel anhand ID laden
 */
function getDiscountRuleById($id) {
    $db = getDbConnection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare('SELECT * FROM discount_rules WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Rabattregel: ' . $e->getMessage());
        return null;
    }
}

/**
 * Rabattregel speichern
 */
function saveDiscountRule($data, $id = null) {
    $db = getDbConnection();
    if (!$db) return false;
    
    $ruleId = $id ?: 'disc_' . bin2hex(random_bytes(4));
    $type = in_array($data['type'] ?? '', ['duration', 'quantity']) ? $data['type'] : 'duration';
    
    $values = [
        ':id' => $ruleId,
        ':type' => $type,
        ':threshold' => max(1, (int)($data['threshold'] ?? 1)),
        ':discount_percent' => max(0, min(100, (float)($data['discount_percent'] ?? 0))),
        ':active' => !empty($data['active']) ? 1 : 0,
        ':sort_order' => (int)($data['sort_order'] ?? 0),
        ':updated_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $existing = getDiscountRuleById($ruleId);
        
        if ($existing) {
            $stmt = $db->prepare('
                UPDATE discount_rules SET
                    type = :type, threshold = :threshold, discount_percent = :discount_percent,
                    active = :active, sort_order = :sort_order, updated_at = :updated_at
                WHERE id = :id
            ');
        } else {
            $values[':created_at'] = date('Y-m-d H:i:s');
            $stmt = $db->prepare('
                INSERT INTO discount_rules (id, type, threshold, discount_percent, active, sort_order, created_at, updated_at)
                VALUES (:id, :type, :threshold, :discount_percent, :active, :sort_order, :created_at, :updated_at)
            ');
        }
        
        $stmt->execute($values);
        return $ruleId;
    } catch (PDOException $e) {
        error_log('Fehler beim Speichern der Rabattregel: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rabattregel löschen
 */
function deleteDiscountRule($id) {
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare('DELETE FROM discount_rules WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen der Rabattregel: ' . $e->getMessage());
        return false;
    }
}
