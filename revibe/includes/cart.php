<?php
/**
 * Anfragekorb (Session-basiert)
 */

const CART_SESSION_KEY = 'jukebox_cart';

/**
 * Warenkorb aus der Session laden
 */
function getCart() {
    if (!isset($_SESSION[CART_SESSION_KEY]) || !is_array($_SESSION[CART_SESSION_KEY])) {
        $_SESSION[CART_SESSION_KEY] = [
            'items' => [], // Array von Jukebox-IDs
            'date_start' => '',
            'date_end' => '',
            'duration_days' => 0,
            'event_address' => '',
            'event_street' => '',
            'event_housenumber' => '',
            'event_zip' => '',
            'event_city' => '',
            'event_country' => '',
            'coupon_code' => '',
            'transport_calculated' => false,
            'transport_distance_km' => 0,
            'transport_duration_min' => 0,
            'transport_error' => ''
        ];
    }
    return $_SESSION[CART_SESSION_KEY];
}

/**
 * Warenkorb speichern
 */
function saveCart($cart) {
    $_SESSION[CART_SESSION_KEY] = $cart;
}

/**
 * Jukebox zum Warenkorb hinzufügen
 */
function cartAddItem($jukeboxId) {
    $cart = getCart();
    if (!in_array($jukeboxId, $cart['items'], true)) {
        $cart['items'][] = $jukeboxId;
        saveCart($cart);
    }
}

/**
 * Jukebox aus dem Warenkorb entfernen
 */
function cartRemoveItem($jukeboxId) {
    $cart = getCart();
    $cart['items'] = array_values(array_diff($cart['items'], [$jukeboxId]));
    saveCart($cart);
}

/**
 * Warenkorb leeren
 */
function cartClear() {
    saveCart([
        'items' => [],
        'date_start' => '',
        'date_end' => '',
        'duration_days' => 0,
        'event_address' => '',
        'event_street' => '',
        'event_housenumber' => '',
        'event_zip' => '',
        'event_city' => '',
        'event_country' => '',
        'coupon_code' => '',
        'transport_calculated' => false,
        'transport_distance_km' => 0,
        'transport_duration_min' => 0,
        'transport_error' => ''
    ]);
}

/**
 * Mietdaten im Warenkorb aktualisieren
 */
function cartUpdateRentalData($dateStart, $dateEnd, $eventAddress) {
    $cart = getCart();
    $cart['date_start'] = $dateStart;
    $cart['date_end'] = $dateEnd;
    $cart['event_address'] = trim($eventAddress);

    // Adresse in Einzelteile parsen, damit Formularfelder vorausgefüllt werden können
    $components = parseEventAddress($cart['event_address']);
    $cart['event_street'] = $components['street'];
    $cart['event_housenumber'] = $components['housenumber'];
    $cart['event_zip'] = $components['zip'];
    $cart['event_city'] = $components['city'];
    $cart['event_country'] = $components['country'];

    // Dauer in Tagen berechnen
    $cart['duration_days'] = calculateRentalDays($dateStart, $dateEnd);

    // Bei Adressänderung Transport zurücksetzen
    saveCart($cart);
}

/**
 * Adressstring in Straße, Nr., PLZ, Ort und Land aufteilen
 */
function parseEventAddress($address) {
    $components = [
        'street' => '',
        'housenumber' => '',
        'zip' => '',
        'city' => '',
        'country' => ''
    ];

    $address = trim($address);
    if (empty($address)) {
        return $components;
    }

    // Zeilenumbrüche in Kommas umwandeln
    $line = preg_replace('/[\r\n]+/', ', ', $address);
    $parts = array_map('trim', explode(',', $line));

    // Land: letztes Segment ohne Ziffern (z. B. "Österreich", "Deutschland")
    if (count($parts) > 1) {
        $last = $parts[count($parts) - 1];
        if (!preg_match('/\d/', $last)) {
            $components['country'] = $last;
            array_pop($parts);
            $line = implode(', ', $parts);
        }
    }

    $left = '';
    $right = '';

    if (count($parts) === 2) {
        $left = $parts[0];
        $right = $parts[1];
    } else {
        // Einzeleingabe: versuche, Hausnummer, PLZ und Ort zu erkennen
        if (preg_match('/^(.*?)\s+(\d+\S*)\s+(\d{1,6})\s+(.+)$/u', $line, $m)) {
            $left = trim($m[1]) . ' ' . $m[2];
            $right = $m[3] . ' ' . trim($m[4]);
        } elseif (preg_match('/^(.*?)\s*,?\s*(\d{1,6})\s+(.+)$/u', $line, $m)) {
            $left = trim($m[1]);
            $right = $m[2] . ' ' . trim($m[3]);
        } else {
            $left = $line;
        }
    }

    // Straße und Hausnummer trennen (Nummer am Ende)
    if (preg_match('/^(.*?)\s+(\d+\S*)$/u', trim($left), $m)) {
        $components['street'] = trim($m[1]);
        $components['housenumber'] = $m[2];
    } else {
        $components['street'] = trim($left);
    }

    // PLZ und Ort trennen
    $right = trim($right);
    if (!empty($right)) {
        if (preg_match('/^(\d{1,6})\s+(.+)$/u', $right, $m)) {
            $components['zip'] = $m[1];
            $components['city'] = trim($m[2]);
        } else {
            $components['city'] = $right;
        }
    }

    return $components;
}

/**
 * Coupon im Warenkorb setzen
 */
function cartSetCoupon($code) {
    $cart = getCart();
    $cart['coupon_code'] = strtoupper(trim($code));
    saveCart($cart);
}

/**
 * Transportdaten im Warenkorb speichern
 */
function cartSetTransportData($distanceKm, $durationMin, $error = '') {
    $cart = getCart();
    $cart['transport_calculated'] = empty($error);
    $cart['transport_distance_km'] = (float)$distanceKm;
    $cart['transport_duration_min'] = (int)$durationMin;
    $cart['transport_error'] = $error;
    saveCart($cart);
}

/**
 * Miettage aus zwei Datumsstrings berechnen (DD.MM.YYYY)
 */
function calculateRentalDays($dateStart, $dateEnd) {
    if (empty($dateStart)) return 0;
    
    $start = DateTime::createFromFormat('d.m.Y', $dateStart);
    if (!$start) return 0;
    
    if (empty($dateEnd) || $dateEnd === $dateStart) {
        return 1;
    }
    
    $end = DateTime::createFromFormat('d.m.Y', $dateEnd);
    if (!$end) return 1;
    
    $diff = $start->diff($end);
    return max(1, (int)$diff->days + 1);
}

/**
 * Aktuelle Warenkorb-Jukeboxen mit Details laden
 */
function getCartItems() {
    $cart = getCart();
    $items = [];
    foreach ($cart['items'] as $id) {
        $jb = getJukeboxById($id);
        if ($jb) {
            $jb['category'] = getCategoryById($jb['category_id'] ?? '');
            $items[] = $jb;
        }
    }
    return $items;
}

/**
 * Anzahl der Items im Warenkorb
 */
function getCartItemCount() {
    return count(getCart()['items']);
}

/**
 * Ist eine Jukebox im Warenkorb?
 */
function cartHasItem($jukeboxId) {
    return in_array($jukeboxId, getCart()['items'], true);
}
