<?php
// addons/maintenance_mode/hooks.php

$GLOBALS['addon_system_links'][] = '<a href="../addons/maintenance_mode/index.php"><i class="fas fa-wrench" style="color: #f59e0b;"></i> Maintenance Mode</a>';

/**
 * Check maintenance mode on every page load
 * This hook runs early to block non-admin users
 */
function check_maintenance_mode($pdo) {
    // Only check if user is not admin
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return;
    }
    
    try {
        $stmt = $pdo->query("SELECT is_enabled, message, allowed_ips FROM maintenance_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings || !$settings['is_enabled']) {
            return;
        }
        
        // Check if IP is whitelisted
        $allowedIps = $settings['allowed_ips'] ? explode(',', $settings['allowed_ips']) : [];
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!empty($allowedIps) && in_array($userIp, array_map('trim', $allowedIps))) {
            return;
        }
        
        // Show maintenance page for non-admin users
        $message = htmlspecialchars($settings['message'] ?? 'System is under maintenance.');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Under Maintenance - OBJSIS</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                }
                .maintenance-card {
                    background: rgba(255, 255, 255, 0.05);
                    backdrop-filter: blur(10px);
                    border-radius: 20px;
                    padding: 60px 40px;
                    text-align: center;
                    max-width: 600px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                }
                .maintenance-icon {
                    font-size: 5rem;
                    color: #f59e0b;
                    margin-bottom: 30px;
                    animation: pulse 2s ease-in-out infinite;
                }
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.7; transform: scale(1.05); }
                }
                .maintenance-title {
                    font-size: 2.5rem;
                    font-weight: 800;
                    color: #fff;
                    margin-bottom: 20px;
                }
                .maintenance-message {
                    font-size: 1.1rem;
                    color: rgba(255, 255, 255, 0.7);
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .maintenance-footer {
                    color: rgba(255, 255, 255, 0.4);
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-card">
                <div class="maintenance-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <h1 class="maintenance-title">Under Maintenance</h1>
                <p class="maintenance-message"><?= $message ?></p>
                <div class="maintenance-footer">
                    &copy; <?= date('Y') ?> OBJSIS - Please check back soon
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } catch (Exception $e) {
        // Fail silently if database is not ready
    }
}
?>
