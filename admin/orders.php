<?php
// admin/orders.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);

    if ($new_status === 'paid' || $new_status === 'cancelled') {
        $stmt_get = $pdo->prepare("SELECT table_number FROM orders WHERE id = ?");
        $stmt_get->execute([$order_id]);
        $tbl = $stmt_get->fetch();
        if ($tbl) {
            $stmt_tbl = $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?");
            $stmt_tbl->execute([$tbl['table_number']]);
        }
    }

    setFlashMessage("Order #$order_id updated to $new_status");
    redirect('orders.php');
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
    <title>Orders - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header style="background: transparent; border: none; padding: 0 0 2rem 0; margin: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $page_title ?>
                        </h2>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                    <button onclick="location.reload()" class="btn"
                        style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color);">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
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
                        <?php
                        $status_class = htmlspecialchars($order['status']);
                        $status_icon = 'fa-dot-circle';
                        switch ($order['status']) {
                            case 'received':
                                $status_icon = 'fa-inbox';
                                break;
                            case 'preparing':
                                $status_icon = 'fa-fire';
                                break;
                            case 'ready':
                                $status_icon = 'fa-bell';
                                break;
                            case 'delivered':
                                $status_icon = 'fa-truck';
                                break;
                        }
                        ?>
                        <div class="card order-card status-<?= $status_class ?>"
                            style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                            <div
                                style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0;">
                                            Table
                                            <?= $order['table_number'] ?>
                                        </h3>
                                        <span
                                            style="font-size: 0.75rem; background: var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">#
                                            <?= $order['id'] ?>
                                        </span>
                                    </div>
                                    <div
                                        style="font-size: 0.8rem; color: var(--primary-color); margin-top: 4px; font-weight: 600;">
                                        <i class="fas fa-clock"></i>
                                        <?= date('H:i', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="status-badge"
                                    style="padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                    <i class="fas <?= $status_icon ?>"></i>
                                    <?= $status_class ?>
                                </div>
                            </div>

                            <div style="padding: 20px; flex: 1;">
                                <div
                                    style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        <?php
                                        $items_array = explode(', ', $order['items']);
                                        foreach ($items_array as $item):
                                            preg_match('/(\d+x) (.*)/', $item, $matches);
                                            $qty = $matches[1] ?? '';
                                            $name = $matches[2] ?? $item;
                                            ?>
                                            <li
                                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                                                <span style="color: var(--text-main);"><strong style="color: var(--primary-color);">
                                                        <?= $qty ?>
                                                    </strong>
                                                    <?= htmlspecialchars($name) ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div
                                style="padding: 20px; background: rgba(255,255,255,0.02); border-top: 1px solid var(--border-color);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <span style="color: var(--text-muted); font-size: 0.9rem;">Order Total</span>
                                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-main);">
                                        <?= number_format($order['total_price'], 2) ?> €
                                    </span>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">

                                    <?php
                                    $next_status = '';
                                    $btn_label = '';
                                    $btn_color = 'var(--primary-color)';
                                    $btn_icon = 'fa-arrow-right';
                                    switch ($order['status']) {
                                        case 'received':
                                            $next_status = 'preparing';
                                            $btn_label = 'Start Preparing';
                                            $btn_color = '#e67e22';
                                            $btn_icon = 'fa-fire';
                                            break;
                                        case 'preparing':
                                            $next_status = 'ready';
                                            $btn_label = 'Finish Item';
                                            $btn_color = '#2ecc71';
                                            $btn_icon = 'fa-check-circle';
                                            break;
                                        case 'ready':
                                            $next_status = 'delivered';
                                            $btn_label = 'Send to Table';
                                            $btn_color = '#3498db';
                                            $btn_icon = 'fa-serving-glass';
                                            break;
                                        case 'delivered':
                                            $next_status = 'paid';
                                            $btn_label = 'Mark as Paid';
                                            $btn_color = '#f1c40f';
                                            $btn_icon = 'fa-cash-register';
                                            break;
                                    }
                                    ?>

                                    <?php if ($next_status): ?>
                                        <input type="hidden" name="status" value="<?= $next_status ?>">
                                        <div style="display:flex; gap:10px;">
                                            <button type="submit" class="btn"
                                                style="background-color: <?= $btn_color ?>; flex: 1; justify-content: center; padding: 14px; font-size: 1rem; border-radius: 12px;">
                                                <i class="fas <?= $btn_icon ?>" style="margin-right: 10px;"></i>
                                                <?= $btn_label ?>
                                            </button>
                                            <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn"
                                                style="padding:14px; background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color); width: 54px; justify-content: center; border-radius: 12px;">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <a href="receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="btn"
                                            style="width:100%; justify-content:center; background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color); padding: 14px; border-radius: 12px;">
                                            <i class="fas fa-print" style="margin-right:8px;"></i> Print Receipt
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>