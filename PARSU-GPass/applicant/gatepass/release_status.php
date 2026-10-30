<?php
/**
 * Applicant: Physical Sticker Release Status Tracker
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT s.*, a.application_number, v.plate_number, v.vehicle_type,
           rel.release_id, rel.released_at, rel.received_by, rel.release_remarks
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$gatepass = $stmt->fetch();

$pageTitle = "Sticker Release Status";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Physical Sticker Release Status</h1>
        <p class="page-subtitle">Track the physical distribution and claim status of your campus RFID sticker.</p>
    </div>
</div>

<?php if (!$gatepass || !$gatepass['sticker_number']): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">📦</div>
        <h2 class="empty-state-title">No Sticker Issued Yet</h2>
        <p class="empty-state-desc">Your sticker will be available for pickup after application approval and payment verification.</p>
        <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-primary">Check Application Progress</a>
    </div>
<?php else: ?>
    <div class="card" style="max-width: 750px; margin: 0 auto;">
        <div class="card-header-flex">
            <div>
                <h3 class="card-heading">Sticker Handover Tracking</h3>
                <span class="card-subtext">Sticker #<strong><?= e($gatepass['sticker_number']) ?></strong> &bull; Plate #<strong><?= e($gatepass['plate_number']) ?></strong></span>
            </div>
            <div>
                <?= getStatusBadge($gatepass['sticker_status']) ?>
            </div>
        </div>

        <?php if ($gatepass['sticker_status'] === 'released'): ?>
            <div class="alert alert-success" style="margin-top: 20px;">
                <div class="alert-title">✓ Sticker Successfully Handed Over</div>
                <p>The physical gate pass sticker was released and received by <strong><?= e($gatepass['received_by']) ?></strong> on <strong><?= date('F d, Y h:i A', strtotime($gatepass['released_at'])) ?></strong>.</p>
                <?php if ($gatepass['release_remarks']): ?>
                    <small>Remarks: <?= e($gatepass['release_remarks']) ?></small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-primary" style="margin-top: 20px;">
                <div class="alert-title">🏷️ Ready for Claiming at Campus Security</div>
                <p>Your RFID gate pass sticker is printed and ready at the <strong>Security and Safety Office</strong>.</p>
                <div style="margin-top: 10px;">
                    <strong>Requirements to Claim:</strong>
                    <ul style="margin: 5px 0 0 20px;">
                        <li>Original Valid Student ID / Employee ID Card</li>
                        <li>Copy of Vehicle Certificate of Registration (CR)</li>
                        <li>Official Cashier Receipt (PSU-OR)</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 25px; display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-primary">View Sticker Badge</a>
            <a href="<?= BASE_URL ?>/applicant/dashboard.php" class="btn btn-outline-secondary">Return to Dashboard</a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

