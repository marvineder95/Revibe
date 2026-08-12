<?php
/**
 * Preiszusammenfassung Partial
 * Wird serverseitig und via AJAX verwendet
 */
$cartItems = getCartItems();
$cart = getCart();

$transport = ['costs' => 0, 'error' => $cart['transport_error'] ?? ''];
if (!empty($cart['event_address'])) {
    if ($cart['transport_calculated']) {
        $transport['costs'] = computeTransportPrice($cart['transport_distance_km'], $cart['transport_duration_min']);
        $transport['error'] = '';
    } else {
        $transport = calculateTransportCosts($cart['event_address']);
        if (empty($transport['error'])) {
            cartSetTransportData($transport['distance_km'], $transport['duration_min'], '');
        } else {
            cartSetTransportData(0, 0, $transport['error']);
        }
    }
}

$pricing = calculatePricing($cartItems, $cart['duration_days'], $cart['coupon_code'], $transport['costs']);
$lang = getCurrentLanguage();
?>

<div class="pricing-lines" style="display: grid; gap: var(--space-2); font-size: var(--text-sm);">
    
    <?php foreach ($pricing['items'] as $item): ?>
    <div style="display: flex; justify-content: space-between;">
        <span><?php echo e($item['name']); ?> <span style="color: var(--color-gray-500);">(<?php echo formatMoneyRaw($item['price_day']); ?> € × <?php echo $item['days']; ?> <?php echo $lang === 'de' ? 'Tg' : 'd'; ?>)</span></span>
        <span><?php echo formatMoney($item['total']); ?></span>
    </div>
    <?php endforeach; ?>
    
    <div style="border-top: 1px solid var(--color-gray-700); margin-top: var(--space-2); padding-top: var(--space-2); display: flex; justify-content: space-between; font-weight: 500;">
        <span><?php echo __('request_price_subtotal_jukeboxes'); ?></span>
        <span><?php echo formatMoney($pricing['rental_subtotal']); ?></span>
    </div>
    
    <?php if ($pricing['duration_discount_amount'] > 0): ?>
    <div style="display: flex; justify-content: space-between; color: var(--color-primary);">
        <span><?php echo __('request_duration_discount'); ?> (<?php echo formatMoneyRaw($pricing['duration_discount_percent']); ?> %)</span>
        <span>– <?php echo formatMoney($pricing['duration_discount_amount']); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($pricing['quantity_discount_amount'] > 0): ?>
    <div style="display: flex; justify-content: space-between; color: var(--color-primary);">
        <span><?php echo __('request_quantity_discount'); ?> (<?php echo formatMoneyRaw($pricing['quantity_discount_percent']); ?> %)</span>
        <span>– <?php echo formatMoney($pricing['quantity_discount_amount']); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($pricing['coupon_discount_amount'] > 0): ?>
    <div style="display: flex; justify-content: space-between; color: var(--color-primary);">
        <span><?php echo __('request_coupon_discount'); ?> (<?php echo e($pricing['coupon_code']); ?>, <?php echo formatMoneyRaw($pricing['coupon_discount_percent']); ?> %)</span>
        <span>– <?php echo formatMoney($pricing['coupon_discount_amount']); ?></span>
    </div>
    <?php endif; ?>
    
    <div style="border-top: 1px solid var(--color-gray-700); margin-top: var(--space-2); padding-top: var(--space-2); display: flex; justify-content: space-between;">
        <span><?php echo __('request_rental_net'); ?></span>
        <span><?php echo formatMoney($pricing['rental_net']); ?></span>
    </div>
    
    <div style="display: flex; justify-content: space-between;">
        <span><?php echo __('request_transport'); ?></span>
        <?php if (!empty($cart['event_address'])): ?>
            <?php if (empty($transport['error'])): ?>
            <span><?php echo formatMoney($pricing['transport_net']); ?></span>
            <?php else: ?>
            <span style="color: var(--color-gray-500); font-size: var(--text-xs);"><?php echo __('request_individually_calculated'); ?></span>
            <?php endif; ?>
        <?php else: ?>
        <span style="color: var(--color-gray-500); font-size: var(--text-xs);"><?php echo __('request_enter_address'); ?></span>
        <?php endif; ?>
    </div>
    
    <div style="border-top: 1px solid var(--color-gray-700); margin-top: var(--space-2); padding-top: var(--space-2); display: flex; justify-content: space-between; font-weight: 600;">
        <span><?php echo __('request_total_net'); ?></span>
        <span><?php echo formatMoney($pricing['total_net']); ?></span>
    </div>
    
    <div style="display: flex; justify-content: space-between; color: var(--color-gray-400);">
        <span><?php echo __('request_vat'); ?> (<?php echo formatMoneyRaw($pricing['tax_rate']); ?> %)</span>
        <span><?php echo formatMoney($pricing['tax_amount']); ?></span>
    </div>
    
    <div style="display: flex; justify-content: space-between; font-size: var(--text-lg); font-weight: 700; color: var(--color-primary); margin-top: var(--space-2); padding-top: var(--space-2); border-top: 2px solid var(--color-primary);">
        <span><?php echo __('request_total_gross'); ?></span>
        <span><?php echo formatMoney($pricing['total_gross']); ?></span>
    </div>

    <?php if ($pricing['contract_fee_amount'] > 0): ?>
    <div style="display: flex; justify-content: space-between; color: var(--color-gray-400);">
        <span>
            <?php echo __('request_contract_fee', ['percent' => formatMoneyRaw($pricing['contract_fee_percent'])]); ?>
            <span style="cursor: help;" title="<?php echo e(__('request_contract_fee_hint')); ?>">ⓘ</span>
        </span>
        <span><?php echo formatMoney($pricing['contract_fee_amount']); ?></span>
    </div>

    <div style="display: flex; justify-content: space-between; font-size: var(--text-lg); font-weight: 700; color: var(--color-primary); padding-top: var(--space-2); border-top: 1px dashed var(--color-primary);">
        <span><?php echo __('request_total_with_contract_fee'); ?></span>
        <span><?php echo formatMoney($pricing['total_with_fee']); ?></span>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top: var(--space-6); padding: var(--space-4); background: rgba(212,175,55,0.05); border: 1px solid rgba(212,175,55,0.2); border-radius: var(--radius-md);">
    <p style="font-size: var(--text-xs); color: var(--color-gray-400); margin-bottom: var(--space-2);">
        <strong>ℹ <?php echo __('request_hint_non_binding'); ?></strong>
    </p>
    <ul style="font-size: var(--text-xs); color: var(--color-gray-500); margin-bottom: 0; padding-left: var(--space-4);">
        <li><?php echo __('request_hint_no_booking'); ?></li>
        <li><?php echo __('request_hint_final_offer'); ?></li>
        <li><?php echo __('request_hint_transport_depends'); ?></li>
    </ul>
</div>
