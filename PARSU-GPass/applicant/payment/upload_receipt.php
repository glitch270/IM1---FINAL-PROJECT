<?php
/**
 * Applicant: Upload Cashier Payment Receipt
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Check application status
$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number, p.receipt_file, p.payment_date, p.verification_remarks
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application) {
    setFlashMessage('warning', 'Please submit a gate pass application first.');
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

// Payment allowed only after approval
$allowedStatuses = ['approved', 'payment_pending', 'payment_verification'];
if (!in_array($application['application_status'], $allowedStatuses)) {
    if ($application['application_status'] === 'paid' || $application['application_status'] === 'for_release' || $application['application_status'] === 'released') {
        setFlashMessage('info', 'Your payment has already been verified and recorded.');
        header("Location: " . BASE_URL . "/applicant/payment/payment_status.php");
        exit;
    } else {
        setFlashMessage('warning', 'Payment is only permitted after your application is reviewed and approved by security administration.');
        header("Location: " . BASE_URL . "/applicant/application/view.php");
        exit;
    }
}

$fee = getFeeByApplicantType($currentApplicant['applicant_type']);
$payment = $application; // existing payment row if any

$pageTitle = "Upload Payment Receipt";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Submit Official Payment Receipt</h1>
        <p class="page-subtitle">Application: <?= e($application['application_number']) ?> &bull; Vehicle: <?= e($application['plate_number']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading">Cashier Receipt Submission (PSU-OR)</h2>
        <p class="card-subtext">Submit the official receipt details issued by the university cashier.</p>
    </div>

    <?php if ($payment && $payment['payment_status'] === 'rejected'): ?>
        <div class="alert alert-danger">
            <div class="alert-title">⚠️ Previous Receipt Verification Rejected</div>
            <p><strong>Remarks from Cashier Evaluator:</strong> <?= e($payment['verification_remarks'] ?? 'Invalid receipt number or blurry image.') ?></p>
            <small>Please double-check your PSU-OR number and upload a clearer photo.</small>
        </div>
    <?php elseif ($payment && $payment['payment_status'] === 'pending_verification'): ?>
        <div class="alert alert-info">
            <div class="alert-title">⏳ Receipt Currently Under Verification</div>
            <p>You have previously uploaded receipt PSU-OR #<strong><?= e($payment['psu_or_number']) ?></strong>. You can re-upload below if you made a mistake.</p>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/actions/payment_upload_process.php" method="POST" enctype="multipart/form-data" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $application['application_id'] ?>">

        <div class="form-group">
            <label class="form-label">Assessed Fee Amount (Non-editable)</label>
            <div class="input-icon-wrap">
                <span class="input-icon">₱</span>
                <input type="text" class="form-control font-bold" value="<?= number_format($fee, 2) ?>" readonly style="background: #f4f6f5; font-size: 1.15rem; color: #165b3b;">
            </div>
            <small class="form-hint">Official rate for <?= ucfirst(e($currentApplicant['applicant_type'])) ?> Gate Pass (<?= getValidityYearsByApplicantType($currentApplicant['applicant_type']) ?> years)</small>
        </div>

        <div class="form-group">
            <label for="psu_or_number" class="form-label">University Official Receipt Number (PSU-OR #) <span class="text-danger">*</span></label>
            <input type="text" name="psu_or_number" id="psu_or_number" class="form-control" placeholder="e.g. OR-2026-987654" value="<?= e($payment['psu_or_number'] ?? '') ?>" required style="text-transform: uppercase;">
            <small class="form-hint">Number printed at the top-right corner of your official receipt</small>
        </div>

        <div class="form-group">
            <label for="payment_date" class="form-label">Payment Date (as indicated on receipt) <span class="text-danger">*</span></label>
            <input type="date" name="payment_date" id="payment_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($payment['payment_date'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-group">
            <label for="receipt_file" class="form-label">Upload Official Receipt Photo / Scan <span class="text-danger">*</span></label>
            <input type="file" name="receipt_file" id="receipt_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" <?= !empty($payment['receipt_file']) ? '' : 'required' ?>>
            <small class="form-hint">Accepted file types: JPG, PNG, PDF (Max: 5MB). Ensure amount, date, and OR# are readable.</small>
        </div>

        <?php if (!empty($payment['receipt_file'])): ?>
            <div class="alert alert-secondary" style="margin-top: 10px;">
                <strong>Previously Uploaded Receipt:</strong>
                <a href="<?= BASE_URL . '/' . e($payment['receipt_file']) ?>" target="_blank" class="link-primary" style="margin-left: 5px;">View Current Receipt File ↗</a>
            </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-gold btn-lg">Submit Receipt for Verification →</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

