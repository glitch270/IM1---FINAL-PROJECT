/**
 * PARSU-GPass: Main Client Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function () {
            appSidebar.classList.toggle('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.toggle('active');
        });
    }

    if (sidebarCloseBtn && appSidebar) {
        sidebarCloseBtn.addEventListener('click', function () {
            appSidebar.classList.remove('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
        });
    }

    if (sidebarBackdrop && appSidebar) {
        sidebarBackdrop.addEventListener('click', function () {
            appSidebar.classList.remove('open');
            sidebarBackdrop.classList.remove('active');
        });
    }

    // Password Visibility Toggle
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordBtn.textContent = isPassword ? '🙈' : '👁️';
        });
    }
});

