<?php
// api/shift_actions.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'clock_in') {
        // Check if already clocked in
        $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND end_time IS NULL");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already clocked in']);
            exit;
        }

        $cash_start = $_POST['cash_start'] ?? 0;
        $stmt = $pdo->prepare("INSERT INTO shifts (user_id, cash_start) VALUES (?, ?)");
        $stmt->execute([$user_id, $cash_start]);

        $_SESSION['shift_id'] = $pdo->lastInsertId();
        echo json_encode(['success' => true]);

    } elseif ($action === 'clock_out') {
        // Find active shift
        $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $shift = $stmt->fetch();

        if (!$shift) {
            echo json_encode(['success' => false, 'message' => 'No active shift found']);
            exit;
        }

        $cash_end = $_POST['cash_end'] ?? 0;
        $stmt = $pdo->prepare("UPDATE shifts SET end_time = CURRENT_TIMESTAMP, cash_end = ? WHERE id = ?");
        $stmt->execute([$cash_end, $shift['id']]);

        unset($_SESSION['shift_id']);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>