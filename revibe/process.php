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

<!-- Process Hero Section -->
<section class="section section-fullheight process-hero">
    <div class="container">
        <div class="process-hero-content">
            <div class="process-hero-header reveal">
                <span class="process-eyebrow"><?php echo __('process_eyebrow'); ?></span>
                <h1><?php echo __('process_main_title'); ?></h1>
                <p><?php echo __('process_main_subtitle'); ?></p>
            </div>
            
            <div class="process-steps-new">
                <!-- Step 1 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_1_number'); ?></span>
                        <h3><?php echo __('process_step_1_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2 10v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10M2 10l10-6 10 6"/>
                                <path d="M12 22V12"/>
                                <circle cx="12" cy="7" r="2"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_1_text'); ?></p>
                    </div>
                </div>
                
                <!-- Step 2 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_2_number'); ?></span>
                        <h3><?php echo __('process_step_2_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_2_text'); ?></p>
                    </div>
                </div>
                
                <!-- Step 3 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_3_number'); ?></span>
                        <h3><?php echo __('process_step_3_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_3_text'); ?></p>
                    </div>
                </div>
                
                <!-- Step 4 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_4_number'); ?></span>
                        <h3><?php echo __('process_step_4_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="1" y="3" width="15" height="13"/>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                <circle cx="5.5" cy="18.5" r="2.5"/>
                                <circle cx="18.5" cy="18.5" r="2.5"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_4_text'); ?></p>
                    </div>
                </div>
                
                <!-- Step 5 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_5_number'); ?></span>
                        <h3><?php echo __('process_step_5_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_5_text'); ?></p>
                    </div>
                </div>
                
                <!-- Step 6 -->
                <div class="process-step-card reveal">
                    <div class="process-step-header">
                        <span class="process-step-number"><?php echo __('process_step_6_number'); ?></span>
                        <h3><?php echo __('process_step_6_title'); ?></h3>
                    </div>
                    <div class="process-step-body">
                        <div class="process-step-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                            </svg>
                        </div>
                        <p><?php echo __('process_step_6_text'); ?></p>
                    </div>
                </div>
                
            </div>
            
            <div class="process-hero-cta reveal">
                <div class="process-cta-card">
                    <div class="process-cta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="process-cta-text">
                        <h3><?php echo __('process_cta_title'); ?></h3>
                        <p><?php echo __('process_cta_text'); ?></p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary btn-lg">
                        <?php echo __('process_cta_button'); ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
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
