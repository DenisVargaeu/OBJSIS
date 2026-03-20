<?php
// admin/tables.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Fetch Tables
$stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
$tables = $stmt->fetchAll();

$page_title = "Table Layout Management";
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
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Table Layout</h2>
                    <div class="date-subtitle">Setup and manage your restaurant floor plan</div>
                </div>
                <button class="btn" onclick="openAddTableModal()">
                    <i class="fas fa-plus"></i> Add Table
                </button>
            </header>

            <div class="tables-grid">
                <!-- Add New Table Card (Placeholder for visual add) -->
                <div class="table-card add-table-card" onclick="openAddTableModal()" style="border: 2px dashed var(--border-color); background: rgba(255,255,255,0.02); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 180px; cursor: pointer; border-radius: 20px; transition: var(--transition-base);">
                    <i class="fas fa-plus" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 12px; opacity: 0.6;"></i>
                    <div style="font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;">Add New Table</div>
                </div>

                <?php foreach ($tables as $table): ?>
                    <div class="table-card status-<?= $table['status'] ?>" style="position: relative; padding: 30px 20px; text-align: center; border-radius: 20px; min-height: 180px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <button
                            style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.2); border:none; color:var(--text-muted); cursor:pointer; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; transition: var(--transition-base);"
                            onclick="if(confirm('Delete table?')) deleteTable(<?= $table['id'] ?>)"
                            onmouseover="this.style.color='var(--danger)'; this.style.background='rgba(239, 68, 68, 0.1)'"
                            onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(0,0,0,0.2)'">
                            <i class="fas fa-times" style="font-size: 0.8rem;"></i>
                        </button>

                        <div style="background: rgba(255,255,255,0.05); width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <i class="fas fa-chair" style="font-size: 1.5rem; color: <?= $table['status'] === 'available' ? 'var(--success)' : 'var(--primary-color)' ?>;"></i>
                        </div>
                        
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">
                            <?= htmlspecialchars($table['name']) ?>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">
                            <?= $table['capacity'] ?> Seats
                        </div>
                        
                        <div style="margin-top:16px; font-size:0.65rem; text-transform:uppercase; letter-spacing:1.5px; font-weight: 800; padding: 4px 12px; border-radius: 20px; background: rgba(255,255,255,0.05); color: var(--text-dim);">
                            <?= $table['status'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Table Modal -->
    <div id="add-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 380px; padding: 24px; flex-direction: column; align-items: stretch;">
            <h3 style="margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">Add New Table</h3>
            <form onsubmit="event.preventDefault(); submitAddTable(this);">
                <div class="form-group">
                    <label>Table Name / Number</label>
                    <input type="text" name="name" required placeholder="e.g. Table 10">
                </div>
                <div class="form-group">
                    <label>Seating Capacity</label>
                    <input type="number" name="capacity" required value="4" min="1">
                </div>
                <div style="display:flex; gap:12px; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn" style="flex: 1;">Create Table</button>
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