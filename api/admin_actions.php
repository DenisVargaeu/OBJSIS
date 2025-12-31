<?php
// api/admin_actions.php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Ensure JSON response
header('Content-Type: application/json');

// Check Auth
// Check Auth
// session_start(); // Already started in functions.php
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // --- MENU ACTIONS ---
        case 'add_item':
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $price = $_POST['price'];
            $cat_id = $_POST['category_id'];
            $img_url = $_POST['image_url']; // For MVP, simple text URL

            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cat_id, $name, $desc, $price, $img_url]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_item':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_availability':
            $id = $_POST['id'];
            $val = $_POST['is_available']; // 0 or 1
            $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
            $stmt->execute([$val, $id]);
            echo json_encode(['success' => true]);
            break;

        // --- USER ACTIONS ---
        case 'add_user':
            $name = $_POST['name'];
            $role = $_POST['role'];
            $pin = $_POST['pin'];
            $hash = hashPin($pin);

            $stmt = $pdo->prepare("INSERT INTO users (name, role, pin_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $role, $hash]);
            echo json_encode(['success' => true]);
            break;

        case 'edit_user':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $role = $_POST['role'];
            $pin = $_POST['pin'] ?? '';

            if (!empty($pin)) {
                $hash = hashPin($pin);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, pin_hash = ? WHERE id = ?");
                $stmt->execute([$name, $role, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $role, $id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_user':
            $id = $_POST['id'];
            // Prevent deleting self (optional check)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // --- COUPON ACTIONS ---
        case 'add_coupon':
            $code = strtoupper($_POST['code']);
            $type = $_POST['type'];
            $value = $_POST['value'];
            $expiration_date = $_POST['expiration_date'] ?? null;
            $max_uses = $_POST['max_uses'] ?? null;
            $one_time_use = isset($_POST['one_time_use']) ? 1 : 0;

            // Validate expiration date if provided
            if ($expiration_date && strtotime($expiration_date) < time()) {
                echo json_encode(['success' => false, 'message' => 'Expiration date must be in the future']);
                exit;
            }

            // Convert empty max_uses to NULL
            if ($max_uses === '' || $max_uses === '0') {
                $max_uses = null;
            }

            $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, expiration_date, max_uses, one_time_use) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $expiration_date, $max_uses, $one_time_use]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_coupon':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // --- TABLE ACTIONS ---
        case 'add_table':
            $name = $_POST['name'];
            $capacity = $_POST['capacity'];

            $stmt = $pdo->prepare("INSERT INTO tables (name, capacity) VALUES (?, ?)");
            $stmt->execute([$name, $capacity]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_table':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
