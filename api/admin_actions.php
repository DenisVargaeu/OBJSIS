<?php
// api/admin_actions.php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Ensure JSON response
header('Content-Type: application/json');

// Check Auth - Granular checks inside switch
// checkPermission('manage_system'); // Removed global block

$action = $_POST['action'] ?? '';

// SECURITY FIX: CSRF Validation
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
    exit;
}

try {
    switch ($action) {
        // --- MENU ACTIONS ---
        case 'add_item':
            checkPermission('manage_menu');
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $price = $_POST['price'];
            $cat_id = $_POST['category_id'];
            $img_url = $_POST['image_url'];
            $allergens = $_POST['allergens'] ?? '';

            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, allergens) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cat_id, $name, $desc, $price, $img_url, $allergens]);
            echo json_encode(['success' => true]);
            break;

        case 'edit_item':
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $price = $_POST['price'];
            $cat_id = $_POST['category_id'];
            $img_url = $_POST['image_url'];
            $allergens = $_POST['allergens'] ?? '';

            $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, allergens = ? WHERE id = ?");
            $stmt->execute([$cat_id, $name, $desc, $price, $img_url, $allergens, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_item':
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_availability':
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $val = $_POST['is_available']; // 0 or 1
            $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
            $stmt->execute([$val, $id]);
            echo json_encode(['success' => true]);
            break;

        // --- USER ACTIONS ---
        case 'add_user':
            checkPermission('manage_users');
            $name = $_POST['name'];
            $role_id = $_POST['role_id'];
            $pin = $_POST['pin'];
            $hash = hashPin($pin);

            $stmt = $pdo->prepare("INSERT INTO users (name, role_id, pin_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $role_id, $hash]);
            echo json_encode(['success' => true]);
            break;

        case 'edit_user':
            checkPermission('manage_users');
            $id = $_POST['id'];
            $name = $_POST['name'];
            $role_id = $_POST['role_id'];
            $pin = $_POST['pin'] ?? '';

            if (!empty($pin)) {
                $hash = hashPin($pin);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role_id = ?, pin_hash = ? WHERE id = ?");
                $stmt->execute([$name, $role_id, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, role_id = ? WHERE id = ?");
                $stmt->execute([$name, $role_id, $id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_user':
            checkPermission('manage_users');
            $id = $_POST['id'];
            // Prevent deleting self (optional check)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // --- ROLE ACTIONS ---
case 'add_role':
  checkPermission('manage_roles');
  $rname = strtolower(trim($_POST['role_name']));
  $dname = trim($_POST['display_name']);
  if (empty($dname)) $dname = ucfirst($rname);
  $exists = $pdo->query("SELECT id FROM roles WHERE name = " . $pdo->quote($rname))->fetchColumn();
  if ($exists) {
    echo json_encode(['success' => false, 'message' => 'Role name "' . $rname . '" already exists']);
    break;
  }
  $stmt = $pdo->prepare("INSERT INTO roles (name, display_name) VALUES (?, ?)");
  $stmt->execute([$rname, $dname]);
  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  break;

case 'edit_role':
  checkPermission('manage_roles');
  $id = $_POST['id'];
  $dname = trim($_POST['display_name']);
  $stmt = $pdo->prepare("UPDATE roles SET display_name = ? WHERE id = ?");
  $stmt->execute([$dname, $id]);
  echo json_encode(['success' => true]);
  break;

case 'delete_role':
  checkPermission('manage_roles');
  $id = $_POST['id'];
  $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
  $pdo->prepare("UPDATE users SET role_id = NULL WHERE role_id = ?")->execute([$id]);
  $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
  echo json_encode(['success' => true]);
  break;

case 'update_role_permissions':
  checkPermission('manage_roles');
  $role_id = $_POST['role_id'];
  $perm_ids = $_POST['permission_ids'] ?? [];
  if (!is_array($perm_ids)) $perm_ids = [];
  $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
  $stmt_ins = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
  foreach ($perm_ids as $pid) {
    $stmt_ins->execute([$role_id, $pid]);
  }
  echo json_encode(['success' => true]);
  break;

// --- COUPON ACTIONS ---
        case 'add_coupon':
            checkPermission('manage_menu');
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
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // --- TABLE ACTIONS ---
        case 'add_table':
            checkPermission('manage_orders');
            $name = $_POST['name'];
            $capacity = $_POST['capacity'];

            $stmt = $pdo->prepare("INSERT INTO tables (name, capacity) VALUES (?, ?)");
            $stmt->execute([$name, $capacity]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_table':
            checkPermission('manage_orders');
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // --- CATEGORY ACTIONS ---
        case 'add_category':
            checkPermission('manage_menu');
            $name = $_POST['name'];
            $sort_order = $_POST['sort_order'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $sort_order]);
            echo json_encode(['success' => true]);
            break;

        case 'edit_category':
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $name = $_POST['name'];
            $sort_order = $_POST['sort_order'] ?? 0;
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$name, $sort_order, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_category':
            checkPermission('manage_menu');
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
