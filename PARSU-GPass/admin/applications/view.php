<?php
/**
 * Admin: Comprehensive Application Detail View & Decision Interface
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$db = getDBConnection();
$appId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT a.*, ap.first_name, ap.middle_name, ap.last_name, ap.suffix, ap.school_id, ap.applicant_type, ap.contact_number, ap.department, ap.course_year_section, u.email as applicant_email,
           v.vehicle_id, v.vehicle_type, v.year_model, v.plate_number, v.color, v.license_number,
           o.ownership_category, o.owner_name, o.buyer_name, o.previous_owner_name, o.relationship_to_applicant,
           sig.signature_file,
           p.payment_id, p.payment_status, p.amount, p.psu_or_number, p.receipt_file,
           s.sticker_id, s.sticker_number, s.sticker_status, s.valid_until,
           rel.released_at, rel.received_by
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN users u ON ap.user_id = u.user_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN ownership_details o ON a.application_id = o.application_id
    LEFT JOIN application_signatures sig ON a.application_id = sig.application_id AND sig.is_current = 1
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    WHERE a.application_id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    setFlashMessage('error', 'Application not found.');
    header("Location: " . BASE_URL . "/admin/applications/pending.php");
    exit;
}

// Fetch all documents for this application (both current and historical)
$docStmt = $db->prepare("
    SELECT ad.*, dr.display_label, dr.subject_role, dt.document_code, dt.document_name
    FROM application_documents ad
    JOIN document_requirements dr ON ad.requirement_id = dr.requirement_id
    JOIN document_types dt ON dr.document_type_id = dt.document_type_id
    WHERE ad.application_id = ?
    ORDER BY ad.is_current DESC, dr.sort_order ASC, ad.version_number DESC
");
$docStmt->execute([$appId]);
$documents = $docStmt->fetchAll();

// Fetch reviews
$revStmt = $db->prepare("
    SELECT r.*, u.email as admin_email
    FROM application_reviews r
    JOIN users u ON r.admin_id = u.user_id
    WHERE r.application_id = ?
    ORDER BY r.reviewed_at DESC
");
$revStmt->execute([$appId]);
$reviews = $revStmt->fetchAll();

// Fetch status history
$histStmt = $db->prepare("
    SELECT h.*, u.email as user_email, u.account_role
    FROM application_status_history h
    LEFT JOIN users u ON h.changed_by = u.user_id
    WHERE h.application_id = ?
    ORDER BY h.changed_at DESC
");
$histStmt->execute([$appId]);
$history = $histStmt->fetchAll();

$pageTitle = "Review Application: " . $app['application_number'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Application: <?= e($app['application_number']) ?></h1>
        <p class="page-subtitle">Submitted: <?= date('F d, Y h:i A', strtotime($app['submitted_at'])) ?> &bull; Status: <?= getStatusBadge($app['application_status']) ?></p>
    </div>
    <div class="header-action-group">
        <a href="<?= BASE_URL ?>/admin/applications/review_documents.php?id=<?= $appId ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-expand"></i> Inspect Documents Full-screen
        </a>
        <?php if (in_array($app['application_status'], ['pending', 'resubmitted'])): ?>
            <button type="button" class="btn btn-success" onclick="openApproveModal()">
                <i class="fa-solid fa-check"></i> Approve Application
            </button>
            <button type="button" class="btn btn-danger" onclick="openDeclineModal()">
                <i class="fa-solid fa-xmark"></i> Decline Application
            </button>
        <?php elseif ($app['application_status'] === 'paid' && !$app['sticker_number']): ?>
            <a href="<?= BASE_URL ?>/admin/gatepass/generate_sticker.php?id=<?= $appId ?>" class="btn btn-primary">
                <i class="fa-solid fa-id-card"></i> Generate Gate Pass Sticker
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="app-details-grid">
    <div class="app-details-main">
        <!-- Applicant Information Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Applicant Profile</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 25%;">Full Legal Name:</th>
                            <td class="font-bold"><?= e(trim($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name'] . ' ' . $app['suffix'])) ?></td>
                            <th style="width: 25%;">Classification:</th>
                            <td><span class="badge <?= $app['applicant_type'] === 'employee' ? 'badge-indigo' : 'badge-primary' ?>"><?= strtoupper(e($app['applicant_type'])) ?></span></td>
                        </tr>
                        <tr>
                            <th>School / Employee ID:</th>
                            <td><?= e($app['school_id']) ?></td>
                            <th>Email Address:</th>
                            <td><?= e($app['applicant_email']) ?></td>
                        </tr>
                        <tr>
                            <th>Department / College:</th>
                            <td><?= e($app['department']) ?></td>
                            <th>Mobile Number:</th>
                            <td><?= e($app['contact_number']) ?></td>
                        </tr>
                        <tr>
                            <th>Course / Year / Role:</th>
                            <td colspan="3"><?= e($app['course_year_section'] ?? 'N/A') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vehicle Details Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Registered Vehicle & License</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 25%;">Vehicle Classification:</th>
                            <td><?= e($app['vehicle_type']) ?></td>
                            <th style="width: 25%;">Year Model:</th>
                            <td><?= e($app['year_model']) ?></td>
                        </tr>
                        <tr>
                            <th>Plate / MV File Number:</th>
                            <td class="font-bold text-primary" style="font-size: 1.1rem;"><?= e($app['plate_number']) ?></td>
                            <th>Color:</th>
                            <td><?= e($app['color']) ?></td>
                        </tr>
                        <tr>
                            <th>Driver's License Number:</th>
                            <td colspan="3" class="font-bold"><?= e($app['license_number']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ownership Details Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Ownership Category & Legal Chain</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 25%;">Ownership Category:</th>
                            <td colspan="3" class="font-bold text-primary"><?= e(getOwnershipCategoryLabel($app['ownership_category'])) ?></td>
                        </tr>
                        <?php if ($app['owner_name']): ?>
                            <tr>
                                <th>Registered Owner (CR):</th>
                                <td><?= e($app['owner_name']) ?></td>
                                <th>Relationship to Applicant:</th>
                                <td><?= e($app['relationship_to_applicant']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($app['previous_owner_name']): ?>
                            <tr>
                                <th>Previous Owner (Seller):</th>
                                <td><?= e($app['previous_owner_name']) ?></td>
                                <th>Buyer / Authorized:</th>
                                <td><?= e($app['buyer_name']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submitted Documents Verification Table -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-flex">
                <div>
                    <h3 class="card-heading">Submitted Verification Documents</h3>
                    <span class="card-subtext">Click on any file to open preview in a new window</span>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/admin/applications/review_documents.php?id=<?= $appId ?>" class="btn btn-outline-primary btn-sm">Document Inspector</a>
                </div>
            </div>

            <div class="table-responsive" style="margin-top: 15px;">
                <table class="table table-hover table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Requirement Label</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Version</th>
                            <th>State</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $i => $doc): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= e($doc['display_label']) ?></strong><br>
                                    <small class="text-muted"><?= e($doc['document_code']) ?></small>
                                </td>
                                <td><?= e($doc['file_name']) ?></td>
                                <td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td>
                                <td><span class="badge badge-secondary">v<?= e($doc['version_number']) ?></span></td>
                                <td>
                                    <?= $doc['is_current'] ? '<span class="badge badge-success">Current</span>' : '<span class="badge badge-secondary">Replaced</span>' ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL . '/' . e($doc['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Inspect File</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Digital Specimen Signature -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Applicant Specimen Signature</h3>
            </div>
            <div class="signature-display-wrap">
                <?php if ($app['signature_file']): ?>
                    <img src="<?= BASE_URL . '/' . e($app['signature_file']) ?>" alt="Applicant Signature" style="max-height: 100px; border: 1px solid #ccc; background: #fff; padding: 10px;">
                <?php else: ?>
                    <span class="text-danger">No specimen signature recorded.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Column: Reviews, Status History -->
    <div class="app-details-sidebar">
        <!-- Review Action Box (For Pending/Resubmitted) -->
        <?php if (in_array($app['application_status'], ['pending', 'resubmitted'])): ?>
            <div class="card" style="margin-bottom: 20px; border-top: 4px solid #000035;">
                <h3 class="card-heading" style="margin-bottom: 15px;">Evaluation Action</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" class="btn btn-success btn-block" onclick="openApproveModal()">
                        <i class="fa-solid fa-check"></i> Approve Application
                    </button>
                    <button type="button" class="btn btn-danger btn-block" onclick="openDeclineModal()">
                        <i class="fa-solid fa-xmark"></i> Decline Application
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reviews History -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header-tabs">
                <h3 class="card-heading">Evaluator Reviews</h3>
            </div>
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No review decisions recorded yet.</p>
            <?php else: ?>
                <div class="review-history-items">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item-box <?= $rev['review_decision'] === 'approved' ? 'rev-approved' : 'rev-declined' ?>" style="margin-bottom: 12px; padding: 10px; border: 1px solid #eee; border-radius: 6px;">
                            <div class="rev-header">
                                <span class="badge <?= $rev['review_decision'] === 'approved' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst(e($rev['review_decision'])) ?>
                                </span>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($rev['reviewed_at'])) ?></small>
                            </div>
                            <div class="rev-body" style="margin-top: 6px; font-size: 0.9rem;">
                                <?= nl2br(e($rev['remarks'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Status History Timeline -->
        <div class="card">
            <div class="card-header-tabs">
                <h3 class="card-heading">Status History</h3>
            </div>
            <div class="timeline-container">
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<i class="fa-solid fa-check"></i> Approve Application -->
<div class="modal-backdrop" id="approveModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content card">
            <div class="modal-header">
                <h3 class="modal-title text-success">Approve Gate Pass Application</h3>
                <button type="button" class="modal-close-btn" onclick="closeApproveModal()">&times;</button>
            </div>
            <form action="<?= BASE_URL ?>/actions/application_review_process.php" method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="application_id" value="<?= $appId ?>">
                <input type="hidden" name="review_decision" value="approved">

                <div class="modal-body">
                    <p>Are you sure you want to approve application <strong><?= e($app['application_number']) ?></strong> for <strong><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong>?</p>
                    <p>Once approved, the status will advance to <strong>Payment Pending</strong> and the applicant will be prompted to settle the fee of <strong><?= formatCurrency(getFeeByApplicantType($app['applicant_type'])) ?></strong>.</p>
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="approve_remarks" class="form-label">Approval Remarks (Optional):</label>
                        <textarea name="remarks" id="approve_remarks" rows="3" class="form-control" placeholder="All vehicle documents verified authentic with LTO guidelines."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeApproveModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<i class="fa-solid fa-xmark"></i> Decline Application -->
<div class="modal-backdrop" id="declineModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content card">
            <div class="modal-header">
                <i class="fa-solid fa-xmark"></i> Decline Application</h3>
                <button type="button" class="modal-close-btn" onclick="closeDeclineModal()">&times;</button>
            </div>
            <form action="<?= BASE_URL ?>/actions/application_review_process.php" method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="application_id" value="<?= $appId ?>">
                <input type="hidden" name="review_decision" value="declined">

                <div class="modal-body">
                    <p>You are declining application <strong><?= e($app['application_number']) ?></strong>. A clear reason must be provided so the applicant can upload corrections.</p>
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="decline_remarks" class="form-label">Decline Reason & Required Actions <span class="text-danger">*</span></label>
                        <textarea name="remarks" id="decline_remarks" rows="4" class="form-control" placeholder="Specify which documents are blurry, expired, or missing (e.g. 'Certificate of Registration is expired; driver license is missing back side specimen signatures')..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeDeclineModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openApproveModal() {
    document.getElementById('approveModal').style.display = 'flex';
}
function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}
function openDeclineModal() {
    document.getElementById('declineModal').style.display = 'flex';
}
function closeDeclineModal() {
    document.getElementById('declineModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


