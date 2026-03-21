<?php
// admin/inventory.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

// Access Control: Inventory permission
checkPermission('view_inventory');

// Handle Add/Edit Ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        checkPermission('manage_inventory');
        $name = $_POST['name'];
        $qty = $_POST['quantity'];
        $unit = $_POST['unit'];
        $threshold = $_POST['threshold'];

        $stmt = $pdo->prepare("INSERT INTO inventory (name, current_quantity, unit, critical_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $qty, $unit, $threshold]);

        // Log the initial stock
        $last_id = $pdo->lastInsertId();
        $stmt_log = $pdo->prepare("INSERT INTO inventory_logs (inventory_id, user_id, change_type, quantity_change) VALUES (?, ?, 'purchase', ?)");
        $stmt_log->execute([$last_id, $_SESSION['user_id'], $qty]);

        updateMenuAvailability($pdo);

        setFlashMessage("Ingredient '$name' added successfully");
    } elseif (isset($_POST['restock'])) {
        checkPermission('manage_inventory');
        $id = $_POST['item_id'];
        $add_qty = $_POST['add_quantity'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET current_quantity = current_quantity + ? WHERE id = ?");
            $stmt->execute([$add_qty, $id]);

            $stmt_log = $pdo->prepare("INSERT INTO inventory_logs (inventory_id, user_id, change_type, quantity_change) VALUES (?, ?, 'purchase', ?)");
            $stmt_log->execute([$id, $_SESSION['user_id'], $add_qty]);

            // Update menu items availability
            updateMenuAvailability($pdo);

            $pdo->commit();
            setFlashMessage("Restocked successfully");
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage("Error restocking: " . $e->getMessage(), 'error');
        }
    } elseif (isset($_POST['log_adjustment'])) {
        checkPermission('manage_inventory');
        $id = $_POST['item_id'];
        $qty_change = $_POST['qty_change']; // Can be negative
        $type = $_POST['adj_type']; // 'waste' or 'correction'

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET current_quantity = current_quantity + ? WHERE id = ?");
            $stmt->execute([$qty_change, $id]);

            $stmt_log = $pdo->prepare("INSERT INTO inventory_logs (inventory_id, user_id, change_type, quantity_change) VALUES (?, ?, ?, ?)");
            $stmt_log->execute([$id, $_SESSION['user_id'], $type, $qty_change]);

            updateMenuAvailability($pdo);

            $pdo->commit();
            setFlashMessage("Adjustment logged successfully");
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage("Error logging adjustment: " . $e->getMessage(), 'error');
        }
    } elseif (isset($_POST['delete_item'])) {
        checkPermission('manage_inventory');
        $id = $_POST['item_id'];
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage("Ingredient deleted");
    }
    header("Location: inventory.php");
    exit;
}

// Fetch Inventory Items
$stmt = $pdo->query("SELECT * FROM inventory ORDER BY name ASC");
$inventory = $stmt->fetchAll();

