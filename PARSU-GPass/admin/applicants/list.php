<?php
/**
 * Admin: Registered Applicants Directory
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$params = [];

$sql = "
    SELECT ap.*, u.email, u.account_status,
           (SELECT COUNT(*) FROM applications WHERE applicant_id = ap.applicant_id) as total_applications,
           (SELECT COUNT(*) FROM vehicles WHERE applicant_id = ap.applicant_id) as total_vehicles
    FROM applicant_profiles ap
    JOIN users u ON ap.user_id = u.user_id
    WHERE 1=1
";

if (!empty($search)) {
    $sql .= " AND (ap.school_id LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.department LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

if (!empty($typeFilter)) {
    $sql .= " AND ap.applicant_type = ?";
    $params[] = $typeFilter;
}

$sql .= " ORDER BY ap.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applicants = $stmt->fetchAll();

$pageTitle = "Registered Applicants Directory";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Registered Applicants Directory</h1>
        <p class="page-subtitle">Directory of university students, faculty, and staff registered in the gate pass portal.</p>
    </div>
    <div>
        <span class="badge badge-primary" style="font-size: 1rem; padding: 8px 14px;">
            <?= count($applicants) ?> Registered
        </span>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/applicants/list.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap" style="flex: 2;">
                <input type="text" name="search" class="form-control" placeholder="Search by School ID, Name, Department, or Email..." value="<?= e($search) ?>">
            </div>
            <div style="flex: 1;">
                <select name="type" class="form-control">
                    <option value="">-- All Classifications --</option>
                    <option value="student" <?= $typeFilter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="employee" <?= $typeFilter === 'employee' ? 'selected' : '' ?>>Employees / Faculty</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search) || !empty($typeFilter)): ?>
                    <a href="<?= BASE_URL ?>/admin/applicants/list.php" class="btn btn-outline-secondary">Reset</a>
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
                    <th>School / Employee ID</th>
                    <th>Full Name</th>
                    <th>Classification</th>
                    <th>College / Department</th>
                    <th>Contact #</th>
                    <th>Email</th>
                    <th>Vehicles</th>
                    <th>Applications</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted" style="padding: 30px;">
                            No registered applicants found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): ?>
                        <tr>
                            <td><strong><?= e($app['school_id']) ?></strong></td>
                            <td>
                                <strong><?= e(trim($app['first_name'] . ' ' . $app['last_name'])) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $app['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>">
                                    <?= ucfirst(e($app['applicant_type'])) ?>
                                </span>
                            </td>
                            <td><?= e($app['department']) ?></td>
                            <td><?= e($app['contact_number']) ?></td>
                            <td><?= e($app['email']) ?></td>
                            <td class="text-center"><?= $app['total_vehicles'] ?></td>
                            <td class="text-center font-bold"><?= $app['total_applications'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/applicants/view.php?id=<?= $app['applicant_id'] ?>" class="btn btn-outline-primary btn-sm">Inspect Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

