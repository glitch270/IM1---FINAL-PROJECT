<?php
/**
 * Admin: Master Applications Report
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$typeFilter = trim($_GET['applicant_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$ownershipFilter = trim($_GET['ownership'] ?? '');

$sql = "
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.plate_number, v.vehicle_type, o.ownership_category,
           s.sticker_number
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    WHERE 1=1
";
$params = [];

if (!empty($dateFrom)) {
    $sql .= " AND DATE(a.submitted_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND DATE(a.submitted_at) <= ?";
    $params[] = $dateTo;
}
if (!empty($typeFilter)) {
    $sql .= " AND ap.applicant_type = ?";
    $params[] = $typeFilter;
}
if (!empty($statusFilter)) {
    $sql .= " AND a.application_status = ?";
    $params[] = $statusFilter;
}
if (!empty($ownershipFilter)) {
    $sql .= " AND o.ownership_category = ?";
    $params[] = $ownershipFilter;
}

$sql .= " ORDER BY a.submitted_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

$pageTitle = "Applications Master Report";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Applications Master Report</h1>
        <p class="page-subtitle">Filtered audit report of vehicle gate pass applications across all academic units.</p>
    </div>
    <div class="header-action-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Report</button>
    </div>
</div>

<!-- Filters Bar (Hidden on Print) -->
<div class="card no-print" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/reports/applications_report.php" class="filter-form">
        <div class="filter-row">
            <div style="flex: 1;">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">Classification</label>
                <select name="applicant_type" class="form-control">
                    <option value="">-- All --</option>
                    <option value="student" <?= $typeFilter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="employee" <?= $typeFilter === 'employee' ? 'selected' : '' ?>>Employees</option>
                </select>
            </div>
            <div style="flex: 1;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="declined" <?= $statusFilter === 'declined' ? 'selected' : '' ?>>Declined</option>
                    <option value="resubmitted" <?= $statusFilter === 'resubmitted' ? 'selected' : '' ?>>Resubmitted</option>
                    <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="released" <?= $statusFilter === 'released' ? 'selected' : '' ?>>Released</option>
                </select>
            </div>
            <div style="flex: 1.5;">
                <label class="form-label">Ownership</label>
                <select name="ownership" class="form-control">
                    <option value="">-- All Categories --</option>
                    <option value="first_owner_own_name" <?= $ownershipFilter === 'first_owner_own_name' ? 'selected' : '' ?>>First Owner (Own Name)</option>
                    <option value="first_owner_relative_name" <?= $ownershipFilter === 'first_owner_relative_name' ? 'selected' : '' ?>>First Owner (Relative)</option>
                    <option value="secondhand_applicant_buyer" <?= $ownershipFilter === 'secondhand_applicant_buyer' ? 'selected' : '' ?>>Secondhand (Buyer)</option>
                    <option value="secondhand_applicant_not_buyer" <?= $ownershipFilter === 'secondhand_applicant_not_buyer' ? 'selected' : '' ?>>Secondhand (Not Buyer)</option>
                </select>
            </div>
            <div class="filter-actions" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/reports/applications_report.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Printable Header -->
<div class="print-report-header" style="text-align: center; margin-bottom: 25px;">
    <h2><?= strtoupper(e(UNIVERSITY_NAME)) ?></h2>
    <h4>CAMPUS SAFETY AND SECURITY SERVICES</h4>
    <h3>VEHICLE GATE PASS APPLICATIONS REPORT</h3>
    <p class="text-muted">Generated on <?= date('F d, Y h:i A') ?> &bull; Total Records: <?= count($reportData) ?></p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>App Number</th>
                    <th>Date Submitted</th>
                    <th>Applicant Name</th>
                    <th>School ID</th>
                    <th>Type</th>
                    <th>Department</th>
                    <th>Vehicle Plate</th>
                    <th>Vehicle Type</th>
                    <th>Ownership Category</th>
                    <th>Status</th>
                    <th>Sticker #</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportData)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted" style="padding: 25px;">No applications found matching the selected report criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportData as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($row['application_number']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                            <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= e($row['school_id']) ?></td>
                            <td><?= ucfirst(e($row['applicant_type'])) ?></td>
                            <td><?= e($row['department']) ?></td>
                            <td class="font-bold"><?= e($row['plate_number']) ?></td>
                            <td><?= e($row['vehicle_type']) ?></td>
                            <td><?= e(getOwnershipCategoryLabel($row['ownership_category'])) ?></td>
                            <td><?= getStatusBadge($row['application_status']) ?></td>
                            <td><?= e($row['sticker_number'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

