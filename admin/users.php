<?php
// admin/users.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access Control
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY role, name");
$users = $stmt->fetchAll();
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
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display:flex; gap: 30px; align-items: flex-start;">

                <!-- List Section -->
                <div style="flex: 2;">
                    <h2 style="margin-bottom: 20px;">Staff List</h2>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td style="font-weight:600;">
                                            <?= htmlspecialchars($user['name']) ?>
                                        </td>
                                        <td><span class="user-role-badge role-<?= $user['role'] ?>">
                                                <?= $user['role'] ?>
                                            </span></td>
                                        <td style="color:var(--text-muted); font-size:0.9rem;">
                                            <?= date('Y-m-d', strtotime($user['created_at'])) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-secondary"
                                                style="padding: 5px 10px; font-size: 0.8rem; margin-right:5px;"
                                                onclick="openEditModal(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>', '<?= $user['role'] ?>')">Edit</button>

                                            <?php if ($user['role'] !== 'admin' || $user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn"
                                                    style="padding: 5px 10px; font-size: 0.8rem; background: var(--danger);"
                                                    onclick="if(confirm('Remove this user?')) deleteUser(<?= $user['id'] ?>)">Remove</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Form Section -->
                <div style="flex: 1;">
                    <div class="add-user-form">
                        <h3 style="margin-bottom: 15px;">Add New Employee</h3>
                        <form onsubmit="event.preventDefault(); addUser(this);">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" required placeholder="e.g. John Doe">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role">
                                    <option value="waiter">Waiter</option>
                                    <option value="cook">Cook</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Set PIN (4-6 digits)</label>
                                <input type="number" name="pin" required placeholder="1234" maxlength="6">
                            </div>
                            <button type="submit" class="btn" style="width:100%;">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="edit-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 400px; max-width: 90%;">
            <h3>Edit Employee</h3>
            <form onsubmit="event.preventDefault(); submitEditUser(this);">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit-role">
                        <option value="waiter">Waiter</option>
                        <option value="cook">Cook</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>New PIN (Leave empty to keep existing)</label>
                    <input type="number" name="pin" placeholder="Enter new PIN" maxlength="6">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('edit-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn">Update</button>
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

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function openEditModal(id, name, role) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-role').value = role;
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