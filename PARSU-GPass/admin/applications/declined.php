<?php
/**
 * Admin: Declined Applications List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.vehicle_type, v.plate_number,
           (SELECT remarks FROM application_reviews WHERE application_id = a.application_id AND review_decision = 'declined' ORDER BY review_id DESC LIMIT 1) AS decline_remarks,
           (SELECT reviewed_at FROM application_reviews WHERE application_id = a.application_id AND review_decision = 'declined' ORDER BY review_id DESC LIMIT 1) AS declined_at
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.application_status = 'declined'
";

if (!empty($search)) {
    $sql .= " AND (a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY a.updated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = "Declined Applications";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Declined Applications</h1>
        <p class="page-subtitle">Applications that did not satisfy university criteria or had invalid/missing vehicle documents.</p>
    </div>
    <div>
        <span class="badge badge-danger" style="font-size: 1rem; padding: 8px 14px;">
            <?= count($applications) ?> Declined
        </span>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/applications/declined.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search declined applications by applicant, App #, or plate..." value="<?= e($search) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>/admin/applications/declined.php" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Reason / Admin Remarks</th>
                    <th>Declined Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 30px;">
                            No declined applications recorded.
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
                            <td>
                                <div class="text-truncate" style="max-width: 300px; color: #d32f2f;" title="<?= e($app['decline_remarks'] ?? '') ?>">
                                    <?= e($app['decline_remarks'] ?? 'No remarks specified') ?>
                                </div>
                            </td>
                            <td><?= $app['declined_at'] ? date('M d, Y h:i A', strtotime($app['declined_at'])) : date('M d, Y', strtotime($app['updated_at'])) ?></td>
                            <td><?= getStatusBadge($app['application_status']) ?></td>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>

