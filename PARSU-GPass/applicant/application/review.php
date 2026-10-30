<?php
/**
 * Application Wizard: Step 6 - Comprehensive Review
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

if (!isset($_SESSION['application_draft'])) {
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$draft = &$_SESSION['application_draft'];
$personal = $draft['personal'] ?? [];
$vehicle = $draft['vehicle'] ?? [];
$ownership = $draft['ownership'] ?? [];
$signature = $draft['signature'] ?? null;
$documents = $draft['documents'] ?? [];

$category = $ownership['ownership_category'] ?? null;
if (!$category || empty($vehicle) || empty($signature)) {
    setFlashMessage('error', 'Please complete all preceding steps before reviewing your application.');
    header("Location: " . BASE_URL . "/applicant/application/personal_details.php");
    exit;
}

$db = getDBConnection();
$reqStmt = $db->prepare("
    SELECT dr.*, dt.document_code, dt.document_name
    FROM document_requirements dr
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE dr.ownership_category = ?
    ORDER BY dr.sort_order ASC
");
$reqStmt->execute([$category]);
$requirements = $reqStmt->fetchAll();

// Check if all requirements have a file
$allRequirementsMet = true;
$missingDocs = [];
foreach ($requirements as $req) {
    if (empty($documents[$req['requirement_id']])) {
        $allRequirementsMet = false;
        $missingDocs[] = $req['display_label'];
    }
}

$pageTitle = "Apply for Gate Pass - Step 6: Review & Submit";
include __DIR__ . '/../../includes/header.php';
?>

<div class="wizard-header">
    <h1 class="wizard-title">Vehicle Gate Pass Application</h1>
    <p class="wizard-subtitle">Step 6 of 6: Carefully review all information and documents before final submission</p>
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
        <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Signature</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="wizard-step-item completed">
            <span class="step-num">✓</span>
            <span class="step-title">Documents</span>
        </a>
        <a href="<?= BASE_URL ?>/applicant/application/review.php" class="wizard-step-item active">
            <span class="step-num">6</span>
            <span class="step-title">Review</span>
        </a>
    </div>
</div>

<?php if (!$allRequirementsMet): ?>
    <div class="alert alert-danger">
        <div class="alert-title">⚠️ Incomplete Application Documents</div>
        <p>The following required documents have not yet been uploaded:</p>
        <ul>
            <?php foreach ($missingDocs as $md): ?>
                <li><?= e($md) ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="btn btn-danger btn-sm" style="margin-top: 10px;">Return to Step 5: Upload Missing Files</a>
    </div>
<?php endif; ?>

<div class="card wizard-content-card">
    <div class="card-header-tabs">
        <h2 class="card-heading">Application Summary</h2>
        <p class="card-subtext">Please verify all details. Once submitted, your application will enter administrator review.</p>
    </div>

    <!-- Review Section: Personal -->
    <div class="review-section">
        <div class="review-section-header">
            <h3 class="review-section-title">1. Applicant Identity & Contact</h3>
            <a href="<?= BASE_URL ?>/applicant/application/personal_details.php" class="btn btn-outline-secondary btn-sm">Edit</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 25%;">Classification:</th>
                        <td><?= strtoupper(e($personal['applicant_type'])) ?></td>
                        <th style="width: 25%;">School / Employee ID:</th>
                        <td><?= e($personal['school_id']) ?></td>
                    </tr>
                    <tr>
                        <th>Full Name:</th>
                        <td><?= e(trim($personal['first_name'] . ' ' . ($personal['middle_name'] ?? '') . ' ' . $personal['last_name'] . ' ' . ($personal['suffix'] ?? ''))) ?></td>
                        <th>Contact Number:</th>
                        <td><?= e($personal['contact_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Department / College:</th>
                        <td><?= e($personal['department']) ?></td>
                        <th>Course / Section / Role:</th>
                        <td><?= e($personal['course_year_section'] ?? 'N/A') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Review Section: Vehicle -->
    <div class="review-section" style="margin-top: 25px;">
        <div class="review-section-header">
            <h3 class="review-section-title">2. Vehicle Information</h3>
            <a href="<?= BASE_URL ?>/applicant/application/vehicle_details.php" class="btn btn-outline-secondary btn-sm">Edit</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 25%;">Vehicle Type:</th>
                        <td><?= e($vehicle['vehicle_type']) ?></td>
                        <th style="width: 25%;">Year Model:</th>
                        <td><?= e($vehicle['year_model']) ?></td>
                    </tr>
                    <tr>
                        <th>Plate Number / MV File:</th>
                        <td class="font-bold"><?= e($vehicle['plate_number']) ?></td>
                        <th>Color:</th>
                        <td><?= e($vehicle['color']) ?></td>
                    </tr>
                    <tr>
                        <th>Driver's License No.:</th>
                        <td colspan="3"><?= e($vehicle['license_number']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Review Section: Ownership -->
    <div class="review-section" style="margin-top: 25px;">
        <div class="review-section-header">
            <h3 class="review-section-title">3. Ownership Category & Legal Context</h3>
            <a href="<?= BASE_URL ?>/applicant/application/ownership_type.php" class="btn btn-outline-secondary btn-sm">Edit</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 25%;">Ownership Category:</th>
                        <td colspan="3" class="font-bold text-primary">
                            <?= e(getOwnershipCategoryLabel($category)) ?>
                        </td>
                    </tr>
                    <?php if (!empty($ownership['owner_name'])): ?>
                        <tr>
                            <th>Registered Vehicle Owner:</th>
                            <td><?= e($ownership['owner_name']) ?></td>
                            <th>Relationship:</th>
                            <td><?= e($ownership['relationship_to_applicant'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($ownership['previous_owner_name'])): ?>
                        <tr>
                            <th>Previous Owner (Seller):</th>
                            <td><?= e($ownership['previous_owner_name']) ?></td>
                            <th>Buyer / Relative:</th>
                            <td><?= e($ownership['buyer_name'] ?? 'Applicant') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Review Section: Signature -->
    <div class="review-section" style="margin-top: 25px;">
        <div class="review-section-header">
            <h3 class="review-section-title">4. Specimen Digital Signature</h3>
            <a href="<?= BASE_URL ?>/applicant/application/signature.php" class="btn btn-outline-secondary btn-sm">Re-sign</a>
        </div>
        <div class="signature-box-display">
            <?php if (!empty($signature['path'])): ?>
                <img src="<?= BASE_URL . '/' . e($signature['path']) ?>" alt="Applicant Signature" style="max-height: 90px; border: 1px solid #ddd; background: #fff; padding: 8px;">
            <?php else: ?>
                <span class="text-danger">Signature missing</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Section: Uploaded Documents -->
    <div class="review-section" style="margin-top: 25px;">
        <div class="review-section-header">
            <h3 class="review-section-title">5. Attached Verification Documents</h3>
            <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="btn btn-outline-secondary btn-sm">Manage Files</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Required Document Label</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirements as $i => $req): 
                        $doc = $documents[$req['requirement_id']] ?? null;
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($req['display_label']) ?></td>
                            <td>
                                <?php if ($doc): ?>
                                    <span class="text-success font-bold">✓ <?= e($doc['file_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-danger font-bold">✕ Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $doc ? number_format($doc['file_size'] / 1024, 1) . ' KB' : '-' ?></td>
                            <td>
                                <?php if ($doc): ?>
                                    <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View File</a>
                                <?php else: ?>
                                    <span class="badge badge-danger">Required</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fee Calculation Box -->
    <div class="fee-calculation-box card" style="margin-top: 25px; background: #fdfdf9; border-left: 4px solid #c69214;">
        <div class="fee-summary-row">
            <div>
                <h4>Applicable University Gate Pass Fee</h4>
                <p>Calculated officially for <?= ucfirst(e($personal['applicant_type'])) ?> classification (Valid for <?= getValidityYearsByApplicantType($personal['applicant_type']) ?> Years).</p>
            </div>
            <div class="fee-amount-display">
                <?= formatCurrency(getFeeByApplicantType($personal['applicant_type'])) ?>
            </div>
        </div>
    </div>

    <!-- Final Submission Form -->
    <form action="<?= BASE_URL ?>/applicant/application/submit.php" method="POST" id="finalSubmitForm" style="margin-top: 25px;">
        <?= csrfInput() ?>

        <div class="form-group agreement-checkbox">
            <label class="custom-control-label">
                <input type="checkbox" name="undertaking_agree" id="undertakingAgree" value="1" required>
                <span>I solemnly declare that all information, vehicle specifications, and uploaded documents provided are authentic, valid, and registered. I authorize university authorities to verify these documents with the Land Transportation Office (LTO) and appropriate agencies.</span>
            </label>
        </div>

        <div class="wizard-actions-bar">
            <a href="<?= BASE_URL ?>/applicant/application/requirements.php" class="btn btn-outline-secondary">← Back: Documents</a>
            <button type="submit" class="btn btn-success btn-lg" id="submitApplicationBtn" <?= $allRequirementsMet ? '' : 'disabled' ?>>
                🚀 Confirm & Submit Application
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

