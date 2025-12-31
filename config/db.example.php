<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'db.php' in the same directory
 * 2. Update the values below with your database credentials
 * 3. OR use the install.php wizard which will generate this automatically
 */

$host = 'localhost';
$db_name = 'objsis_v2';
$username = 'root';  // Change this to your MySQL username
$password = '';      // Change this to your MySQL password

define('OBJSIS_VERSION', 'beta 1.0');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>