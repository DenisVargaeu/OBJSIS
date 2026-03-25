<?php
// addons/api_manager/external_api.php
ob_start(); // Start output buffering
require_once '../../config/db.php';
require_once '../../includes/functions.php';
ob_clean(); // Clear any whitespace/content from included files

header('Content-Type: application/json');
error_reporting(0); 

$key = $_GET['key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');

// 1. IP Whitelist Check

// 2. Multi-Key Validation
try {
    $stmt_key = $pdo->prepare("SELECT id FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt_key->execute([$key]);
    if (!$stmt_key->fetch()) {
        // Fallback for transition: Allow legacy OBJSIS_ key if no keys exist in DB yet
        $db_keys_count = $pdo->query("SELECT COUNT(*) FROM api_keys")->fetchColumn();
        if ($db_keys_count > 0 || strpos($key, 'OBJSIS_') !== 0) {
            http_response_code(401);
            die(json_encode(['success' => false, 'message' => 'Invalid or inactive API Key']));
        }
    }
} catch (Exception $e) {}

$action = $_GET['action'] ?? 'stats';

// 3. Log the request
try {
    $stmt_log = $pdo->prepare("INSERT INTO api_logs (api_key, endpoint, user_ip, status_code) VALUES (?, ?, ?, ?)");
    $stmt_log->execute([$key ?: 'NONE', $action, $_SERVER['REMOTE_ADDR'], 200]);
} catch (Exception $e) {}

try {
    switch ($action) {
        case 'stats':
            $stmt = $pdo->query("SELECT (SELECT SUM(total_price) FROM orders WHERE status = 'paid' AND DATE(created_at) = CURDATE()) as revenue, (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as orders");
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'active_orders':
            $stmt = $pdo->query("SELECT id, table_number, status, total_price FROM orders WHERE status NOT IN ('paid', 'cancelled') ORDER BY created_at DESC");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
