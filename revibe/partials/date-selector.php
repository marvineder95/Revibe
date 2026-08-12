<?php
/**
 * Wiederverwendbarer Datums-Selektor für Katalog/Detailseite.
 * Zeigt ein Modal-Popup auf dem Katalog, falls noch kein Mietzeitraum gewählt wurde,
 * und einen Inline-Selektor mit Info-Bubble auf allen Seiten.
 *
 * Parameter:
 *   $dsShowModal (bool) - true auf Katalogseite, false auf Detailseite
 */
$dsShowModal = !empty($dsShowModal);
$dsLang = getCurrentLanguage();
$dsCart = getCart();
$dsHasDates = !empty($dsCart['date_start']) && !empty($dsCart['date_end']);
$dsDefaultDates = '';
$dsFormattedPeriod = '';
if ($dsHasDates) {
    $startFormatted = date('d.m.Y', strtotime($dsCart['date_start']));
    $endFormatted = date('d.m.Y', strtotime($dsCart['date_end']));
    if ($dsCart['date_end'] !== $dsCart['date_start']) {
        $dsDefaultDates = $dsCart['date_start'] . ' - ' . $dsCart['date_end'];
        $dsFormattedPeriod = $startFormatted . ' - ' . $endFormatted;
    } else {
        $dsDefaultDates = $dsCart['date_start'];
        $dsFormattedPeriod = $startFormatted;
    }
}
?>

<?php if ($dsShowModal): ?>
<!-- Date Selection Modal -->
<div class="date-modal-overlay<?php echo $dsHasDates ? ' is-hidden' : ''; ?>" id="date-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="date-modal-title">
    <div class="date-modal">
        <div class="date-modal-header">
            <h2 id="date-modal-title" class="date-modal-title"><?php echo __('catalog_select_dates'); ?></h2>
            <div class="info-bubble-wrapper">
                <button type="button" class="info-bubble" aria-label="<?php echo e(__('catalog_date_info_title')); ?>" aria-expanded="false" aria-controls="date-modal-tooltip">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </button>
                <div id="date-modal-tooltip" class="info-tooltip" role="tooltip">
                    <strong><?php echo __('catalog_date_info_title'); ?></strong>
                    <p><?php echo __('catalog_date_info_text'); ?></p>
                </div>
            </div>
        </div>

        <p class="date-modal-intro"><?php echo __('catalog_date_modal_intro'); ?></p>

        <div class="date-modal-input-wrap">
            <input type="text" class="date-modal-input form-input" value="<?php echo e($dsDefaultDates); ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ" aria-label="<?php echo e(__('catalog_select_dates')); ?>">
            <input type="hidden" class="date-modal-start" value="<?php echo e($dsCart['date_start']); ?>">
            <input type="hidden" class="date-modal-end" value="<?php echo e($dsCart['date_end']); ?>">
        </div>

        <div class="date-modal-actions">
            <button type="button" class="btn btn-secondary" id="date-modal-skip"><?php echo __('catalog_date_skip'); ?></button>
            <button type="button" class="btn btn-primary" id="date-modal-view"><?php echo __('catalog_date_view_all'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Inline Date Selector -->
<div class="date-selector" data-has-dates="<?php echo $dsHasDates ? '1' : '0'; ?>">
    <div class="date-selector-header">
        <div class="date-selector-title-row">
            <label class="form-label"><?php echo __('catalog_select_dates'); ?></label>
            <div class="info-bubble-wrapper">
                <button type="button" class="info-bubble" aria-label="<?php echo e(__('catalog_date_info_title')); ?>" aria-expanded="false" aria-controls="date-selector-tooltip-<?php echo $dsShowModal ? 'catalog' : 'detail'; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </button>
                <div id="date-selector-tooltip-<?php echo $dsShowModal ? 'catalog' : 'detail'; ?>" class="info-tooltip" role="tooltip">
                    <strong><?php echo __('catalog_date_info_title'); ?></strong>
                    <p><?php echo __('catalog_date_info_text'); ?></p>
                </div>
            </div>
        </div>
        <?php if ($dsHasDates): ?>
        <div class="date-selector-period">
            <span><?php echo __('catalog_date_selected_period'); ?>:</span>
            <strong><?php echo e($dsFormattedPeriod); ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="date-selector-body">
        <div class="date-selector-input-wrap">
            <input type="text" class="date-selector-input form-input" value="<?php echo e($dsDefaultDates); ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ">
            <input type="hidden" class="date-selector-start" value="<?php echo e($dsCart['date_start']); ?>">
            <input type="hidden" class="date-selector-end" value="<?php echo e($dsCart['date_end']); ?>">
        </div>
        <div class="date-selector-status">
            <?php echo $dsHasDates ? e($dsFormattedPeriod) : __('catalog_date_selector_hint'); ?>
        </div>
    </div>
</div>

<script>
window.catalogDateSavingText = <?php echo json_encode(__('catalog_date_saving')); ?>;
window.catalogDateSavedText = <?php echo json_encode(__('catalog_date_saved')); ?>;
window.catalogNotAvailableText = <?php echo json_encode(__('catalog_not_available')); ?>;
window.catalogSelectDatesText = <?php echo json_encode(__('catalog_select_dates_first')); ?>;
window.catalogDateSelectorHint = <?php echo json_encode(__('catalog_date_selector_hint')); ?>;
window.catalogDateSelectedPeriod = <?php echo json_encode(__('catalog_date_selected_period')); ?>;
window.catalogDateChange = <?php echo json_encode(__('catalog_date_change')); ?>;
</script>
