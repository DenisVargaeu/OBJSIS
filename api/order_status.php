<?php
// api/order_status.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$table_id = $_GET['table_id'] ?? null;

if (!$table_id) {
    echo json_encode(['success' => false, 'message' => 'Missing table_id']);
    exit;
}

try {
    // Fetch active orders for this table (not paid or cancelled)
    $stmt = $pdo->prepare("
        SELECT id, status, created_at 
        FROM orders 
        WHERE table_number = (SELECT id FROM tables WHERE id = ?) 
        AND status NOT IN ('paid', 'cancelled')
        ORDER BY created_at DESC
    ");
    $stmt->execute([$table_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
