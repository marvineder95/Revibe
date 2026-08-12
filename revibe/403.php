<?php
/**
 * 403 Fehlerseite
 */
require_once 'config/config.php';

setSecurityHeaders();
http_response_code(403);

$page = 'home';
$metaData = [
    'title' => 'Zugriff verweigert | ' . COMPANY_NAME,
    'url' => BASE_URL . '403.php'
];

include PARTIALS_PATH . 'header.php';
?>

<section class="section">
    <div class="container">
        <div style="text-align: center; max-width: 500px; margin: 0 auto;">
            <div style="font-size: 6rem; margin-bottom: var(--space-6);">🚫</div>
            <h2 style="margin-bottom: var(--space-4);">
                <?php echo getCurrentLanguage() === 'de' ? 'Zugriff nicht erlaubt.' : 'Access not allowed.'; ?>
            </h2>
            <p style="margin-bottom: var(--space-8);">
                <?php echo getCurrentLanguage() === 'de'
                    ? 'Sie haben keine Berechtigung, auf diese Ressource zuzugreifen. Kehren Sie zur Startseite zurück oder entdecken Sie unseren Jukebox-Katalog.'
                    : 'You do not have permission to access this resource. Return to the homepage or discover our jukebox catalog.'; ?>
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
