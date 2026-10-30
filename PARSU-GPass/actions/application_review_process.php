<?php
/**
 * Administrator Application Review Action (Approve / Decline)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/applications/pending.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicationId = (int)($_POST['application_id'] ?? 0);
$decision = trim($_POST['review_decision'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');
$adminId = (int)$_SESSION['user_id'];

if (!in_array($decision, ['approved', 'declined'], true)) {
    redirect('admin/applications/pending.php', 'error', 'Invalid review decision specified.');
}

// Rule 6: Decline REQUIRES a reason
if ($decision === 'declined' && empty($remarks)) {
    redirect("admin/applications/decline.php?id=$applicationId", 'error', 'A decline reason must be provided so the applicant knows what to correct.');
}

if ($decision === 'approved' && empty($remarks)) {
    $remarks = 'Application documents verified authentic and approved for vehicle gate pass.';
}

$db = getDBConnection();

$stmt = $db->prepare("SELECT application_id, application_number, application_status FROM applications WHERE application_id = ?");
$stmt->execute([$applicationId]);
$application = $stmt->fetch();

if (!$application) {
    redirect('admin/applications/pending.php', 'error', 'Application record not found.');
}

$prevStatus = $application['application_status'];
$newStatus = ($decision === 'approved') ? 'approved' : 'declined';

try {
    $db->beginTransaction();

    // 1. Update applications table
    $upd = $db->prepare("UPDATE applications SET application_status = ?, updated_at = NOW() WHERE application_id = ?");
    $upd->execute([$newStatus, $applicationId]);

    // 2. Insert into application_reviews
    $revStmt = $db->prepare("INSERT INTO application_reviews (application_id, admin_id, review_decision, remarks, reviewed_at) VALUES (?, ?, ?, ?, NOW())");
    $revStmt->execute([$applicationId, $adminId, $decision, $remarks]);

    // 3. Insert into application_status_history
    $histStmt = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $histStmt->execute([$applicationId, $prevStatus, $newStatus, $adminId, $remarks]);

    $db->commit();

    $msg = ($decision === 'approved')
        ? "Application " . $application['application_number'] . " successfully approved!"
        : "Application " . $application['application_number'] . " has been declined. The applicant can now view the remarks and resubmit.";

    redirect("admin/applications/view.php?id=$applicationId", 'success', $msg);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Application review error: " . $e->getMessage());
    redirect("admin/applications/view.php?id=$applicationId", 'error', 'Failed to save review decision. Please try again.');
}

