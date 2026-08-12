<?php
/**
 * Rabattregeln verwalten
 */
require_once '../config/config.php';

setSecurityHeaders();

if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

if (isset($_GET['delete'])) {
    if (validateCsrfToken($_GET['csrf_token'] ?? '')) {
        deleteDiscountRule($_GET['delete']);
        $success = 'Rabattregel gelöscht.';
    } else {
        $error = 'Sicherheitsfehler.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitsfehler.';
    } else {
        $data = [
            'type' => $_POST['type'] ?? 'duration',
            'threshold' => $_POST['threshold'] ?? 1,
            'discount_percent' => $_POST['discount_percent'] ?? 0,
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'] ?? 0
        ];
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        if (saveDiscountRule($data, $id)) {
            $success = 'Rabattregel gespeichert.';
        } else {
            $error = 'Fehler beim Speichern.';
        }
    }
}

$rules = getAllDiscountRules(null, false);
$edit = null;
if (isset($_GET['edit'])) {
    $edit = getDiscountRuleById($_GET['edit']);
}

$lang = getCurrentLanguage();
$pageTitle = $lang === 'de' ? 'Rabatte' : 'Discounts';

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
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo $edit ? 'Rabattregel bearbeiten' : 'Neue Rabattregel'; ?></h2>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Rabattart</label>
                                <select name="type" class="form-select">
                                    <option value="duration" <?php echo ($edit['type'] ?? '') === 'duration' ? 'selected' : ''; ?>>Mietdauer-Rabatt</option>
                                    <option value="quantity" <?php echo ($edit['type'] ?? '') === 'quantity' ? 'selected' : ''; ?>>Mengenrabatt</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ab (Tage / Stück)</label>
                                <input type="number" name="threshold" class="form-input" min="1" value="<?php echo e($edit['threshold'] ?? 1); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rabatt (%)</label>
                                <input type="number" name="discount_percent" class="form-input" min="0" max="100" step="0.01" value="<?php echo e($edit['discount_percent'] ?? 0); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sortierung</label>
                                <input type="number" name="sort_order" class="form-input" value="<?php echo e($edit['sort_order'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="active" <?php echo (!isset($edit) || !empty($edit['active'])) ? 'checked' : ''; ?>>
                                <span>Aktiv</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                            <button type="submit" class="btn btn-primary">Speichern</button>
                            <?php if ($edit): ?>
                            <a href="/admin/discounts.php" class="btn btn-dark">Abbrechen</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><h2 style="font-size: var(--text-xl); margin-bottom: 0;">Rabattregeln</h2></div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead><tr><th>Art</th><th>Ab</th><th>Rabatt</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                            <?php foreach ($rules as $r): ?>
                            <tr>
                                <td><?php echo $r['type'] === 'duration' ? 'Mietdauer' : 'Menge'; ?></td>
                                <td><?php echo e($r['threshold']); ?> <?php echo $r['type'] === 'duration' ? 'Tage' : 'Stück'; ?></td>
                                <td><?php echo e($r['discount_percent']); ?> %</td>
                                <td><?php echo !empty($r['active']) ? 'Aktiv' : 'Inaktiv'; ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="?edit=<?php echo e($r['id']); ?>" class="admin-btn admin-btn-edit">Bearbeiten</a>
                                        <a href="?delete=<?php echo e($r['id']); ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="admin-btn admin-btn-delete" onclick="return confirm('Löschen?')">Löschen</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
