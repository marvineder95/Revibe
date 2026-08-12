<?php
/**
 * Globale Einstellungen
 */
require_once '../config/config.php';

setSecurityHeaders();

if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitsfehler. Bitte laden Sie die Seite neu.';
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'update_api_key') {
            setSetting('google_maps_api_key', trim($_POST['google_maps_api_key'] ?? ''));
            unset($_SESSION['transport_cache']);
            $success = 'API-Key wurde aktualisiert.';
        } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = __('admin_error_password_empty');
            } elseif (!password_verify($currentPassword, ADMIN_PASSWORD_HASH)) {
                $error = __('admin_error_password_current_wrong');
            } elseif ($newPassword !== $confirmPassword) {
                $error = __('admin_error_password_mismatch');
            } elseif (strlen($newPassword) < 8) {
                $error = __('admin_error_password_too_short');
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $adminConfigFile = DATA_PATH . 'admin_config.php';
                $configContent = "<?php\n" .
                    "// AUTOMATISCH GENERIERT - NICHT MANUELL BEARBEITEN\n" .
                    "// Zuletzt aktualisiert: " . date('Y-m-d H:i:s') . "\n" .
                    "define('ADMIN_USERNAME', '" . addslashes(ADMIN_USERNAME) . "');\n" .
                    "define('ADMIN_PASSWORD_HASH', '" . $newHash . "');\n";

                if (file_put_contents($adminConfigFile, $configContent) !== false) {
                    chmod($adminConfigFile, 0600);
                    $success = __('admin_success_password_changed');
                } else {
                    $error = __('admin_error_save');
                }
            }
        } else {
            $fields = [
                'tax_rate',
                'transport_price_per_km',
                'transport_worker_hourly_rate',
                'transport_worker_count',
                'transport_setup_fee',
                'warehouse_address',
                'contract_fee_enabled',
                'contract_fee_percent'
            ];
            
            foreach ($fields as $field) {
                setSetting($field, $_POST[$field] ?? '');
            }

            // Transport-Cache leeren, damit API-Änderungen sofort wirksam werden
            unset($_SESSION['transport_cache']);

            $success = 'Einstellungen gespeichert.';
        }
    }
}

$settings = getAllSettings();
$lang = getCurrentLanguage();

// Google Maps API-Verbindung testen, wenn ein Key hinterlegt ist
$apiTest = null;
if (!empty($settings['google_maps_api_key'])) {
    $apiTest = testGoogleMapsApiConnection($settings['google_maps_api_key'], $settings['warehouse_address']);
}

$lang = getCurrentLanguage();
$pageTitle = $lang === 'de' ? 'Einstellungen' : 'Settings';

