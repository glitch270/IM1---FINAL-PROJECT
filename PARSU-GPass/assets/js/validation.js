/**
 * PARSU-GPass: Client-Side Form Validation
 */

document.addEventListener('DOMContentLoaded', function () {
    // Register Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const contactInput = document.getElementById('contact_number');
        const emailInput = document.getElementById('email');

        registerForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Password length
            if (passwordInput && passwordInput.value.length < 8) {
                showError('passwordError', 'Password must be at least 8 characters long.');
                isValid = false;
            } else {
                hideError('passwordError');
            }

            // Password match
            if (passwordInput && confirmInput && passwordInput.value !== confirmInput.value) {
                showError('confirmPasswordError', 'Password confirmation does not match.');
                isValid = false;
            } else {
                hideError('confirmPasswordError');
            }

            // Contact number check (11 digits starting with 09)
            if (contactInput && !/^09\d{9}$/.test(contactInput.value.trim())) {
                showError('contactError', 'Please enter a valid 11-digit Philippine mobile number (09XXXXXXXXX).');
                isValid = false;
            } else {
                hideError('contactError');
            }

            // Email check
            if (emailInput && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                showError('emailError', 'Please enter a valid email address.');
                isValid = false;
            } else {
                hideError('emailError');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Login Form Validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;

            if (email && !email.value.trim()) {
                showError('emailError', 'Please enter your registered email address.');
                isValid = false;
            } else {
                hideError('emailError');
            }

            if (password && !password.value) {
                showError('passwordError', 'Please enter your account password.');
                isValid = false;
            } else {
                hideError('passwordError');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    function showError(elementId, message) {
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = message;
            el.style.display = 'block';
        }
    }

    function hideError(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = '';
            el.style.display = 'none';
        }
    }
});

