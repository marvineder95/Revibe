<?php
/**
 * Jukebox-Katalog
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'catalog';
$metaData = [
    'url' => BASE_URL . 'catalog.php'
];

// Sortierung (weiterhin via URL-Parameter möglich, aber ohne UI-Dropdown)
$sort = $_GET['sort'] ?? 'order';
$order = $_GET['order'] ?? 'ASC';
$allowedSorts = ['name', 'price_day', 'order'];
$allowedOrders = ['ASC', 'DESC'];

if (!in_array($sort, $allowedSorts, true)) $sort = 'order';
if (!in_array($order, $allowedOrders, true)) $order = 'ASC';

// Filter auslesen
$filters = [
    'size' => $_GET['size'] ?? [],
    'color' => $_GET['color'] ?? [],
    'manufacturer' => $_GET['manufacturer'] ?? [],
    'function_status' => $_GET['function_status'] ?? [],
    'category_id' => $_GET['category_id'] ?? [],
    'new_arrival' => !empty($_GET['new_arrival']),
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? ''
];

// Arrays normalisieren
foreach (['size', 'color', 'manufacturer', 'function_status', 'category_id'] as $key) {
    $filters[$key] = array_filter((array)$filters[$key], function($v) {
        return $v !== '' && $v !== null;
    });
}

// Preisfilter validieren: max muss größer als min sein
$priceMin = $filters['price_min'] !== '' ? (float)$filters['price_min'] : null;
$priceMax = $filters['price_max'] !== '' ? (float)$filters['price_max'] : null;

if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
    // Werte tauschen, damit der Filter sinnvoll bleibt
    $filters['price_min'] = (string)$priceMax;
    $filters['price_max'] = (string)$priceMin;
}

// Filter-Optionen laden
$filterManufacturers = getFilterManufacturers();
$filterSizes = getFilterSizes();
$filterColors = getFilterColors();
$filterCategories = getAllCategories(true);
$filterStatuses = ['working', 'deco', 'restored', 'original'];
$priceRange = getPriceRange();

// Jukeboxen laden
$jukeboxes = getAllJukeboxes($sort, $order, $filters);

// Hilfsfunktion: prüft ob ein Filterwert aktiv ist
function isFilterActive($type, $value) {
    global $filters;
    return is_array($filters[$type]) && in_array($value, $filters[$type], true);
}

$hasActiveFilters = !empty($filters['size']) || !empty($filters['color']) || !empty($filters['manufacturer'])
    || !empty($filters['function_status']) || !empty($filters['category_id']) || !empty($filters['new_arrival'])
    || $filters['price_min'] !== '' || $filters['price_max'] !== '';

include PARTIALS_PATH . 'header.php';
?>

<!-- Catalog Section -->
<section class="section">
    <div class="container">
        <form method="get" class="catalog-filter-form" id="catalog-filter-form">
            <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
            <input type="hidden" name="order" value="<?php echo e($order); ?>">

            <!-- Filter Bar -->
            <div class="catalog-filter-bar reveal">
                <div class="catalog-filter-pills">
                    <!-- Hersteller -->
                    <?php if (!empty($filterManufacturers)): ?>
                    <div class="filter-pill" data-filter="manufacturer">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_manufacturer'); ?>
                            <?php if (!empty($filters['manufacturer'])): ?>
                            <span class="filter-pill-count"><?php echo count($filters['manufacturer']); ?></span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown">
                            <?php foreach ($filterManufacturers as $m): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="manufacturer[]" value="<?php echo e($m); ?>" <?php echo isFilterActive('manufacturer', $m) ? 'checked' : ''; ?>>
                                <span class="filter-option-check"></span>
                                <span class="filter-option-label"><?php echo e($m); ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Größe -->
                    <?php if (!empty($filterSizes)): ?>
                    <div class="filter-pill" data-filter="size">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_size'); ?>
                            <?php if (!empty($filters['size'])): ?>
                            <span class="filter-pill-count"><?php echo count($filters['size']); ?></span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown">
                            <?php foreach ($filterSizes as $s): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="size[]" value="<?php echo e($s); ?>" <?php echo isFilterActive('size', $s) ? 'checked' : ''; ?>>
                                <span class="filter-option-check"></span>
                                <span class="filter-option-label"><?php echo e($s); ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Farbe -->
                    <?php if (!empty($filterColors)): ?>
                    <div class="filter-pill" data-filter="color">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_color'); ?>
                            <?php if (!empty($filters['color'])): ?>
                            <span class="filter-pill-count"><?php echo count($filters['color']); ?></span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown">
                            <?php foreach ($filterColors as $c): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="color[]" value="<?php echo e($c); ?>" <?php echo isFilterActive('color', $c) ? 'checked' : ''; ?>>
                                <span class="filter-option-check"></span>
                                <span class="filter-option-label"><?php echo e($c); ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Funktionsstatus -->
                    <div class="filter-pill" data-filter="function_status">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_function'); ?>
                            <?php if (!empty($filters['function_status'])): ?>
                            <span class="filter-pill-count"><?php echo count($filters['function_status']); ?></span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown">
                            <?php foreach ($filterStatuses as $status): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="function_status[]" value="<?php echo e($status); ?>" <?php echo isFilterActive('function_status', $status) ? 'checked' : ''; ?>>
                                <span class="filter-option-check"></span>
                                <span class="filter-option-label"><?php echo __('status_' . $status); ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Kategorie -->
                    <?php if (!empty($filterCategories)): ?>
                    <div class="filter-pill" data-filter="category_id">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_category'); ?>
                            <?php if (!empty($filters['category_id'])): ?>
                            <span class="filter-pill-count"><?php echo count($filters['category_id']); ?></span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown">
                            <?php foreach ($filterCategories as $cat): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="category_id[]" value="<?php echo e($cat['id']); ?>" <?php echo isFilterActive('category_id', $cat['id']) ? 'checked' : ''; ?>>
                                <span class="filter-option-check"></span>
                                <span class="filter-option-label"><?php echo e(getCategoryName($cat)); ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Preis -->
                    <div class="filter-pill" data-filter="price">
                        <button type="button" class="filter-pill-toggle">
                            <?php echo __('catalog_filter_price'); ?>
                            <?php if ($filters['price_min'] !== '' || $filters['price_max'] !== ''): ?>
                            <span class="filter-pill-count">1</span>
                            <?php endif; ?>
                            <svg class="filter-pill-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="filter-pill-dropdown filter-pill-dropdown-wide">
                            <div class="filter-price-inputs">
                                <div class="form-group">
                                    <label class="form-label">Min (€)</label>
                                    <input type="number" name="price_min" class="form-input" min="0" step="1" value="<?php echo $filters['price_min'] !== '' ? e($filters['price_min']) : ''; ?>" placeholder="<?php echo e((int)$priceRange['min']); ?>">
                                </div>
                                <span class="filter-price-separator">–</span>
                                <div class="form-group">
                                    <label class="form-label">Max (€)</label>
                                    <input type="number" name="price_max" class="form-input" min="0" step="1" value="<?php echo $filters['price_max'] !== '' ? e($filters['price_max']) : ''; ?>" placeholder="<?php echo e((int)$priceRange['max']); ?>">
                                </div>
                            </div>
                            <div class="filter-pill-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo __('catalog_filter_apply'); ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Neu im Sortiment -->
                    <label class="filter-pill filter-pill-toggleable <?php echo !empty($filters['new_arrival']) ? 'is-active' : ''; ?>">
                        <input type="checkbox" name="new_arrival" value="1" <?php echo !empty($filters['new_arrival']) ? 'checked' : ''; ?>>
                        <span><?php echo __('catalog_filter_new_arrival'); ?></span>
                    </label>
                </div>

                <?php if ($hasActiveFilters): ?>
                <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-dark btn-sm filter-reset-btn">
                    <?php echo __('catalog_reset_filters'); ?>
                </a>
                <?php endif; ?>

                <span class="catalog-results-count">
                    <?php echo str_replace('{count}', (string)count($jukeboxes), __('catalog_results_count')); ?>
                </span>
            </div>

            <!-- Aktive Filter Chips -->
            <?php if ($hasActiveFilters): ?>
            <div class="catalog-active-filters reveal">
                <?php
                $activeChips = [];
                foreach ($filters['manufacturer'] as $m) {
                    $activeChips[] = ['type' => 'manufacturer', 'value' => $m, 'label' => $m];
                }
                foreach ($filters['size'] as $s) {
                    $activeChips[] = ['type' => 'size', 'value' => $s, 'label' => $s];
                }
                foreach ($filters['color'] as $c) {
                    $activeChips[] = ['type' => 'color', 'value' => $c, 'label' => $c];
                }
                foreach ($filters['function_status'] as $status) {
                    $activeChips[] = ['type' => 'function_status', 'value' => $status, 'label' => __('status_' . $status)];
                }
                foreach ($filters['category_id'] as $catId) {
                    $cat = getCategoryById($catId);
                    $activeChips[] = ['type' => 'category_id', 'value' => $catId, 'label' => $cat ? getCategoryName($cat) : $catId];
                }
                if (!empty($filters['new_arrival'])) {
                    $activeChips[] = ['type' => 'new_arrival', 'value' => '1', 'label' => __('catalog_filter_new_arrival')];
                }
                if ($filters['price_min'] !== '' || $filters['price_max'] !== '') {
                    $priceLabel = '';
                    if ($filters['price_min'] !== '' && $filters['price_max'] !== '') {
                        $priceLabel = '€ ' . (int)$filters['price_min'] . ' – € ' . (int)$filters['price_max'];
                    } elseif ($filters['price_min'] !== '') {
                        $priceLabel = 'ab € ' . (int)$filters['price_min'];
                    } else {
                        $priceLabel = 'bis € ' . (int)$filters['price_max'];
                    }
                    $activeChips[] = ['type' => 'price', 'value' => '', 'label' => $priceLabel];
                }
                ?>
                <?php foreach ($activeChips as $chip): ?>
                <button type="button" class="filter-chip" data-type="<?php echo e($chip['type']); ?>" data-value="<?php echo e($chip['value']); ?>">
                    <span><?php echo e($chip['label']); ?></span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2.5 2.5L9.5 9.5M9.5 2.5L2.5 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!empty($jukeboxes)): ?>
        <?php $dsShowModal = true; include PARTIALS_PATH . 'date-selector.php'; ?>
        <div class="jukebox-grid">
            <?php foreach ($jukeboxes as $jukebox): ?>
            <article class="jukebox-card reveal">
                <div class="jukebox-card-image">
                    <img src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>"
                         alt="<?php echo e(getLocalizedValue($jukebox, 'name')); ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=600&q=80'">
                    <?php if (!empty($jukebox['new_arrival'])): ?>
                    <span class="jukebox-card-badge jukebox-card-badge-new"><?php echo __('catalog_filter_new_arrival'); ?></span>
                    <?php elseif (!empty($jukebox['featured'])): ?>
                    <span class="jukebox-card-badge">Highlight</span>
                    <?php endif; ?>
                    <?php
                    $cat = getCategoryById($jukebox['category_id'] ?? '');
                    if ($cat && !empty($cat['active'])):
                    ?>
                    <span class="jukebox-card-badge" style="top: 36px; background: <?php echo e($cat['color']); ?>; color: #1a1a1a;"><?php echo e(getCategoryName($cat)); ?></span>
                    <?php endif; ?>
                    <div class="jukebox-card-overlay">
                        <a href="<?php echo BASE_URL; ?>jukebox.php?id=<?php echo e($jukebox['id']); ?>" class="btn btn-primary">
                            <?php echo __('view_details'); ?>
                        </a>
                    </div>
                </div>
                <div class="jukebox-card-content">
                    <div class="jukebox-card-header">
                        <div>
                            <h3 class="jukebox-card-title"><?php echo e(getLocalizedValue($jukebox, 'name')); ?></h3>
                            <p class="jukebox-card-subtitle"><?php echo e($jukebox['manufacturer']); ?> <?php echo e($jukebox['model']); ?></p>
                        </div>
                        <div class="jukebox-card-price">
                            <?php echo formatPrice($jukebox['price_day']); ?>
                        </div>
                    </div>
                    <p class="jukebox-card-description">
                        <?php echo e(getLocalizedValue($jukebox, 'short_description')); ?>
                    </p>
                    <div class="jukebox-card-actions">
                        <a href="<?php echo BASE_URL; ?>jukebox.php?id=<?php echo e($jukebox['id']); ?>" class="btn btn-dark btn-sm">
                            <?php echo __('view_details'); ?>
                        </a>
                        <button class="btn btn-primary btn-sm inquiry-btn"
                                data-jukebox-id="<?php echo e($jukebox['id']); ?>"
                                data-text-add="<?php echo e(__('add_to_inquiry')); ?>"
                                data-text-remove="<?php echo e(__('remove_from_inquiry')); ?>">
                            <?php echo __('add_to_inquiry'); ?>
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center reveal" style="padding: var(--space-16) 0;">
            <p style="font-size: var(--text-xl); color: var(--color-gray-500);">
                <?php echo __('catalog_empty'); ?>
            </p>
            <?php if ($hasActiveFilters): ?>
            <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-primary" style="margin-top: var(--space-4);">
                <?php echo __('catalog_reset_filters'); ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-content reveal">
            <h2><?php echo __('cta_title'); ?></h2>
            <p><?php echo __('cta_text'); ?></p>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary btn-lg">
                <?php echo __('cta_button'); ?>
            </a>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
