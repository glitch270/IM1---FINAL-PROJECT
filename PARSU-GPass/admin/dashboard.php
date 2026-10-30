<?php
/**
 * Administrator Overview Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = getDBConnection();

// Summary counts
$counts = [];

// Applications by status
$stmt = $db->query("SELECT application_status, COUNT(*) as count FROM applications GROUP BY application_status");
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$totalApps = array_sum($statusCounts);
$pendingApps = $statusCounts['pending'] ?? 0;
$resubmittedApps = $statusCounts['resubmitted'] ?? 0;
$approvedApps = ($statusCounts['approved'] ?? 0) + ($statusCounts['payment_pending'] ?? 0);
$declinedApps = $statusCounts['declined'] ?? 0;

// Payments pending & total revenue
$payStmt = $db->query("SELECT COUNT(*) as pending_count FROM payments WHERE payment_status = 'pending_verification'");
$pendingPayments = (int)$payStmt->fetchColumn();

$revStmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total_rev FROM payments WHERE payment_status = 'verified'");
$totalRevenue = (float)$revStmt->fetchColumn();

// Stickers count
$stkStmt = $db->query("SELECT sticker_status, COUNT(*) as count FROM gate_pass_stickers GROUP BY sticker_status");
$stkCounts = $stkStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$releasedStickers = $stkCounts['released'] ?? 0;
$forReleaseStickers = $stkCounts['for_release'] ?? 0;

// Total registered applicants
$appUserStmt = $db->query("SELECT COUNT(*) FROM applicant_profiles");
$totalApplicants = (int)$appUserStmt->fetchColumn();

// Recent applications
$recentStmt = $db->query("
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type,
           v.plate_number, v.vehicle_type
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    ORDER BY a.submitted_at DESC LIMIT 5
");
$recentApplications = $recentStmt->fetchAll();

// Recent verified payments
$recentPayStmt = $db->query("
    SELECT p.*, a.application_number, ap.first_name, ap.last_name, ap.school_id
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    WHERE p.payment_status = 'verified'
    ORDER BY p.verified_at DESC LIMIT 5
");
$recentPayments = $recentPayStmt->fetchAll();

$pageTitle = "Administrator Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header-banner">
    <div class="banner-text">
        <h1 class="dashboard-heading">Security Administration Control Center</h1>
        <p class="dashboard-sub">Campus Vehicle Gate Pass & Traffic Safety Management System</p>
    </div>
    <div class="banner-badge">
        <span class="badge-role-tag">SYSTEM ADMINISTRATOR</span>
    </div>
</div>

<!-- Primary Metric Cards -->
<div class="dashboard-metrics-grid">
    <div class="metric-card card">
        <div class="metric-icon icon-warning">⏳</div>
        <div class="metric-info">
            <span class="metric-label">Pending Reviews</span>
            <div class="metric-value font-bold text-warning"><?= $pendingApps ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/applications/pending.php" class="link-primary">Review queue →</a></small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-info">🔄</div>
        <div class="metric-info">
            <span class="metric-label">Resubmitted</span>
            <div class="metric-value font-bold text-info"><?= $resubmittedApps ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/applications/resubmitted.php" class="link-primary">Evaluate corrections →</a></small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-gold">💳</div>
        <div class="metric-info">
            <span class="metric-label">Pending Payments</span>
            <div class="metric-value font-bold" style="color: #c69214;"><?= $pendingPayments ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/payments/pending.php" class="link-primary">Verify receipts →</a></small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-success">💰</div>
        <div class="metric-info">
            <span class="metric-label">Total Verified Revenue</span>
            <div class="metric-value font-bold text-success" style="font-size: 1.35rem;"><?= formatCurrency($totalRevenue) ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/reports/payments_report.php" class="link-primary">Financial report →</a></small>
        </div>
    </div>
</div>

<!-- Secondary Metric Row -->
<div class="dashboard-metrics-grid" style="margin-top: 15px;">
    <div class="metric-card card">
        <div class="metric-icon icon-primary">📄</div>
        <div class="metric-info">
            <span class="metric-label">Total Applications</span>
            <div class="metric-value"><?= $totalApps ?></div>
            <small class="metric-subtext"><?= $approvedApps ?> Approved &bull; <?= $declinedApps ?> Declined</small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-indigo">🏷️</div>
        <div class="metric-info">
            <span class="metric-label">Active Stickers Released</span>
            <div class="metric-value text-primary"><?= $releasedStickers ?></div>
            <small class="metric-subtext"><?= $forReleaseStickers ?> ready for release</small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-secondary">👥</div>
        <div class="metric-info">
            <span class="metric-label">Registered Applicants</span>
            <div class="metric-value"><?= $totalApplicants ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/applicants/list.php" class="link-primary">Manage directory →</a></small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-danger">❌</div>
        <div class="metric-info">
            <span class="metric-label">Declined Applications</span>
            <div class="metric-value text-danger"><?= $declinedApps ?></div>
            <small class="metric-subtext"><a href="<?= BASE_URL ?>/admin/applications/declined.php" class="link-danger">View declined →</a></small>
        </div>
    </div>
</div>

<div class="app-details-grid" style="margin-top: 25px;">
    <!-- Recent Applications Table -->
    <div class="app-details-main">
        <div class="card">
            <div class="card-header-flex">
                <div>
                    <h3 class="card-heading">Recent Gate Pass Applications</h3>
                    <span class="card-subtext">Latest submissions awaiting or undergoing review</span>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/admin/applications/pending.php" class="btn btn-outline-primary btn-sm">View All Pending</a>
                </div>
            </div>

            <div class="table-responsive" style="margin-top: 15px;">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>App #</th>
                            <th>Applicant</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentApplications)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: 20px;">No applications submitted yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentApplications as $app): ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="link-primary"><?= e($app['application_number']) ?></a></strong>
                                    </td>
                                    <td>
                                        <strong><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($app['school_id']) ?> (<?= ucfirst(e($app['applicant_type'])) ?>)</small>
                                    </td>
                                    <td><?= e($app['plate_number']) ?> (<?= e($app['vehicle_type']) ?>)</td>
                                    <td><?= getStatusBadge($app['application_status']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="btn btn-outline-primary btn-sm">Inspect</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts & Recent Verified Payments -->
    <div class="app-details-sidebar">
        <!-- Quick Action Links -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Quick Actions</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="<?= BASE_URL ?>/admin/applications/pending.php" class="btn btn-warning btn-block">Review Pending Applications</a>
                <a href="<?= BASE_URL ?>/admin/payments/pending.php" class="btn btn-gold btn-block">Verify Cashier Payments</a>
                <a href="<?= BASE_URL ?>/admin/gatepass/sticker_list.php" class="btn btn-primary btn-block">Sticker Inventory & Releases</a>
                <a href="<?= BASE_URL ?>/admin/reports/applications_report.php" class="btn btn-outline-secondary btn-block">Generate Master Reports</a>
            </div>
        </div>

        <!-- Recent Verified Payments -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Recent Verified Payments</h3>
            </div>
            <?php if (empty($recentPayments)): ?>
                <p class="text-muted">No verified payments recorded yet.</p>
            <?php else: ?>
                <ul class="profile-meta-list">
                    <?php foreach ($recentPayments as $rp): ?>
                        <li style="border-bottom: 1px solid #f0f0f0; padding-bottom: 8px;">
                            <div>
                                <strong><?= e($rp['psu_or_number']) ?></strong> &bull; <span class="text-success font-bold"><?= formatCurrency($rp['amount']) ?></span><br>
                                <small class="text-muted"><?= e($rp['first_name'] . ' ' . $rp['last_name']) ?> (<?= e($rp['application_number']) ?>)</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

