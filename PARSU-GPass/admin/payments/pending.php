<?php
/**
 * Admin: Pending Payment Verifications List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "
    SELECT p.*, a.application_number, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.plate_number, v.vehicle_type
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE p.payment_status = 'pending_verification'
";

if (!empty($search)) {
    $sql .= " AND (p.psu_or_number LIKE ? OR a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like, $like];
}

$sql .= " ORDER BY p.created_at ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pageTitle = "Pending Payment Verifications";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Pending Payment Verifications</h1>
        <p class="page-subtitle">Verify official receipts uploaded by applicants against university cashier records.</p>
    </div>
    <div>
        <span class="badge badge-warning" style="font-size: 1rem; padding: 8px 14px;">
            <?= count($payments) ?> Pending Verification
        </span>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/payments/pending.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search by PSU-OR #, Applicant Name, or App #..." value="<?= e($search) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>/admin/payments/pending.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
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
                    <th>Vehicle</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Receipt</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                            No pending payment receipts awaiting verification.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <strong class="text-primary font-bold" style="font-size: 1.05rem;"><?= e($pay['psu_or_number']) ?></strong>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $pay['application_id'] ?>" class="link-primary"><?= e($pay['application_number']) ?></a>
                            </td>
                            <td>
                                <strong><?= e($pay['first_name'] . ' ' . $pay['last_name']) ?></strong><br>
                                <small class="text-muted"><?= e($pay['school_id']) ?> (<?= ucfirst(e($pay['applicant_type'])) ?>)</small>
                            </td>
                            <td>
                                <strong><?= e($pay['plate_number']) ?></strong><br>
                                <small class="text-muted"><?= e($pay['vehicle_type']) ?></small>
                            </td>
                            <td class="font-bold text-success"><?= formatCurrency($pay['amount']) ?></td>
                            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL . '/' . e($pay['receipt_file']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Inspect File</a>
                            </td>
                            <td>
                                <div class="btn-group-sm">
                                    <a href="<?= BASE_URL ?>/admin/payments/view.php?id=<?= $pay['payment_id'] ?>" class="btn btn-primary btn-sm">Verify Payment</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

