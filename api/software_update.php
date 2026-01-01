<?php
// api/software_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/updater_helper.php';

// Silence errors to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
requireLogin();

// Check for required extensions
if (!extension_loaded('curl')) {
    echo json_encode([
        'success' => false,
        'message' => 'PHP cURL extension is not enabled on your server. Please enable it
in php.ini'
    ]);
    exit;
}

if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'message' => 'PHP ZipArchive extension is not enabled on your server.']);
    exit;
}

// Only admin can perform updates
if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$updater = new OBJSIS_Updater($pdo);

try {
    switch ($action) {
        case 'check_update':
            $result = $updater->checkUpdate();
            echo json_encode($result);
            break;

        case 'start_update':
            // Verification check again
            $updateInfo = $updater->checkUpdate();
            if (!$updateInfo['success'] || !$updateInfo['has_update']) {
                echo json_encode(['success' => false, 'message' => 'No update available or check failed.']);
                exit;
            }

            $result = $updater->startUpdate($updateInfo);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>