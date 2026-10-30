<?php
/**
 * Admin: Released Stickers Audit Report
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "
    SELECT s.*, rel.release_id, rel.released_at, rel.received_by, rel.release_remarks,
           a.application_number,
           ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.plate_number, v.vehicle_type, v.color,
           u_admin.email as released_by_admin
    FROM gate_pass_stickers s
    JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    JOIN applications a ON s.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN users u_admin ON rel.released_by = u_admin.user_id
    WHERE s.sticker_status = 'released'
";
$params = [];

if (!empty($dateFrom)) {
    $sql .= " AND DATE(rel.released_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND DATE(rel.released_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY rel.released_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$releasedStickers = $stmt->fetchAll();

$pageTitle = "Released Stickers Report";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Released Stickers Audit Report</h1>
        <p class="page-subtitle">Historical records of physical gate pass stickers claimed and active on university campus.</p>
    </div>
    <div class="header-action-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Release Report</button>
    </div>
</div>

<div class="card no-print" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/reports/released_stickers.php" class="filter-form">
        <div class="filter-row">
            <div style="flex: 1;">
                <label class="form-label">Released From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">Released To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <div class="filter-actions" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/reports/released_stickers.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Printable Header -->
<div class="print-report-header" style="text-align: center; margin-bottom: 25px;">
    <h2><?= strtoupper(e(UNIVERSITY_NAME)) ?></h2>
    <h4>CAMPUS SAFETY AND SECURITY SERVICES</h4>
    <h3>RELEASED GATE PASS STICKERS REPORT</h3>
    <p class="text-muted">Generated on <?= date('F d, Y h:i A') ?> &bull; Total Released: <?= count($releasedStickers) ?></p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sticker Number</th>
                    <th>Released Date</th>
                    <th>Received By (Claimant)</th>
                    <th>Registered Owner</th>
                    <th>School ID</th>
                    <th>Type</th>
                    <th>Vehicle Plate</th>
                    <th>Vehicle Type</th>
                    <th>Valid Until</th>
                    <th>Releasing Officer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($releasedStickers)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted" style="padding: 25px;">No released stickers recorded for the specified criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($releasedStickers as $i => $stk): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong class="text-primary"><?= e($stk['sticker_number']) ?></strong></td>
                            <td><?= date('M d, Y h:i A', strtotime($stk['released_at'])) ?></td>
                            <td class="font-bold"><?= e($stk['received_by']) ?></td>
                            <td><?= e($stk['first_name'] . ' ' . $stk['last_name']) ?></td>
                            <td><?= e($stk['school_id']) ?></td>
                            <td><?= ucfirst(e($stk['applicant_type'])) ?></td>
                            <td class="font-bold"><?= e($stk['plate_number']) ?></td>
                            <td><?= e($stk['vehicle_type']) ?></td>
                            <td><?= date('M d, Y', strtotime($stk['valid_until'])) ?></td>
                            <td><?= e($stk['released_by_admin'] ?? 'Security Admin') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

