<?php
/**
 * Admin: Pending Applications List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "
    SELECT a.*, ap.first_name, ap.last_name, ap.school_id, ap.applicant_type, ap.department,
           v.vehicle_type, v.plate_number, o.ownership_category
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    WHERE a.application_status = 'pending'
";

if (!empty($search)) {
    $sql .= " AND (a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY a.submitted_at ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = "Pending Applications Review";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Pending Applications</h1>
        <p class="page-subtitle">Initial gate pass applications submitted by students and employees awaiting document verification.</p>
    </div>
    <div>
        <span class="badge badge-warning" style="font-size: 1rem; padding: 8px 14px;">
            <?= count($applications) ?> Pending Review
        </span>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/applications/pending.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap">
                <input type="text" name="search" class="form-control" placeholder="Search by App #, Applicant Name, ID, or Plate Number..." value="<?= e($search) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>/admin/applications/pending.php" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Type</th>
                    <th>Vehicle</th>
                    <th>Ownership Category</th>
                    <th>Submitted Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                            <?= !empty($search) ? 'No pending applications matched your search query.' : 'No pending applications in queue.' ?>
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
                                <small class="text-muted"><?= e($app['school_id']) ?> &bull; <?= e($app['department']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $app['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>">
                                    <?= ucfirst(e($app['applicant_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= e($app['plate_number']) ?></strong><br>
                                <small class="text-muted"><?= e($app['vehicle_type']) ?></small>
                            </td>
                            <td>
                                <span class="ownership-tag"><?= e(getOwnershipCategoryLabel($app['ownership_category'])) ?></span>
                            </td>
                            <td><?= date('M d, Y h:i A', strtotime($app['submitted_at'])) ?></td>
                            <td><?= getStatusBadge($app['application_status']) ?></td>
                            <td>
                                <div class="btn-group-sm">
                                    <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $app['application_id'] ?>" class="btn btn-primary btn-sm">Review</a>
                                    <a href="<?= BASE_URL ?>/admin/applications/review_documents.php?id=<?= $app['application_id'] ?>" class="btn btn-outline-secondary btn-sm" title="Inspect files">Files</a>
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

