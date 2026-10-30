<?php
/**
 * Applicant: Payment Status Check
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number, p.receipt_file, p.payment_date, p.verified_at, p.verification_remarks,
           u.email as verified_by_email
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN users u ON p.verified_by = u.user_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$payment = $stmt->fetch();

$pageTitle = "Payment Status";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment Status</h1>
        <p class="page-subtitle">Status of university vehicle gate pass cashier fee settlement.</p>
    </div>
</div>

<?php if (!$payment): ?>
    <div class="card empty-state-card">
        <p>No active applications or payments found.</p>
        <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary">Apply for Gate Pass</a>
    </div>
<?php elseif (!$payment['payment_id'] && !in_array($payment['application_status'], ['approved', 'payment_pending'])): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">⏳</div>
        <h2 class="empty-state-title">Payment Stage Not Yet Reached</h2>
        <p class="empty-state-desc">Your application is currently <strong><?= e(ucfirst(str_replace('_', ' ', $payment['application_status']))) ?></strong>. Payment unlocks after security administrators review and approve your submitted documents.</p>
        <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-primary">View Application</a>
    </div>
<?php else: ?>
    <div class="card" style="max-width: 750px; margin: 0 auto;">
        <div class="card-header-flex">
            <div>
                <h3 class="card-heading">Payment Record</h3>
                <span class="card-subtext">Application: <?= e($payment['application_number']) ?> &bull; Vehicle: <?= e($payment['plate_number']) ?></span>
            </div>
            <div>
                <?= $payment['payment_status'] ? getStatusBadge($payment['payment_status']) : '<span class="badge badge-warning">Awaiting Upload</span>' ?>
            </div>
        </div>

        <div class="table-responsive" style="margin-top: 20px;">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 35%;">Official Fee Amount:</th>
                        <td class="font-bold text-success" style="font-size: 1.15rem;">
                            <?= formatCurrency($payment['amount'] ?? getFeeByApplicantType($currentApplicant['applicant_type'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>University Receipt (PSU-OR #):</th>
                        <td class="font-bold"><?= e($payment['psu_or_number'] ?? 'Not yet uploaded') ?></td>
                    </tr>
                    <tr>
                        <th>Payment Date:</th>
                        <td><?= $payment['payment_date'] ? date('F d, Y', strtotime($payment['payment_date'])) : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Verification Status:</th>
                        <td><?= $payment['payment_status'] ? getStatusBadge($payment['payment_status']) : '<span class="badge badge-warning">Pending Payment</span>' ?></td>
                    </tr>
                    <?php if ($payment['verified_at']): ?>
                        <tr>
                            <th>Verified At:</th>
                            <td><?= date('F d, Y h:i A', strtotime($payment['verified_at'])) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($payment['verification_remarks']): ?>
                        <tr>
                            <th>Evaluator Remarks:</th>
                            <td><?= e($payment['verification_remarks']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($payment['receipt_file'])): ?>
                        <tr>
                            <th>Uploaded Receipt:</th>
                            <td>
                                <a href="<?= BASE_URL . '/' . e($payment['receipt_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Inspect Receipt File ↗</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="payment-actions" style="margin-top: 25px; display: flex; gap: 10px;">
            <?php if (!$payment['payment_id'] || $payment['payment_status'] === 'rejected' || in_array($payment['application_status'], ['approved', 'payment_pending'])): ?>
                <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold">
                    <?= $payment['payment_status'] === 'rejected' ? 'Re-upload Corrected Receipt' : 'Upload Receipt Now' ?>
                </a>
            <?php elseif ($payment['payment_status'] === 'verified'): ?>
                <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-primary">Check Sticker Status →</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-outline-secondary">Back to Application</a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

