<?php
// admin/stats.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// 1. Daily Revenue (Today)
$stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM orders WHERE status = 'paid' AND DATE(created_at) = CURDATE()");
$stmt->execute();
$daily_revenue = $stmt->fetch()['total'] ?? 0;

// 2. Weekly Revenue (Last 7 Days)
$stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM orders WHERE status = 'paid' AND created_at >= DATE(NOW()) - INTERVAL 7 DAY");
$stmt->execute();
$weekly_revenue = $stmt->fetch()['total'] ?? 0;

// 3. Top Selling Items
$stmt = $pdo->query("
    SELECT m.name, SUM(oi.quantity) as sold_count, SUM(oi.quantity * oi.price_at_time) as revenue
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'paid'
    GROUP BY m.id
    ORDER BY sold_count DESC
    LIMIT 5
");
$top_items = $stmt->fetchAll();

$page_title = "Statistics";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Statistics - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_stats.css">
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
                <li class="nav-item"><a href="shifts.php" class="nav-link"><i class="fas fa-clock"></i> Shifts</a></li>
                <li class="nav-item"><a href="coupons.php" class="nav-link"><i class="fas fa-ticket-alt"></i>
                        Coupons</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Employees</a>
                    </li>
                    <li class="nav-item"><a href="stats.php" class="nav-link active"><i class="fas fa-chart-line"></i>
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
            <main class="main-content">
                <style>
                    @media print {

                        .sidebar,
                        .print-btn-container {
                            display: none !important;
                        }

                        .main-content {
                            margin: 0;
                            padding: 0;
                            width: 100%;
                        }

                        .app-container {
                            flex-direction: column;
                        }

                        body {
                            background: #fff;
                            color: #000;
                        }

                        .stat-card {
                            border: 1px solid #ccc;
                            box-shadow: none;
                            color: #000;
                            background: #fff;
                        }

                        .stat-value {
                            color: #000;
                        }

                        .progress-bar-bg {
                            background: #eee;
                        }

                        .progress-bar-fill {
                            background: #666;
                        }
                    }
                </style>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <h2 style="margin:0;">Business Overview</h2>
                    <div class="print-btn-container">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print" style="margin-right:8px;"></i> Print Report
                        </button>
                    </div>
                </div>

                <div class="stats-grid">
                    <!-- Daily Revenue -->
                    <div class="stat-card">
                        <div class="stat-title">Daily Revenue</div>
                        <div class="stat-value">
                            <?= number_format($daily_revenue, 2) ?> €
                        </div>
                        <div class="stat-sub">
                            <?= date('M d, Y') ?>
                        </div>
                    </div>

                    <!-- Weekly Revenue -->
                    <div class="stat-card">
                        <div class="stat-title">Weekly Revenue</div>
                        <div class="stat-value">
                            <?= number_format($weekly_revenue, 2) ?> €
                        </div>
                        <div class="stat-sub">Last 7 Days</div>
                    </div>
                </div>

                <!-- Top Items -->
                <div class="stat-card" style="max-width: 600px;">
                    <div class="stat-title" style="margin-bottom: 20px;">Top Selling Items</div>
                    <?php if (empty($top_items)): ?>
                        <p style="color:var(--text-muted);">No sales data yet.</p>
                    <?php else: ?>
                        <table class="top-items-table">
                            <?php
                            $max_sales = $top_items[0]['sold_count'];
                            foreach ($top_items as $item):
                                $percent = ($item['sold_count'] / $max_sales) * 100;
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:bold;">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </div>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <div style="font-weight:bold;">
                                            <?= $item['sold_count'] ?> sold
                                        </div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);">
                                            <?= number_format($item['revenue'], 2) ?> €
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>

            </main>
    </div>
</body>

</html>