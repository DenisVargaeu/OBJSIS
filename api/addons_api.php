<?php
// api/addons_api.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'notifications':
            // 1. New Orders
            $stmt_orders = $pdo->query("SELECT id, table_number, created_at FROM orders WHERE status = 'received' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 5");
            $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

            // 2. Low Stock Alerts
            $stmt_stock = $pdo->query("SELECT name, current_quantity, unit FROM inventory WHERE current_quantity <= critical_threshold AND critical_threshold > 0 LIMIT 5");
            $low_stock = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'low_stock' => $low_stock,
                'timestamp' => time()
            ]);
            break;

        case 'terminal':
            if ($_SESSION['user_role'] !== 'admin') throw new Exception("Unauthorized");
            
            $cmd = $_GET['cmd'] ?? '';
            $output = "";

            if ($cmd === 'stats') {
                $revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
                $orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
                $output = "REVENUE: " . number_format($revenue, 2) . " EUR\nORDERS: " . $orders . "\nDB: ONLINE";
            } elseif ($cmd === 'ls') {
                $dir_arg = $_GET['arg'] ?? '.';
                // SECURITY FIX: Prevent path traversal using realpath() and whitelisting
                $base_dir = realpath(__DIR__ . "/..");
                $requested_dir = realpath($base_dir . "/" . $dir_arg);
                
                $allowed_dirs = [
                    $base_dir . "/assets",
                    $base_dir . "/docs",
                    $base_dir . "/sql"
                ];
                
                $is_allowed = false;
                if ($requested_dir !== false) {
                    foreach ($allowed_dirs as $allowed) {
                        if (strpos($requested_dir, $allowed) === 0) {
                            $is_allowed = true;
                            break;
                        }
                    }
                }

                if (!$is_allowed) throw new Exception("Access Denied: Directory not allowed");
                
                $files = scandir($requested_dir);
                $output = implode("\n", array_diff($files, ['.', '..']));
            } elseif ($cmd === 'cat') {
                $file_arg = $_GET['arg'] ?? '';
                // SECURITY FIX: Prevent path traversal using realpath() and whitelisting
                $base_dir = realpath(__DIR__ . "/..");
                $requested_file = realpath($base_dir . "/" . $file_arg);
                
                $allowed_dirs = [
                    $base_dir . "/assets",
                    $base_dir . "/docs",
                    $base_dir . "/sql"
                ];
                
                $is_allowed = false;
                if ($requested_file !== false && is_file($requested_file)) {
                    foreach ($allowed_dirs as $allowed) {
                        if (strpos($requested_file, $allowed) === 0) {
                            $is_allowed = true;
                            break;
                        }
                    }
                }

                if (!$is_allowed) throw new Exception("Access Denied: File not allowed or not found");
                
                $output = file_get_contents($requested_file);
            } elseif ($cmd === 'sysinfo') {
                $output = "OS: " . php_uname() . "\nPHP: " . phpversion() . "\nSERVER: " . $_SERVER['SERVER_SOFTWARE'];
            } elseif ($cmd === 'uptime') {
                $output = shell_exec('uptime') ?: "System uptime info unavailable.";
            } elseif ($cmd === 'disk') {
                $output = shell_exec('df -h /') ?: "Disk info unavailable.";
            } elseif ($cmd === 'mem') {
                $output = shell_exec('free -m') ?: "Memory info unavailable.";
            } elseif ($cmd === 'whoami') {
                $output = "USER: " . $_SESSION['user_name'] . "\nROLE: " . $_SESSION['user_role'] . "\nIP: " . $_SERVER['REMOTE_ADDR'];
            } else {
                $output = "Unknown system command.";
            }

            echo json_encode(['success' => true, 'output' => $output]);
            break;

        case 'currency':
            // Optional: Fetch real rate or return a better mock
            $codes = ['USD' => 1.08, 'GBP' => 0.85, 'HUF' => 395.0];
            echo json_encode(['success' => true, 'rates' => $codes]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
