<?php
/**
 * Admin-Vermietungskalender
 */
require_once '../config/config.php';

setSecurityHeaders();

if (!isAdminLoggedIn()) {
    redirect(BASE_URL . 'admin/login.php');
    exit;
}

$page = 'calendar';
$lang = getCurrentLanguage();
$pageTitle = __('admin_calendar_title');

// Monat / Jahr aus URL
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

$firstDay = strtotime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int)date('t', $firstDay);
$monthName = formatMonthYear($firstDay, $lang);

// Jukeboxen laden
$jukeboxes = getAllJukeboxes();

// Zeitraum für Rental-Abfrage
$startDate = date('Y-m-01', $firstDay);
$endDate = date('Y-m-t', $firstDay);

$rentals = getAllRentals([
    'date_from' => $startDate,
    'date_to' => $endDate,
    'status' => $_GET['status'] ?? ''
]);

// Rentals nach Jukebox gruppieren
$rentalsByJukebox = [];
foreach ($rentals as $rental) {
    $rentalsByJukebox[$rental['jukebox_id']][] = $rental;
}

// Status-Filter
$statusFilter = $_GET['status'] ?? '';
$statuses = [
    'reserved' => __('admin_rental_status_reserved'),
    'confirmed' => __('admin_rental_status_confirmed'),
    'cancelled' => __('admin_rental_status_cancelled'),
    'completed' => __('admin_rental_status_completed'),
];

include PARTIALS_PATH . 'admin-header.php';

/**
 * Formatiert Monat/Jahr sprachabhängig.
 */
