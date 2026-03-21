<?php
// admin/shifts.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Fetch Shifts
$stmt = $pdo->query("
    SELECT s.*, u.name as user_name,
    TIMESTAMPDIFF(MINUTE, s.start_time, IFNULL(s.end_time, NOW())) as duration_minutes
    FROM shifts s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.start_time DESC
    LIMIT 50
");
$shifts = $stmt->fetchAll();

$page_title = "Shift History";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .shift-status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .shift-status-active { background: rgba(34, 197, 94, 0.1); color: var(--success); border: 1px solid rgba(34, 197, 94, 0.2); }
        .shift-status-ended { background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--border-color); }
        
        .duration-badge {
            background: rgba(249, 115, 22, 0.1);
            color: var(--primary-color);
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Track employee working hours and shifts</p>
                </div>
            </header>

            <div class="admin-table-container glass-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td style="font-weight:700; color: var(--text-main);">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="background:var(--primary-color); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:0.8rem;">
                                            <?= strtoupper(substr($shift['user_name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($shift['user_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:0.9rem;"><?= date('M d, Y', strtotime($shift['start_time'])) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;"><?= date('H:i:s', strtotime($shift['start_time'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($shift['end_time']): ?>
                                        <div style="font-size:0.9rem;"><?= date('M d, Y', strtotime($shift['end_time'])) ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;"><?= date('H:i:s', strtotime($shift['end_time'])) ?></div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="duration-badge">
                                        <?= floor($shift['duration_minutes'] / 60) ?>h
                                        <?= $shift['duration_minutes'] % 60 ?>m
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($shift['end_time']): ?>
                                        <span class="shift-status-badge shift-status-ended">Completed</span>
                                    <?php else: ?>
                                        <span class="shift-status-badge shift-status-active"><i class="fas fa-circle-play" style="margin-right:5px;"></i> Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">No shift records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>

</html>