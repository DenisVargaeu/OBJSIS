<?php
// admin/coupons.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access Control
checkPermission('manage_menu');

// Fetch Coupons
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll();

$page_title = "Discount Management";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Coupons - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_coupons.css"> <!-- Specific CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        .coupon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }
        .coupon-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px 24px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition-base);
            box-shadow: var(--shadow-md);
        }
        .coupon-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-lg);
        }
        .coupon-card::before, .coupon-card::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            background: var(--bg-main);
            border-radius: 50%;
            transform: translateY(-50%);
        }
        .coupon-card::before { left: -10px; border-right: 1px solid var(--border-color); }
        .coupon-card::after { right: -10px; border-left: 1px solid var(--border-color); }

        .coupon-code-display {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: 2px;
            margin-bottom: 12px;
            padding: 8px 16px;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 12px;
            border: 1px dashed var(--primary-color);
        }
        .coupon-val {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2>Discount Coupons</h2>
                    <div class="date-subtitle">Create and manage promotional offers</div>
                </div>
            </header>

            <!-- Creation Form -->
            <div class="glass-card" style="margin-bottom: 40px; padding: 24px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; font-weight: 700;">Create New Coupon</h3>
                <form onsubmit="event.preventDefault(); addCoupon(this);" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end;">
                    <!-- SECURITY FIX: CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="form-group" style="margin: 0;">
                        <label>Coupon Code</label>
                        <input type="text" name="code" placeholder="e.g. SAVE20" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Discount Type</label>
                        <select name="type">
                            <option value="percent">% Percentage</option>
                            <option value="fixed">€ Fixed Amount</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Value</label>
                        <input type="number" name="value" placeholder="20" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Expiration Date</label>
                        <input type="datetime-local" name="expiration_date">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Max Uses</label>
                        <input type="number" name="max_uses" placeholder="Unlimited">
                    </div>
                    <div class="form-group" style="margin: 0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="one_time_use" id="one_time_use" style="width: 18px; height: 18px; margin: 0;">
                        <label for="one_time_use" style="margin: 0; font-size: 0.85rem; font-weight: 600;">One-time use</label>
                    </div>
                    <button type="submit" class="btn" style="height: 48px;">
                        <i class="fas fa-magic"></i> Generate
                    </button>
                </form>
            </div>

            <div class="coupon-grid">
                <?php if (empty($coupons)): ?>
                    <div class="glass-card" style="grid-column: 1/-1; text-align: center; padding: 60px;">
                        <i class="fas fa-ticket-alt" style="font-size: 3rem; color: var(--text-muted); opacity: 0.2; margin-bottom: 15px; display: block;"></i>
                        <p style="color:var(--text-muted);">No active coupons created yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($coupons as $coupon):
                        $is_expired = $coupon['expiration_date'] && strtotime($coupon['expiration_date']) < time();
                        $is_maxed = $coupon['max_uses'] !== null && $coupon['current_uses'] >= $coupon['max_uses'];
                        $is_inactive = $is_expired || $is_maxed || !$coupon['is_active'];
                        ?>
                        <div class="coupon-card" style="opacity: <?= $is_inactive ? '0.6' : '1' ?>;">
                            <div style="position: absolute; top: 15px; right: 15px; display: flex; gap: 8px;">
                                <button class="btn btn-secondary" style="padding: 6px; width: 32px; height: 32px; border-radius: 8px; font-size: 0.8rem;"
                                    onclick="openPrintModal(<?= $coupon['id'] ?>, '<?= addslashes($coupon['code']) ?>', '<?= $coupon['value'] ?>', '<?= $coupon['type'] ?>', '<?= $coupon['expiration_date'] ?>')" title="Print">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn" style="padding: 6px; width: 32px; height: 32px; border-radius: 8px; font-size: 0.8rem; background: var(--danger);" 
                                    onclick="deleteCoupon(<?= $coupon['id'] ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="coupon-code-display"><?= htmlspecialchars($coupon['code']) ?></div>
                            
                            <?php if ($coupon['one_time_use']): ?>
                                <span style="font-size:0.65rem; font-weight: 800; text-transform: uppercase; color: var(--secondary-color); margin-bottom: 12px; display: block; letter-spacing: 1px;">
                                    <i class="fas fa-bolt"></i> Single Use
                                </span>
                            <?php endif; ?>

                            <div class="coupon-val">
                                <span style="font-size: 1.5rem; vertical-align: middle; margin-right: 2px;"><?= $coupon['type'] == 'fixed' ? '€' : '' ?></span>
                                <?= floatval($coupon['value']) ?>
                                <span style="font-size: 1.5rem; vertical-align: middle; margin-left: 2px;"><?= $coupon['type'] == 'percent' ? '%' : '' ?></span>
                            </div>
                            
                            <div style="font-size:0.85rem; font-weight: 600; color: var(--text-dim); margin-bottom: 20px;">
                                <?php if ($coupon['max_uses']): ?>
                                    Used <span style="color: var(--primary-color);"><?= $coupon['current_uses'] ?></span> of <span style="color: var(--primary-color);"><?= $coupon['max_uses'] ?></span>
                                <?php else: ?>
                                    Redeemed <span style="color: var(--primary-color);"><?= $coupon['current_uses'] ?></span> times
                                <?php endif; ?>
                            </div>

                            <?php if ($coupon['expiration_date']): ?>
                                <div style="font-size:0.75rem; color: var(--text-muted); padding: 8px 16px; background: rgba(255,255,255,0.03); border-radius: 30px; margin-bottom: 15px;">
                                    <i class="far fa-calendar-alt"></i> Expires: <?= date('M d, Y', strtotime($coupon['expiration_date'])) ?>
                                </div>
                            <?php endif; ?>

                            <div class="status-badge" style="background: <?= $is_inactive ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)' ?>; color: <?= $is_inactive ? 'var(--danger)' : 'var(--success)' ?>; border: 1px solid <?= $is_inactive ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)' ?>; width: 100%; border-radius: 12px;">
                                <?php
                                if ($is_expired) echo 'Expired';
                                elseif ($is_maxed) echo 'Max Uses Reached';
                                elseif (!$coupon['is_active']) echo 'Disabled';
                                else echo 'Active & Ready';
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Print Style Selection Modal -->
    <div id="print-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(8px);">
        <div class="stat-card" style="width: 550px; max-width: 95%; padding: 24px; flex-direction: column; align-items: stretch;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 style="margin:0; font-size: 1.25rem; font-weight: 700;">Print Coupon Styles</h3>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('print-modal').style.display='none'"
                    style="padding: 6px 12px; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom: 20px;">Choose a visual style for coupon: <strong id="modal-coupon-code" style="color:var(--primary-color);"></strong></p>

            <div class="preview-container" style="background: rgba(0,0,0,0.2); border-radius: 16px; padding: 30px; margin-bottom: 24px;">
                <div id="coupon-preview" class="preview-scale">
                    <!-- Preview content injected here -->
                </div>
            </div>

            <input type="hidden" id="print-coupon-id">

            <div class="style-options" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="style-option selected" onclick="selectStyle(this, 'standard')" style="padding: 16px; border: 1px solid var(--border-color); border-radius: 12px; cursor: pointer;">
                    <strong>Standard</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">Dashed border, brand colors</div>
                </div>
                <div class="style-option" onclick="selectStyle(this, 'modern')" style="padding: 16px; border: 1px solid var(--border-color); border-radius: 12px; cursor: pointer;">
                    <strong>Modern</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">Rounded, clean gradient</div>
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top: 25px;">
                <button type="button" class="btn btn-secondary" style="flex:1;"
                    onclick="document.getElementById('print-modal').style.display='none'">Cancel</button>
                <button type="button" class="btn" style="flex:2;" onclick="confirmPrint()">
                    <i class="fas fa-print"></i> Generate PDF & Print
                </button>
            </div>
        </div>
    </div>

    <script>
        function addCoupon(form) {
            const formData = new FormData(form);
            formData.append('action', 'add_coupon');

            fetch('../api/admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) location.reload();
                    else alert(res.message);
                });
        }

        function deleteCoupon(id) {
            if (!confirm("Delete this coupon permanently?")) return;
            const formData = new FormData();
            formData.append('action', 'delete_coupon');
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

        let selectedStyle = 'standard';
        let currentCouponData = {};

        function openPrintModal(id, code, value, type, expiry) {
            document.getElementById('print-coupon-id').value = id;
            document.getElementById('modal-coupon-code').innerText = code;

            currentCouponData = {
                code: code,
                value: value,
                type: type,
                expiry: expiry,
                restaurant: "<?= addslashes(getSetting('restaurant_name', 'OBJSIS Restaurant')) ?>"
            };

            document.getElementById('print-modal').style.display = 'flex';

            const options = document.querySelectorAll('.style-option');
            options.forEach(opt => opt.classList.remove('selected'));
            options[0].classList.add('selected');
            selectedStyle = 'standard';
            updatePreview();
        }

        function selectStyle(element, style) {
            const options = document.querySelectorAll('.style-option');
            options.forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            selectedStyle = style;
            updatePreview();
        }

        function updatePreview() {
            const container = document.getElementById('coupon-preview');
            const data = currentCouponData;
            const symbol = data.type === 'percent' ? '%' : ' €';
            const expiryText = data.expiry ? `Valid until: ${new Date(data.expiry).toLocaleDateString()}` : '';

            container.className = `preview-scale style-${selectedStyle}`;
            container.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 40px; text-align: center; color: #0f172a; width: 300px; margin: 0 auto; border: 2px dashed #e2e8f0;">
                    <div style="font-size: 1rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 10px;">Coupon</div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #f97316;">${parseFloat(data.value)}${symbol}</div>
                    <div style="margin: 20px 0; padding: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; font-weight: 800; font-size: 1.5rem; letter-spacing: 2px;">${data.code}</div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">${expiryText}</div>
                </div>
            `;
        }

        function confirmPrint() {
            const id = document.getElementById('print-coupon-id').value;
            const url = `print_coupon.php?id=${id}&style=${selectedStyle}`;
            window.open(url, '_blank', 'width=800,height=800');
            document.getElementById('print-modal').style.display = 'none';
        }
    </script>
</body>

</html>