<?php
// admin/history.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Filters
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';
$table_filter = $_GET['table'] ?? '';

// Build query
$where = ["(o.status = 'paid' OR o.status = 'cancelled')"];
$params = [];

if ($date_from) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

if ($status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($table_filter !== '') {
    $where[] = "o.table_number = ?";
    $params[] = $table_filter;
}

$where_clause = implode(' AND ', $where);

// Fetch orders
$stmt = $pdo->prepare("
    SELECT o.*, 
    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ') as items_summary
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE $where_clause
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculate revenue summary
$revenue_stmt = $pdo->prepare("
    SELECT 
        SUM(total_price) as total_revenue,
        SUM(discount_amount) as total_discounts,
        COUNT(*) as order_count
    FROM orders o
    WHERE $where_clause
");
$revenue_stmt->execute($params);
$summary = $revenue_stmt->fetch();

// Fetch all tables for filter
$tables_stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
$tables = $tables_stmt->fetchAll();

$page_title = "Order History";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order History - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Order History</h2>
                    <div class="date-subtitle">Review past performance and detailed logs</div>
                </div>
            </header>

            <!-- Filters -->
            <form class="admin-filters" method="GET">
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Table</label>
                    <select name="table">
                        <option value="">All Tables</option>
                        <?php foreach ($tables as $tbl): ?>
                            <option value="<?= $tbl['id'] ?>" <?= $table_filter == $tbl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tbl['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-filter"></i> Apply
                </button>
            </form>

            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?= $summary['order_count'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value"><?= number_format($summary['total_revenue'] ?? 0, 2) ?> €</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(249, 115, 22, 0.1); color: var(--primary-color);">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="stat-card-content">
                        <div class="stat-label">Total Discounts</div>
                        <div class="stat-value"><?= number_format($summary['total_discounts'] ?? 0, 2) ?> €</div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="admin-table-container">
                <?php if (empty($orders)): ?>
                    <div style="text-align:center; padding:60px; color:var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:15px; display:block; opacity:0.3;"></i>
                        No orders found for the selected filters.
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date & Time</th>
                                <th>Table</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $subtotal = $order['total_price'] + $order['discount_amount'];
                                ?>
                                <tr>
                                    <td style="font-weight:700; color: var(--primary-color);">#<?= $order['id'] ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                        <div style="font-size:0.8rem; color: var(--text-muted);"><?= date('H:i', strtotime($order['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <span style="font-weight:600;">Table <?= $order['table_number'] ?></span>
                                    </td>
                                    <td style="max-width:250px;">
                                        <div style="font-size:0.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($order['items_summary']) ?>">
                                            <?= htmlspecialchars($order['items_summary']) ?>
                                        </div>
                                    </td>
                                    <td><?= number_format($subtotal, 2) ?> €</td>
                                    <td style="color:var(--success);">
                                        <?= $order['discount_amount'] > 0 ? '-' . number_format($order['discount_amount'], 2) . ' €' : '—' ?>
                                    </td>
                                    <td style="font-weight:800; color: var(--text-main);"><?= number_format($order['total_price'], 2) ?> €</td>
                                    <td>
                                        <?php if ($order['status'] === 'paid'): ?>
                                            <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); font-size: 0.7rem; padding: 4px 10px;">
                                                <i class="fas fa-check-circle"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.7rem; padding: 4px 10px;">
                                                <i class="fas fa-times-circle"></i> Cancelled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary" style="padding:8px 12px; font-size:0.8rem; border-radius: 8px;">
                                            <i class="fas fa-receipt"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>