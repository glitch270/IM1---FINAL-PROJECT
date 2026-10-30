<?php
/**
 * Admin: Generate Gate Pass Sticker Interface
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$appId = (int)($_GET['id'] ?? 0);

// Fetch application (must be paid or for_release)
$stmt = $db->prepare("
    SELECT a.*, ap.first_name, ap.middle_name, ap.last_name, ap.suffix, ap.school_id, ap.applicant_type, ap.department,
           v.vehicle_type, v.plate_number, v.year_model, v.color, v.license_number,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number, p.payment_date,
           s.sticker_id, s.sticker_number, s.sticker_status
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    WHERE a.application_id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    redirect('admin/applications/approved.php', 'error', 'Application not found.');
}

// Rule 9: Generate sticker ONLY after payment is verified
if ($app['payment_status'] !== 'verified' && !in_array($app['application_status'], ['paid', 'for_release', 'released'])) {
    redirect("admin/applications/view.php?id=$appId", 'error', 'Stickers can only be generated after payment is verified by the administrator.');
}

if (!empty($app['sticker_number'])) {
    redirect("admin/gatepass/sticker_details.php?id={$app['sticker_id']}", 'info', 'A sticker has already been generated for this application.');
}

$generatedNumber = generateStickerNumber($db);
$issueDate = date('Y-m-d');
$validityYears = getValidityYearsByApplicantType($app['applicant_type']);
$validUntil = date('Y-m-d', strtotime("+$validityYears years"));

$pageTitle = "Generate Sticker - " . $app['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Generate Gate Pass Sticker</h1>
        <p class="page-subtitle">Application: <?= e($app['application_number']) ?> &bull; Vehicle: <?= e($app['plate_number']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading">Sticker Issuance Authorization</h2>
        <p class="card-subtext">Verified payment received (PSU-OR #<?= e($app['psu_or_number']) ?> &bull; <?= formatCurrency($app['amount']) ?>).</p>
    </div>

    <!-- Sticker Specs Table -->
    <div class="table-responsive" style="margin-bottom: 25px;">
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width: 35%;">Generated Sticker Number:</th>
                    <td class="font-bold text-primary" style="font-size: 1.25rem; letter-spacing: 1px;"><?= e($generatedNumber) ?></td>
                </tr>
                <tr>
                    <th>Applicant Name:</th>
                    <td><?= e(trim($app['first_name'] . ' ' . $app['last_name'])) ?> (<?= e($app['school_id']) ?>)</td>
                </tr>
                <tr>
                    <th>Classification:</th>
                    <td>
                        <span class="badge <?= $app['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>">
                            <?= ucfirst(e($app['applicant_type'])) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Registered Vehicle:</th>
                    <td class="font-bold"><?= e($app['plate_number']) ?> (<?= e($app['vehicle_type']) ?> - <?= e($app['color']) ?>)</td>
                </tr>
                <tr>
                    <th>Issue Date:</th>
                    <td><?= date('F d, Y', strtotime($issueDate)) ?></td>
                </tr>
                <tr>
                    <th>Validity Period:</th>
                    <td class="font-bold text-success">
                        <?= $validityYears ?> Academic Years (Valid until <?= date('F d, Y', strtotime($validUntil)) ?>)
                    </td>
                </tr>
                <tr>
                    <th>Initial Sticker Status:</th>
                    <td><span class="badge badge-indigo">For Release</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <form action="<?= BASE_URL ?>/actions/sticker_generation_process.php" method="POST" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="application_id" value="<?= $appId ?>">
        <input type="hidden" name="sticker_number" value="<?= e($generatedNumber) ?>">
        <input type="hidden" name="applicant_type" value="<?= e($app['applicant_type']) ?>">
        <input type="hidden" name="issue_date" value="<?= $issueDate ?>">
        <input type="hidden" name="valid_until" value="<?= $validUntil ?>">

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $appId ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                🏷️ Authorize & Issue Sticker
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

