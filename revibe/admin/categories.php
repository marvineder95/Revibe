<?php
/**
 * Kategorien verwalten
 */
require_once '../config/config.php';

setSecurityHeaders();

if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

// Löschen
if (isset($_GET['delete'])) {
    if (validateCsrfToken($_GET['csrf_token'] ?? '')) {
        if (deleteCategory($_GET['delete'])) {
            $success = 'Kategorie gelöscht.';
        } else {
            $error = 'Kategorie konnte nicht gelöscht werden (noch in Verwendung?).';
        }
    } else {
        $error = 'Sicherheitsfehler.';
    }
}

// Speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitsfehler.';
    } else {
        $data = [
            'name' => $_POST['name'] ?? '',
            'name_en' => $_POST['name_en'] ?? '',
            'description' => $_POST['description'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'color' => $_POST['color'] ?? '#0066B1',
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'] ?? 0
        ];
        $id = $_POST['id'] ?? null;
        if (saveCategory($data, $id)) {
            $success = 'Kategorie gespeichert.';
        } else {
            $error = 'Fehler beim Speichern.';
        }
    }
}

$categories = getAllCategories();
$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = getCategoryById($_GET['edit']);
}

$lang = getCurrentLanguage();
$pageTitle = $lang === 'de' ? 'Kategorien' : 'Categories';

include PARTIALS_PATH . 'admin-header.php';
?>

            <?php if ($success): ?>
            <div style="padding: var(--space-4); background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color:#22c55e; margin-bottom:0;"><?php echo e($success); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="padding: var(--space-4); background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color:#ef4444; margin-bottom:0;"><?php echo e($error); ?></p>
            </div>
            <?php endif; ?>

            <div class="admin-card" style="margin-bottom: var(--space-8);">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo $editCategory ? 'Kategorie bearbeiten' : 'Neue Kategorie'; ?></h2>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo e($editCategory['id']); ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Name (DE) *</label>
                                <input type="text" name="name" class="form-input" value="<?php echo e($editCategory['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Name (EN)</label>
                                <input type="text" name="name_en" class="form-input" value="<?php echo e($editCategory['name_en'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Farbe</label>
                                <input type="color" name="color" class="form-input" style="height: 40px; padding: 2px;" value="<?php echo e($editCategory['color'] ?? '#0066B1'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sortierung</label>
                                <input type="number" name="sort_order" class="form-input" value="<?php echo e($editCategory['sort_order'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Beschreibung (DE)</label>
                                <textarea name="description" class="form-textarea" rows="2"><?php echo e($editCategory['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Beschreibung (EN)</label>
                                <textarea name="description_en" class="form-textarea" rows="2"><?php echo e($editCategory['description_en'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="active" <?php echo (!isset($editCategory) || !empty($editCategory['active'])) ? 'checked' : ''; ?>>
                                <span>Aktiv</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                            <button type="submit" class="btn btn-primary">Speichern</button>
                            <?php if ($editCategory): ?>
                            <a href="/admin/categories.php" class="btn btn-dark">Abbrechen</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;">Kategorien</h2>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Name</th><th>Farbe</th><th>Sortierung</th><th>Status</th><th>Aktionen</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo e($cat['name']); ?></td>
                                <td><span style="display:inline-block;width:20px;height:20px;background:<?php echo e($cat['color']); ?>;border-radius:4px;"></span></td>
                                <td><?php echo e($cat['sort_order']); ?></td>
                                <td><?php echo !empty($cat['active']) ? 'Aktiv' : 'Inaktiv'; ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="?edit=<?php echo e($cat['id']); ?>" class="admin-btn admin-btn-edit">Bearbeiten</a>
                                        <a href="?delete=<?php echo e($cat['id']); ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="admin-btn admin-btn-delete" onclick="return confirm('Löschen?')">Löschen</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
