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
$monthName = date('F Y', $firstDay);

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

include PARTIALS_PATH . 'admin-sidebar.php';
?>

<main class="admin-main">
    <div class="admin-header">
        <h1 class="admin-title"><?php echo __('admin_calendar_title'); ?></h1>
        <div class="admin-actions" style="display: flex; gap: var(--space-3); flex-wrap: wrap; align-items: center;">
            <form method="get" action="calendar.php" style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap;">
                <select name="status" class="form-input" style="min-width: 160px;">
                    <option value=""><?php echo $lang === 'de' ? 'Alle Status' : 'All statuses'; ?></option>
                    <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?php echo e($key); ?>" <?php echo $statusFilter === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-dark"><?php echo $lang === 'de' ? 'Filtern' : 'Filter'; ?></button>
            </form>
            <div style="display: flex; gap: var(--space-2);">
                <a href="calendar.php?year=<?php echo $year; ?>&month=<?php echo $month - 1; ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-dark"><?php echo __('admin_calendar_prev'); ?></a>
                <a href="calendar.php?year=<?php echo date('Y'); ?>&month=<?php echo date('n'); ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-secondary"><?php echo __('admin_calendar_today'); ?></a>
                <a href="calendar.php?year=<?php echo $year; ?>&month=<?php echo $month + 1; ?>&status=<?php echo e($statusFilter); ?>" class="btn btn-dark"><?php echo __('admin_calendar_next'); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-3);">
            <h2 style="font-size: var(--text-xl); margin: 0;"><?php echo e($monthName); ?></h2>
            <div style="display: flex; gap: var(--space-4); font-size: var(--text-sm);">
                <span style="display: flex; align-items: center; gap: var(--space-1);"><span style="width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;"></span> <?php echo __('admin_rental_status_reserved'); ?></span>
                <span style="display: flex; align-items: center; gap: var(--space-1);"><span style="width: 12px; height: 12px; background: #22c55e; border-radius: 2px;"></span> <?php echo __('admin_rental_status_confirmed'); ?></span>
                <span style="display: flex; align-items: center; gap: var(--space-1);"><span style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></span> <?php echo __('admin_rental_status_cancelled'); ?></span>
            </div>
        </div>

        <?php if (empty($jukeboxes)): ?>
        <p style="color: var(--color-gray-500);"><?php echo $lang === 'de' ? 'Keine Jukeboxen vorhanden.' : 'No jukeboxes available.'; ?></p>
        <?php else: ?>
        <div class="calendar-scroll" style="overflow-x: auto;">
            <table class="calendar-table" style="width: 100%; min-width: 800px; border-collapse: collapse; font-size: var(--text-sm);">
                <thead>
                    <tr>
                        <th style="position: sticky; left: 0; background: var(--color-cream); min-width: 160px; padding: var(--space-2); border: 1px solid var(--color-gray-700); text-align: left; z-index: 2;"><?php echo __('admin_calendar_jukebox'); ?></th>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                        <th style="min-width: 34px; padding: var(--space-1); border: 1px solid var(--color-gray-700); text-align: center; <?php echo $d === (int)date('j') && $year === (int)date('Y') && $month === (int)date('n') ? 'background: rgba(212,175,55,0.15);' : ''; ?>">
                            <?php echo $d; ?>
                        </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jukeboxes as $jb): ?>
                    <tr>
                        <td style="position: sticky; left: 0; background: var(--color-cream); padding: var(--space-2); border: 1px solid var(--color-gray-700); font-weight: 500; z-index: 1;">
                            <?php echo e(getLocalizedValue($jb, 'name')); ?>
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
                            $bg = '';
                            if ($statusClass === 'confirmed') $bg = 'background: #22c55e;';
                            elseif ($statusClass === 'reserved') $bg = 'background: #f59e0b;';
                            elseif ($statusClass === 'cancelled') $bg = 'background: #ef4444;';
                        ?>
                        <td style="padding: 0; border: 1px solid var(--color-gray-700); text-align: center; height: 34px; <?php echo $bg; ?>" title="<?php echo e(implode("\n", $title)); ?>">
                            <?php if (!empty($cellRentals)): ?>
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.8);"></span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($rentals)): ?>
        <div style="margin-top: var(--space-8);">
            <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);"><?php echo __('admin_rentals'); ?></h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; min-width: 600px; border-collapse: collapse; font-size: var(--text-sm);">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-gray-700);">
                            <th style="text-align: left; padding: var(--space-2);"><?php echo __('admin_calendar_jukebox'); ?></th>
                            <th style="text-align: left; padding: var(--space-2);"><?php echo __('admin_calendar_period'); ?></th>
                            <th style="text-align: left; padding: var(--space-2);"><?php echo __('admin_calendar_status'); ?></th>
                            <th style="text-align: left; padding: var(--space-2);"><?php echo __('admin_customer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $r): ?>
                        <tr style="border-bottom: 1px solid var(--color-gray-700);">
                            <td style="padding: var(--space-2);"><?php echo e(getLocalizedValue(['name' => $r['jukebox_name'], 'name_en' => $r['jukebox_name_en']], 'name')); ?></td>
                            <td style="padding: var(--space-2);">
                                <?php echo date('d.m.Y', strtotime($r['date_start'])); ?> - <?php echo date('d.m.Y', strtotime($r['date_end'])); ?>
                            </td>
                            <td style="padding: var(--space-2);">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: var(--radius-sm); color: #fff; background: <?php echo $r['status'] === 'confirmed' ? '#22c55e' : ($r['status'] === 'reserved' ? '#f59e0b' : '#ef4444'); ?>;">
                                    <?php echo e($statuses[$r['status']] ?? $r['status']); ?>
                                </span>
                            </td>
                            <td style="padding: var(--space-2);">
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
        <?php else: ?>
        <p style="color: var(--color-gray-500); margin-top: var(--space-6);"><?php echo __('admin_no_rentals'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include PARTIALS_PATH . 'footer.php'; ?>
