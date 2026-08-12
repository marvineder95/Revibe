<?php
/**
 * Admin-Dashboard
 */
require_once '../config/config.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// Erfolgsmeldungen
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Jukeboxen laden
$jukeboxes = getAllJukeboxes();

// Statistiken laden
$openOffers = countOffersByStatus('pending');
$acceptedOffers = countOffersByStatus('accepted');
$openInvoices = countInvoicesByStatus('open');
$totalRevenue = getTotalRevenue();
$openRevenue = getOpenRevenue();

// Aktuelle Angebote und Rechnungen für die Übersicht
$recentOffers = getAllOffers(5);
$recentInvoices = getAllInvoices(5);

$lang = getCurrentLanguage();
$pageTitle = __('admin_dashboard_title');

include PARTIALS_PATH . 'admin-header.php';
?>

                <?php if ($success): ?>
                <div class="alert alert-success" style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                    <p style="color: #22c55e; margin-bottom: 0;">
                        <?php
                        $galleryCount = isset($_GET['gallery']) ? (int)$_GET['gallery'] : 0;
                        if ($success === 'create' && $galleryCount > 0) {
                            echo e(__('admin_success_' . $success)) . ' (mit ' . $galleryCount . ' Galeriebild' . ($galleryCount > 1 ? 'ern' : '') . ')';
                        } elseif ($success === 'update' && $galleryCount > 0) {
                            echo e(__('admin_success_' . $success)) . ' (mit ' . $galleryCount . ' Galeriebild' . ($galleryCount > 1 ? 'ern' : '') . ')';
                        } else {
                            echo e(__('admin_success_' . $success));
                        }
                        ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger" style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                    <p style="color: #ef4444; margin-bottom: 0;"><?php echo e(__('admin_error_' . $error)); ?></p>
                </div>
                <?php endif; ?>

                <div class="admin-page-header">
                    <div>
                        <h1 class="admin-page-title"><?php echo __('admin_dashboard_title'); ?></h1>
                        <p class="admin-page-subtitle"><?php echo $lang === 'de' ? 'Übersicht über Jukeboxen, Angebote und Rechnungen' : 'Overview of jukeboxes, offers and invoices'; ?></p>
                    </div>
                    <div class="admin-page-actions">
                        <a href="/admin/create.php" class="btn btn-primary">
                            + <?php echo __('admin_create_jukebox'); ?>
                        </a>
                    </div>
                </div>

                <!-- Statistiken -->
                <div class="admin-stats-grid">
                    <a href="/admin/offers.php" class="admin-stat-card">
                        <div class="admin-stat-icon amber">📄</div>
                        <div class="admin-stat-content">
                            <div class="admin-stat-value"><?php echo (int)$openOffers; ?></div>
                            <div class="admin-stat-label"><?php echo __('admin_open_offers'); ?></div>
                        </div>
                    </a>
                    <a href="/admin/offers.php" class="admin-stat-card">
                        <div class="admin-stat-icon green">✓</div>
                        <div class="admin-stat-content">
                            <div class="admin-stat-value"><?php echo (int)$acceptedOffers; ?></div>
                            <div class="admin-stat-label"><?php echo __('admin_accepted_offers'); ?></div>
                        </div>
                    </a>
                    <a href="/admin/invoices.php" class="admin-stat-card">
                        <div class="admin-stat-icon red">💶</div>
                        <div class="admin-stat-content">
                            <div class="admin-stat-value"><?php echo (int)$openInvoices; ?></div>
                            <div class="admin-stat-label"><?php echo __('admin_open_invoices'); ?></div>
                        </div>
                    </a>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon blue">€</div>
                        <div class="admin-stat-content">
                            <div class="admin-stat-value"><?php echo formatMoney($totalRevenue); ?></div>
                            <div class="admin-stat-label"><?php echo __('admin_total_revenue'); ?></div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon purple">⏳</div>
                        <div class="admin-stat-content">
                            <div class="admin-stat-value"><?php echo formatMoney($openRevenue); ?></div>
                            <div class="admin-stat-label"><?php echo __('admin_open_revenue'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Schnellzugriff -->
                <div class="admin-quick-actions">
                    <a href="/admin/create.php" class="admin-quick-action">
                        <div class="admin-quick-action-icon">➕</div>
                        <span class="admin-quick-action-text"><?php echo $lang === 'de' ? 'Neue Jukebox' : 'New Jukebox'; ?></span>
                    </a>
                    <a href="/admin/categories.php" class="admin-quick-action">
                        <div class="admin-quick-action-icon">🏷️</div>
                        <span class="admin-quick-action-text"><?php echo $lang === 'de' ? 'Kategorien' : 'Categories'; ?></span>
                    </a>
                    <a href="/admin/discounts.php" class="admin-quick-action">
                        <div class="admin-quick-action-icon">📉</div>
                        <span class="admin-quick-action-text"><?php echo $lang === 'de' ? 'Rabatte' : 'Discounts'; ?></span>
                    </a>
                    <a href="/admin/settings.php" class="admin-quick-action">
                        <div class="admin-quick-action-icon">⚙️</div>
                        <span class="admin-quick-action-text"><?php echo $lang === 'de' ? 'Einstellungen' : 'Settings'; ?></span>
                    </a>
                </div>

                <div class="admin-section-grid">
                    <!-- Jukeboxen -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-section-title"><?php echo __('admin_jukeboxes_title'); ?></h2>
                            <a href="/admin/create.php" class="btn btn-primary btn-sm">+ <?php echo $lang === 'de' ? 'Hinzufügen' : 'Add'; ?></a>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <?php if (!empty($jukeboxes)): ?>
                            <div style="overflow-x: auto;">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;"><?php echo $lang === 'de' ? 'Bild' : 'Image'; ?></th>
                                            <th><?php echo $lang === 'de' ? 'Name' : 'Name'; ?></th>
                                            <th><?php echo $lang === 'de' ? 'Hersteller' : 'Manufacturer'; ?></th>
                                            <th><?php echo $lang === 'de' ? 'Preis/Tag' : 'Price/Day'; ?></th>
                                            <th><?php echo __('admin_status'); ?></th>
                                            <th style="width: 120px;"><?php echo __('admin_actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jukeboxes as $jukebox): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo getJukeboxImageUrl($jukebox['main_image']); ?>"
                                                     alt=""
                                                     style="width: 56px; height: 56px; object-fit: cover; border-radius: var(--radius-md);"
                                                     onerror="this.src='https://via.placeholder.com/60x60/242424/0066B1?text=JB'">
                                            </td>
                                            <td>
                                                <strong><?php echo e($jukebox['name']); ?></strong>
                                                <?php if (!empty($jukebox['featured'])): ?>
                                                <span style="display: inline-block; margin-left: var(--space-2); padding: 2px 8px; background: var(--gradient-brand); color: var(--color-light); font-size: 10px; border-radius: var(--radius-full); text-transform: uppercase;">Highlight</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($jukebox['manufacturer']); ?></td>
                                            <td><?php echo number_format($jukebox['price_day'], 0, ',', '.'); ?> €</td>
                                            <td>
                                                <?php if ($jukebox['function_status'] === 'working'): ?>
                                                <span class="admin-status admin-status-success"><?php echo getFunctionStatusLabel($jukebox['function_status']); ?></span>
                                                <?php else: ?>
                                                <span class="admin-status admin-status-warning"><?php echo getFunctionStatusLabel($jukebox['function_status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-actions">
                                                    <a href="/admin/edit.php?id=<?php echo $jukebox['id']; ?>" class="admin-btn admin-btn-edit"><?php echo __('btn_edit'); ?></a>
                                                    <a href="/admin/delete.php?id=<?php echo $jukebox['id']; ?>" class="admin-btn admin-btn-delete" onclick="return confirm('<?php echo __('admin_delete_confirm'); ?>')"><?php echo __('btn_delete'); ?></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="admin-empty-state">
                                <div class="admin-empty-state-icon">🎵</div>
                                <p><?php echo __('admin_no_jukeboxes'); ?></p>
                                <a href="/admin/create.php" class="btn btn-primary"><?php echo __('admin_create_jukebox'); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aktuelle Angebote -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-section-title"><?php echo $lang === 'de' ? 'Neueste Angebote' : 'Latest Offers'; ?></h2>
                            <a href="/admin/offers.php" class="btn btn-dark btn-sm"><?php echo $lang === 'de' ? 'Alle' : 'All'; ?></a>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <?php if (!empty($recentOffers)): ?>
                            <div style="overflow-x: auto;">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('admin_offer_number'); ?></th>
                                            <th><?php echo __('admin_status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOffers as $offer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($offer['offer_number']); ?></strong>
                                                <br>
                                                <span style="color: var(--color-gray-500); font-size: var(--text-xs);">
                                                    <?php echo e(trim(($offer['firstname'] ?? '') . ' ' . ($offer['lastname'] ?? ''))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($offer['status'] === 'pending'): ?>
                                                <span class="admin-status admin-status-warning"><?php echo __('admin_offer_status_pending'); ?></span>
                                                <?php elseif ($offer['status'] === 'accepted'): ?>
                                                <span class="admin-status admin-status-success"><?php echo __('admin_offer_status_accepted'); ?></span>
                                                <?php else: ?>
                                                <span class="admin-status admin-status-danger"><?php echo __('admin_offer_status_declined'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="admin-empty-state">
                                <div class="admin-empty-state-icon">📄</div>
                                <p><?php echo __('admin_no_offers'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
