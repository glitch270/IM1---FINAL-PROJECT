<?php
/**
 * Document Replacement Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';
require_once __DIR__ . '/../includes/file_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/dashboard.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicationId = (int)($_POST['application_id'] ?? 0);
$requirementId = (int)($_POST['requirement_id'] ?? 0);
$applicantId = (int)$_SESSION['applicant_id'];
$userId = (int)$_SESSION['user_id'];

$db = getDBConnection();

// Verify application ownership and status
$stmt = $db->prepare("SELECT application_id, application_status FROM applications WHERE application_id = ? AND applicant_id = ?");
$stmt->execute([$applicationId, $applicantId]);
$application = $stmt->fetch();

if (!$application || $application['application_status'] !== 'declined') {
    redirect('applicant/dashboard.php', 'error', 'Only declined applications can accept document replacements.');
}

if (!isset($_FILES['replacement_file']) || $_FILES['replacement_file']['error'] === UPLOAD_ERR_NO_FILE) {
    redirect("applicant/documents/replace_document.php?requirement_id=$requirementId", 'error', 'Please choose a replacement file to upload.');
}

// Fetch requirement info for target folder
$reqStmt = $db->prepare("
    SELECT dr.requirement_id, dr.display_label, dt.document_code
    FROM document_requirements dr
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE dr.requirement_id = ?
");
$reqStmt->execute([$requirementId]);
$requirement = $reqStmt->fetch();

if (!$requirement) {
    redirect('applicant/dashboard.php', 'error', 'Invalid requirement identifier.');
}

$file = $_FILES['replacement_file'];
$targetInfo = getUploadFolderByDocCode($requirement['document_code']);
$storeResult = storeUploadedFile($file, $targetInfo['dir'], $targetInfo['subfolder']);

if (!$storeResult['success']) {
    redirect("applicant/documents/replace_document.php?requirement_id=$requirementId", 'error', $storeResult['error']);
}

try {
    $db->beginTransaction();

    // Find highest version number
    $vStmt = $db->prepare("SELECT COALESCE(MAX(version_number), 0) AS max_v FROM application_documents WHERE application_id = ? AND requirement_id = ?");
    $vStmt->execute([$applicationId, $requirementId]);
    $row = $vStmt->fetch();
    $nextVersion = ((int)$row['max_v']) + 1;

    // Archive previous versions
    $archStmt = $db->prepare("UPDATE application_documents SET is_current = 0 WHERE application_id = ? AND requirement_id = ?");
    $archStmt->execute([$applicationId, $requirementId]);

    // Insert new version
    $insStmt = $db->prepare("INSERT INTO application_documents (application_id, requirement_id, file_name, file_path, file_type, file_size, version_number, is_current, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())");
    $insStmt->execute([
        $applicationId,
        $requirementId,
        $file['name'],
        $storeResult['path'],
        $storeResult['mime'],
        $storeResult['size'],
        $nextVersion,
        $userId
    ]);

    $db->commit();

    redirect('applicant/application/resubmit.php', 'success', "Successfully replaced " . $requirement['display_label'] . " (version $nextVersion recorded).");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Document replace error: " . $e->getMessage());
    redirect("applicant/documents/replace_document.php?requirement_id=$requirementId", 'error', 'Failed to save replaced document. Please try again.');
}

