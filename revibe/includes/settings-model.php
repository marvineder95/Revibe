<?php
/**
 * Globale Einstellungen (Key-Value Store)
 */

require_once __DIR__ . '/database.php';

const DEFAULT_SETTINGS = [
    'tax_rate' => '20',
    'transport_price_per_km' => '0.85',
    'transport_worker_hourly_rate' => '45',
    'transport_worker_count' => '2',
    'transport_setup_fee' => '120',
    'contract_fee_enabled' => '1',
    'contract_fee_percent' => '1.00',
    'google_maps_api_key' => '',
    'warehouse_address' => 'Oberstdorfer Straße 5, 2201 Sering, Österreich',
    'company_bank_name' => '',
    'company_iban' => '',
    'company_bic' => ''
];

/**
 * Setting abrufen
 */
function getSetting($key, $default = null) {
    $db = getDbConnection();
    if (!$db) return $default ?? (DEFAULT_SETTINGS[$key] ?? null);
    
    try {
        $stmt = $db->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();
        return $result !== false ? $result['value'] : ($default ?? (DEFAULT_SETTINGS[$key] ?? null));
    } catch (PDOException $e) {
        return $default ?? (DEFAULT_SETTINGS[$key] ?? null);
    }
}

/**
 * Mehrere Settings auf einmal abrufen
 */
function getSettings(array $keys) {
    $values = [];
    foreach ($keys as $key) {
        $values[$key] = getSetting($key);
    }
    return $values;
}

/**
 * Setting speichern
 */
function setSetting($key, $value) {
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare('INSERT INTO settings (key, value) VALUES (:key, :value) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        return $stmt->execute([':key' => $key, ':value' => (string)$value]);
    } catch (PDOException $e) {
        error_log('Settings-Fehler: ' . $e->getMessage());
        return false;
    }
}

/**
 * Alle Settings laden
 */
function getAllSettings() {
    $db = getDbConnection();
    $settings = DEFAULT_SETTINGS;
    
    if (!$db) return $settings;
    
    try {
        $stmt = $db->query('SELECT key, value FROM settings');
        $dbSettings = $stmt->fetchAll();
        foreach ($dbSettings as $row) {
            $settings[$row['key']] = $row['value'];
        }
    } catch (PDOException $e) {
        // ignore
    }
    
    return $settings;
}
