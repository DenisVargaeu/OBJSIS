<?php
// admin/receipt_editor.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

checkPermission('manage_menu');

$page_title = "Receipt Editor";

$settings = [
  'restaurant_name'    => getSetting('restaurant_name', 'My Restaurant'),
  'restaurant_address' => getSetting('restaurant_address', ''),
  'restaurant_phone'   => getSetting('restaurant_phone', ''),
  'receipt_footer'     => getSetting('receipt_footer', 'Thank you for your visit!'),
  'receipt_show_logo'  => getSetting('receipt_show_logo', '1'),
  'receipt_show_qr'    => getSetting('receipt_show_qr', '0'),
  'receipt_currency'   => getSetting('receipt_currency', 'EUR'),
  'receipt_tax_rate'   => getSetting('receipt_tax_rate', '0'),
  'receipt_tax_label'   => getSetting('receipt_tax_label', 'VAT'),
  'receipt_vat_id'      => getSetting('receipt_vat_id', ''),
  'receipt_reg_id'      => getSetting('receipt_reg_id', ''),
  'receipt_header'      => getSetting('receipt_header', ''),
];

$currencies = ['EUR' => '€ Euro', 'USD' => '$ US Dollar', 'GBP' => '£ British Pound', 'HUF' => 'Ft Hungarian Forint', 'CZK' => 'Kč Czech Koruna', 'SK' => '€ Slovak Euro'];

