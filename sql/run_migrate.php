<?php
// migrate.php - Run the RBAC migration

$host = 'localhost';
$db_name = 'objsis_v2';
$username = 'root';
$password = '';

// Try to load actual config if it exists (hidden or otherwise)
if (file_exists(__DIR__ . '/config/db.php')) {
    include __DIR__ . '/config/db.php';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents(__DIR__ . '/sql/migrate_rbac.sql');
    if (!$sql) {
        die("Error: migrate_rbac.sql not found.\n");
    }

    // Split by semicolon and run each query
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
            echo "Executed: " . substr($query, 0, 50) . "...\n";
        }
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
