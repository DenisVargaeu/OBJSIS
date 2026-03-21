<?php
// api/verify_coupon.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$code = $_POST['code'] ?? '';
$cart_total = floatval($_POST['cart_total'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Enter a code']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Invalid or inactive coupon']);
    exit;
}

// Check if expired
if ($coupon['expiration_date'] && strtotime($coupon['expiration_date']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This coupon has expired']);
    exit;
}

// Check if max uses reached
if ($coupon['max_uses'] !== null && $coupon['current_uses'] >= $coupon['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit']);
    exit;
}

// Calculate discount
$discount = 0;
if ($coupon['type'] === 'fixed') {
    $discount = floatval($coupon['value']);
} elseif ($coupon['type'] === 'percent') {
    $discount = $cart_total * (floatval($coupon['value']) / 100);
}

// Ensure discount doesn't exceed total
if ($discount > $cart_total) {
    $discount = $cart_total;
}

echo json_encode([
    'success' => true,
    'code' => $coupon['code'],
    'type' => $coupon['type'],
    'value' => $coupon['value'],
    'discount_amount' => $discount,
    'new_total' => $cart_total - $discount,
    'expiration_date' => $coupon['expiration_date'],
    'remaining_uses' => $coupon['max_uses'] ? ($coupon['max_uses'] - $coupon['current_uses']) : null
]);
?>