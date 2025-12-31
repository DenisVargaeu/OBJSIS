<?php
// admin/settings.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = $_POST['restaurant_name'] ?? 'OBJSIS Restaurant';

    // Upsert Setting
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('restaurant_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$restaurant_name, $restaurant_name]);

    setFlashMessage("Settings updated successfully!");
    header("Location: settings.php");
    exit;
}

$restaurant_name = getSetting('restaurant_name', 'OBJSIS Restaurant');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <?= htmlspecialchars(getSetting('restaurant_name')) ?>
                <div style="font-size: 0.8rem; opacity: 0.5; font-weight: normal; margin-top: 5px;">
                    <?= OBJSIS_VERSION ?>
                </div>
            </div>
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="menu.php" class="nav-link">
                        <i class="fas fa-utensils"></i> Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tables.php" class="nav-link">
                        <i class="fas fa-chair"></i> Tables
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shifts.php" class="nav-link">
                        <i class="fas fa-clock"></i> Shifts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="coupons.php" class="nav-link">
                        <i class="fas fa-ticket-alt"></i> Coupons
                    </a>
                </li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i> Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="stats.php" class="nav-link">
                            <i class="fas fa-chart-line"></i> Statistics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link">
                            <i class="fas fa-history"></i> History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link active">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="user-profile">
                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <div
                    style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                    <?= htmlspecialchars($_SESSION['user_role']) ?>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <a href="../logout.php" style="color: var(--primary-color); font-size: 0.9rem; font-weight: 500;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <button onclick="toggleTheme()"
                        style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">
                        <i class="fas fa-adjust"></i>
                    </button>
                </div>
            </div>
            <script src="../assets/js/theme.js"></script>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">System Settings</h1>

            <?php
            $flash = getFlashMessage();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px;">
                <form method="POST">
                    <div class="form-group">
                        <label for="restaurant_name">Restaurant Name</label>
                        <input type="text" id="restaurant_name" name="restaurant_name"
                            value="<?= htmlspecialchars($restaurant_name) ?>" required>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">This name will be
                            displayed on the login page, dashboard, and customer kiosk.</p>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <script src="../assets/js/theme.js"></script>
</body>

</html>