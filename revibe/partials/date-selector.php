<?php
/**
 * Wiederverwendbarer Datums-Selektor für Katalog/Detailseite.
 * Zeigt ein Modal-Popup auf dem Katalog, falls noch kein Mietzeitraum gewählt wurde,
 * und einen Inline-Selektor mit Info-Bubble auf allen Seiten.
 *
 * Parameter:
 *   $dsShowModal (bool) - true auf Katalogseite, false auf Detailseite
 *   $dsCompact (bool)  - true = nur Trigger-Button ohne Header/Status (z. B. in Filter-Bar)
 */
$dsShowModal = !empty($dsShowModal);
$dsCompact = !empty($dsCompact);
$dsLang = getCurrentLanguage();
$dsCart = getCart();
$dsHasDates = !empty($dsCart['date_start']) && !empty($dsCart['date_end']);
$dsDefaultDates = '';
$dsFormattedPeriod = '';
if ($dsHasDates) {
    $startFormatted = date('d.m.Y', strtotime($dsCart['date_start']));
    $endFormatted = date('d.m.Y', strtotime($dsCart['date_end']));
    if ($dsCart['date_end'] !== $dsCart['date_start']) {
        $dsDefaultDates = $startFormatted . ' - ' . $endFormatted;
        $dsFormattedPeriod = $startFormatted . ' - ' . $endFormatted;
    } else {
        $dsDefaultDates = $startFormatted;
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
            <div class="date-modal-calendar"></div>
            <input type="hidden" class="date-modal-start" value="<?php echo e($dsCart['date_start']); ?>">
            <input type="hidden" class="date-modal-end" value="<?php echo e($dsCart['date_end']); ?>">
        </div>

        <div class="date-modal-actions">
            <button type="button" class="btn btn-primary btn-lg" id="date-modal-view"><?php echo __('catalog_date_view_all'); ?></button>
            <button type="button" class="btn btn-text" id="date-modal-skip"><?php echo __('catalog_date_skip'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Inline Date Selector -->
<div class="date-selector<?php echo $dsCompact ? ' date-selector-compact' : ''; ?>" data-has-dates="<?php echo $dsHasDates ? '1' : '0'; ?>">
    <?php if (!$dsCompact): ?>
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
    <?php endif; ?>

    <div class="date-selector-body">
        <?php if ($dsShowModal): ?>
        <button type="button" class="date-selector-trigger" id="date-selector-trigger">
            <span class="date-selector-trigger-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
            <span class="date-selector-trigger-dates" id="date-selector-trigger-dates"><?php echo $dsHasDates ? e($dsFormattedPeriod) : e(__('catalog_select_dates')); ?></span>
            <span class="date-selector-trigger-action" id="date-selector-trigger-action"><?php echo $dsHasDates ? e(__('catalog_date_change')) : e(__('catalog_select_dates')); ?></span>
        </button>
        <?php else: ?>
        <div class="date-selector-input-wrap">
            <input type="text" class="date-selector-input form-input" value="<?php echo e($dsDefaultDates); ?>" placeholder="TT.MM.JJJJ - TT.MM.JJJJ" readonly>
        </div>
        <?php endif; ?>
        <input type="hidden" class="date-selector-start" value="<?php echo e($dsCart['date_start']); ?>">
        <input type="hidden" class="date-selector-end" value="<?php echo e($dsCart['date_end']); ?>">
        <?php if (!$dsCompact): ?>
        <div class="date-selector-status" id="date-selector-status">
            <?php echo $dsHasDates ? e($dsFormattedPeriod) : __('catalog_date_selector_hint'); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.catalogDateSavingText = <?php echo json_encode(__('catalog_date_saving')); ?>;
window.catalogDateSavedText = <?php echo json_encode(__('catalog_date_saved')); ?>;
window.catalogNotAvailableText = <?php echo json_encode(__('catalog_not_available')); ?>;
window.catalogSelectDatesText = <?php echo json_encode(__('catalog_select_dates_first')); ?>;
window.catalogSelectDatesTitleText = <?php echo json_encode(__('catalog_select_dates')); ?>;
window.catalogDateSelectorHint = <?php echo json_encode(__('catalog_date_selector_hint')); ?>;
window.catalogDateSelectedPeriod = <?php echo json_encode(__('catalog_date_selected_period')); ?>;
window.catalogDateChange = <?php echo json_encode(__('catalog_date_change')); ?>;
</script>
