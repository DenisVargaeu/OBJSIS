<?php
// admin/inventory.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

// Access Control: Only admin or inventory role
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'inventory') {
    header("Location: dashboard.php");
    exit;
}

// Handle Add/Edit Ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stock-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .stock-card.critical {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
        }

        .stock-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }

        .stock-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stock-unit {
            font-size: 1rem;
            color: var(--text-muted);
            margin-left: 5px;
        }

        .stock-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 10px;
        }

        .badge-critical {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--danger);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
        }

        .restock-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .restock-form input {
            flex: 1;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background: var(--bg-main);
            color: var(--text-main);
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
                <h2 style="margin:0;">Inventory Management</h2>
                <button class="btn" onclick="document.getElementById('addModal').style.display='flex'">
                    <i class="fas fa-plus"></i> Add Ingredient
                </button>
            </div>

            <?php if (empty($inventory)): ?>
                <div class="card" style="text-align:center; padding: 50px;">
                    <i class="fas fa-boxes"
                        style="font-size: 4rem; color: var(--text-muted); opacity: 0.2; margin-bottom: 20px;"></i>
                    <p style="color: var(--text-muted);">No ingredients in inventory yet.</p>
                </div>
            <?php else: ?>
                <div class="inventory-grid">
                    <?php foreach ($inventory as $item):
                        $is_critical = $item['current_quantity'] <= $item['critical_threshold'];
                        ?>
                        <div class="stock-card <?= $is_critical ? 'critical' : '' ?>">
                            <?php if ($is_critical): ?>
                                <span class="badge-critical">Low Stock</span>
                            <?php endif; ?>
                            <h3>
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    <button class="btn btn-secondary"
                                        onclick="viewLogs(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')"
                                        style="padding: 4px 8px; font-size: 0.8rem;">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </h3>
                            <div class="stock-value">
                                <?= (float) $item['current_quantity'] ?>
                                <span class="stock-unit">
                                    <?= htmlspecialchars($item['unit']) ?>
                                </span>
                            </div>
                            <div class="stock-meta">
                                Threshold:
                                <?= (float) $item['critical_threshold'] ?>
                                <?= htmlspecialchars($item['unit']) ?>
                            </div>

                            <form method="POST" class="restock-form">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="number" step="0.01" name="add_quantity" placeholder="Add quantity..." required>
                                <button type="submit" name="restock" class="btn btn-secondary"
                                    style="padding: 8px 12px;">Restock</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Modal Omitted for space, imagine a simple form modal -->
    <div id="addModal" class="modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width:400px; padding:30px;">
            <h3>Add New Ingredient</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g. Beef Patty" required
                        style="width:100%; padding:10px; margin-top:5px;">
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Initial Quantity</label>
                    <input type="number" step="0.01" name="quantity" value="0" required
                        style="width:100%; padding:10px; margin-top:5px;">
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Unit</label>
                    <input type="text" name="unit" placeholder="kg, pcs, l" required
                        style="width:100%; padding:10px; margin-top:5px;">
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label>Critical Threshold</label>
                    <input type="number" step="0.01" name="threshold" value="5" required
                        style="width:100%; padding:10px; margin-top:5px;">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_item" class="btn">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Modal -->
    <div id="logsModal" class="modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width:600px; max-width:95%; padding:30px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 id="logsTitle">Movement History</h3>
                <button class="btn btn-secondary"
                    onclick="document.getElementById('logsModal').style.display='none'">&times;</button>
            </div>
            <div id="logsContent" style="max-height: 400px; overflow-y: auto;">
                <!-- Logs loaded here -->
            </div>
        </div>
    </div>

    <script>
        function viewLogs(id, name) {
            document.getElementById('logsTitle').innerText = name + " - History";
            document.getElementById('logsModal').style.display = 'flex';
            const content = document.getElementById('logsContent');
            content.innerHTML = '<p style="text-align:center; opacity:0.5;">Loading...</p>';

            fetch(`../api/inventory_logs.php?item_id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (res.logs.length === 0) {
                            content.innerHTML = '<p style="text-align:center; opacity:0.5; padding:20px;">No movement history found.</p>';
                        } else {
                            let html = '<table style="width:100%; border-collapse:collapse; font-size:0.9rem;">';
                            html += '<tr style="border-bottom:2px solid var(--border-color); text-align:left;"><th style="padding:10px;">Date</th><th style="padding:10px;">Type</th><th style="padding:10px;">Change</th><th style="padding:10px;">User</th></tr>';
                            res.logs.forEach(l => {
                                const color = l.quantity_change > 0 ? '#10b981' : '#ef4444';
                                html += `<tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:10px;">${l.timestamp}</td>
                                    <td style="padding:10px; text-transform:capitalize;">${l.change_type}</td>
                                    <td style="padding:10px; font-weight:bold; color:${color}">${l.quantity_change > 0 ? '+' : ''}${parseFloat(l.quantity_change)}</td>
                                    <td style="padding:10px;">${l.user_name || 'System'}</td>
                                </tr>`;
                            });
                            html += '</table>';
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