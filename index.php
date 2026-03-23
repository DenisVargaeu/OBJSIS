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
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ?");
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
$restaurant_name = getSetting('restaurant_name', 'OBJSIS');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant_name) ?> Menu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/kiosk_improvements.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?= getCustomStyles() ?>
    <style>
        /* ===== FULL PAGE RESET ===== */
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0; padding: 0;
            background: var(--bg-color, #0a0a1a);
            color: var(--text-main, #e2e8f0);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== ANIMATED BACKGROUND ===== */
        .page-bg {
            position: fixed; inset: 0; z-index: 0;
            background: var(--bg-color, #0a0a1a);
            overflow: hidden;
        }
        .page-bg::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--primary-color, #f97316) 0%, transparent 70%);
            opacity: 0.06;
            top: -200px; right: -200px;
            animation: floatOrb1 20s ease-in-out infinite;
        }
        .page-bg::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, #3b82f6 0%, transparent 70%);
            opacity: 0.04;
            bottom: -150px; left: -150px;
            animation: floatOrb2 25s ease-in-out infinite;
        }
        @keyframes floatOrb1 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-80px, 60px); }
        }
        @keyframes floatOrb2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(60px, -80px); }
        }

        /* ===== MAIN WRAPPER ===== */
        .page-content {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }

        /* ===== LANDING: TABLE SELECTION ===== */
        .landing-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .landing-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .landing-header .subtitle {
            font-size: 1.1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--primary-color, #f97316);
            margin-bottom: 12px;
        }
        .landing-header h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 900;
            margin: 0 0 16px 0;
            letter-spacing: -1.5px;
            line-height: 1.1;
        }
        .landing-header h1 .restaurant-name {
            display: block;
            background: linear-gradient(135deg, #fff 0%, var(--primary-color, #f97316) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .landing-header .description {
            font-size: 1.15rem;
            color: var(--text-muted, #94a3b8);
            font-weight: 400;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Table Grid */
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 24px;
            max-width: 1100px;
            width: 100%;
            padding: 0 20px;
        }
        .table-tile {
            position: relative;
            height: 180px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .table-tile::before {
            content: '';
            position: absolute; inset: 0;
            border-radius: 20px;
            border: 1.5px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            transition: all 0.35s ease;
        }
        .table-tile > * { position: relative; z-index: 1; }
        .table-tile .table-icon {
            font-size: 2.2rem;
            margin-bottom: 14px;
            transition: transform 0.35s ease;
        }
        .table-tile .table-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .table-tile .table-cap {
            font-size: 0.85rem;
            opacity: 0.6;
        }

        /* Free */
        .table-tile.tile-free::before { border-color: rgba(34, 197, 94, 0.2); }
        .table-tile.tile-free .table-icon { color: #22c55e; }
        .table-tile.tile-free:hover {
            transform: translateY(-6px);
        }
        .table-tile.tile-free:hover::before {
            border-color: rgba(34, 197, 94, 0.5);
            background: rgba(34, 197, 94, 0.06);
            box-shadow: 0 20px 50px rgba(34, 197, 94, 0.15);
        }
        .table-tile.tile-free:hover .table-icon { transform: scale(1.15); }

        /* Occupied */
        .table-tile.tile-occupied {
            cursor: not-allowed;
            opacity: 0.45;
            filter: grayscale(0.4);
        }
        .table-tile.tile-occupied::before { border-color: rgba(239, 68, 68, 0.15); }
        .table-tile.tile-occupied .table-icon { color: #ef4444; }
        .table-tile.tile-occupied .table-status-tag {
            position: absolute; bottom: 16px;
            font-size: 0.7rem; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 3px 10px; border-radius: 20px;
        }

        /* Reserved */
        .table-tile.tile-reserved {
            cursor: not-allowed;
            opacity: 0.45;
        }
        .table-tile.tile-reserved::before { border-color: rgba(245, 158, 11, 0.15); }
        .table-tile.tile-reserved .table-icon { color: #f59e0b; }
        .table-tile.tile-reserved .table-status-tag {
            position: absolute; bottom: 16px;
            font-size: 0.7rem; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase;
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 3px 10px; border-radius: 20px;
        }

        .landing-footer {
            margin-top: 50px;
            text-align: center;
        }
        .landing-footer a {
            color: var(--text-muted, #64748b);
            font-size: 0.85rem;
            text-decoration: none;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        .landing-footer a:hover { opacity: 1; }

        /* ===== MENU VIEW ===== */
        .menu-topbar {
            position: sticky;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: rgba(10, 10, 26, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .topbar-brand {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--primary-color, #f97316);
            white-space: nowrap;
        }
        .topbar-table-badge {
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.06);
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .topbar-table-badge i { color: var(--primary-color, #f97316); }
        .topbar-search {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        .topbar-search input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .topbar-search input:focus {
            outline: none;
            border-color: var(--primary-color, #f97316);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.15);
        }
        .topbar-search i {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted, #64748b);
            font-size: 0.9rem;
        }
        .topbar-actions {
            display: flex; gap: 10px; align-items: center;
        }
        .topbar-btn {
            width: 40px; height: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.04);
            color: var(--text-muted, #94a3b8);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            font-weight: 800; font-size: 0.85rem;
        }
        .topbar-btn:hover {
            background: var(--primary-color, #f97316);
            color: #fff;
            border-color: var(--primary-color, #f97316);
            transform: scale(1.05);
        }

        /* Active order chips in topbar */
        .topbar-orders {
            display: flex; gap: 8px; align-items: center;
        }
        .topbar-order-chip {
            background: rgba(249, 115, 22, 0.12);
            border: 1px solid rgba(249, 115, 22, 0.3);
            color: var(--primary-color, #f97316);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
        }
        .topbar-order-chip:hover {
            background: var(--primary-color, #f97316);
            color: #fff;
        }

        /* Category pills row */
        .category-bar {
            display: flex;
            gap: 10px;
            padding: 16px 24px;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            background: rgba(10, 10, 26, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .category-bar::-webkit-scrollbar { display: none; }
        .cat-pill {
            flex-shrink: 0;
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted, #94a3b8);
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .cat-pill:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .cat-pill.active {
            background: var(--primary-color, #f97316);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.3);
        }

        /* Menu content area */
        .menu-body {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 24px 140px 24px;
        }
        .category-section {
            margin-bottom: 60px;
            scroll-margin-top: 140px;
        }
        .category-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0 0 24px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .category-title::before {
            content: '';
            width: 4px; height: 28px;
            border-radius: 4px;
            background: var(--primary-color, #f97316);
            flex-shrink: 0;
        }

        /* Food cards */
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 22px;
        }
        .food-card {
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.025);
        }
        .food-card:hover {
            transform: translateY(-6px);
            border-color: rgba(255,255,255,0.12);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .food-card.sold-out {
            filter: grayscale(1) brightness(0.5);
            pointer-events: none;
        }
        .food-card .food-img {
            height: 200px;
            overflow: hidden;
            background: #111;
            position: relative;
        }
        .food-card .food-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .food-card:hover .food-img img {
            transform: scale(1.06);
        }
        .food-card .food-img .placeholder-icon {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.06);
            font-size: 3rem;
        }
        .food-card .price-tag {
            position: absolute;
            top: 14px; right: 14px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1rem;
            color: #fff;
        }
        .food-card .food-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .food-card .food-info h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 6px 0;
        }
        .food-card .food-info .allergen-info {
            font-size: 0.78rem;
            color: var(--warning, #f59e0b);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .food-card .food-info .food-desc {
            font-size: 0.9rem;
            color: var(--text-muted, #94a3b8);
            line-height: 1.5;
            margin: 0 0 16px 0;
            flex: 1;
        }
        .food-card .add-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: var(--primary-color, #f97316);
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: 'Inter', sans-serif;
        }
        .food-card .add-btn:hover {
            filter: brightness(1.15);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
            transform: translateY(-1px);
        }
        .food-card .add-btn.disabled-btn {
            background: rgba(255,255,255,0.06);
            color: var(--text-muted, #64748b);
            cursor: not-allowed;
            box-shadow: none;
        }
        .food-card .add-btn.disabled-btn:hover {
            filter: none;
            transform: none;
        }

        /* No-photo mode card */
        .food-card.no-photo .price-tag {
            position: relative;
            top: auto; right: auto;
            display: inline-block;
            margin-bottom: 10px;
        }

        /* Cart FAB */
        .cart-fab {
            position: fixed;
            bottom: 30px; right: 30px;
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--primary-color, #f97316);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            cursor: pointer;
            box-shadow: 0 8px 30px rgba(249, 115, 22, 0.4);
            z-index: 900;
            transition: all 0.3s ease;
            border: none;
        }
        .cart-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(249, 115, 22, 0.5);
        }
        .cart-fab .fab-count {
            position: absolute;
            top: -4px; right: -4px;
            width: 24px; height: 24px;
            border-radius: 50%;
            background: #ef4444;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid var(--bg-color, #0a0a1a);
        }

        /* Bottom bar */
        .bottom-strip {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            text-align: center;
            padding: 8px;
            pointer-events: none;
            z-index: 50;
        }
        .bottom-strip a, .bottom-strip span {
            pointer-events: auto;
            color: rgba(255,255,255,0.15);
            font-size: 0.75rem;
            text-decoration: none;
            margin: 0 8px;
        }
        .bottom-strip a:hover { color: rgba(255,255,255,0.5); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .topbar-search { max-width: 200px; }
            .topbar-orders { display: none; }
            .menu-topbar { padding: 10px 16px; flex-wrap: wrap; }
            .food-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
            .table-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; }
        }
        @media (max-width: 480px) {
            .topbar-search { max-width: 100%; flex: 1 1 100%; order: 10; }
            .food-grid { grid-template-columns: 1fr; }
        }
    </style>
    <script src="assets/js/theme.js"></script>
</head>

<body>
    <div class="page-bg"></div>
    <div class="page-content">

    <?php if (!$table_number): ?>
        <!-- ========== LANDING PAGE: TABLE SELECTION ========== -->
        <div class="landing-wrapper">
            <div class="landing-header">
                <div class="subtitle" data-i18n="welcome">Welcome to</div>
                <h1><span class="restaurant-name"><?= htmlspecialchars($restaurant_name) ?></span></h1>
                <p class="description" data-i18n="select_table">Please select your table to begin ordering</p>
            </div>

            <div class="table-grid">
                <?php foreach ($tables as $tbl): ?>
                    <?php if ($tbl['status'] === 'free'): ?>
                        <a href="?table=<?= $tbl['id'] ?>" class="table-tile tile-free">
                            <i class="fas fa-utensils table-icon"></i>
                            <div class="table-name"><?= htmlspecialchars($tbl['name']) ?></div>
                            <div class="table-cap"><span data-i18n="capacity">Capacity</span>: <?= $tbl['capacity'] ?></div>
                        </a>
                    <?php elseif ($tbl['status'] === 'reserved'): ?>
                        <div class="table-tile tile-reserved">
                            <i class="fas fa-bookmark table-icon"></i>
                            <div class="table-name"><?= htmlspecialchars($tbl['name']) ?></div>
                            <span class="table-status-tag">Reserved</span>
                        </div>
                    <?php else: ?>
                        <div class="table-tile tile-occupied">
                            <i class="fas fa-users table-icon"></i>
                            <div class="table-name"><?= htmlspecialchars($tbl['name']) ?></div>
                            <span class="table-status-tag">Occupied</span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="landing-footer">
                <a href="login.php" data-i18n="staff_login">Staff Login</a>
            </div>
        </div>

    <?php else: ?>
        <!-- ========== MENU VIEW ========== -->

        <!-- Top Bar -->
        <div class="menu-topbar">
            <div class="topbar-brand"><?= htmlspecialchars($restaurant_name) ?></div>

            <div class="topbar-table-badge">
                <i class="fas fa-map-marker-alt"></i>
                <span data-i18n="table">Table</span> <?= htmlspecialchars($table_number) ?>
            </div>

            <?php if (!empty($active_orders)): ?>
                <div class="topbar-orders">
                    <?php foreach ($active_orders as $ao): ?>
                        <div class="topbar-order-chip" onclick="showOrderDetails(<?= htmlspecialchars(json_encode($ao)) ?>)">
                            #<?= $ao['id'] ?> · <?= strtoupper($ao['status']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="topbar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="menu-search" onkeyup="filterMenu()" data-i18n="search_hint" placeholder="Search menu...">
            </div>

            <div class="topbar-actions">
                <div class="topbar-btn" onclick="toggleTheme()" title="Toggle Theme"><i class="fas fa-adjust"></i></div>
                <div class="topbar-btn" onclick="toggleLanguage()" title="Switch Language"><span id="lang-indicator">EN</span></div>
                <div class="topbar-btn" onclick="toggleTracking()" title="Track Orders"><i class="fas fa-clock-rotate-left"></i></div>
            </div>
        </div>

        <?php if (!empty($categories)): ?>
            <!-- Category Pills -->
            <div class="category-bar">
                <?php foreach ($categories as $i => $category): ?>
                    <?php if (isset($menu[$category['name']])): ?>
                        <a href="#cat-<?= $i ?>" class="cat-pill <?= $i === 0 ? 'active' : '' ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Food Items -->
            <div class="menu-body">
                <?php foreach ($categories as $i => $category): ?>
                    <?php if (isset($menu[$category['name']])): ?>
                        <div id="cat-<?= $i ?>" class="category-section">
                            <h2 class="category-title"><?= htmlspecialchars($category['name']) ?></h2>

                            <div class="food-grid">
                                <?php foreach ($menu[$category['name']] as $item): ?>
                                    <div class="food-card <?= !$item['is_available'] ? 'sold-out' : '' ?> <?= getSetting('show_menu_photos', '1') !== '1' ? 'no-photo' : '' ?>">
                                        
                                        <?php if (!$item['is_available']): ?>
                                            <div class="sold-out-badge" data-i18n="sold_out">SOLD OUT</div>
                                        <?php endif; ?>

                                        <?php if (getSetting('show_menu_photos', '1') === '1'): ?>
                                            <div class="food-img">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                                                <?php else: ?>
                                                    <div class="placeholder-icon"><i class="fas fa-utensils"></i></div>
                                                <?php endif; ?>
                                                <div class="price-tag"><?= number_format($item['price'], 2) ?> €</div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="food-info">
                                            <?php if (getSetting('show_menu_photos', '1') !== '1'): ?>
                                                <div class="price-tag"><?= number_format($item['price'], 2) ?> €</div>
                                            <?php endif; ?>

                                            <h3><?= htmlspecialchars($item['name']) ?></h3>

                                            <?php if ($item['allergens']): ?>
                                                <div class="allergen-info">
                                                    <i class="fas fa-exclamation-triangle"></i> <span data-i18n="allergens">Allergens</span>: <?= htmlspecialchars($item['allergens']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <p class="food-desc"><?= htmlspecialchars($item['description']) ?></p>

                                            <?php if ($item['is_available']): ?>
                                                <button class="add-btn" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>)">
                                                    <span data-i18n="add">Add to Order</span> <i class="fas fa-plus"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="add-btn disabled-btn" disabled>
                                                    <span data-i18n="out_of_stock">Out of Stock</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Order Details Modal -->
            <div id="order-details-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2001; justify-content:center; align-items:center;">
                <div class="card" style="width: 380px; max-width:90%; border-radius: 20px; padding: 28px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                        <h3 id="od-title">Order #</h3>
                        <button onclick="document.getElementById('order-details-modal').style.display='none'" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
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

            <!-- Cart FAB -->
            <div id="floating-cart-btn" class="cart-fab" onclick="toggleCart()" style="display:none;">
                <i class="fas fa-shopping-basket"></i>
                <span id="cart-count" class="fab-count">0</span>
            </div>

            <!-- Cart Drawer -->
            <div id="cart-modal">
                <div class="cart-header">
                    <h3 style="margin:0;" data-i18n="your_order">Your Order</h3>
                    <button onclick="toggleCart()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.5rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="cart-body">
                    <div id="cart-items"></div>
                </div>

                <div class="cart-footer">
                    <!-- Coupon Section -->
                    <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; margin-bottom: 15px; border:1px solid var(--border-color);">
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="coupon-input" placeholder="Promo Code" style="flex:1; padding:8px 12px; font-size:0.9rem;">
                            <button onclick="applyCoupon()" class="btn" style="padding:8px 15px; font-size:0.85rem;">Apply</button>
                        </div>
                        <div id="coupon-msg" style="font-size: 0.85rem; margin-top: 5px;"></div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem; color:var(--text-muted);">
                            <span data-i18n="subtotal">Subtotal:</span>
                            <span id="cart-subtotal">0.00 €</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.9rem; color: var(--success); display:none;" id="discount-row">
                            <span data-i18n="discount">Discount:</span>
                            <span id="cart-discount">-0.00 €</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-weight:700; font-size: 1.3rem; color:var(--text-main); border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 5px;">
                            <span data-i18n="total">Total:</span>
                            <span><span id="cart-total">0.00</span> €</span>
                        </div>
                    </div>

                    <button onclick="placeOrder()" class="btn" style="width:100%; padding: 15px; font-size: 1.1rem; justify-content: center; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);">
                        <span data-i18n="confirm_order">Confirm Order</span> <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
                    </button>
                </div>
            </div>

            <!-- Bottom strip -->
            <div class="bottom-strip">
                <a href="?exit=1" data-i18n="exit_table">Exit Table</a>
                <span><?= OBJSIS_VERSION ?></span>
            </div>

            <script src="assets/js/app.js"></script>
        <?php endif; ?>
    <?php endif; ?>

    </div><!-- /.page-content -->

    <!-- Tracking Modal -->
    <div id="tracking-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:3000; justify-content:center; align-items:center;">
        <div class="card" style="width:400px; max-width:90%; padding:30px; border-radius: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 data-i18n="order_tracking">Track Your Orders</h3>
                <button onclick="toggleTracking()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.2rem;">&times;</button>
            </div>
            <div id="tracking-content">
                <!-- Statuses loaded here -->
            </div>
        </div>
    </div>

    <script>
        let currentLang = localStorage.getItem('kiosk_lang') || 'en';
        let translations = null;

        async function initI18n() {
            const res = await fetch('assets/lang.json');
            translations = await res.json();
            updateINDICATOR();
            applyTranslations();
        }

        function toggleLanguage() {
            currentLang = currentLang === 'en' ? 'sk' : 'en';
            localStorage.setItem('kiosk_lang', currentLang);
            updateINDICATOR();
            applyTranslations();
        }

        function updateINDICATOR() {
            document.getElementById('lang-indicator').innerText = currentLang.toUpperCase();
        }

        function applyTranslations() {
            if (!translations) return;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[currentLang][key]) {
                    if (el.tagName === 'INPUT') {
                        el.placeholder = translations[currentLang][key];
                    } else {
                        const icon = el.querySelector('i');
                        if (icon) {
                            el.childNodes.forEach(node => {
                                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0) {
                                    node.textContent = translations[currentLang][key];
                                }
                            });
                        } else {
                            el.innerText = translations[currentLang][key];
                        }
                    }
                }
            });
            if(document.getElementById('tracking-modal').style.display === 'flex') {
                updateTracking();
            }
        }

        initI18n();

        // --- Live Search ---
        function filterMenu() {
            const query = document.getElementById('menu-search').value.toLowerCase();
            const items = document.querySelectorAll('.food-card');
            items.forEach(card => {
                const name = card.querySelector('h3')?.innerText.toLowerCase() || '';
                const desc = card.querySelector('.food-desc')?.innerText.toLowerCase() || '';
                card.style.display = (name.includes(query) || desc.includes(query)) ? 'flex' : 'none';
            });
        }

        // --- Category pill active state on scroll ---
        const catPills = document.querySelectorAll('.cat-pill');
        catPills.forEach(pill => {
            pill.addEventListener('click', function(e) {
                catPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // --- Order Tracking ---
        function toggleTracking() {
            const modal = document.getElementById('tracking-modal');
            modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
            if(modal.style.display === 'flex') updateTracking();
        }

        async function updateTracking() {
            const content = document.getElementById('tracking-content');
            const tableId = new URLSearchParams(window.location.search).get('table');
            if(!tableId) return;

            try {
                const res = await fetch(`api/order_status.php?table_id=${tableId}`);
                const data = await res.json();
                
                if(data.success) {
                    if(data.orders.length === 0) {
                        content.innerHTML = `<p style="text-align:center; opacity:0.5;" data-i18n="no_active_orders">${translations[currentLang]['no_active_orders']}</p>`;
                    } else {
                        content.innerHTML = data.orders.map(o => `
                            <div style="padding:15px; border-radius:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-weight:700; color:var(--text-main);">Order #${o.id}</div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);">${new Date(o.created_at).toLocaleTimeString()}</div>
                                </div>
                                <div style="font-size:0.7rem; padding:4px 10px; border-radius:20px; background: var(--primary-color); color:white; font-weight:800; text-transform:uppercase;">
                                    ${translations[currentLang]['status_' + o.status] || o.status}
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } catch(e) {
                console.error("Tracking update failed", e);
            }
        }
        
        // Auto-refresh tracking if modal is open
        setInterval(() => {
            if(document.getElementById('tracking-modal').style.display === 'flex') updateTracking();
        }, 10000);
    </script>

    <script src="assets/js/theme.js"></script>
</body>

</html>