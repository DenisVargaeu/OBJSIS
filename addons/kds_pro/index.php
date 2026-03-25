<?php
// addons/kds_pro/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();

checkPermission('view_orders');

$page_title = "KDS Pro Display";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        :root {
            --kds-bg: #020617;
            --kds-card: #0f172a;
            --kds-border: rgba(255,255,255,0.08);
        }
        
        body.kds-pro-theme {
            background: var(--kds-bg);
            color: #fff;
            overflow: hidden;
            height: 100vh;
        }

        .kds-main {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 20px;
        }

        .kds-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--kds-border);
        }

        .kds-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            overflow-y: auto;
            padding-bottom: 50px;
        }

        .kds-card {
            background: var(--kds-card);
            border: 2px solid #1e293b;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            position: relative;
        }

        .kds-card.urgent { border-color: #ef4444; }
        .kds-card.new { border-color: var(--primary-color); }

        .card-header {
            padding: 15px 20px;
            background: rgba(255,255,255,0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--kds-border);
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .card-footer {
            padding: 15px;
        }

        .order-item {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .item-qty {
            color: var(--primary-color);
            min-width: 30px;
        }

        .timer {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: var(--primary-color);
        }

        /* Full-screen Toggle */
        .fs-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--kds-border);
            color: #fff;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="kds-pro-theme">
    <div class="kds-main">
        <header class="kds-header">
            <div>
                <h1 style="font-size: 2rem; font-weight: 900; margin: 0;">KDS <span style="color:var(--primary-color)">PRO</span></h1>
                <p style="opacity: 0.5; margin: 0; font-size: 0.8rem;"><i class="fas fa-satellite-dish"></i> Real-time Sync Active</p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div id="stats" style="background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 20px; font-weight: 700;">
                    0 ACTIVE ORDERS
                </div>
                <button class="fs-btn" onclick="toggleFullScreen()"><i class="fas fa-expand"></i></button>
                <a href="../../admin/dashboard.php" class="fs-btn" style="text-decoration: none;"><i class="fas fa-times"></i></a>
            </div>
        </header>

        <div id="kds-grid" class="kds-grid">
            <!-- Orders load here -->
            <div style="grid-column: 1/-1; text-align: center; padding: 100px; opacity: 0.2;">
                <i class="fas fa-circle-notch fa-spin fa-3x"></i>
            </div>
        </div>
    </div>

    <script>
        let orders = [];

        async function fetchOrders() {
            try {
                const res = await fetch('api.php');
                const data = await res.json();
                if (data.success) {
                    orders = data.orders;
                    render();
                }
            } catch (e) { console.error(e); }
        }

        function render() {
            const grid = document.getElementById('kds-grid');
            const stats = document.getElementById('stats');
            stats.innerText = `${orders.length} ACTIVE ORDERS`;

            if (orders.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px; opacity: 0.3;">
                        <i class="fas fa-check-circle fa-4x" style="margin-bottom: 20px; color: var(--success);"></i>
                        <h2>KITCHEN IS CLEAR</h2>
                    </div>
                `;
                return;
            }

            grid.innerHTML = orders.map(o => {
                const elapsed = Math.floor((new Date() - new Date(o.created_at.replace(' ', 'T'))) / 60000);
                const items = JSON.parse(o.items_json);
                const isUrgent = elapsed >= 10;
                
                return `
                    <div class="kds-card ${isUrgent ? 'urgent' : ''}">
                        <div class="card-header">
                            <span style="font-size: 1.5rem; font-weight: 900;">T-${o.table_number}</span>
                            <span class="timer">${elapsed}M</span>
                        </div>
                        <div class="card-body">
                            ${items.map(i => `
                                <div class="order-item">
                                    <span class="item-qty">${i.qty}x</span>
                                    <span>${i.name}</span>
                                </div>
                            `).join('')}
                            ${o.note ? `<p style="margin-top:15px; color: #fb923c; font-size: 0.85rem; font-style: italic;"><i class="fas fa-comment"></i> ${o.note}</p>` : ''}
                        </div>
                        <div class="card-footer">
                            <button onclick="setStatus(${o.id}, 'ready')" class="btn" style="width: 100%; height: 50px; background: var(--success); font-weight: 800; border-radius: 12px;">
                                MARK AS READY
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function setStatus(id, status) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', id);
            formData.append('status', status);
            formData.append('ajax', '1');

            // We reuse the main kitchen.php for status updates to avoid duplicating logic
            await fetch('../../admin/kitchen.php', { method: 'POST', body: formData });
            fetchOrders();
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        setInterval(fetchOrders, 3000);
        fetchOrders();
    </script>
</body>
</html>
