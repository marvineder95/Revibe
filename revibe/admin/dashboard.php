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
$jukeboxes = getAllJukeboxes('order', 'ASC');

// Statistiken berechnen
$total = count($jukeboxes);
$working = 0;
$deco = 0;
foreach ($jukeboxes as $jukebox) {
    if (($jukebox['function_status'] ?? '') === 'working') {
        $working++;
    } elseif (($jukebox['function_status'] ?? '') === 'deco') {
        $deco++;
    }
}

$today = date('Y-m-d');
$activeRentals = getAllRentals(['date_from' => $today, 'date_to' => $today]);
$rentedJukeboxIds = [];
foreach ($activeRentals as $rental) {
    if (in_array($rental['status'] ?? '', ['reserved', 'confirmed'], true)) {
        $rentedJukeboxIds[$rental['jukebox_id']] = true;
    }
}
$rented = count($rentedJukeboxIds);

$available = 0;
foreach ($jukeboxes as $jukebox) {
    if (($jukebox['function_status'] ?? '') === 'working' && empty($rentedJukeboxIds[$jukebox['id']])) {
        $available++;
    }
}

$lang = getCurrentLanguage();
$pageTitle = __('admin_dashboard_title');
$adminUser = $_SESSION['admin_username'] ?? 'Admin';

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

                <div class="admin-dashboard-header">
                    <div class="admin-dashboard-header-text">
                        <h1 class="admin-page-title"><?php echo __('admin_dashboard_title'); ?></h1>
                        <p class="admin-page-subtitle"><?php echo $lang === 'de' ? 'Willkommen zurück, ' . e($adminUser) . '! Hier ist die Übersicht über deine Jukeboxen.' : 'Welcome back, ' . e($adminUser) . '! Here is the overview of your jukeboxes.'; ?></p>
                    </div>
                    <div class="admin-dashboard-header-actions">
                        <a href="/admin/create.php" class="btn btn-primary btn-dashboard-create">
                            <span class="btn-icon">+</span> <?php echo $lang === 'de' ? 'Neue Jukebox' : 'New Jukebox'; ?>
                        </a>
                    </div>
                </div>

                <div class="admin-dashboard-stats">
                    <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter active" data-filter="all">
                        <div class="admin-dashboard-stat-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </div>
                        <div class="admin-dashboard-stat-content">
                            <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Gesamt Jukeboxen' : 'Total Jukeboxes'; ?></div>
                            <div class="admin-dashboard-stat-value"><?php echo $total; ?></div>
                            <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Alle Jukeboxen im System' : 'All jukeboxes in the system'; ?></div>
                        </div>
                    </button>
                    <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="available">
                        <div class="admin-dashboard-stat-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div class="admin-dashboard-stat-content">
                            <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Verfügbar' : 'Available'; ?></div>
                            <div class="admin-dashboard-stat-value"><?php echo $available; ?></div>
                            <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Aktuell verfügbar' : 'Currently available'; ?></div>
                        </div>
                    </button>
                    <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="rented">
                        <div class="admin-dashboard-stat-icon amber">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div class="admin-dashboard-stat-content">
                            <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Vermietet' : 'Rented'; ?></div>
                            <div class="admin-dashboard-stat-value"><?php echo $rented; ?></div>
                            <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Aktuell vermietet' : 'Currently rented'; ?></div>
                        </div>
                    </button>
                    <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="deco">
                        <div class="admin-dashboard-stat-icon purple">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </div>
                        <div class="admin-dashboard-stat-content">
                            <div class="admin-dashboard-stat-label"><?php echo $lang === 'de' ? 'Deko-Objekte' : 'Decorative Objects'; ?></div>
                            <div class="admin-dashboard-stat-value"><?php echo $deco; ?></div>
                            <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Zur Vermietung' : 'For rent'; ?></div>
                        </div>
                    </button>
                </div>

                <div class="admin-card admin-dashboard-table-card">
                    <div class="admin-card-header admin-dashboard-toolbar">
                        <h2 class="admin-section-title"><?php echo $lang === 'de' ? 'Jukeboxen verwalten' : 'Manage Jukeboxes'; ?></h2>
                        <div class="admin-dashboard-toolbar-actions">
                            <div class="admin-search-box">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" id="jukeboxSearch" placeholder="<?php echo $lang === 'de' ? 'Suche Jukeboxen...' : 'Search jukeboxes...'; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body admin-dashboard-table-body">
                        <?php if (!empty($jukeboxes)): ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table admin-dashboard-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;"><?php echo $lang === 'de' ? 'BILD' : 'IMAGE'; ?></th>
                                        <th><?php echo $lang === 'de' ? 'NAME' : 'NAME'; ?></th>
                                        <th><?php echo $lang === 'de' ? 'HERSTELLER' : 'MANUFACTURER'; ?></th>
                                        <th><?php echo $lang === 'de' ? 'PREIS / TAG' : 'PRICE / DAY'; ?></th>
                                        <th><?php echo __('admin_status'); ?></th>
                                        <th style="width: 120px; text-align: right;"><?php echo $lang === 'de' ? 'AKTIONEN' : 'ACTIONS'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jukeboxes as $jukebox): ?>
                                    <?php $isRented = !empty($rentedJukeboxIds[$jukebox['id']]); ?>
                                    <tr data-status="<?php echo e($jukebox['function_status']); ?>" data-rented="<?php echo $isRented ? '1' : '0'; ?>">
                                        <td>
                                            <img src="<?php echo getJukeboxImageUrl($jukebox['main_image']); ?>"
                                                 alt=""
                                                 class="admin-dashboard-table-img"
                                                 onerror="this.src='https://via.placeholder.com/60x60/242424/0066B1?text=JB'">
                                        </td>
                                        <td>
                                            <div class="admin-dashboard-table-name">
                                                <strong><?php echo e($jukebox['name']); ?></strong>
                                                <?php if (!empty($jukebox['featured'])): ?>
                                                <span class="admin-tag-highlight">Highlight</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($jukebox['tags'])): ?>
                                            <div class="jukebox-tags-list admin-dashboard-table-tags">
                                                <?php foreach ($jukebox['tags'] as $tag): ?>
                                                <?php echo renderJukeboxTag($tag); ?>
                                                <?php endforeach; ?>
                                            </div>
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
                                            <div class="admin-dashboard-table-actions">
                                                <a href="/admin/edit.php?id=<?php echo $jukebox['id']; ?>" class="admin-dashboard-action-btn" aria-label="<?php echo __('btn_edit'); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                </a>
                                                <a href="/admin/delete.php?id=<?php echo $jukebox['id']; ?>" class="admin-dashboard-action-btn admin-dashboard-action-btn-more" aria-label="<?php echo __('btn_delete'); ?>" onclick="return confirm('<?php echo __('admin_delete_confirm'); ?>')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                                </a>
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

