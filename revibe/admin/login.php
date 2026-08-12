<?php
/**
 * Admin-Login
 */
require_once '../config/config.php';

setSecurityHeaders();

// Wenn bereits eingeloggt, zum Dashboard
if (isAdminLoggedIn()) {
    redirect('/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('admin_error_csrf');
    } elseif (isLoginLockedOut()) {
        $remaining = ceil(getLoginLockoutTimeRemaining() / 60);
        $error = __('admin_login_lockout', ['minutes' => $remaining]);
    } elseif (loginAdmin($username, $password)) {
        redirect('/admin/dashboard.php');
    } else {
        $error = __('admin_login_error');
    }
}

$lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo e($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_login_title'); ?> | <?php echo COMPANY_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-warm-white);
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-login">
        <div class="admin-login-card">
            <div style="text-align: center; margin-bottom: var(--space-8);">
                <div class="logo" style="justify-content: center;">
                    <span class="logo-icon">🎵</span>
                    <span><?php echo COMPANY_NAME; ?></span>
                </div>
            </div>
            
            <h1 style="text-align: center; font-size: var(--text-2xl); margin-bottom: var(--space-8);">
                <?php echo __('admin_login_title'); ?>
            </h1>
            
            <?php if (ADMIN_SETUP_NOTICE): ?>
            <div style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #22c55e; margin-bottom: 0; font-size: var(--text-sm);">
                    <strong>Erstinstallation:</strong> Ein Admin-Passwort wurde automatisch generiert.<br>
                    Benutzername: <code>admin</code><br>
                    Passwort: <code><?php echo e(ADMIN_SETUP_PASSWORD); ?></code><br>
                    <em style="font-size: 11px;">Bitte notieren Sie sich das Passwort. Diese Meldung erscheint nur einmal.</em>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #ef4444; margin-bottom: 0; font-size: var(--text-sm);"><?php echo e($error); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label for="username" class="form-label"><?php echo __('admin_username'); ?></label>
                    <input type="text" id="username" name="username" class="form-input" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label"><?php echo __('admin_password'); ?></label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-input" style="padding-right: 90px;" required>
                        <button type="button" id="toggle-password" class="btn btn-dark btn-sm" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); padding: 6px 12px; font-size: 12px;">
                            <?php echo getCurrentLanguage() === 'de' ? 'Anzeigen' : 'Show'; ?>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: var(--space-4);">
                    <?php echo __('admin_login_button'); ?>
                </button>
            </form>
            
            <script>
                (function() {
                    var passwordInput = document.getElementById('password');
                    var toggleButton = document.getElementById('toggle-password');
                    if (passwordInput && toggleButton) {
                        toggleButton.addEventListener('click', function() {
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                toggleButton.textContent = <?php echo json_encode(getCurrentLanguage() === 'de' ? 'Verbergen' : 'Hide'); ?>;
                            } else {
                                passwordInput.type = 'password';
                                toggleButton.textContent = <?php echo json_encode(getCurrentLanguage() === 'de' ? 'Anzeigen' : 'Show'); ?>;
                            }
                        });
                    }
                })();
            </script>
            
            <div style="text-align: center; margin-top: var(--space-6);">
                <a href="<?php echo BASE_URL; ?>index.php" style="color: var(--color-gray-500); font-size: var(--text-sm);">
                    ← <?php echo getCurrentLanguage() === 'de' ? 'Zurück zur Website' : 'Back to website'; ?>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
