<?php
// addons/maintenance_mode/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

checkPermission('manage_system');

$message = '';
$success = false;

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $maintenanceMessage = trim($_POST['message'] ?? '');
        $allowedIps = trim($_POST['allowed_ips'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE maintenance_settings 
            SET is_enabled = ?, message = ?, allowed_ips = ?
        ");
        $stmt->execute([$isEnabled, $maintenanceMessage, $allowedIps]);
        $message = 'Maintenance settings updated successfully!';
        $success = true;
    }
    
    // Fetch current settings
    $stmt = $pdo->query("SELECT * FROM maintenance_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $success = false;
}

$page_title = "Maintenance Mode";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .maintenance-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 25px 30px;
            margin-bottom: 25px;
        }
        .toggle-info h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .toggle-info p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            flex-shrink: 0;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            transition: 0.3s;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }
        input:checked + .slider {
            background: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .warning-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .status-active {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .status-inactive {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="app-container" style="display: block;">
        <main class="main-content" style="margin: 0; padding: 40px; max-width: 800px; margin: 0 auto;">
            <header class="page-header" style="border:none; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title-group">
                    <h2 style="font-size: 2.5rem; font-weight: 900;">
                        <i class="fas fa-wrench" style="margin-right: 15px; color: #f59e0b;"></i> <?= $page_title ?>
                    </h2>
                    <p style="color:var(--text-muted); margin:0;">Control system access and downtime</p>
                </div>
                <a href="../../admin/addons.php" class="btn btn-secondary" style="border-radius: 30px; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </header>

            <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>" style="margin-bottom: 25px;">
                <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="glass-card toggle-card">
                <div class="toggle-info">
                    <h3>Enable Maintenance Mode</h3>
                    <p>When enabled, only admins can access the system</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="status-indicator <?= $settings['is_enabled'] ? 'status-active' : 'status-inactive' ?>">
                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                        <?= $settings['is_enabled'] ? 'ACTIVE' : 'INACTIVE' ?>
                    </span>
                    <label class="switch">
                        <input type="checkbox" id="maintenanceToggle" <?= $settings['is_enabled'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <?php if ($settings['is_enabled']): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-right: 10px;"></i>
                <strong>Maintenance mode is currently ACTIVE.</strong> Non-admin users are blocked from accessing the system.
            </div>
            <?php else: ?>
            <div class="warning-box" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
                <i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i>
                <strong>Maintenance mode is currently INACTIVE.</strong> All users can access the system normally.
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="glass-card" style="padding: 30px;">
                    <div class="form-group">
                        <label for="message">Maintenance Message</label>
                        <textarea id="message" name="message" class="form-control" rows="4" placeholder="Enter the message shown to users during maintenance..."><?= htmlspecialchars($settings['message'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="allowed_ips">Allowed IP Addresses (Optional)</label>
                        <input type="text" id="allowed_ips" name="allowed_ips" class="form-control" 
                               value="<?= htmlspecialchars($settings['allowed_ips'] ?? '') ?>" 
                               placeholder="e.g. 192.168.1.100, 10.0.0.50 (comma separated)">
                        <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                            These IPs will still have access even if maintenance mode is enabled
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary" style="border-radius: 30px; padding: 12px 30px; width: 100%;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="../../assets/js/app.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script>
        const toggle = document.getElementById('maintenanceToggle');
        const form = document.querySelector('form');
        
        if (toggle) {
            toggle.addEventListener('change', function() {
                const messageField = document.getElementById('message');
                if (this.checked) {
                    if (!messageField.value.trim()) {
                        messageField.value = 'We are currently performing scheduled maintenance. Please try again later.';
                    }
                }
                form.submit();
            });
        }
    </script>
</body>
</html>