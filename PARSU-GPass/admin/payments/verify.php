<?php
/**
 * Admin: Dedicated Payment Verification Confirmation Page
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$paymentId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT p.*, a.application_number, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, v.plate_number, v.vehicle_type
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE p.payment_id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    redirect('admin/payments/pending.php', 'error', 'Payment not found.');
}

$pageTitle = "Verify Payment - " . $payment['psu_or_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment Verification</h1>
        <p class="page-subtitle">Verify payment for Application #<?= e($payment['application_number']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading">Confirm Cashier Receipt</h2>
        <p class="card-subtext">Receipt PSU-OR #<strong><?= e($payment['psu_or_number']) ?></strong> &bull; Amount: <strong><?= formatCurrency($payment['amount']) ?></strong></p>
    </div>

    <div class="alert alert-secondary">
        <p><strong>Applicant:</strong> <?= e($payment['first_name'] . ' ' . $payment['last_name']) ?> (<?= e($payment['school_id']) ?>)</p>
        <p><strong>Vehicle:</strong> <?= e($payment['plate_number']) ?> (<?= e($payment['vehicle_type']) ?>)</p>
        <p><strong>Uploaded Receipt:</strong> <a href="<?= BASE_URL . '/' . e($payment['receipt_file']) ?>" target="_blank" class="link-primary">Open Receipt File ↗</a></p>
    </div>

    <form action="<?= BASE_URL ?>/actions/payment_verification_process.php" method="POST" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="payment_id" value="<?= $paymentId ?>">

        <div class="form-group">
            <label for="remarks" class="form-label">Verification Remarks (Optional if verified; Required if rejecting):</label>
            <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Confirmed with cashier daily collection log."></textarea>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 25px;">
            <button type="submit" name="verification_status" value="verified" class="btn btn-success btn-lg" style="flex: 1;">
                ✓ Approve & Mark as Paid
            </button>
            <button type="submit" name="verification_status" value="rejected" class="btn btn-danger btn-lg" style="flex: 1;">
                ✕ Reject Receipt
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

