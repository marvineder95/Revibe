<?php
/**
 * Jukebox-Detailseite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'catalog';

// Jukebox-ID aus URL
$id = $_GET['id'] ?? '';
$jukebox = getJukeboxById($id);

// Wenn nicht gefunden, zurück zum Katalog
if (!$jukebox) {
    redirect(BASE_URL . 'catalog.php');
}

$metaData = [
    'title' => getLocalizedValue($jukebox, 'name') . ' | ' . COMPANY_NAME,
    'description' => getLocalizedValue($jukebox, 'short_description'),
    'image' => getJukeboxImageUrl($jukebox['main_image']),
    'url' => BASE_URL . 'jukebox.php?id=' . e($id)
];

// Verfügbarkeit im aktuell gewählten Mietzeitraum prüfen
$cart = getCart();
$hasRentalDates = !empty($cart['date_start']) && !empty($cart['date_end']);
$isAvailable = !$hasRentalDates || isJukeboxAvailable($jukebox['id'], $cart['date_start'], $cart['date_end']);

include PARTIALS_PATH . 'header.php';
?>

<!-- Detail Section -->
<section class="section">
    <div class="container">
        <div class="detail-grid">
            <!-- Gallery -->
            <?php
                // Alle anzuzeigenden Bilder: Hauptbild + Galerie
                $galleryImages = $jukebox['gallery_images'] ?? [];
                $allImages = [];
                if (!empty($jukebox['main_image'])) {
                    $allImages[] = $jukebox['main_image'];
                }
                foreach ($galleryImages as $img) {
                    if (!empty($img) && $img !== $jukebox['main_image']) {
                        $allImages[] = $img;
                    }
                }
            ?>
            <div class="detail-gallery reveal">
                <div class="detail-main-image" id="mainImageContainer">
                    <img src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>" 
                         alt="<?php echo e(getLocalizedValue($jukebox, 'name')); ?>"
                         id="mainImage"
                         onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=800&q=80'">
                    
                    <?php if (count($allImages) > 1): ?>
                    <button type="button" class="detail-gallery-nav detail-gallery-prev" onclick="navigateGallery(-1)" aria-label="<?php echo e(__('gallery_prev') ?? 'Vorheriges Bild'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="detail-gallery-nav detail-gallery-next" onclick="navigateGallery(1)" aria-label="<?php echo e(__('gallery_next') ?? 'Nächstes Bild'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (count($allImages) > 1): ?>
                <div class="detail-thumbnails">
                    <?php foreach ($allImages as $index => $image): ?>
                        <button type="button" 
                                class="detail-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-image-src="<?php echo e(getJukeboxImageUrl($image)); ?>"
                                onclick="setMainImage('<?php echo e(getJukeboxImageUrl($image)); ?>', this)">
                            <img src="<?php echo e(getJukeboxImageUrl($image)); ?>" 
                                 alt="<?php echo e(getLocalizedValue($jukebox, 'name') . ' ' . ($index + 1)); ?>"
                                 loading="lazy"
                                 onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=200&q=80'">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Info -->
            <div class="detail-info reveal">
                <h1><?php echo e(getLocalizedValue($jukebox, 'name')); ?></h1>

                <!-- Short Description -->
                <p class="detail-short-description">
                    <?php echo e(getLocalizedValue($jukebox, 'short_description')); ?>
                </p>

                <!-- Price & Date Selection -->
                <div class="detail-booking-bar">
                    <div class="detail-price">
                        <div class="detail-price-label"><?php echo __('detail_price'); ?></div>
                        <div class="detail-price-value"><?php echo formatPrice($jukebox['price_day']); ?></div>
                    </div>
                    <div class="detail-date-card">
                        <?php $dsShowModal = false; $dsCompact = true; include PARTIALS_PATH . 'date-selector.php'; ?>
                        <span class="detail-date-card-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </span>
                    </div>
                </div>

                <?php if ($hasRentalDates && !$isAvailable): ?>
                <div class="detail-availability-notice" style="margin-bottom: var(--space-4); padding: var(--space-3); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); color: #ef4444; font-size: var(--text-sm);">
                    <?php echo __('catalog_not_available'); ?>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="detail-actions">
                    <span class="detail-action-border">
                        <a href="<?php echo ($hasRentalDates && !$isAvailable) ? '#' : BASE_URL . 'contact.php?jukebox=' . e($jukebox['id']); ?>"
                           class="btn btn-secondary btn-lg"
                           <?php echo ($hasRentalDates && !$isAvailable) ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                            <?php echo __('detail_inquiry'); ?>
                        </a>
                    </span>
                    <button class="btn btn-primary btn-lg inquiry-btn"
                            data-jukebox-id="<?php echo e($jukebox['id']); ?>"
                            data-text-add="<?php echo e(__('add_to_inquiry')); ?>"
                            data-text-remove="<?php echo e(__('remove_from_inquiry')); ?>"
                            <?php echo ($hasRentalDates && !$isAvailable) ? 'disabled' : ''; ?>>
                        <span class="detail-action-plus">+</span>
                        <?php echo ($hasRentalDates && !$isAvailable) ? __('catalog_not_available') : __('add_to_inquiry'); ?>
                    </button>
                </div>

                <!-- Meta Daten -->
                <div class="detail-meta">
                    <?php if ($jukebox['manufacturer']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_manufacturer'); ?></span>
                        <span class="detail-meta-value"><?php echo e($jukebox['manufacturer']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($jukebox['model']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_model'); ?></span>
                        <span class="detail-meta-value"><?php echo e($jukebox['model']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($jukebox['year']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_year'); ?></span>
                        <span class="detail-meta-value"><?php echo e($jukebox['year']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($jukebox['music_format']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_format'); ?></span>
                        <span class="detail-meta-value"><?php echo e(getLocalizedValue($jukebox, 'music_format')); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_function'); ?></span>
                        <span class="detail-meta-value detail-meta-value-status status-<?php echo e($jukebox['function_status']); ?>">
                            <?php echo getFunctionStatusLabel($jukebox['function_status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Features -->
                <div class="detail-features">
                    <div class="detail-feature">
                        <div class="detail-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
                        </div>
                        <span><?php echo e(__('detail_feature_vinyl')); ?></span>
                    </div>
                    <div class="detail-feature">
                        <div class="detail-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        </div>
                        <span><?php echo e(__('detail_feature_serviced')); ?></span>
                    </div>
                    <div class="detail-feature">
                        <div class="detail-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        </div>
                        <span><?php echo e(__('detail_feature_delivery')); ?></span>
                    </div>
                    <div class="detail-feature">
                        <div class="detail-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <span><?php echo e(__('detail_feature_insured')); ?></span>
                    </div>
                </div>

                <!-- Description / Story -->
                <div class="detail-description">
                    <?php echo nl2br(e(getLocalizedValue($jukebox, 'description'))); ?>
                </div>

                <div style="margin-top: var(--space-8);">
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-dark">
                        ← <?php echo __('detail_back'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Galerie-Bild wechseln
document.querySelectorAll('.detail-thumbnail').forEach(function(thumb) {
    thumb.addEventListener('click', function() {
        var src = this.getAttribute('data-image-src');
        setMainImage(src, this);
    });
});

function setMainImage(src, activeThumb) {
    document.getElementById('mainImage').src = src;

    // Aktiven Thumbnail markieren
    document.querySelectorAll('.detail-thumbnail').forEach(function(t) {
        t.classList.remove('active');
    });
    if (activeThumb) {
        activeThumb.classList.add('active');
    } else {
        document.querySelectorAll('.detail-thumbnail').forEach(function(t) {
            if (t.getAttribute('data-image-src') === src) {
                t.classList.add('active');
            }
        });
    }
}

function navigateGallery(direction) {
    var thumbs = Array.from(document.querySelectorAll('.detail-thumbnail'));
    if (thumbs.length === 0) return;

    var currentIndex = thumbs.findIndex(function(t) {
        return t.classList.contains('active');
    });

    if (currentIndex === -1) currentIndex = 0;

    var newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = thumbs.length - 1;
    if (newIndex >= thumbs.length) newIndex = 0;

    var newThumb = thumbs[newIndex];
    setMainImage(newThumb.getAttribute('data-image-src'), newThumb);
}

// Tastatur-Navigation für Galerie
document.addEventListener('keydown', function(e) {
    if (document.querySelectorAll('.detail-thumbnail').length === 0) return;
    if (e.key === 'ArrowLeft') navigateGallery(-1);
    if (e.key === 'ArrowRight') navigateGallery(1);
});


</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
