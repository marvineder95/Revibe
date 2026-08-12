<?php
/**
 * Über uns Seite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'about';
$metaData = [
    'url' => BASE_URL . 'about.php'
];

include PARTIALS_PATH . 'header.php';
?>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="intro-grid intro-grid-centered">
            <div class="intro-content reveal">
                <p><?php echo __('about_text_1'); ?></p>
                <p><?php echo __('about_text_2'); ?></p>
                <p><?php echo __('about_text_3'); ?></p>
            </div>
            
            <div class="intro-visual reveal">
                <div class="intro-image">
                    <img src="<?php echo ASSETS_URL; ?>images/HeroImgWithoutBackground.png" 
                         alt="<?php echo getCurrentLanguage() === 'de' ? 'Jukebox von Revibe' : 'Revibe Jukebox'; ?>" 
                         onerror="this.src='https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=800&q=80'">
                </div>
            </div>
        </div>
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

<?php include PARTIALS_PATH . 'footer.php'; ?>
