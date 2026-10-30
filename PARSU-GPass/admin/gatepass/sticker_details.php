<?php
/**
 * Admin: Printable Gate Pass Sticker Card & Details
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$stickerId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT s.*, a.application_number,
           ap.first_name, ap.middle_name, ap.last_name, ap.suffix, ap.school_id, ap.department, ap.course_year_section,
           v.vehicle_type, v.plate_number, v.year_model, v.color, v.license_number,
           p.amount, p.psu_or_number, p.payment_date,
           rel.release_id, rel.released_at, rel.received_by, rel.release_remarks,
           u_rel.email as released_by_admin
    FROM gate_pass_stickers s
    JOIN applications a ON s.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    LEFT JOIN users u_rel ON rel.released_by = u_rel.user_id
    WHERE s.sticker_id = ?
");
$stmt->execute([$stickerId]);
$sticker = $stmt->fetch();

if (!$sticker) {
    redirect('admin/gatepass/sticker_list.php', 'error', 'Sticker record not found.');
}

$pageTitle = "Gate Pass Sticker - " . $sticker['sticker_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Gate Pass Sticker: <?= e($sticker['sticker_number']) ?></h1>
        <p class="page-subtitle">Status: <?= getStatusBadge($sticker['sticker_status']) ?> &bull; Vehicle: <?= e($sticker['plate_number']) ?></p>
    </div>
    <div class="header-action-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Sticker Badge</button>
        <?php if ($sticker['sticker_status'] === 'for_release'): ?>
            <a href="<?= BASE_URL ?>/admin/gatepass/release.php?id=<?= $stickerId ?>" class="btn btn-success">Record Physical Release</a>
        <?php endif; ?>
    </div>
</div>

<!-- Physical Sticker Badge Preview Box -->
<div class="sticker-print-container" style="display: flex; justify-content: center; margin: 20px 0;">
    <div class="physical-sticker-card <?= $sticker['applicant_type'] === 'employee' ? 'sticker-employee' : 'sticker-student' ?>">
        <div class="sticker-header">
            <div class="sticker-emblem-wrap">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="University Seal" class="sticker-emblem">
            </div>
            <div class="sticker-header-text">
                <h4 class="sticker-univ-title"><?= strtoupper(e(UNIVERSITY_NAME)) ?></h4>
                <span class="sticker-tagline">OFFICIAL CAMPUS VEHICLE GATE PASS</span>
            </div>
            <div class="sticker-type-pill">
                <?= strtoupper(e($sticker['applicant_type'])) ?>
            </div>
        </div>

        <div class="sticker-body">
            <div class="sticker-number-banner">
                <?= e($sticker['sticker_number']) ?>
            </div>

            <div class="sticker-plate-box">
                <span class="plate-label">VEHICLE PLATE / MV FILE:</span>
                <div class="plate-code"><?= e($sticker['plate_number']) ?></div>
                <span class="plate-type"><?= e($sticker['vehicle_type']) ?> &bull; <?= e($sticker['color']) ?></span>
            </div>

            <div class="sticker-holder-info">
                <div class="holder-name"><?= e(trim($sticker['first_name'] . ' ' . $sticker['last_name'])) ?></div>
                <div class="holder-sub"><?= e($sticker['school_id']) ?> &bull; <?= e($sticker['department']) ?></div>
            </div>
        </div>

        <div class="sticker-footer">
            <div class="sticker-validity-col">
                <span class="valid-label">EXPIRY DATE:</span>
                <span class="valid-val text-danger font-bold"><?= strtoupper(date('M d, Y', strtotime($sticker['valid_until']))) ?></span>
            </div>
            <div class="sticker-barcode-mock">
                ||| | |||| | || ||||| | ||||| | |||
            </div>
        </div>
    </div>
</div>

<!-- Detailed Meta Card -->
<div class="card no-print" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header-tabs">
        <h3 class="card-heading">Sticker Issuance & Physical Release Record</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width: 35%;">Sticker Number:</th>
                    <td class="font-bold text-primary"><?= e($sticker['sticker_number']) ?></td>
                </tr>
                <tr>
                    <th>Application Number:</th>
                    <td><a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $sticker['application_id'] ?>" class="link-primary"><?= e($sticker['application_number']) ?></a></td>
                </tr>
                <tr>
                    <th>Issue Date:</th>
                    <td><?= date('F d, Y', strtotime($sticker['issue_date'])) ?></td>
                </tr>
                <tr>
                    <th>Valid Until:</th>
                    <td class="font-bold text-success"><?= date('F d, Y', strtotime($sticker['valid_until'])) ?></td>
                </tr>
                <tr>
                    <th>Current Sticker Status:</th>
                    <td><?= getStatusBadge($sticker['sticker_status']) ?></td>
                </tr>
                <?php if ($sticker['released_at']): ?>
                    <tr>
                        <th>Physical Release Date:</th>
                        <td class="font-bold text-success"><?= date('F d, Y h:i A', strtotime($sticker['released_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>Received In-Person By:</th>
                        <td class="font-bold"><?= e($sticker['received_by']) ?></td>
                    </tr>
                    <tr>
                        <th>Released By Officer:</th>
                        <td><?= e($sticker['released_by_admin'] ?? 'Security Staff') ?></td>
                    </tr>
                    <tr>
                        <th>Release Remarks:</th>
                        <td><?= e($sticker['release_remarks'] ?? 'Physical sticker and certificate handed over.') ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <th>Physical Release:</th>
                        <td>
                            <span class="badge badge-warning">Awaiting Pickup</span> - Physical sticker is at the Campus Security Office.
                            <div style="margin-top: 8px;">
                                <a href="<?= BASE_URL ?>/admin/gatepass/release.php?id=<?= $stickerId ?>" class="btn btn-success btn-sm">Record Physical Release Now</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

