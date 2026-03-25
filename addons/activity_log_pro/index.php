<?php
// addons/activity_log_pro/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

checkPermission('manage_system');

$page_title = "System Activity Log";

// Fetch logs with user names
$stmt = $pdo->query("
    SELECT l.*, u.name as user_name 
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 100
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .log-table-wrapper {
            margin-top: 25px;
            overflow-x: auto;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }
        .log-table th {
            text-align: left;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }
        .log-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        .log-time {
            font-family: monospace;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .log-action {
            font-weight: 700;
            color: var(--primary-color);
        }
        .badge-user {
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="app-container" style="display: block;">
        <main class="main-content" style="margin: 0; padding: 40px; max-width: 1200px; margin: 0 auto;">
            <header class="page-header" style="border:none; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title-group">
                    <h2 style="font-size: 2.5rem; font-weight: 900;"><i class="fas fa-list-check" style="margin-right: 15px; color: #6366f1;"></i> <?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Detailed audit trail of all system actions</p>
                </div>
                <a href="../../admin/addons.php" class="btn btn-secondary" style="border-radius: 30px; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Back to Addons
                </a>
            </header>

            <div class="glass-card log-table-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 50px; opacity: 0.5;">
                                    No activity recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="log-time"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <span class="badge-user"><i class="fas fa-user"></i> <?= htmlspecialchars($log['user_name']) ?></span>
                                    <?php else: ?>
                                        <span style="opacity: 0.5;">System</span>
                                    <?php endif; ?>
                                </td>
                                <td class="log-action"><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['details']) ?></td>
                                <td style="opacity: 0.6; font-size: 0.8rem;"><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../../assets/js/theme.js"></script>
</body>
</html>
