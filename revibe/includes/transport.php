<?php
/**
 * Transportberechnung via Google Maps Distance Matrix API
 */

require_once __DIR__ . '/settings-model.php';

const WAREHOUSE_ADDRESS_DEFAULT = 'Oberstdorfer Straße 5, 2201 Sering, Österreich';

/**
 * Transportkosten berechnen
 * Rückgabe: Array mit costs, distance_km, duration_min, error
 */
function calculateTransportCosts($eventAddress) {
    $settings = getAllSettings();
    $warehouse = !empty($settings['warehouse_address']) ? $settings['warehouse_address'] : WAREHOUSE_ADDRESS_DEFAULT;
    
    if (empty($eventAddress)) {
        return [
            'costs' => 0,
            'distance_km' => 0,
            'duration_min' => 0,
            'error' => ''
        ];
    }
    
    // Session-Cache prüfen (Kosten sparen)
    $cacheKey = md5($warehouse . '|' . strtolower(trim($eventAddress)));
    $cacheTtl = 600; // 10 Minuten
    if (!empty($_SESSION['transport_cache'][$cacheKey])) {
        $cached = $_SESSION['transport_cache'][$cacheKey];
        if (($cached['time'] ?? 0) > (time() - $cacheTtl)) {
            return $cached['data'];
        }
    }
    
    $apiKey = trim($settings['google_maps_api_key'] ?? '');

    // Fallback wenn kein API-Key vorhanden
    if (empty($apiKey)) {
        return [
            'costs' => 0,
            'distance_km' => 0,
            'duration_min' => 0,
            'error' => 'API-Key fehlt'
        ];
    }
    
    // Google Distance Matrix API aufrufen
    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $params = [
        'origins' => $warehouse,
        'destinations' => $eventAddress,
        'mode' => 'driving',
        'units' => 'metric',
        'language' => getCurrentLanguage() === 'de' ? 'de' : 'en',
        'key' => $apiKey
    ];
    
    $url .= '?' . http_build_query($params);

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
            'error' => 'Route nicht berechnbar: ' . ($element['status'] ?? 'Unbekannt')
        ];
    }
    
    $distanceMeters = (int)($element['distance']['value'] ?? 0);
    $durationSeconds = (int)($element['duration']['value'] ?? 0);
    
    $distanceKm = round($distanceMeters / 1000, 1);
    $durationMin = ceil($durationSeconds / 60);
    
    // Vervielfachen für 2 Hin- und Rückfahrten (Lieferung + Abholung)
    $totalDistanceKm = $distanceKm * 4;
    $totalDurationMin = $durationMin * 4;
    
    $costs = computeTransportPrice($totalDistanceKm, $totalDurationMin);
    
    $result = [
        'costs' => $costs,
        'distance_km' => $totalDistanceKm,
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
