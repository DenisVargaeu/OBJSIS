<?php
// login.php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY FIX: Brute-force protection
    if (!isset($_SESSION['failed_attempts'])) $_SESSION['failed_attempts'] = 0;
    if (!isset($_SESSION['last_attempt_time'])) $_SESSION['last_attempt_time'] = 0;

    $currentTime = time();
    $lockoutTime = 300; // 5 minutes

    if ($_SESSION['failed_attempts'] >= 5 && ($currentTime - $_SESSION['last_attempt_time']) < $lockoutTime) {
        $error = "Too many failed attempts. Please wait 5 minutes.";
    } else {
        $pin = $_POST['pin'] ?? '';

        if (!empty($pin)) {
            try {
                // SECURITY FIX: Limit user fetch to prevent OOM
                $stmt = $pdo->query("SELECT * FROM users LIMIT 100");
                $users = $stmt->fetchAll();

                $user_found = false;

                foreach ($users as $user) {
                    if (verifyPin($pin, $user['pin_hash'])) {
                        // SECURITY FIX: Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['failed_attempts'] = 0; // Reset on success

                        // Get Role and Permissions
                        $stmt_role = $pdo->prepare("
                            SELECT r.name as role_name, r.id as role_id, p.name as permission_name 
                            FROM roles r
                            LEFT JOIN role_permissions rp ON r.id = rp.role_id
                            LEFT JOIN permissions p ON rp.permission_id = p.id
                            WHERE r.id = ?
                        ");
                        $stmt_role->execute([$user['role_id']]);
                        $role_data = $stmt_role->fetchAll();

                        if ($role_data) {
                            $_SESSION['user_role'] = $role_data[0]['role_name'];
                            $_SESSION['role_id'] = $role_data[0]['role_id'];
                            $_SESSION['permissions'] = array_filter(array_column($role_data, 'permission_name'));
                        } else {
                            // Fallback to old system if role_id not set correctly
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['permissions'] = [];
                        }

                        $user_found = true;
                        break;
                    }
                }

                if ($user_found) {
                    redirect('admin/dashboard.php');
                } else {
                    $_SESSION['failed_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    
                    // SECURITY FIX: Add delay after 5 failures
                    if ($_SESSION['failed_attempts'] >= 5) {
                        sleep(3);
                        $error = "Too many failed attempts. Locked for 5 minutes.";
                    } else {
                        $error = "Invalid PIN";
                    }
                }

            } catch (PDOException $e) {
                die("DB ERROR: " . $e->getMessage());
            }
        } else {
            $error = "Please enter a PIN";
        }
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

            <?php if (!empty($error)): ?>
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

        document.addEventListener('keydown', (e) => {
            if (e.key >= '0' && e.key <= '9') {
                appendPin(e.key);
            } else if (e.key === 'Backspace') {
                pinInput.value = pinInput.value.slice(0, -1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (pinInput.value.length > 0) {
                    document.getElementById('loginForm').submit();
                }
            }
        });
    </script>
</body>

</html>