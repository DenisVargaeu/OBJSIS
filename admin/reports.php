<?php
// admin/reports.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access: Admin only
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$page_title = "Business Intelligence";

// --- Stats Logic ---
$today = date('Y-m-d');
$month = date('Y-m');

// 1. Revenue Stats
$rev_today = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'paid' AND DATE(created_at) = '$today'")->fetchColumn() ?: 0;
$rev_month = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'paid' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn() ?: 0;
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid' AND DATE(created_at) = '$today'")->fetchColumn() ?: 0;

// 2. Top Items (Today)
$stmt_top = $pdo->query("
    SELECT m.name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price_at_time) as total_revenue
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'paid' AND DATE(o.created_at) = '$today'
    GROUP BY m.id
    ORDER BY total_qty DESC
    LIMIT 10
");
$top_items = $stmt_top->fetchAll();

// 3. Status Distribution
$stmt_status = $pdo->query("SELECT status, COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today' GROUP BY status");
$status_dist = $stmt_status->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?= getCustomStyles() ?>
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            height: 400px;
        }
        
        @media print {
            .sidebar, .btn, .no-print, .page-header { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .glass-card { border: 1px solid #000 !important; box-shadow: none !important; color: #000 !important; }
            body { background: #fff !important; color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header no-print">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Detailed business analytics and performance reports</p>
                </div>
                <button class="btn" onclick="window.print()">
                    <i class="fas fa-file-pdf" style="margin-right:8px;"></i> Export Report
                </button>
            </header>

            <div class="report-grid">
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(249, 115, 22, 0.1); color: var(--primary-color);"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Daily Revenue</div>
                        <div class="stat-value"><?= number_format($rev_today, 2) ?> €</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;">From <?= $total_orders ?> completed sales</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.1); color: var(--success);"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Monthly Gross</div>
                        <div class="stat-value"><?= number_format($rev_month, 2) ?> €</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;">Total for <?= date('F') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6%;"><i class="fas fa-percentage"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Avg. Ticket Size</div>
                        <div class="stat-value"><?= $total_orders > 0 ? number_format($rev_today / $total_orders, 2) : '0.00' ?> €</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;">Today's average</div>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 3fr 2fr; gap: 25px; margin-bottom: 25px;">
                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin:0 0 20px 0; font-size:1.1rem;">Sales Distribution by Category</h3>
                    <div style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin:0 0 20px 0; font-size:1.1rem;">Order Status Overview</h3>
                    <div style="height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="glass-card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; border-bottom:1px solid var(--border-color); background:rgba(255,255,255,0.01);">
                    <h3 style="margin:0; font-size:1.1rem;">Top Performance Items (Today)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Menu Item</th>
                            <th>Quantity Sold</th>
                            <th>Item Revenue</th>
                            <th style="text-align: right;">Contribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($item['name']) ?></td>
                                <td><span class="duration-badge" style="background:rgba(59, 130, 246, 0.1); color:#3b82f6;"><?= (int)$item['total_qty'] ?> units</span></td>
                                <td style="font-weight:700; color:var(--primary-color);"><?= number_format($item['total_revenue'], 2) ?> €</td>
                                <td style="text-align: right;">
                                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px;">
                                        <div style="width:100px; height:6px; background:rgba(255,255,255,0.05); border-radius:3px; overflow:hidden;">
                                            <div style="width:<?= $rev_today > 0 ? ($item['total_revenue'] / $rev_today * 100) : 0 ?>%; height:100%; background:var(--primary-color);"></div>
                                        </div>
                                        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:700;">
                                            <?= $rev_today > 0 ? round($item['total_revenue'] / $rev_today * 100, 1) : 0 ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_items)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:40px; color:var(--text-muted);">No sales data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Category Chart (Mock for now, normally fetch from DB)
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: ['Appetizers', 'Main Courses', 'Beverages', 'Desserts'],
                datasets: [{
                    label: 'Sales (€)',
                    data: [120, 450, 180, 90],
                    backgroundColor: 'rgba(249, 115, 22, 0.6)',
                    borderColor: 'var(--primary-color)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($status_dist, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($status_dist, 'count')) ?>,
                    backgroundColor: ['#f97316', '#3b82f6', '#22c55e', '#ef4444', '#a855f7'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.6)', padding: 20, font: { size: 10 } } } },
                cutout: '70%'
            }
        });
    </script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
