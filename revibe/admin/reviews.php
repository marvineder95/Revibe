<?php
/**
 * Bewertungen verwalten
 */
require_once '../config/config.php';

setSecurityHeaders();

if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

// Aktionen verarbeiten (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('admin_error_csrf');
    } else {
        $action = $_POST['action'] ?? '';
        $id = $_POST['id'] ?? '';

        switch ($action) {
            case 'approve':
                if (updateReviewStatus($id, 'approved')) {
                    $success = __('admin_reviews_success_approved');
                } else {
                    $error = __('admin_reviews_error_action');
                }
                break;
            case 'reject':
                if (updateReviewStatus($id, 'rejected')) {
                    $success = __('admin_reviews_success_rejected');
                } else {
                    $error = __('admin_reviews_error_action');
                }
                break;
            case 'delete':
                if (deleteReview($id)) {
                    $success = __('admin_reviews_success_deleted');
                } else {
                    $error = __('admin_reviews_error_action');
                }
                break;
        }
    }
}

$reviews = getAllReviews();

$reviewCounts = [
    'all' => count($reviews),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];
foreach ($reviews as $review) {
    if (isset($reviewCounts[$review['status']])) {
        $reviewCounts[$review['status']]++;
    }
}

$lang = getCurrentLanguage();
$pageTitle = __('admin_reviews_title');

