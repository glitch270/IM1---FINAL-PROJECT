<?php
/**
 * User Logout Action
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/notifications.php';

// Clear session data
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start fresh session to set flash notice
session_start();
setFlashMessage('info', 'You have been successfully signed out of PARSU-GPass.');
header("Location: " . BASE_URL . "/login.php");
exit;

