<?php
// admin/receipt.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

if (!isset($_GET['order_id'])) {
    die("Order ID missing");
}

$order_id = $_GET['order_id'];

// Fetch Order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found");
}

// Fetch Items
$stmt_items = $pdo->prepare("
    SELECT oi.*, m.name 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

$restaurant_name = getSetting('restaurant_name', 'My Restaurant');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt #
        <?= $order_id ?>
    </title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #eee;
            padding: 20px;
        }

        .receipt {
            background: #fff;
            width: 300px;
            /* Thermal printer width approx */
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .line-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-section {
            margin-top: 15px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .receipt {
                width: 100%;
                box-shadow: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
    <?= getCustomStyles() ?>
</head>

<body>

    <div class="receipt">
        <div class="text-center bold" style="font-size: 1.2rem; margin-bottom: 5px;">
            <?= htmlspecialchars($restaurant_name) ?>
        </div>
        <div class="text-center" style="font-size: 0.9rem;">Receipt #
            <?= $order_id ?>
        </div>
        <div class="text-center" style="font-size: 0.8rem;">
            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
        </div>
        <div class="text-center" style="font-size:0.9rem; margin-bottom:10px;">Table:
            <?= $order['table_number'] ?>
        </div>

        <div class="divider"></div>

        <?php
        $subtotal = 0;
        foreach ($items as $item):
            $line_total = $item['price_at_time'] * $item['quantity'];
            $subtotal += $line_total;
            ?>
            <div class="line-item">
                <span>
                    <?= $item['quantity'] ?>x
                    <?= htmlspecialchars($item['name']) ?>
                </span>
                <span>
                    <?= number_format($line_total, 2) ?>
                </span>
            </div>
        <?php endforeach; ?>

        <div class="divider"></div>

        <?php if ($order['discount_amount'] > 0): ?>
            <div class="line-item">
                <span>Subtotal</span>
                <span>
                    <?= number_format($subtotal, 2) ?> €
                </span>
            </div>
            <div class="line-item">
                <span>Discount
                    <?= $order['coupon_code'] ? '(' . $order['coupon_code'] . ')' : '' ?>
                </span>
                <span>-
                    <?= number_format($order['discount_amount'], 2) ?> €
                </span>
            </div>
            <div class="divider"></div>
        <?php endif; ?>

        <div class="line-item bold" style="font-size: 1.1rem;">
            <span>TOTAL</span>
            <span>
                <?= number_format($order['total_price'], 2) ?> €
            </span>
        </div>

        <div class="text-center" style="margin-top: 20px; font-size: 0.8rem;">
            Thank you for your visit!
        </div>
    </div>

    <div class="text-center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 1rem; cursor: pointer;">Print
            Receipt</button>
        <br><br>
        <a href="dashboard.php" style="color: #666;">Back to Dashboard</a>
    </div>

</body>

</html>