function formatMonthYear($timestamp, $lang) {
    $monthsDe = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    $monthsEn = $monthsDe;
    $monthIndex = (int)date('n', $timestamp) - 1;
    $year = date('Y', $timestamp);
    if ($lang === 'de') {
        $de = ['Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        return $de[$monthIndex] . ' ' . $year;
    }
    return $monthsEn[$monthIndex] . ' ' . $year;
}
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><?php echo __('admin_calendar_title'); ?></h1>
        <p class="admin-page-subtitle"><?php echo $lang === 'de' ? 'Belegung aller Jukeboxen im gewählten Monat' : 'Availability of all jukeboxes in the selected month'; ?></p>
    </div>
    <div class="admin-page-actions">
        <a href="/admin/dashboard.php" class="btn btn-dark btn-sm"><?php echo __('admin_back_to_dashboard'); ?></a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="calendar-toolbar">
            <div class="calendar-nav">
                <a href="calendar.php?year=<?php echo $year; ?>&month=<?php echo $month - 1; ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-dark btn-sm">
                    <?php echo __('admin_calendar_prev'); ?>
                </a>
                <a href="calendar.php?year=<?php echo date('Y'); ?>&month=<?php echo date('n'); ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-secondary btn-sm">
                    <?php echo __('admin_calendar_today'); ?>
                </a>
                <a href="calendar.php?year=<?php echo $year; ?>&month=<?php echo $month + 1; ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-dark btn-sm">
                    <?php echo __('admin_calendar_next'); ?>
                </a>
            </div>
            <h2 class="calendar-month-title"><?php echo e($monthName); ?></h2>
            <form method="get" action="calendar.php" class="calendar-filter">
                <input type="hidden" name="year" value="<?php echo $year; ?>">
                <input type="hidden" name="month" value="<?php echo $month; ?>">
                <select name="status" class="form-input">
                    <option value=""><?php echo $lang === 'de' ? 'Alle Status' : 'All statuses'; ?></option>
                    <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?php echo e($key); ?>" <?php echo $statusFilter === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-dark btn-sm"><?php echo $lang === 'de' ? 'Filtern' : 'Filter'; ?></button>
            </form>
        </div>

        <div class="calendar-legend">
            <span class="calendar-legend-item"><span class="calendar-legend-dot reserved"></span> <?php echo __('admin_rental_status_reserved'); ?></span>
            <span class="calendar-legend-item"><span class="calendar-legend-dot confirmed"></span> <?php echo __('admin_rental_status_confirmed'); ?></span>
            <span class="calendar-legend-item"><span class="calendar-legend-dot cancelled"></span> <?php echo __('admin_rental_status_cancelled'); ?></span>
        </div>
    </div>

    <div class="admin-card-body">
        <?php if (empty($jukeboxes)): ?>
        <div class="admin-empty-state">
            <div class="admin-empty-state-icon">🎵</div>
            <p><?php echo $lang === 'de' ? 'Keine Jukeboxen vorhanden.' : 'No jukeboxes available.'; ?></p>
            <a href="/admin/create.php" class="btn btn-primary"><?php echo __('admin_create_jukebox'); ?></a>
        </div>
        <?php else: ?>
        <div class="calendar-scroll">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th class="calendar-jukebox-header"><?php echo __('admin_calendar_jukebox'); ?></th>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                        <th class="calendar-day-header <?php echo $d === (int)date('j') && $year === (int)date('Y') && $month === (int)date('n') ? 'today' : ''; ?>">
                            <?php echo $d; ?>
                        </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jukeboxes as $jb): ?>
                    <tr>
                        <td class="calendar-jukebox-cell">
                            <a href="/admin/edit.php?id=<?php echo e($jb['id']); ?>" class="calendar-jukebox-link">
                                <?php echo e(getLocalizedValue($jb, 'name')); ?>
                            </a>
                        </td>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++):
                            $currentDay = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $cellRentals = [];
                            foreach ($rentalsByJukebox[$jb['id']] ?? [] as $r) {
                                if ($r['date_start'] <= $currentDay && $r['date_end'] >= $currentDay) {
                                    $cellRentals[] = $r;
                                }
                            }
                            $statusClass = '';
                            $title = [];
                            foreach ($cellRentals as $r) {
                                $title[] = ($statuses[$r['status']] ?? $r['status']) . ': ' . date('d.m.Y', strtotime($r['date_start'])) . ' - ' . date('d.m.Y', strtotime($r['date_end']));
                                if ($r['status'] === 'confirmed') {
                                    $statusClass = 'confirmed';
                                } elseif ($r['status'] === 'reserved' && $statusClass !== 'confirmed') {
                                    $statusClass = 'reserved';
                                } elseif ($r['status'] === 'cancelled') {
                                    $statusClass = 'cancelled';
                                }
                            }
                            $isToday = $d === (int)date('j') && $year === (int)date('Y') && $month === (int)date('n');
                        ?>
                        <td class="calendar-day-cell <?php echo $statusClass ? 'calendar-cell-' . $statusClass : ''; ?> <?php echo $isToday ? 'calendar-cell-today' : ''; ?>"
                            title="<?php echo e(implode("\n", $title)); ?>">
                            <?php if (!empty($cellRentals)): ?>
                            <span class="calendar-day-marker"></span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($rentals)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-section-title"><?php echo __('admin_rentals'); ?></h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="admin-table calendar-rentals-table">
                <thead>
                    <tr>
                        <th><?php echo __('admin_calendar_jukebox'); ?></th>
                        <th><?php echo __('admin_calendar_period'); ?></th>
                        <th><?php echo __('admin_calendar_status'); ?></th>
                        <th><?php echo __('admin_customer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $r): ?>
                    <tr>
                        <td>
                            <a href="/admin/edit.php?id=<?php echo e($r['jukebox_id']); ?>" class="calendar-rental-link">
                                <?php echo e(getLocalizedValue(['name' => $r['jukebox_name'], 'name_en' => $r['jukebox_name_en']], 'name')); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo date('d.m.Y', strtotime($r['date_start'])); ?> - <?php echo date('d.m.Y', strtotime($r['date_end'])); ?>
                        </td>
                        <td>
                            <span class="admin-status admin-status-<?php echo $r['status'] === 'confirmed' ? 'success' : ($r['status'] === 'reserved' ? 'warning' : 'danger'); ?>">
                                <?php echo e($statuses[$r['status']] ?? $r['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $customer = '';
                            if (!empty($r['inquiry_id'])) {
                                $inq = getInquiryById($r['inquiry_id']);
                                if ($inq) {
                                    $customer = trim(($inq['firstname'] ?? '') . ' ' . ($inq['lastname'] ?? ''));
                                }
                            }
                            echo e($customer);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="admin-card">
    <div class="admin-card-body">
        <p class="text-muted" style="margin-bottom: 0;"><?php echo __('admin_no_rentals'); ?></p>
    </div>
</div>
<?php endif; ?>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
