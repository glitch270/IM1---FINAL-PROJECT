<?php
/**
 * Replace Document Interface (For Declined Applications)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];
$requirementId = (int)($_GET['requirement_id'] ?? 0);

// Fetch current declined application
$stmt = $db->prepare("SELECT application_id, application_number, application_status FROM applications WHERE applicant_id = ? AND application_status = 'declined' ORDER BY application_id DESC LIMIT 1");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application) {
    setFlashMessage('error', 'Only applications with "Declined" status can replace documents.');
    header("Location: " . BASE_URL . "/applicant/documents/view_documents.php");
    exit;
}

$appId = (int)$application['application_id'];

// Fetch requirement and current document
$reqStmt = $db->prepare("
    SELECT dr.*, dt.document_code, dt.document_name
    FROM document_requirements dr
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE dr.requirement_id = ?
");
$reqStmt->execute([$requirementId]);
$requirement = $reqStmt->fetch();

if (!$requirement) {
    setFlashMessage('error', 'Invalid document requirement selected.');
    header("Location: " . BASE_URL . "/applicant/documents/view_documents.php");
    exit;
}

// Fetch current active document
$docStmt = $db->prepare("SELECT * FROM application_documents WHERE application_id = ? AND requirement_id = ? AND is_current = 1 LIMIT 1");
$docStmt->execute([$appId, $requirementId]);
$currentDoc = $docStmt->fetch();

$pageTitle = "Replace Document: " . $requirement['display_label'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Replace Application Document</h1>
        <p class="page-subtitle">Ref: <?= e($application['application_number']) ?> &bull; <?= e($requirement['display_label']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading">Upload Corrected Document</h2>
        <p class="card-subtext">Uploading a new file will archive version <?= $currentDoc ? e($currentDoc['version_number']) : '1' ?> and create a new version.</p>
    </div>

    <?php if ($currentDoc): ?>
        <div class="alert alert-secondary">
            <strong>Current Active File:</strong> <?= e($currentDoc['file_name']) ?> (v<?= e($currentDoc['version_number']) ?>, <?= number_format($currentDoc['file_size'] / 1024, 1) ?> KB)<br>
            <a href="<?= BASE_URL . '/' . e($currentDoc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="margin-top: 8px;">View Current Active File</a>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/actions/document_replace_process.php" method="POST" enctype="multipart/form-data" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">
        <input type="hidden" name="requirement_id" value="<?= $requirementId ?>">

        <div class="form-group">
            <label for="replacement_file" class="form-label">Select Replacement File <span class="text-danger">*</span></label>
            <input type="file" name="replacement_file" id="replacement_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            <small class="form-hint">Accepted file formats: JPG, JPEG, PNG, PDF (Max: 5MB). If replacing a Driver's License, include front, back, and 3 signatures in one file.</small>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/applicant/application/resubmit.php" class="btn btn-outline-secondary">← Back to Resubmission</a>
            <button type="submit" class="btn btn-primary">Upload & Replace Document</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

