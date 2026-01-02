<?php
// admin/print_coupon.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$style = isset($_GET['style']) ? $_GET['style'] : 'standard';

// Fetch Coupon
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt->execute([$id]);
$coupon = $stmt->fetch();

if (!$coupon) {
    die("Coupon not found.");
}

$restaurant_name = getSetting('restaurant_name', 'OBJSIS Restaurant');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Coupon -
        <?= htmlspecialchars($coupon['code']) ?>
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page_coupons.css">
    <?= getCustomStyles() ?>
    <style>
        /* Override some base styles for clean printing */
        body {
            background: #fff !important;
            margin: 0;
            padding: 0;
        }

        .app-container {
            display: block;
            min-height: auto;
        }

        .main-content {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body class="print-layout">
    <div class="no-print" style="position: fixed; top: 20px; left: 20px; display: flex; gap: 10px; z-index: 100;">
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Now</button>
        <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
    </div>

    <div class="coupon-print-card style-<?= htmlspecialchars($style) ?>">
        <div style="font-size: 1.2rem; font-weight: 600; opacity: 0.8;">
            <?= htmlspecialchars($restaurant_name) ?>
        </div>

        <div class="print-code">
            <?= htmlspecialchars($coupon['code']) ?>
        </div>

        <div style="font-size: 1.5rem; font-weight: 700; color: #000; margin-bottom: 10px;">
            Value: -
            <?= floatval($coupon['value']) ?>
            <?= $coupon['type'] == 'percent' ? '%' : ' €' ?>
        </div>

        <?php if ($coupon['expiration_date']): ?>
            <div style="font-size: 0.9rem; opacity: 0.7; margin-top: 15px;">
                Valid until:
                <?= date('M d, Y', strtotime($coupon['expiration_date'])) ?>
            </div>
        <?php endif; ?>

        <div style="font-size: 0.8rem; opacity: 0.5; margin-top: 20px; font-style: italic;">
            Thank you for your business!
        </div>
    </div>

    <script>
        // Auto-trigger print if not already printing
        window.onload = function () {
            setTimeout(() => {
                // window.print();
            }, 500);
        };
    </script>
</body>

</html>