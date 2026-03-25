<?php
// addons/global_search/api.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 2) {
    try {
        $searchTerm = "%$query%";

        // 1. Search Orders
        $stmt = $pdo->prepare("SELECT id, table_number, status FROM orders WHERE CAST(id as CHAR) LIKE ? OR table_number LIKE ? LIMIT 5");
        $stmt->execute([$searchTerm, $searchTerm]);
        while ($r = $stmt->fetch()) {
            $results[] = [
                'icon' => 'fa-receipt',
                'title' => "Order #{$r['id']} (Table {$r['table_number']})",
                'subtitle' => "Status: " . strtoupper($r['status']),
                'url' => 'orders.php' // Link ideally would be specific, but this works
            ];
        }

        // 2. Search Menu Items
        $stmt = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE name LIKE ? LIMIT 5");
        $stmt->execute([$searchTerm]);
        while ($r = $stmt->fetch()) {
            $results[] = [
                'icon' => 'fa-utensils',
                'title' => $r['name'],
                'subtitle' => "Menu Item - " . number_format($r['price'], 2) . "€",
                'url' => 'menu.php'
            ];
        }

        // 3. Search Staff
        $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE name LIKE ? LIMIT 5");
        $stmt->execute([$searchTerm]);
        while ($r = $stmt->fetch()) {
            $results[] = [
                'icon' => 'fa-user',
                'title' => $r['name'],
                'subtitle' => "Staff - " . strtoupper($r['role']),
                'url' => 'users.php'
            ];
        }
        
    } catch (Exception $e) {}
}

echo json_encode(['success' => true, 'results' => $results]);
?>
