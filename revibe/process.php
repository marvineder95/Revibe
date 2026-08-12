<?php
/**
 * Mietablauf Seite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'process';
$metaData = [
    'url' => BASE_URL . 'process.php'
];

include PARTIALS_PATH . 'header.php';
?>

<!-- Process Detail Section -->
<section class="section">
    <div class="container">
        <div class="reveal" style="max-width: 800px; margin: 0 auto var(--space-12);">
            <p style="font-size: var(--text-lg); text-align: center;">
                <?php echo __('process_detail_intro'); ?>
            </p>
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
    </div>
</section>

<!-- Delivery Info Section -->
<section class="section" style="background: var(--color-light-muted);">
    <div class="container">
        <div class="intro-grid">
            <div class="reveal">
                <h2><?php echo __('process_delivery_title'); ?></h2>
                <p><?php echo __('process_delivery_text'); ?></p>
            </div>
            <div class="reveal">
                <h2><?php echo __('process_timing_title'); ?></h2>
                <p><?php echo __('process_timing_text'); ?></p>
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
