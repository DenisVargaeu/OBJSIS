<?php
// api/kitchen_fetch.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'cook') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch only RECEIVED or PREPARING orders
    $stmt = $pdo->query("
        SELECT o.id, o.table_number, o.status, o.created_at,
               JSON_ARRAYAGG(JSON_OBJECT('name', m.name, 'qty', oi.quantity)) as items_json
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE o.status = 'received' OR o.status = 'preparing'
        GROUP BY o.id
        ORDER BY o.created_at ASC
    ");
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
