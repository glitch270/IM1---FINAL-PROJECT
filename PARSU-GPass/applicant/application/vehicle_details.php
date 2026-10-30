<?php
/**
 * Application Wizard: Step 2 - Vehicle Details
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$vehicle = $draft['vehicle'] ?? [];

$pageTitle = "Apply for Gate Pass - Step 2: Vehicle Details";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 2 of 6: Provide your vehicle technical specifications and plate number</p>
</div>

<div class="wizard-nav-wrap">
    <div class="wizard-steps-indicator">
        <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Personal</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="wizard-step-item active">
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
        <a href="<?= BASE_URL ?>/applicant/application/review.php" class="wizard-step-item disabled">
            <span class="step-num">6</span>
            <span class="step-title">Review</span>
        </a>
    </div>
</div>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <h2 class="card-heading">Vehicle Technical Information</h2>
        <p class="card-subtext">Must match details indicated on your Land Transportation Office (LTO) Certificate of Registration.</p>
    </div>

    <form action="<?= BASE_URL ?>/actions/application_process.php?step=vehicle" method="POST" class="standard-form" id="vehicleForm">
        <?= csrfInput() ?>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="vehicle_type" class="form-label">Vehicle Classification / Type <span class="text-danger">*</span></label>
                <select name="vehicle_type" id="vehicle_type" class="form-control" required>
                    <option value="">-- Select Vehicle Type --</option>
                    <?php
                    $types = ['Motorcycle', 'Scooter', 'Sedan', 'Hatchback', 'SUV', 'Pickup Truck', 'Van', 'MPV'];
                    foreach ($types as $t):
                    ?>
                        <option value="<?= e($t) ?>" <?= ($vehicle['vehicle_type'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-md-6">
                <label for="year_model" class="form-label">Year Model <span class="text-danger">*</span></label>
                <input type="number" name="year_model" id="year_model" class="form-control" min="1990" max="<?= date('Y') + 1 ?>" value="<?= e($vehicle['year_model'] ?? date('Y')) ?>" required>
                <small class="form-hint">e.g. 2022, 2023</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="plate_number" class="form-label">Plate Number / MV File No. <span class="text-danger">*</span></label>
                <input type="text" name="plate_number" id="plate_number" class="form-control" placeholder="e.g. ABC 1234 or 123-ABC" value="<?= e($vehicle['plate_number'] ?? '') ?>" required style="text-transform: uppercase;">
                <small class="form-hint">If newly purchased without metal plates, enter MV File Number</small>
            </div>

            <div class="form-group col-md-6">
                <label for="color" class="form-label">Vehicle Color <span class="text-danger">*</span></label>
                <input type="text" name="color" id="color" class="form-control" placeholder="e.g. Glossy Black, Pearl White" value="<?= e($vehicle['color'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="license_number" class="form-label">Applicant Driver's License Number <span class="text-danger">*</span></label>
            <input type="text" name="license_number" id="license_number" class="form-control" placeholder="e.g. N01-18-123456" value="<?= e($vehicle['license_number'] ?? '') ?>" required style="text-transform: uppercase;">
            <small class="form-hint">Official LTO Driver's License Number of the primary driver</small>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="btn btn-outline-secondary">← Back: Personal Details</a>
            <button type="submit" class="btn btn-primary btn-lg">Next: Ownership Type →</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

