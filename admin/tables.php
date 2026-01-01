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
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

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