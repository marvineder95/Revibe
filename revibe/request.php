<?php
/**
 * Anfragekorb / Warenkorb
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'contact';
$metaData = ['url' => BASE_URL . 'request.php'];

$cartItems = getCartItems();
$cart = getCart();

// Coupon per URL vorausfüllen
if (isset($_GET['coupon'])) {
    cartSetCoupon($_GET['coupon']);
    $cart = getCart();
}

$lang = getCurrentLanguage();

// Verfügbarkeit des Warenkorbs prüfen
$cartAvailability = checkCartAvailability($cart);

include PARTIALS_PATH . 'header.php';
?>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <!-- Linke Spalte: Warenkorb & Konfiguration -->
            <div class="reveal">
                <div class="contact-form">
                    <h3 style="margin-bottom: var(--space-4);"><?php echo __('request_selected_jukeboxes'); ?></h3>
                    
                    <div id="cart-items">
                        <?php if (empty($cartItems)): ?>
                        <div style="text-align: center; padding: var(--space-8); background: var(--color-cream); border: 1px solid rgba(32, 33, 33, 0.1); border-radius: var(--radius-md);">
                            <p style="color: var(--color-gray-500); margin-bottom: var(--space-4);"><?php echo __('cart_empty_title'); ?></p>
                            <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-primary"><?php echo __('cart_add_boxes'); ?></a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($cartItems as $jb): ?>
                        <div class="cart-item" style="display: flex; gap: var(--space-4); align-items: center; padding: var(--space-4); background: var(--color-cream); border: 1px solid rgba(32, 33, 33, 0.1); border-radius: var(--radius-md); margin-bottom: var(--space-3);">
                            <img src="<?php echo e(getJukeboxImageUrl($jb['main_image'])); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-md);">
                            <div style="flex: 1;">
                                <div style="font-weight: 600;"><?php echo e(getLocalizedValue($jb, 'name')); ?></div>
                                <div style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                    <?php echo formatPrice($jb['price_day']); ?>
                                    <?php if (!empty($jb['category'])): ?>
                                    <span style="display: inline-block; margin-left: var(--space-2); padding: 2px 6px; background: <?php echo e($jb['category']['color']); ?>22; color: <?php echo e($jb['category']['color']); ?>; border-radius: var(--radius-sm); font-size: 11px;">
                                        <?php echo e(getCategoryName($jb['category'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-dark btn-sm cart-remove" data-id="<?php echo e($jb['id']); ?>" title="<?php echo __('cart_remove'); ?>">×</button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($cartItems)): ?>
                    <hr style="border-color: var(--color-gray-700); margin: var(--space-6) 0;">
                    
                    <h3 style="margin-bottom: var(--space-4);"><?php echo __('request_rental_data'); ?></h3>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('request_rental_period'); ?> *</label>
                        <div class="date-input-wrapper">
                            <input type="text" id="req-date" class="form-input" value="<?php echo $cart['date_start'] ? e($cart['date_start'] . ($cart['date_end'] && $cart['date_end'] !== $cart['date_start'] ? ' - ' . $cart['date_end'] : '')) : ''; ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ" required>
                        </div>
                        <input type="hidden" id="req-date-start" value="<?php echo e($cart['date_start']); ?>">
                        <input type="hidden" id="req-date-end" value="<?php echo e($cart['date_end']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo __('request_event_address'); ?> *</label>
                        <input type="text" id="req-address" class="form-input" value="<?php echo e($cart['event_address']); ?>" placeholder="Straße, PLZ Ort, Land" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo __('request_discount_code'); ?></label>
                        <div style="display: flex; gap: var(--space-2);">
                            <input type="text" id="req-coupon" class="form-input" value="<?php echo e($cart['coupon_code']); ?>" placeholder="CODE" style="text-transform: uppercase;">
                            <button type="button" id="coupon-apply" class="btn btn-dark"><?php echo __('request_apply'); ?></button>
                        </div>
                        <p id="coupon-message" style="font-size: var(--text-sm); margin-top: var(--space-2); margin-bottom: 0; min-height: 1.2em;"></p>
                    </div>

                    <?php if (!empty($cartItems) && !$cartAvailability['available']): ?>
                    <div id="cart-availability-warning" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-md); padding: var(--space-4); margin-bottom: var(--space-4); color: var(--color-error, #ef4444); font-size: var(--text-sm);">
                        <strong><?php echo __('cart_item_not_available'); ?></strong>
                        <?php foreach ($cartAvailability['conflicts'] as $jukeboxId => $conflicts): ?>
                            <?php foreach ($conflicts as $conflict): ?>
                            <div style="margin-top: var(--space-1);">
                                <?php echo __('cart_item_not_available_period', [
                                    'name' => e(getLocalizedValue(getJukeboxById($jukeboxId), 'name')),
                                    'start' => date('d.m.Y', strtotime($conflict['date_start'])),
                                    'end' => date('d.m.Y', strtotime($conflict['date_end']))
                                ]); ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap; margin-top: var(--space-6);">
                        <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-dark">
                            ← <?php echo __('request_continue_browsing'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary btn-lg" <?php echo (!empty($cartItems) && !$cartAvailability['available']) ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                            <?php echo $lang === 'de' ? 'Direkt anfragen' : 'Inquire now'; ?> →
                        </a>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-dark" style="margin-top: var(--space-4);">
                        ← <?php echo __('request_continue_browsing'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Rechte Spalte: Hinweis / Zusammenfassung kompakt -->
            <div class="reveal">
                <div class="contact-info-card">
                    <?php if (empty($cartItems)): ?>
                    <h3 style="margin-bottom: var(--space-4);"><?php echo __('request_price_summary'); ?></h3>
                    <p style="color: var(--color-gray-500); margin-bottom: var(--space-6);"><?php echo $lang === 'de' ? 'Fügen Sie Jukeboxen hinzu, um eine Preisschätzung zu sehen.' : 'Add jukeboxes to see a price estimate.'; ?></p>
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-primary btn-lg btn-full">
                        <?php echo $lang === 'de' ? 'Jukeboxen entdecken' : 'Discover jukeboxes'; ?> →
                    </a>
                    <?php else: ?>
                    <h3 style="margin-bottom: var(--space-4);"><?php echo $lang === 'de' ? 'Ihre Anfrage' : 'Your Inquiry'; ?></h3>
                    <p style="color: var(--color-gray-400); margin-bottom: var(--space-4);">
                        <?php echo $lang === 'de' 
                            ? 'Sobald Sie alle Angaben gemacht haben, können Sie auf der nächsten Seite Ihre unverbindliche Anfrage absenden.' 
                            : 'Once you have entered all details, you can submit your non-binding inquiry on the next page.'; ?>
                    </p>
                    <ul style="color: var(--color-gray-500); font-size: var(--text-sm); padding-left: var(--space-5); margin-bottom: 0;">
                        <li style="margin-bottom: var(--space-2);"><?php echo $lang === 'de' ? 'Mietdauer frei wählbar' : 'Flexible rental period'; ?></li>
                        <li style="margin-bottom: var(--space-2);"><?php echo $lang === 'de' ? 'Transportkosten werden automatisch berechnet' : 'Transport costs are calculated automatically'; ?></li>
                        <li><?php echo $lang === 'de' ? 'Unverbindliches Angebot' : 'Non-binding offer'; ?></li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var lang = '<?php echo e($lang); ?>';
    
    var defaultDates = <?php echo json_encode($cart['date_start'] ? ($cart['date_end'] && $cart['date_end'] !== $cart['date_start'] ? [$cart['date_start'], $cart['date_end']] : $cart['date_start']) : null); ?>;
    
    flatpickr('#req-date', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'd.m.Y',
        locale: lang === 'de' ? 'de' : 'en',
        allowInput: true,
        defaultDate: defaultDates,
        onChange: function(selectedDates, dateStr) {
            var start = selectedDates[0] ? formatDate(selectedDates[0]) : '';
            var end = selectedDates[1] ? formatDate(selectedDates[1]) : '';
            document.getElementById('req-date-start').value = start;
            document.getElementById('req-date-end').value = end || start;
            updateCart();
        }
    });
    
    function formatDate(date) {
        var d = date.getDate().toString().padStart(2, '0');
        var m = (date.getMonth() + 1).toString().padStart(2, '0');
        var y = date.getFullYear();
        return d + '.' + m + '.' + y;
    }
    
    var addressInput = document.getElementById('req-address');
    var couponInput = document.getElementById('req-coupon');
    var couponBtn = document.getElementById('coupon-apply');
    
    var updateTimeout;
    function updateCart() {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function() {
            var start = document.getElementById('req-date-start').value;
            var end = document.getElementById('req-date-end').value;
            var address = addressInput.value;
            var coupon = couponInput.value;
            
            fetch('includes/ajax.php?action=updateCart', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'date_start=' + encodeURIComponent(start) + '&date_end=' + encodeURIComponent(end) + '&address=' + encodeURIComponent(address) + '&coupon=' + encodeURIComponent(coupon)
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('coupon-message').textContent = data.couponMessage || '';
                        document.getElementById('coupon-message').style.color = data.couponValid ? '#22c55e' : '#ef4444';
                        if (typeof data.available !== 'undefined') {
                            window.location.reload();
                        }
                    }
                });
        }, 400);
    }
    
    addressInput.addEventListener('blur', updateCart);
    couponBtn.addEventListener('click', updateCart);
    
    document.querySelectorAll('.cart-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            fetch('includes/ajax.php?action=cartRemove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (data.count === 0) {
                            window.location.href = 'catalog.php';
                        } else {
                            window.location.reload();
                        }
                    }
                });
        });
    });
});
</script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
