<?php
/**
 * Application Wizard Step Processor (POST Only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/applicant_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applicant/dashboard.php', 'error', 'Invalid request method.');
}

requireCsrfToken();

if (!isset($_SESSION['application_draft'])) {
    redirect('applicant/application/create.php', 'warning', 'Please start a new application.');
}

$step = $_GET['step'] ?? '';
$draft = &$_SESSION['application_draft'];

switch ($step) {
    case 'personal':
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $courseYearSection = trim($_POST['course_year_section'] ?? '');

        if (empty($contactNumber) || empty($department)) {
            redirect('applicant/application/personal_details.php', 'error', 'Contact number and department are required.');
        }

        $draft['personal']['contact_number'] = $contactNumber;
        $draft['personal']['department'] = $department;
        $draft['personal']['course_year_section'] = $courseYearSection;

        redirect('applicant/application/vehicle_details.php', 'success', 'Personal details confirmed.');
        break;

    case 'vehicle':
        $vehicleType = trim($_POST['vehicle_type'] ?? '');
        $yearModel = (int)($_POST['year_model'] ?? 0);
        $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
        $color = trim($_POST['color'] ?? '');
        $licenseNumber = strtoupper(trim($_POST['license_number'] ?? ''));

        if (empty($vehicleType) || empty($yearModel) || empty($plateNumber) || empty($color) || empty($licenseNumber)) {
            redirect('applicant/application/vehicle_details.php', 'error', 'All vehicle information fields are required.');
        }

        $draft['vehicle'] = [
            'vehicle_type'   => $vehicleType,
            'year_model'     => $yearModel,
            'plate_number'   => $plateNumber,
            'color'          => $color,
            'license_number' => $licenseNumber
        ];

        redirect('applicant/application/ownership_type.php', 'success', 'Vehicle details saved.');
        break;

    case 'ownership':
        $category = trim($_POST['ownership_category'] ?? '');
        $allowed = [
            'first_owner_own_name',
            'first_owner_relative_name',
            'secondhand_applicant_buyer',
            'secondhand_applicant_not_buyer'
        ];

        if (!in_array($category, $allowed, true)) {
            redirect('applicant/application/ownership_type.php', 'error', 'Please select a valid ownership category.');
        }

        $ownerName = trim($_POST['owner_name'] ?? '');
        $relationship = trim($_POST['relationship_to_applicant'] ?? '');
        $previousOwner = trim($_POST['previous_owner_name'] ?? '');
        $buyerName = trim($_POST['buyer_name'] ?? '');

        // Validation per category
        if ($category === 'first_owner_relative_name') {
            if (empty($ownerName) || empty($relationship)) {
                redirect('applicant/application/ownership_type.php', 'error', 'Please provide the registered relative\'s owner name and relationship.');
            }
        } elseif ($category === 'secondhand_applicant_buyer') {
            if (empty($previousOwner)) {
                redirect('applicant/application/ownership_type.php', 'error', 'Please provide the previous registered owner\'s name.');
            }
        } elseif ($category === 'secondhand_applicant_not_buyer') {
            if (empty($previousOwner) || empty($buyerName) || empty($relationship)) {
                redirect('applicant/application/ownership_type.php', 'error', 'Please provide the previous owner, buyer, and relationship details.');
            }
        }

        // If category changed, reset documents to prevent mixing requirements
        if (isset($draft['ownership']['ownership_category']) && $draft['ownership']['ownership_category'] !== $category) {
            $draft['documents'] = [];
        }

        $draft['ownership'] = [
            'ownership_category'        => $category,
            'owner_name'                => $ownerName ?: null,
            'relationship_to_applicant' => $relationship ?: null,
            'previous_owner_name'       => $previousOwner ?: null,
            'buyer_name'                => $buyerName ?: null
        ];

        redirect('applicant/application/signature.php', 'success', 'Ownership category set.');
        break;

    default:
        redirect('applicant/application/personal_details.php', 'error', 'Unknown application step.');
        break;
}

