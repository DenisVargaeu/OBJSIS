<?php
// migrate_inventory.php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Starting Inventory System migration...\n";

    // 1. Create inventory table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        current_quantity DECIMAL(10,2) DEFAULT 0.00,
        unit VARCHAR(20) DEFAULT 'pcs',
        critical_threshold DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Table 'inventory' created.\n";

    // 2. Create menu_item_ingredients table
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_item_ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_item_id INT NOT NULL,
        inventory_id INT NOT NULL,
        quantity_required DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
        FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
    )");
    echo "✓ Table 'menu_item_ingredients' created.\n";

    // 3. Create inventory_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT NOT NULL,
        user_id INT NULL,
        change_type ENUM('purchase', 'sale', 'waste', 'correction') NOT NULL,
        quantity_change DECIMAL(10,2) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
    )");
    echo "✓ Table 'inventory_logs' created.\n";

    // 4. Update users roles (Add 'inventory')
    // Note: Altering ENUM in MySQL
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cook', 'waiter', 'inventory') NOT NULL");
        echo "✓ User roles updated (added 'inventory').\n";
    } catch (Exception $e) {
        echo "! Could not update user roles (might already be updated or using restricted SQL): " . $e->getMessage() . "\n";
    }

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("❌ Migration failed: " . $e->getMessage() . "\n");
}
?>