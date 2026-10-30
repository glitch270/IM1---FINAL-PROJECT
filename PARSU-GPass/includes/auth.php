<?php
/**
 * Authentication Helper and Session Check
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

/**
 * Checks if a user is currently logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['account_role']);
}

/**
 * Returns current logged-in user array or null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'      => (int)$_SESSION['user_id'],
        'email'        => $_SESSION['user_email'] ?? '',
        'account_role' => $_SESSION['account_role'] ?? '',
        'display_name' => $_SESSION['display_name'] ?? 'User'
    ];
}

/**
 * Enforces general login check
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please sign in to access this page.');
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

