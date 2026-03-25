<?php
// addons/system_health/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

checkPermission('manage_system');

$page_title = "System Health Monitor";

// Helper functions for health stats
function get_db_size($pdo) {
    try {
        $stmt = $pdo->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()");
        return round($stmt->fetchColumn(), 2);
    } catch (Exception $e) { return 0; }
}

function get_uptime() {
    if (PHP_OS_FAMILY === 'Linux') {
        $uptime = shell_exec('uptime -p');
        return $uptime ? trim($uptime) : 'Unknown';
    }
    return 'Not supported on this OS';
}

$db_size = get_db_size($pdo);
$php_version = PHP_VERSION;
$server_os = PHP_OS_FAMILY;
$uptime = get_uptime();
$memory_limit = ini_get('memory_limit');
$upload_max = ini_get('upload_max_filesize');
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
        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            padding: 25px;
            border-radius: 20px;
            text-align: center;
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 900;
            margin: 10px 0;
        }
        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="app-container" style="display: block;">
        <main class="main-content" style="margin: 0; padding: 40px; max-width: 1200px; margin: 0 auto;">
            <header class="page-header" style="border:none; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title-group">
                    <h2 style="font-size: 2.5rem; font-weight: 900;"><i class="fas fa-heart-pulse" style="margin-right: 15px; color: #f43f5e;"></i> <?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Real-time server vitals and performance</p>
                </div>
                <a href="../../admin/addons.php" class="btn btn-secondary" style="border-radius: 30px; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Back to Addons
                </a>
            </header>

            <div class="health-grid">
                <div class="glass-card stat-card">
                    <i class="fas fa-database stat-icon" style="color: #3b82f6;"></i>
                    <div class="stat-value"><?= $db_size ?> MB</div>
                    <div class="stat-label">Database Size</div>
                </div>

                <div class="glass-card stat-card">
                    <i class="fas fa-microchip stat-icon" style="color: #a855f7;"></i>
                    <div class="stat-value"><?= $php_version ?></div>
                    <div class="stat-label">PHP Version</div>
                </div>

                <div class="glass-card stat-card">
                    <i class="fas fa-clock stat-icon" style="color: #10b981;"></i>
                    <div class="stat-value" style="font-size: 1.2rem;"><?= $uptime ?></div>
                    <div class="stat-label">System Uptime</div>
                </div>

                <div class="glass-card stat-card">
                    <i class="fas fa-memory stat-icon" style="color: #f59e0b;"></i>
                    <div class="stat-value"><?= $memory_limit ?></div>
                    <div class="stat-label">Memory Limit</div>
                </div>

                <div class="glass-card stat-card">
                    <i class="fas fa-upload stat-icon" style="color: #6366f1;"></i>
                    <div class="stat-value"><?= $upload_max ?></div>
                    <div class="stat-label">Max Upload</div>
                </div>

                <div class="glass-card stat-card">
                    <i class="fas fa-server stat-icon" style="color: #64748b;"></i>
                    <div class="stat-value"><?= $server_os ?></div>
                    <div class="stat-label">Server OS</div>
                </div>
            </div>

            <div class="glass-card" style="margin-top: 30px; padding: 30px;">
                <h3><i class="fas fa-info-circle"></i> Environment Details</h3>
                <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.9rem;">
                    <div><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?></div>
                    <div><strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?></div>
                    <div><strong>Server Address:</strong> <?= $_SERVER['SERVER_ADDR'] ?? '127.0.0.1' ?></div>
                    <div><strong>Protocol:</strong> <?= $_SERVER['SERVER_PROTOCOL'] ?></div>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/theme.js"></script>
</body>
</html>
