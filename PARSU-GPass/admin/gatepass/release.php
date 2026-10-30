<?php
/**
 * Admin: Record Physical Gate Pass Sticker Release
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$stickerId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT s.*, a.application_number,
           ap.first_name, ap.last_name, ap.school_id, ap.applicant_type,
           v.plate_number, v.vehicle_type
    FROM gate_pass_stickers s
    JOIN applications a ON s.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE s.sticker_id = ?
");
$stmt->execute([$stickerId]);
$sticker = $stmt->fetch();

if (!$sticker) {
    redirect('admin/gatepass/sticker_list.php', 'error', 'Sticker record not found.');
}

if ($sticker['sticker_status'] === 'released') {
    redirect("admin/gatepass/sticker_details.php?id=$stickerId", 'info', 'This gate pass sticker has already been released.');
}

$defaultReceiver = trim($sticker['first_name'] . ' ' . $sticker['last_name']);

$pageTitle = "Record Sticker Release - " . $sticker['sticker_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Record Physical Sticker Release</h1>
        <p class="page-subtitle">Handover record for Sticker #<?= e($sticker['sticker_number']) ?> &bull; Vehicle: <?= e($sticker['plate_number']) ?></p>
    </div>
</div>

<div class="card wizard-content-card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h2 class="card-heading">Sticker Handover Verification</h2>
        <p class="card-subtext">Record the identity of the person claiming the physical sticker at the Security Office.</p>
    </div>

    <div class="alert alert-secondary">
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8;">
            <li><strong>Sticker Number:</strong> <span class="font-bold text-primary"><?= e($sticker['sticker_number']) ?></span></li>
            <li><strong>Registered Driver:</strong> <?= e($defaultReceiver) ?> (<?= e($sticker['school_id']) ?>)</li>
            <li><strong>Vehicle:</strong> <?= e($sticker['plate_number']) ?> (<?= e($sticker['vehicle_type']) ?>)</li>
            <li><strong>Application Ref:</strong> <?= e($sticker['application_number']) ?></li>
        </ul>
    </div>

    <form action="<?= BASE_URL ?>/actions/sticker_release_process.php" method="POST" class="standard-form">
        <?= csrfInput() ?>
        <input type="hidden" name="sticker_id" value="<?= $stickerId ?>">

        <div class="form-group">
            <label for="received_by" class="form-label">Full Name of Person Receiving Physical Sticker <span class="text-danger">*</span></label>
            <input type="text" name="received_by" id="received_by" class="form-control" value="<?= e($defaultReceiver) ?>" required>
            <small class="form-hint">Must match presented university/government identification</small>
        </div>

        <div class="form-group">
            <label for="release_remarks" class="form-label">Release Notes / Observations:</label>
            <textarea name="release_remarks" id="release_remarks" rows="3" class="form-control" placeholder="Verified School ID and vehicle registration before issuing physical RFID windshield sticker."></textarea>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>/admin/gatepass/sticker_details.php?id=<?= $stickerId ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-success btn-lg">
                ✓ Record Handover & Activate Gate Pass
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

