<?php
/**
 * Admin: Dedicated Decline Interface (Remarks Mandatory)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$appId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, v.plate_number, v.vehicle_type
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.application_id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('admin/applications/pending.php', 'error', 'Application not found.');
}

$pageTitle = "Decline Application - " . $app['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Decline Application</h1>
        <p class="page-subtitle">Reject application #<?= e($app['application_number']) ?> and request corrective action.</p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading text-danger">Specify Decline Reasons</h2>
        <p class="card-subtext">Per Rule 6, a detailed explanation is mandatory. The applicant will read these remarks to replace files and resubmit.</p>
    </div>

    <div class="alert alert-secondary">
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8;">
            <li><strong>Applicant:</strong> <?= e($app['first_name'] . ' ' . $app['last_name']) ?> (<?= e($app['school_id']) ?>)</li>
            <li><strong>Vehicle:</strong> <?= e($app['vehicle_type']) ?> (<?= e($app['plate_number']) ?>)</li>
            <li><strong>Current Status:</strong> <?= getStatusBadge($app['application_status']) ?></li>
        </ul>
    </div>

    <form action="<?= BASE_URL ?>/actions/application_review_process.php" method="POST" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">
        <input type="hidden" name="review_decision" value="declined">

        <div class="form-group">
            <label for="decline_remarks" class="form-label">Decline Reason & Required Corrections <span class="text-danger">*</span></label>
            <textarea name="remarks" id="decline_remarks" rows="5" class="form-control" placeholder="Clearly describe why the application was declined (e.g. 'Certificate of Registration copy is illegible. Driver\'s license must show both front and back with three specimen signatures.')..." required></textarea>
            <small class="form-hint">Be specific to help the applicant quickly correct their submission.</small>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $appId ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-danger btn-lg">✕ Confirm Decline & Notify Applicant</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

