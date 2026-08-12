<?php
/**
 * 404 Fehlerseite
 */
require_once 'config/config.php';

setSecurityHeaders();

$page = 'home';
$metaData = [
    'title' => 'Seite nicht gefunden | ' . COMPANY_NAME,
    'url' => BASE_URL . '404.php'
];

include PARTIALS_PATH . 'header.php';
?>

<section class="section">
    <div class="container">
        <div style="text-align: center; max-width: 500px; margin: 0 auto;">
            <div style="font-size: 6rem; margin-bottom: var(--space-6);">🎵</div>
            <h2 style="margin-bottom: var(--space-4);">
                <?php echo getCurrentLanguage() === 'de' ? 'Die gesuchte Seite existiert nicht.' : 'The requested page does not exist.'; ?>
            </h2>
            <p style="margin-bottom: var(--space-8);">
                <?php echo getCurrentLanguage() === 'de' 
                    ? 'Vielleicht wurde sie verschoben oder gelöscht. Kehren Sie zur Startseite zurück oder entdecken Sie unseren Jukebox-Katalog.' 
                    : 'It may have been moved or deleted. Return to the homepage or discover our jukebox catalog.'; ?>
            </p>
            <div style="display: flex; gap: var(--space-4); justify-content: center;">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                    <?php echo getCurrentLanguage() === 'de' ? 'Zur Startseite' : 'Back to home'; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>catalog.php" class="btn btn-secondary">
                    <?php echo getCurrentLanguage() === 'de' ? 'Zum Katalog' : 'To catalog'; ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include PARTIALS_PATH . 'footer.php'; ?>
