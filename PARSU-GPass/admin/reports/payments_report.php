<?php
/**
 * Admin: Financial Payments & Collections Report
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'verified'); // default to verified for collections

$sql = "
    SELECT p.*, a.application_number, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.plate_number, v.vehicle_type
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE 1=1
";
$params = [];

if (!empty($dateFrom)) {
    $sql .= " AND p.payment_date >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND p.payment_date <= ?";
    $params[] = $dateTo;
}
if (!empty($statusFilter)) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Calculations
$studentTotal = 0.0;
$studentCount = 0;
$employeeTotal = 0.0;
$employeeCount = 0;
$grandTotal = 0.0;

foreach ($payments as $p) {
    $amt = (float)$p['amount'];
    $grandTotal += $amt;
    if ($p['applicant_type'] === 'student') {
        $studentTotal += $amt;
        $studentCount++;
    } else {
        $employeeTotal += $amt;
        $employeeCount++;
    }
}

$pageTitle = "Payments & Collections Report";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Payments & Collections Financial Report</h1>
        <p class="page-subtitle">Detailed university cashier revenue and official receipt audit.</p>
    </div>
    <div class="header-action-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Financial Report</button>
    </div>
</div>

<!-- Filters (Hidden on Print) -->
<div class="card no-print" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/reports/payments_report.php" class="filter-form">
        <div class="filter-row">
            <div style="flex: 1;">
                <label class="form-label">Payment Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">Payment Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">Payment Status</label>
                <select name="status" class="form-control">
                    <option value="">-- All Payments --</option>
                    <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified Only</option>
                    <option value="pending_verification" <?= $statusFilter === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="filter-actions" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/reports/payments_report.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Revenue Summary Cards (Visible on screen & print) -->
<div class="dashboard-metrics-grid" style="margin-bottom: 20px;">
    <div class="metric-card card">
        <div class="metric-icon icon-primary">🎓</div>
        <div class="metric-info">
            <span class="metric-label">Student Collections</span>
            <div class="metric-value font-bold"><?= formatCurrency($studentTotal) ?></div>
            <small class="metric-subtext"><?= $studentCount ?> Transactions (@ ₱130.00)</small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-indigo">💼</div>
        <div class="metric-info">
            <span class="metric-label">Employee Collections</span>
            <div class="metric-value font-bold"><?= formatCurrency($employeeTotal) ?></div>
            <small class="metric-subtext"><?= $employeeCount ?> Transactions (@ ₱280.00)</small>
        </div>
    </div>

    <div class="metric-card card" style="background: #f8faf9; border-top: 4px solid #165b3b;">
        <div class="metric-icon icon-success">💰</div>
        <div class="metric-info">
            <span class="metric-label">Total Collections</span>
            <div class="metric-value font-bold text-success" style="font-size: 1.4rem;"><?= formatCurrency($grandTotal) ?></div>
            <small class="metric-subtext"><?= count($payments) ?> Total Receipts Recorded</small>
        </div>
    </div>
</div>

<!-- Printable Header -->
<div class="print-report-header" style="text-align: center; margin-bottom: 25px;">
    <h2><?= strtoupper(e(UNIVERSITY_NAME)) ?></h2>
    <h4>OFFICE OF THE UNIVERSITY CASHIER & SAFETY SERVICES</h4>
    <h3>VEHICLE GATE PASS COLLECTIONS REPORT</h3>
    <p class="text-muted">Generated on <?= date('F d, Y h:i A') ?> &bull; Status: <?= ucfirst(e($statusFilter ?: 'All')) ?></p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PSU-OR Number</th>
                    <th>Payment Date</th>
                    <th>Application #</th>
                    <th>Applicant Name</th>
                    <th>School ID</th>
                    <th>Classification</th>
                    <th>Vehicle Plate</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding: 25px;">No payment records found for the specified criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $idx => $p): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= e($p['psu_or_number']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><?= e($p['application_number']) ?></td>
                            <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td><?= e($p['school_id']) ?></td>
                            <td><?= ucfirst(e($p['applicant_type'])) ?></td>
                            <td><?= e($p['plate_number']) ?></td>
                            <td class="font-bold text-success"><?= formatCurrency($p['amount']) ?></td>
                            <td><?= getStatusBadge($p['payment_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-size: 1.05rem; font-weight: bold; background: #fafafa;">
                    <td colspan="8" class="text-right">GRAND TOTAL:</td>
                    <td colspan="2" class="text-success"><?= formatCurrency($grandTotal) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

