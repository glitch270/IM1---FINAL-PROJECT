<?php
/**
 * Admin: Gate Pass Sticker Inventory & Distribution List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$params = [];

$sql = "
    SELECT s.*, a.application_number, ap.first_name, ap.last_name, ap.school_id, ap.department,
           v.plate_number, v.vehicle_type, v.color,
           rel.release_id, rel.released_at, rel.received_by,
           u_admin.email as generator_email
    FROM gate_pass_stickers s
    JOIN applications a ON s.application_id = a.application_id
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    LEFT JOIN users u_admin ON s.generated_by = u_admin.user_id
    WHERE 1=1
";

if (!empty($search)) {
    $sql .= " AND (s.sticker_number LIKE ? OR a.application_number LIKE ? OR ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.school_id LIKE ? OR v.plate_number LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like, $like];
}

if (!empty($statusFilter)) {
    $sql .= " AND s.sticker_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY s.generated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$stickers = $stmt->fetchAll();

$pageTitle = "Gate Pass Stickers Inventory";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Gate Pass Stickers Inventory</h1>
        <p class="page-subtitle">Track generated RFID stickers, distribution status, and physical releases to campus vehicle owners.</p>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/gatepass/sticker_list.php" class="filter-form">
        <div class="filter-row">
            <div class="search-input-wrap" style="flex: 2;">
                <input type="text" name="search" class="form-control" placeholder="Search by Sticker #, Plate #, Applicant Name, or School ID..." value="<?= e($search) ?>">
            </div>
            <div style="flex: 1;">
                <select name="status" class="form-control">
                    <option value="">-- All Sticker Statuses --</option>
                    <option value="for_release" <?= $statusFilter === 'for_release' ? 'selected' : '' ?>>For Release</option>
                    <option value="released" <?= $statusFilter === 'released' ? 'selected' : '' ?>>Released</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="<?= BASE_URL ?>/admin/gatepass/sticker_list.php" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Sticker Number</th>
                    <th>Application #</th>
                    <th>Applicant Name</th>
                    <th>Type</th>
                    <th>Vehicle Plate</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Released Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stickers)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted" style="padding: 30px;">
                            No gate pass stickers found matching your search.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stickers as $stk): ?>
                        <tr>
                            <td>
                                <strong class="text-primary" style="font-size: 1.05rem;">
                                    <a href="<?= BASE_URL ?>/admin/gatepass/sticker_details.php?id=<?= $stk['sticker_id'] ?>" class="link-primary"><?= e($stk['sticker_number']) ?></a>
                                </strong>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/applications/view.php?id=<?= $stk['application_id'] ?>" class="link-muted"><?= e($stk['application_number']) ?></a>
                            </td>
                            <td>
                                <strong><?= e($stk['first_name'] . ' ' . $stk['last_name']) ?></strong><br>
                                <small class="text-muted"><?= e($stk['school_id']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $stk['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>">
                                    <?= ucfirst(e($stk['applicant_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= e($stk['plate_number']) ?></strong><br>
                                <small class="text-muted"><?= e($stk['vehicle_type']) ?></small>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($stk['valid_until'])) ?>
                            </td>
                            <td><?= getStatusBadge($stk['sticker_status']) ?></td>
                            <td>
                                <?php if ($stk['released_at']): ?>
                                    <small class="text-success font-bold">Claimed by <?= e($stk['received_by']) ?></small><br>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($stk['released_at'])) ?></small>
                                <?php else: ?>
                                    <span class="badge badge-warning">At Security Office</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group-sm">
                                    <a href="<?= BASE_URL ?>/admin/gatepass/sticker_details.php?id=<?= $stk['sticker_id'] ?>" class="btn btn-outline-primary btn-sm" title="View printable badge">Sticker</a>
                                    <?php if ($stk['sticker_status'] === 'for_release'): ?>
                                        <a href="<?= BASE_URL ?>/admin/gatepass/release.php?id=<?= $stk['sticker_id'] ?>" class="btn btn-success btn-sm">Release</a>
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

