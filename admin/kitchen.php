<?php
// admin/kitchen.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

// Access Control: Kitchen permission
checkPermission('view_orders');

// Handle Rapid Status Update (AJAX or POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // SECURITY FIX: CSRF Validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'message' => 'CSRF Token Invalid']);
            exit;
        }
        die("CSRF Token Invalid");
    }
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    if(isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    header("Location: kitchen.php");
    exit;
}

$page_title = "Kitchen Display System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        :root {
            --kitchen-bg: #020617;
            --kitchen-card: #0f172a;
            --kitchen-text: #f8fafc;
            --kitchen-accent: #f97316;
        }
        body.kitchen-mode {
            background-color: var(--kitchen-bg);
            color: var(--kitchen-text);
        }
        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }
        .kds-card {
            background: var(--kitchen-card);
            border-radius: 20px;
            border: 2px solid #1e293b;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
            transition: transform 0.2s ease;
        }
        .kds-card.status-received { border-color: var(--kitchen-accent); }
        .kds-card.status-preparing { border-color: #3b82f6; }

        .kds-header {
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            border-bottom: 2px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kds-table { font-size: 1.6rem; font-weight: 900; color: #fff; }
        .kds-time { font-family: monospace; font-weight: 700; color: var(--kitchen-accent); }

        .kds-body {
            padding: 20px;
            flex: 1;
        }
        .item-row {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            align-items: flex-start;
        }
        .item-qty {
            background: var(--kitchen-accent);
            color: #fff;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 900;
            font-size: 1.2rem;
            min-width: 45px;
            text-align: center;
        }
        .item-name { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }

        .kds-footer { padding: 15px; }
        
        @keyframes pulse-new {
            0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(249, 115, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
        }
        .new-order { animation: pulse-new 2s infinite; }

        .alert-banner {
            background: #ef4444;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body class="kitchen-mode">
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="page-header" style="border-bottom-color: rgba(255,255,255,0.1);">
                <div class="page-title-group">
                    <h2 style="color:#fff; font-size: 2.4rem;">KDS <span style="font-weight:300; opacity:0.6;">System</span></h2>
                    <div id="connection-status" style="color:var(--success); font-weight:700; font-size:0.8rem;">
                        <i class="fas fa-circle" style="font-size:0.6rem; margin-right:5px;"></i> LIVE UPDATES
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <div id="summary-badge" style="background:rgba(255,255,255,0.05); padding:10px 20px; border-radius:15px; font-weight:800;">
                        0 ORDERS ACTIVE
                    </div>
                    <button class="btn btn-secondary" onclick="toggleFullscreen()" title="Fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </header>

            <div id="critical-alert" class="alert-banner">
                <i class="fas fa-exclamation-triangle"></i> Multiple orders waiting > 15 mins!
            </div>

            <div id="kitchen-orders" class="kitchen-grid">
                <!-- Orders loaded via AJAX -->
                <div style="text-align:center; padding: 100px; grid-column: 1/-1; opacity:0.3;">
                    <i class="fas fa-circle-notch fa-spin fa-3x"></i>
                    <p style="margin-top:20px; font-weight:700;">SYNCHRONIZING...</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        let lastOrderCount = -1;

        async function fetchOrders() {
            try {
                const response = await fetch('../api/kitchen_fetch.php');
                const data = await response.json();
                
                if (data.success) {
                    renderOrders(data.orders);
                    updateSummary(data.orders);
                    
                    if (lastOrderCount !== -1 && data.orders.length > lastOrderCount) {
                        playAlert();
                    }
                    lastOrderCount = data.orders.length;
                }
            } catch (err) {
                console.error("KDS sync failed", err);
            }
        }

        function renderOrders(orders) {
            const container = document.getElementById('kitchen-orders');
            if (orders.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding: 120px; grid-column: 1/-1; background: rgba(255,255,255,0.02); border-radius: 30px; border: 2px dashed rgba(255,255,255,0.1);">
                        <i class="fas fa-coffee fa-4x" style="color:var(--kitchen-accent); margin-bottom:20px; opacity:0.4;"></i>
                        <h2 style="opacity:0.6; font-weight:900;">ALL CLEAR</h2>
                        <p style="opacity:0.4;">Enjoy the break!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(o => {
                const elapsed = Math.floor((new Date() - new Date(o.created_at.replace(' ', 'T'))) / 60000);
                const isUrgent = elapsed >= 10;
                const isNew = elapsed < 1;
                const items = JSON.parse(o.items_json); 
                
                return `
                <div class="kds-card status-${o.status} ${isNew ? 'new-order' : ''}">
                    <div class="kds-header">
                        <div class="kds-table">T-${escHTML(o.table_number)}</div>
                        <div class="kds-time ${isUrgent ? 'urgent' : ''}" style="${isUrgent ? 'color:#ef4444' : ''}">
                            ${elapsed}m <i class="fas fa-clock" style="font-size:0.7rem;"></i>
                        </div>
                    </div>
                    <div class="kds-body">
                        ${items.map(i => `
                            <div class="item-row">
                                <div class="item-qty">${i.qty}</div>
                                <div class="item-name">${escHTML(i.name)}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="kds-footer">
                        ${o.status === 'received' ? `
                            <button onclick="updateStatus(${o.id}, 'preparing')" class="btn" style="width:100%; background:#3b82f6; height:60px; font-size:1.1rem; font-weight:900; border-radius:15px;">
                                RUN KITCHEN
                            </button>
                        ` : `
                            <button onclick="updateStatus(${o.id}, 'ready')" class="btn" style="width:100%; background:var(--success); height:60px; font-size:1.1rem; font-weight:900; border-radius:15px;">
                                DONE / READY
                            </button>
                        `}
                    </div>
                </div>
                `;
            }).join('');
        }

        function updateSummary(orders) {
            document.getElementById('summary-badge').innerText = `${orders.length} ORDERS ACTIVE`;
            
            const veryOld = orders.filter(o => {
                const elapsed = Math.floor((new Date() - new Date(o.created_at.replace(' ', 'T'))) / 60000);
                return elapsed >= 15;
            });
            
            document.getElementById('critical-alert').style.display = veryOld.length > 0 ? 'block' : 'none';
        }

        async function updateStatus(id, status) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', id);
            formData.append('status', status);
            formData.append('ajax', '1');

            const res = await fetch('kitchen.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) fetchOrders();
        }

        function playAlert() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                if (context.state === 'suspended') return;
                const os = context.createOscillator();
                const gn = context.createGain();
                os.connect(gn);
                gn.connect(context.destination);
                os.type = 'square';
                os.frequency.setValueAtTime(440, context.currentTime);
                gn.gain.setValueAtTime(0.05, context.currentTime);
                gn.gain.linearRampToValueAtTime(0, context.currentTime + 0.3);
                os.start();
                os.stop(context.currentTime + 0.3);
            } catch(e) {}
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        // Initial fetch and start interval
        fetchOrders();
        setInterval(fetchOrders, 4000); // 4s for kitchen is better
    </script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
