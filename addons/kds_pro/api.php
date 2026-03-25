<?php
// addons/kds_pro/api.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

// Basic security
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'cook') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch only RECEIVED or PREPARING orders with item details
    // We also fetch 'updated_at' to help with frontend sorting/refreshing
    $stmt = $pdo->query("
        SELECT o.id, o.table_number, o.status, o.created_at, o.updated_at, o.note,
               JSON_ARRAYAGG(JSON_OBJECT('name', m.name, 'qty', oi.quantity)) as items_json
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE o.status IN ('received', 'preparing')
        GROUP BY o.id
        ORDER BY o.created_at ASC
    ");
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'orders' => $orders,
        'server_time' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
