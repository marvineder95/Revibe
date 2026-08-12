<?php
/**
 * Kompakte Preiszusammenfassung für Kontaktseite
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

<div class="pricing-compact" style="font-size: var(--text-sm);">
    <?php if (!empty($pricing['items'])): ?>
    <div style="margin-bottom: var(--space-3);">
        <?php foreach ($pricing['items'] as $item): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-2) 0; border-bottom: 1px dashed var(--color-gray-700);">
            <div style="display: flex; align-items: center; gap: var(--space-2);">
                <?php if (!empty($item['image'])): ?>
                <img src="<?php echo e($item['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: var(--radius-sm);">
                <?php endif; ?>
                <span style="color: var(--color-gray-300); font-size: var(--text-sm);"><?php echo e($item['name']); ?></span>
            </div>
            <span style="font-weight: 500;"><?php echo formatMoney($item['total']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($pricing['duration_discount_amount'] > 0 || $pricing['quantity_discount_amount'] > 0 || $pricing['coupon_discount_amount'] > 0): ?>
    <div style="margin-bottom: var(--space-3);">
        <?php if ($pricing['duration_discount_amount'] > 0): ?>
        <div style="display: flex; justify-content: space-between; color: var(--color-primary); font-size: var(--text-sm);">
            <span><?php echo __('request_duration_discount'); ?> (<?php echo formatMoneyRaw($pricing['duration_discount_percent']); ?> %)</span>
            <span>– <?php echo formatMoney($pricing['duration_discount_amount']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($pricing['quantity_discount_amount'] > 0): ?>
        <div style="display: flex; justify-content: space-between; color: var(--color-primary); font-size: var(--text-sm);">
            <span><?php echo __('request_quantity_discount'); ?> (<?php echo formatMoneyRaw($pricing['quantity_discount_percent']); ?> %)</span>
            <span>– <?php echo formatMoney($pricing['quantity_discount_amount']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($pricing['coupon_discount_amount'] > 0): ?>
        <div style="display: flex; justify-content: space-between; color: var(--color-primary); font-size: var(--text-sm);">
            <span><?php echo __('request_coupon_discount'); ?> (<?php echo e($pricing['coupon_code']); ?>)</span>
            <span>– <?php echo formatMoney($pricing['coupon_discount_amount']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="border-top: 1px solid var(--color-gray-700); padding-top: var(--space-2); margin-bottom: var(--space-2);">
        <div style="display: flex; justify-content: space-between; font-size: var(--text-sm);">
            <span style="color: var(--color-gray-400);"><?php echo __('request_rental_net'); ?></span>
            <span><?php echo formatMoney($pricing['rental_net']); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: var(--text-sm);">
            <span style="color: var(--color-gray-400);"><?php echo __('request_transport'); ?></span>
            <?php if (!empty($cart['event_address']) && empty($transport['error'])): ?>
            <span><?php echo formatMoney($pricing['transport_net']); ?></span>
            <?php elseif (!empty($cart['event_address'])): ?>
            <span style="color: var(--color-gray-500); font-size: var(--text-xs);"><?php echo __('request_individually_calculated'); ?></span>
            <?php else: ?>
            <span style="color: var(--color-gray-500); font-size: var(--text-xs);"><?php echo __('request_enter_address'); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="border-top: 1px solid var(--color-gray-700); padding-top: var(--space-2); margin-bottom: var(--space-2);">
        <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: var(--text-sm);">
            <span><?php echo __('request_total_net'); ?></span>
            <span><?php echo formatMoney($pricing['total_net']); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; color: var(--color-gray-400); font-size: var(--text-sm);">
            <span><?php echo __('request_vat'); ?> (<?php echo formatMoneyRaw($pricing['tax_rate']); ?> %)</span>
            <span><?php echo formatMoney($pricing['tax_amount']); ?></span>
        </div>
    </div>
    
    <div style="display: flex; justify-content: space-between; font-size: var(--text-base); font-weight: 700; color: var(--color-primary); margin-top: var(--space-2); padding-top: var(--space-2); border-top: 2px solid var(--color-primary);">
        <span><?php echo __('request_total_gross'); ?></span>
        <span><?php echo formatMoney($pricing['total_gross']); ?></span>
    </div>

    <?php if ($pricing['contract_fee_amount'] > 0): ?>
    <div style="display: flex; justify-content: space-between; color: var(--color-gray-400); font-size: var(--text-sm);">
        <span>
            <?php echo __('request_contract_fee', ['percent' => formatMoneyRaw($pricing['contract_fee_percent'])]); ?>
            <span style="cursor: help;" title="<?php echo e(__('request_contract_fee_hint')); ?>">ⓘ</span>
        </span>
        <span><?php echo formatMoney($pricing['contract_fee_amount']); ?></span>
    </div>

    <div style="display: flex; justify-content: space-between; font-size: var(--text-base); font-weight: 700; color: var(--color-primary); padding-top: var(--space-2); border-top: 1px dashed var(--color-primary);">
        <span><?php echo __('request_total_with_contract_fee'); ?></span>
        <span><?php echo formatMoney($pricing['total_with_fee']); ?></span>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top: var(--space-4); padding: var(--space-3); background: rgba(212,175,55,0.05); border: 1px solid rgba(212,175,55,0.2); border-radius: var(--radius-md);">
    <p style="font-size: 11px; color: var(--color-gray-500); margin-bottom: 0; line-height: 1.5;">
        ℹ <?php echo __('request_hint_non_binding'); ?>. <?php echo __('request_hint_no_booking'); ?>
    </p>
</div>
