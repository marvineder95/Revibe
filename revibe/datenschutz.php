<?php
/**
 * Datenschutzerklärung
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'privacy';
$metaData = [
    'url' => BASE_URL . 'datenschutz.php'
];

include PARTIALS_PATH . 'header.php';
?>

<!-- Content -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="reveal" style="margin-bottom: var(--space-12);">
                <p style="font-size: var(--text-lg);"><?php echo __('privacy_intro'); ?></p>
            </div>
            
            <div class="reveal" style="margin-bottom: var(--space-12);">
                <h2 style="margin-bottom: var(--space-6);"><?php echo __('privacy_data_title'); ?></h2>
                <p><?php echo __('privacy_data_text'); ?></p>
                
                <h3 style="margin: var(--space-8) 0 var(--space-4);">Kontaktformular</h3>
                <p>Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert. Diese Daten geben wir nicht ohne Ihre Einwilligung weiter.</p>
                
                <h3 style="margin: var(--space-8) 0 var(--space-4);">Cookies</h3>
                <p>Unsere Website verwendet Cookies. Dies sind kleine Textdateien, die Ihr Browser automatisch erstellt und die auf Ihrem Endgerät gespeichert werden. Cookies richten auf Ihrem Rechner keinen Schaden an und enthalten keine Viren.</p>
            </div>
            
            <div class="reveal" style="margin-bottom: var(--space-12);">
                <h2 style="margin-bottom: var(--space-6);"><?php echo __('privacy_rights_title'); ?></h2>
                <p><?php echo __('privacy_rights_text'); ?></p>
                
                <ul style="list-style: disc; padding-left: var(--space-6); margin-top: var(--space-4);">
                    <li style="margin-bottom: var(--space-2);">Auskunft über Ihre bei uns gespeicherten Daten</li>
                    <li style="margin-bottom: var(--space-2);">Berichtigung unrichtiger Daten</li>
                    <li style="margin-bottom: var(--space-2);">Löschung Ihrer Daten</li>
                    <li style="margin-bottom: var(--space-2);">Einschränkung der Datenverarbeitung</li>
                    <li style="margin-bottom: var(--space-2);">Datenübertragbarkeit</li>
                    <li>Widerspruch gegen die Datenverarbeitung</li>
                </ul>
            </div>
            
            <div class="reveal">
                <h2 style="margin-bottom: var(--space-6);"><?php echo __('privacy_contact_title'); ?></h2>
                <p><?php echo __('privacy_contact_text'); ?></p>
                <p style="margin-top: var(--space-4);">
                    <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
