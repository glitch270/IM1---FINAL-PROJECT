<?php
/**
 * PARSU-GPass System Configuration
 * University Vehicle Gate Pass Management System
 */

// Set Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Ensure session starts securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // If running on HTTPS, mark secure
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Filesystem Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
define('INCLUDES_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'includes');
define('UPLOADS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Specific Upload Folders
define('UPLOAD_SIGNATURES_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'signatures');
define('UPLOAD_OR_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'official_receipts');
define('UPLOAD_CR_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'certificates_of_registration');
define('UPLOAD_DL_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'drivers_licenses');
define('UPLOAD_AUTH_LETTERS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'authorization_letters');
define('UPLOAD_DEEDS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'deeds_of_sale');
define('UPLOAD_RECEIPTS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'payment_receipts');

// Dynamic Base URL Detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Detect folder path from script name
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If scriptDir contains PARSU-GPass, isolate until /PARSU-GPass
    $pos = stripos($scriptDir, '/PARSU-GPass');
    if ($pos !== false) {
        $baseUrl = $protocol . $host . substr($scriptDir, 0, $pos + strlen('/PARSU-GPass'));
    } else {
        // Fallback for document root or aliases
        $baseUrl = $protocol . $host . rtrim($scriptDir, '/');
    }
    define('BASE_URL', rtrim($baseUrl, '/'));
}

// Application & Gate Pass Business Constants
define('APP_NAME', 'PARSU-GPass');
define('APP_FULL_NAME', 'Partida State University - Online Vehicle Gate Pass System');
define('UNIVERSITY_NAME', 'Partido State University');

// Fee Computation (PHP)
define('FEE_STUDENT', 130.00);
define('FEE_EMPLOYEE', 280.00);

// Gate Pass Validity (Years)
define('VALIDITY_STUDENT_YEARS', 2);
define('VALIDITY_EMPLOYEE_YEARS', 3);

// File Upload Constraints
define('MAX_FILE_SIZE_BYTES', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/pjpeg',
    'image/png',
    'application/pdf'
]);

