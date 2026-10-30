<?php
/**
 * Application Wizard: Step 3 - Ownership Type & Extra Details
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$ownership = $draft['ownership'] ?? [];
$selectedCat = $ownership['ownership_category'] ?? '';

$pageTitle = "Apply for Gate Pass - Step 3: Ownership Type";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 3 of 6: Select your vehicle ownership classification</p>
</div>

<div class="wizard-nav-wrap">
    <div class="wizard-steps-indicator">
        <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Personal</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Vehicle</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="wizard-step-item active">
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
        <a href="<?= BASE_URL ?>/applicant/application/review.php" class="wizard-step-item disabled">
            <span class="step-num">6</span>
            <span class="step-title">Review</span>
        </a>
    </div>
</div>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <h2 class="card-heading">Vehicle Legal Ownership Classification</h2>
        <p class="card-subtext">Select the category that reflects your vehicle's LTO registration and acquisition history.</p>
    </div>

    <form action="<?= BASE_URL ?>/actions/application_process.php?step=ownership" method="POST" class="standard-form" id="ownershipForm">
        <?= csrfInput() ?>

        <div class="ownership-options-grid">
            <!-- Option 1 -->
            <label class="ownership-card-option <?= $selectedCat === 'first_owner_own_name' ? 'selected' : '' ?>">
                <input type="radio" name="ownership_category" value="first_owner_own_name" <?= $selectedCat === 'first_owner_own_name' ? 'checked' : '' ?> required>
                <div class="option-content">
                    <div class="option-header">
                        <span class="option-badge badge-success">Category 1 &bull; 3 Documents</span>
                    </div>
                    <h3 class="option-title">First-hand Owner (Own Name)</h3>
                    <p class="option-desc">The official LTO Certificate of Registration (CR) is directly registered under your legal name.</p>
                </div>
            </label>

            <!-- Option 2 -->
            <label class="ownership-card-option <?= $selectedCat === 'first_owner_relative_name' ? 'selected' : '' ?>">
                <input type="radio" name="ownership_category" value="first_owner_relative_name" <?= $selectedCat === 'first_owner_relative_name' ? 'checked' : '' ?>>
                <div class="option-content">
                    <div class="option-header">
                        <span class="option-badge badge-primary">Category 2 &bull; 5 Documents</span>
                    </div>
                    <h3 class="option-title">First-hand Owner (Relative's Name)</h3>
                    <p class="option-desc">The vehicle is registered under an immediate parent, spouse, sibling, or relative.</p>
                </div>
            </label>

            <!-- Option 3 -->
            <label class="ownership-card-option <?= $selectedCat === 'secondhand_applicant_buyer' ? 'selected' : '' ?>">
                <input type="radio" name="ownership_category" value="secondhand_applicant_buyer" <?= $selectedCat === 'secondhand_applicant_buyer' ? 'checked' : '' ?>>
                <div class="option-content">
                    <div class="option-header">
                        <span class="option-badge badge-warning">Category 3 &bull; 5 Documents</span>
                    </div>
                    <h3 class="option-title">Second-hand (Applicant is Buyer)</h3>
                    <p class="option-desc">Vehicle was acquired second-hand and your name is explicitly named as Buyer on the Notarized Deed of Sale.</p>
                </div>
            </label>

            <!-- Option 4 -->
            <label class="ownership-card-option <?= $selectedCat === 'secondhand_applicant_not_buyer' ? 'selected' : '' ?>">
                <input type="radio" name="ownership_category" value="secondhand_applicant_not_buyer" <?= $selectedCat === 'secondhand_applicant_not_buyer' ? 'checked' : '' ?>>
                <div class="option-content">
                    <div class="option-header">
                        <span class="option-badge badge-indigo">Category 4 &bull; 7 Documents</span>
                    </div>
                    <h3 class="option-title">Second-hand (Applicant NOT Buyer)</h3>
                    <p class="option-desc">Vehicle was purchased second-hand by a relative/buyer, and you are authorized to drive it.</p>
                </div>
            </label>
        </div>

        <!-- Dynamic Context Fields Depending on Category -->
        <div id="dynamicOwnershipFields" class="dynamic-fields-section" style="margin-top: 25px;">
            <div id="fieldOwnerName" class="form-group" style="display: none;">
                <label for="owner_name" class="form-label">Registered Vehicle Owner's Full Name <span class="text-danger">*</span></label>
                <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="Name as appearing on LTO CR" value="<?= e($ownership['owner_name'] ?? '') ?>">
            </div>

            <div id="fieldRelationship" class="form-group" style="display: none;">
                <label for="relationship_to_applicant" class="form-label">Relationship to Applicant <span class="text-danger">*</span></label>
                <input type="text" name="relationship_to_applicant" id="relationship_to_applicant" class="form-control" placeholder="e.g. Parent (Father/Mother), Spouse, Sibling" value="<?= e($ownership['relationship_to_applicant'] ?? '') ?>">
            </div>

            <div id="fieldPreviousOwner" class="form-group" style="display: none;">
                <label for="previous_owner_name" class="form-label">Previous Registered Owner's Name (Seller on Deed of Sale) <span class="text-danger">*</span></label>
                <input type="text" name="previous_owner_name" id="previous_owner_name" class="form-control" placeholder="Seller name as indicated on Deed of Sale" value="<?= e($ownership['previous_owner_name'] ?? '') ?>">
            </div>

            <div id="fieldBuyerName" class="form-group" style="display: none;">
                <label for="buyer_name" class="form-label">Buyer / Authorized Relative's Full Name <span class="text-danger">*</span></label>
                <input type="text" name="buyer_name" id="buyer_name" class="form-control" placeholder="Buyer name as indicated on Deed of Sale" value="<?= e($ownership['buyer_name'] ?? '') ?>">
            </div>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="btn btn-outline-secondary">← Back: Vehicle Details</a>
            <button type="submit" class="btn btn-primary btn-lg">Next: Digital Signature →</button>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/assets/js/ownership.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

