<?php
// Direct SQL execution to fix orders table
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'se_project';
$username = 'root';
$password = '';

echo "<h2>Fixing Orders Table Structure</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Database connection successful.</p>";
    
    // Check if column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_address'");
    $columnExists = ($columnCheck->rowCount() > 0);
    
    if (!$columnExists) {
        echo "<p>The shipping_address column does not exist. Adding it now...</p>";
        
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER payment_method");
            echo "<p style='color:green;'>Success: The shipping_address column was added to the orders table.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error adding column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>The shipping_address column already exists in the orders table.</p>";
    }
    
    // Display the current table structure
    echo "<h3>Current Orders Table Structure:</h3>";
    $tableStructure = $pdo->query("DESCRIBE orders");
    $columns = $tableStructure->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . ($value === null ? "NULL" : htmlspecialchars($value)) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database connection failed: " . $e->getMessage() . "</p>";
}
?> 