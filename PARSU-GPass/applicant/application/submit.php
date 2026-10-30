<?php
/**
 * Application Wizard: Final Submission Handler
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/applicant/application/review.php");
    exit;
}

requireCsrfToken();

if (!isset($_SESSION['application_draft'])) {
    redirect('applicant/dashboard.php', 'error', 'No active application session found.');
}

$draft = $_SESSION['application_draft'];
$applicantId = (int)$_SESSION['applicant_id'];
$userId = (int)$_SESSION['user_id'];

$vehicle = $draft['vehicle'] ?? [];
$ownership = $draft['ownership'] ?? [];
$signature = $draft['signature'] ?? null;
$documents = $draft['documents'] ?? [];

$category = $ownership['ownership_category'] ?? null;

if (empty($vehicle) || !$category || empty($signature) || empty($documents)) {
    redirect('applicant/application/review.php', 'error', 'Incomplete application data. Please verify all sections.');
}

$db = getDBConnection();

// Check if applicant already has an application
$checkStmt = $db->prepare("SELECT application_id FROM applications WHERE applicant_id = ? AND application_status != 'declined' LIMIT 1");
$checkStmt->execute([$applicantId]);
if ($checkStmt->fetch()) {
    redirect('applicant/application/view.php', 'warning', 'You already have an active submitted application.');
}

// Fetch requirements to ensure none are missing
$reqStmt = $db->prepare("SELECT requirement_id FROM document_requirements WHERE ownership_category = ?");
$reqStmt->execute([$category]);
$requiredIds = $reqStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requiredIds as $rId) {
    if (empty($documents[$rId])) {
        redirect('applicant/application/requirements.php', 'error', 'Missing one or more required document uploads.');
    }
}

try {
    $db->beginTransaction();

    // 1. Insert vehicle
    $vStmt = $db->prepare("INSERT INTO vehicles (applicant_id, vehicle_type, year_model, plate_number, color, license_number, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $vStmt->execute([
        $applicantId,
        $vehicle['vehicle_type'],
        $vehicle['year_model'],
        strtoupper($vehicle['plate_number']),
        $vehicle['color'],
        strtoupper($vehicle['license_number'])
    ]);
    $vehicleId = (int)$db->lastInsertId();

    // 2. Generate application number
    $appNumber = generateApplicationNumber($db);

    // 3. Insert application
    $appStmt = $db->prepare("INSERT INTO applications (applicant_id, vehicle_id, application_number, application_status, submitted_at) VALUES (?, ?, ?, 'pending', NOW())");
    $appStmt->execute([$applicantId, $vehicleId, $appNumber]);
    $applicationId = (int)$db->lastInsertId();

    // 4. Insert ownership details
    $ownStmt = $db->prepare("INSERT INTO ownership_details (application_id, ownership_category, owner_name, buyer_name, previous_owner_name, relationship_to_applicant, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $ownStmt->execute([
        $applicationId,
        $category,
        $ownership['owner_name'] ?? null,
        $ownership['buyer_name'] ?? null,
        $ownership['previous_owner_name'] ?? null,
        $ownership['relationship_to_applicant'] ?? null
    ]);

    // 5. Insert signature
    $sigStmt = $db->prepare("INSERT INTO application_signatures (application_id, signature_file, version_number, is_current, signed_at) VALUES (?, ?, 1, 1, NOW())");
    $sigStmt->execute([$applicationId, $signature['path']]);

    // 6. Insert documents
    $docStmt = $db->prepare("INSERT INTO application_documents (application_id, requirement_id, file_name, file_path, file_type, file_size, version_number, is_current, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, NOW())");
    foreach ($documents as $rId => $doc) {
        $docStmt->execute([
            $applicationId,
            $rId,
            $doc['file_name'],
            $doc['file_path'],
            $doc['file_type'],
            $doc['file_size'],
            $userId
        ]);
    }

    // 7. Insert status history
    $histStmt = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, NULL, 'pending', ?, 'Initial application submitted for review.', NOW())");
    $histStmt->execute([$applicationId, $userId]);

    $db->commit();

    // Clear draft session
    unset($_SESSION['application_draft']);

    redirect('applicant/application/view.php', 'success', "Application $appNumber submitted successfully! Your documents are now under review.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Application submission error: " . $e->getMessage());
    redirect('applicant/application/review.php', 'error', 'An error occurred while saving your application. Please try again.');
}

