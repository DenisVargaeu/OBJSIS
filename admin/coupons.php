<?php
// admin/coupons.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

// Access Control
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Fetch Coupons
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll();
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
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
                <h2 style="margin:0;">Active Discounts</h2>
                <div class="card" style="padding: 10px 20px; display:flex; gap:10px; align-items:flex-end; margin:0;">
                    <form onsubmit="event.preventDefault(); addCoupon(this);"
                        style="display:flex; gap:10px; align-items: flex-end; flex-wrap: wrap;">
                        <div style="text-align:left;">
                            <label style="font-size:0.8rem;">Code</label>
                            <input type="text" name="code" placeholder="SAVE10" required
                                style="width:120px; padding:8px;">
                        </div>
                        <div style="text-align:left;">
                            <label style="font-size:0.8rem;">Type</label>
                            <select name="type" style="width:100px; padding:8px;">
                                <option value="percent">% Off</option>
                                <option value="fixed">€ Off</option>
                            </select>
                        </div>
                        <div style="text-align:left;">
                            <label style="font-size:0.8rem;">Value</label>
                            <input type="number" name="value" placeholder="10" required
                                style="width:80px; padding:8px;">
                        </div>
                        <div style="text-align:left;">
                            <label style="font-size:0.8rem;">Expires</label>
                            <input type="datetime-local" name="expiration_date" style="width:180px; padding:8px;">
                        </div>
                        <div style="text-align:left;">
                            <label style="font-size:0.8rem;">Max Uses</label>
                            <input type="number" name="max_uses" placeholder="Unlimited"
                                style="width:100px; padding:8px;">
                        </div>
                        <div style="text-align:left; display:flex; align-items:center; gap:5px; padding:8px 0;">
                            <input type="checkbox" name="one_time_use" id="one_time_use" style="width:auto;">
                            <label for="one_time_use" style="font-size:0.8rem; margin:0;">One-time use</label>
                        </div>
                        <button type="submit" class="btn" style="padding: 8px 15px;">Create</button>
                    </form>
                </div>
            </div>

            <div class="coupon-grid">
                <?php if (empty($coupons)): ?>
                    <p style="color:var(--text-muted);">No active coupons created yet.</p>
                <?php else: ?>
                    <?php foreach ($coupons as $coupon):
                        $is_expired = $coupon['expiration_date'] && strtotime($coupon['expiration_date']) < time();
                        $is_maxed = $coupon['max_uses'] !== null && $coupon['current_uses'] >= $coupon['max_uses'];
                        $status_class = ($is_expired || $is_maxed || !$coupon['is_active']) ? 'coupon-inactive' : 'coupon-active';
                        ?>
                        <div class="coupon-ticket" style="position:relative;">
                            <button class="print-coupon-btn"
                                onclick="openPrintModal(<?= $coupon['id'] ?>, '<?= addslashes($coupon['code']) ?>', '<?= $coupon['value'] ?>', '<?= $coupon['type'] ?>', '<?= $coupon['expiration_date'] ?>')">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="delete-coupon-btn" onclick="deleteCoupon(<?= $coupon['id'] ?>)"><i
                                    class="fas fa-trash"></i></button>
                            <div class="coupon-code">
                                <?= htmlspecialchars($coupon['code']) ?>
                                <?php if ($coupon['one_time_use']): ?>
                                    <span style="font-size:0.7rem; opacity:0.7; display:block; margin-top:3px;">⚡ One-time
                                        use</span>
                                <?php endif; ?>
                            </div>
                            <div class="coupon-value">
                                -<?= floatval($coupon['value']) ?><?= $coupon['type'] == 'percent' ? '%' : ' €' ?>
                            </div>
                            <div style="font-size:0.75rem; opacity:0.8; margin:5px 0;">
                                <?php if ($coupon['max_uses']): ?>
                                    <?= $coupon['current_uses'] ?>/<?= $coupon['max_uses'] ?> uses
                                <?php else: ?>
                                    <?= $coupon['current_uses'] ?> uses
                                <?php endif; ?>
                            </div>
                            <?php if ($coupon['expiration_date']): ?>
                                <div style="font-size:0.7rem; opacity:0.7;">
                                    Expires: <?= date('M d, Y', strtotime($coupon['expiration_date'])) ?>
                                </div>
                            <?php endif; ?>
                            <div class="<?= $status_class ?>" style="margin-top:8px;">
                                <?php
                                if ($is_expired)
                                    echo 'Expired';
                                elseif ($is_maxed)
                                    echo 'Max Uses Reached';
                                elseif (!$coupon['is_active'])
                                    echo 'Inactive';
                                else
                                    echo 'Active';
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Print Style Selection Modal -->
    <div id="print-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 500px; max-width: 90%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 style="margin:0;">Select Coupon Style</h3>
                <button type="button" class="btn btn-secondary" style="padding: 5px 10px;"
                    onclick="document.getElementById('print-modal').style.display='none'">&times;</button>
            </div>

            <p style="color:var(--text-muted); font-size:0.9rem;">Choose a visual style for coupon: <strong
                    id="modal-coupon-code" style="color:var(--primary-color);"></strong></p>

            <div class="preview-container">
                <div id="coupon-preview" class="preview-scale">
                    <!-- Preview content injected here -->
                </div>
            </div>

            <input type="hidden" id="print-coupon-id">

            <div class="style-options">
                <div class="style-option selected" onclick="selectStyle(this, 'standard')">
                    <i class="fas fa-certificate"></i>
                    <strong>Standard</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">Dashed border, brand colors</div>
                </div>
                <div class="style-option" onclick="selectStyle(this, 'modern')">
                    <i class="fas fa-sparkles"></i>
                    <strong>Modern</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">Rounded, clean gradient</div>
                </div>
                <div class="style-option" onclick="selectStyle(this, 'retro')">
                    <i class="fas fa-ticket"></i>
                    <strong>Retro</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">Classic ticket cutout</div>
                </div>
                <div class="style-option" onclick="selectStyle(this, 'minimal')">
                    <i class="fas fa-align-center"></i>
                    <strong>Minimal</strong>
                    <div style="font-size:0.7rem; opacity:0.6; margin-top:5px;">B&W for receipt printers</div>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" style="flex:1;"
                    onclick="document.getElementById('print-modal').style.display='none'">Cancel</button>
                <button type="button" class="btn" style="flex:2;" onclick="confirmPrint()">
                    <i class="fas fa-print"></i> Confirm & Print
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
            if (!confirm("Delete this coupon?")) return;
            const formData = new FormData();
            formData.append('action', 'delete_coupon');
            formData.append('id', id);

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

            // Reset selection to standard
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

            container.className = `preview-scale coupon-print-card style-${selectedStyle}`;
            container.innerHTML = `
                <div style="font-size: 1.2rem; font-weight: 600; opacity: 0.8;">${data.restaurant}</div>
                <div class="print-code">${data.code}</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #000; margin-bottom: 10px;">
                    Value: -${parseFloat(data.value)}${symbol}
                </div>
                <div style="font-size: 0.9rem; opacity: 0.7; margin-top: 15px;">${expiryText}</div>
                <div style="font-size: 0.8rem; opacity: 0.5; margin-top: 20px; font-style: italic;">
                    Preview Only
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