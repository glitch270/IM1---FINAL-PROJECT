<?php
/**
 * User & Administrator Login Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['account_role'] ?? 'applicant';
    header("Location: " . BASE_URL . "/$role/dashboard.php");
    exit;
}

$pageTitle = "Login to Portal";
$customCss = ['login.css'];
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-card-container">
        <div class="auth-brand-header">
            <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="Partido State University Logo" class="auth-logo-img">
            <h1 class="auth-title">PARSU-GPass</h1>
            <p class="auth-subtitle">Online Vehicle Gate Pass Management System</p>
        </div>

        <div class="auth-card card">
            <div class="card-header-tabs">
                <h2 class="auth-card-heading">Sign In to Your Account</h2>
                <p class="auth-card-desc">Enter your university registered email and password</p>
            </div>

            <form action="<?= BASE_URL ?>/actions/login_process.php" method="POST" class="auth-form" id="loginForm" novalidate>
                <?= csrfInput() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <input type="email" name="email" id="email" class="form-control" placeholder="enter your email" required autofocus>
                    </div>
                    <div class="form-error-feedback" id="emailError"></div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">Password</label>
                    </div>
                    <div class="input-icon-wrap">
                        <input type="password" name="password" id="password" class="form-control" placeholder="enter your password" required>
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <div class="form-error-feedback" id="passwordError"></div>
                </div>

                <div class="form-actions" style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginSubmitBtn">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="auth-footer-links">
                <p>Don't have an account yet? <a href="<?= BASE_URL ?>/register.php" class="link-dark font-bold">Register as Applicant</a></p>
                <p><a href="<?= BASE_URL ?>/index.php" class="link-muted">&larr; Back to Homepage</a></p>
            </div>
        </div>

    </div>
</div>

<?php 
$customJs = ['validation.js'];
include __DIR__ . '/includes/footer.php'; 
?>

