<?php
/**
 * Application Wizard: Step 4 - Digital Signature
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$savedSignature = $draft['signature'] ?? null;

$pageTitle = "Apply for Gate Pass - Step 4: Digital Signature";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 4 of 6: Provide your digital specimen signature using the canvas pad below</p>
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
        <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Ownership</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="wizard-step-item active">
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
        <h2 class="card-heading">Applicant Specimen Signature</h2>
        <p class="card-subtext">Use your mouse, trackpad, or finger (on touch screens) to draw your signature inside the box below.</p>
    </div>

    <!-- Legal Attestation Statement -->
    <div class="alert alert-secondary">
        <div class="alert-title">📜 University Gate Pass Undertaking</div>
        <p>I hereby certify that all statements and documents submitted are authentic, true, and correct. I agree to abide by all university traffic rules, speed limits, parking regulations, and campus security guidelines.</p>
    </div>

    <form action="<?= BASE_URL ?>/actions/signature_process.php" method="POST" id="signatureForm">
        <?= csrfInput() ?>
        <input type="hidden" name="signature_data" id="signatureData">

        <div class="signature-canvas-wrapper">
            <canvas id="signatureCanvas" width="600" height="200" class="signature-pad"></canvas>
            <div class="signature-line-guide">Sign above this line</div>
        </div>

        <div class="signature-tools-bar">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSignatureBtn">
                🔄 Clear Signature
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="undoSignatureBtn">
                ↩️ Undo Stroke
            </button>
            <span class="signature-status" id="signatureStatus">
                <?= $savedSignature ? '✓ Current signature saved in draft' : 'Pad is empty. Draw signature to proceed.' ?>
            </span>
        </div>

        <?php if ($savedSignature && !empty($savedSignature['path'])): ?>
            <div class="saved-signature-preview" style="margin-top: 15px;">
                <label class="form-label">Current Saved Signature in Draft:</label>
                <div class="signature-preview-box">
                    <img src="<?= BASE_URL . '/' . e($savedSignature['path']) ?>" alt="Signature Specimen" style="max-height: 80px; border: 1px dashed #ccc; padding: 5px; background: #fff;">
                </div>
            </div>
        <?php endif; ?>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="btn btn-outline-secondary">← Back: Ownership Type</a>
            <button type="submit" class="btn btn-primary btn-lg" id="saveSignatureBtn">
                Save & Continue to Documents →
            </button>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/assets/js/signature.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

