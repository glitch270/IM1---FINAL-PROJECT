<?php
/**
 * Dedicated Application Lifecycle Tracker Page
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT a.*, v.vehicle_type, v.plate_number,
           p.payment_status, p.amount, p.psu_or_number,
           s.sticker_number, s.sticker_status, s.valid_until,
           rel.released_at, rel.received_by
    FROM applications a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$application = $stmt->fetch();

$pageTitle = "Gate Pass Application Status";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Application Status Tracking</h1>
        <p class="page-subtitle">Real-time status monitoring for your vehicle gate pass application.</p>
    </div>
</div>

<?php if (!$application): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">📄</div>
        <h2 class="empty-state-title">No Application on File</h2>
        <p class="empty-state-desc">You haven't submitted a gate pass application yet.</p>
        <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary">Apply Now</a>
    </div>
<?php else: 
    $status = $application['application_status'];
?>
    <div class="card status-tracking-card">
        <div class="status-top-header">
            <div>
                <span class="tracking-label">APPLICATION NUMBER</span>
                <h2 class="tracking-app-number"><?= e($application['application_number']) ?></h2>
                <span class="tracking-vehicle"><?= e($application['vehicle_type']) ?> &bull; Plate #<strong><?= e($application['plate_number']) ?></strong></span>
            </div>
            <div class="tracking-badge-box">
                <?= getStatusBadge($status) ?>
            </div>
        </div>

        <!-- Lifecycle Steps Flow -->
        <div class="lifecycle-steps-grid" style="margin-top: 30px;">
            <!-- Step 1: Submission -->
            <div class="lifecycle-node completed">
                <div class="node-icon">📝</div>
                <div class="node-title">Submitted</div>
                <div class="node-date"><?= date('M d, Y', strtotime($application['submitted_at'])) ?></div>
                <div class="node-desc">Application & files received</div>
            </div>

            <!-- Step 2: Review -->
            <div class="lifecycle-node <?= in_array($status, ['approved', 'payment_pending', 'payment_verification', 'paid', 'for_release', 'released']) ? 'completed' : ($status === 'declined' ? 'declined' : 'active') ?>">
                <div class="node-icon"><?= $status === 'declined' ? '❌' : '🔍' ?></div>
                <div class="node-title"><?= $status === 'declined' ? 'Declined' : 'Admin Review' ?></div>
                <div class="node-desc">
                    <?= $status === 'declined' ? 'Corrections needed' : (in_array($status, ['approved', 'payment_pending', 'payment_verification', 'paid', 'for_release', 'released']) ? 'Verified & Approved' : 'In review') ?>
                </div>
            </div>

            <!-- Step 3: Payment -->
            <div class="lifecycle-node <?= in_array($status, ['paid', 'for_release', 'released']) ? 'completed' : (in_array($status, ['approved', 'payment_pending', 'payment_verification']) ? 'active' : '') ?>">
                <div class="node-icon">💳</div>
                <div class="node-title">Cashier Payment</div>
                <div class="node-desc">
                    <?php if (in_array($status, ['paid', 'for_release', 'released'])): ?>
                        Paid & Verified
                    <?php elseif ($status === 'payment_verification'): ?>
                        Verifying PSU-OR
                    <?php elseif (in_array($status, ['approved', 'payment_pending'])): ?>
                        Pending Payment
                    <?php else: ?>
                        Awaiting Review
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 4: Sticker -->
            <div class="lifecycle-node <?= ($status === 'released') ? 'completed' : ($status === 'for_release' ? 'active' : '') ?>">
                <div class="node-icon">🏷️</div>
                <div class="node-title">Gate Pass Sticker</div>
                <div class="node-desc">
                    <?php if ($status === 'released'): ?>
                        Released to Driver
                    <?php elseif ($status === 'for_release'): ?>
                        Ready at Security
                    <?php else: ?>
                        Not ready
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Next Action Card -->
        <div class="next-action-box card" style="margin-top: 30px; background: #fafbfc;">
            <h4 class="action-heading">What to do next:</h4>
            <?php if ($status === 'declined'): ?>
                <p>Your application was declined due to issues with uploaded documents or ownership records. Click below to review admin remarks and resubmit.</p>
                <a href="<?= BASE_URL ?>/applicant/application/decline_details.php" class="btn btn-danger">View Reasons & Resubmit</a>
            <?php elseif (in_array($status, ['pending', 'resubmitted'])): ?>
                <p>Your application is in queue for security administration verification. Typical review time is 1-2 business days.</p>
                <a href="<?= BASE_URL ?>/applicant/application/view.php" class="btn btn-outline-primary">View Submitted Application</a>
            <?php elseif (in_array($status, ['approved', 'payment_pending'])): ?>
                <p>Your application is approved! Settle your fee at the Cashier's Office and upload a clear photo or scan of your Official Receipt.</p>
                <a href="<?= BASE_URL ?>/applicant/payment/upload_receipt.php" class="btn btn-gold">Upload PSU-OR Receipt</a>
            <?php elseif ($status === 'payment_verification'): ?>
                <p>We have received your receipt (OR #<?= e($application['psu_or_number'] ?? 'N/A') ?>). Our administration is verifying it with Cashier records.</p>
                <a href="<?= BASE_URL ?>/applicant/payment/payment_status.php" class="btn btn-outline-primary">View Payment Status</a>
            <?php elseif ($status === 'for_release'): ?>
                <p>Your physical sticker is printed and ready! Visit the Campus Security Office with your School ID and Certificate of Registration to receive your vehicle sticker.</p>
                <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-primary">View Sticker Information</a>
            <?php elseif ($status === 'released'): ?>
                <p>Your gate pass is active on campus! Sticker #<strong><?= e($application['sticker_number']) ?></strong> is valid until <strong><?= date('F d, Y', strtotime($application['valid_until'])) ?></strong>.</p>
                <a href="<?= BASE_URL ?>/applicant/gatepass/view_sticker.php" class="btn btn-success">View Active Gate Pass</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