$page_title = "Inventory Management";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?= $page_title ?> - OBJSIS
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stock-card.critical {
            border: 2px solid var(--danger);
            background: rgba(239, 68, 68, 0.08);
        }
        .stock-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -1px;
        }
        .stock-unit {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-left: 6px;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Inventory Management</h2>
                    <div class="date-subtitle">Track stock levels, restocks, and waste logs</div>
                </div>
                <div style="display:flex; gap:12px;">
                    <button class="btn btn-secondary" onclick="toggleLowStockOnly()" id="filter-btn">
                        <i class="fas fa-filter"></i> Low Stock Only
                    </button>
                    <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">
                        <i class="fas fa-plus"></i> Add Ingredient
                    </button>
                </div>
            </header>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if (empty($inventory)): ?>
                <div class="glass-card" style="text-align:center; padding: 60px;">
                    <i class="fas fa-boxes"
                        style="font-size: 4rem; color: var(--text-muted); opacity: 0.2; margin-bottom: 20px; display: block;"></i>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">No ingredients in inventory yet.</p>
                </div>
            <?php else: ?>
                <div class="inventory-grid">
                    <?php foreach ($inventory as $item):
                        $is_critical = $item['current_quantity'] <= $item['critical_threshold'];
                        ?>
                        <div class="stat-card <?= $is_critical ? 'critical' : '' ?>" data-low-stock="<?= $is_critical ? '1' : '0' ?>" style="flex-direction: column; align-items: stretch; padding: 24px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px;">
                                <h3 style="margin:0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($item['name']) ?></h3>
                                <div style="display:flex; gap:8px;">
                                    <button class="btn btn-secondary"
                                        onclick="viewLogs(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')"
                                        style="padding: 6px 10px; font-size: 0.8rem; border-radius: 8px;" title="View History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Delete this ingredient?');" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="delete_item" class="btn" style="padding: 6px 10px; font-size: 0.8rem; background: var(--danger); border-radius: 8px;" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div style="display:flex; align-items:baseline; margin-bottom: 8px;">
                                <div class="stock-value"><?= (float) $item['current_quantity'] ?></div>
                                <div class="stock-unit"><?= htmlspecialchars($item['unit']) ?></div>
                                <?php if ($is_critical): ?>
                                    <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.65rem; margin-left:12px; padding: 3px 8px;">LOW STOCK</span>
                                <?php endif; ?>
                            </div>

                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 24px; font-weight: 500;">
                                Threshold: <span style="color: var(--text-dim);"><?= (float) $item['critical_threshold'] ?> <?= htmlspecialchars($item['unit']) ?></span>
                            </div>

                            <div style="display:flex; flex-direction:column; gap:12px; margin-top: auto;">
                                <form method="POST" style="display: flex; gap: 8px; margin: 0;">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="number" step="0.01" name="add_quantity" placeholder="Qty" required style="flex: 1; padding: 8px; font-size: 0.9rem;">
                                    <button type="submit" name="restock" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.9rem;">
                                        <i class="fas fa-plus"></i> Restock
                                    </button>
                                </form>
                                
                                <button class="btn btn-secondary" style="width:100%; font-size:0.8rem; background: rgba(255,255,255,0.03);" 
                                    onclick="openAdjustmentModal(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', '<?= $item['unit'] ?>')">
                                    <i class="fas fa-minus-circle"></i> Log Waste / Correction
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Adjustment Modal -->
    <div id="adjModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 420px; padding: 24px; flex-direction: column; align-items: stretch;">
            <h3 id="adjTitle" style="margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">Adjust Stock</h3>
            <form method="POST">
                <input type="hidden" name="item_id" id="adj-item-id">
                <div class="form-group">
                    <label>Adjustment Type</label>
                    <select name="adj_type" required>
                        <option value="waste">Waste (Spoilage/Broken)</option>
                        <option value="correction">Inventory Correction (Count)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Change Quantity (Negative for loss)</label>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <input type="number" step="0.01" name="qty_change" required style="flex:1;" placeholder="-2.50">
                        <span id="adj-unit" style="font-weight: 600; color: var(--text-muted);">unit</span>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:30px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="document.getElementById('adjModal').style.display='none'">Cancel</button>
                    <button type="submit" name="log_adjustment" class="btn" style="flex: 1;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 400px; padding: 24px; flex-direction: column; align-items: stretch;">
            <h3 style="margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">Add New Ingredient</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" placeholder="e.g. Beef Patty" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div class="form-group">
                        <label>Initial Qty</label>
                        <input type="number" step="0.01" name="quantity" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" placeholder="kg, pcs, l" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Critical Threshold</label>
                    <input type="number" step="0.01" name="threshold" value="5" required>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:30px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_item" class="btn" style="flex: 1;">Create Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Modal -->
    <div id="logsModal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width:650px; max-width:95%; padding: 24px; flex-direction: column; align-items: stretch;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 id="logsTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700;">Movement History</h3>
                <button class="btn btn-secondary"
                    onclick="document.getElementById('logsModal').style.display='none'"
                    style="padding: 6px 12px; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="logsContent" style="max-height: 450px; overflow-y: auto; border-radius: 12px; border: 1px solid var(--border-color);">
                <!-- Logs loaded here -->
            </div>
        </div>
    </div>

    <script>
        function openAdjustmentModal(id, name, unit) {
            document.getElementById('adjTitle').innerHTML = "<i class='fas fa-sliders-h' style='color:var(--primary-color); margin-right:10px;'></i> Adjust: " + name;
            document.getElementById('adj-item-id').value = id;
            document.getElementById('adj-unit').innerText = unit;
            document.getElementById('adjModal').style.display = 'flex';
        }

        let lowStockOnly = false;
        function toggleLowStockOnly() {
            lowStockOnly = !lowStockOnly;
            const cards = document.querySelectorAll('[data-low-stock]');
            cards.forEach(card => {
                if (lowStockOnly && card.getAttribute('data-low-stock') === '0') {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'flex';
                }
            });
            
            const btn = document.getElementById('filter-btn');
            btn.style.background = lowStockOnly ? 'var(--primary-color)' : '';
            btn.style.color = lowStockOnly ? 'white' : '';
        }

        function viewLogs(id, name) {
            document.getElementById('logsTitle').innerText = name + " - History";
            document.getElementById('logsModal').style.display = 'flex';
            const content = document.getElementById('logsContent');
            content.innerHTML = '<p style="text-align:center; padding: 40px; opacity:0.5;">Fetching logs...</p>';

            fetch(`../api/inventory_logs.php?item_id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (res.logs.length === 0) {
                            content.innerHTML = '<div style="text-align:center; opacity:0.5; padding:60px;"><i class="fas fa-history" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i> No movement history found.</div>';
                        } else {
                            let html = '<table class="admin-table" style="box-shadow: none; border-radius: 0;">';
                            html += '<thead><tr><th>Date</th><th>Type</th><th>Change</th><th>User</th></tr></thead><tbody>';
                            res.logs.forEach(l => {
                                const isPositive = l.quantity_change > 0;
                                const color = isPositive ? 'var(--success)' : 'var(--danger)';
                                html += `<tr>
                                    <td style="font-size: 0.85rem;">${new Date(l.timestamp).toLocaleString()}</td>
                                    <td style="text-transform:capitalize; font-weight: 600;">${l.change_type}</td>
                                    <td style="font-weight:800; color:${color}">${isPositive ? '+' : ''}${parseFloat(l.quantity_change)}</td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);">${l.user_name || 'System'}</td>
                                </tr>`;
                            });
                            html += '</tbody></table>';
                            content.innerHTML = html;
                        }
                    } else {
                        alert(res.message);
                    }
                });
        }
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>