<?php
/**
 * Applicant Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';

$pageTitle = "Applicant Dashboard";
include __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Get applicant's latest application with vehicle & payment & sticker info
$query = "SELECT a.*, v.vehicle_type, v.plate_number, v.year_model, v.color,
                 p.payment_id, p.payment_status, p.amount, p.psu_or_number,
                 s.sticker_id, s.sticker_number, s.sticker_status, s.valid_until,
                 rel.released_at, rel.received_by
          FROM applications a
          LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
          LEFT JOIN payments p ON a.application_id = p.application_id
          LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
          LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
          WHERE a.applicant_id = ?
          ORDER BY a.application_id DESC LIMIT 1";

$stmt = $db->prepare($query);
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

// Calculate progress step
$currentStep = 1;
if ($application) {
    $status = $application['application_status'];
    if (in_array($status, ['pending', 'resubmitted'])) {
        $currentStep = 2; // Under review
    } elseif ($status === 'declined') {
        $currentStep = 2; // Declined
    } elseif ($status === 'approved' || $status === 'payment_pending') {
        $currentStep = 3; // Payment pending
    } elseif ($status === 'payment_verification') {
        $currentStep = 3; // Verification
    } elseif ($status === 'paid' || $status === 'for_release') {
        $currentStep = 4; // Ready for release
    } elseif ($status === 'released') {
        $currentStep = 5; // Gate pass active
    }
}
?>

<div class="dashboard-header-banner">
    <div class="banner-text">
        <h1 class="dashboard-heading">Welcome, <?= e($currentApplicant['first_name']) ?>!</h1>
        <p class="dashboard-sub">
            <?= strtoupper(e($currentApplicant['applicant_type'])) ?> PORTAL &bull; <?= e($currentApplicant['school_id']) ?> &bull; <?= e($currentApplicant['department']) ?>
        </p>
    </div>
    <div class="banner-badge">
        <span class="badge-role-tag"><?= strtoupper(e($currentApplicant['applicant_type'])) ?></span>
    </div>
</div>

<div class="dashboard-metrics-grid">
    <div class="metric-card card">
        <div class="metric-icon icon-primary">📄</div>
        <div class="metric-info">
            <span class="metric-label">Application Status</span>
            <div class="metric-value">
                <?= $application ? getStatusBadge($application['application_status']) : '<span class="badge badge-secondary">No Application</span>' ?>
            </div>
            <small class="metric-subtext"><?= $application ? 'Ref: ' . e($application['application_number']) : 'Ready to apply' ?></small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-gold">💳</div>
        <div class="metric-info">
            <span class="metric-label">Payment Status</span>
            <div class="metric-value">
                <?php if ($application && $application['payment_status']): ?>
                    <?= getStatusBadge($application['payment_status']) ?>
                <?php elseif ($application && in_array($application['application_status'], ['approved', 'payment_pending'])): ?>
                    <span class="badge badge-warning">Awaiting Payment</span>
                <?php else: ?>
                    <span class="badge badge-secondary">N/A</span>
                <?php endif; ?>
            </div>
            <small class="metric-subtext">
                <?= ($currentApplicant['applicant_type'] === 'employee') ? '₱280.00 (3 yrs)' : '₱130.00 (2 yrs)' ?>
            </small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-success">🏷️</div>
        <div class="metric-info">
            <span class="metric-label">Gate Pass Sticker</span>
            <div class="metric-value">
                <?php if ($application && $application['sticker_number']): ?>
                    <span class="badge <?= $application['sticker_status'] === 'released' ? 'badge-success' : 'badge-indigo' ?>">
                        <?= e($application['sticker_number']) ?>
                    </span>
                <?php else: ?>
                    <span class="badge badge-secondary">Not Issued</span>
                <?php endif; ?>
            </div>
            <small class="metric-subtext">
                <?= ($application && $application['sticker_status'] === 'released') ? 'Active on Campus' : 'Pending issuance' ?>
            </small>
        </div>
    </div>

    <div class="metric-card card">
        <div class="metric-icon icon-indigo">🏍️</div>
        <div class="metric-info">
            <span class="metric-label">Registered Vehicle</span>
            <div class="metric-value">
                <?= $application ? e($application['plate_number']) : '<span class="text-muted">None</span>' ?>
            </div>
            <small class="metric-subtext">
                <?= $application ? e($application['vehicle_type'] . ' (' . $application['year_model'] . ')') : 'Not registered' ?>
            </small>
        </div>
    </div>
</div>

<!-- Main Application Status Card -->
<?php if (!$application): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">📝</div>
        <h2 class="empty-state-title">No Active Gate Pass Application</h2>
        <p class="empty-state-desc">You haven't submitted a vehicle gate pass application yet. Get started in minutes by preparing your vehicle details and required documents.</p>
        <div class="empty-state-actions">
            <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary btn-lg">
                Start Gate Pass Application
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Active Application Overview -->
    <div class="card active-app-card">
        <div class="card-header-flex">
            <div>
                <h2 class="card-heading">Application Overview: <?= e($application['application_number']) ?></h2>
                <span class="card-subtext">Submitted on <?= date('F d, Y h:i A', strtotime($application['submitted_at'])) ?></span>
            </div>
            <div>
                <?= getStatusBadge($application['application_status']) ?>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="progress-tracker-wrap">
            <div class="progress-step <?= $currentStep >= 1 ? 'completed' : '' ?> <?= $currentStep === 1 ? 'current' : '' ?>">
                <div class="step-circle">1</div>
                <div class="step-label">Application Submitted</div>
            </div>
            <div class="progress-step-line <?= $currentStep > 1 ? 'completed' : '' ?>"></div>
            <div class="progress-step <?= $currentStep >= 2 ? ($application['application_status'] === 'declined' ? 'declined' : 'completed') : '' ?> <?= $currentStep === 2 ? 'current' : '' ?>">
                <div class="step-circle"><?= $application['application_status'] === 'declined' ? '!' : '2' ?></div>
                <div class="step-label"><?= $application['application_status'] === 'declined' ? 'Declined (Action Required)' : 'Document Review' ?></div>
            </div>
            <div class="progress-step-line <?= $currentStep > 2 ? 'completed' : '' ?>"></div>
            <div class="progress-step <?= $currentStep >= 3 ? 'completed' : '' ?> <?= $currentStep === 3 ? 'current' : '' ?>">
                <div class="step-circle">3</div>
                <div class="step-label">Fee Payment</div>
            </div>
            <div class="progress-step-line <?= $currentStep > 3 ? 'completed' : '' ?>"></div>
            <div class="progress-step <?= $currentStep >= 4 ? 'completed' : '' ?> <?= $currentStep === 4 ? 'current' : '' ?>">
                <div class="step-circle">4</div>
                <div class="step-label">Sticker Ready</div>
            </div>
            <div class="progress-step-line <?= $currentStep > 4 ? 'completed' : '' ?>"></div>
            <div class="progress-step <?= $currentStep >= 5 ? 'completed' : '' ?> <?= $currentStep === 5 ? 'current' : '' ?>">
                <div class="step-circle">5</div>
                <div class="step-label">Released</div>
            </div>
        </div>

        <!-- Status Context Alert / Call to Action -->
        <?php if ($application['application_status'] === 'declined'): ?>
            <div class="alert alert-danger" style="margin-top: 20px;">
                <div class="alert-title">⚠️ Application Requires Attention</div>
                <p>Your gate pass application was reviewed and declined by the Campus Security Administrator. Please review the reasons and upload the corrected files.</p>
                <div style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/applicant/application/decline_details.php" class="btn btn-danger btn-sm">View Decline Details & Resubmit</a>
                </div>
            </div>
        <?php elseif ($application['application_status'] === 'approved' || $application['application_status'] === 'payment_pending'): ?>
            <div class="alert alert-success" style="margin-top: 20px;">
                <div class="alert-title">🎉 Application Approved! Settle Payment</div>
                <p>Your vehicle registration and documents have been approved. Please pay the cashier amount of <strong><?= formatCurrency(getFeeByApplicantType($currentApplicant['applicant_type'])) ?></strong> and upload your PSU-OR receipt.</p>
                <div style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold btn-sm">Upload Payment Receipt</a>
                </div>
            </div>
        <?php elseif ($application['application_status'] === 'payment_verification'): ?>
            <div class="alert alert-info" style="margin-top: 20px;">
                <div class="alert-title">⏳ Payment Under Verification</div>
                <p>Your payment receipt (PSU-OR #<?= e($application['psu_or_number'] ?? 'N/A') ?>) is being verified by university cashier administration.</p>
            </div>
        <?php elseif ($application['application_status'] === 'for_release'): ?>
            <div class="alert alert-primary" style="margin-top: 20px;">
                <div class="alert-title">🏷️ Sticker Ready for Release</div>
                <p>Your gate pass sticker <strong><?= e($application['sticker_number']) ?></strong> has been generated! You can claim the physical sticker at the Campus Security Office.</p>
                <div style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-primary btn-sm">View Sticker Card</a>
                </div>
            </div>
        <?php elseif ($application['application_status'] === 'released'): ?>
            <div class="alert alert-success" style="margin-top: 20px;">
                <div class="alert-title">✅ Gate Pass Active & Released</div>
                <p>Sticker #<strong><?= e($application['sticker_number']) ?></strong> is active and valid until <strong><?= date('F d, Y', strtotime($application['valid_until'])) ?></strong>.</p>
                <div style="margin-top: 10px;">
                    <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-outline-success btn-sm">View Sticker & Certificate</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-action-buttons" style="margin-top: 20px;">
            <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-outline-primary">View Full Details</a>
            <a href="<?= BASE_URL ?>/applicant/documents/view_documents.php" class="btn btn-outline-secondary">My Documents</a>
            <a href="<?= BASE_URL ?>/applicant/payment/payment_status.php" class="btn btn-outline-secondary">Payment History</a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

