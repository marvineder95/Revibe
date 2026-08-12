<?php
/**
 * Wiederverwendbarer Datums-Selektor für Katalog/Detailseite
 * Speichert den gewählten Zeitraum direkt im Warenkorb.
 */
$dsLang = getCurrentLanguage();
$dsCart = getCart();
$dsDefaultDates = '';
if (!empty($dsCart['date_start'])) {
    $dsDefaultDates = $dsCart['date_start'];
    if (!empty($dsCart['date_end']) && $dsCart['date_end'] !== $dsCart['date_start']) {
        $dsDefaultDates .= ' - ' . $dsCart['date_end'];
    }
}
?>
<div class="date-selector" style="background: var(--color-cream); border: 1px solid rgba(32,33,33,0.1); border-radius: var(--radius-md); padding: var(--space-4); margin-bottom: var(--space-6);">
    <label class="form-label" style="display: block; margin-bottom: var(--space-2);"><?php echo __('catalog_select_dates'); ?></label>
    <div style="display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" class="date-selector-input form-input" value="<?php echo e($dsDefaultDates); ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ" style="width: 100%;">
            <input type="hidden" class="date-selector-start" value="<?php echo e($dsCart['date_start']); ?>">
            <input type="hidden" class="date-selector-end" value="<?php echo e($dsCart['date_end']); ?>">
        </div>
        <div class="date-selector-status" style="font-size: var(--text-sm); color: var(--color-gray-500);">
            <?php echo __('catalog_date_selector_hint'); ?>
        </div>
    </div>
</div>
<script>
window.catalogDateSavingText = <?php echo json_encode(__('catalog_date_saving')); ?>;
window.catalogDateSavedText = <?php echo json_encode(__('catalog_date_saved')); ?>;
window.catalogNotAvailableText = <?php echo json_encode(__('catalog_not_available')); ?>;
window.catalogSelectDatesText = <?php echo json_encode(__('catalog_select_dates_first')); ?>;
</script>