include PARTIALS_PATH . 'admin-header.php';
?>

                <div class="admin-page-header">
                    <div>
                        <h1 class="admin-page-title"><?php echo $lang === 'de' ? 'Einstellungen' : 'Settings'; ?></h1>
                        <p class="admin-page-subtitle"><?php echo $lang === 'de' ? 'Globale Konfiguration, Steuer, Transport und Sicherheit' : 'Global configuration, tax, transport and security'; ?></p>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo $lang === 'de' ? 'Globale Einstellungen' : 'Global Settings'; ?></h2>
                    </div>
                <div class="admin-card-body">
                    <?php if ($success): ?>
                    <div style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                        <p style="color: #22c55e; margin-bottom: 0;"><?php echo e($success); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                        <p style="color: #ef4444; margin-bottom: 0;"><?php echo e($error); ?></p>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div style="display: grid; gap: var(--space-8);">
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Steuer & Preise</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">USt-Satz (%)</label>
                                        <input type="number" name="tax_rate" class="form-input" step="0.01" value="<?php echo e($settings['tax_rate']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Vertragsgebühr (GebG)</h3>
                                <div class="form-row">
                                    <div class="form-group" style="display: flex; align-items: center; gap: var(--space-2);">
                                        <input type="hidden" name="contract_fee_enabled" value="0">
                                        <input type="checkbox" name="contract_fee_enabled" id="contract_fee_enabled" value="1" <?php echo !empty($settings['contract_fee_enabled']) && (int)$settings['contract_fee_enabled'] === 1 ? 'checked' : ''; ?>>
                                        <label for="contract_fee_enabled" class="form-label" style="margin-bottom: 0;">Vertragsgebühr anwenden</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Satz (%)</label>
                                        <input type="number" name="contract_fee_percent" class="form-input" step="0.01" value="<?php echo e($settings['contract_fee_percent']); ?>">
                                    </div>
                                </div>
                                <p style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                    Hinweis: § 33 Tarifpunkt 5 Gebührengesetz 1957 – 1 % des Bruttowertes des Mietvertrags.
                                </p>
                            </div>

                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Transportkosten</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Preis pro km (€)</label>
                                        <input type="number" name="transport_price_per_km" class="form-input" step="0.01" value="<?php echo e($settings['transport_price_per_km']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Stundensatz Mitarbeiter (€)</label>
                                        <input type="number" name="transport_worker_hourly_rate" class="form-input" step="0.01" value="<?php echo e($settings['transport_worker_hourly_rate']); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Anzahl Mitarbeiter</label>
                                        <input type="number" name="transport_worker_count" class="form-input" min="1" value="<?php echo e($settings['transport_worker_count']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Fixe Aufbau-/Abholungspauschale (€)</label>
                                        <input type="number" name="transport_setup_fee" class="form-input" step="0.01" value="<?php echo e($settings['transport_setup_fee']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Google Maps API</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">API-Key</label>
                                        <div style="display: flex; gap: var(--space-3); align-items: stretch;">
                                            <input type="text" class="form-input" value="<?php echo !empty($settings['google_maps_api_key']) ? '••••••••••••' : ''; ?>" placeholder="Kein API-Key hinterlegt" readonly style="flex: 1; background: var(--color-cream);">
                                            <button type="button" class="btn btn-primary" onclick="openApiKeyModal()">Aktualisieren</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Lager-Adresse (Startpunkt)</label>
                                        <input type="text" name="warehouse_address" class="form-input" value="<?php echo e($settings['warehouse_address']); ?>">
                                    </div>
                                </div>
                                <p style="font-size: var(--text-sm); color: var(--color-gray-500);">
                                    Hinweis: Ohne gültigen API-Key werden Transportkosten als „individuell berechnet“ angezeigt.
                                </p>
                                <?php if ($apiTest !== null): ?>
                                <p style="font-size: var(--text-sm); margin-top: var(--space-2); margin-bottom: 0;">
                                    <strong>API-Status:</strong>
                                    <?php if ($apiTest['ok']): ?>
                                    <span style="color: #22c55e;">✓ Verbindung OK</span>
                                    <?php else: ?>
                                    <span style="color: #ef4444;">✗ Fehler: <?php echo e($apiTest['status']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-8); padding-top: var(--space-8); border-top: 1px solid var(--color-gray-700);">
                            <button type="submit" class="btn btn-primary">Speichern</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Passwort ändern -->
            <div class="admin-card" style="margin-top: var(--space-8);">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_settings_password_title'); ?></h2>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="/admin/settings.php" style="max-width: 480px;">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <div class="form-group" style="margin-bottom: var(--space-4);">
                            <label class="form-label"><?php echo __('admin_settings_password_current'); ?></label>
                            <div class="password-toggle-wrapper" style="position: relative;">
                                <input type="password" name="current_password" class="form-input" style="padding-right: 90px;" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: var(--space-4);">
                            <label class="form-label"><?php echo __('admin_settings_password_new'); ?></label>
                            <div class="password-toggle-wrapper" style="position: relative;">
                                <input type="password" name="new_password" class="form-input" minlength="8" style="padding-right: 90px;" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: var(--space-6);">
                            <label class="form-label"><?php echo __('admin_settings_password_confirm'); ?></label>
                            <div class="password-toggle-wrapper" style="position: relative;">
                                <input type="password" name="confirm_password" class="form-input" minlength="8" style="padding-right: 90px;" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo __('admin_settings_password_button'); ?></button>
                    </form>
                    <script>
                        (function() {
                            var wrappers = document.querySelectorAll('.password-toggle-wrapper');
                            var showText = <?php echo json_encode(getCurrentLanguage() === 'de' ? 'Anzeigen' : 'Show'); ?>;
                            var hideText = <?php echo json_encode(getCurrentLanguage() === 'de' ? 'Verbergen' : 'Hide'); ?>;
                            wrappers.forEach(function(wrapper) {
                                var input = wrapper.querySelector('input[type="password"], input[type="text"]');
                                if (!input) return;
                                var button = document.createElement('button');
                                button.type = 'button';
                                button.textContent = showText;
                                button.className = 'btn btn-dark btn-sm';
                                button.style.cssText = 'position: absolute; right: 8px; top: 50%; transform: translateY(-50%); padding: 6px 12px; font-size: 12px;';
                                button.addEventListener('click', function() {
                                    if (input.type === 'password') {
                                        input.type = 'text';
                                        button.textContent = hideText;
                                    } else {
                                        input.type = 'password';
                                        button.textContent = showText;
                                    }
                                });
                                wrapper.appendChild(button);
                            });
                        })();
                    </script>
                </div>
            </div>

    <!-- API-Key Modal -->
    <div id="apiKeyModal" class="admin-modal">
        <div class="admin-modal-overlay" onclick="closeApiKeyModal()"></div>
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Google Maps API-Key aktualisieren</h3>
                <button type="button" class="admin-modal-close" onclick="closeApiKeyModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_api_key">
                <div class="admin-modal-body">
                    <div class="form-group">
                        <label class="form-label">Neuer API-Key</label>
                        <input type="text" name="google_maps_api_key" class="form-input" placeholder="Neuen API-Key eingeben" required>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeApiKeyModal()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
    </main>

    <script>
        function openApiKeyModal() {
            document.getElementById('apiKeyModal').classList.add('active');
        }
        function closeApiKeyModal() {
            document.getElementById('apiKeyModal').classList.remove('active');
        }
    </script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
