<?php
/**
 * Application Sidebar Navigation Component
 */
$role = $_SESSION['account_role'] ?? null;
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function isNavActive(string $pathSubstring, string $currentScript): bool {
    return str_contains($currentScript, $pathSubstring);
}
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard.php" class="sidebar-brand">
            <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="PARSU Logo" class="brand-logo-img">
            <div class="brand-text-wrap">
                <h2 class="brand-main">PARSU-<span>GPass</span></h2>
                <span class="brand-sub">Gate Pass Portal</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'applicant'): ?>
            <div class="nav-section-title">APPLICANT PORTAL</div>
            <ul class="nav-list">
                <li class="nav-item <?= isNavActive('/applicant/dashboard.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/dashboard.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/application/create.php', $currentScript) || isNavActive('/applicant/application/personal_details.php', $currentScript) || isNavActive('/applicant/application/vehicle_details.php', $currentScript) || isNavActive('/applicant/application/ownership_type.php', $currentScript) || isNavActive('/applicant/application/signature.php', $currentScript) || isNavActive('/applicant/application/requirements.php', $currentScript) || isNavActive('/applicant/application/review.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/application/create.php" class="nav-link">
                        <span class="nav-icon">📝</span>
                        <span class="nav-label">Apply for Gate Pass</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/application/view.php', $currentScript) || isNavActive('/applicant/application/application_status.php', $currentScript) || isNavActive('/applicant/application/decline_details.php', $currentScript) || isNavActive('/applicant/application/resubmit.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/application/view.php" class="nav-link">
                        <span class="nav-icon">🔍</span>
                        <span class="nav-label">My Application</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/documents/', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/documents/view_documents.php" class="nav-link">
                        <span class="nav-icon">📁</span>
                        <span class="nav-label">My Documents</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/payment/', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/payment/payment_status.php" class="nav-link">
                        <span class="nav-icon">💳</span>
                        <span class="nav-label">Payment & Receipt</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/gatepass/', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span class="nav-label">Gate Pass Sticker</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/applicant/profile.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/applicant/profile.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span class="nav-label">My Profile</span>
                    </a>
                </li>
            </ul>

        <?php elseif ($role === 'admin'): ?>
            <div class="nav-section-title">ADMINISTRATION</div>
            <ul class="nav-list">
                <li class="nav-item <?= isNavActive('/admin/dashboard.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link">
                        <span class="nav-icon">📈</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/applicants/', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/applicants/list.php" class="nav-link">
                        <span class="nav-icon">👥</span>
                        <span class="nav-label">Registered Applicants</span>
                    </a>
                </li>

                <div class="nav-section-title">APPLICATIONS</div>
                <li class="nav-item <?= isNavActive('/admin/applications/pending.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/applications/pending.php" class="nav-link">
                        <span class="nav-icon">⏳</span>
                        <span class="nav-label">Pending Review</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/applications/resubmitted.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/applications/resubmitted.php" class="nav-link">
                        <span class="nav-icon">🔄</span>
                        <span class="nav-label">Resubmitted</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/applications/approved.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/applications/approved.php" class="nav-link">
                        <span class="nav-icon">✅</span>
                        <span class="nav-label">Approved</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/applications/declined.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/applications/declined.php" class="nav-link">
                        <span class="nav-icon">❌</span>
                        <span class="nav-label">Declined</span>
                    </a>
                </li>

                <div class="nav-section-title">PAYMENTS</div>
                <li class="nav-item <?= isNavActive('/admin/payments/pending.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/payments/pending.php" class="nav-link">
                        <span class="nav-icon">💵</span>
                        <span class="nav-label">Pending Verification</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/payments/payment_history.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/payments/payment_history.php" class="nav-link">
                        <span class="nav-icon">🧾</span>
                        <span class="nav-label">Payment History</span>
                    </a>
                </li>

                <div class="nav-section-title">GATE PASS STICKERS</div>
                <li class="nav-item <?= isNavActive('/admin/gatepass/generate_sticker.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/gatepass/generate_sticker.php" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span class="nav-label">Generate Sticker</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/gatepass/sticker_list.php', $currentScript) || isNavActive('/admin/gatepass/release.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/gatepass/sticker_list.php" class="nav-link">
                        <span class="nav-icon">📦</span>
                        <span class="nav-label">Stickers & Releases</span>
                    </a>
                </li>

                <div class="nav-section-title">SYSTEM REPORTS</div>
                <li class="nav-item <?= isNavActive('/admin/reports/applications_report.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/reports/applications_report.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span class="nav-label">Applications Report</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/reports/payments_report.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/reports/payments_report.php" class="nav-link">
                        <span class="nav-icon">💰</span>
                        <span class="nav-label">Payments Report</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/reports/released_stickers.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/reports/released_stickers.php" class="nav-link">
                        <span class="nav-icon">🚗</span>
                        <span class="nav-label">Released Stickers</span>
                    </a>
                </li>
                <li class="nav-item <?= isNavActive('/admin/reports/expired_stickers.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/reports/expired_stickers.php" class="nav-link">
                        <span class="nav-icon">⚠️</span>
                        <span class="nav-label">Expired Stickers</span>
                    </a>
                </li>

                <div class="nav-section-title">ACCOUNT</div>
                <li class="nav-item <?= isNavActive('/admin/settings/profile.php', $currentScript) || isNavActive('/admin/profile.php', $currentScript) ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/admin/settings/profile.php" class="nav-link">
                        <span class="nav-icon">⚙️</span>
                        <span class="nav-label">Admin Settings</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <span class="sidebar-user-role"><?= strtoupper(e($role)) ?></span>
            <span class="sidebar-user-email"><?= e($_SESSION['user_email'] ?? '') ?></span>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="sidebar-logout-link" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

