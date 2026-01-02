<?php
// api/import_actions.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'import_json') {
    if (!isset($_FILES['json_file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['json_file'];
    $data = json_decode(file_get_contents($file['tmp_name']), true);

    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format']);
        exit;
    }

    if (!is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'JSON must be an array of items']);
        exit;
    }

    $success_count = 0;
    $error_count = 0;
    $errors = [];

    try {
        $pdo->beginTransaction();

        foreach ($data as $index => $item) {
            $name = $item['name'] ?? null;
            $category_name = $item['category'] ?? 'Uncategorized';
            $price = $item['price'] ?? 0;
            $description = $item['description'] ?? '';
            $image_url = $item['image_url'] ?? '';
            $is_available = isset($item['is_available']) ? (int) $item['is_available'] : 1;
            $allergens = $item['allergens'] ?? '';

            if (!$name) {
                $errors[] = "Item at index $index is missing a name.";
                $error_count++;
                continue;
            }

            // 1. Ensure category exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category_name]);
            $category = $stmt->fetch();

            if (!$category) {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$category_name]);
                $category_id = $pdo->lastInsertId();
            } else {
                $category_id = $category['id'];
            }

            // 2. Insert menu item
            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, allergens) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $price, $image_url, $is_available, $allergens]);

            $success_count++;
        }

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => "Import complete. $success_count items added, $error_count skipped.",
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>