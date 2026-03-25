<?php
// addons/activity_log_pro/hooks.php

$GLOBALS['addon_system_links'][] = '<a href="../addons/activity_log_pro/index.php"><i class="fas fa-list-check" style="color: #6366f1;"></i> Activity Log</a>';

/**
 * Log a system activity
 */
function log_activity($pdo, $action, $details = null) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // Fail silently
    }
}
?>
