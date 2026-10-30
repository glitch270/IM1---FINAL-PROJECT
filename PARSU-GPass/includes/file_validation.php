<?php
/**
 * File Validation and Safe Storage Handler
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Validates an uploaded file array from $_FILES
 *
 * @param array $file Single $_FILES['input_name'] array
 * @return array ['valid' => bool, 'error' => ?string, 'ext' => ?string, 'mime' => ?string]
 */
function validateUploadedFile(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['valid' => false, 'error' => 'No file was uploaded.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['valid' => false, 'error' => 'Exceeded maximum allowable file size (5MB).'];
        default:
            return ['valid' => false, 'error' => 'Upload error code: ' . $file['error']];
    }

    // Check size against application maximum
    if ($file['size'] > MAX_FILE_SIZE_BYTES) {
        return ['valid' => false, 'error' => 'File size exceeds 5MB limit. Please compress or optimize the file.'];
    }

    // Check file extension
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['valid' => false, 'error' => 'Disallowed file extension. Only JPG, JPEG, PNG, and PDF files are accepted.'];
    }

    // Verify MIME type using fileinfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        return ['valid' => false, 'error' => 'Invalid file MIME type (' . htmlspecialchars($mimeType) . '). Genuine JPG, PNG, or PDF required.'];
    }

    return [
        'valid' => true,
        'error' => null,
        'ext'   => $ext,
        'mime'  => $mimeType,
        'size'  => $file['size']
    ];
}

/**
 * Moves validated file to destination directory with a cryptographically secure random name
 *
 * @param array $file $_FILES element
 * @param string $targetDir Absolute directory path
 * @param string $subfolder Relative subfolder name inside uploads/
 * @return array ['success' => bool, 'path' => ?string, 'filename' => ?string, 'error' => ?string]
 */
function storeUploadedFile(array $file, string $targetDir, string $subfolder): array {
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Random safe filename
    $randomName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $validation['ext'];
    $destination = $targetDir . DIRECTORY_SEPARATOR . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file to destination storage.'];
    }

    // Relative path for database storage (e.g. "uploads/official_receipts/filename.pdf")
    $relativePath = 'uploads/' . trim($subfolder, '/') . '/' . $randomName;

    return [
        'success'   => true,
        'filename'  => $randomName,
        'path'      => $relativePath,
        'full_path' => $destination,
        'mime'      => $validation['mime'],
        'size'      => $validation['size']
    ];
}

/**
 * Saves base64 PNG signature to uploads/signatures/
 *
 * @param string $base64Data
 * @return array ['success' => bool, 'path' => ?string, 'filename' => ?string, 'error' => ?string]
 */
function storeBase64Signature(string $base64Data): array {
    if (empty($base64Data)) {
        return ['success' => false, 'error' => 'Signature canvas is empty.'];
    }

    // Check data URL format
    if (!preg_match('/^data:image\/png;base64,(.+)$/', $base64Data, $matches)) {
        return ['success' => false, 'error' => 'Invalid signature data format. PNG required.'];
    }

    $imageData = base64_decode($matches[1]);
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Failed to decode signature image data.'];
    }

    // Ensure signature directory exists
    if (!is_dir(UPLOAD_SIGNATURES_PATH)) {
        mkdir(UPLOAD_SIGNATURES_PATH, 0755, true);
    }

    $filename = 'sig_' . bin2hex(random_bytes(12)) . '_' . time() . '.png';
    $targetPath = UPLOAD_SIGNATURES_PATH . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($targetPath, $imageData) === false) {
        return ['success' => false, 'error' => 'Failed to write signature image to disk.'];
    }

    return [
        'success'   => true,
        'filename'  => $filename,
        'path'      => 'uploads/signatures/' . $filename,
        'full_path' => $targetPath
    ];
}

/**
 * Maps document_code or requirement subject to target upload folder and relative path
 */
function getUploadFolderByDocCode(string $code): array {
    return match (strtoupper($code)) {
        'OR'                   => ['dir' => UPLOAD_OR_PATH, 'subfolder' => 'official_receipts'],
        'CR'                   => ['dir' => UPLOAD_CR_PATH, 'subfolder' => 'certificates_of_registration'],
        'APPLICANT_DL',
        'VEHICLE_OWNER_DL',
        'PREVIOUS_OWNER_DL',
        'BUYER_DL'             => ['dir' => UPLOAD_DL_PATH, 'subfolder' => 'drivers_licenses'],
        'AUTHORIZATION_LETTER' => ['dir' => UPLOAD_AUTH_LETTERS_PATH, 'subfolder' => 'authorization_letters'],
        'DEED_OF_SALE'         => ['dir' => UPLOAD_DEEDS_PATH, 'subfolder' => 'deeds_of_sale'],
        default                => ['dir' => UPLOAD_RECEIPTS_PATH, 'subfolder' => 'payment_receipts']
    };
}

