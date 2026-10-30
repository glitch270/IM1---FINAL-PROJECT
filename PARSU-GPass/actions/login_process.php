<?php
/**
 * User Login Process Action (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    redirect('login.php', 'error', 'Please provide both your email and password.');
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT user_id, email, password, account_role, account_status FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    redirect('login.php', 'error', 'Invalid email or password. Please verify your credentials and try again.');
}

if ($user['account_status'] !== 'active') {
    redirect('login.php', 'error', 'Your account has been deactivated or suspended. Please contact the Campus Security Administrator.');
}

// Regenerate session ID upon successful authentication
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['account_role'] = $user['account_role'];

if ($user['account_role'] === 'applicant') {
    $profStmt = $db->prepare("SELECT applicant_id, applicant_type, first_name, last_name FROM applicant_profiles WHERE user_id = ?");
    $profStmt->execute([$user['user_id']]);
    $profile = $profStmt->fetch();

    if ($profile) {
        $_SESSION['applicant_id'] = (int)$profile['applicant_id'];
        $_SESSION['applicant_type'] = $profile['applicant_type'];
        $_SESSION['display_name'] = trim($profile['first_name'] . ' ' . $profile['last_name']);
    } else {
        $_SESSION['display_name'] = 'Applicant';
    }

    redirect('applicant/dashboard.php', 'success', 'Welcome back, ' . $_SESSION['display_name'] . '!');
} else {
    $_SESSION['display_name'] = 'Campus Administrator';
    redirect('admin/dashboard.php', 'success', 'Welcome to the PARSU-GPass Admin Dashboard.');
}

