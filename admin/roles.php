<?php
// admin/roles.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();
checkPermission('manage_roles');

$page_title = "Role Management";

// Fetch all roles with user counts and permissions
$roles = $pdo->query("
  SELECT r.*, COUNT(u.id) as user_count
  FROM roles r
  LEFT JOIN users u ON u.role_id = r.id
  GROUP BY r.id
  ORDER BY r.id
")->fetchAll();

// Fetch all permissions
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY id")->fetchAll();

// Fetch role-permission mappings for all roles
$role_perms = [];
$pdo->query("SELECT rp.role_id, rp.permission_id, p.name as perm_name, p.description as perm_desc FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id")->execute();
$active_perm_rows = $pdo->query("SELECT rp.role_id, rp.permission_id, p.name as perm_name, p.description as perm_desc FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id")->fetchAll();
foreach ($active_perm_rows as $row) {
  $role_perms[$row['role_id']][] = $row;
}

// Page-to-permission map from functions.php (user-facing menu groups)
$page_groups = [
  'Dashboard' => ['view_dashboard'],
  'Kitchen Display' => ['view_orders'],
  'Orders List' => ['view_orders'],
  'Manage Tables' => ['view_orders'],
  'New Order' => ['view_orders'],
  'Menu Management' => ['manage_menu'],
  'Categories' => ['manage_menu'],
  'Inventory' => ['view_inventory', 'manage_inventory'],
  'Coupons' => ['manage_menu'],
  'Staff Management' => ['manage_users'],
  'Role Management' => ['manage_roles'],
  'Analytics' => ['view_reports'],
  'Reports' => ['view_reports'],
  'Order History' => ['view_orders'],
  'System Updates' => ['manage_settings'],
  'Settings' => ['manage_settings'],
  'Addons' => ['manage_system'],
];

$perm_names = [];
foreach ($permissions as $p) {
  $perm_names[$p['name']] = $p['description'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Role Management - OBJSIS</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<?= getCustomStyles() ?>
<style>
.roles-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px; }

.role-card {
  background: var(--card-bg-glass, rgba(15,23,42,0.8));
  border: 1px solid var(--border-color, rgba(255,255,255,0.08));
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}
.role-card:hover { border-color: var(--primary-color, #f97316); box-shadow: 0 0 30px rgba(249,115,22,0.08); }
.role-card.protected { border-color: rgba(239,68,68,0.3); opacity: 0.95; }

.role-card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.06));
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  background: rgba(255,255,255,0.015);
}
.role-card-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--text-main, #f8fafc);
}
.role-card-header .role-users-badge {
  font-size: 0.78rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(255,255,255,0.05);
  color: var(--text-muted, #94a3b8);
  white-space: nowrap;
}

.role-perms-section { padding: 16px 24px; flex: 1; }
.role-perms-section h4 {
  margin: 0 0 12px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text-muted, #64748b);
}

.perm-tag {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  margin: 0 4px 6px 0;
  background: rgba(255,255,255,0.04);
  color: var(--text-muted, #94a3b8);
  border: 1px solid rgba(255,255,255,0.06);
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
}
.perm-tag:hover { background: rgba(255,255,255,0.08); }
.perm-tag.active {
  background: rgba(249,115,22,0.15);
  color: #f97316;
  border-color: rgba(249,115,22,0.35);
}

.page-group {
  margin-bottom: 14px;
}
.page-group-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--text-secondary, #cbd5e1);
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.page-group-label i { font-size: 0.7rem; color: var(--text-muted, #64748b); width: 14px; text-align: center; }

.role-card-actions {
  padding: 14px 24px;
  border-top: 1px solid var(--border-color, rgba(255,255,255,0.06));
  display: flex;
  gap: 8px;
}
.role-card-actions .btn { flex: 1; padding: 8px; font-size: 0.8rem; }
.role-card-actions .btn i { margin-right: 4px; }

.btn-icon-sm {
  padding: 8px 12px;
  font-size: 0.8rem;
}

.modal-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(2, 6, 23, 0.88);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(8px);
}
.modal-overlay.active { display: flex; }
.modal-card {
  background: var(--card-bg-glass, #0f172a);
  border: 1px solid var(--border-color, rgba(255,255,255,0.1));
  border-radius: 20px;
  padding: 28px;
  width: 480px;
  max-width: 94%;
  max-height: 85vh;
  overflow-y: auto;
}
.modal-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.modal-card-header h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main, #f8fafc); }
.modal-close {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 1.1rem;
}

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted, #94a3b8); margin-bottom: 6px; }
.form-group input, .form-group select {
  width: 100%;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid var(--border-color, rgba(255,255,255,0.1));
  background: rgba(255,255,255,0.03);
  color: var(--text-main, #f8fafc);
  font-size: 0.9rem;
  box-sizing: border-box;
}
.form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-color, #f97316); }

.perm-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.perm-grid .perm-tag { padding: 8px 12px; font-size: 0.78rem; }

.page-assign-group {
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.page-assign-group:last-child { border-bottom: none; }
.page-assign-group label.pg-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-secondary, #e2e8f0);
  cursor: pointer;
  user-select: none;
}
.page-assign-group input[type="checkbox"] { accent-color: var(--primary-color, #f97316); width: 16px; height: 16px; }
.page-assign-group .pg-desc { font-size: 0.72rem; color: var(--text-muted, #64748b); font-weight: 400; margin-top: 2px; }

.alert-box {
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  margin-bottom: 16px;
}
.alert-box.error { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
.alert-box.success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }

.row-btns { display: flex; gap: 8px; }

@media (max-width: 800px) {
  .roles-container { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="app-container">
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
  <header class="page-header">
    <div class="page-title-group">
      <h2>Role Management</h2>
      <div class="date-subtitle">Manage roles and page-access permissions</div>
    </div>
    <div class="header-actions">
      <button class="btn" onclick="openAddRoleModal()"><i class="fas fa-plus"></i> New Role</button>
    </div>
  </header>

  <div id="main-alert"></div>

  <div class="roles-container" id="roles-container">
    <?php foreach ($roles as $role): $rid = $role['id']; $perms = $role_perms[$rid] ?? []; $perm_ids_arr = array_column($perms, 'permission_id'); $is_admin = $role['name'] === 'admin'; ?>
    <div class="role-card<?= $is_admin ? ' protected' : '' ?>" id="role-card-<?= $rid ?>">
      <div class="role-card-header">
        <div>
          <h3><i class="fas fa-shield-halved" style="color: var(--primary-color); margin-right: 6px;"></i><?= htmlspecialchars($role['display_name']) ?></h3>
          <div style="font-size:0.75rem; color: var(--text-muted, #64748b); font-weight:600;"><?= htmlspecialchars($role['name']) ?></div>
        </div>
        <span class="role-users-badge"><i class="fas fa-users"></i> <?= $role['user_count'] ?></span>
      </div>

      <div class="role-perms-section">
        <h4>Active Permissions (<?= count($perms) ?>)</h4>
        <div>
          <?php if (count($perms) === 0): ?>
            <span style="font-size:0.8rem; color: var(--text-muted, #64748b);">No permissions assigned</span>
          <?php else: foreach ($perms as $rp): ?>
            <span class="perm-tag active" title="<?= htmlspecialchars($rp['perm_name']) ?>: <?= htmlspecialchars($rp['perm_desc']) ?>">
              <?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $rp['perm_name']))) ?>
              <?php if (!$is_admin): ?><i class="fas fa-times" style="font-size:0.65rem; margin-left:3px; opacity:0.7; cursor:pointer;" onclick="togglePerm(<?= $rid ?>, <?= $rp['permission_id'] ?>)"></i><?php endif; ?>
            </span>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="role-card-actions">
        <?php if (!$is_admin): ?>
          <button class="btn btn-secondary btn-icon-sm" onclick="openEditModal(<?= $rid ?>, '<?= addslashes($role['display_name']) ?>')"><i class="fas fa-edit"></i> Edit</button>
          <button class="btn btn-icon-sm" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);" onclick="openPermModal(<?= $rid ?>, '<?= addslashes($role['display_name']) ?>')"><i class="fas fa-key"></i> Permissions</button>
          <button class="btn btn-icon-sm" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);" onclick="confirmDelete(<?= $rid ?>, '<?= addslashes($role['display_name']) ?>', <?= $role['user_count'] ?>)"><i class="fas fa-trash"></i></button>
        <?php else: ?>
          <span class="btn btn-icon-sm" style="opacity:0.5; cursor:default; background:rgba(255,255,255,0.03); color: var(--text-muted);"><i class="fas fa-lock"></i> Protected</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
</div>

<!-- Add Role Modal -->
<div id="add-role-modal" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-card-header">
      <h3><i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>Create New Role</h3>
      <button class="modal-close" onclick="closeModal('add-role-modal')"><i class="fas fa-times"></i></button>
    </div>
    <div id="add-role-alert"></div>
    <form onsubmit="addRole(event)">
      <div class="form-group">
        <label>Display Name (shown to users)</label>
        <input type="text" name="display_name" required placeholder="e.g. Manager, Chef, Host">
      </div>
      <div class="form-group">
        <label>System Name (slug, auto-generated if empty)</label>
        <input type="text" name="role_name" placeholder="e.g. manager (lowercase, no spaces)">
      </div>
      <div class="form-group">
        <label>Grant Permissions</label>
        <div class="perm-grid" id="add-perm-grid">
          <?php foreach ($permissions as $p): ?>
            <span class="perm-tag" data-pid="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" onclick="this.classList.toggle('active')">
              <i class="fas fa-key" style="font-size:0.65rem; margin-right:3px; opacity:0.6;"></i><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $p['name']))) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="row-btns">
        <button type="button" class="btn btn-secondary" onclick="closeModal('add-role-modal')">Cancel</button>
        <button type="submit" class="btn"><i class="fas fa-check"></i> Create Role</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Role Modal -->
<div id="edit-role-modal" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-card-header">
      <h3><i class="fas fa-edit" style="color: var(--primary-color); margin-right: 8px;"></i>Edit Role</h3>
      <button class="modal-close" onclick="closeModal('edit-role-modal')"><i class="fas fa-times"></i></button>
    </div>
    <div id="edit-role-alert"></div>
    <form onsubmit="editRole(event)">
      <input type="hidden" name="id" id="edit-role-id">
      <div class="form-group">
        <label>Display Name</label>
        <input type="text" name="display_name" id="edit-display-name" required>
      </div>
      <div class="row-btns">
        <button type="button" class="btn btn-secondary" onclick="closeModal('edit-role-modal')">Cancel</button>
        <button type="submit" class="btn"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Permissions Modal -->
<div id="perm-modal" class="modal-overlay">
  <div class="modal-card" style="width:560px;">
    <div class="modal-card-header">
      <h3><i class="fas fa-key" style="color: var(--primary-color); margin-right: 8px;"></i>Edit Permissions: <span id="perm-modal-title" style="color:var(--primary-color);"></span></h3>
      <button class="modal-close" onclick="closeModal('perm-modal')"><i class="fas fa-times"></i></button>
    </div>
    <div id="perm-modal-alert"></div>

    <h4 style="font-size:0.8rem; font-weight:800; color: var(--text-muted, #64748b); text-transform:uppercase; letter-spacing:0.6px; margin: 0 0 14px;">Menu Pages Access</h4>
    <div id="page-assign-section">
      <?php $all_permission_ids = array_column($permissions, 'id'); $pid_to_name = []; foreach ($permissions as $p) $pid_to_name[$p['name']] = $p['id']; ?>
      <?php foreach ($page_groups as $label => $perm_needed): $required = array_intersect($perm_needed, array_column($permissions, 'name')); $perm_id_list = []; foreach ($required as $rn) if (isset($pid_to_name[$rn])) $perm_id_list[] = $pid_to_name[$rn]; ?>
      <?php if (!empty($perm_id_list)): ?>
      <div class="page-assign-group">
        <label class="pg-label">
          <input type="checkbox" class="page-group-cb" data-perms="<?= implode(',', $perm_id_list) ?>" onchange="togglePageGroup(this)">
          <div>
            <?= htmlspecialchars($label) ?>
            <div class="pg-desc">Requires: <?= htmlspecialchars(implode(', ', array_map(fn($x)=>ucwords(str_replace(['_','-'],' ',$x)), $perm_needed))) ?></div>
          </div>
        </label>
      </div>
      <?php endif; endforeach; ?>
    </div>

    <h4 style="font-size:0.8rem; font-weight:800; color: var(--text-muted, #64748b); text-transform:uppercase; letter-spacing:0.6px; margin: 16px 0 14px;">Granular Permissions</h4>
    <div class="perm-grid" id="perm-modal-grid">
      <?php foreach ($permissions as $p): $pid = $p['id']; ?>
        <span class="perm-tag" data-pid="<?= $pid ?>" onclick="this.classList.toggle('active')" title="<?= htmlspecialchars($p['description']) ?>">
          <i class="fas fa-key" style="font-size:0.65rem; margin-right:3px; opacity:0.6;"></i><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $p['name']))) ?>
        </span>
      <?php endforeach; ?>
    </div>

    <div class="row-btns" style="margin-top:20px;">
      <button class="btn btn-secondary" onclick="closeModal('perm-modal')">Cancel</button>
      <button class="btn" onclick="savePermissions()"><i class="fas fa-save"></i> Save Permissions</button>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div id="delete-modal" class="modal-overlay">
  <div class="modal-card" style="width:400px; text-align:center;">
    <div style="font-size:2.5rem; color:#ef4444; margin-bottom:12px;"><i class="fas fa-exclamation-triangle"></i></div>
    <h3 style="margin:0 0 8px;">Delete Role</h3>
    <p id="delete-msg" style="color: var(--text-muted); font-size:0.9rem; margin-bottom:20px;"></p>
    <input type="hidden" id="delete-role-id">
    <div class="row-btns" style="justify-content:center;">
      <button class="btn btn-secondary" onclick="closeModal('delete-modal')">Cancel</button>
      <button class="btn" style="background:#ef4444;" onclick="deleteRole()"><i class="fas fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<script>
