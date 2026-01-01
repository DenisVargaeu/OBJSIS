<?php
// admin/dashboard.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Check for active shift
$stmt_shift = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
$stmt_shift->execute([$_SESSION['user_id']]);
$active_shift = $stmt_shift->fetch();

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);

    // Phase 3: Update Table Status if Paid or Cancelled
    if ($new_status === 'paid' || $new_status === 'cancelled') {
        // Get table number from order
        $stmt_get = $pdo->prepare("SELECT table_number FROM orders WHERE id = ?");
        $stmt_get->execute([$order_id]);
        $tbl = $stmt_get->fetch();
        if ($tbl) {
            $stmt_tbl = $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?");
            $stmt_tbl->execute([$tbl['table_number']]);
        }
    }

    setFlashMessage("Order #$order_id updated to $new_status");
    redirect('dashboard.php');
}

// Fetch Active Orders based on Role
$role_clause = "";
if ($user_role === 'cook') {
    $role_clause = "AND (o.status = 'received' OR o.status = 'preparing')";
} elseif ($user_role === 'waiter') {
    $role_clause = "AND (o.status = 'ready' OR o.status = 'delivered')";
}

$stmt = $pdo->query("
    SELECT o.*, GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ') as items 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE o.status != 'paid' AND o.status != 'cancelled' $role_clause
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

$page_title = "Active Orders";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBJSIS Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="menu.php" class="nav-link">
                        <i class="fas fa-utensils"></i> Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tables.php" class="nav-link">
                        <i class="fas fa-chair"></i> Tables
                    </a>
                </li>
                <li class="nav-item">
                    <a href="coupons.php" class="nav-link">
                        <i class="fas fa-ticket-alt"></i> Coupons
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shifts.php" class="nav-link">
                        <i class="fas fa-clock"></i> Shifts
                    </a>
                </li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i> Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="stats.php" class="nav-link">
                            <i class="fas fa-chart-line"></i> Statistics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link">
                            <i class="fas fa-history"></i> History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="user-profile">
                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                    <?= htmlspecialchars($user_name) ?>
                </div>
                <div
                    style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                    <?= htmlspecialchars($user_role) ?>
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
        </aside>
        <script src="../assets/js/theme.js"></script>

        <!-- Main Content -->
        <main class="main-content">
            <header style="background: transparent; border: none; padding: 0 0 2rem 0; margin: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 5px;"><?= $page_title ?></h2>
                        <div style="font-size: 0.9rem; color: var(--text-muted);"><?= date('l, F j, Y') ?></div>
                    </div>

                    <!-- Shift Widget -->
                    <div class="shift-widget"
                        style="background: var(--card-bg); padding: 10px 20px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px;">
                        <?php if ($active_shift): ?>
                            <div style="text-align: right;">
                                <div style="font-size: 0.8rem; color: var(--success); font-weight: bold;">Clocked In</div>
                                <div style="font-size: 0.9rem; font-family: monospace;">Since
                                    <?= date('H:i', strtotime($active_shift['start_time'])) ?>
                                </div>
                            </div>
                            <button onclick="openClockOutModal()" class="btn"
                                style="background: var(--danger); font-size: 0.9rem; padding: 8px 15px;">Clock Out</button>
                        <?php else: ?>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">Not working?</div>
                            <button onclick="openClockInModal()" class="btn"
                                style="font-size: 0.9rem; padding: 8px 15px;">Clock In</button>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <?php if ($msg = getFlashMessage()): ?>
                <div class="alert alert-<?= $msg['type'] ?>">
                    <?= $msg['message'] ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div
                    style="text-align: center; padding: 4rem; border: 2px dashed rgba(255,255,255,0.1); border-radius: 16px; color: var(--text-muted);">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--success);"></i>
                    <p style="font-size: 1.2rem;">All caught up! No active orders.</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="card status-<?= htmlspecialchars($order['status']) ?>">
                            <div
                                style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="font-size: 1.5rem; color: var(--text-main);">Table <?= $order['table_number'] ?>
                                    </h3>
                                    <span style="font-size: 0.85rem; color: var(--text-muted);">#<?= $order['id'] ?> •
                                        <?= date('H:i', strtotime($order['created_at'])) ?></span>
                                </div>
                                <span class="status-badge"><?= htmlspecialchars($order['status']) ?></span>
                            </div>

                            <div
                                style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 1rem; min-height: 80px;">
                                <p style="font-size: 1rem; line-height: 1.5; color: var(--text-main);">
                                    <?= htmlspecialchars($order['items']) ?>
                                </p>
                            </div>

                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                                <span style="color: var(--text-muted); font-size: 0.9rem;">Total Amount</span>
                                <span
                                    style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);"><?= number_format($order['total_price'], 2) ?>
                                    €</span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="update_status" value="1">

                                <?php
                                $next_status = '';
                                $btn_label = '';
                                $btn_color = 'var(--primary-color)';
                                switch ($order['status']) {
                                    case 'received':
                                        $next_status = 'preparing';
                                        $btn_label = 'Start Cooking';
                                        $btn_color = '#e67e22';
                                        break;
                                    case 'preparing':
                                        $next_status = 'ready';
                                        $btn_label = 'Mark Ready';
                                        $btn_color = '#2ecc71';
                                        break;
                                    case 'ready':
                                        $next_status = 'delivered';
                                        $btn_label = 'Mark Delivered';
                                        $btn_color = '#3498db';
                                        break;
                                    case 'delivered':
                                        $next_status = 'paid';
                                        $btn_label = 'Mark Paid';
                                        $btn_color = '#f1c40f';
                                        break;
                                }
                                ?>

                                <?php if ($next_status): ?>
                                    <input type="hidden" name="status" value="<?= $next_status ?>">
                                    <div style="display:flex; gap:10px;">
                                        <button type="submit" class="btn"
                                            style="background-color: <?= $btn_color ?>; flex: 1; justify-content: center; padding: 12px;">
                                            <?= $btn_label ?> <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                                        </button>
                                        <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary"
                                            style="padding:12px; background: rgba(255,255,255,0.1);">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div
                                        style="text-align:center; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 8px; font-size: 0.9rem; margin-bottom:10px;">
                                        Status: <?= htmlspecialchars($order['status']) ?>
                                    </div>
                                    <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary"
                                        style="width:100%; justify-content:center; background: rgba(255,255,255,0.1);">
                                        <i class="fas fa-print" style="margin-right:8px;"></i> Print Receipt
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <!-- Clock In Modal -->
    <div id="clock-in-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 350px;">
            <h3>Start Shift</h3>
            <p style="color:var(--text-muted); margin-bottom:15px; font-size:0.9rem;">Ready to start working?</p>
            <form onsubmit="event.preventDefault(); submitClockIn(this);">
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('clock-in-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn">Start Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clock Out Modal -->
    <div id="clock-out-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 350px;">
            <h3>End Shift</h3>
            <p style="color:var(--text-muted); margin-bottom:15px; font-size:0.9rem;">Confirm to end your shift.</p>
            <form onsubmit="event.preventDefault(); submitClockOut(this);">
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('clock-out-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn" style="background:var(--danger)">End Shift</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openClockInModal() {
            document.getElementById('clock-in-modal').style.display = 'flex';
        }

        function openClockOutModal() {
            document.getElementById('clock-out-modal').style.display = 'flex';
        }

        function submitClockIn(form) {
            const formData = new FormData(form);
            formData.append('action', 'clock_in');

            fetch('../api/shift_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function submitClockOut(form) {
            const formData = new FormData(form);
            formData.append('action', 'clock_out');

            fetch('../api/shift_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }
    </script>
</body>

</html>