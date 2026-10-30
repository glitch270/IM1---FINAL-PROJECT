<?php
/**
 * Applicant: Gate Pass Fee & Payment Instructions
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Get applicant's latest application
$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

$fee = getFeeByApplicantType($currentApplicant['applicant_type']);
$validityYears = getValidityYearsByApplicantType($currentApplicant['applicant_type']);

$pageTitle = "Gate Pass Fee & Payment Details";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Payment & Fee Details</h1>
        <p class="page-subtitle">Official university cashier payment guidelines and schedule for vehicle stickers.</p>
    </div>
    <?php if ($application && in_array($application['application_status'], ['approved', 'payment_pending'])): ?>
        <div>
            <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold btn-lg">
                Upload Payment Receipt →
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="pricing-grid" style="margin-bottom: 25px;">
    <div class="card pricing-card featured" style="max-width: 500px; margin: 0 auto;">
        <div class="featured-tag"><?= strtoupper(e($currentApplicant['applicant_type'])) ?> RATE</div>
        <div class="pricing-header">
            <span class="pricing-role">Official Gate Pass Assessment</span>
            <div class="pricing-amount"><?= formatCurrency($fee) ?></div>
            <span class="pricing-duration">Valid for <?= $validityYears ?> Academic Years</span>
        </div>
        <ul class="pricing-features">
            <li>✓ Registration for Vehicle: <strong><?= $application ? e($application['plate_number']) : 'Primary Vehicle' ?></strong></li>
            <li>✓ Fast Lane Security Tag & Physical University Sticker</li>
            <li>✓ Valid on Main Campus and External Campuses</li>
            <li>✓ Payment settled at University Cashier / Designated Bank</li>
        </ul>
        <div class="pricing-footer">
            <?php if (!$application): ?>
                <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary btn-block">Apply for Gate Pass First</a>
            <?php elseif (in_array($application['application_status'], ['pending', 'resubmitted'])): ?>
                <button class="btn btn-secondary btn-block" disabled>Application Under Review (Payment Unlocks Upon Approval)</button>
            <?php elseif ($application['application_status'] === 'declined'): ?>
                <a href="<?= BASE_URL ?>/applicant/application/decline_details.php" class="btn btn-danger btn-block">Review Declined Application</a>
            <?php elseif ($application['payment_status'] === 'verified' || $application['application_status'] === 'paid'): ?>
                <span class="badge badge-success btn-block" style="padding: 12px; font-size: 1rem;">Payment Settled & Verified</span>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold btn-block">Upload Cashier Receipt (PSU-OR)</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h3 class="card-heading">Payment Procedures</h3>
    </div>
    <ol style="padding-left: 20px; line-height: 2;">
        <li>Visit the <strong>University Cashier Office</strong> (Administration Building) or partner payment center.</li>
        <li>Inform the cashier that you are paying for the <strong>Vehicle Gate Pass Sticker</strong> (Student: ₱130.00 / Employee: ₱280.00).</li>
        <li>Present your School ID and Application Number (<strong><?= $application ? e($application['application_number']) : 'Your App Number' ?></strong>).</li>
        <li>Receive your printed <strong>PSU Official Receipt (PSU-OR)</strong> with a printed receipt number.</li>
        <li>Take a clear, well-lit photo or scanned PDF of the receipt and upload it via the portal.</li>
    </ol>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

