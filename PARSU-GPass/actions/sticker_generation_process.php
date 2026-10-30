<?php
/**
 * Administrator Gate Pass Sticker Generation Action (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/gatepass/sticker_list.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicationId = (int)($_POST['application_id'] ?? 0);
$stickerNumber = trim($_POST['sticker_number'] ?? '');
$applicantType = trim($_POST['applicant_type'] ?? '');
$issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
$validUntil = trim($_POST['valid_until'] ?? '');
$adminId = (int)$_SESSION['user_id'];

$db = getDBConnection();

// Verify application and payment status
$stmt = $db->prepare("
    SELECT a.*, ap.applicant_type, p.payment_status
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    WHERE a.application_id = ?
");
$stmt->execute([$applicationId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('admin/applications/approved.php', 'error', 'Application not found.');
}

// Rule 9: Generate sticker ONLY after payment is verified
if ($app['payment_status'] !== 'verified' && !in_array($app['application_status'], ['paid', 'for_release', 'released'])) {
    redirect("admin/applications/view.php?id=$applicationId", 'error', 'Stickers can only be generated after payment is verified.');
}

// Check if sticker already exists for this application
$checkStk = $db->prepare("SELECT sticker_id FROM gate_pass_stickers WHERE application_id = ?");
$checkStk->execute([$applicationId]);
if ($checkStk->fetch()) {
    redirect('admin/gatepass/sticker_list.php', 'info', 'A sticker has already been generated for this application.');
}

// Enforce server-side calculation for validity years
$type = $app['applicant_type'];
$validityYears = getValidityYearsByApplicantType($type);
$issueDate = date('Y-m-d');
$validUntil = date('Y-m-d', strtotime("+$validityYears years"));

// Ensure unique sticker number
if (empty($stickerNumber)) {
    $stickerNumber = generateStickerNumber($db);
}

try {
    $db->beginTransaction();

    // 1. Insert into gate_pass_stickers
    $ins = $db->prepare("
        INSERT INTO gate_pass_stickers (application_id, sticker_number, applicant_type, issue_date, valid_until, sticker_status, generated_by, generated_at)
        VALUES (?, ?, ?, ?, ?, 'for_release', ?, NOW())
    ");
    $ins->execute([$applicationId, $stickerNumber, $type, $issueDate, $validUntil, $adminId]);
    $stickerId = (int)$db->lastInsertId();

    $prevStatus = $app['application_status'];

    // 2. Update application status to for_release
    $updApp = $db->prepare("UPDATE applications SET application_status = 'for_release', updated_at = NOW() WHERE application_id = ?");
    $updApp->execute([$applicationId]);

    // 3. Insert status history
    $hist = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, 'for_release', ?, ?, NOW())");
    $hist->execute([$applicationId, $prevStatus, $adminId, "Generated Gate Pass Sticker #$stickerNumber. Status updated to For Release."]);

    $db->commit();

    redirect("admin/gatepass/sticker_details.php?id=$stickerId", 'success', "Sticker #$stickerNumber successfully generated and marked ready for physical release!");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Sticker generation error: " . $e->getMessage());
    redirect("admin/applications/view.php?id=$applicationId", 'error', 'Failed to generate sticker. Please try again.');
}

