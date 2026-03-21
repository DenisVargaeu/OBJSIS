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

// Handle Status Update (Allow AJAX)
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

    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    setFlashMessage("Order #$order_id updated to $new_status");
    redirect('dashboard.php');
}

$page_title = "Live Dashboard";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?= getCustomStyles() ?>
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
        }
        @media (max-width: 1200px) {
            .dashboard-layout { grid-template-columns: 1fr; }
        }
        .chart-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
            height: 300px;
        }
        .top-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            margin-bottom: 8px;
            border: 1px solid rgba(255,255,255,0.02);
            transition: var(--transition-base);
        }
        .top-item-row:hover { background: rgba(255,255,255,0.08); }
        .order-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .order-card.new {
            animation: slideIn 0.5s ease-out;
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.2);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <div class="date-subtitle" id="current-time"><?= date('l, F j, Y • H:i:s') ?></div>
                </div>

                <div class="shift-widget stat-card" style="padding: 10px 20px; gap: 15px;">
                    <?php if ($active_shift): ?>
                        <div style="text-align: right;">
                            <div style="font-size: 0.7rem; color: var(--success); font-weight: 800; text-transform: uppercase;">Clocked In</div>
                            <div style="font-size: 0.85rem; font-family: monospace; opacity: 0.8;">Since <?= date('H:i', strtotime($active_shift['start_time'])) ?></div>
                        </div>
                        <button onclick="openClockOutModal()" class="btn" style="background: var(--danger); font-size: 0.85rem; padding: 6px 15px; border-radius: 8px;">Clock Out</button>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Not working?</div>
                        <button onclick="openClockInModal()" class="btn" style="font-size: 0.85rem; padding: 6px 15px; border-radius: 8px;">Clock In</button>
                    <?php endif; ?>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(249, 115, 22, 0.1); color: var(--primary-color);"><i class="fas fa-euro-sign"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-value" id="stat-revenue">0.00 €</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Today's Orders</div>
                        <div class="stat-value" id="stat-orders">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.1); color: var(--success);"><i class="fas fa-clock"></i></div>
                    <div class="stat-card-content">
                        <div class="stat-label">Active Orders</div>
                        <div class="stat-value" id="stat-active">0</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-layout">
                <div class="main-column">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>

                    <div id="orders-container" class="orders-list">
                        <!-- Orders Rendered Here -->
                        <div style="text-align: center; padding: 60px; opacity: 0.5;">
                            <i class="fas fa-circle-notch fa-spin fa-2x"></i>
                            <p style="margin-top: 15px;">Loading live orders...</p>
                        </div>
                    </div>
                </div>

                <div class="side-column">
                    <div class="glass-card" style="padding: 24px;">
                        <h3 style="margin: 0 0 20px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">
                            <i class="fas fa-fire" style="color: var(--primary-color); margin-right: 8px;"></i> Top Items Today
                        </h3>
                        <div id="top-items-list">
                            <!-- Top Items Rendered Here -->
                        </div>
                    </div>

                    <div class="stat-card" style="margin-top: 25px; flex-direction: column; align-items: stretch; padding: 24px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Live Status</h4>
                        <div style="display: flex; align-items: center; gap: 10px; color: var(--success); font-weight: 600; font-size: 0.9rem;">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--success); box-shadow: 0 0 10px var(--success);"></span>
                            Connected to Server
                        </div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Automatically refreshing metrics every 10 seconds.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals (Simplified versions of the existing ones) -->
    <div id="clock-in-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 350px; flex-direction: column; align-items: flex-start; padding: 25px;">
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">Start Shift</h3>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size:0.9rem;">Ready to begin your service?</p>
            <div style="display:flex; gap:12px; width: 100%;">
                <button class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('clock-in-modal').style.display='none'">Cancel</button>
                <button class="btn" style="flex: 1;" onclick="submitShift('clock_in')">Start</button>
            </div>
        </div>
    </div>

    <div id="clock-out-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 350px; flex-direction: column; align-items: flex-start; padding: 25px;">
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">End Shift</h3>
            <p style="color:var(--text-muted); margin-bottom:20px; font-size:0.9rem;">Complete your daily session.</p>
            <div style="display:flex; gap:12px; width: 100%;">
                <button class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('clock-out-modal').style.display='none'">Cancel</button>
                <button class="btn" style="background:var(--danger); flex: 1;" onclick="submitShift('clock_out')">End</button>
            </div>
        </div>
    </div>

    <script>
        let revenueChart = null;
        let lastOrderCount = -1;

        async function updateDashboard() {
            try {
                const response = await fetch('../api/dashboard_fetch.php');
                const data = await response.json();
                
                if (data.success) {
                    renderStats(data.stats);
                    renderOrders(data.orders);
                    renderChart(data.chart);
                    renderTopItems(data.top_items);
                    
                    if (lastOrderCount !== -1 && data.stats.active > lastOrderCount) {
                        playAlert();
                    }
                    lastOrderCount = data.stats.active;
                }
            } catch (err) {
                console.error("Dashboard fetch failed", err);
            }
        }

        function renderStats(stats) {
            document.getElementById('stat-revenue').innerText = stats.revenue.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
            document.getElementById('stat-orders').innerText = stats.orders;
            document.getElementById('stat-active').innerText = stats.active;
        }

        function renderOrders(orders) {
            const container = document.getElementById('orders-container');
            if (orders.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem; border: 2px dashed rgba(255,255,255,0.05); border-radius: 20px; color: var(--text-muted);">
                        <i class="fas fa-smile-beam" style="font-size: 3rem; margin-bottom: 1rem; color: var(--success); opacity: 0.5;"></i>
                        <p style="font-size: 1.1rem; font-weight: 600;">No active orders. Kitchen is relaxed!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(o => {
                const isNew = (new Date() - new Date(o.created_at)) < 30000;
                const items = JSON.parse(o.items_json);
                const statusInfo = getStatusInfo(o.status);
                
                return `
                <div class="card order-card status-${o.status} ${isNew ? 'new' : ''}" style="padding:0; overflow:hidden; margin-bottom: 25px;">
                    <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.015);">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0;">Table ${o.table_number}</h3>
                                <span style="font-size: 0.65rem; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; color: var(--text-muted); font-weight: 700;">#${o.id}</span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--primary-color); margin-top: 4px; font-weight: 700;">
                                <i class="fas fa-clock"></i> ${new Date(o.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </div>
                        </div>
                        <div class="status-badge" style="background: ${statusInfo.bg}; color: ${statusInfo.color}; border: 1px solid ${statusInfo.border}; padding: 5px 12px; border-radius: 30px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas ${statusInfo.icon}" style="margin-right: 6px;"></i> ${o.status}
                        </div>
                    </div>
                    <div style="padding: 20px 24px;">
                        <div style="background: rgba(0,0,0,0.15); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                ${items.map(i => `
                                    <li style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; color: var(--text-dim);">
                                        <span><strong style="color: var(--primary-color); margin-right: 8px;">${i.qty}x</strong> ${i.name}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                    <div style="padding: 18px 24px; background: rgba(255,255,255,0.01); border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 1.25rem; font-weight: 900; color: var(--text-main);">${parseFloat(o.total_price).toFixed(2)} €</div>
                        <div style="display: flex; gap: 8px;">
                            ${statusInfo.next ? `
                                <button onclick="updateOrderStatus(${o.id}, '${statusInfo.next}')" class="btn" style="background: ${statusInfo.nextColor}; padding: 10px 18px; font-size: 0.85rem; font-weight: 800;">
                                    <i class="fas ${statusInfo.nextIcon}" style="margin-right: 8px;"></i> ${statusInfo.nextLabel}
                                </button>
                            ` : ''}
                            <a href="receipt.php?order_id=${o.id}" target="_blank" class="btn btn-secondary" style="width: 42px; padding: 0; display: flex; align-items: center; justify-content: center;">
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
                'received': { icon: 'fa-inbox', bg: 'rgba(249, 115, 22, 0.1)', color: 'var(--primary-color)', border: 'rgba(249, 115, 22, 0.2)', next: 'preparing', nextLabel: 'Start Kitchen', nextIcon: 'fa-fire', nextColor: '#e67e22' },
                'preparing': { icon: 'fa-fire', bg: 'rgba(230, 126, 34, 0.1)', color: '#e67e22', border: 'rgba(230, 126, 34, 0.2)', next: 'ready', nextLabel: 'Order Ready', nextIcon: 'fa-check-circle', nextColor: 'var(--success)' },
                'ready': { icon: 'fa-bell', bg: 'rgba(34, 197, 94, 0.1)', color: 'var(--success)', border: 'rgba(34, 197, 94, 0.2)', next: 'delivered', nextLabel: 'Served', nextIcon: 'fa-truck', nextColor: '#3498db' },
                'delivered': { icon: 'fa-truck', bg: 'rgba(52, 152, 219, 0.1)', color: '#3498db', border: 'rgba(52, 152, 219, 0.2)', next: 'paid', nextLabel: 'Checkout', nextIcon: 'fa-cash-register', nextColor: '#f1c40f' }
            };
            return map[status] || { icon: 'fa-circle', bg: 'rgba(255,255,255,0.05)', color: '#fff', border: 'rgba(255,255,255,0.1)' };
        }

        function renderChart(chartData) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const labels = chartData.map(d => d.hr + ':00');
            const values = chartData.map(d => d.rev);

            if (revenueChart) {
                revenueChart.data.labels = labels;
                revenueChart.data.datasets[0].data = values;
                revenueChart.update('none');
                return;
            }

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Hourly Revenue (€)',
                        data: values,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 10 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 10 } }
                        }
                    }
                }
            });
        }

        function renderTopItems(items) {
            const container = document.getElementById('top-items-list');
            if(items.length === 0) {
                container.innerHTML = '<p style="text-align:center; opacity:0.3; margin:20px 0;">No sales recorded yet.</p>';
                return;
            }
            container.innerHTML = items.map(i => `
                <div class="top-item-row">
                    <span style="font-weight: 600; color: var(--text-dim);">${i.name}</span>
                    <span style="font-weight: 800; color: var(--primary-color);">${parseInt(i.qty)} sold</span>
                </div>
            `).join('');
        }

        async function updateOrderStatus(id, status) {
            const formData = new FormData();
            formData.append('order_id', id);
            formData.append('status', status);
            formData.append('update_status', '1');
            formData.append('ajax', '1');

            const res = await fetch('dashboard.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) updateDashboard();
        }

        function submitShift(action) {
            const formData = new FormData();
            formData.append('action', action);
            fetch('../api/shift_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => { if (res.success) location.reload(); else alert(res.message); });
        }

        function playAlert() {
            // Web Audio API beep
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                if (context.state === 'suspended') return; // User must interact first
                const os = context.createOscillator();
                const gn = context.createGain();
                os.connect(gn);
                gn.connect(context.destination);
                os.type = 'sine';
                os.frequency.setValueAtTime(880, context.currentTime);
                gn.gain.setValueAtTime(0.1, context.currentTime);
                gn.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
                os.start();
                os.stop(context.currentTime + 0.5);
            } catch(e) {}
        }

        function openClockInModal() { document.getElementById('clock-in-modal').style.display = 'flex'; }
        function openClockOutModal() { document.getElementById('clock-out-modal').style.display = 'flex'; }

        // Initial update and clock
        updateDashboard();
        setInterval(updateDashboard, 10000);
        setInterval(() => {
            document.getElementById('current-time').innerText = new Date().toLocaleString('en-US', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' 
            });
        }, 1000);
    </script>
</body>
</html>