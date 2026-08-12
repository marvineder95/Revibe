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

include PARTIALS_PATH . 'header.php';
?>

<!-- Detail Section -->
<section class="section">
    <div class="container">
        <div class="detail-grid">
            <!-- Gallery -->
            <div class="detail-gallery reveal">
                <div class="detail-main-image" id="mainImageContainer">
                    <img src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>" 
                         alt="<?php echo e(getLocalizedValue($jukebox, 'name')); ?>"
                         id="mainImage"
                         onclick="openLightbox()"
                         style="cursor: zoom-in;"
                         onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=800&q=80'">
                </div>
                
                <?php if (!empty($jukebox['gallery_images'])): ?>
                <div class="detail-thumbnails">
                    <div class="detail-thumbnail active" data-image-src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>">
                        <img src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>" alt="">
                    </div>
                    <?php foreach ($jukebox['gallery_images'] as $image): ?>
                    <div class="detail-thumbnail" data-image-src="<?php echo e(getJukeboxImageUrl($image)); ?>">
                        <img src="<?php echo e(getJukeboxImageUrl($image)); ?>" alt="">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Info -->
            <div class="detail-info reveal">
                <h1><?php echo e(getLocalizedValue($jukebox, 'name')); ?></h1>
                
                <?php
                $cat = getCategoryById($jukebox['category_id'] ?? '');
                if ($cat && !empty($cat['active'])):
                ?>
                <span style="display: inline-block; margin-bottom: var(--space-4); padding: var(--space-1) var(--space-3); background: <?php echo e($cat['color']); ?>22; color: <?php echo e($cat['color']); ?>; border-radius: var(--radius-full); font-size: var(--text-sm); border: 1px solid <?php echo e($cat['color']); ?>44;">
                    <?php echo e(getCategoryName($cat)); ?>
                </span>
                <?php endif; ?>
                
                <!-- Price -->
                <div class="detail-price">
                    <div class="detail-price-label"><?php echo __('detail_price'); ?></div>
                    <div class="detail-price-value"><?php echo formatPrice($jukebox['price_day']); ?></div>
                </div>
                
                <?php $dsShowModal = false; include PARTIALS_PATH . 'date-selector.php'; ?>

                <!-- Actions -->
                <div class="detail-actions">
                    <button class="btn btn-primary btn-lg inquiry-btn" 
                            data-jukebox-id="<?php echo e($jukebox['id']); ?>"
                            data-text-add="<?php echo e(__('add_to_inquiry')); ?>"
                            data-text-remove="<?php echo e(__('remove_from_inquiry')); ?>">
                        <?php echo __('add_to_inquiry'); ?>
                    </button>
                    <a href="<?php echo BASE_URL; ?>contact.php?jukebox=<?php echo e($jukebox['id']); ?>" class="btn btn-secondary btn-lg">
                        <?php echo __('detail_inquiry'); ?>
                    </a>
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
                    
                    <?php if ($jukebox['condition']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_condition'); ?></span>
                        <span class="detail-meta-value"><?php echo e(getLocalizedValue($jukebox, 'condition')); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_function'); ?></span>
                        <span class="detail-meta-value"><?php echo getFunctionStatusLabel($jukebox['function_status']); ?></span>
                    </div>
                    
                    <?php if ($jukebox['power_connection']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_power'); ?></span>
                        <span class="detail-meta-value"><?php echo e(getLocalizedValue($jukebox, 'power_connection')); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($jukebox['dimensions']): ?>
                    <div class="detail-meta-item">
                        <span class="detail-meta-label"><?php echo __('detail_size'); ?></span>
                        <span class="detail-meta-value"><?php echo e(getLocalizedValue($jukebox, 'dimensions')); ?></span>
                    </div>
                    <?php endif; ?>
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
        document.getElementById('mainImage').src = src;
        
        // Aktiven Thumbnail markieren
        document.querySelectorAll('.detail-thumbnail').forEach(function(t) {
            t.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Lightbox öffnen
function openLightbox() {
    var img = document.getElementById('mainImage').src;
    window.open(img, '_blank');
}
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>

<?php include PARTIALS_PATH . 'footer.php'; ?>
