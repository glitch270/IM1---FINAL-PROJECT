<?php
/**
 * View All Uploaded Documents (Applicant Portal)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Get applicant's application
$appStmt = $db->prepare("SELECT application_id, application_number, application_status FROM applications WHERE applicant_id = ? ORDER BY application_id DESC LIMIT 1");
$appStmt->execute([$applicantId]);
$application = $appStmt->fetch();

$documents = [];
if ($application) {
    $docStmt = $db->prepare("
        SELECT ad.*, dr.display_label, dr.subject_role, dt.document_code, dt.document_name
        FROM application_documents ad
        JOIN document_requirements dr ON ad.requirement_id = dr.requirement_id
        JOIN document_types dt ON dr.document_type_id = dt.document_type_id
        WHERE ad.application_id = ?
        ORDER BY ad.is_current DESC, dr.sort_order ASC, ad.version_number DESC
    ");
    $docStmt->execute([$application['application_id']]);
    $documents = $docStmt->fetchAll();
}

$pageTitle = "My Uploaded Documents";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">My Vehicle Documents</h1>
        <p class="page-subtitle">Repository of all verification files and driver's licenses uploaded for your gate pass.</p>
    </div>
    <?php if ($application && $application['application_status'] === 'declined'): ?>
        <div>
            <a href="<?= BASE_URL ?>/applicant/application/resubmit.php" class="btn btn-danger">Resubmit Application</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$application): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">📁</div>
        <h2 class="empty-state-title">No Documents Uploaded Yet</h2>
        <p class="empty-state-desc">You will upload your documents when you apply for your vehicle gate pass.</p>
        <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary">Apply Now</a>
    </div>
<?php elseif (empty($documents)): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">📁</div>
        <h2 class="empty-state-title">No Uploaded Records</h2>
        <p class="empty-state-desc">No files associated with application <?= e($application['application_number']) ?>.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header-flex">
            <div>
                <h3 class="card-heading">Application Documents (Ref: <?= e($application['application_number']) ?>)</h3>
                <span class="card-subtext">Total <?= count($documents) ?> file(s) recorded across versions</span>
            </div>
            <div>
                <?= getStatusBadge($application['application_status']) ?>
            </div>
        </div>

        <div class="table-responsive" style="margin-top: 15px;">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Document Type / Label</th>
                        <th>Original Filename</th>
                        <th>MIME Type</th>
                        <th>Size</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Uploaded Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $i => $doc): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= e($doc['display_label']) ?></strong><br>
                                <small class="text-muted"><?= e($doc['document_code']) ?> &bull; <?= e(ucwords($doc['subject_role'])) ?></small>
                            </td>
                            <td><?= e($doc['file_name']) ?></td>
                            <td><code><?= e($doc['file_type']) ?></code></td>
                            <td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td>
                            <td><span class="badge badge-secondary">v<?= e($doc['version_number']) ?></span></td>
                            <td>
                                <?php if ($doc['is_current']): ?>
                                    <span class="badge badge-success">Current Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Archived</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y h:i A', strtotime($doc['uploaded_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View</a>
                                <?php if ($doc['is_current'] && $application['application_status'] === 'declined'): ?>
                                    <a href="<?= BASE_URL ?>/applicant/documents/replace_document.php?requirement_id=<?= $doc['requirement_id'] ?>" class="btn btn-outline-warning btn-sm">Replace</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