include PARTIALS_PATH . 'admin-header.php';
?>

            <?php if ($success): ?>
            <div class="admin-alert admin-alert-success" style="padding: var(--space-4); background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6); display: flex; justify-content: space-between; align-items: center; gap: var(--space-4);">
                <p style="color:#22c55e; margin-bottom:0;"><?php echo e($success); ?></p>
                <button type="button" class="admin-alert-close" style="background: transparent; border: none; color: #22c55e; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0;" aria-label="Schließen">&times;</button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="admin-alert admin-alert-error" style="padding: var(--space-4); background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6); display: flex; justify-content: space-between; align-items: center; gap: var(--space-4);">
                <p style="color:#ef4444; margin-bottom:0;"><?php echo e($error); ?></p>
                <button type="button" class="admin-alert-close" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0;" aria-label="Schließen">&times;</button>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_reviews_title'); ?></h2>
                </div>
                <div class="admin-card-body">
                    <div class="admin-dashboard-stats" style="margin-bottom: var(--space-6);">
                        <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter active" data-filter="all">
                            <div class="admin-dashboard-stat-icon blue">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            </div>
                            <div class="admin-dashboard-stat-content">
                                <div class="admin-dashboard-stat-label"><?php echo __('admin_reviews_all'); ?></div>
                                <div class="admin-dashboard-stat-value"><?php echo $reviewCounts['all']; ?></div>
                                <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Alle Bewertungen' : 'All reviews'; ?></div>
                            </div>
                        </button>
                        <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="pending">
                            <div class="admin-dashboard-stat-icon amber">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div class="admin-dashboard-stat-content">
                                <div class="admin-dashboard-stat-label"><?php echo __('admin_reviews_status_pending'); ?></div>
                                <div class="admin-dashboard-stat-value"><?php echo $reviewCounts['pending']; ?></div>
                                <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Warten auf Prüfung' : 'Waiting for review'; ?></div>
                            </div>
                        </button>
                        <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="approved">
                            <div class="admin-dashboard-stat-icon green">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div class="admin-dashboard-stat-content">
                                <div class="admin-dashboard-stat-label"><?php echo __('admin_reviews_status_approved'); ?></div>
                                <div class="admin-dashboard-stat-value"><?php echo $reviewCounts['approved']; ?></div>
                                <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Öffentlich sichtbar' : 'Publicly visible'; ?></div>
                            </div>
                        </button>
                        <button type="button" class="admin-dashboard-stat admin-dashboard-stat-filter" data-filter="rejected">
                            <div class="admin-dashboard-stat-icon purple">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            </div>
                            <div class="admin-dashboard-stat-content">
                                <div class="admin-dashboard-stat-label"><?php echo __('admin_reviews_status_rejected'); ?></div>
                                <div class="admin-dashboard-stat-value"><?php echo $reviewCounts['rejected']; ?></div>
                                <div class="admin-dashboard-stat-sublabel"><?php echo $lang === 'de' ? 'Nicht öffentlich' : 'Not public'; ?></div>
                            </div>
                        </button>
                    </div>

                    <?php if (empty($reviews)): ?>
                        <p style="color: var(--color-text-muted); margin-bottom: 0;"><?php echo __('admin_reviews_no_reviews'); ?></p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo __('admin_reviews_date'); ?></th>
                                <th><?php echo __('admin_reviews_author'); ?></th>
                                <th><?php echo __('admin_reviews_rating'); ?></th>
                                <th><?php echo __('admin_reviews_title_label'); ?></th>
                                <th><?php echo __('admin_reviews_text'); ?></th>
                                <th><?php echo __('admin_reviews_status'); ?></th>
                                <th><?php echo __('admin_reviews_actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                            <tr data-status="<?php echo e($review['status']); ?>">
                                <td><?php echo e(date('d.m.Y', strtotime($review['created_at']))); ?></td>
                                <td><?php echo e($review['name']); ?></td>
                                <td>
                                    <span class="review-stars" style="color: var(--color-primary);">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                        <?php endfor; ?>
                                    </span>
                                </td>
                                <td><?php echo e($review['title'] ?? '–'); ?></td>
                                <td style="max-width: 300px;">
                                    <span style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo e($review['text']); ?>">
                                        <?php echo e($review['text']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusLabel = match ($review['status']) {
                                        'approved' => __('admin_reviews_status_approved'),
                                        'rejected' => __('admin_reviews_status_rejected'),
                                        default => __('admin_reviews_status_pending'),
                                    };
                                    $statusStyle = match ($review['status']) {
                                        'approved' => 'background: rgba(34, 197, 94, 0.1); color: #22c55e;',
                                        'rejected' => 'background: rgba(239, 68, 68, 0.1); color: #ef4444;',
                                        default => 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;',
                                    };
                                    ?>
                                    <span style="display: inline-block; padding: var(--space-1) var(--space-3); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: 600; <?php echo $statusStyle; ?>"><?php echo e($statusLabel); ?></span>
                                </td>
                                <td>
                                    <div class="admin-actions" style="flex-wrap: nowrap;">
                                        <?php if ($review['status'] !== 'approved'): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?php echo e($review['id']); ?>">
                                            <button type="submit" class="admin-btn admin-btn-invoice admin-btn-icon" title="<?php echo __('admin_reviews_approve'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($review['status'] !== 'rejected'): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?php echo e($review['id']); ?>">
                                            <button type="submit" class="admin-btn admin-btn-reject admin-btn-icon" title="<?php echo __('admin_reviews_reject'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('<?php echo e(__('admin_reviews_delete_confirm')); ?>');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo e($review['id']); ?>">
                                            <button type="submit" class="admin-btn admin-btn-delete admin-btn-icon" title="<?php echo __('admin_reviews_delete'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            (function() {
                var statFilters = document.querySelectorAll('.admin-dashboard-stat-filter');
                var rows = document.querySelectorAll('.admin-table tbody tr');
                var activeFilter = 'all';

                function applyFilters() {
                    rows.forEach(function(row) {
                        var status = row.getAttribute('data-status');
                        var show = activeFilter === 'all' || status === activeFilter;
                        row.style.display = show ? '' : 'none';
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

                applyFilters();

                document.querySelectorAll('.admin-alert-close').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var alert = this.closest('.admin-alert');
                        if (alert) {
                            alert.style.opacity = '0';
                            alert.style.transform = 'translateY(-10px)';
                            setTimeout(function() {
                                alert.remove();
                            }, 200);
                        }
                    });
                });
            })();
            </script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
