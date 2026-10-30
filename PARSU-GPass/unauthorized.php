<?php
/**
 * Unauthorized Access (403 Forbidden)
 */
http_response_code(403);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = "403 - Access Denied";
include __DIR__ . '/includes/header.php';

$role = $_SESSION['account_role'] ?? null;
$returnUrl = $role ? BASE_URL . "/$role/dashboard.php" : BASE_URL . "/index.php";
?>

<div class="unauthorized-page-container">
    <div class="card error-card">
        <div class="error-badge">403</div>
        <div class="error-icon">🚫</div>
        <h1 class="error-title">Access Restricted</h1>
        <p class="error-message">
            You do not have administrative or appropriate permissions to access this specific portal section or action.
        </p>
        <div class="error-actions">
            <a href="<?= e($returnUrl) ?>" class="btn btn-primary btn-lg">
                Return to <?= $role ? ucfirst(e($role)) . ' Dashboard' : 'Homepage' ?>
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary btn-lg">Sign In with Credentials</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

