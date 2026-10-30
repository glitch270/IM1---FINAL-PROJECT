<?php
/**
 * Applicant Registration Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['account_role'] ?? 'applicant';
    header("Location: " . BASE_URL . "/$role/dashboard.php");
    exit;
}

$preselectedType = $_GET['type'] ?? 'student';
if (!in_array($preselectedType, ['student', 'employee'])) {
    $preselectedType = 'student';
}

$pageTitle = "Applicant Registration";
$customCss = ['register.css'];
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="register-container">
        <div class="auth-brand-header">
            <div class="auth-logo-wrap">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="University Logo" class="auth-logo-img">
            </div>
            <h1 class="auth-title">PARSU-<span>GPass</span></h1>
            <p class="auth-subtitle">New Applicant Registration Portal</p>
        </div>

        <div class="auth-card card">
            <div class="card-header-tabs">
                <h2 class="auth-card-heading">Create Your Applicant Account</h2>
                <p class="auth-card-desc">Fill in your institutional identity details. All fields with <span class="text-danger">*</span> are required.</p>
            </div>

            <form action="<?= BASE_URL ?>/actions/register_process.php" method="POST" class="auth-form" id="registerForm" novalidate>
                <?= csrfInput() ?>

                <!-- Step 1: Applicant Classification -->
                <div class="form-section">
                    <h3 class="form-section-title">1. Applicant Classification</h3>
                    <div class="role-selector-group">
                        <label class="role-radio-card <?= $preselectedType === 'student' ? 'selected' : '' ?>">
                            <input type="radio" name="applicant_type" value="student" <?= $preselectedType === 'student' ? 'checked' : '' ?> id="typeStudent">
                            <div class="role-radio-content">
                                <span class="role-icon">🎓</span>
                                <span class="role-label">University Student</span>
                                <span class="role-fee">₱130.00 (2 Years Validity)</span>
                            </div>
                        </label>
                        <label class="role-radio-card <?= $preselectedType === 'employee' ? 'selected' : '' ?>">
                            <input type="radio" name="applicant_type" value="employee" <?= $preselectedType === 'employee' ? 'checked' : '' ?> id="typeEmployee">
                            <div class="role-radio-content">
                                <span class="role-icon">💼</span>
                                <span class="role-label">Faculty / Employee</span>
                                <span class="role-fee">₱280.00 (3 Years Validity)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Identification & Personal Info -->
                <div class="form-section">
                    <h3 class="form-section-title">2. Identification & Personal Details</h3>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="school_id" class="form-label" id="schoolIdLabel">Student ID Number <span class="text-danger">*</span></label>
                            <input type="text" name="school_id" id="school_id" class="form-control" placeholder="e.g. 2024-00123" required>
                            <small class="form-hint">Official ID printed on your campus identification card</small>
                            <div class="form-error-feedback" id="schoolIdError"></div>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="contact_number" class="form-label">Active Mobile / Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" name="contact_number" id="contact_number" class="form-control" placeholder="09123456789" maxlength="11" required>
                            <small class="form-hint">11-digit Philippine mobile number</small>
                            <div class="form-error-feedback" id="contactError"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Given name" required>
                            <div class="form-error-feedback" id="firstNameError"></div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-control" placeholder="Middle name (optional)">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Family name" required>
                            <div class="form-error-feedback" id="lastNameError"></div>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="suffix" class="form-label">Suffix</label>
                            <input type="text" name="suffix" id="suffix" class="form-control" placeholder="Jr., III">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="department" class="form-label">College / Department / Office <span class="text-danger">*</span></label>
                            <input type="text" name="department" id="department" class="form-control" placeholder="e.g. College of Computer Studies" required>
                            <div class="form-error-feedback" id="departmentError"></div>
                        </div>

                        <div class="form-group col-md-6" id="courseYearGroup">
                            <label for="course_year_section" class="form-label" id="courseYearLabel">Course, Year & Section <span class="text-danger">*</span></label>
                            <input type="text" name="course_year_section" id="course_year_section" class="form-control" placeholder="e.g. BSIT - 4A" required>
                            <div class="form-error-feedback" id="courseYearError"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Account Credentials -->
                <div class="form-section">
                    <h3 class="form-section-title">3. Account Login Credentials</h3>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="e.g. name@parsu.edu.ph" required>
                        <small class="form-hint">Used for signing in and receiving status updates on your gate pass</small>
                        <div class="form-error-feedback" id="emailError"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="At least 8 characters" minlength="8" required>
                            <div class="form-error-feedback" id="passwordError"></div>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-type password" required>
                            <div class="form-error-feedback" id="confirmPasswordError"></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="registerSubmitBtn">
                        Complete Registration & Continue
                    </button>
                </div>
            </form>

            <div class="auth-footer-links">
                <p>Already have an account? <a href="<?= BASE_URL ?>/login.php" class="link-gold font-bold">Sign In here</a></p>
                <p><a href="<?= BASE_URL ?>/index.php" class="link-muted">← Back to Homepage</a></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeStudent = document.getElementById('typeStudent');
    const typeEmployee = document.getElementById('typeEmployee');
    const schoolIdLabel = document.getElementById('schoolIdLabel');
    const courseYearLabel = document.getElementById('courseYearLabel');
    const courseYearInput = document.getElementById('course_year_section');
    const radioCards = document.querySelectorAll('.role-radio-card');

    function updateFormLabels() {
        radioCards.forEach(card => card.classList.remove('selected'));
        if (typeStudent.checked) {
            typeStudent.closest('.role-radio-card').classList.add('selected');
            schoolIdLabel.innerHTML = 'Student ID Number <span class="text-danger">*</span>';
            courseYearLabel.innerHTML = 'Course, Year & Section <span class="text-danger">*</span>';
            courseYearInput.placeholder = 'e.g. BSIT - 4A';
            courseYearInput.required = true;
        } else {
            typeEmployee.closest('.role-radio-card').classList.add('selected');
            schoolIdLabel.innerHTML = 'Employee / Faculty ID <span class="text-danger">*</span>';
            courseYearLabel.innerHTML = 'Designation / Plantilla Position';
            courseYearInput.placeholder = 'e.g. Instructor I / Admin Aide';
            courseYearInput.required = false;
        }
    }

    typeStudent.addEventListener('change', updateFormLabels);
    typeEmployee.addEventListener('change', updateFormLabels);
    updateFormLabels();
});
</script>

<?php 
$customJs = ['validation.js'];
include __DIR__ . '/includes/footer.php'; 
?>

