<?php
// api/order_status_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if (!$order_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        if ($new_status === 'paid' || $new_status === 'cancelled') {
            $stmt_get = $pdo->prepare("SELECT table_number FROM orders WHERE id = ?");
            $stmt_get->execute([$order_id]);
            $tbl = $stmt_get->fetch();
            if ($tbl) {
                // The table_number in orders refers to the id in tables
                $stmt_tbl = $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?");
                $stmt_tbl->execute([$tbl['table_number']]);
            }
        }

        echo json_encode(['success' => true, 'message' => "Order #$order_id updated to $new_status"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
