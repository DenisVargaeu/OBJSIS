<?php
// api/inventory_logs.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'inventory') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Missing item_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as user_name 
        FROM inventory_logs l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.inventory_id = ?
        ORDER BY l.timestamp DESC
        LIMIT 50
    ");
    $stmt->execute([$item_id]);
    echo json_encode(['success' => true, 'logs' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>