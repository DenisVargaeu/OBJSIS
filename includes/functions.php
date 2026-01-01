<?php
// includes/functions.php

session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

// Simple flash message helper
function setFlashMessage($message, $type = 'success')
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Hash PIN for storage
 */
function hashPin($pin)
{
    return password_hash($pin, PASSWORD_DEFAULT);
}

/**
 * Verify PIN
 */
function verifyPin($pin, $hash)
{
    return password_verify($pin, $hash);
}

/**
 * Get Global Setting
 */
function getSetting($key, $default = '')
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $res = $stmt->fetchColumn();
        return $res !== false ? $res : $default;
    } catch (Exception $e) {
        return $default;
    }
}
/**
 * Update Menu Availability based on Stock
 */
function updateMenuAvailability($pdo)
{
    // Fetch all menu items that have ingredients defined
    $stmt = $pdo->query("SELECT DISTINCT menu_item_id FROM menu_item_ingredients");
    $item_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($item_ids as $item_id) {
        $stmt = $pdo->prepare("
            SELECT r.quantity_required, i.current_quantity 
            FROM menu_item_ingredients r
            JOIN inventory i ON r.inventory_id = i.id
            WHERE r.menu_item_id = ?
        ");
        $stmt->execute([$item_id]);
        $ingredients = $stmt->fetchAll();

        $can_make = true;
        foreach ($ingredients as $ing) {
            if ($ing['current_quantity'] < $ing['quantity_required']) {
                $can_make = false;
                break;
            }
        }

        $stmt_update = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $stmt_update->execute([$can_make ? 1 : 0, $item_id]);
    }
}

/**
 * Deduct Stock for a given Order
 */
function deductStockForOrder($pdo, $order_id)
{
    // Fetch all items in the order
    $stmt = $pdo->prepare("SELECT menu_item_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        // Find ingredients for this menu item
        $stmt_ing = $pdo->prepare("SELECT inventory_id, quantity_required FROM menu_item_ingredients WHERE menu_item_id = ?");
        $stmt_ing->execute([$item['menu_item_id']]);
        $ingredients = $stmt_ing->fetchAll();

        foreach ($ingredients as $ing) {
            $total_deduction = $ing['quantity_required'] * $item['quantity'];

            // Deduct from inventory
            $stmt_deduct = $pdo->prepare("UPDATE inventory SET current_quantity = current_quantity - ? WHERE id = ?");
            $stmt_deduct->execute([$total_deduction, $ing['inventory_id']]);

            // Log the sale
            $stmt_log = $pdo->prepare("INSERT INTO inventory_logs (inventory_id, change_type, quantity_change) VALUES (?, 'sale', ?)");
            $stmt_log->execute([$ing['inventory_id'], -$total_deduction]);
        }
    }

    // After all deductions, update availability
    updateMenuAvailability($pdo);
}
?>