const rolesData = <?= json_encode(array_map(fn($r)=>['id'=>$r['id'],'name'=>$r['name'],'display_name'=>$r['display_name'],'user_count'=>$r['user_count']], $roles)) ?>;
let currentPermRoleId = null;

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function openAddRoleModal() {
  document.getElementById('add-role-alert').innerHTML = '';
  document.querySelector('#add-role-modal [name="display_name"]').value = '';
  document.querySelector('#add-role-modal [name="role_name"]').value = '';
  document.querySelectorAll('#add-perm-grid .perm-tag').forEach(t => t.classList.remove('active'));
  document.getElementById('add-role-modal').classList.add('active');
}

function openEditModal(id, display_name) {
  document.getElementById('edit-role-id').value = id;
  document.getElementById('edit-display-name').value = display_name;
  document.getElementById('edit-role-alert').innerHTML = '';
  document.getElementById('edit-role-modal').classList.add('active');
}

function openPermModal(id, role_name) {
  currentPermRoleId = id;
  document.getElementById('perm-modal-title').textContent = role_name;
  document.getElementById('perm-modal-alert').innerHTML = '';

  // Grab current role permissions via inline perms info
  const card = document.getElementById('role-card-' + id);
  const activeTags = card.querySelectorAll('.role-perms-section .perm-tag.active');
  const activePids = new Set();
  activeTags.forEach(t => {
    const match = t.getAttribute('onclick');
    if (match) {
      const m = match.match(/togglePerm\((\d+),\s*(\d+)\)/);
      if (m) activePids.add(parseInt(m[2]));
    }
  });

  document.querySelectorAll('#perm-modal-grid .perm-tag').forEach(t => {
    t.classList.toggle('active', activePids.has(parseInt(t.dataset.pid)));
  });

  document.querySelectorAll('#page-assign-section .page-group-cb').forEach(cb => {
    const needed = cb.dataset.perms.split(',').map(Number);
    const allActive = needed.every(pid => activePids.has(pid));
    cb.checked = allActive;
  });

  document.getElementById('perm-modal').classList.add('active');
}

