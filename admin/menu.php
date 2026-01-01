<?php
// admin/menu.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
$categories = $stmt->fetchAll();

// Fetch Inventory for Recipe Modal
$stmt_inv = $pdo->query("SELECT id, name, unit FROM inventory ORDER BY name ASC");
$inventory_items = $stmt_inv->fetchAll();

$menu = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ?");
    $stmt->execute([$cat['id']]);
    $menu[$cat['name']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Menu Management - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_menu.css"> <!-- Specific CSS -->
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
                <li class="nav-item"><a href="menu.php" class="nav-link active"><i class="fas fa-utensils"></i> Menu
                        Items</a></li>
                <li class="nav-item"><a href="inventory.php" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a>
                </li>
                <li class="nav-item"><a href="tables.php" class="nav-link"><i class="fas fa-chair"></i> Tables</a></li>
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
            <div class="menu-management-header">
                <div>
                    <h2 style="margin:0;">Menu Management</h2>
                    <p style="color:var(--text-muted); margin:0;">Organize categories and items</p>
                </div>
                <button class="btn" onclick="openAddItemModal()">+ Add New Item</button>
            </div>

            <?php foreach ($categories as $category): ?>
                <div class="category-section">
                    <div class="category-title">
                        <?= htmlspecialchars($category['name']) ?>
                        <small style="font-size: 0.8rem; opacity: 0.5;">ID:
                            <?= $category['id'] ?>
                        </small>
                    </div>
                    <div class="admin-menu-grid">
                        <?php if (isset($menu[$category['name']])): ?>
                            <?php foreach ($menu[$category['name']] as $item): ?>
                                <div class="admin-menu-card">
                                    <span
                                        class="badge-availability <?= $item['is_available'] ? 'available-true' : 'available-false' ?>">
                                        <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                    </span>

                                    <img src="<?= htmlspecialchars($item['image_url']) ?: 'https://via.placeholder.com/150' ?>"
                                        class="item-image-preview">

                                    <h3 style="font-size:1.1rem; margin-bottom:5px;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </h3>
                                    <p style="color:var(--primary-color); font-weight:bold;">
                                        <?= number_format($item['price'], 2) ?> €
                                    </p>

                                    <div class="item-actions" style="flex-wrap: wrap; gap: 5px;">
                                        <button class="btn btn-secondary"
                                            style="width:100%; font-size:0.8rem; background-color: var(--primary-color); border:none;"
                                            onclick="openRecipeModal(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')">
                                            <i class="fas fa-list-check"></i> Recipe
                                        </button>
                                        <button class="btn btn-secondary" style="flex:1; font-size:0.8rem;"
                                            onclick="postAction('toggle_availability', {id: <?= $item['id'] ?>, is_available: <?= $item['is_available'] ? 0 : 1 ?>})">
                                            <?= $item['is_available'] ? 'Disable' : 'Enable' ?>
                                        </button>
                                        <button class="btn" style="background-color: var(--danger); flex:1; font-size:0.8rem;"
                                            onclick="if(confirm('Delete?')) postAction('delete_item', {id: <?= $item['id'] ?>})">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Simple Add Modal (Hidden by default, simplistic implementation for MVP) -->
    <div id="add-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 400px; max-width: 90%;">
            <h3>Add New Item</h3>
            <form onsubmit="event.preventDefault(); submitAddItem(this);">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Description</label><input type="text" name="description" required></div>
                <div class="form-group"><label>Price (€)</label><input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group"><label>Image URL</label><input type="text" name="image_url"
                        placeholder="https://..."></div>

                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recipe Modal -->
    <div id="recipe-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1001; justify-content:center; align-items:center;">
        <div class="card" style="width: 500px; max-width: 95%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 id="recipe-title">Recipe Ingredients</h3>
                <button class="btn btn-secondary" onclick="closeRecipeModal()"
                    style="padding: 5px 10px;">&times;</button>
            </div>

            <div id="recipe-list" style="margin-bottom:20px; max-height:300px; overflow-y:auto;">
                <!-- Ingredients loaded here -->
            </div>

            <hr style="opacity:0.1; margin:20px 0;">

            <h4>Add Ingredient</h4>
            <form id="add-ingredient-form" onsubmit="event.preventDefault(); submitAddIngredient(this);">
                <input type="hidden" name="menu_item_id" id="recipe-menu-item-id">
                <input type="hidden" name="action" value="add_ingredient">
                <div class="form-group" style="display:flex; gap:10px; align-items:flex-end;">
                    <div style="flex:2;">
                        <label>Ingredient</label>
                        <select name="inventory_id" required>
                            <option value="">Select...</option>
                            <?php foreach ($inventory_items as $inv): ?>
                                <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?>
                                    (<?= htmlspecialchars($inv['unit']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Qty</label>
                        <input type="number" step="0.01" name="quantity" required placeholder="0.00">
                    </div>
                    <button type="submit" class="btn">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddItemModal() {
            document.getElementById('add-modal').style.display = 'flex';
        }

        function postAction(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (let key in data) { // Changed 'const' to 'let' as per instruction, though 'const' is fine here.
                formData.append(key, data[key]);
            }

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function submitAddItem(form) {
            const formData = new FormData(form);
            formData.append('action', 'add_item');

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        let currentMenuItemId = null;

        function openRecipeModal(itemId, itemName) {
            currentMenuItemId = itemId;
            document.getElementById('recipe-title').innerText = "Recipe: " + itemName;
            document.getElementById('recipe-menu-item-id').value = itemId;
            document.getElementById('recipe-modal').style.display = 'flex';
            loadRecipe(itemId);
        }

        function closeRecipeModal() {
            document.getElementById('recipe-modal').style.display = 'none';
        }

        function loadRecipe(itemId) {
            const list = document.getElementById('recipe-list');
            list.innerHTML = '<p style="text-align:center; opacity:0.5;">Loading...</p>';

            fetch(`../api/recipe_actions.php?action=get_recipe&menu_item_id=${itemId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (res.ingredients.length === 0) {
                            list.innerHTML = '<p style="text-align:center; opacity:0.5; padding:20px;">No ingredients defined yet.</p>';
                        } else {
                            let html = '<table style="width:100%; border-collapse:collapse;">';
                            res.ingredients.forEach(i => {
                                html += `
                                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                        <td style="padding:10px 0;">${i.ingredient_name}</td>
                                        <td style="padding:10px 0; text-align:right;">${parseFloat(i.quantity_required)} ${i.unit}</td>
                                        <td style="padding:10px 0; text-align:right; width:40px;">
                                            <button onclick="removeIngredient(${i.id})" style="background:none; border:none; color:var(--danger); cursor:pointer;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            html += '</table>';
                            list.innerHTML = html;
                        }
                    } else {
                        alert(res.message);
                    }
                });
        }

        function submitAddIngredient(form) {
            const formData = new FormData(form);
            fetch('../api/recipe_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        form.reset();
                        document.getElementById('recipe-menu-item-id').value = currentMenuItemId;
                        loadRecipe(currentMenuItemId);
                    } else {
                        alert(res.message);
                    }
                });
        }

        function removeIngredient(id) {
            if (!confirm('Remove this ingredient?')) return;
            const formData = new FormData();
            formData.append('action', 'remove_ingredient');
            formData.append('id', id);

            fetch('../api/recipe_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) loadRecipe(currentMenuItemId);
                    else alert(res.message);
                });
        }
    </script>
</body>

</html>