<?php
// admin/profile.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$msg = null;

// Handle PIN Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pin'])) {
    // SECURITY FIX: CSRF Validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Invalid");
    }
    $current_pin = $_POST['current_pin'];
    $new_pin = $_POST['new_pin'];
    $confirm_pin = $_POST['confirm_pin'];

    $stmt = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && verifyPin($current_pin, $user['pin_hash'])) {
        if ($new_pin === $confirm_pin) {
            if (strlen($new_pin) >= 4) {
                $new_hash = hashPin($new_pin);
                $stmt_upd = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
                $stmt_upd->execute([$new_hash, $user_id]);
                setFlashMessage("PIN updated successfully");
                redirect('profile.php');
            } else {
                $msg = ['type' => 'error', 'message' => 'New PIN must be at least 4 digits'];
            }
        } else {
            $msg = ['type' => 'error', 'message' => 'New PINs do not match'];
        }
    } else {
        $msg = ['type' => 'error', 'message' => 'Current PIN is incorrect'];
    }
}

$page_title = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> - OBJSIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header">
                <div class="page-title-group">
                    <h2><?= $page_title ?></h2>
                    <p style="color:var(--text-muted); margin:0;">Manage your security and account details</p>
                </div>
            </header>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['message'] ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start;">
                <!-- Profile Summary -->
                <div class="stat-card" style="flex-direction: column; align-items: center; padding: 40px 20px; text-align: center;">
                    <div style="width: 120px; height: 120px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; color: white; font-size: 3.5rem; box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </div>
                    <h3 style="margin: 0 0 10px 0; font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                    <div class="status-badge" style="background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border-color); font-size: 0.8rem; letter-spacing: 1px;">
                        <?= strtoupper(htmlspecialchars($_SESSION['user_role'])) ?>
                    </div>
                    
                    <div style="margin-top: 30px; width: 100%; pt-20; border-top: 1px solid var(--border-color); padding-top: 25px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem;">
                            <span style="color: var(--text-muted);">Account ID</span>
                            <span style="color: var(--text-main); font-weight: 700;">#<?= $user_id ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-muted);">Access Level</span>
                            <span style="color: var(--primary-color); font-weight: 700;">Verified</span>
                        </div>
                    </div>
                </div>

                <!-- PIN Change Section -->
                <div class="glass-card" style="padding: 40px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
                        <div style="width: 48px; height: 48px; background: rgba(249, 115, 22, 0.1); color: var(--primary-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Security Settings</h3>
                            <p style="margin:0; font-size: 0.85rem; color: var(--text-muted);">Update your login PIN regularly for safety</p>
                        </div>
                    </div>

                    <form method="POST" style="max-width: 500px;">
                        <!-- SECURITY FIX: CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; display: block;">Current Pin</label>
                            <input type="password" name="current_pin" required placeholder="••••" maxlength="8" class="form-control" style="font-size: 1.4rem; letter-spacing: 8px; font-family: monospace;">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; display: block;">New Pin</label>
                                <input type="password" name="new_pin" required placeholder="••••" maxlength="8" class="form-control" style="font-size: 1.4rem; letter-spacing: 8px; font-family: monospace;">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; display: block;">Confirm New</label>
                                <input type="password" name="confirm_pin" required placeholder="••••" maxlength="8" class="form-control" style="font-size: 1.4rem; letter-spacing: 8px; font-family: monospace;">
                            </div>
                        </div>

                        <button type="submit" name="update_pin" class="btn" style="width: 100%; height: 54px; font-size: 1rem; font-weight: 800; border-radius: 12px;">
                            <i class="fas fa-save" style="margin-right: 10px;"></i> Save New PIN
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
