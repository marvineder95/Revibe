<?php
/**
 * Kundenrezensionen Seite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'reviews';
$metaData = [
    'url' => BASE_URL . 'reviews.php'
];

$formSuccess = false;
$formError = '';
$formErrors = [];
$formData = [
    'name' => '',
    'rating' => 5,
    'title' => '',
    'text' => ''
];

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token prüfen
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $formError = 'csrf';
    }
    
    // Honeypot prüfen (Bot-Falle)
    if (empty($formError) && !empty($_POST['website'])) {
        $formError = 'honeypot';
    }
    
    // Rate-Limiting prüfen
    if (empty($formError) && isFormRateLimited('reviews', 3, 3600)) {
        $formError = 'rate_limit';
    }
    
    if (empty($formError)) {
        $formData['name'] = sanitizeInput($_POST['name'] ?? '');
        $formData['rating'] = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $formData['title'] = sanitizeInput($_POST['title'] ?? '');
        $formData['text'] = sanitizeInput($_POST['text'] ?? '');
        $privacy = isset($_POST['privacy']) ? true : false;
        
        // Validierung
        if (empty($formData['name'])) {
            $formErrors['name'] = __('error_required');
        } elseif (mb_strlen($formData['name']) > 100) {
            $formErrors['name'] = 'Bitte maximal 100 Zeichen.';
        }
        
        if (empty($formData['text'])) {
            $formErrors['text'] = __('error_required');
        } elseif (mb_strlen($formData['text']) > 2000) {
            $formErrors['text'] = 'Bitte maximal 2000 Zeichen.';
        }
        
        if (!$privacy) {
            $formErrors['privacy'] = __('error_privacy');
        }
        
        if (empty($formErrors)) {
            $result = createReview([
                'name' => $formData['name'],
                'rating' => $formData['rating'],
                'title' => $formData['title'],
                'text' => $formData['text'],
                'status' => 'pending'
            ]);
            
            if ($result) {
                $formSuccess = true;
                recordFormSubmission('reviews');
                regenerateCsrfToken();
                $formData = [
                    'name' => '',
                    'rating' => 5,
                    'title' => '',
                    'text' => ''
                ];
            } else {
                $formError = 'save';
            }
        }
    }
}

$reviews = getApprovedReviews();
$reviewCount = getApprovedReviewCount();
$averageRating = getAverageRating();

include PARTIALS_PATH . 'header.php';
?>

<!-- SVG Gradient for star rating -->
<svg width="0" height="0" aria-hidden="true" style="position: absolute; overflow: hidden;">
    <defs>
        <linearGradient id="starGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#0066B1;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#845383;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#E51A22;stop-opacity:1" />
        </linearGradient>
    </defs>
</svg>

<!-- Reviews Section -->
<section class="section reviews-section">
    <div class="container">
        <div class="section-header">
            <h1><?php echo __('reviews_title'); ?></h1>
            <p><?php echo __('reviews_subtitle'); ?></p>
        </div>
        
        <?php if ($reviewCount > 0): ?>
        <div class="reviews-summary reveal">
            <div class="reviews-average">
                <span class="reviews-average-number"><?php echo number_format($averageRating, 1, ',', '.'); ?></span>
                <div class="reviews-stars-static" aria-label="<?php echo $averageRating; ?> von 5 Sternen">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $i <= round($averageRating) ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span class="reviews-count-text"><?php echo $reviewCount === 1 ? __('reviews_count_one') : __('reviews_count', ['count' => $reviewCount]); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Reviews List -->
        <div class="reviews-list-wrapper">
            
            <?php if (empty($reviews)): ?>
                <div class="reviews-empty reveal">
                    <p><?php echo __('reviews_empty'); ?></p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card reveal">
                            <div class="review-header">
                                <div class="review-stars" aria-label="<?php echo (int)$review['rating']; ?> von 5 Sternen">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $i <= (int)$review['rating'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <?php if (!empty($review['title'])): ?>
                                    <h3 class="review-title"><?php echo e($review['title']); ?></h3>
                                <?php endif; ?>
                            </div>
                            <div class="review-body">
                                <p><?php echo nl2br(e($review['text']), false); ?></p>
                            </div>
                            <div class="review-footer">
                                <span class="review-author"><?php echo e($review['name']); ?></span>
                                <span class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Review Form (Collapsible) -->
        <div class="reviews-form-wrapper reveal <?php echo ($formSuccess || $formError || !empty($formErrors)) ? 'is-open' : ''; ?>" id="reviews-form-wrapper">
            <button type="button" class="reviews-form-toggle" aria-expanded="false" aria-controls="reviews-form-content">
                <span><?php echo __('reviews_form_toggle'); ?></span>
                <svg class="reviews-form-toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            
            <div class="reviews-form-content" id="reviews-form-content">
                <p><?php echo __('reviews_intro'); ?></p>
                
                <?php if ($formSuccess): ?>
                    <div class="alert alert-success">
                        <strong><?php echo __('reviews_success_title'); ?></strong>
                        <p><?php echo __('reviews_success_message'); ?></p>
                    </div>
                <?php elseif ($formError === 'rate_limit'): ?>
                    <div class="alert alert-error">
                        <strong><?php echo __('reviews_error_title'); ?></strong>
                        <p><?php echo __('reviews_error_rate_limit'); ?></p>
                    </div>
                <?php elseif ($formError === 'csrf' || $formError === 'honeypot'): ?>
                    <div class="alert alert-error">
                        <strong><?php echo __('reviews_error_title'); ?></strong>
                        <p><?php echo __('reviews_error_message'); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo BASE_URL; ?>reviews.php" class="reviews-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                    <input type="text" name="website" class="reviews-honeypot" tabindex="-1" autocomplete="off">
                    
                    <div class="reviews-form-grid">
                        <div class="form-group">
                            <label for="review-name"><?php echo __('reviews_form_name'); ?></label>
                            <input type="text" id="review-name" name="name" value="<?php echo e($formData['name']); ?>" placeholder="<?php echo __('reviews_form_name_placeholder'); ?>" maxlength="100" required>
                            <?php if (isset($formErrors['name'])): ?>
                                <span class="form-error"><?php echo e($formErrors['name']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('reviews_form_rating'); ?></label>
                            <div class="reviews-rating-input" role="radiogroup" aria-label="<?php echo __('reviews_form_rating'); ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="reviews-rating-star">
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo $formData['rating'] == $i ? 'checked' : ''; ?> required>
                                        <svg class="reviews-star" width="32" height="32" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        <span class="sr-only"><?php echo $i; ?> <?php echo __('reviews_form_rating_label'); ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review-title"><?php echo __('reviews_form_review_title'); ?></label>
                        <input type="text" id="review-title" name="title" value="<?php echo e($formData['title']); ?>" placeholder="<?php echo __('reviews_form_review_title_placeholder'); ?>" maxlength="150">
                    </div>
                    
                    <div class="form-group">
                        <label for="review-text"><?php echo __('reviews_form_text'); ?></label>
                        <textarea id="review-text" name="text" rows="5" placeholder="<?php echo __('reviews_form_text_placeholder'); ?>" maxlength="2000" required><?php echo e($formData['text']); ?></textarea>
                        <?php if (isset($formErrors['text'])): ?>
                            <span class="form-error"><?php echo e($formErrors['text']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group form-checkbox">
                        <label>
                            <input type="checkbox" name="privacy" value="1" required>
                            <span><?php echo __('reviews_form_privacy'); ?></span>
                        </label>
                        <?php if (isset($formErrors['privacy'])): ?>
                            <span class="form-error"><?php echo e($formErrors['privacy']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?php echo __('reviews_form_submit'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const wrapper = document.getElementById('reviews-form-wrapper');
    const toggle = wrapper.querySelector('.reviews-form-toggle');
    const content = document.getElementById('reviews-form-content');
    
    function setOpen(isOpen) {
        wrapper.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    
    toggle.addEventListener('click', function() {
        setOpen(!wrapper.classList.contains('is-open'));
    });
    
    // Falls Server-seitig Fehler/Erfolg vorliegt, automatisch öffnen
    if (wrapper.classList.contains('is-open')) {
        toggle.setAttribute('aria-expanded', 'true');
    }
})();
</script>

<script>
(function() {
    var container = document.querySelector('.reviews-rating-input');
    if (!container) return;

    var stars = Array.from(container.querySelectorAll('.reviews-rating-star'));
    var inputs = stars.map(function(star) { return star.querySelector('input[type="radio"]'); });

    function setHover(value) {
        stars.forEach(function(star, index) {
            star.classList.toggle('is-hover', index < value);
        });
    }

    function updateSelection() {
        var checkedValue = 0;
        inputs.forEach(function(input, index) {
            if (input.checked) checkedValue = index + 1;
        });
        stars.forEach(function(star, index) {
            star.classList.toggle('is-selected', index < checkedValue);
        });
    }

    stars.forEach(function(star, index) {
        star.addEventListener('mouseenter', function() {
            setHover(index + 1);
        });
        var input = inputs[index];
        if (input) {
            input.addEventListener('change', updateSelection);
        }
    });

    container.addEventListener('mouseleave', function() {
        setHover(0);
    });

    updateSelection();
})();
</script>

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

<?php include PARTIALS_PATH . 'footer.php'; ?>
