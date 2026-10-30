<?php
/**
 * Administrator Route Protection Guard
 */

require_once __DIR__ . '/auth.php';

requireLogin();

if ($_SESSION['account_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/unauthorized.php");
    exit;
}

// Ensure account is active
$db = getDBConnection();
$stmt = $db->prepare("SELECT user_id, email, account_role, account_status FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$adminUser = $stmt->fetch();

if (!$adminUser || $adminUser['account_status'] !== 'active') {
    setFlashMessage('error', 'Your administrator account is inactive or suspended.');
    header("Location: " . BASE_URL . "/logout.php");
    exit;
}

$_SESSION['display_name'] = $_SESSION['display_name'] ?? 'Administrator';

