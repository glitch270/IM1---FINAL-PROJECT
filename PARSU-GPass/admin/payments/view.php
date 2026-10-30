<?php
/**
 * Admin: View & Inspect Individual Payment Receipt
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$paymentId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT p.*, a.application_number, a.application_status,
           ap.first_name, ap.middle_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department, ap.contact_number,
           v.vehicle_type, v.plate_number, v.year_model, v.color,
           u.email as applicant_email,
           admin_u.email as verifier_email
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN users u ON ap.user_id = u.user_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN users admin_u ON p.verified_by = admin_u.user_id
    WHERE p.payment_id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    redirect('admin/payments/pending.php', 'error', 'Payment record not found.');
}

$isPdf = strtolower(pathinfo($payment['receipt_file'], PATHINFO_EXTENSION)) === 'pdf';
$receiptUrl = BASE_URL . '/' . e($payment['receipt_file']);

$pageTitle = "Verify Payment: " . $payment['psu_or_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment Verification: <?= e($payment['psu_or_number']) ?></h1>
        <p class="page-subtitle">Application: <?= e($payment['application_number']) ?> &bull; Status: <?= getStatusBadge($payment['payment_status']) ?></p>
    </div>
    <div class="header-action-group">
        <a href="<?= BASE_URL ?>/admin/payments/pending.php" class="btn btn-outline-secondary">← Back to Pending List</a>
    </div>
</div>

<div class="doc-inspector-layout">
    <!-- Left Column: Details & Verification Action Form -->
    <div class="doc-list-sidebar card" style="flex: 1;">
        <h3 class="card-heading" style="margin-bottom: 15px;">Receipt Information</h3>
        <table class="table table-bordered" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <th>PSU-OR #:</th>
                    <td class="font-bold text-primary"><?= e($payment['psu_or_number']) ?></td>
                </tr>
                <tr>
                    <th>Assessed Amount:</th>
                    <td class="font-bold text-success" style="font-size: 1.1rem;"><?= formatCurrency($payment['amount']) ?></td>
                </tr>
                <tr>
                    <th>Payment Date:</th>
                    <td><?= date('F d, Y', strtotime($payment['payment_date'])) ?></td>
                </tr>
                <tr>
                    <th>Applicant:</th>
                    <td><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?> (<?= e($payment['school_id']) ?>)</td>
                </tr>
                <tr>
                    <th>Classification:</th>
                    <td><?= ucfirst(e($payment['applicant_type'])) ?></td>
                </tr>
                <tr>
                    <th>Vehicle:</th>
                    <td><?= e($payment['plate_number']) ?> (<?= e($payment['vehicle_type']) ?>)</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td><?= getStatusBadge($payment['payment_status']) ?></td>
                </tr>
                <?php if ($payment['verified_at']): ?>
                    <tr>
                        <th>Verified At:</th>
                        <td><?= date('M d, Y h:i A', strtotime($payment['verified_at'])) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($payment['verification_remarks']): ?>
                    <tr>
                        <th>Remarks:</th>
                        <td><?= e($payment['verification_remarks']) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Verification Action Form (if pending) -->
        <?php if ($payment['payment_status'] === 'pending_verification'): ?>
            <div class="card" style="background: #fdfdf9; border-top: 3px solid #c69214; padding: 15px;">
                <h4 style="margin-bottom: 12px;">Evaluation Decision</h4>
                <form action="<?= BASE_URL ?>/actions/payment_verification_process.php" method="POST">
                    <?= csrfInput() ?>
                    <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">

                    <div class="form-group">
                        <label for="verification_remarks" class="form-label">Verification Remarks (Required if rejecting):</label>
                        <textarea name="remarks" id="verification_remarks" rows="3" class="form-control" placeholder="Enter remarks (e.g. 'PSU-OR verified against cashier ledger' or 'Amount or OR# mismatch')..."></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button type="submit" name="verification_status" value="verified" class="btn btn-success" style="flex: 1;">
                            ✓ Confirm Payment
                        </button>
                        <button type="submit" name="verification_status" value="rejected" class="btn btn-danger" style="flex: 1;">
                            ✕ Reject Receipt
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($payment['payment_status'] === 'verified' && $payment['application_status'] === 'paid'): ?>
            <div class="alert alert-success">
                <strong>Payment Confirmed.</strong> Gate pass sticker can now be generated for this application.
                <div style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/admin/gatepass/generate_sticker.php?id=<?= $payment['application_id'] ?>" class="btn btn-primary btn-block">Generate Sticker Now →</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Receipt Image / PDF Preview -->
    <div class="doc-preview-area card" style="flex: 2;">
        <div class="preview-header">
            <h3 class="preview-title">Cashier Receipt Document Preview</h3>
            <a href="<?= $receiptUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm">Open File ↗</a>
        </div>
        <div class="preview-viewport" style="margin-top: 15px; min-height: 550px; background: #525659; display: flex; align-items: center; justify-content: center; border-radius: 6px; overflow: hidden;">
            <?php if ($isPdf): ?>
                <iframe src="<?= $receiptUrl ?>" style="width: 100%; height: 600px; border: none;"></iframe>
            <?php else: ?>
                <img src="<?= $receiptUrl ?>" alt="Payment Receipt" style="max-width: 100%; max-height: 600px; object-fit: contain;">
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

