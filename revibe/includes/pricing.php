<?php
/**
 * Zentrale Preisberechnung
 */

require_once __DIR__ . '/settings-model.php';
require_once __DIR__ . '/discounts-model.php';
require_once __DIR__ . '/coupons-model.php';

/**
 * Komplette Preisberechnung für einen Warenkorb
 * 
 * @param array $cartItems  Array von Jukebox-Datensätzen
 * @param int   $days       Mietdauer in Tagen
 * @param string $couponCode Optionaler Coupon-Code
 * @param float $transportCosts Transportkosten (bereits berechnet)
 * @return array Preisaufstellung
 */
function calculatePricing($cartItems, $days, $couponCode = '', $transportCosts = 0) {
    $settings = getAllSettings();
    $taxRate = (float)($settings['tax_rate'] ?? DEFAULT_SETTINGS['tax_rate']);
    
    $itemCount = count($cartItems);
    
    // 1. Mietsumme brutto (vor Rabatte)
    $rentalSubtotal = 0;
    $itemDetails = [];
    
    foreach ($cartItems as $jb) {
        $itemTotal = (float)$jb['price_day'] * max(1, $days);
        $rentalSubtotal += $itemTotal;
        $itemDetails[] = [
            'id' => $jb['id'],
            'name' => getLocalizedValue($jb, 'name'),
            'price_day' => (float)$jb['price_day'],
            'days' => max(1, $days),
            'total' => $itemTotal,
            'category' => $jb['category'] ?? null,
            'image' => getJukeboxImageUrl($jb['main_image'] ?? null)
        ];
    }
    
    // 2. Rabatte
    $durationDiscountPercent = getApplicableDiscount('duration', max(1, $days));
    $quantityDiscountPercent = getApplicableDiscount('quantity', $itemCount);
    
    $durationDiscountAmount = round($rentalSubtotal * ($durationDiscountPercent / 100), 2);
    $quantityDiscountAmount = round($rentalSubtotal * ($quantityDiscountPercent / 100), 2);
    
    $rentalAfterStandardDiscounts = $rentalSubtotal - $durationDiscountAmount - $quantityDiscountAmount;
    
    // 3. Coupon-Rabatt
    $couponDiscountAmount = 0;
    $couponStatus = null;
    $appliedCoupon = null;
    
    if (!empty($couponCode)) {
        $validation = validateCoupon($couponCode, $rentalAfterStandardDiscounts);
        $couponStatus = $validation['status'];
        
        if ($couponStatus === 'valid') {
            $appliedCoupon = $validation['coupon'];
            $couponDiscountAmount = round($rentalAfterStandardDiscounts * ((float)$appliedCoupon['discount_percent'] / 100), 2);
        }
    }
    
    $rentalNet = max(0, $rentalAfterStandardDiscounts - $couponDiscountAmount);
    
    // 4. Transportkosten (immer netto, nicht rabattfähig)
    $transportNet = max(0, (float)$transportCosts);
    
    // 5. Gesamtsumme netto
    $totalNet = $rentalNet + $transportNet;
    
    // 6. USt und Brutto
    $taxAmount = round($totalNet * ($taxRate / 100), 2);
    $totalGross = $totalNet + $taxAmount;

    // 7. Gesetzliche Vertragsgebühr (§ 33 TP 5 GebG 1957)
    $contractFeeEnabled = !empty($settings['contract_fee_enabled']) && (int)$settings['contract_fee_enabled'] === 1;
    $contractFeePercent = (float)($settings['contract_fee_percent'] ?? DEFAULT_SETTINGS['contract_fee_percent']);
    $contractFeeAmount = 0;

    if ($contractFeeEnabled && $contractFeePercent > 0 && $totalGross > 150) {
        $contractFeeAmount = round($totalGross * ($contractFeePercent / 100), 2);
    }

    $totalWithFee = $totalGross + $contractFeeAmount;

    return [
        'item_count' => $itemCount,
        'days' => max(1, $days),
        'rental_subtotal' => round($rentalSubtotal, 2),
        'duration_discount_percent' => $durationDiscountPercent,
        'duration_discount_amount' => $durationDiscountAmount,
        'quantity_discount_percent' => $quantityDiscountPercent,
        'quantity_discount_amount' => $quantityDiscountAmount,
        'coupon_code' => $couponCode,
        'coupon_status' => $couponStatus,
        'coupon_discount_percent' => $appliedCoupon ? (float)$appliedCoupon['discount_percent'] : 0,
        'coupon_discount_amount' => $couponDiscountAmount,
        'rental_net' => $rentalNet,
        'transport_net' => $transportNet,
        'total_net' => $totalNet,
        'tax_rate' => $taxRate,
        'tax_amount' => $taxAmount,
        'total_gross' => $totalGross,
        'contract_fee_enabled' => $contractFeeEnabled,
        'contract_fee_percent' => $contractFeePercent,
        'contract_fee_amount' => $contractFeeAmount,
        'total_with_fee' => $totalWithFee,
        'items' => $itemDetails
    ];
}

/**
 * Preis als formatierten String ausgeben
 */
function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Preis als formatierten String ohne Währung
 */
function formatMoneyRaw($amount) {
    return number_format($amount, 2, ',', '.');
}
