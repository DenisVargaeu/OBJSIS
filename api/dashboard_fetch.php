<?php
// api/dashboard_fetch.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

try {
    // 1. Basic Stats
    $today_revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $active_orders_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('paid', 'cancelled')")->fetchColumn() ?: 0;

    // 2. Active Orders List (Detailed)
    $user_role = $_SESSION['user_role'];
    $role_clause = "";
    if ($user_role === 'cook') {
        $role_clause = "AND (o.status = 'received' OR o.status = 'preparing')";
    } elseif ($user_role === 'waiter') {
        $role_clause = "AND (o.status = 'ready' OR o.status = 'delivered')";
    }

    $stmt_orders = $pdo->prepare("
        SELECT o.id, o.table_number, o.status, o.total_price, o.created_at,
               JSON_ARRAYAGG(JSON_OBJECT('name', m.name, 'qty', oi.quantity)) as items_json
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id 
        WHERE o.status != 'paid' AND o.status != 'cancelled' $role_clause
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt_orders->execute();
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    // 3. Hourly Revenue (for Chart)
    $stmt_chart = $pdo->query("
        SELECT HOUR(created_at) as hr, SUM(total_price) as rev 
        FROM orders 
        WHERE DATE(created_at) = CURDATE() AND status = 'paid'
        GROUP BY HOUR(created_at)
        ORDER BY hr ASC
    ");
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top 5 Items
    $stmt_top = $pdo->query("
        SELECT m.name, SUM(oi.quantity) as qty
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE() AND o.status IN ('delivered', 'paid')
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 5
    ");
    $top_items = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'revenue' => (float)$today_revenue,
            'orders' => (int)$today_orders,
            'active' => (int)$active_orders_count
        ],
        'orders' => $orders,
        'chart' => $chart_data,
        'top_items' => $top_items
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
