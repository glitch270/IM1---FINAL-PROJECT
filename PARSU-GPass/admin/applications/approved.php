<?php
/**
 * Admin: Approved Applications List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.vehicle_type, v.plate_number,
           p.payment_status, p.amount,
           s.sticker_number, s.sticker_status
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    WHERE a.application_status IN ('approved', 'payment_pending', 'payment_verification', 'paid', 'for_release', 'released')
";

if (!empty($search)) {
    $sql .= " AND (a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ? OR s.sticker_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like, $like];
}

$sql .= " ORDER BY a.updated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = "Approved Applications";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Approved Applications</h1>
        <p class="page-subtitle">Applications that passed security document evaluation and are moving through payment and sticker issuance.</p>
    </div>
    <div>
        <span class="badge badge-success" style="font-size: 1rem; padding: 8px 14px;">
            <?= count($applications) ?> Approved
        </span>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/applications/approved.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search approved applications by name, App #, or plate..." value="<?= e($search) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>/admin/applications/approved.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>App Number</th>
                    <th>Applicant</th>
                    <th>Vehicle</th>
                    <th>Stage Status</th>
                    <th>Payment</th>
                    <th>Sticker #</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 30px;">
                            No approved applications found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="link-primary"><?= e($app['application_number']) ?></a></strong>
                            </td>
                            <td>
                                <strong><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong><br>
                                <small class="text-muted"><?= e($app['school_id']) ?> (<?= ucfirst(e($app['applicant_type'])) ?>)</small>
                            </td>
                            <td>
                                <strong><?= e($app['plate_number']) ?></strong><br>
                                <small class="text-muted"><?= e($app['vehicle_type']) ?></small>
                            </td>
                            <td><?= getStatusBadge($app['application_status']) ?></td>
                            <td>
                                <?= $app['payment_status'] ? getStatusBadge($app['payment_status']) : '<span class="badge badge-warning">Awaiting Pay</span>' ?>
                            </td>
                            <td>
                                <?= $app['sticker_number'] ? '<span class="badge badge-indigo">' . e($app['sticker_number']) . '</span>' : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <div class="btn-group-sm">
                                    <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="btn btn-outline-primary btn-sm">View</a>
                                    <?php if ($app['application_status'] === 'paid' && !$app['sticker_number']): ?>
                                        <a href="<?= BASE_URL ?>/admin/gatepass/generate_sticker.php?id=<?= $app['application_id'] ?>" class="btn btn-success btn-sm">Generate Sticker</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

