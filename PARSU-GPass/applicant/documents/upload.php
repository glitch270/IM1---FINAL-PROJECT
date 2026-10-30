<?php
/**
 * Applicant Documents Upload Routing Interface
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

// If user is currently in the wizard draft, redirect to step 5
if (isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/requirements.php");
    exit;
}

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("SELECT application_id, application_status FROM applications WHERE applicant_id = ? ORDER BY application_id DESC LIMIT 1");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

if ($application['application_status'] === 'declined') {
    header("Location: " . BASE_URL . "/applicant/application/resubmit.php");
    exit;
}

// If already submitted and not declined, view documents
header("Location: " . BASE_URL . "/applicant/documents/view_documents.php");
exit;

