<?php
/**
 * Transportberechnung via Google Maps Distance Matrix API
 */

require_once __DIR__ . '/settings-model.php';

if (!defined('WAREHOUSE_ADDRESS_DEFAULT')) {
    define('WAREHOUSE_ADDRESS_DEFAULT', 'Oberstdorfer Straße 5, 2201 Seyring, Österreich');
}

/**
 * Google Maps API-Key ermitteln
 * Priorität:
 * 1. Umgebungsvariable GOOGLE_MAPS_API_KEY
 * 2. Geschützte Datei DATA_PATH/google-maps-api-key.php (return 'key')
 * 3. Datenbank-Einstellung google_maps_api_key (veraltet)
 */
function getGoogleMapsApiKey($settings = null) {
    // 1. Umgebungsvariable
    $envKey = getenv('GOOGLE_MAPS_API_KEY');
    if (!empty($envKey)) {
        return trim($envKey);
    }

    // 2. Geschützte Datei (für Shared-Hosting ohne verfügbare Umgebungsvariable)
    if (defined('DATA_PATH')) {
        $keyFile = DATA_PATH . 'google-maps-api-key.php';
        if (file_exists($keyFile)) {
            $fileKey = require $keyFile;
            if (!empty($fileKey) && is_string($fileKey)) {
                return trim($fileKey);
            }
        }
    }

    // 3. Datenbank-Einstellung (veraltet)
    if ($settings === null) {
        $settings = getAllSettings();
    }
    return trim($settings['google_maps_api_key'] ?? '');
}

/**
 * Transportkosten berechnen
 * Rückgabe: Array mit costs, distance_km, duration_min, error
 */
function calculateTransportCosts($eventAddress, $jukeboxes = []) {
    if (empty($eventAddress)) {
        return [
            'costs' => 0,
            'distance_km' => 0,
            'duration_min' => 0,
            'error' => ''
        ];
    }

    // Eindeutige Lageradressen aus den Jukeboxen ermitteln
    $warehouses = [];
    if (!empty($jukeboxes) && is_array($jukeboxes)) {
        foreach ($jukeboxes as $jb) {
            $warehouses[] = !empty($jb['warehouse_address']) ? $jb['warehouse_address'] : WAREHOUSE_ADDRESS_DEFAULT;
        }
    }
    if (empty($warehouses)) {
        $warehouses = [WAREHOUSE_ADDRESS_DEFAULT];
    }
    $warehouses = array_values(array_unique($warehouses));

    // Session-Cache prüfen (Kosten sparen)
    $cacheKey = md5(implode('|', $warehouses) . '|' . strtolower(trim($eventAddress)));
    $cacheTtl = 600; // 10 Minuten
    if (!empty($_SESSION['transport_cache'][$cacheKey])) {
        $cached = $_SESSION['transport_cache'][$cacheKey];
        if (($cached['time'] ?? 0) > (time() - $cacheTtl)) {
            return $cached['data'];
        }
    }

    $apiKey = getGoogleMapsApiKey();

    // Fallback wenn kein API-Key vorhanden
    if (empty($apiKey)) {
        return [
            'costs' => 0,
            'distance_km' => 0,
            'duration_min' => 0,
            'error' => 'API-Key fehlt'
        ];
    }

    $language = getCurrentLanguage() === 'de' ? 'de' : 'en';
    $totalDistanceKm = 0;
    $totalDurationMin = 0;

    foreach ($warehouses as $warehouse) {
        $params = [
            'origins' => $warehouse,
            'destinations' => $eventAddress,
            'mode' => 'driving',
            'units' => 'metric',
            'language' => $language,
            'key' => $apiKey
        ];

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query($params);

        $response = fetchGoogleApiUrl($url);
        if ($response === false) {
            error_log('Google Distance Matrix API: Verbindung fehlgeschlagen für URL ' . preg_replace('/key=[^&]+/', 'key=***', $url));
            return [
                'costs' => 0,
                'distance_km' => 0,
                'duration_min' => 0,
                'error' => 'API-Verbindung fehlgeschlagen'
            ];
        }

        $data = json_decode($response, true);
        if (empty($data) || $data['status'] !== 'OK') {
            $apiStatus = $data['status'] ?? 'Unbekannt';
            error_log('Google Distance Matrix API-Fehler: ' . $apiStatus . ' – ' . substr($response, 0, 500));
            return [
                'costs' => 0,
                'distance_km' => 0,
                'duration_min' => 0,
                'error' => 'API-Fehler: ' . $apiStatus
            ];
        }

        $element = $data['rows'][0]['elements'][0] ?? null;
        if (!$element || $element['status'] !== 'OK') {
            return [
                'costs' => 0,
                'distance_km' => 0,
                'duration_min' => 0,
                'error' => 'Route nicht berechenbar: ' . ($element['status'] ?? 'Unbekannt')
            ];
        }

        $distanceMeters = (int)($element['distance']['value'] ?? 0);
        $durationSeconds = (int)($element['duration']['value'] ?? 0);

        $distanceKm = round($distanceMeters / 1000, 1);
        $durationMin = ceil($durationSeconds / 60);

        // Vervielfachen für 2 Hin- und Rückfahrten (Lieferung + Abholung)
        $totalDistanceKm += $distanceKm * 4;
        $totalDurationMin += $durationMin * 4;
    }

    $costs = computeTransportPrice($totalDistanceKm, $totalDurationMin);

    $result = [
        'costs' => $costs,
        'distance_km' => round($totalDistanceKm, 1),
        'duration_min' => $totalDurationMin,
        'error' => ''
    ];

    $_SESSION['transport_cache'][$cacheKey] = ['time' => time(), 'data' => $result];
    return $result;
}

