<?php
/**
 * Landingpage / Startseite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'home';
$metaData = [
    'url' => BASE_URL
];

// Featured Jukeboxen laden
$featuredJukeboxes = getFeaturedJukeboxes(3);

include PARTIALS_PATH . 'header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg"></div>
    
    <div class="container">
        <div class="hero-content">
            <div class="hero-visual">
                <img src="<?php echo ASSETS_URL; ?>images/HeroImgWithoutBackground.png" 
                     alt="<?php echo e(COMPANY_NAME); ?> Jukeboxen" 
                     class="hero-image">
            </div>
            
            <div class="hero-text">
                <span class="hero-label"><?php echo __('hero_label'); ?></span>
                <h1 class="hero-title">
                    <span class="line"><?php echo __('hero_title'); ?></span>
                </h1>
                
                <p class="hero-subtitle">
                    <?php echo __('hero_subtitle'); ?>
                </p>
                
                <div class="hero-actions">
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-primary btn-lg">
                        <?php echo __('hero_cta_primary'); ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-secondary btn-lg">
                        <?php echo __('hero_cta_secondary'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="scroll-indicator">
        <span>Scroll</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12l7 7 7-7"/>
        </svg>
    </div>
</section>

<!-- Featured Jukeboxes Section -->
<section class="section section-fullheight featured">
    <div class="container">
        <div class="section-header reveal">
            <h2><?php echo __('featured_title'); ?></h2>
            <p><?php echo __('featured_subtitle'); ?></p>
        </div>
        
        <?php if (!empty($featuredJukeboxes)): ?>
        <div class="jukebox-grid">
            <?php foreach ($featuredJukeboxes as $jukebox): ?>
            <article class="jukebox-card reveal">
                <div class="jukebox-card-image">
                    <img src="<?php echo getJukeboxImageUrl($jukebox['main_image']); ?>" 
                         alt="<?php echo e(getLocalizedValue($jukebox, 'name')); ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=600&q=80'">
                    <?php if (!empty($jukebox['featured'])): ?>
                    <span class="jukebox-card-badge">Highlight</span>
                    <?php endif; ?>
                    <div class="jukebox-card-overlay">
                        <a href="<?php echo BASE_URL; ?>jukebox.php?id=<?php echo $jukebox['id']; ?>" class="btn btn-primary">
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
                        <a href="<?php echo BASE_URL; ?>jukebox.php?id=<?php echo $jukebox['id']; ?>" class="btn btn-dark btn-sm">
                            <?php echo __('view_details'); ?>
                        </a>
                        <button class="btn btn-primary btn-sm inquiry-btn" 
                                data-jukebox-id="<?php echo $jukebox['id']; ?>"
                                data-text-add="<?php echo __('add_to_inquiry'); ?>"
                                data-text-remove="<?php echo __('remove_from_inquiry'); ?>">
                            <?php echo __('add_to_inquiry'); ?>
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8 reveal">
            <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-secondary btn-lg">
                <?php echo __('featured_cta'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="text-center reveal">
            <p><?php echo __('catalog_empty'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Intro Section (Musikgeschichte) -->
<section class="section section-fullheight intro">
    <div class="container">
        <div class="intro-grid">
            <div class="intro-content reveal">
                <h2><?php echo __('intro_title'); ?></h2>
                <p><?php echo __('intro_text'); ?></p>
            </div>
            
            <div class="intro-features reveal">
                <div class="intro-feature">
                    <div class="intro-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                    <div>
                        <h4><?php echo __('intro_feature_1_title'); ?></h4>
                        <p><?php echo __('intro_feature_1_text'); ?></p>
                    </div>
                </div>
                <div class="intro-feature">
                    <div class="intro-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4><?php echo __('intro_feature_2_title'); ?></h4>
                        <p><?php echo __('intro_feature_2_text'); ?></p>
                    </div>
                </div>
                <div class="intro-feature intro-feature-wide">
                    <div class="intro-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h4><?php echo __('intro_feature_3_title'); ?></h4>
                        <p><?php echo __('intro_feature_3_text'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="section section-fullheight benefits">
    <div class="container">
        <div class="section-header reveal">
            <h2><?php echo __('benefits_title'); ?></h2>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-card reveal">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <h3><?php echo __('benefit_1_title'); ?></h3>
                <p><?php echo __('benefit_1_text'); ?></p>
            </div>
            <div class="benefit-card reveal">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <circle cx="12" cy="12" r="6"></circle>
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                </div>
                <h3><?php echo __('benefit_2_title'); ?></h3>
                <p><?php echo __('benefit_2_text'); ?></p>
            </div>
            <div class="benefit-card reveal">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <h3><?php echo __('benefit_3_title'); ?></h3>
                <p><?php echo __('benefit_3_text'); ?></p>
            </div>
            <div class="benefit-card reveal">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                        <path d="M4 22h16"></path>
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
                    </svg>
                </div>
                <h3><?php echo __('benefit_4_title'); ?></h3>
                <p><?php echo __('benefit_4_text'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="section section-fullheight process">
    <div class="container">
        <div class="section-header reveal">
            <h2><?php echo __('process_title'); ?></h2>
        </div>
        
        <div class="process-steps">
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_1_number'); ?></div>
                <h3><?php echo __('process_step_1_title'); ?></h3>
                <p><?php echo __('process_step_1_text'); ?></p>
            </div>
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_2_number'); ?></div>
                <h3><?php echo __('process_step_2_title'); ?></h3>
                <p><?php echo __('process_step_2_text'); ?></p>
            </div>
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_3_number'); ?></div>
                <h3><?php echo __('process_step_3_title'); ?></h3>
                <p><?php echo __('process_step_3_text'); ?></p>
            </div>
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_4_number'); ?></div>
                <h3><?php echo __('process_step_4_title'); ?></h3>
                <p><?php echo __('process_step_4_text'); ?></p>
            </div>
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_5_number'); ?></div>
                <h3><?php echo __('process_step_5_title'); ?></h3>
                <p><?php echo __('process_step_5_text'); ?></p>
            </div>
            <div class="process-step reveal">
                <div class="process-step-number"><?php echo __('process_step_6_number'); ?></div>
                <h3><?php echo __('process_step_6_title'); ?></h3>
                <p><?php echo __('process_step_6_text'); ?></p>
            </div>
        </div>
        
        <div class="text-center mt-8 reveal">
            <a href="<?php echo BASE_URL; ?>process.php" class="btn btn-secondary">
                <?php echo getCurrentLanguage() === 'de' ? 'Mehr zum Ablauf' : 'More about the process'; ?>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section section-fullheight cta-section">
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

<!-- FAQ Preview Section -->
<section class="section section-fullheight faq">
    <div class="container">
        <div class="section-header reveal">
            <h2><?php echo __('faq_preview_title'); ?></h2>
        </div>
        
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo __('faq_question_1'); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p><?php echo __('faq_answer_1'); ?></p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo __('faq_question_2'); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p><?php echo __('faq_answer_2'); ?></p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo __('faq_question_3'); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p><?php echo __('faq_answer_3'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-8 reveal">
            <a href="<?php echo BASE_URL; ?>faq.php" class="btn btn-secondary">
                <?php echo __('faq_preview_cta'); ?>
            </a>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
