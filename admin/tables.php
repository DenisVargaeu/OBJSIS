<?php
// admin/tables.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

checkPermission('manage_orders');

$stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
$tables = $stmt->fetchAll();

$page_title = "Table Layout Management";

// Check which tables have active orders (to avoid locking status auto-change)
$table_has_active = [];
foreach ($tables as $t) {
  $c = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
  $c->execute([$t['id']]);
  $table_has_active[$t['id']] = (bool)$c->fetchColumn();
}
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
<style>
.status-select {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  color: var(--text-dim);
  padding: 5px 26px 5px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='%2394a3b8'%3E%3Cpath d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
  transition: all 0.2s;
}
.status-select:hover { border-color: rgba(255,255,255,0.28); }
.status-select.status-free    { color: #10b981; border-color: rgba(16,185,129,.35); }
.status-select.status-occupied { color: #ef4444; border-color: rgba(239,68,68,.35); }
.status-select.status-reserved { color: #f59e0b; border-color: rgba(245,158,11,.35); }
</style>
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
    <button class="btn" onclick="openAddTableModal()"><i class="fas fa-plus"></i> Add Table</button>
  </header>

  <div class="tables-grid">
    <div class="table-card add-table-card" onclick="openAddTableModal()" style="border: 2px dashed var(--border-color); background: rgba(255,255,255,0.02); display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:180px; cursor:pointer; border-radius:20px; transition: var(--transition-base);">
      <i class="fas fa-plus" style="font-size:2rem; color:var(--primary-color); margin-bottom:12px; opacity:0.6;"></i>
      <div style="font-weight:700; color:var(--text-dim); text-transform:uppercase; letter-spacing:1px; font-size:0.8rem;">Add New Table</div>
    </div>

    <?php foreach ($tables as $table): $tid = (int)$table['id']; $locked = $table_has_active[$tid] ?? false; ?>
    <div class="table-card status-<?= $table['status'] ?>" style="position:relative; padding:30px 20px; text-align:center; border-radius:20px; min-height:180px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
      <button style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,.2); border:none; color:var(--text-muted); cursor:pointer; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; transition: var(--transition-base);"
        onclick="if(confirm('Delete table \'<?= htmlspecialchars($table['name']) ?>\'?')) deleteTable(<?= $tid ?>)"
        onmouseover="this.style.color='var(--danger)'; this.style.background='rgba(239,68,68,.1)'"
        onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(0,0,0,.2)'">
        <i class="fas fa-times" style="font-size:0.8rem;"></i>
      </button>

      <div style="background:rgba(255,255,255,.05); width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
        <i class="fas fa-chair" style="font-size:1.5rem; color:<?= $table['status']==='free' ? 'var(--success)' : 'var(--primary-color)' ?>;"></i>
      </div>

      <div style="font-size:1.25rem; font-weight:800; color:var(--text-main); margin-bottom:4px;"><?= htmlspecialchars($table['name']) ?></div>
      <div style="font-size:0.85rem; font-weight:500; color:var(--text-muted);"><?= $table['capacity'] ?> Seats</div>

      <div style="margin-top:14px; display:flex; align-items:center; gap:8px;">
        <select class="status-select status-<?= $table['status'] ?>" data-tid="<?= $tid ?>" onchange="updateStatus(this, <?= $tid ?>)">
          <option value="free">🟢 Free</option>
          <option value="occupied" <?= $table['status']==='occupied'?'selected':'' ?>>🔴 Occupied</option>
          <option value="reserved" <?= $table['status']==='reserved'?'selected':'' ?>>🟡 Reserved</option>
        </select>
        <?php if ($locked): ?>
          <span title="Active order on this table — status will auto-update with order" style="font-size:0.7rem; color:var(--primary-color);"><i class="fas fa-link"></i></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
</div>

<!-- Add Table Modal -->
<div id="add-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,6,23,.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter:blur(8px);">
  <div class="stat-card" style="width:380px; padding:24px; flex-direction:column; align-items:stretch;">
    <h3 style="margin-bottom:20px; font-size:1.25rem; font-weight:700;">Add New Table</h3>
    <form onsubmit="event.preventDefault(); submitAddTable(this);">
      <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
      <div class="form-group">
        <label>Table Name / Number</label>
        <input type="text" name="name" required placeholder="e.g. Table 10">
      </div>
      <div class="form-group">
        <label>Seating Capacity</label>
        <input type="number" name="capacity" required value="4" min="1">
      </div>
      <div style="display:flex; gap:12px; margin-top:25px;">
        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn" style="flex:1;">Create Table</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddTableModal() { document.getElementById('add-modal').style.display = 'flex'; }

function submitAddTable(form) {
  const fd = new FormData(form);
  fd.append('action', 'add_table');
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); });
}

function updateStatus(sel, tid) {
  const fd = new FormData();
  fd.append('action', 'update_table_status');
  fd.append('id', tid);
  fd.append('status', sel.value);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) {
        sel.className = 'status-select status-' + sel.value;
        sel.closest('.table-card').className = 'table-card status-' + sel.value;
      } else {
        alert(res.message || 'Failed');
      }
    });
}

function deleteTable(id) {
  if (!confirm('Delete this table permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_table');
  fd.append('id', id);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); });
}
</script>
</body>
</html>
