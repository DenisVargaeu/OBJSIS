<?php
// api/recipe_actions.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

// Only admin or inventory role
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'inventory') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_recipe':
            $item_id = $_GET['menu_item_id'];
            $stmt = $pdo->prepare("
                SELECT r.*, i.name as ingredient_name, i.unit 
                FROM menu_item_ingredients r
                JOIN inventory i ON r.inventory_id = i.id
                WHERE r.menu_item_id = ?
            ");
            $stmt->execute([$item_id]);
            echo json_encode(['success' => true, 'ingredients' => $stmt->fetchAll()]);
            break;

        case 'add_ingredient':
            $menu_item_id = $_POST['menu_item_id'];
            $inventory_id = $_POST['inventory_id'];
            $qty = $_POST['quantity'];

            // Check if already exists
            $check = $pdo->prepare("SELECT id FROM menu_item_ingredients WHERE menu_item_id = ? AND inventory_id = ?");
            $check->execute([$menu_item_id, $inventory_id]);
            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE menu_item_ingredients SET quantity_required = ? WHERE menu_item_id = ? AND inventory_id = ?");
                $stmt->execute([$qty, $menu_item_id, $inventory_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO menu_item_ingredients (menu_item_id, inventory_id, quantity_required) VALUES (?, ?, ?)");
                $stmt->execute([$menu_item_id, $inventory_id, $qty]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'remove_ingredient':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM menu_item_ingredients WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>