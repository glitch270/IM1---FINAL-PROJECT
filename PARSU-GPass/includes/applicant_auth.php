<?php
/**
 * Applicant Route Protection Guard
 */

require_once __DIR__ . '/auth.php';

requireLogin();

if ($_SESSION['account_role'] !== 'applicant') {
    header("Location: " . BASE_URL . "/unauthorized.php");
    exit;
}

// Fetch applicant profile
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM applicant_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentApplicant = $stmt->fetch();

if (!$currentApplicant) {
    setFlashMessage('error', 'Applicant profile could not be found. Please contact the administrator.');
    header("Location: " . BASE_URL . "/logout.php");
    exit;
}

$_SESSION['applicant_id'] = (int)$currentApplicant['applicant_id'];
$_SESSION['applicant_type'] = $currentApplicant['applicant_type'];
$_SESSION['display_name'] = trim($currentApplicant['first_name'] . ' ' . $currentApplicant['last_name']);

