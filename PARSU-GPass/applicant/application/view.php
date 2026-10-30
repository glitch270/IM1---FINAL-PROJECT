<?php
/**
 * View Application Details (Applicant View)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

// Fetch the applicant's latest application
$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.year_model, v.plate_number, v.color, v.license_number,
           o.ownership_category, o.owner_name, o.buyer_name, o.previous_owner_name, o.relationship_to_applicant,
           sig.signature_file,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number,
           s.sticker_id, s.sticker_number, s.sticker_status, s.valid_until
    FROM applications a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    LEFT JOIN application_signatures sig ON a.application_id = sig.application_id AND sig.is_current = 1
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

if (!$application) {
    setFlashMessage('info', 'You have not submitted an application yet.');
    header("Location: " . BASE_URL . "/applicant/application/create.php");
    exit;
}

$appId = (int)$application['application_id'];

// Fetch current documents
$docStmt = $db->prepare("
    SELECT ad.*, dr.display_label, dr.subject_role, dt.document_code, dt.document_name
    FROM application_documents ad
    JOIN document_requirements dr ON ad.requirement_id = dr.requirement_id
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE ad.application_id = ? AND ad.is_current = 1
    ORDER BY dr.sort_order ASC
");
$docStmt->execute([$appId]);
$documents = $docStmt->fetchAll();

// Fetch status history
$histStmt = $db->prepare("
    SELECT h.*, u.account_role, u.email
    FROM application_status_history h
    LEFT JOIN users u ON h.changed_by = u.user_id
    WHERE h.application_id = ?
    ORDER BY h.changed_at DESC
");
$histStmt->execute([$appId]);
$history = $histStmt->fetchAll();

$pageTitle = "Application Details - " . $application['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Application: <?= e($application['application_number']) ?></h1>
        <p class="page-subtitle">Submitted on <?= date('F d, Y h:i A', strtotime($application['submitted_at'])) ?></p>
    </div>
    <div>
        <?= getStatusBadge($application['application_status']) ?>
    </div>
</div>

<?php if ($application['application_status'] === 'declined'): ?>
    <div class="alert alert-danger">
        <div class="alert-title">⚠️ Application Declined</div>
        <p>Your application was not approved by the security administrator. Please check the decline remarks and submit the requested replacements.</p>
        <div style="margin-top: 10px;">
            <a href="<?= BASE_URL ?>/applicant/application/decline_details.php" class="btn btn-danger btn-sm">View Reason & Resubmit Application</a>
        </div>
    </div>
<?php elseif (in_array($application['application_status'], ['approved', 'payment_pending'])): ?>
    <div class="alert alert-success">
        <div class="alert-title">🎉 Approved! Proceed to Payment</div>
        <p>Your vehicle application has been approved. Settle the fee of <?= formatCurrency(getFeeByApplicantType($currentApplicant['applicant_type'])) ?> at the Cashier and upload your PSU-OR receipt.</p>
        <div style="margin-top: 10px;">
            <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold btn-sm">Upload Cashier Receipt</a>
        </div>
    </div>
<?php endif; ?>

<div class="app-details-grid">
    <!-- Left Column: Details -->
    <div class="app-details-main">
        <!-- Vehicle Details Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Vehicle Specifications</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 30%;">Vehicle Type:</th>
                            <td><?= e($application['vehicle_type']) ?></td>
                            <th style="width: 30%;">Year Model:</th>
                            <td><?= e($application['year_model']) ?></td>
                        </tr>
                        <tr>
                            <th>Plate Number:</th>
                            <td class="font-bold text-primary"><?= e($application['plate_number']) ?></td>
                            <th>Color:</th>
                            <td><?= e($application['color']) ?></td>
                        </tr>
                        <tr>
                            <th>Driver's License No.:</th>
                            <td colspan="3"><?= e($application['license_number']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ownership Details Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Ownership Information</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 30%;">Category:</th>
                            <td colspan="3" class="font-bold"><?= e(getOwnershipCategoryLabel($application['ownership_category'])) ?></td>
                        </tr>
                        <?php if ($application['owner_name']): ?>
                            <tr>
                                <th>Registered Owner:</th>
                                <td><?= e($application['owner_name']) ?></td>
                                <th>Relationship:</th>
                                <td><?= e($application['relationship_to_applicant']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($application['previous_owner_name']): ?>
                            <tr>
                                <th>Previous Owner:</th>
                                <td><?= e($application['previous_owner_name']) ?></td>
                                <th>Buyer Name:</th>
                                <td><?= e($application['buyer_name']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submitted Documents Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Submitted Verification Documents</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Requirement</th>
                            <th>Original File Name</th>
                            <th>Version</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $idx => $doc): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><?= e($doc['display_label']) ?></td>
                                <td><?= e($doc['file_name']) ?></td>
                                <td><span class="badge badge-secondary">v<?= e($doc['version_number']) ?></span></td>
                                <td>
                                    <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View File</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Signature Card -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Applicant Specimen Signature</h3>
            </div>
            <div class="signature-display-wrap">
                <?php if ($application['signature_file']): ?>
                    <img src="<?= BASE_URL . '/' . e($application['signature_file']) ?>" alt="Applicant Signature" style="max-height: 90px; border: 1px solid #ddd; padding: 5px; background: #fff;">
                <?php else: ?>
                    <span class="text-muted">No signature captured.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Timeline & Summary -->
    <div class="app-details-sidebar">
        <!-- Sticker & Gate Pass Summary -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Gate Pass Summary</h3>
            </div>
            <ul class="profile-meta-list">
                <li>
                    <span class="meta-label">Application Status:</span>
                    <span class="meta-value"><?= getStatusBadge($application['application_status']) ?></span>
                </li>
                <li>
                    <span class="meta-label">Payment:</span>
                    <span class="meta-value">
                        <?= $application['payment_status'] ? getStatusBadge($application['payment_status']) : '<span class="text-muted">Not paid</span>' ?>
                    </span>
                </li>
                <li>
                    <span class="meta-label">Sticker Number:</span>
                    <span class="meta-value font-bold">
                        <?= $application['sticker_number'] ? e($application['sticker_number']) : '<span class="text-muted">Not generated</span>' ?>
                    </span>
                </li>
                <?php if ($application['valid_until']): ?>
                    <li>
                        <span class="meta-label">Valid Until:</span>
                        <span class="meta-value text-success font-bold"><?= date('F d, Y', strtotime($application['valid_until'])) ?></span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Status History Timeline -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Status History Timeline</h3>
            </div>
            <div class="timeline-container">
                <?php if (empty($history)): ?>
                    <p class="text-muted">No history records yet.</p>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-status"><?= getStatusBadge($h['new_status']) ?></span>
                                    <span class="timeline-date"><?= date('M d, Y h:i A', strtotime($h['changed_at'])) ?></span>
                                </div>
                                <?php if ($h['remarks']): ?>
                                    <div class="timeline-remarks"><?= e($h['remarks']) ?></div>
                                <?php endif; ?>
                                <small class="timeline-user">By: <?= e($h['account_role'] ?? 'System') ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

