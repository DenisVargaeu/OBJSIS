<?php
// login.php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';

    if (!empty($pin)) {
        // Find user by PIN (in real app, compare hash)
        // For MVP phase 1, we will fetch all users and verify hash in PHP loops or assuming direct match if not hashed yet?
        // Let's assume we implement hash verification.

        // Fetch all users to find the matching PIN (inefficient but safe for hashed PINs without storing plain text search)
        // Or if we trust the hash is unique enough or we use a username to narrow it down? 
        // The requirement says "PIN login". Usually you enter a PIN.

        // For this MVP, let's Fetch user where... wait, we can't query by hash. 
        // We might need an ID + PIN, or just PIN. If just PIN, we need to hope it's unique or iterate.
        // Let's assume for MVP we fetch all users and check verifyPin.

        // Fetch user with Role details
        $stmt = $pdo->query("SELECT u.*, r.name as role_name, r.permissions FROM users u LEFT JOIN roles r ON u.role_id = r.id");
        $users = $stmt->fetchAll();

        $user_found = false;
        foreach ($users as $user) {
            // For MVP compatibility, if migration hasn't run or role_id is null, fall back to old role column
            // But we enforced migration.

            if (verifyPin($pin, $user['pin_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                // Use new role name, fallback to old if something weird happened
                $role_name = $user['role_name'] ?? $user['role'];
                $_SESSION['user_role'] = $role_name;

                // Decode permissions
                $permissions = isset($user['permissions']) ? json_decode($user['permissions'], true) : [];
                $_SESSION['permissions'] = $permissions;

                $user_found = true;
                break;
            }
        }

        if ($user_found) {
            redirect('admin/dashboard.php');
        } else {
            $error = "Invalid PIN";
        }
    } else {
        $error = "Please enter a PIN";
    }
}

$restaurant_name = getSetting('restaurant_name', 'OBJSIS');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($restaurant_name) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?= getCustomStyles() ?>
    <script src="assets/js/theme.js"></script>
</head>

<body>
    <div class="login-wrapper">
        <div class="card" style="width: 100%; max-width: 400px; text-align: center; padding: 40px;">
            <div style="margin-bottom: 30px;">
                <div
                    style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: var(--shadow-glow);">
                    <i class="fas fa-user-lock" style="font-size: 1.8rem; color: #fff;"></i>
                </div>
                <h1 style="font-size: 2rem; margin-bottom: 10px;"><?= htmlspecialchars($restaurant_name) ?></h1>
                <p style="color: var(--text-muted);">Please enter your PIN to continue</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="justify-content: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="password" name="pin" id="pinInput" class="pin-display" readonly>

                <div class="pin-pad">
                    <button type="button" class="pin-btn" onclick="appendPin('1')">1</button>
                    <button type="button" class="pin-btn" onclick="appendPin('2')">2</button>
                    <button type="button" class="pin-btn" onclick="appendPin('3')">3</button>
                    <button type="button" class="pin-btn" onclick="appendPin('4')">4</button>
                    <button type="button" class="pin-btn" onclick="appendPin('5')">5</button>
                    <button type="button" class="pin-btn" onclick="appendPin('6')">6</button>
                    <button type="button" class="pin-btn" onclick="appendPin('7')">7</button>
                    <button type="button" class="pin-btn" onclick="appendPin('8')">8</button>
                    <button type="button" class="pin-btn" onclick="appendPin('9')">9</button>
                    <button type="button" class="pin-btn"
                        style="background:rgba(239, 68, 68, 0.2); border-color:rgba(239, 68, 68, 0.3); color:#f87171;"
                        onclick="clearPin()">C</button>
                    <button type="button" class="pin-btn" onclick="appendPin('0')">0</button>
                    <button type="submit" class="pin-btn"
                        style="background:var(--primary-color); border-color:var(--primary-color);">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <div style="margin-top: 30px;">
                <a href="index.php" style="color: var(--text-muted); font-size: 0.9rem; opacity: 0.7;">
                    <i class="fas fa-arrow-left"></i> Back to Kiosk
                </a>
                <div style="margin-top: 15px; color: var(--text-muted); font-size: 0.7rem; opacity: 0.5;">
                    <?= OBJSIS_VERSION ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div onclick="toggleTheme()"
        style="position:fixed; top:20px; right:20px; width:40px; height:40px; background:var(--card-bg-glass); border:1px solid var(--border-color); border-radius:50%; display:flex; justify-content:center; align-items:center; cursor:pointer; z-index:2000; backdrop-filter:blur(5px); color:var(--text-muted); box-shadow:var(--shadow-sm);">
        <i class="fas fa-adjust"></i>
    </div>
    <script src="assets/js/theme.js"></script>

    <script>
        const pinInput = document.getElementById('pinInput');

        function appendPin(num) {
            if (pinInput.value.length < 6) {
                pinInput.value += num;
            }
        }

        function clearPin() {
            pinInput.value = '';
        }

        // Keyboard Support
        document.addEventListener('keydown', (e) => {
            if (e.key >= '0' && e.key <= '9') {
                appendPin(e.key);
            } else if (e.key === 'Backspace') {
                pinInput.value = pinInput.value.slice(0, -1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (pinInput.value.length > 0) document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>

</html>