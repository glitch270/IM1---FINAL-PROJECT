<?php
/**
 * Applicant: Sticker Specifications & Placement Guidelines
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT s.*, a.application_number, v.plate_number, v.vehicle_type, v.color,
           rel.released_at, rel.received_by
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$gatepass = $stmt->fetch();

$pageTitle = "Sticker Specifications & Placement Guide";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Sticker Specifications & Placement Guide</h1>
        <p class="page-subtitle">Guidelines for mounting and maintaining your official campus vehicle gate pass.</p>
    </div>
    <div class="header-action-group">
        <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-primary">View Sticker Badge</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto 25px auto;">
    <div class="card-header-tabs">
        <h3 class="card-heading">Sticker Information Summary</h3>
    </div>
    <?php if ($gatepass && $gatepass['sticker_number']): ?>
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width: 35%;">Sticker Number:</th>
                    <td class="font-bold text-primary"><?= e($gatepass['sticker_number']) ?></td>
                </tr>
                <tr>
                    <th>Vehicle:</th>
                    <td><?= e($gatepass['plate_number']) ?> (<?= e($gatepass['vehicle_type']) ?> - <?= e($gatepass['color']) ?>)</td>
                </tr>
                <tr>
                    <th>Issue Date:</th>
                    <td><?= date('F d, Y', strtotime($gatepass['issue_date'])) ?></td>
                </tr>
                <tr>
                    <th>Expiry / Valid Until:</th>
                    <td class="font-bold text-success"><?= date('F d, Y', strtotime($gatepass['valid_until'])) ?></td>
                </tr>
                <tr>
                    <th>Distribution Status:</th>
                    <td><?= getStatusBadge($gatepass['sticker_status']) ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted">No sticker issued yet for your account.</p>
    <?php endif; ?>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h3 class="card-heading">Proper Sticker Affixing Guidelines</h3>
    </div>

    <div style="line-height: 1.8;">
        <h4>1. Four-Wheel Motor Vehicles (Cars, Sedans, SUVs, Vans, Pickups)</h4>
        <p>Affix the sticker on the <strong>upper right-hand corner (passenger side)</strong> of the front windshield, clean and dry, visible from outside security booths.</p>

        <h4 style="margin-top: 20px;">2. Motorcycles & Scooters</h4>
        <p>Affix the sticker on the <strong>front windshield / visor</strong> or on the <strong>front fork / headlight fairing</strong> where campus security scanners can easily read the barcode and number.</p>

        <h4 style="margin-top: 20px;">3. Tamper Policy</h4>
        <p class="text-danger">Attempting to transfer, peel, or re-apply the sticker to an unauthorized vehicle voids the gate pass and is subject to administrative sanctions under university disciplinary rules.</p>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

