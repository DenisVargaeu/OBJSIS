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
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <?= htmlspecialchars(getSetting('restaurant_name')) ?>
                <div style="font-size: 0.8rem; opacity: 0.5; font-weight: normal; margin-top: 5px;">
                    <?= OBJSIS_VERSION ?>
                </div>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Active
                        Orders</a></li>
                <li class="nav-item"><a href="menu.php" class="nav-link"><i class="fas fa-utensils"></i> Menu Items</a>
                </li>
                <li class="nav-item"><a href="tables.php" class="nav-link"><i class="fas fa-chair"></i> Tables</a></li>
                <li class="nav-item"><a href="shifts.php" class="nav-link active"><i class="fas fa-clock"></i>
                        Shifts</a></li>
                <li class="nav-item"><a href="coupons.php" class="nav-link"><i class="fas fa-ticket-alt"></i>
                        Coupons</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Employees</a>
                    </li>
                    <li class="nav-item"><a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>
                            Statistics</a></li>
                    <li class="nav-item"><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a>
                    </li>
                    <li class="nav-item"><a href="updates.php" class="nav-link"><i class="fas fa-sync"></i> Updates</a>
                    </li>
                    <li class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="user-profile">
                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <div
                    style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                    <?= htmlspecialchars($_SESSION['user_role']) ?>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <a href="../logout.php" style="color: var(--primary-color); font-size: 0.9rem; font-weight: 500;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <button onclick="toggleTheme()"
                        style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">
                        <i class="fas fa-adjust"></i>
                    </button>
                </div>
            </div>
            <script src="../assets/js/theme.js"></script>
        </aside>

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