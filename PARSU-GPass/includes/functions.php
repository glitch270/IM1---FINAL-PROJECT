<?php
/**
 * Core Helper Functions for PARSU-GPass
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escapes HTML output
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generates and returns a CSRF token stored in the session
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders hidden CSRF input field
 */
function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

/**
 * Verifies the incoming POST CSRF token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforces CSRF token check on POST requests
 */
function requireCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            setFlashMessage('error', 'Security validation failed (Invalid CSRF token). Please try submitting the form again.');
            $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
            header("Location: $referer");
            exit;
        }
    }
}

/**
 * Safe redirect helper
 */
function redirect(string $path, ?string $flashType = null, ?string $flashMessage = null): void {
    if ($flashType && $flashMessage) {
        setFlashMessage($flashType, $flashMessage);
    }
    if (!preg_match('#^https?://#i', $path)) {
        $path = BASE_URL . '/' . ltrim($path, '/');
    }
    header("Location: $path");
    exit;
}

/**
 * Get formatted currency in Philippine Pesos
 */
function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

/**
 * Calculates fee based on applicant type
 */
function getFeeByApplicantType(string $type): float {
    return (strtolower($type) === 'employee') ? FEE_EMPLOYEE : FEE_STUDENT;
}

/**
 * Calculates validity period in years based on applicant type
 */
function getValidityYearsByApplicantType(string $type): int {
    return (strtolower($type) === 'employee') ? VALIDITY_EMPLOYEE_YEARS : VALIDITY_STUDENT_YEARS;
}

/**
 * Generates application number in format: GP-YYYY-NNNNNN
 */
function generateApplicationNumber(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM applications WHERE application_number LIKE ?");
    $stmt->execute(["GP-$year-%"]);
    $row = $stmt->fetch();
    $nextSequence = ($row ? (int)$row['total'] : 0) + 1;
    return sprintf("GP-%s-%06d", $year, $nextSequence);
}

/**
 * Generates gate pass sticker number in format: GP-YYYY-NNNNNN
 */
function generateStickerNumber(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM gate_pass_stickers WHERE sticker_number LIKE ?");
    $stmt->execute(["GP-$year-%"]);
    $row = $stmt->fetch();
    $nextSequence = ($row ? (int)$row['total'] : 0) + 1;
    return sprintf("GP-%s-%06d", $year, $nextSequence);
}

/**
 * Returns HTML badge for application status
 */
function getStatusBadge(string $status): string {
    $status = strtolower($status);
    $map = [
        'pending'              => ['label' => 'Pending Review',        'class' => 'badge-warning'],
        'declined'             => ['label' => 'Declined',              'class' => 'badge-danger'],
        'resubmitted'          => ['label' => 'Resubmitted',           'class' => 'badge-info'],
        'approved'             => ['label' => 'Approved (Awaiting Payment)', 'class' => 'badge-primary'],
        'payment_pending'      => ['label' => 'Payment Pending',       'class' => 'badge-warning'],
        'payment_verification' => ['label' => 'Payment Verification',  'class' => 'badge-info'],
        'paid'                 => ['label' => 'Paid',                  'class' => 'badge-success'],
        'for_release'          => ['label' => 'For Sticker Release',   'class' => 'badge-indigo'],
        'released'             => ['label' => 'Sticker Released',      'class' => 'badge-success'],
        'expired'              => ['label' => 'Expired',               'class' => 'badge-secondary'],
        'verified'             => ['label' => 'Verified',              'class' => 'badge-success'],
        'rejected'             => ['label' => 'Rejected',              'class' => 'badge-danger'],
        'pending_verification' => ['label' => 'Pending Verification',  'class' => 'badge-warning'],
    ];

    $info = $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-secondary'];
    return '<span class="badge ' . e($info['class']) . '">' . e($info['label']) . '</span>';
}

/**
 * Atomically updates application status and records history
 */
function updateApplicationStatus(PDO $pdo, int $applicationId, string $newStatus, ?int $changedBy = null, ?string $remarks = null): bool {
    // Get current status
    $stmt = $pdo->prepare("SELECT application_status FROM applications WHERE application_id = ? FOR UPDATE");
    $stmt->execute([$applicationId]);
    $current = $stmt->fetch();
    if (!$current) {
        return false;
    }

    $previousStatus = $current['application_status'];

    // Update applications table
    $upd = $pdo->prepare("UPDATE applications SET application_status = ?, updated_at = NOW() WHERE application_id = ?");
    $upd->execute([$newStatus, $applicationId]);

    // Insert into history
    $hist = $pdo->prepare("INSERT INTO application_status_history (application_id, previous_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $hist->execute([$applicationId, $previousStatus, $newStatus, $changedBy, $remarks]);

    return true;
}

/**
 * Human-readable ownership category label
 */
function getOwnershipCategoryLabel(string $category): string {
    $labels = [
        'first_owner_own_name'         => 'First-hand Owner (Registered under Applicant\'s Name)',
        'first_owner_relative_name'    => 'First-hand Owner (Registered under Relative\'s Name)',
        'secondhand_applicant_buyer'   => 'Second-hand Vehicle (Applicant is Direct Buyer on Deed of Sale)',
        'secondhand_applicant_not_buyer'=> 'Second-hand Vehicle (Applicant is NOT Direct Buyer / Using Relative\'s Bought Vehicle)'
    ];
    return $labels[$category] ?? ucwords(str_replace('_', ' ', $category));
}

