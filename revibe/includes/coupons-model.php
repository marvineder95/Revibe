<?php
/**
 * Coupons-Datenmodell
 */

require_once __DIR__ . '/database.php';

/**
 * Alle Coupons laden
 */
function getAllCoupons($activeOnly = false) {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $sql = 'SELECT * FROM coupons';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY code ASC';
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Coupons: ' . $e->getMessage());
        return [];
    }
}

/**
 * Coupon anhand des Codes laden
 */
function getCouponByCode($code) {
    $db = getDbConnection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare('SELECT * FROM coupons WHERE code = :code COLLATE NOCASE');
        $stmt->execute([':code' => strtoupper(trim($code))]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden des Coupons: ' . $e->getMessage());
        return null;
    }
}

/**
 * Coupon anhand der ID laden
 */
function getCouponById($id) {
    $db = getDbConnection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare('SELECT * FROM coupons WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden des Coupons: ' . $e->getMessage());
        return null;
    }
}

/**
 * Coupon validieren
 * Rückgabe: Array mit status (valid|invalid|expired|inactive|min_order) und coupon-Daten
 */
function validateCoupon($code, $orderValue = 0) {
    $coupon = getCouponByCode($code);
    
    if (!$coupon) {
        return ['status' => 'invalid', 'coupon' => null];
    }
    
    if (empty($coupon['active'])) {
        return ['status' => 'inactive', 'coupon' => $coupon];
    }
    
    $today = date('Y-m-d');
    if (!empty($coupon['valid_from']) && $coupon['valid_from'] > $today) {
        return ['status' => 'expired', 'coupon' => $coupon];
    }
    if (!empty($coupon['valid_until']) && $coupon['valid_until'] < $today) {
        return ['status' => 'expired', 'coupon' => $coupon];
    }
    
    if ((float)$coupon['min_order_value'] > 0 && $orderValue < (float)$coupon['min_order_value']) {
        return ['status' => 'min_order', 'coupon' => $coupon];
    }
    
    return ['status' => 'valid', 'coupon' => $coupon];
}

/**
 * Coupon speichern
 */
function saveCoupon($data, $id = null) {
    $db = getDbConnection();
    if (!$db) return false;
    
    $couponId = $id ?: 'cp_' . bin2hex(random_bytes(4));
    $code = strtoupper(trim($data['code'] ?? ''));
    
    if (empty($code)) return false;
    
    $values = [
        ':id' => $couponId,
        ':code' => $code,
        ':description' => sanitizeInput($data['description'] ?? ''),
        ':discount_percent' => max(0, min(100, (float)($data['discount_percent'] ?? 0))),
        ':valid_from' => !empty($data['valid_from']) ? $data['valid_from'] : null,
        ':valid_until' => !empty($data['valid_until']) ? $data['valid_until'] : null,
        ':active' => !empty($data['active']) ? 1 : 0,
        ':min_order_value' => max(0, (float)($data['min_order_value'] ?? 0)),
        ':reusable' => isset($data['reusable']) ? 1 : 0,
        ':combinable' => isset($data['combinable']) ? 1 : 0,
        ':updated_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $existing = getCouponById($couponId);
        
        if ($existing) {
            $stmt = $db->prepare('
                UPDATE coupons SET
                    code = :code, description = :description, discount_percent = :discount_percent,
                    valid_from = :valid_from, valid_until = :valid_until, active = :active,
                    min_order_value = :min_order_value, reusable = :reusable, combinable = :combinable,
                    updated_at = :updated_at
                WHERE id = :id
            ');
        } else {
            $values[':created_at'] = date('Y-m-d H:i:s');
            $stmt = $db->prepare('
                INSERT INTO coupons (id, code, description, discount_percent, valid_from, valid_until, active, min_order_value, reusable, combinable, created_at, updated_at)
                VALUES (:id, :code, :description, :discount_percent, :valid_from, :valid_until, :active, :min_order_value, :reusable, :combinable, :created_at, :updated_at)
            ');
        }
        
        $stmt->execute($values);
        return $couponId;
    } catch (PDOException $e) {
        error_log('Fehler beim Speichern des Coupons: ' . $e->getMessage());
        return false;
    }
}

/**
 * Coupon löschen
 */
function deleteCoupon($id) {
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare('DELETE FROM coupons WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen des Coupons: ' . $e->getMessage());
        return false;
    }
}
