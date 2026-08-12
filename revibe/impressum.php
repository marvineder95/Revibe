<?php
/**
 * Impressum
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'imprint';
$metaData = [
    'url' => BASE_URL . 'impressum.php'
];

include PARTIALS_PATH . 'header.php';
?>

<!-- Content -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="reveal" style="margin-bottom: var(--space-12);">
                <h2 style="margin-bottom: var(--space-6);"><?php echo __('imprint_responsible'); ?></h2>
                
                <div style="background: var(--color-cream); padding: var(--space-8); border-radius: var(--radius-xl); border: 1px solid rgba(32, 33, 33, 0.1);">
                    <p style="margin-bottom: var(--space-4);">
                        <strong><?php echo __('imprint_company'); ?>:</strong><br>
                        <?php echo COMPANY_NAME; ?>
                    </p>
                    
                    <p style="margin-bottom: var(--space-4);">
                        <strong><?php echo __('imprint_address'); ?>:</strong><br>
                        <?php echo COMPANY_STREET; ?><br>
                        <?php echo COMPANY_ZIP; ?> <?php echo COMPANY_CITY; ?><br>
                        <?php echo COMPANY_COUNTRY; ?>
                    </p>
                    
                    <p style="margin-bottom: var(--space-4);">
                        <strong><?php echo __('imprint_contact'); ?>:</strong><br>
                        Telefon: <?php echo COMPANY_PHONE; ?><br>
                        E-Mail: <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a><br>
                        Web: <a href="https://<?php echo COMPANY_WEB; ?>" target="_blank"><?php echo COMPANY_WEB; ?></a>
                    </p>
                    
                    <p style="margin-bottom: 0;">
                        <strong><?php echo __('imprint_vat'); ?>:</strong><br>
                        ATU12345678
                    </p>
                </div>
            </div>
            
            <div class="reveal" style="margin-bottom: var(--space-12);">
                <h2 style="margin-bottom: var(--space-6);"><?php echo __('imprint_disclaimer'); ?></h2>
                <p><?php echo __('imprint_disclaimer_text'); ?></p>
            </div>
            
            <div class="reveal">
                <h2 style="margin-bottom: var(--space-6);">Haftung für Inhalte</h2>
                <p>Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich.</p>
            </div>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
