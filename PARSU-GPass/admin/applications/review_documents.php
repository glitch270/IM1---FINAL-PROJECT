<?php
/**
 * Admin: Full-Screen Document Inspector
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$appId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type,
           v.vehicle_type, v.plate_number, o.ownership_category
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    WHERE a.application_id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    setFlashMessage('error', 'Application not found.');
    header("Location: " . BASE_URL . "/admin/applications/pending.php");
    exit;
}

// Fetch active documents
$docStmt = $db->prepare("
    SELECT ad.*, dr.display_label, dr.subject_role, dt.document_code, dt.document_name
    FROM application_documents ad
    JOIN document_requirements dr ON ad.requirement_id = dr.requirement_id
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE ad.application_id = ? AND ad.is_current = 1
    ORDER BY dr.sort_order ASC
");
$docStmt->execute([$appId]);
$documents = $docStmt->fetchAll();

$selectedIndex = (int)($_GET['doc_index'] ?? 0);
if ($selectedIndex < 0 || $selectedIndex >= count($documents)) {
    $selectedIndex = 0;
}
$activeDoc = $documents[$selectedIndex] ?? null;

$pageTitle = "Inspect Documents: " . $app['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Document Inspector: <?= e($app['application_number']) ?></h1>
        <p class="page-subtitle"><?= e($app['first_name'] . ' ' . $app['last_name']) ?> (<?= e($app['school_id']) ?>) &bull; <?= e($app['plate_number']) ?></p>
    </div>
    <div class="header-action-group">
        <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $appId ?>" class="btn btn-outline-secondary">
            ← Back to Application View
        </a>
    </div>
</div>

<?php if (empty($documents)): ?>
    <div class="card empty-state-card">
        <p>No documents found for this application.</p>
    </div>
<?php else: ?>
    <div class="doc-inspector-layout">
        <!-- Sidebar: Document List -->
        <div class="doc-list-sidebar card">
            <h3 class="card-heading" style="margin-bottom: 15px;">Requirements (<?= count($documents) ?>)</h3>
            <ul class="inspector-doc-nav">
                <?php foreach ($documents as $idx => $doc): ?>
                    <li class="<?= $idx === $selectedIndex ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/applications/review_documents.php?id=<?= $appId ?>&doc_index=<?= $idx ?>">
                            <span class="doc-idx-badge"><?= $idx + 1 ?></span>
                            <div class="doc-info-col">
                                <span class="doc-name-text"><?= e($doc['display_label']) ?></span>
                                <small class="text-muted"><?= e($doc['file_name']) ?> (v<?= e($doc['version_number']) ?>)</small>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Preview Area -->
        <div class="doc-preview-area card">
            <?php if ($activeDoc): 
                $filePath = BASE_URL . '/' . e($activeDoc['file_path']);
                $isPdf = strtolower(pathinfo($activeDoc['file_path'], PATHINFO_EXTENSION)) === 'pdf';
            ?>
                <div class="preview-header">
                    <div>
                        <h3 class="preview-title"><?= e($activeDoc['display_label']) ?></h3>
                        <span class="preview-meta"><?= e($activeDoc['file_name']) ?> &bull; <?= number_format($activeDoc['file_size'] / 1024, 1) ?> KB &bull; Version <?= e($activeDoc['version_number']) ?></span>
                    </div>
                    <div>
                        <a href="<?= $filePath ?>" target="_blank" class="btn btn-outline-primary btn-sm">Open in New Tab ↗</a>
                    </div>
                </div>

                <div class="preview-viewport" style="margin-top: 15px; min-height: 550px; background: #525659; display: flex; align-items: center; justify-content: center; border-radius: 6px; overflow: hidden;">
                    <?php if ($isPdf): ?>
                        <iframe src="<?= $filePath ?>" style="width: 100%; height: 600px; border: none;"></iframe>
                    <?php else: ?>
                        <img src="<?= $filePath ?>" alt="Document Preview" style="max-width: 100%; max-height: 600px; object-fit: contain;">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

