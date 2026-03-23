<?php
// admin/new_order.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();
checkPermission('new_order.php');

// Fetch all tables
$tables = [];
try {
    $stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tables = [];
}

// Fetch categories and menu items
$categories = [];
$menu = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
    $categories = $stmt->fetchAll();
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY name ASC");
        $stmt->execute([$cat['id']]);
        $items = $stmt->fetchAll();
        if (!empty($items)) {
            $menu[$cat['id']] = [
                'name' => $cat['name'],
                'items' => $items
            ];
        }
    }
} catch (Exception $e) {
    $categories = [];
    $menu = [];
}

$page_title = "New Order";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .new-order-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .new-order-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Table selector */
        .table-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .table-selector select {
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-main);
            font-size: 1rem;
            min-width: 200px;
        }
        .table-selector .table-status-indicator {
            font-size: 0.85rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
        }

        /* Category filter */
        .category-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .cat-filter-btn {
            padding: 8px 18px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .cat-filter-btn:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-main);
        }
        .cat-filter-btn.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        /* Menu list table */
        .menu-list-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .menu-list-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }
        .menu-list-table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .menu-list-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .menu-list-table .item-name {
            font-weight: 600;
            color: var(--text-main);
        }
        .menu-list-table .item-price {
            font-weight: 700;
            color: var(--primary-color);
            white-space: nowrap;
        }
        .menu-list-table .item-unavailable {
            opacity: 0.4;
        }
        .menu-list-table .item-unavailable .item-name {
            text-decoration: line-through;
        }
        .menu-list-table .qty-input {
            width: 60px;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-main);
            text-align: center;
            font-size: 0.95rem;
        }
        .menu-list-table .add-item-btn {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            background: var(--primary-color);
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .menu-list-table .add-item-btn:hover {
            filter: brightness(1.15);
        }
        .menu-list-table .add-item-btn:disabled {
            background: rgba(255,255,255,0.06);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        /* Category group header in table */
        .cat-group-header td {
            padding: 16px 16px 8px 16px;
            font-weight: 800;
            font-size: 1rem;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            background: transparent !important;
        }

        /* Search */
        .menu-search-bar {
            position: relative;
            margin-bottom: 16px;
        }
        .menu-search-bar input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-main);
            font-size: 0.95rem;
        }
        .menu-search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .menu-search-bar i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Order summary card */
        .order-summary-card {
            position: sticky;
            top: 20px;
        }
        .order-summary-card h3 {
            margin: 0 0 16px 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .order-items-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 16px;
        }
        .order-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            gap: 10px;
        }
        .order-item-row .oi-name {
            flex: 1;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .order-item-row .oi-qty {
            color: var(--text-muted);
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .order-item-row .oi-price {
            font-weight: 700;
            white-space: nowrap;
        }
        .order-item-row .oi-remove {
            color: var(--danger, #ef4444);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 0.9rem;
            padding: 4px;
            transition: opacity 0.2s;
        }
        .order-item-row .oi-remove:hover {
            opacity: 0.7;
        }
        .order-empty-state {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .order-totals {
            border-top: 2px solid var(--border-color);
            padding-top: 12px;
            margin-bottom: 16px;
        }
        .order-totals .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .submit-order-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: var(--primary-color);
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .submit-order-btn:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.3);
        }
        .submit-order-btn:disabled {
            background: rgba(255,255,255,0.06);
            color: var(--text-muted);
            cursor: not-allowed;
            box-shadow: none;
        }

        .clear-order-btn {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s ease;
        }
        .clear-order-btn:hover {
            border-color: var(--danger, #ef4444);
            color: var(--danger, #ef4444);
        }

        /* Flash message */
        .order-flash {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.95rem;
            display: none;
        }
        .order-flash.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        .order-flash.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><i class="fas fa-concierge-bell" style="margin-right: 8px; color: var(--primary-color);"></i> New Order</h2>
                    <div class="date-subtitle">Create an order for a table</div>
                </div>
            </header>

            <div id="order-flash" class="order-flash"></div>

            <!-- Table Selector -->
            <div class="table-selector">
                <label style="font-weight: 700; font-size: 0.95rem;"><i class="fas fa-chair" style="margin-right: 6px;"></i> Table:</label>
                <select id="table-select" onchange="onTableChange()">
                    <option value="">— Select a table —</option>
                    <?php foreach ($tables as $tbl): ?>
                        <option value="<?= $tbl['id'] ?>" data-status="<?= $tbl['status'] ?>">
                            <?= htmlspecialchars($tbl['name']) ?> (<?= ucfirst($tbl['status']) ?>, Cap: <?= $tbl['capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span id="table-status-badge" class="table-status-indicator" style="display:none;"></span>
            </div>

            <div class="new-order-layout">
                <!-- Left: Menu List -->
                <div class="glass-card">
                    <h3 style="margin: 0 0 16px 0; font-size: 1.1rem;"><i class="fas fa-utensils" style="margin-right: 8px; color: var(--primary-color);"></i> Menu</h3>

                    <div class="menu-search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="menu-search" placeholder="Search items..." oninput="filterMenuItems()">
                    </div>

                    <div class="category-filters">
                        <button class="cat-filter-btn active" onclick="filterCategory('all', this)">All</button>
                        <?php foreach ($menu as $catId => $catData): ?>
                            <button class="cat-filter-btn" onclick="filterCategory(<?= $catId ?>, this)"><?= htmlspecialchars($catData['name']) ?></button>
                        <?php endforeach; ?>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="menu-list-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th style="width: 80px;">Qty</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody id="menu-tbody">
                                <?php foreach ($menu as $catId => $catData): ?>
                                    <tr class="cat-group-header" data-cat="<?= $catId ?>">
                                        <td colspan="4"><i class="fas fa-tag" style="margin-right: 6px;"></i> <?= htmlspecialchars($catData['name']) ?></td>
                                    </tr>
                                    <?php foreach ($catData['items'] as $item): ?>
                                        <tr class="menu-item-row <?= !$item['is_available'] ? 'item-unavailable' : '' ?>" 
                                            data-cat="<?= $catId ?>"
                                            data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>"
                                            data-item-id="<?= $item['id'] ?>"
                                            data-item-name="<?= htmlspecialchars($item['name']) ?>"
                                            data-item-price="<?= $item['price'] ?>">
                                            <td>
                                                <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                                <?php if ($item['allergens']): ?>
                                                    <br><small style="color: var(--text-muted); font-size: 0.75rem;"><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> <?= htmlspecialchars($item['allergens']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="item-price"><?= number_format($item['price'], 2) ?> €</td>
                                            <td>
                                                <input type="number" class="qty-input" value="1" min="1" max="50" <?= !$item['is_available'] ? 'disabled' : '' ?>>
                                            </td>
                                            <td>
                                                <button class="add-item-btn" <?= !$item['is_available'] ? 'disabled' : '' ?>
                                                    onclick="addItemToOrder(this)">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="glass-card order-summary-card">
                    <h3><i class="fas fa-receipt" style="color: var(--primary-color);"></i> Order Summary</h3>

                    <div class="order-items-list" id="order-items-list">
                        <div class="order-empty-state" id="order-empty">
                            <i class="fas fa-shopping-basket" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.3;"></i>
                            No items added yet
                        </div>
                    </div>

                    <div class="order-totals">
                        <div class="total-row">
                            <span>Total:</span>
                            <span id="order-total">0.00 €</span>
                        </div>
                    </div>

                    <button class="submit-order-btn" id="submit-order-btn" onclick="submitOrder()" disabled>
                        <i class="fas fa-paper-plane"></i> Place Order
                    </button>

                    <button class="clear-order-btn" onclick="clearOrder()">
                        <i class="fas fa-trash-alt"></i> Clear All
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Order state
        let orderItems = [];

        function onTableChange() {
            const sel = document.getElementById('table-select');
            const badge = document.getElementById('table-status-badge');
            const opt = sel.options[sel.selectedIndex];
            if (!sel.value) {
                badge.style.display = 'none';
                return;
            }
            const status = opt.getAttribute('data-status');
            badge.style.display = 'inline-block';
            badge.textContent = status.toUpperCase();
            if (status === 'free') {
                badge.style.background = 'rgba(34,197,94,0.15)';
                badge.style.color = '#22c55e';
            } else if (status === 'occupied') {
                badge.style.background = 'rgba(249,115,22,0.15)';
                badge.style.color = '#f97316';
            } else {
                badge.style.background = 'rgba(245,158,11,0.15)';
                badge.style.color = '#f59e0b';
            }
            updateSubmitState();
        }

        function filterCategory(catId, btn) {
            document.querySelectorAll('.cat-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const rows = document.querySelectorAll('#menu-tbody tr');
            rows.forEach(row => {
                if (catId === 'all') {
                    row.style.display = '';
                } else {
                    row.style.display = row.getAttribute('data-cat') == catId ? '' : 'none';
                }
            });
        }

        function filterMenuItems() {
            const q = document.getElementById('menu-search').value.toLowerCase();
            document.querySelectorAll('.menu-item-row').forEach(row => {
                const name = row.getAttribute('data-name') || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
            // Also show/hide category headers
            document.querySelectorAll('.cat-group-header').forEach(header => {
                const catId = header.getAttribute('data-cat');
                const visibleItems = document.querySelectorAll(`.menu-item-row[data-cat="${catId}"]:not([style*="display: none"])`);
                header.style.display = visibleItems.length > 0 ? '' : 'none';
            });
        }

        function addItemToOrder(btn) {
            const row = btn.closest('tr');
            const id = parseInt(row.getAttribute('data-item-id'));
            const name = row.getAttribute('data-item-name');
            const price = parseFloat(row.getAttribute('data-item-price'));
            const qty = parseInt(row.querySelector('.qty-input').value) || 1;

            // Check if item already in order
            const existing = orderItems.find(i => i.id === id);
            if (existing) {
                existing.quantity += qty;
            } else {
                orderItems.push({ id, name, price, quantity: qty });
            }

            // Reset qty input
            row.querySelector('.qty-input').value = 1;

            renderOrderSummary();
        }

        function removeFromOrder(index) {
            orderItems.splice(index, 1);
            renderOrderSummary();
        }

        function renderOrderSummary() {
            const list = document.getElementById('order-items-list');
            const emptyState = document.getElementById('order-empty');

            if (orderItems.length === 0) {
                list.innerHTML = '';
                list.appendChild(emptyState);
                emptyState.style.display = 'block';
            } else {
                let html = '';
                orderItems.forEach((item, idx) => {
                    const lineTotal = (item.price * item.quantity).toFixed(2);
                    html += `
                        <div class="order-item-row">
                            <span class="oi-name">${item.name}</span>
                            <span class="oi-qty">${item.quantity}×</span>
                            <span class="oi-price">${lineTotal} €</span>
                            <button class="oi-remove" onclick="removeFromOrder(${idx})" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                });
                list.innerHTML = html;
            }

            // Update total
            const total = orderItems.reduce((sum, i) => sum + i.price * i.quantity, 0);
            document.getElementById('order-total').textContent = total.toFixed(2) + ' €';

            updateSubmitState();
        }

        function updateSubmitState() {
            const table = document.getElementById('table-select').value;
            const hasItems = orderItems.length > 0;
            document.getElementById('submit-order-btn').disabled = !(table && hasItems);
        }

        function clearOrder() {
            orderItems = [];
            renderOrderSummary();
        }

        async function submitOrder() {
            const table = document.getElementById('table-select').value;
            if (!table || orderItems.length === 0) return;

            const btn = document.getElementById('submit-order-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

            try {
                const res = await fetch('../api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table_number: table,
                        items: orderItems.map(i => ({
                            id: i.id,
                            price: i.price,
                            quantity: i.quantity
                        }))
                    })
                });

                const data = await res.json();

                if (data.success) {
                    showFlash('success', `Order #${data.order_id} placed successfully for Table ${table}!`);
                    orderItems = [];
                    renderOrderSummary();
                    // Update table status in dropdown
                    const opt = document.querySelector(`#table-select option[value="${table}"]`);
                    if (opt) opt.setAttribute('data-status', 'occupied');
                    onTableChange();
                } else {
                    showFlash('error', 'Failed: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                showFlash('error', 'Network error: ' + e.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Place Order';
            updateSubmitState();
        }

        function showFlash(type, msg) {
            const el = document.getElementById('order-flash');
            el.className = 'order-flash ' + type;
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(() => { el.style.display = 'none'; }, 5000);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>

    <script src="../assets/js/theme.js"></script>
</body>

</html>
