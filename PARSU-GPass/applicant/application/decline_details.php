<?php
/**
 * View Decline Details and Admin Remarks
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Get latest application
$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number, o.ownership_category
    FROM applications a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application || $application['application_status'] !== 'declined') {
    setFlashMessage('info', 'Your application is not in declined status.');
    header("Location: " . BASE_URL . "/applicant/application/view.php");
    exit;
}

$appId = (int)$application['application_id'];

// Get all reviews for this application
$revStmt = $db->prepare("
    SELECT r.*, u.email as admin_email
    FROM application_reviews r
    JOIN users u ON r.admin_id = u.user_id
    WHERE r.application_id = ? AND r.review_decision = 'declined'
    ORDER BY r.reviewed_at DESC
");
$revStmt->execute([$appId]);
$reviews = $revStmt->fetchAll();

// Get current active documents
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

$pageTitle = "Decline Details - " . $application['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Application Decline Notice</h1>
        <p class="page-subtitle">Application Number: <?= e($application['application_number']) ?> &bull; Vehicle: <?= e($application['plate_number']) ?></p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/applicant/application/resubmit.php" class="btn btn-danger btn-lg">
            🔄 Resubmit Application
        </a>
    </div>
</div>

<div class="card alert-card-danger" style="border-left: 5px solid #d32f2f; margin-bottom: 25px;">
    <div class="card-header-tabs">
        <h2 class="card-heading text-danger">Administrator Remarks & Reasons for Decline</h2>
        <p class="card-subtext">Review the comments provided by the campus security evaluator below.</p>
    </div>

    <div class="review-history-list">
        <?php foreach ($reviews as $rev): ?>
            <div class="review-comment-card card" style="background: #fff8f8; margin-bottom: 15px;">
                <div class="review-comment-header">
                    <span class="badge badge-danger">Declined</span>
                    <span class="text-muted">Reviewed on <?= date('F d, Y h:i A', strtotime($rev['reviewed_at'])) ?> by Security Admin</span>
                </div>
                <div class="review-comment-body" style="font-size: 1.05rem; margin-top: 10px; color: #333;">
                    <strong>Remarks / Required Action:</strong><br>
                    <?= nl2br(e($rev['remarks'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header-flex">
        <div>
            <h3 class="card-heading">Current Attached Documents</h3>
            <span class="card-subtext">Click "Replace Document" to upload updated or clearer versions of rejected files.</span>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/applicant/application/resubmit.php" class="btn btn-primary">Go to Resubmission Page →</a>
        </div>
    </div>

    <div class="table-responsive" style="margin-top: 15px;">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Document Requirement</th>
                    <th>Current File Name</th>
                    <th>Version</th>
                    <th>Uploaded At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $i => $doc): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= e($doc['display_label']) ?></strong><br>
                            <small class="text-muted"><?= e($doc['document_code']) ?></small>
                        </td>
                        <td><?= e($doc['file_name']) ?></td>
                        <td><span class="badge badge-secondary">v<?= e($doc['version_number']) ?></span></td>
                        <td><?= date('M d, Y h:i A', strtotime($doc['uploaded_at'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View</a>
                            <a href="<?= BASE_URL ?>/applicant/documents/replace_document.php?requirement_id=<?= $doc['requirement_id'] ?>" class="btn btn-warning btn-sm">Replace Document</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