// Fetch a recent order for preview
$preview_order = null;
$stmt_prev = $pdo->query("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) as item_count FROM orders o ORDER BY id DESC LIMIT 1");
$preview_order = $stmt_prev->fetch();
if ($preview_order) {
  $stmt_items = $pdo->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?");
  $stmt_items->execute([$preview_order['id']]);
  $preview_items = $stmt_items->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt Editor - OBJSIS</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<?= getCustomStyles() ?>
<style>
.editor-layout { display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start; }
.editor-form { display: flex; flex-direction: column; gap: 20px; }
.form-section { background: var(--card-bg-glass, rgba(15,23,42,0.8)); border: 1px solid var(--border-color, rgba(255,255,255,0.08)); border-radius: 16px; padding: 22px; }
.form-section h3 { margin: 0 0 16px; font-size: 1rem; font-weight: 800; color: var(--text-main, #f8fafc); display: flex; align-items: center; gap: 8px; }
.form-section h3 i { color: var(--primary-color, #f97316); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 0; }
.form-row.single { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group label { font-size: 0.78rem; font-weight: 700; color: var(--text-muted, #94a3b8); }
.form-group input, .form-group select, .form-group textarea {
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid var(--border-color, rgba(255,255,255,0.1));
  background: rgba(255,255,255,0.03);
  color: var(--text-main, #f8fafc);
  font-size: 0.88rem;
  outline: none;
  width: 100%;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color, #f97316); }
.form-group textarea { resize: vertical; min-height: 70px; font-family: inherit; }
.toggle-group { display: flex; gap: 8px; flex-wrap: wrap; }
.toggle-opt {
  padding: 7px 14px; border-radius: 20px; cursor: pointer; font-size: 0.82rem; font-weight: 600;
  border: 1px solid var(--border-color, rgba(255,255,255,0.12)); color: var(--text-muted, #94a3b8);
  transition: all 0.2s; background: transparent;
}
.toggle-opt.active { background: var(--primary-color, #f97316); color: white; border-color: var(--primary-color); }
.toggle-opt:hover:not(.active) { border-color: rgba(255,255,255,0.3); }

.preview-sticky { position: sticky; top: 20px; }
.preview-frame {
  background: #fff; border-radius: 18px; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35); font-family: 'Courier New', Courier, monospace;
}
.preview-header {
  background: #1e293b; color: white; padding: 10px 14px; font-size: 0.75rem; font-weight: 700;
  display: flex; justify-content: space-between; align-items: center;
}
.preview-body { padding: 20px; }
.pv-center { text-align: center; }
.pv-bold { font-weight: 700; }
.pv-divider { border-top: 1px dashed #ccc; margin: 8px 0; }
.pv-line { display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 3px; }
.pv-total { font-size: 1rem; font-weight: 700; }
.pv-small { font-size: 0.7rem; color: #888; }
.pv-qr { width: 70px; height: 70px; background: #f0f0f0; margin: 8px auto; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 0.65rem; }

.alert-box { padding: 10px 16px; border-radius: 10px; font-size: 0.82rem; font-weight: 600; margin-bottom: 16px; }
.alert-box.error { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
.alert-box.success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }

.row-btns { display: flex; gap: 10px; }

@media (max-width: 900px) { .editor-layout { grid-template-columns: 1fr; } .preview-sticky { position: static; } }
</style>
</head>
<body>
<div class="app-container">
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
  <header class="page-header">
    <div class="page-title-group">
      <h2>Receipt Editor</h2>
      <div class="date-subtitle">Customize your printed receipt layout and content</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary" onclick="resetDefaults()"><i class="fas fa-undo"></i> Reset</button>
      <button class="btn" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </header>

  <div id="alert-box"></div>

  <div class="editor-layout">
    <div class="editor-form">

      <div class="form-section">
        <h3><i class="fas fa-store"></i> Restaurant Details</h3>
        <div class="form-row single">
          <div class="form-group">
            <label>Restaurant Name (shown on receipt header)</label>
            <input type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" oninput="updatePreview()">
          </div>
        </div>
        <div class="form-row" style="margin-top:12px;">
          <div class="form-group">
            <label>Address</label>
            <input type="text" name="restaurant_address" value="<?= htmlspecialchars($settings['restaurant_address']) ?>" oninput="updatePreview()">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="restaurant_phone" value="<?= htmlspecialchars($settings['restaurant_phone']) ?>" oninput="updatePreview()">
          </div>
        </div>
        <div class="form-row" style="margin-top:12px;">
          <div class="form-group">
            <label>VAT ID (optional)</label>
            <input type="text" name="receipt_vat_id" value="<?= htmlspecialchars($settings['receipt_vat_id']) ?>" oninput="updatePreview()">
          </div>
          <div class="form-group">
            <label>Company Reg. ID</label>
            <input type="text" name="receipt_reg_id" value="<?= htmlspecialchars($settings['receipt_reg_id']) ?>" oninput="updatePreview()">
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3><i class="fas fa-file-invoice"></i> Receipt Content</h3>
        <div class="form-row single">
          <div class="form-group">
            <label>Custom Header Text (shown before items, leave blank to skip)</label>
            <textarea name="receipt_header" placeholder="e.g. VAT included — Thank you!" oninput="updatePreview()"><?= htmlspecialchars($settings['receipt_header']) ?></textarea>
          </div>
        </div>
        <div class="form-row single" style="margin-top:12px;">
          <div class="form-group">
            <label>Footer Message</label>
            <textarea name="receipt_footer" oninput="updatePreview()"><?= htmlspecialchars($settings['receipt_footer']) ?></textarea>
          </div>
        </div>
        <div class="form-row" style="margin-top:12px;">
          <div class="form-group">
            <label>Currency Symbol</label>
            <select name="receipt_currency" onchange="updatePreview()">
              <?php foreach ($currencies as $code => $label): ?>
                <option value="<?= $code ?>" <?= $settings['receipt_currency']==$code?'selected':'' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Tax / VAT Rate (%)</label>
            <input type="number" name="receipt_tax_rate" value="<?= htmlspecialchars($settings['receipt_tax_rate']) ?>" min="0" max="100" step="0.5" oninput="updatePreview()">
          </div>
        </div>
        <div class="form-row" style="margin-top:12px;">
          <div class="form-group">
            <label>Tax Label</label>
            <input type="text" name="receipt_tax_label" value="<?= htmlspecialchars($settings['receipt_tax_label']) ?>" oninput="updatePreview()">
          </div>
          <div class="form-group">
            <label>&nbsp;</label>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3><i class="fas fa-eye"></i> Display Options</h3>
        <div class="form-row single">
          <div class="form-group">
            <label>Show Elements</label>
            <div class="toggle-group" id="toggle-group">
              <button class="toggle-opt <?= $settings['receipt_show_logo'] ? 'active' : '' ?>" data-key="receipt_show_logo" data-val="1" onclick="toggleOpt(this)">
                <i class="fas fa-image"></i> Restaurant Name Badge
              </button>
              <button class="toggle-opt <?= $settings['receipt_show_qr'] ? 'active' : '' ?>" data-key="receipt_show_qr" data-val="1" onclick="toggleOpt(this)">
                <i class="fas fa-qrcode"></i> QR Placeholder
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="preview-sticky">
      <div style="font-size:0.8rem; font-weight:700; color: var(--text-muted, #94a3b8); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
        <i class="fas fa-eye" style="margin-right:4px;"></i> Live Preview
      </div>
      <div class="preview-frame" id="receipt-preview">
        <div class="preview-header">
          <span>Receipt Preview</span>
          <span style="color:#94a3b8;">thermal</span>
        </div>
        <div class="preview-body" id="preview-body">
        </div>
      </div>
    </div>
  </div>
</main>
</div>

<script>
const toggleState = {
  receipt_show_logo: <?= (int)$settings['receipt_show_logo'] ?>,
  receipt_show_qr:   <?= (int)$settings['receipt_show_qr'] ?>,
};

function toggleOpt(btn) {
  const key = btn.dataset.key;
  toggleState[key] = toggleState[key] ? 0 : 1;
  btn.classList.toggle('active', !!toggleState[key]);
  updatePreview();
}

function getFormVal(name) {
  const el = document.querySelector('[name="' + name + '"]');
  return el ? el.value : '';
}

function escapeHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function updatePreview() {
  const name     = getFormVal('restaurant_name');
  const address  = getFormVal('restaurant_address');
  const phone    = getFormVal('restaurant_phone');
  const vatId    = getFormVal('receipt_vat_id');
  const regId    = getFormVal('receipt_reg_id');
  const header   = getFormVal('receipt_header');
  const footer   = getFormVal('receipt_footer');
  const cur      = getFormVal('receipt_currency') || 'EUR';
  const taxRate  = parseFloat(getFormVal('receipt_tax_rate')) || 0;
  const taxLabel = getFormVal('receipt_tax_label') || 'VAT';

  const showLogo = toggleState.receipt_show_logo;
  const showQr   = toggleState.receipt_show_qr;

  const curMap = { EUR:'€', USD:'$', GBP:'£', HUF:'Ft', CZK:'Kč', SK:'€' };
  const sym = curMap[cur] || cur;

  const sub = 42.50;
  const disc = 5.00;
  const taxAmt = (sub - disc) * taxRate / 100;
  const total = sub - disc + taxAmt;

  let h = '';

  if (showLogo) {
    h += '<div class="pv-center pv-bold" style="font-size:1.15rem; margin-bottom:4px;">' + escapeHtml(name || 'Restaurant') + '</div>';
  }
  if (address) h += '<div class="pv-center pv-small">' + escapeHtml(address) + '</div>';
  if (phone) h += '<div class="pv-center pv-small">Tel: ' + escapeHtml(phone) + '</div>';
  if (vatId || regId) h += '<div class="pv-center pv-small" style="margin-top:4px;">' + escapeHtml([vatId, regId].filter(Boolean).join(' | ')) + '</div>';

  h += '<div class="pv-divider"></div>';
  h += '<div class="pv-center" style="font-size:0.75rem; color:#666;">Receipt #<?= $preview_order ? $preview_order['id'] : '001' ?></div>';
  h += '<div class="pv-center pv-small"><?= date("d.m.Y H:i", $preview_order ? strtotime($preview_order["created_at"]) : time()) ?></div>';
  h += '<div class="pv-center pv-small">Table: <?= $preview_order ? $preview_order['table_number'] : '5' ?></div>';
  h += '<div class="pv-divider"></div>';

  if (header) {
    h += '<div class="pv-center" style="font-size:0.75rem; margin-bottom:6px;">' + escapeHtml(header) + '</div>';
    h += '<div class="pv-divider"></div>';
  }

  h += '<div style="font-weight:800; font-size:0.78rem; margin-bottom:4px;">ITEMS</div>';
  h += '<div class="pv-line"><span>Qty Item</span><span>Price</span></div>';
  h += '<div class="pv-divider"></div>';

  const demos = [
    {qty:2, name:'Margherita Pizza', price:12.50},
    {qty:1, name:'Caesar Salad', price:9.50},
    {qty:3, name:'Coke', price:2.50},
  ];
  demos.forEach(d => {
    h += '<div class="pv-line"><span>' + d.qty + 'x ' + escapeHtml(d.name) + '</span><span>' + sym.toFixed(2) + ' ' + number_format(d.price, 2) + '</span></div>';
  });

  h += '<div class="pv-divider"></div>';
  if (taxRate > 0) {
    h += '<div class="pv-line"><span>Subtotal</span><span>' + sym + ' ' + number_format(sub, 2) + '</span></div>';
    h += '<div class="pv-line"><span>' + escapeHtml(taxLabel) + ' (' + taxRate + '%)</span><span>' + sym + ' ' + number_format(taxAmt, 2) + '</span></div>';
  }
  h += '<div class="pv-line pv-total"><span>TOTAL</span><span>' + sym + ' ' + number_format(total, 2) + '</span></div>';
  h += '<div class="pv-divider"></div>';

  if (showQr) {
    h += '<div class="pv-qr">QR</div>';
    h += '<div class="pv-center pv-small">Scan for digital receipt</div>';
  }

  h += '<div class="pv-center" style="margin-top:12px; font-size:0.75rem; color:#555;">' + escapeHtml(footer) + '</div>';

  document.getElementById('preview-body').innerHTML = h;
}

function number_format(n, d) {
  return n.toFixed(d).replace('.', ',');
}

function saveSettings() {
  const fd = new FormData();
  const fields = ['restaurant_name','restaurant_address','restaurant_phone','receipt_footer','receipt_show_logo','receipt_show_qr','receipt_currency','receipt_tax_rate','receipt_tax_label','receipt_vat_id','receipt_reg_id','receipt_header'];
  fields.forEach(f => fd.append(f, document.querySelector('[name="' + f + '"]').value));
  fd.append('action', 'update_receipt_settings');
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);

  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      const box = document.getElementById('alert-box');
      if (res.success) {
        box.innerHTML = '<div class="alert-box success"><i class="fas fa-check-circle"></i> Receipt settings saved!</div>';
        setTimeout(() => box.innerHTML = '', 3000);
      } else {
        box.innerHTML = '<div class="alert-box error"><i class="fas fa-exclamation-circle"></i> ' + res.message + '</div>';
      }
    });
}

function resetDefaults() {
  if (!confirm('Reset all receipt settings to defaults?')) return;
  const fd = new FormData();
  fd.append('action', 'reset_receipt_settings');
  fd.append('csrf_token', OBJSIS_CSRF_TOKEN);
  fetch('../api/admin_actions.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
      if (res.success) location.reload();
      else alert(res.message || 'Failed');
    });
}

window.addEventListener('DOMContentLoaded', updatePreview);
</script>
</body>
</html>
