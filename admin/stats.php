<?php
// admin/stats.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access: Statistics permission
checkPermission('view_reports');

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
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Business Overview</h2>
                    <div class="date-subtitle"><?= date('F j, Y') ?></div>
                </div>
                <div class="print-btn-container no-print">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print" style="margin-right:8px;"></i> Print Report
                    </button>
                </div>
            </header>

            <div class="stats-grid">
                <!-- Daily Revenue -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-label">Daily Revenue</div>
                        <div class="stat-value"><?= number_format($daily_revenue, 2) ?> €</div>
                        <div class="stat-sub"><?= date('M d, Y') ?></div>
                    </div>
                </div>

                <!-- Weekly Revenue -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-label">Weekly Revenue</div>
                        <div class="stat-value"><?= number_format($weekly_revenue, 2) ?> €</div>
                        <div class="stat-sub">Last 7 Days</div>
                    </div>
                </div>
            </div>

            <!-- Top Items -->
            <div class="glass-card" style="max-width: 800px;">
                <h3 style="margin-bottom: 24px; font-size: 1.25rem; font-weight: 700;">Top Selling Items</h3>
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
                                    <div style="font-weight:600; margin-bottom: 8px;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <div style="font-weight:700; color: var(--primary-color);">
                                        <?= $item['sold_count'] ?> sold
                                    </div>
                                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top: 4px;">
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