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

// Fetch Active Orders based on Role (Initial load handled by fragment below)
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
    <style>
        /* Fullscreen styles */
        :fullscreen .sidebar {
            display: none;
        }

        :fullscreen .main-content {
            margin-left: 0 !important;
            width: 100%;
            padding: 20px;
        }

        :-webkit-full-screen .sidebar {
            display: none;
        }

        :-webkit-full-screen .main-content {
            margin-left: 0 !important;
            width: 100%;
            padding: 20px;
        }

        .update-indicator {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .update-indicator.updating i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="app-container" id="app-container" style="background: var(--bg-color);">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header style="background: transparent; border: none; padding: 0 0 2rem 0; margin: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $page_title ?>
                        </h2>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="font-size: 0.9rem; color: var(--text-muted);">
                                <?= date('l, F j, Y') ?>
                            </div>
                            <div class="update-indicator" id="update-indicator">
                                <i class="fas fa-sync-alt"></i> <span>Last updated: Just now</span>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button id="fullscreen-btn" class="btn"
                            style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color);">
                            <i class="fas fa-expand"></i> Fullscreen
                        </button>
                        <button onclick="refreshOrders()" class="btn"
                            style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color);">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
            </header>

            <?php if ($msg = getFlashMessage()): ?>
                <div class="alert alert-<?= $msg['type'] ?>">
                    <?= $msg['message'] ?>
                </div>
            <?php endif; ?>

            <div id="orders-container">
                <?php include '../api/get_active_orders_fragment.php'; ?>
            </div>
        </main>
    </div>

    <script>
        const ordersContainer = document.getElementById('orders-container');
        const updateIndicator = document.getElementById('update-indicator');
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const appContainer = document.getElementById('app-container');

        function refreshOrders() {
            updateIndicator.classList.add('updating');
            
            fetch('../api/get_active_orders_fragment.php')
                .then(response => response.text())
                .then(html => {
                    ordersContainer.innerHTML = html;
                    const now = new Date();
                    updateIndicator.querySelector('span').textContent = `Last updated: ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;
                    setupFormInterception(); // Setup interception for new items
                })
                .catch(error => {
                    console.error('Error refreshing orders:', error);
                })
                .finally(() => {
                    setTimeout(() => {
                        updateIndicator.classList.remove('updating');
                    }, 500);
                });
        }

        function setupFormInterception() {
            document.querySelectorAll('.status-update-form').forEach(form => {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('../api/order_status_update.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshOrders(); // Refresh only on success
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error updating status:', error);
                    });
                };
            });
        }

        // Initial setup
        setupFormInterception();

        // Auto refresh every 10 seconds
        setInterval(refreshOrders, 10000);

        // Fullscreen logic
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                appContainer.requestFullscreen().catch(err => {
                    alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
                });
            } else {
                document.exitFullscreen();
            }
        });

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i> Fullscreen';
            } else {
                fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i> Exit Fullscreen';
            }
        });
    </script>
</body>

</html>