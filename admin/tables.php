<?php
// admin/tables.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

checkPermission('manage_orders');

$sections = $pdo->query("SELECT * FROM sections ORDER BY sort_order, id")->fetchAll();

$tables = $pdo->query("
  SELECT t.*, s.name as section_name, s.icon as section_icon, s.sort_order as s_order
  FROM tables t
  LEFT JOIN sections s ON t.section_id = s.id
  ORDER BY COALESCE(s.sort_order, 999), t.sort_order, t.id
")->fetchAll();

// index by section_id for rendering
$by_section = [];
$unsectioned = [];
foreach ($tables as $t) {
  $sid = $t['section_id'];
  if ($sid === null) $unsectioned[] = $t;
  else $by_section[$sid][] = $t;
}

// Which tables have active orders
$table_has_active = [];
foreach ($tables as $t) {
  $c = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
  $c->execute([$t['id']]);
  $table_has_active[$t['id']] = (bool)$c->fetchColumn();
}

$page_title = "Table Layout Manager";
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
.section-col {
  background: var(--card-bg-glass, rgba(15,23,42,0.75));
  border: 1px solid var(--border-color, rgba(255,255,255,0.07));
  border-radius: 18px;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  transition: border-color 0.2s;
}
.section-col:hover { border-color: rgba(255,255,255,0.14); }
.section-col-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.06));
  display: flex;
  align-items: center;
  gap: 9px;
  cursor: default;
  user-select: none;
}
.section-col-header i { font-size: 1rem; color: var(--primary-color, #f97316); }
.section-col-header h3 { margin: 0; font-size: 0.9rem; font-weight: 800; color: var(--text-main, #f8fafc); }
.section-col-header .badge {
  margin-left: auto;
  background: rgba(255,255,255,0.06);
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-muted, #94a3b8);
}
.section-cols-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
.section-col-body { padding: 12px; display: flex; flex-wrap: wrap; gap: 10px; align-content: flex-start; flex: 1; }
.section-col-empty {
  width: 100%; text-align: center; padding: 22px; color: var(--text-dim, #64748b);
  font-size: 0.78rem; font-weight: 600;
}
.section-col-actions {
  border-top: 1px solid rgba(255,255,255,0.04);
  padding: 8px 14px;
  display: flex;
  gap: 6px;
  align-items: center;
}
.btn-icon-xs {
  background: transparent; border: none; color: var(--text-muted);
  cursor: pointer; padding: 4px 6px; border-radius: 6px; transition: all 0.15s;
  font-size: 0.75rem;
}
.btn-icon-xs:hover { color: var(--primary-color, #f97316); background: rgba(249,115,22,0.08); }
.btn-icon-xs.danger:hover { color: #ef4444; background: rgba(239,68,68,0.08); }

.add-table-card {
  border: 2px dashed var(--border-color, rgba(255,255,255,0.1));
  background: transparent;
  min-height: 100px;
  cursor: pointer;
  border-radius: 14px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px;
  color: var(--text-dim, #64748b);
  transition: all 0.2s;
  width: 100%;
}
.add-table-card:hover { border-color: var(--primary-color, #f97316); color: var(--primary-color); }
.add-table-card i { font-size: 1.2rem; }
.add-table-card span { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }

.status-select {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  color: var(--text-dim);
  padding: 4px 22px 4px 8px;
  border-radius: 14px;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='9' height='5' fill='%2394a3b8'%3E%3Cpath d='M0 0l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 6px center;
  transition: all 0.2s;
}
.status-select:hover { border-color: rgba(255,255,255,0.28); }
.status-select.status-free     { color: #10b981; border-color: rgba(16,185,129,.4); }
.status-select.status-occupied { color: #ef4444; border-color: rgba(239,68,68,.4); }
.status-select.status-reserved { color: #f59e0b; border-color: rgba(245,158,11,.4); }

.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; justify-content: center; align-items: center; }
.modal-overlay.active { display: flex; }

.assign-select {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--text-muted);
  padding: 3px 20px 3px 7px;
  border-radius: 8px;
  font-size: 0.68rem;
  cursor: pointer;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='5' fill='%2364748b'%3E%3Cpath d='M0 0l4 5 4-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 5px center;
}
.assign-select:focus { border-color: var(--primary-color, #f97316); }

.quick-add-form { display: flex; gap: 6px; align-items: center; }
.quick-add-form input {
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
  color: var(--text-main); padding: 5px 9px; border-radius: 8px; font-size: 0.75rem;
  outline: none; width: 70px;
}
.quick-add-form input:focus { border-color: var(--primary-color); }

.sections-strip {
  display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
}
.pill-btn {
  padding: 6px 14px; border-radius: 20px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: var(--text-secondary);
  font-size: 0.78rem; font-weight: 700;
  cursor: pointer; transition: all 0.15s;
  display: inline-flex; align-items: center; gap: 5px;
}
.pill-btn:hover { border-color: var(--primary-color); color: var(--primary-color); background: rgba(249,115,22,0.06); }
.pill-btn .del { margin-left: 3px; opacity: 0.5; font-size: 0.7rem; }
.pill-btn .del:hover { opacity: 1; color: #ef4444; }
</style>
</head>
<body>
<div class="app-container">
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
  <header class="page-header">
    <div class="page-title-group">
      <h2>Table Layout</h2>
      <div class="date-subtitle">Organize tables into restaurant sections / rooms</div>
    </div>
    <div class="header-actions">
      <button class="btn" onclick="openAddSectionModal()"><i class="fas fa-plus"></i> New Section</button>
    </div>
  </header>

  <div class="sections-strip" id="section-strip" style="margin-bottom: 22px;">
    <?php foreach ($sections as $s): ?>
      <span class="pill-btn" data-sid="<?= $s['id'] ?>">
        <i class="fas <?= htmlspecialchars($s['icon'] ?? 'fa-chair') ?>"></i> <?= htmlspecialchars($s['name']) ?>
        <span class="del" onclick="editSection(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>', '<?= addslashes($s['icon']) ?>', <?= $s['sort_order'] ?>)"><i class="fas fa-pen"></i></span>
        <span class="del" onclick="deleteSection(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>')"><i class="fas fa-times"></i></span>
      </span>
    <?php endforeach; ?>
  </div>

  <div class="section-cols-row" id="layout-grid">
    <?php foreach ($sections as $s): $sid = $s['id']; $cols = $by_section[$sid] ?? []; ?>
    <div class="section-col" data-section-id="<?= $sid ?>">
      <div class="section-col-header">
        <i class="fas <?= htmlspecialchars($s['icon'] ?? 'fa-chair') ?>"></i>
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <span class="badge"><?= count($cols) ?> tbl</span>
      </div>
      <div class="section-col-body">
        <?php foreach ($cols as $t): $tid = (int)$t['id']; $locked = $table_has_active[$tid] ?? false; ?>
        <div class="table-card status-<?= $t['status'] ?>" style="position:relative; width:100%; padding:18px 16px; text-align:center; border-radius:16px; min-height:130px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
          <div style="margin-bottom:10px;">
            <i class="fas fa-chair" style="font-size:1.25rem; color:<?= $t['status']==='free'?'var(--success)':'var(--primary-color)' ?>;"></i>
          </div>
          <div style="font-size:1rem; font-weight:800; color:var(--text-main);"><?= htmlspecialchars($t['name']) ?></div>
          <div style="font-size:0.75rem; color:var(--text-muted);"><?= $t['capacity'] ?> seats</div>
          <select class="status-select status-<?= $t['status'] ?>" style="margin-top:8px;" onchange="updateStatus(this, <?= $tid ?>)">
            <option value="free">🟢 Free</option>
            <option value="occupied" <?= $t['status']==='occupied'?'selected':'' ?>>🔴 Occupied</option>
            <option value="reserved" <?= $t['status']==='reserved'?'selected':'' ?>>🟡 Reserved</option>
          </select>
          <div style="position:absolute; top:8px; right:8px; display:flex; gap:4px;">
            <?php if ($locked): ?><span title="Active order" style="font-size:0.65rem; color:var(--primary-color);"><i class="fas fa-link"></i></span><?php endif; ?>
            <button class="btn-icon-xs" onclick="editTable(<?= $tid ?>, '<?= addslashes($t['name']) ?>', <?= $t['capacity'] ?>, <?= $sid ?>, <?= $t['sort_order'] ?>)" title="Edit"><i class="fas fa-pen"></i></button>
            <button class="btn-icon-xs danger" onclick="if(confirm('Delete \'<?= addslashes($t['name']) ?>\'?')) deleteTable(<?= $tid ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
        <button class="add-table-card" onclick="openAddTableModal(<?= $sid ?>)">
          <i class="fas fa-plus"></i>
          <span>Add Table</span>
        </button>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($unsectioned)): ?>
    <div class="section-col" data-section-id="0" style="border-style: dashed;">
      <div class="section-col-header">
        <i class="fas fa-question-circle" style="color: var(--text-muted);"></i>
        <h3 style="color: var(--text-muted);">Unassigned</h3>
        <span class="badge"><?= count($unsectioned) ?></span>
      </div>
      <div class="section-col-body">
        <?php foreach ($unsectioned as $t): $tid = (int)$t['id']; $locked = $table_has_active[$tid] ?? false; ?>
        <div class="table-card status-<?= $t['status'] ?>" style="position:relative; width:100%; padding:18px 16px; text-align:center; border-radius:16px; min-height:130px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
          <div style="margin-bottom:10px;">
            <i class="fas fa-chair" style="font-size:1.25rem; color:<?= $t['status']==='free'?'var(--success)':'var(--primary-color)' ?>;"></i>
          </div>
          <div style="font-size:1rem; font-weight:800; color:var(--text-main);"><?= htmlspecialchars($t['name']) ?></div>
          <div style="font-size:0.75rem; color:var(--text-muted);"><?= $t['capacity'] ?> seats</div>
          <select class="status-select status-<?= $t['status'] ?>" style="margin-top:8px;" onchange="updateStatus(this, <?= $tid ?>)">
            <option value="free">🟢 Free</option>
            <option value="occupied" <?= $t['status']==='occupied'?'selected':'' ?>>🔴 Occupied</option>
            <option value="reserved" <?= $t['status']==='reserved'?'selected':'' ?>>🟡 Reserved</option>
          </select>
          <div style="position:absolute; top:8px; right:8px; display:flex; gap:4px;">
            <?php if ($locked): ?><span title="Active order" style="font-size:0.65rem; color:var(--primary-color);"><i class="fas fa-link"></i></span><?php endif; ?>
            <button class="btn-icon-xs" onclick="editTable(<?= $tid ?>, '<?= addslashes($t['name']) ?>', <?= $t['capacity'] ?>)" title="Edit"><i class="fas fa-pen"></i></button>
            <button class="btn-icon-xs danger" onclick="if(confirm('Delete \'<?= addslashes($t['name']) ?>\'?')) deleteTable(<?= $tid ?>)" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>
</div>

<!-- Section Modal (add / edit) -->
<div id="section-modal" class="modal-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,6,23,.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter:blur(8px);">
  <div class="stat-card" style="width:420px; padding:26px; flex-direction:column; align-items:stretch;">
    <h3 id="section-modal-title" style="margin-bottom:18px; font-size:1.2rem; font-weight:800;">New Section</h3>
    <form onsubmit="event.preventDefault(); submitSection(this);">
      <input type="hidden" name="id" id="section-id">
      <div class="form-group">
        <label>Section / Room Name</label>
        <input type="text" name="name" id="section-name" required placeholder="e.g. Main Hall, Terrace">
      </div>
      <div class="form-row" style="margin-top:12px;">
        <div class="form-group">
          <label>Icon (FontAwesome class)</label>
          <input type="text" name="icon" id="section-icon" value="fa-chair" placeholder="fa-chair">
        </div>
        <div class="form-group">
          <label>Sort Order</label>
          <input type="number" name="sort_order" value="0">
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal('section-modal')">Cancel</button>
        <button type="submit" class="btn" style="flex:1;" id="section-submit-btn">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Table Modal (add / edit) -->
<div id="table-modal" class="modal-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,6,23,.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter:blur(8px);">
  <div class="stat-card" style="width:400px; padding:26px; flex-direction:column; align-items:stretch;">
    <h3 id="table-modal-title" style="margin-bottom:18px; font-size:1.2rem; font-weight:800;">Add Table</h3>
    <form onsubmit="event.preventDefault(); submitTable(this);">
      <input type="hidden" name="id" id="table-id">
      <div class="form-group">
        <label>Table Name</label>
        <input type="text" name="name" id="table-name" required placeholder="e.g. Table 11">
      </div>
      <div style="display:flex; gap:10px; margin-top:10px;">
        <div class="form-group" style="flex:1;">
          <label>Capacity</label>
          <input type="number" name="capacity" id="table-capacity" value="4" min="1">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Sort Order</label>
          <input type="number" name="sort_order" id="table-sort" value="0">
        </div>
      </div>
      <div class="form-group" style="margin-top:10px;">
        <label>Assign to Section</label>
        <select class="assign-select" name="section_id" id="table-section" style="width:100%; padding:8px 10px;">
          <option value="">-- Unassigned --</option>
          <?php foreach ($sections as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal('table-modal')">Cancel</button>
        <button type="submit" class="btn" style="flex:1;" id="table-submit-btn">Create</button>
      </div>
    </form>
  </div>
</div>

<script>
const SECTIONS_DATA = <?= json_encode(array_values($sections)) ?>;
let currentTableSection = null; // when adding, pre-fill parent section

function closeModal(id) { document.getElementById(id).classList.remove('active'); if (document.getElementById(id).style.display === 'none') {} }

function openModal(id) { document.getElementById(id).classList.add('active'); }

function openAddSectionModal() {
  document.getElementById('section-modal-title').textContent = 'New Section';
  document.getElementById('section-id').value = '';
  document.getElementById('section-name').value = '';
  document.getElementById('section-icon').value = 'fa-chair';
  document.querySelector('#section-modal [name="sort_order"]').value = 0;
  document.getElementById('section-submit-btn').innerHTML = '<i class="fas fa-plus"></i> Create';
  openModal('section-modal');
}

function editSection(id, name, icon, sort) {
  document.getElementById('section-modal-title').textContent = 'Edit Section';
  document.getElementById('section-id').value = id;
  document.getElementById('section-name').value = name;
  document.getElementById('section-icon').value = icon;
  document.querySelector('#section-modal [name="sort_order"]').value = sort;
  document.getElementById('section-submit-btn').innerHTML = '<i class="fas fa-save"></i> Save';
  openModal('section-modal');
}

function submitSection(form) {
  const fd = new FormData(form);
  fd.append('action', form.querySelector('#section-id').value ? 'edit_section' : 'add_section');
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) { closeModal('section-modal'); location.reload(); }
      else alert(res.message || 'Failed');
    });
}

function deleteSection(id, name) {
  if (!confirm('Delete section "' + name + '"? Tables in it will become unassigned.')) return;
  const fd = new FormData();
  fd.append('action', 'delete_section');
  fd.append('id', id);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) location.reload();
      else alert(res.message);
    });
}

function openAddTableModal(sectionId) {
  currentTableSection = sectionId || null;
  document.getElementById('table-modal-title').textContent = 'Add Table';
  document.getElementById('table-id').value = '';
  document.getElementById('table-name').value = '';
  document.getElementById('table-capacity').value = 4;
  document.getElementById('table-sort').value = 0;
  document.getElementById('table-submit-btn').innerHTML = '<i class="fas fa-plus"></i> Create';
  const sel = document.getElementById('table-section');
  sel.value = currentTableSection || '';
  openModal('table-modal');
}

function editTable(id, name, capacity, section, sort) {
  currentTableSection = section || null;
  document.getElementById('table-modal-title').textContent = 'Edit Table';
  document.getElementById('table-id').value = id;
  document.getElementById('table-name').value = name;
  document.getElementById('table-capacity').value = capacity;
  document.getElementById('table-sort').value = sort || 0;
  document.getElementById('table-submit-btn').innerHTML = '<i class="fas fa-save"></i> Save';
  const sel = document.getElementById('table-section');
  sel.value = currentTableSection || '';
  openModal('table-modal');
}

function submitTable(form) {
  const fd = new FormData(form);
  fd.append('action', form.querySelector('#table-id').value ? 'edit_table' : 'add_table');
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) { closeModal('table-modal'); location.reload(); }
      else alert(res.message || 'Failed');
    });
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
        const card = sel.closest('.table-card');
        if (card) card.className = 'table-card status-' + sel.value;
      } else {
        alert(res.message || 'Failed to update');
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

function assignTable(tid, sid) {
  const fd = new FormData();
  fd.append('action', 'update_table_section');
  fd.append('table_id', tid);
  fd.append('section_id', sid);
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) location.reload();
      else alert(res.message);
    });
}

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', function(e) { if (e.target === el) el.classList.remove('active'); });
});
</script>
</body>
</html>
