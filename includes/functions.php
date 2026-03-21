<?php
// includes/functions.php
require_once dirname(__DIR__) . '/config/version.php';

session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Check if user has permission for a specific page
 * @param string $page e.g., 'users.php'
 * @return bool
 */
function hasPermission($page)
{
    // Admin role usually has all access, but better to rely on permissions array.
    // However, if we want a hard override:
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }

    if (!isset($_SESSION['permissions'])) {
        return false;
    }

    // Normalize page name (remove path)
    $page = basename($page);

    // If permissions is an array, check it
    if (is_array($_SESSION['permissions']) && in_array($page, $_SESSION['permissions'])) {
        return true;
    }

    return false;
}

/**
 * Enforce permission on a page
 */
function checkPermission($page)
{
    if (!hasPermission($page)) {
        // If it's an API call, return JSON
        if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Otherwise redirect to dashboard or error
        setFlashMessage("Access Denied: You do not have permission to view this page.", "error");
        header("Location: dashboard.php");
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

/**
 * Get Dynamic Custom Styles
 */
function getCustomStyles()
{
    $primary = getSetting('primary_color', '#f97316');
    $primary_hover = getSetting('primary_hover', '#ea580c');

    // Kiosk Background
    $bg_type_kiosk = getSetting('bg_type_kiosk', 'image');
    $bg_image_kiosk = getSetting('bg_image_kiosk', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80');
    $bg_color_kiosk = getSetting('bg_color_kiosk', '#0f172a');

    // Login Background
    $bg_type_login = getSetting('bg_type_login', 'gradient');
    $bg_image_login = getSetting('bg_image_login', '');
    $bg_color_login = getSetting('bg_color_login', '#0f172a');

    $styles = "
    <style>
        :root {
            --primary-color: $primary !important;
            --primary-hover: $primary_hover !important;
        }
    ";

    // Kiosk Hero Styling
    if ($bg_type_kiosk === 'color') {
        $styles .= "
        .kiosk-hero {
            background: $bg_color_kiosk !important;
        }
        ";
    } else {
        $styles .= "
        .kiosk-hero {
            background: linear-gradient(rgba(15, 23, 42, 0.7), var(--bg-color)), url('$bg_image_kiosk') !important;
            background-size: cover !important;
            background-position: center !important;
        }
        ";
    }

    // Login Wrapper Styling
    if ($bg_type_login === 'color') {
        $styles .= "
        .login-wrapper {
            background: $bg_color_login !important;
        }
        ";
    } elseif ($bg_type_login === 'image' && !empty($bg_image_login)) {
        $styles .= "
        .login-wrapper {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('$bg_image_login') !important;
            background-size: cover !important;
            background-position: center !important;
        }
        ";
    }

    $styles .= "</style>";
    return $styles;
}
?>