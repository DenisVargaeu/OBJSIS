<?php
// addons/analytics_pro/api.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

try {
    // 1. Sales by Day (Last 7 days)
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(total_price) as total 
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $sales_by_day = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Sales by Category
    $stmt = $pdo->query("
        SELECT c.name as category, SUM(oi.price * oi.quantity) as total
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN categories c ON m.category_id = c.id
        GROUP BY c.id
        ORDER BY total DESC
    ");
    $sales_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'sales_by_day' => $sales_by_day,
        'sales_by_category' => $sales_by_category
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
