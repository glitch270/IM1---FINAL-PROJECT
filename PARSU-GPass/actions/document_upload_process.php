<?php
/**
 * Application Document Upload Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';
require_once __DIR__ . '/../includes/file_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/application/requirements.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

if (!isset($_SESSION['application_draft'])) {
    redirect('applicant/application/create.php', 'warning', 'Please start a new application.');
}

$draft = &$_SESSION['application_draft'];
$category = $draft['ownership']['ownership_category'] ?? null;

if (!$category) {
    redirect('applicant/application/ownership_type.php', 'error', 'Ownership category is missing.');
}

$db = getDBConnection();
$reqStmt = $db->prepare("
    SELECT dr.requirement_id, dr.display_label, dt.document_code
    FROM document_requirements dr
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE dr.ownership_category = ?
");
$reqStmt->execute([$category]);
$requirements = $reqStmt->fetchAll();

if (!isset($draft['documents'])) {
    $draft['documents'] = [];
}

$uploadCount = 0;
$uploadErrors = [];

foreach ($requirements as $req) {
    $reqId = $req['requirement_id'];
    $inputKey = "doc_" . $reqId;

    if (isset($_FILES[$inputKey]) && $_FILES[$inputKey]['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES[$inputKey];
        $targetInfo = getUploadFolderByDocCode($req['document_code']);
        
        $result = storeUploadedFile($file, $targetInfo['dir'], $targetInfo['subfolder']);

        if ($result['success']) {
            $draft['documents'][$reqId] = [
                'requirement_id' => $reqId,
                'file_name'      => $file['name'],
                'file_path'      => $result['path'],
                'file_type'      => $result['mime'],
                'file_size'      => $result['size']
            ];
            $uploadCount++;
        } else {
            $uploadErrors[] = $req['display_label'] . ': ' . $result['error'];
        }
    }
}

if (!empty($uploadErrors)) {
    redirect('applicant/application/requirements.php', 'error', implode('<br>', $uploadErrors));
}

// Check how many are missing
$missingCount = 0;
foreach ($requirements as $req) {
    if (empty($draft['documents'][$req['requirement_id']])) {
        $missingCount++;
    }
}

if ($missingCount === 0) {
    redirect('applicant/application/review.php', 'success', 'All required documents uploaded successfully.');
} else {
    redirect('applicant/application/requirements.php', 'warning', "Saved uploaded files. Still missing $missingCount required document(s).");
}

