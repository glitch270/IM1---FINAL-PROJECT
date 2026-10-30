<?php
/**
 * Admin: System Settings & Profile Overview
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$adminId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("SELECT user_id, email, account_role, account_status, created_at FROM users WHERE user_id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$pageTitle = "System Settings & Profile";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">System Settings & Parameters</h1>
        <p class="page-subtitle">University vehicle policy configuration and administrative account details.</p>
    </div>
</div>

<div class="app-details-grid">
    <div class="app-details-main">
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Gate Pass Fee Assessment Matrix</h3>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Classification</th>
                        <th>Standard Fee</th>
                        <th>Sticker Validity</th>
                        <th>Required Verification</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>University Student</strong></td>
                        <td class="font-bold text-success"><?= formatCurrency(FEE_STUDENT) ?></td>
                        <td><?= VALIDITY_STUDENT_YEARS ?> Academic Years</td>
                        <td>Active Enrollment & Student ID</td>
                    </tr>
                    <tr>
                        <td><strong>Faculty & Staff Employee</strong></td>
                        <td class="font-bold text-success"><?= formatCurrency(FEE_EMPLOYEE) ?></td>
                        <td><?= VALIDITY_EMPLOYEE_YEARS ?> Academic Years</td>
                        <td>Plantilla / Employment Verification</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">System Environment & File Constraints</h3>
            </div>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 35%;">Application Name:</th>
                        <td><?= e(APP_FULL_NAME) ?></td>
                    </tr>
                    <tr>
                        <th>Governing Institution:</th>
                        <td><?= e(UNIVERSITY_NAME) ?></td>
                    </tr>
                    <tr>
                        <th>Max Upload File Size:</th>
                        <td><?= MAX_FILE_SIZE_BYTES / (1024 * 1024) ?> MB (per document)</td>
                    </tr>
                    <tr>
                        <th>Allowed Document Formats:</th>
                        <td><code><?= implode(', ', ALLOWED_EXTENSIONS) ?></code></td>
                    </tr>
                    <tr>
                        <th>Upload Storage Security:</th>
                        <td>Protected by <code>.htaccess</code> script execution barriers</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-details-sidebar">
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Admin Profile</h3>
            </div>
            <p><strong>Logged in as:</strong> <?= e($admin['email']) ?></p>
            <p><strong>Account Status:</strong> <span class="badge badge-success"><?= strtoupper(e($admin['account_status'])) ?></span></p>
            <div style="margin-top: 15px;">
                <a href="<?= BASE_URL ?>/admin/profile.php" class="btn btn-outline-primary btn-block">Change Admin Password</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

