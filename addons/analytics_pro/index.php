<?php
// addons/analytics_pro/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

checkPermission('manage_system');

$page_title = "Analytics Pro";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?= getCustomStyles() ?>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 25px;
        }
        @media (max-width: 900px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
        .chart-container {
            padding: 25px;
            min-height: 400px;
        }
    </style>
</head>
<body>
    <div class="app-container" style="display: block;">
        <main class="main-content" style="margin: 0; padding: 40px; max-width: 1400px; margin: 0 auto;">
            <header class="page-header" style="border:none; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title-group">
                    <h2 style="font-size: 2.5rem; font-weight: 900;"><i class="fas fa-chart-line" style="margin-right: 15px; color: #6366f1;"></i> <?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Visualizing your points of sale performance</p>
                </div>
                <a href="../../admin/addons.php" class="btn btn-secondary" style="border-radius: 30px; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Back to Addons
                </a>
            </header>

            <div class="analytics-grid">
                <div class="glass-card chart-container">
                    <h3><i class="fas fa-calendar-day"></i> Weekly Sales Trend</h3>
                    <canvas id="salesTrendChart"></canvas>
                </div>

                <div class="glass-card chart-container">
                    <h3><i class="fas fa-pie-chart"></i> Category Distribution</h3>
                    <canvas id="categoryPieChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadCharts() {
            const res = await fetch('api.php');
            const data = await res.json();
            
            if (data.success) {
                // Sales Trend Chart
                new Chart(document.getElementById('salesTrendChart'), {
                    type: 'line',
                    data: {
                        labels: data.sales_by_day.map(d => d.date),
                        datasets: [{
                            label: 'Daily Revenue (€)',
                            data: data.sales_by_day.map(d => d.total),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Category Pie Chart
                new Chart(document.getElementById('categoryPieChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.sales_by_category.map(c => c.category),
                        datasets: [{
                            data: data.sales_by_category.map(c => c.total),
                            backgroundColor: [
                                '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#94a3b8' } }
                        }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', loadCharts);
    </script>
    <script src="../../assets/js/theme.js"></script>
</body>
</html>
