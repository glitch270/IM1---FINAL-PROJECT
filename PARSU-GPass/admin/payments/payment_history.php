<?php
/**
 * Admin: Complete Payment History & Financial Records
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "
    SELECT p.*, a.application_number, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.plate_number, v.vehicle_type,
           u.email as verifier_email
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON p.verified_by = u.user_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.psu_or_number LIKE ? OR a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

if (!empty($statusFilter)) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $sql .= " AND p.payment_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND p.payment_date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total collections summary
$totalCollected = 0.0;
foreach ($payments as $p) {
    if ($p['payment_status'] === 'verified') {
        $totalCollected += (float)$p['amount'];
    }
}

$pageTitle = "Payment History & Receipts";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment History</h1>
        <p class="page-subtitle">Master ledger of cashier receipts and payment verifications across all applications.</p>
    </div>
    <div>
        <div class="card" style="padding: 10px 20px; background: #eef0fb; border: 1px solid #dfe3f7;">
            <span style="font-size: 0.85rem; color: #000035; font-weight: bold;">TOTAL VERIFIED COLLECTION</span>
            <div style="font-size: 1.4rem; font-weight: bold; color: #000035;"><?= formatCurrency($totalCollected) ?></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/payments/payment_history.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap" style="flex: 2;">
                <input type="text" name="search" class="form-control" placeholder="Search PSU-OR #, Applicant, Plate, or App #..." value="<?= e($search) ?>">
            </div>

            <div style="flex: 1;">
                <select name="status" class="form-control">
                    <option value="">-- All Statuses --</option>
                    <option value="pending_verification" <?= $statusFilter === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <div style="flex: 1;">
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>" title="From date">
            </div>

            <div style="flex: 1;">
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>" title="To date">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/payments/payment_history.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>PSU-OR #</th>
                    <th>Application #</th>
                    <th>Applicant</th>
                    <th>Type</th>
                    <th>Vehicle</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th>Verified By</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding: 30px;">
                            No payment transactions matching your filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= BASE_URL ?>/admin/payments/view.php?id=<?= $pay['payment_id'] ?>" class="link-primary"><?= e($pay['psu_or_number']) ?></a></strong>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $pay['application_id'] ?>" class="link-muted"><?= e($pay['application_number']) ?></a>
                            </td>
                            <td>
                                <strong><?= e($pay['first_name'] . ' ' . $pay['last_name']) ?></strong><br>
                                <small class="text-muted"><?= e($pay['school_id']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $pay['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>">
                                    <?= ucfirst(e($pay['applicant_type'])) ?>
                                </span>
                            </td>
                            <td><?= e($pay['plate_number']) ?></td>
                            <td class="font-bold text-success"><?= formatCurrency($pay['amount']) ?></td>
                            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                            <td><?= getStatusBadge($pay['payment_status']) ?></td>
                            <td><?= e($pay['verifier_email'] ?? 'Pending') ?></td>
                            <td>
                                <?php if (!empty($pay['receipt_file'])): ?>
                                    <a href="<?= BASE_URL . '/' . e($pay['receipt_file']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Inspect</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


