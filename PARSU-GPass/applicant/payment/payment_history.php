<?php
/**
 * Applicant: Payment History Table
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT p.*, a.application_number, v.plate_number, v.vehicle_type
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.applicant_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$applicantId]);
$payments = $stmt->fetchAll();

$pageTitle = "My Payment History";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment History</h1>
        <p class="page-subtitle">Historical records of cashier receipts and payments submitted for vehicle stickers.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Application #</th>
                    <th>Vehicle</th>
                    <th>PSU-OR #</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th>Verified Date</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                            No payment records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><strong><?= e($pay['application_number']) ?></strong></td>
                            <td><?= e($pay['plate_number']) ?> (<?= e($pay['vehicle_type']) ?>)</td>
                            <td class="font-bold text-primary"><?= e($pay['psu_or_number']) ?></td>
                            <td class="font-bold"><?= formatCurrency($pay['amount']) ?></td>
                            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                            <td><?= getStatusBadge($pay['payment_status']) ?></td>
                            <td><?= $pay['verified_at'] ? date('M d, Y h:i A', strtotime($pay['verified_at'])) : '<span class="text-muted">Pending</span>' ?></td>
                            <td>
                                <?php if (!empty($pay['receipt_file'])): ?>
                                    <a href="<?= BASE_URL . '/' . e($pay['receipt_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View Receipt</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
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

