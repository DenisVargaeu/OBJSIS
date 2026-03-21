<?php
/**
 * OBJSIS V2 - Interactive Installation Wizard
 * 
 * This installer will guide you through:
 * 1. Database configuration
 * 2. Restaurant setup
 * 3. Admin account creation
 * 4. Database table creation
 */

session_start();

// Check if already installed
if (file_exists('config/db.php') && !isset($_GET['force'])) {
    die("
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Already Installed</title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; background: #0f172a; color: #e2e8f0; text-align: center; }
            .container { background: #1e293b; padding: 40px; border-radius: 12px; }
            h1 { color: #f97316; }
            .btn { display: inline-block; padding: 12px 24px; background: #f97316; color: white; text-decoration: none; border-radius: 8px; margin: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>✅ Already Installed</h1>
            <p>OBJSIS V2 is already installed on this system.</p>
            <a href='login.php' class='btn'>Go to Login</a>
            <a href='install.php?force=1' class='btn' style='background: #64748b;'>Reinstall (Danger)</a>
        </div>
    </body>
    </html>
    ");
}

$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>OBJSIS V2 Installation Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }

        .wizard-container {
            max-width: 700px;
            margin: 40px auto;
            background: #1e293b;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            padding: 30px;
            text-align: center;
        }

        .wizard-header h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .wizard-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .progress-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: #22c55e;
            transition: width 0.3s ease;
        }

        .wizard-body {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .step-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
        }

        .step-dot.active {
            background: #f97316;
            color: white;
        }

        .step-dot.completed {
            background: #22c55e;
            color: white;
        }

        .step-dot::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
        }

        .step-dot:last-child::after {
            display: none;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f97316;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .btn {
            padding: 14px 28px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
        }

        .btn-secondary {
            background: #64748b;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
            color: #86efac;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid #3b82f6;
            color: #93c5fd;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .success-icon {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class='wizard-container'>
        <div class='wizard-header'>
            <h1>🚀 OBJSIS V2 Setup</h1>
            <p>Restaurant Management System Installation</p>
        </div>
        <div class='progress-bar'>
            <div class='progress-fill' style='width: <?= ($step / 4) * 100 ?>%'></div>
        </div>

        <div class='wizard-body'>
            <?php if ($step == 1): ?>
                <!-- Step 1: Database Configuration -->
                <div class='step-indicator'>
                    <div class='step-dot active'>1</div>
                    <div class='step-dot'>2</div>
                    <div class='step-dot'>3</div>
                    <div class='step-dot'>4</div>
                </div>

                <h2 style='margin-bottom: 10px;'>Database Configuration</h2>
                <p style='color: #94a3b8; margin-bottom: 30px;'>Enter your MySQL/MariaDB database credentials</p>

                <form method='POST' action='install.php?step=2'>
                    <div class='form-group'>
                        <label>Database Host</label>
                        <input type='text' name='db_host' value='localhost' required>
                        <small>Usually 'localhost' for local installations</small>
                    </div>

                    <div class='form-group'>
                        <label>Database Name</label>
                        <input type='text' name='db_name' value='objsis_v2' required>
                        <small>The database will be created if it doesn't exist</small>
                    </div>

                    <div class='form-group'>
                        <label>Database Username</label>
                        <input type='text' name='db_user' value='root' required>
                        <small>MySQL username (default: root for XAMPP)</small>
                    </div>

                    <div class='form-group'>
                        <label>Database Password</label>
                        <input type='password' name='db_pass' value=''>
                        <small>Leave empty if no password (default for XAMPP)</small>
                    </div>

                    <div class='button-group'>
                        <button type='submit' class='btn'>Test Connection & Continue →</button>
                    </div>
                </form>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Test Connection & Create Database -->
                <?php
                $db_host = $_POST['db_host'] ?? '';
                $db_name = $_POST['db_name'] ?? '';
                $db_user = $_POST['db_user'] ?? '';
                $db_pass = $_POST['db_pass'] ?? '';

                $_SESSION['db_config'] = compact('db_host', 'db_name', 'db_user', 'db_pass');

                $connection_success = false;
                $error_message = '';

                try {
                    // Test connection without database
                    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Create database if not exists
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$db_name`");

                    $connection_success = true;
                } catch (PDOException $e) {
                    $error_message = $e->getMessage();
                }
                ?>

                <div class='step-indicator'>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot active'>2</div>
                    <div class='step-dot'>3</div>
                    <div class='step-dot'>4</div>
                </div>

                <?php if ($connection_success): ?>
                    <div class='alert alert-success'>
                        ✅ Database connection successful!
                    </div>

                    <h2 style='margin-bottom: 10px;'>Restaurant Information</h2>
                    <p style='color: #94a3b8; margin-bottom: 30px;'>Configure your restaurant details</p>

                    <form method='POST' action='install.php?step=3'>
                        <div class='form-group'>
                            <label>Restaurant Name</label>
                            <input type='text' name='restaurant_name' value='My Restaurant' required>
                            <small>This will appear on receipts and the customer kiosk</small>
                        </div>

                        <div class='form-group'>
                            <label>Admin PIN (4-6 digits)</label>
                            <input type='text' name='admin_pin' value='1234' pattern='[0-9]{4,6}' required>
                            <small>You'll use this to login as admin. Can be changed later.</small>
                        </div>

                        <div class='form-group'>
                            <label>Admin Name</label>
                            <input type='text' name='admin_name' value='Admin' required>
                            <small>Display name for the admin account</small>
                        </div>

                        <div class='button-group'>
                            <a href='install.php?step=1' class='btn btn-secondary'>← Back</a>
                            <button type='submit' class='btn'>Continue →</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class='alert alert-error'>
                        ❌ Database connection failed: <?= htmlspecialchars($error_message) ?>
                    </div>

                    <div class='button-group'>
                        <a href='install.php?step=1' class='btn'>← Try Again</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Create Tables and Data -->
                <?php
                $restaurant_name = $_POST['restaurant_name'] ?? 'My Restaurant';
                $admin_pin = $_POST['admin_pin'] ?? '1234';
                $admin_name = $_POST['admin_name'] ?? 'Admin';

                $_SESSION['restaurant_config'] = compact('restaurant_name', 'admin_pin', 'admin_name');

                $config = $_SESSION['db_config'];
                extract($config);

                $errors = [];
                $success_count = 0;

                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Create tables
                    $tables = [
                        'users' => "CREATE TABLE IF NOT EXISTS users (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(50) NOT NULL,
                            pin_hash VARCHAR(255) NOT NULL,
                            role ENUM('admin', 'cook', 'waiter', 'inventory') NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )",
                        'categories' => "CREATE TABLE IF NOT EXISTS categories (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            sort_order INT DEFAULT 0
                        )",
                        'menu_items' => "CREATE TABLE IF NOT EXISTS menu_items (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            category_id INT,
                            name VARCHAR(100) NOT NULL,
                            description TEXT,
                            price DECIMAL(10, 2) NOT NULL,
                            image_url VARCHAR(255),
                            is_available BOOLEAN DEFAULT TRUE,
                            allergens VARCHAR(255),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
                        )",
                        'tables' => "CREATE TABLE IF NOT EXISTS tables (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(50) NOT NULL,
                            capacity INT DEFAULT 4,
                            status ENUM('free', 'occupied', 'reserved') DEFAULT 'free',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )",
                        'orders' => "CREATE TABLE IF NOT EXISTS orders (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            table_number INT NOT NULL,
                            status ENUM('received', 'preparing', 'ready', 'delivered', 'paid', 'cancelled') DEFAULT 'received',
                            total_price DECIMAL(10, 2) DEFAULT 0.00,
                            discount_amount DECIMAL(10, 2) DEFAULT 0.00,
                            coupon_code VARCHAR(20) DEFAULT NULL,
                            note TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )",
                        'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            order_id INT NOT NULL,
                            menu_item_id INT NOT NULL,
                            quantity INT DEFAULT 1,
                            price_at_time DECIMAL(10, 2) NOT NULL,
                            note TEXT,
                            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
                        )",
                        'coupons' => "CREATE TABLE IF NOT EXISTS coupons (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            code VARCHAR(20) NOT NULL UNIQUE,
                            type ENUM('fixed', 'percent') NOT NULL,
                            value DECIMAL(10, 2) NOT NULL,
                            is_active BOOLEAN DEFAULT TRUE,
                            expiration_date DATETIME DEFAULT NULL,
                            max_uses INT DEFAULT NULL,
                            current_uses INT DEFAULT 0,
                            one_time_use BOOLEAN DEFAULT FALSE,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )",
                        'shifts' => "CREATE TABLE IF NOT EXISTS shifts (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                            end_time DATETIME DEFAULT NULL,
                            cash_start DECIMAL(10, 2) DEFAULT 0.00,
                            cash_end DECIMAL(10, 2) DEFAULT 0.00,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        )",
                        'settings' => "CREATE TABLE IF NOT EXISTS settings (
                            setting_key VARCHAR(50) PRIMARY KEY,
                            setting_value TEXT,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )",
                        'inventory' => "CREATE TABLE IF NOT EXISTS inventory (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            current_quantity DECIMAL(10,2) DEFAULT 0.00,
                            unit VARCHAR(20) DEFAULT 'pcs',
                            critical_threshold DECIMAL(10,2) DEFAULT 0.00,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )",
                        'menu_item_ingredients' => "CREATE TABLE IF NOT EXISTS menu_item_ingredients (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            menu_item_id INT NOT NULL,
                            inventory_id INT NOT NULL,
                            quantity_required DECIMAL(10,2) NOT NULL,
                            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
                            FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
                        )",
                        'inventory_logs' => "CREATE TABLE IF NOT EXISTS inventory_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            inventory_id INT NOT NULL,
                            user_id INT,
                            change_type ENUM('purchase', 'sale', 'waste', 'correction') NOT NULL,
                            quantity_change DECIMAL(10,2) NOT NULL,
                            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
                        )"
                    ];

                    foreach ($tables as $name => $sql) {
                        $pdo->exec($sql);
                        $success_count++;
                    }

                    // --- Schema Synchronization (Robustness Fix) ---
                    // Ensure the 'shifts' table has the correct columns (converting old schema if needed)
                    try {
                        $stmt = $pdo->query("SHOW COLUMNS FROM shifts");
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        // Handle rename clock_in -> start_time
                        if (in_array('clock_in', $columns) && !in_array('start_time', $columns)) {
                            $pdo->exec("ALTER TABLE shifts CHANGE COLUMN clock_in start_time DATETIME DEFAULT CURRENT_TIMESTAMP");
                        }
                        // Handle rename clock_out -> end_time
                        if (in_array('clock_out', $columns) && !in_array('end_time', $columns)) {
                            $pdo->exec("ALTER TABLE shifts CHANGE COLUMN clock_out end_time DATETIME DEFAULT NULL");
                        }
                        // Ensure cash columns exist
                        if (!in_array('cash_start', $columns)) {
                            $pdo->exec("ALTER TABLE shifts ADD COLUMN cash_start DECIMAL(10, 2) DEFAULT 0.00");
                        }
                        if (!in_array('cash_end', $columns)) {
                            $pdo->exec("ALTER TABLE shifts ADD COLUMN cash_end DECIMAL(10, 2) DEFAULT 0.00");
                        }
                    } catch (PDOException $e) {
                        // Table might not exist or other issue, safe to ignore for fresh installs
                    }
                    // -----------------------------------------------
            
                    // Insert admin user
                    require_once 'includes/functions.php';
                    $pin_hash = hashPin($admin_pin);
                    $pdo->exec("INSERT IGNORE INTO users (name, pin_hash, role) VALUES ('$admin_name', '$pin_hash', 'admin')");

                    // Insert settings
                    $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('restaurant_name', '$restaurant_name') ON DUPLICATE KEY UPDATE setting_value = '$restaurant_name'");

                    // Insert sample data
                    $pdo->exec("INSERT IGNORE INTO categories (id, name, sort_order) VALUES 
                        (1, 'Appetizers', 1),
                        (2, 'Main Courses', 2),
                        (3, 'Desserts', 3),
                        (4, 'Beverages', 4)
                    ");

                    $pdo->exec("INSERT IGNORE INTO tables (id, name, capacity) VALUES 
                        (1, 'Table 1', 4),
                        (2, 'Table 2', 4),
                        (3, 'Table 3', 2),
                        (4, 'Table 4', 6),
                        (5, 'Table 5', 4)
                    ");

                } catch (PDOException $e) {
                    $errors[] = $e->getMessage();
                }
                ?>

                <div class='step-indicator'>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot active'>3</div>
                    <div class='step-dot'>4</div>
                </div>

                <h2 style='margin-bottom: 20px;'>Installing Database...</h2>

                <?php if (empty($errors)): ?>
                    <div class='alert alert-success'>
                        ✅ Created <?= $success_count ?> database tables<br>
                        ✅ Admin account configured<br>
                        ✅ Sample data inserted
                    </div>

                    <form method='POST' action='install.php?step=4'>
                        <div class='button-group'>
                            <button type='submit' class='btn'>Finish Installation →</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class='alert alert-error'>
                        ❌ Installation errors:<br>
                        <?php foreach ($errors as $err): ?>
                            • <?= htmlspecialchars($err) ?><br>
                        <?php endforeach; ?>
                    </div>
                    <div class='button-group'>
                        <a href='install.php?step=1' class='btn'>← Start Over</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($step == 4): ?>
                <!-- Step 4: Generate Config File & Complete -->
                <?php
                $db_config = $_SESSION['db_config'];
                $restaurant_config = $_SESSION['restaurant_config'];

                // Generate config/db.php
                $config_content = "<?php
// config/db.php
// Auto-generated by OBJSIS V2 Installer

\$host = '{$db_config['db_host']}';
\$db_name = '{$db_config['db_name']}';
\$username = '{$db_config['db_user']}';
\$password = '{$db_config['db_pass']}';



try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$db_name;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die(\"ERROR: Could not connect. \" . \$e->getMessage());
}
?>";

                $config_written = file_put_contents('config/db.php', $config_content);

                // Clean up temp files
                $temp_files = ['update_orders_schema.php', 'update_coupons_schema.php', 'fix_settings_table.php', 'setup_db.php'];
                foreach ($temp_files as $file) {
                    if (file_exists($file))
                        @unlink($file);
                }

                session_destroy();
                ?>

                <div class='step-indicator'>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot completed'>✓</div>
                    <div class='step-dot completed'>✓</div>
                </div>

                <div style='text-align: center;'>
                    <div class='success-icon'>🎉</div>
                    <h2 style='color: #22c55e; margin-bottom: 10px;'>Installation Complete!</h2>
                    <p style='color: #94a3b8; margin-bottom: 30px;'>OBJSIS V2 is ready to use</p>

                    <div class='alert alert-info' style='text-align: left;'>
                        <strong>📋 Your Login Credentials:</strong><br><br>
                        <strong>Admin PIN:</strong> <?= htmlspecialchars($restaurant_config['admin_pin']) ?><br>
                        <strong>Admin Name:</strong> <?= htmlspecialchars($restaurant_config['admin_name']) ?><br>
                        <strong>Restaurant:</strong> <?= htmlspecialchars($restaurant_config['restaurant_name']) ?>
                    </div>

                    <div class='alert alert-error' style='text-align: left;'>
                        <strong>🔐 Security Reminder:</strong><br>
                        Please delete <code>install.php</code> from your server for security!
                    </div>

                    <div class='button-group' style='justify-content: center;'>
                        <a href='login.php' class='btn'>Go to Login Page →</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>