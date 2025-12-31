<?php
// index.php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Handle Exit Table
if (isset($_GET['exit'])) {
    setcookie('active_table', '', time() - 3600, "/");
    header("Location: index.php");
    exit;
}

$table_number = $_GET['table'] ?? $_COOKIE['active_table'] ?? null;

// Set Table Cookie if provided in URL
if (isset($_GET['table'])) {
    setcookie('active_table', $_GET['table'], time() + (86400 * 1), "/"); // 1 day
}

// Fetch Active Orders for this table (Status Check)
$active_orders = [];
if ($table_number) {
    try {
        $stmt_orders = $pdo->prepare("
            SELECT o.*, 
            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR '||') as item_details
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE o.table_number = ? AND o.status != 'paid' AND o.status != 'cancelled' 
            GROUP BY o.id
            ORDER BY o.id DESC
        ");
        $stmt_orders->execute([$table_number]);
        $active_orders = $stmt_orders->fetchAll();
    } catch (Exception $e) { /* Ignore */
    }
}

// Fetch Menu Items
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
    $categories = $stmt->fetchAll();

    $menu = [];
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1");
        $stmt->execute([$cat['id']]);
        $items = $stmt->fetchAll();
        if (!empty($items)) {
            $menu[$cat['name']] = $items;
        }
    }
    // Fetch Tables for Landing Page
    $tables = [];
    if (!$table_number) {
        $stmt = $pdo->query("SELECT * FROM tables ORDER BY id");
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // If table fetch failed, just ignore
    $tables = [];
    $categories = [];
    $menu = [];
    $db_error = $e->getMessage();
}

