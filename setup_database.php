<?php
// Database configuration
$host = 'localhost';
$dbname = 'art_marketplace';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    
    // Select the database
    $pdo->exec("USE $dbname");
    
    // Read setup.sql file content
    $sql = file_get_contents('database/setup.sql');
    
    // Remove the database creation line since we already created it
    $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
    
    // Execute the SQL script
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div class='container py-5'>";
    echo "<div class='card shadow-sm'>";
    echo "<div class='card-body'>";
    echo "<h2 class='card-title text-success'><i class='fas fa-check-circle me-2'></i>Database Setup Complete!</h2>";
    echo "<p class='card-text'>The database has been successfully created and populated with initial data.</p>";
    echo "<hr>";
    echo "<p>You can now:</p>";
    echo "<div class='d-flex gap-2'>";
    echo "<a href='index.php' class='btn btn-primary'><i class='fas fa-home me-2'></i>Go to Homepage</a>";
    echo "<a href='register.php' class='btn btn-outline-primary'><i class='fas fa-user-plus me-2'></i>Register a New Account</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='container py-5'>";
    echo "<div class='card shadow-sm border-danger'>";
    echo "<div class='card-body'>";
    echo "<h2 class='card-title text-danger'><i class='fas fa-exclamation-triangle me-2'></i>Database Setup Error</h2>";
    echo "<p class='card-text'>An error occurred while setting up the database:</p>";
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    echo "<hr>";
    echo "<a href='index.php' class='btn btn-primary'>Go to Homepage</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
?> 