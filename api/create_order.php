<?php
// api/create_order.php
require_once '../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$table_number = $data['table_number'] ?? null;
$items = $data['items'] ?? [];
$coupon_code = $data['coupon_code'] ?? null;

if (!$table_number || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ALWAYS CREATE NEW ORDER (As per "Multiple Orders" requirement)

    // Calculate initial total
    $total_price = 0;
    foreach ($items as $item) {
        $total_price += ($item['price'] * $item['quantity']);
    }

    // Apply Coupon Logic
    $discount_amount = 0;
    if ($coupon_code) {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            if ($coupon['type'] === 'fixed') {
                $discount_amount = min($total_price, $coupon['value']);
            } elseif ($coupon['type'] === 'percent') {
                $discount_amount = $total_price * ($coupon['value'] / 100);
            }
        }
    }

    // Final Charge Amount (Total to be Paid)
    $final_price = max(0, $total_price - $discount_amount);

    // Insert Order
    $stmt = $pdo->prepare("INSERT INTO orders (table_number, total_price, discount_amount, coupon_code, status) VALUES (?, ?, ?, ?, 'received')");
    $stmt->execute([$table_number, $final_price, $discount_amount, $coupon_code]);
    $order_id = $pdo->lastInsertId();

    // Insert Items
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_item->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
    }

    // Update Table Status to occupied (in case it was free)
    // Update Table Status to occupied (in case it was free)
    $stmt_table = $pdo->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
    $stmt_table->execute([$table_number]);

    // Increment coupon usage counter if coupon was used
    if ($coupon_code) {
        $stmt_coupon = $pdo->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE code = ?");
        $stmt_coupon->execute([$coupon_code]);
    }

    // Phase 6: Deduct Stock
    require_once '../includes/functions.php';
    deductStockForOrder($pdo, $order_id);

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>