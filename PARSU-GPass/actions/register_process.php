<?php
/**
 * Applicant Registration Process Action (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

$applicantType = trim($_POST['applicant_type'] ?? 'student');
$schoolId = trim($_POST['school_id'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');
$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$suffix = trim($_POST['suffix'] ?? '');
$department = trim($_POST['department'] ?? '');
$courseYearSection = trim($_POST['course_year_section'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (!in_array($applicantType, ['student', 'employee'], true)) {
    redirect('register.php', 'error', 'Please select a valid applicant classification.');
}

if (empty($schoolId) || empty($contactNumber) || empty($firstName) || empty($lastName) || empty($department) || empty($email) || empty($password)) {
    redirect('register.php', 'error', 'Please complete all required fields.');
}

if ($applicantType === 'student' && empty($courseYearSection)) {
    redirect('register.php', 'error', 'Students must provide their Course, Year & Section.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('register.php', 'error', 'The email address format is invalid.');
}

if (strlen($password) < 8) {
    redirect('register.php', 'error', 'Your password must be at least 8 characters long.');
}

if ($password !== $confirmPassword) {
    redirect('register.php', 'error', 'The password confirmation does not match.');
}

$db = getDBConnection();

// Check if email already registered
$checkEmail = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$checkEmail->execute([$email]);
if ($checkEmail->fetch()) {
    redirect('register.php', 'error', 'An account with that email address already exists. Please log in.');
}

// Check if school_id already registered
$checkId = $db->prepare("SELECT applicant_id FROM applicant_profiles WHERE school_id = ? LIMIT 1");
$checkId->execute([$schoolId]);
if ($checkId->fetch()) {
    redirect('register.php', 'error', 'An applicant profile with that School ID number already exists.');
}

// Hash password securely
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    // 1. Insert into users
    $stmtUser = $db->prepare("INSERT INTO users (email, password, account_role, account_status) VALUES (?, ?, 'applicant', 'active')");
    $stmtUser->execute([$email, $hashedPassword]);
    $userId = (int)$db->lastInsertId();

    // 2. Insert into applicant_profiles
    $stmtProfile = $db->prepare("INSERT INTO applicant_profiles (user_id, applicant_type, school_id, first_name, middle_name, last_name, suffix, contact_number, department, course_year_section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtProfile->execute([
        $userId,
        $applicantType,
        $schoolId,
        $firstName,
        $middleName ?: null,
        $lastName,
        $suffix ?: null,
        $contactNumber,
        $department,
        $courseYearSection ?: null
    ]);

    $db->commit();

    redirect('login.php', 'success', 'Registration completed successfully! You may now sign in using your email and password.');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Registration error: " . $e->getMessage());
    redirect('register.php', 'error', 'A system error occurred while creating your account. Please try again.');
}

