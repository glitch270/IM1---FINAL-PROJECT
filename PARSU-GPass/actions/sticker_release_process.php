<?php
/**
 * Administrator Physical Sticker Release Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/gatepass/sticker_list.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$stickerId = (int)($_POST['sticker_id'] ?? 0);
$receivedBy = trim($_POST['received_by'] ?? '');
$remarks = trim($_POST['release_remarks'] ?? '');
$adminId = (int)$_SESSION['user_id'];

if (empty($receivedBy)) {
    redirect("admin/gatepass/release.php?id=$stickerId", 'error', 'Please specify the name of the person receiving the physical sticker.');
}

$db = getDBConnection();

$stmt = $db->prepare("
    SELECT s.*, a.application_id, a.application_number, a.application_status
    FROM gate_pass_stickers s
    JOIN applications a ON s.application_id = a.application_id
    WHERE s.sticker_id = ?
");
$stmt->execute([$stickerId]);
$sticker = $stmt->fetch();

if (!$sticker) {
    redirect('admin/gatepass/sticker_list.php', 'error', 'Sticker record not found.');
}

if ($sticker['sticker_status'] === 'released') {
    redirect("admin/gatepass/sticker_details.php?id=$stickerId", 'info', 'Sticker is already marked as released.');
}

$appId = (int)$sticker['application_id'];
$prevAppStatus = $sticker['application_status'];

try {
    $db->beginTransaction();

    // Rule 10: Record the physical release (sticker_releases) before marking application released
    $insRel = $db->prepare("
        INSERT INTO sticker_releases (sticker_id, released_by, received_by, release_remarks, released_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insRel->execute([$stickerId, $adminId, $receivedBy, $remarks ?: 'Physical sticker handed over to driver.']);

    // Update sticker status to released
    $updStk = $db->prepare("UPDATE gate_pass_stickers SET sticker_status = 'released' WHERE sticker_id = ?");
    $updStk->execute([$stickerId]);

    // Update application status to released
    $updApp = $db->prepare("UPDATE applications SET application_status = 'released', updated_at = NOW() WHERE application_id = ?");
    $updApp->execute([$appId]);

    // Insert history
    $hist = $db->prepare("
        INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at)
        VALUES (?, ?, 'released', ?, ?, NOW())
    ");
    $hist->execute([$appId, $prevAppStatus, $adminId, "Physical sticker #{$sticker['sticker_number']} released to $receivedBy."]);

    $db->commit();

    redirect("admin/gatepass/sticker_details.php?id=$stickerId", 'success', "Sticker #{$sticker['sticker_number']} marked as released to $receivedBy! Gate pass is now fully active.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Sticker release error: " . $e->getMessage());
    redirect("admin/gatepass/release.php?id=$stickerId", 'error', 'Failed to record sticker release. Please try again.');
}

