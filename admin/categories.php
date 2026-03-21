<?php
// admin/categories.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access: Categories permission
checkPermission('manage_menu');

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
$categories = $stmt->fetchAll();

$page_title = "Category Management";
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
        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .drag-handle {
            cursor: grab;
            color: var(--text-dim);
            margin-right: 15px;
            font-size: 1.1rem;
        }
        .category-info h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .category-info span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
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
                    <div class="date-subtitle">Organize menu navigation and groups</div>
                </div>
                <button class="btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </header>

            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <div class="stat-card" style="flex-direction: row; justify-content: space-between; align-items: center; padding: 20px;">
                        <div style="display:flex; align-items:center;">
                            <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                            <div class="category-info">
                                <h3><?= htmlspecialchars($cat['name']) ?></h3>
                                <span>Order Weight: <?= $cat['sort_order'] ?></span>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.85rem;" onclick="openEditModal(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', <?= $cat['sort_order'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn" style="background:var(--danger); padding: 8px 12px; font-size: 0.85rem;" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="category-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 400px; flex-direction: column; align-items: stretch; padding: 24px;">
            <h3 id="modal-title" style="margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">Add Category</h3>
            <form id="category-form" onsubmit="event.preventDefault(); submitForm(this);">
                <input type="hidden" name="action" id="form-action" value="add_category">
                <input type="hidden" name="id" id="cat-id">
                
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" id="cat-name" required placeholder="e.g. Burgers">
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" id="cat-order" value="0">
                </div>

                <div style="display:flex; gap:12px; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('category-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn" style="flex: 1;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').innerText = "Add Category";
            document.getElementById('form-action').value = "add_category";
            document.getElementById('cat-id').value = "";
            document.getElementById('category-form').reset();
            document.getElementById('category-modal').style.display = 'flex';
        }

        function openEditModal(id, name, order) {
            document.getElementById('modal-title').innerText = "Edit Category";
            document.getElementById('form-action').value = "edit_category";
            document.getElementById('cat-id').value = id;
            document.getElementById('cat-name').value = name;
            document.getElementById('cat-order').value = order;
            document.getElementById('category-modal').style.display = 'flex';
        }

        function submitForm(form) {
            const formData = new FormData(form);
            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function deleteCategory(id) {
            if(!confirm('Deleting a category will NOT delete menu items inside it (they will become uncategorized). Continue?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_category');
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