function togglePageGroup(cb) {
  const needed = cb.dataset.perms.split(',').map(Number);
  const gridTags = document.querySelectorAll('#perm-modal-grid .perm-tag');
  gridTags.forEach(t => {
    const pid = parseInt(t.dataset.pid);
    if (needed.includes(pid)) {
      t.classList.toggle('active', cb.checked);
    }
  });
}

function addRole(e) {
  e.preventDefault();
  const alertBox = document.getElementById('add-role-alert');
  const form = e.target;
  const fd = new FormData(form);
  fd.append('action', 'add_role');

  const selectedPerms = [];
  document.querySelectorAll('#add-perm-grid .perm-tag.active').forEach(t => {
    selectedPerms.push(t.dataset.pid);
  });

  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);

  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        fd.append('permission_ids', JSON.stringify(selectedPerms));
        fd.set('id', res.id);
        fd.set('action', 'update_role_permissions');
        return fetch('../api/admin_actions.php', { method: 'POST', body: fd });
      }
      throw new Error(res.message || 'Failed');
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alertBox.innerHTML = '<div class="alert-box success">Role created successfully!</div>';
        setTimeout(() => location.reload(), 800);
      } else {
        alertBox.innerHTML = '<div class="alert-box error">' + res.message + '</div>';
      }
    })
    .catch(err => {
      alertBox.innerHTML = '<div class="alert-box error">' + err.message + '</div>';
    });
}

