<?php
// admin/users.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access Control
checkPermission('users.php');

// Fetch Roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

// Fetch Users with Role Names
$stmt = $pdo->query("
    SELECT u.*, r.display_name as role_name, r.name as role_slug 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY r.id, u.name
");
$users = $stmt->fetchAll();

$page_title = "Employee Management";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee Management - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_users.css"> <!-- Specific CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .user-role-badge {
            background: var(--card-bg-glass);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        .user-role-badge.role-admin { border-color: #f59e0b; color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .user-role-badge.role-manager { border-color: #3b82f6; color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
        .user-role-badge.role-waiter { border-color: #10b981; color: #10b981; background: rgba(16, 185, 129, 0.1); }
        .user-role-badge.role-cook { border-color: #ec4899; color: #ec4899; background: rgba(236, 72, 153, 0.1); }
        
        .admin-table tr {
            transition: background 0.2s ease;
        }
        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Employee Management</h2>
                    <div class="date-subtitle">Manage staff access and permissions</div>
                </div>
                <div class="header-actions">
                     <button class="btn btn-secondary" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                </div>
            </header>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: flex-start;">

                <!-- List Section -->
                <div class="admin-table-container card-glass">
                    <div style="padding: 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Staff List</h3>
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><?= count($users) ?> Total Employees</span>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td style="font-weight:600; color: var(--text-main);">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--text-muted);">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-role-badge role-<?= $user['role_slug'] ?>" style="padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($user['role_name'] ?: 'No Role') ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.9rem;">
                                        <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 8px;">
                                            <button class="btn btn-secondary"
                                                style="padding: 8px 12px; font-size: 0.8rem;"
                                                onclick="openEditModal(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>', '<?= $user['role_id'] ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>

                                            <?php if ($user['role_slug'] !== 'admin' || $user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn"
                                                    style="padding: 8px 12px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);"
                                                    onclick="if(confirm('Remove this user?')) deleteUser(<?= $user['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add Form Section -->
                <div class="stat-card card-glass" style="flex-direction: column; align-items: stretch; padding: 24px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700;">Add New Employee</h3>
                    <form onsubmit="event.preventDefault(); addUser(this);">
                        <!-- SECURITY FIX: CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required placeholder="e.g. John Doe">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Set PIN (4-6 digits)</label>
                            <input type="number" name="pin" required placeholder="1234" maxlength="6">
                        </div>
                        <button type="submit" class="btn" style="width:100%; margin-top: 10px;">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card card-glass" style="width: 400px; max-width: 90%; flex-direction: column; align-items: stretch; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Edit Employee</h3>
                <button type="button" style="background: none; border: none; color: var(--text-muted); cursor: pointer;" onclick="document.getElementById('edit-modal').style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="event.preventDefault(); submitEditUser(this);">
                <!-- SECURITY FIX: CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" id="edit-role-id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>New PIN (Keep empty to stay same)</label>
                    <input type="number" name="pin" placeholder="Enter new PIN" maxlength="6">
                </div>
                <div style="display:flex; gap:12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="document.getElementById('edit-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn" style="flex: 1;">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addUser(form) {
            const formData = new FormData(form);
            formData.append('action', 'add_user');

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function deleteUser(id) {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('id', id);
            // SECURITY FIX: Add CSRF Token
            formData.append('csrf_token', OBJSIS_CSRF_TOKEN);

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function openEditModal(id, name, roleId) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-role-id').value = roleId;
            document.getElementById('edit-modal').style.display = 'flex';
        }

        function submitEditUser(form) {
            const formData = new FormData(form);
            formData.append('action', 'edit_user');

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