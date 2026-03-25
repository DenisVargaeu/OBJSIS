<?php
// api/addons.php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/addon_helper.php';

header('Content-Type: application/json');
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$manager = new OBJSIS_AddonManager($pdo);

// Ensure table exists for existing installs
$pdo->exec("CREATE TABLE IF NOT EXISTS `addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addon_id` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `version` varchar(20) DEFAULT '1.0.0',
  `description` text DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `installed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

try {
    switch ($action) {
        case 'list':
            $addons = $manager->getAvailableAddons();
            echo json_encode(['success' => true, 'addons' => $addons]);
            break;

        case 'toggle':
            $addonId = $_POST['addon_id'] ?? '';
            $enable = ($_POST['enable'] ?? '0') === '1';
            if (empty($addonId)) throw new Exception("Addon ID is required.");
            
            $success = $manager->toggleAddon($addonId, $enable);
            echo json_encode(['success' => $success, 'message' => $enable ? 'Addon enabled' : 'Addon disabled']);
            break;

        case 'install':
            // In a real scenario, this would handle a ZIP upload or URL download.
            // For now, it will look for a folder in the addons directory and "register" it.
            $addonId = $_POST['addon_id'] ?? '';
            if (empty($addonId)) throw new Exception("Addon ID is required.");
            
            $addons = $manager->getAvailableAddons();
            $target = null;
            foreach ($addons as $addon) {
                if ($addon['addon_id'] === $addonId) {
                    $target = $addon;
                    break;
                }
            }
            
            if (!$target) throw new Exception("Addon files not found in /addons/ directory.");
            
            $success = $manager->installAddon($addonId, $target);
            echo json_encode(['success' => $success, 'message' => 'Addon installed successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