function editRole(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'edit_role');
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      const alertBox = document.getElementById('edit-role-alert');
      if (res.success) {
        alertBox.innerHTML = '<div class="alert-box success">Role updated!</div>';
        setTimeout(() => location.reload(), 500);
      } else {
        alertBox.innerHTML = '<div class="alert-box error">' + res.message + '</div>';
      }
    });
}

function togglePerm(roleId, permId) {
  // Fetch current state from the card
  const card = document.getElementById('role-card-' + roleId);
  const tag = card.querySelector(`.role-perms-section .perm-tag[onclick*="${permId}"]`) ||
              [...card.querySelectorAll('.role-perms-section .perm-tag.active')].find(t => t.getAttribute('onclick').includes(String(permId)));
  const willAdd = !(tag && tag.classList.contains('active'));

  const fd = new FormData();
  fd.append('action', 'update_role_permissions');
  fd.append('role_id', roleId);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);

  // Determine current active perms
  const activePerms = [];
  card.querySelectorAll('.role-perms-section .perm-tag.active').forEach(t => {
    const m = t.getAttribute('onclick')?.match(/togglePerm\((\d+),\s*(\d+)\)/);
    if (m) activePerms.push(m[2]);
  });
  if (willAdd && !activePerms.includes(String(permId))) {
    activePerms.push(String(permId));
  } else if (!willAdd) {
    const idx = activePerms.indexOf(String(permId));
    if (idx >= 0) activePerms.splice(idx, 1);
  }
  fd.append('permission_ids', JSON.stringify(activePerms));

  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const tag2 = card.querySelector(`.perm-tag[onclick*="${permId}"]`);
        if (tag2) tag2.classList.toggle('active', willAdd);
      }
    });
}

