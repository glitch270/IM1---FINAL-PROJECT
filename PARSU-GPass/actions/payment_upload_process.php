<?php
/**
 * Applicant: Payment Receipt Upload Action (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';
require_once __DIR__ . '/../includes/file_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/dashboard.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicationId = (int)($_POST['application_id'] ?? 0);
$psuOrNumber = strtoupper(trim($_POST['psu_or_number'] ?? ''));
$paymentDate = trim($_POST['payment_date'] ?? '');
$applicantId = (int)$_SESSION['applicant_id'];
$userId = (int)$_SESSION['user_id'];

if (empty($psuOrNumber) || empty($paymentDate)) {
    redirect('applicant/payment/upload_receipt.php', 'error', 'PSU Official Receipt Number and payment date are required.');
}

$db = getDBConnection();

// Verify application ownership and status
$stmt = $db->prepare("SELECT a.*, ap.applicant_type FROM applications a JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id WHERE a.application_id = ? AND a.applicant_id = ?");
$stmt->execute([$applicationId, $applicantId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('applicant/dashboard.php', 'error', 'Application not found.');
}

// Rule 8: Payment allowed only after approval
$allowedStatuses = ['approved', 'payment_pending', 'payment_verification'];
if (!in_array($app['application_status'], $allowedStatuses)) {
    redirect('applicant/application/view.php', 'error', 'Payment receipt submission is only permitted for approved applications.');
}

// Rule 8: Amount is computed SERVER-SIDE; never trust browser
$computedAmount = getFeeByApplicantType($app['applicant_type']);

// Check uniqueness of psu_or_number (excluding current application's payment if updating)
$checkOr = $db->prepare("SELECT payment_id FROM payments WHERE psu_or_number = ? AND application_id != ? LIMIT 1");
$checkOr->execute([$psuOrNumber, $applicationId]);
if ($checkOr->fetch()) {
    redirect('applicant/payment/upload_receipt.php', 'error', 'That Official Receipt Number (PSU-OR #) has already been registered in the system.');
}

// Check existing payment
$existStmt = $db->prepare("SELECT payment_id, receipt_file FROM payments WHERE application_id = ?");
$existStmt->execute([$applicationId]);
$existingPayment = $existStmt->fetch();

// Receipt file processing
$receiptFilePath = $existingPayment['receipt_file'] ?? null;
if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['receipt_file'];
    $storeResult = storeUploadedFile($file, UPLOAD_RECEIPTS_PATH, 'payment_receipts');
    if (!$storeResult['success']) {
        redirect('applicant/payment/upload_receipt.php', 'error', $storeResult['error']);
    }
    $receiptFilePath = $storeResult['path'];
}

if (empty($receiptFilePath)) {
    redirect('applicant/payment/upload_receipt.php', 'error', 'Please upload a clear copy of your cashier payment receipt.');
}

try {
    $db->beginTransaction();

    if ($existingPayment) {
        $updPay = $db->prepare("UPDATE payments SET amount = ?, psu_or_number = ?, receipt_file = ?, payment_status = 'pending_verification', payment_date = ?, verification_remarks = NULL, verified_by = NULL, verified_at = NULL WHERE application_id = ?");
        $updPay->execute([$computedAmount, $psuOrNumber, $receiptFilePath, $paymentDate, $applicationId]);
    } else {
        $insPay = $db->prepare("INSERT INTO payments (application_id, amount, psu_or_number, receipt_file, payment_status, payment_date, created_at) VALUES (?, ?, ?, ?, 'pending_verification', ?, NOW())");
        $insPay->execute([$applicationId, $computedAmount, $psuOrNumber, $receiptFilePath, $paymentDate]);
    }

    $prevStatus = $app['application_status'];
    // Update application status to payment_verification
    $updApp = $db->prepare("UPDATE applications SET application_status = 'payment_verification', updated_at = NOW() WHERE application_id = ?");
    $updApp->execute([$applicationId]);

    // Insert history
    $hist = $db->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, 'payment_verification', ?, ?, NOW())");
    $hist->execute([$applicationId, $prevStatus, $userId, "Submitted cashier receipt PSU-OR #$psuOrNumber (Amount: " . formatCurrency($computedAmount) . ")"]);

    $db->commit();

    redirect('applicant/payment/payment_status.php', 'success', "Receipt for PSU-OR #$psuOrNumber submitted successfully! Verification is underway.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Payment upload error: " . $e->getMessage());
    redirect('applicant/payment/upload_receipt.php', 'error', 'Failed to save payment receipt. Please try again.');
}

