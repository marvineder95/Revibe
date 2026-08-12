<?php
/**
 * AJAX-Endpunkte
 */
require_once '../config/config.php';

// Sicherheits-Header setzen
setSecurityHeaders();

$action = $_GET['action'] ?? '';

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');

/**
 * Prüft, ob die Anfrage vom eigenen Host kommt.
 */
function validateAjaxOrigin($referer, $allowedOrigin) {
    return strpos($referer, $allowedOrigin) === 0;
}

/**
 * Beendet die Anfrage mit einem JSON-Fehler.
 */
function ajaxError($httpCode, $message = 'Forbidden') {
    header('HTTP/1.1 ' . $httpCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

/**
 * Liest einen POST-Wert sicher aus.
 */
function ajaxPost($key, $default = '') {
    return sanitizeInput($_POST[$key] ?? $default);
}

// Zustandsändernde Aktionen erfordern POST
$mutationActions = ['cartAdd', 'cartRemove', 'updateCart', 'updateCartContact', 'setCartDates'];
if (in_array($action, $mutationActions, true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ajaxError(405, 'Method Not Allowed');
}

// Verfügbarkeitsabfrage ebenfalls per POST (liest aber nur)
if ($action === 'checkAvailability' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ajaxError(405, 'Method Not Allowed');
}

switch ($action) {
    case 'getInquiryItems':
        // Legacy-Endpunkt für Sidebar
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }

        $ids = $_GET['ids'] ?? '';
        $idArray = explode(',', $ids);

        $items = [];
        foreach ($idArray as $id) {
            $id = trim($id);
            if (empty($id)) continue;

            $jukebox = getJukeboxById($id);
            if ($jukebox) {
                $items[] = [
                    'id' => $jukebox['id'],
                    'name' => getLocalizedValue($jukebox, 'name'),
                    'price' => formatPrice($jukebox['price_day']),
                    'image' => getJukeboxImageUrl($jukebox['main_image'])
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'items' => $items], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'getCartItems':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $items = [];
        foreach (getCart()['items'] as $id) {
            $jb = getJukeboxById($id);
            if ($jb) {
                $items[] = [
                    'id' => $jb['id'],
                    'name' => getLocalizedValue($jb, 'name'),
                    'price' => formatPrice($jb['price_day']),
                    'image' => getJukeboxImageUrl($jb['main_image'])
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'items' => $items], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'cartAdd':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $id = ajaxPost('id');
        $dateStart = ajaxPost('date_start');
        $dateEnd = ajaxPost('date_end');

        $jb = $id ? getJukeboxById($id) : null;
        if (!$jb) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Jukebox not found'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            break;
        }

        // Datum im Warenkorb setzen, falls mitgeliefert
        if (!empty($dateStart) && !empty($dateEnd)) {
            $cart = getCart();
            $cart['date_start'] = $dateStart;
            $cart['date_end'] = $dateEnd;
            $cart['duration_days'] = calculateRentalDays($dateStart, $dateEnd);
            saveCart($cart);
        }

        $cart = getCart();
        if (empty($cart['date_start']) || empty($cart['date_end'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'date_required',
                'message' => __('cart_date_required')
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            break;
        }

        // Verfügbarkeit prüfen
        if (!isJukeboxAvailable($id, $cart['date_start'], $cart['date_end'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'not_available',
                'message' => __('cart_item_not_available')
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            break;
        }

        cartAddItem($id);
        // Legacy-Cookie synchronisieren
        addToInquiryList($id);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => getCartItemCount()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'cartRemove':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $id = ajaxPost('id');
        if ($id) {
            cartRemoveItem($id);
            removeFromInquiryList($id);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => getCartItemCount()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'cartGet':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => getCartItemCount(), 'items' => getCart()['items']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'checkAvailability':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $jukeboxId = ajaxPost('jukebox_id');
        $dateStart = ajaxPost('date_start');
        $dateEnd = ajaxPost('date_end');

        if (empty($jukeboxId) || empty($dateStart) || empty($dateEnd)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'missing_params'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            break;
        }

        $available = isJukeboxAvailable($jukeboxId, $dateStart, $dateEnd);
        $conflicts = $available ? [] : getConflictingRentals($jukeboxId, $dateStart, $dateEnd);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'available' => $available,
            'conflicts' => $conflicts
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'setCartDates':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $dateStart = ajaxPost('date_start');
        $dateEnd = ajaxPost('date_end');

        if (empty($dateStart) || empty($dateEnd)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'missing_params'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            break;
        }

        $cart = getCart();
        cartUpdateRentalData($dateStart, $dateEnd, $cart['event_address'] ?? '');

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => getCartItemCount()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'updateCart':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $dateStart = ajaxPost('date_start');
        $dateEnd = ajaxPost('date_end');
        $address = ajaxPost('address');
        $coupon = ajaxPost('coupon');

        cartUpdateRentalData($dateStart, $dateEnd, $address);
        cartSetCoupon($coupon);

        // Verfügbarkeit prüfen
        $availability = checkCartAvailability(getCart());

        // Transport berechnen
        if (!empty($address)) {
            $transport = calculateTransportCosts($address);
            cartSetTransportData($transport['distance_km'], $transport['duration_min'], $transport['error']);
        }

        // Pricing HTML generieren
        ob_start();
        include PARTIALS_PATH . 'pricing-summary.php';
        $pricingHtml = ob_get_clean();

        // Coupon-Status ermitteln
        $cart = getCart();
        $couponMessage = '';
        $couponValid = false;
        if (!empty($coupon)) {
            $cartItems = getCartItems();
            $pricingCheck = calculatePricing($cartItems, $cart['duration_days'], $coupon, 0);
            switch ($pricingCheck['coupon_status']) {
                case 'valid':
                    $couponMessage = __('request_coupon_valid');
                    $couponValid = true;
                    break;
                case 'invalid':
                    $couponMessage = __('request_coupon_invalid');
                    break;
                case 'expired':
                    $couponMessage = __('request_coupon_expired');
                    break;
                case 'inactive':
                    $couponMessage = __('request_coupon_inactive');
                    break;
                case 'min_order':
                    $couponMessage = __('request_coupon_min_order');
                    break;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'pricingHtml' => $pricingHtml,
            'couponMessage' => $couponMessage,
            'couponValid' => $couponValid,
            'available' => $availability['available'],
            'availabilityMessage' => $availability['available'] ? '' : __('cart_item_not_available')
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    case 'updateCartContact':
        if (!validateAjaxOrigin($referer, $allowedOrigin)) {
            ajaxError(403, 'Invalid origin');
        }
        $dateStart = ajaxPost('date_start');
        $dateEnd = ajaxPost('date_end');
        $street = ajaxPost('street');
        $housenumber = ajaxPost('housenumber');
        $zip = ajaxPost('zip');
        $city = ajaxPost('city');
        $country = ajaxPost('country');
        $coupon = ajaxPost('coupon');

        $address = trim($street . ' ' . $housenumber);
        if ($zip || $city) {
            $address .= "\n" . trim($zip . ' ' . $city);
        }
        if ($country) {
            $address .= ($address ? ', ' : '') . $country;
        }

        cartUpdateRentalData($dateStart, $dateEnd, $address);
        cartSetCoupon($coupon);

        // Verfügbarkeit prüfen
        $availability = checkCartAvailability(getCart());

        if (!empty($address)) {
            $transport = calculateTransportCosts($address);
            cartSetTransportData($transport['distance_km'], $transport['duration_min'], $transport['error']);
        }

        ob_start();
        include PARTIALS_PATH . 'pricing-compact.php';
        $pricingHtml = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'pricingHtml' => $pricingHtml,
            'available' => $availability['available'],
            'availabilityMessage' => $availability['available'] ? '' : __('cart_item_not_available')
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        break;

    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => 'Unknown action'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
