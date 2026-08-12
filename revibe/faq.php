<?php
/**
 * FAQ Seite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'faq';
$metaData = [
    'url' => BASE_URL . 'faq.php'
];

// FAQ-Fragen organisieren
$faqCategories = [
    'general' => [
        'title' => __('faq_category_general'),
        'questions' => [1, 4, 5]
    ],
    'technical' => [
        'title' => __('faq_category_technical'),
        'questions' => [2, 6, 7, 8]
    ],
    'pricing' => [
        'title' => __('faq_category_pricing'),
        'questions' => [3]
    ]
];

include PARTIALS_PATH . 'header.php';
?>

<!-- FAQ Section -->
<section class="section faq">
    <div class="container">
        <?php foreach ($faqCategories as $category): ?>
        <div class="reveal" style="margin-bottom: var(--space-12);">
            <h3 style="margin-bottom: var(--space-6); color: var(--color-primary);">
                <?php echo $category['title']; ?>
            </h3>
            <div class="faq-list">
                <?php foreach ($category['questions'] as $num): ?>
                <div class="faq-item">
                    <button class="faq-question">
                        <?php echo __('faq_question_' . $num); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p><?php echo __('faq_answer_' . $num); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-content reveal">
            <h2><?php echo __('faq_cta_title'); ?></h2>
            <p><?php echo __('faq_cta_text'); ?></p>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary btn-lg">
                <?php echo __('faq_cta_button'); ?>
            </a>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
