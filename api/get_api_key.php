<?php
// api/get_api_key.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'get';

try {
    if ($action === 'regen') {
        // SECURITY FIX: Generate key server-side
        $new_key = 'OBJSIS_' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('master_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$new_key, $new_key]);
        echo json_encode(['success' => true, 'api_key' => $new_key]);
    } else {
        // SECURITY FIX: Fetch key from DB
        $api_key = getSetting('master_api_key');
        if (!$api_key) {
            // Generate initial if missing
            $api_key = 'OBJSIS_' . bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('master_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$api_key, $api_key]);
        }
        echo json_encode(['success' => true, 'api_key' => $api_key]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
