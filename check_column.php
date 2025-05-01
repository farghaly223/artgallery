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
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Orders Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check if the column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_address'");
    $column_exists = ($stmt->rowCount() > 0);
    
    if ($column_exists) {
        echo "<p style='color:green;'><strong>Success:</strong> The shipping_address column exists in the orders table.</p>";
    } else {
        echo "<p style='color:red;'><strong>Error:</strong> The shipping_address column does not exist in the orders table.</p>";
    }
    
    // Execute the ALTER TABLE again to make sure the column exists
    echo "<h3>Attempting to add the column (if it doesn't exist):</h3>";
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER payment_method");
        echo "<p style='color:green;'>Column added successfully.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "<p style='color:blue;'>Column already exists.</p>";
        } else {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Get updated table structure
    $stmt = $pdo->query("DESCRIBE orders");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Updated Orders Table Structure:</h3>";
    echo "<pre>";
    print_r($updated_columns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?> 