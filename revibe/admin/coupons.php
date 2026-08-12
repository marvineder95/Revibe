<?php
/**
 * Coupons verwalten
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
        deleteCoupon($_GET['delete']);
        $success = 'Coupon gelöscht.';
    } else {
        $error = 'Sicherheitsfehler.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitsfehler.';
    } else {
        $data = [
            'code' => $_POST['code'] ?? '',
            'description' => $_POST['description'] ?? '',
            'discount_percent' => $_POST['discount_percent'] ?? 0,
            'valid_from' => $_POST['valid_from'] ?? '',
            'valid_until' => $_POST['valid_until'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'min_order_value' => $_POST['min_order_value'] ?? 0,
            'reusable' => isset($_POST['reusable']) ? 1 : 0,
            'combinable' => isset($_POST['combinable']) ? 1 : 0
        ];
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        if (saveCoupon($data, $id)) {
            $success = 'Coupon gespeichert.';
        } else {
            $error = 'Fehler beim Speichern (Code bereits vorhanden?).';
        }
    }
}

$coupons = getAllCoupons();
$edit = null;
if (isset($_GET['edit'])) {
    $edit = getCouponById($_GET['edit']);
}

$lang = getCurrentLanguage();
$pageTitle = $lang === 'de' ? 'Coupons' : 'Coupons';

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
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo $edit ? 'Coupon bearbeiten' : 'Neuer Coupon'; ?></h2>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Code *</label>
                                <input type="text" name="code" class="form-input" value="<?php echo e($edit['code'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rabatt (%)</label>
                                <input type="number" name="discount_percent" class="form-input" min="0" max="100" step="0.01" value="<?php echo e($edit['discount_percent'] ?? 0); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Gültig von</label>
                                <input type="date" name="valid_from" class="form-input" value="<?php echo e($edit['valid_from'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gültig bis</label>
                                <input type="date" name="valid_until" class="form-input" value="<?php echo e($edit['valid_until'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mindestbestellwert (€)</label>
                                <input type="number" name="min_order_value" class="form-input" min="0" step="0.01" value="<?php echo e($edit['min_order_value'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Beschreibung (intern)</label>
                            <input type="text" name="description" class="form-input" value="<?php echo e($edit['description'] ?? ''); ?>">
                        </div>
                        <div class="form-row" style="gap: var(--space-6);">
                            <label class="form-checkbox">
                                <input type="checkbox" name="active" <?php echo (!isset($edit) || !empty($edit['active'])) ? 'checked' : ''; ?>>
                                <span>Aktiv</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="reusable" <?php echo (!isset($edit) || !empty($edit['reusable'])) ? 'checked' : ''; ?>>
                                <span>Mehrfach verwendbar</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="combinable" <?php echo (!isset($edit) || !empty($edit['combinable'])) ? 'checked' : ''; ?>>
                                <span>Kombinierbar</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                            <button type="submit" class="btn btn-primary">Speichern</button>
                            <?php if ($edit): ?>
                            <a href="/admin/coupons.php" class="btn btn-dark">Abbrechen</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><h2 style="font-size: var(--text-xl); margin-bottom: 0;">Coupons</h2></div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead><tr><th>Code</th><th>Rabatt</th><th>Gültig</th><th>Status</th><th>Aktionen</th></tr></thead>
                        <tbody>
                            <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td><code><?php echo e($c['code']); ?></code></td>
                                <td><?php echo e($c['discount_percent']); ?> %</td>
                                <td><?php echo e($c['valid_from']); ?> – <?php echo e($c['valid_until']); ?></td>
                                <td><?php echo !empty($c['active']) ? 'Aktiv' : 'Inaktiv'; ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="?edit=<?php echo e($c['id']); ?>" class="admin-btn admin-btn-edit">Bearbeiten</a>
                                        <a href="?delete=<?php echo e($c['id']); ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="admin-btn admin-btn-delete" onclick="return confirm('Löschen?')">Löschen</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
