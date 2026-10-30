<?php
/**
 * Global Header Template
 * Expects optional variables: $pageTitle, $customCss
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME . ' | ' . APP_FULL_NAME;
$role = $_SESSION['account_role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
    <!-- Base CSS Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    
    <?php if ($role === 'admin'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <?php elseif ($role === 'applicant'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/application.css">
    <?php endif; ?>
    
    <?php if (isset($customCss) && is_array($customCss)): ?>
        <?php foreach ($customCss as $cssFile): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= e($cssFile) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $role ? 'app-layout role-' . e($role) : 'public-layout' ?>">
    <?= renderFlashMessages() ?>
    
    <?php if ($role): ?>
        <div class="app-wrapper">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <div class="app-main-content">
                <?php include __DIR__ . '/navbar.php'; ?>
                <main class="content-body" id="mainContent">
    <?php endif; ?>

