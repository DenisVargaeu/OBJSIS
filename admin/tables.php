<?php
// admin/tables.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Fetch Tables
$stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
$tables = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tables - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_tables.css">
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Active
                        Orders</a></li>
                <li class="nav-item"><a href="menu.php" class="nav-link"><i class="fas fa-utensils"></i> Menu Items</a>
                </li>
                <li class="nav-item"><a href="inventory.php" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a>
                </li>
                <li class="nav-item"><a href="tables.php" class="nav-link active"><i class="fas fa-chair"></i>
                        Tables</a></li>
                <li class="nav-item"><a href="shifts.php" class="nav-link"><i class="fas fa-clock"></i> Shifts</a></li>
                <li class="nav-item"><a href="coupons.php" class="nav-link"><i class="fas fa-ticket-alt"></i>
                        Coupons</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Employees</a>
                    </li>
                    <li class="nav-item"><a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>
                            Statistics</a></li>
                    <li class="nav-item"><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a>
                    </li>
                    <li class="nav-item"><a href="updates.php" class="nav-link"><i class="fas fa-sync"></i> Updates</a>
                    </li>
                    <li class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="user-profile">
                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <div
                    style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                    <?= htmlspecialchars($_SESSION['user_role']) ?>
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
            <script src="../assets/js/theme.js"></script>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 20px;">Table Layout</h2>

            <div class="tables-grid">
                <!-- Add New Table Card -->
                <div class="table-card add-table-card" onclick="openAddTableModal()">
                    <i class="fas fa-plus table-icon"></i>
                    <div class="table-title">Add Table</div>
                    <div class="table-capacity">Setup new</div>
                </div>

                <?php foreach ($tables as $table): ?>
                    <div class="table-card status-<?= $table['status'] ?>">
                        <button
                            style="position:absolute; top:10px; right:10px; background:none; border:none; color:var(--text-muted); cursor:pointer;"
                            onclick="if(confirm('Delete table?')) deleteTable(<?= $table['id'] ?>)">
                            <i class="fas fa-times"></i>
                        </button>

                        <i class="fas fa-chair table-icon"></i>
                        <div class="table-title">
                            <?= htmlspecialchars($table['name']) ?>
                        </div>
                        <div class="table-capacity">
                            <?= $table['capacity'] ?> Seats
                        </div>
                        <div
                            style="margin-top:10px; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; opacity:0.7;">
                            <?= $table['status'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Table Modal -->
    <div id="add-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 350px;">
            <h3>New Table</h3>
            <form onsubmit="event.preventDefault(); submitAddTable(this);">
                <div class="form-group">
                    <label>Table Name / Number</label>
                    <input type="text" name="name" required placeholder="Table 10">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" required value="4">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddTableModal() {
            document.getElementById('add-modal').style.display = 'flex';
        }

        function submitAddTable(form) {
            const formData = new FormData(form);
            formData.append('action', 'add_table');

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function deleteTable(id) {
            const formData = new FormData();
            formData.append('action', 'delete_table');
            formData.append('id', id);

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }
    </script>
</body>

</html>