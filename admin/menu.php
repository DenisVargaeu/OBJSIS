<?php
// admin/menu.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();
checkPermission('menu.php');

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

$page_title = "Menu Management";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Menu Management - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_menu.css"> <!-- Specific CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Menu Management</h2>
                    <div class="date-subtitle">Organize menu items, prices, and availability</div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="import_menu.php" class="btn btn-secondary">
                        <i class="fas fa-file-import"></i> Import JSON
                    </a>
                    <button class="btn" onclick="openAddItemModal()">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
            </header>

            <?php foreach ($categories as $category): ?>
                <div class="category-section" style="margin-bottom: 40px;">
                    <div class="category-title" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.5px; color: var(--text-main);">
                        <?= htmlspecialchars($category['name']) ?>
                        <small style="font-size: 0.75rem; opacity: 0.4; font-weight: 500; margin-left: 10px;">ID: <?= $category['id'] ?></small>
                    </div>
                    <div class="admin-menu-grid">
                        <?php if (isset($menu[$category['name']])): ?>
                            <?php foreach ($menu[$category['name']] as $item): ?>
                                <div class="stat-card" style="padding: 0; overflow: hidden; height: auto; min-height: 400px; display: flex; flex-direction: column;">
                                    <div style="position: relative; height: 180px; overflow: hidden;">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?: 'https://via.placeholder.com/300x200?text=' . urlencode($item['name']) ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                                             class="item-image-preview">
                                        <div style="position: absolute; top: 12px; left: 12px;">
                                            <span class="status-badge" style="background: <?= $item['is_available'] ? 'rgba(16, 185, 129, 0.9)' : 'rgba(239, 68, 68, 0.9)' ?>; color: white; border: none; font-size: 0.7rem; padding: 4px 10px;">
                                                <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                                        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </h3>
                                        
                                        <?php if ($item['allergens']): ?>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-triangle-exclamation" style="color: var(--warning);"></i> 
                                                <?= htmlspecialchars($item['allergens']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <p style="color: var(--primary-color); font-weight: 800; font-size: 1.2rem; margin-top: auto; margin-bottom: 20px;">
                                            <?= number_format($item['price'], 2) ?> €
                                        </p>

                                        <div class="item-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                            <button class="btn btn-secondary" style="grid-column: span 2; padding: 8px; font-size: 0.85rem;"
                                                onclick="openRecipeModal(<?= $item['id'] ?>, <?= htmlspecialchars(json_encode($item['name'])) ?>)">
                                                <i class="fas fa-list-check"></i> Manage Recipe
                                            </button>
                                            <button class="btn btn-secondary" style="padding: 8px; font-size: 0.85rem;"
                                                onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-secondary" style="padding: 8px; font-size: 0.85rem;"
                                                onclick="postAction('toggle_availability', {id: <?= $item['id'] ?>, is_available: <?= $item['is_available'] ? 0 : 1 ?>})">
                                                <i class="fas <?= $item['is_available'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i> <?= $item['is_available'] ? 'Hide' : 'Show' ?>
                                            </button>
                                            <button class="btn" style="grid-column: span 2; background-color: var(--danger); padding: 8px; font-size: 0.85rem;"
                                                onclick="if(confirm('Delete this item? Equipping recipe links will also be removed.')) postAction('delete_item', {id: <?= $item['id'] ?>})">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Multi-purpose Item Modal (Add/Edit) -->
    <div id="item-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 500px; max-width: 90%; padding: 24px; flex-direction: column; align-items: stretch;">
            <h3 id="modal-title" style="margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">Add New Item</h3>
            <form id="item-form" onsubmit="event.preventDefault(); submitItemForm(this);">
                <!-- SECURITY FIX: CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="action" id="form-action" value="add_item">
                <input type="hidden" name="id" id="item-id">
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="form-category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="form-name" required placeholder="e.g. Classic Burger">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="form-description" required style="height: 80px;"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Price (€)</label>
                        <input type="number" step="0.01" name="price" id="form-price" required>
                    </div>
                    <div class="form-group">
                        <label>Allergens</label>
                        <input type="text" name="allergens" id="form-allergens" placeholder="e.g. 1, 3, 7">
                    </div>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image_url" id="form-image-url" placeholder="https://...">
                </div>

                <div style="display:flex; gap:12px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="document.getElementById('item-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn" style="flex: 1;">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recipe Modal -->
    <div id="recipe-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1001; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 550px; max-width: 95%; padding: 24px; flex-direction: column; align-items: stretch;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 id="recipe-title" style="margin:0; font-size: 1.25rem; font-weight: 700;">Recipe Ingredients</h3>
                <button class="btn btn-secondary" onclick="closeRecipeModal()"
                    style="padding: 6px 12px; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="recipe-list" style="margin-bottom:24px; max-height:300px; overflow-y:auto; border: 1px solid var(--border-color); border-radius: 12px; padding: 10px; background: rgba(0,0,0,0.15);">
                <!-- Ingredients loaded here -->
            </div>

            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); padding: 20px; border-radius: 16px;">
                <h4 style="margin: 0 0 15px 0; font-size: 1rem; color: var(--text-main);">Add Ingredient</h4>
                <form id="add-ingredient-form" onsubmit="event.preventDefault(); submitAddIngredient(this);">
                    <!-- SECURITY FIX: CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="menu_item_id" id="recipe-menu-item-id">
                    <input type="hidden" name="action" value="add_ingredient">
                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; align-items:flex-end;">
                        <div class="form-group" style="margin:0;">
                            <label>Ingredient</label>
                            <select name="inventory_id" required>
                                <option value="">Select an item...</option>
                                <?php foreach ($inventory_items as $inv): ?>
                                    <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?>
                                        (<?= htmlspecialchars($inv['unit']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Qty</label>
                            <input type="number" step="0.01" name="quantity" required placeholder="0.00">
                        </div>
                        <button type="submit" class="btn" style="grid-column: span 2; margin-top: 10px;">
                            <i class="fas fa-plus"></i> Add to Recipe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddItemModal() {
            document.getElementById('modal-title').innerText = "Add New Item";
            document.getElementById('form-action').value = "add_item";
            document.getElementById('item-id').value = "";
            document.getElementById('item-form').reset();
            document.getElementById('item-modal').style.display = 'flex';
        }

        function openEditItemModal(item) {
            document.getElementById('modal-title').innerText = "Edit Item: " + item.name;
            document.getElementById('form-action').value = "edit_item";
            document.getElementById('item-id').value = item.id;
            
            document.getElementById('form-category').value = item.category_id;
            document.getElementById('form-name').value = item.name;
            document.getElementById('form-description').value = item.description;
            document.getElementById('form-price').value = item.price;
            document.getElementById('form-allergens').value = item.allergens || "";
            document.getElementById('form-image-url').value = item.image_url || "";
            
            document.getElementById('item-modal').style.display = 'flex';
        }

        function postAction(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            // SECURITY FIX: Add CSRF Token
            formData.append('csrf_token', OBJSIS_CSRF_TOKEN);
            for (let key in data) {
                formData.append(key, data[key]);
            }

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function submitItemForm(form) {
            const formData = new FormData(form);
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
            // SECURITY FIX: Use escHTML
            document.getElementById('recipe-title').innerHTML = "<i class='fas fa-book' style='color:var(--primary-color); margin-right:10px;'></i> Recipe: " + escHTML(itemName);
            document.getElementById('recipe-menu-item-id').value = itemId;
            document.getElementById('recipe-modal').style.display = 'flex';
            loadRecipe(itemId);
        }

        function closeRecipeModal() {
            document.getElementById('recipe-modal').style.display = 'none';
        }

        function loadRecipe(itemId) {
            const list = document.getElementById('recipe-list');
            list.innerHTML = '<p style="text-align:center; padding:20px; opacity:0.5;">Loading recipe details...</p>';

            fetch(`../api/recipe_actions.php?action=get_recipe&menu_item_id=${itemId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (res.ingredients.length === 0) {
                            list.innerHTML = '<div style="text-align:center; opacity:0.5; padding:40px;"><i class="fas fa-mortar-pestle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i> No ingredients defined.</div>';
                        } else {
                            let html = '<table class="admin-table" style="box-shadow:none; background:transparent;">';
                            res.ingredients.forEach(i => {
                                html += `
                                    <tr>
                                        <!-- SECURITY FIX: Use escHTML for ingredient name -->
                                        <td style="padding:14px 10px; border-bottom:1px solid rgba(255,255,255,0.05); font-weight:600;">${escHTML(i.ingredient_name)}</td>
                                        <td style="padding:14px 10px; border-bottom:1px solid rgba(255,255,255,0.05); text-align:right; color: var(--primary-color); font-weight:700;">${parseFloat(i.quantity_required)} ${escHTML(i.unit)}</td>
                                        <td style="padding:14px 10px; border-bottom:1px solid rgba(255,255,255,0.05); text-align:right; width:44px;">
                                            <button onclick="removeIngredient(${i.id})" style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.2); color:var(--danger); cursor:pointer; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition: var(--transition-base);">
                                                <i class="fas fa-trash-alt" style="font-size:0.8rem;"></i>
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
            if (!confirm('Remove this ingredient from recipe?')) return;
            const formData = new FormData();
            formData.append('action', 'remove_ingredient');
            formData.append('id', id);
            // SECURITY FIX: Add CSRF Token
            formData.append('csrf_token', OBJSIS_CSRF_TOKEN);

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