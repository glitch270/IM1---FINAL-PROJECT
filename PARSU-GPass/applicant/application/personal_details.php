<?php
/**
 * Application Wizard: Step 1 - Personal & Institutional Details
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$personal = $draft['personal'] ?? $currentApplicant;

$pageTitle = "Apply for Gate Pass - Step 1: Personal Details";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 1 of 6: Review your institutional identity and verify your contact details</p>
</div>

<!-- Wizard Step Navigation Bar -->
<div class="wizard-nav-wrap">
    <div class="wizard-steps-indicator">
        <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="wizard-step-item active">
            <span class="step-num">1</span>
            <span class="step-title">Personal</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="wizard-step-item <?= !empty($draft['vehicle']) ? '' : 'disabled' ?>">
            <span class="step-num">2</span>
            <span class="step-title">Vehicle</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="wizard-step-item <?= !empty($draft['ownership']) ? '' : 'disabled' ?>">
            <span class="step-num">3</span>
            <span class="step-title">Ownership</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="wizard-step-item <?= !empty($draft['signature']) ? '' : 'disabled' ?>">
            <span class="step-num">4</span>
            <span class="step-title">Signature</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="wizard-step-item <?= !empty($draft['documents']) ? '' : 'disabled' ?>">
            <span class="step-num">5</span>
            <span class="step-title">Documents</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/review.php" class="wizard-step-item <?= (!empty($draft['documents']) && !empty($draft['signature'])) ? '' : 'disabled' ?>">
            <span class="step-num">6</span>
            <span class="step-title">Review</span>
        </a>
    </div>
</div>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <h2 class="card-heading">Applicant Identity Information</h2>
        <p class="card-subtext">Information verified against university registrar/HR records.</p>
    </div>

    <form action="<?= BASE_URL ?>/actions/application_process.php?step=personal" method="POST" class="standard-form">
        <?= csrfInput() ?>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="form-label">Classification</label>
                <input type="text" class="form-control" value="<?= strtoupper(e($personal['applicant_type'])) ?>" readonly>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">School / Employee ID</label>
                <input type="text" class="form-control" value="<?= e($personal['school_id']) ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" value="<?= e($personal['first_name']) ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" value="<?= e($personal['middle_name'] ?? '') ?>" readonly>
            </div>
            <div class="form-group col-md-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" value="<?= e($personal['last_name']) ?>" readonly>
            </div>
            <div class="form-group col-md-2">
                <label class="form-label">Suffix</label>
                <input type="text" class="form-control" value="<?= e($personal['suffix'] ?? '') ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="contact_number" class="form-label">Active Contact / Mobile Number <span class="text-danger">*</span></label>
                <input type="tel" name="contact_number" id="contact_number" class="form-control" value="<?= e($personal['contact_number']) ?>" maxlength="11" required>
            </div>

            <div class="form-group col-md-6">
                <label for="department" class="form-label">College / Department / Office <span class="text-danger">*</span></label>
                <input type="text" name="department" id="department" class="form-control" value="<?= e($personal['department']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="course_year_section" class="form-label">Course, Year & Section / Designation <?= $personal['applicant_type'] === 'student' ? '<span class="text-danger">*</span>' : '' ?></label>
            <input type="text" name="course_year_section" id="course_year_section" class="form-control" value="<?= e($personal['course_year_section'] ?? '') ?>" <?= $personal['applicant_type'] === 'student' ? 'required' : '' ?>>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">Next: Vehicle Details →</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

