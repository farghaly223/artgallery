<?php
// Database connection parameters
$dbHost = 'localhost';
$dbName = 'se_project';
$dbUser = 'root';
$dbPass = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_address'");
    $column_exists = ($stmt->rowCount() > 0);
    
    if (!$column_exists) {
        // Add shipping_address column
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER payment_method");
        echo "Success: The shipping_address column has been added to the orders table.";
    } else {
        echo "Info: The shipping_address column already exists in the orders table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 