<?php
/**
 * Application Wizard: Step 5 - Upload Required Documents
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$category = $draft['ownership']['ownership_category'] ?? null;

if (!$category) {
    setFlashMessage('warning', 'Please select an ownership classification before uploading documents.');
    header("Location: " . BASE_URL . "/applicant/application/ownership_type.php");
    exit;
}

$db = getDBConnection();
$reqStmt = $db->prepare("
    SELECT dr.*, dt.document_code, dt.document_name
    FROM document_requirements dr
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE dr.ownership_category = ?
    ORDER BY dr.sort_order ASC
");
$reqStmt->execute([$category]);
$requirements = $reqStmt->fetchAll();

$uploadedDocs = $draft['documents'] ?? [];

$pageTitle = "Apply for Gate Pass - Step 5: Document Requirements";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 5 of 6: Upload the verified documents required for your ownership type</p>
</div>

<div class="wizard-nav-wrap">
    <div class="wizard-steps-indicator">
        <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Personal</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Vehicle</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Ownership</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Signature</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="wizard-step-item active">
            <span class="step-num">5</span>
            <span class="step-title">Documents</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/review.php" class="wizard-step-item <?= !empty($uploadedDocs) ? '' : 'disabled' ?>">
            <span class="step-num">6</span>
            <span class="step-title">Review</span>
        </a>
    </div>
</div>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <div class="badge badge-primary" style="margin-bottom: 8px;">
            <?= e(getOwnershipCategoryLabel($category)) ?> (<?= count($requirements) ?> Required Documents)
        </div>
        <h2 class="card-heading">Document Upload Checklist</h2>
        <p class="card-subtext">Accepted formats: <strong>JPG, JPEG, PNG, or PDF</strong> (Maximum 5MB per file). Driver's licenses must include front and back in one file with 3 specimen signatures.</p>
    </div>

    <form action="<?= BASE_URL ?>/actions/document_upload_process.php" method="POST" enctype="multipart/form-data" class="standard-form" id="requirementsForm">
        <?= csrfInput() ?>

        <div class="document-upload-list">
            <?php foreach ($requirements as $index => $req): 
                $reqId = $req['requirement_id'];
                $alreadyUploaded = $uploadedDocs[$reqId] ?? null;
            ?>
                <div class="upload-item-card card <?= $alreadyUploaded ? 'has-uploaded' : 'pending-upload' ?>">
                    <div class="upload-item-header">
                        <div class="upload-number"><?= $index + 1 ?></div>
                        <div class="upload-details">
                            <h4 class="upload-title">
                                <?= e($req['display_label']) ?>
                                <span class="text-danger">*</span>
                            </h4>
                            <span class="upload-code-tag"><?= e($req['document_code']) ?> &bull; <?= e(strtoupper($req['subject_role'])) ?></span>
                        </div>
                        <div class="upload-status-indicator">
                            <?php if ($alreadyUploaded): ?>
                                <span class="badge badge-success">✓ Uploaded</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Required</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($alreadyUploaded): ?>
                        <div class="uploaded-file-banner">
                            <span class="file-icon">📄</span>
                            <span class="file-name"><?= e($alreadyUploaded['file_name']) ?></span>
                            <span class="file-size">(<?= number_format($alreadyUploaded['file_size'] / 1024, 1) ?> KB)</span>
                            <a href="<?= BASE_URL . '/' . e($alreadyUploaded['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="margin-left: auto;">View File</a>
                        </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-top: 10px;">
                        <label for="doc_<?= $reqId ?>" class="form-label">
                            <?= $alreadyUploaded ? 'Replace this file (optional):' : 'Select file to upload:' ?>
                        </label>
                        <input type="file" name="doc_<?= $reqId ?>" id="doc_<?= $reqId ?>" class="form-control file-input-check" accept=".jpg,.jpeg,.png,.pdf" <?= $alreadyUploaded ? '' : 'required' ?>>
                        <small class="form-hint">OR & CR must be separate uploads. Driver's License must be 1 file containing front, back, and 3 signatures.</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="btn btn-outline-secondary">← Back: Signature</a>
            <button type="submit" class="btn btn-primary btn-lg" id="uploadSubmitBtn">
                Save Documents & Proceed to Review →
            </button>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/assets/js/requirements.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