/**
 * Intern: Preis aus km und Minuten berechnen
 */
function computeTransportPrice($totalDistanceKm, $totalDurationMin) {
    $settings = getAllSettings();
    
    $pricePerKm = (float)($settings['transport_price_per_km'] ?? DEFAULT_SETTINGS['transport_price_per_km']);
    $hourlyRate = (float)($settings['transport_worker_hourly_rate'] ?? DEFAULT_SETTINGS['transport_worker_hourly_rate']);
    $workerCount = (int)($settings['transport_worker_count'] ?? DEFAULT_SETTINGS['transport_worker_count']);
    $setupFee = (float)($settings['transport_setup_fee'] ?? DEFAULT_SETTINGS['transport_setup_fee']);
    
    $kmCost = $totalDistanceKm * $pricePerKm;
    $timeCost = ($totalDurationMin / 60) * $hourlyRate * $workerCount;

    return round($kmCost + $timeCost + $setupFee, 2);
}

/**
 * Google API URL abrufen (cURL bevorzugt, Fallback auf file_get_contents)
 */
/**
 * Testet die Google Maps API-Verbindung mit einem kurzen Request
 */
function testGoogleMapsApiConnection($apiKey, $warehouse) {
    $apiKey = trim($apiKey);
    if (empty($apiKey)) {
        $apiKey = getGoogleMapsApiKey();
    }
    if (empty($apiKey)) {
        return ['ok' => false, 'status' => 'API-Key fehlt'];
    }

    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $url .= '?origins=' . urlencode($warehouse);
    $url .= '&destinations=' . urlencode($warehouse);
    $url .= '&mode=driving&units=metric&key=' . urlencode($apiKey);

    $response = fetchGoogleApiUrl($url);
    if ($response === false) {
        return ['ok' => false, 'status' => 'Verbindung fehlgeschlagen'];
    }

    $data = json_decode($response, true);
    if (empty($data) || empty($data['status'])) {
        return ['ok' => false, 'status' => 'Ungültige API-Antwort'];
    }

    if ($data['status'] === 'OK') {
        return ['ok' => true, 'status' => 'OK'];
    }

    return ['ok' => false, 'status' => $data['status']];
}

/**
 * Google API URL abrufen (cURL bevorzugt, Fallback auf file_get_contents)
 */
function fetchGoogleApiUrl($url) {
    if (extension_loaded('curl')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_REFERER, defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://revibe.at');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Revibe/1.0');

        $response = curl_exec($ch);
        if ($response === false) {
            error_log('Google Distance Matrix API cURL-Fehler: ' . curl_error($ch));
        }
        return $response;
    }

    if (ini_get('allow_url_fopen')) {
        return @file_get_contents($url);
    }

    error_log('Google Distance Matrix API: Weder cURL noch allow_url_fopen verfügbar.');
    return false;
}
