<?php
/**
 * Application Top Navbar Component
 */
$currentUser = getCurrentUser();
$role = $_SESSION['account_role'] ?? 'guest';
$displayName = $_SESSION['display_name'] ?? 'User';
$email = $_SESSION['user_email'] ?? '';
$profileLink = ($role === 'admin') ? BASE_URL . '/admin/profile.php' : BASE_URL . '/applicant/profile.php';
?>
<header class="app-topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle navigation menu">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>
        <div class="topbar-brand-mobile" style="display: flex; align-items: center; gap: 8px;">
            <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="PSU Logo" style="height: 28px; width: auto; object-fit: contain;">
            <span class="brand-title">PARSU-<span>GPass</span></span>
        </div>
    </div>

    <div class="topbar-right">
        <div class="user-greeting">
            <span class="greeting-role"><?= strtoupper(e($role)) ?></span>
            <span class="greeting-name"><?= e($displayName) ?></span>
        </div>

        <div class="user-menu-dropdown">
            <a href="<?= $profileLink ?>" class="user-avatar-btn" title="My Profile">
                <div class="avatar-circle">
                    <?= strtoupper(substr($displayName, 0, 1)) ?>
                </div>
            </a>
        </div>

        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm logout-btn" title="Sign Out">
            <span class="btn-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
            <span class="btn-text">Logout</span>
        </a>
    </div>
</header>

