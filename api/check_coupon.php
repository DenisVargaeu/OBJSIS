<?php
// api/check_coupon.php
require_once '../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'No code provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        // Return coupon details (value, type) so frontend can calculate preview
        echo json_encode([
            'success' => true,
            'coupon' => [
                'code' => $coupon['code'],
                'type' => $coupon['type'],
                'value' => floatval($coupon['value'])
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error']);
}
?>