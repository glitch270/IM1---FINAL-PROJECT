<?php
/**
 * Signature Upload / Save Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';
require_once __DIR__ . '/../includes/file_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/application/signature.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

if (!isset($_SESSION['application_draft'])) {
    redirect('applicant/application/create.php', 'warning', 'Please start a new application.');
}

$signatureData = $_POST['signature_data'] ?? '';
$draft = &$_SESSION['application_draft'];

// If no new signature drawn but already had one saved in draft, proceed
if (empty($signatureData) && !empty($draft['signature'])) {
    redirect('applicant/application/requirements.php', 'info', 'Retained previously saved signature.');
}

if (empty($signatureData)) {
    redirect('applicant/application/signature.php', 'error', 'Please draw your specimen signature on the canvas pad before proceeding.');
}

$saveResult = storeBase64Signature($signatureData);

if (!$saveResult['success']) {
    redirect('applicant/application/signature.php', 'error', $saveResult['error']);
}

$draft['signature'] = [
    'path'     => $saveResult['path'],
    'filename' => $saveResult['filename']
];

redirect('applicant/application/requirements.php', 'success', 'Specimen signature captured successfully.');

