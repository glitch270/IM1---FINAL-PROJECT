<?php
/**
 * Applicant Profile View & Update Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];
$userId = (int)$_SESSION['user_id'];

// Process profile update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $contactNumber = trim($_POST['contact_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $courseYearSection = trim($_POST['course_year_section'] ?? '');

    if (empty($contactNumber) || empty($department)) {
        setFlashMessage('error', 'Contact number and department are required.');
    } else {
        $upd = $db->prepare("UPDATE applicant_profiles SET contact_number = ?, department = ?, course_year_section = ? WHERE applicant_id = ?");
        $upd->execute([$contactNumber, $department, $courseYearSection ?: null, $applicantId]);
        setFlashMessage('success', 'Profile information updated successfully.');
        
        // Refresh local variable
        $stmt = $db->prepare("SELECT * FROM applicant_profiles WHERE applicant_id = ?");
        $stmt->execute([$applicantId]);
        $currentApplicant = $stmt->fetch();
    }
}

$pageTitle = "My Applicant Profile";
include __DIR__ . '/../includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Applicant Profile</h1>
        <p class="page-subtitle">View and update your registered campus identity and contact information.</p>
    </div>
</div>

<div class="profile-layout-grid">
    <!-- Profile Summary Card -->
    <div class="card profile-card">
        <div class="profile-avatar-wrap">
            <div class="profile-avatar-large">
                <?= strtoupper(substr($currentApplicant['first_name'], 0, 1) . substr($currentApplicant['last_name'], 0, 1)) ?>
            </div>
            <h2 class="profile-fullname"><?= e(trim($currentApplicant['first_name'] . ' ' . $currentApplicant['middle_name'] . ' ' . $currentApplicant['last_name'] . ' ' . $currentApplicant['suffix'])) ?></h2>
            <div class="profile-role-badge">
                <span class="badge badge-primary"><?= strtoupper(e($currentApplicant['applicant_type'])) ?></span>
            </div>
        </div>

        <ul class="profile-meta-list">
            <li>
                <span class="meta-label">School / Employee ID:</span>
                <span class="meta-value font-bold"><?= e($currentApplicant['school_id']) ?></span>
            </li>
            <li>
                <span class="meta-label">Email Address:</span>
                <span class="meta-value"><?= e($_SESSION['user_email'] ?? '') ?></span>
            </li>
            <li>
                <span class="meta-label">Classification:</span>
                <span class="meta-value"><?= ucfirst(e($currentApplicant['applicant_type'])) ?></span>
            </li>
            <li>
                <span class="meta-label">Registered Since:</span>
                <span class="meta-value"><?= date('F d, Y', strtotime($currentApplicant['created_at'])) ?></span>
            </li>
        </ul>
    </div>

    <!-- Editable Details Form Card -->
    <div class="card profile-form-card">
        <div class="card-header-tabs">
            <h3 class="card-heading">Update Contact & Department Details</h3>
            <p class="card-subtext">Ensure your contact number is active for urgent gate pass notices.</p>
        </div>

        <form action="<?= BASE_URL ?>/applicant/profile.php" method="POST" class="standard-form">
            <?= csrfInput() ?>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="form-label">First Name (Official)</label>
                    <input type="text" class="form-control" value="<?= e($currentApplicant['first_name']) ?>" disabled>
                    <small class="form-hint">Contact administration to update official legal name</small>
                </div>

                <div class="form-group col-md-6">
                    <label class="form-label">Last Name (Official)</label>
                    <input type="text" class="form-control" value="<?= e($currentApplicant['last_name']) ?>" disabled>
                </div>
            </div>

            <div class="form-group">
                <label for="contact_number" class="form-label">Active Mobile / Contact Number <span class="text-danger">*</span></label>
                <input type="tel" name="contact_number" id="contact_number" class="form-control" value="<?= e($currentApplicant['contact_number']) ?>" maxlength="11" required>
            </div>

            <div class="form-group">
                <label for="department" class="form-label">College / Department / Office <span class="text-danger">*</span></label>
                <input type="text" name="department" id="department" class="form-control" value="<?= e($currentApplicant['department']) ?>" required>
            </div>

            <div class="form-group">
                <label for="course_year_section" class="form-label">Course, Year & Section / Designation</label>
                <input type="text" name="course_year_section" id="course_year_section" class="form-control" value="<?= e($currentApplicant['course_year_section'] ?? '') ?>">
            </div>

            <div class="form-actions" style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

