<?php
// admin/settings.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_customization'])) {
        $keys_to_reset = ['primary_color', 'primary_hover', 'bg_image_kiosk', 'bg_image_login'];
        $placeholders = str_repeat('?,', count($keys_to_reset) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys_to_reset);

        setFlashMessage("Appearance settings reset to defaults.");
        header("Location: settings.php");
        exit;
    }

    $restaurant_name = $_POST['restaurant_name'] ?? 'OBJSIS Restaurant';
    $primary_color = $_POST['primary_color'] ?? '#f97316';
    $primary_hover = $_POST['primary_hover'] ?? '#ea580c';

    $bg_type_kiosk = $_POST['bg_type_kiosk'] ?? 'image';
    $bg_image_kiosk = $_POST['bg_image_kiosk'] ?? '';
    $bg_color_kiosk = $_POST['bg_color_kiosk'] ?? '#0f172a';

    $bg_type_login = $_POST['bg_type_login'] ?? 'gradient';
    $bg_image_login = $_POST['bg_image_login'] ?? '';
    $bg_color_login = $_POST['bg_color_login'] ?? '#0f172a';

    $settings = [
        'restaurant_name' => $restaurant_name,
        'primary_color' => $primary_color,
        'primary_hover' => $primary_hover,
        'bg_type_kiosk' => $bg_type_kiosk,
        'bg_image_kiosk' => $bg_image_kiosk,
        'bg_color_kiosk' => $bg_color_kiosk,
        'bg_type_login' => $bg_type_login,
        'bg_image_login' => $bg_image_login,
        'bg_color_login' => $bg_color_login
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }

    setFlashMessage("Settings updated successfully!");
    header("Location: settings.php");
    exit;
}

$restaurant_name = getSetting('restaurant_name', 'OBJSIS Restaurant');
$primary_color = getSetting('primary_color', '#f97316');
$primary_hover = getSetting('primary_hover', '#ea580c');

// Kiosk
$bg_type_kiosk = getSetting('bg_type_kiosk', 'image');
$bg_image_kiosk = getSetting('bg_image_kiosk', '');
$bg_color_kiosk = getSetting('bg_color_kiosk', '#0f172a');

// Login
$bg_type_login = getSetting('bg_type_login', 'gradient');
$bg_image_login = getSetting('bg_image_login', '');
$bg_color_login = getSetting('bg_color_login', '#0f172a');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>

<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>

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
                    <h3><i class="fas fa-building"></i> General</h3>
                    <div class="form-group">
                        <label for="restaurant_name">Restaurant Name</label>
                        <input type="text" id="restaurant_name" name="restaurant_name"
                            value="<?= htmlspecialchars($restaurant_name) ?>" required>
                    </div>

                    <h3 style="margin-top: 2rem;"><i class="fas fa-palette"></i> Appearance</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="primary_color">Primary Color</label>
                            <input type="color" id="primary_color" name="primary_color"
                                value="<?= htmlspecialchars($primary_color) ?>" style="height: 45px; padding: 5px;">
                        </div>
                        <div class="form-group">
                            <label for="primary_hover">Hover Color</label>
                            <input type="color" id="primary_hover" name="primary_hover"
                                value="<?= htmlspecialchars($primary_hover) ?>" style="height: 45px; padding: 5px;">
                        </div>
                    </div>

                    <h3 style="margin-top: 2rem;"><i class="fas fa-image"></i> Kiosk Appearance</h3>
                    <div class="form-group">
                        <label for="bg_type_kiosk">Background Type</label>
                        <select id="bg_type_kiosk" name="bg_type_kiosk">
                            <option value="image" <?= $bg_type_kiosk === 'image' ? 'selected' : '' ?>>Image URL</option>
                            <option value="color" <?= $bg_type_kiosk === 'color' ? 'selected' : '' ?>>Solid Color</option>
                        </select>
                    </div>
                    <div class="form-group" id="kiosk_image_group"
                        style="<?= $bg_type_kiosk === 'color' ? 'display:none;' : '' ?>">
                        <label for="bg_image_kiosk">Background Image URL</label>
                        <input type="text" id="bg_image_kiosk" name="bg_image_kiosk"
                            value="<?= htmlspecialchars($bg_image_kiosk) ?>"
                            placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="form-group" id="kiosk_color_group"
                        style="<?= $bg_type_kiosk === 'image' ? 'display:none;' : '' ?>">
                        <label for="bg_color_kiosk">Background Color</label>
                        <input type="color" id="bg_color_kiosk" name="bg_color_kiosk"
                            value="<?= htmlspecialchars($bg_color_kiosk) ?>" style="height: 45px; padding: 5px;">
                    </div>

                    <h3 style="margin-top: 2rem;"><i class="fas fa-sign-in-alt"></i> Login Page Appearance</h3>
                    <div class="form-group">
                        <label for="bg_type_login">Background Type</label>
                        <select id="bg_type_login" name="bg_type_login">
                            <option value="gradient" <?= $bg_type_login === 'gradient' ? 'selected' : '' ?>>Default
                                Gradient</option>
                            <option value="image" <?= $bg_type_login === 'image' ? 'selected' : '' ?>>Image URL</option>
                            <option value="color" <?= $bg_type_login === 'color' ? 'selected' : '' ?>>Solid Color</option>
                        </select>
                    </div>
                    <div class="form-group" id="login_image_group"
                        style="<?= $bg_type_login !== 'image' ? 'display:none;' : '' ?>">
                        <label for="bg_image_login">Background Image URL</label>
                        <input type="text" id="bg_image_login" name="bg_image_login"
                            value="<?= htmlspecialchars($bg_image_login) ?>" placeholder="https://example.com/bg.jpg">
                    </div>
                    <div class="form-group" id="login_color_group"
                        style="<?= $bg_type_login !== 'color' ? 'display:none;' : '' ?>">
                        <label for="bg_color_login">Background Color</label>
                        <input type="color" id="bg_color_login" name="bg_color_login"
                            value="<?= htmlspecialchars($bg_color_login) ?>" style="height: 45px; padding: 5px;">
                    </div>

                    <script>
                        document.getElementById('bg_type_kiosk').addEventListener('change', function () {
                            document.getElementById('kiosk_image_group').style.display = this.value === 'image' ? 'block' : 'none';
                            document.getElementById('kiosk_color_group').style.display = this.value === 'color' ? 'block' : 'none';
                        });
                        document.getElementById('bg_type_login').addEventListener('change', function () {
                            document.getElementById('login_image_group').style.display = this.value === 'image' ? 'block' : 'none';
                            document.getElementById('login_color_group').style.display = this.value === 'color' ? 'block' : 'none';
                        });
                    </script>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 1rem;">
                        <button type="submit" class="btn">
                            <i class="fas fa-save" style="margin-right: 8px;"></i> Save Changes
                        </button>
                        <button type="submit" name="reset_customization" class="btn"
                            style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border-color);"
                            onclick="return confirm('Reset all colors and backgrounds to defaults?')">
                            <i class="fas fa-undo" style="margin-right: 8px;"></i> Reset Defaults
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <script src="../assets/js/theme.js"></script>
</body>

</html>