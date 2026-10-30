<?php
/**
 * Admin: Dedicated Approve Confirmation Page
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

$pageTitle = "Approve Application - " . $app['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Approve Application</h1>
        <p class="page-subtitle">Verify and authorize vehicle gate pass application #<?= e($app['application_number']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading text-success">Confirm Approval</h2>
        <p class="card-subtext">Approving will advance this application to Payment Pending stage.</p>
    </div>

    <div class="alert alert-secondary">
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8;">
            <li><strong>Applicant:</strong> <?= e($app['first_name'] . ' ' . $app['last_name']) ?> (<?= e($app['school_id']) ?>)</li>
            <li><strong>Classification:</strong> <?= ucfirst(e($app['applicant_type'])) ?></li>
            <li><strong>Vehicle:</strong> <?= e($app['vehicle_type']) ?> (<?= e($app['plate_number']) ?>)</li>
            <li><strong>Gate Pass Fee:</strong> <?= formatCurrency(getFeeByApplicantType($app['applicant_type'])) ?></li>
        </ul>
    </div>

    <form action="<?= BASE_URL ?>/actions/application_review_process.php" method="POST" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">
        <input type="hidden" name="review_decision" value="approved">

        <div class="form-group">
            <label for="remarks" class="form-label">Approval Remarks (Optional):</label>
            <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="All requirements validated and verified in accordance with campus vehicle regulations."></textarea>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $appId ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-success btn-lg">✓ Authorize & Approve Application</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

