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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order History - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .history-filters {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .summary-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .summary-card-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            text-align: left;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }

        .history-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge-small {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
    </style>
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item"><a href="menu.php" class="nav-link"><i class="fas fa-utensils"></i> Menu</a></li>
                <li class="nav-item"><a href="tables.php" class="nav-link"><i class="fas fa-chair"></i> Tables</a></li>
                <li class="nav-item"><a href="shifts.php" class="nav-link"><i class="fas fa-clock"></i> Shifts</a></li>
                <li class="nav-item"><a href="coupons.php" class="nav-link"><i class="fas fa-ticket-alt"></i>
                        Coupons</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Employees</a>
                </li>
                <li class="nav-item"><a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>
                        Statistics</a></li>
                <li class="nav-item"><a href="history.php" class="nav-link active"><i class="fas fa-history"></i>
                        History</a>
                </li>
                <li class="nav-item"><a href="updates.php" class="nav-link"><i class="fas fa-sync"></i> Updates</a>
                </li>
                <li class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
                </li>
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
            <h2 style="margin-bottom: 20px;">Order History</h2>

            <!-- Filters -->
            <form class="history-filters" method="GET">
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
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled
                        </option>
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
                <button type="submit" class="btn">Apply Filters</button>
            </form>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-value">
                        <?= $summary['order_count'] ?? 0 ?>
                    </div>
                    <div class="summary-card-label">Total Orders</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-value">
                        <?= number_format($summary['total_revenue'] ?? 0, 2) ?> €
                    </div>
                    <div class="summary-card-label">Total Revenue</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-value">
                        <?= number_format($summary['total_discounts'] ?? 0, 2) ?> €
                    </div>
                    <div class="summary-card-label">Total Discounts</div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card" style="overflow-x: auto;">
                <?php if (empty($orders)): ?>
                    <p style="text-align:center; padding:40px; color:var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:15px; display:block; opacity:0.3;"></i>
                        No orders found for the selected filters.
                    </p>
                <?php else: ?>
                    <table class="history-table">
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $subtotal = $order['total_price'] + $order['discount_amount'];
                                ?>
                                <tr>
                                    <td style="font-weight:600;">#
                                        <?= $order['id'] ?>
                                    </td>
                                    <td>
                                        <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                    <td>Table
                                        <?= $order['table_number'] ?>
                                    </td>
                                    <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?= htmlspecialchars($order['items_summary']) ?>
                                    </td>
                                    <td>
                                        <?= number_format($subtotal, 2) ?> €
                                    </td>
                                    <td style="color:var(--success);">
                                        <?= $order['discount_amount'] > 0 ? '-' . number_format($order['discount_amount'], 2) . ' €' : '-' ?>
                                    </td>
                                    <td style="font-weight:700;">
                                        <?= number_format($order['total_price'], 2) ?> €
                                    </td>
                                    <td>
                                        <span class="status-badge-small status-<?= $order['status'] ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank"
                                            class="btn btn-secondary" style="padding:6px 12px; font-size:0.85rem;">
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