<script>
(function() {
    var searchInput = document.getElementById('jukeboxSearch');
    var table = document.querySelector('.admin-dashboard-table');
    var statFilters = document.querySelectorAll('.admin-dashboard-stat-filter');
    if (!table) return;

    var rows = table.querySelectorAll('tbody tr');
    var noResults = document.createElement('div');
    noResults.className = 'admin-empty-state admin-search-empty';
    noResults.style.display = 'none';
    noResults.innerHTML = '<div class="admin-empty-state-icon">🔍</div><p><?php echo $lang === "de" ? "Keine Jukeboxen gefunden." : "No jukeboxes found."; ?></p>';
    table.parentNode.insertBefore(noResults, table.nextSibling);

    var activeFilter = 'all';
    var searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

    function matchesFilter(row, filter) {
        if (filter === 'all') return true;
        var status = row.getAttribute('data-status');
        var rented = row.getAttribute('data-rented') === '1';
        if (filter === 'available') return status === 'working' && !rented;
        if (filter === 'rented') return rented;
        if (filter === 'deco') return status === 'deco';
        return true;
    }

    function applyFilters() {
        var visibleCount = 0;
        rows.forEach(function(row) {
            var textMatch = searchTerm === '' || row.textContent.toLowerCase().indexOf(searchTerm) !== -1;
            var filterMatch = matchesFilter(row, activeFilter);
            var show = textMatch && filterMatch;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTerm = this.value.toLowerCase().trim();
            applyFilters();
        });
    }

    statFilters.forEach(function(button) {
        button.addEventListener('click', function() {
            activeFilter = this.getAttribute('data-filter');
            statFilters.forEach(function(btn) {
                btn.classList.toggle('active', btn === this);
            }, this);
            applyFilters();
        });
    });
})();
</script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
