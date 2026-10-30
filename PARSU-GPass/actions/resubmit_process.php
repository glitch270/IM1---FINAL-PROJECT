<?php
/**
 * Application Resubmission Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/dashboard.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicationId = (int)($_POST['application_id'] ?? 0);
$remarks = trim($_POST['resubmission_remarks'] ?? '');
$applicantId = (int)$_SESSION['applicant_id'];
$userId = (int)$_SESSION['user_id'];

if (empty($remarks)) {
    redirect('applicant/application/resubmit.php', 'error', 'Please provide an explanation of the corrections made.');
}

$db = getDBConnection();

// Verify application ownership and current status
$stmt = $db->prepare("SELECT application_id, application_number, application_status FROM applications WHERE application_id = ? AND applicant_id = ?");
$stmt->execute([$applicationId, $applicantId]);
$application = $stmt->fetch();

if (!$application || $application['application_status'] !== 'declined') {
    redirect('applicant/dashboard.php', 'error', 'Only declined applications can be resubmitted.');
}

try {
    $db->beginTransaction();

    // Update status to resubmitted
    $updStmt = $db->prepare("UPDATE applications SET application_status = 'resubmitted', updated_at = NOW() WHERE application_id = ?");
    $updStmt->execute([$applicationId]);

    // Insert into status history
    $histStmt = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, 'declined', 'resubmitted', ?, ?, NOW())");
    $histStmt->execute([$applicationId, $userId, "Applicant Resubmission: " . $remarks]);

    $db->commit();

    redirect('applicant/application/view.php', 'success', "Application " . $application['application_number'] . " resubmitted successfully! The administrator has been notified.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Resubmit error: " . $e->getMessage());
    redirect('applicant/application/resubmit.php', 'error', 'Failed to resubmit application. Please try again.');
}