function savePermissions() {
  const selectedPerms = [];
  document.querySelectorAll('#perm-modal-grid .perm-tag.active').forEach(t => {
    selectedPerms.push(t.dataset.pid);
  });
  const fd = new FormData();
  fd.append('action', 'update_role_permissions');
  fd.append('role_id', currentPermRoleId);
  fd.append('permission_ids', JSON.stringify(selectedPerms));
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      const alertBox = document.getElementById('perm-modal-alert');
      if (res.success) {
        alertBox.innerHTML = '<div class="alert-box success">Permissions saved!</div>';
        setTimeout(() => location.reload(), 600);
      } else {
        alertBox.innerHTML = '<div class="alert-box error">' + res.message + '</div>';
      }
    });
}

function confirmDelete(id, name, user_count) {
  document.getElementById('delete-role-id').value = id;
  document.getElementById('delete-msg').textContent =
    user_count > 0
      ? `Role "${name}" has ${user_count} user(s). Those users will lose their role assignment. Continue?`
      : `Delete role "${name}" permanently?`;
  document.getElementById('delete-modal').classList.add('active');
}

function deleteRole() {
  const id = document.getElementById('delete-role-id').value;
  const fd = new FormData();
  fd.append('action', 'delete_role');
  fd.append('id', id);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('role-card-' + id)?.remove();
        closeModal('delete-modal');
        setTimeout(() => location.reload(), 300);
      } else {
        alert(res.message || 'Failed');
      }
    });
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('active');
  });
});
</script>
</body>
</html>
