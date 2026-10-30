<?php
/**
 * Application Wizard Entry Point
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Check if applicant has an existing application
$stmt = $db->prepare("SELECT application_id, application_number, application_status FROM applications WHERE applicant_id = ? ORDER BY application_id DESC LIMIT 1");
$stmt->execute([$applicantId]);
$existingApp = $stmt->fetch();

if ($existingApp) {
    $status = $existingApp['application_status'];
    if ($status === 'declined') {
        setFlashMessage('warning', 'You have a declined application. Please view the decline reasons and resubmit with updated documents.');
        header("Location: " . BASE_URL . "/applicant/application/decline_details.php");
        exit;
    } else {
        setFlashMessage('info', 'You already have an active application (' . htmlspecialchars($existingApp['application_number']) . '). You can view its progress below.');
        header("Location: " . BASE_URL . "/applicant/application/view.php");
        exit;
    }
}

// Initialize draft wizard session if not set
if (!isset($_SESSION['application_draft'])) {
    $_SESSION['application_draft'] = [
        'applicant_id' => $applicantId,
        'step'         => 1,
        'personal'     => [
            'applicant_type'      => $currentApplicant['applicant_type'],
            'school_id'           => $currentApplicant['school_id'],
            'first_name'          => $currentApplicant['first_name'],
            'middle_name'         => $currentApplicant['middle_name'],
            'last_name'           => $currentApplicant['last_name'],
            'suffix'              => $currentApplicant['suffix'],
            'contact_number'      => $currentApplicant['contact_number'],
            'department'          => $currentApplicant['department'],
            'course_year_section' => $currentApplicant['course_year_section']
        ],
        'vehicle'      => [],
        'ownership'    => [],
        'signature'    => null,
        'documents'    => []
    ];
}

header("Location: " . BASE_URL . "/applicant/application/personal_details.php");
exit;

