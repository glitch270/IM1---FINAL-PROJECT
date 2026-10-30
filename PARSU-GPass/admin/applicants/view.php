<?php
/**
 * Admin: View Individual Applicant Profile & Record History
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$applicantId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT ap.*, u.email, u.account_status
    FROM applicant_profiles ap
    JOIN users u ON ap.user_id = u.user_id
    WHERE ap.applicant_id = ?
");
$stmt->execute([$applicantId]);
$applicant = $stmt->fetch();

if (!$applicant) {
    redirect('admin/applicants/list.php', 'error', 'Applicant record not found.');
}

// Applications submitted by this applicant
$appStmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number, v.year_model, v.color,
           p.payment_status, p.amount,
           s.sticker_number, s.sticker_status
    FROM applications a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC
");
$appStmt->execute([$applicantId]);
$applications = $appStmt->fetchAll();

// Registered vehicles
$vehStmt = $db->prepare("SELECT * FROM vehicles WHERE applicant_id = ? ORDER BY created_at DESC");
$vehStmt->execute([$applicantId]);
$vehicles = $vehStmt->fetchAll();

$pageTitle = "Applicant: " . $applicant['first_name'] . ' ' . $applicant['last_name'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title"><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></h1>
        <p class="page-subtitle"><?= strtoupper(e($applicant['applicant_type'])) ?> &bull; ID: <?= e($applicant['school_id']) ?></p>
    </div>
    <div class="header-action-group">
        <a href="<?= BASE_URL ?>/admin/applicants/list.php" class="btn btn-outline-secondary">← Back to Applicants List</a>
    </div>
</div>

<div class="app-details-grid">
    <div class="app-details-main">
        <!-- Profile Details Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Applicant Identity</h3>
            </div>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 25%;">Full Name:</th>
                        <td class="font-bold"><?= e(trim($applicant['first_name'] . ' ' . $applicant['middle_name'] . ' ' . $applicant['last_name'] . ' ' . $applicant['suffix'])) ?></td>
                        <th style="width: 25%;">School ID:</th>
                        <td><?= e($applicant['school_id']) ?></td>
                    </tr>
                    <tr>
                        <th>Classification:</th>
                        <td><span class="badge <?= $applicant['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>"><?= ucfirst(e($applicant['applicant_type'])) ?></span></td>
                        <th>Email:</th>
                        <td><?= e($applicant['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Department:</th>
                        <td><?= e($applicant['department']) ?></td>
                        <th>Mobile Number:</th>
                        <td><?= e($applicant['contact_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Course / Role:</th>
                        <td><?= e($applicant['course_year_section'] ?? 'N/A') ?></td>
                        <th>Registered Date:</th>
                        <td><?= date('F d, Y', strtotime($applicant['created_at'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Applications History -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Applications History (<?= count($applications) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>App #</th>
                            <th>Vehicle</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Sticker #</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No applications submitted yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><strong><?= e($app['application_number']) ?></strong></td>
                                    <td><?= e($app['plate_number']) ?> (<?= e($app['vehicle_type']) ?>)</td>
                                    <td><?= date('M d, Y', strtotime($app['submitted_at'])) ?></td>
                                    <td><?= getStatusBadge($app['application_status']) ?></td>
                                    <td><?= $app['payment_status'] ? getStatusBadge($app['payment_status']) : '<span class="text-muted">None</span>' ?></td>
                                    <td><?= $app['sticker_number'] ? '<span class="badge badge-indigo">' . e($app['sticker_number']) . '</span>' : '-' ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="btn btn-outline-primary btn-sm">Inspect</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Registered Vehicles -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Registered Vehicles (<?= count($vehicles) ?>)</h3>
            </div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Type</th>
                        <th>Year Model</th>
                        <th>Color</th>
                        <th>Driver's License No.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No registered vehicles.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td class="font-bold text-primary"><?= e($v['plate_number']) ?></td>
                                <td><?= e($v['vehicle_type']) ?></td>
                                <td><?= e($v['year_model']) ?></td>
                                <td><?= e($v['color']) ?></td>
                                <td><?= e($v['license_number']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-details-sidebar">
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Account Status</h3>
            </div>
            <p><strong>Status:</strong> <span class="badge badge-success"><?= strtoupper(e($applicant['account_status'])) ?></span></p>
            <p><strong>Total Applications:</strong> <?= count($applications) ?></p>
            <p><strong>Total Vehicles:</strong> <?= count($vehicles) ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