$page_title = $table_number ? "Table $table_number" : "Welcome";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(getSetting('restaurant_name', 'OBJSIS')) ?> Menu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/kiosk_improvements.css">
    <style>
        .tables-grid-customer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .table-card-customer {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .status-free {
            border-color: var(--success);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.1);
        }

        .status-free:hover {
            transform: translateY(-5px);
            background: rgba(34, 197, 94, 0.1);
        }

        .status-occupied {
            border-color: var(--danger);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .status-occupied::after {
            content: 'OCCUPIED';
            position: absolute;
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--danger);
            bottom: 10px;
        }

        .status-reserved {
            border-color: var(--warning);
            opacity: 0.7;
            cursor: not-allowed;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <?php if (!$table_number): ?>
        <!-- Landing Page Kiosk View -->
        <!-- Landing Page Kiosk View: Table Selection -->
        <div class="kiosk-hero"
            style="min-height: 100vh; margin: 0; display:flex; flex-direction:column; align-items:center; overflow-y: auto;">
            <div style="text-align: center; margin-top: 60px; margin-bottom: 20px; z-index:2;">
                <h1 style="font-size: 3.5rem; margin-bottom: 10px; text-shadow: 0 4px 20px rgba(0,0,0,0.5);">Welcome to
                    <?= htmlspecialchars(getSetting('restaurant_name', 'OBJSIS')) ?>
                </h1>
                <p style="font-size: 1.2rem; opacity: 0.9;">Please select your table to start ordering</p>
            </div>

            <div class="tables-grid-customer" style="width: 100%; z-index:2; padding-bottom: 50px;">
                <?php foreach ($tables as $tbl): ?>
                    <?php if ($tbl['status'] === 'free'): ?>
                        <a href="?table=<?= $tbl['id'] ?>" class="table-card-customer status-free">
                            <i class="fas fa-utensils" style="font-size: 2rem; margin-bottom: 10px; color: var(--success);"></i>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?= htmlspecialchars($tbl['name']) ?></div>
                            <div style="font-size: 0.9rem; opacity: 0.7;">Capacity: <?= $tbl['capacity'] ?></div>
                        </a>
                    <?php else: ?>
                        <div class="table-card-customer status-<?= $tbl['status'] ?>">
                            <i class="fas fa-ban" style="font-size: 2rem; margin-bottom: 10px; color: var(--danger);"></i>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?= htmlspecialchars($tbl['name']) ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 2rem; z-index: 2; margin-bottom: 20px;">
                <a href="login.php" style="color: var(--text-muted); opacity: 0.6; font-size: 0.9rem;">Staff Login</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Menu Kiosk View -->
        <div class="kiosk-hero">
            <div class="table-info-container">
                <div class="table-badge-large">
                    <i class="fas fa-map-marker-alt" style="color:var(--primary-color)"></i>
                    Table <?= htmlspecialchars($table_number) ?>
                </div>

                <?php if (!empty($active_orders)): ?>
                    <?php foreach ($active_orders as $ao): ?>
                        <div onclick="showOrderDetails(<?= htmlspecialchars(json_encode($ao)) ?>)" class="order-status-chip">
                            <span>Order #<?= $ao['id'] ?></span>
                            <span style="font-size:0.8em; opacity:0.8; text-transform:uppercase;"><?= $ao['status'] ?></span>
                            <i class="fas fa-chevron-right" style="font-size:0.8em;"></i>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="z-index: 2; position: relative;">
                <h1 style="font-size: 3rem; margin-bottom: 0.5rem; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                    <?= !empty($active_orders) ? 'Ordering More?' : 'Our Menu' ?>
                </h1>
                <p style="color: rgba(255,255,255,0.9); text-shadow: 0 1px 4px rgba(0,0,0,0.5);">
                    <?= !empty($active_orders) ? 'Add delicious items to your active orders' : 'Select a category to browse' ?>
                </p>
            </div>
        </div>

        <?php if (!empty($categories)): ?>
            <!-- Sticky Nav -->
            <div class="kiosk-nav-wrapper">
                <nav class="kiosk-nav">
                    <?php foreach ($categories as $i => $category): ?>
                        <?php if (isset($menu[$category['name']])): ?>
                            <a href="#cat-<?= $i ?>" class="kiosk-category <?= $i === 0 ? 'active' : '' ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="container" style="padding-bottom: 100px;">
                <?php foreach ($categories as $i => $category): ?>
                    <?php if (isset($menu[$category['name']])): ?>
                        <div id="cat-<?= $i ?>" style="scroll-margin-top: 120px; margin-bottom: 4rem;">
                            <h2
                                style="font-size: 2rem; margin-bottom: 1.5rem; padding-left: 10px; border-left: 4px solid var(--primary-color);">
                                <?= htmlspecialchars($category['name']) ?>
                            </h2>

                            <div class="menu-grid">
                                <?php foreach ($menu[$category['name']] as $item): ?>
                                    <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                                        <div style="height: 200px; background-color: #333; position: relative;">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                                    style="width:100%; height:100%; object-fit:cover; border-radius: 0;">
                                            <?php else: ?>
                                                <div
                                                    style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.1); font-size:3rem;">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div
                                                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #fff; padding: 5px 10px; border-radius: 20px; font-weight: bold;">
                                                <?= number_format($item['price'], 2) ?> €
                                            </div>
                                        </div>

                                        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                                            <h3 style="margin-bottom: 10px;"><?= htmlspecialchars($item['name']) ?></h3>
                                            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px; flex: 1;">
                                                <?= htmlspecialchars($item['description']) ?>
                                            </p>
                                            <button class="btn" style="width: 100%;"
                                                onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>)">
                                                Add <i class="fas fa-plus" style="margin-left: 8px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Order Details Modal -->
            <div id="order-details-modal"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2001; justify-content:center; align-items:center;">
                <div class="card" style="width: 350px; max-width:90%;">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                        <h3 id="od-title">Order #</h3>
                        <button onclick="document.getElementById('order-details-modal').style.display='none'"
                            style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i
                                class="fas fa-times"></i></button>
                    </div>
                    <div id="od-content" style="margin-bottom:20px; color:var(--text-main);"></div>
                    <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:1.1rem;">
                        <span>Total:</span>
                        <span id="od-total"></span>
                    </div>
                    <div style="margin-top:20px;">
                        <span id="od-status" class="user-badge" style="width:100%; text-align:center; display:block;"></span>
                    </div>
                </div>
            </div>

            <script>
                function showOrderDetails(order) {
                    document.getElementById('od-title').innerText = 'Order #' + order.id;
                    document.getElementById('od-total').innerText = parseFloat(order.total_price).toFixed(2) + ' €';

                    document.getElementById('od-status').innerText = order.status.toUpperCase();
                    document.getElementById('od-status').style.background = 'var(--primary-color)';

                    const items = order.item_details.split('||');
                    let html = '<ul style="list-style:none; padding:0;">';
                    items.forEach(item => {
                        html += `<li style="margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.05);">${item}</li>`;
                    });
                    html += '</ul>';
                    document.getElementById('od-content').innerHTML = html;

                    document.getElementById('order-details-modal').style.display = 'flex';
                }
            </script>

            <!-- Cart Floating Action Button (FAB) -->
            <div id="floating-cart-btn" onclick="toggleCart()"
                style="display:none; position:fixed; bottom:30px; right:30px; width: 70px; height: 70px; background:var(--primary-color); border-radius:50%; box-shadow: var(--shadow-glow); cursor:pointer; display: flex; align-items: center; justify-content: center; z-index: 1000;">
                <i class="fas fa-shopping-basket" style="font-size: 1.8rem; color: #fff;"></i>
                <span id="cart-count"
                    style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; border: 2px solid var(--bg-color);">0</span>
            </div>

            <!-- Cart Drawer -->
            <div id="cart-modal">
                <div class="cart-header">
                    <h3 style="margin:0;">Your Order</h3>
                    <button onclick="toggleCart()"
                        style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.5rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="cart-body">
                    <div id="cart-items"></div>
                </div>

                <div class="cart-footer">
                    <!-- Coupon Section -->
                    <div
                        style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; margin-bottom: 15px; border:1px solid var(--border-color);">
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="coupon-input" placeholder="Promo Code"
                                style="flex:1; padding:8px 12px; font-size:0.9rem;">
                            <button onclick="applyCoupon()" class="btn"
                                style="padding:8px 15px; font-size:0.85rem;">Apply</button>
                        </div>
                        <div id="coupon-msg" style="font-size: 0.85rem; margin-top: 5px;"></div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div
                            style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem; color:var(--text-muted);">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal">0.00 €</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem; color: var(--success); display:none;"
                            id="discount-row">
                            <span>Discount:</span>
                            <span id="cart-discount">-0.00 €</span>
                        </div>
                        <div
                            style="display:flex; justify-content:space-between; font-weight:700; font-size: 1.3rem; color:var(--text-main); border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 5px;">
                            <span>Total:</span>
                            <span><span id="cart-total">0.00</span> €</span>
                        </div>
                    </div>

                    <button onclick="placeOrder()" class="btn"
                        style="width:100%; padding: 15px; font-size: 1.1rem; justify-content: center; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);">
                        Confirm Order <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
                    </button>
                </div>
            </div>
            </div>

            <p
                style="text-align: center; margin-top: 2rem; position: fixed; bottom: 10px; left: 0; width: 100%; pointer-events: none; display: flex; flex-direction: column; gap: 5px;">
                <a href="?exit=1" style="color: rgba(255,255,255,0.2); pointer-events: auto; font-size: 0.8rem;">Exit
                    Table</a>
                <span
                    style="color: rgba(255,255,255,0.1); font-size: 0.7rem; pointer-events: auto;"><?= OBJSIS_VERSION ?></span>
            </p>

            <script src="assets/js/app.js"></script>
        <?php endif; ?>
    <?php endif; ?>
    <div onclick="toggleTheme()"
        style="position:fixed; top:20px; left:20px; width:40px; height:40px; background:var(--card-bg-glass); border:1px solid var(--border-color); border-radius:50%; display:flex; justify-content:center; align-items:center; cursor:pointer; z-index:2000; backdrop-filter:blur(5px); color:var(--text-muted); box-shadow:var(--shadow-sm);">
        <i class="fas fa-adjust"></i>
    </div>

    <script src="assets/js/theme.js"></script>
</body>

</html>