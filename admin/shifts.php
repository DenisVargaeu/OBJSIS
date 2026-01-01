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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shifts History - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_shifts.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 20px;">Shift History</h2>

            <table class="shift-history-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $shift): ?>
                        <tr>
                            <td style="font-weight:bold;">
                                <?= htmlspecialchars($shift['user_name']) ?>
                            </td>
                            <td>
                                <?= date('M d, H:i', strtotime($shift['start_time'])) ?>
                            </td>
                            <td>
                                <?= $shift['end_time'] ? date('M d, H:i', strtotime($shift['end_time'])) : '-' ?>
                            </td>
                            <td>
                                <span class="duration-badge">
                                    <?= floor($shift['duration_minutes'] / 60) ?>h
                                    <?= $shift['duration_minutes'] % 60 ?>m
                                </span>
                            </td>
                            <td>
                                <?php if ($shift['end_time']): ?>
                                    <span style="color:var(--text-muted); font-size:0.9rem;">Ended</span>
                                <?php else: ?>
                                    <span style="color:var(--success); font-weight:bold; font-size:0.9rem;">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>

</html>