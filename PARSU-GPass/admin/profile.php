<?php
/**
 * Administrator Profile Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = getDBConnection();
$adminId = (int)$_SESSION['user_id'];

// Process password change if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPass, $user['password'])) {
        setFlashMessage('error', 'Your current password was entered incorrectly.');
    } elseif (strlen($newPass) < 8) {
        setFlashMessage('error', 'The new password must be at least 8 characters long.');
    } elseif ($newPass !== $confirmPass) {
        setFlashMessage('error', 'New password confirmation does not match.');
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $upd->execute([$hash, $adminId]);
        setFlashMessage('success', 'Administrator password updated successfully.');
    }
}

$stmt = $db->prepare("SELECT user_id, email, account_role, account_status, created_at FROM users WHERE user_id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$pageTitle = "Administrator Profile";
include __DIR__ . '/../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Administrator Account</h1>
        <p class="page-subtitle">Security administration credentials and system access parameters.</p>
    </div>
</div>

<div class="profile-layout-grid">
    <div class="card profile-card">
        <div class="profile-avatar-wrap">
            <div class="profile-avatar-large" style="background: #000035;">
                ADM
            </div>
            <h2 class="profile-fullname">Campus Security Admin</h2>
            <div class="profile-role-badge">
                <span class="badge badge-success">Active Administrator</span>
            </div>
        </div>

        <ul class="profile-meta-list">
            <li>
                <span class="meta-label">Email Address:</span>
                <span class="meta-value"><?= e($admin['email']) ?></span>
            </li>
            <li>
                <span class="meta-label">Role:</span>
                <span class="meta-value">System Administrator</span>
            </li>
            <li>
                <span class="meta-label">Account Status:</span>
                <span class="meta-value text-success font-bold"><?= strtoupper(e($admin['account_status'])) ?></span>
            </li>
            <li>
                <span class="meta-label">Created At:</span>
                <span class="meta-value"><?= date('F d, Y', strtotime($admin['created_at'])) ?></span>
            </li>
        </ul>
    </div>

    <div class="card profile-form-card">
        <div class="card-header-tabs">
            <h3 class="card-heading">Change Password</h3>
            <p class="card-subtext">Ensure your admin account uses a strong, secure password.</p>
        </div>

        <form action="<?= BASE_URL ?>/admin/profile.php" method="POST" class="standard-form">
            <?= csrfInput() ?>

            <div class="form-group">
                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required>
                    <small class="form-hint">At least 8 characters</small>
                </div>

                <div class="form-group col-md-6">
                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


