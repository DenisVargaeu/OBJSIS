<?php
// admin/orders.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();
checkPermission('view_orders');

$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Handle Status Update (Allow AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // SECURITY FIX: CSRF Validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
            exit;
        }
        die("CSRF Token Invalid");
    }

    $order_id = (int)$_POST['order_id'];
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

    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    setFlashMessage("Order #$order_id updated to $new_status");
    redirect('orders.php');
}

$page_title = "Active Orders Management";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .order-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body>
    <div class="app-container" id="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 0.9rem; color: var(--text-muted);" id="live-timer">
                            <?= date('l, F j, Y') ?>
                        </div>
                        <div id="sync-indicator" style="font-size: 0.75rem; color: var(--success); font-weight: 700; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> LIVE SYNC
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button id="fullscreen-btn" class="btn btn-secondary">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button onclick="updateOrders()" class="btn btn-secondary">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </header>

            <?php if ($msg = getFlashMessage()): ?>
                <div class="alert alert-<?= $msg['type'] ?>">
                    <?= $msg['message'] ?>
                </div>
            <?php endif; ?>

            <div id="orders-list-container" class="orders-grid">
                <!-- Orders Rendered via AJAX -->
                <div style="text-align: center; padding: 100px; grid-column: 1/-1; opacity: 0.4;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 15px; font-weight: 700;">FETCHING ORDERS...</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        let lastOrderCount = -1;

        async function updateOrders() {
            try {
                const response = await fetch('../api/dashboard_fetch.php');
                const data = await response.json();
                
                if (data.success) {
                    renderOrders(data.orders);
                    if (lastOrderCount !== -1 && data.orders.length > lastOrderCount) {
                        playAlert();
                    }
                    lastOrderCount = data.orders.length;
                }
            } catch (err) {
                console.error("Order fetch failed", err);
            }
        }

        function renderOrders(orders) {
            const container = document.getElementById('orders-list-container');
            if (orders.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 5rem; grid-column: 1/-1; border: 2px dashed rgba(255,255,255,0.05); border-radius: 24px; color: var(--text-muted);">
                        <i class="fas fa-clipboard-check" style="font-size: 4rem; color: var(--success); margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3 style="font-weight: 800; font-size: 1.5rem;">No Active Orders</h3>
                        <p>All service tables are currently cleared.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(o => {
                const items = JSON.parse(o.items_json);
                const statusInfo = getStatusInfo(o.status);
                
                return `
                <div class="card order-card status-${o.status}" style="padding:0; overflow:hidden;">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.01);">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h3 style="font-size: 1.2rem; font-weight: 900; color: var(--text-main); margin: 0;">Table ${o.table_number}</h3>
                                <span style="font-size: 0.7rem; background: var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted); font-weight: 700;">#${o.id}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--primary-color); margin-top: 5px; font-weight: 700;">
                                <i class="fas fa-clock"></i> ${new Date(o.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </div>
                        </div>
                        <div class="status-badge" style="background: ${statusInfo.bg}; color: ${statusInfo.color}; border: 1px solid ${statusInfo.border}; padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase;">
                            <i class="fas ${statusInfo.icon}" style="margin-right: 6px;"></i> ${o.status}
                        </div>
                    </div>

                    <div style="padding: 20px; flex: 1;">
                        <div style="background: rgba(0,0,0,0.1); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                ${items.map(i => `
                                    <li style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 1rem; font-weight: 600;">
                                        <!-- SECURITY FIX: Use escHTML for user-provided data -->
                                        <span><strong style="color: var(--primary-color); margin-right: 10px;">${i.qty}x</strong> ${escHTML(i.name)}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>

                    <div style="padding: 20px; background: rgba(255,255,255,0.02); border-top: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 700;">TOTAL AMOUNT</span>
                            <span style="font-size: 1.5rem; font-weight: 900; color: var(--text-main);">${parseFloat(o.total_price).toFixed(2)} €</span>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            ${statusInfo.next ? `
                                <button onclick="updateOrderStatus(${o.id}, '${statusInfo.next}')" class="btn" style="flex: 1; background: ${statusInfo.nextColor}; height: 50px; font-weight: 800; border-radius: 12px;">
                                    <i class="fas ${statusInfo.nextIcon}" style="margin-right: 10px;"></i> ${statusInfo.nextLabel}
                                </button>
                            ` : ''}
                            <a href="receipt.php?order_id=${o.id}" target="_blank" class="btn btn-secondary" style="width: 50px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        }

        function getStatusInfo(status) {
            const map = {
                'received': { icon: 'fa-inbox', bg: 'rgba(249, 115, 22, 0.1)', color: 'var(--primary-color)', border: 'rgba(249, 115, 22, 0.2)', next: 'preparing', nextLabel: 'Cook Now', nextIcon: 'fa-fire', nextColor: '#e67e22' },
                'preparing': { icon: 'fa-fire', bg: 'rgba(230, 126, 34, 0.1)', color: '#e67e22', border: 'rgba(230, 126, 34, 0.2)', next: 'ready', nextLabel: 'Ready', nextIcon: 'fa-check-circle', nextColor: 'var(--success)' },
                'ready': { icon: 'fa-bell', bg: 'rgba(34, 197, 94, 0.1)', color: 'var(--success)', border: 'rgba(34, 197, 94, 0.2)', next: 'delivered', nextLabel: 'Serve', nextIcon: 'fa-truck', nextColor: '#3498db' },
                'delivered': { icon: 'fa-truck', bg: 'rgba(52, 152, 219, 0.1)', color: '#3498db', border: 'rgba(52, 152, 219, 0.2)', next: 'paid', nextLabel: 'Pay', nextIcon: 'fa-cash-register', nextColor: '#f1c40f' }
            };
            return map[status] || { icon: 'fa-circle', bg: 'rgba(255,255,255,0.05)', color: '#fff', border: 'rgba(255,255,255,0.1)' };
        }

        async function updateOrderStatus(id, status) {
            const formData = new FormData();
            formData.append('order_id', id);
            formData.append('status', status);
            formData.append('update_status', '1');
            formData.append('ajax', '1');
            // SECURITY FIX: Include CSRF Token in AJAX
            formData.append('csrf_token', OBJSIS_CSRF_TOKEN);

            const res = await fetch('orders.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) updateOrders();
        }

        function playAlert() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                if (context.state === 'suspended') return;
                const os = context.createOscillator();
                const gn = context.createGain();
                os.connect(gn);
                gn.connect(context.destination);
                os.type = 'sine';
                os.frequency.setValueAtTime(660, context.currentTime);
                gn.gain.setValueAtTime(0.05, context.currentTime);
                gn.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.4);
                os.start();
                os.stop(context.currentTime + 0.4);
            } catch(e) {}
        }

        const fullscreenBtn = document.getElementById('fullscreen-btn');
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.getElementById('app-container').requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });

        // Initial setup
        updateOrders();
        setInterval(updateOrders, 8000);
    </script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>