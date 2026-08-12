<?php
/**
 * Jukebox löschen
 */
require_once '../config/config.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// CSRF-Token prüfen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
    // GET-Request: Bestätigungsseite anzeigen
    $id = $_GET['id'] ?? '';
    $jukebox = getJukeboxById($id);
    
    if (!$jukebox) {
        redirect('/admin/dashboard.php');
    }
    
    $lang = getCurrentLanguage();
    $pageTitle = __('admin_delete_jukebox');

    include PARTIALS_PATH . 'admin-header.php';
?>
            <div class="admin-card" style="max-width: 500px; margin: 0 auto;">
                <div class="admin-card-body" style="text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: var(--space-4);">⚠️</div>
                    <h2 style="margin-bottom: var(--space-4);">Jukebox löschen?</h2>
                    <p style="margin-bottom: var(--space-6);">
                        Möchten Sie die Jukebox <strong>"<?php echo e($jukebox['name']); ?>"</strong> wirklich löschen?<br>
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo e($jukebox['id']); ?>">
                        <div style="display: flex; gap: var(--space-4); justify-content: center;">
                            <a href="/admin/dashboard.php" class="btn btn-dark">Abbrechen</a>
                            <button type="submit" class="btn btn-primary" style="background: #ef4444;">Ja, löschen</button>
                        </div>
                    </form>
                </div>
            </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
    <?php
    exit;
}

// POST-Request: Löschen durchführen
$id = $_POST['id'] ?? '';

if ($id && deleteJukebox($id)) {
    redirect('/admin/dashboard.php?success=delete');
} else {
    redirect('/admin/dashboard.php?error=delete');
}
