<?php
/**
 * Administrator Payment Verification Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/payments/pending.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$paymentId = (int)($_POST['payment_id'] ?? 0);
$status = trim($_POST['verification_status'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');
$adminId = (int)$_SESSION['user_id'];

if (!in_array($status, ['verified', 'rejected'], true)) {
    redirect('admin/payments/pending.php', 'error', 'Invalid verification decision.');
}

if ($status === 'rejected' && empty($remarks)) {
    redirect("admin/payments/view.php?id=$paymentId", 'error', 'A remark is required when rejecting a payment receipt.');
}

if ($status === 'verified' && empty($remarks)) {
    $remarks = 'Official receipt validated against university cashier records.';
}

$db = getDBConnection();

$stmt = $db->prepare("
    SELECT p.*, a.application_id, a.application_number, a.application_status
    FROM payments p
    JOIN applications a ON p.application_id = a.application_id
    WHERE p.payment_id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    redirect('admin/payments/pending.php', 'error', 'Payment record not found.');
}

$appId = (int)$payment['application_id'];
$prevAppStatus = $payment['application_status'];
$newAppStatus = ($status === 'verified') ? 'paid' : 'payment_pending';

try {
    $db->beginTransaction();

    // 1. Update payments table
    $updPay = $db->prepare("
        UPDATE payments
        SET payment_status = ?, verified_by = ?, verification_remarks = ?, verified_at = NOW()
        WHERE payment_id = ?
    ");
    $updPay->execute([$status, $adminId, $remarks, $paymentId]);

    // 2. Update applications table
    $updApp = $db->prepare("UPDATE applications SET application_status = ?, updated_at = NOW() WHERE application_id = ?");
    $updApp->execute([$newAppStatus, $appId]);

    // 3. Insert status history
    $histMsg = ($status === 'verified')
        ? "Payment verified (PSU-OR #{$payment['psu_or_number']}). Status advanced to Paid."
        : "Payment receipt rejected: $remarks";

    $hist = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $hist->execute([$appId, $prevAppStatus, $newAppStatus, $adminId, $histMsg]);

    $db->commit();

    $notice = ($status === 'verified')
        ? "Payment of " . formatCurrency($payment['amount']) . " (PSU-OR #{$payment['psu_or_number']}) verified successfully! Gate pass sticker can now be generated."
        : "Payment receipt marked as rejected. The applicant will be notified to re-upload.";

    redirect("admin/payments/view.php?id=$paymentId", 'success', $notice);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Payment verification error: " . $e->getMessage());
    redirect("admin/payments/view.php?id=$paymentId", 'error', 'Failed to process payment verification. Please try again.');
}

