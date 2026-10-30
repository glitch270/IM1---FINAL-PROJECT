<?php
/**
 * Admin: Expired Gate Pass Stickers Report
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

// Rule 11: Update status to expired when viewing/running the report
$db->exec("UPDATE gate_pass_stickers SET sticker_status = 'expired' WHERE valid_until < CURDATE() AND sticker_status != 'expired'");

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "
    SELECT s.*, a.application_number,
           ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department, ap.contact_number,
           v.plate_number, v.vehicle_type, v.color,
           DATEDIFF(CURDATE(), s.valid_until) AS days_expired
    FROM gate_pass_stickers s
    JOIN applications a ON s.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE s.valid_until < CURDATE() OR s.sticker_status = 'expired'
";

if (!empty($search)) {
    $sql .= " AND (s.sticker_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY s.valid_until ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$expiredStickers = $stmt->fetchAll();

$pageTitle = "Expired Gate Pass Stickers Report";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Expired Gate Pass Stickers Report</h1>
        <p class="page-subtitle">Vehicles whose gate pass stickers have surpassed their valid expiration date.</p>
    </div>
    <div class="header-action-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Expired Report</button>
    </div>
</div>

<div class="card no-print" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/reports/expired_stickers.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap" style="flex: 2;">
                <input type="text" name="search" class="form-control" placeholder="Search expired stickers by number, plate, or driver..." value="<?= e($search) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>/admin/reports/expired_stickers.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Printable Header -->
<div class="print-report-header" style="text-align: center; margin-bottom: 25px;">
    <h2><?= strtoupper(e(UNIVERSITY_NAME)) ?></h2>
    <h4>CAMPUS SAFETY AND SECURITY SERVICES</h4>
    <h3>EXPIRED VEHICLE GATE PASS STICKERS REPORT</h3>
    <p class="text-muted">Generated on <?= date('F d, Y h:i A') ?> &bull; Total Expired: <?= count($expiredStickers) ?></p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sticker Number</th>
                    <th>Vehicle Plate</th>
                    <th>Vehicle Type</th>
                    <th>Driver Name</th>
                    <th>School ID</th>
                    <th>Classification</th>
                    <th>Department</th>
                    <th>Contact #</th>
                    <th>Expired Date</th>
                    <th>Days Expired</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expiredStickers)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted" style="padding: 25px;">No expired gate pass stickers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expiredStickers as $idx => $stk): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong class="text-danger"><?= e($stk['sticker_number']) ?></strong></td>
                            <td class="font-bold"><?= e($stk['plate_number']) ?></td>
                            <td><?= e($stk['vehicle_type']) ?></td>
                            <td><?= e($stk['first_name'] . ' ' . $stk['last_name']) ?></td>
                            <td><?= e($stk['school_id']) ?></td>
                            <td><?= ucfirst(e($stk['applicant_type'])) ?></td>
                            <td><?= e($stk['department']) ?></td>
                            <td><?= e($stk['contact_number']) ?></td>
                            <td class="text-danger font-bold"><?= date('M d, Y', strtotime($stk['valid_until'])) ?></td>
                            <td><span class="badge badge-danger"><?= max(0, (int)$stk['days_expired']) ?> days</span></td>
                            <td><span class="badge badge-secondary">Expired</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

