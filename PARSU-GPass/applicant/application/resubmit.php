<?php
/**
 * Application Resubmission Page
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Get the declined application
$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number, v.color, v.year_model, v.license_number,
           o.ownership_category, o.owner_name, o.buyer_name, o.previous_owner_name, o.relationship_to_applicant,
           sig.signature_file
    FROM applications a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    LEFT JOIN application_signatures sig ON a.application_id = sig.application_id AND sig.is_current = 1
    WHERE a.applicant_id = ? AND a.application_status = 'declined'
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application) {
    setFlashMessage('info', 'No declined application requires resubmission at this time.');
    header("Location: " . BASE_URL . "/applicant/application/view.php");
    exit;
}

$appId = (int)$application['application_id'];

// Fetch latest decline review
$revStmt = $db->prepare("SELECT remarks, reviewed_at FROM application_reviews WHERE application_id = ? AND review_decision = 'declined' ORDER BY reviewed_at DESC LIMIT 1");
$revStmt->execute([$appId]);
$latestDecline = $revStmt->fetch();

// Fetch current documents
$docStmt = $db->prepare("
    SELECT ad.*, dr.display_label, dr.subject_role, dt.document_code
    FROM application_documents ad
    JOIN document_requirements dr ON ad.requirement_id = dr.requirement_id
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE ad.application_id = ? AND ad.is_current = 1
    ORDER BY dr.sort_order ASC
");
$docStmt->execute([$appId]);
$documents = $docStmt->fetchAll();

$pageTitle = "Resubmit Gate Pass Application - " . $application['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Resubmit Application</h1>
        <p class="page-subtitle">Application #<?= e($application['application_number']) ?> &bull; Update required documents and re-notify the admin evaluator.</p>
    </div>
</div>

<!-- Decline Reason Reminder Banner -->
<?php if ($latestDecline): ?>
    <div class="alert alert-danger" style="margin-bottom: 25px;">
        <div class="alert-title">Evaluator Decline Remarks:</div>
        <p style="font-size: 1.05rem; margin-top: 5px;"><?= nl2br(e($latestDecline['remarks'])) ?></p>
        <small class="text-muted">Reviewed on <?= date('F d, Y h:i A', strtotime($latestDecline['reviewed_at'])) ?></small>
    </div>
<?php endif; ?>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <h2 class="card-heading">1. Verify / Replace Documents</h2>
        <p class="card-subtext">Ensure all requested document corrections have been made.</p>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Required Document</th>
                    <th>Current File</th>
                    <th>Version</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $i => $doc): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= e($doc['display_label']) ?></strong>
                        </td>
                        <td>
                            <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="link-primary"><?= e($doc['file_name']) ?></a>
                        </td>
                        <td><span class="badge badge-secondary">v<?= e($doc['version_number']) ?></span></td>
                        <td>
                            <a href="<?= BASE_URL ?>/applicant/documents/replace_document.php?requirement_id=<?= $doc['requirement_id'] ?>" class="btn btn-warning btn-sm">Replace Document</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form action="<?= BASE_URL ?>/actions/resubmit_process.php" method="POST" class="standard-form" style="margin-top: 30px;">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">

        <div class="form-group">
            <label for="resubmission_remarks" class="form-label">Applicant Explanation / Resubmission Notes <span class="text-danger">*</span></label>
            <textarea name="resubmission_remarks" id="resubmission_remarks" rows="4" class="form-control" placeholder="Describe the corrections or replacements made (e.g., 'Uploaded clear copy of LTO CR and updated driver\'s license with 3 signatures as requested.')..." required></textarea>
        </div>

        <div class="form-group agreement-checkbox">
            <label class="custom-control-label">
                <input type="checkbox" name="confirm_resubmit" value="1" required>
                <span>I confirm that I have addressed all administrator decline reasons and updated all necessary documents.</span>
            </label>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/decline_details.php" class="btn btn-outline-secondary">← Back to Decline Details</a>
            <button type="submit" class="btn btn-danger btn-lg">
                🔄 Finalize & Resubmit Application
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

