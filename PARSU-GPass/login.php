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
            <div class="auth-logo-wrap">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="University Logo" class="auth-logo-img">
            </div>
            <h1 class="auth-title">PARSU-<span>GPass</span></h1>
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
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                        <span class="input-icon">✉️</span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="user@parsu.edu.ph" required autofocus>
                    </div>
                    <div class="form-error-feedback" id="emailError"></div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    </div>
                    <div class="input-icon-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">👁️</button>
                    </div>
                    <div class="form-error-feedback" id="passwordError"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginSubmitBtn">
                        Sign In to Portal
                    </button>
                </div>
            </form>

            <div class="auth-footer-links">
                <p>Don't have an account yet? <a href="<?= BASE_URL ?>/register.php" class="link-gold font-bold">Register as Applicant</a></p>
                <p><a href="<?= BASE_URL ?>/index.php" class="link-muted">← Back to Homepage</a></p>
            </div>
        </div>

        <!-- Quick Demo Credentials Guide for Evaluation -->
        <div class="demo-credentials-box">
            <div class="demo-header">🔑 Test Accounts (XAMPP Seed Data):</div>
            <div class="demo-grid">
                <div class="demo-item">
                    <strong>Admin:</strong> <code>admin@parsu.edu.ph</code> / <code>Admin@123456</code>
                </div>
                <div class="demo-item">
                    <strong>Student:</strong> <code>student@parsu.edu.ph</code> / <code>Student@123456</code>
                </div>
                <div class="demo-item">
                    <strong>Employee:</strong> <code>employee@parsu.edu.ph</code> / <code>Employee@123456</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$customJs = ['validation.js'];
include __DIR__ . '/includes/footer.php'; 
?>

